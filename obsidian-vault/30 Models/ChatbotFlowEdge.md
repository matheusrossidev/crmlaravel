---
auto_generated: true
type: model
class: App\Models\ChatbotFlowEdge
table: chatbot_flow_edges
file: app/Models/ChatbotFlowEdge.php
tags: [model, auto]
---

# ChatbotFlowEdge

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/ChatbotFlowEdge.php`

## Tabela
`chatbot_flow_edges`

## Traits
- `BelongsToTenant`

## Fillable
- `flow_id`
- `tenant_id`
- `source_node_id`
- `source_handle`
- `target_node_id`

## Casts
| Coluna | Cast |
|---|---|
| `source_node_id` | `integer` |
| `target_node_id` | `integer` |

## Relações
- `flow()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[ChatbotFlowEdge]]`
