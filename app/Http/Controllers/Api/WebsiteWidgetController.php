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
     * GET /api/widget/{token}/init?visitor_id={uuid}
     *
     * Initializes or resumes a website chat session.
     * Returns conversation history and conversation_id.
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

        $visitorId = (string) $request->query('visitor_id', '');
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
                'tenant_id'  => $flow->tenant_id,
                'flow_id'    => $flow->id,
                'visitor_id' => $visitorId,
                'status'     => 'open',
                'started_at' => now(),
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
        $replies = [];
        if ($isNew && $flow->is_active) {
            $service = new WebsiteChatService();
            $replies = $service->processMessage($conversation->fresh(), '');
        }

        return response()->json([
            'conversation_id' => $conversation->id,
            'status'          => $conversation->status,
            'messages'        => $messages,
            'replies'         => $replies,
        ]);
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
        $replies = $service->processMessage($conversation->fresh(), $validated['message']);

        return response()->json([
            'replies'         => $replies,
            'conversation_id' => $conversation->id,
        ]);
    }
}
