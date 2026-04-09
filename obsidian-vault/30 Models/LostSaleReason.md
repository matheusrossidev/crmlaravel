---
auto_generated: true
type: model
class: App\Models\LostSaleReason
table: lost_sale_reasons
file: app/Models/LostSaleReason.php
tags: [model, auto]
---

# LostSaleReason

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/LostSaleReason.php`

## Tabela
`lost_sale_reasons`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `name`
- `sort_order`
- `is_active`

## Casts
| Coluna | Cast |
|---|---|
| `sort_order` | `integer` |
| `is_active` | `boolean` |

## Relações
- `lostSales()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[LostSaleReason]]`
