<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadSequence;
use App\Models\NurtureSequence;
use App\Models\NurtureSequenceStep;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\NurtureSequenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NurtureSequenceController extends Controller
{
    public function index(): View
    {
        $sequences = NurtureSequence::withCount([
            'steps',
            'leadSequences as active_count' => fn ($q) => $q->where('status', 'active'),
        ])
        ->orderByDesc('updated_at')
        ->get();

        return view('tenant.settings.sequences.index', compact('sequences'));
    }

    public function create(): View
    {
        $stages = PipelineStage::withoutGlobalScope('tenant')->orderBy('position')->get(['id', 'name']);
        $users  = User::where('tenant_id', activeTenant()->id)->get(['id', 'name']);

        return view('tenant.settings.sequences.form', [
            'sequence' => null,
            'stages'   => $stages,
            'users'    => $users,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:100',
            'description'           => 'nullable|string|max:191',
            'exit_on_reply'         => 'boolean',
            'exit_on_stage_change'  => 'boolean',
            'steps'                 => 'required|array|min:1',
            'steps.*.type'          => 'required|string|in:message,wait_reply,condition,action',
            'steps.*.delay_minutes' => 'required|integer|min:0',
            'steps.*.config'        => 'required|array',
        ]);

        $sequence = NurtureSequence::create([
            'name'                 => $data['name'],
            'description'          => $data['description'] ?? null,
            'exit_on_reply'        => $data['exit_on_reply'] ?? true,
            'exit_on_stage_change' => $data['exit_on_stage_change'] ?? false,
            'is_active'            => false,
        ]);

        foreach ($data['steps'] as $i => $stepData) {
            NurtureSequenceStep::create([
                'sequence_id'   => $sequence->id,
                'position'      => $i + 1,
                'delay_minutes' => $stepData['delay_minutes'],
                'type'          => $stepData['type'],
                'config'        => $stepData['config'],
            ]);
        }

        return response()->json(['success' => true, 'id' => $sequence->id]);
    }

    public function edit(NurtureSequence $sequence): View
    {
        $sequence->load('steps');
        $stages = PipelineStage::withoutGlobalScope('tenant')->orderBy('position')->get(['id', 'name']);
        $users  = User::where('tenant_id', activeTenant()->id)->get(['id', 'name']);

        return view('tenant.settings.sequences.form', [
            'sequence' => $sequence,
            'stages'   => $stages,
            'users'    => $users,
        ]);
    }

    public function update(Request $request, NurtureSequence $sequence): JsonResponse
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:100',
            'description'           => 'nullable|string|max:191',
            'exit_on_reply'         => 'boolean',
            'exit_on_stage_change'  => 'boolean',
            'steps'                 => 'required|array|min:1',
            'steps.*.type'          => 'required|string|in:message,wait_reply,condition,action',
            'steps.*.delay_minutes' => 'required|integer|min:0',
            'steps.*.config'        => 'required|array',
        ]);

        $sequence->update([
            'name'                 => $data['name'],
            'description'          => $data['description'] ?? null,
            'exit_on_reply'        => $data['exit_on_reply'] ?? true,
            'exit_on_stage_change' => $data['exit_on_stage_change'] ?? false,
        ]);

        // Replace steps
        $sequence->steps()->delete();
        foreach ($data['steps'] as $i => $stepData) {
            NurtureSequenceStep::create([
                'sequence_id'   => $sequence->id,
                'position'      => $i + 1,
                'delay_minutes' => $stepData['delay_minutes'],
                'type'          => $stepData['type'],
                'config'        => $stepData['config'],
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(NurtureSequence $sequence): JsonResponse
    {
        $sequence->delete();
        return response()->json(['success' => true]);
    }

    public function toggle(NurtureSequence $sequence): JsonResponse
    {
        $sequence->update(['is_active' => !$sequence->is_active]);
        return response()->json(['success' => true, 'is_active' => $sequence->is_active]);
    }

    public function enroll(Request $request, NurtureSequence $sequence): JsonResponse
    {
        $data = $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'integer|exists:leads,id',
        ]);

        $service  = new NurtureSequenceService();
        $enrolled = 0;

        foreach ($data['lead_ids'] as $leadId) {
            $lead = Lead::find($leadId);
            if ($lead && $service->enroll($lead, $sequence)) {
                $enrolled++;
            }
        }

        return response()->json(['success' => true, 'enrolled' => $enrolled]);
    }

    public function unenroll(Request $request, NurtureSequence $sequence): JsonResponse
    {
        $data = $request->validate([
            'lead_id' => 'required|integer',
        ]);

        $ls = LeadSequence::where('lead_id', $data['lead_id'])
            ->where('sequence_id', $sequence->id)
            ->whereIn('status', ['active', 'paused'])
            ->first();

        if ($ls) {
            $ls->markExited('manual');
        }

        return response()->json(['success' => true]);
    }
}
