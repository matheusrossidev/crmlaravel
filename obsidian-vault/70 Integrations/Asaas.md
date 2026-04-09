---
type: integration
status: active
provider: Asaas (Brasil)
auth: api_key + token webhook
related: ["[[Billing (Asaas + Stripe)]]", "[[Partner Program]]"]
env_vars:
  - ASAAS_API_URL
  - ASAAS_API_KEY
  - ASAAS_WEBHOOK_TOKEN
tags: [integration, billing, brasil, asaas]
---

# Asaas

> Gateway de pagamento brasileiro. PIX, boleto, cartão. Usado pra subscriptions de tenants brasileiros + pagamentos de token increments + saques de parceiros (Transfer API).

## URLs
- **Produção**: `https://www.asaas.com/api/v3`
- **Sandbox**: `https://sandbox.asaas.com/api/v3`

Configurado via `ASAAS_API_URL` no Portainer.

## Auth
- API key no header `access_token` (note: nome do header é literal `access_token`, não `Authorization`)
- Webhook validation: token na URL (`?token=...` configurado em `ASAAS_WEBHOOK_TOKEN`)

## Endpoints usados
| Método | Endpoint | Função |
|---|---|---|
| `POST` | `/customers` | Criar customer |
| `POST` | `/payments` | Criar cobrança PIX/boleto/cartão |
| `POST` | `/subscriptions` | Criar subscription recorrente |
| `GET` | `/payments/{id}/pixQrCode` | QR PIX |
| `POST` | `/transfers` | Saque PIX (Transfer API — exige ativação manual) |
| `GET` | `/payments?externalReference={ref}` | Buscar por external reference |

## Webhooks (POST `/api/webhook/asaas`)
| Evento | Ação |
|---|---|
| `PAYMENT_RECEIVED` / `PAYMENT_CONFIRMED` | Ativa subscription, limpa `ai_tokens_exhausted` |
| `PAYMENT_OVERDUE` | Marca overdue, envia email |
| `SUBSCRIPTION_INACTIVATED` | Suspende tenant |
| `TRANSFER_DONE` | Marca PartnerWithdrawal como pago |
| `TRANSFER_FAILED` | Marca falha + log |

## externalReference patterns
- `subscription:{tenant_id}` — pagamento de subscription
- `token_increment:{id}` — pagamento de pacote de tokens IA
- `partner_withdrawal:{id}` — saque de parceiro

## Transfer API (saques PIX)
**Não vem ativada por default.** Cliente Asaas precisa:
1. Solicitar ativação no painel Asaas (atendimento manual)
2. Receber token SMS pra validar
3. Configurar webhook pra eventos `TRANSFER_*`

Ver [[Asaas Transfer Setup]] pra passo-a-passo.

## Service
- [[AsaasService]] — `app/Services/AsaasService.php`
- Webhook handler: [`AsaasWebhookController`](app/Http/Controllers/AsaasWebhookController.php)

## Decisões / Notas
- [[Billing (Asaas + Stripe)]]
- Stripe-first pra novos cadastros (commit `ee95739`) — Asaas continua suportado mas não é o default
