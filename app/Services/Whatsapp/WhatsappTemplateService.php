<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Models\WhatsappInstance;
use App\Models\WhatsappTemplate;
use App\Services\WhatsappCloudService;
use App\Services\WhatsappServiceFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Camada de domínio pros Message Templates HSM.
 *
 * Responsabilidades:
 *  - CRUD local (tabela whatsapp_templates)
 *  - Sync com Meta Graph API (listar, upsert, detectar removidos)
 *  - Validação de formato antes de mandar pra Meta
 *  - Envio de template formatado (monta components com variáveis preenchidas)
 *
 * Só funciona pra WhatsappInstance com provider='cloud_api'.
 */
class WhatsappTemplateService
{
    /**
     * Lista templates diretamente da Meta. Usado pelo sync.
     */
    public function listFromMeta(WhatsappInstance $instance): array
    {
        if (! $instance->isCloudApi()) {
            return ['error' => 'not_cloud_api', 'data' => []];
        }

        return (new WhatsappCloudService($instance))->listTemplates();
    }

    /**
     * Sincroniza templates do Meta pro banco local.
     * Retorna ['created' => N, 'updated' => N, 'removed' => N, 'error' => ?string]
     */
    public function syncFromMeta(WhatsappInstance $instance): array
    {
        $result = $this->listFromMeta($instance);

        if (isset($result['error']) && $result['error'] !== null && $result['error'] !== false) {
            return [
                'created' => 0,
                'updated' => 0,
                'removed' => 0,
                'error'   => is_string($result['error']) ? $result['error'] : 'sync_failed',
            ];
        }

        $created = 0;
        $updated = 0;
        $removed = 0;
        $seenIds = [];

        foreach ((array) ($result['data'] ?? []) as $raw) {
            $metaId = (string) ($raw['id'] ?? '');
            if ($metaId === '') {
                continue;
            }
            $seenIds[] = $metaId;

            $payload = [
                'tenant_id'            => $instance->tenant_id,
                'whatsapp_instance_id' => $instance->id,
                'name'                 => (string) ($raw['name'] ?? ''),
                'language'             => (string) ($raw['language'] ?? 'pt_BR'),
                'category'             => (string) ($raw['category'] ?? 'UTILITY'),
                'components'           => (array)  ($raw['components'] ?? []),
                'status'               => (string) ($raw['status'] ?? 'PENDING'),
                'meta_template_id'     => $metaId,
                'rejected_reason'      => $raw['rejected_reason'] ?? null,
                'quality_rating'       => $raw['quality_score']['score'] ?? null,
                'last_synced_at'       => now(),
            ];

            $existing = WhatsappTemplate::withoutGlobalScope('tenant')
                ->where('meta_template_id', $metaId)
                ->first();

            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                WhatsappTemplate::create($payload);
                $created++;
            }
        }

        // Remove locais que sumiram da Meta (só da mesma instance)
        if (! empty($seenIds)) {
            $removed = WhatsappTemplate::withoutGlobalScope('tenant')
                ->where('whatsapp_instance_id', $instance->id)
                ->whereNotNull('meta_template_id')
                ->whereNotIn('meta_template_id', $seenIds)
                ->delete();
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'removed' => $removed,
            'error'   => null,
        ];
    }

    /**
     * Cria template local e dispara criação na Meta.
     * Throws ValidationException se local validation falhar.
     *
     * @param array $data keys: name, language, category, header, body, footer, buttons, samples
     */
    public function create(WhatsappInstance $instance, array $data): WhatsappTemplate
    {
        if (! $instance->isCloudApi()) {
            throw ValidationException::withMessages(['provider' => 'Instância não é Cloud API.']);
        }

        $name     = strtolower(trim((string) ($data['name'] ?? '')));
        $language = (string) ($data['language'] ?? 'pt_BR');
        $category = strtoupper((string) ($data['category'] ?? 'UTILITY'));

        // Validação local — formato/padrão
        if (! preg_match('/^[a-z0-9_]{1,64}$/', $name)) {
            throw ValidationException::withMessages([
                'name' => 'Nome deve ser snake_case (só letras minúsculas, números e underscore), máx 64 chars.',
            ]);
        }

        if (! in_array($category, ['UTILITY', 'MARKETING', 'AUTHENTICATION'], true)) {
            throw ValidationException::withMessages([
                'category' => 'Categoria inválida.',
            ]);
        }

        $body = trim((string) ($data['body'] ?? ''));
        if ($body === '') {
            throw ValidationException::withMessages(['body' => 'Body é obrigatório.']);
        }
        if (mb_strlen($body) > 1024) {
            throw ValidationException::withMessages(['body' => 'Body tem no máximo 1024 caracteres.']);
        }

        // Checa variáveis sequenciais
        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $body, $m);
        $vars = array_values(array_unique(array_map('intval', $m[1] ?? [])));
        sort($vars);
        $expected = range(1, count($vars));
        if ($vars !== $expected && count($vars) > 0) {
            throw ValidationException::withMessages([
                'body' => 'Variáveis precisam ser sequenciais começando em {{1}}.',
            ]);
        }

        $samples = (array) ($data['samples'] ?? []);
        if (count($vars) > 0 && count($samples) < count($vars)) {
            throw ValidationException::withMessages([
                'samples' => 'Preencha exemplos pra todas as variáveis.',
            ]);
        }

        // Unicidade local
        $dup = WhatsappTemplate::withoutGlobalScope('tenant')
            ->where('whatsapp_instance_id', $instance->id)
            ->where('name', $name)
            ->where('language', $language)
            ->exists();
        if ($dup) {
            throw ValidationException::withMessages([
                'name' => 'Já existe template com esse nome e idioma nessa instância.',
            ]);
        }

        // Monta components no formato Meta
        $components = $this->buildComponentsForSubmit($data, $samples);

        // Cria na Meta
        $metaResp = (new WhatsappCloudService($instance))->createTemplate($name, $language, $category, $components);

        if (isset($metaResp['error'])) {
            $msg = is_array($metaResp['body'] ?? null)
                ? json_encode($metaResp['body'])
                : ($metaResp['body'] ?? ($metaResp['message'] ?? 'Erro ao criar template na Meta.'));

            Log::channel('whatsapp')->warning('WhatsappTemplate create: Meta rejeitou', [
                'name'     => $name,
                'language' => $language,
                'response' => $metaResp,
            ]);

            throw ValidationException::withMessages(['meta' => (string) $msg]);
        }

        return WhatsappTemplate::create([
            'tenant_id'            => $instance->tenant_id,
            'whatsapp_instance_id' => $instance->id,
            'name'                 => $name,
            'language'             => $language,
            'category'             => $category,
            'components'           => $components,
            'sample_variables'     => $samples,
            'status'               => (string) ($metaResp['status'] ?? 'PENDING'),
            'meta_template_id'     => isset($metaResp['id']) ? (string) $metaResp['id'] : null,
            'last_synced_at'       => now(),
        ]);
    }

    /**
     * Deleta template na Meta + local. Idempotente: se Meta retornar "não existe",
     * ainda remove local pra evitar templates órfãos.
     */
    public function delete(WhatsappTemplate $template): void
    {
        $instance = $template->instance;

        if ($instance && $instance->isCloudApi()) {
            (new WhatsappCloudService($instance))
                ->deleteTemplate($template->name, $template->meta_template_id);
        }

        $template->delete();
    }

    /**
     * Envia template pra um número.
     * Retorna o payload bruto da Meta (inclui 'id' do wamid no top-level).
     *
     * @param array $variables    ['1' => 'Maria', '2' => '15/04']
     * @param array|null $headerMedia ['type' => 'image'|'video'|'document', 'link' => 'https://...'] ou ['id' => '...']
     */
    public function send(
        WhatsappInstance $instance,
        string $toPhone,
        WhatsappTemplate $template,
        array $variables = [],
        ?array $headerMedia = null,
    ): array {
        if (! $instance->isCloudApi()) {
            return ['error' => 'not_cloud_api'];
        }

        if (! $template->isApproved()) {
            return ['error' => 'template_not_approved', 'status' => $template->status];
        }

        $components = $this->buildSendComponents($template, $variables, $headerMedia);

        return WhatsappServiceFactory::for($instance)
            ->sendTemplate($toPhone, $template->name, $template->language, $components);
    }

    // ── Helpers privados ─────────────────────────────────────────────────────

    /**
     * Monta o array de components pro POST /message_templates (submissão).
     * Inclui "example" em BODY/HEADER quando tem variáveis (Meta exige).
     */
    private function buildComponentsForSubmit(array $data, array $samples): array
    {
        $components = [];

        // HEADER (opcional)
        $header = $data['header'] ?? null;
        if (is_array($header) && ! empty($header['type'])) {
            $headerType = strtoupper((string) $header['type']);
            if ($headerType === 'TEXT') {
                $text = (string) ($header['text'] ?? '');
                $comp = ['type' => 'HEADER', 'format' => 'TEXT', 'text' => $text];

                if (str_contains($text, '{{1}}') && ! empty($header['sample'])) {
                    $comp['example'] = ['header_text' => [(string) $header['sample']]];
                }
                $components[] = $comp;
            } elseif (in_array($headerType, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
                $comp = ['type' => 'HEADER', 'format' => $headerType];
                if (! empty($header['sample_handle'])) {
                    $comp['example'] = ['header_handle' => [(string) $header['sample_handle']]];
                }
                $components[] = $comp;
            }
        }

        // BODY (obrigatório)
        $body = (string) ($data['body'] ?? '');
        $bodyComp = ['type' => 'BODY', 'text' => $body];
        if (! empty($samples)) {
            $bodyComp['example'] = [
                'body_text' => [array_values(array_map(fn ($v) => (string) $v, $samples))],
            ];
        }
        $components[] = $bodyComp;

        // FOOTER (opcional, sem variáveis)
        $footer = trim((string) ($data['footer'] ?? ''));
        if ($footer !== '') {
            $components[] = ['type' => 'FOOTER', 'text' => mb_substr($footer, 0, 60)];
        }

        // BUTTONS (opcional)
        $buttons = $data['buttons'] ?? [];
        if (is_array($buttons) && ! empty($buttons)) {
            $btnPayload = [];
            foreach (array_slice($buttons, 0, 10) as $b) {
                $btnType = strtoupper((string) ($b['type'] ?? ''));
                if ($btnType === 'QUICK_REPLY') {
                    $btnPayload[] = [
                        'type' => 'QUICK_REPLY',
                        'text' => mb_substr((string) ($b['text'] ?? ''), 0, 25),
                    ];
                } elseif ($btnType === 'URL') {
                    $btn = [
                        'type' => 'URL',
                        'text' => mb_substr((string) ($b['text'] ?? ''), 0, 20),
                        'url'  => (string) ($b['url'] ?? ''),
                    ];
                    if (! empty($b['sample'])) {
                        $btn['example'] = [(string) $b['sample']];
                    }
                    $btnPayload[] = $btn;
                } elseif ($btnType === 'PHONE_NUMBER') {
                    $btnPayload[] = [
                        'type'         => 'PHONE_NUMBER',
                        'text'         => mb_substr((string) ($b['text'] ?? ''), 0, 20),
                        'phone_number' => (string) ($b['phone_number'] ?? ''),
                    ];
                } elseif ($btnType === 'COPY_CODE') {
                    $btnPayload[] = [
                        'type'    => 'COPY_CODE',
                        'example' => (string) ($b['example'] ?? $b['text'] ?? ''),
                    ];
                }
            }
            if (! empty($btnPayload)) {
                $components[] = ['type' => 'BUTTONS', 'buttons' => $btnPayload];
            }
        }

        return $components;
    }

    /**
     * Monta components no formato "send" (POST /messages) — diferente do "submit".
     * Aqui injetamos os valores reais das variáveis, não exemplos.
     */
    private function buildSendComponents(
        WhatsappTemplate $template,
        array $variables,
        ?array $headerMedia,
    ): array {
        $out = [];

        $tplComponents = (array) $template->components;

        foreach ($tplComponents as $comp) {
            $type = strtoupper((string) ($comp['type'] ?? ''));

            if ($type === 'HEADER') {
                $format = strtoupper((string) ($comp['format'] ?? 'TEXT'));

                if ($format === 'TEXT' && str_contains((string) ($comp['text'] ?? ''), '{{1}}')) {
                    $out[] = [
                        'type'       => 'header',
                        'parameters' => [[
                            'type' => 'text',
                            'text' => (string) ($variables['header_1'] ?? $variables[1] ?? ''),
                        ]],
                    ];
                } elseif (in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT'], true) && $headerMedia) {
                    $mediaKey = strtolower($format);
                    $param    = ['type' => $mediaKey];

                    if (! empty($headerMedia['id'])) {
                        $param[$mediaKey] = ['id' => (string) $headerMedia['id']];
                    } elseif (! empty($headerMedia['link'])) {
                        $param[$mediaKey] = ['link' => (string) $headerMedia['link']];
                    }
                    if ($format === 'DOCUMENT' && ! empty($headerMedia['filename'])) {
                        $param[$mediaKey]['filename'] = (string) $headerMedia['filename'];
                    }

                    $out[] = ['type' => 'header', 'parameters' => [$param]];
                }
            } elseif ($type === 'BODY') {
                $body = (string) ($comp['text'] ?? '');
                preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $body, $m);
                $ids = array_values(array_unique(array_map('intval', $m[1] ?? [])));
                sort($ids);

                if (! empty($ids)) {
                    $params = [];
                    foreach ($ids as $id) {
                        $params[] = [
                            'type' => 'text',
                            'text' => (string) ($variables[(string) $id] ?? $variables[$id] ?? ''),
                        ];
                    }
                    $out[] = ['type' => 'body', 'parameters' => $params];
                }
            } elseif ($type === 'BUTTONS') {
                foreach ((array) ($comp['buttons'] ?? []) as $i => $btn) {
                    $btnType = strtoupper((string) ($btn['type'] ?? ''));
                    if ($btnType === 'URL' && ! empty($btn['example'])) {
                        $out[] = [
                            'type'       => 'button',
                            'sub_type'   => 'url',
                            'index'      => (string) $i,
                            'parameters' => [[
                                'type' => 'text',
                                'text' => (string) ($variables["btn_{$i}"] ?? ''),
                            ]],
                        ];
                    }
                    // Quick reply com payload dinâmico não suportado aqui (rarely used)
                }
            }
        }

        return $out;
    }
}
