---
auto_generated: true
type: service
class: App\Services\StripeService
file: app/Services/StripeService.php
tags: [service, auto]
---

# StripeService

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Services/StripeService.php`

## Métodos públicos
| Método | Static | Assinatura |
|---|---|---|
| `createCustomer` |  | `($email, $name)` |
| `getOrCreateCustomer` |  | `($email, $name, $existingId)` |
| `createSubscriptionCheckout` |  | `($customerId, $priceId, $successUrl, $cancelUrl, $metadata, $paymentMethodTypes)` |
| `createPaymentCheckout` |  | `($customerId, $priceId, $successUrl, $cancelUrl, $metadata)` |
| `createPortalSession` |  | `($customerId, $returnUrl)` |
| `cancelSubscription` |  | `($subscriptionId)` |
| `getSubscription` |  | `($subscriptionId)` |
| `constructWebhookEvent` |  | `($payload, $sigHeader)` |
| `retrieveCheckoutSession` |  | `($sessionId)` |

## Links sugeridos
- Notas escritas à mão sobre esse service: procure no vault por `[[StripeService]]`
