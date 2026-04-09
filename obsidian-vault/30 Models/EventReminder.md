---
auto_generated: true
type: model
class: App\Models\EventReminder
table: event_reminders
file: app/Models/EventReminder.php
tags: [model, auto]
---

# EventReminder

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/EventReminder.php`

## Tabela
`event_reminders`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `lead_id`
- `conversation_id`
- `ai_agent_id`
- `google_event_id`
- `event_title`
- `event_starts_at`
- `offset_minutes`
- `send_at`
- `body`
- `status`
- `error`
- `sent_at`

## Casts
| Coluna | Cast |
|---|---|
| `event_starts_at` | `datetime` |
| `send_at` | `datetime` |
| `sent_at` | `datetime` |
| `offset_minutes` | `integer` |

## Relações
- `lead()` — BelongsTo
- `conversation()` — BelongsTo
- `aiAgent()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[EventReminder]]`
