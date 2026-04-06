<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Extrai dados estruturados de uma conversa de WhatsApp via OpenAI
 * (gpt-4o-mini com structured outputs) e preenche campos padrão e
 * custom fields do lead.
 *
 * Política: NUNCA sobrescreve campo já preenchido manualmente.
 */
class LeadDataExtractorService
{
    private const OPENAI_URL = 'https://api.openai.com/v1/chat/completions';
    private const DEFAULT_MAX_MESSAGES = 50;

    /** Standard lead columns que podem ser alvo de extração. */
    public const ALLOWED_LEAD_FIELDS = [
        'name', 'email', 'phone', 'company', 'instagram_username',
        'birthday', 'value', 'notes',
    ];

    /**
     * Roda a extração.
     *
     * @param  Lead   $lead
     * @param  array  $fieldsConfig  [{key, instruction, target}, ...]
     * @param  int    $maxMessages
     * @return array  {
     *   updated: array<string,mixed>,    // chave => valor aplicado
     *   skipped: array<string,string>,   // chave => motivo
     *   tokens_prompt: int,
     *   tokens_completion: int,
     *   model: string,
     *   provider: string,
     *   conversation_id: int|null,
     *   error: ?string,
     * }
     */
    public function extract(Lead $lead, array $fieldsConfig, int $maxMessages = self::DEFAULT_MAX_MESSAGES): array
    {
        $result = [
            'updated'           => [],
            'skipped'           => [],
            'tokens_prompt'     => 0,
            'tokens_completion' => 0,
            'model'             => '',
            'provider'          => 'openai',
            'conversation_id'   => null,
            'error'             => null,
        ];

        if (empty($fieldsConfig)) {
            $result['error'] = 'no_fields_configured';
            return $result;
        }

        // 1. Carrega conversa mais recente do lead
        $conv = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $lead->tenant_id)
            ->where('lead_id', $lead->id)
            ->orderByDesc('last_message_at')
            ->first();

        if (! $conv) {
            $result['error'] = 'no_conversation';
            return $result;
        }
        $result['conversation_id'] = $conv->id;

        $transcript = $this->buildTranscript($conv, $maxMessages);
        if ($transcript === null || $transcript === '') {
            $result['error'] = 'empty_history';
            return $result;
        }

        // 2. Filtra campos que JÁ estão preenchidos (skip antes da chamada à IA — economia)
        $fieldsToExtract = [];
        foreach ($fieldsConfig as $field) {
            $key    = (string) ($field['key'] ?? '');
            $target = (string) ($field['target'] ?? '');
            if ($key === '' || $target === '') {
                continue;
            }
            if ($this->targetAlreadyFilled($lead, $target)) {
                $result['skipped'][$key] = 'already_filled';
                continue;
            }
            $fieldsToExtract[] = $field;
        }

        if (empty($fieldsToExtract)) {
            return $result; // tudo já preenchido — nada a fazer
        }

        // 3. Monta JSON Schema dinâmico
        $schema = $this->buildJsonSchema($fieldsToExtract);

        // 4. Chama OpenAI
        try {
            $apiResponse = $this->callOpenAi($transcript, $fieldsToExtract, $schema);
        } catch (\Throwable $e) {
            Log::warning('LeadDataExtractor: OpenAI call failed', [
                'lead_id' => $lead->id,
                'error'   => $e->getMessage(),
            ]);
            $result['error'] = 'openai_error: ' . $e->getMessage();
            return $result;
        }

        $result['tokens_prompt']     = $apiResponse['tokens_prompt'];
        $result['tokens_completion'] = $apiResponse['tokens_completion'];
        $result['model']             = $apiResponse['model'];
        $extracted                   = $apiResponse['data'];

        // 5. Aplica updates
        foreach ($fieldsToExtract as $field) {
            $key    = (string) $field['key'];
            $target = (string) $field['target'];
            $value  = $extracted[$key] ?? null;

            if ($value === null || $value === '') {
                $result['skipped'][$key] = 'not_found_in_conversation';
                continue;
            }

            try {
                $applied = $this->applyValue($lead, $target, $value);
                if ($applied) {
                    $result['updated'][$key] = $value;
                } else {
                    $result['skipped'][$key] = 'apply_failed';
                }
            } catch (\Throwable $e) {
                Log::warning('LeadDataExtractor: failed to apply field', [
                    'lead_id' => $lead->id,
                    'key'     => $key,
                    'target'  => $target,
                    'error'   => $e->getMessage(),
                ]);
                $result['skipped'][$key] = 'apply_error';
            }
        }

        return $result;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function buildTranscript(WhatsappConversation $conv, int $maxMessages): ?string
    {
        $messages = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('conversation_id', $conv->id)
            ->whereIn('type', ['text', 'image', 'audio', 'video', 'document'])
            ->orderBy('sent_at')
            ->limit($maxMessages)
            ->get(['direction', 'type', 'body', 'sent_at']);

        if ($messages->isEmpty()) {
            return null;
        }

        $lines = [];
        foreach ($messages as $m) {
            $body = trim((string) ($m->body ?? ''));
            if ($body === '') {
                $body = '[' . $m->type . ']';
            }
            $prefix = $m->direction === 'inbound' ? 'CLIENTE' : 'VENDEDOR';
            $lines[] = "{$prefix}: {$body}";
        }

        return implode("\n", $lines);
    }

    private function buildJsonSchema(array $fields): array
    {
        $properties = [];
        $required   = [];
        foreach ($fields as $field) {
            $key = (string) $field['key'];
            $properties[$key] = [
                'type'        => ['string', 'null'],
                'description' => (string) ($field['instruction'] ?? ''),
            ];
            $required[] = $key;
        }

        return [
            'type'                 => 'object',
            'properties'           => $properties,
            'required'             => $required,
            'additionalProperties' => false,
        ];
    }

    private function callOpenAi(string $transcript, array $fields, array $schema): array
    {
        $apiKey = (string) (config('services.openai_extraction.key') ?? config('services.openai.key') ?? env('OPENAI_API_KEY'));
        if ($apiKey === '') {
            throw new \RuntimeException('OPENAI_API_KEY not configured');
        }

        $model = (string) config('services.openai_extraction.model', 'gpt-4o-mini');

        $systemPrompt = "Você é um assistente de extração de dados de CRM.\n"
            . "Sua tarefa: ler a transcrição completa de uma conversa de WhatsApp entre VENDEDOR e CLIENTE\n"
            . "e extrair campos específicos solicitados.\n\n"
            . "REGRAS:\n"
            . "- Use APENAS informações explicitamente mencionadas pelo CLIENTE (ou confirmadas por ele).\n"
            . "- Se a informação NÃO estiver claramente presente, retorne null para aquele campo.\n"
            . "- NÃO invente dados. Prefira null à dúvida.\n"
            . "- Para e-mails: valide formato básico (deve ter @).\n"
            . "- Para telefones: retorne só os dígitos.\n"
            . "- Respeite as instruções específicas de cada campo no schema.";

        $userPrompt = "Transcrição da conversa:\n\n```\n" . $transcript . "\n```\n\nExtraia os campos definidos no schema.";

        $payload = [
            'model'    => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'response_format' => [
                'type'        => 'json_schema',
                'json_schema' => [
                    'name'   => 'lead_extraction',
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
            'temperature' => 0,
        ];

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post(self::OPENAI_URL, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI HTTP ' . $response->status() . ': ' . $response->body());
        }

        $json = $response->json();
        $content = $json['choices'][0]['message']['content'] ?? '{}';
        $data = json_decode($content, true);

        if (! is_array($data)) {
            throw new \RuntimeException('OpenAI returned invalid JSON: ' . substr($content, 0, 200));
        }

        return [
            'data'              => $data,
            'tokens_prompt'     => (int) ($json['usage']['prompt_tokens'] ?? 0),
            'tokens_completion' => (int) ($json['usage']['completion_tokens'] ?? 0),
            'model'             => (string) ($json['model'] ?? $model),
        ];
    }

    private function targetAlreadyFilled(Lead $lead, string $target): bool
    {
        if (str_starts_with($target, 'lead.')) {
            $col = substr($target, 5);
            if (! in_array($col, self::ALLOWED_LEAD_FIELDS, true)) {
                return true; // bloqueia targets desconhecidos
            }
            $current = $lead->{$col} ?? null;
            if ($current === null) return false;
            if (is_string($current) && trim($current) === '') return false;
            if (is_numeric($current) && (float) $current === 0.0) return false;
            return true;
        }

        if (str_starts_with($target, 'custom:')) {
            $fieldId = (int) substr($target, 7);
            if ($fieldId <= 0) return true;

            $existing = CustomFieldValue::withoutGlobalScope('tenant')
                ->where('lead_id', $lead->id)
                ->where('field_id', $fieldId)
                ->first();

            if (! $existing) return false;

            // Se qualquer coluna de valor estiver preenchida, considera filled
            return ($existing->value_text !== null && $existing->value_text !== '')
                || $existing->value_number !== null
                || $existing->value_date !== null
                || $existing->value_boolean !== null
                || (! empty($existing->value_json));
        }

        return true; // target inválido — pula
    }

    private function applyValue(Lead $lead, string $target, mixed $value): bool
    {
        if (str_starts_with($target, 'lead.')) {
            $col = substr($target, 5);
            if (! in_array($col, self::ALLOWED_LEAD_FIELDS, true)) {
                return false;
            }

            $cleanValue = $this->coerceLeadValue($col, $value);
            if ($cleanValue === null) {
                return false;
            }

            Lead::withoutGlobalScope('tenant')
                ->where('id', $lead->id)
                ->update([$col => $cleanValue]);
            return true;
        }

        if (str_starts_with($target, 'custom:')) {
            $fieldId = (int) substr($target, 7);
            if ($fieldId <= 0) return false;

            $def = CustomFieldDefinition::withoutGlobalScope('tenant')
                ->where('id', $fieldId)
                ->where('tenant_id', $lead->tenant_id)
                ->where('is_active', true)
                ->first();

            if (! $def) return false;

            $cfv = CustomFieldValue::withoutGlobalScope('tenant')
                ->firstOrNew([
                    'lead_id'  => $lead->id,
                    'field_id' => $def->id,
                ]);
            $cfv->tenant_id = $lead->tenant_id;

            // Mesma lógica de save por tipo do ProcessAiResponse::applyUpdateCustomField
            switch ($def->field_type) {
                case 'number':
                case 'currency':
                    $num = is_numeric($value) ? (float) $value : null;
                    if ($num === null) return false;
                    $cfv->value_number = $num;
                    break;
                case 'date':
                    $date = $this->parseDate($value);
                    if (! $date) return false;
                    $cfv->value_date = $date;
                    break;
                case 'checkbox':
                    $cfv->value_boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'multiselect':
                    $cfv->value_json = is_array($value) ? $value : array_map('trim', explode(',', (string) $value));
                    break;
                default:
                    $cfv->value_text = (string) $value;
            }

            $cfv->save();
            return true;
        }

        return false;
    }

    private function coerceLeadValue(string $col, mixed $value): mixed
    {
        return match ($col) {
            'email' => filter_var((string) $value, FILTER_VALIDATE_EMAIL) ? mb_strtolower((string) $value) : null,
            'phone' => ($digits = preg_replace('/\D/', '', (string) $value)) !== '' ? mb_substr($digits, 0, 30) : null,
            'value' => is_numeric($value) ? (float) $value : null,
            'birthday' => $this->parseDate($value),
            'instagram_username' => mb_substr(ltrim((string) $value, '@'), 0, 100),
            'name', 'company' => mb_substr((string) $value, 0, 191),
            'notes' => (string) $value,
            default => (string) $value,
        };
    }

    private function parseDate(mixed $value): ?string
    {
        try {
            return \Carbon\Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
