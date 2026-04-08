<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\Automation;
use App\Models\ChatbotFlow;
use App\Models\Department;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\User;
use App\Models\WhatsappInstance;
use App\Models\WhatsappTag;
use App\Services\AutomationTemplateInstaller;
use App\Support\AutomationTemplates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AutomationController extends Controller
{
    private function loadFormData(): array
    {
        $tenantId = activeTenantId();

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

        $wahaConnected = WhatsappInstance::where('status', 'connected')->exists();

        $whatsappInstances = WhatsappInstance::where('status', 'connected')
            ->orderBy('label')
            ->get(['id', 'label', 'phone_number', 'session_name']);

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

        $knownSources = ['manual', 'facebook', 'google', 'instagram', 'whatsapp', 'site', 'indicacao', 'outro'];
        $allLeadSources = collect(array_unique(array_merge($knownSources, $leadSources->toArray())))
            ->sort()
            ->values();

        $campaigns = \App\Models\Campaign::orderBy('name')->get(['id', 'name']);

        $dateCustomFields = \App\Models\CustomFieldDefinition::where('is_active', true)
            ->where('field_type', 'date')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'label']);

        // All custom fields (used by ai_extract_fields and send_webhook builders)
        $allCustomFields = \App\Models\CustomFieldDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'label', 'field_type']);

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return compact('pipelines', 'users', 'aiAgents', 'chatbotFlows', 'wahaConnected',
                       'whatsappInstances', 'whatsappTags', 'leadTags', 'leadSources',
                       'allLeadSources', 'campaigns', 'dateCustomFields', 'allCustomFields',
                       'departments');
    }

    public function index(): View
    {
        $automations         = Automation::orderByDesc('created_at')->get();
        $templates           = AutomationTemplates::all();
        $templateCategories  = AutomationTemplates::categories();

        return view('tenant.settings.automations', array_merge(
            [
                'automations'        => $automations,
                'templates'          => $templates,
                'templateCategories' => $templateCategories,
            ],
            $this->loadFormData()
        ));
    }

    /**
     * Instala um template de automação (biblioteca de modelos).
     */
    public function installTemplate(string $slug, AutomationTemplateInstaller $installer): JsonResponse
    {
        try {
            $tenantId   = activeTenantId();
            $automation = $installer->install($tenantId, $slug);
            return response()->json(['success' => true, 'automation' => $automation]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
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
            'trigger_type'   => 'required|string|in:message_received,conversation_created,lead_created,lead_stage_changed,lead_won,lead_lost,date_field,recurring',
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
            'trigger_type'   => 'required|string|in:message_received,conversation_created,lead_created,lead_stage_changed,lead_won,lead_lost,date_field,recurring',
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

    /**
     * Dispara o webhook configurado pra um lead real do tenant (mais recente)
     * pra validar URL/headers/body sem precisar salvar a automação.
     */
    public function testWebhook(Request $request): JsonResponse
    {
        $config = $request->validate([
            'url'         => 'required|string|max:2000',
            'method'      => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE',
            'headers'     => 'nullable|array',
            'body_mode'   => 'nullable|string|in:builder,raw',
            'body_fields' => 'nullable|array',
            'body_raw'    => 'nullable|string',
        ]);

        $tenantId = activeTenantId();

        // Pega o lead mais recente do tenant pra teste com dado real
        $lead = Lead::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->with(['stage', 'pipeline', 'assignedTo'])
            ->orderByDesc('id')
            ->first();

        if (! $lead) {
            return response()->json([
                'success' => false,
                'message' => __('automations.wh_test_no_lead'),
            ], 422);
        }

        $tenant = \App\Models\Tenant::withoutGlobalScope('tenant')->find($tenantId);

        $context = [
            'lead'         => $lead,
            'tenant'       => $tenant,
            'trigger_type' => 'manual_test',
        ];

        $dispatcher = new \App\Services\WebhookDispatcherService();
        $result = $dispatcher->dispatch($config, $context);

        return response()->json([
            'success'      => true,
            'status'       => $result['status'],
            'duration_ms'  => $result['duration_ms'],
            'error'        => $result['error'],
            'request_body' => mb_substr($result['request_body'] ?? '', 0, 3000),
            'response_body'=> mb_substr($result['body'] ?? '', 0, 3000),
            'lead_used'    => [
                'id'   => $lead->id,
                'name' => $lead->name,
            ],
        ]);
    }
}
