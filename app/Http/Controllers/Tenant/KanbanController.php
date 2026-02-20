<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CustomFieldDefinition;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\LostSale;
use App\Models\LostSaleReason;
use App\Models\Sale;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
                    ->with(['campaign', 'assignedTo', 'customFieldValues.fieldDefinition'])
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
                    'id'      => $stage->id,
                    'name'    => $stage->name,
                    'color'   => $stage->color,
                    'is_won'  => $stage->is_won,
                    'is_lost' => $stage->is_lost,
                    'leads'   => $leads,
                    'lead_cf' => $leadCf,
                    'count'   => $leads->count(),
                ];
            });
        }

        $lostReasons     = LostSaleReason::where('is_active', true)->orderBy('sort_order')->get();
        $customFieldDefs = CustomFieldDefinition::where('is_active', true)->orderBy('sort_order')->get();

        return view('tenant.crm.kanban', compact('pipelines', 'pipeline', 'stages', 'campaigns', 'lostReasons', 'customFieldDefs'));
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

    // ── GET /crm/poll?pipeline_id=X&since=TIMESTAMP ───────────────────────
    public function poll(Request $request): JsonResponse
    {
        $since      = (int) $request->get('since', 0);
        $pipelineId = (int) $request->get('pipeline_id', 0);

        if (!$pipelineId) {
            return response()->json(['leads' => [], 'server_time' => now()->timestamp]);
        }

        $sinceDate = Carbon::createFromTimestamp($since);

        $leads = Lead::with(['campaign', 'customFieldValues.fieldDefinition', 'stage'])
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
            'id'          => $lead->id,
            'name'        => $lead->name,
            'phone'       => $lead->phone,
            'email'       => $lead->email,
            'value'       => $lead->value,
            'value_fmt'   => $lead->value ? 'R$ ' . number_format((float) $lead->value, 0, ',', '.') : null,
            'source'      => $lead->source,
            'tags'        => $lead->tags ?? [],
            'stage_id'    => $lead->stage_id,
            'pipeline_id' => $lead->pipeline_id,
            'campaign_id' => $lead->campaign_id,
            'campaign'    => $lead->campaign ? ['id' => $lead->campaign->id, 'name' => $lead->campaign->name] : null,
            'cf_flat'     => $cfFlat,
        ];
    }
}
