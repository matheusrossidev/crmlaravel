<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncCampaignsJob;
use App\Models\OAuthConnection;
use App\Models\Tenant;
use Illuminate\Console\Command;

class SyncCampaignsCommand extends Command
{
    protected $signature   = 'campaigns:sync {--platform= : facebook or google (omit for all)}';
    protected $description = 'Sync campaigns from Facebook Ads and Google Ads for all tenants';

    public function handle(): int
    {
        $platform = $this->option('platform');

        $query = OAuthConnection::whereIn('status', ['active', 'expired'])
            ->with('tenant');

        if ($platform) {
            $query->where('platform', $platform);
        }

        $connections = $query->get();

        if ($connections->isEmpty()) {
            $this->info('No active OAuth connections found.');
            return self::SUCCESS;
        }

        foreach ($connections as $conn) {
            if (! $conn->tenant) {
                continue;
            }

            SyncCampaignsJob::dispatch($conn->tenant, $conn->platform);
            $this->line("Dispatched sync for tenant [{$conn->tenant->id}] / {$conn->platform}");
        }

        $this->info("Dispatched {$connections->count()} sync job(s).");

        return self::SUCCESS;
    }
}
