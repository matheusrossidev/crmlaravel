---
type: integration-reference
topic: contacts-groups
last_review: 2026-04-17
related: ["[[README]]", "[[08-chats]]", "[[15-lid-handling]]"]
tags: [waha, contacts, groups]
---

# 09 — Contacts & Groups

Endpoints separados das tags principais do OpenAPI — ficam em paths próprios `/api/contacts/*` e `/api/{session}/groups/*`.

## Contacts

### GET /api/contacts/all — listar todos

```
GET /api/contacts/all?session=tenant_12&limit=100&offset=0&sortBy=id&sortOrder=asc
```

Response:
```json
[
  {
    "id": "5511999999999@c.us",
    "number": "5511999999999",
    "name": "João Silva",
    "pushname": "Jão",
    "shortName": "João",
    "isMe": false,
    "isGroup": false,
    "isWAContact": true,
    "isMyContact": true,
    "isBlocked": false
  }
]
```

### GET /api/contacts — um contato

```
GET /api/contacts?contactId=5511999999999@c.us&session=tenant_12
```

Aceita número com ou sem sufixo `@c.us`.

### GET /api/contacts/check-exists — checar se é conta WhatsApp

```
GET /api/contacts/check-exists?phone=5511999999999&session=tenant_12
```

Response:
```json
{
  "numberExists": true,
  "chatId": "5511999999999@c.us"
}
```

Útil antes de tentar enviar — evita erro se número não tem WhatsApp.

### POST /api/contacts/block e /unblock

```
POST /api/contacts/block
{
  "contactId": "5511999999999",
  "session": "tenant_12"
}
```

### GET /api/contacts/profile-picture — foto (endpoint alternativo)

```
GET /api/contacts/profile-picture?contactId=5511999999999&session=tenant_12
```

Response:
```json
{
  "profilePictureURL": "https://pps.whatsapp.net/..."
}
```

⚠️ **Diferente de [[08-chats|`/chats/{id}/picture`]]**:
- Este retorna chave `profilePictureURL`
- `/chats/{id}/picture` retorna chave `url`

Duas rotas pro mesmo recurso, chaves diferentes. Cached 24h por default; `?refresh=true` força update.

### GET /api/contacts/about — "about" do contato

```
GET /api/contacts/about?contactId=5511999999999&session=tenant_12
```

Response:
```json
{ "about": "Disponível" }
```

### PUT /api/{session}/contacts/{chatId} — atualizar nome salvo

```
PUT /api/tenant_12/contacts/5511999999999@c.us
{
  "firstName": "João",
  "lastName": "Silva"
}
```

Salva o contato na agenda do número autenticado.

## Groups

Endpoints em `/api/{session}/groups/*`. Total: **~30+ endpoints** (o WAHA documenta bem grupos).

### Listagem

```
GET /api/{session}/groups?limit=10&offset=0&sortBy=subject&sortOrder=asc&exclude=participants
```

`exclude=participants` retorna grupos sem a lista de membros (muito mais rápido se tiver grupos grandes).

```
GET /api/{session}/groups/count
```

Só o count.

### Get info de um grupo

```
GET /api/{session}/groups/{groupId}
```

Retorna info completa (name, description, picture, participants, admins, settings).

### Create group

```
POST /api/{session}/groups
{
  "name": "Meu Grupo",
  "participants": [
    { "id": "5511999999999@c.us" }
  ]
}
```

### Leave group

```
POST /api/{session}/groups/{groupId}/leave
```

### Delete group (só admin)

```
DELETE /api/{session}/groups/{groupId}
```

### Update name

```
PUT /api/{session}/groups/{groupId}/subject
{ "subject": "Novo Nome" }
```

### Update description

```
PUT /api/{session}/groups/{groupId}/description
{ "description": "Nova descrição" }
```

## Group picture

```
GET /api/{session}/groups/{groupId}/picture?refresh=false
PUT /api/{session}/groups/{groupId}/picture   # Plus only
DELETE /api/{session}/groups/{groupId}/picture # Plus only
```

Set picture aceita URL ou base64 no mesmo shape do send-image ([[06-chatting-send]]).

## Participants

### Listar

```
GET /api/{session}/groups/{groupId}/participants/v2
```

Response:
```json
[
  {
    "id": "5511999999999@c.us",
    "role": "participant|admin|superadmin|left"
  }
]
```

### Add / Remove / Promote / Demote

```
POST /api/{session}/groups/{groupId}/participants/add
POST /api/{session}/groups/{groupId}/participants/remove
POST /api/{session}/groups/{groupId}/admin/promote
POST /api/{session}/groups/{groupId}/admin/demote
```

Body:
```json
{
  "participants": [
    { "id": "5511999999999@c.us" }
  ]
}
```

## Invite links

```
GET /api/{session}/groups/{groupId}/invite-code
POST /api/{session}/groups/{groupId}/invite-code/revoke   # revoga e gera novo
GET /api/{session}/groups/join-info?code=XYZ             # info antes de entrar
POST /api/{session}/groups/join                          # entrar via code
```

Body do `/join`:
```json
{ "code": "Abc123DefGhi" }
```

## Group security settings

```
GET  /api/{session}/groups/{groupId}/settings/security/info-admin-only
PUT  /api/{session}/groups/{groupId}/settings/security/info-admin-only
{ "adminsOnly": true }

GET  /api/{session}/groups/{groupId}/settings/security/messages-admin-only
PUT  /api/{session}/groups/{groupId}/settings/security/messages-admin-only
{ "adminsOnly": true }
```

Controla se não-admins podem editar info ou enviar mensagens.

## Events relacionados (webhook)

Ver [[13-webhooks-events]] pra payloads completos:
- `group.v2.join` — você entrou/foi adicionado
- `group.v2.leave` — você saiu/foi removido
- `group.v2.participants` — alguém entrou/saiu/virou admin
- `group.v2.update` — info do grupo mudou

Eventos `group.join`/`group.leave` (sem v2) são **deprecated**.

## Gotchas

- **`/api/contacts/*` endpoints usam QUERY STRING** pra `session` e `contactId`, não path. Diferente da maioria das outras tags.
- **Duas keys diferentes pra foto**: `url` em `/chats/{id}/picture`, `profilePictureURL` em `/contacts/profile-picture`. Nossa WahaService usa `/chats/.../picture` + key `url`.
- **Cache de 24h** em `/contacts/profile-picture` — use `refresh=true` se precisa forçar refetch (e.g., "usuário trocou foto, sincronizar já").
- **`participants/v2`** — versão nova. Versão legacy `/participants` pode não retornar `role` corretamente.
- **Grupos grandes (500+ membros)**: `exclude=participants` faz muita diferença no timing.
- **Admin-only settings**: só super admin pode alterar.

## Uso na Syncro

- [WahaService::getContactInfo](app/Services/WahaService.php) — usa path `/api/contacts?contactId=...`
- [WahaService::getGroupInfo](app/Services/WahaService.php) — info de grupo
- [ImportWhatsappHistory](app/Jobs/ImportWhatsappHistory.php) — chama `getContactInfo` antes de criar conversa (fallback de pushName)
- Grupos: apenas leitura; não criamos/modificamos grupos via CRM hoje.
