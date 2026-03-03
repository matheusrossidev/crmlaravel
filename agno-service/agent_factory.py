import os
from typing import Any

from agno.agent import Agent
from agno.db.postgres import PostgresDb

PGVECTOR_URL = os.getenv("PGVECTOR_URL", "postgresql://agno:agno@pgvector:5432/agno")

# In-memory cache: agent_key -> Agent instance
_agent_cache: dict[str, Agent] = {}
# In-memory config store: agent_id -> ConfigureRequest dict
_agent_configs: dict[int, dict[str, Any]] = {}


def store_agent_config(agent_id: int, config: dict[str, Any]) -> None:
    """Store agent config and invalidate cache so next call rebuilds the agent."""
    _agent_configs[agent_id] = config
    _agent_cache.pop(_make_key(agent_id, config.get("tenant_id", 0)), None)


def _make_key(agent_id: int, tenant_id: int) -> str:
    return f"{tenant_id}:{agent_id}"


def get_or_create_agent(
    agent_id: int,
    tenant_id: int,
    pipeline_stages: list[dict] | None = None,
    available_tags: list[str] | None = None,
    lead_id: int | None = None,
    conversation_id: int | None = None,
) -> Agent:
    """Return a cached Agent, creating it on first call or after config update."""

    # Agents with tools bound to a specific lead cannot be cached globally
    has_contextual_tools = bool(lead_id or conversation_id)
    cache_key = _make_key(agent_id, tenant_id)

    if not has_contextual_tools and cache_key in _agent_cache:
        return _agent_cache[cache_key]

    config = _agent_configs.get(agent_id, {})
    if not config:
        # Fallback: minimal agent if not yet configured
        config = {"tenant_id": tenant_id, "llm_provider": "openai", "llm_model": "gpt-4o-mini"}

    model = _build_model(config)
    tools = _build_tools(config, lead_id, pipeline_stages or [], available_tags or [], conversation_id)

    agent = Agent(
        agent_id=f"agent_{agent_id}",
        model=model,
        description=_build_instructions(config),
        # PostgresDb persists session history and user memories across runs
        db=PostgresDb(
            db_url=PGVECTOR_URL,
            session_table=f"sessions_{tenant_id}_{agent_id}",
        ),
        tools=tools if tools else None,
        add_history_to_messages=True,
        num_history_responses=10,
    )

    if not has_contextual_tools:
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

    # Default: openai
    from agno.models.openai import OpenAIChat
    return OpenAIChat(id=model_id, api_key=api_key or None)


def _build_instructions(config: dict) -> str:
    name = config.get("name", "Assistente")
    objective = config.get("objective", "ajudar clientes")
    company = config.get("company_name", "")
    industry = config.get("industry", "")
    style = config.get("communication_style", "professional")
    persona = config.get("persona_description", "")
    behavior = config.get("behavior", "")
    max_len = config.get("max_message_length", 800)

    style_desc = {
        "formal": "Seja formal e profissional.",
        "casual": "Seja descontraído e amigável.",
        "professional": "Seja profissional mas acessível.",
        "friendly": "Seja caloroso, simpático e próximo.",
        "technical": "Seja técnico e preciso.",
    }.get(style, "Seja profissional.")

    return f"""Você é {name}, assistente virtual de {company or 'nossa empresa'}.
{'Setor: ' + industry if industry else ''}
Objetivo: {objective}

{style_desc}
{persona}
{behavior}

REGRAS DE FORMATAÇÃO (WhatsApp):
- NUNCA use markdown: sem **negrito**, sem _itálico_, sem # títulos, sem listas com -
- Máximo {max_len} caracteres por bloco de mensagem
- Divida sua resposta em MÚLTIPLOS blocos curtos (2-4 linhas cada), como uma pessoa digitando
- Use emojis com moderação quando o estilo permitir
- Separe ideias em mensagens distintas, não em um bloco único longo

FORMATO DE RESPOSTA OBRIGATÓRIO:
Retorne sempre um JSON com o campo "reply_blocks" contendo um array de strings.
Cada string é uma mensagem separada que será enviada individualmente.
Exemplo: {{"reply_blocks": ["Olá! 😊", "Posso te ajudar com isso.", "O que mais precisa saber?"]}}"""


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
