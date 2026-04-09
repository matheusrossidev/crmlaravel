import json
import re

import httpx

# Default fallback caso o caller nao passe max_block (retro-compat).
# Cada agente tem o seu max_message_length no banco — esse default so
# entra em uso se alguem chamar a funcao sem especificar.
DEFAULT_MAX_BLOCK = 150

_PROMPT = """Você vai receber uma resposta de um assistente de WhatsApp.
Divida ela em blocos humanizados respeitando o limite de caracteres.

REGRAS:
- Máximo {max_block} caracteres por bloco — use esse limite com sabedoria.
- Se o texto cabe em poucos blocos, prefira blocos MAIORES com ideias completas
  ao invés de picotar tudo em pedaços minusculos.
- Nunca corte uma frase no meio — cada bloco é uma ideia COMPLETA.
- Itens de uma lista podem ficar no mesmo bloco se couberem dentro do limite.
- Preserve emojis e formatação natural.

Retorne SOMENTE este JSON (sem texto extra antes ou depois):
{{"reply_blocks": ["bloco 1", "bloco 2", "bloco 3"]}}

TEXTO A FORMATAR:
{text}"""


async def format_as_whatsapp_blocks(
    text: str,
    provider: str,
    model: str,
    api_key: str,
    max_block: int = DEFAULT_MAX_BLOCK,
) -> list[str] | None:
    """
    Second-pass LLM that receives the raw agent response and splits it into
    humanized WhatsApp blocks respecting max_block chars each.

    max_block vem do max_message_length do agent (config). Cada agente pode
    ter seu proprio limite — Camila clinica usa ~700 pra explicar procedimentos
    detalhadamente, Sophia comercial usa ~200 pra ser mais punchy.

    Returns None on any failure so the caller keeps the original blocks.
    """
    if not text.strip():
        return None

    prompt = _PROMPT.format(max_block=max_block, text=text.strip())

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
