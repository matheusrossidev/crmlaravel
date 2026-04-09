---
type: module
status: active
related: ["[[FacebookLeadFormConnection]]", "[[FacebookLeadAdsService]]", "[[Meta Graph API]]"]
files:
  - app/Services/FacebookLeadAdsService.php
  - app/Jobs/ProcessFacebookLeadgenWebhook.php
  - app/Http/Controllers/FacebookLeadgenWebhookController.php
last_review: 2026-04-09
tags: [module, facebook, leads]
---

# Facebook Lead Ads

## O que é
Captura automática de leads de **Facebook Lead Ads / Instagram Lead Ads** via webhook. User mapeia campos do form Meta → campos do Lead no CRM (com pipeline+stage de destino + tags + auto-assign).

## Fluxo
```
Meta → form submetido
  → POST /api/webhook/facebook/leadgen
    → FacebookLeadgenWebhookController::handle()
      → Valida HMAC SHA256 (X-Hub-Signature-256, FACEBOOK_APP_SECRET)
      → ProcessFacebookLeadgenWebhook::dispatch
        → Encontra FacebookLeadFormConnection por (page_id, form_id)
        → Busca form fields no Graph API com page_access_token
        → Mapeia field_mapping (JSON) → Lead.{name, email, phone, custom_fields}
        → Sanitiza phone/name/email
        → Cria Lead com tenant_id + pipeline_id + stage_id da connection
        → Aplica default_tags
        → Auto-assign user (assign_to)
        → Dedup por phone/email se !allow_duplicates
        → Cria FacebookLeadFormEntry (audit log)
```

## Setup pelo usuário
1. `/configuracoes/integracoes` → "Facebook Lead Ads" → Conectar (OAuth Business Login)
2. Lista páginas autorizadas via `/me/accounts` (com fallback `business_management`)
3. Lista forms da página via Graph API
4. Mapeia cada `meta_field` → `crm_field`
5. Define pipeline + stage + default_tags + auto_assign
6. Salva como `FacebookLeadFormConnection` (page_access_token encrypted)

## Pré-requisitos no Meta Dashboard
- App Facebook com produtos "Webhooks" + "Facebook Login for Business"
- Permissões: `pages_show_list`, `pages_manage_metadata`, `pages_read_engagement`, `leads_retrieval`, `business_management`
- Subscribed app no webhook `leadgen`

## Models
- [[FacebookLeadFormConnection]] (config + page_access_token encrypted)
- [[FacebookLeadFormEntry]] (audit log per submission)
