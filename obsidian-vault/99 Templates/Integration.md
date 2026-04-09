---
type: integration
status: active
provider: ""
auth: oauth | api_key | webhook
related: []
env_vars: []
tags: [integration]
---

# {{title}}

> O que essa integração faz pelo Syncro

## Tipo de auth
> OAuth / API key / HMAC webhook / etc

## Env vars
| Var | Onde se obtém | Notas |
|---|---|---|
| `EXEMPLO_KEY` | Painel do provider | Encriptada no banco |

## Endpoints usados
- `GET /v1/foo` → ...
- `POST /v1/bar` → ...

## Webhooks recebidos
- `POST /api/webhook/{{slug}}` → handler `XController@handle`
- HMAC: `HEADER_NAME` com `APP_SECRET`

## Code touchpoints
- Service: [[XService]]
- Job: [[ProcessXWebhook]]
- Controller: ...

## Limitações conhecidas
- ...

## Decisões / RCAs
- ...
