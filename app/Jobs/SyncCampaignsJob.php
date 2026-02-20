<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\OAuthConnection;
use App\Models\Tenant;
use App\Services\FacebookAdsService;
use App\Services\GoogleAdsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncCampaignsJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $platform,
    ) {}

    public function handle(FacebookAdsService $facebookService, GoogleAdsService $googleService): void
    {
        $conn = OAuthConnection::where('tenant_id', $this->tenant->id)
            ->where('platform', $this->platform)
            ->whereIn('status', ['active', 'expired'])
            ->first();

        if (! $conn) {
            Log::info("SyncCampaignsJob: no active connection for tenant {$this->tenant->id} / {$this->platform}");
            return;
        }

        match ($this->platform) {
            'facebook' => $facebookService->sync($conn),
            'google'   => $googleService->sync($conn),
            default    => Log::warning("SyncCampaignsJob: unknown platform '{$this->platform}'"),
        };
    }
}
