---
type: integration
status: active
provider: Agno (microsserviço próprio)
auth: shared token (X-Agno-Token)
related: ["[[AI Agents]]", "[[AgnoService]]"]
env_vars:
  - AGNO_BASE_URL
  - AGNO_INTERNAL_TOKEN
  - LLM_API_KEY
tags: [integration, ai, agno, microsservice, python]
---

# Agno

> **Microsserviço Python (FastAPI)** rodando como container separado no mesmo Swarm. Hospeda agentes de IA com **memória persistente** (pgvector) e **function calling** estruturado.

## Por que existe (em vez de PHP direto)
- Suporte nativo a function calling estruturado (sem prompt eng manual)
- Memória vetorial via pgvector (não vale reimplementar em PHP)
- Cache de agentes em RAM (`agent_factory.py` mantém instâncias por `tenant_id:agent_id`)
- Latency menor pra batches de mensagens (Python > PHP-FPM cold start)

## Endpoints
- `POST /chat` — message in, reply + actions out
- `POST /agents/{id}/configure` — atualiza config do agente em RAM
- `GET /agents/{id}/memories` — lista memórias do agente
- `POST /agents/{id}/memories` — adiciona memória
- `DELETE /agents/{id}/memories/{mem_id}` — remove memória

## Auth
Header `X-Agno-Token` validado por middleware `agno_internal` (Laravel) e por dependency `verify_token` (FastAPI). Token compartilhado via env `AGNO_INTERNAL_TOKEN`.

## Comunicação
PHP → Agno: `AgnoService::chat()` faz `POST http://agno:8000/chat` (Docker overlay network `crm_private`).
Agno → PHP: pode chamar de volta tools via `/api/internal/agno/*` se precisar de dados live (não usa muito).

## Stack interna do Agno
- **FastAPI** (`main.py`)
- **PostgreSQL + pgvector** (`pgvector` service do Swarm)
- **OpenAI/Anthropic/Gemini SDK** via `LLM_API_KEY` (env var no Portainer)
- `agent_factory.py` — criação/cache de agentes
- `memory_store.py` — wrapper pgvector
- `formatter.py` — humanização de respostas
- `schemas.py` — Pydantic models
- `tools/` — function calling tools

## Routing PHP → Agno
Em [[ProcessAiResponse]]:
```php
if ($agent->use_agno) {
    $result = (new AgnoService())->chat($conv, $agent, ...);
} else {
    // LLM direto via AiConfigurationController::callLlm()
}
```

`AiAgent.use_agno` é boolean per-agent — alguns tenants usam Agno, outros usam o caminho direto.

## Limitações
- Single replica (não escala horizontal sem perder cache em RAM)
- Memory store em pgvector dedicado (não compartilha com MySQL)
- Reload de config exige `POST /agents/{id}/configure` explícito (não detecta mudança automaticamente)

## Decisões
- [[ADR — Agno como microsserviço Python separado]]
- [[ADR — pgvector separado pro Agno]]
