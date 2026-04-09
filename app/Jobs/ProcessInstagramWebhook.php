<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\InstagramConversationUpdated;
use App\Events\InstagramMessageCreated;
use App\Models\AiAgent;
use App\Models\ChatbotFlow;
use App\Models\InstagramAutomation;
use App\Models\InstagramConversation;
use App\Models\InstagramInstance;
use App\Models\InstagramMessage;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\Tenant;
use App\Services\AutomationEngine;
use App\Services\InstagramService;
use App\Services\PlanLimitChecker;
use App\Support\ProfilePictureDownloader;
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

            // Lookup: tentar casar entry.id com qualquer ID armazenado da instância
            $instance = InstagramInstance::withoutGlobalScope('tenant')
                ->where(function ($q) use ($igAccountId) {
                    $q->where('instagram_account_id', $igAccountId)
                      ->orWhere('ig_business_account_id', $igAccountId)
                      ->orWhere('ig_page_id', $igAccountId);
                })
                ->first();

            if (! $instance) {
                // NAO auto-descobrir. A "auto-descoberta" antiga pegava a primeira
                // instance conectada com ig_business_account_id null e atribuia o
                // entry.id do webhook a ela — bug de cross-tenant. Webhooks de
                // QUALQUER tenant podiam acabar grudados na instance errada.
                //
                // Pra resolver instances com IDs faltando, rode o command:
                //   php artisan instagram:repair-instances
                // que pega o token de cada instance, chama /me, e popula
                // instagram_account_id + ig_business_account_id corretamente.
                Log::channel('instagram')->warning('entry.id nao corresponde a nenhuma instance — webhook ignorado', [
                    'ig_account_id' => $igAccountId,
                    'hint'          => 'rode `php artisan instagram:repair-instances` se houver instances com IDs nulos',
                ]);
                continue;
            }

            // A entry do ig_page_id contém apenas outbound echoes com IGSIDs inválidos
            // (diferente ID space). Ignorar messaging dessa entry para evitar conversas duplicadas.
            $isPageEntry = $instance->ig_page_id && $igAccountId === $instance->ig_page_id;

            if (! $isPageEntry) {
                foreach ($entry['messaging'] ?? [] as $messaging) {
                    $this->processMessaging($instance, $messaging, $igAccountId);
                }
            } else {
                Log::channel('instagram')->debug('Ignorando messaging da entry ig_page_id (outbound echo)', [
                    'entry_id' => $igAccountId,
                ]);
            }

            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') === 'comments') {
                    $this->processComment($instance, $change['value'] ?? []);
                }
            }
        }
    }

    // ── Handlers ──────────────────────────────────────────────────────────────

    private function processMessaging(InstagramInstance $instance, array $messaging, string $entryId): void
    {
        $senderId    = $messaging['sender']['id'] ?? null;
        $recipientId = $messaging['recipient']['id'] ?? null;
        $messageData = $messaging['message'] ?? null;
        $postback    = $messaging['postback'] ?? null;
        $timestamp   = $messaging['timestamp'] ?? null;

        // Button Template postback: tratar como mensagem de texto (o título do botão)
        if ($postback && $senderId && ! $messageData) {
            $messageData = [
                'mid'  => $postback['mid'] ?? ('postback_' . $senderId . '_' . ($timestamp ?? time())),
                'text' => $postback['title'] ?? $postback['payload'] ?? '',
            ];
            Log::channel('instagram')->info('Postback recebido como mensagem', [
                'sender_id' => $senderId,
                'title'     => $postback['title'] ?? null,
                'payload'   => $postback['payload'] ?? null,
            ]);
        }

        if (! $senderId || ! $messageData) {
            return;
        }

        $msgId    = $messageData['mid'] ?? null;
        // entry.id é a referência mais confiável — identifica a conta que recebeu o webhook
        $isFromMe = ($senderId === $entryId);

        Log::channel('instagram')->debug('isFromMe check', [
            'sender_id'    => $senderId,
            'recipient_id' => $recipientId,
            'entry_id'     => $entryId,
            'is_from_me'   => $isFromMe,
        ]);

        // Dedup via Cache (atomic): Meta pode entregar o mesmo evento mais de uma vez
        if ($msgId && ! Cache::add("ig:processing:{$msgId}", 1, 10)) {
            Log::channel('instagram')->debug('Evento duplicado ignorado', ['mid' => $msgId]);
            return;
        }

        // Ignorar story mentions: a Meta NÃO grant consent pra User Profile API
        // nesses casos (a pessoa só te marcou no story dela, não te mandou DM),
        // então não conseguimos buscar nome/@username. Também não dá pra responder
        // via API. O resultado seria um card vazio e inútil na inbox.
        // Story replies (a pessoa respondendo ao SEU story via DM) NÃO caem aqui
        // porque não usam attachment type='story_mention' — esses continuam funcionando.
        // Docs: https://developers.facebook.com/docs/messenger-platform/instagram/features/webhook/
        if (! empty($messageData['attachments'])) {
            foreach ($messageData['attachments'] as $att) {
                if (($att['type'] ?? '') === 'story_mention') {
                    Log::channel('instagram')->info('Story mention ignorado', [
                        'mid'       => $msgId,
                        'sender_id' => $senderId,
                    ]);
                    return;
                }
            }
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

        $conversation = $this->findOrCreateConversation($instance, $igsid, $msgId, ! $isFromMe);

        if (! $conversation) {
            return;
        }

        [$type, $mediaUrl, $shareUrl] = $this->extractMedia($messageData);
        $body = $messageData['text'] ?? ($type === 'share' ? $shareUrl : null);

        // Baixa midia inbound pra storage local — URLs do CDN do Meta
        // (lookaside.fbsbx.com / cdninstagram.com) expiram em horas, entao
        // se nao baixar agora o user perde a midia depois.
        if ($mediaUrl && in_array($type, ['image', 'video', 'audio', 'document', 'sticker'], true)) {
            $mediaUrl = $this->downloadMediaToStorage($mediaUrl, $type, $instance->tenant_id);
        }

        // Fix fuso: converter timestamp UTC para o timezone da aplicação antes de salvar
        $sentAt = $timestamp
            ? \Carbon\Carbon::createFromTimestampMs((int) $timestamp)->setTimezone(config('app.timezone'))
            : now();

        // Fix duplicata: se fromMe com body de texto, verificar se foi enviado via CRM
        // (plataforma salva com ig_message_id = null — apenas atualizar o ID em vez de criar novo)
        if ($isFromMe && $msgId && $body) {
            $existing = InstagramMessage::withoutGlobalScope('tenant')
                ->where('conversation_id', $conversation->id)
                ->where('direction', 'outbound')
                ->whereNull('ig_message_id')
                ->where('body', $body)
                ->where('sent_at', '>=', now()->subMinutes(5))
                ->first();

            if ($existing) {
                $existing->update(['ig_message_id' => $msgId, 'ack' => 'delivered']);
                Log::channel('instagram')->debug('fromMe: ig_message_id atualizado', ['mid' => $msgId]);
                return;
            }
        }

        // Salvar mensagem (UNIQUE em ig_message_id previne duplicatas de webhook retry)
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
                'sent_at'         => $sentAt,
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

        // Chatbot flow: trigger por keyword ou continuar fluxo ativo
        if (! $isFromMe) {
            $conversation->refresh();

            // Se já tem chatbot ativo (flow + node), processar próximo step
            if ($conversation->chatbot_flow_id && $conversation->chatbot_node_id) {
                try {
                    (new ProcessChatbotStep($conversation->id, $body ?? '', 'instagram'))->handle();
                } catch (\Throwable $e) {
                    Log::channel('instagram')->error('Chatbot step falhou', [
                        'conversation_id' => $conversation->id,
                        'error'           => $e->getMessage(),
                    ]);
                }
                return; // Chatbot consome a mensagem — não passa para AutomationEngine
            }

            // Tentar ativar chatbot por keyword
            if (! $conversation->ai_agent_id && $body) {
                $this->triggerChatbotFlow($instance, $conversation, $body);
                // Se chatbot foi ativado, não continua para AutomationEngine
                if ($conversation->chatbot_flow_id) {
                    return;
                }
            }
        }

        // Automação: mensagem recebida (apenas inbound)
        if (! $isFromMe) {
            try {
                $leadForEngine = $conversation->lead_id
                    ? Lead::withoutGlobalScope('tenant')->find($conversation->lead_id)
                    : null;
                (new AutomationEngine())->run('message_received', [
                    'tenant_id'    => $instance->tenant_id,
                    'channel'      => 'instagram',
                    'message'      => $message,
                    'conversation' => $conversation,
                    'lead'         => $leadForEngine,
                ]);
            } catch (\Throwable) {}
        }
    }

    /**
     * Busca contact info (name + username + profile_pic) via fluxo hybrid:
     *
     * 1) Tenta GET /{IGSID}?fields=name,username,profile_pic — retorna data
     *    completa (nome real + foto). Funciona pra instances criadas antes
     *    de ~28/03/2026.
     *
     * 2) Se falhar (Meta retorna 100/33 pra instances novas), cai pra
     *    listConversations + getConversationParticipants — retorna so o
     *    username. Pelo menos da pra mostrar @handle no card.
     *
     * Quando consegue foto, baixa pro storage local via ProfilePictureDownloader
     * porque URLs do CDN do Meta (cdninstagram.com) expiram em horas.
     */
    private function fetchContactInfo(InstagramInstance $instance, string $igsid, ?string $messageId = null): array
    {
        $result = ['name' => null, 'username' => null, 'picture' => null];

        try {
            $token   = decrypt($instance->access_token);
            $service = new InstagramService($token);

            // ── Tentativa 1: endpoint direto /{IGSID} (full data) ────────
            $profile = $service->getProfile($igsid);

            if (empty($profile['error'])) {
                $result['name']     = $profile['name']     ?? null;
                $result['username'] = $profile['username'] ?? null;
                $remotePic          = $profile['profile_pic'] ?? null;

                if ($remotePic) {
                    $localPic = ProfilePictureDownloader::download(
                        $remotePic,
                        'instagram',
                        $instance->tenant_id,
                        $igsid,
                    );
                    $result['picture'] = $localPic ?: $remotePic;
                }

                Log::channel('instagram')->info('Contact info obtido via /{IGSID}', [
                    'igsid'    => $igsid,
                    'name'     => $result['name'],
                    'username' => $result['username'],
                    'has_pic'  => (bool) $result['picture'],
                ]);

                return $result;
            }

            // Endpoint direto falhou — log defensivo (so se nao for 100/33,
            // que e o caso "esperado" pra instances novas)
            $errorBody = $profile['body'] ?? '';
            if (! str_contains($errorBody, '"code":100')) {
                Log::channel('instagram')->warning('getProfile retornou erro inesperado', [
                    'igsid'  => $igsid,
                    'status' => $profile['status'] ?? null,
                    'body'   => $errorBody,
                ]);
            }

            // ── Tentativa 2: fallback via /me/conversations + participants ──
            $list = $service->listConversations(20);
            if (! empty($list['error'])) {
                Log::channel('instagram')->warning('Fallback listConversations tambem falhou', [
                    'igsid'  => $igsid,
                    'status' => $list['status'] ?? null,
                ]);
                return $result;
            }

            foreach ($list['data'] ?? [] as $conv) {
                $convId = $conv['id'] ?? null;
                if (! $convId) {
                    continue;
                }

                $details = $service->getConversationParticipants($convId);
                if (! empty($details['error'])) {
                    continue;
                }

                foreach ($details['participants']['data'] ?? [] as $p) {
                    if (($p['id'] ?? null) === $igsid) {
                        $result['username'] = $p['username'] ?? null;
                        Log::channel('instagram')->info('Contact info obtido via fallback (participants)', [
                            'igsid'    => $igsid,
                            'username' => $result['username'],
                        ]);
                        return $result;
                    }
                }
            }

            Log::channel('instagram')->info('IGSID nao encontrado em nenhum endpoint', [
                'igsid'       => $igsid,
                'instance_id' => $instance->id,
            ]);
            return $result;
        } catch (\Throwable $e) {
            Log::channel('instagram')->warning('Excecao ao buscar contact info', [
                'igsid' => $igsid,
                'error' => $e->getMessage(),
            ]);
            return $result;
        }
    }

    private function findOrCreateConversation(
        InstagramInstance $instance,
        string $igsid,
        ?string $messageId = null,
        bool $isInbound = true
    ): ?InstagramConversation {
        $conversation = InstagramConversation::withoutGlobalScope('tenant')
            ->where('instance_id', $instance->id)
            ->where('igsid', $igsid)
            ->first();

        if ($conversation) {
            // Se conversa ja existe mas falta username/name/foto, tentar atualizar
            // (so se for inbound — outbound echo tem from=business, nao da pra extrair).
            $needsName    = ! $conversation->contact_name;
            $needsUser    = ! $conversation->contact_username;
            $needsPicture = ! $conversation->contact_picture_url;

            if (($needsName || $needsUser || $needsPicture) && $isInbound) {
                $info = $this->fetchContactInfo($instance, $igsid, $messageId);

                $update = [];
                if ($needsUser && $info['username']) {
                    $update['contact_username'] = $info['username'];
                }
                if ($needsName) {
                    // name real se tiver, fallback pra username
                    $name = $info['name'] ?? $info['username'] ?? null;
                    if ($name) {
                        $update['contact_name'] = $name;
                    }
                }
                if ($needsPicture && $info['picture']) {
                    $update['contact_picture_url'] = $info['picture'];
                }

                if (! empty($update)) {
                    InstagramConversation::withoutGlobalScope('tenant')
                        ->where('id', $conversation->id)
                        ->update($update);
                    foreach ($update as $k => $v) {
                        $conversation->$k = $v;
                    }
                    Log::channel('instagram')->info('Contact info atualizado em conv existente', [
                        'conversation_id' => $conversation->id,
                        'fields'          => array_keys($update),
                    ]);
                }
            }
            return $conversation;
        }

        // Conversa nova — buscar contact info (so se for inbound)
        $info = $isInbound
            ? $this->fetchContactInfo($instance, $igsid, $messageId)
            : ['name' => null, 'username' => null, 'picture' => null];

        $contactUsername = $info['username'];
        // Display name: name real se tiver, fallback pra username
        $contactName     = $info['name'] ?? $contactUsername;
        $contactPicture  = $info['picture'];

        $conversation = InstagramConversation::withoutGlobalScope('tenant')->create([
            'tenant_id'           => $instance->tenant_id,
            'instance_id'         => $instance->id,
            'igsid'               => $igsid,
            'contact_name'        => $contactName,
            'contact_username'    => $contactUsername,
            'contact_picture_url' => $contactPicture,
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
            $conversation->lead_id = $lead->id;
        }

        // Automação: nova conversa Instagram criada
        try {
            (new AutomationEngine())->run('conversation_created', [
                'tenant_id'    => $instance->tenant_id,
                'channel'      => 'instagram',
                'conversation' => $conversation,
                'lead'         => $lead,
            ]);
        } catch (\Throwable) {}

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

        if ($pipeline->auto_create_lead === false || $pipeline->auto_create_from_instagram === false) {
            return null; // Auto-criação via Instagram desativada para este pipeline
        }

        $tenant = Tenant::find($tenantId);
        if ($tenant) {
            $limitMsg = PlanLimitChecker::check('leads', $tenant);
            if ($limitMsg) {
                Log::channel('whatsapp')->info("Lead IG não criado (limite do plano): {$name} tenant={$tenantId}");
                return null;
            }
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

                $mappedType = match ($type) {
                    'image'    => 'image',
                    'audio'    => 'audio',
                    'video'    => 'video',
                    'file'     => 'document',
                    'sticker'  => 'sticker',
                    'share'    => 'share',
                    default    => 'image',
                };

                // Publicações compartilhadas: thumbnail_url é a imagem renderizável;
                // payload['url'] é a URL do post Instagram (para o link)
                if ($type === 'share') {
                    $displayUrl = $payload['thumbnail_url'] ?? null;
                    $postUrl    = $payload['url'] ?? null;
                    return ['share', $displayUrl, $postUrl];
                }

                return [$mappedType, $payload['url'] ?? null, null];
            }
        }

        return ['text', null, null];
    }

    // ── Chatbot Flow Trigger ──────────────────────────────────────────────────

    private function triggerChatbotFlow(InstagramInstance $instance, InstagramConversation $conv, string $body): void
    {
        $bodyLower = strtolower(trim($body));

        $flow = ChatbotFlow::withoutGlobalScope('tenant')
            ->where('tenant_id', $instance->tenant_id)
            ->where('channel', 'instagram')
            ->where('is_active', true)
            ->get()
            ->first(function ($f) use ($bodyLower) {
                foreach ($f->trigger_keywords ?? [] as $kw) {
                    if (strtolower(trim($kw)) === $bodyLower) {
                        return true;
                    }
                }
                return false;
            });

        if (! $flow) {
            return;
        }

        InstagramConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update(['chatbot_flow_id' => $flow->id]);
        $conv->chatbot_flow_id = $flow->id;

        Log::channel('instagram')->info('Chatbot flow ativado', [
            'conversation_id' => $conv->id,
            'flow_id'         => $flow->id,
        ]);

        try {
            (new ProcessChatbotStep($conv->id, $body, 'instagram'))->handle();
        } catch (\Throwable $e) {
            Log::channel('instagram')->error('Chatbot trigger step falhou', [
                'conversation_id' => $conv->id,
                'error'           => $e->getMessage(),
            ]);
        }
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
                // A 1ª mensagem de texto usa Private Reply (recipient.comment_id) para
                // abrir a janela de conversa. Mensagens subsequentes usam recipient.id.
                // Private Reply só suporta texto puro (sem imagem, sem botões).
                // Se o 1º bloco tem botões, enviamos Private Reply (texto) + Button Template (botões) logo em seguida.
                $firstSent = false;

                foreach ($automation->dm_messages as $msg) {
                    try {
                        $type = $msg['type'] ?? '';

                        if (! $firstSent && $type === 'text' && ! empty($msg['text'])) {
                            // Private Reply: abre janela de DM via comment_id (texto puro)
                            $service->sendPrivateReply($commentId, $msg['text']);
                            $firstSent = true;

                            // Se o 1º bloco tem botões, enviar Button Template logo em seguida
                            $buttons = $msg['buttons'] ?? [];
                            if (! empty($buttons)) {
                                usleep(500000); // 0.5s para garantir que a janela abriu
                                $formatted = $this->formatButtonsForTemplate($buttons);
                                $service->sendButtonTemplate($fromId, $msg['text'], $formatted);
                            }
                        } elseif ($type === 'image' && ! empty($msg['url'])) {
                            if (! $firstSent) {
                                // 1º bloco é imagem — enviar texto genérico como Private Reply antes
                                $service->sendPrivateReply($commentId, '📩');
                                $firstSent = true;
                            }
                            $service->sendImageAttachment($fromId, $msg['url']);
                        } elseif ($type === 'text' && ! empty($msg['text'])) {
                            // Mensagens subsequentes: DM regular (janela já aberta)
                            $buttons = $msg['buttons'] ?? [];
                            if (! empty($buttons)) {
                                $formatted = $this->formatButtonsForTemplate($buttons);
                                $service->sendButtonTemplate($fromId, $msg['text'], $formatted);
                            } else {
                                $service->sendMessage($fromId, $msg['text']);
                            }
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

                if ($firstSent) {
                    $automation->increment('dms_sent');
                    Log::channel('instagram')->info('Sequência de DM enviada', [
                        'from_id'       => $fromId,
                        'automation_id' => $automation->id,
                        'blocks'        => count($automation->dm_messages),
                    ]);
                }
            } elseif ($automation->dm_message) {
                try {
                    // Legacy: campo dm_message (texto único) — também usa Private Reply
                    $service->sendPrivateReply($commentId, $automation->dm_message);
                    $automation->increment('dms_sent');
                    Log::channel('instagram')->info('DM enviada para comentarista (Private Reply)', [
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

        // ── Chatbot Flow trigger from comments ──────────────────────────
        $this->triggerChatbotFromComment($instance, $commentId, $commentText, $mediaId, $fromId);
    }

    /**
     * Trigger a chatbot flow from an Instagram comment.
     * The chatbot runs via DM (Private Reply opens the window, then ProcessChatbotStep handles the rest).
     */
    private function triggerChatbotFromComment(
        InstagramInstance $instance,
        string $commentId,
        string $commentText,
        ?string $mediaId,
        string $fromId,
    ): void {
        $commentLower = strtolower(trim($commentText));

        $flows = ChatbotFlow::withoutGlobalScope('tenant')
            ->where('tenant_id', $instance->tenant_id)
            ->where('channel', 'instagram')
            ->where('trigger_type', 'instagram_comment')
            ->where('is_active', true)
            ->where(function ($q) use ($mediaId) {
                $q->whereNull('trigger_media_id')->orWhere('trigger_media_id', $mediaId);
            })
            ->get();

        foreach ($flows as $flow) {
            $keywords = $flow->trigger_keywords ?? [];
            if (!empty($keywords)) {
                $matched = false;
                foreach ($keywords as $kw) {
                    if (str_contains($commentLower, strtolower(trim($kw)))) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    continue;
                }
            }

            Log::channel('instagram')->info('Chatbot flow acionado por comentário', [
                'flow_id'    => $flow->id,
                'comment_id' => $commentId,
                'media_id'   => $mediaId,
            ]);

            try {
                $service = new InstagramService(decrypt($instance->access_token));

                // Reply on the comment (optional)
                if ($flow->trigger_reply_comment) {
                    try {
                        $service->replyToComment($commentId, $flow->trigger_reply_comment);
                    } catch (\Throwable $e) {
                        Log::channel('instagram')->warning('Chatbot: falha ao responder comentário', ['error' => $e->getMessage()]);
                    }
                }

                // Find or create conversation with the commenter
                $conv = InstagramConversation::withoutGlobalScope('tenant')
                    ->where('tenant_id', $instance->tenant_id)
                    ->where('igsid', $fromId)
                    ->first();

                if (!$conv) {
                    // Comentadores nao tem message_id (so comment_id), entao nao
                    // da pra usar getMessageSender pra obter username. Cria a
                    // conversation com fallback. Quando o user mandar a primeira
                    // DM real (em resposta a Private Reply do CRM), o username
                    // sera atualizado via fetchContactInfo no processMessaging.
                    $conv = InstagramConversation::withoutGlobalScope('tenant')->create([
                        'tenant_id'        => $instance->tenant_id,
                        'instance_id'      => $instance->id,
                        'igsid'            => $fromId,
                        'contact_name'     => 'Instagram User',
                        'contact_username' => null,
                        'status'           => 'open',
                    ]);
                }

                // Skip if conversation already has an active chatbot or AI agent
                if (($conv->chatbot_flow_id && $conv->chatbot_node_id) || $conv->ai_agent_id) {
                    Log::channel('instagram')->debug('Chatbot comment: conversa já tem fluxo/agente ativo', ['conv_id' => $conv->id]);
                    return;
                }

                // Send Private Reply to open DM window
                $startNode = $flow->nodes()->where('is_start', true)->first();
                $privateReplyText = $startNode?->config['message'] ?? $flow->welcome_message ?? '👋';

                try {
                    $service->sendPrivateReply($commentId, $privateReplyText);
                } catch (\Throwable $e) {
                    Log::channel('instagram')->warning('Chatbot: falha ao enviar Private Reply', ['error' => $e->getMessage()]);
                    // Try regular DM as fallback
                    $service->sendMessage($fromId, $privateReplyText);
                }

                // Set chatbot flow on the conversation
                $conv->update([
                    'chatbot_flow_id'    => $flow->id,
                    'chatbot_node_id'    => $startNode?->id,
                    'chatbot_variables'  => [],
                    'status'             => 'open',
                ]);

                // Trigger the chatbot step processing
                (new ProcessChatbotStep($conv->id, $commentText, 'instagram'))->handle();

            } catch (\Throwable $e) {
                Log::channel('instagram')->error('Chatbot comment trigger falhou', [
                    'flow_id'    => $flow->id,
                    'comment_id' => $commentId,
                    'error'      => $e->getMessage(),
                ]);
            }

            // Only trigger the first matching flow
            return;
        }
    }

    /**
     * Convert button definitions to the Meta Button Template format.
     * Supports both legacy (string[]) and new (object[]) formats.
     * Max 3 buttons per template.
     *
     * @param  array  $buttons  ['title'] or [['type'=>'postback','title'=>'...','payload'=>'...'], ...]
     * @return array  Formatted buttons for sendButtonTemplate()
     */
    private function formatButtonsForTemplate(array $buttons): array
    {
        $formatted = array_map(function (mixed $btn, int $i): array {
            // Legacy format: plain string → convert to postback
            if (is_string($btn)) {
                return [
                    'type'    => 'postback',
                    'title'   => mb_substr($btn, 0, 20),
                    'payload' => 'BTN_' . $i,
                ];
            }

            // New format: already an object with type/title/url|payload
            $type  = $btn['type'] ?? 'postback';
            $title = mb_substr($btn['title'] ?? '', 0, 20);

            if ($type === 'web_url') {
                return [
                    'type'  => 'web_url',
                    'title' => $title,
                    'url'   => $btn['url'] ?? '',
                ];
            }

            return [
                'type'    => 'postback',
                'title'   => $title,
                'payload' => $btn['payload'] ?? 'BTN_' . $i,
            ];
        }, $buttons, array_keys($buttons));

        return array_slice($formatted, 0, 3);
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

    /**
     * Baixa midia da URL do CDN do Meta (que expira em horas) e salva localmente
     * em storage/app/public/instagram-media/ retornando a URL publica permanente.
     *
     * Retorna a URL original se o download falhar (degradacao graciosa).
     * Equivalente ao que ProcessWhatsappCloudWebhook ja faz pra mensagens
     * Cloud API (linhas 317-340 daquele job).
     */
    private function downloadMediaToStorage(string $remoteUrl, string $type, int $tenantId): string
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($remoteUrl);

            if (! $response->successful()) {
                Log::channel('instagram')->warning('Download de midia inbound falhou', [
                    'url'    => substr($remoteUrl, 0, 100),
                    'status' => $response->status(),
                    'type'   => $type,
                ]);
                return $remoteUrl;
            }

            $binary    = $response->body();
            $extension = match ($type) {
                'image'    => 'jpg',
                'video'    => 'mp4',
                'audio'    => 'm4a',
                'document' => 'bin',
                'sticker'  => 'webp',
                default    => 'bin',
            };

            $filename = sprintf(
                'instagram-media/%d/%s_%s.%s',
                $tenantId,
                now()->format('Y-m-d'),
                \Illuminate\Support\Str::random(20),
                $extension,
            );

            \Storage::disk('public')->put($filename, $binary);

            return \Storage::disk('public')->url($filename);
        } catch (\Throwable $e) {
            Log::channel('instagram')->warning('Excecao ao baixar midia inbound', [
                'url'   => substr($remoteUrl, 0, 100),
                'error' => $e->getMessage(),
                'type'  => $type,
            ]);
            return $remoteUrl;  // fallback graciosa: usa a URL original
        }
    }
}
