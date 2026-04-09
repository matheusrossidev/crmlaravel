---
auto_generated: true
type: model
class: App\Models\SaleItem
table: sale_items
file: app/Models/SaleItem.php
tags: [model, auto]
---

# SaleItem

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/SaleItem.php`

## Tabela
`sale_items`

## Fillable
- `sale_id`
- `product_id`
- `description`
- `quantity`
- `unit_price`
- `total`
- `created_at`

## Casts
| Coluna | Cast |
|---|---|
| `quantity` | `decimal:2` |
| `unit_price` | `decimal:2` |
| `total` | `decimal:2` |
| `created_at` | `datetime` |

## Relações
- `sale()` — BelongsTo
- `product()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[SaleItem]]`
