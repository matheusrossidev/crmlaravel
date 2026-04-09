---
auto_generated: true
type: model
class: App\Models\NurtureSequence
table: nurture_sequences
file: app/Models/NurtureSequence.php
tags: [model, auto]
---

# NurtureSequence

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/NurtureSequence.php`

## Tabela
`nurture_sequences`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `name`
- `description`
- `is_active`
- `channel`
- `exit_on_reply`
- `exit_on_stage_change`
- `stats_enrolled`
- `stats_completed`

## Casts
| Coluna | Cast |
|---|---|
| `is_active` | `boolean` |
| `exit_on_reply` | `boolean` |
| `exit_on_stage_change` | `boolean` |
| `stats_enrolled` | `integer` |
| `stats_completed` | `integer` |

## Relações
- `tenant()` — BelongsTo
- `steps()` — HasMany
- `leadSequences()` — HasMany
- `activeLeadSequences()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[NurtureSequence]]`
