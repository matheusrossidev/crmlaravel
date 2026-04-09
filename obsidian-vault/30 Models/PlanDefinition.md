---
auto_generated: true
type: model
class: App\Models\PlanDefinition
table: plan_definitions
file: app/Models/PlanDefinition.php
tags: [model, auto]
---

# PlanDefinition

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/PlanDefinition.php`

## Tabela
`plan_definitions`

## Fillable
- `name`
- `display_name`
- `price_monthly`
- `price_usd`
- `stripe_price_id`
- `stripe_price_id_brl`
- `stripe_price_id_usd`
- `trial_days`
- `features_json`
- `features_en_json`
- `is_active`
- `is_visible`

## Casts
| Coluna | Cast |
|---|---|
| `price_monthly` | `decimal:2` |
| `price_usd` | `decimal:2` |
| `trial_days` | `integer` |
| `features_json` | `array` |
| `features_en_json` | `array` |
| `is_active` | `boolean` |
| `is_visible` | `boolean` |

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[PlanDefinition]]`
