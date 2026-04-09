---
auto_generated: true
type: model
class: App\Models\PartnerRank
table: partner_ranks
file: app/Models/PartnerRank.php
tags: [model, auto]
---

# PartnerRank

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/PartnerRank.php`

## Tabela
`partner_ranks`

## Fillable
- `name`
- `image_path`
- `min_sales`
- `commission_pct`
- `sort_order`
- `color`

## Casts
| Coluna | Cast |
|---|---|
| `min_sales` | `integer` |
| `commission_pct` | `decimal:2` |
| `sort_order` | `integer` |

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[PartnerRank]]`
