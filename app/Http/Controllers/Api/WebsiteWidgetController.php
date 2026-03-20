<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\ChatbotFlow;
use App\Models\Tenant;
use App\Models\WhatsappButton;
use App\Models\WhatsappButtonClick;
use App\Models\WebsiteConversation;
use App\Models\WebsiteMessage;
use App\Services\AiAgentWebChatService;
use App\Services\WebsiteChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class WebsiteWidgetController extends Controller
{
    /**
     * Resolve a website_token to either a ChatbotFlow or AiAgent.
     *
     * @return array{type: string, entity: ChatbotFlow|AiAgent, tenant_id: int}|null
     */
    private function resolveToken(string $token): ?array
    {
        $flow = ChatbotFlow::withoutGlobalScope('tenant')
            ->where('website_token', $token)
            ->first();

        if ($flow) {
            return ['type' => 'flow', 'entity' => $flow, 'tenant_id' => $flow->tenant_id];
        }

        $agent = AiAgent::withoutGlobalScope('tenant')
            ->where('website_token', $token)
            ->where('is_active', true)
            ->first();

        if ($agent) {
            return ['type' => 'agent', 'entity' => $agent, 'tenant_id' => $agent->tenant_id];
        }

        return null;
    }

    /**
     * GET /api/widget/{token}.js
     *
     * Serves the widget JavaScript with the token and apiBase baked in.
     * This allows a clean embed: <script src="https://domain/api/widget/{token}.js"></script>
     */
    public function script(string $token): Response
    {
        $resolved = $this->resolveToken($token);

        if (! $resolved) {
            abort(404);
        }

        $js = file_get_contents(public_path('widget.js'));

        $appUrl = rtrim((string) config('app.url'), '/');
        $color  = $resolved['entity']->widget_color ?? '#0085f3';
        $js = str_replace('var __INJECTED_TOKEN__ = null;', "var __INJECTED_TOKEN__ = '{$token}';", $js);
        $js = str_replace('var __INJECTED_BASE__  = null;', "var __INJECTED_BASE__  = '{$appUrl}';", $js);
        $js = str_replace('var __INJECTED_COLOR__ = null;', "var __INJECTED_COLOR__ = '{$color}';", $js);

        return response($js, 200)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * POST /api/widget/{token}/init
     *
     * Initializes or resumes a website chat session.
     * Body: { visitor_id: string, utm_source?, utm_medium?, utm_campaign?, utm_content?, utm_term?, page_url?, referrer_url? }
     * Returns conversation history, conversation_id, bot identity, and widget_type.
     */
    public function init(string $token, Request $request): JsonResponse
    {
        $resolved = $this->resolveToken($token);

        if (! $resolved) {
            return response()->json(['error' => 'Widget not found'], 404);
        }

        $entity   = $resolved['entity'];
        $tenantId = $resolved['tenant_id'];
        $isAgent  = $resolved['type'] === 'agent';

        // Bloquear se tenant com serviço bloqueado (trial expirado, suspenso, etc.)
        $tenant = Tenant::find($tenantId);
        if ($tenant && $tenant->isServiceBlocked()) {
            return response()->json(['error' => 'Service unavailable'], 403)
                ->header('Access-Control-Allow-Origin', '*');
        }

        // Accept visitor_id from body (POST) or query string (legacy GET)
        $visitorId = (string) ($request->input('visitor_id') ?? $request->query('visitor_id', ''));
        if ($visitorId === '') {
            return response()->json(['error' => 'visitor_id is required'], 422);
        }

        // Find existing conversation
        $convQuery = WebsiteConversation::withoutGlobalScope('tenant')
            ->where('visitor_id', $visitorId);

        if ($isAgent) {
            $convQuery->where('ai_agent_id', $entity->id);
        } else {
            $convQuery->where('flow_id', $entity->id);
        }

        $conversation = $convQuery->first();

        $isNew = false;
        if (! $conversation) {
            $isNew = true;
            $conversation = WebsiteConversation::withoutGlobalScope('tenant')->create([
                'tenant_id'    => $tenantId,
                'flow_id'      => $isAgent ? null : $entity->id,
                'ai_agent_id'  => $isAgent ? $entity->id : null,
                'visitor_id'   => $visitorId,
                'status'       => 'open',
                'started_at'   => now(),
                'utm_id'       => $this->truncate($request->input('utm_id'),       100),
                'utm_source'   => $this->truncate($request->input('utm_source'),   100),
                'utm_medium'   => $this->truncate($request->input('utm_medium'),   100),
                'utm_campaign' => $this->truncate($request->input('utm_campaign'), 150),
                'utm_content'  => $this->truncate($request->input('utm_content'),  150),
                'utm_term'     => $this->truncate($request->input('utm_term'),     150),
                'fbclid'       => $this->truncate($request->input('fbclid'),       255),
                'gclid'        => $this->truncate($request->input('gclid'),        255),
                'page_url'     => $this->truncate($request->input('page_url'),     500),
                'referrer_url' => $this->truncate($request->input('referrer_url'), 500),
            ]);
        }

        $messages = WebsiteMessage::where('conversation_id', $conversation->id)
            ->orderBy('sent_at')
            ->get()
            ->map(fn ($m) => [
                'direction' => $m->direction,
                'content'   => $m->content,
                'sent_at'   => $m->sent_at?->toISOString(),
            ]);

        $replies   = [];
        $buttons   = [];
        $inputType = 'text';

        if ($isAgent) {
            // AI Agent: send welcome message as first reply for new conversations
            if ($isNew && $entity->welcome_message) {
                $replies = [$entity->welcome_message];

                // Save welcome message as outbound
                WebsiteMessage::create([
                    'conversation_id' => $conversation->id,
                    'direction'       => 'outbound',
                    'content'         => $entity->welcome_message,
                    'sent_at'         => now(),
                ]);
            }
        } else {
            // ChatbotFlow: trigger flow start for new conversations
            if ($isNew && $entity->is_active) {
                $service   = new WebsiteChatService();
                $result    = $service->processMessage($conversation->fresh(), '');
                $replies   = $result['replies'] ?? [];
                $buttons   = $result['buttons'] ?? [];
                $inputType = $result['input_type'] ?? 'text';
            } elseif (! $isNew && ! empty($conversation->chatbot_cursor['waiting'])) {
                $service   = new WebsiteChatService();
                $state     = $service->getCurrentInputState($conversation);
                $buttons   = $state['buttons'];
                $inputType = $state['input_type'];
            }
        }

        return response()->json([
            'conversation_id' => $conversation->id,
            'status'          => $conversation->status,
            'messages'        => $messages,
            'replies'         => $replies,
            'buttons'         => $buttons,
            'input_type'      => $inputType,
            'bot_name'        => $entity->bot_name,
            'bot_avatar'      => $this->resolveAvatarUrl($entity->bot_avatar),
            'welcome_message' => $entity->welcome_message,
            'widget_type'     => $entity->widget_type ?? 'bubble',
        ])->header('Access-Control-Allow-Origin', '*');
    }

    private function resolveAvatarUrl(?string $avatar): ?string
    {
        if (! $avatar) {
            return null;
        }

        // Already a relative path (e.g. /images/avatars/agent-1.png) — resolve to full URL
        if (str_starts_with($avatar, '/')) {
            return asset($avatar);
        }

        // Full URL from any domain — extract the path and re-resolve with current app URL
        if (preg_match('#^https?://#i', $avatar)) {
            // Extract path after the domain (handles both localhost and production URLs)
            $path = (string) parse_url($avatar, PHP_URL_PATH);
            // Remove common Laravel public prefixes
            $path = preg_replace('#^/crm/public#', '', $path) ?? $path;
            return asset($path);
        }

        return asset($avatar);
    }

    private function truncate(?string $value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return mb_substr($value, 0, $max);
    }

    /**
     * POST /api/widget/{token}/message
     *
     * Sends a visitor message and returns bot replies.
     * Body: { visitor_id: string, message: string }
     */
    public function message(string $token, Request $request): JsonResponse
    {
        $resolved = $this->resolveToken($token);

        if (! $resolved) {
            return response()->json(['error' => 'Widget not found'], 404);
        }

        $entity   = $resolved['entity'];
        $tenantId = $resolved['tenant_id'];
        $isAgent  = $resolved['type'] === 'agent';

        // Bloquear se tenant com serviço bloqueado
        $tenant = Tenant::find($tenantId);
        if ($tenant && $tenant->isServiceBlocked()) {
            return response()->json(['error' => 'Service unavailable', 'replies' => []], 403)
                ->header('Access-Control-Allow-Origin', '*');
        }

        $validated = $request->validate([
            'visitor_id' => 'required|string|max:64',
            'message'    => 'required|string|max:2000',
        ]);

        // Find conversation
        $convQuery = WebsiteConversation::withoutGlobalScope('tenant')
            ->where('visitor_id', $validated['visitor_id']);

        if ($isAgent) {
            $convQuery->where('ai_agent_id', $entity->id);
        } else {
            $convQuery->where('flow_id', $entity->id);
        }

        $conversation = $convQuery->first();

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        if ($conversation->status === 'closed') {
            return response()->json(['error' => 'Conversation is closed', 'replies' => []], 422);
        }

        // Increment unread count and update last_message_at
        WebsiteConversation::withoutGlobalScope('tenant')
            ->where('id', $conversation->id)
            ->update([
                'unread_count'    => $conversation->unread_count + 1,
                'last_message_at' => now(),
            ]);

        if ($isAgent) {
            // AI Agent: save inbound message, then process via LLM
            WebsiteMessage::create([
                'conversation_id' => $conversation->id,
                'direction'       => 'inbound',
                'content'         => $validated['message'],
                'sent_at'         => now(),
            ]);

            $service = new AiAgentWebChatService();
            $result  = $service->processMessage($conversation->fresh(), $entity, $validated['message']);

            return response()->json([
                'replies'         => $result['replies'] ?? [],
                'buttons'         => $result['buttons'] ?? [],
                'cards'           => $result['cards'] ?? [],
                'input_type'      => $result['input_type'] ?? 'text',
                'conversation_id' => $conversation->id,
            ])->header('Access-Control-Allow-Origin', '*');
        }

        // ChatbotFlow: save inbound message, then process flow step
        WebsiteMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'inbound',
            'content'         => $validated['message'],
            'sent_at'         => now(),
        ]);

        $service = new WebsiteChatService();
        $result  = $service->processMessage($conversation->fresh(), $validated['message']);

        return response()->json([
            'replies'          => $result['replies'] ?? [],
            'buttons'          => $result['buttons'] ?? [],
            'input_type'       => $result['input_type'] ?? 'text',
            'conversation_id'  => $conversation->id,
            'redirect_url'     => $result['redirect_url'] ?? null,
            'redirect_target'  => $result['redirect_target'] ?? null,
        ])->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * GET /chat/{tenantSlug}/{botSlug}
     *
     * Public hosted chatbot page — full-screen inline widget.
     */
    public function hostedPage(string $tenantSlug, string $botSlug): View
    {
        $tenant = Tenant::where('slug', $tenantSlug)->first();
        if (! $tenant) {
            abort(404);
        }

        // Try ChatbotFlow first
        $flow = ChatbotFlow::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('slug', $botSlug)
            ->where('channel', 'website')
            ->where('is_active', true)
            ->first();

        if ($flow && $flow->website_token) {
            $scriptUrl   = rtrim((string) config('app.url'), '/') . '/api/widget/' . $flow->website_token . '.js';
            $widgetColor = $flow->widget_color ?? '#0085f3';
            $botName     = $flow->bot_name ?? 'Assistente';
            $tenantName  = $tenant->name ?? 'Chat';

            return view('chatbot.hosted', compact('scriptUrl', 'widgetColor', 'botName', 'tenantName'));
        }

        // Fallback: try AiAgent by slug (name slugified) or website_token
        $agent = AiAgent::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('channel', 'web_chat')
            ->where('is_active', true)
            ->where('website_token', '!=', null)
            ->get()
            ->first(function ($a) use ($botSlug) {
                return \Illuminate\Support\Str::slug($a->name) === $botSlug
                    || $a->website_token === $botSlug;
            });

        if (! $agent) {
            abort(404);
        }

        $scriptUrl   = rtrim((string) config('app.url'), '/') . '/api/widget/' . $agent->website_token . '.js';
        $widgetColor = $agent->widget_color ?? '#0085f3';
        $botName     = $agent->bot_name ?? $agent->name ?? 'Assistente';
        $tenantName  = $tenant->name ?? 'Chat';

        return view('chatbot.hosted', compact('scriptUrl', 'widgetColor', 'botName', 'tenantName'));
    }

    /**
     * GET /api/widget/{token}/wa-button.js
     * Serves the WhatsApp button embed script with config baked in.
     */
    public function waButtonScript(string $token): Response
    {
        $btn = WhatsappButton::withoutGlobalScope('tenant')
            ->where('website_token', $token)
            ->where('is_active', true)
            ->first();

        if (! $btn) {
            return response('/* button not found */', 404)
                ->header('Content-Type', 'application/javascript');
        }

        $config = json_encode([
            'token'    => $btn->website_token,
            'phone'    => $btn->phone_number,
            'message'  => $btn->default_message,
            'label'    => $btn->button_label,
            'floating' => $btn->show_floating,
            'apiBase'  => rtrim((string) config('app.url'), '/'),
        ], JSON_UNESCAPED_UNICODE);

        $js = file_get_contents(public_path('wa-button-core.js'));
        $js = "/* Syncro WhatsApp Button */\n(function(){var CFG={$config};\n{$js}\n})();";

        return response($js, 200)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=300')
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * POST /api/widget/{token}/wa-click
     * Tracks a WhatsApp button click with UTMs + device info.
     */
    public function trackWaClick(string $token, Request $request): JsonResponse
    {
        $btn = WhatsappButton::withoutGlobalScope('tenant')
            ->where('website_token', $token)
            ->where('is_active', true)
            ->first();

        if (! $btn) {
            return response()->json(['error' => 'not found'], 404);
        }

        $ua = $request->userAgent() ?? '';
        $device = str_contains(strtolower($ua), 'mobile') ? 'mobile' : 'desktop';

        WhatsappButtonClick::create([
            'tenant_id'    => $btn->tenant_id,
            'button_id'    => $btn->id,
            'visitor_id'   => $this->truncate($request->input('visitor_id'), 36),
            'utm_source'   => $this->truncate($request->input('utm_source'), 100),
            'utm_medium'   => $this->truncate($request->input('utm_medium'), 100),
            'utm_campaign' => $this->truncate($request->input('utm_campaign'), 191),
            'utm_content'  => $this->truncate($request->input('utm_content'), 191),
            'utm_term'     => $this->truncate($request->input('utm_term'), 191),
            'fbclid'       => $this->truncate($request->input('fbclid'), 191),
            'gclid'        => $this->truncate($request->input('gclid'), 191),
            'page_url'     => $this->truncate($request->input('page_url'), 2000),
            'referrer_url' => $this->truncate($request->input('referrer_url'), 500),
            'device_type'  => $device,
            'ip_hash'      => hash('sha256', $request->ip() ?? ''),
            'clicked_at'   => now(),
        ]);

        $msg = rawurlencode($btn->default_message);
        $url = "https://wa.me/{$btn->phone_number}?text={$msg}";

        return response()->json(['redirect' => $url]);
    }
}
