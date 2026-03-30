<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\SendNpsSurveyJob;
use App\Models\NpsSurvey;
use App\Models\Sale;
use App\Support\TenantCache;

class SaleObserver
{
    public function created(Sale $sale): void
    {
        $this->clearCaches($sale->tenant_id);
        $this->triggerNpsSurvey($sale);
    }

    public function updated(Sale $sale): void
    {
        $this->clearCaches($sale->tenant_id);
    }

    public function deleted(Sale $sale): void
    {
        $this->clearCaches($sale->tenant_id);
    }

    private function triggerNpsSurvey(Sale $sale): void
    {
        if (!$sale->lead_id) return;

        $survey = NpsSurvey::withoutGlobalScope('tenant')
            ->where('tenant_id', $sale->tenant_id)
            ->where('trigger', 'lead_won')
            ->where('is_active', true)
            ->first();

        if (!$survey) return;

        $delay = $survey->delay_hours > 0 ? now()->addHours($survey->delay_hours) : now();

        try {
            SendNpsSurveyJob::dispatch($sale->lead_id, $survey->id)->delay($delay);
        } catch (\Throwable) {
            // Redis may not be available in dev
            try {
                SendNpsSurveyJob::dispatchSync($sale->lead_id, $survey->id);
            } catch (\Throwable) {}
        }
    }

    private function clearCaches(int $tenantId): void
    {
        TenantCache::forgetMany($tenantId, [
            'dashboard:stats',
            'dashboard:monthly',
            'reports:overview',
            'reports:vendors',
            'reports:sourceConversion',
            'campaigns:utm',
            'campaigns:ranking',
        ]);
    }
}
