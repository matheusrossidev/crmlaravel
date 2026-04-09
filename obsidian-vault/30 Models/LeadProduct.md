---
auto_generated: true
type: model
class: App\Models\LeadProduct
table: lead_products
file: app/Models/LeadProduct.php
tags: [model, auto]
---

# LeadProduct

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/LeadProduct.php`

## Tabela
`lead_products`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `lead_id`
- `product_id`
- `quantity`
- `unit_price`
- `discount_percent`
- `total`
- `notes`

## Casts
| Coluna | Cast |
|---|---|
| `quantity` | `decimal:2` |
| `unit_price` | `decimal:2` |
| `discount_percent` | `decimal:2` |
| `total` | `decimal:2` |

## Relações
- `lead()` — BelongsTo
- `product()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[LeadProduct]]`
