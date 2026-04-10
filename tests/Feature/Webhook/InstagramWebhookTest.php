<?php

declare(strict_types=1);

namespace Tests\Feature\Webhook;

use Tests\TestCase;

class InstagramWebhookTest extends TestCase
{
    // ── GET verify ─────────────────────────────────────────────────────

    public function test_verify_returns_challenge_on_valid_token(): void
    {
        config(['services.instagram.webhook_verify_token' => 'ig-verify']);

        $response = $this->get('/api/webhook/instagram?' . http_build_query([
            'hub_mode'         => 'subscribe',
            'hub_verify_token' => 'ig-verify',
            'hub_challenge'    => 'ig_challenge_456',
        ]));

        $response->assertOk();
        $response->assertSee('ig_challenge_456');
    }

    public function test_verify_rejects_invalid_token(): void
    {
        config(['services.instagram.webhook_verify_token' => 'correct']);

        $response = $this->get('/api/webhook/instagram?' . http_build_query([
            'hub_mode'         => 'subscribe',
            'hub_verify_token' => 'wrong',
            'hub_challenge'    => 'test',
        ]));

        $response->assertStatus(403);
    }

    // ── POST webhook ───────────────────────────────────────────────────

    public function test_post_without_signature_returns_403(): void
    {
        config(['services.instagram.client_secret' => 'ig-secret']);

        $response = $this->postJson('/api/webhook/instagram', [
            'object' => 'instagram',
            'entry'  => [],
        ]);

        $response->assertStatus(403);
    }

    public function test_post_with_valid_signature_returns_200(): void
    {
        $secret = 'ig-test-secret';
        config(['services.instagram.client_secret' => $secret]);

        $payload = json_encode([
            'object' => 'instagram',
            'entry'  => [],
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $response = $this->call('POST', '/api/webhook/instagram', [], [], [], [
            'CONTENT_TYPE'           => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $payload);

        $response->assertOk();
    }

    public function test_non_instagram_object_is_ignored(): void
    {
        $secret = 'ig-test';
        config(['services.instagram.client_secret' => $secret]);

        $payload = json_encode(['object' => 'page', 'entry' => []]);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $response = $this->call('POST', '/api/webhook/instagram', [], [], [], [
            'CONTENT_TYPE'           => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $payload);

        $response->assertOk();
    }
}
