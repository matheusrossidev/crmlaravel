---
auto_generated: true
type: service
class: App\Services\WhatsappCloudService
file: app/Services/WhatsappCloudService.php
tags: [service, auto]
---

# WhatsappCloudService

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Services/WhatsappCloudService.php`

## Métodos públicos
| Método | Static | Assinatura |
|---|---|---|
| `getProviderName` |  | `()` |
| `sendText` |  | `($chatId, $text)` |
| `sendImage` |  | `($chatId, $url, $caption)` |
| `sendImageBase64` |  | `($chatId, $filePath, $mimeType, $caption)` |
| `sendVoice` |  | `($chatId, $url)` |
| `sendVoiceBase64` |  | `($chatId, $filePath, $mimeType)` |
| `sendFileBase64` |  | `($chatId, $filePath, $mimeType, $filename, $caption)` |
| `sendList` |  | `($chatId, $description, $rows, $title, $buttonText, $footer)` |
| `sendReaction` |  | `($messageId, $emoji)` |
| `sendReactionWithRecipient` |  | `($chatId, $messageId, $emoji)` |
| `subscribeApp` |  | `()` |
| `listPhoneNumbers` |  | `($wabaId)` |
| `getMediaInfo` |  | `($mediaId)` |
| `downloadMediaBinary` |  | `($url)` |

## Links sugeridos
- Notas escritas à mão sobre esse service: procure no vault por `[[WhatsappCloudService]]`
