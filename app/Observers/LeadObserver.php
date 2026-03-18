<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Lead;
use App\Support\TenantCache;

class LeadObserver
{
    public function created(Lead $lead): void
    {
        $this->clearCaches($lead->tenant_id);
    }

    public function updated(Lead $lead): void
    {
        $this->clearCaches($lead->tenant_id);
    }

    public function deleted(Lead $lead): void
    {
        $this->clearCaches($lead->tenant_id);
    }

    private function clearCaches(int $tenantId): void
    {
        TenantCache::forgetMany($tenantId, [
            'dashboard:stats',
            'dashboard:monthly',
            'dashboard:stages',
            'dashboard:sources',
            'dashboard:daySource',
            'reports:overview',
            'reports:funnel',
            'reports:vendors',
            'reports:sourceConversion',
            'reports:teamActivity',
            'campaigns:utm',
            'campaigns:ranking',
        ]);
    }
}
