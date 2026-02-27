<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiAgent;
use App\Models\Automation;
use App\Models\ChatbotFlow;
use App\Models\InstagramConversation;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\PipelineStage;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use Illuminate\Support\Facades\Log;

class AutomationEngine
{
    /**
     * Dispara automações para um determinado trigger_type.
     *
     * @param  string  $triggerType  message_received|conversation_created|lead_created|lead_stage_changed|lead_won|lead_lost
     * @param  array   $context {
     *   tenant_id: int,
     *   channel: 'whatsapp'|'instagram'|null,
     *   message: WhatsappMessage|InstagramMessage|null,
     *   conversation: WhatsappConversation|InstagramConversation|null,
     *   lead: Lead|null,
     *   stage_new: PipelineStage|null,
     *   stage_old_id: int|null,
     * }
     */
    public function run(string $triggerType, array $context): void
    {
        $tenantId = $context['tenant_id'] ?? null;
        if (! $tenantId) {
            return;
        }

        $automations = Automation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('trigger_type', $triggerType)
            ->get();

        foreach ($automations as $automation) {
            try {
                if (! $this->matchesConditions($automation, $context)) {
                    continue;
                }

                foreach ($automation->actions as $action) {
                    $this->executeAction($action, $context, $automation);
                }

                Automation::withoutGlobalScope('tenant')
                    ->where('id', $automation->id)
                    ->update([
                        'run_count'   => $automation->run_count + 1,
                        'last_run_at' => now(),
                    ]);
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->error('AutomationEngine: erro ao executar automação', [
                    'automation_id' => $automation->id,
                    'trigger'       => $triggerType,
                    'error'         => $e->getMessage(),
                ]);
            }
        }
    }

    // ────────────────────────────────────────────────────────────────────────────

    private function matchesConditions(Automation $automation, array $ctx): bool
    {
        $conditions = $automation->conditions ?? [];
        $config     = $automation->trigger_config ?? [];

        // Filtro de canal (WhatsApp / Instagram / ambos)
        if (! empty($config['channel']) && $config['channel'] !== 'both') {
            if (($ctx['channel'] ?? null) !== $config['channel']) {
                return false;
            }
        }

        // Filtro de pipeline específica
        if (! empty($config['pipeline_id'])) {
            $lead = $ctx['lead'] ?? null;
            if (! $lead || (int) $lead->pipeline_id !== (int) $config['pipeline_id']) {
                return false;
            }
        }

        // Filtro de etapa destino (para lead_stage_changed)
        if (! empty($config['stage_id'])) {
            $stageNew = $ctx['stage_new'] ?? null;
            if (! $stageNew || (int) $stageNew->id !== (int) $config['stage_id']) {
                return false;
            }
        }

        // Filtro de origem do lead
        if (! empty($config['source'])) {
            $lead = $ctx['lead'] ?? null;
            if (! $lead || $lead->source !== $config['source']) {
                return false;
            }
        }

        // Condições dinâmicas adicionais
        foreach ($conditions as $condition) {
            if (! $this->evaluateCondition($condition, $ctx)) {
                return false;
            }
        }

        return true;
    }

    private function evaluateCondition(array $condition, array $ctx): bool
    {
        $field    = $condition['field']    ?? '';
        $operator = $condition['operator'] ?? 'contains';
        $value    = $condition['value']    ?? '';

        if ($field === 'message_body') {
            $body = '';
            if (isset($ctx['message'])) {
                $body = (string) ($ctx['message']->body ?? '');
            }
            return match($operator) {
                'contains'    => str_contains(mb_strtolower($body), mb_strtolower((string) $value)),
                'not_contains'=> ! str_contains(mb_strtolower($body), mb_strtolower((string) $value)),
                'equals'      => mb_strtolower($body) === mb_strtolower((string) $value),
                'starts_with' => str_starts_with(mb_strtolower($body), mb_strtolower((string) $value)),
                default       => false,
            };
        }

        if ($field === 'lead_source') {
            $source = (string) ($ctx['lead']?->source ?? '');
            return match($operator) {
                'equals'     => $source === (string) $value,
                'not_equals' => $source !== (string) $value,
                default      => false,
            };
        }

        if ($field === 'lead_tag') {
            $tags = (array) ($ctx['lead']?->tags ?? []);
            return match($operator) {
                'contains'     => in_array((string) $value, $tags, true),
                'not_contains' => ! in_array((string) $value, $tags, true),
                default        => false,
            };
        }

        if ($field === 'conversation_tag') {
            $conv = $ctx['conversation'] ?? null;
            $tags = (array) ($conv?->tags ?? []);
            return match($operator) {
                'contains'     => in_array((string) $value, $tags, true),
                'not_contains' => ! in_array((string) $value, $tags, true),
                default        => false,
            };
        }

        return true;
    }

    // ────────────────────────────────────────────────────────────────────────────

    private function executeAction(array $action, array $ctx, Automation $automation): void
    {
        $type   = $action['type']   ?? '';
        $config = $action['config'] ?? [];

        match($type) {
            'add_tag_lead'        => $this->actionAddTagLead($config, $ctx),
            'remove_tag_lead'     => $this->actionRemoveTagLead($config, $ctx),
            'add_tag_conversation'=> $this->actionAddTagConversation($config, $ctx),
            'move_to_stage'       => $this->actionMoveToStage($config, $ctx),
            'set_lead_source'     => $this->actionSetLeadSource($config, $ctx),
            'assign_to_user'      => $this->actionAssignToUser($config, $ctx),
            'add_note'            => $this->actionAddNote($config, $ctx),
            'assign_ai_agent'     => $this->actionAssignAiAgent($config, $ctx),
            'assign_chatbot_flow' => $this->actionAssignChatbotFlow($config, $ctx),
            'close_conversation'  => $this->actionCloseConversation($ctx),
            'send_whatsapp_message'=> $this->actionSendWhatsappMessage($config, $ctx, $automation),
            default               => null,
        };
    }

    // ── Ações ────────────────────────────────────────────────────────────────────

    private function actionAddTagLead(array $config, array $ctx): void
    {
        $lead = $this->resolveLead($ctx);
        if (! $lead) {
            return;
        }
        $tagsToAdd = (array) ($config['tags'] ?? []);
        $current   = (array) ($lead->tags ?? []);
        $merged    = array_values(array_unique(array_merge($current, $tagsToAdd)));
        Lead::withoutGlobalScope('tenant')->where('id', $lead->id)->update(['tags' => json_encode($merged)]);
    }

    private function actionRemoveTagLead(array $config, array $ctx): void
    {
        $lead = $this->resolveLead($ctx);
        if (! $lead) {
            return;
        }
        $tagsToRemove = (array) ($config['tags'] ?? []);
        $current      = (array) ($lead->tags ?? []);
        $filtered     = array_values(array_filter($current, fn ($t) => ! in_array($t, $tagsToRemove, true)));
        Lead::withoutGlobalScope('tenant')->where('id', $lead->id)->update(['tags' => json_encode($filtered)]);
    }

    private function actionAddTagConversation(array $config, array $ctx): void
    {
        $conv = $ctx['conversation'] ?? null;
        if (! $conv) {
            return;
        }
        $tagsToAdd = (array) ($config['tags'] ?? []);
        $current   = (array) ($conv->tags ?? []);
        $merged    = array_values(array_unique(array_merge($current, $tagsToAdd)));

        if ($conv instanceof WhatsappConversation) {
            WhatsappConversation::withoutGlobalScope('tenant')
                ->where('id', $conv->id)
                ->update(['tags' => json_encode($merged)]);
        } elseif ($conv instanceof InstagramConversation) {
            InstagramConversation::withoutGlobalScope('tenant')
                ->where('id', $conv->id)
                ->update(['tags' => json_encode($merged)]);
        }
    }

    private function actionMoveToStage(array $config, array $ctx): void
    {
        $lead = $this->resolveLead($ctx);
        if (! $lead || empty($config['stage_id'])) {
            return;
        }
        $stage = PipelineStage::withoutGlobalScope('tenant')->find((int) $config['stage_id']);
        if (! $stage) {
            return;
        }
        Lead::withoutGlobalScope('tenant')->where('id', $lead->id)->update([
            'stage_id'    => $stage->id,
            'pipeline_id' => $stage->pipeline_id,
        ]);

        \App\Models\LeadEvent::create([
            'lead_id'      => $lead->id,
            'event_type'   => 'stage_changed',
            'description'  => "Movido para {$stage->name} (automação)",
            'performed_by' => null,
            'created_at'   => now(),
        ]);
    }

    private function actionSetLeadSource(array $config, array $ctx): void
    {
        $lead = $this->resolveLead($ctx);
        if (! $lead || empty($config['source'])) {
            return;
        }
        Lead::withoutGlobalScope('tenant')->where('id', $lead->id)->update(['source' => $config['source']]);
    }

    private function actionAssignToUser(array $config, array $ctx): void
    {
        $lead = $this->resolveLead($ctx);
        if (! $lead || empty($config['user_id'])) {
            return;
        }
        Lead::withoutGlobalScope('tenant')->where('id', $lead->id)->update(['assigned_to' => (int) $config['user_id']]);
    }

    private function actionAddNote(array $config, array $ctx): void
    {
        $lead = $this->resolveLead($ctx);
        if (! $lead || empty($config['body'])) {
            return;
        }
        $body = $this->interpolate((string) $config['body'], $ctx);
        LeadNote::create([
            'tenant_id'  => $lead->tenant_id,
            'lead_id'    => $lead->id,
            'body'       => $body,
            'created_by' => null,
        ]);
    }

    private function actionAssignAiAgent(array $config, array $ctx): void
    {
        $conv = $ctx['conversation'] ?? null;
        if (! ($conv instanceof WhatsappConversation) || empty($config['ai_agent_id'])) {
            return;
        }
        $agent = AiAgent::withoutGlobalScope('tenant')->find((int) $config['ai_agent_id']);
        if (! $agent) {
            return;
        }
        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update(['ai_agent_id' => $agent->id, 'chatbot_flow_id' => null]);
    }

    private function actionAssignChatbotFlow(array $config, array $ctx): void
    {
        $conv = $ctx['conversation'] ?? null;
        if (! ($conv instanceof WhatsappConversation) || empty($config['chatbot_flow_id'])) {
            return;
        }
        $flow = ChatbotFlow::withoutGlobalScope('tenant')->find((int) $config['chatbot_flow_id']);
        if (! $flow) {
            return;
        }
        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update(['chatbot_flow_id' => $flow->id, 'ai_agent_id' => null]);
    }

    private function actionCloseConversation(array $ctx): void
    {
        $conv = $ctx['conversation'] ?? null;
        if (! $conv) {
            return;
        }
        if ($conv instanceof WhatsappConversation) {
            WhatsappConversation::withoutGlobalScope('tenant')
                ->where('id', $conv->id)
                ->update(['status' => 'closed', 'closed_at' => now()]);
        } elseif ($conv instanceof InstagramConversation) {
            InstagramConversation::withoutGlobalScope('tenant')
                ->where('id', $conv->id)
                ->update(['status' => 'closed', 'closed_at' => now()]);
        }
    }

    private function actionSendWhatsappMessage(array $config, array $ctx, Automation $automation): void
    {
        $conv = $ctx['conversation'] ?? null;
        if (! ($conv instanceof WhatsappConversation) || empty($config['message'])) {
            return;
        }

        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('tenant_id', $automation->tenant_id)
            ->where('status', 'WORKING')
            ->first();

        if (! $instance) {
            return;
        }

        $text  = $this->interpolate((string) $config['message'], $ctx);
        $waha  = new WahaService($instance->session_name);
        $chatId = $conv->phone . '@c.us';

        try {
            $waha->sendText($chatId, $text);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('AutomationEngine: falha ao enviar mensagem', [
                'conversation_id' => $conv->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    private function resolveLead(array $ctx): ?Lead
    {
        if (isset($ctx['lead']) && $ctx['lead'] instanceof Lead) {
            return $ctx['lead'];
        }
        $conv = $ctx['conversation'] ?? null;
        if ($conv && $conv->lead_id) {
            return Lead::withoutGlobalScope('tenant')->find($conv->lead_id);
        }
        return null;
    }

    /**
     * Substitui variáveis como {{contact_name}}, {{phone}}, {{pipeline}}, {{stage}} no texto.
     */
    private function interpolate(string $text, array $ctx): string
    {
        $lead  = $this->resolveLead($ctx);
        $conv  = $ctx['conversation'] ?? null;
        $stage = $ctx['stage_new'] ?? null;

        $vars = [
            '{{contact_name}}' => $conv?->contact_name ?? $lead?->name ?? '',
            '{{phone}}'        => $lead?->phone ?? ($conv?->phone ?? ''),
            '{{lead_name}}'    => $lead?->name ?? '',
            '{{pipeline}}'     => $lead?->pipeline?->name ?? '',
            '{{stage}}'        => $stage?->name ?? $lead?->stage?->name ?? '',
        ];

        return str_replace(array_keys($vars), array_values($vars), $text);
    }
}
