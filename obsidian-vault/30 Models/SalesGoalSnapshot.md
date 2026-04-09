---
auto_generated: true
type: model
class: App\Models\SalesGoalSnapshot
table: sales_goal_snapshots
file: app/Models/SalesGoalSnapshot.php
tags: [model, auto]
---

# SalesGoalSnapshot

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/SalesGoalSnapshot.php`

## Tabela
`sales_goal_snapshots`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `user_id`
- `goal_id`
- `type`
- `period`
- `target_value`
- `achieved_value`
- `percentage`
- `start_date`
- `end_date`
- `created_at`

## Casts
| Coluna | Cast |
|---|---|
| `target_value` | `decimal:2` |
| `achieved_value` | `decimal:2` |
| `percentage` | `decimal:1` |
| `start_date` | `date` |
| `end_date` | `date` |
| `created_at` | `datetime` |

## Relações
- `goal()` — BelongsTo
- `user()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[SalesGoalSnapshot]]`
