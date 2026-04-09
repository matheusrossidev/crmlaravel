---
auto_generated: true
type: model
class: App\Models\PartnerAgencyCode
table: partner_agency_codes
file: app/Models/PartnerAgencyCode.php
tags: [model, auto]
---

# PartnerAgencyCode

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/PartnerAgencyCode.php`

## Tabela
`partner_agency_codes`

## Fillable
- `code`
- `description`
- `tenant_id`
- `is_active`

## Casts
| Coluna | Cast |
|---|---|
| `is_active` | `boolean` |

## Relações
- `tenant()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[PartnerAgencyCode]]`
