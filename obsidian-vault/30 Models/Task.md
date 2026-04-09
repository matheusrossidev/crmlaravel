---
auto_generated: true
type: model
class: App\Models\Task
table: tasks
file: app/Models/Task.php
tags: [model, auto]
---

# Task

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/Task.php`

## Tabela
`tasks`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `subject`
- `description`
- `type`
- `status`
- `priority`
- `due_date`
- `due_time`
- `completed_at`
- `lead_id`
- `whatsapp_conversation_id`
- `instagram_conversation_id`
- `assigned_to`
- `created_by`
- `notes`
- `stage_requirement_id`

## Casts
| Coluna | Cast |
|---|---|
| `due_date` | `date` |
| `completed_at` | `datetime` |
| `created_at` | `datetime` |
| `updated_at` | `datetime` |

## Relações
- `lead()` — BelongsTo
- `whatsappConversation()` — BelongsTo
- `instagramConversation()` — BelongsTo
- `assignedTo()` — BelongsTo
- `createdBy()` — BelongsTo
- `stageRequirement()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[Task]]`
