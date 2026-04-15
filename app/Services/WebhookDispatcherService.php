<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CustomFieldValue;
use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Despacha webhooks HTTP arbitrários a partir de actions de automação.
 *
 * Suporta dois modos de body:
 *  - builder: lista de {key, source, value?} resolvida pra JSON estruturado
 *  - raw: template de texto (JSON ou outro) com {{interpolação}}
 *
 * Sources suportadas em builder e raw:
 *  - lead.<column>            (qualquer coluna do model Lead)
 *  - lead.stage_name          (virtual)
 *  - lead.pipeline_name       (virtual)
 *  - lead.assigned_user_name  (virtual)
 *  - lead.tags                (array)
 *  - custom:<field_id>        (CustomFieldValue tipado)
 *  - tenant.name|id|slug
 *  - system.now_iso|now_unix|trigger_type
 *  - literal                  (apenas builder, com value)
 */
class WebhookDispatcherService
{
    /**
     * Dispara o webhook com o config + context dado.
     *
     * @param  array  $config   Action config (url, method, headers, body_mode, body_fields, body_raw)
     * @param  array  $context  ['lead' => Lead, 'tenant' => Tenant, 'trigger_type' => string]
     * @return array  ['status' => int|null, 'body' => string, 'duration_ms' => int, 'error' => ?string, 'request_body' => string]
     */
    public function dispatch(array $config, array $context): array
    {
        $result = [
            'status'       => null,
            'body'         => '',
            'duration_ms'  => 0,
            'error'        => null,
            'request_body' => '',
        ];

        $url = $this->interpolate((string) ($config['url'] ?? ''), $context);
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            $result['error'] = 'invalid_url';
            return $result;
        }

        // SSRF protection: bloqueia IPs privados/loopback/metadata (F-08)
        $safety = \App\Support\UrlSafety::isSafeOutboundHttp($url);
        if (! $safety['safe']) {
            $result['error'] = 'ssrf_blocked: ' . ($safety['reason'] ?? 'unknown');
            \Log::warning('WebhookDispatcher: URL bloqueada por SSRF policy', [
                'url'    => $url,
                'reason' => $safety['reason'],
            ]);
            return $result;
        }

        $method = strtoupper((string) ($config['method'] ?? 'POST'));
        if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $method = 'POST';
        }

        // Headers
        $headers = [];
        foreach ((array) ($config['headers'] ?? []) as $h) {
            $key   = trim((string) ($h['key'] ?? ''));
            $value = $this->interpolate((string) ($h['value'] ?? ''), $context);
            if ($key !== '') {
                $headers[$key] = $value;
            }
        }

        // Body
        $bodyMode  = (string) ($config['body_mode'] ?? 'builder');
        $bodyData  = null;
        $bodyText  = null;
        $hasBody   = in_array($method, ['POST', 'PUT', 'PATCH'], true);

        if ($hasBody) {
            if ($bodyMode === 'raw') {
                $bodyText = $this->interpolate((string) ($config['body_raw'] ?? ''), $context);
                $headersLower = array_change_key_case($headers, CASE_LOWER);
                if (! isset($headersLower['content-type'])) {
                    $headers['Content-Type'] = 'application/json';
                }
                $result['request_body'] = $bodyText;
            } else {
                // builder mode
                $bodyData = $this->buildBodyFromFields((array) ($config['body_fields'] ?? []), $context);
                $headers['Content-Type'] = 'application/json';
                $result['request_body'] = json_encode($bodyData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        $start = microtime(true);
        try {
            // `withoutRedirecting` previne SSRF via 302 → IP privado após a validação inicial
            $request = Http::withHeaders($headers)->timeout(15)->withoutRedirecting();

            if ($hasBody) {
                if ($bodyMode === 'raw') {
                    $response = $request->send($method, $url, ['body' => $bodyText ?? '']);
                } else {
                    $response = $request->send($method, $url, ['json' => $bodyData ?? []]);
                }
            } else {
                $response = $request->send($method, $url);
            }

            $result['status'] = $response->status();
            $result['body']   = mb_substr((string) $response->body(), 0, 2000);
        } catch (\Throwable $e) {
            $result['error'] = mb_substr($e->getMessage(), 0, 300);
            Log::warning('WebhookDispatcher: HTTP error', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);
        }

        $result['duration_ms'] = (int) round((microtime(true) - $start) * 1000);
        return $result;
    }

    // ── Builder body ─────────────────────────────────────────────────────────

    private function buildBodyFromFields(array $fields, array $context): array
    {
        $body = [];
        foreach ($fields as $field) {
            $key    = trim((string) ($field['key'] ?? ''));
            $source = (string) ($field['source'] ?? '');
            if ($key === '' || $source === '') {
                continue;
            }
            if ($source === 'literal') {
                $body[$key] = $field['value'] ?? '';
                continue;
            }
            $body[$key] = $this->resolveSource($source, $context);
        }
        return $body;
    }

    // ── Source resolver ──────────────────────────────────────────────────────

    /**
     * Resolve uma fonte de dado pra valor concreto.
     */
    public function resolveSource(string $source, array $context): mixed
    {
        $lead   = $context['lead']   ?? null;
        $tenant = $context['tenant'] ?? null;

        // tenant.*
        if (str_starts_with($source, 'tenant.')) {
            if (! $tenant instanceof Tenant) return null;
            $col = substr($source, 7);
            return match ($col) {
                'name' => $tenant->name,
                'id'   => $tenant->id,
                'slug' => $tenant->slug,
                default => $tenant->{$col} ?? null,
            };
        }

        // system.*
        if (str_starts_with($source, 'system.')) {
            $key = substr($source, 7);
            return match ($key) {
                'now_iso'      => now()->toIso8601String(),
                'now_unix'     => now()->timestamp,
                'trigger_type' => (string) ($context['trigger_type'] ?? ''),
                default        => null,
            };
        }

        // custom:N
        if (str_starts_with($source, 'custom:')) {
            if (! $lead instanceof Lead) return null;
            $fieldId = (int) substr($source, 7);
            return $this->resolveCustomField($lead, $fieldId);
        }

        // lead.*
        if (str_starts_with($source, 'lead.')) {
            if (! $lead instanceof Lead) return null;
            $col = substr($source, 5);
            return match ($col) {
                'stage_name'         => $lead->stage?->name,
                'pipeline_name'      => $lead->pipeline?->name,
                'assigned_user_name' => $lead->assignedTo?->name,
                'tags'               => $lead->tags ?? [],
                default              => $lead->{$col} ?? null,
            };
        }

        return null;
    }

    private function resolveCustomField(Lead $lead, int $fieldId): mixed
    {
        if ($fieldId <= 0) return null;

        $cfv = CustomFieldValue::withoutGlobalScope('tenant')
            ->where('lead_id', $lead->id)
            ->where('field_id', $fieldId)
            ->with('fieldDefinition')
            ->first();

        if (! $cfv) return null;

        $type = $cfv->fieldDefinition?->field_type ?? 'text';

        return match ($type) {
            'number', 'currency' => $cfv->value_number !== null ? (float) $cfv->value_number : null,
            'date'               => $cfv->value_date?->toDateString(),
            'checkbox'           => $cfv->value_boolean !== null ? (bool) $cfv->value_boolean : null,
            'multiselect'        => $cfv->value_json ?? [],
            default              => $cfv->value_text,
        };
    }

    // ── Interpolation (raw mode + headers + url) ─────────────────────────────

    public function interpolate(string $template, array $context): string
    {
        if ($template === '' || ! str_contains($template, '{{')) {
            return $template;
        }

        return preg_replace_callback('/\{\{\s*([\w.:]+)\s*\}\}/', function ($m) use ($context) {
            $value = $this->resolveSource($m[1], $context);
            if (is_array($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
            return (string) ($value ?? '');
        }, $template);
    }
}
