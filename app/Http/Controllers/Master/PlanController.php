<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\PlanDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = PlanDefinition::orderBy('price_monthly')->get();

        return view('master.plans.index', compact('plans'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:50|unique:plan_definitions,name',
            'display_name'  => 'required|string|max:100',
            'price_monthly' => 'required|numeric|min:0',
            'trial_days'    => 'nullable|integer|min:0|max:365',
            'features_json' => 'nullable|array',
            'is_active'     => 'nullable|boolean',
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['trial_days'] = isset($data['trial_days']) ? (int) $data['trial_days'] : null;

        $plan = PlanDefinition::create($data);

        return response()->json(['success' => true, 'plan' => $plan]);
    }

    public function update(Request $request, PlanDefinition $plan): JsonResponse
    {
        $data = $request->validate([
            'display_name'  => 'required|string|max:100',
            'price_monthly' => 'required|numeric|min:0',
            'trial_days'    => 'nullable|integer|min:0|max:365',
            'features_json' => 'nullable|array',
            'is_active'     => 'nullable|boolean',
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['trial_days'] = isset($data['trial_days']) ? (int) $data['trial_days'] : null;

        $plan->update($data);

        return response()->json(['success' => true, 'plan' => $plan->fresh()]);
    }

    public function destroy(PlanDefinition $plan): JsonResponse
    {
        $plan->delete();

        return response()->json(['success' => true]);
    }
}
