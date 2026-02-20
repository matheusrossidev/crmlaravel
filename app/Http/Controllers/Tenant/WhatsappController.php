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
use Illuminate\Support\Facades\Storage;
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

        $convId = $request->input('conversation_id');

        $newMessages = [];
        if ($convId) {
            $newMessages = WhatsappMessage::where('conversation_id', $convId)
                ->where('created_at', '>=', $since)
                ->orderBy('sent_at')
                ->get()
                ->map(fn ($m) => $this->formatMessage($m));
        }

        $updatedConvs = WhatsappConversation::with(['latestMessage', 'assignedUser'])
            ->where('last_message_at', '>=', $since)
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn ($c) => $this->formatConversation($c));

        return response()->json([
            'new_messages'          => $newMessages,
            'conversations_updated' => $updatedConvs,
            'now'                   => now()->utc()->toISOString(), // always UTC so browser comparison is unambiguous
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

    public function destroy(WhatsappConversation $conversation): JsonResponse
    {
        // Cascade delete via FK — messages are deleted with the conversation
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
            'status'            => $c->status,
            'unread_count'      => $c->unread_count,
            'last_message_at'   => $c->last_message_at?->toISOString(),
            'last_message_body' => $latest?->body ?? ($latest ? '[' . $latest->type . ']' : null),
            'last_message_type' => $latest?->type,
            'assigned_user'     => $c->assignedUser?->name,
        ];
    }
}
