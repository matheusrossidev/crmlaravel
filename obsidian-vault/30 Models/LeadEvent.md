---
auto_generated: true
type: model
class: App\Models\LeadEvent
table: lead_events
file: app/Models/LeadEvent.php
tags: [model, auto]
---

# LeadEvent

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/LeadEvent.php`

## Tabela
`lead_events`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `lead_id`
- `event_type`
- `description`
- `data_json`
- `performed_by`
- `created_at`

## Casts
| Coluna | Cast |
|---|---|
| `data_json` | `array` |
| `created_at` | `datetime` |

## Relações
- `lead()` — BelongsTo
- `performedBy()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[LeadEvent]]`
