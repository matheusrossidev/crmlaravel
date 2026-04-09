---
auto_generated: true
type: model
class: App\Models\ChatbotFlowNode
table: chatbot_flow_nodes
file: app/Models/ChatbotFlowNode.php
tags: [model, auto]
---

# ChatbotFlowNode

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/ChatbotFlowNode.php`

## Tabela
`chatbot_flow_nodes`

## Traits
- `BelongsToTenant`

## Fillable
- `flow_id`
- `tenant_id`
- `type`
- `label`
- `config`
- `canvas_x`
- `canvas_y`
- `is_start`

## Casts
| Coluna | Cast |
|---|---|
| `config` | `array` |
| `canvas_x` | `float` |
| `canvas_y` | `float` |
| `is_start` | `boolean` |

## Relações
- `flow()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[ChatbotFlowNode]]`
