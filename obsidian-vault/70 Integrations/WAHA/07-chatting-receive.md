---
type: integration-reference
topic: receive-messages
last_review: 2026-04-17
related: ["[[README]]", "[[06-chatting-send]]", "[[13-webhooks-events]]", "[[02-engines]]"]
tags: [waha, receive, inbound, pushname, _data]
---

# 07 — Chatting: Receive Messages (Inbound)

Mensagens recebidas chegam via **webhook** (eventos `message`, `message.any`, `message.ack`, etc — ver [[13-webhooks-events]]) ou podem ser buscadas via `GET /chats/{id}/messages` (ver [[08-chats]]).

## Estrutura base do payload

```json
{
  "event": "message",
  "session": "tenant_12",
  "payload": {
    "id": "false_5511999999999@c.us_AAAAA...",
    "timestamp": 1745123456,
    "from": "5511999999999@c.us",
    "fromMe": false,
    "to": "5511888888888@c.us",
    "body": "Oi!",
    "hasMedia": false,
    "ack": 1,
    "ackName": "SERVER",
    "vCards": [],
    "_data": { ... engine-specific ... }
  }
}
```

### Campos essenciais

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | string | `waha_message_id` único — formato `{fromMe}_{chatId}_{hash}` |
| `timestamp` | int | Unix seconds (algumas engines devolvem millis se `>9999999999`) |
| `from` | string | JID remetente (nos casos inbound, é o contato; nos casos `fromMe`, é o nosso número) |
| `fromMe` | bool | `true` se enviamos, `false` se recebemos |
| `to` | string | JID destino (complementa `from`) |
| `body` | string | Texto (se type=text) ou caption (se media) |
| `hasMedia` | bool | Se tem mídia anexada |
| `ack` | int | Status de entrega (ver tabela abaixo) |
| `ackName` | string | Versão string do `ack` |
| `vCards` | array | Contatos compartilhados (vCard format) |
| `_data` | object | Engine-specific metadata ([[02-engines]]) |

## Tipos de mensagem (`type` dentro de `_data` ou inferível)

- `chat` — texto
- `image` — imagem
- `video` — video
- `audio` / `ptt` — áudio (ptt = push-to-talk, voice note)
- `document` — arquivo
- `sticker` — sticker (tratamos como document no nosso CRM)
- `location` — coordenadas
- `vcard` / `multi_vcard` — contatos compartilhados
- `poll` / `poll_vote` — enquetes
- `reaction` — reação (emoji) em outra message
- `revoked` — mensagem deletada pelo remetente
- `edited` — mensagem editada
- `e2e_notification` — notificação de criptografia
- `notification` — system message (entrou/saiu do grupo, etc)

## ACK codes

| Código | Nome | Significado |
|--------|------|-------------|
| -1 | ERROR | Falhou |
| 0 | PENDING | Pendente (offline) |
| 1 | SERVER | Recebida pelo servidor WhatsApp |
| 2 | DEVICE | Entregue no dispositivo do destinatário |
| 3 | READ | Lida (duas azuis) |
| 4 | PLAYED | Áudio/video reproduzido |

Evento `message.ack` emite mudanças. Nós salvamos última ACK em `whatsapp_messages.ack`.

## PushName — onde achar

Engine-specific, **3 variantes**. Nosso código em [ProcessWahaWebhook:456-464](app/Jobs/ProcessWahaWebhook.php#L456-L464):

```php
$contactName = $msg['_data']['Info']['PushName']
    ?? $msg['_data']['notifyName']
    ?? $msg['notifyName']
    ?? null;
```

Primeira fonte (GOWS): `_data.Info.PushName`
Segunda fonte (GOWS alternativa): `_data.notifyName`
Terceira fonte (WEBJS/outros): `notifyName` no root

### Via contacts endpoint (mais confiável pro import)

Ver [[09-contacts-groups]] — `GET /api/contacts?contactId={jid}` retorna `{name, pushname, shortName}`. O próprio WAHA na integração com Chatwoot checa **3 variantes** ([[17-chatwoot-reference]]):

```typescript
contact?.name || contact?.pushName || contact?.pushname || this.chatId;
```

Note a diferença sutil: `pushName` (camelCase) E `pushname` (lowercase). Ambas existem — engines/versions diferentes retornam cada uma.

## Media payload

Quando `hasMedia: true`:

```json
{
  "media": {
    "url": "http://localhost:3000/api/files/abc123.jpg",
    "mimetype": "image/jpeg",
    "filename": null,
    "error": null
  }
}
```

- `url` — baixar via GET. Expira ou pode exigir `X-Api-Key`.
- `mimetype` — ajuda a saber extensão/tipo
- `filename` — pode ser null se o contato não enviou filename (ex: imagem tirada na câmera)
- `error` — se download falhou

**Nosso tratamento**: ao receber `hasMedia: true`, fazer `GET` pra baixar e armazenar local em [ProfilePictureDownloader::download](app/Support/ProfilePictureDownloader.php) (embora o nome do helper seja "profile picture", ele é usado pra mídia em geral também).

Ver [[14-media-files]].

## replyTo (quote)

Quando a msg é resposta a outra:

```json
{
  "replyTo": {
    "id": "false_5511999999999@c.us_AAA...",
    "participant": "5511999999999@c.us",
    "body": "Mensagem original",
    "hasMedia": true,
    "media": { ... }
  }
}
```

## Reaction event (separado)

```json
{
  "event": "message.reaction",
  "payload": {
    "id": "false_5511999999999@c.us_AAA...",
    "from": "5511999999999@c.us",
    "reaction": {
      "text": "🙏",
      "messageId": "true_5511999999999@c.us_BBB..."
    }
  }
}
```

`text: ""` = reação removida.

## Revoked event

```json
{
  "event": "message.revoked",
  "payload": {
    "before": {
      "id": "false_5511999999999@c.us_AAA",
      "body": "Mensagem original"
    },
    "after": {
      "id": "false_5511999999999@c.us_AAA",
      "body": ""
    }
  }
}
```

Mesmo ID, body zerado. Nosso CRM pode marcar como `revoked` visualmente ou esconder.

## Edited event

Envia a nova versão do body. Estrutura similar a `message` mas com `event: "message.edited"` e metadata do edit.

## Distinguir 1:1 vs grupo

Pelo sufixo do `from`:
- `@c.us` → 1:1
- `@g.us` → grupo (payload terá `participant` indicando quem dentro do grupo enviou)
- `@lid` → ID interno GOWS ([[15-lid-handling]])
- `@s.whatsapp.net` → formato NOWEB/GOWS legacy (converter pra `@c.us`)
- `@newsletter` → channel (não é mensagem tradicional)

Em grupos, o payload tem campo `participant` adicional com o JID do remetente individual.

## Gotchas

- **`message` vs `message.any` — race condition**: WAHA dispara AMBOS por cada inbound. Sem dedup, duplica conversa no CRM. Fix em [ProcessWahaWebhook::handleInbound](app/Jobs/ProcessWahaWebhook.php) com `Cache::add("waha:processing:{msgId}", 1, 10)` atômico no Redis. Ver [[19-gotchas-producao]].
- **Timestamp às vezes vem em ms** (GOWS em alguns casos). Detectar com `> 9999999999` e dividir por 1000. Ver [ImportWhatsappHistory:498-499](app/Jobs/ImportWhatsappHistory.php#L498).
- **PushName pode ser null** se o contato tem privacy setting impedindo. Cair pra fallback: `getContactInfo` → `formatPhoneName(phone)`.
- **`fromMe: true` mensagens** também chegam no webhook — elas são enviadas do celular conectado OU do nosso próprio send via API. Pra distinguir "humano no celular" de "nossa UI", usamos `sent_by` intent cache (ver [[18-nossa-implementacao]]).
- **Ordenação não garantida** pelo WAHA — se precisar ordem cronológica (ex: bulk import), sort por `timestamp` antes de processar. Ver [ImportWhatsappHistory:468-474](app/Jobs/ImportWhatsappHistory.php#L468).
- **Mensagens de grupo têm `from: grupoId@g.us` + `participant: membroId@c.us`** — sempre checar os dois campos.

## Uso na Syncro

- [ProcessWahaWebhook](app/Jobs/ProcessWahaWebhook.php) — job principal que processa cada message event
- [ImportWhatsappHistory](app/Jobs/ImportWhatsappHistory.php) — bulk import de histórico
- [WhatsappMessage model](app/Models/WhatsappMessage.php) — persistência
- [[13-webhooks-events]] — todos os eventos inbound
- [[15-lid-handling]] — LID resolution quando `from` termina com `@lid`
- [[19-gotchas-producao]] — race conditions + edge cases
