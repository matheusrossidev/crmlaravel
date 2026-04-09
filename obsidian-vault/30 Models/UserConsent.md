---
auto_generated: true
type: model
class: App\Models\UserConsent
table: user_consents
file: app/Models/UserConsent.php
tags: [model, auto]
---

# UserConsent

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/UserConsent.php`

## Tabela
`user_consents`

## Fillable
- `user_id`
- `consent_type`
- `policy_version`
- `accepted_at`
- `ip_address`
- `user_agent`

## Casts
| Coluna | Cast |
|---|---|
| `accepted_at` | `datetime` |

## Relações
- `user()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[UserConsent]]`
