<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Automation;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\Lead;
use App\Services\AutomationEngine;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessDateTriggers extends Command
{
    protected $signature   = 'automations:process-date-triggers';
    protected $description = 'Dispara automações baseadas em datas (aniversários, campos de data) diariamente';

    public function handle(): int
    {
        $automations = Automation::withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->where('trigger_type', 'date_field')
            ->get();

        if ($automations->isEmpty()) {
            $this->line('Nenhuma automação de data ativa encontrada.');
            return self::SUCCESS;
        }

        $engine = new AutomationEngine();
        $today  = Carbon::today();

        foreach ($automations as $automation) {
            try {
                $this->processAutomation($automation, $engine, $today);
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->error('ProcessDateTriggers: erro na automação', [
                    'automation_id' => $automation->id,
                    'error'         => $e->getMessage(),
                    'trace'         => $e->getTraceAsString(),
                ]);
                $this->error("Erro na automação #{$automation->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    private function processAutomation(Automation $automation, AutomationEngine $engine, Carbon $today): void
    {
        $config       = $automation->trigger_config ?? [];
        $dateField    = $config['date_field']    ?? null;
        $daysBefore   = (int)  ($config['days_before']  ?? 0);
        $repeatYearly = (bool) ($config['repeat_yearly'] ?? true);
        $tenantId     = $automation->tenant_id;

        if (! $dateField) {
            return;
        }

        $targetDate = $today->copy()->addDays($daysBefore);

        $leads = $this->findMatchingLeads($tenantId, $dateField, $targetDate, $repeatYearly);

        $this->line("Automação #{$automation->id}: {$leads->count()} lead(s) encontrado(s).");

        foreach ($leads as $lead) {
            try {
                $context = $this->buildContext($lead, $dateField, $daysBefore);
                $engine->run('date_field', $context);
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->error('ProcessDateTriggers: erro no lead', [
                    'automation_id' => $automation->id,
                    'lead_id'       => $lead->id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }
    }

    private function findMatchingLeads(int $tenantId, string $dateField, Carbon $targetDate, bool $repeatYearly)
    {
        if ($dateField === 'birthday') {
            $query = Lead::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->whereNotNull('birthday');

            if ($repeatYearly) {
                $query->whereMonth('birthday', $targetDate->month)
                      ->whereDay('birthday', $targetDate->day);
            } else {
                $query->whereDate('birthday', $targetDate->toDateString());
            }

            return $query->with([
                'pipeline'             => fn ($q) => $q->withoutGlobalScope('tenant'),
                'stage'                => fn ($q) => $q->withoutGlobalScope('tenant'),
                'whatsappConversation' => fn ($q) => $q->withoutGlobalScope('tenant'),
            ])->get();
        }

        if (str_starts_with($dateField, 'cf:')) {
            $fieldId = (int) substr($dateField, 3);

            $cfQuery = CustomFieldValue::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('field_id', $fieldId)
                ->whereNotNull('value_date');

            if ($repeatYearly) {
                $cfQuery->whereMonth('value_date', $targetDate->month)
                        ->whereDay('value_date', $targetDate->day);
            } else {
                $cfQuery->whereDate('value_date', $targetDate->toDateString());
            }

            $leadIds = $cfQuery->pluck('lead_id');

            if ($leadIds->isEmpty()) {
                return collect();
            }

            return Lead::withoutGlobalScope('tenant')
                ->whereIn('id', $leadIds)
                ->where('tenant_id', $tenantId)
                ->with([
                    'pipeline'             => fn ($q) => $q->withoutGlobalScope('tenant'),
                    'stage'                => fn ($q) => $q->withoutGlobalScope('tenant'),
                    'whatsappConversation' => fn ($q) => $q->withoutGlobalScope('tenant'),
                ])
                ->get();
        }

        return collect();
    }

    private function buildContext(Lead $lead, string $dateField, int $daysBefore): array
    {
        $actualDate = null;
        $cfLabel    = '';

        if ($dateField === 'birthday') {
            $actualDate = $lead->birthday;
            $cfLabel    = 'Aniversário';
        } elseif (str_starts_with($dateField, 'cf:')) {
            $fieldId    = (int) substr($dateField, 3);
            $cfv        = CustomFieldValue::withoutGlobalScope('tenant')
                ->where('lead_id', $lead->id)
                ->where('field_id', $fieldId)
                ->first();
            $actualDate = $cfv?->value_date;

            $def     = CustomFieldDefinition::withoutGlobalScope('tenant')->find($fieldId);
            $cfLabel = $def?->label ?? '';
        }

        $conversation = $lead->whatsappConversation;

        return [
            'tenant_id'          => $lead->tenant_id,
            'lead'               => $lead,
            'conversation'       => $conversation,
            'channel'            => $conversation ? 'whatsapp' : null,
            'stage_new'          => $lead->stage,
            'stage_old_id'       => null,
            'message'            => null,
            'birthday_formatted' => $actualDate ? $actualDate->format('d/m/Y') : '',
            'days_until'         => $daysBefore,
            'custom_field_label' => $cfLabel,
        ];
    }
}
