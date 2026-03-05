<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFlow;
use App\Models\WebsiteConversation;
use App\Models\WebsiteMessage;
use App\Services\WebsiteChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebsiteWidgetController extends Controller
{
    /**
     * POST /api/widget/{token}/init
     *
     * Initializes or resumes a website chat session.
     * Body: { visitor_id: string, utm_source?, utm_medium?, utm_campaign?, utm_content?, utm_term?, page_url?, referrer_url? }
     * Returns conversation history, conversation_id, bot identity, and widget_type.
     */
    public function init(string $token, Request $request): JsonResponse
    {
        $flow = ChatbotFlow::withoutGlobalScope('tenant')
            ->where('website_token', $token)
            ->where('channel', 'website')
            ->first();

        if (! $flow) {
            return response()->json(['error' => 'Widget not found'], 404);
        }

        // Accept visitor_id from body (POST) or query string (legacy GET)
        $visitorId = (string) ($request->input('visitor_id') ?? $request->query('visitor_id', ''));
        if ($visitorId === '') {
            return response()->json(['error' => 'visitor_id is required'], 422);
        }

        $conversation = WebsiteConversation::withoutGlobalScope('tenant')
            ->where('flow_id', $flow->id)
            ->where('visitor_id', $visitorId)
            ->first();

        $isNew = false;
        if (! $conversation) {
            $isNew = true;
            $conversation = WebsiteConversation::withoutGlobalScope('tenant')->create([
                'tenant_id'    => $flow->tenant_id,
                'flow_id'      => $flow->id,
                'visitor_id'   => $visitorId,
                'status'       => 'open',
                'started_at'   => now(),
                'utm_source'   => $this->truncate($request->input('utm_source'),   100),
                'utm_medium'   => $this->truncate($request->input('utm_medium'),   100),
                'utm_campaign' => $this->truncate($request->input('utm_campaign'), 150),
                'utm_content'  => $this->truncate($request->input('utm_content'),  150),
                'utm_term'     => $this->truncate($request->input('utm_term'),     150),
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

        // If this is a brand-new conversation, trigger the flow start immediately
        $replies   = [];
        $buttons   = [];
        $inputType = 'text';
        if ($isNew && $flow->is_active) {
            $service   = new WebsiteChatService();
            $result    = $service->processMessage($conversation->fresh(), '');
            $replies   = $result['replies'] ?? [];
            $buttons   = $result['buttons'] ?? [];
            $inputType = $result['input_type'] ?? 'text';
        }

        return response()->json([
            'conversation_id' => $conversation->id,
            'status'          => $conversation->status,
            'messages'        => $messages,
            'replies'         => $replies,
            'buttons'         => $buttons,
            'input_type'      => $inputType,
            'bot_name'        => $flow->bot_name,
            'bot_avatar'      => $flow->bot_avatar,
            'welcome_message' => $flow->welcome_message,
            'widget_type'     => $flow->widget_type ?? 'bubble',
        ])->header('Access-Control-Allow-Origin', '*');
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
        $flow = ChatbotFlow::withoutGlobalScope('tenant')
            ->where('website_token', $token)
            ->where('channel', 'website')
            ->first();

        if (! $flow) {
            return response()->json(['error' => 'Widget not found'], 404);
        }

        $validated = $request->validate([
            'visitor_id' => 'required|string|max:64',
            'message'    => 'required|string|max:2000',
        ]);

        $conversation = WebsiteConversation::withoutGlobalScope('tenant')
            ->where('flow_id', $flow->id)
            ->where('visitor_id', $validated['visitor_id'])
            ->first();

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        if ($conversation->status === 'closed') {
            return response()->json(['error' => 'Conversation is closed', 'replies' => []], 422);
        }

        // Save inbound message
        WebsiteMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'inbound',
            'content'         => $validated['message'],
            'sent_at'         => now(),
        ]);

        // Increment unread count and update last_message_at
        WebsiteConversation::withoutGlobalScope('tenant')
            ->where('id', $conversation->id)
            ->update([
                'unread_count'    => $conversation->unread_count + 1,
                'last_message_at' => now(),
            ]);

        // Process chatbot step
        $service = new WebsiteChatService();
        $result  = $service->processMessage($conversation->fresh(), $validated['message']);

        return response()->json([
            'replies'         => $result['replies'] ?? [],
            'buttons'         => $result['buttons'] ?? [],
            'input_type'      => $result['input_type'] ?? 'text',
            'conversation_id' => $conversation->id,
        ])->header('Access-Control-Allow-Origin', '*');
    }
}
