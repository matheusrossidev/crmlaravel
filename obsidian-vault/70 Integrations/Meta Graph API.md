---
type: integration
status: active
provider: Meta (Facebook/Instagram/WhatsApp)
auth: oauth + hmac webhook
related: ["[[WhatsApp Cloud API]]", "[[Instagram]]", "[[Facebook Lead Ads]]"]
env_vars:
  - WHATSAPP_CLOUD_APP_ID
  - WHATSAPP_CLOUD_APP_SECRET
  - INSTAGRAM_APP_ID
  - INSTAGRAM_APP_SECRET
  - FACEBOOK_APP_ID
  - FACEBOOK_APP_SECRET
tags: [integration, meta, facebook]
---

# Meta Graph API

> Integração guarda-chuva pra **3 produtos Meta** que o Syncro consome:

## Produtos consumidos
1. **WhatsApp Business Platform (Cloud API)** — ver [[WhatsApp Cloud API]]
2. **Instagram API with Instagram Login** — ver [[Instagram]]
3. **Facebook Lead Ads (leadgen webhook)** — ver [[Facebook Lead Ads]]

## Auth
Cada produto tem seu **App ID + App Secret separados** no Meta Developer Portal. Embora possam estar no mesmo App, na prática:
- WhatsApp Cloud API: app dedicado pra Coexistence (precisa Business Verification + App Review)
- Instagram: app dedicado com scopes `instagram_business_*`
- Facebook Lead Ads: pode reutilizar o app do Instagram OU ter app próprio

## HMAC SHA256 webhook validation
Padrão Meta: header `X-Hub-Signature-256` com `sha256=<hash>` calculado sobre raw body com app_secret.

```php
$expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);
if (! hash_equals($expected, $request->header('X-Hub-Signature-256'))) {
    abort(401);
}
```

## Endpoints WhatsApp Cloud
- `POST /{phone_number_id}/messages` — envio
- `POST /{phone_number_id}/register` — register no número (Coexistence)
- `POST /{waba_id}/subscribed_apps` — registra webhook
- `GET /{media_id}` — download de media inbound

API version: `v22.0` (configurável via `WHATSAPP_CLOUD_API_VERSION`)

## Endpoints Instagram (Instagram Login flow)
- `GET /me` — info da conta autenticada
- `GET /{igsid}?fields=name,username,profile_pic` — profile do contato (⚠️ ver [[2026-04-08 Instagram getProfile mudanca silenciosa Meta]])
- `GET /me/conversations?platform=instagram` — lista conversations (fallback)
- `GET /{conversation_id}?fields=participants` — participantes (fallback)
- `POST /me/messages` — DM (text, media, buttons, private reply)
- `POST /{comment_id}/replies` — reply em comentário
- `GET /me/media` — feed de posts

Base URL: `https://graph.instagram.com` (não `graph.facebook.com`)
API version: `v25.0`

## Endpoints Facebook Lead Ads
- `GET /me/accounts` — lista páginas do Business Account
- `GET /{page_id}/leadgen_forms` — lista forms
- `GET /{form_id}` — schema do form
- `GET /{leadgen_id}` — payload de submission

## Mudança silenciosa importante (2026-04-08)
A Meta mudou comportamento do `GET /{IGSID}` no flow Instagram Login entre ~27/03 e 01/04/2026. Sem changelog. Ver [[2026-04-08 Instagram getProfile mudanca silenciosa Meta]] e [[Verificar empiricamente antes de declarar limitacao]].

## Limitações conhecidas
- Throughput Coexistence WhatsApp: 20 mps
- Instagram não retorna `name` real nem foto pra IGSIDs novos (pós 28/03/2026)
- Facebook Lead Ads form fields podem ter case-insensitive duplications no `field_data` payload
- App Review obrigatório pra qualquer app que vá produção
