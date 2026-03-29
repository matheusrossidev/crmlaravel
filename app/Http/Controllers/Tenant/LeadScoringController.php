<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ScoringRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadScoringController extends Controller
{
    public function index(): View
    {
        $rules = ScoringRule::orderBy('sort_order')->orderBy('name')->get();

        return view('tenant.settings.lead-scoring', compact('rules'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'category'       => 'required|string|in:engagement,pipeline,profile',
            'event_type'     => 'required|string|max:50',
            'conditions'     => 'nullable|array',
            'points'         => 'required|integer|min:-100|max:100',
            'is_active'      => 'boolean',
            'cooldown_hours' => 'integer|min:0|max:720',
        ]);

        $data['sort_order'] = ScoringRule::max('sort_order') + 1;

        $rule = ScoringRule::create($data);

        return response()->json(['success' => true, 'rule' => $rule]);
    }

    public function update(Request $request, ScoringRule $rule): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'category'       => 'required|string|in:engagement,pipeline,profile',
            'event_type'     => 'required|string|max:50',
            'conditions'     => 'nullable|array',
            'points'         => 'required|integer|min:-100|max:100',
            'is_active'      => 'boolean',
            'cooldown_hours' => 'integer|min:0|max:720',
        ]);

        $rule->update($data);

        return response()->json(['success' => true, 'rule' => $rule]);
    }

    public function destroy(ScoringRule $rule): JsonResponse
    {
        $rule->delete();

        return response()->json(['success' => true]);
    }
}
