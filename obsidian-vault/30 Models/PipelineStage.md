---
auto_generated: true
type: model
class: App\Models\PipelineStage
table: pipeline_stages
file: app/Models/PipelineStage.php
tags: [model, auto]
---

# PipelineStage

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/PipelineStage.php`

## Tabela
`pipeline_stages`

## Fillable
- `pipeline_id`
- `name`
- `color`
- `position`
- `is_won`
- `is_lost`

## Casts
| Coluna | Cast |
|---|---|
| `position` | `integer` |
| `is_won` | `boolean` |
| `is_lost` | `boolean` |

## Relações
- `pipeline()` — BelongsTo
- `leads()` — HasMany
- `requiredTasks()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[PipelineStage]]`
