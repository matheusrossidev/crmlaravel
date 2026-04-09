---
auto_generated: true
type: model
class: App\Models\PartnerLessonProgress
table: partner_lesson_progress
file: app/Models/PartnerLessonProgress.php
tags: [model, auto]
---

# PartnerLessonProgress

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/PartnerLessonProgress.php`

## Tabela
`partner_lesson_progress`

## Fillable
- `tenant_id`
- `lesson_id`
- `completed_at`
- `created_at`

## Casts
| Coluna | Cast |
|---|---|
| `completed_at` | `datetime` |
| `created_at` | `datetime` |

## Relações
- `lesson()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[PartnerLessonProgress]]`
