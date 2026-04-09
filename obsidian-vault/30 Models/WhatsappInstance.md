---
auto_generated: true
type: model
class: App\Models\WhatsappInstance
table: whatsapp_instances
file: app/Models/WhatsappInstance.php
tags: [model, auto]
---

# WhatsappInstance

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/WhatsappInstance.php`

## Tabela
`whatsapp_instances`

## Traits
- `BelongsToTenant`
- `LogsActivity`

## Fillable
- `tenant_id`
- `session_name`
- `status`
- `provider`
- `phone_number`
- `phone_number_id`
- `waba_id`
- `business_account_id`
- `access_token`
- `token_expires_at`
- `display_name`
- `label`
- `is_primary`
- `history_imported`

## Casts
| Coluna | Cast |
|---|---|
| `history_imported` | `boolean` |
| `is_primary` | `boolean` |
| `access_token` | `encrypted` |
| `token_expires_at` | `datetime` |

## Relações
- `conversations()` — HasMany
- `aiAgents()` — BelongsToMany
- `users()` — BelongsToMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[WhatsappInstance]]`
