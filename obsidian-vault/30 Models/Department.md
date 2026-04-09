---
auto_generated: true
type: model
class: App\Models\Department
table: departments
file: app/Models/Department.php
tags: [model, auto]
---

# Department

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/Department.php`

## Tabela
`departments`

## Traits
- `BelongsToTenant`
- `LogsActivity`

## Fillable
- `tenant_id`
- `name`
- `description`
- `icon`
- `color`
- `default_ai_agent_id`
- `default_chatbot_flow_id`
- `assignment_strategy`
- `last_assigned_user_id`
- `is_active`

## Casts
| Coluna | Cast |
|---|---|
| `is_active` | `boolean` |
| `default_ai_agent_id` | `integer` |
| `default_chatbot_flow_id` | `integer` |
| `last_assigned_user_id` | `integer` |

## Relações
- `users()` — BelongsToMany
- `defaultAiAgent()` — BelongsTo
- `defaultChatbotFlow()` — BelongsTo
- `whatsappConversations()` — HasMany
- `instagramConversations()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[Department]]`
