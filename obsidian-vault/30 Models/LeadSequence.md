---
auto_generated: true
type: model
class: App\Models\LeadSequence
table: lead_sequences
file: app/Models/LeadSequence.php
tags: [model, auto]
---

# LeadSequence

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/LeadSequence.php`

## Tabela
`lead_sequences`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `lead_id`
- `sequence_id`
- `current_step_position`
- `status`
- `next_step_at`
- `started_at`
- `completed_at`
- `exited_at`
- `exit_reason`

## Casts
| Coluna | Cast |
|---|---|
| `current_step_position` | `integer` |
| `next_step_at` | `datetime` |
| `started_at` | `datetime` |
| `completed_at` | `datetime` |
| `exited_at` | `datetime` |

## Relações
- `tenant()` — BelongsTo
- `lead()` — BelongsTo
- `sequence()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[LeadSequence]]`
