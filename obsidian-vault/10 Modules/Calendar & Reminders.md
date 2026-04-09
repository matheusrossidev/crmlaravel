---
type: module
status: active
related: ["[[AI Agents]]", "[[EventReminderService]]", "[[GoogleCalendarService]]", "[[Google Calendar]]"]
files:
  - app/Models/EventReminder.php
  - app/Services/EventReminderService.php
  - app/Services/GoogleCalendarService.php
  - app/Console/Commands/SendEventReminders.php
last_review: 2026-04-09
tags: [module, calendar, ai, reminders]
---

# Calendar & Reminders

## O que é
Sistema de criação de eventos no Google Calendar pela IA durante chat + envio automático de lembretes via WhatsApp antes da reunião.

## Status
- ✅ IA cria eventos via action `calendar_create` durante conversa
- ✅ Lembretes (default 1 dia antes + 1 hora antes) enviados via cron a cada 1 min
- ✅ Reagendamento e cancelamento propagam pros reminders
- ✅ Telefone do contato injetado no `description` do evento (commit `f8e6513`)
- ❌ **Eventos criados FORA do CRM** (no Google Calendar direto) **NÃO geram lembrete** — sem sync
- ❌ Lembretes só WhatsApp (não Instagram/Website)

## Como o agente sabe o telefone do contato?

**Resposta curta:** o agente NÃO descobre, ele **herda**.

**Caso A — IA cria evento durante conversa (funciona 100%):**
1. Cliente conversa no WhatsApp. `WhatsappConversation` já existe com `phone` + `lead_id`
2. `ProcessAiResponse` tem `$conv` e `$lead` no escopo
3. Action `calendar_create` lê `$lead->phone` e:
   - Injeta no `description` do evento Google
   - Chama `EventReminderService::createRemindersForEvent` passando `lead_id` direto
4. Cron `whatsapp:send-event-reminders` lê `$reminder->lead->phone` na hora de enviar

**Caso B — Evento criado fora do CRM (não funciona):**
- Não existe sync. Nenhum cron lê `listEvents` → cria reminder.
- Mesmo se existisse, `EventReminder.lead_id` é NOT NULL — teria que matchear `attendee.email → leads.email`. Não implementado.

## Modelo `EventReminder`
- `lead_id` (NOT NULL), `conversation_id` (nullable), `ai_agent_id` (nullable)
- `google_event_id` — ancoragem pra reschedule/cancel
- `event_title`, `event_starts_at`, `offset_minutes`
- `send_at` calculado (`event_starts_at - offset_minutes`)
- `body` pré-renderizado
- `status` enum: `pending` / `sent` / `failed` / `cancelled`

Um evento de 14h com offsets `[1440, 60]` vira **2 EventReminders** (1 dia antes + 1 hora antes).

## Configuração por agente ([[AiAgent]])
- `enable_calendar_tool` (bool)
- `calendar_id` (default `'primary'`)
- `calendar_tool_instructions` (texto livre adicional)
- `reminder_offsets` (array JSON, default `[1440, 60]`)
- `reminder_message_template` (template com `{{lead_name}}`, `{{event_title}}`, `{{event_date}}`, `{{event_time}}`, `{{event_location}}`)

## Comandos
- `whatsapp:send-event-reminders` — cron a cada 1 min, envia reminders pendentes
- (não há comando de sync de Google Calendar — Caso B fora do escopo)

## Telefone obrigatório no description (commit f8e6513)
Reforço duplo:
1. **System prompt** instrui o LLM explicitamente: "se Dados do contato mostra Telefone X, você OBRIGATORIAMENTE inclui essa linha no description"
2. **PHP fallback** em [[ProcessAiResponse]] linhas 1437-1451 — se o LLM esquecer, código injeta o bloco "Cliente: Nome / Telefone / Email" automaticamente

## Decisões / RCAs
- [[2026-04-09 Telefone obrigatorio no description do calendar]]
- [[ADR — Caso B (eventos externos) fora de escopo]]
