---
auto_generated: true
type: model
class: App\Models\NurtureSequenceStep
table: nurture_sequence_steps
file: app/Models/NurtureSequenceStep.php
tags: [model, auto]
---

# NurtureSequenceStep

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/NurtureSequenceStep.php`

## Tabela
`nurture_sequence_steps`

## Fillable
- `sequence_id`
- `position`
- `delay_minutes`
- `type`
- `config`
- `is_active`

## Casts
| Coluna | Cast |
|---|---|
| `position` | `integer` |
| `delay_minutes` | `integer` |
| `config` | `array` |
| `is_active` | `boolean` |

## Relações
- `sequence()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[NurtureSequenceStep]]`
