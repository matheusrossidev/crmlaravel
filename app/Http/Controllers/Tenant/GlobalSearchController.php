<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Task;
use App\Models\WhatsappConversation;
use App\Models\InstagramConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['leads' => [], 'conversations' => [], 'tasks' => []]);
        }

        $like = "%{$term}%";

        // Leads
        $leads = Lead::where('status', '!=', 'merged')
            ->where(fn ($q) => $q
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('company', 'like', $like))
            ->with('stage:id,name,color')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'name', 'phone', 'email', 'company', 'stage_id', 'created_at'])
            ->map(fn ($l) => [
                'id'      => $l->id,
                'name'    => $l->name,
                'phone'   => $l->phone,
                'email'   => $l->email,
                'company' => $l->company,
                'stage'   => $l->stage?->name,
                'url'     => "/contatos/{$l->id}/perfil",
            ]);

        // WhatsApp conversations
        $waConversations = WhatsappConversation::where(fn ($q) => $q
                ->where('contact_name', 'like', $like)
                ->orWhere('phone', 'like', $like))
            ->orderByDesc('last_message_at')
            ->limit(5)
            ->get(['id', 'contact_name', 'phone', 'status', 'last_message_at'])
            ->map(fn ($c) => [
                'id'     => $c->id,
                'name'   => $c->contact_name ?: $c->phone,
                'phone'  => $c->phone,
                'status' => $c->status,
                'channel' => 'whatsapp',
                'url'    => "/chats?conversation={$c->id}",
            ]);

        // Instagram conversations
        $igConversations = InstagramConversation::where(fn ($q) => $q
                ->where('contact_name', 'like', $like)
                ->orWhere('contact_username', 'like', $like))
            ->orderByDesc('last_message_at')
            ->limit(3)
            ->get(['id', 'contact_name', 'contact_username', 'status'])
            ->map(fn ($c) => [
                'id'     => $c->id,
                'name'   => $c->contact_name ?: $c->contact_username,
                'phone'  => '@' . $c->contact_username,
                'status' => $c->status,
                'channel' => 'instagram',
                'url'    => "/chats?ig_conversation={$c->id}",
            ]);

        $conversations = $waConversations->merge($igConversations)->take(5);

        // Tasks
        $tasks = Task::where('subject', 'like', $like)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get(['id', 'subject', 'type', 'status', 'due_date'])
            ->map(fn ($t) => [
                'id'      => $t->id,
                'subject' => $t->subject,
                'type'    => $t->type,
                'status'  => $t->status,
                'due_date' => $t->due_date?->format('d/m/Y'),
                'url'     => "/tarefas?task={$t->id}",
            ]);

        return response()->json([
            'leads'         => $leads,
            'conversations' => $conversations,
            'tasks'         => $tasks,
        ]);
    }
}
