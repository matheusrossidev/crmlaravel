<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SalesGoal;
use App\Models\User;
use App\Services\SalesGoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesGoalController extends Controller
{
    public function index(SalesGoalService $service): View
    {
        $goals = SalesGoal::with('user:id,name')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (SalesGoal $g) => [
                'goal'     => $g,
                'progress' => $service->progress($g),
            ]);

        $users = User::where('tenant_id', activeTenantId())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tenant.goals.index', compact('goals', 'users'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'      => 'nullable|exists:users,id',
            'type'         => 'required|in:leads_won,revenue,leads_created',
            'period'       => 'required|in:monthly,weekly,quarterly',
            'target_value' => 'required|numeric|min:1',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after:start_date',
        ]);

        $goal = SalesGoal::create([
            ...$data,
            'created_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'goal' => $goal]);
    }

    public function update(SalesGoal $goal, Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'      => 'nullable|exists:users,id',
            'type'         => 'required|in:leads_won,revenue,leads_created',
            'target_value' => 'required|numeric|min:1',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after:start_date',
        ]);

        $goal->update($data);

        return response()->json(['success' => true, 'goal' => $goal->fresh()]);
    }

    public function destroy(SalesGoal $goal): JsonResponse
    {
        $goal->delete();
        return response()->json(['success' => true]);
    }
}
