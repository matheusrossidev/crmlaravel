---
auto_generated: true
type: model
class: App\Models\LeadContact
table: lead_contacts
file: app/Models/LeadContact.php
tags: [model, auto]
---

# LeadContact

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/LeadContact.php`

## Tabela
`lead_contacts`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `lead_id`
- `name`
- `role`
- `phone`
- `email`
- `is_primary`

## Casts
| Coluna | Cast |
|---|---|
| `is_primary` | `boolean` |

## Relações
- `lead()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[LeadContact]]`
