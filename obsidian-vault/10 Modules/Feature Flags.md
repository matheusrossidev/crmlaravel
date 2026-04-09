---
type: module
status: active
related: ["[[FeatureFlag]]", "[[Master Panel]]"]
files:
  - app/Models/FeatureFlag.php
  - app/Http/Controllers/Master/FeatureController.php
last_review: 2026-04-09
tags: [module, feature-flags]
---

# Feature Flags

## O que é
Sistema de gating de features per-tenant via painel master. Usado pra rollout gradual (ex: WhatsApp Cloud API saiu primeiro só pro tenant 12).

## Helper
```php
\App\Models\FeatureFlag::isEnabled('whatsapp_cloud_api', $tenantId)
```

Retorna `bool`. Considera flag global (`is_enabled_globally`) + override per-tenant (pivot `feature_tenant`).

## Painel
`/master/features` (role super_admin) — toggle global ou per-tenant. Auto-seed via `FeatureFlagSeeder` no entrypoint do Docker.

## Convenção
- Pra esconder UI: `@if(\App\Models\FeatureFlag::isEnabled('slug', $tenantId)) ... @endif`
- Pra bloquear backend: mesmo helper no controller
- **NÃO usar permissões/roles pra isso** — feature flag é a fonte da verdade
- **NÃO hardcode flags em código** — toggle no painel sempre

## Flags atuais (referência)
- `whatsapp_cloud_api`
- `facebook_leadads`
- (extensível via UI)

## Decisões
- [[ADR — Feature flag rollout per tenant]]
