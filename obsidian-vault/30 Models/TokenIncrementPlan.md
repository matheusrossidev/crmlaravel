---
auto_generated: true
type: model
class: App\Models\TokenIncrementPlan
table: token_increment_plans
file: app/Models/TokenIncrementPlan.php
tags: [model, auto]
---

# TokenIncrementPlan

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/TokenIncrementPlan.php`

## Tabela
`token_increment_plans`

## Fillable
- `display_name`
- `tokens_amount`
- `price`
- `price_usd`
- `stripe_price_id`
- `is_active`

## Casts
| Coluna | Cast |
|---|---|
| `tokens_amount` | `integer` |
| `price` | `decimal:2` |
| `price_usd` | `decimal:2` |
| `is_active` | `boolean` |

## Relações
- `increments()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[TokenIncrementPlan]]`
