<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\Tenant;
use App\Services\WebhookDispatcherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchAutomationWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    /** @var int[] */
    public array $backoff = [10, 60, 300];

    public function __construct(
        private readonly int $leadId,
        private readonly int $tenantId,
        private readonly array $config,
        private readonly string $triggerType,
    ) {
        $this->onQueue('default');
    }

    public function handle(WebhookDispatcherService $dispatcher): void
    {
        $lead = Lead::withoutGlobalScope('tenant')
            ->with(['stage', 'pipeline', 'assignedTo'])
            ->find($this->leadId);
        if (! $lead) {
            return;
        }

        $tenant = Tenant::withoutGlobalScope('tenant')->find($this->tenantId);
        if (! $tenant) {
            return;
        }

        $context = [
            'lead'         => $lead,
            'tenant'       => $tenant,
            'trigger_type' => $this->triggerType,
        ];

        $result = $dispatcher->dispatch($this->config, $context);

        // Loga no LeadEvent (visível pro usuário no timeline do lead)
        $url    = (string) ($this->config['url'] ?? '');
        $status = $result['status'];
        $error  = $result['error'];

        if ($error) {
            $description = "🌐 Webhook falhou ({$url}): {$error}";
            $eventType   = 'webhook_failed';
        } elseif ($status !== null && $status >= 200 && $status < 300) {
            $description = "🌐 Webhook enviado ({$status}) para {$url}";
            $eventType   = 'webhook_sent';
        } else {
            $description = "🌐 Webhook respondeu HTTP {$status} ({$url})";
            $eventType   = 'webhook_failed';
        }

        try {
            LeadEvent::create([
                'tenant_id'    => $lead->tenant_id,
                'lead_id'      => $lead->id,
                'event_type'   => $eventType,
                'description'  => $description,
                'data_json'    => [
                    'url'           => $url,
                    'method'        => $this->config['method'] ?? 'POST',
                    'status'        => $status,
                    'duration_ms'   => $result['duration_ms'],
                    'error'         => $error,
                    'request_body'  => mb_substr($result['request_body'] ?? '', 0, 1500),
                    'response_body' => mb_substr($result['body'] ?? '', 0, 1500),
                    'trigger'       => $this->triggerType,
                ],
                'performed_by' => null,
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('DispatchAutomationWebhook: failed to log event', ['error' => $e->getMessage()]);
        }

        Log::info('AutomationWebhook: dispatched', [
            'lead_id'     => $lead->id,
            'url'         => $url,
            'status'      => $status,
            'duration_ms' => $result['duration_ms'],
            'error'       => $error,
        ]);

        // Lança exceção pro retry só em erros de transporte (não em status 4xx/5xx)
        if ($error !== null && $this->attempts() < $this->tries) {
            throw new \RuntimeException('Webhook transport error: ' . $error);
        }
    }
}
