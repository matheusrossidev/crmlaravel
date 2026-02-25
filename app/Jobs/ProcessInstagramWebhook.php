<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\InstagramConversationUpdated;
use App\Events\InstagramMessageCreated;
use App\Models\AiAgent;
use App\Models\InstagramAutomation;
use App\Models\InstagramConversation;
use App\Models\InstagramInstance;
use App\Models\InstagramMessage;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Services\InstagramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessInstagramWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly array $payload) {}

    public function handle(): void
    {
        foreach ($this->payload['entry'] ?? [] as $entry) {
            $igAccountId = $entry['id'] ?? null;
            if (! $igAccountId) {
                continue;
            }

            $instance = InstagramInstance::withoutGlobalScope('tenant')
                ->where(function ($q) use ($igAccountId) {
                    $q->where('instagram_account_id', $igAccountId)
                      ->orWhere('ig_business_account_id', $igAccountId);
                })
                ->first();

            if (! $instance) {
                // Auto-descoberta: IGA token retorna ID diferente do usado no webhook (entry.id).
                // Na primeira entrega, procurar instância conectada sem ig_business_account_id
                // e atualizar automaticamente.
                $instance = InstagramInstance::withoutGlobalScope('tenant')
                    ->where('status', 'connected')
                    ->whereNull('ig_business_account_id')
                    ->orderByDesc('updated_at')
                    ->first();

                if ($instance) {
                    $instance->update(['ig_business_account_id' => $igAccountId]);
                    Log::channel('instagram')->info('ig_business_account_id auto-descoberto e salvo', [
                        'instance_id'            => $instance->id,
                        'ig_business_account_id' => $igAccountId,
                    ]);
                } else {
                    // entry.id pode ser o FB Page ID enviado pelo Meta como webhook duplicado —
                    // o evento real chega pelo outro entry com o IG Account ID correto.
                    Log::channel('instagram')->debug('entry.id não corresponde a nenhuma instância (possível duplicata Meta)', [
                        'ig_account_id' => $igAccountId,
                    ]);
                    continue;
                }
            }

            foreach ($entry['messaging'] ?? [] as $messaging) {
                $this->processMessaging($instance, $messaging);
            }

            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') === 'comments') {
                    $this->processComment($instance, $change['value'] ?? []);
                }
            }
        }
    }

    // ── Handlers ──────────────────────────────────────────────────────────────

    private function processMessaging(InstagramInstance $instance, array $messaging): void
    {
        $senderId    = $messaging['sender']['id'] ?? null;
        $recipientId = $messaging['recipient']['id'] ?? null;
        $messageData = $messaging['message'] ?? null;
        $timestamp   = $messaging['timestamp'] ?? null;

        if (! $senderId || ! $messageData) {
            return;
        }

        $msgId    = $messageData['mid'] ?? null;
        $isFromMe = ($senderId === $instance->instagram_account_id)
            || ($instance->ig_business_account_id && $senderId === $instance->ig_business_account_id);

        // Dedup via Cache (atomic): Meta pode entregar o mesmo evento mais de uma vez
        if ($msgId && ! Cache::add("ig:processing:{$msgId}", 1, 10)) {
            Log::channel('instagram')->debug('Evento duplicado ignorado', ['mid' => $msgId]);
            return;
        }

        // Se fromMe: verificar se já existe no banco (enviado pelo CRM)
        if ($isFromMe) {
            if ($msgId && InstagramMessage::withoutGlobalScope('tenant')
                ->where('ig_message_id', $msgId)->exists()
            ) {
                Log::channel('instagram')->debug('fromMe já salvo — ignorando', ['mid' => $msgId]);
                return;
            }
        }

        // O IGSID do contato é o senderId em mensagens inbound, recipientId em fromMe
        $igsid = $isFromMe ? $recipientId : $senderId;

        if (! $igsid) {
            return;
        }

        Log::channel('instagram')->info('Processando mensagem', [
            'mid'       => $msgId,
            'igsid'     => $igsid,
            'is_from_me'=> $isFromMe,
        ]);

        $conversation = $this->findOrCreateConversation($instance, $igsid);

        if (! $conversation) {
            return;
        }

        [$type, $mediaUrl] = $this->extractMedia($messageData);
        $body = $messageData['text'] ?? null;

        // Salvar mensagem (UNIQUE em ig_message_id previne duplicatas)
        $message = null;
        try {
            $message = InstagramMessage::withoutGlobalScope('tenant')->create([
                'tenant_id'       => $instance->tenant_id,
                'conversation_id' => $conversation->id,
                'ig_message_id'   => $msgId,
                'direction'       => $isFromMe ? 'outbound' : 'inbound',
                'type'            => $type,
                'body'            => $body,
                'media_url'       => $mediaUrl,
                'ack'             => 'delivered',
                'sent_at'         => $timestamp
                    ? \Carbon\Carbon::createFromTimestampMs((int) $timestamp)
                    : now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Apenas UNIQUE violations (1062) devem ser silenciadas
            if (($e->errorInfo[1] ?? 0) !== 1062) {
                throw $e;
            }
            Log::channel('instagram')->debug('Mensagem duplicada ignorada (UNIQUE)', ['mid' => $msgId]);
            return;
        }

        // Atualizar conversa
        $convUpdate = [
            'last_message_at' => now(),
            'status'          => 'open',
            'closed_at'       => null,
        ];
        if (! $isFromMe) {
            $convUpdate['unread_count'] = \Illuminate\Support\Facades\DB::raw('unread_count + 1');
        }
        InstagramConversation::withoutGlobalScope('tenant')
            ->where('id', $conversation->id)
            ->update($convUpdate);

        Log::channel('instagram')->info('Mensagem salva', [
            'conversation_id' => $conversation->id,
            'mid'             => $msgId,
            'type'            => $type,
        ]);

        // Broadcast via WebSocket — envolvido em try/catch para que uma falha
        // no broadcaster não impeça que a mensagem seja salva no banco.
        try {
            InstagramMessageCreated::dispatch($message, $instance->tenant_id);
            $conversation->refresh();
            InstagramConversationUpdated::dispatch($conversation, $instance->tenant_id);
            Log::channel('instagram')->info('Broadcast enviado', ['tenant_id' => $instance->tenant_id]);
        } catch (\Throwable $e) {
            Log::channel('instagram')->error('Broadcast FALHOU', ['error' => $e->getMessage()]);
        }
    }

    private function fetchContactProfile(InstagramInstance $instance, string $igsid): array
    {
        try {
            $token   = decrypt($instance->access_token);
            $service = new InstagramService($token);
            $profile = $service->getProfile($igsid);

            // getProfile() retorna ['error'=>true,...] em vez de lançar exceção
            if (! empty($profile['error'])) {
                Log::channel('instagram')->warning('Falha ao buscar perfil do contato', [
                    'igsid' => $igsid,
                    'body'  => $profile['body'] ?? null,
                ]);
                return ['name' => null, 'username' => null];
            }

            return [
                'name'     => $profile['name']     ?? null,
                'username' => $profile['username'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::channel('instagram')->warning('Exceção ao buscar perfil do contato', [
                'igsid' => $igsid,
                'error' => $e->getMessage(),
            ]);
            return ['name' => null, 'username' => null];
        }
    }

    private function findOrCreateConversation(
        InstagramInstance $instance,
        string $igsid
    ): ?InstagramConversation {
        $conversation = InstagramConversation::withoutGlobalScope('tenant')
            ->where('instance_id', $instance->id)
            ->where('igsid', $igsid)
            ->first();

        if ($conversation) {
            // Se conversa já existe mas sem nome/username (falha anterior), tentar atualizar
            if (! $conversation->contact_name && ! $conversation->contact_username) {
                $profile = $this->fetchContactProfile($instance, $igsid);
                if ($profile['name'] || $profile['username']) {
                    InstagramConversation::withoutGlobalScope('tenant')
                        ->where('id', $conversation->id)
                        ->update([
                            'contact_name'     => $profile['name'],
                            'contact_username' => $profile['username'],
                        ]);
                    $conversation->contact_name     = $profile['name'];
                    $conversation->contact_username = $profile['username'];
                    Log::channel('instagram')->info('Perfil do contato atualizado', [
                        'conversation_id' => $conversation->id,
                        'name'            => $profile['name'],
                        'username'        => $profile['username'],
                    ]);
                }
            }
            return $conversation;
        }

        // Conversa nova — buscar perfil do contato
        $profile         = $this->fetchContactProfile($instance, $igsid);
        $contactName     = $profile['name'];
        $contactUsername = $profile['username'];
        $pictureUrl      = null; // profile_picture_url não disponível via IGSID endpoint

        $conversation = InstagramConversation::withoutGlobalScope('tenant')->create([
            'tenant_id'           => $instance->tenant_id,
            'instance_id'         => $instance->id,
            'igsid'               => $igsid,
            'contact_name'        => $contactName,
            'contact_username'    => $contactUsername,
            'contact_picture_url' => $pictureUrl,
            'status'              => 'open',
            'started_at'          => now(),
            'last_message_at'     => now(),
            'unread_count'        => 0,
        ]);

        Log::channel('instagram')->info('Conversa criada', [
            'conversation_id' => $conversation->id,
            'igsid'           => $igsid,
        ]);

        // Auto-assign AI agent
        $autoAgent = AiAgent::withoutGlobalScope('tenant')
            ->where('tenant_id', $instance->tenant_id)
            ->where('is_active', true)
            ->where('auto_assign', true)
            ->where('channel', 'instagram')
            ->first();

        if ($autoAgent) {
            InstagramConversation::withoutGlobalScope('tenant')
                ->where('id', $conversation->id)
                ->update(['ai_agent_id' => $autoAgent->id]);
            $conversation->ai_agent_id = $autoAgent->id;
        }

        // Criar lead vinculado
        $lead = $this->findOrCreateLead(
            $instance->tenant_id,
            $contactName ?? $contactUsername ?? $igsid,
            $contactUsername
        );

        if ($lead) {
            InstagramConversation::withoutGlobalScope('tenant')
                ->where('id', $conversation->id)
                ->update(['lead_id' => $lead->id]);
        }

        return $conversation;
    }

    private function findOrCreateLead(int $tenantId, string $name, ?string $username): ?Lead
    {
        // Tentar encontrar lead existente pelo username do Instagram
        if ($username) {
            $lead = Lead::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('instagram_username', $username)
                ->first();

            if ($lead) {
                return $lead;
            }
        }

        $pipeline = Pipeline::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->first()
            ?? Pipeline::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->first();

        if (! $pipeline) {
            return null;
        }

        $stage = $pipeline->stages()->orderBy('position')->first();

        if (! $stage) {
            return null;
        }

        $data = [
            'tenant_id'   => $tenantId,
            'name'        => $name,
            'source'      => 'instagram',
            'pipeline_id' => $pipeline->id,
            'stage_id'    => $stage->id,
        ];

        if ($username) {
            $data['instagram_username'] = $username;
        }

        return Lead::withoutGlobalScope('tenant')->create($data);
    }

    private function extractMedia(array $messageData): array
    {
        // Image attachment
        if (! empty($messageData['attachments'])) {
            foreach ($messageData['attachments'] as $attachment) {
                $type    = $attachment['type'] ?? 'image';
                $payload = $attachment['payload'] ?? [];
                $url     = $payload['url'] ?? null;

                $mappedType = match ($type) {
                    'image'    => 'image',
                    'audio'    => 'audio',
                    'video'    => 'video',
                    'file'     => 'document',
                    'sticker'  => 'sticker',
                    default    => 'image',
                };

                return [$mappedType, $url];
            }
        }

        return ['text', null];
    }

    // ── Comment Automation ────────────────────────────────────────────────────

    private function processComment(InstagramInstance $instance, array $value): void
    {
        $commentId   = $value['id'] ?? null;
        $commentText = $value['text'] ?? '';
        $mediaId     = $value['media']['id'] ?? null;
        $fromId      = $value['from']['id'] ?? null; // IGSID do comentarista

        if (! $commentId || ! $fromId) {
            return;
        }

        // Dedup via Cache (60s)
        if (! Cache::add("ig:comment:{$commentId}", 1, 60)) {
            Log::channel('instagram')->debug('Comentário duplicado ignorado', ['comment_id' => $commentId]);
            return;
        }

        Log::channel('instagram')->info('Comentário recebido', [
            'comment_id' => $commentId,
            'media_id'   => $mediaId,
            'from_id'    => $fromId,
            'text'       => mb_substr($commentText, 0, 100),
        ]);

        $automations = InstagramAutomation::withoutGlobalScope('tenant')
            ->where('tenant_id', $instance->tenant_id)
            ->where('instance_id', $instance->id)
            ->where('is_active', true)
            ->where(function ($q) use ($mediaId) {
                $q->whereNull('media_id')->orWhere('media_id', $mediaId);
            })
            ->get();

        foreach ($automations as $automation) {
            if (! $this->matchesKeywords($commentText, $automation)) {
                continue;
            }

            Log::channel('instagram')->info('Automação acionada por comentário', [
                'automation_id' => $automation->id,
                'comment_id'    => $commentId,
            ]);

            $service = new InstagramService(decrypt($instance->access_token));

            if ($automation->reply_comment) {
                try {
                    $service->replyToComment($commentId, $automation->reply_comment);
                    $automation->increment('comments_replied');
                    Log::channel('instagram')->info('Comentário respondido com sucesso', [
                        'comment_id'    => $commentId,
                        'automation_id' => $automation->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::channel('instagram')->error('Falha ao responder comentário', [
                        'comment_id' => $commentId,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            if (! empty($automation->dm_messages)) {
                foreach ($automation->dm_messages as $msg) {
                    try {
                        $type = $msg['type'] ?? '';
                        if ($type === 'image' && ! empty($msg['url'])) {
                            $service->sendImageAttachment($fromId, $msg['url']);
                        } elseif ($type === 'text' && ! empty($msg['text'])) {
                            $service->sendMessage($fromId, $msg['text']);
                        }
                    } catch (\Throwable $e) {
                        Log::channel('instagram')->error('Falha ao enviar DM (sequência)', [
                            'from_id'       => $fromId,
                            'automation_id' => $automation->id,
                            'msg_type'      => $msg['type'] ?? '?',
                            'error'         => $e->getMessage(),
                        ]);
                    }
                }
                $automation->increment('dms_sent');
                Log::channel('instagram')->info('Sequência de DM enviada', [
                    'from_id'       => $fromId,
                    'automation_id' => $automation->id,
                    'blocks'        => count($automation->dm_messages),
                ]);
            } elseif ($automation->dm_message) {
                try {
                    $service->sendMessage($fromId, $automation->dm_message);
                    $automation->increment('dms_sent');
                    Log::channel('instagram')->info('DM enviada para comentarista', [
                        'from_id'       => $fromId,
                        'automation_id' => $automation->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::channel('instagram')->error('Falha ao enviar DM para comentarista', [
                        'from_id' => $fromId,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    private function matchesKeywords(string $text, InstagramAutomation $automation): bool
    {
        $keywords = $automation->keywords ?? [];
        if (empty($keywords)) {
            return false;
        }

        $lower = mb_strtolower($text);

        if ($automation->match_type === 'all') {
            foreach ($keywords as $kw) {
                if (! str_contains($lower, mb_strtolower($kw))) {
                    return false;
                }
            }
            return true;
        }

        foreach ($keywords as $kw) {
            if (str_contains($lower, mb_strtolower($kw))) {
                return true;
            }
        }
        return false;
    }
}
