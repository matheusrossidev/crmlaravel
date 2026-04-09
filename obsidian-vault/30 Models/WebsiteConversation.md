---
auto_generated: true
type: model
class: App\Models\WebsiteConversation
table: website_conversations
file: app/Models/WebsiteConversation.php
tags: [model, auto]
---

# WebsiteConversation

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/WebsiteConversation.php`

## Tabela
`website_conversations`

## Traits
- `BelongsToTenant`
- `HasTags`

## Fillable
- `tenant_id`
- `flow_id`
- `ai_agent_id`
- `visitor_id`
- `contact_name`
- `contact_email`
- `contact_phone`
- `lead_id`
- `chatbot_node_id`
- `chatbot_cursor`
- `chatbot_variables`
- `tags`
- `status`
- `unread_count`
- `started_at`
- `last_message_at`
- `utm_id`
- `utm_source`
- `utm_medium`
- `utm_campaign`
- `utm_content`
- `utm_term`
- `fbclid`
- `gclid`
- `page_url`
- `referrer_url`

## Casts
| Coluna | Cast |
|---|---|
| `chatbot_variables` | `array` |
| `chatbot_cursor` | `array` |
| `tags` | `array` |
| `started_at` | `datetime` |
| `last_message_at` | `datetime` |

## Relações
- `flow()` — BelongsTo
- `aiAgent()` — BelongsTo
- `lead()` — BelongsTo
- `messages()` — HasMany
- `latestMessage()` — HasOne
- `tagModels()` — MorphToMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[WebsiteConversation]]`
