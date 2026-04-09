---
auto_generated: true
type: service
class: App\Services\InstagramService
file: app/Services/InstagramService.php
tags: [service, auto]
---

# InstagramService

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Services/InstagramService.php`

## Métodos públicos
| Método | Static | Assinatura |
|---|---|---|
| `sendMessage` |  | `($igsid, $text)` |
| `sendImageAttachment` |  | `($igsid, $url)` |
| `sendMessageWithButtons` |  | `($igsid, $text, $buttons)` |
| `sendButtonTemplate` |  | `($igsid, $text, $buttons)` |
| `sendPrivateReply` |  | `($commentId, $text)` |
| `listConversations` |  | `($limit, $after)` |
| `getConversationParticipants` |  | `($conversationId)` |
| `getProfile` |  | `($igsid)` |
| `getProfilePicture` |  | `($igsid)` |
| `getMe` |  | `()` |
| `getBusinessAccountId` |  | `()` |
| `subscribeToWebhooks` |  | `()` |
| `getUserMedia` |  | `($after)` |
| `replyToComment` |  | `($commentId, $message)` |
| `exchangeToken` | ✅ | `($shortLived)` |

## Links sugeridos
- Notas escritas à mão sobre esse service: procure no vault por `[[InstagramService]]`
