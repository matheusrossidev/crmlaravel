<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CustomFieldDefinition;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\Tenant;
use App\Services\LeadDataExtractorService;
use App\Services\TokenQuotaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExtractLeadDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    /** @var int[] */
    public array $backoff = [30, 120];

    public function __construct(
        private readonly int $leadId,
        private readonly int $tenantId,
        private readonly array $config,
    ) {
        $this->onQueue('ai');
    }

    public function handle(LeadDataExtractorService $extractor): void
    {
        $lead = Lead::withoutGlobalScope('tenant')->find($this->leadId);
        if (! $lead) {
            return;
        }

        $tenant = Tenant::withoutGlobalScope('tenant')->find($this->tenantId);
        if (! $tenant) {
            return;
        }

        // Tokens esgotados? Skip silenciosamente com log no LeadEvent
        if ($tenant->ai_tokens_exhausted || ! TokenQuotaService::canSpend($tenant)) {
            $this->logEvent($lead, 'ai_extract_skipped', 'Extração de IA pulada — cota de tokens esgotada');
            return;
        }

        $fields       = (array) ($this->config['fields'] ?? []);
        $maxMessages  = (int) ($this->config['max_messages'] ?? 50);

        if (empty($fields)) {
            return;
        }

        $result = $extractor->extract($lead, $fields, max(10, min(200, $maxMessages)));

        // Grava uso de tokens (se houve chamada à IA)
        if ($result['tokens_prompt'] > 0 || $result['tokens_completion'] > 0) {
            TokenQuotaService::recordUsage(
                $tenant,
                model: $result['model'] ?: 'gpt-4o-mini',
                provider: $result['provider'] ?: 'openai',
                promptTokens: $result['tokens_prompt'],
                completionTokens: $result['tokens_completion'],
                type: 'extraction',
                conversationId: $result['conversation_id'],
            );
        }

        // Mensagem humanizada
        if (! empty($result['error'])) {
            $msg = match ($result['error']) {
                'no_conversation' => 'Extração de IA pulada — lead sem histórico de conversa',
                'empty_history'   => 'Extração de IA pulada — histórico de conversa vazio',
                'no_fields_configured' => 'Extração de IA pulada — nenhum campo configurado',
                default           => 'Extração de IA falhou: ' . substr((string) $result['error'], 0, 120),
            };
            $this->logEvent($lead, 'ai_extract_skipped', $msg);
            Log::info('ExtractLeadData: skipped', ['lead_id' => $lead->id, 'error' => $result['error']]);
            return;
        }

        if (empty($result['updated'])) {
            $this->logEvent($lead, 'ai_extract_skipped',
                'Extração de IA: nenhum campo novo encontrado na conversa');
            return;
        }

        // Sucesso — log com lista de campos preenchidos
        $labels = $this->humanizeFieldLabels($lead->tenant_id, array_keys($result['updated']), $fields);
        $description = '🤖 IA extraiu da conversa: ' . implode(', ', $labels);

        $this->logEvent($lead, 'ai_extracted', $description, [
            'source'   => 'lead_data_extractor',
            'updated'  => $result['updated'],
            'skipped'  => $result['skipped'],
            'tokens'   => $result['tokens_prompt'] + $result['tokens_completion'],
        ]);

        Log::info('ExtractLeadData: success', [
            'lead_id'        => $lead->id,
            'updated'        => array_keys($result['updated']),
            'tokens_total'   => $result['tokens_prompt'] + $result['tokens_completion'],
        ]);
    }

    /**
     * Mapeia keys configuradas → labels humanizados (Lead.email → "E-mail",
     * custom:42 → label do CustomFieldDefinition).
     */
    private function humanizeFieldLabels(int $tenantId, array $keys, array $fieldsConfig): array
    {
        $byKey = collect($fieldsConfig)->keyBy('key');

        $labels = [];
        foreach ($keys as $key) {
            $field  = $byKey->get($key);
            $target = (string) ($field['target'] ?? '');

            if (str_starts_with($target, 'lead.')) {
                $col = substr($target, 5);
                $labels[] = match ($col) {
                    'name'               => 'Nome',
                    'email'              => 'E-mail',
                    'phone'              => 'Telefone',
                    'company'            => 'Empresa',
                    'instagram_username' => 'Instagram',
                    'birthday'           => 'Aniversário',
                    'value'              => 'Valor',
                    'notes'              => 'Notas',
                    default              => $col,
                };
            } elseif (str_starts_with($target, 'custom:')) {
                $cfId = (int) substr($target, 7);
                $def = CustomFieldDefinition::withoutGlobalScope('tenant')
                    ->where('id', $cfId)
                    ->where('tenant_id', $tenantId)
                    ->first();
                $labels[] = $def?->label ?: ($field['key'] ?? 'campo');
            } else {
                $labels[] = $field['key'] ?? 'campo';
            }
        }

        return $labels;
    }

    private function logEvent(Lead $lead, string $type, string $description, array $data = []): void
    {
        try {
            LeadEvent::create([
                'tenant_id'    => $lead->tenant_id,
                'lead_id'      => $lead->id,
                'event_type'   => $type,
                'description'  => $description,
                'data_json'    => $data ?: null,
                'performed_by' => null,
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ExtractLeadData: failed to log event', ['error' => $e->getMessage()]);
        }
    }
}
