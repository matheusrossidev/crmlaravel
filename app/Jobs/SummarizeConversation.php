<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Http\Controllers\Tenant\AiConfigurationController;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\AgnoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SummarizeConversation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 60;

    public function __construct(
        public readonly int $conversationId,
    ) {
        $this->queue = 'ai';
    }

    public function handle(): void
    {
        $conv = WhatsappConversation::withoutGlobalScope('tenant')
            ->with('aiAgent')
            ->find($this->conversationId);

        if (! $conv || ! $conv->ai_agent_id) {
            return;
        }

        $agent = $conv->aiAgent;
        if (! $agent) {
            return;
        }

        // Fetch conversation messages
        $messages = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('whatsapp_conversation_id', $conv->id)
            ->orderBy('created_at')
            ->limit(100)
            ->get(['from_me', 'body', 'type']);

        if ($messages->count() < 3) {
            // Too short to summarize
            return;
        }

        // Build transcript
        $transcript = $messages->map(function ($msg) {
            $role = $msg->from_me ? 'Agente' : 'Cliente';
            $body = $msg->type === 'audio' ? '[mensagem de áudio]' : ($msg->body ?? '');
            return "{$role}: {$body}";
        })->implode("\n");

        // Truncate if too long
        $transcript = mb_substr($transcript, 0, 8000);

        // Generate summary via LLM
        $provider = (string) config('ai.provider', 'openai');
        $apiKey   = (string) config('ai.api_key', '');
        $model    = (string) config('ai.model', 'gpt-4o-mini');

        if ($apiKey === '') {
            return;
        }

        try {
            $result = AiConfigurationController::callLlm(
                provider:  $provider,
                apiKey:    $apiKey,
                model:     $model,
                messages:  [['role' => 'user', 'content' => $transcript]],
                maxTokens: 500,
                system:    <<<'PROMPT'
Analise esta conversa de WhatsApp entre um agente IA e um cliente.
Gere um JSON com:
{
  "summary": "Resumo conciso da conversa (2-3 frases)",
  "customer_profile": "Perfil do cliente baseado na conversa (necessidades, preferências, comportamento)",
  "key_learnings": "Aprendizados importantes para futuras interações com este cliente"
}
Responda APENAS o JSON, sem texto adicional.
PROMPT,
                forceJson: true,
            );

            $parsed = json_decode($result['reply'], true);
            if (! $parsed || empty($parsed['summary'])) {
                Log::channel('whatsapp')->warning('SummarizeConversation: LLM returned invalid JSON', [
                    'conversation_id' => $this->conversationId,
                    'reply'           => mb_substr($result['reply'], 0, 200),
                ]);
                return;
            }

            // Store memory in Agno
            $agnoService = app(AgnoService::class);
            $stored = $agnoService->storeMemory($agent->id, [
                'tenant_id'        => $conv->tenant_id,
                'conversation_id'  => $conv->id,
                'contact_phone'    => $conv->phone ?? null,
                'summary'          => $parsed['summary'],
                'customer_profile' => $parsed['customer_profile'] ?? null,
                'key_learnings'    => $parsed['key_learnings'] ?? null,
            ]);

            Log::channel('whatsapp')->info('SummarizeConversation: memory stored', [
                'conversation_id' => $this->conversationId,
                'agent_id'        => $agent->id,
                'stored'          => $stored,
            ]);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('SummarizeConversation: failed', [
                'conversation_id' => $this->conversationId,
                'error'           => $e->getMessage(),
            ]);
        }
    }
}
