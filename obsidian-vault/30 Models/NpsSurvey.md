---
auto_generated: true
type: model
class: App\Models\NpsSurvey
table: nps_surveys
file: app/Models/NpsSurvey.php
tags: [model, auto]
---

# NpsSurvey

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/NpsSurvey.php`

## Tabela
`nps_surveys`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `name`
- `type`
- `question`
- `follow_up_question`
- `trigger`
- `delay_hours`
- `send_via`
- `is_active`
- `slug`
- `thank_you_message`

## Casts
| Coluna | Cast |
|---|---|
| `is_active` | `boolean` |
| `delay_hours` | `integer` |

## Relações
- `responses()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[NpsSurvey]]`
