---
auto_generated: true
type: model
class: App\Models\WhatsappConversation
table: whatsapp_conversations
file: app/Models/WhatsappConversation.php
tags: [model, auto]
---

# WhatsappConversation

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/WhatsappConversation.php`

## Tabela
`whatsapp_conversations`

## Traits
- `BelongsToTenant`
- `LogsActivity`
- `HasTags`

## Fillable
- `tenant_id`
- `instance_id`
- `lead_id`
- `phone`
- `lid`
- `is_group`
- `contact_name`
- `contact_picture_url`
- `tags`
- `whatsapp_message_id`
- `status`
- `assigned_user_id`
- `department_id`
- `ai_agent_id`
- `chatbot_flow_id`
- `chatbot_node_id`
- `chatbot_variables`
- `unread_count`
- `started_at`
- `last_message_at`
- `last_inbound_at`
- `first_response_at`
- `closed_at`
- `followup_count`
- `last_followup_at`
- `utm_source`
- `utm_medium`
- `utm_campaign`
- `utm_content`
- `utm_term`
- `fbclid`
- `gclid`

## Casts
| Coluna | Cast |
|---|---|
| `tags` | `array` |
| `chatbot_variables` | `array` |
| `is_group` | `boolean` |
| `started_at` | `datetime` |
| `last_message_at` | `datetime` |
| `last_inbound_at` | `datetime` |
| `first_response_at` | `datetime` |
| `closed_at` | `datetime` |
| `last_followup_at` | `datetime` |
| `created_at` | `datetime` |
| `ai_agent_id` | `integer` |
| `chatbot_flow_id` | `integer` |
| `chatbot_node_id` | `integer` |
| `followup_count` | `integer` |

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
- Notas escritas à mão sobre esse model: procure no vault por `[[WhatsappConversation]]`
