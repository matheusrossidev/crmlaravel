---
type: module
status: active
related: ["[[Asaas]]", "[[Stripe]]", "[[Tenant]]", "[[PlanDefinition]]"]
files:
  - app/Services/AsaasService.php
  - app/Services/StripeService.php
  - app/Http/Controllers/AsaasWebhookController.php
  - app/Http/Controllers/StripeWebhookController.php
last_review: 2026-04-09
tags: [module, billing, payments]
---

# Billing (Asaas + Stripe)

## O que é
Billing dual: **Asaas** pra Brasil (PIX, boleto, cartão) e **Stripe** pra internacional (cartão). `PaymentLog` registra todos os pagamentos independente do gateway.

## Status
- ✅ Asaas: subscription, PIX checkout, token increment purchase
- ✅ Stripe: subscription via Checkout, recurring invoice, customer portal
- ✅ Stripe-first pra novos cadastros (commit `ee95739`)
- ✅ Webhook handlers idempotentes via `external_reference` ou `stripe_event_id`
- ✅ Coluna `is_usd` separada de `is_stripe` (commit `dbc5542`)
- ⚠️ Asaas Transfer Setup: ativação manual de transferências PIX (ver [[Asaas Transfer Setup]])

## Webhooks Asaas
| Evento | Ação |
|---|---|
| `PAYMENT_RECEIVED` / `PAYMENT_CONFIRMED` | Ativa subscription, limpa `ai_tokens_exhausted` |
| `PAYMENT_OVERDUE` | Marca overdue, envia email |
| `SUBSCRIPTION_INACTIVATED` | Suspende tenant |

## Webhooks Stripe
| Evento | Ação |
|---|---|
| `checkout.session.completed` | Ativa subscription |
| `invoice.paid` | Confirma pagamento recorrente |
| `invoice.payment_failed` | Marca falha, notifica |
| `customer.subscription.deleted` | Suspende tenant |

## Token Increments
- `externalReference = "token_increment:{id}"` identifica pagamento de tokens IA
- Ao pagar: `TenantTokenIncrement.status = 'paid'`, `tenant.ai_tokens_exhausted = false`
- Modal de upsell em `/ia/agentes` abre quando quota esgotada

## Decisões / RCAs
- [[ADR — Stripe-first pra novos cadastros]]
- [[ADR — is_usd vs is_stripe (separar moeda de gateway)]]
