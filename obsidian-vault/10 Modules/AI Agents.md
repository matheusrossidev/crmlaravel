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
last_review: 2026-04-09
tags: [module, ai, agent]
---

# AI Agents

## O que é
Agentes IA configuráveis (objetivo, persona, tools, memória) que respondem mensagens automaticamente em WhatsApp e Instagram. Roteiam por **2 caminhos**: LLM direto (PHP → OpenAI/Anthropic/Gemini) ou microsserviço **Agno** (FastAPI + pgvector).

## Status
- ✅ Atribuição manual ou auto-assign por canal/instance
- ✅ Tools: pipeline (`set_stage`), tags, intent_notify, calendar (Google), voice reply (ElevenLabs)
- ✅ Follow-up automático ([[Follow-up de IA]]) — só WhatsApp
- ✅ Sistema de quota de tokens por tenant + upsell modal
- ✅ Memória persistente via Agno + pgvector
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
- `main.py` — endpoints `/chat`, `/agents/{id}/configure`, `/agents/{id}/memories/*`
- `agent_factory.py` — cria/cacheia agentes por `tenant_id:agent_id`
- `memory_store.py` — PostgreSQL + pgvector
- `formatter.py` — humaniza respostas, quebra em blocos de 150 chars
- `tools/` — function calling tools

## Padrões críticos
- **`AiAgent->use_agno`** controla qual caminho usa
- **`max_message_length`** define máx caracteres por mensagem (humanização)
- **`response_wait_seconds`** define batching (agrupa msgs próximas em uma resposta)
- **`response_delay_*`** simula tempo de digitação
- **Quota**: `TokenQuotaService` soma `base_tokens` + `TenantTokenIncrement` pagos do mês

## Decisões / RCAs
- [[2026-04-09 Telefone obrigatorio no description do calendar]]
- [[ADR — Agno como microsserviço Python separado]]
- [[Calendar & Reminders]] (módulo dependente)
- [[Follow-up de IA]] (sub-feature)
