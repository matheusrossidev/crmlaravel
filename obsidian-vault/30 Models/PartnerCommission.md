---
auto_generated: true
type: model
class: App\Models\PartnerCommission
table: partner_commissions
file: app/Models/PartnerCommission.php
tags: [model, auto]
---

# PartnerCommission

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/PartnerCommission.php`

## Tabela
`partner_commissions`

## Fillable
- `tenant_id`
- `client_tenant_id`
- `asaas_payment_id`
- `amount`
- `status`
- `available_at`

## Casts
| Coluna | Cast |
|---|---|
| `amount` | `decimal:2` |
| `available_at` | `date` |

## Relações
- `partner()` — BelongsTo
- `clientTenant()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[PartnerCommission]]`
