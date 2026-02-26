<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\Tenant\AiConfigurationController;
use App\Models\AiAnalystSuggestion;
use App\Models\AiUsageLog;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\LeadNote;
use App\Models\Pipeline;
use App\Models\WhatsappConversation;
use Illuminate\Support\Facades\Log;

class ConversationAnalystService
{
    /**
     * Chama o LLM para analisar a conversa e retorna array de sugestões.
     */
    public function runLlm(WhatsappConversation $conv): array
    {
        $provider = (string) config('ai.provider', '');
        $apiKey   = (string) config('ai.api_key', '');
        $model    = (string) config('ai.model', '');

        if ($provider === '' || $apiKey === '' || $model === '') {
            Log::channel('whatsapp')->warning('ConversationAnalystService: AI não configurada', [
                'conversation_id' => $conv->id,
            ]);
            return [];
        }

        $lead = $conv->lead;
        if (! $lead) {
            return [];
        }

        // Funis e etapas do tenant
        $pipelines = Pipeline::withoutGlobalScope('tenant')
            ->where('tenant_id', $conv->tenant_id)
            ->with(['stages' => fn ($q) => $q->orderBy('position')])
            ->get();

        $pipelineContext = $pipelines->map(fn ($p) =>
            "Funil '{$p->name}' (id:{$p->id}):\n" .
            $p->stages->map(fn ($s) =>
                "  - '{$s->name}' (stage_id:{$s->id})" .
                ($s->is_won  ? ' [ETAPA DE GANHO]'  : '') .
                ($s->is_lost ? ' [ETAPA DE PERDA]' : '')
            )->implode("\n")
        )->implode("\n\n");

        // Campos personalizados do tenant
        $customFields = CustomFieldDefinition::withoutGlobalScope('tenant')
            ->where('tenant_id', $conv->tenant_id)
            ->where('is_active', true)
            ->get()
            ->map(fn ($f) => "- {$f->label} (campo:{$f->name}, tipo:{$f->field_type})")
            ->implode("\n");

        $stage    = $lead->stage;
        $pipeline = $lead->pipeline;
        $stageStr = $stage
            ? "{$stage->name} (stage_id:{$stage->id}) — Funil: " . ($pipeline->name ?? '?')
            : 'Sem etapa definida';

        $tagsStr = ! empty($lead->tags) ? implode(', ', $lead->tags) : 'nenhuma';

        $system = <<<PROMPT
Você é um analista de CRM especializado. Analise a conversa abaixo e recomende ações para o vendedor.
NÃO responda ao contato. Retorne SOMENTE JSON válido, sem markdown, sem texto antes ou depois.

FUNIS E ETAPAS DISPONÍVEIS:
{$pipelineContext}

CAMPOS PERSONALIZADOS:
{$customFields}

LEAD ATUAL:
- Nome: {$lead->name}
- Etapa atual: {$stageStr}
- Tags existentes: {$tagsStr}
- Email: {$lead->email}
- Telefone: {$lead->phone}

FORMATO DE RESPOSTA (JSON puro — omita chaves para as quais não há recomendação):
{
  "stage_change": {"stage_id": 5, "stage_name": "Proposta", "pipeline_id": 2, "reason": "motivo curto"},
  "tags": ["tag1", "tag2"],
  "note": "Resumo da conversa com insights relevantes sobre o lead",
  "fields": [{"name": "campo_name", "label": "Label", "value": "valor"}],
  "lead_update": {"email": "email@exemplo.com", "name": "Nome Completo"}
}

REGRAS:
- Use APENAS os stage_id listados acima. Não invente IDs.
- Para lead_update: inclua apenas name, email ou phone (nada mais).
- Só sugira mudança de etapa se houver sinal claro na conversa.
- Só crie nota se houver informação relevante sobre o lead (cargo, empresa, budget, prazo, etc).
- Tags devem ser curtas e descritivas (ex: "interessado", "urgente", "orcamento-confirmado").
PROMPT;

        $agentService = new AiAgentService();
        $history      = $agentService->buildHistory($conv, 60);

        if (empty($history)) {
            return [];
        }

        try {
            $result = AiConfigurationController::callLlm(
                provider:  $provider,
                apiKey:    $apiKey,
                model:     $model,
                messages:  $history,
                maxTokens: 800,
                system:    $system,
            );
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('ConversationAnalystService: LLM erro', [
                'conversation_id' => $conv->id,
                'error'           => $e->getMessage(),
            ]);
            return [];
        }

        // Registrar uso
        $usage = $result['usage'] ?? [];
        AiUsageLog::create([
            'tenant_id'         => $conv->tenant_id,
            'conversation_id'   => $conv->id,
            'model'             => $model,
            'provider'          => $provider,
            'tokens_prompt'     => $usage['prompt']     ?? 0,
            'tokens_completion' => $usage['completion'] ?? 0,
            'tokens_total'      => $usage['total']      ?? 0,
            'type'              => 'analyst',
        ]);

        // Parse JSON
        $reply = trim($result['reply'] ?? '');
        $reply = (string) preg_replace('/^```(?:json)?\s*/i', '', $reply);
        $reply = (string) preg_replace('/\s*```$/i', '', $reply);

        $data = json_decode($reply, true);
        if (! is_array($data)) {
            Log::channel('whatsapp')->warning('ConversationAnalystService: resposta não é JSON', [
                'conversation_id' => $conv->id,
                'reply'           => mb_substr($reply, 0, 500),
            ]);
            return [];
        }

        $suggestions = [];

        // stage_change
        if (isset($data['stage_change']['stage_id'])) {
            $sc = $data['stage_change'];
            $suggestions[] = [
                'type'    => 'stage_change',
                'payload' => [
                    'stage_id'    => (int) $sc['stage_id'],
                    'stage_name'  => (string) ($sc['stage_name']  ?? ''),
                    'pipeline_id' => isset($sc['pipeline_id']) ? (int) $sc['pipeline_id'] : null,
                ],
                'reason' => isset($sc['reason']) ? (string) $sc['reason'] : null,
            ];
        }

        // tags
        if (! empty($data['tags']) && is_array($data['tags'])) {
            $existingTags = $lead->tags ?? [];
            foreach ($data['tags'] as $tag) {
                $tag = (string) $tag;
                if ($tag === '' || in_array($tag, $existingTags, true)) {
                    continue;
                }
                $suggestions[] = [
                    'type'    => 'add_tag',
                    'payload' => ['tag' => $tag],
                    'reason'  => null,
                ];
            }
        }

        // note
        if (! empty($data['note']) && is_string($data['note'])) {
            $suggestions[] = [
                'type'    => 'add_note',
                'payload' => ['note' => $data['note']],
                'reason'  => null,
            ];
        }

        // fields
        if (! empty($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as $field) {
                if (empty($field['name']) || ! isset($field['value'])) {
                    continue;
                }
                $suggestions[] = [
                    'type'    => 'fill_field',
                    'payload' => [
                        'name'  => (string) $field['name'],
                        'label' => (string) ($field['label'] ?? $field['name']),
                        'value' => (string) $field['value'],
                    ],
                    'reason' => null,
                ];
            }
        }

        // lead_update (somente campos seguros)
        if (! empty($data['lead_update']) && is_array($data['lead_update'])) {
            $allowed = ['name', 'email', 'phone'];
            foreach ($allowed as $field) {
                $value = isset($data['lead_update'][$field]) ? (string) $data['lead_update'][$field] : '';
                if ($value === '' || $value === (string) ($lead->{$field} ?? '')) {
                    continue;
                }
                $suggestions[] = [
                    'type'    => 'update_lead',
                    'payload' => ['field' => $field, 'value' => $value],
                    'reason'  => null,
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Salva sugestões no banco com deduplicação (não duplica pending idênticos).
     */
    public function createSuggestions(array $items, WhatsappConversation $conv): void
    {
        $existing = AiAnalystSuggestion::withoutGlobalScope('tenant')
            ->where('conversation_id', $conv->id)
            ->where('status', 'pending')
            ->get();

        foreach ($items as $item) {
            $isDuplicate = match ($item['type']) {
                'stage_change' => $existing->where('type', 'stage_change')
                    ->filter(fn ($s) => (int) ($s->payload['stage_id'] ?? 0) === (int) ($item['payload']['stage_id'] ?? 0))
                    ->isNotEmpty(),
                'add_tag'      => $existing->where('type', 'add_tag')
                    ->filter(fn ($s) => ($s->payload['tag'] ?? '') === ($item['payload']['tag'] ?? ''))
                    ->isNotEmpty(),
                'fill_field'   => $existing->where('type', 'fill_field')
                    ->filter(fn ($s) => ($s->payload['name'] ?? '') === ($item['payload']['name'] ?? ''))
                    ->isNotEmpty(),
                'update_lead'  => $existing->where('type', 'update_lead')
                    ->filter(fn ($s) => ($s->payload['field'] ?? '') === ($item['payload']['field'] ?? ''))
                    ->isNotEmpty(),
                default        => false,
            };

            if ($isDuplicate) {
                continue;
            }

            AiAnalystSuggestion::withoutGlobalScope('tenant')->create([
                'tenant_id'       => $conv->tenant_id,
                'lead_id'         => $conv->lead_id,
                'conversation_id' => $conv->id,
                'type'            => $item['type'],
                'payload'         => $item['payload'],
                'reason'          => $item['reason'] ?? null,
                'status'          => 'pending',
            ]);
        }
    }

    /**
     * Aplica uma sugestão aprovada e registra LeadEvent.
     */
    public function applySuggestion(AiAnalystSuggestion $suggestion): void
    {
        $lead = $suggestion->lead;
        if (! $lead) {
            $suggestion->update(['status' => 'approved']);
            return;
        }

        $payload = $suggestion->payload;

        match ($suggestion->type) {
            'stage_change' => $this->applyStageChange($lead, $payload, $suggestion->reason),
            'add_tag'      => $this->applyAddTag($lead, $payload),
            'add_note'     => $this->applyAddNote($lead, $payload),
            'fill_field'   => $this->applyFillField($lead, $payload),
            'update_lead'  => $this->applyUpdateLead($lead, $payload),
            default        => null,
        };

        $suggestion->update(['status' => 'approved']);
    }

    // ── Ações privadas ────────────────────────────────────────────────────────

    private function applyStageChange(Lead $lead, array $payload, ?string $reason): void
    {
        $stageId   = (int) ($payload['stage_id'] ?? 0);
        $stageName = (string) ($payload['stage_name'] ?? "ID {$stageId}");

        if ($stageId === 0) {
            return;
        }

        $lead->update(['stage_id' => $stageId]);

        LeadEvent::create([
            'tenant_id'    => $lead->tenant_id,
            'lead_id'      => $lead->id,
            'event_type'   => 'stage_changed',
            'description'  => "🤖 IA moveu para etapa '{$stageName}'" . ($reason ? ": {$reason}" : ''),
            'data_json'    => ['source' => 'ai_analyst', 'stage_id' => $stageId],
            'performed_by' => null,
            'created_at'   => now(),
        ]);
    }

    private function applyAddTag(Lead $lead, array $payload): void
    {
        $tag = (string) ($payload['tag'] ?? '');
        if ($tag === '') {
            return;
        }

        $tags = $lead->tags ?? [];
        if (! in_array($tag, $tags, true)) {
            $tags[] = $tag;
            $lead->update(['tags' => $tags]);
        }

        LeadEvent::create([
            'tenant_id'    => $lead->tenant_id,
            'lead_id'      => $lead->id,
            'event_type'   => 'ai_tag_added',
            'description'  => "🤖 IA adicionou tag '{$tag}'",
            'data_json'    => ['source' => 'ai_analyst', 'tag' => $tag],
            'performed_by' => null,
            'created_at'   => now(),
        ]);
    }

    private function applyAddNote(Lead $lead, array $payload): void
    {
        $note = (string) ($payload['note'] ?? '');
        if ($note === '') {
            return;
        }

        LeadNote::create([
            'tenant_id'  => $lead->tenant_id,
            'lead_id'    => $lead->id,
            'body'       => $note,
            'created_by' => null,
        ]);

        LeadEvent::create([
            'tenant_id'    => $lead->tenant_id,
            'lead_id'      => $lead->id,
            'event_type'   => 'ai_note',
            'description'  => '🤖 IA criou nota automática',
            'data_json'    => ['source' => 'ai_analyst'],
            'performed_by' => null,
            'created_at'   => now(),
        ]);
    }

    private function applyFillField(Lead $lead, array $payload): void
    {
        $fieldName  = (string) ($payload['name']  ?? '');
        $fieldLabel = (string) ($payload['label'] ?? $fieldName);
        $value      = (string) ($payload['value'] ?? '');

        if ($fieldName === '' || $value === '') {
            return;
        }

        $definition = CustomFieldDefinition::withoutGlobalScope('tenant')
            ->where('tenant_id', $lead->tenant_id)
            ->where('name', $fieldName)
            ->first();

        if (! $definition) {
            return;
        }

        CustomFieldValue::withoutGlobalScope('tenant')->updateOrCreate(
            ['tenant_id' => $lead->tenant_id, 'lead_id' => $lead->id, 'field_id' => $definition->id],
            ['value_text' => $value]
        );

        LeadEvent::create([
            'tenant_id'    => $lead->tenant_id,
            'lead_id'      => $lead->id,
            'event_type'   => 'ai_field_filled',
            'description'  => "🤖 IA preencheu '{$fieldLabel}': {$value}",
            'data_json'    => ['source' => 'ai_analyst', 'field' => $fieldName, 'value' => $value],
            'performed_by' => null,
            'created_at'   => now(),
        ]);
    }

    private function applyUpdateLead(Lead $lead, array $payload): void
    {
        $field = (string) ($payload['field'] ?? '');
        $value = (string) ($payload['value'] ?? '');

        $allowed = ['name', 'email', 'phone'];
        if ($field === '' || $value === '' || ! in_array($field, $allowed, true)) {
            return;
        }

        $lead->update([$field => $value]);

        $labels = ['name' => 'nome', 'email' => 'e-mail', 'phone' => 'telefone'];
        $label  = $labels[$field] ?? $field;

        LeadEvent::create([
            'tenant_id'    => $lead->tenant_id,
            'lead_id'      => $lead->id,
            'event_type'   => 'ai_data_updated',
            'description'  => "🤖 IA atualizou {$label}: {$value}",
            'data_json'    => ['source' => 'ai_analyst', 'field' => $field, 'value' => $value],
            'performed_by' => null,
            'created_at'   => now(),
        ]);
    }
}
