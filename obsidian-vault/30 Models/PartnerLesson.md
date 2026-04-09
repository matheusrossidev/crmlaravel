---
auto_generated: true
type: model
class: App\Models\PartnerLesson
table: partner_lessons
file: app/Models/PartnerLesson.php
tags: [model, auto]
---

# PartnerLesson

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/PartnerLesson.php`

## Tabela
`partner_lessons`

## Fillable
- `course_id`
- `title`
- `description`
- `video_url`
- `duration_minutes`
- `sort_order`

## Casts
| Coluna | Cast |
|---|---|
| `duration_minutes` | `integer` |
| `sort_order` | `integer` |

## Relações
- `course()` — BelongsTo
- `progress()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[PartnerLesson]]`
