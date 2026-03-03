import json
import os
import re
from contextlib import asynccontextmanager

from fastapi import FastAPI, HTTPException

from agent_factory import AgnoReply, get_agent_config, get_or_create_agent, store_agent_config
from formatter import format_as_whatsapp_blocks
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

        # Second-pass formatter: a dedicated LLM call that only splits and
        # humanizes the text — much more reliable than prompt instructions alone.
        config = get_agent_config(req.agent_id)
        if config:
            raw_text = " ".join(reply_blocks)
            formatted = await format_as_whatsapp_blocks(
                text=raw_text,
                provider=config.get("llm_provider", "openai"),
                model=config.get("llm_model", "gpt-4o-mini"),
                api_key=config.get("llm_api_key", ""),
            )
            if formatted:
                reply_blocks = formatted

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


MAX_BLOCK_CHARS = 150


def _split_long_block(text: str) -> list[str]:
    """
    Split a block that exceeds MAX_BLOCK_CHARS at natural sentence boundaries.
    Never cuts mid-word or mid-sentence. Falls back to splitting at the last
    space before the limit if no sentence boundary is found.
    """
    if len(text) <= MAX_BLOCK_CHARS:
        return [text]

    results = []
    remaining = text

    # Sentence-ending punctuation patterns (highest priority split points)
    sentence_ends = re.compile(r'(?<=[.!?])\s+')
    # Clause boundaries (lower priority)
    clause_ends = re.compile(r'(?<=[,;])\s+')

    while len(remaining) > MAX_BLOCK_CHARS:
        chunk = remaining[:MAX_BLOCK_CHARS + 1]

        # 1. Try to split at a sentence boundary within the limit
        cut = -1
        for m in sentence_ends.finditer(chunk):
            if m.start() <= MAX_BLOCK_CHARS:
                cut = m.start()
        if cut > 0:
            results.append(remaining[:cut].strip())
            remaining = remaining[cut:].strip()
            continue

        # 2. Try clause boundary
        for m in clause_ends.finditer(chunk):
            if m.start() <= MAX_BLOCK_CHARS:
                cut = m.start()
        if cut > 0:
            results.append(remaining[:cut].strip())
            remaining = remaining[cut:].strip()
            continue

        # 3. Last resort: split at last space before the limit
        space = chunk.rfind(' ')
        if space > 0:
            results.append(remaining[:space].strip())
            remaining = remaining[space:].strip()
        else:
            # No space found — hard cut (should not happen in practice)
            results.append(remaining[:MAX_BLOCK_CHARS])
            remaining = remaining[MAX_BLOCK_CHARS:]

    if remaining:
        results.append(remaining)

    return [r for r in results if r]


def _enforce_max_length(blocks: list[str]) -> list[str]:
    """Pass every block through _split_long_block to guarantee MAX_BLOCK_CHARS."""
    result = []
    for block in blocks:
        result.extend(_split_long_block(block.strip()))
    return [b for b in result if b]


def _extract_reply_blocks(result) -> list[str]:
    """
    Extract reply_blocks from the agent result, then enforce MAX_BLOCK_CHARS.

    Priority order:
    1. Parsed AgnoReply from response_model (most reliable — structured output)
    2. JSON string in result.content
    3. Fallback: split by newlines
    """
    # 1. response_model already parsed — result.content is AgnoReply instance
    if isinstance(result.content, AgnoReply):
        blocks = [b.strip() for b in result.content.reply_blocks if b.strip()]
        return _enforce_max_length(blocks)

    # 2. response_model parsed to dict
    if isinstance(result.content, dict):
        blocks = result.content.get("reply_blocks", [])
        if isinstance(blocks, list) and blocks:
            return _enforce_max_length([str(b).strip() for b in blocks if str(b).strip()])

    content = result.content if isinstance(result.content, str) else ""

    if not content:
        return [""]

    # 3. JSON string — try multiple extraction patterns
    # Pattern A: clean JSON object
    try:
        data = json.loads(content)
        blocks = data.get("reply_blocks", [])
        if isinstance(blocks, list) and blocks:
            return _enforce_max_length([str(b).strip() for b in blocks if str(b).strip()])
    except Exception:
        pass

    # Pattern B: JSON embedded in text (e.g. markdown code block or prose)
    try:
        match = re.search(r'"reply_blocks"\s*:\s*(\[.*?\])', content, re.DOTALL)
        if match:
            blocks = json.loads(match.group(1))
            if isinstance(blocks, list) and blocks:
                return _enforce_max_length([str(b).strip() for b in blocks if str(b).strip()])
    except Exception:
        pass

    # 4. Fallback: split by ---SPLIT--- or blank lines
    if "---SPLIT---" in content:
        parts = [p.strip() for p in content.split("---SPLIT---")]
        return _enforce_max_length([p for p in parts if p])

    parts = [p.strip() for p in re.split(r"\n{2,}", content)]
    return _enforce_max_length([p for p in parts if p] or [content.strip()])
