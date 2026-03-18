import json
import os
import re
from contextlib import asynccontextmanager

from fastapi import FastAPI, HTTPException

from agent_factory import AgnoReply, get_agent_config, get_or_create_agent, store_agent_config
from formatter import format_as_whatsapp_blocks
from memory_store import init_memory_tables, search_memories, store_memory
from schemas import (
    AgentResponse,
    ChatRequest,
    ConfigureRequest,
    IndexFileRequest,
    SearchMemoryRequest,
    StoreMemoryRequest,
)


@asynccontextmanager
async def lifespan(app: FastAPI):
    print("Agno service starting...")
    try:
        init_memory_tables()
        print("Memory tables initialized.")
    except Exception as e:
        print(f"Warning: memory tables init failed: {e}")
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
            memories=req.memories if req.memories else None,
            lead_data=req.lead_data,
            custom_fields=req.custom_fields if req.custom_fields else None,
            lead_notes=req.lead_notes if req.lead_notes else None,
        )

        # Build input with conversation history for context
        if req.history:
            history_text = "\n".join(
                f"{'Cliente' if m.role == 'user' else 'Você'}: {m.content}"
                for m in req.history[-20:]
            )
            full_input = f"[HISTÓRICO DA CONVERSA]\n{history_text}\n\n[MENSAGEM ATUAL]\n{req.message}"
        else:
            full_input = req.message

        result = await agent.arun(
            input=full_input,
            user_id=f"tenant_{req.tenant_id}_contact_{req.contact_phone}",
        )

        reply_blocks, actions = _extract_reply_and_actions(result)

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
            actions=actions,
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


@app.post("/agents/{agent_id}/memories/store")
async def store_agent_memory(agent_id: int, req: StoreMemoryRequest) -> dict:
    memory_id = await store_memory(
        tenant_id=req.tenant_id,
        agent_id=agent_id,
        summary=req.summary,
        conversation_id=req.conversation_id,
        contact_phone=req.contact_phone,
        customer_profile=req.customer_profile,
        key_learnings=req.key_learnings,
    )
    if memory_id is None:
        raise HTTPException(status_code=500, detail="Failed to store memory")
    return {"ok": True, "memory_id": memory_id}


@app.post("/agents/{agent_id}/memories/search")
async def search_agent_memories(agent_id: int, req: SearchMemoryRequest) -> dict:
    results = await search_memories(
        tenant_id=req.tenant_id,
        agent_id=agent_id,
        query=req.query,
        top_k=req.top_k,
        contact_phone=req.contact_phone,
    )
    return {"ok": True, "memories": results}


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


def _extract_reply_and_actions(result) -> tuple[list[str], list[dict]]:
    """
    Extract reply_blocks and actions from the agent result.

    Priority order:
    1. Parsed AgnoReply from response_model (most reliable — structured output)
    2. JSON string in result.content
    3. Fallback: split by newlines
    """
    actions = []

    # 1. response_model already parsed — result.content is AgnoReply instance
    if isinstance(result.content, AgnoReply):
        blocks = [b.strip() for b in result.content.reply_blocks if b.strip()]
        actions = [a.model_dump() for a in result.content.actions] if result.content.actions else []
        return (_enforce_max_length(blocks), actions)

    # 2. response_model parsed to dict
    if isinstance(result.content, dict):
        blocks = result.content.get("reply_blocks", [])
        actions = result.content.get("actions", [])
        if isinstance(blocks, list) and blocks:
            return (_enforce_max_length([str(b).strip() for b in blocks if str(b).strip()]), actions)

    content = result.content if isinstance(result.content, str) else ""

    if not content:
        return ([""], [])

    # 3. JSON string — try multiple extraction patterns
    # Pattern A: clean JSON object
    try:
        data = json.loads(content)
        blocks = data.get("reply_blocks", [])
        actions = data.get("actions", [])
        if isinstance(blocks, list) and blocks:
            return (_enforce_max_length([str(b).strip() for b in blocks if str(b).strip()]), actions)
    except Exception:
        pass

    # Pattern B: JSON embedded in text (e.g. markdown code block or prose)
    try:
        match = re.search(r'"reply_blocks"\s*:\s*(\[.*?\])', content, re.DOTALL)
        if match:
            blocks = json.loads(match.group(1))
            if isinstance(blocks, list) and blocks:
                # Try to extract actions too
                act_match = re.search(r'"actions"\s*:\s*(\[.*?\])', content, re.DOTALL)
                if act_match:
                    try:
                        actions = json.loads(act_match.group(1))
                    except Exception:
                        pass
                return (_enforce_max_length([str(b).strip() for b in blocks if str(b).strip()]), actions)
    except Exception:
        pass

    # 4. Fallback: split by ---SPLIT--- or blank lines
    if "---SPLIT---" in content:
        parts = [p.strip() for p in content.split("---SPLIT---")]
        return (_enforce_max_length([p for p in parts if p]), [])

    parts = [p.strip() for p in re.split(r"\n{2,}", content)]
    return (_enforce_max_length([p for p in parts if p] or [content.strip()]), [])
