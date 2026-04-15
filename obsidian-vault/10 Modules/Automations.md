---
type: module
status: active
related: ["[[AutomationEngine]]", "[[Automation]]", "[[Lead Scoring]]", "[[Nurture Sequences]]"]
files:
  - app/Models/Automation.php
  - app/Services/AutomationEngine.php
  - app/Http/Controllers/Tenant/AutomationController.php
last_review: 2026-04-17
tags: [module, automation]
---

# Automations

## O que é
Sistema de automação por trigger + condições + actions. Configurável pela UI (sem código). Suporta triggers de evento (mensagem, conversa, lead, etapa) + triggers temporais (data field, recorrente).

## Status
- ✅ 8 trigger types + **21 actions** (incluindo 3 novas Cloud-only)
- ✅ Condições filtráveis (canal, pipeline, stage, source, instance, message body, tags)
- ✅ Recurring trigger (cron diário)
- ✅ Biblioteca de templates por nicho (9 nichos × 4 templates)
- ✅ **Idempotência**: 5 actions de "set state" agora skipam quando o valor já é o esperado (commit `41cc967`)
- ✅ **Actions Cloud-only** (2026-04-14 `16aebda`): `send_whatsapp_template`, `send_whatsapp_buttons`, `send_whatsapp_list` — aparecem na sidebar só se tenant tem instância Cloud (via `tenantHasCloudApi()`)
- ✅ **Janela 24h awareness**: `send_whatsapp_message` em Cloud fora da janela → pula (sem `fallback_template_id`) ou delega pra `send_whatsapp_template` com o fallback
- ⚠️ `add_note`, `send_whatsapp_message`, `create_task`, `enroll_sequence`, `send_webhook` continuam disparando a cada execução (deliberado — são "criar coisa")

## Trigger types
| Trigger | Quando dispara |
|---|---|
| `message_received` | Toda mensagem inbound (WhatsApp/Instagram) |
| `conversation_created` | Conversa nova criada (1x por contato) |
| `lead_created` | Lead novo criado |
| `lead_stage_changed` | Lead muda de etapa do pipeline |
| `lead_won` | Lead vai pra Sale |
| `lead_lost` | Lead vai pra LostSale |
| `date_field` | Cron diário 08h checa custom_field_date |
| `recurring` | Cron processa por `recurrence_type` (daily/weekly/monthly) |

## Actions disponíveis (18)
**Idempotentes (state changes):**
- `assign_to_user` ✅ idempotente
- `set_lead_source` ✅ idempotente
- `move_to_stage` ✅ idempotente
- `assign_ai_agent` ✅ idempotente
- `assign_chatbot_flow` ✅ idempotente

**Não idempotentes (criação ou envio — comportamento intencional):**
- `add_tag_lead` / `remove_tag_lead` (trait HasTags já é idempotente naturalmente)
- `add_tag_conversation`
- `add_note` (cria nota nova a cada disparo)
- `create_task`
- `send_whatsapp_message` (com fallback automático pra template se Cloud + janela fechada)
- `schedule_whatsapp_message`
- `send_whatsapp_template` — Cloud only; escolhe WhatsappTemplate APPROVED + mapping de variáveis (default: 1=lead.name, 2=lead.company, 3=lead.email)
- `send_whatsapp_buttons` — Cloud only; até 3 botões reply (text max 20 chars cada) + footer opcional
- `send_whatsapp_list` — ambos providers; lista interativa com título, button text, rows (max 10)
- `enroll_sequence`
- `send_webhook` (Job assíncrono)
- `ai_extract_fields` (Job assíncrono — IA extrai campos do histórico)
- `set_utm_params`
- `transfer_to_department`
- `close_conversation`

## Padrões críticos
- **Filtro estrito por `trigger_type`** no `AutomationEngine::run()` — automação `conversation_created` NÃO roda em `message_received`
- **State changes idempotentes** após commit `41cc967` — não dispara notificação spam quando o valor não muda
- **Lead Scoring** é avaliado dentro do mesmo `run()` (linhas 83-85)

## Decisões / RCAs
- [[2026-04-09 Idempotencia de actions de automacao]]
- [[ADR — Templates de automacoes por nicho]]
- [[ADR — Recurring triggers via cron diario]]
