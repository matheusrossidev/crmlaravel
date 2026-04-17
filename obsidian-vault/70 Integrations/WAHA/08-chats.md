---
type: integration-reference
topic: chats
last_review: 2026-04-17
related: ["[[README]]", "[[06-chatting-send]]", "[[07-chatting-receive]]"]
tags: [waha, chats]
---

# 08 — Chats

Tag `Chats` no OpenAPI: **16 endpoints** pra listagem + gestão de conversas e histórico de mensagens (não confundir com tag `Chatting` que é envio).

## Listagem de chats

### GET /chats — lista completa

```
GET /api/{session}/chats?limit=100&offset=0&sortBy=messageTimestamp&sortOrder=desc
```

Parâmetros:
- `limit` — paginação (default: 50)
- `offset` — paginação
- `sortBy` — `messageTimestamp`, `id`, `name`
- `sortOrder` — `asc`, `desc`

Response:
```json
[
  {
    "id": "5511999999999@c.us",
    "name": "João",
    "isGroup": false,
    "isReadOnly": false,
    "unreadCount": 2,
    "timestamp": 1745123456,
    "pinned": false,
    "archived": false,
    "muted": false,
    "lastMessage": { ... }
  }
]
```

### GET /chats/overview — versão leve

```
GET /api/{session}/chats/overview?limit=20&offset=0
```

Retorna id/name/picture/last message **sem media attachments** (mais rápido, menos bandwidth). Ideal pra listagem inicial.

### POST /chats/overview — variante com filtros complexos

```
POST /api/{session}/chats/overview
{
  "limit": 20,
  "offset": 0,
  "filter": { ... }
}
```

Nota: algumas engines precisam POST pra passar filtros grandes (GET tem limit de URL).

## Messages dentro de um chat

### GET /chats/{chatId}/messages — histórico

```
GET /api/{session}/chats/5511999999999@c.us/messages?limit=50&downloadMedia=false
```

Parâmetros suportados:
- `limit`
- `offset`
- `downloadMedia` — `true` baixa mídia (gera URL); `false` retorna só metadata (mais rápido)
- `filter.timestamp.gte` — unix seconds
- `filter.timestamp.lte`
- `filter.fromMe` — `true`/`false` filtra por remetente
- `filter.ack` — filtra por ACK code (ex: só lidas = ack >= 3)

**Sort**: doc oficial **não garante ordem** — sempre fazer `usort` por `timestamp` no client antes de processar bulk.

Response: array de messages ([[07-chatting-receive|estrutura aqui]]).

### GET /chats/{chatId}/messages/{messageId} — mensagem por ID

```
GET /api/{session}/chats/5511999999999@c.us/messages/false_5511999999999@c.us_AAAA
```

Aceita ID completo OU forma simplificada. Útil pra buscar mensagem específica pra edit/delete/reply.

### POST /chats/{chatId}/messages/read — marcar todas como lidas

```
POST /api/{session}/chats/5511999999999@c.us/messages/read
```

Body: vazio. Marca todas pendentes como lidas (envia read receipt).

### DELETE /chats/{chatId}/messages — limpar histórico do chat

```
DELETE /api/{session}/chats/5511999999999@c.us/messages
```

Apaga do SEU lado apenas (outro contato continua vendo).

## Edit / Delete / Pin de mensagens

### PUT /chats/{chatId}/messages/{messageId} — editar

```
PUT /api/{session}/chats/5511999999999@c.us/messages/false_5511999999999@c.us_AAAA
{
  "text": "Texto novo"
}
```

**Encoding**: URL encode `@` como `%40` no path.

Limite WhatsApp: **edit só dentro de 15 minutos** do envio. Após, retorna erro.

### DELETE /chats/{chatId}/messages/{messageId} — deletar mensagem

```
DELETE /api/{session}/chats/5511999999999@c.us/messages/false_5511999999999@c.us_AAAA
```

Sem body. Deleta pros dois lados (se mensagem foi enviada por você) ou só pra você.

### POST /chats/{chatId}/messages/{messageId}/pin — fixar

```
POST /api/{session}/chats/5511999999999@c.us/messages/false_5511999999999@c.us_AAAA/pin
{
  "duration": 86400
}
```

`duration` em segundos. Max 30 dias.

### POST /chats/{chatId}/messages/{messageId}/unpin — desfixar

```
POST /api/{session}/chats/5511999999999@c.us/messages/false_5511999999999@c.us_AAAA/unpin
```

## Gestão do chat

### DELETE /chats/{chatId} — apagar chat inteiro

```
DELETE /api/{session}/chats/5511999999999@c.us
```

Remove a conversa inteira (local, não afeta o outro lado).

### POST /chats/{chatId}/archive e /unarchive

```
POST /api/{session}/chats/5511999999999@c.us/archive
POST /api/{session}/chats/5511999999999@c.us/unarchive
```

### POST /chats/{chatId}/unread — marcar como não lida

```
POST /api/{session}/chats/5511999999999@c.us/unread
```

Dispara evento `chat.archive`/etc no webhook.

## Chat picture

### GET /chats/{chatId}/picture

```
GET /api/{session}/chats/5511999999999@c.us/picture
```

Retorna (**sempre HTTP 200**):
```json
{ "url": "https://pps.whatsapp.net/..." }
```

ou se o contato não tem foto:
```json
{ "url": null }
```

**NUNCA retorna 404** pra ausência de foto. Minhas docs antigas diziam 404 — estava errado.

**Query param opcional**: `?refresh=true` força re-fetch (default: usa cache de 24h).

Ver [[09-contacts-groups]] pro endpoint alternativo `/api/contacts/profile-picture` (com chave `profilePictureURL` — diferente!).

## Gotchas

- **Paginação sem total**: response não traz `total` count — client tem que iterar até receber array vazio.
- **Ordem não garantida** em `GET /messages` — sempre ordenar por `timestamp` no client.
- **Edit limite 15min** — fora disso, WhatsApp rejeita.
- **`downloadMedia=true` pode ser lento** — cada mídia baixada gera overhead. Pra bulk import usar `false` e baixar sob demanda depois.
- **`POST /messages/read` marca TODAS** — se precisar seletivo, usar `POST /sendSeen` com `messageIds` ([[06-chatting-send]]).
- **Chat picture tem 2 endpoints diferentes** com chaves diferentes no response (`url` vs `profilePictureURL`). Não confundir.

## Uso na Syncro

- [WahaService::getChats](app/Services/WahaService.php) — lista
- [WahaService::getChatMessages](app/Services/WahaService.php) — histórico
- [WahaService::getChatPicture](app/Services/WahaService.php#L151) — foto (refatorado em [commit 379a452](https://github.com/matheusrossidev/crmlaravel/commit/379a452))
- [ImportWhatsappHistory](app/Jobs/ImportWhatsappHistory.php) — bulk import via `getChats` + `getChatMessages`
- [ToolboxController::syncProfilePictures](app/Http/Controllers/Master/ToolboxController.php#L530) — re-sync de fotos
