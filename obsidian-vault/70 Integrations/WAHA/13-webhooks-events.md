---
type: integration-reference
topic: webhooks-events
last_review: 2026-04-17
related: ["[[README]]", "[[03-sessions]]", "[[07-chatting-receive]]", "[[19-gotchas-producao]]"]
tags: [waha, webhooks, events, hmac]
---

# 13 — Webhooks & Events

WAHA entrega eventos em tempo real via HTTP POST pro webhook configurado em cada session ([[03-sessions]]).

Alternativa: WebSocket em `ws://waha-host:port/ws?session=X&events=Y&x-api-key=Z` — não usamos.

## Configuração do webhook (na session config)

```json
{
  "webhooks": [
    {
      "url": "https://app.syncro.chat/api/webhook/waha",
      "events": ["message", "message.any", "message.ack", "session.status", "group.v2.participants"],
      "hmac": { "key": "<WAHA_WEBHOOK_SECRET>" },
      "customHeaders": [
        { "name": "X-Tenant-Id", "value": "12" }
      ],
      "retries": {
        "policy": "constant",
        "delaySeconds": 2,
        "attempts": 15
      }
    }
  ]
}
```

Pra assinar TODOS os eventos: `"events": ["*"]`.

## HMAC (segurança)

**Algoritmo: SHA-512** (NÃO SHA-256 como eu havia documentado antes).

**Headers enviados pelo WAHA em toda request:**
- `X-Webhook-Hmac` — hash SHA-512 do raw body, hex
- `X-Webhook-Hmac-Algorithm` — literal `sha512`
- `X-Webhook-Request-Id` — UUID único por request (útil pra idempotência no receiver)
- `X-Webhook-Timestamp` — Unix ms

Validação:

```python
# Pseudo-code
signature = request.headers.get('X-Webhook-Hmac')
body_raw = request.raw_body
expected = hmac_sha512(WAHA_WEBHOOK_SECRET, body_raw)
if signature != expected:
    return 401
```

## Retry policy

Três policies:
- `constant` — delay fixo entre tries: `[2s, 2s, 2s, ...]`
- `linear` — crescimento linear: `[2, 4, 6, 8]`
- `exponential` — 2^n com 20% jitter: `[2, 4.1, 8.4, 16.3, ...]`

Default seguro: `constant` com 2s delay e 15 attempts.

**At-most-once** — sem garantia explícita de idempotência pelo WAHA. Receiver precisa deduplicar (nós usamos `Cache::add("waha:processing:{msgId}", 1, 10)` atômico Redis).

## Lista COMPLETA de eventos

### Session
- **session.status** — mudança de status (STOPPED → STARTING → SCAN_QR_CODE → WORKING → FAILED)
  ```json
  {
    "event": "session.status",
    "session": "tenant_12",
    "payload": {
      "status": "WORKING",
      "statuses": [
        {"status": "STOPPED", "timestamp": 1745123000},
        {"status": "STARTING", "timestamp": 1745123001},
        {"status": "WORKING", "timestamp": 1745123050}
      ]
    }
  }
  ```

- **state.change** — DEPRECATED, use `session.status`. Ainda funciona.

- **engine.event** — eventos internos da engine pra debug. Verboso. Não assinar em prod.

### Message
- **message** — inbound (ou outbound de outro device)
  ```json
  {
    "event": "message",
    "session": "tenant_12",
    "payload": { /* ver 07-chatting-receive */ }
  }
  ```

- **message.any** — TODAS as criações de mensagem, **incluindo enviadas via API por você**. Tem `source: "app" | "api"` pra distinguir. Nós usamos pra detectar envios feitos pelo celular (source=app) vs nossa UI (source=api).

- **message.ack** — ACK update
  ```json
  {
    "event": "message.ack",
    "payload": {
      "id": "true_5511999999999@c.us_AAA",
      "ack": 3,
      "ackName": "READ"
    }
  }
  ```

- **message.waiting** — "Aguardando essa mensagem" (status temporário de sync do histórico)

- **message.edited** — mensagem editada pelo remetente
  ```json
  {
    "event": "message.edited",
    "payload": {
      "id": "false_5511999999999@c.us_AAA",
      "body": "Texto novo",
      "editedAt": 1745123500
    }
  }
  ```

- **message.revoked** — mensagem deletada pelo remetente
  ```json
  {
    "event": "message.revoked",
    "payload": {
      "before": { "id": "...", "body": "Original" },
      "after":  { "id": "...", "body": "" }
    }
  }
  ```

- **message.reaction** — emoji reagido
  ```json
  {
    "event": "message.reaction",
    "payload": {
      "id": "false_...",
      "from": "5511999999999@c.us",
      "reaction": {
        "text": "🙏",
        "messageId": "true_...",
      }
    }
  }
  ```
  `reaction.text = ""` = reação removida.

### Presence
- **presence.update** — typing, recording, online, paused
  ```json
  {
    "event": "presence.update",
    "payload": {
      "id": "120363xxx@g.us",
      "presences": [
        {
          "participant": "5511999999999@c.us",
          "lastKnownPresence": "typing",
          "lastSeen": 1745123400
        }
      ]
    }
  }
  ```

### Groups
- **group.v2.join** — você entrou/foi adicionado
- **group.v2.leave** — você saiu/foi removido
- **group.v2.participants** — outros entraram/saíram/viraram admin
  ```json
  {
    "event": "group.v2.participants",
    "payload": {
      "groupId": "120363xxx@g.us",
      "type": "join|leave|promote|demote",
      "participants": ["5511999999999@c.us"]
    }
  }
  ```
- **group.v2.update** — info do grupo mudou (nome, descrição, foto)

Legacy: `group.join` / `group.leave` (sem v2) — deprecated, não usar.

### Calls
- **call.received** — chamada entrando
- **call.accepted** — raro, só se outro device atendeu
- **call.rejected** — rejeitada (por você ou caller desistiu)

Schema comum:
```json
{
  "event": "call.received",
  "payload": {
    "id": "AAA...",
    "from": "5511999999999@c.us",
    "isVideo": false,
    "isGroup": false,
    "timestamp": 1745123456
  }
}
```

### Chat
- **chat.archive** — chat arquivado/desarquivado
  ```json
  {
    "event": "chat.archive",
    "payload": {
      "id": "5511999999999@c.us",
      "archived": true
    }
  }
  ```

### Labels
- **label.upsert** — label criado/atualizado
- **label.deleted**
- **label.chat.added**
- **label.chat.deleted**

### Polls
- **poll.vote** — voto registrado
- **poll.vote.failed** — voto falhou (descriptografia)

### Events (RSVP a eventos do WhatsApp)
- **event.response** — resposta a evento criado (GOING, NOT_GOING, MAYBE)
- **event.response.failed**

## Race condition `message` vs `message.any`

**WAHA dispara AMBOS os eventos** pra cada mensagem inbound. Sem dedup, o CRM duplica conversa. Nossa solução:

```php
// Em ProcessWahaWebhook::handleInbound()
if (! Cache::add("waha:processing:{$msgId}", 1, 10)) {
    return; // Já está sendo processado
}
```

Combinado com UNIQUE constraint em `whatsapp_messages.waha_message_id`.

## Ordenação de eventos

**Não garantida globalmente.** Dentro de uma session específica, eventos tendem a chegar em ordem. Entre sessions, paralelo.

Eventos `message.ack` com ACK=3 (READ) podem chegar ANTES do `message` original se a rede reordenar.

## Gotchas

- **SHA-512** (não 256) — doc errada no passado causava mismatch de HMAC.
- **`message.any` inclui enviadas por você via API** — filtrar por `payload.fromMe` ou usar só `message` se não precisar rastrear outbound echo.
- **`source: "app" | "api"`** em `message.any` permite distinguir "celular enviou" vs "API enviou". Útil pra autoria ([[18-nossa-implementacao|sent_by tracking]]).
- **Nenhum evento específico pra "chat picture changed"** — precisa re-fetch periódico (6h throttle é o que fazemos).
- **Retry respawn aggressive** — se seu endpoint falha, pode receber 15 tentativas do mesmo event em 30s. Dedup obrigatório.
- **Custom headers úteis**: `X-Tenant-Id` pra roteamento sem parse do body.

## Uso na Syncro

- [WhatsappWebhookController](app/Http/Controllers/WhatsappWebhookController.php) — recebe + valida HMAC + dispatcha job
- [ProcessWahaWebhook](app/Jobs/ProcessWahaWebhook.php) — processa event por tipo
- [WahaService::setWebhook](app/Services/WahaService.php) — configura webhook ao criar session
- Route: `POST /api/webhook/waha` em [routes/api.php](routes/api.php)
- Env: `WAHA_WEBHOOK_SECRET` = secret do HMAC ([[01-setup-deploy]])
- Dedup: `Cache::add("waha:processing:{msgId}", 1, 10)` atômico Redis
