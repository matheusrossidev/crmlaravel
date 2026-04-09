---
auto_generated: true
type: model
class: App\Models\AiAgent
table: ai_agents
file: app/Models/AiAgent.php
tags: [model, auto]
---

# AiAgent

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/AiAgent.php`

## Tabela
`ai_agents`

## Traits
- `BelongsToTenant`
- `LogsActivity`

## Fillable
- `tenant_id`
- `name`
- `objective`
- `communication_style`
- `company_name`
- `industry`
- `language`
- `persona_description`
- `behavior`
- `on_finish_action`
- `on_transfer_message`
- `on_invalid_response`
- `conversation_stages`
- `knowledge_base`
- `max_message_length`
- `response_delay_seconds`
- `response_wait_seconds`
- `channel`
- `website_token`
- `bot_name`
- `bot_avatar`
- `display_avatar`
- `welcome_message`
- `widget_type`
- `widget_color`
- `is_active`
- `auto_assign`
- `enable_pipeline_tool`
- `enable_tags_tool`
- `enable_intent_notify`
- `enable_calendar_tool`
- `calendar_tool_instructions`
- `calendar_id`
- `reminder_message_template`
- `reminder_offsets`
- `enable_products_tool`
- `enable_voice_reply`
- `elevenlabs_voice_id`
- `followup_enabled`
- `followup_delay_minutes`
- `followup_max_count`
- `followup_hour_start`
- `followup_hour_end`
- `transfer_to_user_id`
- `transfer_to_department_id`
- `use_agno`

## Casts
| Coluna | Cast |
|---|---|
| `conversation_stages` | `array` |
| `max_message_length` | `integer` |
| `response_delay_seconds` | `integer` |
| `response_wait_seconds` | `integer` |
| `is_active` | `boolean` |
| `auto_assign` | `boolean` |
| `enable_pipeline_tool` | `boolean` |
| `enable_tags_tool` | `boolean` |
| `enable_intent_notify` | `boolean` |
| `enable_calendar_tool` | `boolean` |
| `reminder_offsets` | `array` |
| `enable_products_tool` | `boolean` |
| `enable_voice_reply` | `boolean` |
| `followup_enabled` | `boolean` |
| `followup_delay_minutes` | `integer` |
| `followup_max_count` | `integer` |
| `followup_hour_start` | `integer` |
| `followup_hour_end` | `integer` |
| `use_agno` | `boolean` |

## Relações
- `conversations()` — HasMany
- `knowledgeFiles()` — HasMany
- `mediaFiles()` — HasMany
- `webConversations()` — HasMany
- `transferDepartment()` — BelongsTo
- `whatsappInstances()` — BelongsToMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[AiAgent]]`
