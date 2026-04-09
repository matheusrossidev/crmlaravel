---
auto_generated: true
type: model
class: App\Models\AuditLog
table: audit_logs
file: app/Models/AuditLog.php
tags: [model, auto]
---

# AuditLog

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/AuditLog.php`

## Tabela
`audit_logs`

## Fillable
- `tenant_id`
- `user_id`
- `action`
- `entity_type`
- `entity_id`
- `old_data_json`
- `new_data_json`
- `ip_address`
- `user_agent`

## Casts
| Coluna | Cast |
|---|---|
| `old_data_json` | `array` |
| `new_data_json` | `array` |
| `created_at` | `datetime` |

## Relações
- `user()` — BelongsTo
- `tenant()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[AuditLog]]`
