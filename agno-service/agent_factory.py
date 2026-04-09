import os
from typing import Any, Optional

from agno.agent import Agent
from agno.db.postgres import PostgresDb
from pydantic import BaseModel

PGVECTOR_URL = os.getenv("PGVECTOR_URL", "postgresql://agno:agno@pgvector:5432/agno")


class AgnoAction(BaseModel):
    """An action the AI wants to execute on the CRM (PHP will process it)."""
    type: str
    payload: Optional[dict[str, Any]] = None
    media_id: Optional[int] = None
    stage_id: Optional[int] = None
    tags: Optional[list[str]] = None
    field: Optional[str] = None
    value: Optional[str] = None
    body: Optional[str] = None


class AgnoReply(BaseModel):
    """Structured output enforced at API level — each item is one WhatsApp message."""
    reply_blocks: list[str]
    actions: Optional[list[AgnoAction]] = None


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
    lead_data: dict | None = None,
    custom_fields: list[dict] | None = None,
    lead_notes: list[dict] | None = None,
    products: list[dict] | None = None,
    lead_products: list[dict] | None = None,
    available_media: list[dict] | None = None,
    knowledge_chunks: list[dict] | None = None,
    current_datetime: str | None = None,
    period_of_day: str | None = None,
    greeting: str | None = None,
) -> Agent:
    """Return a cached Agent, creating it on first call or after config update."""

    has_contextual = bool(
        lead_id or conversation_id or memories or lead_data
        or knowledge_chunks or current_datetime
    )
    cache_key = _make_key(agent_id, tenant_id)

    if not has_contextual and cache_key in _agent_cache:
        return _agent_cache[cache_key]

    config = _agent_configs.get(agent_id, {})
    if not config:
        config = {"tenant_id": tenant_id, "llm_provider": "openai", "llm_model": "gpt-4o-mini"}

    model = _build_model(config)
    tools = []  # Actions são retornadas via JSON e executadas pelo PHP
    instructions = _build_instructions(
        config,
        memories or [],
        pipeline_stages or [],
        available_tags or [],
        lead_data,
        custom_fields or [],
        lead_notes or [],
        products or [],
        lead_products or [],
        available_media or [],
        knowledge_chunks or [],
        current_datetime,
        period_of_day,
        greeting,
    )

    agent = Agent(
        name=f"agent_{agent_id}",
        model=model,
        instructions=instructions,
        output_schema=AgnoReply,
        db=PostgresDb(
            db_url=PGVECTOR_URL,
            session_table=f"sessions_{tenant_id}_{agent_id}",
        ),
        tools=tools if tools else None,
        add_history_to_context=True,
        num_history_runs=10,
    )

    if not has_contextual:
        _agent_cache[cache_key] = agent

    return agent


def _build_model(config: dict) -> Any:
    provider = config.get("llm_provider", "openai")
    model_id = config.get("llm_model", "gpt-4o-mini")
    api_key = config.get("llm_api_key", "") or os.getenv("LLM_API_KEY", "")

    if provider == "anthropic":
        from agno.models.anthropic import Claude
        return Claude(id=model_id, api_key=api_key or None)

    if provider == "google":
        from agno.models.google import Gemini
        return Gemini(id=model_id, api_key=api_key or None)

    from agno.models.openai import OpenAIChat
    return OpenAIChat(id=model_id, api_key=api_key or None)


def _build_instructions(
    config: dict,
    memories: list[str] | None = None,
    pipeline_stages: list[dict] | None = None,
    available_tags: list[str] | None = None,
    lead_data: dict | None = None,
    custom_fields: list[dict] | None = None,
    lead_notes: list[dict] | None = None,
    products: list[dict] | None = None,
    lead_products: list[dict] | None = None,
    available_media: list[dict] | None = None,
    knowledge_chunks: list[dict] | None = None,
    current_datetime: str | None = None,
    period_of_day: str | None = None,
    greeting: str | None = None,
) -> str:
    name = config.get("name", "Assistente")
    objective = config.get("objective", "ajudar clientes")
    company = config.get("company_name", "")
    industry = config.get("industry", "")
    style = config.get("communication_style", "professional")
    persona = config.get("persona_description", "")
    behavior = config.get("behavior", "")
    max_len = config.get("max_message_length", 150)
    kb = config.get("knowledge_base_text", "")
    language = config.get("language", "pt-BR")

    lang_names = {
        "pt-BR": "Português (Brasil)",
        "en-US": "English",
        "es-ES": "Español",
    }
    lang_name = lang_names.get(language, language)

    style_desc = {
        "formal": "Tom formal e profissional.",
        "casual": "Tom descontraído e amigável.",
        "professional": "Tom profissional mas acessível.",
        "friendly": "Tom caloroso, simpático e próximo.",
        "technical": "Tom técnico e preciso.",
    }.get(style, "Tom profissional.")

    sections = [f"""⚠️ LANGUAGE RULE: You MUST respond ONLY in {lang_name}. Every single message must be written in {lang_name}. This overrides any other instruction about language.

Você é {name}, assistente de {company or "nossa empresa"} atendendo pelo WhatsApp.
{'Setor: ' + industry + '.' if industry else ''}
Objetivo: {objective}
{style_desc}
{persona}
{behavior}"""]

    # ── Contexto temporal: data, hora, periodo do dia, saudacao correta ────
    # Sem isso o LLM nao tem nocao de horario e diz "bom dia" as 19h ou
    # "tenha um otimo dia" quando ja e noite. PHP envia tudo formatado no
    # fuso correto do tenant, Python so injeta como instrucao.
    if current_datetime:
        greeting_text = greeting or "ola"
        period_text = period_of_day or "dia"
        sections.append(f"""
═══════════════════════════════════════
DATA E HORA ATUAL (CRITICO — RESPEITE)
═══════════════════════════════════════
Data e hora agora: {current_datetime}
Periodo do dia: {period_text}
Saudacao correta agora: "{greeting_text}"

REGRAS DE SAUDACAO E DESPEDIDA — OBRIGATORIAS:
- Se for cumprimentar, use SEMPRE "{greeting_text}" (nao outra saudacao).
- NUNCA use "bom dia" se nao for manha (00h-12h).
- NUNCA use "boa tarde" se nao for tarde (12h-18h).
- NUNCA use "boa noite" se nao for noite (18h-24h).
- Ao se DESPEDIR no periodo da NOITE, NUNCA diga "tenha um otimo dia".
  Use "tenha uma otima noite", "ate amanha", ou "ate logo".
- Ao se despedir de TARDE, prefira "tenha uma otima tarde" ou "ate logo".
- Ao se despedir de MANHA, "tenha um otimo dia" e ok.
═══════════════════════════════════════""")

    if kb:
        sections.append(f"""
═══════════════════════════════════════
BASE DE CONHECIMENTO
═══════════════════════════════════════
{kb}""")

    # ── RAG: chunks recuperados via similaridade vetorial pra ESTA mensagem ──
    if knowledge_chunks:
        chunks_text = "\n\n".join(
            f"[Trecho de {c.get('filename', 'arquivo')}]\n{c.get('content', '')}"
            for c in knowledge_chunks
            if c.get("content")
        )
        if chunks_text:
            sections.append(f"""
═══════════════════════════════════════
CONTEXTO RELEVANTE DA BASE DE CONHECIMENTO
═══════════════════════════════════════
Os trechos abaixo foram selecionados automaticamente como os mais relevantes
para a mensagem atual do cliente. Use-os como FONTE DE VERDADE ao responder.
Se o trecho cobre a pergunta, responda baseado nele. Se nao cobre, diga que
nao tem essa informacao especifica em vez de inventar.

{chunks_text}
═══════════════════════════════════════""")

    sections.append(f"""
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
- Se uma ideia precisa de mais de {max_len} chars, divida em 2 blocos em pontos naturais.

REGRA DE OURO: cada item de uma lista = 1 reply_block separado.""")

    # ── Pipeline stages ──────────────────────────────────────────────
    if pipeline_stages:
        stages_text = "\n".join(
            f"  - id={s['id']}: {s.get('name', '')}{'  ← ETAPA ATUAL' if s.get('current') else ''}"
            for s in pipeline_stages
        )
        sections.append(f"""
═══════════════════════════════════════
FUNIL DE VENDAS
═══════════════════════════════════════
Etapas disponíveis:
{stages_text}

Para mover o lead, inclua em actions: {{"type": "set_stage", "payload": {{"stage_id": <id>}}}}
Avance gradualmente. Use GANHO somente com confirmação explícita de compra.
Use PERDIDO somente com recusa explícita.""")

    # ── Tags ─────────────────────────────────────────────────────────
    if available_tags:
        tags_text = ", ".join(available_tags)
        sections.append(f"""
═══════════════════════════════════════
TAGS DISPONÍVEIS
═══════════════════════════════════════
{tags_text}

Para adicionar tags: {{"type": "add_tags", "payload": {{"tags": ["tag1", "tag2"]}}}}""")

    # ── Lead data ────────────────────────────────────────────────────
    if lead_data:
        val = lead_data.get("value")
        val_str = f"R$ {val:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".") if val else "(vazio)"
        sections.append(f"""
═══════════════════════════════════════
DADOS DO LEAD
═══════════════════════════════════════
Nome: {lead_data.get('name') or '(vazio)'}
Telefone: {lead_data.get('phone') or '(vazio)'}
E-mail: {lead_data.get('email') or '(vazio)'}
Empresa: {lead_data.get('company') or '(vazio)'}
Data nascimento: {lead_data.get('birthday') or '(vazio)'}
Valor do lead: {val_str}

Para atualizar dados: {{"type": "update_lead", "payload": {{"field": "email", "value": "novo@email.com"}}}}
Campos: name, email, company, birthday (YYYY-MM-DD), value (número decimal).
Colete NATURALMENTE durante a conversa. NÃO pergunte dados sem contexto.""")

    # ── Custom fields ────────────────────────────────────────────────
    if custom_fields:
        cf_lines = []
        for cf in custom_fields:
            type_hint = {
                "number": "(número)", "currency": "(valor em R$)", "date": "(data: YYYY-MM-DD)",
                "checkbox": "(true/false)", "multiselect": f"(opções: {', '.join(cf.get('options', []))})",
            }.get(cf.get("type", "text"), "(texto)")
            val = cf.get("value")
            val_display = str(val) if val is not None else "(vazio)"
            if isinstance(val, list):
                val_display = ", ".join(str(v) for v in val)
            cf_lines.append(f"  - {cf.get('label', '')} [{cf.get('name', '')}] {type_hint}: {val_display}")
        sections.append(f"""
═══════════════════════════════════════
CAMPOS PERSONALIZADOS DO LEAD
═══════════════════════════════════════
{chr(10).join(cf_lines)}

Para preencher: {{"type": "update_custom_field", "payload": {{"field": "nome_campo", "value": "valor"}}}}
Para multiselect: {{"type": "update_custom_field", "payload": {{"field": "campo", "value": ["op1", "op2"]}}}}""")

    # ── Notes ────────────────────────────────────────────────────────
    if lead_notes:
        notes_lines = []
        for n in lead_notes[:5]:
            notes_lines.append(f"  - [{n.get('date', '')}] ({n.get('author', 'IA')}): {n.get('body', '')[:150]}")
        sections.append(f"""
═══════════════════════════════════════
NOTAS DO LEAD (últimas {len(lead_notes)})
═══════════════════════════════════════
{chr(10).join(notes_lines)}""")

    # ── Products catalog ─────────────────────────────────────────────
    if products:
        prod_lines = []
        for p in products:
            price_str = f"R$ {p['price']:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
            unit_str = f"/{p['unit']}" if p.get('unit') else ""
            media_str = ""
            if p.get('media'):
                media_parts = [f"{m['id']}({m['type']})" for m in p['media']]
                media_str = f" [mídias: {', '.join(media_parts)}]"
            desc = f" — {p['description']}" if p.get('description') else ""
            prod_lines.append(f"  - id={p['id']}: {p['name']} ({price_str}{unit_str}){desc}{media_str}")

        sections.append(f"""
═══════════════════════════════════════
CATÁLOGO DE PRODUTOS/SERVIÇOS
═══════════════════════════════════════
{chr(10).join(prod_lines)}

Para enviar foto/vídeo de produto: {{"type": "send_product_media", "payload": {{"product_id": <id>, "media_id": <media_id>}}}}
Para vincular produto ao lead: {{"type": "add_product_to_lead", "payload": {{"product_id": <id>, "quantity": 1}}}}
Para remover produto do lead: {{"type": "remove_product_from_lead", "payload": {{"product_id": <id>}}}}
Informe preços e detalhes NATURALMENTE durante a conversa.
Envie fotos quando o cliente perguntar sobre um produto específico.
NÃO liste todos os produtos de uma vez — apresente conforme o interesse do cliente.""")

    if lead_products:
        lp_lines = []
        for lp in lead_products:
            total_str = f"R$ {lp['total']:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
            lp_lines.append(f"  - {lp['name']} (x{lp['quantity']}) = {total_str}")

        grand_total = sum(lp['total'] for lp in lead_products)
        gt_str = f"R$ {grand_total:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
        sections.append(f"""
═══════════════════════════════════════
PRODUTOS VINCULADOS AO LEAD
═══════════════════════════════════════
{chr(10).join(lp_lines)}
TOTAL: {gt_str}""")

    # ── Agent media (images, documents uploaded by admin) ────────────
    if available_media:
        media_lines = []
        for m in available_media:
            media_lines.append(f"  media_id {m['id']}: {m['name']} — {m.get('description', m['name'])} ({m.get('type', 'arquivo')})")
        sections.append(f"""
═══════════════════════════════════════
MÍDIAS DISPONÍVEIS PARA ENVIO
═══════════════════════════════════════
{chr(10).join(media_lines)}

Quando o contato pedir prints, fotos, imagens ou exemplos visuais, envie a mídia correspondente.
Para enviar: {{"type": "send_media", "media_id": <id numérico>}}
IMPORTANTE: Inclua o media_id numérico da lista acima. Envie APENAS 1 mídia por vez.
SEMPRE envie a mídia quando relevante. NÃO diga que não pode enviar imagens.""")

    # ── Actions instructions ─────────────────────────────────────────
    sections.append(f"""
═══════════════════════════════════════
AÇÕES DISPONÍVEIS
═══════════════════════════════════════
Inclua ações em "actions" quando necessário. O sistema PHP as executará.

- set_stage: mover lead no funil. {{"type": "set_stage", "stage_id": 123}}
- add_tags: adicionar tags. {{"type": "add_tags", "tags": ["tag1"]}}
- update_lead: atualizar dados. {{"type": "update_lead", "field": "value", "value": "2500.00"}}
- create_note: registrar observação. {{"type": "create_note", "body": "Cliente pediu proposta"}}
- update_custom_field: preencher campo. {{"type": "update_custom_field", "field": "interesse", "value": "premium"}}
- assign_human: transferir para humano. {{"type": "assign_human"}}
- send_media: enviar mídia do agente. {{"type": "send_media", "media_id": 1}}
- send_product_media: enviar foto de produto. {{"type": "send_product_media", "media_id": 42}}
- add_product_to_lead: vincular produto. {{"type": "add_product_to_lead"}}
- remove_product_from_lead: remover produto. {{"type": "remove_product_from_lead"}}

REGRAS para actions:
- NÃO crie nota para cada mensagem — apenas informações estratégicas.
- NÃO pergunte dados para preencher — colete naturalmente.
- Se o campo já tem o mesmo valor, NÃO emita a ação.
- Use "actions": [] quando nenhuma ação é necessária.""")

    # ── Calendar restriction ──────────────────────────────────────────
    enable_calendar = config.get("enable_calendar_tool", False)
    if not enable_calendar:
        sections.append("""
═══════════════════════════════════════
RESTRIÇÃO DE AGENDA
═══════════════════════════════════════
Você NÃO tem acesso a agenda ou calendário.
NÃO ofereça agendamento, NÃO fale sobre marcar horários ou demonstrações.
Se o cliente pedir para agendar, oriente que entre em contato diretamente com a equipe ou transfira para um humano usando assign_human.""")
    else:
        # Bloco hardcoded de regras CRITICAS pra agendamento. Sem isso, o LLM
        # alucina "agendado!" sem chamar tool de fato. Bug historico:
        # 2026-04-09 Camila confirmando consulta sem criar evento real no Google.
        # Esse bloco e auto-aplicado pra qualquer agent com calendar tool ativa
        # — usuario leigo nao precisa saber dessas regras pra que funcione.
        sections.append("""
═══════════════════════════════════════
REGRAS DE AGENDAMENTO (CRITICAS — NUNCA QUEBRE)
═══════════════════════════════════════

Voce TEM acesso a agenda real. Quando o cliente quer agendar, voce DEVE
chamar as actions estruturadas. NUNCA simule confirmacao com palavras.

PALAVRAS PROIBIDAS sem antes ter chamado calendar_create nessa MESMA resposta:
- "agendado", "agendei", "marquei", "marcado"
- "confirmado", "confirmando", "consulta confirmada"
- "reservei", "reservado", "horario garantido"
- "ok, esta marcado", "ok, esta agendado", "esta tudo certo para [data]"

Se voce ainda nao chamou calendar_create, voce NUNCA pode dizer essas
palavras. Em vez disso, diga "vou verificar a disponibilidade agora" ou
"um momento, estou registrando" e ENTAO use a action.

SEQUENCIA OBRIGATORIA pra agendar:
1. Colete data + hora desejada (se ainda nao tem)
2. Colete nome completo + telefone (se ainda nao estao no lead — use update_lead)
3. (Opcional, se enable_check) Chame check_calendar_availability {start, end}
   pra verificar se o horario esta livre. Se nao estiver, sugira alternativas.
4. Chame calendar_create com:
   - title: descreva sucintamente (ex: "Consulta - [Nome do cliente]")
   - start: ISO 8601 no formato YYYY-MM-DDTHH:MM (fuso local do tenant)
   - end: start + duracao tipica (consulta = 1h, reuniao = 30min, ajuste pelo contexto)
   - description: motivo da consulta + telefone do cliente (importante!)
   - attendees: email do cliente se voce tiver
5. SO DEPOIS de calendar_create, confirme pro cliente: "Pronto! Sua [tipo]
   esta confirmada para [data formatada]. Voce recebera lembretes."

REGRA DE AUTO-CHECK: se o cliente disse "sim, pode agendar" ou similar e
voce nao chamou calendar_create na resposta anterior, voce esta erradoa.
A confirmacao do cliente deveria ter sido seguida pela action, nao por
mais texto. Corrija na proxima interacao chamando calendar_create AGORA.

CASOS ESPECIAIS:
- Cliente pediu pra cancelar: chame calendar_cancel com o google_event_id
  (esta no contexto se tiver eventos vinculados)
- Cliente pediu pra reagendar: chame calendar_reschedule com novo start/end
- Cliente perguntou que dias tem vagas: liste o que voce ja tem no contexto
  (eventos agendados aparecem na area "Eventos do calendario")
- Voce nao tem certeza sobre a data: PERGUNTE antes de agendar, NUNCA
  chute. "Para qual dia voce gostaria? [oferecer 2-3 opcoes]"

Lembre: o cliente confia que voce esta agendando de verdade. Mentir que
agendou e quebra de confianca grave.""")

    # ── Memories ─────────────────────────────────────────────────────
    if memories:
        sections.append(_build_memories_section(memories))

    return "\n".join(sections)


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
