import json
import os
import re
from contextlib import asynccontextmanager

from fastapi import FastAPI, HTTPException

from agent_factory import AgnoReply, get_or_create_agent, store_agent_config
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
            lead_id=None,
            conversation_id=req.conversation_id,
        )

        result = await agent.arun(
            message=req.message,
            user_id=f"tenant_{req.tenant_id}_contact_{req.contact_phone}",
        )

        reply_blocks = _extract_reply_blocks(result)

        tokens_prompt = 0
        tokens_completion = 0
        tokens_total = 0
        model_name = ""

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
        )

    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/agents/{agent_id}/configure")
async def configure(agent_id: int, req: ConfigureRequest) -> dict:
    store_agent_config(agent_id, req.model_dump())
    return {"ok": True, "agent_id": agent_id}


@app.post("/agents/{agent_id}/index-file")
async def index_file(agent_id: int, req: IndexFileRequest) -> dict:
    return {"ok": True, "agent_id": agent_id, "filename": req.filename, "note": "RAG indexing not yet enabled"}


def _extract_reply_blocks(result) -> list[str]:
    """
    Extract reply_blocks from the agent result.

    Priority order:
    1. Parsed AgnoReply from response_model (most reliable — structured output)
    2. JSON string in result.content
    3. Fallback: split by newlines
    """
    # 1. response_model already parsed — result.content is AgnoReply instance
    if isinstance(result.content, AgnoReply):
        blocks = result.content.reply_blocks
        return [b.strip() for b in blocks if b.strip()]

    # 2. response_model parsed to dict
    if isinstance(result.content, dict):
        blocks = result.content.get("reply_blocks", [])
        if isinstance(blocks, list) and blocks:
            return [str(b).strip() for b in blocks if str(b).strip()]

    content = result.content if isinstance(result.content, str) else ""

    if not content:
        return [""]

    # 3. JSON string — try multiple extraction patterns
    # Pattern A: clean JSON object
    try:
        data = json.loads(content)
        blocks = data.get("reply_blocks", [])
        if isinstance(blocks, list) and blocks:
            return [str(b).strip() for b in blocks if str(b).strip()]
    except Exception:
        pass

    # Pattern B: JSON embedded in text (e.g. markdown code block or prose)
    try:
        match = re.search(r'"reply_blocks"\s*:\s*(\[.*?\])', content, re.DOTALL)
        if match:
            blocks = json.loads(match.group(1))
            if isinstance(blocks, list) and blocks:
                return [str(b).strip() for b in blocks if str(b).strip()]
    except Exception:
        pass

    # 4. Fallback: split by ---SPLIT--- or blank lines
    if "---SPLIT---" in content:
        parts = [p.strip() for p in content.split("---SPLIT---")]
        return [p for p in parts if p]

    parts = [p.strip() for p in re.split(r"\n{2,}", content)]
    return [p for p in parts if p] or [content.strip()]
