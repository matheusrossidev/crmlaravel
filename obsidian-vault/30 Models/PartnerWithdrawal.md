---
auto_generated: true
type: model
class: App\Models\PartnerWithdrawal
table: partner_withdrawals
file: app/Models/PartnerWithdrawal.php
tags: [model, auto]
---

# PartnerWithdrawal

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/PartnerWithdrawal.php`

## Tabela
`partner_withdrawals`

## Fillable
- `tenant_id`
- `amount`
- `status`
- `pix_key`
- `pix_key_type`
- `pix_holder_name`
- `pix_holder_cpf_cnpj`
- `asaas_transfer_id`
- `requested_at`
- `approved_at`
- `paid_at`
- `rejected_reason`

## Casts
| Coluna | Cast |
|---|---|
| `amount` | `decimal:2` |
| `requested_at` | `datetime` |
| `approved_at` | `datetime` |
| `paid_at` | `datetime` |

## Relações
- `partner()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[PartnerWithdrawal]]`
