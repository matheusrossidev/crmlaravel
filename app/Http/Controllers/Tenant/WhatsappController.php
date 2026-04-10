<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\ChatbotFlow;
use App\Models\Lead;
use App\Models\InstagramConversation;
use App\Models\InstagramInstance;
use App\Models\InstagramMessage;
use App\Models\Pipeline;
use App\Models\User;
use App\Models\WebsiteConversation;
use App\Models\WebsiteMessage;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Models\WhatsappQuickMessage;
use App\Models\WhatsappTag;
use App\Models\Department;
use App\Services\ConversationResolver;
use App\Services\InstagramService;
use App\Support\TenantCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Jobs\ProcessAiResponse;
use App\Jobs\SummarizeConversation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class WhatsappController extends Controller
{
    public function index(): View
    {
        $allInstances  = WhatsappInstance::orderBy('id')->get();
        $instance      = $allInstances->first();
        $waConnected   = $allInstances->where('status', 'connected')->isNotEmpty();
        $igConnected   = InstagramInstance::where('status', 'connected')->exists();
        $connected     = $waConnected || $igConnected;
        $instanceCount = $allInstances->count();

        $conversations   = [];
        $igConversations = collect();
        $users           = [];
        $pipelines       = [];
        $whatsappTags    = collect();

        $authUser = auth()->user();
        $restrictByDept = !$authUser->isAdmin();
        $userDeptIds = $restrictByDept ? $authUser->departments()->pluck('departments.id') : collect();

        if ($connected) {
            $waQuery = WhatsappConversation::with(['latestMessage', 'assignedUser', 'department', 'instance:id,label,phone_number']);
            // Visibilidade por instancia (pivot user_whatsapp_instance) +
            // fallback de department/assigned_user_id pra users sem nenhuma
            // instancia atribuida.
            $waQuery->visibleToUser($authUser);
            if ($restrictByDept && $authUser->allowedWhatsappInstanceIds() === null) {
                // Esse user nao tem instancias atribuidas — mantem o filtro
                // antigo de department + assigned. Se ele TEM instancias, o
                // scope visibleToUser ja resolveu (com OR de assigned + dept).
                $waQuery->where(function ($q) use ($authUser, $userDeptIds) {
                    $q->whereIn('department_id', $userDeptIds)
                      ->orWhereNull('department_id')
                      ->orWhere('assigned_user_id', $authUser->id);
                });
            }
            $conversations = $waQuery->orderByDesc('last_message_at')->get();

            $users = TenantCache::remember('config:users', 7200, fn () =>
                User::where('tenant_id', $authUser->tenant_id)->orderBy('name')->get(['id', 'name', 'avatar'])
            );

            $pipelines = TenantCache::remember('config:pipelines', 3600, fn () =>
                Pipeline::with('stages:id,pipeline_id,name,position')->orderBy('sort_order')
                    ->get(['id', 'name', 'is_default'])
            );

            $whatsappTags = TenantCache::remember('config:waTags', 3600, fn () =>
                WhatsappTag::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'color'])
            );
        }

        // Carregar conversas Instagram independente do WhatsApp estar conectado
        $igInstance = InstagramInstance::first();
        if ($igInstance && $igInstance->status === 'connected') {
            $igQuery = InstagramConversation::with(['latestMessage', 'department']);
            if ($restrictByDept) {
                $igQuery->where(function ($q) use ($authUser, $userDeptIds) {
                    $q->whereIn('department_id', $userDeptIds)
                      ->orWhereNull('department_id')
                      ->orWhere('assigned_user_id', $authUser->id);
                });
            }
            $igConversations = $igQuery->orderByDesc('last_message_at')->get();
        }

        // Unificar e ordenar por data (WhatsApp + Instagram)
        $allConversations = collect();
        foreach ($conversations as $c) {
            $c->_channel = 'whatsapp';
            $allConversations->push($c);
        }
        foreach ($igConversations as $c) {
            $c->_channel = 'instagram';
            $allConversations->push($c);
        }
        $allConversations = $allConversations->sortByDesc('last_message_at')->values();

        $aiAgents = TenantCache::remember('config:aiAgents', 1800, fn () =>
            AiAgent::where('is_active', true)->orderBy('name')->get(['id', 'name'])
        );

        $chatbotFlows = TenantCache::remember('config:chatbotFlows', 600, fn () =>
            ChatbotFlow::where('is_active', true)->whereIn('channel', ['whatsapp', 'instagram'])
                ->orderBy('name')->get(['id', 'name', 'channel'])
        );

        $quickMessages = TenantCache::remember('config:quickMessages', 3600, fn () =>
            WhatsappQuickMessage::orderBy('sort_order')->orderBy('title')
                ->get(['id', 'title', 'body'])->toArray()
        );

        $isPartnerView = session()->has('impersonating_tenant_id');

        $departments = TenantCache::remember('config:departments', 3600, fn () =>
            Department::where('is_active', true)->orderBy('name')->get(['id', 'name', 'color', 'icon'])
        );

        return view('tenant.whatsapp.index', compact('instance', 'connected', 'conversations', 'igConversations', 'allConversations', 'users', 'pipelines', 'whatsappTags', 'aiAgents', 'chatbotFlows', 'quickMessages', 'isPartnerView', 'departments', 'instanceCount'));
    }

    // ── Instagram Conversations ───────────────────────────────────────────────

    public function showInstagram(InstagramConversation $conversation): JsonResponse
    {
        $conversation->load(['lead.pipeline', 'lead.stage']);

        $messages = InstagramMessage::where('conversation_id', $conversation->id)
            ->with(['user:id,name', 'sentByAgent:id,name,display_avatar'])
            ->orderBy('sent_at')->orderBy('id')
            ->get()
            ->map(fn ($m) => [
                'id'            => $m->id,
                'direction'     => $m->direction,
                'type'          => $m->type,
                'body'          => $m->body,
                'media_url'     => $m->media_url,
                'ack'           => $m->ack,
                'sent_at'       => $m->sent_at?->toISOString(),
                'user_name'     => $m->user?->name,
                'sent_by'       => $m->sent_by,
                'sent_by_agent' => $m->sentByAgent ? [
                    'id'     => $m->sentByAgent->id,
                    'name'   => $m->sentByAgent->name,
                    'avatar' => $m->sentByAgent->display_avatar,
                ] : null,
            ]);

        $lead = null;
        if ($conversation->lead) {
            $l    = $conversation->lead;
            $lead = [
                'id'            => $l->id,
                'name'          => $l->name,
                'phone'         => $l->phone,
                'email'         => $l->email,
                'value'         => $l->value,
                'pipeline_id'   => $l->pipeline_id,
                'pipeline_name' => $l->pipeline?->name,
                'stage_id'      => $l->stage_id,
                'stage_name'    => $l->stage?->name,
                'source'        => $l->source,
            ];
        }

        return response()->json([
            'messages'            => $messages,
            'lead'                => $lead,
            'assigned_user_id'    => $conversation->assigned_user_id,
            'ai_agent_id'         => $conversation->ai_agent_id,
            'chatbot_flow_id'     => null,
            'tags'                => $conversation->tags ?? [],
            'contact_name'        => $conversation->contact_name,
            'contact_picture_url' => $conversation->contact_picture_url,
            'phone'               => '@' . ltrim($conversation->contact_username ?? '', '@'),
            'is_group'            => false,
        ]);
    }

    public function markReadInstagram(InstagramConversation $conversation): JsonResponse
    {
        $conversation->update(['unread_count' => 0]);
        return response()->json(['success' => true]);
    }

    public function sendInstagramMessage(InstagramConversation $conversation, Request $request): JsonResponse
    {
        // Parceiros só podem visualizar
        if (session()->has('impersonating_tenant_id')) {
            return response()->json(['error' => 'Acesso somente leitura para agências parceiras.'], 403);
        }

        $body = $request->input('body', '');
        if (! $body) {
            return response()->json(['error' => 'Mensagem vazia'], 422);
        }

        $instance = InstagramInstance::first();
        if (! $instance || $instance->status !== 'connected') {
            return response()->json(['error' => 'Instagram não conectado'], 422);
        }

        try {
            $service = new InstagramService(decrypt($instance->access_token));
            $result = $service->sendMessage($conversation->igsid, $body);

            if (! empty($result['error'])) {
                Log::channel('instagram')->error('Falha ao enviar DM', [
                    'igsid'  => $conversation->igsid,
                    'status' => $result['status'] ?? null,
                    'body'   => $result['body'] ?? null,
                ]);

                // Parse Facebook error for user-friendly message
                $errorBody = $result['body'] ?? '';
                $friendlyMsg = 'Falha ao enviar mensagem.';
                if (str_contains($errorBody, '"code":190') || str_contains($errorBody, 'OAuthException') || str_contains($errorBody, 'access token')) {
                    $friendlyMsg = 'Sessão do Instagram expirada. Reconecte sua conta em Configurações > Integrações.';
                    $instance->update(['status' => 'expired']);
                } elseif (str_contains($errorBody, '"code":10')) {
                    $friendlyMsg = 'Permissão negada pelo Instagram. Reconecte a conta com as permissões necessárias.';
                } elseif (str_contains($errorBody, 'rate limit') || str_contains($errorBody, '"code":4')) {
                    $friendlyMsg = 'Limite de envio do Instagram atingido. Tente novamente em alguns minutos.';
                }

                return response()->json(['error' => $friendlyMsg], 422);
            }
        } catch (\Throwable $e) {
            Log::channel('instagram')->error('Falha ao enviar mensagem', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Falha ao enviar: ' . $e->getMessage()], 500);
        }

        $message = InstagramMessage::create([
            'tenant_id'       => $conversation->tenant_id,
            'conversation_id' => $conversation->id,
            'ig_message_id'   => null,
            'direction'       => 'outbound',
            'type'            => 'text',
            'body'            => $body,
            'user_id'         => auth()->id(),
            'sent_by'         => 'human',
            'ack'             => 'sent',
            'sent_at'         => now(),
        ]);

        InstagramConversation::where('id', $conversation->id)
            ->update(['last_message_at' => now(), 'status' => 'open', 'closed_at' => null]);

        return response()->json([
            'success' => true,
            'message' => [
                'id'        => $message->id,
                'direction' => 'outbound',
                'type'      => 'text',
                'body'      => $body,
                'media_url' => null,
                'ack'       => 'sent',
                'sent_at'   => $message->sent_at->toISOString(),
            ],
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        // Parse $since with timezone awareness (browser sends UTC ISO, e.g. "2026-02-20T03:31:31.000Z").
        // Carbon::parse handles any ISO 8601 format and utc() normalises to UTC for MySQL TIMESTAMP comparison.
        $sinceRaw = $request->input('since');
        $since    = $sinceRaw
            ? \Carbon\Carbon::parse($sinceRaw)->utc()
            : now()->utc()->subMinutes(1);

        // Recua 2 segundos para cobrir possível divergência de relógio entre browser e servidor,
        // e também a perda de precisão sub-segundo (MySQL TIMESTAMP armazena só segundos).
        // O frontend usa renderedMsgIds (Set) para evitar duplicatas.
        $since = $since->subSeconds(2);

        $convId      = $request->input('conversation_id');
        $convChannel = $request->input('conv_channel', 'whatsapp');

        $newMessages = [];
        if ($convId) {
            if ($convChannel === 'instagram') {
                $newMessages = InstagramMessage::where('conversation_id', $convId)
                    ->where('created_at', '>=', $since)
                    ->with(['user:id,name', 'sentByAgent:id,name,display_avatar'])
                    ->orderBy('sent_at')->orderBy('id')
                    ->get()
                    ->map(fn ($m) => [
                        'id'            => $m->id,
                        'direction'     => $m->direction,
                        'type'          => $m->type,
                        'body'          => $m->body,
                        'media_url'     => $m->media_url,
                        'ack'           => $m->ack,
                        'is_deleted'    => false,
                        'sent_at'       => $m->sent_at?->toISOString(),
                        'user_name'     => $m->user?->name,
                        'sent_by'       => $m->sent_by,
                        'sent_by_agent' => $m->sentByAgent ? [
                            'id'     => $m->sentByAgent->id,
                            'name'   => $m->sentByAgent->name,
                            'avatar' => $m->sentByAgent->display_avatar,
                        ] : null,
                    ]);
            } elseif ($convChannel === 'website') {
                $newMessages = WebsiteMessage::where('conversation_id', $convId)
                    ->where('sent_at', '>=', $since)
                    ->orderBy('sent_at')->orderBy('id')
                    ->get()
                    ->map(fn ($m) => [
                        'id'         => $m->id,
                        'direction'  => $m->direction,
                        'type'       => 'text',
                        'body'       => $m->content,
                        'media_url'  => null,
                        'ack'        => null,
                        'is_deleted' => false,
                        'sent_at'    => $m->sent_at?->toISOString(),
                    ]);
            } else {
                $newMessages = WhatsappMessage::where('conversation_id', $convId)
                    ->where('created_at', '>=', $since)
                    ->with(['user:id,name', 'sentByAgent:id,name,display_avatar'])
                    ->orderBy('sent_at')->orderBy('id')
                    ->get()
                    ->map(fn ($m) => $this->formatMessage($m));
            }
        }

        $pollUser = auth()->user();
        $pollRestrict = !$pollUser->isAdmin();
        $pollDeptIds  = $pollRestrict ? $pollUser->departments()->pluck('departments.id') : collect();

        $waQuery = WhatsappConversation::with(['latestMessage', 'assignedUser', 'department', 'instance:id,label,phone_number'])
            ->where('last_message_at', '>=', $since);
        if ($pollRestrict) {
            $waQuery->where(function ($q) use ($pollUser, $pollDeptIds) {
                $q->whereIn('department_id', $pollDeptIds)
                  ->orWhereNull('department_id')
                  ->orWhere('assigned_user_id', $pollUser->id);
            });
        }
        $updatedWaConvs = $waQuery->orderByDesc('last_message_at')
            ->get()
            ->map(fn ($c) => $this->formatConversation($c));

        $igQuery = InstagramConversation::with(['latestMessage', 'department'])
            ->where('last_message_at', '>=', $since);
        if ($pollRestrict) {
            $igQuery->where(function ($q) use ($pollUser, $pollDeptIds) {
                $q->whereIn('department_id', $pollDeptIds)
                  ->orWhereNull('department_id')
                  ->orWhere('assigned_user_id', $pollUser->id);
            });
        }
        $updatedIgConvs = $igQuery->orderByDesc('last_message_at')
            ->get()
            ->map(fn ($c) => $this->formatInstagramConversation($c));

        $updatedWebConvs = WebsiteConversation::with(['latestMessage', 'lead'])
            ->where('last_message_at', '>=', $since)
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn ($c) => $this->formatWebsiteConversation($c));

        $updatedConvs = $updatedWaConvs->concat($updatedIgConvs)->concat($updatedWebConvs)
            ->sortByDesc('last_message_at')
            ->values();

        return response()->json([
            'new_messages'          => $newMessages,
            'conversations_updated' => $updatedConvs,
            'now'                   => now()->utc()->toISOString(),
        ]);
    }

    public function show(WhatsappConversation $conversation): JsonResponse
    {
        $conversation->load(['lead.pipeline', 'lead.stage']);

        $messages = WhatsappMessage::where('conversation_id', $conversation->id)
            ->with(['user:id,name', 'sentByAgent:id,name,display_avatar'])
            ->orderBy('sent_at')->orderBy('id')
            ->get()
            ->map(fn ($m) => $this->formatMessage($m));

        $lead = null;
        if ($conversation->lead) {
            $l    = $conversation->lead;
            $lead = [
                'id'            => $l->id,
                'name'          => $l->name,
                'phone'         => $l->phone,
                'email'         => $l->email,
                'value'         => $l->value,
                'pipeline_id'   => $l->pipeline_id,
                'pipeline_name' => $l->pipeline?->name,
                'stage_id'      => $l->stage_id,
                'stage_name'    => $l->stage?->name,
                'source'        => $l->source,
            ];
        }

        return response()->json([
            'messages'            => $messages,
            'lead'                => $lead,
            'assigned_user_id'    => $conversation->assigned_user_id,
            'ai_agent_id'         => $conversation->ai_agent_id,
            'chatbot_flow_id'     => $conversation->chatbot_flow_id,
            'tags'                => $conversation->tags ?? [],
            'contact_name'        => $conversation->contact_name,
            'phone'               => $conversation->phone,
            'is_group'            => $conversation->is_group,
            'contact_picture_url' => $conversation->contact_picture_url,
            'department_id'       => $conversation->department_id,
            'department_name'     => $conversation->department?->name,
            'department_color'    => $conversation->department?->color,
            'instance_label'      => $conversation->instance?->label,
        ]);
    }

    public function markRead(WhatsappConversation $conversation): JsonResponse
    {
        $conversation->update(['unread_count' => 0]);
        return response()->json(['success' => true]);
    }

    public function assign(WhatsappConversation $conversation, Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $userId = $request->input('user_id');
        $conversation->update(['assigned_user_id' => $userId ?: null]);

        // Notificação: conversa atribuída
        if ($userId && (int) $userId !== auth()->id()) {
            try {
                (new \App\Services\NotificationDispatcher())->dispatch('whatsapp_assigned', [
                    'contact_name' => $conversation->contact_name ?? $conversation->phone,
                    'assigned_by'  => auth()->user()->name,
                    'url'          => route('chats.index') . '?open=' . $conversation->id,
                ], activeTenantId(), targetUserId: (int) $userId);
            } catch (\Throwable) {}
        }

        return response()->json(['success' => true]);
    }

    public function updateStatus(WhatsappConversation $conversation, Request $request): JsonResponse
    {
        $status = $request->input('status');
        if (! in_array($status, ['open', 'closed'])) {
            return response()->json(['error' => 'Status inválido'], 422);
        }

        $conversation->update([
            'status'    => $status,
            'closed_at' => $status === 'closed' ? now() : null,
        ]);

        // Summarize conversation for AI memory when closing a conversation with an AI agent
        if ($status === 'closed' && $conversation->ai_agent_id) {
            SummarizeConversation::dispatch($conversation->id);
        }

        return response()->json(['success' => true]);
    }

    public function updateLead(WhatsappConversation $conversation, Request $request): JsonResponse
    {
        $data = $request->validate([
            'pipeline_id' => 'nullable|integer|exists:pipelines,id',
            'stage_id'    => 'nullable|integer|exists:pipeline_stages,id',
            'value'       => 'nullable|numeric|min:0',
            'email'       => 'nullable|email|max:191',
        ]);

        $conversation->load('lead');

        if (! $conversation->lead) {
            return response()->json(['error' => 'Conversa sem lead vinculado'], 422);
        }

        $data = array_filter($data, fn ($v) => $v !== null);

        $conversation->lead->update($data);

        return response()->json(['success' => true]);
    }

    public function linkLead(WhatsappConversation $conversation, Request $request): JsonResponse
    {
        $request->validate(['lead_id' => 'required|integer|exists:leads,id']);
        $conversation->update(['lead_id' => $request->lead_id]);
        return response()->json(['success' => true]);
    }

    public function linkLeadInstagram(\App\Models\InstagramConversation $conversation, Request $request): JsonResponse
    {
        $request->validate(['lead_id' => 'required|integer']);
        $conversation->update(['lead_id' => $request->lead_id]);
        return response()->json(['success' => true]);
    }

    public function unlinkLead(WhatsappConversation $conversation): JsonResponse
    {
        $conversation->update(['lead_id' => null]);
        return response()->json(['success' => true]);
    }

    public function unlinkLeadInstagram(\App\Models\InstagramConversation $conversation): JsonResponse
    {
        $conversation->update(['lead_id' => null]);
        return response()->json(['success' => true]);
    }

    public function searchLeads(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));
        $leads = \App\Models\Lead::where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%");
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'name', 'phone']);

        return response()->json(['leads' => $leads]);
    }

    public function updateContact(WhatsappConversation $conversation, Request $request): JsonResponse
    {
        $data = [];

        if ($request->has('contact_name')) {
            $data['contact_name'] = $request->input('contact_name') ?: null;
        }
        if ($request->has('phone')) {
            $phone = trim((string) $request->input('phone'));
            if ($phone) {
                $data['phone'] = preg_replace('/\D/', '', $phone); // só dígitos
            }
        }
        if ($request->has('tags')) {
            $tags = array_values(array_filter(array_map('trim', (array) $request->input('tags'))));
            $data['tags'] = $tags ?: null;
        }

        if ($data) {
            $conversation->update($data);
            // Sincroniza o lead vinculado com o mesmo nome/telefone
            if ($conversation->lead && isset($data['contact_name'])) {
                $conversation->lead->update(['name' => $data['contact_name']]);
            }
            if ($conversation->lead && isset($data['phone'])) {
                $conversation->lead->update(['phone' => $data['phone']]);
            }
            // Dual write tags: pivot polimorfica
            if (array_key_exists('tags', $data)) {
                $conversation->syncTagsByName((array) ($data['tags'] ?? []));
            }
        }

        return response()->json(['success' => true, 'conversation' => [
            'contact_name' => $conversation->contact_name,
            'phone'        => $conversation->phone,
            'tags'         => $conversation->tags ?? [],
        ]]);
    }

    /**
     * Endpoint generico de update de contato pra qualquer canal do inbox.
     * Substitui (em cobertura) o `updateContact` especifico de WhatsApp e
     * habilita pela primeira vez o salvamento de tags em Instagram e Website.
     *
     * Rota: PUT /chats/inbox/{channel}/{conversation}/contact
     */
    public function updateConversationContact(
        Request $request,
        string $channel,
        int $conversation,
        ConversationResolver $resolver
    ): JsonResponse {
        if (! $resolver->isValidChannel($channel)) {
            return response()->json(['success' => false, 'message' => 'Canal invalido.'], 404);
        }

        $conv = $resolver->resolve($channel, $conversation);
        if (! $conv) {
            return response()->json(['success' => false, 'message' => 'Conversa nao encontrada.'], 404);
        }

        // Garante isolamento de tenant (alem do GlobalScope, defesa em profundidade)
        if ((int) $conv->tenant_id !== (int) auth()->user()->tenant_id) {
            return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }

        $data = $request->validate([
            'name'   => 'nullable|string|max:191',
            'phone'  => 'nullable|string|max:30',
            'tags'   => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $update = [];

        // contact_name e o campo padrao em todos os 3 conversation models
        if (array_key_exists('name', $data) && in_array('contact_name', $conv->getFillable(), true)) {
            $update['contact_name'] = $data['name'] ?: null;
        }

        // phone so existe em WhatsappConversation; WebsiteConversation usa contact_phone; IG nao tem
        if (array_key_exists('phone', $data)) {
            $cleanPhone = preg_replace('/\D/', '', (string) $data['phone']) ?: null;
            if (in_array('phone', $conv->getFillable(), true)) {
                $update['phone'] = $cleanPhone;
            } elseif (in_array('contact_phone', $conv->getFillable(), true)) {
                $update['contact_phone'] = $cleanPhone;
            }
        }

        // tags como JSON (compat backward) — pivot e atualizada via syncTagsByName abaixo
        $hasTagsKey = array_key_exists('tags', $data);
        if ($hasTagsKey && in_array('tags', $conv->getFillable(), true)) {
            $cleanTags = array_values(array_filter(array_map('trim', (array) $data['tags'])));
            $update['tags'] = $cleanTags ?: null;
        }

        if (! empty($update)) {
            $conv->update($update);
        }

        // Dual write: pivot polimorfica
        if ($hasTagsKey) {
            $cleanTags = $cleanTags ?? [];
            $conv->syncTagsByName($cleanTags);
        }

        // Sincroniza nome do lead vinculado (mesmo comportamento do updateContact legado)
        if (isset($update['contact_name']) && method_exists($conv, 'lead') && $conv->lead) {
            $conv->lead->update(['name' => $update['contact_name']]);
        }
        if (isset($update['phone']) && method_exists($conv, 'lead') && $conv->lead) {
            $conv->lead->update(['phone' => $update['phone']]);
        }

        return response()->json([
            'success'      => true,
            'channel'      => $channel,
            'conversation' => [
                'id'           => $conv->id,
                'contact_name' => $conv->getContactName(),
                'phone'        => $conv->getContactPhone(),
                'tags'         => $conv->tag_names,
            ],
        ]);
    }

    public function assignAiAgent(WhatsappConversation $conversation, Request $request): JsonResponse
    {
        $request->validate([
            'ai_agent_id' => 'nullable|exists:ai_agents,id',
        ]);

        $agentId = $request->input('ai_agent_id');

        // Exclusividade: se atribuindo agente de IA, limpa chatbot ativo
        $updateData = ['ai_agent_id' => $agentId];
        if ($agentId && $conversation->chatbot_flow_id) {
            $updateData['chatbot_flow_id']   = null;
            $updateData['chatbot_node_id']   = null;
            $updateData['chatbot_variables'] = null;
        }
        $conversation->update($updateData);

        Log::channel('whatsapp')->info('Agente IA atribuído à conversa', [
            'conversation_id' => $conversation->id,
            'ai_agent_id'     => $agentId,
        ]);

        // Disparar resposta de IA imediatamente para cobrir mensagens pendentes
        if ($agentId) {
            try {
                (new ProcessAiResponse($conversation->id))->process();
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->error('AI agent (assign) falhou', [
                    'conversation_id' => $conversation->id,
                    'error'           => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['success' => true, 'ai_agent_id' => $conversation->fresh()->ai_agent_id]);
    }

    public function assignChatbotFlow(WhatsappConversation $conversation, Request $request): JsonResponse
    {
        $request->validate([
            'chatbot_flow_id' => 'nullable|exists:chatbot_flows,id',
        ]);

        $flowId = $request->input('chatbot_flow_id');

        // Resolve o nó de start do flow — sem isso o chatbot fica atribuído
        // mas nunca dispara (ProcessWahaWebhook requer AMBOS flow_id E node_id).
        $startNodeId = null;
        if ($flowId) {
            $startNodeId = \App\Models\ChatbotFlowNode::where('flow_id', $flowId)
                ->where('is_start', true)
                ->value('id');
        }

        // Exclusividade: se atribuindo chatbot, limpa agente de IA ativo
        $updateData = [
            'chatbot_flow_id'   => $flowId,
            'chatbot_node_id'   => $startNodeId,
            'chatbot_variables' => null,
        ];
        if ($flowId && $conversation->ai_agent_id) {
            $updateData['ai_agent_id'] = null;
        }
        $conversation->update($updateData);

        Log::channel('whatsapp')->info('Chatbot flow atribuído à conversa', [
            'conversation_id' => $conversation->id,
            'chatbot_flow_id' => $flowId,
        ]);

        return response()->json([
            'success'         => true,
            'chatbot_flow_id' => $conversation->fresh()->chatbot_flow_id,
        ]);
    }

    public function assignDepartment(WhatsappConversation $conversation, Request $request): JsonResponse
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $deptId = $request->input('department_id');

        if ($deptId) {
            $dept = Department::findOrFail($deptId);
            $dept->assignConversation($conversation);
            $conversation->refresh();
        } else {
            $conversation->update(['department_id' => null]);
        }

        Log::channel('whatsapp')->info('Departamento atribuído à conversa', [
            'conversation_id' => $conversation->id,
            'department_id'   => $deptId,
        ]);

        return response()->json([
            'success'       => true,
            'department_id' => $conversation->department_id,
        ]);
    }

    public function destroy(WhatsappConversation $conversation): JsonResponse
    {
        // Cascade delete via FK — messages are deleted with the conversation
        $conversation->delete();

        return response()->json(['success' => true]);
    }

    public function destroyInstagram(InstagramConversation $conversation): JsonResponse
    {
        $conversation->delete();

        return response()->json(['success' => true]);
    }

    public function destroyWebsite(WebsiteConversation $websiteConversation): JsonResponse
    {
        $websiteConversation->messages()->delete();
        $websiteConversation->delete();

        return response()->json(['success' => true]);
    }

    // ── Formatters ────────────────────────────────────────────────────────────

    private function formatMessage(WhatsappMessage $m): array
    {
        return [
            'id'              => $m->id,
            'waha_message_id' => $m->waha_message_id,
            'direction'       => $m->direction,
            'type'            => $m->type,
            'body'            => $m->body,
            'media_url'       => $this->resolveMediaUrl($m->media_url),
            'media_mime'      => $m->media_mime,
            'media_filename'  => $m->media_filename,
            'reaction_data'   => $m->reaction_data,
            'ack'             => $m->ack,
            'is_deleted'      => $m->is_deleted,
            'sent_at'         => $m->sent_at?->toISOString(),
            'user_name'       => $m->user?->name,
            'sender_name'     => $m->sender_name,
            'sent_by'         => $m->sent_by,
            'sent_by_agent'   => $m->sentByAgent ? [
                'id'     => $m->sentByAgent->id,
                'name'   => $m->sentByAgent->name,
                'avatar' => $m->sentByAgent->display_avatar,
            ] : null,
        ];
    }

    /**
     * Convert a relative storage path (e.g. "whatsapp/image/foo.jpg") stored by
     * older messages to a full public URL. S3/external URLs are returned as-is.
     */
    private function resolveMediaUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        // Already an absolute URL (S3, WAHA proxy, http/https external)
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        // Relative storage path — convert to public URL
        return Storage::disk('public')->url($url);
    }

    private function formatConversation(WhatsappConversation $c): array
    {
        $latest = $c->latestMessage;
        return [
            'id'                => $c->id,
            'phone'             => $c->phone,
            'contact_name'      => $c->contact_name,
            'contact_picture'   => $c->contact_picture_url,
            'tags'              => $c->tags ?? [],
            'status'            => $c->status,
            'unread_count'      => $c->unread_count,
            'last_message_at'   => $c->last_message_at?->toISOString(),
            'last_message_body' => $latest?->body ?? ($latest ? '[' . $latest->type . ']' : null),
            'last_message_type' => $latest?->type,
            'assigned_user'     => $c->assignedUser?->name,
            'assigned_user_id'  => $c->assigned_user_id,
            'is_group'          => $c->is_group ?? false,
            'ai_agent_id'       => $c->ai_agent_id,
            'department_id'     => $c->department_id,
            'department_name'   => $c->department?->name,
            'department_color'  => $c->department?->color,
            'instance_label'    => $c->instance?->label,
            'channel'           => 'whatsapp',
        ];
    }

    private function formatInstagramConversation(InstagramConversation $c): array
    {
        $latest = $c->latestMessage;
        return [
            'id'                => $c->id,
            'phone'             => '@' . ltrim($c->contact_username ?? '', '@'),
            'contact_name'      => $c->contact_name,
            'contact_picture'   => $c->contact_picture_url,
            'tags'              => $c->tags ?? [],
            'status'            => $c->status,
            'unread_count'      => $c->unread_count,
            'last_message_at'   => $c->last_message_at?->toISOString(),
            'last_message_body' => $latest?->body ?? ($latest ? '[' . $latest->type . ']' : null),
            'last_message_type' => $latest?->type,
            'assigned_user'     => null,
            'assigned_user_id'  => $c->assigned_user_id,
            'is_group'          => false,
            'ai_agent_id'       => $c->ai_agent_id,
            'department_id'     => $c->department_id,
            'department_name'   => $c->department?->name,
            'department_color'  => $c->department?->color,
            'channel'           => 'instagram',
        ];
    }

    // ── Website Conversations ─────────────────────────────────────────────────

    public function showWebsite(WebsiteConversation $websiteConversation): JsonResponse
    {
        $websiteConversation->load(['lead.pipeline', 'lead.stage']);

        $messages = WebsiteMessage::where('conversation_id', $websiteConversation->id)
            ->orderBy('sent_at')->orderBy('id')
            ->get()
            ->map(fn ($m) => [
                'id'         => $m->id,
                'direction'  => $m->direction,
                'type'       => 'text',
                'body'       => $m->content,
                'media_url'  => null,
                'ack'        => null,
                'is_deleted' => false,
                'sent_at'    => $m->sent_at?->toISOString(),
            ]);

        $lead = null;
        if ($websiteConversation->lead) {
            $l    = $websiteConversation->lead;
            $lead = [
                'id'            => $l->id,
                'name'          => $l->name,
                'phone'         => $l->phone,
                'email'         => $l->email,
                'value'         => $l->value,
                'pipeline_id'   => $l->pipeline_id,
                'pipeline_name' => $l->pipeline?->name,
                'stage_id'      => $l->stage_id,
                'stage_name'    => $l->stage?->name,
                'source'        => $l->source,
            ];
        }

        return response()->json([
            'messages'      => $messages,
            'lead'          => $lead,
            'contact_name'  => $websiteConversation->contact_name,
            'contact_email' => $websiteConversation->contact_email,
            'contact_phone' => $websiteConversation->contact_phone,
            'status'        => $websiteConversation->status,
            'channel'       => 'website',
        ]);
    }

    public function markReadWebsite(WebsiteConversation $websiteConversation): JsonResponse
    {
        $websiteConversation->update(['unread_count' => 0]);
        return response()->json(['success' => true]);
    }

    public function updateStatusWebsite(WebsiteConversation $websiteConversation, Request $request): JsonResponse
    {
        $status = $request->input('status');
        if (! in_array($status, ['open', 'closed'])) {
            return response()->json(['error' => 'Status inválido'], 422);
        }

        $websiteConversation->update(['status' => $status]);
        return response()->json(['success' => true]);
    }

    public function linkLeadWebsite(WebsiteConversation $websiteConversation, Request $request): JsonResponse
    {
        $request->validate(['lead_id' => 'required|integer']);
        $websiteConversation->update(['lead_id' => $request->lead_id]);

        $lead = Lead::find($request->lead_id);
        if ($lead) {
            $utmUpdate = array_filter([
                'utm_source'   => $lead->utm_source   ?: $websiteConversation->utm_source,
                'utm_medium'   => $lead->utm_medium   ?: $websiteConversation->utm_medium,
                'utm_campaign' => $lead->utm_campaign ?: $websiteConversation->utm_campaign,
                'utm_content'  => $lead->utm_content  ?: $websiteConversation->utm_content,
                'utm_term'     => $lead->utm_term     ?: $websiteConversation->utm_term,
            ]);
            if ($utmUpdate) {
                $lead->update($utmUpdate);
            }
        }

        return response()->json(['success' => true]);
    }

    public function unlinkLeadWebsite(WebsiteConversation $websiteConversation): JsonResponse
    {
        $websiteConversation->update(['lead_id' => null]);
        return response()->json(['success' => true]);
    }

    private function formatWebsiteConversation(WebsiteConversation $c): array
    {
        $latest = $c->latestMessage;
        return [
            'id'                => $c->id,
            'phone'             => $c->contact_phone ?? $c->visitor_id,
            'contact_name'      => $c->contact_name ?? ('Visitante #' . substr($c->visitor_id, 0, 8)),
            'contact_picture'   => null,
            'contact_email'     => $c->contact_email,
            'contact_phone'     => $c->contact_phone,
            'tags'              => [],
            'status'            => $c->status,
            'unread_count'      => $c->unread_count,
            'last_message_at'   => $c->last_message_at?->toISOString(),
            'last_message_body' => $latest?->content,
            'last_message_type' => 'text',
            'assigned_user'     => null,
            'assigned_user_id'  => null,
            'is_group'          => false,
            'ai_agent_id'       => null,
            'channel'           => 'website',
        ];
    }
}
