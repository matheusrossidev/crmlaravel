---
auto_generated: true
type: service
class: App\Services\MasterWhatsappNotifier
file: app/Services/MasterWhatsappNotifier.php
tags: [service, auto]
---

# MasterWhatsappNotifier

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Services/MasterWhatsappNotifier.php`

## Métodos públicos
| Método | Static | Assinatura |
|---|---|---|
| `newRegistration` | ✅ | `($tenant, $user, $agencyName)` |
| `newAgencyRegistration` | ✅ | `($tenant, $user, $code)` |
| `paymentConfirmed` | ✅ | `($tenant, $value, $gateway, $paymentId)` |
| `tokenPurchase` | ✅ | `($tenant, $tokens, $price, $gateway)` |
| `weeklyReport` | ✅ | `()` |

## Links sugeridos
- Notas escritas à mão sobre esse service: procure no vault por `[[MasterWhatsappNotifier]]`
