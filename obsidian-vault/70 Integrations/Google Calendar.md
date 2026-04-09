---
type: integration
status: active
provider: Google Cloud (OAuth)
auth: oauth2
related: ["[[Calendar & Reminders]]", "[[GoogleCalendarService]]", "[[OAuthConnection]]"]
env_vars:
  - GOOGLE_CLIENT_ID
  - GOOGLE_CLIENT_SECRET
  - GOOGLE_REDIRECT_URI
tags: [integration, google, calendar, oauth]
---

# Google Calendar

> Único uso ativo do `OAuthConnection` no projeto. Permite a IA criar eventos no calendário do usuário durante conversa com o cliente.

## Auth
**OAuth 2.0** com refresh token persistido.

Scope: `https://www.googleapis.com/auth/calendar.events`

`OAuthConnection` table:
- `tenant_id`, `user_id`, `platform='google'`
- `access_token`, `refresh_token`, `scopes_json`, `expires_at`
- `status` (active/expired/revoked)

## Endpoints usados
- `GET /calendar/v3/calendars/{calendarId}/events` — list (até 7 dias)
- `POST /calendar/v3/calendars/{calendarId}/events` — create
- `PATCH /calendar/v3/calendars/{calendarId}/events/{eventId}` — update (reschedule)
- `DELETE /calendar/v3/calendars/{calendarId}/events/{eventId}` — delete (cancel)

## Service
[[GoogleCalendarService]] — `app/Services/GoogleCalendarService.php`

Métodos:
- `listEvents($timeMin, $timeMax)` — usado pelo agente como contexto + pelo `check_calendar_availability`
- `createEvent($params)` — usado pelo `calendar_create` action
- `updateEvent($eventId, $params)` — usado pelo `calendar_reschedule`
- `deleteEvent($eventId)` — usado pelo `calendar_cancel`

## Calendar ID
Por agente: `AiAgent.calendar_id` (default `'primary'`).

## Importante: SEM sync
**Não existe sync de Google Calendar pra dentro do sistema.** Eventos criados FORA do CRM (ex: vendedor abrindo app do Google Calendar e criando evento) **não viram `EventReminder`**.

User decidiu deliberadamente que não vai cobrir esse caso. Ver [[Calendar & Reminders]] e [[ADR — Caso B (eventos externos) fora de escopo]].

## Decisões
- [[ADR — Caso B (eventos externos) fora de escopo]]
- [[ADR — OAuthConnection só pra Google Calendar (não Meta)]]
