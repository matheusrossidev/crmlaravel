<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Lead;
use App\Models\NpsSurvey;
use App\Models\SurveyResponse;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Services\WahaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendNpsSurveyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $leadId,
        private readonly int $surveyId,
    ) {
        $this->queue = 'default';
    }

    public function handle(): void
    {
        $lead = Lead::withoutGlobalScope('tenant')->find($this->leadId);
        if (!$lead || $lead->opted_out || !$lead->phone) {
            return;
        }

        $survey = NpsSurvey::withoutGlobalScope('tenant')->find($this->surveyId);
        if (!$survey || !$survey->is_active) {
            return;
        }

        $uuid = (string) Str::uuid();

        $response = SurveyResponse::create([
            'uuid'       => $uuid,
            'tenant_id'  => $lead->tenant_id,
            'survey_id'  => $survey->id,
            'lead_id'    => $lead->id,
            'user_id'    => $lead->assigned_to,
            'status'     => 'pending',
            'sent_at'    => now(),
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
        ]);

        $url  = url("/s/{$uuid}");
        $name = $lead->name ?: 'Cliente';

        if ($survey->send_via === 'whatsapp') {
            $this->sendViaWhatsapp($lead, $name, $url);
        }

        Log::info('NPS survey sent', [
            'lead_id'   => $lead->id,
            'survey_id' => $survey->id,
            'uuid'      => $uuid,
        ]);
    }

    private function sendViaWhatsapp(Lead $lead, string $name, string $url): void
    {
        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('tenant_id', $lead->tenant_id)
            ->where('status', 'connected')
            ->first();

        if (!$instance) {
            return;
        }

        $waha = new WahaService($instance->session_name);
        $phone = preg_replace('/\D/', '', $lead->phone);

        $waha->sendText(
            "{$phone}@c.us",
            "Olá {$name}! 😊\n\nComo foi sua experiência conosco? Leva menos de 1 minuto para responder:\n\n👉 {$url}\n\nSua opinião é muito importante para nós!"
        );
    }
}
