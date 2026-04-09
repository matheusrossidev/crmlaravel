---
auto_generated: true
type: model
class: App\Models\LeadDuplicate
table: lead_duplicates
file: app/Models/LeadDuplicate.php
tags: [model, auto]
---

# LeadDuplicate

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/LeadDuplicate.php`

## Tabela
`lead_duplicates`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `lead_id_a`
- `lead_id_b`
- `score`
- `status`
- `detected_by`
- `reviewed_by`
- `reviewed_at`
- `created_at`

## Casts
| Coluna | Cast |
|---|---|
| `score` | `integer` |
| `reviewed_at` | `datetime` |
| `created_at` | `datetime` |

## Relações
- `leadA()` — BelongsTo
- `leadB()` — BelongsTo
- `reviewedBy()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[LeadDuplicate]]`
