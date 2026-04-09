---
auto_generated: true
type: model
class: App\Models\SurveyResponse
table: survey_responses
file: app/Models/SurveyResponse.php
tags: [model, auto]
---

# SurveyResponse

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/SurveyResponse.php`

## Tabela
`survey_responses`

## Fillable
- `uuid`
- `tenant_id`
- `survey_id`
- `lead_id`
- `user_id`
- `score`
- `comment`
- `status`
- `sent_at`
- `answered_at`
- `expires_at`
- `created_at`

## Casts
| Coluna | Cast |
|---|---|
| `score` | `integer` |
| `sent_at` | `datetime` |
| `answered_at` | `datetime` |
| `expires_at` | `datetime` |
| `created_at` | `datetime` |

## Relações
- `survey()` — BelongsTo
- `lead()` — BelongsTo
- `user()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[SurveyResponse]]`
