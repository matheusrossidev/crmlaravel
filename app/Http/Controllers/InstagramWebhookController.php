<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessInstagramWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    /**
     * GET /api/webhook/instagram
     * Meta platform verification challenge.
     */
    public function verify(Request $request): Response|string
    {
        $mode      = $request->get('hub_mode');
        $token     = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($mode === 'subscribe'
            && $token === config('services.instagram.webhook_verify_token')
        ) {
            Log::channel('instagram')->info('Webhook verified by Meta');
            return response($challenge ?? '', 200);
        }

        Log::channel('instagram')->warning('Webhook verification failed', [
            'mode'  => $mode,
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * POST /api/webhook/instagram
     * Receives events from the Meta Webhooks platform.
     */
    public function handle(Request $request): Response
    {
        // Validate Meta's HMAC-SHA256 signature
        if (! $this->verifySignature($request)) {
            Log::channel('instagram')->warning('Webhook signature inválida', [
                'ip' => $request->ip(),
            ]);
            return response('Invalid signature', 403);
        }

        $payload = $request->json()->all();

        Log::channel('instagram')->info('Webhook recebido', [
            'object' => $payload['object'] ?? null,
            'entries'=> count($payload['entry'] ?? []),
        ]);

        // Only process instagram_business events
        if (($payload['object'] ?? '') !== 'instagram') {
            return response('', 200);
        }

        // Dispatch async: retorna 200 imediatamente ao Meta, processa em background.
        ProcessInstagramWebhook::dispatch($payload)->onQueue('webhooks');

        return response('', 200);
    }

    /**
     * Verify Meta's X-Hub-Signature-256 HMAC header.
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256', '');
        $secret    = config('services.instagram.client_secret', '');

        if ($signature === '' || $secret === '') {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
