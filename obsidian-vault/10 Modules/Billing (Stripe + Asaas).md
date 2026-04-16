---
type: module
status: active
related: ["[[Stripe]]", "[[Asaas]]", "[[Tenant]]", "[[PlanDefinition]]"]
files:
  - app/Http/Controllers/Tenant/BillingController.php
  - app/Services/StripeService.php
  - app/Services/AsaasService.php
  - app/Http/Controllers/StripeWebhookController.php
  - app/Http/Controllers/AsaasWebhookController.php
last_review: 2026-04-17
tags: [module, billing, payments, stripe, asaas]
---

# Billing

## Estado atual (2026-04-17)

**Stripe é o principal gateway pra subscriptions novas.** Asaas está em papéis **específicos**: subscriptions legadas (forever-locked), Token Increments PIX e Partner Withdrawals PIX.

## Papéis dos gateways

### Stripe (principal)
- **Subscriptions novas** (BRL + USD, mensal + anual) — `/configuracoes/cobranca` cai em Stripe Checkout por default
- **Planos anuais (abr/2026):** cada ciclo (monthly/yearly) é uma row separada em `plan_definitions`, vinculada por `group_slug`. Admin cria variante anual em `/master/planos` com seu próprio `stripe_price_id`. Tenant guarda `billing_cycle` (monthly/yearly).
- `PlanDefinition.stripe_price_id_brl` / `stripe_price_id_usd` — resolvidos por `stripePriceIdFor($currency)`
- `PlanDefinition.is_recommended` — marca 1 plano por ciclo como "Mais popular" no checkout. `ensureSingleRecommended()` garante constraint.
- **Checkout redesenhado (abr/2026):** layout centralizado com tabs Mensal/Anual + grid cards agrupados por `group_slug`. Controller agrupa via `BillingController::buildPlanGroups()`. Badge "Economize X%" via `yearlyDiscountPctVs()`.
- **Prices são IMUTÁVEIS** — pra mudar preço, criar Price novo no Dashboard + colar ID; quem já paga fica no Price antigo (forever)
- **Downgrade anual → mensal mid-cycle:** bloqueado. User troca na renovação via Stripe Customer Portal.
- Stripe Customer Portal pra self-service (trocar cartão, cancelar)
- Webhook: `checkout.session.completed` (grava `billing_cycle`), `invoice.payment_succeeded`, `invoice.payment_failed`, `customer.subscription.deleted`

### Asaas (3 papéis específicos)

**1. Legacy subscriptions**
- Tenants com `asaas_subscription_id` ficam **forever-locked** em Asaas
- `BillingController::subscribe` (cartão inline, sem redirect)
- Webhook: `PAYMENT_RECEIVED`, `PAYMENT_CONFIRMED`, `PAYMENT_OVERDUE`, `SUBSCRIPTION_INACTIVATED`

**2. Token Increments** (compra de tokens IA)
- `TokenIncrementController::purchase` cria Payment PIX via Asaas (sem alternativa Stripe no controller)
- `externalReference = "token_increment:{id}"` identifica no webhook
- Webhook → `TenantTokenIncrement.status='paid'` + `tenant.ai_tokens_exhausted=false`
- Modal de upsell em `/ia/agentes` abre quando quota esgotada

**3. Partner Withdrawals**
- `PartnerWithdrawalController` cria Transfer PIX via Asaas Transfers API
- Webhook `TRANSFER_DONE` marca `PartnerWithdrawal.status='paid'`
- Ver [[Asaas Transfer Setup]] pra setup de permissões

## PaymentLog — fonte única

`PaymentLog` registra TODOS os pagamentos (Asaas + Stripe) — cada row tem `asaas_payment_id` OU `stripe_session_id`/`stripe_invoice_id`. Page `/configuracoes/cobranca` unifica histórico buscando de ambos.

## Partner Commissions — agnóstico de gateway

Ambos webhooks disparam `PartnerCommissionService::generateCommission()` quando pagamento rola. 30 dias de carência → comando `partners:release-commissions` marca como `available` → parceiro saca via PIX Asaas.

## Roteamento de gateway

`BillingController::showCheckout` decide:
- Tem `stripe_subscription_id`? → Stripe portal
- Tem `asaas_subscription_id`? → Asaas (legacy, forever-locked)
- Sem nenhum dos dois? → Default Stripe Checkout (BRL ou USD por detecção de moeda)

## Páginas de billing (estado abr/2026)

**`/configuracoes/cobranca` (billing settings):**
- **Não assinado:** tabs Mensal/Anual + grid de cards (mesmo estilo checkout). Clicar "Assinar" vai direto pro Stripe Checkout.
- **Assinado:** hero card azul horizontal full-width (plano, ciclo, status, preço, botões) + histórico de cobranças.

**`/cobranca/checkout` (checkout standalone):**
- Página standalone (fora do layout app). Logo centralizada + tabs + 3 cards.
- Plano `is_recommended` no meio com badge "Mais popular".
- i18n: todas strings via `lang/{pt_BR,en}/settings.php` (chaves `checkout_*`).

## Decisões / RCAs
- [[ADR — Stripe-first pra novos cadastros]]
- [[ADR — is_usd vs is_stripe (separar moeda de gateway)]]
- [[ADR — Asaas forever-locked pra tenants legacy]]
- [[ADR — Planos anuais como rows separadas (não colunas extras)]]
