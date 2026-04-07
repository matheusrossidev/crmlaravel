<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Pipeline;
use App\Models\ScoringRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadScoringController extends Controller
{
    /**
     * Validation rules compartilhadas entre store/update.
     * Centralizado pra evitar drift.
     */
    private function ruleValidation(): array
    {
        return [
            'name'                  => 'required|string|max:100',
            'category'              => 'required|string|in:engagement,pipeline,profile',
            'event_type'            => 'required|string|max:50',
            'pipeline_id'           => 'nullable|integer|exists:pipelines,id',
            'stage_id'              => 'nullable|integer|exists:pipeline_stages,id',
            'conditions'            => 'nullable|array',
            'points'                => 'required|integer|min:-100|max:100',
            'is_active'             => 'boolean',
            'cooldown_hours'        => 'integer|min:0|max:720',
            'valid_from'            => 'nullable|date',
            'valid_until'           => 'nullable|date|after_or_equal:valid_from',
            'max_triggers_per_lead' => 'nullable|integer|min:1|max:1000',
        ];
    }

    public function index(): View
    {
        $rules = ScoringRule::orderBy('sort_order')->orderBy('name')->get();

        // Pipelines + stages eager pra popular os selects do form (Fase 1)
        $pipelines = Pipeline::with(['stages' => fn ($q) => $q->orderBy('position')])
            ->orderBy('sort_order')
            ->get();

        // Score limits globais (Fix 7) — armazenados em tenants.settings_json
        $tenant = auth()->user()->tenant;
        $settings = $tenant?->settings_json ?? [];
        $scoreSettings = [
            'min' => (int) ($settings['score_min'] ?? 0),
            'max' => $settings['score_max'] ?? null,
        ];

        return view('tenant.settings.lead-scoring', compact('rules', 'pipelines', 'scoreSettings'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->ruleValidation());

        $data['sort_order'] = (int) ScoringRule::max('sort_order') + 1;

        $rule = ScoringRule::create($data);

        return response()->json(['success' => true, 'rule' => $rule]);
    }

    public function update(Request $request, ScoringRule $rule): JsonResponse
    {
        $data = $request->validate($this->ruleValidation());

        $rule->update($data);

        return response()->json(['success' => true, 'rule' => $rule]);
    }

    public function destroy(ScoringRule $rule): JsonResponse
    {
        $rule->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Fase 1 (Fix 7) — Atualiza limites globais de score do tenant.
     * Salvo em tenants.settings_json (já existe).
     *
     * - score_min: piso (default 0). Score nunca cai abaixo.
     * - score_max: teto (nullable). Null = sem teto.
     */
    public function updateScoreSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'score_min' => 'nullable|integer|min:-1000|max:1000',
            'score_max' => 'nullable|integer|min:-1000|max:10000',
        ]);

        $tenant = auth()->user()->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant não encontrado.'], 404);
        }

        $settings = $tenant->settings_json ?? [];
        $settings['score_min'] = $data['score_min'] ?? 0;
        $settings['score_max'] = $data['score_max'] ?? null;

        $tenant->update(['settings_json' => $settings]);

        return response()->json([
            'success' => true,
            'settings' => [
                'min' => (int) $settings['score_min'],
                'max' => $settings['score_max'],
            ],
        ]);
    }
}
