<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessWahaWebhook;
use App\Models\WhatsappInstance;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $request->json()->all();
        $session = $payload['session'] ?? null;
        $event   = $payload['event'] ?? 'unknown';
        $from    = $payload['payload']['from'] ?? 'n/a';
        $msgId   = $payload['payload']['id'] ?? 'n/a';

        Log::channel('whatsapp')->info('Webhook recebido', [
            'event'   => $event,
            'session' => $session,
            'from'    => $from,
            'msg_id'  => $msgId,
        ]);

        if (! $session) {
            return response('', 200);
        }

        // Lookup primário: session_name exato (fluxo normal após connectWhatsapp())
        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('session_name', $session)
            ->first();

        // Fallback 1: WAHA enviou ID interno (ex: sessão criada manualmente no painel WAHA)
        // Tenta pelo número conectado (me.id = "5511999...@c.us")
        if (! $instance) {
            $mePhone = str_replace(['@c.us', '@s.whatsapp.net', '@lid'], '', $payload['me']['id'] ?? '');
            if ($mePhone) {
                $instance = WhatsappInstance::withoutGlobalScope('tenant')
                    ->where('phone_number', $mePhone)
                    ->first();
            }
        }

        // Fallback 2: se só existe uma instância cadastrada, é ela
        if (! $instance && WhatsappInstance::withoutGlobalScope('tenant')->count() === 1) {
            $instance = WhatsappInstance::withoutGlobalScope('tenant')->first();
        }

        if (! $instance) {
            return response('', 200);
        }

        // Garante que session_name está sincronizado com o WAHA
        if ($instance->session_name !== $session) {
            $instance->updateQuietly(['session_name' => $session]);
        }

        // dispatchSync: processa imediatamente, sem depender do queue worker.
        // Envolvido em try/catch para que erros internos (ex: migration pendente,
        // DB indisponível) não retornem 500 ao WAHA — o que faria o WAHA parar
        // de enviar webhooks para essa sessão.
        try {
            ProcessWahaWebhook::dispatchSync($payload);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Webhook processamento falhou', [
                'error'   => $e->getMessage(),
                'event'   => $event,
                'session' => $session,
            ]);
        }

        return response('', 200);
    }
}
