---
auto_generated: true
type: service
class: App\Services\WahaService
file: app/Services/WahaService.php
tags: [service, auto]
---

# WahaService

> Auto-gerado por `php artisan obsidian:sync`. Não edite à mão — re-rode o sync.

## Arquivo
`app/Services/WahaService.php`

## Métodos públicos
| Método | Static | Assinatura |
|---|---|---|
| `createSession` |  | `($webhookUrl, $webhookSecret)` |
| `patchSession` |  | `($webhookUrl, $webhookSecret)` |
| `getSession` |  | `()` |
| `startSession` |  | `()` |
| `stopSession` |  | `()` |
| `deleteSession` |  | `()` |
| `getQrResponse` |  | `()` |
| `getGroupInfo` |  | `($groupJid)` |
| `getContactInfo` |  | `($contactJid)` |
| `getChatPicture` |  | `($chatId)` |
| `getContactPicture` |  | `($contactJid)` |
| `getGroupPicture` |  | `($groupJid)` |
| `getAllLids` |  | `()` |
| `getPhoneByLid` |  | `($lid)` |
| `setPresence` |  | `($chatId, $presence)` |
| `sendText` |  | `($chatId, $text)` |
| `sendImage` |  | `($chatId, $url, $caption)` |
| `sendImageBase64` |  | `($chatId, $filePath, $mimeType, $caption)` |
| `sendVoice` |  | `($chatId, $url)` |
| `sendVoiceBase64` |  | `($chatId, $filePath, $mimeType)` |
| `sendFileBase64` |  | `($chatId, $filePath, $mimeType, $filename, $caption)` |
| `sendList` |  | `($chatId, $description, $rows, $title, $buttonText, $footer)` |
| `sendReaction` |  | `($messageId, $emoji)` |
| `getChats` |  | `($limit, $offset)` |
| `getChatMessages` |  | `($chatId, $limit, $offset, $downloadMedia, $timestampGte)` |
| `setWebhook` |  | `($url, $events)` |
| `getProviderName` |  | `()` |

## Links sugeridos
- Notas escritas à mão sobre esse service: procure no vault por `[[WahaService]]`
