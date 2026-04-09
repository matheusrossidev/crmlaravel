---
auto_generated: true
type: model
class: App\Models\TenantTokenIncrement
table: tenant_token_increments
file: app/Models/TenantTokenIncrement.php
tags: [model, auto]
---

# TenantTokenIncrement

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/TenantTokenIncrement.php`

## Tabela
`tenant_token_increments`

## Fillable
- `tenant_id`
- `token_increment_plan_id`
- `tokens_added`
- `price_paid`
- `asaas_payment_id`
- `status`
- `paid_at`

## Casts
| Coluna | Cast |
|---|---|
| `tokens_added` | `integer` |
| `price_paid` | `decimal:2` |
| `paid_at` | `datetime` |

## Relações
- `tenant()` — BelongsTo
- `plan()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[TenantTokenIncrement]]`
