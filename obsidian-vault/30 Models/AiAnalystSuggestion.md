---
auto_generated: true
type: model
class: App\Models\AiAnalystSuggestion
table: ai_analyst_suggestions
file: app/Models/AiAnalystSuggestion.php
tags: [model, auto]
---

# AiAnalystSuggestion

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/AiAnalystSuggestion.php`

## Tabela
`ai_analyst_suggestions`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `lead_id`
- `conversation_id`
- `type`
- `payload`
- `reason`
- `status`

## Casts
| Coluna | Cast |
|---|---|
| `payload` | `array` |

## Relações
- `lead()` — BelongsTo
- `conversation()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[AiAnalystSuggestion]]`
