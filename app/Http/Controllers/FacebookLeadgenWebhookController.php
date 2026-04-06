<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessFacebookLeadgenWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class FacebookLeadgenWebhookController extends Controller
{
    /**
     * Meta webhook verification (GET).
     */
    public function verify(Request $request): Response
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.facebook.leadgen_webhook_verify_token')) {
            Log::info('FacebookLeadgen: webhook verified');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Meta webhook handler (POST).
     */
    public function handle(Request $request): Response
    {
        Log::info('FacebookLeadgen: webhook received', [
            'has_signature' => $request->hasHeader('X-Hub-Signature-256'),
            'body_size'     => strlen($request->getContent()),
            'object'        => $request->input('object'),
        ]);

        if (! $this->verifySignature($request)) {
            Log::warning('FacebookLeadgen: invalid signature', [
                'signature' => $request->header('X-Hub-Signature-256'),
                'body_preview' => substr($request->getContent(), 0, 200),
            ]);
            return response('Invalid signature', 403);
        }

        $payload = $request->all();

        if (($payload['object'] ?? '') !== 'page') {
            Log::info('FacebookLeadgen: ignoring non-page object', ['object' => $payload['object'] ?? 'null']);
            return response('OK', 200);
        }

        $dispatched = 0;
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'leadgen') {
                    continue;
                }

                $value = $change['value'] ?? [];
                if (empty($value['leadgen_id']) || empty($value['form_id'])) {
                    Log::warning('FacebookLeadgen: missing leadgen_id or form_id', ['value' => $value]);
                    continue;
                }

                try {
                    ProcessFacebookLeadgenWebhook::dispatch([
                        'leadgen_id'   => (string) $value['leadgen_id'],
                        'form_id'      => (string) $value['form_id'],
                        'page_id'      => (string) ($value['page_id'] ?? $entry['id'] ?? ''),
                        'ad_id'        => (string) ($value['ad_id'] ?? ''),
                        'created_time' => (int) ($value['created_time'] ?? 0),
                    ]);
                    $dispatched++;
                } catch (\Throwable $e) {
                    Log::error('FacebookLeadgen: dispatch failed', ['error' => $e->getMessage()]);
                }
            }
        }

        Log::info('FacebookLeadgen: webhook processed', ['dispatched' => $dispatched]);
        return response('OK', 200);
    }

    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        if (! $signature) {
            return false;
        }

        $secret   = config('services.facebook.client_secret');
        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
