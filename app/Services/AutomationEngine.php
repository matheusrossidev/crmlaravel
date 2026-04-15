<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SummarizeConversation;
use App\Models\AiAgent;
use App\Models\Automation;
use App\Models\ChatbotFlow;
use App\Models\Department;
use App\Models\InstagramConversation;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\PipelineStage;
use App\Models\ScheduledMessage;
use App\Models\Task;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use Illuminate\Support\Facades\Log;

class AutomationEngine
{
    private static array $scoringMap = [
        'message_received'    => 'message_received',
        'lead_created'        => 'profile_complete',
        'lead_stage_changed'  => null, // handled specially
        'lead_won'            => 'lead_won',
        'lead_lost'           => 'lead_lost',
    ];
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

        // ── Lead Scoring ────────────────────────────────────────────────
        $this->evaluateScoring($triggerType, $context);
    }

    private function evaluateScoring(string $triggerType, array $context): void
    {
        $lead = $context['lead'] ?? null;
        if (! $lead instanceof Lead) {
            return;
        }

        try {
            $scorer = new LeadScoringService();

            // Map trigger to scoring event(s)
            if ($triggerType === 'lead_stage_changed') {
                $stageOldId = $context['stage_old_id'] ?? null;
                $stageNew   = $context['stage_new'] ?? null;
                if ($stageNew && $stageOldId) {
                    $oldPos = PipelineStage::withoutGlobalScope('tenant')->find($stageOldId)?->position ?? 0;
                    $newPos = $stageNew->position ?? 0;
                    $scorer->evaluate($lead, $newPos > $oldPos ? 'stage_advanced' : 'stage_regressed', $context);
                }
            } elseif ($triggerType === 'message_received') {
                $scorer->evaluate($lead, 'message_received', $context);

                // Check for media
                $msg = $context['message'] ?? null;
                if ($msg && in_array($msg->type ?? '', ['image', 'video', 'audio', 'document'])) {
                    $scorer->evaluate($lead, 'message_sent_media', $context);
                }
            } elseif (isset(self::$scoringMap[$triggerType])) {
                $scorer->evaluate($lead, self::$scoringMap[$triggerType], $context);
            }
        } catch (\Throwable $e) {
            Log::warning('LeadScoring: evaluation failed in AutomationEngine', [
                'trigger' => $triggerType,
                'lead_id' => $lead->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Execute actions for a specific automation (used by recurring triggers).
     */
    public function runForAutomation(Automation $automation, array $context): void
    {
        foreach ($automation->actions as $action) {
            $this->executeAction($action, $context, $automation);
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

        // Filtro de instância WhatsApp (só faz sentido se há conversa no contexto)
        if (! empty($config['whatsapp_instance_id'])) {
            $conv = $ctx['conversation'] ?? null;
            if (! ($conv instanceof WhatsappConversation)
                || (int) $conv->instance_id !== (int) $config['whatsapp_instance_id']) {
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
            'send_whatsapp_message'         => $this->actionSendWhatsappMessage($config, $ctx, $automation),
            'schedule_whatsapp_message'     => $this->actionScheduleWhatsappMessage($config, $ctx),
            'send_whatsapp_template'        => $this->actionSendWhatsappTemplate($config, $ctx, $automation),
            'send_whatsapp_buttons'         => $this->actionSendWhatsappButtons($config, $ctx, $automation),
            'send_whatsapp_list'            => $this->actionSendWhatsappList($config, $ctx, $automation),
            'set_utm_params'                => $this->actionSetUtmParams($config, $ctx),
            'transfer_to_department'        => $this->actionTransferToDepartment($config, $ctx),
            'create_task'                   => $this->actionCreateTask($config, $ctx),
            'enroll_sequence'               => $this->actionEnrollSequence($config, $ctx),
            'ai_extract_fields'             => $this->actionAiExtractFields($config, $ctx, $automation),
            'send_webhook'                  => $this->actionSendWebhook($config, $ctx, $automation),
            default               => null,
        };
    }

    private function actionAiExtractFields(array $config, array $ctx, Automation $automation): void
    {
        $lead = $this->resolveLead($ctx);
        if (! $lead) {
            return;
        }
        \App\Jobs\ExtractLeadDataJob::dispatch(
            $lead->id,
            $automation->tenant_id,
            $config,
        );
    }

    private function actionSendWebhook(array $config, array $ctx, Automation $automation): void
    {
        $lead = $this->resolveLead($ctx);
        if (! $lead) {
            return;
        }
        \App\Jobs\DispatchAutomationWebhookJob::dispatch(
            $lead->id,
            $automation->tenant_id,
            $config,
            $automation->trigger_type,
        );
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

        // Dual write: pivot polimorfica
        $leadModel = Lead::withoutGlobalScope('tenant')->find($lead->id);
        if ($leadModel) {
            $leadModel->attachTagsByName($tagsToAdd);
        }
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

        // Dual write: pivot polimorfica
        $leadModel = Lead::withoutGlobalScope('tenant')->find($lead->id);
        if ($leadModel) {
            $leadModel->detachTagsByName($tagsToRemove);
        }
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
            // Dual write
            $fresh = WhatsappConversation::withoutGlobalScope('tenant')->find($conv->id);
            $fresh?->attachTagsByName($tagsToAdd);
        } elseif ($conv instanceof InstagramConversation) {
            InstagramConversation::withoutGlobalScope('tenant')
                ->where('id', $conv->id)
                ->update(['tags' => json_encode($merged)]);
            // Dual write
            $fresh = InstagramConversation::withoutGlobalScope('tenant')->find($conv->id);
            $fresh?->attachTagsByName($tagsToAdd);
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

        // Idempotente: se o lead ja esta nesse stage, nao reatribui — evita criar
        // LeadEvent duplicado + re-disparar StageRequirementService que pode
        // gerar tasks duplicadas.
        if ((int) $lead->stage_id === (int) $stage->id) {
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

        // Create mandatory tasks for the new stage (no validation — automations are admin-configured)
        try {
            (new StageRequirementService())->createRequiredTasks($lead->fresh(), $stage);
        } catch (\Throwable) {}
    }

    private function actionSetLeadSource(array $config, array $ctx): void
    {
        $lead = $this->resolveLead($ctx);
        if (! $lead || empty($config['source'])) {
            return;
        }

        // Idempotente
        if ($lead->source === $config['source']) {
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
        $userId = (int) $config['user_id'];

        // Idempotente: se o lead ja esta atribuido a esse user, nao faz nada.
        // Sem esse check, toda execucao da automacao fazia UPDATE + disparava
        // notificacao "Lead atribuido a voce" mesmo quando nada mudava — causando
        // spam de notificacoes pra automacoes que rodam em sequencia (ex: lead
        // entra na conversa e cada disparo de message_received tentava reatribuir).
        if ((int) $lead->assigned_to === $userId) {
            return;
        }

        Lead::withoutGlobalScope('tenant')->where('id', $lead->id)->update(['assigned_to' => $userId]);

        // Notificação: lead atribuído via automação (so quando assignment mudou)
        try {
            (new NotificationDispatcher())->dispatch('lead_assigned', [
                'lead_name'   => $lead->name,
                'assigned_by' => 'Automação',
                'url'         => route('leads.index', ['lead' => $lead->id]),
            ], $ctx['tenant_id'], targetUserId: $userId);
        } catch (\Throwable) {}
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
        $agentId = (int) $config['ai_agent_id'];

        // Idempotente: se o agent ja esta atribuido E nao tem chatbot pra limpar, skip
        if ((int) $conv->ai_agent_id === $agentId && $conv->chatbot_flow_id === null) {
            return;
        }

        $agent = AiAgent::withoutGlobalScope('tenant')->find($agentId);
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
        $flowId = (int) $config['chatbot_flow_id'];

        // Idempotente: se o flow ja esta atribuido E nao tem ai_agent pra limpar, skip
        if ((int) $conv->chatbot_flow_id === $flowId && $conv->ai_agent_id === null) {
            return;
        }

        $flow = ChatbotFlow::withoutGlobalScope('tenant')->find($flowId);
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
            if ($conv->ai_agent_id) {
                SummarizeConversation::dispatch($conv->id);
            }
        } elseif ($conv instanceof InstagramConversation) {
            InstagramConversation::withoutGlobalScope('tenant')
                ->where('id', $conv->id)
                ->update(['status' => 'closed', 'closed_at' => now()]);
        }
    }

    private function actionSendWhatsappMessage(array $config, array $ctx, Automation $automation): void
    {
        Log::channel('whatsapp')->info('AutomationEngine: actionSendWhatsappMessage INICIADA', [
            'automation_id' => $automation->id,
            'has_message'   => !empty($config['message']),
        ]);

        if (empty($config['message'])) {
            Log::channel('whatsapp')->warning('AutomationEngine: message vazia, abortando');
            return;
        }

        // Resolver conversa: do contexto direto, ou buscar via lead, ou via phone
        $conv = $ctx['conversation'] ?? null;
        $lead = $this->resolveLead($ctx);

        if (! ($conv instanceof WhatsappConversation) && $lead) {
            // 1. Busca por lead_id direto
            $conv = WhatsappConversation::withoutGlobalScope('tenant')
                ->where('tenant_id', $automation->tenant_id)
                ->where('lead_id', $lead->id)
                ->latest('last_message_at')
                ->first();

            // 2. Se não achou, busca pelo phone (com e sem nono dígito BR)
            if (! $conv && $lead->phone) {
                $e164 = \App\Support\PhoneNormalizer::toE164($lead->phone);
                if ($e164) {
                    $phoneVariants = [$e164];
                    // Nono dígito BR: 55 + 2 DDD + 9 + 8 dígitos = 13 chars
                    // Sem nono:       55 + 2 DDD + 8 dígitos     = 12 chars
                    if (strlen($e164) === 13 && str_starts_with($e164, '55')) {
                        $withoutNine = substr($e164, 0, 4) . substr($e164, 5); // remove o 9 após DDD
                        $phoneVariants[] = $withoutNine;
                    } elseif (strlen($e164) === 12 && str_starts_with($e164, '55')) {
                        $withNine = substr($e164, 0, 4) . '9' . substr($e164, 4); // adiciona 9 após DDD
                        $phoneVariants[] = $withNine;
                    }

                    $conv = WhatsappConversation::withoutGlobalScope('tenant')
                        ->where('tenant_id', $automation->tenant_id)
                        ->whereIn('phone', $phoneVariants)
                        ->latest('last_message_at')
                        ->first();
                }
            }
        }

        // Determinar o telefone — preferir o da conversa (é o que o WhatsApp conhece)
        $phone = $conv instanceof WhatsappConversation
            ? $conv->phone
            : \App\Support\PhoneNormalizer::toE164($lead?->phone);

        if (! $phone) {
            Log::channel('whatsapp')->warning('AutomationEngine: send_whatsapp_message sem phone', [
                'automation_id' => $automation->id,
            ]);
            return;
        }

        // Selecionar instância conectada — prioridade:
        // 1. instance_id explicito no config da action (escolha do user na UI)
        // 2. Instancia da conversa (se ja existe)
        // 3. Primary instance do tenant (resolvePrimary respeita is_primary + status)
        $instance = null;
        if (! empty($config['instance_id'])) {
            $instance = WhatsappInstance::withoutGlobalScope('tenant')
                ->where('id', (int) $config['instance_id'])
                ->where('tenant_id', $automation->tenant_id)
                ->where('status', 'connected')
                ->first();
        }
        if (! $instance && $conv instanceof WhatsappConversation && $conv->instance_id) {
            $instance = WhatsappInstance::withoutGlobalScope('tenant')
                ->where('id', $conv->instance_id)
                ->where('status', 'connected')
                ->first();
        }
        if (! $instance) {
            $instance = WhatsappInstance::resolvePrimary($automation->tenant_id);
        }

        if (! $instance) {
            Log::channel('whatsapp')->warning('AutomationEngine: nenhuma instância WhatsApp conectada', [
                'automation_id' => $automation->id,
                'tenant_id'     => $automation->tenant_id,
            ]);
            return;
        }

        // Sem conversa existente: criar automaticamente para rastreabilidade completa
        if (! ($conv instanceof WhatsappConversation)) {
            $conv = WhatsappConversation::withoutGlobalScope('tenant')->create([
                'tenant_id'       => $automation->tenant_id,
                'instance_id'     => $instance->id,
                'phone'           => $phone,
                'lead_id'         => $lead?->id,
                'is_group'        => false,
                'contact_name'    => $lead?->name ?? $phone,
                'status'          => 'open',
                'started_at'      => now(),
                'last_message_at' => now(),
                'unread_count'    => 0,
            ]);

            Log::channel('whatsapp')->info('AutomationEngine: conversa criada para envio', [
                'automation_id'   => $automation->id,
                'conversation_id' => $conv->id,
                'phone'           => $phone,
            ]);

        }

        $text = $this->interpolate((string) $config['message'], $ctx);

        // B2/B4: se é Cloud API e janela 24h fechada, texto livre vai falhar no Meta.
        // Opções: (1) pular se não tem fallback_template_id na config, (2) usar template.
        $conv->setRelation('instance', $instance);
        $checker = app(\App\Services\Whatsapp\ConversationWindowChecker::class);
        if ($checker->isCloudApi($conv) && ! $checker->isOpen($conv)) {
            $fallbackTplId = (int) ($config['fallback_template_id'] ?? 0);
            if (! $fallbackTplId) {
                Log::channel('whatsapp')->info('AutomationEngine: send_whatsapp_message pulado (janela 24h fechada, sem template fallback)', [
                    'automation_id'   => $automation->id,
                    'conversation_id' => $conv->id,
                ]);
                return;
            }
            // Delega pra action de template com o template do fallback.
            $this->actionSendWhatsappTemplate(
                ['template_id' => $fallbackTplId, 'instance_id' => $instance->id] + $config,
                $ctx,
                $automation,
            );
            return;
        }

        $service = \App\Services\WhatsappServiceFactory::for($instance);
        $chatId  = app(\App\Services\Whatsapp\ChatIdResolver::class)
            ->for($instance, (string) $phone, (bool) $conv->is_group, $conv);

        Log::channel('whatsapp')->info('AutomationEngine: enviando mensagem', [
            'automation_id' => $automation->id,
            'chatId'        => $chatId,
            'instance_id'   => $instance->id,
            'provider'      => $instance->provider ?? 'waha',
            'text_len'      => mb_strlen($text),
        ]);

        try {
            $result = $service->sendText($chatId, $text);

            if (empty($result['error'])) {
                app(\App\Services\Whatsapp\OutboundMessagePersister::class)->persist(
                    conv: $conv,
                    type: 'text',
                    body: $text,
                    sendResult: $result,
                    sentBy: 'automation',
                );
            }

            Log::channel('whatsapp')->info('AutomationEngine: mensagem enviada', [
                'automation_id' => $automation->id,
                'phone'         => $phone,
                'instance'      => $instance->session_name,
            ]);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('AutomationEngine: falha ao enviar mensagem', [
                'automation_id'   => $automation->id,
                'conversation_id' => $conv->id,
                'phone'           => $phone,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    /**
     * Action `send_whatsapp_template`: envia Message Template HSM aprovado.
     * Exclusivo Cloud API (WAHA não suporta — retorna erro not_supported).
     *
     * Config esperada:
     *   - template_id (int, obrigatório)       — ID do WhatsappTemplate APPROVED
     *   - instance_id (int, opcional)          — força instância específica
     *   - variable_mappings (array, opcional)  — ['1' => 'lead.name', '2' => 'lead.company']
     *                                            se null, usa mapping padrão (1=nome, 2=company, 3=email)
     */
    private function actionSendWhatsappTemplate(array $config, array $ctx, Automation $automation): void
    {
        $templateId = (int) ($config['template_id'] ?? 0);
        if (! $templateId) {
            Log::channel('whatsapp')->warning('AutomationEngine: send_whatsapp_template sem template_id', [
                'automation_id' => $automation->id,
            ]);
            return;
        }

        $template = \App\Models\WhatsappTemplate::withoutGlobalScope('tenant')
            ->where('id', $templateId)
            ->where('tenant_id', $automation->tenant_id)
            ->where('status', 'APPROVED')
            ->first();

        if (! $template) {
            Log::channel('whatsapp')->warning('AutomationEngine: template não encontrado ou não aprovado', [
                'automation_id' => $automation->id,
                'template_id'   => $templateId,
            ]);
            return;
        }

        [$conv, $instance, $phone] = $this->resolveWhatsappTarget($config, $ctx, $automation);
        if (! $instance || ! $phone) {
            return;
        }
        if (! $instance->supportsTemplates()) {
            Log::channel('whatsapp')->warning('AutomationEngine: instância não suporta templates (não é Cloud API)', [
                'automation_id' => $automation->id,
                'instance_id'   => $instance->id,
            ]);
            return;
        }

        // Mapping: extrai variáveis do lead conforme config do user OU mapping padrão.
        $lead = $this->resolveLead($ctx);
        $variables = $this->buildTemplateVariables(
            $template,
            $lead,
            $ctx,
            (array) ($config['variable_mappings'] ?? []),
        );

        $result = app(\App\Services\Whatsapp\WhatsappTemplateService::class)
            ->send($instance, (string) $phone, $template, $variables);

        if (isset($result['error'])) {
            Log::channel('whatsapp')->error('AutomationEngine: envio de template falhou', [
                'automation_id' => $automation->id,
                'template'      => $template->name,
                'error'         => $result['error'],
            ]);
            return;
        }

        // Garante conversa criada + persiste msg local com preview.
        if (! $conv instanceof WhatsappConversation) {
            $conv = $this->findOrCreateConversation($automation, $instance, $phone, $lead);
        }

        $preview = $this->buildTemplatePreview($template, $variables);
        app(\App\Services\Whatsapp\OutboundMessagePersister::class)->persist(
            conv: $conv,
            type: 'template',
            body: $preview,
            sendResult: $result,
            sentBy: 'automation',
        );
    }

    /**
     * Action `send_whatsapp_buttons`: até 3 botões de resposta rápida.
     * Exclusivo Cloud API (SupportsInteractiveMessages).
     *
     * Config:
     *   - message (string, obrigatório)  — texto principal
     *   - buttons (array, obrigatório)   — [['id' => 'yes', 'title' => 'Sim'], ...]
     *   - footer (string, opcional)
     *   - instance_id (int, opcional)
     */
    private function actionSendWhatsappButtons(array $config, array $ctx, Automation $automation): void
    {
        $body    = $this->interpolate((string) ($config['message'] ?? ''), $ctx);
        $buttons = (array) ($config['buttons'] ?? []);
        $footer  = ! empty($config['footer']) ? $this->interpolate((string) $config['footer'], $ctx) : null;

        if ($body === '' || empty($buttons)) {
            Log::channel('whatsapp')->warning('AutomationEngine: send_whatsapp_buttons sem body ou buttons', [
                'automation_id' => $automation->id,
            ]);
            return;
        }

        [$conv, $instance, $phone] = $this->resolveWhatsappTarget($config, $ctx, $automation);
        if (! $instance || ! $phone) {
            return;
        }
        if (! $instance->supportsInteractiveButtons()) {
            Log::channel('whatsapp')->warning('AutomationEngine: instância não suporta interactive buttons (não é Cloud API)', [
                'automation_id' => $automation->id,
                'instance_id'   => $instance->id,
            ]);
            return;
        }

        $service = \App\Services\WhatsappServiceFactory::for($instance);
        if (! $service instanceof \App\Contracts\SupportsInteractiveMessages) {
            return;
        }

        $chatId = app(\App\Services\Whatsapp\ChatIdResolver::class)
            ->for($instance, (string) $phone, false, $conv instanceof WhatsappConversation ? $conv : null);

        $result = $service->sendInteractiveButtons($chatId, $body, $buttons, $footer);

        if (isset($result['error'])) {
            Log::channel('whatsapp')->error('AutomationEngine: envio de buttons falhou', [
                'automation_id' => $automation->id,
                'error'         => $result['error'] ?? 'unknown',
            ]);
            return;
        }

        $lead = $this->resolveLead($ctx);
        if (! $conv instanceof WhatsappConversation) {
            $conv = $this->findOrCreateConversation($automation, $instance, $phone, $lead);
        }

        app(\App\Services\Whatsapp\OutboundMessagePersister::class)->persist(
            conv: $conv,
            type: 'interactive',
            body: $body,
            sendResult: $result,
            sentBy: 'automation',
        );
    }

    /**
     * Action `send_whatsapp_list`: lista interativa com seções.
     * Funciona no Cloud E no WAHA (sendList está no contrato base).
     */
    private function actionSendWhatsappList(array $config, array $ctx, Automation $automation): void
    {
        $description = $this->interpolate((string) ($config['message'] ?? ''), $ctx);
        $rows        = (array) ($config['rows'] ?? []);
        $buttonText  = (string) ($config['button_text'] ?? 'Selecione');
        $title       = ! empty($config['title']) ? (string) $config['title'] : null;

        if ($description === '' || empty($rows)) {
            Log::channel('whatsapp')->warning('AutomationEngine: send_whatsapp_list sem description ou rows', [
                'automation_id' => $automation->id,
            ]);
            return;
        }

        [$conv, $instance, $phone] = $this->resolveWhatsappTarget($config, $ctx, $automation);
        if (! $instance || ! $phone) {
            return;
        }

        $service = \App\Services\WhatsappServiceFactory::for($instance);
        $chatId  = app(\App\Services\Whatsapp\ChatIdResolver::class)
            ->for($instance, (string) $phone, false, $conv instanceof WhatsappConversation ? $conv : null);

        $result = $service->sendList($chatId, $description, $rows, $title, $buttonText);

        if (isset($result['error'])) {
            Log::channel('whatsapp')->error('AutomationEngine: envio de list falhou', [
                'automation_id' => $automation->id,
                'error'         => $result['error'] ?? 'unknown',
            ]);
            return;
        }

        $lead = $this->resolveLead($ctx);
        if (! $conv instanceof WhatsappConversation) {
            $conv = $this->findOrCreateConversation($automation, $instance, $phone, $lead);
        }

        app(\App\Services\Whatsapp\OutboundMessagePersister::class)->persist(
            conv: $conv,
            type: 'interactive',
            body: $description,
            sendResult: $result,
            sentBy: 'automation',
        );
    }

    /**
     * Helper: resolve (conversa, instância, phone) a partir do context + config.
     * Usado pelas 3 actions Cloud-only pra não duplicar lógica de resolução.
     * Retorna [null, null, null] se faltar alguma coisa crítica.
     *
     * @return array{0: ?WhatsappConversation, 1: ?WhatsappInstance, 2: ?string}
     */
    private function resolveWhatsappTarget(array $config, array $ctx, Automation $automation): array
    {
        $conv = $ctx['conversation'] ?? null;
        $lead = $this->resolveLead($ctx);

        if (! ($conv instanceof WhatsappConversation) && $lead) {
            $conv = WhatsappConversation::withoutGlobalScope('tenant')
                ->where('tenant_id', $automation->tenant_id)
                ->where('lead_id', $lead->id)
                ->latest('last_message_at')
                ->first();
        }

        $phone = $conv instanceof WhatsappConversation
            ? $conv->phone
            : \App\Support\PhoneNormalizer::toE164($lead?->phone);

        if (! $phone) {
            return [null, null, null];
        }

        $instance = app(\App\Services\Whatsapp\InstanceSelector::class)->selectFor(
            $automation->tenant_id,
            [
                'instance_id'  => $config['instance_id'] ?? null,
                'conversation' => $conv instanceof WhatsappConversation ? $conv : null,
            ],
        );

        if (! $instance) {
            return [null, null, null];
        }

        return [$conv instanceof WhatsappConversation ? $conv : null, $instance, $phone];
    }

    /**
     * Cria conversa mínima se ainda não existe — usado quando automação dispara
     * antes do lead ter qualquer msg no whatsapp.
     */
    private function findOrCreateConversation(
        Automation $automation,
        WhatsappInstance $instance,
        string $phone,
        ?\App\Models\Lead $lead,
    ): WhatsappConversation {
        $existing = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $automation->tenant_id)
            ->where('instance_id', $instance->id)
            ->where('phone', $phone)
            ->first();

        if ($existing) {
            return $existing;
        }

        return WhatsappConversation::withoutGlobalScope('tenant')->create([
            'tenant_id'       => $automation->tenant_id,
            'instance_id'     => $instance->id,
            'phone'           => $phone,
            'lead_id'         => $lead?->id,
            'is_group'        => false,
            'contact_name'    => $lead?->name ?? $phone,
            'status'          => 'open',
            'started_at'      => now(),
            'last_message_at' => now(),
            'unread_count'    => 0,
        ]);
    }

    /**
     * Monta o array de variáveis pro template baseado em mapping custom OU default.
     *
     *   mapping ['1' => 'lead.name', '2' => 'custom_field.data_consulta']
     *   context tem $lead e $conv; custom fields vêm do lead relation.
     *
     * Default (quando mapping vazio): 1=name, 2=company, 3=email.
     */
    private function buildTemplateVariables(
        \App\Models\WhatsappTemplate $template,
        ?\App\Models\Lead $lead,
        array $ctx,
        array $mapping,
    ): array {
        $vars = [];
        $tplVariables = $template->variables; // indices detectados no body do template

        foreach ($tplVariables as $idx) {
            $key = (string) $idx;
            $path = $mapping[$key] ?? null;

            if ($path) {
                $vars[$key] = (string) ($this->resolveContextPath($path, $lead, $ctx) ?? '');
            } else {
                // Default posicional
                $vars[$key] = match ($idx) {
                    '1', 1 => (string) ($lead?->name    ?? ''),
                    '2', 2 => (string) ($lead?->company ?? ''),
                    '3', 3 => (string) ($lead?->email   ?? ''),
                    default => '',
                };
            }
        }

        return $vars;
    }

    private function resolveContextPath(string $path, ?\App\Models\Lead $lead, array $ctx): ?string
    {
        // Suporta: lead.{field}, lead.custom_field.{slug}, conversation.{field}
        if (str_starts_with($path, 'lead.') && $lead) {
            $field = substr($path, 5);
            if (str_starts_with($field, 'custom_field.')) {
                $slug = substr($field, 13);
                return $lead->customFields->firstWhere('slug', $slug)?->value;
            }
            return (string) ($lead->{$field} ?? '');
        }

        if (str_starts_with($path, 'conversation.') && isset($ctx['conversation'])) {
            $field = substr($path, 13);
            return (string) ($ctx['conversation']->{$field} ?? '');
        }

        return null;
    }

    private function buildTemplatePreview(\App\Models\WhatsappTemplate $template, array $variables): string
    {
        $body = '';
        foreach ((array) $template->components as $c) {
            if (strtoupper((string) ($c['type'] ?? '')) === 'BODY') {
                $body = (string) ($c['text'] ?? '');
                break;
            }
        }
        return (string) preg_replace_callback('/\{\{\s*(\d+)\s*\}\}/', function ($m) use ($variables) {
            return (string) ($variables[$m[1]] ?? $m[0]);
        }, $body);
    }

    private function actionScheduleWhatsappMessage(array $config, array $ctx): void
    {
        $lead = $this->resolveLead($ctx);
        if (! $lead || empty($config['message'])) {
            return;
        }

        // Encontrar a conversa WhatsApp do lead (opcional — pode nao existir)
        $conv = $ctx['conversation'] instanceof WhatsappConversation
            ? $ctx['conversation']
            : WhatsappConversation::withoutGlobalScope('tenant')
                ->where('tenant_id', $lead->tenant_id)
                ->where('lead_id', $lead->id)
                ->latest('last_message_at')
                ->first();

        // Resolver instancia: config explicita > conversa > primary do tenant
        $instanceId = null;
        if (! empty($config['instance_id'])) {
            $exists = WhatsappInstance::withoutGlobalScope('tenant')
                ->where('id', (int) $config['instance_id'])
                ->where('tenant_id', $lead->tenant_id)
                ->exists();
            if ($exists) {
                $instanceId = (int) $config['instance_id'];
            }
        }
        if (! $instanceId && $conv?->instance_id) {
            $instanceId = $conv->instance_id;
        }
        if (! $instanceId) {
            $instanceId = WhatsappInstance::resolvePrimary($lead->tenant_id)?->id;
        }
        if (! $instanceId) {
            return; // Sem nenhuma instancia conectada — nao tem o que agendar
        }

        $delayValue = max(1, (int) ($config['delay_value'] ?? 1));
        $delayUnit  = ($config['delay_unit'] ?? 'days') === 'hours' ? 'hours' : 'days';
        $sendAt     = now()->add($delayValue, $delayUnit);
        $body       = $this->interpolate((string) $config['message'], $ctx);

        ScheduledMessage::withoutGlobalScope('tenant')->create([
            'tenant_id'       => $lead->tenant_id,
            'lead_id'         => $lead->id,
            'conversation_id' => $conv?->id,
            'instance_id'     => $instanceId,
            'created_by'      => null,
            'type'            => 'text',
            'body'            => $body,
            'send_at'         => $sendAt,
            'status'          => 'pending',
        ]);
    }

    private function actionSetUtmParams(array $config, array $ctx): void
    {
        $lead = $this->resolveLead($ctx);
        if (! $lead) {
            return;
        }
        $fields = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $col) {
            if (isset($config[$col]) && $config[$col] !== '') {
                $fields[$col] = $config[$col];
            }
        }
        if (empty($fields)) {
            return;
        }
        Lead::withoutGlobalScope('tenant')->where('id', $lead->id)->update($fields);
    }

    private function actionTransferToDepartment(array $config, array $ctx): void
    {
        $conv = $ctx['conversation'] ?? null;
        if (! $conv || empty($config['department_id'])) {
            return;
        }
        $department = Department::withoutGlobalScope('tenant')
            ->where('id', (int) $config['department_id'])
            ->where('tenant_id', $ctx['tenant_id'] ?? 0)
            ->first();
        if ($department) {
            $department->assignConversation($conv);
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

    private function actionCreateTask(array $config, array $ctx): void
    {
        $lead = $this->resolveLead($ctx);
        if (! $lead || empty($config['subject'])) {
            return;
        }

        $subject     = $this->interpolate($config['subject'], $ctx);
        $description = ! empty($config['description']) ? $this->interpolate($config['description'], $ctx) : null;
        $dueDate     = now()->addDays((int) ($config['due_date_offset'] ?? 1))->format('Y-m-d');
        $assignedTo  = ! empty($config['assigned_to']) ? (int) $config['assigned_to'] : $lead->assigned_to;

        $conv = $ctx['conversation'] ?? null;

        Task::create([
            'tenant_id'                 => $lead->tenant_id,
            'subject'                   => $subject,
            'description'               => $description,
            'type'                      => $config['task_type'] ?? 'task',
            'priority'                  => $config['priority'] ?? 'medium',
            'due_date'                  => $dueDate,
            'due_time'                  => $config['due_time'] ?? null,
            'lead_id'                   => $lead->id,
            'whatsapp_conversation_id'  => $conv instanceof WhatsappConversation ? $conv->id : null,
            'instagram_conversation_id' => $conv instanceof InstagramConversation ? $conv->id : null,
            'assigned_to'               => $assignedTo,
        ]);
    }

    private function actionEnrollSequence(array $config, array $ctx): void
    {
        $lead = $this->resolveLead($ctx);
        if (! $lead || empty($config['sequence_id'])) {
            return;
        }

        $sequence = \App\Models\NurtureSequence::withoutGlobalScope('tenant')
            ->where('id', $config['sequence_id'])
            ->where('tenant_id', $lead->tenant_id)
            ->where('is_active', true)
            ->first();

        if ($sequence) {
            (new NurtureSequenceService())->enroll($lead, $sequence);
        }
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
            '{{contact_name}}'    => $conv?->contact_name ?? $lead?->name ?? '',
            '{{phone}}'           => $lead?->phone ?? ($conv?->phone ?? ''),
            '{{lead_name}}'       => $lead?->name ?? '',
            '{{pipeline}}'        => $lead?->pipeline?->name ?? '',
            '{{stage}}'           => $stage?->name ?? $lead?->stage?->name ?? '',
            '{{birthday}}'        => isset($ctx['birthday_formatted']) ? $ctx['birthday_formatted'] : ($lead?->birthday?->format('d/m/Y') ?? ''),
            '{{days_until}}'      => isset($ctx['days_until']) ? (string) $ctx['days_until'] : '',
            '{{custom_field_label}}' => $ctx['custom_field_label'] ?? '',
        ];

        return str_replace(array_keys($vars), array_values($vars), $text);
    }
}
