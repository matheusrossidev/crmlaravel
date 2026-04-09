---
auto_generated: true
type: model
class: App\Models\InstagramMessage
table: instagram_messages
file: app/Models/InstagramMessage.php
tags: [model, auto]
---

# InstagramMessage

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/InstagramMessage.php`

## Tabela
`instagram_messages`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `conversation_id`
- `ig_message_id`
- `direction`
- `type`
- `body`
- `media_url`
- `is_deleted`
- `ack`
- `user_id`
- `sent_at`

## Casts
| Coluna | Cast |
|---|---|
| `is_deleted` | `boolean` |
| `sent_at` | `datetime` |

## Relações
- `conversation()` — BelongsTo
- `user()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[InstagramMessage]]`
