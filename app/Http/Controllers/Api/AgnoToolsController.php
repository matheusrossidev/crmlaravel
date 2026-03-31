<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\AiIntentDetected;
use App\Events\WhatsappConversationUpdated;
use App\Http\Controllers\Controller;
use App\Models\AiIntentSignal;
use App\Models\Lead;
use App\Models\WhatsappConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\PipelineStage;
use App\Services\StageRequirementService;
use Illuminate\Support\Facades\Log;

/**
 * Rotas internas chamadas pelo microsserviço Agno.
 * Autenticadas via AgnoInternalMiddleware (X-Agno-Token).
 */
class AgnoToolsController extends Controller
{
    public function setStage(Request $request, int $leadId): JsonResponse
    {
        $data = $request->validate([
            'stage_id'  => 'required|integer',
            'tenant_id' => 'required|integer',
        ]);

        $lead = Lead::withoutGlobalScope('tenant')
            ->where('id', $leadId)
            ->where('tenant_id', $data['tenant_id'])
            ->firstOrFail();

        $oldStageId = $lead->stage_id;

        // Check mandatory tasks before allowing stage exit
        if ($oldStageId !== (int) $data['stage_id']) {
            $check = (new StageRequirementService())->canLeaveStage($lead, $oldStageId);
            if (!$check['allowed']) {
                return response()->json([
                    'success'       => false,
                    'blocked'       => true,
                    'message'       => 'Mandatory tasks pending on current stage.',
                    'pending_tasks' => $check['pending'],
                ], 422);
            }
        }

        $lead->update(['stage_id' => $data['stage_id']]);

        // Create mandatory tasks for the new stage
        if ($oldStageId !== (int) $data['stage_id']) {
            try {
                $newStage = PipelineStage::find($data['stage_id']);
                if ($newStage) {
                    (new StageRequirementService())->createRequiredTasks($lead->fresh(), $newStage);
                }
            } catch (\Throwable) {}
        }

        Log::channel('whatsapp')->info('Agno tool: lead movido de etapa', [
            'lead_id'  => $leadId,
            'stage_id' => $data['stage_id'],
        ]);

        return response()->json(['success' => true]);
    }

    public function addTag(Request $request, int $leadId): JsonResponse
    {
        $data = $request->validate([
            'tag_name'  => 'required|string|max:100',
            'tenant_id' => 'required|integer',
        ]);

        $conv = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('lead_id', $leadId)
            ->where('tenant_id', $data['tenant_id'])
            ->latest()
            ->first();

        if ($conv) {
            $existing = $conv->tags ?? [];
            $merged   = array_values(array_unique(array_merge($existing, [$data['tag_name']])));
            WhatsappConversation::withoutGlobalScope('tenant')
                ->where('id', $conv->id)
                ->update(['tags' => json_encode($merged)]);
        }

        return response()->json(['success' => true]);
    }

    public function notifyIntent(Request $request, int $convId): JsonResponse
    {
        $data = $request->validate([
            'intent'    => 'required|string|max:100',
            'reason'    => 'nullable|string|max:500',
            'lead_id'   => 'nullable|integer',
            'tenant_id' => 'required|integer',
        ]);

        $conv = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $convId)
            ->where('tenant_id', $data['tenant_id'])
            ->firstOrFail();

        $validTypes = ['buy', 'schedule', 'close', 'interest'];
        $intentType = in_array($data['intent'], $validTypes, true) ? $data['intent'] : 'interest';

        $signal = AiIntentSignal::withoutGlobalScope('tenant')->create([
            'tenant_id'       => $conv->tenant_id,
            'ai_agent_id'     => $conv->ai_agent_id,
            'conversation_id' => $conv->id,
            'contact_name'    => $conv->contact_name ?? $conv->phone,
            'phone'           => $conv->phone,
            'intent_type'     => $intentType,
            'context'         => mb_substr((string) ($data['reason'] ?? ''), 0, 500),
        ]);

        try {
            AiIntentDetected::dispatch($signal, $conv->tenant_id);
        } catch (\Throwable) {
        }

        return response()->json(['success' => true]);
    }

    public function transferToHuman(Request $request, int $convId): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'required|integer',
        ]);

        $conv = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $convId)
            ->where('tenant_id', $data['tenant_id'])
            ->firstOrFail();

        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update(['ai_agent_id' => null]);

        $conv->refresh();

        try {
            WhatsappConversationUpdated::dispatch($conv, $conv->tenant_id);
        } catch (\Throwable) {
        }

        return response()->json(['success' => true]);
    }
}
