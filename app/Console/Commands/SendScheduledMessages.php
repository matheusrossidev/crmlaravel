<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ScheduledMessage;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\WahaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledMessages extends Command
{
    protected $signature   = 'whatsapp:send-scheduled';
    protected $description = 'Envia mensagens WhatsApp agendadas cujo send_at já passou';

    public function handle(): int
    {
        // CRITICO: withoutGlobalScope('tenant') porque o command roda em CLI sem
        // user logado. Sem isso, dependendo do flow do trait BelongsToTenant +
        // eager loading, queries podem retornar vazio silenciosamente.
        // Lead/Conversation tambem precisam de withoutGlobalScope no eager load
        // porque tem BelongsToTenant.
        $pending = ScheduledMessage::withoutGlobalScope('tenant')
            ->with([
                'lead' => fn ($q) => $q->withoutGlobalScope('tenant'),
                'conversation' => fn ($q) => $q->withoutGlobalScope('tenant'),
            ])
            ->pending()
            ->get();

        // Heartbeat sempre, mesmo quando vazio — confirma que o cron roda
        Log::channel('whatsapp')->info('SendScheduledMessages: tick', [
            'pending_count' => $pending->count(),
            'now'           => now()->toIso8601String(),
            'app_tz'        => config('app.timezone'),
        ]);

        if ($pending->isEmpty()) {
            return self::SUCCESS;
        }

        $this->info("Processando {$pending->count()} mensagem(ns) agendada(s)...");

        foreach ($pending as $scheduled) {
            try {
                $this->dispatch($scheduled);
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->error('SendScheduledMessages: erro inesperado', [
                    'scheduled_id' => $scheduled->id,
                    'tenant_id'    => $scheduled->tenant_id,
                    'error'        => $e->getMessage(),
                    'trace'        => $e->getTraceAsString(),
                ]);
                $scheduled->update(['status' => 'failed', 'error' => $e->getMessage()]);
            }
        }

        return self::SUCCESS;
    }

    private function dispatch(ScheduledMessage $scheduled): void
    {
        // Resolver instancia: explicita do scheduled > conversa > primary do tenant
        $instance = null;
        if ($scheduled->instance_id) {
            $instance = WhatsappInstance::withoutGlobalScope('tenant')
                ->where('id', $scheduled->instance_id)
                ->where('status', 'connected')
                ->first();
        }
        if (! $instance && $scheduled->conversation?->instance_id) {
            $instance = WhatsappInstance::withoutGlobalScope('tenant')
                ->where('id', $scheduled->conversation->instance_id)
                ->where('status', 'connected')
                ->first();
        }
        if (! $instance) {
            $instance = WhatsappInstance::resolvePrimary($scheduled->tenant_id);
        }

        if (! $instance) {
            $scheduled->update([
                'status' => 'failed',
                'error'  => 'WhatsApp desconectado. Verifique a integração em Configurações.',
            ]);
            return;
        }

        $lead         = $scheduled->lead;
        $conversation = $scheduled->conversation;
        $rawPhone     = preg_replace('/\D/', '', $lead->phone ?? '');

        if (! $rawPhone) {
            $scheduled->update([
                'status' => 'failed',
                'error'  => 'Lead sem número de telefone.',
            ]);
            return;
        }

        // Constrói chatId com a mesma lógica do WhatsappMessageController
        $chatId = $rawPhone . '@c.us';

        if ($conversation) {
            if ($conversation->is_group) {
                $chatId = $rawPhone . '@g.us';
            } else {
                $sampleId = WhatsappMessage::withoutGlobalScope('tenant')
                    ->where('conversation_id', $conversation->id)
                    ->whereNotNull('waha_message_id')
                    ->where('direction', 'inbound')
                    ->latest('sent_at')
                    ->value('waha_message_id');

                if ($sampleId && preg_match('/^(?:true|false)_(.+@[\w.]+)_/', $sampleId, $m)) {
                    $jid = $m[1];
                    if (str_ends_with($jid, '@lid')) {
                        $chatId = preg_replace('/[:@].+$/', '', $jid) . '@lid';
                    } else {
                        $chatId = preg_replace('/[:@].+$/', '', $jid) . '@c.us';
                    }
                }
            }
        }

        $waha   = \App\Services\WhatsappServiceFactory::for($instance);
        $result = [];

        switch ($scheduled->type) {
            case 'text':
                $result = $waha->sendText($chatId, $scheduled->body ?? '');
                break;

            case 'image':
                $absPath = storage_path('app/public/' . $scheduled->media_path);
                $result  = $waha->sendImageBase64($chatId, $absPath, $scheduled->media_mime ?? 'image/jpeg', $scheduled->body ?? '');
                break;

            case 'document':
                $absPath = storage_path('app/public/' . $scheduled->media_path);
                $result  = $waha->sendFileBase64($chatId, $absPath, $scheduled->media_mime ?? 'application/octet-stream', $scheduled->media_filename ?? 'arquivo', $scheduled->body ?? '');
                break;
        }

        if (isset($result['error'])) {
            $scheduled->update([
                'status' => 'failed',
                'error'  => $result['error'],
            ]);
            return;
        }

        // Salva como WhatsappMessage outbound
        $wahaMessageId = $result['id'] ?? null;

        WhatsappMessage::withoutGlobalScope('tenant')->create([
            'tenant_id'       => $scheduled->tenant_id,
            'conversation_id' => $conversation?->id,
            'waha_message_id' => $wahaMessageId,
            'direction'       => 'outbound',
            'type'            => $scheduled->type,
            'body'            => $scheduled->body,
            'media_url'       => $scheduled->media_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($scheduled->media_path) : null,
            'media_mime'      => $scheduled->media_mime,
            'media_filename'  => $scheduled->media_filename,
            'user_id'         => $scheduled->created_by,
            'sent_by'         => 'scheduled',
            'ack'             => 'sent',
            'sent_at'         => now(),
        ]);

        if ($conversation) {
            $conversation->update(['last_message_at' => now()]);
        }

        $scheduled->update(['status' => 'sent', 'sent_at' => now()]);

        $this->line("  ✓ Mensagem #{$scheduled->id} enviada para lead #{$scheduled->lead_id}");
    }
}
