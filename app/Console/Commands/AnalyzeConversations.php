<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AnalyzeConversation;
use App\Models\WhatsappConversation;
use Illuminate\Console\Command;

class AnalyzeConversations extends Command
{
    protected $signature   = 'ai:analyze-conversations';
    protected $description = 'Analisa conversas abertas com IA e gera sugestões para os leads';

    public function handle(): int
    {
        // Últimas 30 conversas abertas que têm mensagem nova desde a última análise
        // Filtra tenants com IA analista desativada (settings_json['ai_analyst_enabled'] = false)
        $conversations = WhatsappConversation::withoutGlobalScope('tenant')
            ->with('tenant')
            ->where('status', 'open')
            ->whereNotNull('lead_id')
            ->where(function ($q) {
                $q->whereNull('last_analyst_run_at')
                  ->orWhereColumn('last_message_at', '>', 'last_analyst_run_at');
            })
            ->orderByDesc('last_message_at')
            ->limit(30)
            ->get()
            ->filter(fn ($c) => ($c->tenant?->settings_json['ai_analyst_enabled'] ?? true) !== false);

        foreach ($conversations as $conv) {
            AnalyzeConversation::dispatch($conv->id);
        }

        $this->info("Disparadas {$conversations->count()} análises.");

        return self::SUCCESS;
    }
}
