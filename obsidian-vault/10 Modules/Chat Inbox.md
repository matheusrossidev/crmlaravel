---
type: module
status: active
related: ["[[WhatsApp WAHA]]", "[[WhatsApp Cloud API]]", "[[Instagram]]", "[[Website Chat]]", "[[ConversationResolver]]"]
files:
  - resources/views/tenant/whatsapp/index.blade.php
  - app/Http/Controllers/Tenant/WhatsappController.php
  - app/Contracts/ConversationContract.php
  - app/Services/ConversationResolver.php
last_review: 2026-04-09
tags: [module, chat, inbox]
---

# Chat Inbox

## O que é
UI **unificada** pra responder mensagens de WhatsApp (WAHA + Cloud API), Instagram DM, e Website. Backend ainda fragmentado em 3 models de Conversation, mas com abstração polimórfica via [[ConversationContract]] + [[ConversationResolver]].

## Status atual
- ✅ UI única (`tenant/whatsapp/index.blade.php`) atende os 3 canais
- ✅ Endpoint genérico `PUT /chats/inbox/{channel}/{conversation}/contact` (Fase 3 dos tags)
- ⚠️ Controller chama-se `WhatsappController` mas atende **todos os 3 canais** — vai virar `InboxController` na Fase 5 do refactor
- ⚠️ 3 models de Conversation em vez de 1 polimórfico — pivot polimórfica de tags já existe mas Conversation ainda é split

## Models envolvidos
- [[WhatsappConversation]] · [[WhatsappMessage]] · [[WhatsappInstance]]
- [[InstagramConversation]] · [[InstagramMessage]] · [[InstagramInstance]]
- [[WebsiteConversation]] · [[WebsiteMessage]]
- Os 3 implementam [[ConversationContract]]

## Services
- [[ConversationResolver]] — `resolve($channel, $id)` retorna o concrete certo
- [[WhatsappServiceFactory]] — pra envio outbound
- [[WahaService]] / [[WhatsappCloudService]] / [[InstagramService]]

## Endpoints chave
| Método | URI | Nome |
|---|---|---|
| GET | `/chats` | `chats.index` |
| GET | `/chats/{conversation}` | `chats.show` (WhatsApp) |
| GET | `/chats/instagram/{conversation}` | Instagram show |
| GET | `/chats/website/{conversation}` | Website show |
| PUT | `/chats/inbox/{channel}/{conversation}/contact` | Endpoint genérico (recomendado) |
| PUT | `/chats/conversations/{id}/contact` | Legado WhatsApp-only (manter compat) |

## Padrões críticos
- **Resolver instance via `conversation->instance_id`** — NUNCA usar `WhatsappInstance::first()` (bug histórico, commit `9daa89d`)
- **Envio outbound** sempre via factory: `WhatsappServiceFactory::for($instance)` — nunca instanciar `WahaService` direto em código novo
- **Listagens da página de Integrações** filtrar por `provider='waha'` OR `NULL` no card WAHA, e `provider='cloud_api'` no card Cloud API (bug histórico, commit `2535d46`)

## Decisões relacionadas
- [[ADR — WhatsApp dual provider via factory]]
- [[ADR — Refactor de tags polimorficas (5 fases)]]

## Pendente (Fase 5)
- Renomear `WhatsappController` → `InboxController`
- Renomear pasta `tenant/whatsapp/` → `tenant/inbox/`
- Drop endpoints legados WhatsApp-only quando todos callers migrarem
