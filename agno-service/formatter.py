import json
import re

import httpx

MAX_BLOCK = 150

_PROMPT = """Você vai receber uma resposta de um assistente de WhatsApp.
Divida ela em múltiplos blocos CURTOS e humanizados.

REGRAS:
- Máximo {max_block} caracteres por bloco
- Nunca corte uma frase no meio — cada bloco é uma ideia COMPLETA
- Cada item de uma lista = 1 bloco separado
- Blocos curtos e diretos, como uma pessoa digitando no celular
- Preserve emojis

Retorne SOMENTE este JSON (sem texto extra antes ou depois):
{{"reply_blocks": ["bloco 1", "bloco 2", "bloco 3"]}}

TEXTO A FORMATAR:
{text}"""


async def format_as_whatsapp_blocks(
    text: str,
    provider: str,
    model: str,
    api_key: str,
) -> list[str] | None:
    """
    Second-pass LLM that receives the raw agent response and splits it into
    short humanized WhatsApp blocks (≤ MAX_BLOCK chars each).

    Returns None on any failure so the caller keeps the original blocks.
    """
    if not text.strip():
        return None

    prompt = _PROMPT.format(max_block=MAX_BLOCK, text=text.strip())

    try:
        if provider == "anthropic":
            return await _call_anthropic(prompt, model, api_key)
        return await _call_openai(prompt, model, api_key)
    except Exception:
        return None


async def _call_openai(prompt: str, model: str, api_key: str) -> list[str]:
    async with httpx.AsyncClient(timeout=20) as client:
        r = await client.post(
            "https://api.openai.com/v1/chat/completions",
            headers={"Authorization": f"Bearer {api_key}"},
            json={
                "model": model,
                "messages": [{"role": "user", "content": prompt}],
                "response_format": {"type": "json_object"},
                "temperature": 0,
                "max_tokens": 800,
            },
        )
        r.raise_for_status()
        content = r.json()["choices"][0]["message"]["content"]
        blocks = json.loads(content).get("reply_blocks", [])
        return [b.strip() for b in blocks if b.strip()]


async def _call_anthropic(prompt: str, model: str, api_key: str) -> list[str]:
    async with httpx.AsyncClient(timeout=20) as client:
        r = await client.post(
            "https://api.anthropic.com/v1/messages",
            headers={
                "x-api-key": api_key,
                "anthropic-version": "2023-06-01",
            },
            json={
                "model": model,
                "max_tokens": 800,
                "messages": [{"role": "user", "content": prompt}],
                "temperature": 0,
            },
        )
        r.raise_for_status()
        text = r.json()["content"][0]["text"]
        match = re.search(r'\{.*"reply_blocks".*\}', text, re.DOTALL)
        if not match:
            raise ValueError("No JSON found in Anthropic response")
        blocks = json.loads(match.group()).get("reply_blocks", [])
        return [b.strip() for b in blocks if b.strip()]
