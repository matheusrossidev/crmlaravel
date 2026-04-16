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
    use Traits\ChecksMasterPermission;

    public function index(): View
    {
        $this->authorizeModule('plans');
        $plans    = PlanDefinition::orderBy('group_slug')
            ->orderBy('billing_cycle')
            ->orderBy('price_monthly')
            ->get();
        $limits   = config('plan_limits', []);
        $features = \App\Models\FeatureFlag::orderBy('sort_order')->get();

        return view('master.plans.index', compact('plans', 'limits', 'features'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:50|unique:plan_definitions,name',
            'display_name'         => 'required|string|max:100',
            'price_monthly'        => 'required|numeric|min:0',
            'price_usd'            => 'nullable|numeric|min:0',
            'billing_cycle'        => 'nullable|in:monthly,yearly',
            'group_slug'           => 'nullable|string|max:50',
            'stripe_price_id'      => 'nullable|string|max:191',  // legacy
            'stripe_price_id_brl'  => 'nullable|string|max:191',
            'stripe_price_id_usd'  => 'nullable|string|max:191',
            'trial_days'           => 'nullable|integer|min:0|max:365',
            'features_json'        => 'nullable|array',
            'features_en_json'     => 'nullable|array',
            'is_active'            => 'nullable|boolean',
            'is_visible'           => 'nullable|boolean',
            'is_recommended'       => 'nullable|boolean',
        ]);

        $data['is_active']      = $request->boolean('is_active', true);
        $data['is_visible']     = $request->boolean('is_visible', true);
        $data['is_recommended'] = $request->boolean('is_recommended', false);
        $data['billing_cycle']  = $data['billing_cycle'] ?? 'monthly';
        $data['group_slug']     = $data['group_slug'] ?: null;
        $data['trial_days']     = isset($data['trial_days']) ? (int) $data['trial_days'] : null;

        $plan = PlanDefinition::create($data);

        $this->ensureSingleRecommended($plan);

        return response()->json(['success' => true, 'plan' => $plan->fresh()]);
    }

    public function update(Request $request, PlanDefinition $plan): JsonResponse
    {
        $data = $request->validate([
            'display_name'         => 'required|string|max:100',
            'price_monthly'        => 'required|numeric|min:0',
            'price_usd'            => 'nullable|numeric|min:0',
            'billing_cycle'        => 'nullable|in:monthly,yearly',
            'group_slug'           => 'nullable|string|max:50',
            'stripe_price_id'      => 'nullable|string|max:191',  // legacy
            'stripe_price_id_brl'  => 'nullable|string|max:191',
            'stripe_price_id_usd'  => 'nullable|string|max:191',
            'trial_days'           => 'nullable|integer|min:0|max:365',
            'features_json'        => 'nullable|array',
            'features_en_json'     => 'nullable|array',
            'is_active'            => 'nullable|boolean',
            'is_visible'           => 'nullable|boolean',
            'is_recommended'       => 'nullable|boolean',
        ]);

        $data['is_active']      = $request->boolean('is_active', true);
        $data['is_visible']     = $request->boolean('is_visible', true);
        $data['is_recommended'] = $request->boolean('is_recommended', false);
        $data['billing_cycle']  = $data['billing_cycle'] ?? 'monthly';
        $data['group_slug']     = $data['group_slug'] ?: null;
        $data['trial_days']     = isset($data['trial_days']) ? (int) $data['trial_days'] : null;

        $plan->update($data);

        $this->ensureSingleRecommended($plan->fresh());

        return response()->json(['success' => true, 'plan' => $plan->fresh()]);
    }

    /**
     * Quando um plano e marcado como recomendado, desmarca os outros do mesmo grupo
     * (mensal + anual do mesmo grupo podem estar marcados, mas apenas UM grupo
     * ativo por vez — entao limpa grupos diferentes).
     */
    private function ensureSingleRecommended(PlanDefinition $plan): void
    {
        if (! $plan->is_recommended) {
            return;
        }

        $query = PlanDefinition::where('is_recommended', true)
            ->where('id', '!=', $plan->id);

        if ($plan->group_slug) {
            $query->where(function ($q) use ($plan) {
                $q->whereNull('group_slug')->orWhere('group_slug', '!=', $plan->group_slug);
            });
        }

        $query->update(['is_recommended' => false]);
    }

    public function destroy(PlanDefinition $plan): JsonResponse
    {
        $plan->delete();

        return response()->json(['success' => true]);
    }
}
