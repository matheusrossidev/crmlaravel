<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\KanbanTemplateExport;
use App\Exports\LeadsExport;
use App\Http\Controllers\Controller;
use App\Imports\KanbanImport;
use App\Imports\KanbanPreviewImport;
use App\Models\CustomFieldDefinition;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\LostSale;
use App\Models\LostSaleReason;
use App\Models\Sale;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Models\Task;
use App\Models\WhatsappTag;
use App\Services\AutomationEngine;
use App\Services\StageRequirementService;
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
        $allowedPipelineIds = auth()->user()->allowedPipelineIds();

        $pipelines = Pipeline::with('stages')
            ->when($allowedPipelineIds, fn ($q) => $q->whereIn('id', $allowedPipelineIds))
            ->orderBy('sort_order')->get();

        $pipelineId = $request->get('pipeline_id');

        $pipelineQuery = Pipeline::with('stages')
            ->when($allowedPipelineIds, fn ($q) => $q->whereIn('id', $allowedPipelineIds));

        $pipeline = $pipelineId
            ? $pipelineQuery->findOrFail($pipelineId)
            : (clone $pipelineQuery)->where('is_default', true)->first()
                ?? (clone $pipelineQuery)->first();

        $stages = collect();
        if ($pipeline) {
            $stages = $pipeline->stages->map(function (PipelineStage $stage) use ($request) {
                $query = Lead::where('stage_id', $stage->id)
                    ->where(fn ($q) => $q->where('status', '!=', 'merged')->orWhereNull('status'))
                    ->with(['assignedTo', 'customFieldValues.fieldDefinition', 'whatsappConversation.aiAgent', 'activeSequence'])
                    ->orderByDesc('created_at');

                if ($source = $request->get('source')) {
                    $query->where('source', $source);
                }

                if ($dateFrom = $request->get('date_from')) {
                    $query->whereDate('created_at', '>=', $dateFrom);
                }

                if ($dateTo = $request->get('date_to')) {
                    $query->whereDate('created_at', '<=', $dateTo);
                }

                if ($tag = $request->get('tag')) {
                    $query->filterByTag($tag);
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

                // Pre-compute mandatory task completion status
                $reqStatus = (new StageRequirementService())->getCompletionStatusBatch($leads, $stage->id);

                return [
                    'id'          => $stage->id,
                    'name'        => $stage->name,
                    'color'       => $stage->color,
                    'is_won'      => $stage->is_won,
                    'is_lost'     => $stage->is_lost,
                    'leads'       => $leads,
                    'lead_cf'     => $leadCf,
                    'req_status'  => $reqStatus,
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

        $users = User::where('tenant_id', activeTenantId())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tenant.crm.kanban', compact('pipelines', 'pipeline', 'stages', 'lostReasons', 'customFieldDefs', 'availableTags', 'users'));
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

        // Check mandatory tasks before allowing stage exit
        if ($oldStageId !== (int) $data['stage_id']) {
            $reqService = new StageRequirementService();
            $check = $reqService->canLeaveStage($lead, $oldStageId);
            if (!$check['allowed']) {
                return response()->json([
                    'success'       => false,
                    'blocked'       => true,
                    'message'       => 'Complete as atividades obrigatórias antes de mover o lead.',
                    'pending_tasks' => $check['pending'],
                ], 422);
            }
        }

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

            // Mensagem de evento visível no chat WhatsApp vinculado
            try {
                $waConv = WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('lead_id', $lead->id)
                    ->first();
                if ($waConv) {
                    WhatsappMessage::withoutGlobalScope('tenant')->create([
                        'tenant_id'       => $lead->tenant_id,
                        'conversation_id' => $waConv->id,
                        'waha_message_id' => null,
                        'direction'       => 'outbound',
                        'type'            => 'event',
                        'body'            => auth()->user()->name . " moveu para etapa \"{$newStage?->name}\"",
                        'media_filename'  => 'Etapa alterada',
                        'media_mime'      => 'user_stage_changed',
                        'sent_at'         => now(),
                        'ack'             => 'delivered',
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::error('Falha ao criar evento WhatsApp (kanban)', [
                    'lead_id' => $lead->id,
                    'error'   => $e->getMessage(),
                ]);
            }

            $oldStage = PipelineStage::find($oldStageId);

            if ($newStage?->is_won) {
                // Cria venda se não existir para este lead+pipeline
                Sale::firstOrCreate(
                    ['lead_id' => $lead->id, 'pipeline_id' => $data['pipeline_id']],
                    [
                        'value'       => $data['value'] ?? $lead->value,
                        'closed_by'   => auth()->id(),
                        'closed_at'   => now(),
                    ]
                );
                // Se saiu de "lost", remove o registro de perda
                if ($oldStage?->is_lost) {
                    LostSale::where('lead_id', $lead->id)->where('pipeline_id', $data['pipeline_id'])->delete();
                }
            } elseif ($newStage?->is_lost) {
                // Cria perda se não existir
                LostSale::firstOrCreate(
                    ['lead_id' => $lead->id, 'pipeline_id' => $data['pipeline_id']],
                    [
                        'reason_id'   => !empty($data['lost_reason_id']) ? $data['lost_reason_id'] : null,
                        'lost_at'     => now(),
                        'lost_by'     => auth()->id(),
                    ]
                );
                // Se saiu de "won", remove o registro de venda
                if ($oldStage?->is_won) {
                    Sale::where('lead_id', $lead->id)->where('pipeline_id', $data['pipeline_id'])->delete();
                }
            } else {
                // Moveu para estágio normal — limpa venda e perda se existiam
                if ($oldStage?->is_won) {
                    Sale::where('lead_id', $lead->id)->where('pipeline_id', $data['pipeline_id'])->delete();
                }
                if ($oldStage?->is_lost) {
                    LostSale::where('lead_id', $lead->id)->where('pipeline_id', $data['pipeline_id'])->delete();
                }
            }

            // Automações de etapa
            try {
                $engine  = new AutomationEngine();
                $baseCtx = ['tenant_id' => activeTenantId(), 'lead' => $lead->fresh(), 'stage_new' => $newStage, 'stage_old_id' => $oldStageId];
                $engine->run('lead_stage_changed', $baseCtx);
                if ($newStage?->is_won) {
                    $engine->run('lead_won', $baseCtx);
                }
                if ($newStage?->is_lost) {
                    $engine->run('lead_lost', $baseCtx);
                }
            } catch (\Throwable) {}

            // Create mandatory tasks for the new stage
            try {
                (new StageRequirementService())->createRequiredTasks($lead->fresh(), $newStage);
            } catch (\Throwable) {}

            // Notificação: lead mudou de etapa (para o assigned_to)
            if ($lead->assigned_to && $lead->assigned_to !== auth()->id()) {
                try {
                    (new \App\Services\NotificationDispatcher())->dispatch('lead_stage_changed', [
                        'lead_name'  => $lead->name,
                        'stage_name' => $newStage?->name ?? '',
                        'url'        => route('leads.index', ['lead' => $lead->id]),
                    ], activeTenantId(), targetUserId: $lead->assigned_to);
                } catch (\Throwable) {}
            }
        }

        return response()->json(['success' => true, 'lead_id' => $lead->id]);
    }

    // ── GET /crm/exportar?pipeline_id=X&[filters] ────────────────────────
    public function export(Request $request): BinaryFileResponse
    {
        $pipelineId = (int) $request->get('pipeline_id', 0);

        $filters = array_merge(
            $request->only(['source', 'date_from', 'date_to', 'tag', 'stage_id']),
            $pipelineId ? ['pipeline_id' => $pipelineId] : []
        );

        $filename = 'leads-kanban-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new LeadsExport($filters), $filename);
    }

    // ── POST /crm/importar/preview ────────────────────────────────────────
    // ── Step 1: Upload → headers + suggested mapping ────────────────────
    public function preview(Request $request): JsonResponse
    {
        // Step 2: mapping confirmado → gera preview com dados
        if ($request->filled('token') && $request->filled('mapping')) {
            return $this->previewWithMapping($request);
        }

        // Step 1: upload inicial
        try {
            $request->validate([
                'file'        => 'required|file|max:5120',
                'pipeline_id' => 'required|integer|exists:pipelines,id',
            ]);

            $ext = strtolower($request->file('file')->getClientOriginalExtension());
            if (! in_array($ext, ['xlsx', 'xls', 'csv'])) {
                return response()->json(['success' => false, 'message' => 'Formato invalido. Use .xlsx, .xls ou .csv.'], 422);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Arquivo invalido.',
            ], 422);
        }

        $pipelineId = (int) $request->pipeline_id;

        $tempDir = storage_path('app/private/imports');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $filename = uniqid('import_', true) . '.' . $request->file('file')->getClientOriginalExtension();
        $request->file('file')->move($tempDir, $filename);
        $filePath = $tempDir . DIRECTORY_SEPARATOR . $filename;

        try {
            $headerReader = new \App\Imports\HeaderOnlyImport();
            Excel::import($headerReader, $filePath);
            $fileHeaders = $headerReader->getHeaders();
        } catch (\Throwable $e) {
            @unlink($filePath);
            return response()->json(['success' => false, 'message' => 'Nao foi possivel ler a planilha.'], 422);
        }

        if (empty($fileHeaders)) {
            @unlink($filePath);
            return response()->json(['success' => false, 'message' => 'Planilha vazia ou sem cabecalho.'], 422);
        }

        $aliases = [
            'nome'      => ['nome', 'name', 'nome_completo', 'full_name', 'contato', 'contact', 'cliente', 'razao_social'],
            'telefone'  => ['telefone', 'phone', 'celular', 'mobile', 'whatsapp', 'tel', 'fone', 'numero'],
            'email'     => ['email', 'e_mail', 'mail'],
            'valor'     => ['valor', 'value', 'amount', 'total', 'preco', 'price'],
            'etapa'     => ['etapa', 'stage', 'estagio', 'fase', 'status'],
            'tags'      => ['tags', 'etiquetas', 'labels'],
            'origem'    => ['origem', 'source', 'fonte', 'canal'],
            'empresa'   => ['empresa', 'company', 'organizacao'],
            'criado_em' => ['criado_em', 'created_at', 'data', 'date', 'data_criacao'],
        ];

        $suggested = [];
        foreach ($aliases as $crmKey => $aliasList) {
            $best = null;
            foreach ($fileHeaders as $h) {
                $norm = mb_strtolower(trim($h));
                if (in_array($norm, $aliasList, true)) { $best = $h; break; }
                foreach ($aliasList as $a) {
                    $score = 0;
                    similar_text($norm, $a, $score);
                    if ($score >= 65 && ($best === null)) { $best = $h; }
                }
            }
            $suggested[$crmKey] = $best;
        }

        $customFields = \App\Models\CustomFieldDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'field_type'])
            ->map(fn ($cf) => ['key' => 'custom:' . $cf->id, 'label' => $cf->name, 'type' => $cf->field_type])
            ->values()->toArray();

        $token = encrypt([
            'path' => $filePath, 'pipeline_id' => $pipelineId,
            'tenant_id' => activeTenantId(), 'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        return response()->json([
            'success'           => true,
            'needs_mapping'     => true,
            'file_headers'      => $fileHeaders,
            'suggested_mapping' => $suggested,
            'crm_fields'        => array_keys($aliases),
            'custom_fields'     => $customFields,
            'token'             => $token,
        ]);
    }

    // ── Step 2: Mapping confirmado → preview com dados ───────────────────
    private function previewWithMapping(Request $request): JsonResponse
    {
        try { $payload = decrypt($request->token); }
        catch (\Exception) { return response()->json(['success' => false, 'message' => 'Token invalido.'], 422); }

        if (($payload['tenant_id'] ?? null) !== activeTenantId()
            || ($payload['expires_at'] ?? 0) < now()->timestamp
            || ! file_exists($payload['path'] ?? '')) {
            return response()->json(['success' => false, 'message' => 'Arquivo expirado. Envie novamente.'], 422);
        }

        $pipelineId   = (int) ($request->pipeline_id ?? $payload['pipeline_id']);
        $pipeline     = Pipeline::with('stages')->findOrFail($pipelineId);
        $stagesByName = $pipeline->stages->sortBy('position')
            ->mapWithKeys(fn ($s) => [mb_strtolower($s->name) => $s->id]);

        $mapping       = $request->input('mapping', []);
        $headerToField = [];
        foreach ($mapping as $crmKey => $fileHeader) {
            if ($fileHeader !== null && $fileHeader !== '') {
                $headerToField[$fileHeader] = $crmKey;
            }
        }

        $importer = new KanbanPreviewImport($stagesByName, $headerToField);
        Excel::import($importer, $payload['path']);

        $token = encrypt(array_merge($payload, ['mapping' => $mapping, 'pipeline_id' => $pipelineId]));

        return response()->json([
            'success' => true,
            'rows'    => $importer->getRows(),
            'total'   => count($importer->getRows()),
            'skipped' => count(array_filter($importer->getRows(), fn ($r) => $r['will_skip'])),
            'token'   => $token,
        ]);
    }

    // ── POST /crm/importar ────────────────────────────────────────────────
    public function import(Request $request): JsonResponse
    {
        // Branch A: confirmar preview (token sem file)
        if ($request->filled('token') && ! $request->hasFile('file')) {
            return $this->importFromToken($request);
        }

        // Branch B: upload direto (legado)
        $request->validate([
            'file'        => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'pipeline_id' => 'required|integer|exists:pipelines,id',
        ]);

        $pipelineId = (int) $request->pipeline_id;
        $pipeline   = Pipeline::with('stages')->findOrFail($pipelineId);
        $stages     = $pipeline->stages->sortBy('position');

        $importer = new KanbanImport($pipelineId, $stages->first()?->id ?? 0, $stages->mapWithKeys(fn ($s) => [mb_strtolower($s->name) => $s->id]), $pipeline);
        Excel::import($importer, $request->file('file'));

        return response()->json(['success' => true, 'imported' => $importer->getImported(), 'skipped' => $importer->getSkipped()]);
    }

    private function importFromToken(Request $request): JsonResponse
    {
        try { $payload = decrypt($request->token); }
        catch (\Exception) { return response()->json(['success' => false, 'message' => 'Token invalido.'], 422); }

        if (($payload['tenant_id'] ?? null) !== activeTenantId()
            || ($payload['expires_at'] ?? 0) < now()->timestamp
            || ! file_exists($payload['path'] ?? '')) {
            return response()->json(['success' => false, 'message' => 'Token expirado.'], 422);
        }

        $pipelineId   = (int) ($request->pipeline_id ?? $payload['pipeline_id']);
        $pipeline     = Pipeline::with('stages')->findOrFail($pipelineId);
        $stages       = $pipeline->stages->sortBy('position');
        $stagesByName = $stages->mapWithKeys(fn ($s) => [mb_strtolower($s->name) => $s->id]);

        $mapping       = $payload['mapping'] ?? [];
        $headerToField = [];
        foreach ($mapping as $crmKey => $fileHeader) {
            if ($fileHeader !== null && $fileHeader !== '') {
                $headerToField[$fileHeader] = $crmKey;
            }
        }

        $overrides = $request->input('overrides', []);

        $importer = new KanbanImport($pipelineId, $stages->first()?->id ?? 0, $stagesByName, $pipeline, $headerToField, $overrides);
        Excel::import($importer, $payload['path']);
        @unlink($payload['path']);

        return response()->json(['success' => true, 'imported' => $importer->getImported(), 'skipped' => $importer->getSkipped()]);
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

        // Verificar acesso à pipeline
        $allowedIds = auth()->user()->allowedPipelineIds();
        if ($allowedIds && !in_array($pipelineId, $allowedIds)) {
            return response()->json(['leads' => [], 'server_time' => now()->timestamp]);
        }

        $sinceDate = Carbon::createFromTimestamp($since);

        $leads = Lead::with(['assignedTo', 'customFieldValues.fieldDefinition', 'stage', 'whatsappConversation.aiAgent', 'activeSequence'])
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

        $nearestTask = null;
        $task = $lead->tasks()
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->orderBy('due_time')
            ->first(['subject', 'type', 'due_date']);

        if ($task) {
            $nearestTask = [
                'subject'  => $task->subject,
                'type'     => $task->type,
                'due_date' => $task->due_date->toDateString(),
            ];
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
            'cf_flat'          => $cfFlat,
            'assigned_to_name'     => $lead->assignedTo?->name,
            'assigned_to_avatar'   => $lead->assignedTo?->avatar ? asset($lead->assignedTo->avatar) : null,
            'ai_agent_name'        => $lead->whatsappConversation?->aiAgent?->name,
            'conversation_id'      => $lead->whatsappConversation?->id,
            'unread_count'         => $lead->whatsappConversation?->unread_count ?? 0,
            'contact_picture_url'  => $lead->whatsappConversation?->contact_picture_url,
            'score'                => $lead->score ?? 0,
            'in_sequence'          => $lead->activeSequence !== null,
            'created_at'           => $lead->created_at?->diffForHumans(null, true, true),
            'nearest_task'         => $nearestTask,
        ];
    }
}
