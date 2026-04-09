---
auto_generated: true
type: model
class: App\Models\LeadNote
table: lead_notes
file: app/Models/LeadNote.php
tags: [model, auto]
---

# LeadNote

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/LeadNote.php`

## Tabela
`lead_notes`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `lead_id`
- `body`
- `created_by`

## Casts
| Coluna | Cast |
|---|---|
| `created_at` | `datetime` |
| `updated_at` | `datetime` |

## Relações
- `lead()` — BelongsTo
- `author()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[LeadNote]]`
