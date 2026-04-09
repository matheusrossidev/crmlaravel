---
auto_generated: true
type: model
class: App\Models\UpsellTriggerLog
table: upsell_trigger_logs
file: app/Models/UpsellTriggerLog.php
tags: [model, auto]
---

# UpsellTriggerLog

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/UpsellTriggerLog.php`

## Tabela
`upsell_trigger_logs`

## Traits
- `BelongsToTenant`

## Fillable
- `upsell_trigger_id`
- `tenant_id`
- `action_type`
- `metric_value`
- `metric_limit`
- `fired_at`
- `clicked_at`
- `converted_at`

## Casts
| Coluna | Cast |
|---|---|
| `metric_value` | `integer` |
| `metric_limit` | `integer` |
| `fired_at` | `datetime` |
| `clicked_at` | `datetime` |
| `converted_at` | `datetime` |

## Relações
- `trigger()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[UpsellTriggerLog]]`
