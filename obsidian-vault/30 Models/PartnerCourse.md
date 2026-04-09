---
auto_generated: true
type: model
class: App\Models\PartnerCourse
table: partner_courses
file: app/Models/PartnerCourse.php
tags: [model, auto]
---

# PartnerCourse

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/PartnerCourse.php`

## Tabela
`partner_courses`

## Fillable
- `title`
- `slug`
- `description`
- `cover_image`
- `is_published`
- `sort_order`

## Casts
| Coluna | Cast |
|---|---|
| `is_published` | `boolean` |
| `sort_order` | `integer` |

## Relações
- `lessons()` — HasMany
- `certificates()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[PartnerCourse]]`
