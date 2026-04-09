---
auto_generated: true
type: model
class: App\Models\ChatbotFlow
table: chatbot_flows
file: app/Models/ChatbotFlow.php
tags: [model, auto]
---

# ChatbotFlow

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/ChatbotFlow.php`

## Tabela
`chatbot_flows`

## Traits
- `BelongsToTenant`
- `LogsActivity`

## Fillable
- `tenant_id`
- `name`
- `slug`
- `channel`
- `website_token`
- `description`
- `is_active`
- `is_catch_all`
- `trigger_keywords`
- `trigger_type`
- `trigger_media_id`
- `trigger_media_thumbnail`
- `trigger_media_caption`
- `trigger_reply_comment`
- `completions_count`
- `variables`
- `steps`
- `bot_name`
- `bot_avatar`
- `welcome_message`
- `widget_type`
- `widget_color`

## Casts
| Coluna | Cast |
|---|---|
| `is_active` | `boolean` |
| `is_catch_all` | `boolean` |
| `trigger_keywords` | `array` |
| `variables` | `array` |
| `steps` | `array` |

## Relações
- `nodes()` — HasMany
- `edges()` — HasMany
- `conversations()` — HasMany
- `websiteConversations()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[ChatbotFlow]]`
