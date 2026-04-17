---
type: integration
status: active
provider: WAHA Plus (devlikeapro)
auth: api_key + hmac webhook
related: ["[[WhatsApp WAHA]]", "[[WahaService]]"]
env_vars:
  - WAHA_BASE_URL
  - WAHA_API_KEY
  - WAHA_WEBHOOK_SECRET
last_review: 2026-04-17
tags: [integration, whatsapp, waha]
---

# WAHA

> WhatsApp HTTP API não-oficial. Engine GOWS (Go-WhatsApp-Web).

## URL de produção
**`https://waha.matheusrossi.com.br`** — WAHA roda em stack Swarm **separado** (não faz parte do `portainer-stack.yml` do CRM). O CRM conecta via env `WAHA_BASE_URL` apontando pra esse domínio.

⚠️ **Atenção**: NÃO é `waha.syncro.chat` — o domínio do WAHA hoje é `waha.matheusrossi.com.br` por razões históricas.

## Auth
- **API key** no header `X-Api-Key` (todas as chamadas pra WAHA)
- **Webhook HMAC custom** validado em [`WhatsappWebhookController`](app/Http/Controllers/WhatsappWebhookController.php) — `WAHA_WEBHOOK_SECRET`

## Deploy
WAHA roda como **stack Swarm dedicado** (`syncro_waha` em `waha.matheusrossi.com.br`), fora do stack `syncro_*` do CRM. Multi-sessão por tenant — cada `WhatsappInstance.session_name` mapeia pra uma sessão WAHA. A rede `serverossi` (Traefik) compartilha tráfego entre os stacks.

## Endpoints principais
| Método | Endpoint | Função |
|---|---|---|
| `POST` | `/api/sessions` | Criar sessão |
| `GET` | `/api/sessions/{session}/qr` | QR code (binário PNG) |
| `POST` | `/api/sendText` | Enviar texto |
| `POST` | `/api/sendImage` | Enviar imagem |
| `POST` | `/api/sendList` | Enviar lista interativa |
| `POST` | `/api/sendVoice` | Enviar áudio |
| `POST` | `/api/sendFile` | Enviar arquivo (PDF, etc) |
| `GET` | `/api/{session}/chats` | Lista chats |
| `GET` | `/api/{session}/chats/{id}/messages` | Histórico |
| `GET` | `/api/{session}/chats/{id}/picture` | Foto do contato |
| `GET` | `/api/{session}/groups/{id}` | Info do grupo |
| `GET` | `/api/{session}/groups/{id}/picture` | Foto do grupo |
| `GET` | `/api/{session}/lids/{lid}` | Resolver LID → phone |
| `GET` | `/api/{session}/lids` | Batch mapping LIDs |
| `GET` | `/api/{session}/contacts/{id}` | Info do contato (nome da agenda) |

## Webhook eventos
WAHA envia eventos via `POST` pra `WAHA_WEBHOOK_URL` configurada por sessão:
- `message` — mensagem nova
- `message.any` — qualquer mudança em mensagem (race com `message`!)
- `message.ack` — ACK update
- `state.change` — estado da sessão (CONNECTED/SCAN_QR_CODE/etc)
- `group.join`, `group.leave`

## Race condition crítica
WAHA envia tanto `message` QUANTO `message.any` pra cada mensagem nova. Sem dedup, cria conversação 2x.

**Fix**: dedup atômico Redis no início do `handleInbound()`:
```php
if (! Cache::add("waha:processing:{$msgId}", 1, 10)) {
    return; // já está sendo processado
}
```

Combinado com UNIQUE constraint em `whatsapp_messages.waha_message_id`.

## LID handling
GOWS engine às vezes manda `from: XXX@lid` em vez de `@c.us`. Ver [[WhatsApp WAHA]] pro flow de resolução.

## Limitações
- API não-oficial — Meta pode banir conta a qualquer momento
- Throughput depende de configuração do WAHA + número
- Sem suporte oficial pra templates de mensagem (só números business)
- LID resolution às vezes falha (mensagem é descartada nesse caso)

## Documentação
Local: `docs/waha-api-docs.md` (cópia da doc oficial)
