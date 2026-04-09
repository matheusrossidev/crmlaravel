---
type: architecture
status: in-progress
phase: 3
related: ["[[Tag]]", "[[HasTags trait]]", "[[Lead]]", "[[WhatsappConversation]]", "[[InstagramConversation]]", "[[WebsiteConversation]]"]
files:
  - app/Models/Tag.php
  - app/Models/Traits/HasTags.php
  - database/migrations/2026_04_08_180000_create_tags_and_taggables_tables.php
last_review: 2026-04-09
tags: [architecture, refactor, tags]
---

# Tags polimórficas (refactor 5 fases)

## Status atual: **Fase 3 (coexistência)**

## Motivação
Antes existia tabela `whatsapp_tags` (legacy) + coluna JSON `tags` em cada model. Bagunça:
- WhatsApp tinha catálogo, Lead tinha JSON livre, Instagram tinha outro catálogo
- Não dava pra "tag omnichannel" (tag única que aparece em Lead + Conversation)
- Schema duplicado

## Solução
- **Tabela `tags`** — catálogo único per-tenant: `name`, `color`, `sort_order`, `applies_to` enum (`lead`/`conversation`/`both`)
- **Pivot polimórfica `taggables`** — `tag_id`, `taggable_type`, `taggable_id`, `tenant_id`
- **Trait `HasTags`** — métodos `tagModels()`, `attachTagsByName()`, `syncTagsByName()`, `detachTagsByName()`, accessor `tag_names`

## Fases

### Fase 1 ✅ — Schema novo
Criar tabelas `tags` + `taggables`. Sem mudanças em código.

### Fase 2 ✅ — Trait `HasTags`
Adicionar trait nos models (Lead + 3 conversation models). Sem mudanças em controllers ainda.

### Fase 3 ✅ (atual) — Dual write
Coluna JSON `tags` continua sendo escrita E pivot é escrita em paralelo.
- Leituras autoritativas ainda da coluna JSON (filtros, automation conditions, scoring, exports, webhooks)
- Pivot é write-only ainda
- Comando `php artisan tags:backfill` (idempotente) migra `whatsapp_tags` + JSONs pra `tags`+`taggables`

### Fase 4 ⏳ — Trocar leituras
Migrar todas as leituras pra `tagModels` / `tag_names`. Coluna JSON continua escrita por compat (rollback safety).
**Não iniciada.** Quando começar:
- `LeadController::formatLead` ✅ já usa `tag_names` (commit `9624215`)
- `Lead::scopeFilterByTag` ✅ já existe (apontando pra coluna JSON, mudar pra pivot)
- Falta: `KanbanController`, `LeadsExport`, `WhatsappController`, `AutomationEngine` (matchesConditions), scoring rules, webhook payloads, etc

### Fase 5 ⏳ — Drop legado
Drop coluna JSON `tags` em todos models. Drop `whatsapp_tags`. Rename `WhatsappController` → `InboxController`. Rename `tenant/whatsapp/` → `tenant/inbox/`.

## Convenções (válidas durante Fase 3)
- **Pra LER em código novo:** preferir `$model->tagModels` ou `$model->tag_names`. Coluna JSON funciona porque dual write garante.
- **Pra ESCREVER em código novo:** SEMPRE `attachTagsByName()` / `syncTagsByName()` / `detachTagsByName()` do trait.
- **NUNCA** `WhatsappTag::create(...)` em código novo. Use `Tag::firstOrCreate(...)` ou deixa o trait auto-criar.
- **Endpoint genérico do inbox:** `PUT /chats/inbox/{channel}/{conversation}/contact` é o padrão pra atualizar nome/telefone/tags em qualquer canal. Não inventar endpoint canal-específico.

## Comando de manutenção
```bash
php artisan tags:backfill --dry-run
php artisan tags:backfill --tenant=12
```
Idempotente — pode rodar várias vezes como reconciliador.

## Decisões
- [[ADR — Refactor de tags polimorficas (5 fases)]]
- [[ADR — Coexistência dual-write em vez de big bang]]
