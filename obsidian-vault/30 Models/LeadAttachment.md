---
auto_generated: true
type: model
class: App\Models\LeadAttachment
table: lead_attachments
file: app/Models/LeadAttachment.php
tags: [model, auto]
---

# LeadAttachment

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/LeadAttachment.php`

## Tabela
`lead_attachments`

## Traits
- `BelongsToTenant`

## Fillable
- `lead_id`
- `tenant_id`
- `uploaded_by`
- `original_name`
- `storage_path`
- `mime_type`
- `file_size`

## Casts
| Coluna | Cast |
|---|---|
| `file_size` | `integer` |

## Relações
- `lead()` — BelongsTo
- `uploader()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[LeadAttachment]]`
