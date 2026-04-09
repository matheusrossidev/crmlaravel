---
auto_generated: true
type: model
class: App\Models\UpsellTrigger
table: upsell_triggers
file: app/Models/UpsellTrigger.php
tags: [model, auto]
---

# UpsellTrigger

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/UpsellTrigger.php`

## Tabela
`upsell_triggers`

## Fillable
- `name`
- `source_plan`
- `target_plan`
- `metric`
- `threshold_type`
- `threshold_value`
- `action_type`
- `action_config`
- `cooldown_hours`
- `priority`
- `is_active`

## Casts
| Coluna | Cast |
|---|---|
| `threshold_value` | `decimal:2` |
| `action_config` | `array` |
| `cooldown_hours` | `integer` |
| `priority` | `integer` |
| `is_active` | `boolean` |

## Relações
- `logs()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[UpsellTrigger]]`
