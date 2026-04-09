---
auto_generated: true
type: model
class: App\Models\Automation
table: automations
file: app/Models/Automation.php
tags: [model, auto]
---

# Automation

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/Automation.php`

## Tabela
`automations`

## Traits
- `BelongsToTenant`
- `LogsActivity`

## Fillable
- `tenant_id`
- `name`
- `is_active`
- `trigger_type`
- `trigger_config`
- `conditions`
- `actions`
- `run_count`
- `last_run_at`

## Casts
| Coluna | Cast |
|---|---|
| `is_active` | `boolean` |
| `trigger_config` | `array` |
| `conditions` | `array` |
| `actions` | `array` |
| `run_count` | `integer` |
| `last_run_at` | `datetime` |

## Relações
- `tenant()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[Automation]]`
