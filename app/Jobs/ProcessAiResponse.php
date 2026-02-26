<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\AiIntentDetected;
use App\Events\WhatsappConversationUpdated;
use App\Http\Controllers\Tenant\AiConfigurationController;
use App\Models\AiAgent;
use App\Models\AiIntentSignal;
use App\Models\Lead;
use App\Models\WhatsappConversation;
use App\Services\AiAgentService;
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

        // ── Carregar pipeline stages — apenas se ferramenta habilitada ──────────
        $stages = [];
        if ($agent->enable_pipeline_tool && $conv->lead_id) {
            $lead = Lead::withoutGlobalScope('tenant')
                ->with('stage.pipeline.stages')
                ->find($conv->lead_id);
            if ($lead && $lead->stage && $lead->stage->pipeline) {
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

        // ── Montar prompt e histórico ─────────────────────────────────────────
        $service            = new AiAgentService();
        $enableIntentNotify = (bool) ($agent->enable_intent_notify ?? false);
        $system             = $service->buildSystemPrompt($agent, $stages, $availTags, $enableIntentNotify);
        $history = $service->buildHistory($conv, limit: 50);

        if (empty($history)) {
            Log::channel('whatsapp')->warning('AI job: histórico vazio, abortando', [
                'conversation_id' => $this->conversationId,
            ]);
            return;
        }

        // ── Chamar o LLM ─────────────────────────────────────────────────────
        $maxLength = max(200, $agent->max_message_length ?? 500);
        // Aumentar maxTokens quando esperamos JSON (headers de controle adicionam tokens)
        $extraTokens = (! empty($stages) || ! empty($availTags)) ? 300 : 0;
        $maxTokens   = $maxLength + 200 + $extraTokens;

        $reply = AiConfigurationController::callLlm(
            provider:  $provider,
            apiKey:    $apiKey,
            model:     $model,
            messages:  $history,
            maxTokens: $maxTokens,
            system:    $system,
        );

        $reply = trim($reply);

        if ($reply === '') {
            Log::channel('whatsapp')->warning('AI job: LLM retornou resposta vazia', [
                'conversation_id' => $this->conversationId,
            ]);
            return;
        }

        // ── Parsear JSON de ações (quando pipeline/tags/intent disponíveis) ──
        $actions = [];
        if (! empty($stages) || ! empty($availTags) || $enableIntentNotify) {
            // Remover markdown code blocks se o LLM os incluiu
            $clean = preg_replace('/```(?:json)?\s*([\s\S]*?)```/i', '$1', $reply);
            $clean = trim($clean ?? $reply);

            // O LLM às vezes adiciona texto antes do JSON — encontrar o primeiro '{'
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
                $reply   = trim((string) ($decoded['reply'] ?? ''));
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

        // ── Aplicar ações de pipeline e tags ─────────────────────────────────
        foreach ($actions as $action) {
            $type = $action['type'] ?? '';

            if ($type === 'set_stage') {
                // Só aplicar se a ferramenta está ativa e o stage_id é válido para este funil
                $stageId  = (int) ($action['stage_id'] ?? 0);
                $validIds = array_column($stages, 'id');
                if (! empty($stages) && $stageId > 0 && in_array($stageId, $validIds, true)) {
                    $this->applySetStage($conv, $stageId);
                } else {
                    Log::channel('whatsapp')->warning('AI: set_stage ignorado — id inválido ou ferramenta desativada', [
                        'conversation_id' => $conv->id,
                        'stage_id'        => $stageId,
                        'valid_ids'       => $validIds,
                    ]);
                }
            } elseif ($type === 'add_tags') {
                $this->applyAddTags($conv, (array) ($action['tags'] ?? []));
            } elseif ($type === 'notify_intent') {
                $this->applyNotifyIntent($conv, $action);
            } elseif ($type === 'assign_human') {
                $this->applyAssignHuman($conv, $agent);
            }
        }

        // ── Dividir em mensagens e enviar com delay ───────────────────────────
        $delay    = max(1, $agent->response_delay_seconds ?? 2);
        $messages = $service->splitIntoMessages($reply, $maxLength);

        Log::channel('whatsapp')->info('AI job: enviando resposta', [
            'conversation_id' => $this->conversationId,
            'parts'           => count($messages),
            'delay_seconds'   => $delay,
        ]);

        $service->sendWhatsappReplies($conv, $messages, $delay);
    }

    // ── Ações ─────────────────────────────────────────────────────────────────

    private function applySetStage(WhatsappConversation $conv, int $stageId): void
    {
        if (! $conv->lead_id || $stageId <= 0) return;

        Lead::withoutGlobalScope('tenant')
            ->where('id', $conv->lead_id)
            ->update(['stage_id' => $stageId]);

        Log::channel('whatsapp')->info('AI: lead movido de etapa', [
            'conversation_id' => $conv->id,
            'lead_id'         => $conv->lead_id,
            'new_stage_id'    => $stageId,
        ]);
    }

    private function applyNotifyIntent(WhatsappConversation $conv, array $action): void
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
            'intent_type'     => $intentType,
            'signal_id'       => $signal->id,
        ]);
    }

    private function applyAssignHuman(WhatsappConversation $conv, AiAgent $agent): void
    {
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

        Log::channel('whatsapp')->info('AI: conversa transferida para humano', [
            'conversation_id'  => $conv->id,
            'transfer_to_user' => $agent->transfer_to_user_id,
        ]);
    }

    private function applyAddTags(WhatsappConversation $conv, array $newTags): void
    {
        if (empty($newTags)) return;

        $conv->refresh();
        $existing = $conv->tags ?? [];
        $merged   = array_values(array_unique(array_merge($existing, $newTags)));

        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update(['tags' => json_encode($merged)]);

        Log::channel('whatsapp')->info('AI: tags adicionadas', [
            'conversation_id' => $conv->id,
            'tags_added'      => $newTags,
            'tags_total'      => $merged,
        ]);
    }
}
