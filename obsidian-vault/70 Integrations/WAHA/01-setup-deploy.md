---
type: integration-reference
topic: setup
last_review: 2026-04-17
related: ["[[README]]", "[[03-sessions]]", "[[Deploy & CI-CD]]"]
tags: [waha, setup, deploy, env]
---

# 01 — Setup & Deploy

## URL de produção

**`https://waha.matheusrossi.com.br`** — stack Swarm **separado** do CRM. O CRM conecta via `WAHA_BASE_URL`.

Serviço interno no Swarm chama-se `syncro_waha` (ou similar). O domínio vem via Traefik na mesma rede `serverossi` compartilhada com o CRM.

## Env vars no Portainer do CRM

| Var | Valor | Origem |
|-----|-------|--------|
| `WAHA_BASE_URL` | `https://waha.matheusrossi.com.br` | [portainer-stack.yml:81](portainer-stack.yml#L81) |
| `WAHA_API_KEY` | (secret) | [portainer-stack.yml:82](portainer-stack.yml#L82) |
| `WAHA_WEBHOOK_SECRET` | (secret, HMAC SHA-512) | [portainer-stack.yml:83](portainer-stack.yml#L83) |

Lido no Laravel via [config/services.php](config/services.php) em `services.waha.*`.

## Autenticação

Todas as chamadas HTTP pro WAHA exigem header:

```
X-Api-Key: <WAHA_API_KEY>
```

Sem esse header: retorna 401 Unauthorized. Nossa [WahaService:client()](app/Services/WahaService.php#L389) injeta automaticamente em toda request.

### API Keys — CRUD dinâmico

O próprio WAHA expõe endpoints pra criar múltiplas API keys (útil pra separar uso/revogar):

| Método | Path | Descrição |
|--------|------|-----------|
| POST | `/api/keys` | Cria API key |
| GET | `/api/keys` | Lista todas |
| PUT | `/api/keys/{id}` | Atualiza |
| DELETE | `/api/keys/{id}` | Revoga |

Não usamos hoje — temos uma key estática. Se um dia precisar rotação, tem endpoint.

## Dashboard

WAHA vem com dashboard web visual em `/dashboard`. Config por env var. Permite:
- Listar sessions ativas
- Ver QR ao vivo
- Monitor de eventos em tempo real
- Testar envio de mensagens

Acesso direto: `https://waha.matheusrossi.com.br/dashboard` (auth via `X-Api-Key` como query string ou header).

## Docker — como o WAHA roda

Imagem: `devlikeapro/waha-plus` (versão Plus — paga, mas já licenciada). Core é `devlikeapro/waha`.

Comando básico:

```bash
docker run -v `pwd`/.sessions:/app/.sessions \
  -e "WHATSAPP_DEFAULT_ENGINE=GOWS" \
  -e "WAHA_API_KEY_PLAIN=<secret>" \
  devlikeapro/waha-plus
```

**Volume `/app/.sessions`** — persiste auth de cada session entre restarts. **Sem volume, scan de QR perde-se no restart.**

## Multi-tenant por session

Cada tenant do nosso CRM tem 1 `WhatsappInstance` com `session_name = "tenant_{id}"`. O mesmo container WAHA Plus aguenta múltiplas sessions simultâneas (Plus é licença que libera multi-sessão por container).

Na prática: tenant 12 → session `tenant_12`, tenant 33 → session `tenant_33`, etc.

## Auto-restart

WAHA rastreia qual worker rodou qual session e restarta automaticamente quando o worker reinicia.

Controlado por `WAHA_WORKER_RESTART_SESSIONS=True` (default). Pode desabilitar se quiser gerenciar manualmente.

## Rede

- WAHA roda na rede `serverossi` (compartilhada com Traefik)
- CRM app chama WAHA via `WAHA_BASE_URL` (HTTPS público)
- WAHA chama CRM via webhook pro endpoint `POST /api/webhook/waha` ([routes/api.php](routes/api.php)) com validação HMAC SHA-512 ([[13-webhooks-events]])

## Gotchas

- **Não é `waha.syncro.chat`** — domínio histórico é `waha.matheusrossi.com.br`. Vários lugares (minhas docs antigas inclusive) tinham errado.
- **`WAHA_API_KEY` no header** é diferente de `WAHA_WEBHOOK_SECRET` (HMAC do webhook). São dois secrets distintos.
- **Volume de sessions obrigatório em prod** — sem ele, QR scan some no primeiro restart e tenants precisam re-parear.
- **Plus licença persiste no Portainer** — nunca apagar o env `WAHA_LICENSE_KEY` se existir.

## Uso na Syncro

- [[18-nossa-implementacao|WahaService.php]] — client HTTP
- [[03-sessions]] — gestão de sessions no [IntegrationController](app/Http/Controllers/Tenant/IntegrationController.php) + [WhatsappController](app/Http/Controllers/Tenant/WhatsappController.php)
- [[13-webhooks-events|WhatsappWebhookController]] — recebe eventos + valida HMAC
