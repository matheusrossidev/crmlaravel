---
type: architecture
status: active
related: ["[[WhatsApp WAHA]]", "[[WhatsApp Cloud API]]", "[[Instagram]]", "[[Facebook Lead Ads]]", "[[Asaas]]", "[[Stripe]]"]
files:
  - app/Http/Controllers/WhatsappWebhookController.php
  - app/Http/Controllers/WhatsappCloudWebhookController.php
  - app/Http/Controllers/InstagramWebhookController.php
  - app/Http/Controllers/FacebookLeadgenWebhookController.php
  - app/Http/Controllers/AsaasWebhookController.php
  - app/Http/Controllers/StripeWebhookController.php
last_review: 2026-04-09
tags: [architecture, webhook]
---

# Webhook Pipeline

## Tabela de webhooks
| URI | Handler | Auth |
|---|---|---|
| `POST /api/webhook/waha` | `WhatsappWebhookController` | HMAC custom (`WAHA_WEBHOOK_SECRET`) |
| `GET/POST /api/webhook/whatsapp-cloud` | `WhatsappCloudWebhookController` | HMAC SHA256 (`X-Hub-Signature-256`, `WHATSAPP_CLOUD_APP_SECRET`) |
| `GET/POST /api/webhook/instagram` | `InstagramWebhookController` | HMAC SHA256 (`X-Hub-Signature-256`, `INSTAGRAM_APP_SECRET`) |
| `GET/POST /api/webhook/facebook/leadgen` | `FacebookLeadgenWebhookController` | HMAC SHA256 (`X-Hub-Signature-256`, `FACEBOOK_APP_SECRET`) |
| `POST /api/webhook/asaas` | `AsaasWebhookController` | Token na URL (`ASAAS_WEBHOOK_TOKEN`) |
| `POST /api/webhook/stripe` | `StripeWebhookController` | Stripe signature header |

## Pattern padrão
```
1. Controller valida HMAC/auth ANTES de tocar no payload
2. Se inválido → 401 imediato
3. Se válido → ProcessXWebhook::dispatchSync($payload)
4. Job tem try/catch geral; exceções logadas no canal correto
5. Webhook sempre recebe 200 (mesmo se houver erro processando)
```

## Dedup
Todo webhook que processa mensagem usa cache atômico Redis pra dedup:
```php
if (! Cache::add("waha:processing:{msgId}", 1, 10)) {
    return; // já está sendo processado
}
```

Webhooks de mensagem têm UNIQUE constraint adicional no DB (`waha_message_id`, `cloud_message_id`, `ig_message_id`).

## Logs
Cada webhook tem canal próprio:
- `whatsapp` (compartilhado WAHA + Cloud API)
- `instagram`
- `facebook` (lead ads)
- `asaas`
- `stripe`

Localização: `storage/logs/{channel}-YYYY-MM-DD.log` (rotação diária).

## Padrões críticos
- **Nunca lançar exceção do controller** — sempre 200 pro provider, log no canal
- **Try/catch no `dispatchSync`** — dispara fora do request cycle, exceção não propaga pro 500
- **HMAC validation com `hash_equals`** — timing-safe
- **Sempre validar tenant resolvido** — webhook pode chegar com identifier desconhecido (caso da auto-discovery removida no Instagram)

## Decisões / RCAs
- [[2026-04-08 Instagram getProfile mudanca silenciosa Meta]] (cross-tenant via auto-discovery)
- [[ADR — Sync dispatch em vez de queue worker (latency)]]
