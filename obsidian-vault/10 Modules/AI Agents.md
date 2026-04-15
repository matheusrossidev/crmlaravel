---
type: module
status: active
related: ["[[AiAgent]]", "[[ProcessAiResponse]]", "[[AgnoService]]", "[[Agno]]", "[[Calendar & Reminders]]"]
files:
  - app/Models/AiAgent.php
  - app/Jobs/ProcessAiResponse.php
  - app/Services/AiAgentService.php
  - app/Services/AgnoService.php
  - agno-service/main.py
last_review: 2026-04-17
tags: [module, ai, agent, rag]
---

# AI Agents

## O que é
Agentes IA configuráveis (objetivo, persona, tools, memória) que respondem mensagens automaticamente em WhatsApp e Instagram. Roteiam por **2 caminhos**: LLM direto (PHP → OpenAI/Anthropic/Gemini) ou microsserviço **Agno** (FastAPI + pgvector).

## Status
- ✅ Atribuição manual ou auto-assign por canal/instance
- ✅ Tools: pipeline (`set_stage`), tags, intent_notify, calendar (Google), voice reply (ElevenLabs)
- ✅ Follow-up automático ([[Follow-up de IA]]) — só WhatsApp
- ✅ **Follow-up Strategy** (2026-04-14 `c73edd6`) — `ai_agents.followup_strategy` ENUM:
  - `smart` (default): texto livre dentro da janela 24h; fora manda template fallback se configurado, senão skip (poupa custo Meta)
  - `template`: sempre via template HSM (paga por envio)
  - `off`: sem follow-up
  - UI: aba "Follow-up" do form com radio cards + dropdown de `WhatsappTemplate` APPROVED
- ✅ Sistema de quota de tokens por tenant + upsell modal
- ✅ Memória persistente via Agno + pgvector (resumos de conversa)
- ✅ **RAG real via pgvector** (commit `9c1b7fb`) — upload de PDF/DOCX/TXT/imagem chega na IA via cosine similarity. Ver [[2026-04-09 RAG real implementado]]
- ✅ **Reconfigure on boot** (commit `609aad4`) — entrypoint do app reconfigura todos os agents no Agno pra cobrir cache in-memory perdido em restart. Ver [[Cache in-memory perde tudo no restart]]
- ✅ **Formatter dinâmico** (commit `bd32135`) — `MAX_BLOCK` agora vem do `max_message_length` do agent, não mais hardcoded 150
- ✅ **Contexto temporal** (commit `bd32135`) — PHP envia data/hora/período/saudação correta no `/chat`, agent injeta no system prompt com regras explícitas
- ✅ **Sent_by tracking** (commit `3f0f816`) — toda mensagem outbound marca `sent_by` (humano, IA, chatbot, automation, etc) + `sent_by_agent_id`. Frontend mostra badge no chat. Ver [[2026-04-09 Marcacao de autoria sent_by]]
- ✅ **Split duplo eliminado** (2026-04-15 `5a7ea54`) — `ProcessAiResponse` não re-splita mais os `reply_blocks` do Agno. Respostas deixam de picotar lista numerada em bolhas desordenadas. `cleanFormatting` preserva bullets/numeração. `sleep` entre msgs respeita `response_delay_seconds`.
- ✅ **Cloud API compat** — `sendWhatsappReply` + `sendMediaReply` usam `ChatIdResolver` + `OutboundMessagePersister` (Foundation SOLID) — sem `@c.us` hardcoded
- ⚠️ Calendar tool: telefone agora forçado no description (commit `f8e6513`) — depende de instrução pro LLM + fallback PHP
- ⚠️ Follow-up + lembretes só WhatsApp, não Instagram

## Fluxo de resposta
```
Mensagem chega → ProcessWahaWebhook verifica conversation.ai_agent_id
  → ProcessAiResponse->process()
    → Debounce: cache versioning (novas msgs incrementam versão)
    → Lock atomic: Cache::add('ai:lock:{id}', 1, 120)
    → Check token quota (base + incrementos pagos do mês)
    → Espera response_wait_seconds (batching de mensagens próximas)
    → Monta contexto (stages, tags, lead, custom fields, notes, history, calendar events)
    → Roteamento:
       - use_agno=true → AgnoService::chat()
       - else → AiConfigurationController::callLlm()
    → Processa reply_blocks → envia mensagens (sendList se houver buttons)
    → Processa actions: set_stage, add_tags, update_lead, create_note, assign_human,
                        notify_intent, calendar_create/reschedule/cancel/check
    → Loga tokens em AiUsageLog
```

## Actions disponíveis
| Action | O que faz |
|---|---|
| `set_stage` | Move lead pra etapa do funil |
| `add_tags` | Adiciona tags na conversa |
| `update_lead` | Atualiza nome/email/company/birthday/value |
| `create_note` | Cria nota no lead |
| `update_custom_field` | Atualiza custom field |
| `assign_human` | Limpa `ai_agent_id` (transfere) |
| `send_media` | Envia mídia configurada do agente |
| `notify_intent` | Cria AiIntentSignal (alerta de venda/agendamento/fechamento) |
| `check_calendar_availability` | Verifica conflitos no Google Calendar |
| `calendar_create` | Cria evento + EventReminders |
| `calendar_reschedule` | Reagenda + propaga pros reminders |
| `calendar_cancel` | Cancela + cancela reminders |
| `calendar_list` | Apenas informativo (eventos já vêm no contexto) |

## Microsserviço Agno (`agno-service/`)
- **FastAPI** rodando em `http://agno:8000` (Docker overlay network)
- `main.py` — endpoints `/chat`, `/agents/{id}/configure`, `/agents/{id}/index-file`, `/agents/{id}/knowledge/search`, `DELETE /agents/{id}/knowledge/{file_id}`, `/agents/{id}/memories/*`
- `agent_factory.py` — cria/cacheia agentes por `tenant_id:agent_id`. Aceita kwargs `knowledge_chunks` (RAG) e `current_datetime/period_of_day/greeting` (temporal)
- `memory_store.py` — pgvector: tabela `agent_memories` (resumos de conversa)
- `knowledge_store.py` — pgvector: tabela `agent_knowledge_chunks` (RAG real). Reusa engine SQLAlchemy + `generate_embedding` do memory_store
- `formatter.py` — second-pass LLM call. **`max_block` agora é parâmetro** (vem do `max_message_length` do agent)
- `schemas.py` — ChatRequest aceita `knowledge_chunks`, `current_datetime`, `period_of_day`, `greeting`
- `tools/` — function calling tools

## Knowledge Base (RAG)

Fluxo completo upload → search → injeção:

1. **Upload** (`AiAgentController::uploadKnowledgeFile`): aceita PDF/DOCX/DOC/TXT/CSV/imagens. Extrai texto via Smalot/PdfParser, PhpOffice/PhpWord, leitura direta ou LLM Vision. Salva em `ai_agent_knowledge_files.extracted_text`.

2. **Indexação**: PHP chama `AgnoService::indexFile($agentId, $tenantId, $fileId, $text, $filename)`. Agno chunkifica (~500 chars com overlap 50, respeita parágrafos→sentenças→espaços), embeda cada chunk via `text-embedding-3-small` (1536 dim), salva em `agent_knowledge_chunks` (pgvector + ivfflat cosine). Retorna `{ok, chunks_count, tokens_used}`.

3. **Cost tracking**: `AiUsageLog` com `type='knowledge_indexing'` e `tokens_used`.

4. **Retrieval**: no `ProcessAiResponse` antes do `Agno::chat`, chama `AgnoService::searchKnowledge($agentId, $tenantId, $messageBody, top_k=5)`. Agno embeda a query, faz cosine similarity (threshold 0.25), retorna top-5 chunks.

5. **Injeção**: PHP envia em `knowledge_chunks` no payload. `agent_factory._build_instructions` monta bloco "CONTEXTO RELEVANTE DA BASE DE CONHECIMENTO" no system prompt com instrução "use como FONTE DE VERDADE".

6. **Delete cascade**: deletar arquivo no painel chama `AgnoService::deleteKnowledgeFile()` que apaga chunks no Agno.

7. **Backfill**: `php artisan agno:reindex-knowledge {--agent= --file= --missing}`. Idempotente. Roda no entrypoint do app com `--missing`.

Detalhes em [[2026-04-09 RAG real implementado]].

## Reconfigure on boot

`_agent_configs` no `agent_factory.py` é dict Python in-memory. Restart do `syncro_agno` perde tudo → próxima `/chat` cai num fallback genérico → IA aluci na identidade. Fix permanente: `entrypoint.sh` do app roda em background:

```bash
php artisan agno:reconfigure-all --wait=60 &
```

`AgnoService::configureFromAgent(AiAgent)` é o método único centralizado de mapping AiAgent → payload Agno. Não duplicar lógica em comandos novos.

Pattern documentado em [[Cache in-memory perde tudo no restart]].

## Contexto temporal

Antes: Camila dizia "tenha um ótimo dia" às 19h. Causa: Agno não recebia hora atual. Container Python em UTC, agent config estático.

Fix: PHP (`ProcessAiResponse`) calcula no fuso do app:
```php
$now = now();
$hour = (int) $now->format('H');
$periodOfDay = $hour < 5 ? 'madrugada' : ($hour < 12 ? 'manha' : ($hour < 18 ? 'tarde' : 'noite'));
$greeting = $hour < 5 ? 'ola' : ($hour < 12 ? 'bom dia' : ($hour < 18 ? 'boa tarde' : 'boa noite'));
$currentDt = $now->locale('pt_BR')->isoFormat('DD/MM/YYYY (dddd) — HH:mm');
```

E envia no payload do `/chat`. `_build_instructions` injeta bloco "DATA E HORA ATUAL (CRÍTICO)" no system prompt com regras: NUNCA "bom dia" se não for manhã, NUNCA "tenha um ótimo dia" à noite, etc.

## Padrões críticos
- **`AiAgent->use_agno`** controla qual caminho usa
- **`max_message_length`** define máx caracteres por mensagem (humanização) — agora respeitado pelo `formatter.py`
- **`response_wait_seconds`** define batching (agrupa msgs próximas em uma resposta)
- **`response_delay_*`** simula tempo de digitação
- **Quota**: `TokenQuotaService` soma `base_tokens` + `TenantTokenIncrement` pagos do mês
- **NUNCA** instanciar config de agent in-memory expecting it to persist — sempre via `AgnoService::configureFromAgent()`
- **NUNCA** duplicar mapping AiAgent → payload Agno — use o método centralizado
- **TODO** spot que cria mensagem outbound DEVE marcar `sent_by` (e `sent_by_agent_id` quando for IA)

## Decisões / RCAs
- [[2026-04-09 Camila e Sophia silenciosas — 5 bugs do Agno]]
- [[2026-04-09 RAG real implementado]]
- [[2026-04-09 Marcacao de autoria sent_by]]
- [[Cache in-memory perde tudo no restart]]
- [[2026-04-09 Telefone obrigatorio no description do calendar]]
- [[ADR — Agno como microsserviço Python separado]]
- [[Calendar & Reminders]] (módulo dependente)
- [[Follow-up de IA]] (sub-feature)
