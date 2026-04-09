---
auto_generated: true
type: model
class: App\Models\ApiKey
table: api_keys
file: app/Models/ApiKey.php
tags: [model, auto]
---

# ApiKey

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/ApiKey.php`

## Tabela
`api_keys`

## Traits
- `BelongsToTenant`
- `LogsActivity`

## Fillable
- `tenant_id`
- `name`
- `key_hash`
- `key_prefix`
- `permissions_json`
- `last_used_at`
- `expires_at`
- `is_active`

## Casts
| Coluna | Cast |
|---|---|
| `permissions_json` | `array` |
| `last_used_at` | `datetime` |
| `expires_at` | `datetime` |
| `is_active` | `boolean` |
| `created_at` | `datetime` |

## Relações
- `tenant()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[ApiKey]]`
