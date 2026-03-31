<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SalesGoal;
use App\Models\SalesGoalSnapshot;
use App\Models\User;
use App\Services\SalesGoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesGoalController extends Controller
{
    private const GOAL_TYPES = [
        'leads_won', 'revenue', 'leads_created',
        'messages_sent', 'leads_contacted', 'tasks_completed',
    ];

    public function index(SalesGoalService $service): View
    {
        $goals = SalesGoal::with(['user:id,name', 'children.user:id,name'])
            ->whereNull('parent_goal_id') // only top-level goals
            ->orderByDesc('start_date')
            ->get();

        $enriched = $goals->map(function (SalesGoal $g) use ($service) {
            $isTeam = $g->user_id === null && $g->children->isNotEmpty();

            if ($isTeam) {
                $teamData = $service->teamProgress($g);
                $progress = [
                    'current'    => $teamData['current'],
                    'target'     => $teamData['target'],
                    'percentage' => $teamData['percentage'],
                    'raw_pct'    => $teamData['raw_pct'],
                    'remaining'  => $teamData['remaining'],
                    'status'     => $teamData['status'],
                ];
                $forecast = $service->forecast($g, $progress);
                $contributions = $teamData['contributions'];
            } else {
                $progress = $service->progress($g);
                $forecast = $service->forecast($g, $progress);
                $contributions = null;
            }

            $bonusTier = $service->achievedBonusTier($g, $progress['raw_pct'] ?? $progress['percentage']);

            return [
                'goal'          => $g,
                'progress'      => $progress,
                'forecast'      => $forecast,
                'is_team'       => $isTeam,
                'contributions' => $contributions,
                'bonus_tier'    => $bonusTier,
            ];
        });

        // Separate team goals from individual goals
        $teamGoals       = $enriched->where('is_team', true)->values();
        $individualGoals = $enriched->where('is_team', false)->values();

        // Ranking
        $ranking = $service->ranking(activeTenantId());

        $users = User::where('tenant_id', activeTenantId())
            ->orderBy('name')
            ->get(['id', 'name']);

        $currentUserId = auth()->id();

        return view('tenant.goals.index', compact(
            'teamGoals', 'individualGoals', 'ranking', 'users', 'currentUserId'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'        => 'nullable|exists:users,id',
            'type'           => 'required|in:' . implode(',', self::GOAL_TYPES),
            'period'         => 'required|in:monthly,weekly,quarterly',
            'target_value'   => 'required|numeric|min:1',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'is_recurring'   => 'nullable|boolean',
            'growth_rate'    => 'nullable|numeric|min:0|max:100',
            'bonus_tiers'    => 'nullable|array',
            'bonus_tiers.*.threshold' => 'required_with:bonus_tiers|numeric|min:1',
            'bonus_tiers.*.label'     => 'required_with:bonus_tiers|string|max:100',
            'bonus_tiers.*.value'     => 'nullable|numeric|min:0',
            // Team goal: children
            'children'             => 'nullable|array',
            'children.*.user_id'   => 'required|exists:users,id',
            'children.*.target_value' => 'required|numeric|min:1',
        ]);

        $goal = SalesGoal::create([
            'user_id'      => $data['user_id'] ?? null,
            'type'         => $data['type'],
            'period'       => $data['period'],
            'target_value' => $data['target_value'],
            'start_date'   => $data['start_date'],
            'end_date'     => $data['end_date'],
            'is_recurring' => $data['is_recurring'] ?? false,
            'growth_rate'  => $data['growth_rate'] ?? null,
            'bonus_tiers'  => $data['bonus_tiers'] ?? null,
            'created_by'   => auth()->id(),
        ]);

        // Create child goals for team meta
        if (!empty($data['children'])) {
            foreach ($data['children'] as $child) {
                SalesGoal::create([
                    'user_id'        => $child['user_id'],
                    'type'           => $data['type'],
                    'period'         => $data['period'],
                    'target_value'   => $child['target_value'],
                    'start_date'     => $data['start_date'],
                    'end_date'       => $data['end_date'],
                    'is_recurring'   => $data['is_recurring'] ?? false,
                    'growth_rate'    => $data['growth_rate'] ?? null,
                    'parent_goal_id' => $goal->id,
                    'created_by'     => auth()->id(),
                ]);
            }
        }

        return response()->json(['success' => true, 'goal' => $goal->load('children')]);
    }

    public function update(SalesGoal $goal, Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'      => 'nullable|exists:users,id',
            'type'         => 'required|in:' . implode(',', self::GOAL_TYPES),
            'target_value' => 'required|numeric|min:1',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after:start_date',
            'is_recurring' => 'nullable|boolean',
            'growth_rate'  => 'nullable|numeric|min:0|max:100',
            'bonus_tiers'  => 'nullable|array',
        ]);

        $goal->update($data);

        return response()->json(['success' => true, 'goal' => $goal->fresh()]);
    }

    public function destroy(SalesGoal $goal): JsonResponse
    {
        // Also delete children if team goal
        $goal->children()->delete();
        $goal->delete();

        return response()->json(['success' => true]);
    }

    public function history(SalesGoalService $service, ?int $userId = null): JsonResponse
    {
        $tenantId = activeTenantId();
        $history  = $service->userHistory($tenantId, $userId);

        return response()->json(['success' => true, 'history' => $history]);
    }
}
