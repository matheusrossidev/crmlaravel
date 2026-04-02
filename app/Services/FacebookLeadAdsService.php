<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookLeadAdsService
{
    private string $baseUrl;

    public function __construct(
        private readonly string $userAccessToken,
    ) {
        $version = config('services.facebook.api_version', 'v21.0');
        $this->baseUrl = "https://graph.facebook.com/{$version}";
    }

    /**
     * List Facebook Pages the user manages.
     * Tries /me/accounts first. If empty (Facebook Business Login),
     * falls back to searching by page name/URL.
     * Returns [{id, name, access_token}]
     */
    public function getPages(): array
    {
        $response = Http::withToken($this->userAccessToken)
            ->timeout(15)
            ->get($this->baseUrl . '/me/accounts', [
                'fields' => 'id,name,access_token',
                'limit'  => 100,
            ]);

        $pages = $response->successful() ? $response->json('data', []) : [];

        if (! empty($pages)) {
            return $pages;
        }

        Log::info('FacebookLeadAds: /me/accounts empty (Business Login), use searchPage instead');
        return [];
    }

    /**
     * Search/validate a page by ID or URL.
     * Works with Facebook Business Login where /me/accounts is empty.
     */
    public function searchPage(string $input): ?array
    {
        // Extract page ID from URL or use directly
        $pageId = $this->extractPageId($input);
        if (! $pageId) {
            return null;
        }

        $response = Http::withToken($this->userAccessToken)
            ->timeout(15)
            ->get($this->baseUrl . "/{$pageId}", [
                'fields' => 'id,name,access_token',
            ]);

        if (! $response->successful()) {
            Log::warning('FacebookLeadAds: searchPage failed', ['input' => $input, 'status' => $response->status()]);
            return null;
        }

        return $response->json();
    }

    /**
     * Extract page ID from various input formats.
     * Accepts: numeric ID, facebook.com/pageId, facebook.com/pageName
     */
    private function extractPageId(string $input): ?string
    {
        $input = trim($input);

        // Pure numeric ID
        if (ctype_digit($input)) {
            return $input;
        }

        // URL: extract last path segment
        if (str_contains($input, 'facebook.com')) {
            $path = parse_url($input, PHP_URL_PATH);
            $segments = array_filter(explode('/', trim($path ?? '', '/')));
            $last = end($segments);
            return $last ?: null;
        }

        // Page name/slug — try as-is
        return $input;
    }

    /**
     * List lead gen forms for a Page.
     * Returns [{id, name, status, questions[{key, label, type}]}]
     */
    public function getPageForms(string $pageId, string $pageAccessToken): array
    {
        $response = Http::withToken($pageAccessToken)
            ->timeout(15)
            ->get($this->baseUrl . "/{$pageId}/leadgen_forms", [
                'fields' => 'id,name,status,questions',
                'limit'  => 100,
            ]);

        if (! $response->successful()) {
            Log::warning('FacebookLeadAds: getPageForms failed', ['page' => $pageId, 'status' => $response->status()]);
            return [];
        }

        return $response->json('data', []);
    }

    /**
     * Subscribe a Page to leadgen webhook events.
     */
    public function subscribePage(string $pageId, string $pageAccessToken): bool
    {
        $response = Http::withToken($pageAccessToken)
            ->timeout(15)
            ->asForm()
            ->post($this->baseUrl . "/{$pageId}/subscribed_apps", [
                'subscribed_fields' => 'leadgen',
            ]);

        if (! $response->successful()) {
            Log::warning('FacebookLeadAds: subscribePage failed', ['page' => $pageId, 'body' => $response->body()]);
            return false;
        }

        return (bool) $response->json('success', false);
    }

    /**
     * Fetch lead data by leadgen ID.
     * Returns {id, field_data[{name, values}], form_id, ad_id, platform, created_time}
     */
    public function getLeadData(string $leadgenId, string $pageAccessToken): ?array
    {
        $response = Http::withToken($pageAccessToken)
            ->timeout(15)
            ->get($this->baseUrl . "/{$leadgenId}", [
                'fields' => 'id,field_data,form_id,ad_id,created_time,platform',
            ]);

        if (! $response->successful()) {
            Log::warning('FacebookLeadAds: getLeadData failed', ['leadgen' => $leadgenId, 'status' => $response->status()]);
            return null;
        }

        return $response->json();
    }

    /**
     * Get campaign info from an ad ID (for UTM tracking).
     * Returns {campaign_name, campaign_id} or null.
     */
    public function getCampaignFromAd(string $adId): ?array
    {
        $response = Http::withToken($this->userAccessToken)
            ->timeout(15)
            ->get($this->baseUrl . "/{$adId}", [
                'fields' => 'campaign_id,campaign{name,objective}',
            ]);

        if (! $response->successful()) {
            Log::debug('FacebookLeadAds: getCampaignFromAd failed', ['ad' => $adId]);
            return null;
        }

        $data = $response->json();
        $campaign = $data['campaign'] ?? null;

        return $campaign ? [
            'campaign_id'   => $data['campaign_id'] ?? null,
            'campaign_name' => $campaign['name'] ?? null,
            'objective'     => $campaign['objective'] ?? null,
        ] : null;
    }

    /**
     * Exchange short-lived token for long-lived (60 days).
     */
    public static function exchangeForLongLivedToken(string $shortToken): ?array
    {
        $version = config('services.facebook.api_version', 'v21.0');
        $response = Http::timeout(15)
            ->get("https://graph.facebook.com/{$version}/oauth/access_token", [
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => config('services.facebook.client_id'),
                'client_secret'     => config('services.facebook.client_secret'),
                'fb_exchange_token' => $shortToken,
            ]);

        if (! $response->successful()) {
            Log::warning('FacebookLeadAds: token exchange failed', ['body' => $response->body()]);
            return null;
        }

        return $response->json(); // {access_token, token_type, expires_in}
    }
}
