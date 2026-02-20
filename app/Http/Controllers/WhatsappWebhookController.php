<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessWahaWebhook;
use App\Models\WhatsappInstance;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsappWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $request->json()->all();
        $session = $payload['session'] ?? null;

        if (! $session) {
            return response('', 200);
        }

        // Verificar se a sessão existe (sem tenant scope)
        $exists = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('session_name', $session)
            ->exists();

        if (! $exists) {
            return response('', 200);
        }

        ProcessWahaWebhook::dispatch($payload)->onQueue('whatsapp');

        return response('', 200);
    }
}
