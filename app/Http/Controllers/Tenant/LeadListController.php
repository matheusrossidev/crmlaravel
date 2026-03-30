<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadList;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Models\WhatsappTag;
use App\Services\LeadListQueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadListController extends Controller
{
    public function index(): View
    {
        $lists = LeadList::with('createdBy:id,name')
            ->orderByDesc('updated_at')
            ->get();

        // Dynamic data for the condition builder
        $pipelines = Pipeline::orderBy('sort_order')->get(['id', 'name']);
        $stages    = PipelineStage::whereHas('pipeline', fn ($q) => $q->where('tenant_id', activeTenantId()))
            ->orderBy('position')->get(['id', 'name', 'pipeline_id']);
        $users     = User::where('tenant_id', activeTenantId())->orderBy('name')->get(['id', 'name']);
        $campaigns = Campaign::orderBy('name')->get(['id', 'name']);

        // Tags: configured + from leads
        $configuredTags = WhatsappTag::orderBy('sort_order')->get(['name', 'color']);
        $leadTagNames   = Lead::whereNotNull('tags')->pluck('tags')->flatten()->filter()->unique();
        $configuredNames = $configuredTags->pluck('name')->map(fn ($n) => mb_strtolower($n));
        $extraTags = $leadTagNames
            ->filter(fn ($t) => !$configuredNames->contains(mb_strtolower($t)))
            ->sort()->values()
            ->map(fn ($name) => (object) ['name' => $name, 'color' => null]);
        $tags = $configuredTags->concat($extraTags);

        // Sources
        $sources = Lead::distinct()->pluck('source')->filter()->sort()->values();

        return view('tenant.lists.index', compact(
            'lists', 'pipelines', 'stages', 'users', 'campaigns', 'tags', 'sources',
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:191',
            'description' => 'nullable|string|max:1000',
            'type'        => 'required|in:static,dynamic',
            'filters'     => 'nullable|array',
        ]);

        $list = LeadList::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'type'        => $data['type'],
            'filters'     => $data['type'] === 'dynamic' ? ($data['filters'] ?? null) : null,
            'created_by'  => auth()->id(),
        ]);

        if ($list->type === 'dynamic') {
            $list->refreshCount();
        }

        return response()->json(['success' => true, 'list' => $list]);
    }

    public function show(LeadList $list, Request $request): View
    {
        $builder = app(LeadListQueryBuilder::class);
        $query   = $builder->resolve($list);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $leads = $query->with(['stage:id,name', 'pipeline:id,name'])
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        // Data for add-members modal (static lists)
        $pipelines = $list->type === 'static'
            ? Pipeline::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('tenant.lists.show', compact('list', 'leads', 'pipelines'));
    }

    public function update(LeadList $list, Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:191',
            'description' => 'nullable|string|max:1000',
            'filters'     => 'nullable|array',
        ]);

        $list->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'filters'     => $list->type === 'dynamic' ? ($data['filters'] ?? null) : $list->filters,
        ]);

        if ($list->type === 'dynamic') {
            $list->refreshCount();
        }

        return response()->json(['success' => true, 'list' => $list->fresh()]);
    }

    public function destroy(LeadList $list): JsonResponse
    {
        $list->delete();

        return response()->json(['success' => true]);
    }

    public function addMembers(LeadList $list, Request $request): JsonResponse
    {
        if ($list->type !== 'static') {
            return response()->json(['success' => false, 'message' => 'Só listas estáticas permitem adicionar membros.'], 422);
        }

        $request->validate(['lead_ids' => 'required|array', 'lead_ids.*' => 'integer']);

        $existing = $list->members()->pluck('leads.id')->toArray();
        $new      = array_diff($request->input('lead_ids'), $existing);

        $attach = [];
        foreach ($new as $leadId) {
            $attach[$leadId] = ['added_at' => now(), 'added_by' => auth()->id()];
        }

        $list->members()->attach($attach);
        $list->refreshCount();

        return response()->json(['success' => true, 'added' => count($new), 'lead_count' => $list->lead_count]);
    }

    public function removeMember(LeadList $list, Lead $lead): JsonResponse
    {
        if ($list->type !== 'static') {
            return response()->json(['success' => false, 'message' => 'Só listas estáticas permitem remover membros.'], 422);
        }

        $list->members()->detach($lead->id);
        $list->refreshCount();

        return response()->json(['success' => true, 'lead_count' => $list->lead_count]);
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate(['filters' => 'required|array']);

        $tenant  = activeTenant();
        $builder = app(LeadListQueryBuilder::class);
        $query   = $builder->buildFromFilters($tenant->id, $request->input('filters'));
        $count   = $query->count();
        $sample  = $query->limit(5)->get(['id', 'name', 'email', 'phone', 'score']);

        return response()->json(['count' => $count, 'sample' => $sample]);
    }

    /**
     * API: search leads for the add-members modal.
     */
    public function searchLeads(Request $request): JsonResponse
    {
        $query = Lead::query();

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($pipeline = $request->input('pipeline_id')) {
            $query->where('pipeline_id', $pipeline);
        }

        $leads = $query->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email', 'phone', 'source']);

        return response()->json($leads);
    }
}
