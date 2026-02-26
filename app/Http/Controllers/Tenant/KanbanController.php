<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\KanbanTemplateExport;
use App\Exports\LeadsExport;
use App\Http\Controllers\Controller;
use App\Imports\KanbanImport;
use App\Imports\KanbanPreviewImport;
use App\Models\Campaign;
use App\Models\CustomFieldDefinition;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\LostSale;
use App\Models\LostSaleReason;
use App\Models\Sale;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Models\WhatsappTag;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KanbanController extends Controller
{
    public function index(Request $request): View
    {
        $pipelines = Pipeline::orderBy('sort_order')->get();

        $pipelineId = $request->get('pipeline_id');

        $pipeline = $pipelineId
            ? Pipeline::with('stages')->findOrFail($pipelineId)
            : Pipeline::with('stages')->where('is_default', true)->first()
                ?? Pipeline::with('stages')->first();

        $campaigns = Campaign::orderBy('name')->get(['id', 'name']);

        $stages = collect();
        if ($pipeline) {
            $stages = $pipeline->stages->map(function (PipelineStage $stage) use ($request) {
                $query = Lead::where('stage_id', $stage->id)
                    ->with(['campaign', 'assignedTo', 'customFieldValues.fieldDefinition', 'whatsappConversation.aiAgent'])
                    ->orderByDesc('created_at');

                if ($source = $request->get('source')) {
                    $query->where('source', $source);
                }

                if ($campaignId = $request->get('campaign_id')) {
                    $query->where('campaign_id', $campaignId);
                }

                if ($dateFrom = $request->get('date_from')) {
                    $query->whereDate('created_at', '>=', $dateFrom);
                }

                if ($dateTo = $request->get('date_to')) {
                    $query->whereDate('created_at', '<=', $dateTo);
                }

                if ($tag = $request->get('tag')) {
                    $query->whereJsonContains('tags', $tag);
                }

                $responsible = $request->get('responsible', []);
                if (!empty($responsible)) {
                    $query->where(function ($q) use ($responsible) {
                        $userIds = array_values(array_filter($responsible, fn ($r) => is_numeric($r)));
                        $hasAi   = in_array('ai', $responsible);
                        if ($userIds) {
                            $q->whereIn('assigned_to', $userIds);
                        }
                        if ($hasAi) {
                            $q->orWhereHas('whatsappConversation', fn ($wq) => $wq->whereNotNull('ai_agent_id'));
                        }
                    });
                }

                $leads = $query->get();

                // Pre-compute custom field data per lead (uses already-eager-loaded relations)
                $leadCf = [];
                foreach ($leads as $lead) {
                    $cf = [];
                    foreach ($lead->customFieldValues as $cfv) {
                        $def = $cfv->fieldDefinition;
                        if (!$def) {
                            continue;
                        }
                        $cf[$def->name] = [
                            'label' => $def->label,
                            'type'  => $def->field_type,
                            'value' => match ($def->field_type) {
                                'number', 'currency' => $cfv->value_number,
                                'date'               => $cfv->value_date instanceof \Carbon\Carbon ? $cfv->value_date->format('Y-m-d') : $cfv->value_date,
                                'checkbox'           => (bool) $cfv->value_boolean,
                                'multiselect'        => $cfv->value_json ?? [],
                                default              => $cfv->value_text,
                            },
                        ];
                    }
                    $leadCf[$lead->id] = $cf;
                }

                return [
                    'id'          => $stage->id,
                    'name'        => $stage->name,
                    'color'       => $stage->color,
                    'is_won'      => $stage->is_won,
                    'is_lost'     => $stage->is_lost,
                    'leads'       => $leads,
                    'lead_cf'     => $leadCf,
                    'count'       => $leads->count(),
                    'total_value' => (int) $leads->sum('value'),
                ];
            });
        }

        $lostReasons     = LostSaleReason::where('is_active', true)->orderBy('sort_order')->get();
        $customFieldDefs = CustomFieldDefinition::where('is_active', true)->orderBy('sort_order')->get();

        // Tags disponíveis para o filtro:
        //   1. Tags configuradas em Configurações → Tags (WhatsappTag, com cor)
        //   2. + tags livres já usadas nos leads do funil (leads.tags JSON)
        $configuredTags = WhatsappTag::orderBy('sort_order')->get(['name', 'color']);

        $leadTagNames = collect();
        if ($pipeline) {
            $leadTagNames = Lead::whereHas('stage', fn ($q) => $q->where('pipeline_id', $pipeline->id))
                ->whereNotNull('tags')
                ->pluck('tags')
                ->flatten()
                ->filter()
                ->unique();
        }

        // Nomes das tags configuradas (para deduplicar)
        $configuredNames = $configuredTags->pluck('name')->map(fn ($n) => mb_strtolower($n));

        // Tags livres não presentes nas configuradas
        $extraTags = $leadTagNames
            ->filter(fn ($t) => !$configuredNames->contains(mb_strtolower($t)))
            ->sort()
            ->values()
            ->map(fn ($name) => (object) ['name' => $name, 'color' => null]);

        $availableTags = $configuredTags->concat($extraTags);

        $users = User::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tenant.crm.kanban', compact('pipelines', 'pipeline', 'stages', 'campaigns', 'lostReasons', 'customFieldDefs', 'availableTags', 'users'));
    }

    public function updateStage(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'stage_id'       => 'required|integer|exists:pipeline_stages,id',
            'pipeline_id'    => 'required|integer|exists:pipelines,id',
            'value'          => 'nullable|numeric|min:0',
            'lost_reason_id' => 'nullable|integer|exists:lost_sale_reasons,id',
        ]);

        $oldStageId = $lead->stage_id;

        $updateData = [
            'stage_id'    => $data['stage_id'],
            'pipeline_id' => $data['pipeline_id'],
        ];

        if (array_key_exists('value', $data) && $data['value'] !== null) {
            $updateData['value'] = $data['value'];
        }

        $lead->update($updateData);

        if ($oldStageId !== (int) $data['stage_id']) {
            $newStage = PipelineStage::find($data['stage_id']);

            LeadEvent::create([
                'lead_id'      => $lead->id,
                'event_type'   => 'stage_changed',
                'description'  => "Movido para {$newStage?->name}",
                'performed_by' => auth()->id(),
                'created_at'   => now(),
            ]);

            if ($newStage?->is_won) {
                Sale::create([
                    'lead_id'     => $lead->id,
                    'pipeline_id' => $data['pipeline_id'],
                    'campaign_id' => $lead->campaign_id,
                    'value'       => $data['value'] ?? $lead->value,
                    'closed_by'   => auth()->id(),
                    'closed_at'   => now(),
                ]);
            }

            if ($newStage?->is_lost) {
                LostSale::create([
                    'lead_id'     => $lead->id,
                    'pipeline_id' => $data['pipeline_id'],
                    'campaign_id' => $lead->campaign_id,
                    'reason_id'   => !empty($data['lost_reason_id']) ? $data['lost_reason_id'] : null,
                    'lost_at'     => now(),
                    'lost_by'     => auth()->id(),
                ]);
            }
        }

        return response()->json(['success' => true, 'lead_id' => $lead->id]);
    }

    // ── GET /crm/exportar?pipeline_id=X&[filters] ────────────────────────
    public function export(Request $request): BinaryFileResponse
    {
        $pipelineId = (int) $request->get('pipeline_id', 0);

        $filters = array_merge(
            $request->only(['source', 'campaign_id', 'date_from', 'date_to', 'tag', 'stage_id']),
            $pipelineId ? ['pipeline_id' => $pipelineId] : []
        );

        $filename = 'leads-kanban-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new LeadsExport($filters), $filename);
    }

    // ── POST /crm/importar/preview ────────────────────────────────────────
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file'        => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'pipeline_id' => 'required|integer|exists:pipelines,id',
        ]);

        $pipelineId   = (int) $request->pipeline_id;
        $pipeline     = Pipeline::with('stages')->findOrFail($pipelineId);
        $stagesByName = $pipeline->stages->sortBy('position')
            ->mapWithKeys(fn ($s) => [mb_strtolower($s->name) => $s->id]);

        $importer = new KanbanPreviewImport($stagesByName);
        Excel::import($importer, $request->file('file'));
        $rows = $importer->getRows();

        // Salvar arquivo temporário (expira em 30 min)
        $tempDir = storage_path('app/private/imports');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $filename = uniqid('import_', true) . '.' . $request->file('file')->getClientOriginalExtension();
        $request->file('file')->move($tempDir, $filename);

        $token = encrypt([
            'path'        => $tempDir . DIRECTORY_SEPARATOR . $filename,
            'pipeline_id' => $pipelineId,
            'tenant_id'   => auth()->user()->tenant_id,
            'expires_at'  => now()->addMinutes(30)->timestamp,
        ]);

        return response()->json([
            'success' => true,
            'rows'    => $rows,
            'total'   => count($rows),
            'skipped' => count(array_filter($rows, fn ($r) => $r['will_skip'])),
            'token'   => $token,
        ]);
    }

    // ── POST /crm/importar ────────────────────────────────────────────────
    public function import(Request $request): JsonResponse
    {
        // Branch A: confirmar preview (token sem file)
        if ($request->filled('token') && !$request->hasFile('file')) {
            return $this->importFromToken($request);
        }

        // Branch B: upload direto (legado / inalterado)
        $request->validate([
            'file'        => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'pipeline_id' => 'required|integer|exists:pipelines,id',
        ]);

        $pipelineId = (int) $request->pipeline_id;
        $pipeline   = Pipeline::with('stages')->findOrFail($pipelineId);

        $stages       = $pipeline->stages->sortBy('position');
        $firstStage   = $stages->first();
        $stagesByName = $stages->mapWithKeys(
            fn ($s) => [mb_strtolower($s->name) => $s->id]
        );

        $importer = new KanbanImport($pipelineId, $firstStage?->id ?? 0, $stagesByName, $pipeline);
        Excel::import($importer, $request->file('file'));

        return response()->json([
            'success'  => true,
            'imported' => $importer->getImported(),
            'skipped'  => $importer->getSkipped(),
        ]);
    }

    private function importFromToken(Request $request): JsonResponse
    {
        $request->validate([
            'token'       => 'required|string',
            'pipeline_id' => 'required|integer|exists:pipelines,id',
        ]);

        try {
            $payload = decrypt($request->token);
        } catch (\Exception) {
            return response()->json(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        if (
            ($payload['tenant_id'] ?? null) !== auth()->user()->tenant_id ||
            ($payload['pipeline_id'] ?? null) !== (int) $request->pipeline_id ||
            ($payload['expires_at'] ?? 0) < now()->timestamp ||
            !file_exists($payload['path'] ?? '')
        ) {
            return response()->json(['success' => false, 'message' => 'Token expirado ou inválido.'], 422);
        }

        $pipelineId   = (int) $request->pipeline_id;
        $pipeline     = Pipeline::with('stages')->findOrFail($pipelineId);
        $stages       = $pipeline->stages->sortBy('position');
        $stagesByName = $stages->mapWithKeys(fn ($s) => [mb_strtolower($s->name) => $s->id]);

        $importer = new KanbanImport($pipelineId, $stages->first()?->id ?? 0, $stagesByName, $pipeline);
        Excel::import($importer, $payload['path']);
        @unlink($payload['path']);

        return response()->json([
            'success'  => true,
            'imported' => $importer->getImported(),
            'skipped'  => $importer->getSkipped(),
        ]);
    }

    // ── GET /crm/template?pipeline_id=X ──────────────────────────────────
    public function template(Request $request): BinaryFileResponse
    {
        $pipelineId = (int) $request->get('pipeline_id', 0);
        $pipeline   = Pipeline::with('stages')->findOrFail($pipelineId);

        // Tags para a planilha: configuradas em Settings + livres dos leads
        $configuredTagNames = WhatsappTag::orderBy('sort_order')->pluck('name');
        $leadTagNames = Lead::whereHas('stage', fn ($q) => $q->where('pipeline_id', $pipelineId))
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->unique();
        $existingTags = $configuredTagNames
            ->concat($leadTagNames->filter(fn ($t) => !$configuredTagNames->map(fn ($n) => mb_strtolower($n))->contains(mb_strtolower($t))))
            ->values();

        $safeName = preg_replace('/[^a-z0-9]+/i', '-', $pipeline->name);

        return Excel::download(
            new KanbanTemplateExport($pipeline, $existingTags),
            "template-{$safeName}.xlsx"
        );
    }

    // ── GET /crm/poll?pipeline_id=X&since=TIMESTAMP ───────────────────────
    public function poll(Request $request): JsonResponse
    {
        $since      = (int) $request->get('since', 0);
        $pipelineId = (int) $request->get('pipeline_id', 0);

        if (!$pipelineId) {
            return response()->json(['leads' => [], 'server_time' => now()->timestamp]);
        }

        $sinceDate = Carbon::createFromTimestamp($since);

        $leads = Lead::with(['campaign', 'assignedTo', 'customFieldValues.fieldDefinition', 'stage', 'whatsappConversation.aiAgent'])
            ->whereHas('stage', fn ($q) => $q->where('pipeline_id', $pipelineId))
            ->where(fn ($q) => $q
                ->where('created_at', '>', $sinceDate)
                ->orWhere('updated_at', '>', $sinceDate)
            )
            ->get();

        return response()->json([
            'leads'       => $leads->map(fn ($l) => $this->formatLead($l))->values(),
            'server_time' => now()->timestamp,
        ]);
    }

    private function formatLead(Lead $lead): array
    {
        $cfFlat = [];
        foreach (($lead->customFields ?? []) as $name => $data) {
            $cfFlat[$name] = $data['value'] ?? null;
        }

        return [
            'id'               => $lead->id,
            'name'             => $lead->name,
            'phone'            => $lead->phone,
            'email'            => $lead->email,
            'value'            => $lead->value,
            'value_fmt'        => $lead->value ? 'R$ ' . number_format((float) $lead->value, 0, ',', '.') : null,
            'source'           => $lead->source,
            'tags'             => $lead->tags ?? [],
            'stage_id'         => $lead->stage_id,
            'pipeline_id'      => $lead->pipeline_id,
            'campaign_id'      => $lead->campaign_id,
            'campaign'         => $lead->campaign ? ['id' => $lead->campaign->id, 'name' => $lead->campaign->name] : null,
            'cf_flat'          => $cfFlat,
            'assigned_to_name' => $lead->assignedTo?->name,
            'ai_agent_name'    => $lead->whatsappConversation?->aiAgent?->name,
            'conversation_id'  => $lead->whatsappConversation?->id,
            'unread_count'     => $lead->whatsappConversation?->unread_count ?? 0,
            'created_at'       => $lead->created_at?->format('d/m/y'),
        ];
    }
}
