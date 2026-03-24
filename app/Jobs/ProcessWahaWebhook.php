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
use App\Models\WhatsappButton;
use App\Models\WhatsappButtonClick;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Models\Tenant;
use App\Services\AutomationEngine;
use App\Services\PlanLimitChecker;
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
        // O parceiro da conversa está em chatId / to / _data.Info.Chat / RecipientAlt.
        // Grupos: 'from' já é o JID do grupo (@g.us) mesmo em mensagens fromMe — não sobrescrever.
        if ($isFromMe && ! str_contains($from, '@g.us')) {
            $info = $msg['_data']['Info'] ?? [];

            // Candidatos para o JID do destinatário, em ordem de confiabilidade
            $candidates = [
                $msg['chatId']          ?? '',
                $msg['to']              ?? '',
                $info['Chat']           ?? '',
                $info['RecipientAlt']   ?? '',
                $info['SenderAlt']      ?? '',
            ];

            // Usar o primeiro candidato que NÃO seja @lid e NÃO seja nosso telefone
            $myPhone  = (string) preg_replace('/[:@].+$/', '', $msg['from'] ?? '');
            $resolved = null;
            $firstLid = null;
            foreach ($candidates as $c) {
                if (! $c || str_contains($c, '@g.us') || str_contains($c, 'broadcast')) {
                    continue;
                }
                if (str_ends_with($c, '@lid')) {
                    $firstLid ??= $c;
                    continue;
                }
                $cPhone = (string) preg_replace('/[:@].+$/', '', $c);
                if ($cPhone && $cPhone !== $myPhone) {
                    $resolved = $c;
                    break;
                }
            }

            if ($resolved) {
                $from = $resolved;
            } elseif ($firstLid) {
                // Todos candidatos reais falharam — usar @lid (fallbacks posteriores resolverão)
                $from = $firstLid;
            }
            // Se nenhum candidato válido, $from fica inalterado (nosso telefone — Fix 3 bloqueará)
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

        // Detectar se $from é um JID @lid — sinal definitivo de LID independente do comprimento.
        $fromIsLid = ! $isGroup && str_ends_with($from, '@lid');

        $phone = $this->normalizePhone($from, $msg, $isFromMe);

        // Extrair LID numérico da mensagem (para mapeamento LID↔phone persistente).
        // Movido para ANTES da resolução para que $currentLid esteja disponível no blocker.
        $currentLid = null;
        if (! $isGroup) {
            $info = $msg['_data']['Info'] ?? [];
            foreach ([$msg['from'] ?? '', $info['Chat'] ?? '', $info['Sender'] ?? '', $msg['participant'] ?? ''] as $lidCandidate) {
                if ($lidCandidate && str_ends_with($lidCandidate, '@lid')) {
                    $currentLid = (string) preg_replace('/[:@].+$/', '', $lidCandidate);
                    break;
                }
            }
        }

        // Se o phone parece um LID numérico, tentar resolver para o número real.
        // Usa $fromIsLid (sufixo @lid) como sinal definitivo — captura LIDs de 13 dígitos
        // que o antigo check strlen>13 não pegava.
        if (! $isGroup && ctype_digit($phone) && ($fromIsLid || strlen($phone) > 13)) {
            try {
                $wahaLid = new \App\Services\WahaService($instance->session_name);

                // 1) Endpoint dedicado: GET /api/{session}/lids/{lid}
                $lidResult     = $wahaLid->getPhoneByLid($phone . '@lid');
                $resolvedPhone = $lidResult['phoneNumber'] ?? $lidResult['phone'] ?? $lidResult['chatId'] ?? null;
                if ($resolvedPhone) {
                    $resolved = ltrim((string) preg_replace('/[:@].+$/', '', $resolvedPhone), '+');
                    if ($resolved && ctype_digit($resolved) && strlen($resolved) <= 15) {
                        Log::channel('whatsapp')->info('LID resolvido via /lids endpoint', [
                            'lid'      => $phone,
                            'resolved' => $resolved,
                        ]);
                        $phone = $resolved;
                    }
                }

                // 2) Fallback: contacts API (método antigo)
                if (ctype_digit($phone) && ($fromIsLid || strlen($phone) > 13)) {
                    $contactInfo = $wahaLid->getContactInfo($this->normalizeJidForApi($from));
                    $resolvedJid = $contactInfo['id'] ?? '';
                    if ($resolvedJid && ! str_ends_with($resolvedJid, '@lid')) {
                        $resolved = (string) preg_replace('/[:@].+$/', '', $resolvedJid);
                        if ($resolved && ctype_digit($resolved)) {
                            Log::channel('whatsapp')->info('LID resolvido via contacts API (fallback)', [
                                'lid'      => $phone,
                                'resolved' => $resolved,
                            ]);
                            $phone = $resolved;
                        }
                    }
                }
            } catch (\Throwable) {}
        }

        // BLOQUEAR: se phone continua sendo o LID (resolução falhou).
        // Se $currentLid existe e phone != currentLid → resolução funcionou, NÃO bloquear.
        $phoneStillLid = $currentLid && $phone === $currentLid;
        $phoneTooLong  = strlen($phone) > 13 && ctype_digit($phone);
        if (! $isGroup && ! empty($phone) && ($phoneStillLid || $phoneTooLong)) {
            Log::channel('whatsapp')->info('BLOQUEADO: LID não resolvido — conversa ignorada', [
                'phone'  => $phone,
                'from'   => $from,
                'msg_id' => $msg['id'] ?? null,
            ]);
            return;
        }

        Log::channel('whatsapp')->info('Processando mensagem', [
            'event'  => $event,
            'from'   => $from,
            'phone'  => $phone,
            'lid'    => $currentLid,
            'msg_id' => $msg['id'] ?? null,
        ]);

        $conversation = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $instance->tenant_id)
            ->where('phone', $phone)
            ->first();

        // Fallback: buscar conversa pela coluna `lid` (mapeamento persistente LID↔phone)
        if (! $conversation && $currentLid) {
            $conversation = WhatsappConversation::withoutGlobalScope('tenant')
                ->where('tenant_id', $instance->tenant_id)
                ->where('lid', $currentLid)
                ->first();

            if ($conversation && $conversation->phone !== $phone) {
                Log::channel('whatsapp')->info('Conversa encontrada via lid — migrando phone', [
                    'conversation_id' => $conversation->id,
                    'old_phone'       => $conversation->phone,
                    'new_phone'       => $phone,
                    'lid'             => $currentLid,
                ]);
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conversation->id)
                    ->update(['phone' => $phone]);
                $conversation->phone = $phone;
            }
        }

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

            // Fallback: conversa pode ter sido criada com phone = LID numérico
            if (! $conversation) {
                $conv = WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('tenant_id', $instance->tenant_id)
                    ->where('phone', $lidNumeric)
                    ->first();
                if ($conv) {
                    $conversation = $conv;
                    $phone        = $conv->phone;
                    Log::channel('whatsapp')->info('fromMe @lid: conversa encontrada por phone=LID', [
                        'conversation_id' => $conv->id,
                        'lid'             => $lidNumeric,
                    ]);
                }
            }
        }

        // Fallback fromMe genérico: buscar conversa via chatId do payload
        // (chatId pode ser @lid mas corresponde a uma conversa criada com esse LID como phone)
        if (! $conversation && $isFromMe) {
            $payloadChatId = $msg['chatId'] ?? '';
            if ($payloadChatId) {
                $chatIdPhone = (string) preg_replace('/[:@].+$/', '', $payloadChatId);
                if ($chatIdPhone && $chatIdPhone !== $phone) {
                    $conv = WhatsappConversation::withoutGlobalScope('tenant')
                        ->where('tenant_id', $instance->tenant_id)
                        ->where('phone', $chatIdPhone)
                        ->first();
                    if ($conv) {
                        $conversation = $conv;
                        $phone        = $conv->phone;
                        Log::channel('whatsapp')->info('fromMe: conversa encontrada via chatId payload', [
                            'conversation_id' => $conv->id,
                            'chatId'          => $payloadChatId,
                            'phone'           => $phone,
                        ]);
                    }
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

            // Fallback: buscar via GET /api/{session}/groups/{id} (retorna 'Name' no GOWS)
            if (! $contactName) {
                try {
                    $wahaForGroup = new \App\Services\WahaService($instance->session_name);
                    $groupInfo    = $wahaForGroup->getGroupInfo($from);
                    $contactName  = $groupInfo['Name'] ?? $groupInfo['subject'] ?? $groupInfo['name'] ?? null;
                } catch (\Throwable $e) {
                    Log::channel('whatsapp')->warning('getGroupInfo falhou (create)', ['from' => $from, 'error' => $e->getMessage()]);
                }
            }

            $messageSenderName = $msg['_data']['Info']['PushName']
                ?? $msg['_data']['notifyName']
                ?? $msg['notifyName']
                ?? null;
        } else {
            if ($isFromMe) {
                // pushName em mensagens fromMe = nome da conta conectada (não do cliente).
                // Buscar o nome real via WAHA /api/contacts, que retorna o nome da agenda.
                // Quando $from é @lid, usar RecipientAlt (JID real do destinatário) para a busca.
                $contactName  = null;
                $jidForName   = $from;
                $recipientAlt = $msg['_data']['Info']['RecipientAlt'] ?? '';
                if (str_ends_with($from, '@lid') && $recipientAlt && ! str_ends_with($recipientAlt, '@lid')) {
                    $jidForName = $recipientAlt;
                }
                try {
                    $wahaForContact = new \App\Services\WahaService($instance->session_name);
                    $contactInfo    = $wahaForContact->getContactInfo($this->normalizeJidForApi($jidForName));
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

        // Segurança: se fromMe e phone resolveu para nosso próprio número,
        // tentar extrair o destinatário correto de campos alternativos
        if (! $conversation && $isFromMe && ! $isGroup) {
            $instancePhone = ltrim((string) $instance->phone_number, '+');
            if ($phone === $instancePhone) {
                // phone é nosso número — tentar chatId como última tentativa
                $fallbackChatId = $msg['chatId'] ?? '';
                $fallbackPhone  = (string) preg_replace('/[:@].+$/', '', $fallbackChatId);
                if ($fallbackPhone && $fallbackPhone !== $instancePhone) {
                    $phone = $fallbackPhone;
                    Log::channel('whatsapp')->warning('fromMe: phone era nosso número, corrigido via chatId', [
                        'old_phone' => $instancePhone,
                        'new_phone' => $phone,
                        'chatId'    => $fallbackChatId,
                    ]);
                    // Tentar encontrar conversa com o phone corrigido
                    $conversation = WhatsappConversation::withoutGlobalScope('tenant')
                        ->where('tenant_id', $instance->tenant_id)
                        ->where('phone', $phone)
                        ->first();
                } else {
                    Log::channel('whatsapp')->warning('fromMe: phone resolveu para nosso número e não há fallback', [
                        'phone' => $phone, 'chatId' => $fallbackChatId,
                    ]);
                }
            }
        }

        if (! $conversation) {
            // Tentar buscar foto de perfil do contato/grupo ao criar nova conversa
            $pictureUrl = null;
            try {
                $wahaForPic = new \App\Services\WahaService($instance->session_name);
                $pictureUrl = $wahaForPic->getChatPicture($from);
            } catch (\Throwable) {}

            $conversation = WhatsappConversation::withoutGlobalScope('tenant')->create([
                'tenant_id'           => $instance->tenant_id,
                'instance_id'         => $instance->id,
                'phone'               => $phone,
                'lid'                 => $currentLid,
                'is_group'            => $isGroup,
                'contact_name'        => $contactName ?: $phone,
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

            // Match UTMs de clique no botão WhatsApp (tracking code + fallback por tempo)
            if (! $isGroup) {
                $this->matchUtmsToConversation($conversation, $instance, $msg);
            }

            // Auto-assign: atribuir agente de IA automaticamente (apenas conversas individuais)
            if (! $isGroup) {
                $autoAgent = AiAgent::withoutGlobalScope('tenant')
                    ->where('tenant_id', $instance->tenant_id)
                    ->where('is_active', true)
                    ->where('auto_assign', true)
                    ->where('channel', 'whatsapp')
                    ->where(fn ($q) => $q
                        ->whereHas('whatsappInstances', fn ($r) => $r->where('whatsapp_instance_id', $instance->id))
                        ->orWhereDoesntHave('whatsappInstances'))
                    ->orderByRaw('(SELECT COUNT(*) FROM ai_agent_whatsapp_instance WHERE ai_agent_id = ai_agents.id) = 0')
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

            // Vincular UTMs via tracking code em conversas já existentes
            if (! $isGroup && ! $isFromMe) {
                $this->matchUtmsToConversation($conversation, $instance, $msg);
            }

            // Atualizar foto de perfil: URLs do WhatsApp expiram.
            // Re-fetch a cada 6h (throttle via Cache) ou quando ausente.
            $convUpdates = [];

            // Persistir LID se ainda não salvo (mapeamento LID↔phone)
            if ($currentLid && empty($conversation->lid)) {
                $convUpdates['lid'] = $currentLid;
            }
            $picCacheKey = "waha:pic_refresh:{$conversation->id}";
            if (! Cache::has($picCacheKey)) {
                try {
                    $wahaForPic  = new \App\Services\WahaService($instance->session_name);
                    $pic         = $wahaForPic->getChatPicture($from);
                    if ($pic && $pic !== $conversation->contact_picture_url) {
                        $convUpdates['contact_picture_url'] = $pic;
                    }
                    Cache::put($picCacheKey, 1, 21600); // 6 horas
                } catch (\Throwable) {
                    Cache::put($picCacheKey, 1, 3600); // 1h se falhou
                }
            }
            // Retry nome para contatos individuais sem nome ou com LID/phone como nome
            $nameIsPlaceholder = empty($conversation->contact_name)
                || $conversation->contact_name === $phone
                || ($currentLid && $conversation->contact_name === $currentLid)
                || (strlen($conversation->contact_name) > 13 && ctype_digit($conversation->contact_name));
            if (! $isGroup && $nameIsPlaceholder) {
                $resolvedName = $contactName; // extraído do payload desta mensagem

                if (! $resolvedName) {
                    try {
                        $jidForName   = $from;
                        $senderAlt    = $msg['_data']['Info']['SenderAlt']    ?? '';
                        $recipientAlt = $msg['_data']['Info']['RecipientAlt'] ?? '';
                        // Inbound com @lid: SenderAlt tem o JID real do remetente
                        if (str_ends_with($from, '@lid') && ! $isFromMe && $senderAlt && ! str_ends_with($senderAlt, '@lid')) {
                            $jidForName = $senderAlt;
                        }
                        // FromMe com @lid: RecipientAlt tem o JID real do destinatário
                        if (str_ends_with($from, '@lid') && $isFromMe && $recipientAlt && ! str_ends_with($recipientAlt, '@lid')) {
                            $jidForName = $recipientAlt;
                        }
                        $wahaContact  = new \App\Services\WahaService($instance->session_name);
                        $contactInfo  = $wahaContact->getContactInfo($this->normalizeJidForApi($jidForName));
                        $resolvedName = $contactInfo['name'] ?? $contactInfo['pushName'] ?? null;
                    } catch (\Throwable) {}
                }

                if ($resolvedName) {
                    $convUpdates['contact_name'] = $resolvedName;
                    // Atualizar lead vinculado se o nome ainda for phone ou LID (fallback original)
                    if ($conversation->lead_id) {
                        $leadNameQuery = Lead::withoutGlobalScope('tenant')
                            ->where('id', $conversation->lead_id)
                            ->where(function ($q) use ($phone, $currentLid) {
                                $q->where('name', $phone);
                                if ($currentLid) {
                                    $q->orWhere('name', $currentLid);
                                }
                                // Nome que parece LID (>13 dígitos, apenas números)
                                $q->orWhereRaw("LENGTH(name) > 13 AND name REGEXP '^[0-9]+$'");
                            });
                        $leadNameQuery->update(['name' => $resolvedName]);
                    }
                }
            }
            if ($isGroup && empty($conversation->contact_name)) {
                $resolvedGroupName = $contactName; // nome extraído do payload (pode ser null)

                if (! $resolvedGroupName) {
                    // Payload não trouxe nome → tentar buscar via WAHA API (GOWS retorna 'Name')
                    try {
                        $wahaRetry         = new \App\Services\WahaService($instance->session_name);
                        $retryInfo         = $wahaRetry->getGroupInfo($from);
                        $resolvedGroupName = $retryInfo['Name'] ?? $retryInfo['subject'] ?? $retryInfo['name'] ?? null;
                    } catch (\Throwable $e) {
                        Log::channel('whatsapp')->warning('getGroupInfo falhou (retry)', ['from' => $from, 'error' => $e->getMessage()]);
                    }
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
            $leadExisted = Lead::withoutGlobalScope('tenant')
                ->where('tenant_id', $instance->tenant_id)
                ->where('phone', $phone)
                ->exists();

            $lead = $this->findOrCreateLead($instance->tenant_id, $phone, $contactName, $conversation);
            if ($lead) {
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conversation->id)
                    ->update(['lead_id' => $lead->id]);
                $conversation->lead_id = $lead->id;
                Log::channel('whatsapp')->info('Lead vinculado', ['lead_id' => $lead->id, 'phone' => $phone]);

                // Disparar automação lead_created (com conversation no contexto para envio de msg)
                if (! $leadExisted) {
                    try {
                        (new AutomationEngine())->run('lead_created', [
                            'tenant_id'    => $instance->tenant_id,
                            'channel'      => 'whatsapp',
                            'lead'         => $lead,
                            'conversation' => $conversation,
                        ]);
                    } catch (\Throwable) {}
                }
            } else {
                Log::channel('whatsapp')->warning('Lead não criado — tenant sem pipeline configurado', ['phone' => $phone]);
            }
        }

        [$type, $mediaUrl, $mediaMime, $mediaFilename] = $this->extractMedia($msg);

        $body = $msg['body'] ?? $msg['caption'] ?? null;

        // Strip tracking code (#XXXXXX) do body para não exibir no chat
        if ($body && preg_match('/\s*#[A-HJ-NP-Z2-9]{6}\s*$/', $body)) {
            $body = rtrim(preg_replace('/\s*#[A-HJ-NP-Z2-9]{6}\s*$/', '', $body)) ?: $body;
        }

        // Detectar mensagem de localização (WAHA envia como text sem hasMedia)
        if ($type === 'text') {
            $wahaType = $msg['_data']['type'] ?? $msg['type'] ?? null;
            if ($wahaType === 'location' || isset($msg['location'])) {
                $type = 'location';
                $lat  = $msg['_data']['lat']  ?? $msg['location']['latitude']  ?? null;
                $lng  = $msg['_data']['lng']  ?? $msg['location']['longitude'] ?? null;
                $addr = $msg['_data']['loc']  ?? $msg['location']['address']   ?? '';
                if ($lat && $lng) {
                    $body = ($addr ? "Localização: {$addr}\n" : '')
                          . "Coordenadas: {$lat}, {$lng}\n"
                          . "https://maps.google.com/?q={$lat},{$lng}";
                }
            }
        }

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

        // Auto-import REMOVIDO — importação só ocorre via ação manual do cliente
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Encontra um Lead pelo telefone ou cria um novo vinculado ao pipeline padrão.
     * Retorna null se não houver pipeline configurado para o tenant.
     */
    private function findOrCreateLead(int $tenantId, string $phone, ?string $contactName, ?WhatsappConversation $conversation = null): ?Lead
    {
        // Tenta encontrar lead existente com o mesmo telefone
        $lead = Lead::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('phone', $phone)
            ->first();

        // Fallback: mesmo contato pode ter sido salvo com formato de phone diferente
        // (ex: LID numérico vs número real). Verificar via WhatsappConversation vinculada.
        if (! $lead) {
            $linkedConv = WhatsappConversation::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('phone', $phone)
                ->whereNotNull('lead_id')
                ->first();
            if ($linkedConv) {
                $lead = Lead::withoutGlobalScope('tenant')->find($linkedConv->lead_id);
            }
        }

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

        $tenant = Tenant::find($tenantId);
        if ($tenant) {
            $limitMsg = PlanLimitChecker::check('leads', $tenant);
            if ($limitMsg) {
                Log::channel('whatsapp')->info("Lead não criado (limite do plano): {$phone} tenant={$tenantId}");
                return null;
            }
        }

        $stage = $pipeline->stages()->orderBy('position')->first();

        if (! $stage) {
            return null; // Pipeline sem estágios — não cria lead
        }

        $leadData = [
            'tenant_id'   => $tenantId,
            'name'        => $contactName ?? $phone,
            'phone'       => $phone,
            'source'      => 'whatsapp',
            'pipeline_id' => $pipeline->id,
            'stage_id'    => $stage->id,
        ];

        // Copiar UTMs da conversa para o lead (vieram do botão WA)
        if ($conversation) {
            foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'gclid'] as $f) {
                if (! empty($conversation->{$f})) {
                    $leadData[$f] = $conversation->{$f};
                }
            }
        }

        return Lead::withoutGlobalScope('tenant')->create($leadData);
    }

    private function normalizePhone(string $from, array $msg = [], bool $isFromMe = false): string
    {
        // WAHA GOWS engine identifies contacts via LID (internal WhatsApp numeric ID)
        // instead of their real phone number. When this happens, Chat/Sender arrive as
        // "83296646115442@lid" but SenderAlt/RecipientAlt contain the real JID:
        //   _data.Info.Chat:         "83296646115442@lid"          ← LID (internal ID)
        //   _data.Info.SenderAlt:    "556181749938@s.whatsapp.net" ← real phone (inbound)
        //   _data.Info.RecipientAlt: "556181749938@s.whatsapp.net" ← real phone (fromMe)
        //
        // Priority: Alt fields first (most reliable), then Chat, then $from (pre-processed).
        $info = $msg['_data']['Info'] ?? [];

        $chatJid      = $info['Chat']         ?? '';
        $senderAlt    = $info['SenderAlt']    ?? '';
        $recipientAlt = $info['RecipientAlt'] ?? '';
        $chatIsLid    = str_ends_with($chatJid, '@lid');

        // Build candidates: Alt fields FIRST when Chat is LID (they have the real phone)
        $candidates = [];
        if ($chatIsLid && ! $isFromMe && $senderAlt) {
            $candidates[] = $senderAlt;       // inbound: SenderAlt = real phone of sender
        }
        if ($chatIsLid && $isFromMe && $recipientAlt) {
            $candidates[] = $recipientAlt;    // fromMe: RecipientAlt = real phone of recipient
        }
        $candidates[] = $chatJid;             // conversation JID — contact in 1:1 chats
        $candidates[] = $from;                // pre-processed in handleInbound() for fromMe

        foreach ($candidates as $jid) {
            if (! $jid
                || str_contains($jid, '@g.us')
                || str_contains($jid, '@broadcast')
                || str_ends_with($jid, '@lid')
            ) {
                continue;
            }

            $digits = (string) preg_replace('/[:@].+$/u', '', $jid);
            if ($digits) {
                return $digits;
            }
        }

        // All candidates were LIDs — last resort: strip suffix from 'from' as-is
        return (string) preg_replace('/[:@].+$/u', '', $from) ?: $from;
    }

    /**
     * Normaliza JID para formato @c.us aceito pela WAHA API.
     * "556192008997@s.whatsapp.net" → "556192008997@c.us"
     * "556192008997@c.us" → inalterado
     * "36576092528787@lid" → inalterado (API tenta resolver)
     */
    private function normalizeJidForApi(string $jid): string
    {
        return (string) preg_replace('/@s\.whatsapp\.net$/', '@c.us', $jid);
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

    /**
     * Match UTMs de clique no botão WhatsApp à conversa.
     * Fase 1: match direto por tracking_code (preciso).
     * Fase 2: fallback por janela de 15 minutos (quando usuário apaga o código).
     */
    private function matchUtmsToConversation(
        WhatsappConversation $conversation,
        WhatsappInstance $instance,
        array $msg,
    ): void {
        try {
            $rawBody = $msg['body'] ?? $msg['caption'] ?? '';
            $trackingCode = null;
            if ($rawBody && preg_match('/#([A-HJ-NP-Z2-9]{6})\s*$/', $rawBody, $m)) {
                $trackingCode = $m[1];
            }

            $recentClick = null;

            // Fase 1: match preciso por tracking code
            if ($trackingCode) {
                $recentClick = WhatsappButtonClick::withoutGlobalScope('tenant')
                    ->where('tracking_code', $trackingCode)
                    ->where('tenant_id', $instance->tenant_id)
                    ->where('matched', false)
                    ->first();
            }

            // Fase 2: fallback por janela de tempo (15 min)
            if (! $recentClick) {
                $recentClick = WhatsappButtonClick::withoutGlobalScope('tenant')
                    ->where('tenant_id', $instance->tenant_id)
                    ->where('clicked_at', '>=', now()->subMinutes(15))
                    ->where('matched', false)
                    ->whereHas('button', fn ($q) => $q->where('tenant_id', $instance->tenant_id))
                    ->orderByDesc('clicked_at')
                    ->first();
            }

            if ($recentClick) {
                $utmFields = array_filter([
                    'utm_source'   => $recentClick->utm_source,
                    'utm_medium'   => $recentClick->utm_medium,
                    'utm_campaign' => $recentClick->utm_campaign,
                    'utm_content'  => $recentClick->utm_content,
                    'utm_term'     => $recentClick->utm_term,
                    'fbclid'       => $recentClick->fbclid,
                    'gclid'        => $recentClick->gclid,
                ]);
                if (! empty($utmFields)) {
                    WhatsappConversation::withoutGlobalScope('tenant')
                        ->where('id', $conversation->id)
                        ->update($utmFields);
                    foreach ($utmFields as $k => $v) {
                        $conversation->$k = $v;
                    }
                }

                // Marcar click como matched
                WhatsappButtonClick::withoutGlobalScope('tenant')
                    ->where('id', $recentClick->id)
                    ->update(['matched' => true]);

                Log::channel('whatsapp')->info('UTMs do botão WA vinculados à conversa', [
                    'conversation_id' => $conversation->id,
                    'click_id'        => $recentClick->id,
                    'tracking_code'   => $trackingCode,
                    'match_type'      => $trackingCode ? 'code' : 'time_window',
                    'utm_source'      => $recentClick->utm_source,
                ]);
            }
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('Erro ao vincular UTMs do botão', ['error' => $e->getMessage()]);
        }
    }
}
