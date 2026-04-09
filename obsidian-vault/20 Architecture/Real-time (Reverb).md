---
type: architecture
status: active
related: ["[[Chat Inbox]]"]
files:
  - resources/views/tenant/layouts/app.blade.php
last_review: 2026-04-09
tags: [architecture, websocket, reverb]
---

# Real-time (Reverb)

## O que é
Laravel Reverb (WebSocket nativo) pra broadcasting de mensagens novas, conversation updates, AI intent detected, etc. Substitui Pusher/Soketi sem custo de SaaS.

## Stack
- **Reverb** (porta interna 8080)
- **nginx** faz proxy de `/app/` e `/apps/` → `reverb:8080`
- **Frontend**: Laravel Echo + Pusher.js (Reverb usa protocolo Pusher-compatible)
- **Channels privados**: `private-tenant.{id}`

## Eventos broadcasted
- `WhatsappMessageCreated`
- `WhatsappConversationUpdated`
- `InstagramMessageCreated`
- `InstagramConversationUpdated`
- `AiIntentDetected`
- `MasterNotificationSent`

## ⚠️ Gotcha crítico — VITE_*

**`VITE_*` env vars NÃO estão disponíveis no build do Docker**, porque o `Dockerfile` roda `npm run build` SEM build args. Vars do `portainer-stack.yml` são RUNTIME only.

Usar `import.meta.env.VITE_REVERB_APP_KEY` em JS bundlado pelo Vite resulta em `undefined` em produção → Pusher.js lança `"You must pass your app key"` → bundle falha silenciosamente.

**Padrão correto:**
1. Injetar config no servidor via Blade: `window.reverbConfig = @json([...]);`
2. Ler no `app.js`: `const cfg = window.reverbConfig`

Ver `resources/views/tenant/layouts/app.blade.php` pra exemplo real.

**Bug histórico (commit pré-2026-02-20):** `bootstrap.js` importava `echo.js` que usava `VITE_REVERB_APP_KEY` undefined → bundle falhava → `window.Echo` nunca setado → toast "Tempo real indisponível". Fix: remover `import './echo'` do `bootstrap.js`.

## Config Reverb
- Path no config PHP: `reverb.apps.apps.0.key` (dois `apps`)
- nginx proxy WebSocket headers obrigatórios: `Upgrade`, `Connection`
- Reverb escuta em `0.0.0.0:8080` interno; browser conecta via `wss://dominio/app/`

## Decisões
- [[ADR — Reverb em vez de Pusher SaaS]]
