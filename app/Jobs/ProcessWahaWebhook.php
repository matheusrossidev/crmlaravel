<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\WhatsappConversationUpdated;
use App\Events\WhatsappMessageCreated;
use App\Jobs\ProcessAiResponse;
use App\Jobs\ProcessChatbotStep;
use App\Models\AiAgent;
use App\Models\ChatbotFlow;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\AutomationEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $msg   = $this->payload['payload'] ?? [];
        $event = $this->payload['event'] ?? '';

        // Deduplicação: WAHA envia 'message' E 'message.any' para cada mensagem.
        // Usamos Cache::add() (atômico no Redis) para garantir que apenas o
        // primeiro worker processa o msg_id — o segundo retorna imediatamente.
        $msgIdForLock = $msg['id'] ?? null;
        if ($msgIdForLock && ! Cache::add("waha:processing:{$msgIdForLock}", 1, 10)) {
            Log::channel('whatsapp')->debug('Evento duplicado ignorado (message/message.any)', [
                'id'    => $msgIdForLock,
                'event' => $event,
            ]);
            return;
        }

        $from     = $msg['from'] ?? '';
        $isFromMe = ! empty($msg['fromMe']);

        // Para mensagens fromMe, 'from' é o JID do nosso celular conectado.
        // O parceiro da conversa está em chatId / to ou embutido no message ID.
        // No GOWS engine, msgs enviadas do celular têm msg_id="true_{account_lid}_{id}",
        // codificando o LID da CONTA (não o JID do cliente). chatId e to têm o JID
        // correto do cliente. Preferir chatId/to quando disponíveis e não forem @lid.
        if ($isFromMe) {
            $msgId         = $msg['id'] ?? '';
            $recipientFrom = null;
            if (preg_match('/^true_(.+@[\w.]+)_/', $msgId, $m)) {
                $recipientFrom = $m[1];
            }
            $chatIdCandidate = $msg['chatId'] ?? '';
            $toCandidate     = $msg['to']     ?? '';
            if ($chatIdCandidate && ! str_ends_with($chatIdCandidate, '@lid')) {
                $from = $chatIdCandidate;
            } elseif ($toCandidate && ! str_ends_with($toCandidate, '@lid')) {
                $from = $toCandidate;
            } else {
                // Ambos @lid ou ausentes: usar recipientFrom do msg_id como fallback
                $from = $recipientFrom ?? $toCandidate ?? $chatIdCandidate ?? $from;
            }
        }

        // Ignorar: Status/Stories (@broadcast), Canais (@newsletter) e mensagens de sistema.
        // Verifica tanto $from quanto $chatId: quando alguém visualiza/reage a um Status,
        // WAHA envia from=JID_real_do_contato mas chatId=status@broadcast — sem checar chatId
        // o guard não filtraria e criaria uma conversa errada.
        $chatId = $msg['chatId'] ?? '';
        if (
            str_contains($from,   'broadcast')   ||
            str_contains($from,   'status@')      ||
            str_contains($from,   '@newsletter')  ||
            str_contains($chatId, 'broadcast')    ||
            str_contains($chatId, '@newsletter')
        ) {
            Log::channel('whatsapp')->debug('Ignorado: status/canal/broadcast', [
                'from'   => $from,
                'chatId' => $chatId,
                'event'  => $event,
            ]);
            return;
        }

        $isGroup = str_contains($from, '@g.us');

        // Ignorar grupos de anúncio de Comunidade (announce = true no payload)
        if ($isGroup) {
            $isAnnounce = ! empty($msg['metadata']['announce'])
                       || ! empty($msg['_data']['announce'])
                       || ! empty($msg['_data']['Info']['Announce']);
            if ($isAnnounce) {
                Log::channel('whatsapp')->debug('Ignorado: grupo de anúncio de Comunidade', ['from' => $from]);
                return;
            }
        }

        // Mensagens fromMe: podem ser (a) enviadas pelo CRM — já no banco, ignorar;
        // ou (b) enviadas diretamente do celular — salvar como outbound.
        // Verificamos pelo waha_message_id antes de continuar.
        if ($isFromMe) {
            $wahaIdCheck = $msg['id'] ?? null;
            if ($wahaIdCheck && WhatsappMessage::withoutGlobalScope('tenant')->where('waha_message_id', $wahaIdCheck)->exists()) {
                Log::channel('whatsapp')->debug('fromMe já salvo (enviado pelo CRM) — ignorando', ['id' => $wahaIdCheck]);
                return;
            }
            Log::channel('whatsapp')->info('fromMe não encontrado no banco — salvando (enviado direto do celular)', ['id' => $wahaIdCheck]);
        }

        $phone = $this->normalizePhone($from, $msg, $isFromMe);

        // Se o phone parece um LID numérico (> 13 dígitos, apenas dígitos),
        // tentar resolver para o número real via WAHA contacts API.
        if (! $isGroup && strlen($phone) > 13 && ctype_digit($phone)) {
            try {
                $wahaLid     = new \App\Services\WahaService($instance->session_name);
                $contactInfo = $wahaLid->getContactInfo($from); // passa o JID completo "@lid"
                $resolvedJid = $contactInfo['id'] ?? '';
                if ($resolvedJid && ! str_ends_with($resolvedJid, '@lid')) {
                    $resolved = (string) preg_replace('/[:@].+$/', '', $resolvedJid);
                    if ($resolved && ctype_digit($resolved)) {
                        Log::channel('whatsapp')->info('LID resolvido via WAHA contacts', [
                            'lid'      => $phone,
                            'resolved' => $resolved,
                        ]);
                        $phone = $resolved;
                    }
                }
            } catch (\Throwable) {}
        }

        Log::channel('whatsapp')->info('Processando mensagem', [
            'event'  => $event,
            'from'   => $from,
            'phone'  => $phone,
            'msg_id' => $msg['id'] ?? null,
        ]);

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
                Log::channel('whatsapp')->info('Conversa @lid migrada para telefone real', [
                    'conversation_id' => $conversation->id,
                    'old_phone'       => $conversation->phone,
                    'new_phone'       => $phone,
                ]);
                // Migra o telefone armazenado para o número real normalizado
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conversation->id)
                    ->update(['phone' => $phone]);
            }
        }

        // Fallback SenderAlt: mensagem chega com from real (@c.us / @s.whatsapp.net)
        // mas a conversa antiga foi gravada com o telefone LID (antes da correção).
        // SenderAlt contém o LID alternativo: "36576092528787:22@lid".
        if (! $conversation) {
            $senderAlt = $msg['_data']['Info']['SenderAlt'] ?? '';
            if ($senderAlt && str_ends_with($senderAlt, '@lid')) {
                $lidNumeric = (string) preg_replace('/[:@].+$/', '', $senderAlt);

                // Tenta por phone LIKE 'lidNumeric%'
                $conv = WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('tenant_id', $instance->tenant_id)
                    ->where('phone', 'LIKE', $lidNumeric . '%')
                    ->first();

                // Tenta por waha_message_id de mensagem inbound com este LID
                if (! $conv) {
                    $existingMsg = WhatsappMessage::withoutGlobalScope('tenant')
                        ->where('waha_message_id', 'LIKE', "false_{$lidNumeric}@%")
                        ->latest('sent_at')
                        ->first();
                    if ($existingMsg) {
                        $conv = WhatsappConversation::withoutGlobalScope('tenant')
                            ->where('tenant_id', $instance->tenant_id)
                            ->find($existingMsg->conversation_id);
                    }
                }

                if ($conv) {
                    $conversation = $conv;
                    Log::channel('whatsapp')->info('Conversa LID migrada via SenderAlt', [
                        'conversation_id' => $conv->id,
                        'old_phone'       => $conv->phone,
                        'new_phone'       => $phone,
                        'lid'             => $lidNumeric,
                    ]);
                    WhatsappConversation::withoutGlobalScope('tenant')
                        ->where('id', $conv->id)
                        ->update(['phone' => $phone]);
                }
            }
        }

        // Fallback extra para fromMe com @lid: o normalizePhone não consegue resolver
        // o telefone real do contato (Sender = nosso telefone, não o do contato).
        // Busca conversa via waha_message_id de mensagens inbound deste mesmo LID.
        if (! $conversation && $isFromMe && str_ends_with($from, '@lid')) {
            $lidNumeric = (string) preg_replace('/[:@].+$/', '', $from);
            $existingMsg = WhatsappMessage::withoutGlobalScope('tenant')
                ->where('waha_message_id', 'LIKE', "false_{$lidNumeric}@%")
                ->latest('sent_at')
                ->first();
            if ($existingMsg) {
                $conv = WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('tenant_id', $instance->tenant_id)
                    ->find($existingMsg->conversation_id);
                if ($conv) {
                    $conversation = $conv;
                    $phone        = $conv->phone;
                    Log::channel('whatsapp')->info('fromMe @lid: conversa encontrada via waha_message_id', [
                        'conversation_id' => $conv->id,
                        'lid'             => $lidNumeric,
                        'phone'           => $phone,
                    ]);
                }
            }
        }

        // Para grupos: contact_name = nome do grupo; sender_name = quem enviou a mensagem.
        // Para 1:1: contact_name = nome do contato; sender_name = null.
        if ($isGroup) {
            // Fontes do nome do grupo — por ordem de confiabilidade
            $contactName = $msg['chatName']                    // maioria dos engines
                ?? $msg['metadata']['subject']                 // GOWS/NOWEB: campo correto
                ?? $msg['_data']['Info']['Subject']
                ?? $msg['_data']['Info']['GroupName']
                ?? $msg['_data']['Info']['Name']
                ?? null;

            // Descartar se for JID (ex: "1234@g.us") em vez de nome legível
            if ($contactName && str_contains($contactName, '@')) {
                $contactName = null;
            }

            // Fallback: buscar via GET /api/{session}/groups/{id} (retorna 'subject')
            if (! $contactName) {
                try {
                    $wahaForGroup = new \App\Services\WahaService($instance->session_name);
                    $groupInfo    = $wahaForGroup->getGroupInfo($from);
                    $contactName  = $groupInfo['subject'] ?? $groupInfo['name'] ?? null;
                } catch (\Throwable) {}
            }

            $messageSenderName = $msg['_data']['Info']['PushName']
                ?? $msg['_data']['notifyName']
                ?? $msg['notifyName']
                ?? null;
        } else {
            if ($isFromMe) {
                // pushName em mensagens fromMe = nome da conta conectada (não do cliente).
                // Buscar o nome real via WAHA /api/contacts, que retorna o nome da agenda.
                $contactName = null;
                try {
                    $wahaForContact = new \App\Services\WahaService($instance->session_name);
                    $contactInfo    = $wahaForContact->getContactInfo($from);
                    $contactName    = $contactInfo['name'] ?? $contactInfo['pushName'] ?? null;
                } catch (\Throwable) {}
            } else {
                // Mensagem recebida do cliente → pushName é o nome do próprio cliente.
                $contactName = $msg['_data']['Info']['PushName']
                    ?? $msg['_data']['notifyName']
                    ?? $msg['notifyName']
                    ?? null;
            }
            $messageSenderName = null;
        }

        if (! $conversation) {
            // Tentar buscar foto de perfil do contato/grupo ao criar nova conversa
            $pictureUrl = null;
            try {
                $wahaForPic = new \App\Services\WahaService($instance->session_name);
                $pictureUrl = $isGroup
                    ? $wahaForPic->getGroupPicture($from)
                    : $wahaForPic->getContactPicture($from);
            } catch (\Throwable) {}

            $conversation = WhatsappConversation::withoutGlobalScope('tenant')->create([
                'tenant_id'           => $instance->tenant_id,
                'instance_id'         => $instance->id,
                'phone'               => $phone,
                'is_group'            => $isGroup,
                'contact_name'        => $contactName,
                'contact_picture_url' => $pictureUrl,
                'status'              => 'open',
                'started_at'          => now(),
                'last_message_at'     => now(),
                'unread_count'        => 0,
            ]);
            Log::channel('whatsapp')->info('Conversa CRIADA', [
                'conversation_id' => $conversation->id,
                'phone'           => $phone,
                'contact_name'    => $contactName,
                'is_group'        => $isGroup,
                'has_picture'     => $pictureUrl !== null,
            ]);

            // Auto-assign: atribuir agente de IA automaticamente (apenas conversas individuais)
            if (! $isGroup) {
                $autoAgent = AiAgent::withoutGlobalScope('tenant')
                    ->where('tenant_id', $instance->tenant_id)
                    ->where('is_active', true)
                    ->where('auto_assign', true)
                    ->where('channel', 'whatsapp')
                    ->first();
                if ($autoAgent) {
                    WhatsappConversation::withoutGlobalScope('tenant')
                        ->where('id', $conversation->id)
                        ->update(['ai_agent_id' => $autoAgent->id]);
                    $conversation->ai_agent_id = $autoAgent->id;
                    Log::channel('whatsapp')->info('AI auto-assign', [
                        'conversation_id' => $conversation->id,
                        'agent_id'        => $autoAgent->id,
                        'agent_name'      => $autoAgent->name,
                    ]);
                }
            }

            // Automação: nova conversa criada
            try {
                (new AutomationEngine())->run('conversation_created', [
                    'tenant_id'    => $instance->tenant_id,
                    'channel'      => 'whatsapp',
                    'conversation' => $conversation,
                    'lead'         => null,
                ]);
            } catch (\Throwable) {}

        } else {
            Log::channel('whatsapp')->info('Conversa encontrada', [
                'conversation_id' => $conversation->id,
                'phone'           => $phone,
            ]);

            // Atualizar foto/nome se ausentes (retry a cada mensagem até preencher)
            $convUpdates = [];
            if ($conversation->contact_picture_url === null) {
                try {
                    $wahaForPic  = new \App\Services\WahaService($instance->session_name);
                    $pic         = $isGroup
                        ? $wahaForPic->getGroupPicture($from)
                        : $wahaForPic->getContactPicture($from);
                    if ($pic) {
                        $convUpdates['contact_picture_url'] = $pic;
                    }
                } catch (\Throwable) {}
            }
            if ($isGroup && empty($conversation->contact_name)) {
                $resolvedGroupName = $contactName; // nome extraído do payload (pode ser null)

                if (! $resolvedGroupName) {
                    // Payload não trouxe nome → tentar buscar via WAHA API
                    try {
                        $wahaRetry         = new \App\Services\WahaService($instance->session_name);
                        $retryInfo         = $wahaRetry->getGroupInfo($from);
                        $resolvedGroupName = $retryInfo['subject'] ?? $retryInfo['name'] ?? null;
                    } catch (\Throwable) {}
                }

                if ($resolvedGroupName) {
                    $convUpdates['contact_name'] = $resolvedGroupName;
                }
            }
            if ($convUpdates) {
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conversation->id)
                    ->update($convUpdates);
                foreach ($convUpdates as $k => $v) {
                    $conversation->$k = $v;
                }
            }
        }

        // Vincular a conversa a um Lead — apenas para conversas individuais (não grupos)
        if (! $isGroup && ! $conversation->lead_id) {
            $lead = $this->findOrCreateLead($instance->tenant_id, $phone, $contactName);
            if ($lead) {
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conversation->id)
                    ->update(['lead_id' => $lead->id]);
                $conversation->lead_id = $lead->id;
                Log::channel('whatsapp')->info('Lead vinculado', ['lead_id' => $lead->id, 'phone' => $phone]);
            } else {
                Log::channel('whatsapp')->warning('Lead não criado — tenant sem pipeline configurado', ['phone' => $phone]);
            }
        }

        [$type, $mediaUrl, $mediaMime, $mediaFilename] = $this->extractMedia($msg);

        $body = $msg['body'] ?? $msg['caption'] ?? null;

        // Auto-trigger de chatbot: verifica keyword em QUALQUER mensagem inbound sem flow ativo.
        // DEVE ficar APÓS a atribuição de $body (acima) para comparar a mensagem correta.
        // Não ativar chatbot se a conversa já tem agente de IA — exclusividade mútua.
        if (! $isFromMe && ! $isGroup && ! $conversation->chatbot_flow_id && ! $conversation->ai_agent_id) {
            $msgBodyLower = strtolower($body ?? '');
            $activeFlow   = ChatbotFlow::withoutGlobalScope('tenant')
                ->where('tenant_id', $instance->tenant_id)
                ->where('is_active', true)
                ->whereNotNull('trigger_keywords')
                ->get()
                ->first(fn ($f) => collect($f->trigger_keywords)
                    ->contains(fn ($kw) => str_contains($msgBodyLower, strtolower($kw))));

            if ($activeFlow) {
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conversation->id)
                    ->update(['chatbot_flow_id' => $activeFlow->id]);
                $conversation->chatbot_flow_id = $activeFlow->id;
                Log::channel('whatsapp')->info('Chatbot: flow auto-atribuído', [
                    'conversation_id' => $conversation->id,
                    'flow_id'         => $activeFlow->id,
                ]);
            }
        }

        // Evitar duplicatas pelo waha_message_id
        $wahaId = $msg['id'] ?? null;
        if ($wahaId && WhatsappMessage::withoutGlobalScope('tenant')->where('waha_message_id', $wahaId)->exists()) {
            Log::channel('whatsapp')->warning('DUPLICATA detectada — abortando', [
                'waha_message_id' => $wahaId,
                'event'           => $event,
            ]);
            return;
        }

        $message = WhatsappMessage::withoutGlobalScope('tenant')->create([
            'tenant_id'       => $instance->tenant_id,
            'conversation_id' => $conversation->id,
            'waha_message_id' => $wahaId,
            'direction'       => $isFromMe ? 'outbound' : 'inbound',
            'sender_name'     => $messageSenderName ?? null,
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

        Log::channel('whatsapp')->info('Mensagem salva', [
            'message_id'      => $message->id,
            'waha_message_id' => $wahaId,
            'type'            => $type,
            'has_media'       => $mediaUrl !== null,
        ]);

        // Transcrever áudio para texto via Whisper (apenas mensagens inbound com agente de IA ativo)
        // Whisper é um serviço OpenAI independente do provider principal do LLM
        if ($type === 'audio') {
            Log::channel('whatsapp')->info('Whisper: diagnóstico de áudio recebido', [
                'message_id'      => $message->id,
                'is_from_me'      => $isFromMe,
                'has_media_url'   => ! empty($mediaUrl),
                'media_url'       => $mediaUrl ? mb_substr($mediaUrl, 0, 80) : null,
                'ai_agent_id'     => $conversation->ai_agent_id,
                'whisper_key_set' => (string) config('ai.whisper_api_key') !== '',
            ]);
        }

        if (! $isFromMe && $type === 'audio' && $mediaUrl && $conversation->ai_agent_id
            && (string) config('ai.whisper_api_key') !== ''
        ) {
            try {
                $transcript = (new \App\Services\AiAgentService())->transcribeAudio($mediaUrl);
                if ($transcript) {
                    WhatsappMessage::withoutGlobalScope('tenant')
                        ->where('id', $message->id)
                        ->update(['body' => $transcript]);
                    $message->body = $transcript;
                    Log::channel('whatsapp')->info('Áudio transcrito via Whisper', [
                        'message_id' => $message->id,
                        'length'     => mb_strlen($transcript),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->error('Whisper: transcrição falhou', [
                    'message_id' => $message->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        // Atualizar conversa ANTES do broadcast — garante que last_message_at e
        // unread_count sejam salvos mesmo se o broadcaster estiver indisponível.
        $convUpdate = [
            'last_message_at' => now(),
            'instance_id'     => $instance->id,
            'status'          => 'open',
            'closed_at'       => null,
        ];
        if (! $isFromMe) {
            $convUpdate['unread_count'] = \Illuminate\Support\Facades\DB::raw('unread_count + 1');
        }
        // Se conversa já existia sem nome (ex: importada), atualizar com o nome resolvido
        if (! empty($contactName) && empty($conversation->contact_name)) {
            $convUpdate['contact_name'] = $contactName;
        }
        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conversation->id)
            ->update($convUpdate);

        // Broadcast via WebSocket — envolvido em try/catch para que uma falha
        // no broadcaster (Reverb OOM, Pusher indisponível, etc.) não impeça
        // que a mensagem e a conversa sejam salvas no banco.
        try {
            WhatsappMessageCreated::dispatch($message, $instance->tenant_id);
            $conversation->refresh();
            WhatsappConversationUpdated::dispatch($conversation, $instance->tenant_id);
            Log::channel('whatsapp')->info('Broadcast enviado', ['tenant_id' => $instance->tenant_id]);
        } catch (\Throwable $e) {
            // Broadcaster indisponível — o polling de 5s do frontend supre o real-time.
            Log::channel('whatsapp')->error('Broadcast FALHOU', ['error' => $e->getMessage()]);
        }

        // Executar chatbot — apenas para mensagens inbound em conversas com fluxo ativo
        if (! $isFromMe && ! $isGroup && $conversation->chatbot_flow_id) {
            try {
                (new ProcessChatbotStep($conversation->id, $body ?? ''))->handle();
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->error('Chatbot step falhou', [
                    'conversation_id' => $conversation->id,
                    'error'           => $e->getMessage(),
                    'file'            => $e->getFile() . ':' . $e->getLine(),
                ]);
            }
        }

        // Executar agente de IA — apenas para mensagens inbound sem chatbot ativo
        if (! $isFromMe && ! $isGroup
            && $conversation->ai_agent_id
            && ! $conversation->chatbot_flow_id
        ) {
            try {
                // Incrementar versão atomicamente — serve como debounce quando o usuário
                // envia várias mensagens seguidas. ProcessAiResponse dorme response_wait_seconds
                // e descarta se a versão não bater (mensagem mais recente assume o processamento).
                $aiVersion = (int) Cache::increment("ai:version:{$conversation->id}");
                (new ProcessAiResponse($conversation->id, $aiVersion))->process();
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->error('AI agent falhou', [
                    'conversation_id' => $conversation->id,
                    'error'           => $e->getMessage(),
                    'file'            => $e->getFile() . ':' . $e->getLine(),
                ]);
            }
        }

        // Automação: mensagem recebida (apenas inbound, individuais)
        if (! $isFromMe && ! $isGroup) {
            try {
                $leadForEngine = isset($lead) ? $lead : Lead::withoutGlobalScope('tenant')->find($conversation->lead_id);
                (new AutomationEngine())->run('message_received', [
                    'tenant_id'    => $instance->tenant_id,
                    'channel'      => 'whatsapp',
                    'message'      => $message,
                    'conversation' => $conversation,
                    'lead'         => $leadForEngine,
                ]);
            } catch (\Throwable) {}
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

    /**
     * Encontra um Lead pelo telefone ou cria um novo vinculado ao pipeline padrão.
     * Retorna null se não houver pipeline configurado para o tenant.
     */
    private function findOrCreateLead(int $tenantId, string $phone, ?string $contactName): ?Lead
    {
        // Tenta encontrar lead existente com o mesmo telefone
        $lead = Lead::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('phone', $phone)
            ->first();

        if ($lead) {
            return $lead;
        }

        // Busca o pipeline padrão do tenant para criar o lead automaticamente
        $pipeline = Pipeline::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->first()
            ?? Pipeline::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->first();

        if (! $pipeline) {
            return null; // Tenant sem pipeline configurado — não cria lead
        }

        if ($pipeline->auto_create_lead === false || $pipeline->auto_create_from_whatsapp === false) {
            return null; // Auto-criação via WhatsApp desativada para este pipeline
        }

        $stage = $pipeline->stages()->orderBy('position')->first();

        if (! $stage) {
            return null; // Pipeline sem estágios — não cria lead
        }

        return Lead::withoutGlobalScope('tenant')->create([
            'tenant_id'   => $tenantId,
            'name'        => $contactName ?? $phone,
            'phone'       => $phone,
            'source'      => 'whatsapp',
            'pipeline_id' => $pipeline->id,
            'stage_id'    => $stage->id,
        ]);
    }

    private function normalizePhone(string $from, array $msg = [], bool $isFromMe = false): string
    {
        // WAHA GOWS engine sometimes identifies contacts via LID (internal WhatsApp numeric ID)
        // instead of their real phone number. When this happens, Chat/Sender arrive as
        // "83296646115442@lid" but SenderAlt contains the real JID:
        //   _data.Info.Chat:      "83296646115442@lid"          ← LID (internal ID)
        //   _data.Info.SenderAlt: "556181749938@s.whatsapp.net" ← real phone
        //
        // Discriminator: any JID ending in "@lid" is an internal WhatsApp ID, not a phone.
        // Any JID ending in "@c.us", "@s.whatsapp.net", etc. is a real phone number.
        //
        // Check candidates in order; skip LIDs and group/broadcast JIDs; return the first
        // real phone found. Works for any country (not restricted to Brazilian numbers).
        $info = $msg['_data']['Info'] ?? [];

        $chatJid    = $info['Chat'] ?? '';
        $candidates = [
            $chatJid,                                                                    // conversation JID — contact in 1:1 chats
            str_ends_with($chatJid, '@lid') ? ($info['SenderAlt'] ?? '') : '',          // SenderAlt only when Chat is LID (not for groups)
            $from,                                                                       // pre-processed in handleInbound() for fromMe messages
        ];

        foreach ($candidates as $jid) {
            if (! $jid
                || str_contains($jid, '@g.us')
                || str_contains($jid, '@broadcast')
                || str_ends_with($jid, '@lid')   // LID = internal WhatsApp ID, skip
            ) {
                continue;
            }

            // Strip @server-suffix and optional :device-id — keep only the phone digits
            $digits = (string) preg_replace('/[:@].+$/u', '', $jid);
            if ($digits) {
                return $digits;
            }
        }

        // All candidates were LIDs — last resort: strip suffix from 'from' as-is
        return (string) preg_replace('/[:@].+$/u', '', $from) ?: $from;
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
        // Strip MIME parameters (e.g. "audio/ogg;codecs=opus" → "audio/ogg")
        $base = trim(explode(';', $mime)[0]);

        return match ($base) {
            'image/jpeg'                => 'jpg',
            'image/png'                 => 'png',
            'image/gif'                 => 'gif',
            'image/webp'                => 'webp',
            'audio/ogg'                 => 'ogg',
            'audio/opus'                => 'ogg',   // Whisper não aceita .opus, usar .ogg
            'audio/mpeg'                => 'mp3',
            'audio/webm'                => 'webm',
            'audio/mp4'                 => 'm4a',
            'audio/aac'                 => 'm4a',
            'video/mp4'                 => 'mp4',
            'application/pdf'           => 'pdf',
            'application/octet-stream'  => 'bin',
            default                     => explode('/', $base)[1] ?? 'ogg',
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
