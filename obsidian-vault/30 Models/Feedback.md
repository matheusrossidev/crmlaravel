---
auto_generated: true
type: model
class: App\Models\Feedback
table: feedbacks
file: app/Models/Feedback.php
tags: [model, auto]
---

# Feedback

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/Feedback.php`

## Tabela
`feedbacks`

## Fillable
- `tenant_id`
- `user_id`
- `type`
- `area`
- `title`
- `description`
- `impact`
- `priority`
- `evidence_path`
- `can_contact`
- `contact_email`
- `url_origin`
- `plan_name`
- `user_role`
- `status`
- `admin_notes`

## Casts
| Coluna | Cast |
|---|---|
| `priority` | `integer` |
| `can_contact` | `boolean` |

## Relações
- `user()` — BelongsTo
- `tenant()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[Feedback]]`
