---
type: integration-reference
topic: send-messages
last_review: 2026-04-17
related: ["[[README]]", "[[07-chatting-receive]]", "[[14-media-files]]", "[[16-polls-reactions-labels]]"]
tags: [waha, send, messages, chatting]
---

# 06 — Chatting: Send Messages

Tag `Chatting` do OpenAPI: **24 endpoints** pra enviar mensagens, reações, presença, etc.

## Formato de `chatId`

- **1:1**: `55119999999999@c.us` (número + `@c.us`)
- **Grupo**: `120363xxx@g.us` (group ID + `@g.us`)
- **LID**: `36576092528787@lid` ([[15-lid-handling]])
- **Channel**: `120363xxx@newsletter` ([[10-channels]])
- **Status broadcast**: `status@broadcast` ([[11-status]])

## 1. Send Text (simples)

```
POST /api/sendText
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "text": "Olá!"
}
```

## 2. Text com link preview

```json
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "text": "Confira: https://app.syncro.chat",
  "linkPreview": true,
  "linkPreviewHighQuality": true
}
```

## 3. Link Preview customizado

```
POST /api/send/link-custom-preview
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "url": "https://example.com",
  "title": "Título custom",
  "description": "Descrição",
  "previewType": 0,
  "thumbnail": "base64..."
}
```

Útil quando o site não expõe OG tags ou você quer override.

## 4. Reply (quote)

```json
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "text": "Respondendo",
  "reply_to": "false_5511999999999@c.us_AAAAAA..."
}
```

`reply_to` = `waha_message_id` da mensagem original.

## 5. Mention (menção @ ou todos)

```json
{
  "chatId": "120363xxx@g.us",
  "text": "Oi @5511999999999",
  "mentions": ["5511999999999@c.us"]
}
```

Mencionar **todos** (só grupos):
```json
{
  "mentions": ["all"]
}
```

## 6. Image (URL)

```
POST /api/sendImage
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "file": {
    "mimetype": "image/jpeg",
    "url": "https://example.com/foto.jpg",
    "filename": "foto.jpeg"
  },
  "caption": "Legenda"
}
```

## 7. Image (base64)

```json
{
  "file": {
    "mimetype": "image/jpeg",
    "filename": "foto.jpeg",
    "data": "/9j/4AAQSkZJ..."
  },
  "caption": "Legenda"
}
```

**Trade-off URL vs base64:**
- URL: WAHA baixa do servidor, dispensa upload, pode falhar se URL expirar ou SSRF bloquear
- Base64: transfere em 1 request, paga overhead de codificação, funciona sempre

Ver [[14-media-files]] pra detalhes.

## 8. File genérico (PDF, DOC, etc)

```
POST /api/sendFile
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "file": {
    "mimetype": "application/pdf",
    "url": "https://example.com/doc.pdf",
    "filename": "doc.pdf"
  },
  "caption": "Documento"
}
```

## 9. Voice

```
POST /api/sendVoice
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "file": {
    "mimetype": "audio/ogg; codecs=opus",
    "url": "https://example.com/voice.opus"
  },
  "convert": false
}
```

**`convert`**: WAHA converte MP3→OGG se `true`. Endpoint separado pra converter sem enviar:

```
POST /api/{session}/media/convert/voice
{
  "url": "https://example.com/voice.mp3"
}
```

Retorna o arquivo convertido (pra enviar depois).

## 10. Video

```
POST /api/sendVideo
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "caption": "Video",
  "asNote": false,
  "file": {
    "mimetype": "video/mp4",
    "filename": "video.mp4",
    "url": "https://example.com/video.mp4"
  },
  "convert": false
}
```

`asNote: true` = video note (aqueles redondos curtos).

Converter video similar: `POST /api/{session}/media/convert/video`.

## 11. Location

```
POST /api/sendLocation
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "latitude": -15.7801,
  "longitude": -47.9292
}
```

## 12. Contact vCard

```
POST /api/sendContactVcard
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "contact": {
    "firstName": "João",
    "lastName": "Silva",
    "phoneNumber": "5511999999999"
  }
}
```

## 13. Interactive List

```
POST /api/sendList
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "title": "Escolha",
  "description": "Menu de opções",
  "buttonText": "Ver opções",
  "footer": "Rodapé",
  "sections": [
    {
      "title": "Seção A",
      "rows": [
        {
          "rowId": "opt_1",
          "title": "Opção 1",
          "description": "Descrição opt 1"
        },
        {
          "rowId": "opt_2",
          "title": "Opção 2"
        }
      ]
    }
  ]
}
```

**Resposta do user** chega como `message` event com `body` = `rowId` selecionado.

## 14. Buttons (reply buttons) — DEPRECATED

`POST /api/sendButtons` — **deprecated** pelo Meta. Não usar em novos fluxos.

Alternativa: `POST /api/send/buttons/reply` (com header type TEXT/IMAGE/VIDEO, footer, até 3 botões). Ainda funciona mas limitado.

```
POST /api/send/buttons/reply
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "headerType": "TEXT",
  "header": "Menu",
  "body": "Escolha:",
  "footerText": "Rodapé",
  "buttons": [
    { "id": "btn_1", "text": "Sim" },
    { "id": "btn_2", "text": "Não" }
  ]
}
```

WhatsApp oficial só recomenda buttons via **Message Templates HSM** da Cloud API hoje. Pra WAHA o suporte é limitado mas funciona.

## 15. Poll

```
POST /api/sendPoll
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "question": "Qual sua preferência?",
  "options": ["A", "B", "C"],
  "multiselect": false
}
```

Vote:
```
POST /api/sendPollVote
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "messageId": "<id_da_poll>",
  "selectedOptions": ["A"]
}
```

Ver [[16-polls-reactions-labels]].

## 16. Reaction (emoji)

```
PUT /api/reaction
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "messageId": "false_5511999999999@c.us_AAAA",
  "emoji": "👍"
}
```

Emoji vazio (`""`) remove reação.

## 17. Star / Unstar

```
PUT /api/star
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "messageId": "false_5511999999999@c.us_AAAA",
  "star": true
}
```

## 18. Forward

```
POST /api/forwardMessage
{
  "session": "tenant_12",
  "chatId": "destino@c.us",
  "messageId": "false_origem@c.us_AAAA"
}
```

## 19. Seen (marcar como lida)

```
POST /api/sendSeen
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us"
}
```

Seletivo (NOWEB/GOWS suportam passar messageIds específicos):
```json
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us",
  "messageIds": ["false_5511999999999@c.us_AAAAA"]
}
```

## 20. Typing indicator

```
POST /api/startTyping    # inicia "digitando..."
POST /api/stopTyping     # para
```

Body:
```json
{
  "session": "tenant_12",
  "chatId": "5511999999999@c.us"
}
```

Útil pra simular digitação antes de enviar (UX de chatbot).

## 21. Presence

```
POST /api/{session}/presence
{
  "presence": "online"
}
```

Valores: `online`, `offline`, `typing`, `recording`, `paused`.

## 22. Edit message

```
PUT /api/{session}/chats/{chatId}/messages/{messageId}
{
  "text": "Novo texto"
}
```

## 23. Delete message

```
DELETE /api/{session}/chats/{chatId}/messages/{messageId}
```

Sem body. Deleta pros dois lados.

## 24. Check phone number exists

```
GET /api/checkNumberStatus?session=tenant_12&phone=5511999999999
```

(Deprecated — preferir `GET /api/contacts/check-exists`, ver [[09-contacts-groups]].)

## Gotchas

- **Rate limit não documentado oficialmente**. Nossa experiência: manter `usleep(50_000)` (50ms) entre sends consecutivos. Spam rápido pode marcar conta como spam no WhatsApp.
- **Endpoints deprecated (sendButtons, sendLinkPreview, GET /sendText, reply)** ainda funcionam mas devem ser evitados — podem sumir em versões novas.
- **WhatsApp bloqueia envios promocionais** sem relação prévia — primeira mensagem enviada pra um número que nunca interagiu pode ser filtrada pelo WhatsApp.
- **`reply_to` só funciona se a mensagem original ainda estiver visível no chat** — mensagens muito antigas podem não ter o quote carregado.
- **Envio pra número bloqueado retorna sucesso** (HTTP 200) mas a mensagem não chega — não há como saber programaticamente.

## Uso na Syncro

- [WahaService::sendText](app/Services/WahaService.php) — implementa `sendText`
- [WahaService::sendImage/sendVoice/sendList](app/Services/WahaService.php) — métodos equivalentes
- [WhatsappMessageController::send](app/Http/Controllers/Tenant/WhatsappMessageController.php) — endpoint interno do CRM pra UI
- [[07-chatting-receive]] — como mensagens enviadas voltam no webhook (`message.any` com `fromMe: true`)
- [[18-nossa-implementacao|OutboundMessagePersister]] — persiste o send result em `whatsapp_messages`
