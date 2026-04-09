---
auto_generated: true
type: model
class: App\Models\WhatsappButtonClick
table: whatsapp_button_clicks
file: app/Models/WhatsappButtonClick.php
tags: [model, auto]
---

# WhatsappButtonClick

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/WhatsappButtonClick.php`

## Tabela
`whatsapp_button_clicks`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `button_id`
- `visitor_id`
- `utm_source`
- `utm_medium`
- `utm_campaign`
- `utm_content`
- `utm_term`
- `fbclid`
- `gclid`
- `page_url`
- `referrer_url`
- `device_type`
- `ip_hash`
- `tracking_code`
- `matched`
- `clicked_at`

## Casts
| Coluna | Cast |
|---|---|
| `clicked_at` | `datetime` |
| `matched` | `boolean` |

## Relações
- `button()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[WhatsappButtonClick]]`
