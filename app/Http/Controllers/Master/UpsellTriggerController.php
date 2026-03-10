<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\PlanDefinition;
use App\Models\UpsellTrigger;
use App\Models\UpsellTriggerLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UpsellTriggerController extends Controller
{
    public function index(): View
    {
        $triggers = UpsellTrigger::withCount('logs')
            ->orderByDesc('priority')
            ->get();

        $plans = PlanDefinition::where('is_active', true)
            ->orderBy('price_monthly')
            ->get(['name', 'display_name']);

        // Stats
        $totalActive   = UpsellTrigger::active()->count();
        $firesThisMonth = UpsellTriggerLog::withoutGlobalScope('tenant')
            ->whereMonth('fired_at', now()->month)
            ->whereYear('fired_at', now()->year)
            ->count();
        $clicksThisMonth = UpsellTriggerLog::withoutGlobalScope('tenant')
            ->whereMonth('fired_at', now()->month)
            ->whereYear('fired_at', now()->year)
            ->whereNotNull('clicked_at')
            ->count();
        $conversionsThisMonth = UpsellTriggerLog::withoutGlobalScope('tenant')
            ->whereMonth('fired_at', now()->month)
            ->whereYear('fired_at', now()->year)
            ->whereNotNull('converted_at')
            ->count();

        return view('master.upsell.index', compact(
            'triggers',
            'plans',
            'totalActive',
            'firesThisMonth',
            'clicksThisMonth',
            'conversionsThisMonth',
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:191',
            'source_plan'     => 'nullable|string|exists:plan_definitions,name',
            'target_plan'     => 'required|string|exists:plan_definitions,name',
            'metric'          => 'required|in:leads,users,pipelines,ai_agents,ai_tokens,chatbot_flows,automations',
            'threshold_type'  => 'required|in:percentage,absolute',
            'threshold_value' => 'required|numeric|min:1',
            'action_type'     => 'required|in:banner,notification,email,all',
            'action_config'   => 'nullable|array',
            'cooldown_hours'  => 'required|integer|min:1|max:8760',
            'priority'        => 'nullable|integer|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['priority']  = $data['priority'] ?? 0;

        if (empty($data['source_plan'])) {
            $data['source_plan'] = null;
        }

        $trigger = UpsellTrigger::create($data);

        return response()->json(['success' => true, 'trigger' => $trigger]);
    }

    public function update(Request $request, UpsellTrigger $trigger): JsonResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:191',
            'source_plan'     => 'nullable|string|exists:plan_definitions,name',
            'target_plan'     => 'required|string|exists:plan_definitions,name',
            'metric'          => 'required|in:leads,users,pipelines,ai_agents,ai_tokens,chatbot_flows,automations',
            'threshold_type'  => 'required|in:percentage,absolute',
            'threshold_value' => 'required|numeric|min:1',
            'action_type'     => 'required|in:banner,notification,email,all',
            'action_config'   => 'nullable|array',
            'cooldown_hours'  => 'required|integer|min:1|max:8760',
            'priority'        => 'nullable|integer|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['priority']  = $data['priority'] ?? 0;

        if (empty($data['source_plan'])) {
            $data['source_plan'] = null;
        }

        $trigger->update($data);

        return response()->json(['success' => true, 'trigger' => $trigger->fresh()]);
    }

    public function destroy(UpsellTrigger $trigger): JsonResponse
    {
        $trigger->delete();

        return response()->json(['success' => true]);
    }

    public function logs(UpsellTrigger $trigger): JsonResponse
    {
        $logs = $trigger->logs()
            ->withoutGlobalScope('tenant')
            ->with('tenant:id,name,plan')
            ->orderByDesc('fired_at')
            ->limit(100)
            ->get();

        return response()->json(['success' => true, 'logs' => $logs]);
    }
}
