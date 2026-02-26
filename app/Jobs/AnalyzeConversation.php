<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WhatsappConversation;
use App\Services\ConversationAnalystService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeConversation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int  $conversationId,
        public readonly bool $force = false,
    ) {}

    public function handle(ConversationAnalystService $analyst): void
    {
        $conv = WhatsappConversation::withoutGlobalScope('tenant')
            ->with(['lead'])
            ->find($this->conversationId);

        if (! $conv) {
            return;
        }

        if (! $conv->lead_id) {
            return;
        }

        try {
            $suggestions = $analyst->runLlm($conv);

            if (! empty($suggestions)) {
                $analyst->createSuggestions($suggestions, $conv);
            }

            // Atualiza timestamp de última análise
            WhatsappConversation::withoutGlobalScope('tenant')
                ->where('id', $conv->id)
                ->update(['last_analyst_run_at' => now()]);

        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('AnalyzeConversation job falhou', [
                'conversation_id' => $this->conversationId,
                'error'           => $e->getMessage(),
                'trace'           => mb_substr($e->getTraceAsString(), 0, 1000),
            ]);
        }
    }
}
