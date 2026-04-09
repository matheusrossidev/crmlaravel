---
auto_generated: true
type: model
class: App\Models\WhatsappMessage
table: whatsapp_messages
file: app/Models/WhatsappMessage.php
tags: [model, auto]
---

# WhatsappMessage

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/WhatsappMessage.php`

## Tabela
`whatsapp_messages`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `conversation_id`
- `waha_message_id`
- `cloud_message_id`
- `direction`
- `sender_name`
- `type`
- `body`
- `media_url`
- `media_mime`
- `media_filename`
- `reaction_data`
- `user_id`
- `ack`
- `is_deleted`
- `sent_at`

## Casts
| Coluna | Cast |
|---|---|
| `reaction_data` | `array` |
| `sent_at` | `datetime` |
| `is_deleted` | `boolean` |

## Relações
- `conversation()` — BelongsTo
- `user()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[WhatsappMessage]]`
