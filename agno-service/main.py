import os
import re
from contextlib import asynccontextmanager

from fastapi import FastAPI, HTTPException
from fastapi.responses import JSONResponse

from agent_factory import get_or_create_agent, store_agent_config
from schemas import AgentResponse, ChatRequest, ConfigureRequest, IndexFileRequest


@asynccontextmanager
async def lifespan(app: FastAPI):
    print("Agno service starting...")
    yield
    print("Agno service shutting down.")


app = FastAPI(title="Agno AI Service", version="1.0.0", lifespan=lifespan)


@app.get("/health")
async def health() -> dict:
    return {"status": "ok"}


@app.post("/chat", response_model=AgentResponse)
async def chat(req: ChatRequest) -> AgentResponse:
    try:
        agent = get_or_create_agent(
            agent_id=req.agent_id,
            tenant_id=req.tenant_id,
            pipeline_stages=req.pipeline_stages,
            available_tags=req.available_tags,
            lead_id=None,   # lead_id not available in initial phase; added in Phase 4
            conversation_id=req.conversation_id,
        )

        result = await agent.arun(
            message=req.message,
            user_id=f"tenant_{req.tenant_id}_contact_{req.contact_phone}",
        )

        # Extract reply_blocks from response
        reply_blocks = _extract_reply_blocks(result.content)

        # Extract token usage if available
        tokens_prompt = 0
        tokens_completion = 0
        tokens_total = 0
        model_name = ""
        provider_name = ""

        if hasattr(result, "metrics") and result.metrics:
            m = result.metrics
            tokens_prompt = getattr(m, "input_tokens", 0) or 0
            tokens_completion = getattr(m, "output_tokens", 0) or 0
            tokens_total = tokens_prompt + tokens_completion

        if hasattr(result, "model") and result.model:
            model_name = str(result.model)

        return AgentResponse(
            reply_blocks=reply_blocks,
            tokens_prompt=tokens_prompt,
            tokens_completion=tokens_completion,
            tokens_total=tokens_total,
            model=model_name,
            provider=provider_name,
        )

    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/agents/{agent_id}/configure")
async def configure(agent_id: int, req: ConfigureRequest) -> dict:
    """Store agent configuration and invalidate agent cache."""
    store_agent_config(agent_id, req.model_dump())
    return {"ok": True, "agent_id": agent_id}


@app.post("/agents/{agent_id}/index-file")
async def index_file(agent_id: int, req: IndexFileRequest) -> dict:
    """Index a knowledge file into the agent's vector store (Phase 3 — RAG)."""
    # Phase 3 placeholder: PgVector indexing will be added here
    return {"ok": True, "agent_id": agent_id, "filename": req.filename, "note": "RAG indexing not yet enabled"}


def _extract_reply_blocks(content: str | None) -> list[str]:
    """
    Parse the LLM response into a list of message blocks.

    The agent is instructed to return JSON with reply_blocks.
    Falls back to splitting by blank lines if JSON parsing fails.
    """
    if not content:
        return [""]

    # Try to extract JSON reply_blocks
    try:
        import json
        # Find JSON object in the response
        match = re.search(r'\{.*"reply_blocks"\s*:\s*\[.*?\].*?\}', content, re.DOTALL)
        if match:
            data = json.loads(match.group())
            blocks = data.get("reply_blocks", [])
            if isinstance(blocks, list) and blocks:
                return [str(b).strip() for b in blocks if str(b).strip()]
    except Exception:
        pass

    # Fallback: split by ---SPLIT--- or double newlines
    if "---SPLIT---" in content:
        parts = [p.strip() for p in content.split("---SPLIT---")]
        return [p for p in parts if p]

    # Fallback: split by double newlines (paragraph breaks)
    parts = [p.strip() for p in re.split(r"\n{2,}", content)]
    return [p for p in parts if p] or [content.strip()]
