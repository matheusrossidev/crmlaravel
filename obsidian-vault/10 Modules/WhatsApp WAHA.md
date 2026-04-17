---
type: module
status: active
related: ["[[Chat Inbox]]", "[[WhatsApp Cloud API]]", "[[WahaService]]", "[[70 Integrations/WAHA/README|WAHA Reference]]"]
files:
  - app/Services/WahaService.php
  - app/Jobs/ProcessWahaWebhook.php
  - app/Http/Controllers/WhatsappWebhookController.php
last_review: 2026-04-17
tags: [module, whatsapp, waha]
---

# WhatsApp WAHA (Módulo de Produto)

> **Este arquivo = nosso módulo de produto.** Pra referência técnica completa da API WAHA (todos os 80 endpoints, 9 tags OpenAPI, 25 webhook events, gotchas), ver pasta dedicada **[[70 Integrations/WAHA/README]]**.

## O que é
Integração com **WAHA Plus (engine GOWS)** — API não-oficial pra WhatsApp. Roda como stack Swarm **separado** em `waha.matheusrossi.com.br` (não `waha.syncro.chat`). Suporta múltiplas sessões por tenant. Coexiste com [[WhatsApp Cloud API]] via [[WhatsappServiceFactory]].

## Status
- ✅ Inbound + outbound + grupos + media
- ✅ Resolução LID via API + persistência em `whatsapp_conversations.lid`
- ✅ Dedup atômico de webhooks via `Cache::add('waha:processing:{msgId}', 1, 10)`
- ✅ Import de histórico via `ImportWhatsappHistory` job

## Fluxo inbound
```
WAHA → POST /api/webhook/waha
  → WhatsappWebhookController::handle()
    → Valida HMAC (WAHA_WEBHOOK_SECRET)
    → ProcessWahaWebhook::dispatchSync($payload)
      → Cache::add('waha:processing:{msgId}', 1, 10) — dedup
      → Resolve phone do JID (limpa @c.us/@lid/@s.whatsapp.net)
      → Se @lid: tenta resolver via WahaService::getPhoneByLid($lid)
      → Se LID não resolvido: BLOQUEIA (não salva conversa)
      → Cria/atualiza WhatsappConversation
      → Auto-assign AI agent (se auto_assign ativo)
      → Salva WhatsappMessage (UNIQUE waha_message_id)
      → Dispara chatbot OU IA OU AutomationEngine('message_received')
      → Broadcast via Reverb
```

## LID handling
WAHA GOWS pode mandar `from: XXX@lid` em vez de `@c.us`. **Regras:**
1. Se `from` termina com `@lid` → flag `$fromIsLid = true`
2. Tenta `WahaService::getPhoneByLid($lid)` → `GET /api/{session}/lids/{lid}`
3. Se falhar: tenta `getAllLids()` pra batch mapping
4. Se phone NÃO resolveu E `$fromIsLid = true` → **BLOQUEIA** mensagem
5. Resolvido: salva phone normalizado + persiste lid original na coluna `lid`

> **NUNCA** usar `strlen($phone) > 13` pra detectar LID. Sempre usar flag `$fromIsLid` do sufixo `@lid`.

## Métodos principais do WahaService
| Método | Endpoint WAHA |
|---|---|
| `sendText` | `POST /api/sendText` |
| `sendImage` | `POST /api/sendImage` |
| `sendList` | `POST /api/sendList` |
| `sendVoice` | `POST /api/sendVoice` |
| `sendFileBase64` | `POST /api/sendFile` |
| `getChatPicture` | `GET /api/{session}/chats/{id}/picture` |
| `getGroupInfo` | `GET /api/{session}/groups/{id}` |
| `getGroupPicture` | `GET /api/{session}/groups/{id}/picture` |
| `getPhoneByLid` | `GET /api/{session}/lids/{lid}` |
| `getAllLids` | `GET /api/{session}/lids` |
| `getChatMessages` | `GET /api/{session}/chats/{id}/messages` |

## Schema de tabelas críticas
- `whatsapp_messages.waha_message_id` — UNIQUE (previne duplicação de webhook retry)
- `whatsapp_conversations.contact_picture_url` — TEXT (URLs do CDN excedem 191 chars)
- `whatsapp_conversations.phone` — VARCHAR(30) (LIDs podem ter 14+ dígitos)

## Decisões / RCAs
- [[ADR — WhatsApp dual provider via factory]]
- [[2026-04-09 Idempotencia de actions de automacao]] (relacionado: bug spam de notification em webhook WAHA)

## Limitações
- API não-oficial — Meta pode banir conta a qualquer momento
- Throughput depende da config do WAHA (não há limite oficial)
