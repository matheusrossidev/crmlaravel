---
auto_generated: true
type: model
class: App\Models\LeadScoreLog
table: lead_score_logs
file: app/Models/LeadScoreLog.php
tags: [model, auto]
---

# LeadScoreLog

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/LeadScoreLog.php`

## Tabela
`lead_score_logs`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `lead_id`
- `scoring_rule_id`
- `points`
- `reason`
- `data_json`
- `created_at`

## Casts
| Coluna | Cast |
|---|---|
| `points` | `integer` |
| `data_json` | `array` |
| `created_at` | `datetime` |

## Relações
- `tenant()` — BelongsTo
- `lead()` — BelongsTo
- `scoringRule()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[LeadScoreLog]]`
