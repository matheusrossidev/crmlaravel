<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\AiIntentDetected;
use App\Events\WhatsappConversationUpdated;
use App\Http\Controllers\Tenant\AiConfigurationController;
use App\Models\AiAgent;
use App\Models\AiIntentSignal;
use App\Models\AiUsageLog;
use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\OAuthConnection;
use App\Models\TenantTokenIncrement;
use App\Models\PlanDefinition;
use App\Models\Tenant;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\AgnoService;
use App\Services\AiAgentService;
use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessAiResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;   // Sem retry — se falhou, a janela de debounce já passou

    public int $timeout = 120;

    public function __construct(
        public readonly int $conversationId,
        public readonly int $version = 0,
    ) {
        $this->queue = 'ai'; // via Queueable trait — evita conflito de tipo com ?string
    }

    public function handle(): void
    {
        // ── 1. Verificar versão (debounce) ────────────────────────────────────
        $currentVersion = (int) Cache::get("ai:version:{$this->conversationId}", 0);
        if ($currentVersion !== $this->version) {
            Log::channel('whatsapp')->debug('AI job stale — nova mensagem chegou, pulando', [
                'conversation_id' => $this->conversationId,
                'job_version'     => $this->version,
                'current_version' => $currentVersion,
            ]);
            return;
        }

        // ── 2. Lock para evitar execução concorrente ──────────────────────────
        $lockAcquired = Cache::add("ai:lock:{$this->conversationId}", 1, 120);
        if (! $lockAcquired) {
            Log::channel('whatsapp')->debug('AI job: já em processamento, pulando', [
                'conversation_id' => $this->conversationId,
            ]);
            return;
        }

        try {
            $this->process();
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('AI job: erro ao processar resposta', [
                'conversation_id' => $this->conversationId,
                'error'           => $e->getMessage(),
                'file'            => $e->getFile() . ':' . $e->getLine(),
            ]);
            throw $e;  // re-throw para o queue worker marcar como failed
        } finally {
            Cache::forget("ai:lock:{$this->conversationId}");
        }
    }

    public function process(): void
    {
        Log::channel('whatsapp')->info('AI job: iniciando', [
            'conversation_id' => $this->conversationId,
            'version'         => $this->version,
        ]);

        // ── Carregar conversa e agente ────────────────────────────────────────
        $conv = WhatsappConversation::withoutGlobalScope('tenant')
            ->with('aiAgent')
            ->find($this->conversationId);

        if (! $conv) {
            Log::channel('whatsapp')->warning('AI job: conversa não encontrada', ['id' => $this->conversationId]);
            return;
        }

        $agent = $conv->aiAgent;
        if (! $agent || ! $agent->is_active) {
            Log::channel('whatsapp')->info('AI job: agente inativo ou removido', [
                'conversation_id' => $this->conversationId,
                'ai_agent_id'     => $conv->ai_agent_id,
            ]);
            return;
        }

        // ── Verificar se serviço está bloqueado (trial expirado, suspenso, etc.) ──
        $tenant = Tenant::find($conv->tenant_id);
        if ($tenant && $tenant->isServiceBlocked()) {
            Log::channel('whatsapp')->warning('AI job: serviço bloqueado para o tenant', [
                'conversation_id' => $this->conversationId,
                'tenant_id'       => $conv->tenant_id,
            ]);
            return;
        }

        // ── Verificar cota de tokens do plano ────────────────────────────────
        if (! $this->checkTokenQuota($conv)) {
            Log::channel('whatsapp')->warning('AI job: cota de tokens do plano excedida', [
                'conversation_id' => $this->conversationId,
                'tenant_id'       => $conv->tenant_id,
            ]);
            return;
        }

        // ── Debounce: aguardar para agregar mensagens enviadas em sequência ────
        // Se o usuário manda 3 mensagens em 5 s, apenas a última passa pelo debounce.
        $waitSecs = max(0, (int) ($agent->response_wait_seconds ?? 0));
        if ($waitSecs > 0) {
            Log::channel('whatsapp')->debug('AI job: aguardando batching', [
                'conversation_id' => $this->conversationId,
                'wait_seconds'    => $waitSecs,
            ]);
            sleep($waitSecs);

            // Re-verificar versão após o sleep — se outra mensagem chegou e
            // incrementou o contador, esta execução é obsoleta.
            $latestVersion = (int) Cache::get("ai:version:{$this->conversationId}", 0);
            if ($latestVersion !== $this->version) {
                Log::channel('whatsapp')->debug('AI job: descartado após batching (nova mensagem chegou)', [
                    'conversation_id' => $this->conversationId,
                    'job_version'     => $this->version,
                    'latest_version'  => $latestVersion,
                ]);
                return;
            }
        }

        // ── Configuração do LLM via ENV ───────────────────────────────────────
        $provider = (string) config('ai.provider', 'openai');
        $apiKey   = (string) config('ai.api_key', '');
        $model    = (string) config('ai.model', 'gpt-4o-mini');

        if ($apiKey === '') {
            Log::channel('whatsapp')->warning('AI job: LLM_API_KEY não configurado no .env', [
                'conversation_id' => $this->conversationId,
            ]);
            return;
        }

        Log::channel('whatsapp')->info('AI job: processando resposta', [
            'conversation_id' => $this->conversationId,
            'agent'           => $agent->name,
            'provider'        => $provider,
            'model'           => $model,
        ]);

        // ── Carregar lead — para pipeline, calendar tool e update_lead ──────
        $lead   = null;
        $stages = [];
        if ($conv->lead_id) {
            $lead = Lead::withoutGlobalScope('tenant')
                ->with('stage.pipeline.stages')
                ->find($conv->lead_id);
        }
        if ($agent->enable_pipeline_tool && $lead?->stage && $lead?->stage?->pipeline) {
            $stages = $lead->stage->pipeline->stages
                ->map(fn ($s) => [
                    'id'      => $s->id,
                    'name'    => $s->name,
                    'current' => $s->id === $lead->stage_id,
                    'is_won'  => (bool) $s->is_won,
                    'is_lost' => (bool) $s->is_lost,
                ])
                ->values()
                ->toArray();
        }

        // ── Carregar tags existentes — apenas se ferramenta habilitada ─────────
        $availTags = [];
        if ($agent->enable_tags_tool) {
            $availTags = WhatsappConversation::withoutGlobalScope('tenant')
                ->where('tenant_id', $conv->tenant_id)
                ->whereNotNull('tags')
                ->pluck('tags')
                ->flatten()
                ->merge(
                    Lead::withoutGlobalScope('tenant')
                        ->where('tenant_id', $conv->tenant_id)
                        ->whereNotNull('tags')
                        ->pluck('tags')
                        ->flatten()
                )
                ->unique()
                ->filter()
                ->values()
                ->toArray();
        }

        // ── Carregar eventos de agenda — apenas se ferramenta habilitada ──────
        $calendarEvents = [];
        $calendarService = null;
        if ($agent->enable_calendar_tool) {
            $calendarConn = OAuthConnection::withoutGlobalScope('tenant')
                ->where('tenant_id', $conv->tenant_id)
                ->where('platform', 'google')
                ->where('status', 'active')
                ->first();
            if ($calendarConn) {
                $scopes = (array) ($calendarConn->scopes_json ?? []);
                if (in_array('https://www.googleapis.com/auth/calendar', $scopes, true)) {
                    try {
                        $calendarService = new GoogleCalendarService($calendarConn, $agent->calendar_id ?? 'primary');
                        $calendarEvents  = $calendarService->listEvents(
                            now()->toIso8601String(),
                            now()->addDays(7)->toIso8601String(),
                        );
                    } catch (\Throwable $e) {
                        Log::channel('whatsapp')->warning('AI job: falha ao carregar eventos de agenda', [
                            'conversation_id' => $this->conversationId,
                            'error'           => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // ── Roteamento: Agno ou LLM direto ───────────────────────────────────
        $service     = new AiAgentService();
        $maxLength   = max(200, $agent->max_message_length ?? 500);
        $reply       = '';
        $actions     = [];
        $replyBlocks = [];

        if ($agent->use_agno) {
            // ── Caminho Agno ──────────────────────────────────────────────────
            try {
                $agnoResult = $this->callAgnoService($conv, $agent, $stages, $availTags);
                $replyBlocks = array_values(array_filter(array_map('trim', $agnoResult['reply_blocks'] ?? [])));
                $reply   = implode("\n\n", $replyBlocks);
                $actions = $agnoResult['actions'] ?? [];

                // Detectar respostas que são erros do LLM (não enviar ao cliente)
                if ($reply !== '' && (
                    str_contains($reply, 'API_KEY') ||
                    str_contains($reply, 'api_key') ||
                    str_contains($reply, 'environment variable') ||
                    str_contains($reply, 'Error code:') ||
                    str_contains($reply, 'Rate limit')
                )) {
                    Log::channel('whatsapp')->error('AI job (Agno): resposta contém erro do LLM, descartando', [
                        'conversation_id' => $this->conversationId,
                        'reply_preview'   => mb_substr($reply, 0, 200),
                    ]);
                    $reply = '';
                }

                try {
                    AiUsageLog::create([
                        'tenant_id'         => $conv->tenant_id,
                        'conversation_id'   => $conv->id,
                        'model'             => $agnoResult['model']              ?? 'agno',
                        'provider'          => $agnoResult['provider']           ?? 'agno',
                        'tokens_prompt'     => $agnoResult['tokens_prompt']      ?? 0,
                        'tokens_completion' => $agnoResult['tokens_completion']  ?? 0,
                        'tokens_total'      => $agnoResult['tokens_total']       ?? 0,
                        'type'              => 'chat',
                    ]);
                } catch (\Throwable $e) {
                    Log::channel('whatsapp')->warning('AI job (Agno): falha ao registrar tokens', [
                        'conversation_id' => $this->conversationId,
                        'error'           => $e->getMessage(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->error('AI job: Agno falhou, caindo para LLM direto', [
                    'conversation_id' => $this->conversationId,
                    'error'           => $e->getMessage(),
                ]);
                // Fallback: força o caminho direto abaixo
                $reply = '';
            }
        }

        if (! $agent->use_agno || $reply === '') {
            // ── Caminho LLM direto (original) ────────────────────────────────
            $enableIntentNotify = (bool) ($agent->enable_intent_notify ?? false);
            $system             = $service->buildSystemPrompt($agent, $stages, $availTags, $enableIntentNotify, $calendarEvents, $lead);
            $history = $service->buildHistory($conv, limit: 50);

            if (empty($history)) {
                Log::channel('whatsapp')->warning('AI job: histórico vazio, abortando', [
                    'conversation_id' => $this->conversationId,
                ]);
                return;
            }

            $extraTokens = (! empty($stages) || ! empty($availTags)) ? 300 : 0;
            $maxTokens   = $maxLength + 200 + $extraTokens;

            $needsJson = ! empty($stages) || ! empty($availTags) || $enableIntentNotify || $agent->enable_calendar_tool;

            $llmResult = AiConfigurationController::callLlm(
                provider:  $provider,
                apiKey:    $apiKey,
                model:     $model,
                messages:  $history,
                maxTokens: $maxTokens,
                system:    $system,
                forceJson: $needsJson,
            );

            $reply    = trim($llmResult['reply']);
            $llmUsage = $llmResult['usage'];

            try {
                AiUsageLog::create([
                    'tenant_id'         => $conv->tenant_id,
                    'conversation_id'   => $conv->id,
                    'model'             => $model,
                    'provider'          => $provider,
                    'tokens_prompt'     => $llmUsage['prompt'] ?? 0,
                    'tokens_completion' => $llmUsage['completion'] ?? 0,
                    'tokens_total'      => $llmUsage['total'] ?? 0,
                    'type'              => 'chat',
                ]);
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->warning('AI job: falha ao registrar uso de tokens', [
                    'conversation_id' => $this->conversationId,
                    'error'           => $e->getMessage(),
                ]);
            }

            if ($reply === '') {
                Log::channel('whatsapp')->warning('AI job: LLM retornou resposta vazia', [
                    'conversation_id' => $this->conversationId,
                ]);
                return;
            }

            // Parsear JSON de ações (quando pipeline/tags/intent/calendar disponíveis)
            if (! empty($stages) || ! empty($availTags) || $enableIntentNotify || $agent->enable_calendar_tool) {
                $clean = preg_replace('/```(?:json)?\s*([\s\S]*?)```/i', '$1', $reply);
                $clean = trim($clean ?? $reply);

                if (! str_starts_with($clean, '{')) {
                    $jsonStart = strpos($clean, '{');
                    if ($jsonStart !== false) {
                        $clean = substr($clean, $jsonStart);
                    }
                }

                $decoded = null;
                if (str_starts_with($clean, '{')) {
                    $decoded = json_decode($clean, true);
                }

                if (is_array($decoded) && isset($decoded['reply'])) {
                    $replyRaw = $decoded['reply'] ?? '';
                    if (is_array($replyRaw)) {
                        $replyParts = array_values(array_filter(array_map('trim', $replyRaw)));
                        $reply = implode("\n\n", $replyParts);
                    } else {
                        $reply = trim((string) $replyRaw);
                    }
                    $actions = (array) ($decoded['actions'] ?? []);
                } else {
                    Log::channel('whatsapp')->warning('AI job: resposta não era JSON válido, usando texto bruto', [
                        'conversation_id' => $this->conversationId,
                        'raw'             => mb_substr($reply, 0, 300),
                    ]);
                }
            }

            if ($reply === '') {
                Log::channel('whatsapp')->warning('AI job: reply vazio após parse JSON', [
                    'conversation_id' => $this->conversationId,
                ]);
                return;
            }

            // ── Agentic loop: tool calling para calendário ──────────────────────
            if ($agent->enable_calendar_tool && $calendarService !== null) {
                $calToolTypes = ['check_calendar_availability', 'calendar_create', 'calendar_reschedule', 'calendar_cancel'];
                $loopHistory  = $history;

                for ($loopIter = 0; $loopIter < 2; $loopIter++) {
                    $calActions = array_values(array_filter($actions, fn ($a) => in_array($a['type'] ?? '', $calToolTypes)));
                    if (empty($calActions)) break;

                    // Executa cada ferramenta e coleta resultados para o LLM
                    $toolResults = [];
                    foreach ($calActions as $calAction) {
                        $toolResults[] = $this->runCalendarTool($calAction, $calendarService, $conv, $lead ?? null);
                    }

                    // Remove ações de calendário desta iteração
                    $actions = array_values(array_filter($actions, fn ($a) => ! in_array($a['type'] ?? '', $calToolTypes)));

                    // Injeta turno do assistente + resultado das ferramentas no histórico
                    $loopHistory[] = ['role' => 'assistant', 'content' => $reply];
                    $loopHistory[] = ['role' => 'user',      'content' => '[RESULTADO DAS FERRAMENTAS]: ' . implode(' | ', $toolResults)];

                    Log::channel('whatsapp')->info('AI calendar loop: ferramentas executadas', [
                        'conversation_id' => $this->conversationId,
                        'iter'            => $loopIter,
                        'results'         => $toolResults,
                    ]);

                    // Nova chamada ao LLM com o histórico atualizado
                    $loopLlmResult = AiConfigurationController::callLlm(
                        provider:  $provider,
                        apiKey:    $apiKey,
                        model:     $model,
                        messages:  $loopHistory,
                        maxTokens: $maxTokens,
                        system:    $system,
                        forceJson: true,
                    );

                    $loopRaw = trim($loopLlmResult['reply']);
                    if ($loopRaw === '') break;

                    try {
                        AiUsageLog::create([
                            'tenant_id'         => $conv->tenant_id,
                            'conversation_id'   => $conv->id,
                            'model'             => $model,
                            'provider'          => $provider,
                            'tokens_prompt'     => $loopLlmResult['usage']['prompt'] ?? 0,
                            'tokens_completion' => $loopLlmResult['usage']['completion'] ?? 0,
                            'tokens_total'      => $loopLlmResult['usage']['total'] ?? 0,
                            'type'              => 'chat',
                        ]);
                    } catch (\Throwable) {}

                    // Re-parse JSON da nova resposta
                    $loopClean = preg_replace('/```(?:json)?\s*([\s\S]*?)```/i', '$1', $loopRaw);
                    $loopClean = trim($loopClean ?? $loopRaw);
                    if (! str_starts_with($loopClean, '{')) {
                        $jsonStart = strpos($loopClean, '{');
                        if ($jsonStart !== false) $loopClean = substr($loopClean, $jsonStart);
                    }
                    $loopDecoded = null;
                    if (str_starts_with($loopClean, '{')) {
                        $loopDecoded = json_decode($loopClean, true);
                    }
                    if (is_array($loopDecoded) && isset($loopDecoded['reply'])) {
                        $loopReplyRaw = $loopDecoded['reply'] ?? '';
                        if (is_array($loopReplyRaw)) {
                            $reply = implode("\n\n", array_values(array_filter(array_map('trim', $loopReplyRaw))));
                        } else {
                            $reply = trim((string) $loopReplyRaw);
                        }
                        // Mescla novas actions (ex: set_stage emitido junto com a resposta final)
                        $actions = array_merge($actions, (array) ($loopDecoded['actions'] ?? []));
                    } else {
                        $reply = $loopRaw;
                    }

                    if ($reply === '') break;
                }

                // Garante que ações de calendário não escapem para o executor legado
                $actions = array_values(array_filter($actions, fn ($a) => ! in_array($a['type'] ?? '', $calToolTypes)));
            }
        }

        // ── Aplicar ações de pipeline e tags ─────────────────────────────────
        $extraMessages = [];
        foreach ($actions as $action) {
            $type = $action['type'] ?? '';

            if ($type === 'set_stage') {
                // Só aplicar se a ferramenta está ativa e o stage_id é válido para este funil
                $stageId  = (int) ($action['stage_id'] ?? 0);
                $validIds = array_column($stages, 'id');
                if (! empty($stages) && $stageId > 0 && in_array($stageId, $validIds, true)) {
                    $this->applySetStage($conv, $stageId, $agent, $stages);
                } else {
                    Log::channel('whatsapp')->warning('AI: set_stage ignorado — id inválido ou ferramenta desativada', [
                        'conversation_id' => $conv->id,
                        'stage_id'        => $stageId,
                        'valid_ids'       => $validIds,
                    ]);
                }
            } elseif ($type === 'add_tags') {
                $this->applyAddTags($conv, (array) ($action['tags'] ?? []), $agent);
            } elseif ($type === 'notify_intent') {
                $this->applyNotifyIntent($conv, $action, $agent);
            } elseif ($type === 'assign_human') {
                $this->applyAssignHuman($conv, $agent);
            } elseif ($type === 'send_media') {
                $mediaId = (int) ($action['media_id'] ?? 0);
                if ($mediaId > 0) {
                    try {
                        $service->sendMediaReply($conv, $agent, $mediaId);
                    } catch (\Throwable $e) {
                        Log::channel('whatsapp')->error('AI send_media falhou', [
                            'conversation_id' => $conv->id,
                            'media_id'        => $mediaId,
                            'error'           => $e->getMessage(),
                        ]);
                    }
                }
            } elseif ($type === 'update_lead') {
                $this->applyUpdateLead($conv, $action, $lead);
            } elseif (in_array($type, ['calendar_create', 'calendar_reschedule', 'calendar_cancel', 'calendar_list'], true)) {
                if ($calendarService !== null) {
                    $extra = $this->applyCalendarAction($conv, $action, $calendarService, $lead ?? null);
                    if ($extra !== null) {
                        $extraMessages[] = $extra;
                    }
                }
            }
        }

        // ── Detectar se última mensagem inbound foi áudio ─────────────────────
        $lastInboundType = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('conversation_id', $conv->id)
            ->where('direction', 'inbound')
            ->latest('sent_at')
            ->value('type');

        $shouldReplyWithVoice = $agent->enable_voice_reply && $lastInboundType === 'audio';

        if ($shouldReplyWithVoice) {
            // ── Cliente mandou áudio → responder SOMENTE com áudio ───────────
            $this->maybeReplyWithVoice($conv, $agent, $reply);
        } else {
            // ── Cliente mandou texto → responder SOMENTE com texto ───────────
            $delay = max(1, $agent->response_delay_seconds ?? 1);

            $messages = $service->splitIntoMessages($reply, $maxLength);

            foreach ($extraMessages as $extra) {
                $messages[] = $extra;
            }

            Log::channel('whatsapp')->info('AI job: enviando resposta', [
                'conversation_id' => $this->conversationId,
                'parts'           => count($messages),
                'delay_seconds'   => $delay,
            ]);

            $service->sendWhatsappReplies($conv, $messages, $delay);
        }
    }

    // ── Agno: verificação de cota ─────────────────────────────────────────────

    private function checkTokenQuota(WhatsappConversation $conv): bool
    {
        $tenant = Tenant::withoutGlobalScope('tenant')->find($conv->tenant_id);
        if (! $tenant) return true;

        if ($tenant->isExemptFromBilling()) return true;

        $plan = PlanDefinition::where('name', $tenant->plan)->first();
        $base = (int) ($plan?->features_json['ai_tokens_monthly'] ?? 0);

        if ($base === 0) return false;   // plano free — sem AI

        // Incrementos pagos no mês corrente somam ao limite base
        $extra = (int) TenantTokenIncrement::where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('tokens_added');

        $limit = $base + $extra;

        $used = (int) AiUsageLog::where('tenant_id', $tenant->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('tokens_total');

        if ($used >= $limit) {
            // Setar flag de esgotamento para bloquear o frontend
            if (! $tenant->ai_tokens_exhausted) {
                Tenant::withoutGlobalScope('tenant')
                    ->where('id', $tenant->id)
                    ->update(['ai_tokens_exhausted' => true]);
            }
            return false;
        }

        return true;
    }

    // ── TTS: gerar e enviar áudio via ElevenLabs ──────────────────────────

    private function maybeReplyWithVoice(WhatsappConversation $conv, AiAgent $agent, string $reply): void
    {
        try {
            $lastInbound = WhatsappMessage::withoutGlobalScope('tenant')
                ->where('conversation_id', $conv->id)
                ->where('direction', 'inbound')
                ->latest('sent_at')
                ->first();

            if (! $lastInbound || $lastInbound->type !== 'audio') {
                return;
            }

            if (! $this->checkElevenLabsQuota($conv)) {
                Log::channel('whatsapp')->info('TTS: quota de caracteres esgotada', [
                    'conversation_id' => $conv->id,
                ]);
                return;
            }

            $tts = app(\App\Services\ElevenLabsService::class);
            if (! $tts->isAvailable()) {
                return;
            }

            // Limitar texto para TTS: máximo 500 caracteres para economizar créditos
            $ttsText = $reply;
            $maxTtsChars = 500;
            if (mb_strlen($ttsText) > $maxTtsChars) {
                // Truncar no último ponto/exclamação/interrogação antes do limite
                $truncated = mb_substr($ttsText, 0, $maxTtsChars);
                $lastSentence = max(
                    (int) mb_strrpos($truncated, '.'),
                    (int) mb_strrpos($truncated, '!'),
                    (int) mb_strrpos($truncated, '?'),
                );
                if ($lastSentence > $maxTtsChars * 0.3) {
                    $ttsText = mb_substr($truncated, 0, $lastSentence + 1);
                } else {
                    $ttsText = $truncated;
                }
            }

            $audioPath = $tts->textToSpeech($ttsText, $agent->elevenlabs_voice_id);
            if (! $audioPath) {
                return;
            }

            $instance = WhatsappInstance::withoutGlobalScope('tenant')->find($conv->instance_id);
            if (! $instance) {
                @unlink($audioPath);
                return;
            }

            $waha = new \App\Services\WahaService($instance->session_name);

            // Resolve chatId — mesma lógica do sendWhatsappReplies (suporta @lid e @c.us)
            $chatId = null;
            $lastMsg = WhatsappMessage::withoutGlobalScope('tenant')
                ->where('conversation_id', $conv->id)
                ->where('direction', 'inbound')
                ->whereNotNull('waha_message_id')
                ->latest('sent_at')
                ->first();

            if ($lastMsg?->waha_message_id && preg_match('/^(?:true|false)_(.+@[\w.]+)_/', $lastMsg->waha_message_id, $m)) {
                $jid = $m[1];
                $chatId = str_ends_with($jid, '@lid')
                    ? preg_replace('/[:@].+$/', '', $jid) . '@lid'
                    : preg_replace('/[:@].+$/', '', $jid) . '@c.us';
            }

            if (! $chatId) {
                $chatId = $conv->phone . '@c.us';
            }

            Log::channel('whatsapp')->info('TTS: enviando áudio ao WAHA', [
                'conversation_id' => $conv->id,
                'chat_id'         => $chatId,
                'file_size_kb'    => round(filesize($audioPath) / 1024, 1),
                'characters'      => mb_strlen($ttsText),
            ]);

            $waha->sendVoiceBase64($chatId, $audioPath, 'audio/mpeg');

            // Log usage
            \App\Models\ElevenlabsUsageLog::create([
                'tenant_id'       => $conv->tenant_id,
                'agent_id'        => $agent->id,
                'conversation_id' => $conv->id,
                'characters_used' => mb_strlen($ttsText),
                'created_at'      => now(),
            ]);

            Log::channel('whatsapp')->info('TTS: áudio enviado com sucesso', [
                'conversation_id' => $conv->id,
                'characters'      => mb_strlen($ttsText),
            ]);

            @unlink($audioPath);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('TTS: falha ao gerar/enviar áudio', [
                'conversation_id' => $conv->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    private function checkElevenLabsQuota(WhatsappConversation $conv): bool
    {
        $tenant = Tenant::withoutGlobalScope('tenant')->find($conv->tenant_id);
        if (! $tenant) {
            return true;
        }
        if ($tenant->isExemptFromBilling()) {
            return true;
        }

        $plan = PlanDefinition::where('name', $tenant->plan)->first();
        $base = (int) ($plan?->features_json['elevenlabs_characters_monthly'] ?? 0);

        if ($base === 0) {
            return false;
        }

        $used = (int) \App\Models\ElevenlabsUsageLog::where('tenant_id', $tenant->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('characters_used');

        if ($used >= $base) {
            if (! $tenant->tts_characters_exhausted) {
                Tenant::withoutGlobalScope('tenant')
                    ->where('id', $tenant->id)
                    ->update(['tts_characters_exhausted' => true]);
            }
            return false;
        }

        return true;
    }

    // ── Agno: chamada ao microsserviço Python ─────────────────────────────────

    private function callAgnoService(
        WhatsappConversation $conv,
        AiAgent $agent,
        array $stages,
        array $availTags,
    ): array {
        $lastMessage = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('conversation_id', $conv->id)
            ->where('direction', 'inbound')
            ->latest()
            ->first();

        if (! $lastMessage) {
            return [
                'reply_blocks'     => [],
                'actions'          => [],
                'tokens_prompt'    => 0,
                'tokens_completion' => 0,
                'tokens_total'     => 0,
                'model'            => '',
                'provider'         => '',
            ];
        }

        // Search for relevant memories from past conversations
        $memories = [];
        try {
            $agnoService = app(AgnoService::class);
            $memoryResults = $agnoService->searchMemories($agent->id, [
                'tenant_id'     => $agent->tenant_id,
                'query'         => $lastMessage->content ?? '',
                'top_k'         => 3,
                'contact_phone' => $conv->phone ?? null,
            ]);
            foreach ($memoryResults as $mem) {
                $parts = [$mem['summary'] ?? ''];
                if (! empty($mem['customer_profile'])) {
                    $parts[] = "Perfil: {$mem['customer_profile']}";
                }
                if (! empty($mem['key_learnings'])) {
                    $parts[] = "Aprendizado: {$mem['key_learnings']}";
                }
                $memories[] = implode(' | ', $parts);
            }
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->debug('AI job: memory search failed (non-blocking)', [
                'conversation_id' => $this->conversationId,
                'error'           => $e->getMessage(),
            ]);
        }

        // Build conversation history for Agno context
        $aiService = new AiAgentService();
        $rawHistory = $aiService->buildHistory($conv, limit: 20);
        $history = array_map(fn ($m) => [
            'role'    => $m['role'],
            'content' => $m['content'],
        ], $rawHistory);

        $agnoResult = app(AgnoService::class)->chat([
            'agent_id'        => $agent->id,
            'tenant_id'       => $agent->tenant_id,
            'conversation_id' => $conv->id,
            'contact_phone'   => $conv->phone,
            'message'         => $lastMessage->content ?? '',
            'history'         => $history,
            'history_limit'   => 20,
            'pipeline_stages' => $stages,
            'available_tags'  => $availTags,
            'memories'        => $memories,
        ]);

        // Flatten nested payload in actions to match the existing PHP action executor format.
        // Agno returns: {"type": "set_stage", "payload": {"stage_id": 3}}
        // PHP executor expects: {"type": "set_stage", "stage_id": 3}
        $agnoResult['actions'] = array_map(
            fn (array $a) => array_merge((array) ($a['payload'] ?? []), ['type' => $a['type'] ?? '']),
            $agnoResult['actions'] ?? [],
        );

        return $agnoResult;
    }

    // ── Ações ─────────────────────────────────────────────────────────────────

    private function applyUpdateLead(WhatsappConversation $conv, array $action, ?Lead $lead): void
    {
        $field = (string) ($action['field'] ?? '');
        $value = trim((string) ($action['value'] ?? ''));

        $allowed = ['name', 'email', 'company', 'birthday'];
        if ($field === '' || $value === '' || ! in_array($field, $allowed, true)) {
            return;
        }

        $lead = $lead ?? ($conv->lead_id ? Lead::withoutGlobalScope('tenant')->find($conv->lead_id) : null);
        if (! $lead) {
            return;
        }

        // Validate birthday format
        if ($field === 'birthday') {
            try {
                $value = \Carbon\Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable) {
                Log::channel('whatsapp')->warning('AI update_lead: data de nascimento inválida', [
                    'conversation_id' => $conv->id,
                    'value' => $value,
                ]);
                return;
            }
        }

        // Skip if value is the same
        $currentValue = (string) ($lead->{$field} ?? '');
        if ($field === 'birthday' && $lead->birthday) {
            $currentValue = $lead->birthday->format('Y-m-d');
        }
        if ($currentValue === $value) {
            return;
        }

        $lead->update([$field => $value]);

        $labels = ['name' => 'nome', 'email' => 'e-mail', 'company' => 'empresa', 'birthday' => 'data de nascimento'];
        $label  = $labels[$field] ?? $field;
        $displayValue = $field === 'birthday'
            ? \Carbon\Carbon::parse($value)->format('d/m/Y')
            : $value;

        LeadEvent::create([
            'tenant_id'    => $lead->tenant_id,
            'lead_id'      => $lead->id,
            'event_type'   => 'ai_data_updated',
            'description'  => "🤖 IA atualizou {$label}: {$displayValue}",
            'data_json'    => ['source' => 'ai_agent', 'field' => $field, 'value' => $value],
            'performed_by' => null,
            'created_at'   => now(),
        ]);

        Log::channel('whatsapp')->info('AI update_lead aplicado', [
            'conversation_id' => $conv->id,
            'lead_id'         => $lead->id,
            'field'           => $field,
            'value'           => $displayValue,
        ]);
    }

    private function applySetStage(WhatsappConversation $conv, int $stageId, AiAgent $agent, array $stages): void
    {
        if (! $conv->lead_id || $stageId <= 0) return;

        Lead::withoutGlobalScope('tenant')
            ->where('id', $conv->lead_id)
            ->update(['stage_id' => $stageId]);

        $stageName = collect($stages)->firstWhere('id', $stageId)['name'] ?? "id:{$stageId}";

        \App\Models\LeadEvent::create([
            'tenant_id'    => $conv->tenant_id,
            'lead_id'      => $conv->lead_id,
            'event_type'   => 'stage_changed',
            'description'  => "🤖 Agente {$agent->name} moveu para etapa \"{$stageName}\"",
            'data_json'    => ['source' => 'ai_agent', 'agent_id' => $agent->id],
            'performed_by' => null,
            'created_at'   => now(),
        ]);

        // Mensagem de evento visível no chat
        WhatsappMessage::withoutGlobalScope('tenant')->create([
            'tenant_id'       => $conv->tenant_id,
            'conversation_id' => $conv->id,
            'waha_message_id' => null,
            'direction'       => 'outbound',
            'type'            => 'event',
            'body'            => "Agente {$agent->name} moveu para etapa \"{$stageName}\"",
            'media_filename'  => 'Etapa alterada',
            'media_mime'      => 'ai_stage_changed',
            'sent_at'         => now(),
            'ack'             => 'delivered',
        ]);

        Log::channel('whatsapp')->info('AI: lead movido de etapa', [
            'conversation_id' => $conv->id,
            'contact_name'    => $conv->contact_name ?? $conv->phone,
            'phone'           => $conv->phone,
            'agent_name'      => $agent->name,
            'lead_id'         => $conv->lead_id,
            'new_stage_id'    => $stageId,
            'stage_name'      => $stageName,
        ]);
    }

    private function applyNotifyIntent(WhatsappConversation $conv, array $action, AiAgent $agent): void
    {
        $validTypes = ['buy', 'schedule', 'close', 'interest'];
        $intentType = in_array($action['intent'] ?? '', $validTypes, true)
            ? $action['intent']
            : 'interest';

        $signal = AiIntentSignal::withoutGlobalScope('tenant')->create([
            'tenant_id'       => $conv->tenant_id,
            'ai_agent_id'     => $conv->ai_agent_id,
            'conversation_id' => $conv->id,
            'contact_name'    => $conv->contact_name ?? $conv->phone,
            'phone'           => $conv->phone,
            'intent_type'     => $intentType,
            'context'         => mb_substr((string) ($action['context'] ?? ''), 0, 500),
        ]);

        try {
            AiIntentDetected::dispatch($signal, $conv->tenant_id);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('AI: falha ao broadcast intent signal', [
                'error' => $e->getMessage(),
            ]);
        }

        Log::channel('whatsapp')->info('AI: sinal de intenção detectado', [
            'conversation_id' => $conv->id,
            'contact_name'    => $conv->contact_name ?? $conv->phone,
            'phone'           => $conv->phone,
            'agent_name'      => $agent->name,
            'intent_type'     => $intentType,
            'signal_id'       => $signal->id,
        ]);
    }

    private function applyAssignHuman(WhatsappConversation $conv, AiAgent $agent): void
    {
        // Prioridade: departamento > usuário direto
        if ($agent->transfer_to_department_id) {
            $dept = Department::withoutGlobalScope('tenant')
                ->where('id', $agent->transfer_to_department_id)
                ->where('tenant_id', $conv->tenant_id)
                ->first();

            if ($dept) {
                // Remove AI agent first
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conv->id)
                    ->update(['ai_agent_id' => null]);
                $conv->refresh();

                $dept->assignConversation($conv);

                // Event message
                WhatsappMessage::withoutGlobalScope('tenant')->create([
                    'tenant_id'       => $conv->tenant_id,
                    'conversation_id' => $conv->id,
                    'waha_message_id' => null,
                    'direction'       => 'outbound',
                    'type'            => 'event',
                    'body'            => "Agente {$agent->name} transferiu a conversa para o departamento {$dept->name}",
                    'media_filename'  => "Transferido para {$dept->name}",
                    'media_mime'      => 'ai_assign_human',
                    'sent_at'         => now(),
                    'ack'             => 'delivered',
                ]);

                Log::channel('whatsapp')->info('AI: conversa transferida para departamento', [
                    'conversation_id' => $conv->id,
                    'department_id'   => $dept->id,
                    'department_name' => $dept->name,
                    'agent_name'      => $agent->name,
                ]);

                try {
                    WhatsappConversationUpdated::dispatch($conv->refresh(), $conv->tenant_id);
                } catch (\Throwable $e) {
                    Log::channel('whatsapp')->warning('AI: falha ao broadcast dept transfer', [
                        'error' => $e->getMessage(),
                    ]);
                }

                return;
            }
        }

        $update = ['ai_agent_id' => null];
        if ($agent->transfer_to_user_id) {
            $update['assigned_user_id'] = $agent->transfer_to_user_id;
        }

        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update($update);

        $conv->refresh();

        try {
            WhatsappConversationUpdated::dispatch($conv, $conv->tenant_id);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('AI: falha ao broadcast assign_human', [
                'error' => $e->getMessage(),
            ]);
        }

        // Mensagem de evento visível no chat
        WhatsappMessage::withoutGlobalScope('tenant')->create([
            'tenant_id'       => $conv->tenant_id,
            'conversation_id' => $conv->id,
            'waha_message_id' => null,
            'direction'       => 'outbound',
            'type'            => 'event',
            'body'            => "Agente {$agent->name} transferiu a conversa para atendimento humano",
            'media_filename'  => 'Transferido para humano',
            'media_mime'      => 'ai_assign_human',
            'sent_at'         => now(),
            'ack'             => 'delivered',
        ]);

        Log::channel('whatsapp')->info('AI: conversa transferida para humano', [
            'conversation_id'  => $conv->id,
            'contact_name'     => $conv->contact_name ?? $conv->phone,
            'phone'            => $conv->phone,
            'agent_name'       => $agent->name,
            'transfer_to_user' => $agent->transfer_to_user_id,
        ]);
    }

    private function applyAddTags(WhatsappConversation $conv, array $newTags, AiAgent $agent): void
    {
        if (empty($newTags)) return;

        $conv->refresh();
        $existing = $conv->tags ?? [];
        $merged   = array_values(array_unique(array_merge($existing, $newTags)));

        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update(['tags' => json_encode($merged)]);

        if ($conv->lead_id) {
            $tagLabel = count($newTags) === 1
                ? "tag \"{$newTags[0]}\""
                : count($newTags) . ' tags: ' . implode(', ', $newTags);
            \App\Models\LeadEvent::create([
                'tenant_id'    => $conv->tenant_id,
                'lead_id'      => $conv->lead_id,
                'event_type'   => 'ai_tag_added',
                'description'  => "🤖 Agente {$agent->name} adicionou {$tagLabel}",
                'data_json'    => ['source' => 'ai_agent', 'agent_id' => $agent->id, 'tags' => $newTags],
                'performed_by' => null,
                'created_at'   => now(),
            ]);
        }

        // Mensagem de evento visível no chat
        $tagLabel = count($newTags) === 1
            ? "tag \"{$newTags[0]}\""
            : count($newTags) . ' tags: ' . implode(', ', $newTags);
        WhatsappMessage::withoutGlobalScope('tenant')->create([
            'tenant_id'       => $conv->tenant_id,
            'conversation_id' => $conv->id,
            'waha_message_id' => null,
            'direction'       => 'outbound',
            'type'            => 'event',
            'body'            => "Agente {$agent->name} adicionou {$tagLabel}",
            'media_filename'  => 'Tag adicionada',
            'media_mime'      => 'ai_tag_added',
            'sent_at'         => now(),
            'ack'             => 'delivered',
        ]);

        Log::channel('whatsapp')->info('AI: tags adicionadas', [
            'conversation_id' => $conv->id,
            'contact_name'    => $conv->contact_name ?? $conv->phone,
            'phone'           => $conv->phone,
            'agent_name'      => $agent->name,
            'tags_added'      => $newTags,
            'tags_total'      => $merged,
        ]);
    }

    private function applyCalendarAction(
        WhatsappConversation $conv,
        array $action,
        GoogleCalendarService $calendarService,
        ?Lead $lead = null,
    ): ?string {
        $type = $action['type'] ?? '';

        try {
            switch ($type) {
                case 'calendar_create':
                    $agentDesc = $action['description'] ?? '';

                    $contactLines = [];
                    if ($lead) {
                        if ($lead->name)    $contactLines[] = "Nome: {$lead->name}";
                        if ($lead->phone)   $contactLines[] = "Telefone: {$lead->phone}";
                        if ($lead->email)   $contactLines[] = "Email: {$lead->email}";
                        if ($lead->company) $contactLines[] = "Empresa: {$lead->company}";
                    } elseif ($conv->contact_name || $conv->phone) {
                        if ($conv->contact_name) $contactLines[] = "Nome: {$conv->contact_name}";
                        if ($conv->phone)        $contactLines[] = "Telefone: {$conv->phone}";
                    }

                    $description = $agentDesc;
                    if (! empty($contactLines)) {
                        // Não duplicar se o LLM já incluiu dados do cliente na descrição
                        $alreadyHasContact = ! empty($agentDesc) && (
                            str_contains($agentDesc, 'Nome:') ||
                            str_contains($agentDesc, 'Telefone:') ||
                            str_contains($agentDesc, 'Cliente:')
                        );
                        if (! $alreadyHasContact) {
                            $contactBlock = implode("\n", $contactLines);
                            $description  = $agentDesc
                                ? $agentDesc . "\n\n---\n" . $contactBlock
                                : $contactBlock;
                        }
                    }

                    $startStr = $action['start'] ?? now()->addHour()->format('Y-m-d\TH:i');
                    $event = $calendarService->createEvent([
                        'title'       => $action['title']     ?? 'Evento',
                        'start'       => $startStr,
                        'end'         => $action['end']       ?? now()->addHours(2)->format('Y-m-d\TH:i'),
                        'description' => $description,
                        'location'    => $action['location']  ?? '',
                        'attendees'   => $action['attendees'] ?? '',
                    ]);
                    Log::channel('whatsapp')->info('AI calendar: evento criado', [
                        'conversation_id' => $conv->id,
                        'event_id'        => $event['id'] ?? null,
                        'title'           => $action['title'] ?? '',
                    ]);
                    $eventTitle = $action['title'] ?? 'Evento';
                    $dateFormatted = \Carbon\Carbon::parse($startStr)
                        ->setTimezone(config('app.timezone', 'America/Sao_Paulo'))
                        ->format('d/m/Y \à\s H:i');
                    $attendee = trim((string) ($action['attendees'] ?? ''));
                    $msg = "Reunião marcada para {$dateFormatted}!";
                    if ($attendee) {
                        $msg .= " O convite foi enviado para {$attendee}.";
                    }

                    // Mensagem de evento visível no chat
                    WhatsappMessage::withoutGlobalScope('tenant')->create([
                        'tenant_id'       => $conv->tenant_id,
                        'conversation_id' => $conv->id,
                        'waha_message_id' => null,
                        'direction'       => 'outbound',
                        'type'            => 'event',
                        'body'            => "Agente agendou \"{$eventTitle}\" para {$dateFormatted}",
                        'media_filename'  => 'Evento agendado',
                        'media_mime'      => 'ai_calendar_create',
                        'sent_at'         => now(),
                        'ack'             => 'delivered',
                    ]);

                    return $msg;

                case 'calendar_reschedule':
                    $eventId = $action['event_id'] ?? '';
                    if ($eventId) {
                        $calendarService->updateEvent($eventId, [
                            'start' => $action['start'] ?? '',
                            'end'   => $action['end']   ?? '',
                        ]);
                        Log::channel('whatsapp')->info('AI calendar: evento reagendado', [
                            'conversation_id' => $conv->id,
                            'event_id'        => $eventId,
                        ]);
                    }
                    return null;

                case 'calendar_cancel':
                    $eventId = $action['event_id'] ?? '';
                    if ($eventId) {
                        $calendarService->deleteEvent($eventId);
                        Log::channel('whatsapp')->info('AI calendar: evento cancelado', [
                            'conversation_id' => $conv->id,
                            'event_id'        => $eventId,
                        ]);
                    }
                    return null;

                case 'calendar_list':
                    // calendar_list é apenas informativo — o agente já recebe os eventos no system prompt
                    Log::channel('whatsapp')->debug('AI calendar: calendar_list solicitado (já no contexto)', [
                        'conversation_id' => $conv->id,
                    ]);
                    return null;

                default:
                    return null;
            }
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('AI calendar: falha ao executar ação de agenda', [
                'conversation_id' => $conv->id,
                'action_type'     => $type,
                'error'           => $e->getMessage(),
            ]);
            return '⚠️ Não foi possível criar o evento no Google Calendar. Verifique se a integração com o Google está ativa nas configurações.';
        }
    }

    /**
     * Executa uma ferramenta de calendário no loop agentic e retorna
     * uma string de resultado legível para o LLM.
     */
    private function runCalendarTool(
        array $action,
        GoogleCalendarService $calendarService,
        WhatsappConversation $conv,
        ?Lead $lead = null,
    ): string {
        $type = $action['type'] ?? '';

        try {
            switch ($type) {
                case 'check_calendar_availability':
                    $start  = $action['start'] ?? now()->format('Y-m-d\TH:i');
                    $end    = $action['end']   ?? now()->addHour()->format('Y-m-d\TH:i');
                    $events = $calendarService->listEvents($start, $end);
                    if (empty($events)) {
                        return "Horário disponível: {$start} até {$end} está livre no calendário.";
                    }
                    $conflicts = implode(', ', array_map(
                        fn ($e) => ($e['title'] ?? 'evento') . ' (' . ($e['start'] ?? '') . ')',
                        $events
                    ));
                    return "Horário INDISPONÍVEL: há conflito com — {$conflicts}";

                case 'calendar_create':
                    $agentDesc    = $action['description'] ?? '';
                    $contactLines = [];
                    if ($lead) {
                        if ($lead->name)    $contactLines[] = "Nome: {$lead->name}";
                        if ($lead->phone)   $contactLines[] = "Telefone: {$lead->phone}";
                        if ($lead->email)   $contactLines[] = "Email: {$lead->email}";
                        if ($lead->company) $contactLines[] = "Empresa: {$lead->company}";
                    } elseif ($conv->contact_name || $conv->phone) {
                        if ($conv->contact_name) $contactLines[] = "Nome: {$conv->contact_name}";
                        if ($conv->phone)        $contactLines[] = "Telefone: {$conv->phone}";
                    }
                    $description = $agentDesc;
                    if (! empty($contactLines)) {
                        $contactBlock = implode("\n", $contactLines);
                        $description  = $agentDesc ? $agentDesc . "\n\n---\n" . $contactBlock : $contactBlock;
                    }
                    $startStr = $action['start'] ?? now()->addHour()->format('Y-m-d\TH:i');
                    $event = $calendarService->createEvent([
                        'title'       => $action['title']     ?? 'Evento',
                        'start'       => $startStr,
                        'end'         => $action['end']       ?? now()->addHours(2)->format('Y-m-d\TH:i'),
                        'description' => $description,
                        'location'    => $action['location']  ?? '',
                        'attendees'   => $action['attendees'] ?? '',
                    ]);
                    Log::channel('whatsapp')->info('AI calendar (loop): evento criado', [
                        'conversation_id' => $conv->id,
                        'event_id'        => $event['id'] ?? null,
                        'title'           => $action['title'] ?? '',
                    ]);
                    $dateFormatted = \Carbon\Carbon::parse($startStr)
                        ->setTimezone(config('app.timezone', 'America/Sao_Paulo'))
                        ->format('d/m/Y \à\s H:i');
                    $attendee = trim((string) ($action['attendees'] ?? ''));
                    $result   = "Evento criado com sucesso: \"{$action['title']}\" para {$dateFormatted}.";
                    if ($attendee) $result .= " Convite enviado para {$attendee}.";
                    return $result;

                case 'calendar_reschedule':
                    $eventId = $action['event_id'] ?? '';
                    if ($eventId) {
                        $calendarService->updateEvent($eventId, [
                            'start' => $action['start'] ?? '',
                            'end'   => $action['end']   ?? '',
                        ]);
                        $newDate = \Carbon\Carbon::parse($action['start'] ?? '')
                            ->setTimezone(config('app.timezone', 'America/Sao_Paulo'))
                            ->format('d/m/Y \à\s H:i');
                        Log::channel('whatsapp')->info('AI calendar (loop): evento reagendado', [
                            'conversation_id' => $conv->id,
                            'event_id'        => $eventId,
                        ]);
                        return "Evento reagendado com sucesso para {$newDate}.";
                    }
                    return "Erro: event_id não informado para reagendamento.";

                case 'calendar_cancel':
                    $eventId = $action['event_id'] ?? '';
                    if ($eventId) {
                        $calendarService->deleteEvent($eventId);
                        Log::channel('whatsapp')->info('AI calendar (loop): evento cancelado', [
                            'conversation_id' => $conv->id,
                            'event_id'        => $eventId,
                        ]);
                        return "Evento cancelado com sucesso (id: {$eventId}).";
                    }
                    return "Erro: event_id não informado para cancelamento.";

                default:
                    return "Ação desconhecida: {$type}";
            }
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('AI calendar (loop): falha ao executar ferramenta', [
                'conversation_id' => $conv->id,
                'action_type'     => $type,
                'error'           => $e->getMessage(),
            ]);
            return "Erro ao executar {$type}: " . $e->getMessage();
        }
    }
}
