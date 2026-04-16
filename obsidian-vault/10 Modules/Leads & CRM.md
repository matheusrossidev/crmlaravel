---
type: module
status: active
related: ["[[Lead]]", "[[Pipeline]]", "[[PipelineStage]]", "[[Sale]]", "[[LostSale]]"]
files:
  - app/Http/Controllers/Tenant/LeadController.php
  - app/Http/Controllers/Tenant/KanbanController.php
  - app/Http/Controllers/Tenant/LeadMergeController.php
  - app/Models/Lead.php
last_review: 2026-04-09
tags: [module, crm, leads]
---

# Leads & CRM

## O que é
Pipeline de vendas Kanban + lista de contatos. Core do CRM. Multi-tenant com `BelongsToTenant`. Suporta múltiplos pipelines, custom fields, tags polimórficas, scoring, sequences, duplicate detection e merge atômico.

## Status atual
- ✅ CRUD completo, kanban drag-drop, custom fields (10 tipos)
- ✅ Tags em coexistência (Fase 3 — JSON + pivot polimórfica)
- ✅ Detect+merge de duplicatas (fuzzy)
- ✅ Auditoria clean code Onda 1+2 aplicada (commits `9624215`)
- ⚠️ `_drawer.blade.php` (1915 linhas) e `show.blade.php` (3132 linhas) são god partials — Onda 3 do refactor pendente
- ⚠️ Tags: `formatLead` agora lê de `tag_names` (Fase 4 não está completa)

## Modelos envolvidos
- [[Lead]] (core)
- [[Pipeline]] · [[PipelineStage]]
- [[Sale]] (imutável) · [[LostSale]] (imutável)
- [[LeadEvent]] (audit, `$timestamps = false`)
- [[LeadNote]] · [[LeadAttachment]] · [[LeadContact]]
- [[CustomFieldDefinition]] · [[CustomFieldValue]]
- [[LeadDuplicate]] (queue de revisão)
- [[Tag]] (polimórfica via [[HasTags trait]])

## Services principais
- [[DuplicateLeadDetector]]
- [[LeadMergeService]]
- [[LeadDataExtractorService]] (IA preenche campos via histórico)

## Endpoints chave
| Método | URI | Nome |
|---|---|---|
| GET | `/contatos` | `leads.index` |
| POST | `/contatos` | `leads.store` |
| GET | `/contatos/{lead}` | `leads.show` |
| PUT | `/contatos/{lead}` | `leads.update` |
| DELETE | `/contatos/{lead}` | `leads.destroy` |
| GET | `/crm` | `crm.index` (kanban) |
| POST | `/crm/lead/{lead}/stage` | `crm.updateStage` |
| GET | `/contatos/duplicatas` | `leads.duplicates` |
| POST | `/contatos/{primary}/merge/{secondary}` | `leads.merge` |

## Fluxo de criação
```
POST /contatos
  → LeadRequest (validação)
  → DuplicateLeadDetector::findDuplicatesFromData()
  → if highConfidence > 70 e !force → 422 com lista
  → senão Lead::create() + syncTagsByName + LeadEvent + AutomationEngine('lead_created')
  → return formatLead(lead)
```

## Padrões / convenções
- **`Sale` e `LostSale` são imutáveis** — sem `updated_at`
- **`LeadEvent::create()`** sempre passar `'created_at' => now()` (`$timestamps = false`)
- **Validação** centralizada em [[LeadRequest]] (FormRequest único)
- **Filtro de tag** centralizado em `Lead::scopeFilterByTag()`
- **`DuplicateLeadDetector`** injetado via `app()` (DI)

## Decisões / RCAs relacionados
- [[2026-04-09 Auditoria leads — Onda 1 + Onda 2]]
- [[ADR — Refactor de tags polimorficas (5 fases)]]

## Import Multistep (abr/2026)

Modal de importacao extraido pro partial `_import-modal.blade.php` com JS em IIFE (zero interferencia com kanban).

**Fluxo**: Upload > Mapping (fuzzy auto-suggest) > Review com acoes bulk/single.

- **Step 1** — Upload arquivo → `POST /crm/importar/preview` → backend le headers via `HeaderOnlyImport`, sugere mapping via `similar_text()`, retorna `file_headers` + `suggested_mapping` + `custom_fields` + `token`
- **Step 2** — User faz mapping de colunas → `POST /crm/importar/preview` com `token` + `mapping` (JSON) → `KanbanPreviewImport` com `$headerToField` → retorna rows preview
- **Step 3** — Review: tabela com checkboxes. Acoes bulk (definir etapa, tags, remover) + select de etapa inline + edit tags. User confirma → `POST /crm/importar` com `token` + `overrides` → `KanbanImport` com `$headerToField` + `$overrides` + salva custom fields via `CustomFieldValue`

**Arquivos**:
- `resources/views/tenant/crm/_import-modal.blade.php` — HTML + JS IIFE isolado
- `app/Imports/HeaderOnlyImport.php` — le headers da planilha
- `app/Imports/KanbanPreviewImport.php` — aceita `$headerToField`
- `app/Imports/KanbanImport.php` — aceita `$headerToField` + `$overrides` + custom fields

## Fora de escopo (não atacar agora)
- Quebrar `_drawer.blade.php` em partials (Onda 3)
- Quebrar `show.blade.php` em partials por tab (Onda 3)
- Migrar `formatLead` totalmente pra `tag_names` (Fase 4 do refactor de tags)
