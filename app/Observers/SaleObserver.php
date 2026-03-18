<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Sale;
use App\Support\TenantCache;

class SaleObserver
{
    public function created(Sale $sale): void
    {
        $this->clearCaches($sale->tenant_id);
    }

    public function updated(Sale $sale): void
    {
        $this->clearCaches($sale->tenant_id);
    }

    public function deleted(Sale $sale): void
    {
        $this->clearCaches($sale->tenant_id);
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
