---
auto_generated: true
type: model
class: App\Models\CustomFieldDefinition
table: custom_field_definitions
file: app/Models/CustomFieldDefinition.php
tags: [model, auto]
---

# CustomFieldDefinition

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/CustomFieldDefinition.php`

## Tabela
`custom_field_definitions`

## Traits
- `BelongsToTenant`
- `LogsActivity`

## Fillable
- `tenant_id`
- `name`
- `label`
- `field_type`
- `options_json`
- `default_value`
- `is_required`
- `show_on_card`
- `card_position`
- `show_on_list`
- `is_active`
- `sort_order`

## Casts
| Coluna | Cast |
|---|---|
| `options_json` | `array` |
| `is_required` | `boolean` |
| `show_on_card` | `boolean` |
| `show_on_list` | `boolean` |
| `is_active` | `boolean` |
| `sort_order` | `integer` |
| `card_position` | `integer` |

## Relações
- `values()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[CustomFieldDefinition]]`
