---
auto_generated: true
type: model
class: App\Models\CustomFieldValue
table: custom_field_values
file: app/Models/CustomFieldValue.php
tags: [model, auto]
---

# CustomFieldValue

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/CustomFieldValue.php`

## Tabela
`custom_field_values`

## Traits
- `BelongsToTenant`

## Fillable
- `tenant_id`
- `lead_id`
- `field_id`
- `value_text`
- `value_number`
- `value_date`
- `value_boolean`
- `value_json`

## Casts
| Coluna | Cast |
|---|---|
| `value_number` | `decimal:4` |
| `value_boolean` | `boolean` |
| `value_json` | `array` |
| `value_date` | `date` |

## Relações
- `lead()` — BelongsTo
- `fieldDefinition()` — BelongsTo

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[CustomFieldValue]]`
