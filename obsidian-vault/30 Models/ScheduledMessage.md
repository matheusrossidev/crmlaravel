---
auto_generated: true
type: model
class: App\Models\ScheduledMessage
table: scheduled_messages
file: app/Models/ScheduledMessage.php
tags: [model, auto]
---

# ScheduledMessage

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/ScheduledMessage.php`

## Tabela
`scheduled_messages`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `lead_id`
- `conversation_id`
- `instance_id`
- `created_by`
- `type`
- `body`
- `media_path`
- `media_mime`
- `media_filename`
- `quick_message_id`
- `send_at`
- `sent_at`
- `status`
- `error`

## Casts
| Coluna | Cast |
|---|---|
| `send_at` | `datetime` |
| `sent_at` | `datetime` |
| `lead_id` | `integer` |
| `conversation_id` | `integer` |
| `instance_id` | `integer` |
| `created_by` | `integer` |
| `quick_message_id` | `integer` |

## Relações
- `lead()` — BelongsTo
- `conversation()` — BelongsTo
- `instance()` — BelongsTo
- `createdBy()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[ScheduledMessage]]`
