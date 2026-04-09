---
auto_generated: true
type: model
class: App\Models\FacebookLeadFormEntry
table: facebook_lead_form_entries
file: app/Models/FacebookLeadFormEntry.php
tags: [model, auto]
---

# FacebookLeadFormEntry

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/FacebookLeadFormEntry.php`

## Tabela
`facebook_lead_form_entries`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `connection_id`
- `meta_lead_id`
- `lead_id`
- `platform`
- `ad_id`
- `campaign_name_meta`
- `raw_data`
- `status`
- `error_message`

## Casts
| Coluna | Cast |
|---|---|
| `raw_data` | `array` |

## Relações
- `connection()` — BelongsTo
- `lead()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[FacebookLeadFormEntry]]`
