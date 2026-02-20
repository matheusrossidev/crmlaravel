<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\LostSale;
use App\Models\PipelineStage;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    // ── POST /api/v1/leads ────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:191',
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
            'description'  => 'Lead criado via API',
            'performed_by' => auth()->id(),
            'created_at'   => now(),
        ]);

        $lead->load(['stage', 'pipeline', 'campaign']);

        return response()->json([
            'success' => true,
            'lead'    => $this->formatLead($lead),
        ], 201);
    }

    // ── GET /api/v1/leads/{lead} ──────────────────────────────────────────
    public function show(Lead $lead): JsonResponse
    {
        $lead->load(['stage', 'pipeline', 'campaign', 'events', 'customFieldValues.fieldDefinition']);

        return response()->json([
            'success' => true,
            'lead'    => $this->formatLead($lead),
        ]);
    }

    // ── PUT /api/v1/leads/{lead}/stage ────────────────────────────────────
    public function stage(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'stage_id'    => 'required|integer|exists:pipeline_stages,id',
            'pipeline_id' => 'required|integer|exists:pipelines,id',
        ]);

        $oldStageId = $lead->stage_id;
        $lead->update($data);

        if ($oldStageId !== (int) $data['stage_id']) {
            $newStage = PipelineStage::find($data['stage_id']);
            LeadEvent::create([
                'lead_id'      => $lead->id,
                'event_type'   => 'stage_changed',
                'description'  => "Movido para {$newStage?->name} via API",
                'performed_by' => auth()->id(),
                'created_at'   => now(),
            ]);
        }

        return response()->json(['success' => true, 'lead_id' => $lead->id]);
    }

    // ── PUT /api/v1/leads/{lead}/won ──────────────────────────────────────
    public function won(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'stage_id' => 'required|integer|exists:pipeline_stages,id',
            'value'    => 'nullable|numeric|min:0',
        ]);

        $stage = PipelineStage::findOrFail($data['stage_id']);

        if (!$stage->is_won) {
            return response()->json(['message' => 'A etapa informada não é uma etapa de ganho.'], 422);
        }

        $updateData = ['stage_id' => $stage->id, 'pipeline_id' => $stage->pipeline_id];
        if (!empty($data['value'])) {
            $updateData['value'] = $data['value'];
        }
        $lead->update($updateData);

        Sale::create([
            'lead_id'     => $lead->id,
            'campaign_id' => $lead->campaign_id,
            'pipeline_id' => $lead->pipeline_id,
            'value'       => $data['value'] ?? $lead->value ?? 0,
            'closed_by'   => auth()->id(),
            'closed_at'   => now(),
        ]);

        LeadEvent::create([
            'lead_id'      => $lead->id,
            'event_type'   => 'sale_won',
            'description'  => 'Venda ganha via API',
            'performed_by' => auth()->id(),
            'created_at'   => now(),
        ]);

        return response()->json(['success' => true, 'lead_id' => $lead->id]);
    }

    // ── PUT /api/v1/leads/{lead}/lost ─────────────────────────────────────
    public function lost(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'stage_id'  => 'required|integer|exists:pipeline_stages,id',
            'reason_id' => 'nullable|integer|exists:lost_sale_reasons,id',
        ]);

        $stage = PipelineStage::findOrFail($data['stage_id']);

        if (!$stage->is_lost) {
            return response()->json(['message' => 'A etapa informada não é uma etapa de perda.'], 422);
        }

        $lead->update(['stage_id' => $stage->id, 'pipeline_id' => $stage->pipeline_id]);

        LostSale::create([
            'lead_id'     => $lead->id,
            'pipeline_id' => $lead->pipeline_id,
            'campaign_id' => $lead->campaign_id,
            'reason_id'   => $data['reason_id'] ?? null,
            'lost_at'     => now(),
            'lost_by'     => auth()->id(),
        ]);

        LeadEvent::create([
            'lead_id'      => $lead->id,
            'event_type'   => 'sale_lost',
            'description'  => 'Lead perdido via API',
            'performed_by' => auth()->id(),
            'created_at'   => now(),
        ]);

        return response()->json(['success' => true, 'lead_id' => $lead->id]);
    }

    // ── DELETE /api/v1/leads/{lead} ───────────────────────────────────────
    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json(['success' => true]);
    }

    private function formatLead(Lead $lead): array
    {
        return [
            'id'            => $lead->id,
            'name'          => $lead->name,
            'phone'         => $lead->phone,
            'email'         => $lead->email,
            'value'         => $lead->value,
            'source'        => $lead->source,
            'tags'          => $lead->tags ?? [],
            'pipeline_id'   => $lead->pipeline_id,
            'stage_id'      => $lead->stage_id,
            'campaign_id'   => $lead->campaign_id,
            'notes'         => $lead->notes,
            'stage'         => $lead->stage   ? ['id' => $lead->stage->id,    'name' => $lead->stage->name]    : null,
            'pipeline'      => $lead->pipeline ? ['id' => $lead->pipeline->id, 'name' => $lead->pipeline->name] : null,
            'created_at'    => $lead->created_at?->toISOString(),
            'custom_fields' => $lead->customFields,
        ];
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
