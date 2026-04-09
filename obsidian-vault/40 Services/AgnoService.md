---
auto_generated: true
type: service
class: App\Services\AgnoService
file: app/Services/AgnoService.php
tags: [service, auto]
---

# AgnoService

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Services/AgnoService.php`

## Métodos públicos
| Método | Static | Assinatura |
|---|---|---|
| `isAvailable` |  | `()` |
| `chat` |  | `($payload)` |
| `configureAgent` |  | `($agentId, $config)` |
| `configureFromAgent` |  | `($agent)` |
| `storeMemory` |  | `($agentId, $payload)` |
| `searchMemories` |  | `($agentId, $payload)` |
| `indexFile` |  | `($agentId, $tenantId, $fileId, $text, $filename)` |
| `searchKnowledge` |  | `($agentId, $tenantId, $query, $topK)` |
| `deleteKnowledgeFile` |  | `($agentId, $fileId)` |

## Links sugeridos
- Notas escritas à mão sobre esse service: procure no vault por `[[AgnoService]]`
