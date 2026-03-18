<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\LostSale;
use App\Support\TenantCache;

class LostSaleObserver
{
    public function created(LostSale $lostSale): void
    {
        $this->clearCaches($lostSale->tenant_id);
    }

    public function deleted(LostSale $lostSale): void
    {
        $this->clearCaches($lostSale->tenant_id);
    }

    private function clearCaches(int $tenantId): void
    {
        TenantCache::forgetMany($tenantId, [
            'dashboard:stats',
            'dashboard:lostReasons',
            'reports:overview',
            'reports:lost',
        ]);
    }
}
