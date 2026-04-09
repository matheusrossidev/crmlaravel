---
auto_generated: true
type: model
class: App\Models\WebsiteMessage
table: website_messages
file: app/Models/WebsiteMessage.php
tags: [model, auto]
---

# WebsiteMessage

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/WebsiteMessage.php`

## Tabela
`website_messages`

## Fillable
- `conversation_id`
- `direction`
- `content`
- `sent_at`

## Casts
| Coluna | Cast |
|---|---|
| `sent_at` | `datetime` |

## Relações
- `conversation()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[WebsiteMessage]]`
