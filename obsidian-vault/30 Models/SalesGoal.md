---
auto_generated: true
type: model
class: App\Models\SalesGoal
table: sales_goals
file: app/Models/SalesGoal.php
tags: [model, auto]
---

# SalesGoal

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/SalesGoal.php`

## Tabela
`sales_goals`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `user_id`
- `type`
- `period`
- `target_value`
- `start_date`
- `end_date`
- `created_by`
- `is_recurring`
- `growth_rate`
- `parent_goal_id`
- `bonus_tiers`

## Casts
| Coluna | Cast |
|---|---|
| `target_value` | `decimal:2` |
| `growth_rate` | `decimal:2` |
| `start_date` | `date` |
| `end_date` | `date` |
| `is_recurring` | `boolean` |
| `bonus_tiers` | `array` |

## Relações
- `user()` — BelongsTo
- `createdBy()` — BelongsTo
- `parent()` — BelongsTo
- `children()` — HasMany
- `snapshots()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[SalesGoal]]`
