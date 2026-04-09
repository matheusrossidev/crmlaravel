---
auto_generated: true
type: model
class: App\Models\AiUsageLog
table: ai_usage_logs
file: app/Models/AiUsageLog.php
tags: [model, auto]
---

# AiUsageLog

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/AiUsageLog.php`

## Tabela
`ai_usage_logs`

## Fillable
- `tenant_id`
- `conversation_id`
- `model`
- `provider`
- `tokens_prompt`
- `tokens_completion`
- `tokens_total`
- `type`

## Casts
| Coluna | Cast |
|---|---|
| `tokens_prompt` | `integer` |
| `tokens_completion` | `integer` |
| `tokens_total` | `integer` |

## Relações
- `tenant()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[AiUsageLog]]`
