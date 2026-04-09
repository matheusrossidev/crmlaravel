---
auto_generated: true
type: model
class: App\Models\StageRequiredTask
table: stage_required_tasks
file: app/Models/StageRequiredTask.php
tags: [model, auto]
---

# StageRequiredTask

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/StageRequiredTask.php`

## Tabela
`stage_required_tasks`

## Fillable
- `pipeline_stage_id`
- `subject`
- `description`
- `task_type`
- `priority`
- `due_date_offset`
- `sort_order`

## Casts
| Coluna | Cast |
|---|---|
| `due_date_offset` | `integer` |
| `sort_order` | `integer` |

## Relações
- `stage()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[StageRequiredTask]]`
