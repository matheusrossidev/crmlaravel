---
type: architecture
status: active
related: ["[[Multi-tenant]]", "[[Tags polimorficas (refactor)]]"]
files:
  - app/Providers/AppServiceProvider.php
  - database/migrations
last_review: 2026-04-09
tags: [architecture, database, mysql]
---

# Database Schema

## Banco
- **MySQL 8.0** em prod
- **MySQL antigo no WAMP** local — exige `Schema::defaultStringLength(191)` no `AppServiceProvider` (key limit 1000 bytes)
- **PostgreSQL + pgvector** separado pra Agno memory store (microsserviço Python)

## Stats
- ~94 models (incluindo `Tag`)
- 100+ migrations
- ~70 tabelas com `tenant_id` (multi-tenant)
- ~16 tabelas globais

## Convenções de schema
- **`tenant_id`** primeira coluna em quase tudo (exceto modelos globais)
- **Soft deletes** raramente usados — leads usam `status='archived'/'merged'` em vez de `deleted_at`
- **Models imutáveis**: `Sale`, `LostSale` (sem `updated_at`)
- **`LeadEvent` tem `$timestamps = false`** — sempre passar `'created_at' => now()` manualmente
- **Tags em coexistência (Fase 3)**:
  - Coluna JSON `tags` legacy continua sendo escrita (dual write)
  - Pivot polimórfica `taggables` (`tag_id`, `taggable_type`, `taggable_id`, `tenant_id`)
  - Trait [[HasTags trait]] gerencia as duas
  - Ver [[Tags polimorficas (refactor)]]

## Encryption no banco
- `whatsapp_instances.access_token` — `cast 'encrypted'`
- `instagram_instances.access_token` — `cast 'encrypted'`
- `oauth_connections.access_token` / `refresh_token` — encrypted
- `facebook_lead_form_connections.page_access_token` — encrypted

## Padrões de índice
- Evitar índices compostos com 3+ varchar longas (max key length 1000 bytes)
- `contact_picture_url` deve ser **`TEXT`** (URLs do WhatsApp/CDN excedem 191 chars)
- `phone` deve ser **`VARCHAR(30)`** (LIDs podem ter 14+ dígitos)

## Auditoria de colunas dead
- ~~`leads.converted_at`~~ — DROPADA em commit `9624215` (Onda 1 da auditoria)
- (espera-se mais nessa lista após auditorias futuras)

## Backups
- Mysqldump diário (não automatizado pelo CRM — config de infra)
- Tabelas críticas pra restore: `whatsapp_messages`, `whatsapp_conversations`, `leads`, `tenants`, `users`, `payment_logs`

## Decisões
- [[ADR — Single DB multi-tenant]]
- [[ADR — pgvector separado pro Agno (não reutiliza MySQL)]]
- [[ADR — Refactor de tags polimorficas (5 fases)]]
