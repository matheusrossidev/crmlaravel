<?php

declare(strict_types=1);

namespace Tests\Feature\Webhook;

use Tests\TestCase;

class WhatsappCloudWebhookTest extends TestCase
{
    // ── GET verify challenge ───────────────────────────────────────────

    public function test_verify_challenge_returns_challenge_on_valid_token(): void
    {
        config(['services.whatsapp_cloud.verify_token' => 'test-verify']);

        $response = $this->get('/api/webhook/whatsapp-cloud?' . http_build_query([
            'hub_mode'         => 'subscribe',
            'hub_verify_token' => 'test-verify',
            'hub_challenge'    => 'challenge_123',
        ]));

        $response->assertOk();
        $response->assertSee('challenge_123');
    }

    public function test_verify_rejects_invalid_token(): void
    {
        config(['services.whatsapp_cloud.verify_token' => 'correct']);

        $response = $this->get('/api/webhook/whatsapp-cloud?' . http_build_query([
            'hub_mode'         => 'subscribe',
            'hub_verify_token' => 'wrong',
            'hub_challenge'    => 'challenge_123',
        ]));

        $response->assertStatus(403);
    }

    // ── POST webhook ───────────────────────────────────────────────────

    public function test_post_without_signature_returns_403(): void
    {
        config(['services.whatsapp_cloud.app_secret' => 'secret123']);

        $response = $this->postJson('/api/webhook/whatsapp-cloud', [
            'object' => 'whatsapp_business_account',
            'entry'  => [],
        ]);

        $response->assertStatus(403);
    }

    public function test_post_with_invalid_signature_returns_403(): void
    {
        config(['services.whatsapp_cloud.app_secret' => 'secret123']);

        $response = $this->postJson('/api/webhook/whatsapp-cloud', [
            'object' => 'whatsapp_business_account',
            'entry'  => [],
        ], [
            'X-Hub-Signature-256' => 'sha256=invalidsignature',
        ]);

        $response->assertStatus(403);
    }

    public function test_post_with_valid_signature_returns_200(): void
    {
        $secret = 'test-app-secret';
        config(['services.whatsapp_cloud.app_secret' => $secret]);

        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry'  => [],
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $response = $this->call('POST', '/api/webhook/whatsapp-cloud', [], [], [], [
            'CONTENT_TYPE'           => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $payload);

        $response->assertOk();
    }

    public function test_non_whatsapp_object_is_ignored(): void
    {
        $secret = 'test-secret';
        config(['services.whatsapp_cloud.app_secret' => $secret]);

        $payload = json_encode(['object' => 'page', 'entry' => []]);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $response = $this->call('POST', '/api/webhook/whatsapp-cloud', [], [], [], [
            'CONTENT_TYPE'           => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $payload);

        $response->assertOk();
    }
}
