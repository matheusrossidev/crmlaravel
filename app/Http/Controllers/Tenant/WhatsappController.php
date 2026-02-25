<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\ChatbotFlow;
use App\Models\InstagramConversation;
use App\Models\InstagramInstance;
use App\Models\InstagramMessage;
use App\Models\Pipeline;
use App\Models\User;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Models\WhatsappTag;
use App\Services\InstagramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Jobs\ProcessAiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class WhatsappController extends Controller
{
    public function index(): View
    {
        $instance  = WhatsappInstance::first();
        $connected = $instance && $instance->status === 'connected';

        $conversations   = [];
        $igConversations = collect();
        $users           = [];
        $pipelines       = [];
        $whatsappTags    = collect();

        if ($connected) {
            $conversations = WhatsappConversation::with(['latestMessage', 'assignedUser'])
                ->orderByDesc('last_message_at')
                ->get();

            $users = User::where('tenant_id', auth()->user()->tenant_id)
                ->orderBy('name')
                ->get(['id', 'name']);

            $pipelines = Pipeline::with('stages:id,pipeline_id,name,position')
                ->orderBy('sort_order')
                ->get(['id', 'name', 'is_default']);

            $whatsappTags = WhatsappTag::orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'color']);
        }

        // Carregar conversas Instagram independente do WhatsApp estar conectado
        $igInstance = InstagramInstance::first();
        if ($igInstance && $igInstance->status === 'connected') {
            $igConversations = InstagramConversation::with(['latestMessage'])
                ->orderByDesc('last_message_at')
                ->get();
        }

        // Unificar e ordenar por data (WhatsApp + Instagram misturados)
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

        $aiAgents     = AiAgent::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $chatbotFlows = ChatbotFlow::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tenant.whatsapp.index', compact('instance', 'connected', 'conversations', 'igConversations', 'allConversations', 'users', 'pipelines', 'whatsappTags', 'aiAgents', 'chatbotFlows'));
    }

    // ── Instagram Conversations ───────────────────────────────────────────────

    public function showInstagram(InstagramConversation $conversation): JsonResponse
    {
        $conversation->load(['lead.pipeline', 'lead.stage']);

        $messages = InstagramMessage::where('conversation_id', $conversation->id)
            ->orderBy('sent_at')
            ->get()
            ->map(fn ($m) => [
                'id'        => $m->id,
                'direction' => $m->direction,
                'type'      => $m->type,
                'body'      => $m->body,
                'media_url' => $m->media_url,
                'ack'       => $m->ack,
                'sent_at'   => $m->sent_at?->toISOString(),
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
            'messages'         => $messages,
            'lead'             => $lead,
            'assigned_user_id' => $conversation->assigned_user_id,
            'ai_agent_id'      => $conversation->ai_agent_id,
            'chatbot_flow_id'  => null,
            'tags'             => $conversation->tags ?? [],
            'contact_name'     => $conversation->contact_name,
            'phone'            => '@' . ltrim($conversation->contact_username ?? '', '@'),
            'is_group'         => false,
        ]);
    }

    public function markReadInstagram(InstagramConversation $conversation): JsonResponse
    {
        $conversation->update(['unread_count' => 0]);
        return response()->json(['success' => true]);
    }

    public function sendInstagramMessage(InstagramConversation $conversation, Request $request): JsonResponse
    {
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
            $service->sendMessage($conversation->igsid, $body);
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
                    ->orderBy('sent_at')
                    ->get()
                    ->map(fn ($m) => [
                        'id'         => $m->id,
                        'direction'  => $m->direction,
                        'type'       => $m->type,
                        'body'       => $m->body,
                        'media_url'  => $m->media_url,
                        'ack'        => $m->ack,
                        'is_deleted' => false,
                        'sent_at'    => $m->sent_at?->toISOString(),
                    ]);
            } else {
                $newMessages = WhatsappMessage::where('conversation_id', $convId)
                    ->where('created_at', '>=', $since)
                    ->orderBy('sent_at')
                    ->get()
                    ->map(fn ($m) => $this->formatMessage($m));
            }
        }

        $updatedWaConvs = WhatsappConversation::with(['latestMessage', 'assignedUser'])
            ->where('last_message_at', '>=', $since)
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn ($c) => $this->formatConversation($c));

        $updatedIgConvs = InstagramConversation::with(['latestMessage'])
            ->where('last_message_at', '>=', $since)
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn ($c) => $this->formatInstagramConversation($c));

        $updatedConvs = $updatedWaConvs->concat($updatedIgConvs)
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
            ->orderBy('sent_at')
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
        ]);
    }

    public function markRead(WhatsappConversation $conversation): JsonResponse
    {
        $conversation->update(['unread_count' => 0]);
        return response()->json(['success' => true]);
    }

    public function assign(WhatsappConversation $conversation, Request $request): JsonResponse
    {
        $userId = $request->input('user_id');
        $conversation->update(['assigned_user_id' => $userId ?: null]);

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

        return response()->json(['success' => true]);
    }

    public function updateLead(WhatsappConversation $conversation, Request $request): JsonResponse
    {
        $conversation->load('lead');

        if (! $conversation->lead) {
            return response()->json(['error' => 'Conversa sem lead vinculado'], 422);
        }

        $data = array_filter([
            'pipeline_id' => $request->input('pipeline_id'),
            'stage_id'    => $request->input('stage_id'),
            'value'       => $request->input('value'),
            'email'       => $request->input('email'),
        ], fn ($v) => $v !== null);

        $conversation->lead->update($data);

        return response()->json(['success' => true]);
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
        }

        return response()->json(['success' => true, 'conversation' => [
            'contact_name' => $conversation->contact_name,
            'phone'        => $conversation->phone,
            'tags'         => $conversation->tags ?? [],
        ]]);
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

        // Exclusividade: se atribuindo chatbot, limpa agente de IA ativo
        $updateData = [
            'chatbot_flow_id'   => $flowId,
            'chatbot_node_id'   => null,
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
            'channel'           => 'instagram',
        ];
    }
}
