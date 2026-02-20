<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsappController extends Controller
{
    public function index(): View
    {
        $instance = WhatsappInstance::first();
        $connected = $instance && $instance->status === 'connected';

        $conversations = [];
        $users         = [];

        if ($connected) {
            $conversations = WhatsappConversation::with(['latestMessage', 'assignedUser'])
                ->orderByDesc('last_message_at')
                ->get();

            $users = User::where('tenant_id', auth()->user()->tenant_id)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('tenant.whatsapp.index', compact('instance', 'connected', 'conversations', 'users'));
    }

    public function poll(Request $request): JsonResponse
    {
        $since  = $request->input('since', now()->subMinutes(1)->toISOString());
        $convId = $request->input('conversation_id');

        $newMessages = [];
        if ($convId) {
            $newMessages = WhatsappMessage::where('conversation_id', $convId)
                ->where('created_at', '>', $since)
                ->orderBy('sent_at')
                ->get()
                ->map(fn ($m) => $this->formatMessage($m));
        }

        $updatedConvs = WhatsappConversation::with(['latestMessage', 'assignedUser'])
            ->where('last_message_at', '>', $since)
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn ($c) => $this->formatConversation($c));

        return response()->json([
            'new_messages'          => $newMessages,
            'conversations_updated' => $updatedConvs,
            'now'                   => now()->toISOString(),
        ]);
    }

    public function show(WhatsappConversation $conversation): JsonResponse
    {
        $messages = WhatsappMessage::where('conversation_id', $conversation->id)
            ->orderBy('sent_at')
            ->get()
            ->map(fn ($m) => $this->formatMessage($m));

        return response()->json(['messages' => $messages]);
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

    // ── Formatters ────────────────────────────────────────────────────────────

    private function formatMessage(WhatsappMessage $m): array
    {
        return [
            'id'              => $m->id,
            'waha_message_id' => $m->waha_message_id,
            'direction'       => $m->direction,
            'type'            => $m->type,
            'body'            => $m->body,
            'media_url'       => $m->media_url,
            'media_mime'      => $m->media_mime,
            'media_filename'  => $m->media_filename,
            'reaction_data'   => $m->reaction_data,
            'ack'             => $m->ack,
            'is_deleted'      => $m->is_deleted,
            'sent_at'         => $m->sent_at?->toISOString(),
            'user_name'       => $m->user?->name,
        ];
    }

    private function formatConversation(WhatsappConversation $c): array
    {
        $latest = $c->latestMessage;
        return [
            'id'                => $c->id,
            'phone'             => $c->phone,
            'contact_name'      => $c->contact_name,
            'contact_picture'   => $c->contact_picture_url,
            'status'            => $c->status,
            'unread_count'      => $c->unread_count,
            'last_message_at'   => $c->last_message_at?->toISOString(),
            'last_message_body' => $latest?->body ?? ($latest ? '[' . $latest->type . ']' : null),
            'last_message_type' => $latest?->type,
            'assigned_user'     => $c->assignedUser?->name,
        ];
    }
}
