---
auto_generated: true
type: model
class: App\Models\PaymentLog
table: payment_logs
file: app/Models/PaymentLog.php
tags: [model, auto]
---

# PaymentLog

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/PaymentLog.php`

## Tabela
`payment_logs`

## Fillable
- `tenant_id`
- `type`
- `description`
- `amount`
- `asaas_payment_id`
- `status`
- `paid_at`

## Casts
| Coluna | Cast |
|---|---|
| `amount` | `decimal:2` |
| `paid_at` | `datetime` |

## Relações
- `tenant()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[PaymentLog]]`
