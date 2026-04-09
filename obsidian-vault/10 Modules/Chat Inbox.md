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

## Autoria de mensagens (`sent_by`)

A partir do commit `3f0f816`, toda mensagem outbound nas 3 tabelas (`whatsapp_messages`, `instagram_messages`, `website_messages`) tem coluna `sent_by` (varchar 20, nullable) + `sent_by_agent_id` (FK pra `ai_agents`). Isso permite distinguir visualmente no chat e gerar métricas tipo "% mensagens da IA vs humano".

Valores possíveis:
- `human` — atendente clicou enviar pelo CRM (`user_id` populado)
- `human_phone` — mandado do celular do dono (echo do WAHA, sem intent)
- `ai_agent` — Camila/Sophia/qualquer AiAgent (`sent_by_agent_id` populado)
- `chatbot` — fluxo do chatbot builder
- `automation` — `AutomationEngine`
- `scheduled` — cron `whatsapp:send-scheduled`
- `followup` — IA reativando lead inativo
- `event` — eventos de sistema da IA (stage/transfer/tags)

NULL = mensagem antiga pre-feature, sem badge no chat.

### Cache de intent (chatbot WhatsApp)

`ProcessChatbotStep` para WhatsApp não cria `WhatsappMessage` direto — manda via WAHA e a mensagem nasce no banco quando o webhook volta com `fromMe=true` (echo). Pra atribuir autoria correta, usa **cache de intent**:

```php
// Em ProcessChatbotStep, antes de cada sendText:
Cache::put("outbound_intent:{$conv->id}:" . md5(trim($body)), [
    'sent_by' => 'chatbot',
    'sent_by_agent_id' => null,
], 120);
```

E `ProcessWahaWebhook`, ao salvar mensagem outbound do echo, faz `Cache::pull` da mesma chave. Sem intent = `human_phone` (mandado fora do CRM).

Pattern reusável pra qualquer fonte futura. Detalhes em [[2026-04-09 Marcacao de autoria sent_by]].

### Frontend

`tenant/whatsapp/index.blade.php` renderiza badge na bolha de cada outbound:
- Pra IA: avatar circular 16px do agent + nome + animação `msg-author-pulse` no primeiro render
- Pra outros: label texto colorido (`.msg-author-{tipo}`)
- 8 cores diferentes (roxo IA, índigo chatbot, verde automation, azul scheduled, etc)

Backend: `WhatsappController::formatMessage` faz eager load `with(['user:id,name', 'sentByAgent:id,name,display_avatar'])` e devolve `sent_by` + `sent_by_agent` no JSON. Mesmo padrão pra Instagram (`showInstagram`).

### Backfill

`php artisan messages:backfill-authorship [--dry-run] [--tenant=N]`. Heurística:
- `outbound + user_id != null` → `human`
- `outbound + type='event' + media_mime LIKE 'ai_%'` → `event`
- Resto fica NULL (sem badge — não dá pra adivinhar)

## Padrões críticos
- **Resolver instance via `conversation->instance_id`** — NUNCA usar `WhatsappInstance::first()` (bug histórico, commit `9daa89d`)
- **Envio outbound** sempre via factory: `WhatsappServiceFactory::for($instance)` — nunca instanciar `WahaService` direto em código novo
- **Listagens da página de Integrações** filtrar por `provider='waha'` OR `NULL` no card WAHA, e `provider='cloud_api'` no card Cloud API (bug histórico, commit `2535d46`)
- **TODA criação direta de mensagem outbound** DEVE setar `sent_by`. Spots cobertos: ver [[2026-04-09 Marcacao de autoria sent_by]]
- **Fontes que não criam mensagem direto** (chatbot, etc) DEVEM registrar intent no cache antes do `sendText` pra que o webhook aplique no echo

## Decisões relacionadas
- [[2026-04-09 Marcacao de autoria sent_by]]
- [[ADR — WhatsApp dual provider via factory]]
- [[ADR — Refactor de tags polimorficas (5 fases)]]

## Pendente (Fase 5)
- Renomear `WhatsappController` → `InboxController`
- Renomear pasta `tenant/whatsapp/` → `tenant/inbox/`
- Drop endpoints legados WhatsApp-only quando todos callers migrarem
