---
auto_generated: true
type: model
class: App\Models\InstagramConversation
table: instagram_conversations
file: app/Models/InstagramConversation.php
tags: [model, auto]
---

# InstagramConversation

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/InstagramConversation.php`

## Tabela
`instagram_conversations`

## Traits
- `BelongsToTenant`
- `HasTags`

## Fillable
- `tenant_id`
- `instance_id`
- `lead_id`
- `igsid`
- `contact_name`
- `contact_username`
- `contact_picture_url`
- `tags`
- `assigned_user_id`
- `department_id`
- `ai_agent_id`
- `chatbot_flow_id`
- `chatbot_node_id`
- `chatbot_variables`
- `status`
- `unread_count`
- `started_at`
- `last_message_at`
- `closed_at`

## Casts
| Coluna | Cast |
|---|---|
| `tags` | `array` |
| `chatbot_variables` | `array` |
| `started_at` | `datetime` |
| `last_message_at` | `datetime` |
| `closed_at` | `datetime` |
| `unread_count` | `integer` |
| `ai_agent_id` | `integer` |

## Relações
- `instance()` — BelongsTo
- `lead()` — BelongsTo
- `assignedUser()` — BelongsTo
- `department()` — BelongsTo
- `aiAgent()` — BelongsTo
- `chatbotFlow()` — BelongsTo
- `messages()` — HasMany
- `latestMessage()` — HasOne
- `tagModels()` — MorphToMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[InstagramConversation]]`
