---
auto_generated: true
type: service
class: App\Services\AiAgentService
file: app/Services/AiAgentService.php
tags: [service, auto]
---

# AiAgentService

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Services/AiAgentService.php`

## Métodos públicos
| Método | Static | Assinatura |
|---|---|---|
| `buildSystemPrompt` |  | `($agent, $stages, $availTags, $enableIntentNotify, $calendarEvents, $lead, $conv)` |
| `buildWebChatSystemPrompt` |  | `($agent, $stages, $availTags, $enableIntentNotify, $lead)` |
| `buildHistory` |  | `($conv, $limit)` |
| `splitIntoMessages` |  | `($text, $maxLength)` |
| `cleanFormatting` |  | `($text)` |
| `sendWhatsappReplies` |  | `($conv, $messages, $delaySeconds)` |
| `transcribeAudio` |  | `($mediaUrl)` |
| `sendWhatsappReply` |  | `($conv, $text)` |
| `sendMediaReply` |  | `($conv, $agent, $mediaId)` |

## Links sugeridos
- Notas escritas à mão sobre esse service: procure no vault por `[[AiAgentService]]`
