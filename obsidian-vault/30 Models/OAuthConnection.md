---
auto_generated: true
type: model
class: App\Models\OAuthConnection
table: oauth_connections
file: app/Models/OAuthConnection.php
tags: [model, auto]
---

# OAuthConnection

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/OAuthConnection.php`

## Tabela
`oauth_connections`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `platform`
- `platform_user_id`
- `platform_user_name`
- `access_token`
- `refresh_token`
- `token_expires_at`
- `scopes_json`
- `status`
- `last_sync_at`

## Casts
| Coluna | Cast |
|---|---|
| `access_token` | `encrypted` |
| `refresh_token` | `encrypted` |
| `scopes_json` | `array` |
| `token_expires_at` | `datetime` |
| `last_sync_at` | `datetime` |

## Relações
- `tenant()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[OAuthConnection]]`
