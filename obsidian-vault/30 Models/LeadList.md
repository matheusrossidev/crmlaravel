---
auto_generated: true
type: model
class: App\Models\LeadList
table: lead_lists
file: app/Models/LeadList.php
tags: [model, auto]
---

# LeadList

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/LeadList.php`

## Tabela
`lead_lists`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `name`
- `description`
- `type`
- `filters`
- `lead_count`
- `created_by`

## Casts
| Coluna | Cast |
|---|---|
| `filters` | `array` |
| `lead_count` | `integer` |

## Relações
- `members()` — BelongsToMany
- `createdBy()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[LeadList]]`
