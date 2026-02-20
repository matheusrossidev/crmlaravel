<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdSpend;
use App\Models\Campaign;
use App\Models\OAuthConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAdsService
{
    private const OAUTH_URL    = 'https://oauth2.googleapis.com/token';
    private const GADS_URL     = 'https://googleads.googleapis.com/v17';
    private const DEV_TOKEN_KEY = 'GOOGLE_ADS_DEVELOPER_TOKEN';

    public function sync(OAuthConnection $conn): void
    {
        // Renovar token se expirado
        if ($conn->isExpired() && $conn->refresh_token) {
            $refreshed = $this->refreshAccessToken($conn->refresh_token);
            if ($refreshed) {
                $conn->update([
                    'access_token'     => $refreshed['access_token'],
                    'token_expires_at' => now()->addSeconds((int) ($refreshed['expires_in'] ?? 3600)),
                    'status'           => 'active',
                ]);
                $conn->refresh();
            } else {
                $conn->update(['status' => 'expired']);
                return;
            }
        }

        $devToken = env(self::DEV_TOKEN_KEY);
        if (! $devToken) {
            Log::warning('GoogleAdsService: GOOGLE_ADS_DEVELOPER_TOKEN not set in .env');
            return;
        }

        $headers = [
            'Authorization'  => 'Bearer ' . $conn->access_token,
            'developer-token' => $devToken,
        ];

        // 1. Listar clientes acessíveis
        $customersRes = Http::withHeaders($headers)
            ->get(self::GADS_URL . '/customers:listAccessibleCustomers');

        if (! $customersRes->successful()) {
            Log::error('GoogleAdsService: failed to list customers', [
                'tenant_id' => $conn->tenant_id,
                'body'      => $customersRes->body(),
            ]);
            if ($customersRes->status() === 401) {
                $conn->update(['status' => 'expired']);
            }
            return;
        }

        $resourceNames = $customersRes->json('resourceNames', []);

        foreach ($resourceNames as $resourceName) {
            // resourceName = "customers/1234567890"
            $customerId = str_replace('customers/', '', $resourceName);
            $this->syncCustomer($customerId, $headers, $conn->tenant_id);
        }

        $conn->update(['last_sync_at' => now(), 'status' => 'active']);
    }

    private function syncCustomer(string $customerId, array $headers, int $tenantId): void
    {
        // 2. GAQL: buscar campanhas ativas + métricas dos últimos 30 dias
        $query = <<<GAQL
            SELECT
                campaign.id,
                campaign.name,
                campaign.status,
                campaign.advertising_channel_type,
                campaign_budget.amount_micros,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros,
                metrics.ctr,
                metrics.average_cpc,
                metrics.average_cpm,
                metrics.conversions,
                segments.date
            FROM campaign
            WHERE segments.date DURING LAST_30_DAYS
              AND campaign.status != 'REMOVED'
            ORDER BY segments.date DESC
            LIMIT 5000
        GAQL;

        $searchRes = Http::withHeaders($headers)
            ->post(self::GADS_URL . '/customers/' . $customerId . '/googleAds:search', [
                'query' => $query,
            ]);

        if (! $searchRes->successful()) {
            Log::warning('GoogleAdsService: GAQL query failed', [
                'customer_id' => $customerId,
                'body'        => $searchRes->body(),
            ]);
            return;
        }

        foreach ($searchRes->json('results', []) as $row) {
            $gc      = $row['campaign'] ?? [];
            $metrics = $row['metrics'] ?? [];
            $date    = $row['segments']['date'] ?? null;

            if (empty($gc['id']) || ! $date) {
                continue;
            }

            $status = strtolower(str_replace('_', '', $gc['status'] ?? 'UNKNOWN'));
            // ENABLED → enabled, PAUSED → paused

            $budgetMicros = $row['campaignBudget']['amountMicros'] ?? null;
            $budgetDaily  = $budgetMicros !== null ? (float) $budgetMicros / 1_000_000 : null;

            $campaign = Campaign::updateOrCreate(
                ['tenant_id' => $tenantId, 'external_id' => (string) $gc['id']],
                [
                    'platform'     => 'google',
                    'name'         => $gc['name'] ?? 'Campanha sem nome',
                    'status'       => match($gc['status'] ?? '') {
                        'ENABLED' => 'active',
                        'PAUSED'  => 'paused',
                        default   => 'archived',
                    },
                    'budget_daily' => $budgetDaily,
                    'last_sync_at' => now(),
                ]
            );

            $costMicros = (float) ($metrics['costMicros'] ?? 0);

            AdSpend::updateOrCreate(
                ['tenant_id' => $tenantId, 'campaign_id' => $campaign->id, 'date' => $date],
                [
                    'spend'       => round($costMicros / 1_000_000, 4),
                    'impressions' => (int)   ($metrics['impressions'] ?? 0),
                    'clicks'      => (int)   ($metrics['clicks'] ?? 0),
                    'conversions' => (int)   ($metrics['conversions'] ?? 0),
                    'cpc'         => round((float) ($metrics['averageCpc'] ?? 0) / 1_000_000, 4),
                    'cpm'         => round((float) ($metrics['averageCpm'] ?? 0) / 1_000_000, 4),
                    'ctr'         => round((float) ($metrics['ctr'] ?? 0) * 100, 4),
                ]
            );
        }
    }

    private function refreshAccessToken(string $refreshToken): ?array
    {
        $res = Http::asForm()->post(self::OAUTH_URL, [
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);

        return $res->successful() ? $res->json() : null;
    }
}
