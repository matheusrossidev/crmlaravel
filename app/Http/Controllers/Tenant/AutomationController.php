<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\Automation;
use App\Models\ChatbotFlow;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\User;
use App\Models\WhatsappInstance;
use App\Models\WhatsappTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AutomationController extends Controller
{
    private function loadFormData(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $pipelines = Pipeline::with(['stages' => fn ($q) => $q->orderBy('position')])
            ->orderBy('sort_order')
            ->get();

        $users = User::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $aiAgents = AiAgent::where('is_active', true)
            ->where('channel', 'whatsapp')
            ->orderBy('name')
            ->get(['id', 'name']);

        $chatbotFlows = ChatbotFlow::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $wahaConnected = WhatsappInstance::where('status', 'WORKING')->exists();

        $whatsappTags = WhatsappTag::orderBy('name')->get(['id', 'name', 'color']);

        $leadTags = Lead::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatMap(fn ($t) => is_array($t) ? $t : (json_decode($t, true) ?? []))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $leadSources = Lead::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        return compact('pipelines', 'users', 'aiAgents', 'chatbotFlows', 'wahaConnected', 'whatsappTags', 'leadTags', 'leadSources');
    }

    public function index(): View
    {
        $automations = Automation::orderByDesc('created_at')->get();

        return view('tenant.settings.automations', array_merge(
            ['automations' => $automations],
            $this->loadFormData()
        ));
    }

    public function create(): View
    {
        return view('tenant.settings.automation-form', $this->loadFormData());
    }

    public function edit(Automation $automation): View
    {
        return view('tenant.settings.automation-form', array_merge(
            ['automation' => $automation],
            $this->loadFormData()
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'trigger_type'   => 'required|string|in:message_received,conversation_created,lead_created,lead_stage_changed,lead_won,lead_lost',
            'trigger_config' => 'nullable|array',
            'conditions'     => 'nullable|array',
            'actions'        => 'required|array|min:1',
        ]);

        $data['is_active'] = true;

        $automation = Automation::create($data);

        return response()->json(['success' => true, 'automation' => $automation]);
    }

    public function update(Request $request, Automation $automation): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'trigger_type'   => 'required|string|in:message_received,conversation_created,lead_created,lead_stage_changed,lead_won,lead_lost',
            'trigger_config' => 'nullable|array',
            'conditions'     => 'nullable|array',
            'actions'        => 'required|array|min:1',
        ]);

        $automation->update($data);

        return response()->json(['success' => true, 'automation' => $automation]);
    }

    public function destroy(Automation $automation): JsonResponse
    {
        $automation->delete();

        return response()->json(['success' => true]);
    }

    public function toggle(Automation $automation): JsonResponse
    {
        $automation->update(['is_active' => ! $automation->is_active]);

        return response()->json(['success' => true, 'is_active' => $automation->is_active]);
    }
}
