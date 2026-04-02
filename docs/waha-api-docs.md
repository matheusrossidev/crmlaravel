# WAHA - WhatsApp HTTP API Documentation

> **Versão:** 2026.2.2 | **Spec:** OAS 3.1  
> **Base URL:** `http://waha.matheusrossi.com.br:3000/`  
> **Swagger UI:** https://waha.matheusrossi.com.br  

WAHA (WhatsApp HTTP API) é uma API HTTP que permite interagir com o WhatsApp de forma programática. Este documento lista todos os endpoints disponíveis organizados por categoria, além dos eventos de webhook.

---

## Autenticação

A maioria dos endpoints requer autenticação via **API Key**. Passe a chave no header:

```
Authorization: Bearer {api_key}
```

Gerencie suas API Keys em: `POST /api/keys` / `GET /api/keys`

---

## Parâmetros Comuns

- `{session}` — Nome da sessão WhatsApp (ex: `default`)
- `{chatId}` — ID do chat (ex: `5511999999999@c.us` para contato, `120363xxx@g.us` para grupo)
- `{messageId}` — ID da mensagem
- `{id}` — ID do recurso (grupo, canal, app, etc.)

---

## 🖥️ Sessions
> Control WhatsApp sessions (accounts)

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `GET` | `/api/sessions` | List all sessions |
| `POST` | `/api/sessions` | Create a session |
| `GET` | `/api/sessions/{session}` | Get session information |
| `PUT` | `/api/sessions/{session}` | Update a session |
| `DELETE` | `/api/sessions/{session}` | Delete the session |
| `GET` | `/api/sessions/{session}/me` | Get information about the authenticated account |
| `POST` | `/api/sessions/{session}/start` | Start the session |
| `POST` | `/api/sessions/{session}/stop` | Stop the session |
| `POST` | `/api/sessions/{session}/logout` | Logout from the session |
| `POST` | `/api/sessions/{session}/restart` | Restart the session |

---

## 📱 Pairing
> Pair a session with WhatsApp on your phone.

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `GET` | `/api/{session}/auth/qr` | Get QR code for pairing WhatsApp API. |
| `POST` | `/api/{session}/auth/request-code` | Request authentication code. |
| `GET` | `/api/screenshot` | Get a screenshot of the current WhatsApp session (**WEBJS** only) |

---

## 🆔 Profile
> Your profile information

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `GET` | `/api/{session}/profile` | Get my profile |
| `PUT` | `/api/{session}/profile/name` | Set my profile name |
| `PUT` | `/api/{session}/profile/status` | Set profile status (About) |
| `PUT` | `/api/{session}/profile/picture` | Set profile picture |
| `DELETE` | `/api/{session}/profile/picture` | Delete profile picture |

---

## 📤 Chatting
> Chatting methods

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `POST` | `/api/sendText` | Send a text message |
| `POST` | `/api/sendImage` | Send an image |
| `POST` | `/api/sendFile` | Send a file |
| `POST` | `/api/sendVoice` | Send an voice message |
| `POST` | `/api/sendVideo` | Send a video |
| `POST` | `/api/send/link-custom-preview` | Send a text message with a CUSTOM link preview. |
| `POST` | `/api/sendList` | Send a list message (interactive) |
| `POST` | `/api/forwardMessage` |  |
| `POST` | `/api/sendSeen` |  |
| `POST` | `/api/startTyping` |  |
| `POST` | `/api/stopTyping` |  |
| `PUT` | `/api/reaction` | React to a message with an emoji |
| `PUT` | `/api/star` | Star or unstar a message |
| `POST` | `/api/sendPoll` | Send a poll with options |
| `POST` | `/api/sendPollVote` | Vote on a poll |
| `POST` | `/api/sendLocation` |  |
| `POST` | `/api/sendContactVcard` |  |
| `POST` | `/api/send/buttons/reply` | Reply on a button message |

### `POST /api/sendList` — Enviar lista interativa

Envia mensagem com menu de opções clicáveis. O usuário toca no botão para abrir a lista e seleciona uma opção. A resposta do usuário é o **título da row** selecionada.

**Payload (testado e confirmado em v2026.2.2 GOWS):**

```json
{
  "session": "tenant_12",
  "chatId": "5561992008997@c.us",
  "message": {
    "title": "Titulo opcional (topo da lista)",
    "description": "Texto principal da mensagem",
    "footer": "Rodape opcional",
    "button": "Ver opcoes",
    "sections": [
      {
        "title": "Nome da secao",
        "rows": [
          {"title": "Opcao 1", "rowId": "opcao1", "description": "Descricao opcional"},
          {"title": "Opcao 2", "rowId": "opcao2", "description": null},
          {"title": "Opcao 3", "rowId": "opcao3", "description": null}
        ]
      }
    ]
  }
}
```

**Campos:**
- `message.title` — título no topo (opcional, pode ser `""`)
- `message.description` — texto principal da mensagem (obrigatório)
- `message.footer` — rodapé (opcional, pode ser `""`)
- `message.button` — texto do botão que abre a lista (ex: `"Ver opções"`)
- `message.sections[].title` — título da seção (obrigatório)
- `message.sections[].rows[].title` — texto da opção (max ~24 chars)
- `message.sections[].rows[].rowId` — ID interno da opção
- `message.sections[].rows[].description` — descrição abaixo do título (opcional, `null` para omitir)

**Nota:** Funciona com chatId `@c.us` e `@lid`.

---

## ✅ Presence
> Presence information

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `POST` | `/api/{session}/presence` | Set session presence |
| `GET` | `/api/{session}/presence` | Get all subscribed presence information. |
| `GET` | `/api/{session}/presence/{chatId}` | Get the presence for the chat id. If it hasn't been subscribed - it also subscribes to it. |
| `POST` | `/api/{session}/presence/{chatId}/subscribe` | Subscribe to presence events for the chat. |

---

## 📢 Channels
> Channels (newsletters) methods

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `GET` | `/api/{session}/channels` | Get list of know channels |
| `POST` | `/api/{session}/channels` | Create a new channel. |
| `DELETE` | `/api/{session}/channels/{id}` | Delete the channel. |
| `GET` | `/api/{session}/channels/{id}` | Get the channel info |
| `GET` | `/api/{session}/channels/{id}/messages/preview` | Preview channel messages |
| `POST` | `/api/{session}/channels/{id}/follow` | Follow the channel. |
| `POST` | `/api/{session}/channels/{id}/unfollow` | Unfollow the channel. |
| `POST` | `/api/{session}/channels/{id}/mute` | Mute the channel. |
| `POST` | `/api/{session}/channels/{id}/unmute` | Unmute the channel. |
| `POST` | `/api/{session}/channels/search/by-view` | Search for channels (by view) |
| `POST` | `/api/{session}/channels/search/by-text` | Search for channels (by text) |
| `GET` | `/api/{session}/channels/search/views` | Get list of views for channel search |
| `GET` | `/api/{session}/channels/search/countries` | Get list of countries for channel search |
| `GET` | `/api/{session}/channels/search/categories` | Get list of categories for channel search |

---

## 🟢 Status
> Status (aka stories) methods

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `POST` | `/api/{session}/status/text` | Send text status |
| `POST` | `/api/{session}/status/image` | Send image status |
| `POST` | `/api/{session}/status/voice` | Send voice status |
| `POST` | `/api/{session}/status/video` | Send video status |
| `POST` | `/api/{session}/status/delete` | DELETE sent status |
| `GET` | `/api/{session}/status/new-message-id` | Generate message ID you can use to batch contacts |

---

## 💬 Chats
> Chats methods

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `GET` | `/api/{session}/chats` | Get chats |
| `GET` | `/api/{session}/chats/overview` | Get chats overview. Includes all necessary things to build UI "your chats overview" page - chat id, name, picture, last message. Sorting by last message timestamp |
| `POST` | `/api/{session}/chats/overview` | Get chats overview. Use POST if you have too many "ids" params - GET can limit it |
| `DELETE` | `/api/{session}/chats/{chatId}` | Deletes the chat |
| `GET` | `/api/{session}/chats/{chatId}/picture` | Gets chat picture |
| `GET` | `/api/{session}/chats/{chatId}/messages` | Gets messages in the chat |
| `DELETE` | `/api/{session}/chats/{chatId}/messages` | Clears all messages from the chat |
| `POST` | `/api/{session}/chats/{chatId}/messages/read` | Read unread messages in the chat |
| `GET` | `/api/{session}/chats/{chatId}/messages/{messageId}` | Gets message by id |
| `DELETE` | `/api/{session}/chats/{chatId}/messages/{messageId}` | Deletes a message from the chat |
| `PUT` | `/api/{session}/chats/{chatId}/messages/{messageId}` | Edits a message in the chat |
| `POST` | `/api/{session}/chats/{chatId}/messages/{messageId}/pin` | Pins a message in the chat |
| `POST` | `/api/{session}/chats/{chatId}/messages/{messageId}/unpin` | Unpins a message in the chat |
| `POST` | `/api/{session}/chats/{chatId}/archive` | Archive the chat |
| `POST` | `/api/{session}/chats/{chatId}/unarchive` | Unarchive the chat |
| `POST` | `/api/{session}/chats/{chatId}/unread` | Unread the chat |

---

## 🔑 Api Keys
> API Keys management

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `POST` | `/api/keys` | Create a new API key |
| `GET` | `/api/keys` | Get all API keys |
| `PUT` | `/api/keys/{id}` | Update an API key |
| `DELETE` | `/api/keys/{id}` | Delete an API key |

---

## 👤 Contacts
> Contacts methods.
Use phone number (without +) or phone number and @c.us at the end as contactId.
'E.g: 12312312310 OR 12312312310@c.us

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `GET` | `/api/contacts/all` | Get all contacts |
| `GET` | `/api/contacts` | Get contact basic info |
| `GET` | `/api/contacts/check-exists` | Check phone number is registered in WhatsApp. |
| `GET` | `/api/contacts/about` | Gets the Contact's "about" info |
| `GET` | `/api/contacts/profile-picture` | Get contact's profile picture URL |
| `POST` | `/api/contacts/block` | Block contact |
| `POST` | `/api/contacts/unblock` | Unblock contact |
| `PUT` | `/api/{session}/contacts/{chatId}` | Create or update contact |
| `GET` | `/api/{session}/lids` | Get all known lids to phone number mapping |
| `GET` | `/api/{session}/lids/count` | Get the number of known lids |
| `GET` | `/api/{session}/lids/{lid}` | Get phone number by lid |
| `GET` | `/api/{session}/lids/pn/{phoneNumber}` | Get lid by phone number (chat id) |

---

## 👥 Groups
> Groups methods.

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `POST` | `/api/{session}/groups` | Create a new group. |
| `GET` | `/api/{session}/groups` | Get all groups. |
| `GET` | `/api/{session}/groups/join-info` | Get info about the group before joining. |
| `POST` | `/api/{session}/groups/join` | Join group via code |
| `GET` | `/api/{session}/groups/count` | Get the number of groups. |
| `POST` | `/api/{session}/groups/refresh` | Refresh groups from the server. |
| `GET` | `/api/{session}/groups/{id}` | Get the group. |
| `DELETE` | `/api/{session}/groups/{id}` | Delete the group. |
| `POST` | `/api/{session}/groups/{id}/leave` | Leave the group. |
| `GET` | `/api/{session}/groups/{id}/picture` | Get group picture |
| `PUT` | `/api/{session}/groups/{id}/picture` | Set group picture |
| `DELETE` | `/api/{session}/groups/{id}/picture` | Delete group picture |
| `PUT` | `/api/{session}/groups/{id}/description` | Updates the group description. |
| `PUT` | `/api/{session}/groups/{id}/subject` | Updates the group subject |
| `PUT` | `/api/{session}/groups/{id}/settings/security/info-admin-only` | Updates the group "info admin only" settings. |
| `GET` | `/api/{session}/groups/{id}/settings/security/info-admin-only` | Get the group's 'info admin only' settings. |
| `PUT` | `/api/{session}/groups/{id}/settings/security/messages-admin-only` | Update settings - who can send messages |
| `GET` | `/api/{session}/groups/{id}/settings/security/messages-admin-only` | Get settings - who can send messages |
| `GET` | `/api/{session}/groups/{id}/invite-code` | Gets the invite code for the group. |
| `POST` | `/api/{session}/groups/{id}/invite-code/revoke` | Invalidates the current group invite code and generates a new one. |
| `GET` | `/api/{session}/groups/{id}/participants` | Get participants |
| `GET` | `/api/{session}/groups/{id}/participants/v2` | Get group participants. |
| `POST` | `/api/{session}/groups/{id}/participants/add` | Add participants |
| `POST` | `/api/{session}/groups/{id}/participants/remove` | Remove participants |
| `POST` | `/api/{session}/groups/{id}/admin/promote` | Promote participants to admin users. |
| `POST` | `/api/{session}/groups/{id}/admin/demote` | Demotes participants to regular users. |

---

## 📞 Calls
> Call handling methods

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `POST` | `/api/{session}/calls/reject` | Reject incoming call |

---

## 📅 Events
> Event Message

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `POST` | `/api/{session}/events` | Send an event message |

---

## 🏷️ Labels
> Labels - available only for WhatsApp Business accounts

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `GET` | `/api/{session}/labels` | Get all labels |
| `POST` | `/api/{session}/labels` | Create a new label |
| `PUT` | `/api/{session}/labels/{labelId}` | Update a label |
| `DELETE` | `/api/{session}/labels/{labelId}` | Delete a label |
| `GET` | `/api/{session}/labels/chats/{chatId}` | Get labels for the chat |
| `PUT` | `/api/{session}/labels/chats/{chatId}` | Save labels for the chat |
| `GET` | `/api/{session}/labels/{labelId}/chats` | Get chats by label |

---

## 🖼️ Media
> Media methods

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `POST` | `/api/{session}/media/convert/voice` | Convert voice to WhatsApp format (opus) |
| `POST` | `/api/{session}/media/convert/video` | Convert video to WhatsApp format (mp4) |

---

## 🧩 Apps
> Applications (built-in integrations)

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `GET` | `/api/apps` | List all apps for a session |
| `POST` | `/api/apps` | Create a new app |
| `GET` | `/api/apps/{id}` | Get app by ID |
| `PUT` | `/api/apps/{id}` | Update an existing app |
| `DELETE` | `/api/apps/{id}` | Delete an app |
| `POST` | `/webhooks/chatwoot/{session}/{id}` | Chatwoot Webhook |
| `GET` | `/api/apps/chatwoot/locales` | Get available languages for Chatwoot app |

---

## 🔍 Observability
> Other methods

| Método | Endpoint | Descrição |
|--------|----------|----------|
| `GET` | `/ping` | Ping the server |
| `GET` | `/health` | Check the health of the server |
| `GET` | `/api/server/version` | Get the version of the server |
| `GET` | `/api/server/environment` | Get the server environment |
| `GET` | `/api/server/status` | Get the server status |
| `POST` | `/api/server/stop` | Stop (and restart) the server |
| `GET` | `/api/server/debug/cpu` | Collect and return a CPU profile for the current nodejs process |
| `GET` | `/api/server/debug/heapsnapshot` | Return a heapsnapshot for the current nodejs process |
| `GET` | `/api/server/debug/browser/trace/{session}` | Collect and get a trace.json for Chrome DevTools |

---

## 🗄️ Storage
> Storage methods

| Método | Endpoint | Descrição |
|--------|----------|----------|

---

## 🔔 Webhooks

Os webhooks são eventos disparados pela API para sua URL configurada. Configure a URL de webhook na criação/atualização da sessão.

| Evento | Descrição |
|--------|----------|
| `session.status` | The event is triggered when the session status changes. |
| `message.reaction` | The event is triggered when a user reacts or removes a reaction. |
| `message.any` | Fired on all message creations, including your own. |
| `message.ack` | Receive events when server or recipient gets the message, read or played it (contacts only). |
| `message.ack.group` | Receive events when participants in a group read or play messages. |
| `message.revoked` | The event is triggered when a user, whether it be you or any other participant, revokes a previously sent message. |
| `message.edited` | The event is triggered when a user edits a previously sent message. |
| `group.v2.join` | When you joined or were added to a group |
| `group.v2.leave` | When you left or were removed from a group |
| `group.v2.update` | When group info is updated |
| `group.v2.participants` | When participants changed - join, leave, promote to admin |
| `presence.update` | The most recent presence information for a chat. |
| `poll.vote` | With this event, you receive new votes for the poll sent. |
| `poll.vote.failed` | There may be cases when it fails to decrypt a vote from the user. Read more about how to handle such events in the documentations. |
| `chat.archive` | The event is triggered when the chat is archived or unarchived |
| `call.received` | The event is triggered when the call is received by the user. |
| `call.accepted` | The event is triggered when the call is accepted by the user. |
| `call.rejected` | The event is triggered when the call is rejected by the user. |
| `label.upsert` | The event is triggered when a label is created or updated |
| `label.deleted` | The event is triggered when a label is deleted |
| `label.chat.added` | The event is triggered when a label is added to a chat |
| `label.chat.deleted` | The event is triggered when a label is deleted from a chat |
| `event.response` | The event is triggered when the event response is received. |
| `event.response.failed` | The event is triggered when the event response is failed to decrypt. |
| `engine.event` | Internal engine event. |

---

## Envio de Lista Interativa (sendList)

> **CONFIRMADO FUNCIONANDO** com WAHA GOWS engine (2026-03-18)

### `POST /api/sendList`

Envia uma mensagem com menu de opções clicáveis.

**Payload:**
```json
{
  "session": "tenant_12",
  "chatId": "5561992008997@c.us",
  "message": {
    "title": "Menu de Opcoes",
    "description": "Escolha uma das opcoes abaixo:",
    "footer": "Syncro CRM",
    "button": "Ver opcoes",
    "sections": [
      {
        "title": "Servicos",
        "rows": [
          {"title": "Suporte Tecnico", "rowId": "suporte", "description": "Fale com nosso time"},
          {"title": "Comercial", "rowId": "comercial", "description": "Conheca nossos planos"},
          {"title": "Financeiro", "rowId": "financeiro", "description": "Duvidas sobre pagamentos"}
        ]
      }
    ]
  }
}
```

**Campos obrigatórios dentro de `message`:**
- `title` — Título do cabeçalho
- `description` — Texto descritivo
- `button` — Label do botão que abre o menu
- `sections[].rows[].title` — Texto de cada opção
- `sections[].rows[].rowId` — ID para matching da resposta

**Campos opcionais:**
- `footer` — Texto de rodapé
- `sections[].rows[].description` — Subtexto da opção

**IMPORTANTE:**
- O `chatId` pode ser `@c.us` ou `@lid` — ambos funcionam
- A resposta do usuário chega como `body = "Suporte Tecnico"` (texto do `title` da row selecionada)
- Matching no chatbot: comparar `body` com o `label` dos branches

---

## Resolução de LID (Link ID)

O WAHA GOWS engine pode enviar contatos como `@lid` em vez de `@c.us`. LIDs são identificadores internos do Meta.

### `GET /api/{session}/lids/{lid}`

Resolve um LID individual para phone number.

**Exemplo:**
```
GET /api/tenant_12/lids/36576092528787
→ {"phone": "5561992008997"}
```

### `GET /api/{session}/lids`

Retorna batch mapping de todos os LIDs conhecidos pela sessão.

**Resposta:**
```json
[
  {"lid": "36576092528787", "phone": "5561992008997"},
  {"lid": "82936489904300", "phone": "5511999999999"}
]
```

---

## Foto de Perfil

### `GET /api/{session}/chats/{chatId}/picture`

Retorna a URL da foto de perfil do contato.

**IMPORTANTE:** Se o contato não tem foto disponível via `@c.us`, tentar com `@lid`:
```
GET /api/tenant_12/chats/5561992008997@c.us/picture  → 404
GET /api/tenant_12/chats/36576092528787@lid/picture   → {"url": "https://..."}
```

---

## Notas de Uso

- **Formato de chatId:** Use número com DDI (sem +) + `@c.us` para contatos individuais (ex: `5511999999999@c.us`)
- **Formato de chatId para grupos:** Use o id do grupo + `@g.us` (ex: `120363xxx@g.us`)
- **Formato de chatId LID:** Use o LID + `@lid` (ex: `36576092528787@lid`)
- **Sessão padrão:** Caso não especificado, use `default` como nome de sessão
- **Engines suportadas:** WEBJS, NOWEB e GOWS (usado em produção)
- **Envio de mídia:** Você pode enviar arquivos via URL pública ou base64
- **Dashboard:** Acesse `/dashboard` para gerenciar sessões visualmente
- **LID vs Phone:** GOWS engine pode usar LID internamente. Sempre armazenar ambos para referência
