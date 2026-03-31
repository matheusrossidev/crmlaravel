<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\EventReminder;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\WahaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendEventReminders extends Command
{
    protected $signature   = 'whatsapp:send-event-reminders';
    protected $description = 'Envia lembretes de eventos via WhatsApp cujo send_at já passou';

    public function handle(): int
    {
        $pending = EventReminder::with(['lead', 'conversation'])
            ->pending()
            ->get();

        if ($pending->isEmpty()) {
            return self::SUCCESS;
        }

        $this->info("Processando {$pending->count()} lembrete(s) de evento...");

        foreach ($pending as $reminder) {
            try {
                $this->dispatch($reminder);
            } catch (\Throwable $e) {
                Log::error('SendEventReminders: erro inesperado', [
                    'reminder_id' => $reminder->id,
                    'error'       => $e->getMessage(),
                ]);
                $reminder->update(['status' => 'failed', 'error' => $e->getMessage()]);
            }
        }

        return self::SUCCESS;
    }

    private function dispatch(EventReminder $reminder): void
    {
        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('tenant_id', $reminder->tenant_id)
            ->where('status', 'connected')
            ->first();

        if (!$instance) {
            $reminder->update([
                'status' => 'failed',
                'error'  => 'WhatsApp desconectado.',
            ]);
            return;
        }

        $lead = $reminder->lead;
        $rawPhone = preg_replace('/\D/', '', $lead->phone ?? '');

        if (!$rawPhone) {
            $reminder->update([
                'status' => 'failed',
                'error'  => 'Lead sem número de telefone.',
            ]);
            return;
        }

        $chatId = $rawPhone . '@c.us';
        $conversation = $reminder->conversation;

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

        $waha   = new WahaService($instance->session_name);
        $result = $waha->sendText($chatId, $reminder->body);

        if (isset($result['error'])) {
            $reminder->update([
                'status' => 'failed',
                'error'  => $result['error'],
            ]);
            return;
        }

        $wahaMessageId = $result['id'] ?? null;

        WhatsappMessage::withoutGlobalScope('tenant')->create([
            'tenant_id'       => $reminder->tenant_id,
            'conversation_id' => $conversation?->id,
            'waha_message_id' => $wahaMessageId,
            'direction'       => 'outbound',
            'type'            => 'text',
            'body'            => $reminder->body,
            'user_id'         => null,
            'ack'             => 'sent',
            'sent_at'         => now(),
        ]);

        if ($conversation) {
            WhatsappConversation::withoutGlobalScope('tenant')
                ->where('id', $conversation->id)
                ->update(['last_message_at' => now()]);
        }

        $reminder->update(['status' => 'sent', 'sent_at' => now()]);

        $this->line("  ✓ Lembrete #{$reminder->id} enviado para lead #{$reminder->lead_id} ({$reminder->event_title})");
    }
}
