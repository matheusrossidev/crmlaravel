---
type: integration-reference
status: active
provider: WAHA Plus (devlikeapro)
engine: GOWS
auth: X-Api-Key + HMAC SHA-512
last_review: 2026-04-17
tags: [integration, whatsapp, waha, reference]
---

# WAHA — Referência Completa

> WhatsApp HTTP API não-oficial (devlikeapro). Roda em VPS dedicada. Cobre envio/recebimento de mensagens, sessions, grupos, canais, status, calls, polls, reactions.
>
> **Fonte autoritativa:** [waha.devlike.pro](https://waha.devlike.pro/docs) + [Swagger/OpenAPI](https://waha.devlike.pro/swagger/openapi.json) + [source code](https://github.com/devlikeapro/waha) (Apache-2.0, TypeScript/NestJS)

## Nossa configuração de produção

- **URL**: `https://waha.matheusrossi.com.br` (stack Swarm **separado** do CRM — `syncro_waha`)
- **Engine**: GOWS (Golang, sem browser) — [[02-engines]]
- **Auth API**: header `X-Api-Key` = `WAHA_API_KEY` (no `portainer-stack.yml` do CRM)
- **Webhook HMAC**: `WAHA_WEBHOOK_SECRET` — header `X-Webhook-Hmac`, algoritmo **SHA-512**
- **Sessão por tenant**: `tenant_{id}` (ex: `tenant_12` pro Plataforma 360)
- **Nossa client service**: [[18-nossa-implementacao|WahaService.php]] (46 métodos)

⚠️ **NÃO é `waha.syncro.chat`** — é `waha.matheusrossi.com.br` por razão histórica. Qualquer doc que fale "waha.syncro.chat" está errada.

## Escopo do WAHA — números reais

| Métrica | Valor |
|---------|-------|
| Tags OpenAPI | 9 (Pairing, Api Keys, Sessions, Profile, Chatting, Chats, Calls, Channels, Status) |
| Endpoints HTTP | ~80 |
| Schemas DTO | ~60 |
| Engines | 5 (GOWS, NOWEB, WEBJS, WPP, VENOM) |
| Webhook events | ~25 tipos |
| Client libraries oficiais | 7 (C#, Go, Java, JS/TS, Kotlin, PHP, Python) |

## Índice

### Setup & infra
- [[01-setup-deploy]] — URL prod, env vars, stack Swarm, dashboard, API Keys
- [[02-engines]] — GOWS (usamos) vs NOWEB vs WEBJS vs WPP vs VENOM
- [[03-sessions]] — Lifecycle, states (STOPPED/STARTING/SCAN_QR_CODE/WORKING/FAILED), CRUD de sessions
- [[04-pairing]] — QR code + pairing code

### Perfil & identidade
- [[05-profile]] — `/profile`, nome, status (about), foto

### Mensagens
- [[06-chatting-send]] — 24 endpoints de envio (text, media, list, poll, reaction, forward, edit, mention, etc)
- [[07-chatting-receive]] — Payload inbound + ACK codes + events
- [[14-media-files]] — Upload (URL/base64), download `/api/files/{hash}`, conversion

### Chats & contatos
- [[08-chats]] — 16 endpoints de gestão (overview, messages, archive, pin)
- [[09-contacts-groups]] — Contatos individuais + 30+ endpoints de grupos (participants, admin, invite)

### Features avançadas
- [[10-channels]] — 14 endpoints de canais (newsletters — não usamos hoje)
- [[11-status]] — Stories do WhatsApp (não usamos hoje)
- [[12-calls]] — Rejeitar chamadas
- [[16-polls-reactions-labels]] — Poll/vote, reactions, labels

### Webhook & realtime
- [[13-webhooks-events]] — Todos os eventos, payload, HMAC SHA-512, retry policy

### Internals
- [[15-lid-handling]] — `@lid` é ID interno GOWS, como resolver

### Nossa integração
- [[17-chatwoot-reference]] — Como WAHA→Chatwoot faz (código de referência)
- [[18-nossa-implementacao]] — Cada arquivo nosso com file:line
- [[19-gotchas-producao]] — Tudo que pegamos na prática (parse() retorna error em 4xx, picture sempre 200, etc)

## Cross-reference

- [[WahaService]] (auto-gerado) — lista de métodos PHP da nossa service
- [[WhatsApp Foundation SOLID]] — abstração multi-provider (WAHA + Cloud API)
- [[WhatsApp WAHA]] (módulo do nosso produto) — features que expomos no CRM
- [[Deploy & CI-CD]] — env vars + secrets

## Histórico

- **2026-04-17**: Reescrita completa após validação contra OpenAPI oficial + source code da integração WAHA→Chatwoot. Doc anterior tinha erros (picture 404 que não existe, chaves vestigiais).
