---
auto_generated: true
type: service
class: App\Services\SalesGoalService
file: app/Services/SalesGoalService.php
tags: [service, auto]
---

# SalesGoalService

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Services/SalesGoalService.php`

## Métodos públicos
| Método | Static | Assinatura |
|---|---|---|
| `progress` |  | `($goal)` |
| `forecast` |  | `($goal, $progress)` |
| `teamProgress` |  | `($parentGoal)` |
| `achievedBonusTier` |  | `($goal, $rawPct)` |
| `generateSnapshot` |  | `($goal)` |
| `renewRecurring` |  | `($goal)` |
| `ranking` |  | `($tenantId)` |
| `calculateStreak` |  | `($tenantId, $userId)` |
| `userHistory` |  | `($tenantId, $userId, $months)` |

## Links sugeridos
- Notas escritas à mão sobre esse service: procure no vault por `[[SalesGoalService]]`
