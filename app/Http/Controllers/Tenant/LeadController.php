<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\LeadsExport;
use App\Http\Controllers\Controller;
use App\Imports\LeadsImport;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\InstagramConversation;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\LeadAttachment;
use App\Models\LeadContact;
use App\Models\LeadNote;
use App\Services\PlanLimitChecker;
use App\Models\LostSale;
use App\Models\Sale;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Models\ScheduledMessage;
use App\Models\Task;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Models\WhatsappQuickMessage;
use App\Services\AutomationEngine;
use App\Services\StageRequirementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $allowedPipelineIds = auth()->user()->allowedPipelineIds();

        $query = Lead::with(['stage', 'pipeline', 'assignedTo', 'whatsappConversation.aiAgent'])
            ->where(fn ($q) => $q->where('exclude_from_pipeline', false)->orWhereNull('exclude_from_pipeline'))
            ->where(fn ($q) => $q->where('status', '!=', 'merged')->orWhereNull('status'))
            ->when($allowedPipelineIds, fn ($q) => $q->whereIn('pipeline_id', $allowedPipelineIds))
            ->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($stageId = $request->get('stage_id')) {
            $query->where('stage_id', $stageId);
        }

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
            $query->whereJsonContains('tags', $tag);
        }

        if ($assignedTo = $request->get('assigned_to')) {
            if ($assignedTo === 'ai') {
                $query->whereHas('whatsappConversation', fn ($q) => $q->whereNotNull('ai_agent_id'));
            } else {
                $query->where('assigned_to', $assignedTo);
            }
        }

        $leads   = $query->paginate(15)->withQueryString();
        $stages  = PipelineStage::whereHas('pipeline', fn ($q) => $q->where('tenant_id', activeTenantId()))
            ->orderBy('position')
            ->get();
        $pipelines = Pipeline::with('stages')
            ->when($allowedPipelineIds, fn ($q) => $q->whereIn('id', $allowedPipelineIds))
            ->orderBy('sort_order')->get();
        // Lista de origens distintas para filtro
        $sources = Lead::distinct()->pluck('source')->filter()->sort()->values();

        // Campos personalizados ativos para o drawer
        $customFieldDefs = CustomFieldDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $users = User::where('tenant_id', activeTenantId())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tenant.leads.index', compact('leads', 'stages', 'pipelines', 'sources', 'customFieldDefs', 'users'));
    }

    public function store(Request $request): JsonResponse
    {
        $limitMsg = PlanLimitChecker::check('leads');
        if ($limitMsg) {
            return response()->json(['success' => false, 'message' => $limitMsg, 'limit_reached' => true], 422);
        }

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:191',
            'company'     => 'nullable|string|max:191',
            'value'       => 'nullable|numeric|min:0',
            'source'      => 'nullable|string|max:100',
            'tags'        => 'nullable|array',
            'tags.*'      => 'string|max:50',
            'pipeline_id' => 'required|integer|exists:pipelines,id',
            'stage_id'    => 'required|integer|exists:pipeline_stages,id',
            'notes'       => 'nullable|string|max:1000000',
            'birthday'    => 'nullable|date',
        ]);

        // Duplicate detection (skip if force=true)
        if (!$request->boolean('force')) {
            $detector = new \App\Services\DuplicateLeadDetector();
            $duplicates = $detector->findDuplicatesFromData($data, auth()->user()->tenant_id);
            $highConfidence = $duplicates->filter(fn ($d) => $d['score'] >= 70);

            if ($highConfidence->isNotEmpty()) {
                return response()->json([
                    'success'          => false,
                    'duplicates_found' => true,
                    'message'          => 'Possíveis duplicatas encontradas.',
                    'duplicates'       => $highConfidence->map(fn ($d) => [
                        'id'         => $d['lead']->id,
                        'name'       => $d['lead']->name,
                        'phone'      => $d['lead']->phone,
                        'email'      => $d['lead']->email,
                        'company'    => $d['lead']->company,
                        'score'      => $d['score'],
                        'created_at' => $d['lead']->created_at?->format('d/m/Y'),
                        'stage'      => $d['lead']->stage?->name,
                    ])->values(),
                ], 409);
            }
        }

        $data['created_by'] = auth()->id();

        $lead = Lead::create($data);

        // Dual write tags: JSON ja foi salvo via fillable acima, agora popula pivot
        if (array_key_exists('tags', $data)) {
            $lead->syncTagsByName((array) $data['tags']);
        }

        $this->saveCustomFields($lead, $request->input('custom_fields', []));

        $agencyPrefix = session()->has('impersonating_tenant_id') ? 'Agência parceira: ' : '';
        LeadEvent::create([
            'lead_id'      => $lead->id,
            'event_type'   => 'created',
            'description'  => $agencyPrefix . 'Lead criado',
            'performed_by' => auth()->id(),
            'created_at'   => now(),
        ]);

        // Se o stage inicial é won/lost, cria Sale/LostSale
        $initialStage = PipelineStage::find($data['stage_id']);
        if ($initialStage?->is_won) {
            Sale::firstOrCreate(
                ['lead_id' => $lead->id, 'pipeline_id' => $data['pipeline_id']],
                [
                    'value'       => $lead->value,
                    'closed_by'   => auth()->id(),
                    'closed_at'   => now(),
                ]
            );
        } elseif ($initialStage?->is_lost) {
            LostSale::firstOrCreate(
                ['lead_id' => $lead->id, 'pipeline_id' => $data['pipeline_id']],
                [
                    'lost_at'     => now(),
                    'lost_by'     => auth()->id(),
                ]
            );
        }

        // Automação: lead criado
        try {
            (new AutomationEngine())->run('lead_created', [
                'tenant_id' => activeTenantId(),
                'lead'      => $lead,
            ]);
        } catch (\Throwable) {}

        // Notificação: novo lead
        try {
            (new \App\Services\NotificationDispatcher())->dispatch('new_lead', [
                'lead_name' => $lead->name,
                'url'       => route('leads.index', ['lead' => $lead->id]),
            ], activeTenantId(), excludeUserId: auth()->id());
        } catch (\Throwable) {}

        $lead->load(['stage', 'pipeline', 'assignedTo']);

        return response()->json(['success' => true, 'lead' => $this->formatLead($lead)]);
    }

    public function show(Request $request, Lead $lead): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        // Browser navigation → redirect to index with ?lead=X so the drawer opens
        if (! $request->expectsJson()) {
            return redirect()->route('leads.index', ['lead' => $lead->id]);
        }

        $lead->load(['stage', 'pipeline', 'assignedTo', 'events.performedBy', 'customFieldValues.fieldDefinition', 'leadNotes.author', 'attachments.uploader', 'activeSequence.sequence.steps']);

        $allowedIds = auth()->user()->allowedPipelineIds();
        $pipelines = Pipeline::with('stages')
            ->when($allowedIds, fn ($q) => $q->whereIn('id', $allowedIds))
            ->orderBy('sort_order')->get();

        $customFieldDefs = CustomFieldDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($d) => [
                'id'           => $d->id,
                'name'         => $d->name,
                'label'        => $d->label,
                'field_type'   => $d->field_type,
                'options_json' => $d->options_json,
                'is_required'  => $d->is_required,
                'default_value'=> $d->default_value,
            ]);

        return response()->json([
            'lead'              => $this->formatLead($lead, withNotes: true),
            'custom_field_defs' => $customFieldDefs,
            'events'            => $lead->events->map(fn ($e) => [
                'type'         => $e->event_type,
                'description'  => $e->description,
                'performed_by' => $e->performedBy?->name ?? 'Sistema',
                'created_at'   => $e->created_at?->format('d/m/Y H:i'),
            ]),
            'pipelines' => $pipelines->map(fn ($p) => [
                'id'     => $p->id,
                'name'   => $p->name,
                'stages' => $p->stages->map(fn ($s) => [
                    'id'    => $s->id,
                    'name'  => $s->name,
                    'color' => $s->color,
                ]),
            ]),
            'attachments' => $lead->attachments->map(fn ($a) => [
                'id'            => $a->id,
                'original_name' => $a->original_name,
                'mime_type'     => $a->mime_type,
                'file_size'     => $a->file_size,
                'url'           => Storage::disk('public')->url($a->storage_path),
                'created_at'    => $a->created_at->format('d/m/Y H:i'),
                'uploaded_by'   => $a->uploader?->name ?? 'Sistema',
            ]),
        ]);
    }

    public function showPage(Lead $lead): View
    {
        $lead->load([
            'pipeline.stages',
            'stage',
            'assignedTo',
            'leadNotes.author',
            'events.performedBy',
            'customFieldValues.fieldDefinition',
            'attachments.uploader',
            'activeSequence.sequence.steps',
            'leadSequences.sequence.steps',
            'sales.closedBy',
            'lostSales.reason',
            'products.product',
            'contacts',
        ]);

        $waConversation = WhatsappConversation::where('lead_id', $lead->id)
            ->with(['messages' => fn ($q) => $q->orderBy('sent_at')->limit(100)])
            ->first();

        $igConversation = InstagramConversation::withoutGlobalScope('tenant')
            ->where('lead_id', $lead->id)
            ->where('tenant_id', activeTenantId())
            ->with(['messages' => fn ($q) => $q->orderBy('sent_at')->limit(100)])
            ->first();

        $allowedIds = auth()->user()->allowedPipelineIds();
        $pipelines = Pipeline::with('stages:id,pipeline_id,name,color,position,is_won,is_lost')
            ->when($allowedIds, fn ($q) => $q->whereIn('id', $allowedIds))
            ->orderBy('sort_order')
            ->get(['id', 'name', 'is_default']);

        $cfDefs            = CustomFieldDefinition::where('is_active', true)->orderBy('sort_order')->get();
        $users             = User::where('tenant_id', activeTenantId())
            ->orderBy('name')
            ->get(['id', 'name']);
        $scheduledMessages = ScheduledMessage::where('lead_id', $lead->id)
            ->with('createdBy:id,name')
            ->orderBy('send_at')
            ->get();
        $quickMessages     = WhatsappQuickMessage::orderBy('sort_order')->get(['id', 'title', 'body']);

        $tasks = $lead->tasks()->with('assignedTo:id,name')->get();
        $pendingTasksCount = $tasks->where('status', 'pending')->count();

        // Sequencias ativas do tenant pra dropdown de inscricao manual no lead
        $activeNurtureSequences = \App\Models\NurtureSequence::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tenant.leads.show', compact(
            'lead', 'waConversation', 'igConversation', 'pipelines', 'cfDefs', 'users',
            'scheduledMessages', 'quickMessages', 'tasks', 'pendingTasksCount',
            'activeNurtureSequences'
        ));
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        // `sometimes` permite update parcial: chamadas como `updateLeadTags` na
        // página do lead enviam só {name, tags}, sem pipeline/stage. Antes da
        // mudança, o `required` quebrava esses cenários com 422.
        $data = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:191',
            'company'     => 'nullable|string|max:191',
            'value'       => 'nullable|numeric|min:0',
            'source'      => 'nullable|string|max:100',
            'tags'        => 'nullable|array',
            'tags.*'      => 'string|max:50',
            'pipeline_id' => 'sometimes|required|integer|exists:pipelines,id',
            'stage_id'    => 'sometimes|required|integer|exists:pipeline_stages,id',
            'notes'       => 'nullable|string|max:1000000',
            'birthday'    => 'nullable|date',
        ]);

        $oldStageId    = $lead->stage_id;
        $oldAssignedTo = $lead->assigned_to;

        // Check mandatory tasks before allowing stage exit (só quando stage muda)
        if (isset($data['stage_id']) && $oldStageId !== (int) $data['stage_id']) {
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

        $lead->update($data);

        // Dual write tags: sync na pivot polimorfica (Fase 3 do refactor de tags)
        if (array_key_exists('tags', $data)) {
            $lead->syncTagsByName((array) $data['tags']);
        }

        $this->saveCustomFields($lead, $request->input('custom_fields', []));

        $agencyPrefix = session()->has('impersonating_tenant_id') ? 'Agência parceira: ' : '';
        if (isset($data['stage_id']) && $oldStageId !== (int) $data['stage_id']) {
            $newStage = PipelineStage::find($data['stage_id']);
            LeadEvent::create([
                'lead_id'      => $lead->id,
                'event_type'   => 'stage_changed',
                'description'  => $agencyPrefix . "Movido para {$newStage?->name}",
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
                \Log::error('Falha ao criar evento WhatsApp (lead update)', [
                    'lead_id' => $lead->id,
                    'error'   => $e->getMessage(),
                ]);
            }

            // Create mandatory tasks for the new stage
            try {
                (new StageRequirementService())->createRequiredTasks($lead->fresh(), $newStage);
            } catch (\Throwable) {}
        } else {
            LeadEvent::create([
                'lead_id'      => $lead->id,
                'event_type'   => 'updated',
                'description'  => $agencyPrefix . 'Lead atualizado',
                'performed_by' => auth()->id(),
                'created_at'   => now(),
            ]);
        }

        // Notificação: lead atribuído a outro usuário
        $newAssignedTo = $lead->assigned_to;
        if ($newAssignedTo && $newAssignedTo !== $oldAssignedTo) {
            try {
                (new \App\Services\NotificationDispatcher())->dispatch('lead_assigned', [
                    'lead_name'   => $lead->name,
                    'assigned_by' => auth()->user()->name,
                    'url'         => route('leads.index', ['lead' => $lead->id]),
                ], activeTenantId(), targetUserId: $newAssignedTo);
            } catch (\Throwable) {}
        }

        $lead->load(['stage', 'pipeline', 'assignedTo']);

        return response()->json(['success' => true, 'lead' => $this->formatLead($lead)]);
    }

    public function removeFromPipeline(Lead $lead): JsonResponse
    {
        // Remove o lead do Kanban sem arquivá-lo: ele continua aparecendo em Contatos.
        $lead->update([
            'stage_id'   => null,
            'pipeline_id' => null,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        // Limpa registros de venda/perda associados ao lead
        Sale::where('lead_id', $lead->id)->delete();
        LostSale::where('lead_id', $lead->id)->delete();

        // Arquiva o lead: remove do funil/pipeline mas mantém o contato.
        $lead->update([
            'stage_id'              => null,
            'pipeline_id'           => null,
            'exclude_from_pipeline' => true,
        ]);

        return response()->json(['success' => true]);
    }

    public function addNote(Request $request, Lead $lead): JsonResponse
    {
        // MEDIUMTEXT no DB suporta ~16M chars; cap em 1M já é absurdo pra texto livre
        $request->validate(['body' => 'required|string|max:1000000']);

        $note = $lead->leadNotes()->create([
            'body'       => $this->sanitizeNoteHtml($request->body),
            'created_by' => auth()->id(),
        ]);

        $note->load('author');

        $agencyPrefix = session()->has('impersonating_tenant_id') ? 'Agência parceira: ' : '';
        LeadEvent::create([
            'lead_id'      => $lead->id,
            'event_type'   => 'note_added',
            'description'  => $agencyPrefix . 'Nota adicionada',
            'performed_by' => auth()->id(),
            'created_at'   => now(),
        ]);

        return response()->json([
            'success' => true,
            'note'    => [
                'id'         => $note->id,
                'body'       => $note->body,
                'author'     => $note->author?->name ?? 'Desconhecido',
                'created_at' => $note->created_at?->format('d/m/Y H:i'),
                'is_mine'    => true,
            ],
        ]);
    }

    public function updateNote(Request $request, Lead $lead, LeadNote $note): JsonResponse
    {
        if ($note->lead_id !== $lead->id || $note->created_by !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Sem permissão.'], 403);
        }

        $data = $request->validate(['body' => 'required|string|max:1000000']);

        $note->update(['body' => $this->sanitizeNoteHtml($data['body'])]);
        $note->load('author');

        // 'note_updated' NÃO existe no enum lead_events.event_type — usar 'updated' genérico.
        // A descrição mantém o contexto pra história do lead.
        LeadEvent::create([
            'lead_id'      => $lead->id,
            'event_type'   => 'updated',
            'description'  => 'Nota editada',
            'performed_by' => auth()->id(),
            'created_at'   => now(),
        ]);

        return response()->json([
            'success' => true,
            'note'    => [
                'id'         => $note->id,
                'body'       => $note->body,
                'author'     => $note->author?->name ?? 'Desconhecido',
                'created_at' => $note->created_at?->format('d/m/Y H:i'),
                'is_mine'    => true,
            ],
        ]);
    }

    public function deleteNote(Request $request, Lead $lead, LeadNote $note): JsonResponse
    {
        if ($note->lead_id !== $lead->id || $note->created_by !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Sem permissão.'], 403);
        }

        $note->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Sanitiza HTML de nota de lead. Whitelist mínima: negrito, itálico, sublinhado,
     * link, quebra de linha, parágrafo. Tudo o mais é removido (incluindo <script>).
     * Links têm href validado (só http/https/mailto) e ganham target=_blank rel=noopener.
     */
    private function sanitizeNoteHtml(string $html): string
    {
        // Whitelist de tags permitidas — qualquer outra coisa (script, iframe, img, etc) é removida
        $clean = strip_tags($html, '<b><strong><i><em><u><a><br><p>');

        // Sanitizar atributos de <a>: aceitar só http(s)/mailto, adicionar rel + target seguros
        $clean = preg_replace_callback('/<a\s+([^>]*)>/i', function (array $m): string {
            if (preg_match('/href\s*=\s*["\']([^"\']*)["\']/i', $m[1], $hm)) {
                $url = trim($hm[1]);
                if (preg_match('/^(https?:\/\/|mailto:)/i', $url)) {
                    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
                         . '" target="_blank" rel="noopener noreferrer nofollow">';
                }
            }
            return '<a>';
        }, $clean) ?? $clean;

        return $clean;
    }

    public function export(Request $request)
    {
        $filters = $request->only(['search', 'stage_id', 'source', 'date_from', 'date_to', 'tag']);

        return Excel::download(new LeadsExport($filters), 'leads-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $remaining = PlanLimitChecker::remaining('leads');
        if ($remaining === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Limite de leads atingido no seu plano. Faça upgrade para importar.',
                'limit_reached' => true,
            ], 422);
        }

        $import = new LeadsImport($remaining);
        Excel::import($import, $request->file('file'));

        $result = [
            'success'  => true,
            'imported' => $import->getImported(),
            'skipped'  => $import->getSkipped(),
        ];

        if ($remaining !== null && $import->getLimitSkipped() > 0) {
            $result['limit_skipped'] = $import->getLimitSkipped();
            $result['message'] = "{$import->getImported()} leads importados. {$import->getLimitSkipped()} ignorados por limite do plano.";
        }

        if ($import->getDuplicatesFound() > 0) {
            $result['duplicates_found'] = $import->getDuplicatesFound();
            $msg = $result['message'] ?? "{$import->getImported()} leads importados.";
            $result['message'] = $msg . " {$import->getDuplicatesFound()} possível(is) duplicata(s) detectada(s) — revise em Contatos > Duplicatas.";
        }

        return response()->json($result);
    }

    private function formatLead(Lead $lead, bool $withNotes = false): array
    {
        $data = [
            'id'            => $lead->id,
            'name'          => $lead->name,
            'phone'         => $lead->phone,
            'email'         => $lead->email,
            'company'       => $lead->company,
            'value'         => $lead->value,
            'value_fmt'     => $lead->value ? 'R$ ' . number_format((float) $lead->value, 2, ',', '.') : null,
            'source'        => $lead->source,
            'tags'          => $lead->tags ?? [],
            'pipeline_id'   => $lead->pipeline_id,
            'stage_id'      => $lead->stage_id,
            'notes'         => $lead->notes ?? null,
            'birthday'      => $lead->birthday?->format('Y-m-d'),
            'stage'         => $lead->stage   ? ['id' => $lead->stage->id,    'name' => $lead->stage->name,    'color' => $lead->stage->color]   : null,
            'pipeline'      => $lead->pipeline ? ['id' => $lead->pipeline->id, 'name' => $lead->pipeline->name] : null,
            'created_at'    => $lead->created_at?->format('d/m/Y H:i'),
            'custom_fields' => $lead->customFields,  // usa o accessor do Model
            'utm_id'        => $lead->utm_id,
            'utm_source'    => $lead->utm_source,
            'utm_medium'    => $lead->utm_medium,
            'utm_campaign'  => $lead->utm_campaign,
            'utm_content'   => $lead->utm_content,
            'utm_term'      => $lead->utm_term,
            'contact_picture_url' => $lead->whatsappConversation?->contact_picture_url,
            'active_sequence'     => $lead->activeSequence ? [
                'id'            => $lead->activeSequence->id,
                'name'          => $lead->activeSequence->sequence?->name,
                'current_step'  => $lead->activeSequence->current_step_position,
                'total_steps'   => $lead->activeSequence->sequence?->steps?->count() ?? 0,
                'status'        => $lead->activeSequence->status,
            ] : null,
            'score'               => $lead->score ?? 0,
        ];

        if ($withNotes) {
            $data['notes_list'] = $lead->leadNotes()->with('author')->get()->map(fn (LeadNote $n) => [
                'id'         => $n->id,
                'body'       => $n->body,
                'author'     => $n->author?->name ?? 'Desconhecido',
                'created_at' => $n->created_at?->format('d/m/Y H:i'),
                'is_mine'    => $n->created_by === auth()->id(),
            ])->values()->all();
        }

        return $data;
    }

    public function uploadCustomFieldFile(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:20480', new \App\Rules\SafeFile]]); // 20 MB

        $path = $request->file('file')->store('lead-files', 'public');

        return response()->json([
            'success' => true,
            'url'     => Storage::disk('public')->url($path),
        ]);
    }

    public function uploadAttachment(Request $request, Lead $lead): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480|mimes:png,jpg,jpeg,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt,zip,rar',
        ]);

        $file = $request->file('file');
        $path = $file->store("lead-attachments/{$lead->id}", 'public');

        $record = LeadAttachment::create([
            'lead_id'       => $lead->id,
            'tenant_id'     => activeTenantId(),
            'uploaded_by'   => auth()->id(),
            'original_name' => preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file->getClientOriginalName())),
            'storage_path'  => $path,
            'mime_type'     => $file->getMimeType() ?? $file->getClientMimeType(),
            'file_size'     => $file->getSize(),
        ]);

        return response()->json([
            'success'    => true,
            'attachment' => [
                'id'            => $record->id,
                'original_name' => $record->original_name,
                'mime_type'     => $record->mime_type,
                'file_size'     => $record->file_size,
                'url'           => Storage::disk('public')->url($record->storage_path),
                'created_at'    => $record->created_at->format('d/m/Y H:i'),
                'uploaded_by'   => auth()->user()->name,
            ],
        ]);
    }

    public function deleteAttachment(Lead $lead, LeadAttachment $attachment): JsonResponse
    {
        abort_unless($attachment->lead_id === $lead->id, 404);

        Storage::disk('public')->delete($attachment->storage_path);
        $attachment->delete();

        return response()->json(['success' => true]);
    }

    // ── Lead Products ─────────────────────────────────────────────────────────

    public function getProducts(Lead $lead): JsonResponse
    {
        $items = $lead->products()->with('product:id,name,price,unit')->get();

        // Sync lead value if products exist but value is out of sync
        $productsTotal = (float) $items->sum('total');
        if ($productsTotal > 0 && (float) $lead->value !== $productsTotal) {
            $lead->update(['value' => $productsTotal]);
        }

        return response()->json(['success' => true, 'products' => $items]);
    }

    public function addProduct(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'product_id'       => 'required|integer|exists:products,id',
            'quantity'         => 'nullable|numeric|min:0.01',
            'unit_price'       => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'notes'            => 'nullable|string|max:500',
        ]);

        $product = \App\Models\Product::findOrFail($data['product_id']);

        $lp = \App\Models\LeadProduct::create([
            'tenant_id'        => $lead->tenant_id,
            'lead_id'          => $lead->id,
            'product_id'       => $product->id,
            'quantity'         => $data['quantity'] ?? 1,
            'unit_price'       => $data['unit_price'] ?? $product->price,
            'discount_percent' => $data['discount_percent'] ?? 0,
            'total'            => 0, // auto-calculated in saving event
            'notes'            => $data['notes'] ?? null,
        ]);

        $lp->load('product:id,name,price,unit');
        $this->syncLeadValueFromProducts($lead);

        return response()->json([
            'success'      => true,
            'lead_product' => $lp,
            'lead_value'   => (float) $lead->fresh()->value,
        ], 201);
    }

    public function updateProduct(Request $request, Lead $lead, \App\Models\LeadProduct $leadProduct): JsonResponse
    {
        abort_unless((int) $leadProduct->lead_id === (int) $lead->id, 404);

        $data = $request->validate([
            'quantity'         => 'nullable|numeric|min:0.01',
            'unit_price'       => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'notes'            => 'nullable|string|max:500',
        ]);

        $leadProduct->update($data);
        $leadProduct->load('product:id,name,price,unit');
        $this->syncLeadValueFromProducts($lead);

        return response()->json([
            'success'      => true,
            'lead_product' => $leadProduct,
            'lead_value'   => (float) $lead->fresh()->value,
        ]);
    }

    public function removeProduct(Lead $lead, \App\Models\LeadProduct $leadProduct): JsonResponse
    {
        abort_unless((int) $leadProduct->lead_id === (int) $lead->id, 404);
        $leadProduct->delete();
        $this->syncLeadValueFromProducts($lead);

        return response()->json([
            'success'    => true,
            'lead_value' => (float) $lead->fresh()->value,
        ]);
    }

    private function syncLeadValueFromProducts(Lead $lead): void
    {
        $total = \App\Models\LeadProduct::where('lead_id', $lead->id)->sum('total');
        $lead->update(['value' => $total]);
    }

    private function saveCustomFields(Lead $lead, array $fields): void
    {
        if (empty($fields)) {
            return;
        }

        $defs = CustomFieldDefinition::where('is_active', true)
            ->get()
            ->keyBy('name');

        foreach ($fields as $name => $value) {
            $def = $defs->get($name);
            if (!$def) {
                continue;
            }

            // Determina coluna correta pelo tipo
            $valueData = match ($def->field_type) {
                'number', 'currency' => ['value_number' => $value !== '' && $value !== null ? (float) $value : null],
                'date'               => ['value_date'   => $value ?: null],
                'checkbox'           => ['value_boolean'=> (bool) $value],
                'multiselect'        => ['value_json'   => is_array($value) ? $value : (array) json_decode((string) $value, true)],
                default              => ['value_text'   => $value !== '' ? (string) $value : null],
            };

            CustomFieldValue::updateOrCreate(
                ['lead_id' => $lead->id, 'field_id' => $def->id],
                array_merge($valueData, ['tenant_id' => $lead->tenant_id])
            );
        }
    }

    // ── Lead Contacts ────────────────────────────────────────────────────────

    public function storeContact(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:191',
            'role'  => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:191',
        ]);

        $contact = $lead->contacts()->create(array_merge($data, [
            'tenant_id' => $lead->tenant_id,
        ]));

        return response()->json([
            'success' => true,
            'contact' => $contact,
        ]);
    }

    public function updateContact(Request $request, Lead $lead, LeadContact $contact): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:191',
            'role'  => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:191',
        ]);

        $contact->update($data);

        return response()->json([
            'success' => true,
            'contact' => $contact->fresh(),
        ]);
    }

    public function destroyContact(Lead $lead, LeadContact $contact): JsonResponse
    {
        $contact->delete();

        return response()->json(['success' => true]);
    }

    public function leadContacts(Lead $lead): JsonResponse
    {
        return response()->json($lead->contacts()->orderBy('name')->get());
    }
}
