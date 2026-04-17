---
type: integration-reference
topic: calls
last_review: 2026-04-17
related: ["[[README]]", "[[13-webhooks-events]]"]
tags: [waha, calls]
---

# 12 — Calls

WhatsApp API **não suporta atender/fazer chamadas de voz/video** — só REJEITAR ou ignorar. É limitação de protocolo do WhatsApp, não do WAHA.

## Endpoint (1 total)

Tag `Calls` no OpenAPI tem apenas 1 endpoint:

| Método | Path | Descrição |
|--------|------|-----------|
| POST | `/api/{session}/calls/reject` | Rejeita chamada recebida |

## Reject call

```
POST /api/tenant_12/calls/reject
{
  "id": "AAA..."
}
```

`id` vem do event `call.received`.

Schema: `RejectCallRequest`.

## Auto-reject (via Apps)

WAHA tem um **app embutido** pra auto-rejeitar TODAS as chamadas:

```
POST /api/apps
{
  "name": "reject-calls",
  "config": {
    "enabled": true
  }
}
```

Ver [[README]] + doc oficial `apps/reject-calls/`.

Ativar `WAHA_APPS_ENABLED=True` + listar `reject-calls` em `WAHA_APPS_ON`.

## Events relacionados

No webhook ([[13-webhooks-events]]):

```json
{
  "event": "call.received",
  "payload": {
    "id": "AAA...",
    "from": "5511999999999@c.us",
    "isVideo": false,
    "isGroup": false,
    "timestamp": 1745123456
  }
}
```

Também `call.accepted` (raríssimo — só se outro device atender) e `call.rejected` (quando rejeitamos ou o caller desiste).

## Gotchas

- **Não dá pra atender chamadas via API** — limitação total do WhatsApp. Se precisar, usar WhatsApp Business diretamente no celular.
- **Auto-reject é a melhor prática** pra números comerciais — evita telefone vibrando toda hora.
- **Call missed** não dispara mensagem automática — se quiser avisar "Recebemos sua chamada, responda por aqui", tem que gerar manualmente após `call.received`.

## Uso na Syncro

- Não implementamos auto-reject hoje. Sessions WAHA não configuram o app `reject-calls`.
- Eventos `call.*` chegam mas nosso [ProcessWahaWebhook](app/Jobs/ProcessWahaWebhook.php) não trata especificamente.
- **Feature request válida**: habilitar auto-reject + enviar mensagem automática "Atendimento só via chat, por favor digite sua dúvida".
