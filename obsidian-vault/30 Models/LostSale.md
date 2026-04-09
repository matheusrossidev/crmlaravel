---
auto_generated: true
type: model
class: App\Models\LostSale
table: lost_sales
file: app/Models/LostSale.php
tags: [model, auto]
---

# LostSale

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/LostSale.php`

## Tabela
`lost_sales`

## Traits
- `BelongsToTenant`
- `LogsActivity`

## Fillable
- `tenant_id`
- `lead_id`
- `pipeline_id`
- `reason_id`
- `reason_notes`
- `lost_at`
- `lost_by`

## Casts
| Coluna | Cast |
|---|---|
| `lost_at` | `datetime` |
| `created_at` | `datetime` |

## Relações
- `lead()` — BelongsTo
- `pipeline()` — BelongsTo
- `reason()` — BelongsTo
- `lostBy()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[LostSale]]`
