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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ImportWhatsappHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 2;
    public int $backoff = 60;

    private string $cacheKey;

    public function __construct(
        private WhatsappInstance $instance,
        private int $days = 30,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $this->cacheKey = "wa_import:{$this->instance->id}";

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
        $totalChatsEstimate = 0;
        $startedAt        = now()->toISOString();

        Log::channel('whatsapp')->info('Import history started', [
            'instance'  => $this->instance->session_name,
            'tenant_id' => $this->instance->tenant_id,
            'days'      => $this->days,
            'since'     => $since ? date('Y-m-d H:i:s', $since) : 'all',
        ]);

        // Progresso inicial
        $this->updateProgress('running', 0, 0, 0, 0, '', $startedAt);

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
                if (isset($chats['error'])) {
                    Log::channel('whatsapp')->warning('Import: getChats retornou erro', [
                        'offset'   => $chatOffset,
                        'response' => array_slice($chats, 0, 3),
                    ]);
                }
                break;
            }

            // Na primeira página, estimar total de chats
            if ($chatOffset === 0) {
                $totalChatsEstimate = count($chats) >= $chatLimit
                    ? $chatLimit * 3 // Estimar ~150 chats se primeira página cheia
                    : count($chats);
            }

            foreach ($chats as $chat) {
                if (! is_array($chat) || empty($chat['id'])) {
                    continue;
                }

                $chatName = $chat['name'] ?? $this->normalizePhone($chat['id']);

                $result = $this->importChat($waha, $chat, $since);
                $importedChats    += $result['chats'];
                $importedMessages += $result['messages'];
                $skipped          += $result['skipped'];

                $chatsProcessed++;

                // Atualizar total estimado conforme avançamos
                $currentEstimate = max($totalChatsEstimate, $chatsProcessed + (count($chats) >= $chatLimit ? $chatLimit : 0));

                $this->updateProgress(
                    'running',
                    $currentEstimate,
                    $chatsProcessed,
                    $importedMessages,
                    $skipped,
                    $chatName,
                    $startedAt,
                );

                // Chunking: a cada 10 chats, descansar 1s para não sobrecarregar
                if ($chatsProcessed % 10 === 0) {
                    sleep(1);
                }
            }

            $chatOffset += $chatLimit;
        } while (count($chats) >= $chatLimit);

        // Marcar como importado se processou chats (mesmo que todas msgs sejam duplicatas)
        if ($importedMessages > 0 || $skipped > 0) {
            WhatsappInstance::withoutGlobalScope('tenant')
                ->where('id', $this->instance->id)
                ->update(['history_imported' => true]);
        }

        // Progresso final
        $this->updateProgress(
            'completed',
            $chatsProcessed,
            $chatsProcessed,
            $importedMessages,
            $skipped,
            '',
            $startedAt,
            now()->toISOString(),
        );

        Log::channel('whatsapp')->info('Import history completed', [
            'instance'          => $this->instance->session_name,
            'tenant_id'         => $this->instance->tenant_id,
            'imported_chats'    => $importedChats,
            'imported_messages' => $importedMessages,
            'skipped'           => $skipped,
            'chats_processed'   => $chatsProcessed,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $cacheKey = "wa_import:{$this->instance->id}";
        $existing = Cache::get($cacheKey, []);

        Cache::put($cacheKey, array_merge($existing, [
            'status'      => 'failed',
            'error'       => $exception->getMessage(),
            'finished_at' => now()->toISOString(),
        ]), 300);

        Log::channel('whatsapp')->error('Import history FAILED', [
            'instance' => $this->instance->session_name,
            'error'    => $exception->getMessage(),
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
                        Log::channel('whatsapp')->info('Import: LID resolvido', [
                            'lid' => $phone, 'resolved' => $resolved,
                        ]);
                        $phone = ltrim($resolved, '+');
                    }
                }
            } catch (\Throwable) {
                // Manter o telefone original
            }
            usleep(200_000); // Rate limit: 200ms
        }

        // BLOQUEAR: LID não resolvido — não importar este chat
        if (! $isGroup && strlen($phone) > 13 && ctype_digit($phone)) {
            Log::channel('whatsapp')->info('Import: LID ignorado — sem número real', [
                'chatId' => $chatId,
                'phone'  => $phone,
            ]);
            return ['chats' => 0, 'messages' => 0, 'skipped' => 0];
        }

        // Se 1:1 sem nome, tentar resolver via WAHA contacts API
        if (empty($contactName) && ! $isGroup) {
            try {
                $jid  = $phone . '@c.us';
                $info = $waha->getContactInfo($jid);
                $contactName = $info['name'] ?? $info['pushName'] ?? null;
            } catch (\Throwable) {
            }
            usleep(200_000);
        }

        // Se grupo sem nome, tentar buscar via WAHA API
        if ($isGroup && empty($contactName)) {
            try {
                $groupInfo   = $waha->getGroupInfo($chatId);
                $contactName = $groupInfo['subject'] ?? $groupInfo['Name'] ?? $groupInfo['name'] ?? null;
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
            // Buscar foto do contato
            $pictureUrl = null;
            try {
                if ($isGroup) {
                    $pictureUrl = $waha->getGroupPicture($chatId);
                } else {
                    $pictureUrl = $waha->getContactPicture($phone . '@c.us');
                }
            } catch (\Throwable) {
            }
            usleep(200_000);

            $conv = WhatsappConversation::withoutGlobalScope('tenant')->create([
                'tenant_id'           => $this->instance->tenant_id,
                'instance_id'         => $this->instance->id,
                'phone'               => $phone,
                'is_group'            => $isGroup,
                'contact_name'        => $contactName ?: $this->formatPhoneName($phone),
                'contact_picture_url' => $pictureUrl,
                'status'              => 'open',
                'started_at'          => now(),
                'last_message_at'     => now(),
                'unread_count'        => 0,
            ]);
            $newChat = true;
        } else {
            $convUpdates = [];

            // Atualizar nome se estava vazio ou era placeholder numérico
            if (! empty($contactName) && (empty($conv->contact_name) || (strlen($conv->contact_name) > 10 && ctype_digit(str_replace(['(', ')', ' ', '-'], '', $conv->contact_name))))) {
                $convUpdates['contact_name'] = $contactName;
            }

            // Atualizar foto se não tinha
            if (empty($conv->contact_picture_url)) {
                try {
                    $pic = $isGroup
                        ? $waha->getGroupPicture($chatId)
                        : $waha->getContactPicture($phone . '@c.us');
                    if ($pic) {
                        $convUpdates['contact_picture_url'] = $pic;
                    }
                } catch (\Throwable) {
                }
                usleep(200_000);
            }

            if ($convUpdates) {
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conv->id)
                    ->update($convUpdates);
            }
        }

        // ── Buscar mensagens (sem download de mídia, máx 200 por chat) ──────────
        $importedMessages = 0;
        $skipped          = 0;

        try {
            $msgs = $waha->getChatMessages($chatId, 200, 0, false, $since);

            // GOWS pode não suportar filter.timestamp.gte — retry sem filtro se retornou vazio
            if ($since !== null && is_array($msgs) && empty($msgs)) {
                Log::channel('whatsapp')->info('Import: getChatMessages vazio com filtro, retentando sem filtro', [
                    'chatId' => $chatId,
                    'since'  => $since,
                ]);
                usleep(200_000);
                $msgs = $waha->getChatMessages($chatId, 200, 0, false, null);
            }

            Log::channel('whatsapp')->debug('Import: getChatMessages raw', [
                'chatId'    => $chatId,
                'count'     => is_array($msgs) ? count($msgs) : 'not_array',
                'has_error' => isset($msgs['error']),
                'sample'    => is_array($msgs) ? array_map(fn($m) => [
                    'id'        => $m['id'] ?? null,
                    'timestamp' => $m['timestamp'] ?? null,
                    'fromMe'    => $m['fromMe'] ?? null,
                    'hasBody'   => ! empty($m['body']),
                ], array_slice($msgs, 0, 3)) : null,
            ]);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('Import: error fetching messages', [
                'chatId' => $chatId,
                'error'  => $e->getMessage(),
            ]);
            return ['chats' => $newChat ? 1 : 0, 'messages' => 0, 'skipped' => 0];
        }

        usleep(200_000); // Rate limit: 200ms entre requests

        // Verificar se a resposta é válida
        if (! is_array($msgs) || isset($msgs['error'])) {
            Log::channel('whatsapp')->warning('Import: getChatMessages retornou erro', [
                'chatId'   => $chatId,
                'since'    => $since,
                'response' => is_array($msgs) ? array_slice($msgs, 0, 3) : 'not_array',
            ]);
            return ['chats' => $newChat ? 1 : 0, 'messages' => 0, 'skipped' => 0];
        }

        if (empty($msgs)) {
            Log::channel('whatsapp')->debug('Import: chat sem mensagens no período', [
                'chatId' => $chatId,
                'since'  => $since,
            ]);
        }

        // Tentar extrair nome do contato das mensagens inbound (pushName/notifyName)
        if (empty($contactName) && ! $isGroup) {
            foreach ($msgs as $m) {
                if (! is_array($m) || ($m['fromMe'] ?? false)) {
                    continue;
                }
                $contactName = $m['_data']['Info']['PushName']
                    ?? $m['_data']['notifyName']
                    ?? $m['notifyName']
                    ?? null;
                if ($contactName) {
                    // Atualizar na conversa
                    WhatsappConversation::withoutGlobalScope('tenant')
                        ->where('id', $conv->id)
                        ->update(['contact_name' => $contactName]);
                    break;
                }
            }
        }

        foreach ($msgs as $msg) {
            if (! is_array($msg)) {
                continue;
            }

            // ID da mensagem: WAHA pode usar formatos diferentes
            $msgId = $msg['id'] ?? $msg['key']['id'] ?? null;
            if (empty($msgId)) {
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

            $ts = isset($msg['timestamp']) ? (int) $msg['timestamp'] : 0;
            // GOWS pode retornar milissegundos em vez de segundos
            if ($ts > 9999999999) {
                $ts = intdiv($ts, 1000);
            }
            // Validar: timestamp deve ser > 2020-01-01 e < agora+1dia
            $sentAt = ($ts > 1577836800 && $ts < time() + 86400)
                ? Carbon::createFromTimestamp($ts, config('app.timezone', 'America/Sao_Paulo'))
                : now();

            try {
                // Body: GOWS pode usar campo diferente
                $msgBody = $msg['body'] ?? $msg['text'] ?? $msg['caption'] ?? null;

                WhatsappMessage::withoutGlobalScope('tenant')->create([
                    'tenant_id'       => $this->instance->tenant_id,
                    'conversation_id' => $conv->id,
                    'waha_message_id' => $msgId,
                    'direction'       => ($msg['fromMe'] ?? false) ? 'outbound' : 'inbound',
                    'type'            => $type,
                    'body'            => $msgBody,
                    'ack'             => 'delivered',
                    'sent_at'         => $sentAt,
                ]);
                $importedMessages++;
            } catch (QueryException $e) {
                Log::channel('whatsapp')->debug('Import: msg duplicada ou erro', [
                    'waha_id' => $msgId,
                    'error'   => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        // Atualiza last_message_at com a mensagem mais recente
        if ($importedMessages > 0) {
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

        Log::channel('whatsapp')->info('Import: chat processado', [
            'chatId'   => $chatId,
            'phone'    => $phone,
            'name'     => $contactName,
            'messages' => $importedMessages,
            'skipped'  => $skipped,
            'newChat'  => $newChat,
        ]);

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

    private function formatPhoneName(string $phone): string
    {
        $d = ltrim($phone, '+');
        if (str_starts_with($d, '55') && strlen($d) >= 12) {
            $d = substr($d, 2);
        }
        if (strlen($d) === 11) {
            return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7));
        }
        if (strlen($d) === 10) {
            return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6));
        }
        return $phone;
    }

    private function updateProgress(
        string $status,
        int $total,
        int $processed,
        int $messages,
        int $skipped,
        string $current,
        string $startedAt,
        ?string $finishedAt = null,
    ): void {
        $data = [
            'status'    => $status,
            'total'     => $total,
            'processed' => $processed,
            'messages'  => $messages,
            'skipped'   => $skipped,
            'current'   => $current,
            'started_at' => $startedAt,
        ];

        if ($finishedAt) {
            $data['finished_at'] = $finishedAt;
        }

        $ttl = $status === 'completed' || $status === 'failed' ? 300 : 3600;
        Cache::put($this->cacheKey, $data, $ttl);
    }
}
