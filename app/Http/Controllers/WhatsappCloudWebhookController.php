<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessWhatsappCloudWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Recebe webhooks da WhatsApp Cloud API oficial da Meta.
 *
 * Endpoint registrado no Meta Developer Console:
 *   GET  /api/webhook/whatsapp-cloud  → verify (handshake inicial)
 *   POST /api/webhook/whatsapp-cloud  → handle  (mensagens recebidas + status)
 *
 * Formato do payload (Cloud API):
 * {
 *   "object": "whatsapp_business_account",
 *   "entry": [{
 *     "id": "WABA_ID",
 *     "changes": [{
 *       "value": {
 *         "messaging_product": "whatsapp",
 *         "metadata": { "display_phone_number", "phone_number_id" },
 *         "contacts": [{ "profile": { "name" }, "wa_id" }],
 *         "messages": [{ "from", "id", "timestamp", "type", "text": { "body" } }],
 *         "statuses": [{ "id", "status", "timestamp", "recipient_id" }]
 *       },
 *       "field": "messages"
 *     }]
 *   }]
 * }
 */
class WhatsappCloudWebhookController extends Controller
{
    /**
     * Handshake inicial — Meta valida que o endpoint é nosso.
     * GET com query: hub.mode=subscribe, hub.verify_token=X, hub.challenge=Y
     * Devemos retornar o challenge se o token bater com o configurado.
     */
    public function verify(Request $request): Response
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $expected = (string) config('services.whatsapp_cloud.verify_token');

        if ($mode === 'subscribe' && $token === $expected && $expected !== '') {
            Log::channel('whatsapp')->info('WhatsappCloud: webhook verified');
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::channel('whatsapp')->warning('WhatsappCloud: webhook verify failed', [
            'mode'  => $mode,
            'token' => $token,
        ]);
        return response('Forbidden', 403);
    }

    /**
     * POST com o payload da mensagem.
     * Validamos signature HMAC-SHA256 com o app_secret do Meta Developer.
     * Retornamos sempre 200 (mesmo em erro) pra Meta não retentar agressivamente.
     */
    public function handle(Request $request): Response
    {
        Log::channel('whatsapp')->info('WhatsappCloud: webhook received', [
            'has_signature' => $request->hasHeader('X-Hub-Signature-256'),
            'body_size'     => strlen($request->getContent()),
        ]);

        if (! $this->verifySignature($request)) {
            Log::channel('whatsapp')->warning('WhatsappCloud: invalid signature', [
                'signature_header' => $request->header('X-Hub-Signature-256'),
                'body_preview'     => substr($request->getContent(), 0, 200),
            ]);
            return response('Invalid signature', 403);
        }

        $payload = $request->all();

        if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
            Log::channel('whatsapp')->info('WhatsappCloud: ignoring non-WABA object', [
                'object' => $payload['object'] ?? 'null',
            ]);
            return response('OK', 200);
        }

        try {
            ProcessWhatsappCloudWebhook::dispatchSync($payload);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('WhatsappCloud: processing failed', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 1000),
            ]);
        }

        return response('OK', 200);
    }

    /**
     * Valida X-Hub-Signature-256 contra HMAC-SHA256 do raw body com app_secret.
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        if (! $signature) {
            return false;
        }

        $secret = (string) config('services.whatsapp_cloud.app_secret');
        if ($secret === '') {
            Log::channel('whatsapp')->error('WhatsappCloud: app_secret not configured');
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
        return hash_equals($expected, $signature);
    }
}
