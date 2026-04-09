---
type: model
status: active
table: ""
file: ""
related: []
tags: [model]
---

# {{title}}

> Resumo de 1 linha do que esse model representa

## Tabela
`{{table}}`

## Arquivo
[[]] · `app/Models/{{title}}.php`

## Traits
- `BelongsToTenant` (multi-tenant)
- ...

## Campos chave
| Coluna | Tipo | Notas |
|---|---|---|
| `id` | bigint | PK |

## Relações
- `belongsTo` → [[Model A]]
- `hasMany` → [[Model B]]

## Scopes / observers
- `scopeNotMerged()` ...
- Observer: `LeadObserver` invalida cache

## Quem usa
- [[Service A]]
- [[Module X]]

## Decisões / RCAs
- ...

## Notas
> Convenções específicas, gotchas
