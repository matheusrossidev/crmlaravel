---
auto_generated: true
type: model
class: App\Models\ProductCategory
table: product_categories
file: app/Models/ProductCategory.php
tags: [model, auto]
---

# ProductCategory

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/ProductCategory.php`

## Tabela
`product_categories`

## Fillable
- `parent_id`
- `name`
- `sort_order`

## Relações
- `parent()` — BelongsTo
- `children()` — HasMany
- `products()` — HasMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[ProductCategory]]`
