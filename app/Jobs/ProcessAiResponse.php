<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Http\Controllers\Tenant\AiConfigurationController;
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

    /** Fila dedicada para respostas de IA (prioridade separada). */
    public string $queue = 'ai';

    public int $tries = 1;   // Sem retry — se falhou, a janela de debounce já passou

    public int $timeout = 120;

    public function __construct(
        public readonly int $conversationId,
        public readonly int $version,
    ) {}

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
        if (! Cache::add("ai:lock:{$this->conversationId}", 1, 120)) {
            Log::channel('whatsapp')->debug('AI job: já em processamento, pulando', [
                'conversation_id' => $this->conversationId,
            ]);
            return;
        }

        try {
            $this->process();
        } finally {
            Cache::forget("ai:lock:{$this->conversationId}");
        }
    }

    private function process(): void
    {
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

        // ── Montar prompt e histórico ─────────────────────────────────────────
        $service = new AiAgentService();
        $system  = $service->buildSystemPrompt($agent);
        $history = $service->buildHistory($conv, limit: 50);

        if (empty($history)) {
            Log::channel('whatsapp')->warning('AI job: histórico vazio, abortando', [
                'conversation_id' => $this->conversationId,
            ]);
            return;
        }

        // ── Chamar o LLM ─────────────────────────────────────────────────────
        $maxTokens = max(200, ($agent->max_message_length ?? 500) + 200);

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

        // Truncar se necessário
        $maxLength = $agent->max_message_length ?? 500;
        if ($maxLength > 0 && mb_strlen($reply) > $maxLength) {
            $reply = mb_substr($reply, 0, $maxLength) . '…';
        }

        // ── Enviar pelo WhatsApp ──────────────────────────────────────────────
        $service->sendWhatsappReply($conv, $reply);
    }
}
