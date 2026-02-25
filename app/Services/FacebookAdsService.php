<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdSpend;
use App\Models\Campaign;
use App\Models\OAuthConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookAdsService
{
    private const GRAPH_URL = 'https://graph.facebook.com/v21.0';

    public function sync(OAuthConnection $conn): void
    {
        $token     = $conn->access_token;
        $tenantId  = $conn->tenant_id;

        // 1. Listar ad accounts
        $accountsRes = Http::get(self::GRAPH_URL . '/me/adaccounts', [
            'fields'       => 'name,account_id,account_status',
            'access_token' => $token,
        ]);

        if (! $accountsRes->successful()) {
            Log::error('FacebookAdsService: failed to fetch ad accounts', [
                'tenant_id' => $tenantId,
                'body'      => $accountsRes->body(),
            ]);
            $conn->update(['status' => 'expired']);
            return;
        }

        $accounts = $accountsRes->json('data', []);

        foreach ($accounts as $account) {
            $this->syncAccount($account['id'], $token, $tenantId);
        }

        $conn->update(['last_sync_at' => now(), 'status' => 'active']);
    }

    private function syncAccount(string $accountId, string $token, int $tenantId): void
    {
        // 2. Listar campanhas da conta
        $campaignsRes = Http::get(self::GRAPH_URL . '/' . $accountId . '/campaigns', [
            'fields'       => 'id,name,status,objective,daily_budget,lifetime_budget',
            'access_token' => $token,
            'limit'        => 200,
        ]);

        if (! $campaignsRes->successful()) {
            Log::warning('FacebookAdsService: failed to fetch campaigns for account', [
                'account_id' => $accountId,
            ]);
            return;
        }

        foreach ($campaignsRes->json('data', []) as $fbCampaign) {
            $campaign = Campaign::updateOrCreate(
                ['tenant_id' => $tenantId, 'external_id' => $fbCampaign['id']],
                [
                    'platform'         => 'facebook',
                    'name'             => $fbCampaign['name'],
                    'status'           => strtolower($fbCampaign['status'] ?? 'unknown'),
                    'objective'        => $fbCampaign['objective'] ?? null,
                    'budget_daily'     => isset($fbCampaign['daily_budget'])
                        ? (float) $fbCampaign['daily_budget'] / 100
                        : null,
                    'budget_lifetime'  => isset($fbCampaign['lifetime_budget'])
                        ? (float) $fbCampaign['lifetime_budget'] / 100
                        : null,
                    'last_sync_at'     => now(),
                ]
            );

            // 3. Buscar insights (últimos 30 dias, por dia)
            $insightsRes = Http::get(self::GRAPH_URL . '/' . $fbCampaign['id'] . '/insights', [
                'fields'         => 'spend,impressions,clicks,cpc,cpm,ctr',
                'date_preset'    => 'last_30d',
                'time_increment' => 1,
                'access_token'   => $token,
            ]);

            if (! $insightsRes->successful()) {
                continue;
            }

            foreach ($insightsRes->json('data', []) as $insight) {
                AdSpend::updateOrCreate(
                    ['tenant_id' => $tenantId, 'campaign_id' => $campaign->id, 'date' => $insight['date_start']],
                    [
                        'spend'       => (float) ($insight['spend'] ?? 0),
                        'impressions' => (int)   ($insight['impressions'] ?? 0),
                        'clicks'      => (int)   ($insight['clicks'] ?? 0),
                        'cpc'         => (float) ($insight['cpc'] ?? 0),
                        'cpm'         => (float) ($insight['cpm'] ?? 0),
                        'ctr'         => (float) ($insight['ctr'] ?? 0),
                        'conversions' => 0,
                    ]
                );
            }
        }
    }
}
