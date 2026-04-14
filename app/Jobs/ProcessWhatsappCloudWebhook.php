<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\AutomationEngine;
use App\Services\WhatsappCloudService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Processa webhooks da WhatsApp Cloud API oficial da Meta.
 *
 * Estrutura do payload:
 * {
 *   "object": "whatsapp_business_account",
 *   "entry": [{
 *     "id": "<WABA_ID>",
 *     "changes": [{
 *       "field": "messages",
 *       "value": {
 *         "messaging_product": "whatsapp",
 *         "metadata": { "display_phone_number", "phone_number_id" },
 *         "contacts": [{ "profile": { "name" }, "wa_id" }],
 *         "messages": [{ "from", "id", "timestamp", "type", "text|image|audio|document|interactive" }],
 *         "statuses": [{ "id", "status", "timestamp", "recipient_id" }]
 *       }
 *     }]
 *   }]
 * }
 *
 * Cria conversa + mensagem nas mesmas tabelas que o WAHA usa, com
 * `provider='cloud_api'` e `cloud_message_id` pra dedup. O resto do app
 * (chat inbox, AI, chatbot, automações) funciona idêntico.
 */
class ProcessWhatsappCloudWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        private readonly array $payload,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        foreach ($this->payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];
                $this->processChange($value);
            }
        }
    }

    private function processChange(array $value): void
    {
        $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
        if (! $phoneNumberId) {
            Log::channel('whatsapp')->warning('WhatsappCloud: missing phone_number_id in payload');
            return;
        }

        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('provider', 'cloud_api')
            ->where('phone_number_id', $phoneNumberId)
            ->first();

        if (! $instance) {
            Log::channel('whatsapp')->info('WhatsappCloud: no active instance for phone_number_id', [
                'phone_number_id' => $phoneNumberId,
            ]);
            return;
        }

        // Update status do número (qualidade etc.) — opcional
        if ($instance->status !== 'connected') {
            WhatsappInstance::withoutGlobalScope('tenant')
                ->where('id', $instance->id)
                ->update(['status' => 'connected']);
        }

        // Mensagens recebidas (inbound)
        $contacts = $value['contacts'] ?? [];
        $contactsByWaId = [];
        foreach ($contacts as $c) {
            if (isset($c['wa_id'])) {
                $contactsByWaId[$c['wa_id']] = $c['profile']['name'] ?? null;
            }
        }

        foreach ($value['messages'] ?? [] as $msg) {
            $this->processMessage($instance, $msg, $contactsByWaId);
        }

        // Status updates (sent/delivered/read) — atualiza ack das mensagens enviadas
        foreach ($value['statuses'] ?? [] as $status) {
            $this->processStatus($status);
        }
    }

    private function processMessage(WhatsappInstance $instance, array $msg, array $contactsByWaId): void
    {
        $cloudMessageId = $msg['id'] ?? null;
        if (! $cloudMessageId) {
            return;
        }

        // Dedup atômico via cache (evita race entre webhooks duplicados)
        $cacheKey = "wacloud:processing:{$cloudMessageId}";
        if (! Cache::add($cacheKey, 1, 30)) {
            Log::channel('whatsapp')->info('WhatsappCloud: dedup hit', ['msg_id' => $cloudMessageId]);
            return;
        }

        // Dedup permanente via DB
        $exists = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('cloud_message_id', $cloudMessageId)
            ->exists();
        if ($exists) {
            return;
        }

        $from   = (string) ($msg['from'] ?? '');
        $type   = (string) ($msg['type'] ?? 'text');
        $tsRaw  = (int) ($msg['timestamp'] ?? time());
        $sentAt = \Carbon\Carbon::createFromTimestamp($tsRaw, config('app.timezone'));

        if (! $from) {
            return;
        }

        $phone = preg_replace('/\D/', '', $from);
        $contactName = $contactsByWaId[$from] ?? $phone;

        // Body / mídia varia por tipo
        $body          = null;
        $mediaUrl      = null;
        $mediaMime     = null;
        $mediaFilename = null;

        switch ($type) {
            case 'text':
                $body = $msg['text']['body'] ?? '';
                break;

            case 'image':
            case 'audio':
            case 'video':
            case 'document':
            case 'sticker':
                $mediaId   = $msg[$type]['id'] ?? null;
                $mediaMime = $msg[$type]['mime_type'] ?? null;
                $body      = $msg[$type]['caption'] ?? null;
                if ($type === 'document') {
                    $mediaFilename = $msg[$type]['filename'] ?? null;
                }
                // Cloud API entrega media_id — precisamos baixar pra exibir
                if ($mediaId) {
                    $mediaUrl = $this->fetchAndStoreMedia($instance, $mediaId, $mediaMime, $mediaFilename ?? '');
                }
                break;

            case 'interactive':
                // Resposta de lista interativa ou botão
                $interactive = $msg['interactive'] ?? [];
                if (($interactive['type'] ?? '') === 'list_reply') {
                    $body = $interactive['list_reply']['title'] ?? '';
                } elseif (($interactive['type'] ?? '') === 'button_reply') {
                    $body = $interactive['button_reply']['title'] ?? '';
                }
                break;

            case 'location':
                $loc = $msg['location'] ?? [];
                $body = sprintf('📍 %s, %s', $loc['latitude'] ?? '?', $loc['longitude'] ?? '?');
                if (! empty($loc['name'])) {
                    $body .= " ({$loc['name']})";
                }
                break;

            case 'contacts':
                $contacts = $msg['contacts'] ?? [];
                $body = '👤 ' . ($contacts[0]['name']['formatted_name'] ?? 'Contact');
                break;

            default:
                $body = "[$type]";
        }

        // Encontra ou cria a conversa
        $conversation = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $instance->tenant_id)
            ->where('instance_id', $instance->id)
            ->where('phone', $phone)
            ->where('is_group', false)
            ->first();

        $isNewConversation = false;
        if (! $conversation) {
            $isNewConversation = true;
            $conversation = WhatsappConversation::withoutGlobalScope('tenant')->create([
                'tenant_id'       => $instance->tenant_id,
                'instance_id'     => $instance->id,
                'phone'           => $phone,
                'is_group'        => false,
                'contact_name'    => $contactName,
                'status'          => 'open',
                'started_at'      => now(),
                'last_message_at' => $sentAt,
                'unread_count'    => 1,
            ]);

            Log::channel('whatsapp')->info('WhatsappCloud: conversation created', [
                'conversation_id' => $conversation->id,
                'phone'           => $phone,
            ]);
        } else {
            // Atualiza contador de não-lidas e last_message_at
            WhatsappConversation::withoutGlobalScope('tenant')
                ->where('id', $conversation->id)
                ->update([
                    'last_message_at' => $sentAt,
                    'last_inbound_at' => $sentAt,
                    'unread_count'    => $conversation->unread_count + 1,
                    'contact_name'    => $contactName ?: $conversation->contact_name,
                ]);
        }

        // Cria a mensagem
        $message = WhatsappMessage::withoutGlobalScope('tenant')->create([
            'tenant_id'        => $instance->tenant_id,
            'conversation_id'  => $conversation->id,
            'cloud_message_id' => $cloudMessageId,
            'direction'        => 'inbound',
            'sender_name'      => $contactName,
            'type'             => $type,
            'body'             => $body,
            'media_url'        => $mediaUrl,
            'media_mime'       => $mediaMime,
            'media_filename'   => $mediaFilename,
            'ack'              => 'delivered',
            'sent_at'          => $sentAt,
        ]);

        Log::channel('whatsapp')->info('WhatsappCloud: message saved', [
            'message_id'       => $message->id,
            'cloud_message_id' => $cloudMessageId,
            'type'             => $type,
            'conversation_id'  => $conversation->id,
        ]);

        // Dispara automações
        try {
            if ($isNewConversation) {
                (new AutomationEngine())->run('conversation_created', [
                    'tenant_id'    => $instance->tenant_id,
                    'channel'      => 'whatsapp',
                    'conversation' => $conversation->fresh(),
                    'message'      => $message,
                    'lead'         => null,
                ]);
            }
            (new AutomationEngine())->run('message_received', [
                'tenant_id'    => $instance->tenant_id,
                'channel'      => 'whatsapp',
                'conversation' => $conversation->fresh(),
                'message'      => $message,
                'lead'         => null,
            ]);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('WhatsappCloud: automation failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // Broadcast Reverb pra atualizar UI em real-time
        try {
            broadcast(new \App\Events\WhatsappMessageCreated($message, $instance->tenant_id))->toOthers();
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('WhatsappCloud: broadcast failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Atualiza ack (sent/delivered/read) das mensagens outbound.
     */
    private function processStatus(array $status): void
    {
        $cloudMessageId = $status['id'] ?? null;
        $statusName = $status['status'] ?? null;
        if (! $cloudMessageId || ! $statusName) {
            return;
        }

        WhatsappMessage::withoutGlobalScope('tenant')
            ->where('cloud_message_id', $cloudMessageId)
            ->update(['ack' => $statusName]);
    }

    /**
     * Baixa a mídia da Cloud API e salva localmente.
     * Retorna a URL pública (servida pelo nosso storage).
     */
    private function fetchAndStoreMedia(WhatsappInstance $instance, string $mediaId, ?string $mime, string $filename = ''): ?string
    {
        try {
            $service = new WhatsappCloudService($instance);
            $info = $service->getMediaInfo($mediaId);
            if (empty($info['url'])) {
                return null;
            }

            $binary = $service->downloadMediaBinary($info['url']);
            if (! $binary) {
                return null;
            }

            $ext = $this->extensionFromMime($mime ?? $info['mime'] ?? 'application/octet-stream');
            $name = $filename ?: ('cloud_' . $mediaId . '.' . $ext);
            $path = 'whatsapp-media/' . date('Y/m') . '/' . $name;

            \Storage::disk('public')->put($path, $binary);
            return \Storage::disk('public')->url($path);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('WhatsappCloud: fetchAndStoreMedia failed', [
                'media_id' => $mediaId,
                'error'    => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function extensionFromMime(string $mime): string
    {
        return match (true) {
            str_contains($mime, 'jpeg')      => 'jpg',
            str_contains($mime, 'png')       => 'png',
            str_contains($mime, 'gif')       => 'gif',
            str_contains($mime, 'webp')      => 'webp',
            str_contains($mime, 'mp4')       => 'mp4',
            str_contains($mime, 'audio/ogg') => 'ogg',
            str_contains($mime, 'audio/mp3') || str_contains($mime, 'mpeg') => 'mp3',
            str_contains($mime, 'pdf')       => 'pdf',
            default                          => 'bin',
        };
    }
}
