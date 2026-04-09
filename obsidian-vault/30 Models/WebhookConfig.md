---
auto_generated: true
type: model
class: App\Models\WebhookConfig
table: webhook_configs
file: app/Models/WebhookConfig.php
tags: [model, auto]
---

# WebhookConfig

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/WebhookConfig.php`

## Tabela
`webhook_configs`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `name`
- `url`
- `secret`
- `events_json`
- `headers_json`
- `is_active`
- `retry_count`

## Casts
| Coluna | Cast |
|---|---|
| `events_json` | `array` |
| `headers_json` | `array` |
| `is_active` | `boolean` |
| `retry_count` | `integer` |

## Relações
- `tenant()` — BelongsTo
- `logs()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[WebhookConfig]]`
