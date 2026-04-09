---
auto_generated: true
type: model
class: App\Models\InstagramInstance
table: instagram_instances
file: app/Models/InstagramInstance.php
tags: [model, auto]
---

# InstagramInstance

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/InstagramInstance.php`

## Tabela
`instagram_instances`

## Traits
- `BelongsToTenant`
- `LogsActivity`

## Fillable
- `tenant_id`
- `instagram_account_id`
- `ig_business_account_id`
- `ig_page_id`
- `username`
- `profile_picture_url`
- `access_token`
- `token_expires_at`
- `status`

## Casts
| Coluna | Cast |
|---|---|
| `token_expires_at` | `datetime` |

## Relações
- `tenant()` — BelongsTo
- `conversations()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[InstagramInstance]]`
