---
auto_generated: true
type: model
class: App\Models\FacebookLeadFormConnection
table: facebook_lead_form_connections
file: app/Models/FacebookLeadFormConnection.php
tags: [model, auto]
---

# FacebookLeadFormConnection

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/FacebookLeadFormConnection.php`

## Tabela
`facebook_lead_form_connections`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `oauth_connection_id`
- `page_id`
- `page_name`
- `page_access_token`
- `form_id`
- `form_name`
- `form_fields_json`
- `pipeline_id`
- `stage_id`
- `field_mapping`
- `default_tags`
- `auto_assign_to`
- `is_active`
- `allow_duplicates`

## Casts
| Coluna | Cast |
|---|---|
| `field_mapping` | `array` |
| `default_tags` | `array` |
| `form_fields_json` | `array` |
| `page_access_token` | `encrypted` |
| `is_active` | `boolean` |
| `allow_duplicates` | `boolean` |

## Relações
- `oauthConnection()` — BelongsTo
- `pipeline()` — BelongsTo
- `stage()` — BelongsTo
- `assignee()` — BelongsTo
- `entries()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[FacebookLeadFormConnection]]`
