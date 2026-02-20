<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\WhatsappConversationUpdated;
use App\Events\WhatsappMessageCreated;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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

        $phone = $this->normalizePhone($from, $msg);

        $conversation = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $instance->tenant_id)
            ->where('phone', $phone)
            ->first();

        // Fallback: procurar conversa que tenha o telefone armazenado no formato @lid
        // (dados antigos antes da correção de normalização).
        // Se encontrada, migra o telefone para o formato normalizado.
        if (! $conversation && str_ends_with($from, '@lid')) {
            $lidNumeric = (string) preg_replace('/[:@].+$/', '', $from); // "36576092528787:22@lid" → "36576092528787"
            $conversation = WhatsappConversation::withoutGlobalScope('tenant')
                ->where('tenant_id', $instance->tenant_id)
                ->where(function ($q) use ($from, $lidNumeric) {
                    $q->where('phone', $from)                     // JID exato armazenado
                      ->orWhere('phone', 'LIKE', $lidNumeric . '%'); // prefixo numérico (qualquer variante @lid)
                })
                ->first();

            if ($conversation) {
                // Migra o telefone armazenado para o número real normalizado
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conversation->id)
                    ->update(['phone' => $phone]);
            }
        }

        if (! $conversation) {
            // GOWS engine: nome vem em _data.Info.PushName
            // Fallback para engines antigas: _data.notifyName ou notifyName
            $contactName = $msg['_data']['Info']['PushName']
                ?? $msg['_data']['notifyName']
                ?? $msg['notifyName']
                ?? null;

            $conversation = WhatsappConversation::withoutGlobalScope('tenant')->create([
                'tenant_id'       => $instance->tenant_id,
                'instance_id'     => $instance->id,
                'phone'           => $phone,
                'contact_name'    => $contactName,
                'status'          => 'open',
                'started_at'      => now(),
                'last_message_at' => now(),
                'unread_count'    => 0,
            ]);
        }

        [$type, $mediaUrl, $mediaMime, $mediaFilename] = $this->extractMedia($msg);

        $body = $msg['body'] ?? $msg['caption'] ?? null;

        // Evitar duplicatas pelo waha_message_id
        $wahaId = $msg['id'] ?? null;
        if ($wahaId && WhatsappMessage::withoutGlobalScope('tenant')->where('waha_message_id', $wahaId)->exists()) {
            return;
        }

        $message = WhatsappMessage::withoutGlobalScope('tenant')->create([
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
                ? \Carbon\Carbon::createFromTimestamp((int) $msg['timestamp'], config('app.timezone'))
                : now(),
        ]);

        // Atualizar conversa ANTES do broadcast — garante que last_message_at e
        // unread_count sejam salvos mesmo se o broadcaster estiver indisponível.
        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conversation->id)
            ->update([
                'last_message_at' => now(),
                'unread_count'    => \Illuminate\Support\Facades\DB::raw('unread_count + 1'),
                'instance_id'     => $instance->id,
                'status'          => 'open',
                'closed_at'       => null,
            ]);

        // Broadcast via WebSocket — envolvido em try/catch para que uma falha
        // no broadcaster (Reverb OOM, Pusher indisponível, etc.) não impeça
        // que a mensagem e a conversa sejam salvas no banco.
        try {
            WhatsappMessageCreated::dispatch($message, $instance->tenant_id);
            $conversation->refresh();
            WhatsappConversationUpdated::dispatch($conversation, $instance->tenant_id);
        } catch (\Throwable) {
            // Broadcaster indisponível — o polling de 5s do frontend supre o real-time.
        }
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

        if (! $status || ! isset($map[$status])) {
            return;
        }

        $update = ['status' => $map[$status]];

        // Salva o número conectado quando a sessão ativa (facilita lookup futuro por telefone)
        if ($status === 'WORKING') {
            $meId = $this->payload['me']['id'] ?? '';
            $phone = str_replace(['@c.us', '@s.whatsapp.net', '@lid'], '', $meId);
            if ($phone) {
                $update['phone_number'] = $phone;
            }
        }

        WhatsappInstance::withoutGlobalScope('tenant')
            ->where('id', $instance->id)
            ->update($update);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function normalizePhone(string $from, array $msg = []): string
    {
        // GOWS engine sometimes uses @lid JIDs as the "from" field.
        // Extract the real phone from _data.Info.Chat which always has the @s.whatsapp.net JID.
        if (str_ends_with($from, '@lid')) {
            $chat = $msg['_data']['Info']['Chat'] ?? '';
            if ($chat) {
                // "556192008997@s.whatsapp.net" → "556192008997"
                return (string) preg_replace('/@.+$/', '', $chat);
            }
            // Last resort: strip @lid and device suffix
            // "36576092528787:22@lid" → "36576092528787"
            return (string) preg_replace('/[:@].+$/', '', $from);
        }

        return str_replace(['@c.us', '@s.whatsapp.net'], '', $from);
    }

    private function extractMedia(array $msg): array
    {
        $hasMedia = ! empty($msg['hasMedia']);
        $media    = $msg['media'] ?? [];

        if (! $hasMedia || empty($media)) {
            return ['text', null, null, null];
        }

        $mime     = $media['mimetype'] ?? $media['mime'] ?? '';
        $filename = $media['filename'] ?? null;

        $type = match (true) {
            str_starts_with($mime, 'image/')       => 'image',
            str_starts_with($mime, 'audio/')       => 'audio',
            str_starts_with($mime, 'video/')       => 'video',
            str_starts_with($mime, 'application/') => 'document',
            default                                => 'document',
        };

        // 1. GOWS engine embute mídia como base64 no payload do webhook (media.data)
        $b64 = $media['data'] ?? null;
        if ($b64) {
            // Remove prefixo data URI se presente: "data:image/jpeg;base64,..." → base64 puro
            $b64  = (string) preg_replace('/^data:[^;]+;base64,/', '', $b64);
            $ext  = $this->mimeToExt($mime);
            $sub  = match ($type) { 'audio' => 'audio', 'video' => 'video', 'image' => 'image', default => 'docs' };
            $path = "whatsapp/{$sub}/" . uniqid('media_', true) . ".{$ext}";

            Storage::disk('public')->put($path, base64_decode($b64));
            $url = Storage::disk('public')->url($path);

            return [$type, $url, $mime, $filename ?? basename($path)];
        }

        // 2. URL externa (WAHA) — tenta fazer download com autenticação e armazenar localmente.
        // Sem isso, o browser não consegue exibir a mídia (requer X-Api-Key).
        $wahaUrl = $media['url'] ?? null;
        if ($wahaUrl) {
            $localUrl = $this->downloadWahaMedia($wahaUrl, $type, $mime);
            return [$type, $localUrl ?? $wahaUrl, $mime, $filename];
        }

        return [$type, null, $mime, $filename];
    }

    private function mimeToExt(string $mime): string
    {
        return match ($mime) {
            'image/jpeg'                => 'jpg',
            'image/png'                 => 'png',
            'image/gif'                 => 'gif',
            'image/webp'                => 'webp',
            'audio/ogg'                 => 'ogg',
            'audio/mpeg'                => 'mp3',
            'audio/webm'                => 'webm',
            'audio/mp4'                 => 'm4a',
            'video/mp4'                 => 'mp4',
            'application/pdf'           => 'pdf',
            'application/octet-stream'  => 'bin',
            default                     => explode('/', $mime)[1] ?? 'bin',
        };
    }

    private function downloadWahaMedia(string $url, string $type, string $mime): ?string
    {
        try {
            $apiKey   = config('services.waha.api_key');
            $response = Http::withHeader('X-Api-Key', $apiKey)
                ->timeout(15)
                ->get($url);

            if ($response->failed()) {
                return null;
            }

            $ext  = $this->mimeToExt($mime);
            $sub  = match ($type) { 'audio' => 'audio', 'video' => 'video', 'image' => 'image', default => 'docs' };
            $path = "whatsapp/{$sub}/" . uniqid('media_', true) . ".{$ext}";

            Storage::disk('public')->put($path, $response->body());

            return Storage::disk('public')->url($path);
        } catch (\Throwable) {
            return null;
        }
    }
}
