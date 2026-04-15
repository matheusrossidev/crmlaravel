---
type: module
status: active
related: ["[[Chat Inbox]]", "[[WhatsApp WAHA]]", "[[WhatsappCloudService]]", "[[Meta Graph API]]"]
files:
  - app/Services/WhatsappCloudService.php
  - app/Jobs/ProcessWhatsappCloudWebhook.php
  - app/Http/Controllers/WhatsappCloudWebhookController.php
last_review: 2026-04-17
tags: [module, whatsapp, cloud-api, meta]
---

# WhatsApp Cloud API

## O que é
WhatsApp **oficial Meta** via Cloud API. Modo **Coexistence** — cliente conecta o app do celular via QR scan no Embedded Signup, fica vinculado à Cloud API mantendo o WhatsApp Business app funcionando (echoes espelhados).

## Status
- ✅ Inbound + outbound + media + lista interativa + buttons interativos (até 3)
- ✅ Embedded Signup (Coexistence) + fallback OAuth velho
- ✅ Webhook HMAC SHA256 com `WHATSAPP_CLOUD_APP_SECRET`
- ✅ Coexiste com WAHA via [[WhatsappServiceFactory]]
- ✅ **System User Token permanente** — chain de fallback (instance.system_user_token → config global → access_token user)
- ✅ **Templates HSM** completo — ver [[WhatsApp Templates (HSM)]]
- ✅ **Foundation SOLID** (2026-04-14) — ChatIdResolver, InstanceSelector, ConversationWindowChecker, OutboundMessagePersister em `app/Services/Whatsapp/`. Ver [[WhatsApp Foundation SOLID]]
- ✅ **Reverb broadcast fixed** (2026-04-14 `96c2dac` + `41b7efa`) — antes mensagens não apareciam em tempo real. Agora usa `::dispatch` igual WAHA + dispara `WhatsappConversationUpdated` junto
- ⚠️ Gated por feature flag `whatsapp_cloud_api` ([[Feature Flags]]) — lançou primeiro só pro tenant 12

## Fluxo de conexão (Embedded Signup)
```
Frontend FB JS SDK
  → FB.login({config_id, featureType:'whatsapp_business_app_onboarding'})
    → janelinha QR scan
    → postMessage WA_EMBEDDED_SIGNUP {phone_number_id, waba_id, business_id}
      → POST /configuracoes/integracoes/whatsapp-cloud/exchange (AJAX)
        → IntegrationController::exchangeWhatsappCloud()
          → troca code → access_token (oauth/access_token, sem redirect_uri)
          → cria WhatsappInstance(provider='cloud_api')
          → POST /{phone_number_id}/register
          → POST /{waba_id}/subscribed_apps (registra webhook)
```

## Fluxo inbound
```
Meta → POST /api/webhook/whatsapp-cloud
  → WhatsappCloudWebhookController::handle()
    → Valida HMAC SHA256 (X-Hub-Signature-256, app_secret)
    → ProcessWhatsappCloudWebhook::dispatchSync($payload)
      → entry → changes → value → messages | statuses
      → Dedup via cache + cloud_message_id
      → Cria WhatsappConversation + WhatsappMessage (mesmas tabelas do WAHA!)
      → Download de mídia inbound via Graph API → storage local
      → Dispara automações conversation_created / message_received
```

## Pré-requisitos pro cliente final
- WhatsApp Business app v2.24.17+ no celular
- Número ativo no WhatsApp Business há 7+ dias
- País suportado (Brasil ✅, EUA, México, Índia, Indonésia, HK, Singapura)
- Throughput: 20 mps (limite específico de números Coexistence)

## Pré-requisitos do app no Meta Developer Portal
- WhatsApp product adicionado
- Business Verification
- `whatsapp_business_messaging` aprovado em App Review (necessário pra Coexistence)
- `config_id` copiado pra `WHATSAPP_CLOUD_CONFIG_ID` no Portainer

## Env vars
| Var | Descrição |
|---|---|
| `WHATSAPP_CLOUD_APP_ID` | App ID do Meta |
| `WHATSAPP_CLOUD_APP_SECRET` | App secret (valida webhook HMAC) |
| `WHATSAPP_CLOUD_CONFIG_ID` | Embedded Signup Configuration ID |
| `WHATSAPP_CLOUD_VERIFY_TOKEN` | Token de verificação do webhook |
| `WHATSAPP_CLOUD_API_VERSION` | `v22.0` |
| `WHATSAPP_CLOUD_REDIRECT` | Callback URL pro fallback velho |

## Schema relevante
- `whatsapp_instances.provider` — `'waha'` ou `'cloud_api'` (NULL = legacy WAHA)
- `whatsapp_instances.phone_number_id` · `waba_id` · `business_account_id`
- `whatsapp_instances.access_token` — `cast 'encrypted'`
- `whatsapp_messages.cloud_message_id` — índice (mutuamente exclusivo com `waha_message_id`)

## Pattern crítico
SEMPRE usar [[WhatsappServiceFactory]]:
```php
$svc = WhatsappServiceFactory::for($instance);
$svc->sendText($chatId, $body);
```
Nunca instanciar `WhatsappCloudService` direto em código novo.

## Decisões / RCAs
- [[ADR — WhatsApp dual provider via factory]]
- [[ADR — Feature flag rollout per tenant]]
