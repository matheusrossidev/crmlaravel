---
auto_generated: true
type: model
class: App\Models\ScoringRule
table: scoring_rules
file: app/Models/ScoringRule.php
tags: [model, auto]
---

# ScoringRule

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/ScoringRule.php`

## Tabela
`scoring_rules`

## Traits
- `BelongsToTenant`
- `LogsActivity`

## Fillable
- `tenant_id`
- `name`
- `category`
- `event_type`
- `pipeline_id`
- `stage_id`
- `conditions`
- `points`
- `is_active`
- `cooldown_hours`
- `valid_from`
- `valid_until`
- `max_triggers_per_lead`
- `sort_order`

## Casts
| Coluna | Cast |
|---|---|
| `conditions` | `array` |
| `points` | `integer` |
| `is_active` | `boolean` |
| `cooldown_hours` | `integer` |
| `sort_order` | `integer` |
| `pipeline_id` | `integer` |
| `stage_id` | `integer` |
| `valid_from` | `date` |
| `valid_until` | `date` |
| `max_triggers_per_lead` | `integer` |

## Relações
- `tenant()` — BelongsTo
- `pipeline()` — BelongsTo
- `stage()` — BelongsTo
- `scoreLogs()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[ScoringRule]]`
