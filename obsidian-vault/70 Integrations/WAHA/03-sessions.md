---
type: integration-reference
topic: sessions
last_review: 2026-04-17
related: ["[[README]]", "[[04-pairing]]", "[[13-webhooks-events]]"]
tags: [waha, sessions, lifecycle]
---

# 03 — Sessions

Uma **session** é uma conexão autenticada com o WhatsApp representando **um número de telefone**. Multi-tenant = múltiplas sessions.

## Status possíveis

5 estados oficiais:

| Status | Significado |
|--------|-------------|
| `STOPPED` | Inativa |
| `STARTING` | Iniciando |
| `SCAN_QR_CODE` | Esperando scan de QR (QR refresh a cada 20s, max 6 attempts antes de `FAILED`) |
| `WORKING` | Operacional, pronta pra enviar/receber |
| `FAILED` | Erro — requer restart ou re-autenticação |

## Lifecycle

```
CREATE → START → SCAN_QR_CODE → [scan QR] → WORKING → STOP → DELETE
                              ↓
                            FAILED (após 6 tentativas)
```

Transições:
- `CREATE` auto-start salvo `"start": false`
- `RESTART` = stop + start imediato
- `LOGOUT` limpa auth mas mantém config
- `DELETE` remove tudo

## Endpoints de CRUD

13 endpoints na tag `Sessions`:

| Método | Path | Descrição |
|--------|------|-----------|
| POST | `/api/sessions` | Criar session |
| GET | `/api/sessions` | Listar todas |
| GET | `/api/sessions/{session}` | Info de uma |
| PUT | `/api/sessions/{session}` | Atualizar config |
| DELETE | `/api/sessions/{session}` | Apagar |
| GET | `/api/sessions/{session}/me` | Info do usuário autenticado |
| POST | `/api/sessions/{session}/start` | Iniciar |
| POST | `/api/sessions/{session}/stop` | Parar |
| POST | `/api/sessions/{session}/logout` | Logout (preserva config) |
| POST | `/api/sessions/{session}/restart` | Restart (stop+start) |
| POST | `/api/sessions/start` | **Deprecated** — upsert+start |
| POST | `/api/sessions/stop` | **Deprecated** |
| POST | `/api/sessions/logout` | **Deprecated** |

## Payload — Create Session

```json
{
  "name": "tenant_12",
  "start": true,
  "config": {
    "proxy": null,
    "webhooks": [
      {
        "url": "https://app.syncro.chat/api/webhook/waha",
        "events": ["message", "session.status", "message.ack", "group.v2.participants", "call.received"],
        "hmac": { "key": "<WAHA_WEBHOOK_SECRET>" },
        "customHeaders": [
          { "name": "X-Tenant-Id", "value": "12" }
        ],
        "retries": {
          "policy": "constant",
          "delaySeconds": 2,
          "attempts": 15
        }
      }
    ],
    "metadata": {
      "tenant_id": "12",
      "owner_email": "admin@demo.com"
    }
  }
}
```

## Payload — Response (SessionDTO)

```json
{
  "name": "tenant_12",
  "status": "WORKING",
  "engine": "GOWS",
  "config": { ... },
  "me": {
    "id": "5511999999999@c.us",
    "pushName": "Syncro Atendimento"
  },
  "metadata": { "tenant_id": "12" }
}
```

## Auto-restart

WAHA rastreia qual worker rodou qual session e **restarta automaticamente quando o worker reinicia**. Controlado por:

```
WAHA_WORKER_RESTART_SESSIONS=True  # default
```

Se desabilitado, cada restart do container requer `POST /api/sessions/{name}/start` manual.

## Storage

**Plus**: volume `/app/.sessions` persiste auth entre restarts. Sem volume → scan QR some.

Para engines NOWEB/GOWS existem config opcionais pra persistir localmente `messages`, `groups`, `chats`, `labels`. Pode ser desabilitado por tipo de dado no config.

## Endpoint /me — info do usuário autenticado

```
GET /api/sessions/{session}/me
```

Retorna `MeInfo`:
```json
{
  "id": "5511999999999@c.us",
  "pushName": "Syncro Atendimento"
}
```

Útil pra verificar que a session ainda tá conectada sem listar mensagens.

## Webhook config

O array `webhooks` aceita múltiplas URLs (raramente necessário). Cada entry:
- `url` — endpoint HTTP
- `events[]` — lista de [[13-webhooks-events|events]] a receber (pra receber tudo, usar `["*"]`)
- `hmac.key` — secret pra HMAC SHA-512 ([[13-webhooks-events#hmac]])
- `customHeaders[]` — headers extras enviados junto
- `retries` — policy (`constant`/`linear`/`exponential`), delay, attempts

Eventos que **assinamos em prod** (no nosso fluxo WAHA→CRM):
- `message`
- `message.any`
- `message.ack`
- `group.v2.participants`
- `session.status`
- `state.change` (legacy mas ainda funciona)

## Gotchas

- **SCAN_QR_CODE só tem 6 tentativas de 20s cada** = ~2 minutos de janela pra scanar. Se expirar, volta pra `STARTING` e tem que tentar de novo.
- **`LOGOUT` vs `DELETE`** — logout mantém a config da session (webhooks, metadata, engine). Delete apaga tudo. Pra trocar o número sem reconfigurar webhook: use LOGOUT + novo START + scan QR novo.
- **Endpoint `/me` pode retornar null temporariamente** durante transição de status — checar `status == WORKING` antes.
- **Multi-session em 1 container exige WAHA Plus** — core (grátis) limita a 1 session.

## Uso na Syncro

- Criação/connect: [IntegrationController.php](app/Http/Controllers/Tenant/IntegrationController.php) — fluxo "Conectar WhatsApp"
- CRUD adm: [WhatsappController.php](app/Http/Controllers/Tenant/WhatsappController.php)
- Client service: [WahaService.php](app/Services/WahaService.php) — métodos `createSession`, `startSession`, `stopSession`, `deleteSession`, `getQrResponse`
- Session name = `WhatsappInstance.session_name` = `"tenant_{id}"`
