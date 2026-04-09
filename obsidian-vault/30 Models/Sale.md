---
auto_generated: true
type: model
class: App\Models\Sale
table: sales
file: app/Models/Sale.php
tags: [model, auto]
---

# Sale

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/Sale.php`

## Tabela
`sales`

## Traits
- `BelongsToTenant`
- `LogsActivity`

## Fillable
- `tenant_id`
- `lead_id`
- `pipeline_id`
- `value`
- `closed_by`
- `closed_at`
- `notes`

## Casts
| Coluna | Cast |
|---|---|
| `value` | `decimal:2` |
| `closed_at` | `datetime` |
| `created_at` | `datetime` |

## Relações
- `lead()` — BelongsTo
- `pipeline()` — BelongsTo
- `closedBy()` — BelongsTo
- `items()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[Sale]]`
