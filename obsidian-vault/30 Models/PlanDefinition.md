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
- `billing_cycle` — `monthly` ou `yearly`
- `group_slug` — vincula variantes mensal↔anual do mesmo tier (ex: `starter`)
- `is_recommended` — 1 por ciclo, mostra badge "Mais popular" no checkout

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
| `is_recommended` | `boolean` |

## Helpers (abr/2026)
- `isYearly()` / `isMonthly()` — bool
- `yearlyVariant()` / `monthlyVariant()` — busca row irmã no mesmo `group_slug`
- `yearlyDiscountPctVs($monthly, $currency)` — `(1 - yearly / (monthly×12)) × 100`
- `stripePriceIdFor($currency)` — resolve BRL/USD com fallback legacy
- `priceFor($currency)` — retorna preço na moeda

## Links sugeridos
- [[Billing (Stripe + Asaas)]] — módulo de billing completo
- Notas escritas à mão sobre esse model: procure no vault por `[[PlanDefinition]]`
