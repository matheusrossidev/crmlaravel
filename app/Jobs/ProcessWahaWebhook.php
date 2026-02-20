<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWahaWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly array $payload) {}

    public function handle(): void
    {
        $event   = $this->payload['event'] ?? '';
        $session = $this->payload['session'] ?? '';

        $instance = WhatsappInstance::where('session_name', $session)->first();
        if (! $instance) {
            return;
        }

        match (true) {
            in_array($event, ['message', 'message.any']) => $this->handleInbound($instance),
            $event === 'message.reaction'                => $this->handleReaction($instance),
            $event === 'message.ack'                     => $this->handleAck(),
            $event === 'message.revoked'                 => $this->handleRevoked(),
            $event === 'session.status'                  => $this->handleSessionStatus($instance),
            default                                      => null,
        };
    }

    // ── Handlers ──────────────────────────────────────────────────────────────

    private function handleInbound(WhatsappInstance $instance): void
    {
        $msg = $this->payload['payload'] ?? [];

        // Ignorar mensagens enviadas por nós (fromMe)
        if (! empty($msg['fromMe'])) {
            return;
        }

        // Ignorar tipos que não são mensagens reais (grupos por enquanto)
        $from = $msg['from'] ?? '';
        if (str_contains($from, '@g.us')) {
            return;
        }

        $phone = $this->normalizePhone($from);

        $conversation = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $instance->tenant_id)
            ->where('phone', $phone)
            ->first();

        if (! $conversation) {
            $conversation = WhatsappConversation::withoutGlobalScope('tenant')->create([
                'tenant_id'    => $instance->tenant_id,
                'instance_id'  => $instance->id,
                'phone'        => $phone,
                'contact_name' => $msg['_data']['notifyName'] ?? $msg['notifyName'] ?? null,
                'status'       => 'open',
                'started_at'   => now(),
                'last_message_at' => now(),
                'unread_count' => 0,
            ]);
        }

        [$type, $mediaUrl, $mediaMime, $mediaFilename] = $this->extractMedia($msg);

        $body = $msg['body'] ?? $msg['caption'] ?? null;

        // Evitar duplicatas pelo waha_message_id
        $wahaId = $msg['id'] ?? null;
        if ($wahaId && WhatsappMessage::withoutGlobalScope('tenant')->where('waha_message_id', $wahaId)->exists()) {
            return;
        }

        WhatsappMessage::withoutGlobalScope('tenant')->create([
            'tenant_id'       => $instance->tenant_id,
            'conversation_id' => $conversation->id,
            'waha_message_id' => $wahaId,
            'direction'       => 'inbound',
            'type'            => $type,
            'body'            => $body,
            'media_url'       => $mediaUrl,
            'media_mime'      => $mediaMime,
            'media_filename'  => $mediaFilename,
            'ack'             => 'delivered',
            'sent_at'         => isset($msg['timestamp'])
                ? \Carbon\Carbon::createFromTimestamp($msg['timestamp'])
                : now(),
        ]);

        // Atualizar conversa
        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conversation->id)
            ->update([
                'last_message_at' => now(),
                'unread_count'    => \DB::raw('unread_count + 1'),
                'instance_id'     => $instance->id,
                'status'          => 'open',
                'closed_at'       => null,
            ]);
    }

    private function handleReaction(WhatsappInstance $instance): void
    {
        $payload = $this->payload['payload'] ?? [];
        $reacted = $payload['reaction']['key']['id'] ?? null;
        $emoji   = $payload['reaction']['text'] ?? '';

        if (! $reacted) {
            return;
        }

        $original = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('waha_message_id', $reacted)
            ->first();

        if (! $original) {
            return;
        }

        WhatsappMessage::withoutGlobalScope('tenant')->create([
            'tenant_id'       => $instance->tenant_id,
            'conversation_id' => $original->conversation_id,
            'waha_message_id' => $payload['id'] ?? null,
            'direction'       => 'inbound',
            'type'            => 'reaction',
            'reaction_data'   => [
                'emoji'               => $emoji,
                'reactedToMessageId'  => $reacted,
            ],
            'ack'     => 'delivered',
            'sent_at' => now(),
        ]);
    }

    private function handleAck(): void
    {
        $payload = $this->payload['payload'] ?? [];
        $wahaId  = $payload['id'] ?? null;
        $ack     = $payload['ack'] ?? null;

        if (! $wahaId || $ack === null) {
            return;
        }

        $ackMap = [1 => 'sent', 2 => 'delivered', 3 => 'read', 4 => 'read'];
        $status = $ackMap[(int) $ack] ?? null;

        if ($status) {
            WhatsappMessage::withoutGlobalScope('tenant')
                ->where('waha_message_id', $wahaId)
                ->update(['ack' => $status]);
        }
    }

    private function handleRevoked(): void
    {
        $payload = $this->payload['payload'] ?? [];
        $wahaId  = $payload['id'] ?? null;

        if ($wahaId) {
            WhatsappMessage::withoutGlobalScope('tenant')
                ->where('waha_message_id', $wahaId)
                ->update(['is_deleted' => true]);
        }
    }

    private function handleSessionStatus(WhatsappInstance $instance): void
    {
        $status = $this->payload['payload']['status'] ?? null;

        $map = [
            'WORKING'      => 'connected',
            'SCAN_QR_CODE' => 'qr',
            'STOPPED'      => 'disconnected',
            'FAILED'       => 'disconnected',
        ];

        if ($status && isset($map[$status])) {
            WhatsappInstance::withoutGlobalScope('tenant')
                ->where('id', $instance->id)
                ->update(['status' => $map[$status]]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function normalizePhone(string $from): string
    {
        return str_replace(['@c.us', '@s.whatsapp.net'], '', $from);
    }

    private function extractMedia(array $msg): array
    {
        $hasMedia = ! empty($msg['hasMedia']);
        $media    = $msg['media'] ?? [];

        if (! $hasMedia || empty($media)) {
            return ['text', null, null, null];
        }

        $mime     = $media['mimetype'] ?? '';
        $url      = $media['url'] ?? null;
        $filename = $media['filename'] ?? null;

        $type = match (true) {
            str_starts_with($mime, 'image/')       => 'image',
            str_starts_with($mime, 'audio/')       => 'audio',
            str_starts_with($mime, 'video/')       => 'video',
            str_starts_with($mime, 'application/') => 'document',
            default                                => 'document',
        };

        return [$type, $url, $mime, $filename];
    }
}
