---
auto_generated: true
type: model
class: App\Models\Pipeline
table: pipelines
file: app/Models/Pipeline.php
tags: [model, auto]
---

# Pipeline

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/Pipeline.php`

## Tabela
`pipelines`

## Traits
- `BelongsToTenant`
- `LogsActivity`

## Fillable
- `tenant_id`
- `name`
- `color`
- `is_default`
- `sort_order`
- `auto_create_lead`
- `auto_create_from_whatsapp`
- `auto_create_from_instagram`

## Casts
| Coluna | Cast |
|---|---|
| `is_default` | `boolean` |
| `sort_order` | `integer` |
| `auto_create_lead` | `boolean` |
| `auto_create_from_whatsapp` | `boolean` |
| `auto_create_from_instagram` | `boolean` |

## Relações
- `tenant()` — BelongsTo
- `stages()` — HasMany
- `leads()` — HasMany
- `users()` — BelongsToMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[Pipeline]]`
