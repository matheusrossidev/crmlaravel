---
type: integration-reference
topic: nossa-implementacao
last_review: 2026-04-17
related: ["[[README]]", "[[17-chatwoot-reference]]", "[[19-gotchas-producao]]", "[[WhatsApp Foundation SOLID]]"]
tags: [waha, syncro, implementation]
---

# 18 — Nossa Implementação

Mapa dos arquivos da Syncro que tocam WAHA, com file:line pras funções/blocos críticos.

## Client HTTP — `app/Services/WahaService.php`

Implementa `WhatsappServiceContract`. 46 métodos públicos cobrindo tudo que usamos do WAHA.

### Sessions & Pairing
- [`__construct(string $sessionName)`](app/Services/WahaService.php#L18) — carrega `WAHA_BASE_URL`/`WAHA_API_KEY`/session
- [`createSession(webhookUrl, webhookSecret)`](app/Services/WahaService.php#L27) — cria session com config de webhook
- [`patchSession(webhookUrl, webhookSecret)`](app/Services/WahaService.php#L61) — atualiza config da session existente
- [`getSession()`](app/Services/WahaService.php#L86) — info da session
- [`startSession()`](app/Services/WahaService.php#L91)
- [`stopSession()`](app/Services/WahaService.php#L96)
- [`deleteSession()`](app/Services/WahaService.php#L101)
- [`getQrResponse()`](app/Services/WahaService.php#L112) — QR code pro pairing

### Groups & Contacts
- [`getGroupInfo(groupJid)`](app/Services/WahaService.php#L126)
- [`getContactInfo(contactJid)`](app/Services/WahaService.php#L139) — retorna `{name, pushName, pushname, ...}`

### Pictures
- [`getChatPicture(chatId)`](app/Services/WahaService.php#L152) — ⭐ **refatorado em commit 379a452** pra checar `$result['error']` explicitamente, já que `parse()` não throw em 4xx/5xx
- [`getContactPicture(contactJid)`](app/Services/WahaService.php#L183) — deprecated, redireciona pra getChatPicture
- [`getGroupPicture(groupJid)`](app/Services/WahaService.php#L189) — deprecated

### LID resolution
- [`getAllLids()`](app/Services/WahaService.php#L200) — batch mapping LID→phone
- [`getPhoneByLid(lid)`](app/Services/WahaService.php#L210) — resolve 1 LID

### Presence
- [`setPresence(chatId, presence)`](app/Services/WahaService.php#L218) — typing/recording/online/paused

### Send
- [`sendText(chatId, text)`](app/Services/WahaService.php#L228)
- [`sendImage(chatId, url, caption)`](app/Services/WahaService.php#L240) — imagem via URL
- [`sendImageBase64(chatId, filePath, mimeType, caption)`](app/Services/WahaService.php#L254) — imagem base64
- [`sendVoice(chatId, url)`](app/Services/WahaService.php#L273)
- [`sendVoiceBase64(chatId, filePath, mimeType)`](app/Services/WahaService.php#L288)
- [`sendFileBase64(chatId, filePath, mimeType, filename, caption)`](app/Services/WahaService.php#L305)
- [`sendList(chatId, description, rows, title, buttonText, footer)`](app/Services/WahaService.php#L325) — lista interativa
- [`sendReaction(messageId, emoji)`](app/Services/WahaService.php#L345)

### Fetch messages
- [`getChats(limit, offset)`](app/Services/WahaService.php#L356)
- [`getChatMessages(...)`](app/Services/WahaService.php#L364) — suporta `since` filter

### Webhook config
- [`setWebhook(url, events)`](app/Services/WahaService.php#L395) — configura endpoint webhook

### HTTP helpers (privados)
- [`client()`](app/Services/WahaService.php#L389) — PendingRequest com `X-Api-Key` + `Accept: application/json` + timeout 30s
- [`get(path, query)`](app/Services/WahaService.php#L397)
- [`post(path, data)`](app/Services/WahaService.php#L403)
- [`parse(Response)`](app/Services/WahaService.php#L426) — ⚠️ converte 4xx/5xx em `['error' => true, ...]` em vez de throw

### Interface contract
- [`getProviderName()`](app/Services/WahaService.php#L443) — retorna `'waha'`
- [`sendTemplate(...)`](app/Services/WahaService.php#L452) — retorna `not_supported` (templates HSM são Cloud API only)

## Foundation SOLID — `app/Services/Whatsapp/`

Abstração pra suportar WAHA + WhatsApp Cloud API em paralelo. Ver [[WhatsApp Foundation SOLID]].

- [`ChatIdResolver`](app/Services/Whatsapp/ChatIdResolver.php) — resolve formato de chatId por provider (WAHA: `@c.us`/`@g.us`/`@lid`; Cloud: número puro)
- [`InstanceSelector`](app/Services/Whatsapp/InstanceSelector.php) — prioridade: explicit → conversation → entity → primary
- [`ConversationWindowChecker`](app/Services/Whatsapp/ConversationWindowChecker.php) — janela 24h Meta (WAHA sempre aberta; Cloud restringe)
- [`OutboundMessagePersister`](app/Services/Whatsapp/OutboundMessagePersister.php) — persiste `WhatsappMessage` sync + broadcast Reverb
- [`WhatsappTemplateService`](app/Services/Whatsapp/WhatsappTemplateService.php) — templates HSM (Cloud API)

## Jobs — `app/Jobs/`

### ProcessWahaWebhook — `app/Jobs/ProcessWahaWebhook.php`

Job principal que processa cada evento WAHA entrando.

- [`__construct(array $payload)`](app/Jobs/ProcessWahaWebhook.php#L40)
- [`handle()`](app/Jobs/ProcessWahaWebhook.php#L42) — orquestrador — identifica tipo de event e despacha
- [`handleInbound(WhatsappInstance)`](app/Jobs/ProcessWahaWebhook.php#L64) — processa `message`/`message.any`
- [`handleReaction(WhatsappInstance)`](app/Jobs/ProcessWahaWebhook.php#L1058) — `message.reaction`
- [`handleAck()`](app/Jobs/ProcessWahaWebhook.php#L1091) — `message.ack`
- [`handleRevoked()`](app/Jobs/ProcessWahaWebhook.php#L1111) — `message.revoked`
- [`handleSessionStatus(WhatsappInstance)`](app/Jobs/ProcessWahaWebhook.php#L1123) — `session.status`
- [`findOrCreateLead(tenantId, phone, contactName, conversation)`](app/Jobs/ProcessWahaWebhook.php#L1162)
- [`normalizePhone(from, msg, isFromMe)`](app/Jobs/ProcessWahaWebhook.php#L1249)
- [`normalizeJidForApi(jid)`](app/Jobs/ProcessWahaWebhook.php#L1302)
- [`extractMedia(msg)`](app/Jobs/ProcessWahaWebhook.php#L1307)
- [`mimeToExt(mime)`](app/Jobs/ProcessWahaWebhook.php#L1353)
- [`downloadWahaMedia(url, type, mime)`](app/Jobs/ProcessWahaWebhook.php#L1376)
- [`matchUtmsToConversation(...)`](app/Jobs/ProcessWahaWebhook.php#L1405)

**Dedup** em [handleInbound](app/Jobs/ProcessWahaWebhook.php#L64): `Cache::add("waha:processing:{msgId}", 1, 10)` atômico Redis.

**PushName extraction** em [linha 456-464](app/Jobs/ProcessWahaWebhook.php#L456-L464):
```php
$contactName = $msg['_data']['Info']['PushName']
    ?? $msg['_data']['notifyName']
    ?? $msg['notifyName']
    ?? null;
```

**Photo download** em [linhas 498-508](app/Jobs/ProcessWahaWebhook.php#L498-L508) e [591-610](app/Jobs/ProcessWahaWebhook.php#L591-L610) — com catch + log estruturado desde commit 379a452.

### ImportWhatsappHistory — `app/Jobs/ImportWhatsappHistory.php`

Bulk import de histórico. Timeout 900s, tries=1.

- `handle()` — orquestra import de todas as conversas da session
- [`importChat(waha, chat, since, lidMap)`](app/Jobs/ImportWhatsappHistory.php#L247) — processa 1 chat (refatorado em commit 379a452)
- [PushName cascade](app/Jobs/ImportWhatsappHistory.php#L317) — getContactInfo primeiro, 3 variantes, fallback pra pushName das msgs
- [LID resolution](app/Jobs/ImportWhatsappHistory.php#L260-L314) — batch map → `/lids/{lid}` → getContactInfo → bloqueia se não resolver
- [Fetch msgs ANTES de criar conv](app/Jobs/ImportWhatsappHistory.php#L395-L480) — permite extrair pushName antes
- [Timestamp validation](app/Jobs/ImportWhatsappHistory.php#L507-L523) — skip se inválido (não usa `now()` fallback)

## Controllers

### WhatsappWebhookController — `app/Http/Controllers/WhatsappWebhookController.php`

Endpoint público `POST /api/webhook/waha`:
- Valida HMAC SHA-512 via header `X-Webhook-Hmac`
- Resolve `WhatsappInstance` por `session_name` / `phone_number`
- Dispatcha `ProcessWahaWebhook::dispatchSync($payload)` (síncrono, sem queue)
- Retorna 200 sempre — try/catch log exceção no canal `whatsapp`

### Tenant — `app/Http/Controllers/Tenant/`

- [`IntegrationController`](app/Http/Controllers/Tenant/IntegrationController.php) — conectar/desconectar WhatsApp, QR, import button
- [`WhatsappController`](app/Http/Controllers/Tenant/WhatsappController.php) — CRUD instances + admin. **Também** responde rotas Instagram/Website por razões históricas (renomeação pendente pra `InboxController` — ver [[Chat Inbox]])
- [`WhatsappMessageController`](app/Http/Controllers/Tenant/WhatsappMessageController.php) — envio outbound da UI. Usa [`resolveInstance($conversation)`](app/Http/Controllers/Tenant/WhatsappMessageController.php) (helper critical — sempre resolve via `conversation.instance_id`, nunca `WhatsappInstance::first()`)
- [`WhatsappTemplateController`](app/Http/Controllers/Tenant/WhatsappTemplateController.php) — CRUD templates HSM (Cloud API)

### Master — `app/Http/Controllers/Master/ToolboxController.php`

- [`reimportWaHistory`](app/Http/Controllers/Master/ToolboxController.php#L485) — dispatcha `ImportWhatsappHistory::dispatch()` manualmente
- [`syncProfilePictures`](app/Http/Controllers/Master/ToolboxController.php#L530) — refetch fotos faltando, com fallback `@lid` + `ProfilePictureDownloader::download()` (refatorado em commit 4f2480e)
- Outras tools: `fix-unread-counts`, `cleanup-lid-conversations`, `resolve-lid-conversations`, `reimport-empty-conversations`, etc.

## Helpers — `app/Support/`

### PhoneNormalizer — `app/Support/PhoneNormalizer.php`

Normaliza qualquer fone → chatId válido via **libphonenumber** (Google):

- `toWahaChatId($phone)` → `"5511999999999@c.us"` (ou `null` se inválido)
- `toCloudApiChatId($phone)` → número puro pro Cloud API
- `toE164($phone)` → `"+5511999999999"`
- `formatForDisplay($phone)` → formatado UX

Importante pro caso BR: strip do 9º dígito automático se o WAHA preferir formato antigo. ([MasterWhatsappNotifier::welcomeUser](app/Services/MasterWhatsappNotifier.php) usa fallback com e sem 9).

### ProfilePictureDownloader — `app/Support/ProfilePictureDownloader.php`

- [`download($remoteUrl, $channel, $tenantId, $contactId)`](app/Support/ProfilePictureDownloader.php#L34) — baixa com SSRF guard + MIME validation + storage local + fallback pra URL original

Suporta channels: `whatsapp`, `instagram`, `facebook`.

## Rotas

```
POST /api/webhook/waha          → WhatsappWebhookController
POST /configuracoes/integracoes/whatsapp/conectar  → IntegrationController::connectWhatsapp
POST /configuracoes/integracoes/whatsapp/import    → IntegrationController::importHistoryWhatsapp
POST /master/tools/run          → ToolboxController (reimport-wa-history, sync-profile-pictures, ...)
```

## Models envolvidos

- [`WhatsappInstance`](app/Models/WhatsappInstance.php) — 1 por tenant. Tem `provider` (`waha` / `cloud_api`), `session_name`, `phone_number`, `status`, `history_imported`.
- [`WhatsappConversation`](app/Models/WhatsappConversation.php) — 1 por contato. Tem `instance_id`, `phone`, `lid`, `contact_name`, `contact_picture_url`, `is_group`.
- [`WhatsappMessage`](app/Models/WhatsappMessage.php) — 1 por mensagem. `waha_message_id` UNIQUE. Tem `sent_at`, `ack`, `direction`, `type`, `body`, `media_url`, `sent_by`, `sent_by_agent_id`.

## Refactors históricos

- Commit `379a452` — fix getChatPicture (check $result['error']) + pushname fix import (3 variantes) + timestamp skip (remove now() fallback)
- Commit `4f2480e` — ToolboxController sync-profile-pictures usa ProfilePictureDownloader
- Commit `7bafec1` — Tenant delete via auto-discovery (separado mas relacionado)
- Commit antigo — Foundation SOLID introdução (ChatIdResolver etc)

## Uso na Syncro

- [[01-setup-deploy]] — config de produção
- [[13-webhooks-events]] — como eventos entram
- [[15-lid-handling]] — LID resolution
- [[19-gotchas-producao]] — tudo que descobrimos na prática
- [[WhatsApp Foundation SOLID]] — detalhes da abstração multi-provider
