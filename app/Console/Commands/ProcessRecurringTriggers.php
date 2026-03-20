<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Automation;
use App\Models\Lead;
use App\Models\WhatsappConversation;
use App\Services\AutomationEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessRecurringTriggers extends Command
{
    protected $signature = 'automations:process-recurring';
    protected $description = 'Process recurring automations (weekly/monthly schedules)';

    public function handle(AutomationEngine $engine): int
    {
        $now = Carbon::now(config('app.timezone', 'America/Sao_Paulo'));
        $currentHour = (int) $now->format('H');

        // Only run during business hours (8-20)
        if ($currentHour < 8 || $currentHour > 20) {
            $this->info('Fora do horário comercial (08-20). Ignorando.');
            return 0;
        }

        $automations = Automation::withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->where('trigger_type', 'recurring')
            ->get();

        if ($automations->isEmpty()) {
            $this->info('Nenhuma automação recorrente ativa.');
            return 0;
        }

        foreach ($automations as $automation) {
            try {
                $this->processAutomation($automation, $engine, $now, $currentHour);
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->error('Erro em automação recorrente', [
                    'automation_id' => $automation->id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        return 0;
    }

    private function processAutomation(Automation $automation, AutomationEngine $engine, Carbon $now, int $currentHour): void
    {
        $config = $automation->trigger_config ?? [];
        $type   = $config['recurrence_type'] ?? 'monthly';
        $days   = $config['days'] ?? [];
        $time   = $config['time'] ?? '09:00';
        $filter = $config['filter_type'] ?? 'all';
        $filterValue  = $config['filter_value'] ?? null;
        $dailyLimit   = (int) ($config['daily_limit'] ?? 100);
        $delaySeconds = (int) ($config['delay_seconds'] ?? 8);

        // Check if configured hour matches current hour
        $targetHour = (int) explode(':', $time)[0];
        if ($currentHour !== $targetHour) {
            return;
        }

        // Check if today matches configured days
        $todayMatch = false;
        if ($type === 'monthly') {
            $todayMatch = in_array($now->day, array_map('intval', $days));
        } elseif ($type === 'weekly') {
            // days: 0=Dom, 1=Seg, 2=Ter, 3=Qua, 4=Qui, 5=Sex, 6=Sab
            $todayMatch = in_array($now->dayOfWeek, array_map('intval', $days));
        }

        if (! $todayMatch) {
            return;
        }

        // Check if already ran today (prevent double execution)
        if ($automation->last_run_at && $automation->last_run_at->isToday()) {
            $this->info("Automação #{$automation->id} já executou hoje. Ignorando.");
            return;
        }

        $this->info("Processando automação #{$automation->id}: {$automation->name}");

        // Find leads matching filter
        $leadsQuery = Lead::withoutGlobalScope('tenant')
            ->where('tenant_id', $automation->tenant_id)
            ->where('exclude_from_pipeline', false);

        if ($filter === 'tag' && $filterValue) {
            $leadsQuery->whereJsonContains('tags', $filterValue);
        } elseif ($filter === 'stage' && $filterValue) {
            $leadsQuery->where('stage_id', (int) $filterValue);
        }

        $leads = $leadsQuery->get();
        $sent = 0;

        foreach ($leads as $lead) {
            if ($sent >= $dailyLimit) {
                $this->warn("Limite diário atingido ({$dailyLimit}). Parando.");
                break;
            }

            // Only send to leads with existing WhatsApp conversation
            $conversation = WhatsappConversation::withoutGlobalScope('tenant')
                ->where('tenant_id', $automation->tenant_id)
                ->where(function ($q) use ($lead) {
                    $q->where('phone', $lead->phone)
                      ->orWhere('phone', ltrim($lead->phone, '+'));
                })
                ->first();

            if (! $conversation) {
                continue; // Skip leads without WA conversation
            }

            // Run automation actions for this lead
            $context = [
                'lead'         => $lead,
                'conversation' => $conversation,
                'tenant_id'    => $automation->tenant_id,
            ];

            $engine->runForAutomation($automation, $context);
            $sent++;

            // Delay between sends (anti-ban)
            if ($delaySeconds > 0 && $sent < $dailyLimit) {
                sleep($delaySeconds);
            }
        }

        // Update metadata
        $automation->update([
            'run_count'   => $automation->run_count + 1,
            'last_run_at' => now(),
        ]);

        $this->info("Automação #{$automation->id}: {$sent} mensagens enviadas de {$leads->count()} leads.");
    }
}
