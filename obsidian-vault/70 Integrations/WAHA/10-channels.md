---
type: integration-reference
topic: channels
status: not-in-use
last_review: 2026-04-17
related: ["[[README]]", "[[11-status]]"]
tags: [waha, channels, newsletters]
---

# 10 — Channels (Newsletters)

Channels no WhatsApp = **newsletters broadcast** unidirecionais (tipo canal do Telegram). Admin posta, seguidores recebem sem poder responder.

⚠️ **Não usamos no Syncro hoje.** Documentação de referência caso precise no futuro.

## Endpoints (14 total)

Todos em `/api/{session}/channels/*`:

| Método | Path | Descrição |
|--------|------|-----------|
| GET | `/channels` | Lista canais conhecidos |
| POST | `/channels` | Criar channel |
| GET | `/channels/{id}` | Info do channel |
| DELETE | `/channels/{id}` | Deletar |
| GET | `/channels/{id}/messages/preview` | Preview de mensagens |
| POST | `/channels/{id}/follow` | Seguir |
| POST | `/channels/{id}/unfollow` | Parar de seguir |
| POST | `/channels/{id}/mute` | Mutar |
| POST | `/channels/{id}/unmute` | Desmutar |
| POST | `/channels/search/by-view` | Busca por categoria de view |
| POST | `/channels/search/by-text` | Busca textual |
| GET | `/channels/search/views` | Lista views disponíveis |
| GET | `/channels/search/countries` | Países disponíveis |
| GET | `/channels/search/categories` | Categorias disponíveis |

## Criar channel

```
POST /api/{session}/channels
{
  "name": "Syncro News",
  "description": "Novidades da plataforma",
  "picture": {
    "url": "https://app.syncro.chat/logo.jpg"
  }
}
```

Schema: `CreateChannelRequest`.

## Enviar mensagens

Usa os mesmos endpoints de `/api/sendText` / `sendImage` etc. com `chatId` = channel ID (`120363xxx@newsletter`).

Como channels são broadcast, **só o admin (nós) envia**. Seguidores não respondem.

## Seguir um channel externo

1. Buscar: `POST /channels/search/by-text { "text": "nome" }`
2. Follow: `POST /channels/{id}/follow`
3. Receber mensagens como `message` events normais (com `chatId` terminando em `@newsletter`)

## Gotchas

- **WAHA Plus licença recomendada** pra channels — core pode ter restrições.
- **Channels não aparecem nos mesmos endpoints** de chats tradicionais por padrão — use `/channels` dedicado.
- **Sem resposta dos seguidores** — channels são broadcast-only. Não esperar reply.
- **Seguidores são anônimos por default** — admin não vê lista completa de quem segue.

## Uso futuro possível no Syncro

- Canal de novidades da plataforma (usuários do CRM seguem).
- Alertas de sistema (status, incidents).
- Marketing/newsletter multi-tenant.

Quando implementar, seguir [[18-nossa-implementacao]] pra manter padrões SOLID (extend Foundation).
