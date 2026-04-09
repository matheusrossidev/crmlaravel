---
auto_generated: true
type: model
class: App\Models\AiAgentKnowledgeFile
table: ai_agent_knowledge_files
file: app/Models/AiAgentKnowledgeFile.php
tags: [model, auto]
---

# AiAgentKnowledgeFile

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/AiAgentKnowledgeFile.php`

## Tabela
`ai_agent_knowledge_files`

## Traits
- `BelongsToTenant`

## Fillable
- `ai_agent_id`
- `tenant_id`
- `original_name`
- `storage_path`
- `mime_type`
- `extracted_text`
- `status`
- `error_message`
- `chunks_count`
- `indexed_at`
- `indexing_error`

## Casts
| Coluna | Cast |
|---|---|
| `indexed_at` | `datetime` |

## Relações
- `agent()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[AiAgentKnowledgeFile]]`
