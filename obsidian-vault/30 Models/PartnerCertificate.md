---
auto_generated: true
type: model
class: App\Models\PartnerCertificate
table: partner_certificates
file: app/Models/PartnerCertificate.php
tags: [model, auto]
---

# PartnerCertificate

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/PartnerCertificate.php`

## Tabela
`partner_certificates`

## Fillable
- `tenant_id`
- `course_id`
- `certificate_code`
- `issued_at`
- `created_at`

## Casts
| Coluna | Cast |
|---|---|
| `issued_at` | `datetime` |
| `created_at` | `datetime` |

## Relações
- `tenant()` — BelongsTo
- `course()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[PartnerCertificate]]`
