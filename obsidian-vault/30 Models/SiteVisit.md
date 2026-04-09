---
auto_generated: true
type: model
class: App\Models\SiteVisit
table: site_visits
file: app/Models/SiteVisit.php
tags: [model, auto]
---

# SiteVisit

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/SiteVisit.php`

## Tabela
`site_visits`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `visitor_id`
- `lead_id`
- `page_url`
- `page_title`
- `referrer`
- `utm_source`
- `utm_medium`
- `utm_campaign`
- `utm_content`
- `utm_term`
- `ip_address`
- `user_agent`

## Casts
| Coluna | Cast |
|---|---|
| `created_at` | `datetime` |

## Relações
- `lead()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[SiteVisit]]`
