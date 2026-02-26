<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeConversation;
use App\Models\AiAnalystSuggestion;
use App\Models\WhatsappConversation;
use App\Services\ConversationAnalystService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiAnalystController extends Controller
{
    public function __construct(
        private readonly ConversationAnalystService $analyst,
    ) {}

    /**
     * Lista sugestões pendentes de uma conversa.
     */
    public function index(WhatsappConversation $conversation): JsonResponse
    {
        $suggestions = AiAnalystSuggestion::forConversation($conversation->id)
            ->pending()
            ->with('lead')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($s) => $this->formatSuggestion($s));

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Aprova e aplica uma sugestão.
     */
    public function approve(AiAnalystSuggestion $suggestion): JsonResponse
    {
        if ($suggestion->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        if ($suggestion->status !== 'pending') {
            return response()->json(['error' => 'Sugestão não está pendente.'], 422);
        }

        $this->analyst->applySuggestion($suggestion);

        return response()->json(['ok' => true]);
    }

    /**
     * Rejeita uma sugestão.
     */
    public function reject(AiAnalystSuggestion $suggestion): JsonResponse
    {
        if ($suggestion->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        if ($suggestion->status !== 'pending') {
            return response()->json(['error' => 'Sugestão não está pendente.'], 422);
        }

        $suggestion->update(['status' => 'rejected']);

        return response()->json(['ok' => true]);
    }

    /**
     * Aprova todas as sugestões pendentes de uma conversa.
     */
    public function approveAll(WhatsappConversation $conversation): JsonResponse
    {
        $suggestions = AiAnalystSuggestion::forConversation($conversation->id)
            ->pending()
            ->get();

        foreach ($suggestions as $suggestion) {
            $this->analyst->applySuggestion($suggestion);
        }

        return response()->json(['approved' => $suggestions->count()]);
    }

    /**
     * Aciona análise manual (síncrona) com LLM.
     */
    public function trigger(WhatsappConversation $conversation): JsonResponse
    {
        AnalyzeConversation::dispatchSync($conversation->id, force: true);

        $suggestions = AiAnalystSuggestion::forConversation($conversation->id)
            ->pending()
            ->with('lead')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($s) => $this->formatSuggestion($s));

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Contagem de sugestões pendentes do tenant (para badge do sino).
     */
    public function pendingCount(Request $request): JsonResponse
    {
        $count = AiAnalystSuggestion::pending()->count();

        // Retorna as 10 mais recentes para mostrar no dropdown
        $recent = AiAnalystSuggestion::pending()
            ->with(['lead', 'conversation'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'id'              => $s->id,
                'type'            => $s->type,
                'type_label'      => $this->typeLabel($s->type),
                'lead_name'       => $s->lead?->name ?? 'Lead',
                'conversation_id' => $s->conversation_id,
                'payload'         => $s->payload,
                'reason'          => $s->reason,
                'time_ago'        => $s->created_at->diffForHumans(),
            ]);

        return response()->json([
            'count'   => $count,
            'recent'  => $recent,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formatSuggestion(AiAnalystSuggestion $s): array
    {
        return [
            'id'         => $s->id,
            'type'       => $s->type,
            'type_label' => $this->typeLabel($s->type),
            'payload'    => $s->payload,
            'reason'     => $s->reason,
            'status'     => $s->status,
            'created_at' => $s->created_at->diffForHumans(),
        ];
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'stage_change' => 'Mover etapa',
            'add_tag'      => 'Adicionar tag',
            'add_note'     => 'Criar nota',
            'fill_field'   => 'Preencher campo',
            'update_lead'  => 'Atualizar lead',
            default        => $type,
        };
    }
}
