---
auto_generated: true
type: model
class: App\Models\WebhookLog
table: webhook_logs
file: app/Models/WebhookLog.php
tags: [model, auto]
---

# WebhookLog

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/WebhookLog.php`

## Tabela
`webhook_logs`

## Fillable
- `webhook_config_id`
- `event_type`
- `payload_json`
- `response_code`
- `response_body`
- `status`
- `attempts`
- `next_retry_at`
- `last_attempt_at`

## Casts
| Coluna | Cast |
|---|---|
| `payload_json` | `array` |
| `response_code` | `integer` |
| `attempts` | `integer` |
| `next_retry_at` | `datetime` |
| `last_attempt_at` | `datetime` |
| `created_at` | `datetime` |

## Relações
- `webhookConfig()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[WebhookLog]]`
