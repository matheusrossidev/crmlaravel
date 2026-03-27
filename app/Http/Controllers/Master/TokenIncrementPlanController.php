<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\TenantTokenIncrement;
use App\Models\TokenIncrementPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TokenIncrementPlanController extends Controller
{
    public function index(): View
    {
        $plans = TokenIncrementPlan::orderBy('tokens_amount')->get();

        return view('master.token-increment-plans.index', compact('plans'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'display_name'    => 'required|string|max:100',
            'tokens_amount'   => 'required|integer|min:1',
            'price'           => 'required|numeric|min:0',
            'price_usd'       => 'nullable|numeric|min:0',
            'stripe_price_id' => 'nullable|string|max:191',
            'is_active'       => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $plan = TokenIncrementPlan::create($data);

        return response()->json(['success' => true, 'plan' => $plan]);
    }

    public function update(Request $request, TokenIncrementPlan $tokenIncrementPlan): JsonResponse
    {
        $data = $request->validate([
            'display_name'    => 'required|string|max:100',
            'tokens_amount'   => 'required|integer|min:1',
            'price'           => 'required|numeric|min:0',
            'price_usd'       => 'nullable|numeric|min:0',
            'stripe_price_id' => 'nullable|string|max:191',
            'is_active'       => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $tokenIncrementPlan->update($data);

        return response()->json(['success' => true, 'plan' => $tokenIncrementPlan->fresh()]);
    }

    public function destroy(TokenIncrementPlan $tokenIncrementPlan): JsonResponse
    {
        $hasPurchases = TenantTokenIncrement::where('token_increment_plan_id', $tokenIncrementPlan->id)
            ->whereIn('status', ['pending', 'paid'])
            ->exists();

        if ($hasPurchases) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir: existem compras vinculadas a este pacote.',
            ], 422);
        }

        $tokenIncrementPlan->delete();

        return response()->json(['success' => true]);
    }
}
