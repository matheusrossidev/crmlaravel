---
auto_generated: true
type: model
class: App\Models\Lead
table: leads
file: app/Models/Lead.php
tags: [model, auto]
---

# Lead

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Models/Lead.php`

## Tabela
`leads`

## Traits
- `BelongsToTenant`
- `LogsActivity`
- `HasTags`

## Fillable
- `tenant_id`
- `name`
- `phone`
- `email`
- `company`
- `value`
- `source`
- `tags`
- `pipeline_id`
- `stage_id`
- `assigned_to`
- `created_by`
- `notes`
- `instagram_username`
- `exclude_from_pipeline`
- `utm_id`
- `utm_source`
- `utm_medium`
- `utm_campaign`
- `utm_term`
- `utm_content`
- `fbclid`
- `gclid`
- `birthday`
- `score`
- `score_updated_at`
- `opted_out`
- `opted_out_at`
- `opted_out_reason`
- `status`
- `merged_into`
- `merged_at`

## Casts
| Coluna | Cast |
|---|---|
| `tags` | `array` |
| `exclude_from_pipeline` | `boolean` |
| `opted_out` | `boolean` |
| `opted_out_at` | `datetime` |
| `value` | `decimal:2` |
| `birthday` | `date` |
| `score` | `integer` |
| `score_updated_at` | `datetime` |
| `created_at` | `datetime` |
| `updated_at` | `datetime` |
| `merged_at` | `datetime` |

## Relações
- `pipeline()` — BelongsTo
- `stage()` — BelongsTo
- `assignedTo()` — BelongsTo
- `createdBy()` — BelongsTo
- `whatsappConversation()` — HasOne
- `customFieldValues()` — HasMany
- `events()` — HasMany
- `sales()` — HasMany
- `lostSales()` — HasMany
- `leadNotes()` — HasMany
- `attachments()` — HasMany
- `products()` — HasMany
- `tasks()` — HasMany
- `contacts()` — HasMany
- `lists()` — BelongsToMany
- `scoreLogs()` — HasMany
- `activeSequence()` — HasOne
- `leadSequences()` — HasMany
- `mergedInto()` — BelongsTo
- `duplicatesAsA()` — HasMany
- `duplicatesAsB()` — HasMany
- `tagModels()` — MorphToMany

## Links sugeridos
- Notas escritas à mão sobre esse model: procure no vault por `[[Lead]]`
