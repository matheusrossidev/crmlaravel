---
auto_generated: true
type: model
class: App\Models\SiteEvent
table: site_events
file: app/Models/SiteEvent.php
tags: [model, auto]
---

# SiteEvent

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/SiteEvent.php`

## Tabela
`site_events`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `visitor_id`
- `lead_id`
- `event_name`
- `event_data_json`
- `page_url`

## Casts
| Coluna | Cast |
|---|---|
| `event_data_json` | `array` |
| `created_at` | `datetime` |

## Relações
- `lead()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[SiteEvent]]`
