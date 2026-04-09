---
auto_generated: true
type: model
class: App\Models\FeatureFlag
table: feature_flags
file: app/Models/FeatureFlag.php
tags: [model, auto]
---

# FeatureFlag

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/FeatureFlag.php`

## Tabela
`feature_flags`

## Fillable
- `slug`
- `label`
- `description`
- `is_enabled_globally`
- `sort_order`

## Casts
| Coluna | Cast |
|---|---|
| `is_enabled_globally` | `boolean` |

## Relações
- `tenants()` — BelongsToMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[FeatureFlag]]`
