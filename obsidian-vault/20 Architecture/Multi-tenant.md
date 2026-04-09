---
type: architecture
status: active
related: ["[[Tenant]]", "[[User]]", "[[BelongsToTenant trait]]"]
files:
  - app/Models/Traits/BelongsToTenant.php
  - app/Http/Middleware/TenantMiddleware.php
last_review: 2026-04-09
tags: [architecture, multi-tenant]
---

# Multi-tenant

## Modelo
Cada `Tenant` representa uma empresa cliente. Todos os dados ficam no mesmo banco MySQL com `tenant_id` em cada tabela. Isolamento via **Global Scope** automático no Eloquent.

## Trait `BelongsToTenant`
[`app/Models/Traits/BelongsToTenant.php`](app/Models/Traits/BelongsToTenant.php)

- Aplicado em **~70 models**
- Adiciona Global Scope automático: filtra queries por `tenant_id` do user logado
- Auto-preenche `tenant_id` ao criar registros
- Suporta impersonação de agências via `session('impersonating_tenant_id')`

## Models SEM tenant (globais)
`Tenant`, `User`, `PipelineStage`, `AiConfiguration`, `PlanDefinition`, `TokenIncrementPlan`, `UpsellTrigger`, `WebhookLog`, `AuditLog`, `PartnerRank`, `PartnerResource`, `PartnerCourse`, `PartnerLesson`, `MasterNotification`, `FeatureFlag`, `ReengagementTemplate`

## Quando bypass o scope
Em jobs e webhooks (que rodam fora de contexto HTTP do user), usar:
```php
Lead::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->...
```

## Middleware chain
```
web → auth → tenant → role:admin → plan.limit:leads
```

| Middleware | Função |
|---|---|
| `tenant` | Seta tenant ativo, verifica trial/suspensão |
| `super_admin` | Exige `is_super_admin=true` |
| `role:X` | Exige role específica (admin/manager/viewer) |
| `plan.limit:X` | Verifica quota do plano |
| `api_key` | Valida X-API-Key (SHA256) |
| `agno_internal` | Valida X-Agno-Token pra chamadas internas |

## Padrões críticos
- **NUNCA assumir tenant via `auth()->user()->tenant_id` em jobs** — passar `tenant_id` explicitamente no constructor do job
- **Sempre `withoutGlobalScope('tenant')`** em código que roda fora de HTTP user context
- **Helper `activeTenantId()`** retorna o tenant atual considerando impersonação

## Decisões
- [[ADR — Tenant_id em todas as tabelas (single DB multi-tenant)]]
