---
auto_generated: true
type: model
class: App\Models\Tag
table: tags
file: app/Models/Tag.php
tags: [model, auto]
---

# Tag

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/Tag.php`

## Tabela
`tags`

## Traits
- `BelongsToTenant`
- `LogsActivity`

## Fillable
- `tenant_id`
- `name`
- `color`
- `sort_order`
- `applies_to`

## Casts
| Coluna | Cast |
|---|---|
| `sort_order` | `integer` |

## Relações
- `leads()` — MorphToMany
- `whatsappConversations()` — MorphToMany
- `instagramConversations()` — MorphToMany
- `websiteConversations()` — MorphToMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[Tag]]`
