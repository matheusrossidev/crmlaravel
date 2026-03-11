import os
from typing import Any

from agno.agent import Agent
from agno.db.postgres import PostgresDb
from pydantic import BaseModel

PGVECTOR_URL = os.getenv("PGVECTOR_URL", "postgresql://agno:agno@pgvector:5432/agno")


class AgnoReply(BaseModel):
    """Structured output enforced at API level — each item is one WhatsApp message."""
    reply_blocks: list[str]


# In-memory cache: agent_key -> Agent instance
_agent_cache: dict[str, Agent] = {}
# In-memory config store: agent_id -> ConfigureRequest dict
_agent_configs: dict[int, dict[str, Any]] = {}


def store_agent_config(agent_id: int, config: dict[str, Any]) -> None:
    """Store agent config and invalidate cache so next call rebuilds the agent."""
    _agent_configs[agent_id] = config
    _agent_cache.pop(_make_key(agent_id, config.get("tenant_id", 0)), None)


def get_agent_config(agent_id: int) -> dict:
    """Return stored config for a given agent_id, or empty dict if not configured."""
    return _agent_configs.get(agent_id, {})


def _make_key(agent_id: int, tenant_id: int) -> str:
    return f"{tenant_id}:{agent_id}"


def get_or_create_agent(
    agent_id: int,
    tenant_id: int,
    pipeline_stages: list[dict] | None = None,
    available_tags: list[str] | None = None,
    lead_id: int | None = None,
    conversation_id: int | None = None,
    memories: list[str] | None = None,
) -> Agent:
    """Return a cached Agent, creating it on first call or after config update."""

    has_contextual = bool(lead_id or conversation_id or memories)
    cache_key = _make_key(agent_id, tenant_id)

    if not has_contextual and cache_key in _agent_cache:
        return _agent_cache[cache_key]

    config = _agent_configs.get(agent_id, {})
    if not config:
        config = {"tenant_id": tenant_id, "llm_provider": "openai", "llm_model": "gpt-4o-mini"}

    model = _build_model(config)
    tools = _build_tools(config, lead_id, pipeline_stages or [], available_tags or [], conversation_id)
    instructions = _build_instructions(config, memories or [])

    agent = Agent(
        agent_id=f"agent_{agent_id}",
        model=model,
        description=instructions,
        response_model=AgnoReply,
        db=PostgresDb(
            db_url=PGVECTOR_URL,
            session_table=f"sessions_{tenant_id}_{agent_id}",
        ),
        tools=tools if tools else None,
        add_history_to_messages=True,
        num_history_responses=10,
    )

    if not has_contextual:
        _agent_cache[cache_key] = agent

    return agent


def _build_model(config: dict) -> Any:
    provider = config.get("llm_provider", "openai")
    model_id = config.get("llm_model", "gpt-4o-mini")
    api_key = config.get("llm_api_key", "")

    if provider == "anthropic":
        from agno.models.anthropic import Claude
        return Claude(id=model_id, api_key=api_key or None)

    if provider == "google":
        from agno.models.google import Gemini
        return Gemini(id=model_id, api_key=api_key or None)

    from agno.models.openai import OpenAIChat
    return OpenAIChat(id=model_id, api_key=api_key or None)


def _build_instructions(config: dict, memories: list[str] | None = None) -> str:
    name = config.get("name", "Assistente")
    objective = config.get("objective", "ajudar clientes")
    company = config.get("company_name", "")
    industry = config.get("industry", "")
    style = config.get("communication_style", "professional")
    persona = config.get("persona_description", "")
    behavior = config.get("behavior", "")
    max_len = config.get("max_message_length", 150)

    style_desc = {
        "formal": "Tom formal e profissional.",
        "casual": "Tom descontraído e amigável.",
        "professional": "Tom profissional mas acessível.",
        "friendly": "Tom caloroso, simpático e próximo.",
        "technical": "Tom técnico e preciso.",
    }.get(style, "Tom profissional.")

    return f"""Você é {name}, assistente de {company or "nossa empresa"} atendendo pelo WhatsApp.
{'Setor: ' + industry + '.' if industry else ''}
Objetivo: {objective}
{style_desc}
{persona}
{behavior}

═══════════════════════════════════════
REGRAS DO WHATSAPP — OBRIGATÓRIAS
═══════════════════════════════════════

Você está numa conversa de WhatsApp. Cada item de "reply_blocks" é enviado como
uma mensagem SEPARADA, com delay entre elas — exatamente como uma pessoa digitando.

REGRAS ABSOLUTAS:
- NUNCA coloque tudo em uma mensagem só.
- NUNCA use markdown: sem **, sem __, sem #, sem listas com hífens.
- Cada bloco deve ter no máximo {max_len} caracteres.
- Cada bloco deve ser uma frase ou ideia COMPLETA — jamais corte no meio de uma frase.
- Se uma ideia precisa de mais de {max_len} chars, divida em 2 blocos em pontos naturais
  (fim de oração, vírgula, conjunção: "e", "mas", "porém").

REGRA DE OURO: cada item de uma lista = 1 reply_block separado.

EXEMPLOS CORRETOS (cada linha entre aspas = uma mensagem distinta):

Pergunta: "quais são os planos?"
reply_blocks: [
  "Temos 3 opções 😊",
  "Starter — ideal para times pequenos, R$ 97/mês.",
  "Pro — recursos avançados + suporte prioritário, R$ 197/mês.",
  "Enterprise — ilimitado e personalizado, sob consulta.",
  "Qual faz mais sentido pra você?"
]

Pergunta: "como funciona?"
reply_blocks: [
  "É bem simples!",
  "Você cadastra seus leads e acompanha cada etapa da venda.",
  "O sistema avisa o time quando algo precisa de atenção.",
  "Quer ver um passo a passo?"
]

Pergunta: "me fala sobre as funcionalidades"
reply_blocks: [
  "Com prazer! Tem bastante coisa útil 😄",
  "Funil Kanban — arraste os leads entre etapas de forma visual.",
  "Pipeline — acompanhe prospecção, proposta e fechamento.",
  "Relatórios — ticket médio, receita e origem dos leads.",
  "Integrações — Google Ads, Facebook Ads e WhatsApp.",
  "Agente de IA — qualifica leads automaticamente.",
  "Tem alguma que quer entender melhor?"
]
""" + (_build_memories_section(memories) if memories else "")


def _build_memories_section(memories: list[str]) -> str:
    """Build the memories section to inject into agent instructions."""
    if not memories:
        return ""
    items = "\n".join(f"- {m}" for m in memories)
    return f"""

═══════════════════════════════════════
EXPERIÊNCIA ANTERIOR COM ESTE CONTATO
═══════════════════════════════════════

Use estas informações de conversas anteriores para personalizar seu atendimento.
NÃO mencione explicitamente que você tem memórias ou registros anteriores.
Apenas use o contexto para ser mais relevante e natural.

{items}
"""


def _build_tools(
    config: dict,
    lead_id: int | None,
    pipeline_stages: list[dict],
    available_tags: list[str],
    conversation_id: int | None,
) -> list:
    tools = []
    tenant_id: int = config.get("tenant_id", 0)

    if lead_id:
        if config.get("enable_pipeline_tool") and pipeline_stages:
            from tools.pipeline_tools import make_pipeline_tools
            tools.extend(make_pipeline_tools(lead_id, pipeline_stages, tenant_id))

        if config.get("enable_tags_tool") and available_tags:
            from tools.tag_tools import make_tag_tools
            tools.extend(make_tag_tools(lead_id, available_tags, tenant_id))

        if config.get("enable_intent_notify") and conversation_id:
            from tools.notify_tools import make_notify_tools
            tools.extend(make_notify_tools(lead_id, conversation_id, tenant_id))

    return tools
