---
type: integration
status: active
provider: Stripe
auth: api_key + signature webhook
related: ["[[Billing (Asaas + Stripe)]]"]
env_vars:
  - STRIPE_KEY
  - STRIPE_SECRET
  - STRIPE_WEBHOOK_SECRET
tags: [integration, billing, stripe, internacional]
---

# Stripe

> Gateway internacional. Cartão de crédito. Default pra novos cadastros (Stripe-first, commit `ee95739`).

## Auth
- **Secret key** no `Authorization: Bearer ...` (server-side)
- **Publishable key** no frontend pra Checkout/Elements
- **Webhook signature** validado via `Stripe::constructEvent()` com `STRIPE_WEBHOOK_SECRET`

## Endpoints usados
- `POST /v1/customers` — criar customer
- `POST /v1/checkout/sessions` — criar Checkout (subscription mode)
- `POST /v1/billing_portal/sessions` — Customer Portal (cancelar/atualizar cartão)
- `GET /v1/subscriptions/{id}` — pegar subscription

## Webhooks (POST `/api/webhook/stripe`)
| Evento | Ação |
|---|---|
| `checkout.session.completed` | Ativa subscription |
| `invoice.paid` | Confirma pagamento recorrente |
| `invoice.payment_failed` | Marca falha, notifica tenant |
| `customer.subscription.deleted` | Suspende tenant |
| `customer.subscription.updated` | Atualiza plano se mudou |

## Coluna `is_usd` vs `is_stripe`
Mudança importante (commit `dbc5542`):
- `is_usd` = boolean indicando moeda do plano (USD vs BRL)
- `is_stripe` = boolean indicando gateway (Stripe vs Asaas)

**São coisas separadas.** Antes ficavam confundidas. Agora `settings/billing` usa `is_usd` pra decidir moeda + features, não `is_stripe`.

## Service
- [[StripeService]] — `app/Services/StripeService.php`
- Webhook handler: `app/Http/Controllers/StripeWebhookController.php`

## Decisões
- [[ADR — Stripe-first pra novos cadastros]]
- [[ADR — is_usd vs is_stripe (separar moeda de gateway)]]
