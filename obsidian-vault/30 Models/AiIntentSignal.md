---
auto_generated: true
type: model
class: App\Models\AiIntentSignal
table: ai_intent_signals
file: app/Models/AiIntentSignal.php
tags: [model, auto]
---

# AiIntentSignal

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/AiIntentSignal.php`

## Tabela
`ai_intent_signals`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `ai_agent_id`
- `conversation_id`
- `contact_name`
- `phone`
- `intent_type`
- `context`
- `read_at`

## Casts
| Coluna | Cast |
|---|---|
| `read_at` | `datetime` |

## Relações
- `agent()` — BelongsTo
- `conversation()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[AiIntentSignal]]`
