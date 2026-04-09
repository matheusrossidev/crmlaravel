---
auto_generated: true
type: model
class: App\Models\WhatsappButton
table: whatsapp_buttons
file: app/Models/WhatsappButton.php
tags: [model, auto]
---

# WhatsappButton

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/WhatsappButton.php`

## Tabela
`whatsapp_buttons`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `phone_number`
- `default_message`
- `button_label`
- `website_token`
- `show_floating`
- `is_active`

## Casts
| Coluna | Cast |
|---|---|
| `show_floating` | `boolean` |
| `is_active` | `boolean` |

## Relações
- `clicks()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[WhatsappButton]]`
