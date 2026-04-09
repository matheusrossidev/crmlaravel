---
auto_generated: true
type: model
class: App\Models\Product
table: products
file: app/Models/Product.php
tags: [model, auto]
---

# Product

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/Product.php`

## Tabela
`products`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `name`
- `description`
- `sku`
- `price`
- `cost_price`
- `category`
- `category_id`
- `unit`
- `is_active`
- `sort_order`

## Casts
| Coluna | Cast |
|---|---|
| `price` | `decimal:2` |
| `cost_price` | `decimal:2` |
| `is_active` | `boolean` |

## Relações
- `categoryRelation()` — BelongsTo
- `media()` — HasMany
- `leadProducts()` — HasMany
- `saleItems()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[Product]]`
