<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramService
{
    private string $accessToken;
    private string $apiVersion = 'v25.0';
    private string $baseUrl    = 'https://graph.instagram.com';

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    // ── Messaging ─────────────────────────────────────────────────────────────

    /**
     * Send a text DM to an Instagram user identified by IGSID.
     * Requires instagram_business_manage_messages permission.
     */
    public function sendMessage(string $igsid, string $text): array
    {
        return $this->post('/me/messages', [
            'recipient' => ['id' => $igsid],
            'message'   => ['text' => $text],
        ]);
    }

    // ── Profile ───────────────────────────────────────────────────────────────

    /**
     * Fetch basic profile info for an IGSID.
     * Fields available: name, username, profile_pic
     */
    public function getProfile(string $igsid): array
    {
        return $this->get("/{$igsid}", [
            'fields' => 'name,username,profile_pic',
        ]);
    }

    /**
     * Returns the profile picture URL or null on failure.
     */
    public function getProfilePicture(string $igsid): ?string
    {
        try {
            $data = $this->getProfile($igsid);
            return $data['profile_pic'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Account ───────────────────────────────────────────────────────────────

    /**
     * Fetch the connected Instagram Business account (me).
     */
    public function getMe(): array
    {
        return $this->get('/me', ['fields' => 'id,username,profile_picture_url,name']);
    }

    /**
     * Subscribe this account to receive webhook events (required after OAuth).
     * Without this call, Meta does NOT send DM webhooks for this account.
     * POST /me/subscribed_apps?subscribed_fields=messages
     */
    public function subscribeToWebhooks(): array
    {
        return $this->post('/me/subscribed_apps', [
            'subscribed_fields' => 'messages',
        ]);
    }

    // ── Token exchange ────────────────────────────────────────────────────────

    /**
     * Exchange a short-lived token for a long-lived token (60 days).
     */
    public static function exchangeToken(string $shortLived): array
    {
        $response = Http::get('https://graph.facebook.com/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => config('services.instagram.client_id'),
            'client_secret'     => config('services.instagram.client_secret'),
            'fb_exchange_token' => $shortLived,
        ]);

        return $response->successful() ? ($response->json() ?? []) : [];
    }

    // ── HTTP helpers ──────────────────────────────────────────────────────────

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl("{$this->baseUrl}/{$this->apiVersion}")
            ->acceptJson()
            ->timeout(30);
    }

    private function get(string $path, array $query = []): array
    {
        $query['access_token'] = $this->accessToken;
        $response = $this->client()->get($path, $query);

        if ($response->failed()) {
            Log::channel('instagram')->warning('InstagramService GET failed', [
                'path'   => $path,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return ['error' => true, 'status' => $response->status(), 'body' => $response->body()];
        }

        return $response->json() ?? [];
    }

    private function post(string $path, array $data): array
    {
        $data['access_token'] = $this->accessToken;
        $response = $this->client()->post($path, $data);

        if ($response->failed()) {
            Log::channel('instagram')->warning('InstagramService POST failed', [
                'path'   => $path,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return ['error' => true, 'status' => $response->status(), 'body' => $response->body()];
        }

        return $response->json() ?? [];
    }
}
