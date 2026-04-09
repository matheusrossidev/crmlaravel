---
auto_generated: true
type: model
class: App\Models\InstagramAutomation
table: instagram_automations
file: app/Models/InstagramAutomation.php
tags: [model, auto]
---

# InstagramAutomation

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/InstagramAutomation.php`

## Tabela
`instagram_automations`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `instance_id`
- `name`
- `media_id`
- `media_thumbnail_url`
- `media_caption`
- `media_type`
- `keywords`
- `match_type`
- `reply_comment`
- `dm_message`
- `is_active`
- `comments_replied`
- `dms_sent`
- `dm_messages`

## Casts
| Coluna | Cast |
|---|---|
| `keywords` | `array` |
| `is_active` | `boolean` |
| `comments_replied` | `integer` |
| `dms_sent` | `integer` |
| `dm_messages` | `array` |

## Relações
- `instance()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[InstagramAutomation]]`
