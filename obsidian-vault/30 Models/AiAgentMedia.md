---
auto_generated: true
type: model
class: App\Models\AiAgentMedia
table: ai_agent_media
file: app/Models/AiAgentMedia.php
tags: [model, auto]
---

# AiAgentMedia

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/AiAgentMedia.php`

## Tabela
`ai_agent_media`

## Traits
- `BelongsToTenant`

## Fillable
- `ai_agent_id`
- `tenant_id`
- `original_name`
- `storage_path`
- `mime_type`
- `file_size`
- `description`

## Casts
| Coluna | Cast |
|---|---|
| `file_size` | `integer` |

## Relações
- `agent()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[AiAgentMedia]]`
