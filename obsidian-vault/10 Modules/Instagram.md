---
type: module
status: active
related: ["[[InstagramService]]", "[[Meta Graph API]]", "[[Chat Inbox]]"]
files:
  - app/Services/InstagramService.php
  - app/Jobs/ProcessInstagramWebhook.php
  - app/Http/Controllers/InstagramWebhookController.php
last_review: 2026-04-09
tags: [module, instagram, meta]
---

# Instagram

## O que é
Inbox Instagram via **Instagram API with Instagram Login** (caminho novo: `graph.instagram.com` + scopes `instagram_business_*`). Suporta DMs (texto + media + buttons + private reply de comentários), automações por keyword em comentários, e chatbot trigger via comentário.

## Status
- ✅ DM inbound + outbound + media (download pra storage local)
- ✅ Comentários com auto-reply + Private Reply (1ª DM via `comment_id`)
- ✅ Chatbot trigger por comentário (`trigger_type: instagram_comment`)
- ⚠️ **Contact fetch hybrid** (getProfile primário + listConversations fallback) — Meta mudou silenciosamente entre 27/03 e 01/04/2026
- ❌ Follow-up de IA NÃO funciona em Instagram (só WhatsApp)
- ❌ Lembretes de evento NÃO funcionam em Instagram (só WhatsApp)

## Fluxo inbound
```
Meta → POST /api/webhook/instagram
  → InstagramWebhookController::handle()
    → Valida HMAC SHA256 (X-Hub-Signature-256, INSTAGRAM_APP_SECRET)
    → ProcessInstagramWebhook::dispatchSync()
      → Encontra InstagramInstance por entry.id (instagram_account_id OU ig_business_account_id)
      → Se messaging: processa DM (cria conv, salva msg)
        → tipos: text, share, story_mention (ignorado), postback, web_url
        → fetchContactInfo HYBRID:
            1) getProfile($igsid) → name+username+pic (instances velhas)
            2) listConversations + participants → username só (instances novas)
        → Download de media inbound via storage local
      → Se comment: verifica InstagramAutomation
        → reply_comment + dm via Private Reply
        → DMs subsequentes via recipient.id
        → trigger chatbot flow se aplicável
```

## Contact fetch (mudança silenciosa Meta)
A Meta mudou comportamento de `GET /{IGSID}?fields=name,username,profile_pic` entre ~27/03 e 01/04/2026. **A mudança é POR IGSID, não por instance:**

- **IGSIDs criados antes de ~28/03**: endpoint retorna name + username + profile_pic
- **IGSIDs criados depois**: retornam erro 100/33 "does not support this operation"

Solução **hybrid**: tentar `getProfile()` primeiro; se falhar, fallback `listConversations + participants` (só username).

> **NUNCA** declarar "endpoint X não funciona" sem testar contra dado real do banco em ≥2 instances de datas/tenants diferentes. Ver [[Verificar empiricamente antes de declarar limitacao]].

## Models
- [[InstagramInstance]] (instagram_account_id, ig_business_account_id, access_token encrypted)
- [[InstagramConversation]] (igsid, contact_name, contact_username, contact_picture_url)
- [[InstagramMessage]] (ig_message_id UNIQUE)
- [[InstagramAutomation]] (regras de auto-reply por post)

## Webhook routing — bug histórico
Antes de [[2026-04-08 Instagram getProfile mudanca silenciosa Meta]], o código tinha "auto-discovery" que pegava primeira instance conectada com `ig_business_account_id NULL` e gravava `entry.id` do webhook nela. Resultado: webhook do tenant A acabava colando IDs na instance do tenant B (cross-tenant contamination).

**Removido em commit `fb32695`.** Pra fixar instances com IDs nulos, rodar:
```bash
php artisan instagram:repair-instances --dry-run
php artisan instagram:repair-instances --force
```

## Comandos de manutenção
- `instagram:repair-instances` — re-valida cada instance via `/me` da própria
- `instagram:repair-contacts` — preenche name/username/foto faltando (probe per-IGSID + fallback)

## Decisões / RCAs
- [[2026-04-08 Instagram getProfile mudanca silenciosa Meta]]
- [[ADR — Hybrid Instagram contact fetch]]
- [[Verificar empiricamente antes de declarar limitacao]]
