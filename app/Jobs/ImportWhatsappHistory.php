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
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ImportWhatsappHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 15 min. Alinhado com o --timeout do worker em docker-compose.yml.
     * Se infra tiver --timeout menor, worker mata o job antes → SIGALRM → retry.
     */
    public int $timeout = 900;

    /**
     * Sem retry: import é caro (500+ requests WAHA) e idempotente.
     * Se falhou a primeira vez, re-disparar do zero vai falhar igual —
     * user pode retentar manualmente pela UI.
     */
    public int $tries = 1;
    public int $maxExceptions = 1;

    /**
     * Marca failed imediato quando estoura timeout (evita retry zumbi).
     */
    public bool $failOnTimeout = true;

    private string $cacheKey;

    public function __construct(
        private WhatsappInstance $instance,
        private int $days = 30,
    ) {
        $this->onQueue('default');
    }

    /**
     * Lock por instance_id pra evitar 2 workers importando a mesma WABA em paralelo
     * (que dobraria o tráfego WAHA e poderia causar o próprio timeout).
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("wa-import:{$this->instance->id}"))
                ->expireAfter(900)
                ->dontRelease(),
        ];
    }

    public function tags(): array
    {
        return [
            'wa-import',
            'instance:'.$this->instance->id,
            'tenant:'.$this->instance->tenant_id,
        ];
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

        // Carregar TODOS os mapeamentos LID→phone em memória (1 request)
        $lidMap = [];
        try {
            $allLids = $waha->getAllLids();
            if (is_array($allLids) && ! isset($allLids['error'])) {
                foreach ($allLids as $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }
                    $lid   = $entry['lid'] ?? $entry['id'] ?? null;
                    $phone = $entry['phoneNumber'] ?? $entry['phone'] ?? $entry['chatId'] ?? null;
                    if ($lid && $phone) {
                        $numericLid   = (string) preg_replace('/[:@].+$/', '', $lid);
                        $numericPhone = ltrim((string) preg_replace('/[:@].+$/', '', $phone), '+');
                        if ($numericLid && $numericPhone) {
                            $lidMap[$numericLid] = $numericPhone;
                        }
                    }
                }
            }
            Log::channel('whatsapp')->info('Import: LID map carregado', ['count' => count($lidMap)]);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('Import: falha ao carregar LID map', ['error' => $e->getMessage()]);
        }

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

                $result = $this->importChat($waha, $chat, $since, $lidMap);
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

        // Mensagem amigável pro banner na UI — o user não precisa ver stack trace.
        $userMessage = $exception instanceof MaxAttemptsExceededException
            ? 'O import demorou demais e foi interrompido. Tente de novo em horário de menor tráfego ou reduza o período (ex: últimos 7 dias).'
            : $exception->getMessage();

        Cache::put($cacheKey, array_merge($existing, [
            'status'      => 'failed',
            'error'       => $userMessage,
            'finished_at' => now()->toISOString(),
        ]), 600);

        Log::channel('whatsapp')->error('Import history FAILED', [
            'instance'  => $this->instance->session_name,
            'tenant_id' => $this->instance->tenant_id,
            'class'     => $exception::class,
            'error'     => $exception->getMessage(),
        ]);
    }

    private function importChat(WahaService $waha, array $chat, ?int $since, array $lidMap = []): array
    {
        $chatId      = $chat['id'];
        $isGroup     = (bool) ($chat['isGroup'] ?? false);
        $contactName = $chat['name'] ?? null;
        $phone       = $this->normalizePhone($chatId);
        $chatIsLid   = str_ends_with($chatId, '@lid'); // Sinal definitivo de LID
        $originalLid = null; // Guardar LID para salvar na coluna

        if ($phone === '') {
            return ['chats' => 0, 'messages' => 0, 'skipped' => 0];
        }

        // Resolver LID para telefone real.
        // Usa $chatIsLid (sufixo @lid do chatId) para capturar LIDs de 13 dígitos.
        if (! $isGroup && ctype_digit($phone) && ($chatIsLid || strlen($phone) > 13)) {
            $originalLid = $phone;

            // 1) Lookup no mapa batch (instantâneo)
            if (isset($lidMap[$phone])) {
                Log::channel('whatsapp')->info('Import: LID resolvido via batch map', [
                    'lid' => $phone, 'resolved' => $lidMap[$phone],
                ]);
                $phone = $lidMap[$phone];
            } else {
                // 2) Endpoint dedicado /lids/{lid}
                try {
                    $lidResult = $waha->getPhoneByLid($phone . '@lid');
                    $resolvedPhone = $lidResult['phoneNumber'] ?? $lidResult['phone'] ?? $lidResult['chatId'] ?? null;
                    if ($resolvedPhone) {
                        $resolved = ltrim((string) preg_replace('/[:@].+$/', '', $resolvedPhone), '+');
                        if ($resolved && ctype_digit($resolved) && strlen($resolved) <= 15) {
                            Log::channel('whatsapp')->info('Import: LID resolvido via /lids endpoint', [
                                'lid' => $phone, 'resolved' => $resolved,
                            ]);
                            $phone = $resolved;
                        }
                    }
                } catch (\Throwable) {
                }
                usleep(50_000);

                // 3) Fallback: contacts API (método antigo)
                if (ctype_digit($phone) && ($chatIsLid || strlen($phone) > 13)) {
                    try {
                        $info   = $waha->getContactInfo($phone . '@c.us');
                        $realId = $info['id'] ?? null;
                        if ($realId && ! str_contains($realId, '@lid')) {
                            $resolved = ltrim((string) preg_replace('/[:@].+$/', '', $realId), '+');
                            if ($resolved && $resolved !== $phone) {
                                $phone = $resolved;
                            }
                        }
                    } catch (\Throwable) {
                    }
                    usleep(50_000);
                }
            }
        }

        // BLOQUEAR: LID não resolvido — não importar este chat
        if (! $isGroup && ctype_digit($phone) && ($chatIsLid || strlen($phone) > 13)) {
            Log::channel('whatsapp')->info('Import: LID ignorado — sem número real', [
                'chatId' => $chatId,
                'phone'  => $phone,
            ]);
            return ['chats' => 0, 'messages' => 0, 'skipped' => 0];
        }

        // Se 1:1 sem nome, tentar resolver via WAHA contacts API.
        // Checar as 3 variantes que o proprio WAHA usa na integracao com Chatwoot:
        // name || pushName || pushname (camelCase + lowercase).
        // Ref: github.com/devlikeapro/waha src/apps/chatwoot/contacts/WhatsAppContactInfo.ts
        if (empty($contactName) && ! $isGroup) {
            try {
                $jid  = $phone . '@c.us';
                $info = $waha->getContactInfo($jid);
                $contactName = $info['name']
                    ?? $info['pushName']
                    ?? $info['pushname']
                    ?? null;
            } catch (\Throwable) {
            }
            usleep(50_000);
        }

        // Se grupo sem nome, tentar buscar via WAHA API
        if ($isGroup && empty($contactName)) {
            try {
                $groupInfo   = $waha->getGroupInfo($chatId);
                $contactName = $groupInfo['subject'] ?? $groupInfo['Name'] ?? $groupInfo['name'] ?? null;
            } catch (\Throwable) {
            }
        }

        // Busca conversa existente (antes de buscar msgs/criar — precisamos do conv->id
        // pro loop de save se existir, mas mensagens precisam ser fetched antes de criar
        // pra extrair pushName das msgs como ultimo fallback de nome).
        $conv = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->instance->tenant_id)
            ->where('phone', $phone)
            ->first();

        // ── Buscar mensagens ANTES de criar conversa ──────────────────────────────
        // Permite extrair pushName das mensagens como ultimo fallback de nome,
        // evitando criar a conversa com placeholder (phone formatado) e depois
        // fazer UPDATE — conversa nasce correta.
        $importedMessages = 0;
        $skipped          = 0;
        $newChat          = false;

        try {
            $msgs = $waha->getChatMessages($chatId, 200, 0, false, $since);

            // GOWS pode não suportar filter.timestamp.gte — retry sem filtro se retornou vazio
            if ($since !== null && is_array($msgs) && empty($msgs)) {
                Log::channel('whatsapp')->info('Import: getChatMessages vazio com filtro, retentando sem filtro', [
                    'chatId' => $chatId,
                    'since'  => $since,
                ]);
                usleep(50_000);
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
            $msgs = [];
        }

        usleep(50_000); // Rate limit: 50ms entre requests WAHA (Plus aguenta bem mais)

        // Verificar se a resposta é válida
        if (! is_array($msgs) || isset($msgs['error'])) {
            Log::channel('whatsapp')->warning('Import: getChatMessages retornou erro', [
                'chatId'   => $chatId,
                'since'    => $since,
                'response' => is_array($msgs) ? array_slice($msgs, 0, 3) : 'not_array',
            ]);
            $msgs = [];
        }

        // Ultimo fallback de nome: PushName/notifyName das mensagens inbound.
        // Engine-specific (GOWS guarda em _data.Info.PushName). So aplica quando
        // getContactInfo nao retornou nada — antes de criar a conversa.
        if (empty($contactName) && ! $isGroup && ! empty($msgs)) {
            foreach ($msgs as $m) {
                if (! is_array($m) || ($m['fromMe'] ?? false)) {
                    continue;
                }
                $contactName = $m['_data']['Info']['PushName']
                    ?? $m['_data']['notifyName']
                    ?? $m['notifyName']
                    ?? null;
                if ($contactName) {
                    break;
                }
            }
        }

        // Agora sim: cria ou atualiza a conversa, com contact_name ja resolvido.
        if (! $conv) {
            // Buscar foto via endpoint correto: /api/{session}/chats/{chatId}/picture
            $pictureUrl = $waha->getChatPicture($chatId);
            usleep(50_000);

            $conv = WhatsappConversation::withoutGlobalScope('tenant')->create([
                'tenant_id'           => $this->instance->tenant_id,
                'instance_id'         => $this->instance->id,
                'phone'               => $phone,
                'lid'                 => $originalLid,
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

            // Atualizar foto se não tinha (endpoint correto: /chats/{chatId}/picture)
            if (empty($conv->contact_picture_url)) {
                $pic = $waha->getChatPicture($chatId);
                if ($pic) {
                    $convUpdates['contact_picture_url'] = $pic;
                }
                usleep(50_000);
            }

            // Salvar LID se não tinha
            if ($originalLid && empty($conv->lid)) {
                $convUpdates['lid'] = $originalLid;
            }

            if ($convUpdates) {
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conv->id)
                    ->update($convUpdates);
            }
        }

        if (empty($msgs)) {
            Log::channel('whatsapp')->debug('Import: chat sem mensagens no período', [
                'chatId' => $chatId,
                'since'  => $since,
            ]);
            return ['chats' => $newChat ? 1 : 0, 'messages' => 0, 'skipped' => 0];
        }

        // Ordenar mensagens cronologicamente (oldest → newest) antes de salvar.
        // WAHA GOWS não garante ordem — pode retornar newest-first.
        usort($msgs, function ($a, $b) {
            $tsA = (int) ($a['timestamp'] ?? 0);
            $tsB = (int) ($b['timestamp'] ?? 0);
            if ($tsA > 9999999999) $tsA = intdiv($tsA, 1000);
            if ($tsB > 9999999999) $tsB = intdiv($tsB, 1000);
            return $tsA <=> $tsB;
        });

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
            // Validar: timestamp deve ser > 2020-01-01 e < agora+1dia.
            // Se invalido, PULA a mensagem — usar now() como fallback embaralhava
            // a ordem cronologica (mensagens antigas apareciam junto das recentes).
            if ($ts <= 1577836800 || $ts >= time() + 86400) {
                Log::channel('whatsapp')->info('Import: msg com timestamp invalido, pulando', [
                    'conversation_id' => $conv->id,
                    'waha_message_id' => $msgId,
                    'raw_ts'          => $msg['timestamp'] ?? null,
                ]);
                $skipped++;
                continue;
            }
            $sentAt = Carbon::createFromTimestamp($ts, config('app.timezone', 'America/Sao_Paulo'));

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
