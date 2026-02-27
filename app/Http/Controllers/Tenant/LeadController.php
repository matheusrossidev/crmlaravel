<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\LeadsExport;
use App\Http\Controllers\Controller;
use App\Imports\LeadsImport;
use App\Models\Campaign;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\InstagramConversation;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\LeadNote;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Models\WhatsappConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $query = Lead::with(['stage', 'pipeline', 'campaign', 'assignedTo', 'whatsappConversation.aiAgent'])
            ->where(fn ($q) => $q->where('exclude_from_pipeline', false)->orWhereNull('exclude_from_pipeline'))
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
        $stages  = PipelineStage::whereHas('pipeline', fn ($q) => $q->where('tenant_id', auth()->user()->tenant_id))
            ->orderBy('position')
            ->get();
        $pipelines = Pipeline::orderBy('sort_order')->get();
        $campaigns = Campaign::orderBy('name')->get(['id', 'name', 'platform']);

        // Lista de origens distintas para filtro
        $sources = Lead::distinct()->pluck('source')->filter()->sort()->values();

        // Campos personalizados ativos para o drawer
        $customFieldDefs = CustomFieldDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $users = User::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tenant.leads.index', compact('leads', 'stages', 'pipelines', 'campaigns', 'sources', 'customFieldDefs', 'users'));
    }

    public function store(Request $request): JsonResponse
    {
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
            'campaign_id' => 'nullable|integer|exists:campaigns,id',
            'notes'       => 'nullable|string|max:2000',
        ]);

        $data['created_by'] = auth()->id();

        $lead = Lead::create($data);

        $this->saveCustomFields($lead, $request->input('custom_fields', []));

        LeadEvent::create([
            'lead_id'      => $lead->id,
            'event_type'   => 'created',
            'description'  => 'Lead criado',
            'performed_by' => auth()->id(),
            'created_at'   => now(),
        ]);

        $lead->load(['stage', 'pipeline', 'campaign', 'assignedTo']);

        return response()->json(['success' => true, 'lead' => $this->formatLead($lead)]);
    }

    public function show(Request $request, Lead $lead): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        // Browser navigation → redirect to index with ?lead=X so the drawer opens
        if (! $request->expectsJson()) {
            return redirect()->route('leads.index', ['lead' => $lead->id]);
        }

        $lead->load(['stage', 'pipeline', 'campaign', 'assignedTo', 'events.performedBy', 'customFieldValues.fieldDefinition', 'leadNotes.author']);

        $pipelines = Pipeline::with('stages')->orderBy('sort_order')->get();
        $campaigns = Campaign::orderBy('name')->get(['id', 'name', 'platform']);

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
            'campaigns' => $campaigns,
        ]);
    }

    public function showPage(Lead $lead): View
    {
        $lead->load([
            'pipeline.stages',
            'stage',
            'assignedTo',
            'campaign',
            'leadNotes.author',
            'events.performedBy',
            'customFieldValues.fieldDefinition',
        ]);

        $waConversation = WhatsappConversation::where('lead_id', $lead->id)
            ->with(['messages' => fn ($q) => $q->orderBy('sent_at')->limit(100)])
            ->first();

        $igConversation = InstagramConversation::withoutGlobalScope('tenant')
            ->where('lead_id', $lead->id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->with(['messages' => fn ($q) => $q->orderBy('sent_at')->limit(100)])
            ->first();

        $pipelines = Pipeline::with('stages:id,pipeline_id,name,color,position,is_won,is_lost')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'is_default']);

        $campaigns = Campaign::orderBy('name')->get(['id', 'name']);
        $cfDefs    = CustomFieldDefinition::where('is_active', true)->orderBy('sort_order')->get();
        $users     = User::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tenant.leads.show', compact(
            'lead', 'waConversation', 'igConversation', 'pipelines', 'campaigns', 'cfDefs', 'users'
        ));
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
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
            'campaign_id' => 'nullable|integer|exists:campaigns,id',
            'notes'       => 'nullable|string|max:2000',
        ]);

        $oldStageId = $lead->stage_id;
        $lead->update($data);

        $this->saveCustomFields($lead, $request->input('custom_fields', []));

        if ($oldStageId !== (int) $data['stage_id']) {
            $newStage = PipelineStage::find($data['stage_id']);
            LeadEvent::create([
                'lead_id'      => $lead->id,
                'event_type'   => 'stage_changed',
                'description'  => "Movido para {$newStage?->name}",
                'performed_by' => auth()->id(),
                'created_at'   => now(),
            ]);
        } else {
            LeadEvent::create([
                'lead_id'      => $lead->id,
                'event_type'   => 'updated',
                'description'  => 'Lead atualizado',
                'performed_by' => auth()->id(),
                'created_at'   => now(),
            ]);
        }

        $lead->load(['stage', 'pipeline', 'campaign', 'assignedTo']);

        return response()->json(['success' => true, 'lead' => $this->formatLead($lead)]);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        // Arquiva o lead: remove do funil/pipeline mas mantém o contato.
        // Evita que contatos de membros da equipe ou descartados reapareçam
        // automaticamente no Kanban ao enviar uma nova mensagem.
        $lead->update([
            'stage_id'              => null,
            'pipeline_id'           => null,
            'exclude_from_pipeline' => true,
        ]);

        return response()->json(['success' => true]);
    }

    public function addNote(Request $request, Lead $lead): JsonResponse
    {
        $request->validate(['body' => 'required|string|max:3000']);

        $note = $lead->leadNotes()->create([
            'body'       => $request->body,
            'created_by' => auth()->id(),
        ]);

        $note->load('author');

        LeadEvent::create([
            'lead_id'      => $lead->id,
            'event_type'   => 'note_added',
            'description'  => 'Nota adicionada',
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

        $import = new LeadsImport();
        Excel::import($import, $request->file('file'));

        return response()->json([
            'success'  => true,
            'imported' => $import->getImported(),
            'skipped'  => $import->getSkipped(),
        ]);
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
            'campaign_id'   => $lead->campaign_id,
            'notes'         => $lead->notes ?? null,
            'stage'         => $lead->stage   ? ['id' => $lead->stage->id,    'name' => $lead->stage->name,    'color' => $lead->stage->color]   : null,
            'pipeline'      => $lead->pipeline ? ['id' => $lead->pipeline->id, 'name' => $lead->pipeline->name] : null,
            'campaign'      => $lead->campaign ? ['id' => $lead->campaign->id, 'name' => $lead->campaign->name] : null,
            'created_at'    => $lead->created_at?->format('d/m/Y H:i'),
            'custom_fields' => $lead->customFields,  // usa o accessor do Model
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
        $request->validate(['file' => 'required|file|max:20480']); // 20 MB

        $path = $request->file('file')->store('lead-files', 'public');

        return response()->json([
            'success' => true,
            'url'     => Storage::disk('public')->url($path),
        ]);
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
}
