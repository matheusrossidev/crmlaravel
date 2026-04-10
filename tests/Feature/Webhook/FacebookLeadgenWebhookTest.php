<?php

declare(strict_types=1);

namespace Tests\Feature\Webhook;

use Tests\TestCase;

class FacebookLeadgenWebhookTest extends TestCase
{
    // ── GET verify ─────────────────────────────────────────────────────

    public function test_verify_returns_challenge(): void
    {
        config(['services.facebook.leadgen_webhook_verify_token' => 'fb-verify']);

        $response = $this->get('/api/webhook/facebook/leadgen?' . http_build_query([
            'hub_mode'         => 'subscribe',
            'hub_verify_token' => 'fb-verify',
            'hub_challenge'    => 'fb_challenge_789',
        ]));

        $response->assertOk();
        $response->assertSee('fb_challenge_789');
    }

    public function test_verify_rejects_wrong_token(): void
    {
        config(['services.facebook.leadgen_webhook_verify_token' => 'correct']);

        $response = $this->get('/api/webhook/facebook/leadgen?' . http_build_query([
            'hub_mode'         => 'subscribe',
            'hub_verify_token' => 'wrong',
            'hub_challenge'    => 'x',
        ]));

        $response->assertStatus(403);
    }

    // ── POST webhook ───────────────────────────────────────────────────

    public function test_post_without_signature_returns_403(): void
    {
        config(['services.facebook.client_secret' => 'fb-secret']);

        $response = $this->postJson('/api/webhook/facebook/leadgen', [
            'object' => 'page',
            'entry'  => [],
        ]);

        $response->assertStatus(403);
    }

    public function test_post_with_valid_signature_returns_200(): void
    {
        $secret = 'fb-test-secret';
        config(['services.facebook.client_secret' => $secret]);

        $payload = json_encode([
            'object' => 'page',
            'entry'  => [],
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $response = $this->call('POST', '/api/webhook/facebook/leadgen', [], [], [], [
            'CONTENT_TYPE'           => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $payload);

        $response->assertOk();
    }

    public function test_non_page_object_returns_200(): void
    {
        $secret = 'fb-test';
        config(['services.facebook.client_secret' => $secret]);

        $payload = json_encode(['object' => 'user', 'entry' => []]);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $response = $this->call('POST', '/api/webhook/facebook/leadgen', [], [], [], [
            'CONTENT_TYPE'           => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $payload);

        $response->assertOk();
    }
}
