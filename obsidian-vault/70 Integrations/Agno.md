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
- `POST /chat` — message in, reply + actions out (aceita `knowledge_chunks`, `current_datetime`, `period_of_day`, `greeting`)
- `POST /agents/{id}/configure` — atualiza config do agente em RAM
- `POST /agents/{id}/index-file` — RAG: chunkifica + embeda + salva no pgvector
- `POST /agents/{id}/knowledge/search` — RAG: top-K chunks por cosine similarity
- `DELETE /agents/{id}/knowledge/{file_id}` — apaga chunks de um arquivo
- `POST /agents/{id}/memories/store` — adiciona memória de conversa
- `POST /agents/{id}/memories/search` — busca memórias por similaridade

## Auth
Header `X-Agno-Token` validado por middleware `agno_internal` (Laravel) e por dependency `verify_token` (FastAPI). Token compartilhado via env `AGNO_INTERNAL_TOKEN`.

## Comunicação
PHP → Agno: `AgnoService::chat()` faz `POST http://agno:8000/chat` (Docker overlay network `crm_private`).
Agno → PHP: pode chamar de volta tools via `/api/internal/agno/*` se precisar de dados live (não usa muito).

## Stack interna do Agno
- **FastAPI** (`main.py`)
- **PostgreSQL + pgvector** (`pgvector` service do Swarm)
- **OpenAI/Anthropic/Gemini SDK** via `LLM_API_KEY` (env var no Portainer)
- `agent_factory.py` — criação/cache de agentes (in-memory dict, ver "Cache in-memory" abaixo)
- `memory_store.py` — pgvector: tabela `agent_memories` (resumos de conversa) + helpers compartilhados (`generate_embedding`, engine SQLAlchemy)
- `knowledge_store.py` — pgvector: tabela `agent_knowledge_chunks` (RAG real). Reusa engine + embedding helper
- `formatter.py` — humanização de respostas (`max_block` agora é parâmetro)
- `schemas.py` — Pydantic models
- `tools/` — function calling tools

## RAG (Knowledge chunks)

Tabela `agent_knowledge_chunks` no pgvector:
```sql
id              SERIAL PRIMARY KEY
tenant_id       INT NOT NULL
agent_id        INT NOT NULL
file_id         INT NOT NULL    -- FK lógica pro PHP (ai_agent_knowledge_files.id)
filename        VARCHAR(255)
chunk_index     INT
content         TEXT
token_count     INT
embedding       vector(1536)
created_at      TIMESTAMP
INDEX idx_..._embedding USING ivfflat (embedding vector_cosine_ops) WITH (lists=100)
INDEX idx_..._agent_file (agent_id, file_id)
```

Fluxo: PHP extrai texto (PDF/DOCX/TXT) → POST `/index-file` → chunkifica ~500 chars com overlap 50 (paragrafos→sentenças→espaços) → embeda cada chunk via `text-embedding-3-small` → INSERT em batch → retorna `{ok, chunks_count, tokens_used}`.

Search: PHP envia query → POST `/knowledge/search` → embeda query → cosine similarity (`<=>`) com filtro tenant+agent → threshold 0.25 → retorna top-K chunks. PHP injeta no `/chat` payload como `knowledge_chunks`. `agent_factory._build_instructions` monta bloco "CONTEXTO RELEVANTE DA BASE DE CONHECIMENTO" no system prompt.

Detalhes em [[2026-04-09 RAG real implementado]].

## Cache in-memory + reconfigure on boot

`_agent_configs` em `agent_factory.py` é dict Python in-memory. Restart do `syncro_agno` perde TODOS os configs. Próxima `/chat` cai em fallback genérico → IA aluci na identidade.

Fix permanente: `docker/entrypoint.sh` do app PHP roda em background no startup:
```bash
php artisan agno:reconfigure-all --wait=60 &
```

Esse comando itera todos `AiAgent` ativos e reconfigura via `AgnoService::configureFromAgent()`. Toda vez que o `syncro_app` inicia, repopula o cache do Agno. Cobre deploy, scale, crash do `syncro_agno`.

Pattern documentado em [[Cache in-memory perde tudo no restart]].

## Contexto temporal

Container Python roda em UTC, fuso BR diferente, e LLMs não sabem hora atual. PHP envia a cada `/chat` os campos `current_datetime` (string formatada pt-BR), `period_of_day` (manha/tarde/noite/madrugada), e `greeting` (bom dia/boa tarde/boa noite). `_build_instructions` injeta no system prompt com regras explícitas: NUNCA "bom dia" se não for manhã, NUNCA "tenha um ótimo dia" à noite, etc.

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
- Reload de config exige `POST /agents/{id}/configure` explícito (não detecta mudança automaticamente) — `agno:reconfigure-all` no boot resolve pra deploys
- `_agent_configs` in-memory perde tudo no restart — mitigado via reconfigure on boot, mas a solução "certa" seria persistir em disco/db (Fase 9 pendente)

## Decisões
- [[ADR — Agno como microsserviço Python separado]]
- [[ADR — pgvector separado pro Agno]]
- [[2026-04-09 RAG real implementado]]
- [[2026-04-09 Camila e Sophia silenciosas — 5 bugs do Agno]]
- [[Cache in-memory perde tudo no restart]]
