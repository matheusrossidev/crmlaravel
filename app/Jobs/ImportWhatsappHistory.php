<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\WahaService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportWhatsappHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 2;
    public int $backoff = 60;

    public function __construct(
        private WhatsappInstance $instance,
        private int $days = 30,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $since = $this->days > 0
            ? (int) now()->subDays($this->days)->timestamp
            : null;

        $waha             = new WahaService($this->instance->session_name);
        $importedChats    = 0;
        $importedMessages = 0;
        $skipped          = 0;
        $chatLimit        = 50;
        $chatOffset       = 0;
        $chatsProcessed   = 0;

        Log::channel('whatsapp')->info('Import history started', [
            'instance'  => $this->instance->session_name,
            'tenant_id' => $this->instance->tenant_id,
            'days'      => $this->days,
        ]);

        do {
            try {
                $chats = $waha->getChats($chatLimit, $chatOffset);
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->error('Import: error fetching chats', [
                    'offset' => $chatOffset,
                    'error'  => $e->getMessage(),
                ]);
                break;
            }

            if (isset($chats['error']) || ! is_array($chats) || empty($chats)) {
                break;
            }

            foreach ($chats as $chat) {
                if (! is_array($chat) || empty($chat['id'])) {
                    continue;
                }

                $result = $this->importChat($waha, $chat, $since);
                $importedChats    += $result['chats'];
                $importedMessages += $result['messages'];
                $skipped          += $result['skipped'];

                $chatsProcessed++;

                // Chunking: a cada 10 chats, descansar 1s para não sobrecarregar
                if ($chatsProcessed % 10 === 0) {
                    sleep(1);
                }
            }

            $chatOffset += $chatLimit;
        } while (count($chats) >= $chatLimit);

        // Marcar que já importou histórico
        WhatsappInstance::withoutGlobalScope('tenant')
            ->where('id', $this->instance->id)
            ->update(['history_imported' => true]);

        Log::channel('whatsapp')->info('Import history completed', [
            'instance'          => $this->instance->session_name,
            'tenant_id'         => $this->instance->tenant_id,
            'imported_chats'    => $importedChats,
            'imported_messages' => $importedMessages,
            'skipped'           => $skipped,
        ]);
    }

    private function importChat(WahaService $waha, array $chat, ?int $since): array
    {
        $chatId      = $chat['id'];
        $isGroup     = (bool) ($chat['isGroup'] ?? false);
        $contactName = $chat['name'] ?? null;
        $phone       = $this->normalizePhone($chatId);

        if ($phone === '') {
            return ['chats' => 0, 'messages' => 0, 'skipped' => 0];
        }

        // Resolver LID para telefone real (13+ dígitos = provável LID)
        if (! $isGroup && strlen($phone) > 13 && ctype_digit($phone)) {
            try {
                $jid  = $phone . '@c.us';
                $info = $waha->getContactInfo($jid);
                $realId = $info['id'] ?? null;
                if ($realId && ! str_contains($realId, '@lid')) {
                    $resolved = preg_replace('/[:@].+$/', '', $realId);
                    if ($resolved && $resolved !== $phone) {
                        $phone = ltrim($resolved, '+');
                    }
                }
            } catch (\Throwable) {
                // Manter o telefone original
            }
            usleep(200_000); // Rate limit: 200ms
        }

        // Se grupo sem nome, tentar buscar via WAHA API
        if ($isGroup && empty($contactName)) {
            try {
                $groupInfo   = $waha->getGroupInfo($chatId);
                $contactName = $groupInfo['subject'] ?? $groupInfo['name'] ?? null;
            } catch (\Throwable) {
            }
        }

        // Busca ou cria conversa
        $conv = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->instance->tenant_id)
            ->where('phone', $phone)
            ->first();

        $newChat = false;
        if (! $conv) {
            // Buscar foto do contato (só para 1:1, não grupos)
            $pictureUrl = null;
            if (! $isGroup) {
                try {
                    $jid = $phone . '@c.us';
                    $pictureUrl = $waha->getContactPicture($jid);
                } catch (\Throwable) {
                }
                usleep(200_000);
            }

            $conv = WhatsappConversation::withoutGlobalScope('tenant')->create([
                'tenant_id'           => $this->instance->tenant_id,
                'instance_id'         => $this->instance->id,
                'phone'               => $phone,
                'is_group'            => $isGroup,
                'contact_name'        => $contactName,
                'contact_picture_url' => $pictureUrl,
                'status'              => 'open',
                'started_at'          => now(),
                'last_message_at'     => now(),
                'unread_count'        => 0,
            ]);
            $newChat = true;
        } elseif (! empty($contactName) && empty($conv->contact_name)) {
            WhatsappConversation::withoutGlobalScope('tenant')
                ->where('id', $conv->id)
                ->update(['contact_name' => $contactName]);
        }

        // Buscar mensagens (sem download de mídia, máx 200 por chat)
        $importedMessages = 0;
        $skipped          = 0;

        try {
            $msgs = $waha->getChatMessages($chatId, 200, 0, false, $since);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('Import: error fetching messages', [
                'chatId' => $chatId,
                'error'  => $e->getMessage(),
            ]);
            return ['chats' => $newChat ? 1 : 0, 'messages' => 0, 'skipped' => 0];
        }

        usleep(200_000); // Rate limit: 200ms entre requests

        if (is_array($msgs) && ! isset($msgs['error'])) {
            foreach ($msgs as $msg) {
                if (! is_array($msg) || empty($msg['id'])) {
                    continue;
                }

                $rawType = $msg['type'] ?? 'chat';
                $type    = match ($rawType) {
                    'image'               => 'image',
                    'audio', 'ptt'        => 'audio',
                    'video'               => 'video',
                    'document', 'sticker' => 'document',
                    default               => 'text',
                };

                $ts     = isset($msg['timestamp']) ? (int) $msg['timestamp'] : null;
                $sentAt = $ts
                    ? Carbon::createFromTimestamp($ts, config('app.timezone', 'America/Sao_Paulo'))
                    : now();

                try {
                    WhatsappMessage::withoutGlobalScope('tenant')->create([
                        'tenant_id'       => $this->instance->tenant_id,
                        'conversation_id' => $conv->id,
                        'waha_message_id' => $msg['id'],
                        'direction'       => ($msg['fromMe'] ?? false) ? 'outbound' : 'inbound',
                        'type'            => $type,
                        'body'            => $msg['body'] ?? null,
                        'ack'             => 'delivered',
                        'sent_at'         => $sentAt,
                    ]);
                    $importedMessages++;
                } catch (QueryException) {
                    $skipped++;
                }
            }

            // Atualiza last_message_at com a mensagem mais recente
            $latestSentAt = WhatsappMessage::withoutGlobalScope('tenant')
                ->where('conversation_id', $conv->id)
                ->orderByDesc('sent_at')
                ->value('sent_at');

            if ($latestSentAt) {
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conv->id)
                    ->update(['last_message_at' => $latestSentAt]);
            }
        }

        return [
            'chats'    => $newChat ? 1 : 0,
            'messages' => $importedMessages,
            'skipped'  => $skipped,
        ];
    }

    private function normalizePhone(string $jid): string
    {
        $phone = preg_replace('/[:@].+$/', '', $jid);
        return ltrim((string) $phone, '+');
    }
}
