<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SupportsInteractiveMessages;
use App\Contracts\SupportsMessageTemplates;
use App\Contracts\WhatsappServiceContract;
use App\Models\WhatsappInstance;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Implementação do WhatsAppServiceContract usando a Cloud API oficial da Meta.
 *
 * Espelha a interface do WahaService — quem chama não precisa saber qual
 * provider está por trás, basta usar WhatsappServiceFactory::for($instance).
 *
 * Diferenças importantes vs WAHA:
 *  - chatId é só o número (sem "@c.us")
 *  - Mídia precisa ser enviada por upload (POST /media → recebe id → usa id na mensagem)
 *  - Lista interativa tem schema diferente (sections + rows com id/title/description)
 *  - Reaction tem schema próprio (sem PUT)
 */
class WhatsappCloudService implements WhatsappServiceContract, SupportsMessageTemplates, SupportsInteractiveMessages
{
    private string $baseUrl;
    private string $phoneNumberId;
    private string $wabaId;
    private string $accessToken;

    public function __construct(WhatsappInstance $instance)
    {
        $version = (string) config('services.whatsapp_cloud.api_version', 'v22.0');
        $this->baseUrl = "https://graph.facebook.com/{$version}";
        $this->phoneNumberId = (string) ($instance->phone_number_id ?? '');
        $this->wabaId = (string) ($instance->waba_id ?? '');

        // Chain de fallback pro token (prioridade):
        //   1. system_user_token da instância — token especificamente linkado a essa WABA
        //      via nosso BM Syncro (Solution Partner). Permanente.
        //   2. config('services.whatsapp_cloud.system_user_token') — token GLOBAL do BM
        //      da Syncro. Permanente. Funciona pra qualquer WABA que tenha sido
        //      adicionada como client_whatsapp_business_account no nosso BM.
        //   3. instance->access_token — token de user do Embedded Signup. Expira em 60 dias.
        //      Fallback de emergência se system tokens falharem.
        //
        // Casts 'encrypted' do model decriptam automaticamente ao acessar.
        $this->accessToken = (string) (
            ($instance->system_user_token ?? '')
            ?: (string) config('services.whatsapp_cloud.system_user_token', '')
            ?: ($instance->access_token ?? '')
        );

        if ($this->phoneNumberId === '' || $this->accessToken === '') {
            Log::warning('WhatsappCloudService: instância sem phone_number_id ou token utilizável', [
                'instance_id' => $instance->id,
                'has_system_token_instance' => ! empty($instance->system_user_token),
                'has_system_token_global'   => ! empty(config('services.whatsapp_cloud.system_user_token')),
                'has_access_token'          => ! empty($instance->access_token),
            ]);
        }
    }

    public function getProviderName(): string
    {
        return 'cloud_api';
    }

    // ── Mensagens ────────────────────────────────────────────────────────────

    public function sendText(string $chatId, string $text): array
    {
        return $this->sendMessage([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizeChatId($chatId),
            'type'              => 'text',
            'text'              => [
                'preview_url' => true,
                'body'        => $text,
            ],
        ]);
    }

    public function sendImage(string $chatId, string $url, string $caption = ''): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizeChatId($chatId),
            'type'              => 'image',
            'image'             => [
                'link' => $url,
            ],
        ];
        if ($caption !== '') {
            $payload['image']['caption'] = $caption;
        }
        return $this->sendMessage($payload);
    }

    public function sendImageBase64(string $chatId, string $filePath, string $mimeType, string $caption = ''): array
    {
        // Cloud API exige upload via /media endpoint primeiro, depois usa o ID retornado
        $mediaId = $this->uploadMedia($filePath, $mimeType);
        if (! $mediaId) {
            return ['error' => 'media_upload_failed'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizeChatId($chatId),
            'type'              => 'image',
            'image'             => [
                'id' => $mediaId,
            ],
        ];
        if ($caption !== '') {
            $payload['image']['caption'] = $caption;
        }
        return $this->sendMessage($payload);
    }

    public function sendVoice(string $chatId, string $url): array
    {
        return $this->sendMessage([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizeChatId($chatId),
            'type'              => 'audio',
            'audio'             => [
                'link' => $url,
            ],
        ]);
    }

    public function sendVoiceBase64(string $chatId, string $filePath, string $mimeType): array
    {
        $mediaId = $this->uploadMedia($filePath, $mimeType);
        if (! $mediaId) {
            return ['error' => 'media_upload_failed'];
        }

        return $this->sendMessage([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizeChatId($chatId),
            'type'              => 'audio',
            'audio'             => [
                'id' => $mediaId,
            ],
        ]);
    }

    public function sendFileBase64(string $chatId, string $filePath, string $mimeType, string $filename, string $caption = ''): array
    {
        $mediaId = $this->uploadMedia($filePath, $mimeType, $filename);
        if (! $mediaId) {
            return ['error' => 'media_upload_failed'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizeChatId($chatId),
            'type'              => 'document',
            'document'          => [
                'id'       => $mediaId,
                'filename' => $filename,
            ],
        ];
        if ($caption !== '') {
            $payload['document']['caption'] = $caption;
        }
        return $this->sendMessage($payload);
    }

    public function sendList(
        string $chatId,
        string $description,
        array $rows,
        ?string $title = null,
        string $buttonText = 'Selecione',
        ?string $footer = null,
    ): array {
        // Cloud API tem limite: máx 10 rows totais, button label máx 20 chars
        $cleanRows = array_slice(array_map(function ($r) {
            return [
                'id'          => (string) ($r['id'] ?? $r['rowId'] ?? uniqid('row_')),
                'title'       => mb_substr((string) ($r['title'] ?? ''), 0, 24),
                'description' => mb_substr((string) ($r['description'] ?? ''), 0, 72),
            ];
        }, $rows), 0, 10);

        $interactive = [
            'type'   => 'list',
            'body'   => ['text' => mb_substr($description, 0, 1024)],
            'action' => [
                'button'   => mb_substr($buttonText, 0, 20),
                'sections' => [
                    [
                        'title' => mb_substr($title ?? 'Opções', 0, 24),
                        'rows'  => $cleanRows,
                    ],
                ],
            ],
        ];
        if ($footer) {
            $interactive['footer'] = ['text' => mb_substr($footer, 0, 60)];
        }
        if ($title) {
            $interactive['header'] = ['type' => 'text', 'text' => mb_substr($title, 0, 60)];
        }

        return $this->sendMessage([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizeChatId($chatId),
            'type'              => 'interactive',
            'interactive'       => $interactive,
        ]);
    }

    public function sendInteractiveButtons(
        string $chatId,
        string $body,
        array $buttons,
        ?string $footer = null,
        ?string $header = null,
    ): array {
        // Schema Meta: até 3 reply buttons, title máx 20 chars, id único máx 256 chars.
        // Doc: https://developers.facebook.com/docs/whatsapp/cloud-api/reference/messages#interactive-reply-buttons
        $cleanButtons = array_slice(array_values(array_map(function ($b, $i) {
            return [
                'type'  => 'reply',
                'reply' => [
                    'id'    => (string) ($b['id']    ?? 'btn_' . $i),
                    'title' => mb_substr((string) ($b['title'] ?? ''), 0, 20),
                ],
            ];
        }, $buttons, array_keys($buttons))), 0, 3);

        $interactive = [
            'type' => 'button',
            'body' => ['text' => mb_substr($body, 0, 1024)],
            'action' => ['buttons' => $cleanButtons],
        ];

        if ($header) {
            $interactive['header'] = ['type' => 'text', 'text' => mb_substr($header, 0, 60)];
        }
        if ($footer) {
            $interactive['footer'] = ['text' => mb_substr($footer, 0, 60)];
        }

        return $this->sendMessage([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizeChatId($chatId),
            'type'              => 'interactive',
            'interactive'       => $interactive,
        ]);
    }

    public function sendTemplate(string $chatId, string $templateName, string $language, array $components): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizeChatId($chatId),
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => ['code' => $language],
            ],
        ];
        if (! empty($components)) {
            $payload['template']['components'] = $components;
        }
        return $this->sendMessage($payload);
    }

    public function sendReaction(string $messageId, string $emoji): array
    {
        // Cloud API: precisa do número do destinatário no payload — extraído da WhatsappMessage
        // Como o contract recebe só o messageId, precisamos de uma forma de pegar o "to".
        // Como esse caso é raro e o Cloud API exige "to", se chamado sem contexto retornamos erro.
        // Quem chamar deve usar uma versão estendida (sendReactionWithRecipient).
        Log::warning('WhatsappCloudService::sendReaction precisa de recipient — use sendReactionWithRecipient', [
            'message_id' => $messageId,
        ]);
        return ['error' => 'reaction_requires_recipient_for_cloud_api'];
    }

    /**
     * Versão estendida pra reação no Cloud API que aceita o destinatário.
     * Use esta quando estiver enviando reação via Cloud (não faz parte do contract).
     */
    public function sendReactionWithRecipient(string $chatId, string $messageId, string $emoji): array
    {
        return $this->sendMessage([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizeChatId($chatId),
            'type'              => 'reaction',
            'reaction'          => [
                'message_id' => $messageId,
                'emoji'      => $emoji,
            ],
        ]);
    }

    // ── Webhook subscription ─────────────────────────────────────────────────

    /**
     * Inscreve a Page no nosso app pra receber webhooks de mensagens.
     * Necessário fazer 1x após conectar via Embedded Signup.
     */
    public function subscribeApp(): array
    {
        // Meta exige WABA ID (não phone_number_id) no endpoint de subscribed_apps.
        // Sem isso o webhook NUNCA é registrado e nenhuma mensagem inbound chega.
        // Referência: https://developers.facebook.com/docs/graph-api/webhooks/reference/whatsapp-business-account/
        if ($this->wabaId === '') {
            Log::error('WhatsappCloudService::subscribeApp() — waba_id ausente na instância', [
                'phone_number_id' => $this->phoneNumberId,
            ]);
            return ['error' => 'waba_id_missing'];
        }

        return $this->parse(
            $this->client()->post("{$this->baseUrl}/{$this->wabaId}/subscribed_apps", [])
        );
    }

    /**
     * Remove a inscrição do app como receptor de webhooks desta WABA.
     * Usado pelo disconnect pra não deixar "lixo" do lado da Meta.
     */
    public function unsubscribeApp(): array
    {
        if ($this->wabaId === '') {
            return ['error' => 'waba_id_missing'];
        }

        return $this->parse(
            $this->client()->delete("{$this->baseUrl}/{$this->wabaId}/subscribed_apps")
        );
    }

    // ── Phone numbers ───────────────────────────────────────────────────────

    /**
     * Lista os phone numbers vinculados a um WABA.
     * Usado pelo callback do Embedded Signup pra escolher qual número conectar.
     */
    public function listPhoneNumbers(string $wabaId): array
    {
        return $this->parse(
            $this->client()->get("{$this->baseUrl}/{$wabaId}/phone_numbers", [
                'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status',
            ])
        );
    }

    // ── Message Templates (HSM) ──────────────────────────────────────────────

    /**
     * Lista templates da WABA. Pagina via cursor até exaurir.
     * @return array lista de objetos crus da Meta
     */
    public function listTemplates(): array
    {
        if ($this->wabaId === '') {
            return ['error' => 'waba_id_missing', 'data' => []];
        }

        $all   = [];
        $after = null;
        $pages = 0;

        do {
            $query = [
                'fields' => 'id,name,status,category,language,components,quality_score,rejected_reason',
                'limit'  => 100,
            ];
            if ($after) {
                $query['after'] = $after;
            }

            $resp = $this->parse(
                $this->client()->get("{$this->baseUrl}/{$this->wabaId}/message_templates", $query)
            );

            if (isset($resp['error']) && $resp['error'] === true) {
                return ['error' => true, 'status' => $resp['status'] ?? null, 'body' => $resp['body'] ?? null, 'data' => $all];
            }

            foreach ((array) ($resp['data'] ?? []) as $t) {
                $all[] = $t;
            }

            $after = $resp['paging']['cursors']['after'] ?? null;
            $next  = $resp['paging']['next'] ?? null;
            $pages++;
        } while ($after && $next && $pages < 20);

        return ['data' => $all];
    }

    /**
     * Cria um template na Meta. Retorna ['id' => ..., 'status' => 'PENDING', ...]
     * ou ['error' => ..., 'message' => ...] se Meta rejeitar o formato.
     */
    public function createTemplate(string $name, string $language, string $category, array $components): array
    {
        if ($this->wabaId === '') {
            return ['error' => 'waba_id_missing'];
        }

        return $this->parse(
            $this->client()->post("{$this->baseUrl}/{$this->wabaId}/message_templates", [
                'name'       => $name,
                'language'   => $language,
                'category'   => $category,
                'components' => $components,
            ])
        );
    }

    /**
     * Deleta template. Se hsmId for passado, deleta só aquele idioma específico;
     * se só o name, deleta todos os idiomas daquele nome.
     */
    public function deleteTemplate(string $name, ?string $hsmId = null): array
    {
        if ($this->wabaId === '') {
            return ['error' => 'waba_id_missing'];
        }

        $query = ['name' => $name];
        if ($hsmId) {
            $query['hsm_id'] = $hsmId;
        }

        return $this->parse(
            $this->client()->delete("{$this->baseUrl}/{$this->wabaId}/message_templates", $query)
        );
    }

    // ── Helpers internos ─────────────────────────────────────────────────────

    private function sendMessage(array $payload): array
    {
        $response = $this->client()->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", $payload);
        $parsed = $this->parse($response);

        // Normaliza retorno: retorna sempre 'id' no top-level (igual ao WAHA pra facilitar dedup)
        if (isset($parsed['messages'][0]['id'])) {
            $parsed['id'] = $parsed['messages'][0]['id'];
        }
        return $parsed;
    }

    /**
     * Upload de mídia local pro endpoint /media da Cloud API.
     * Retorna o media_id retornado pela Meta.
     */
    private function uploadMedia(string $filePath, string $mimeType, ?string $filename = null): ?string
    {
        if (! is_file($filePath)) {
            Log::warning('WhatsappCloud uploadMedia: arquivo não existe', ['path' => $filePath]);
            return null;
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(60)
                ->attach(
                    'file',
                    file_get_contents($filePath),
                    $filename ?? basename($filePath),
                    ['Content-Type' => $mimeType],
                )
                ->post("{$this->baseUrl}/{$this->phoneNumberId}/media", [
                    'messaging_product' => 'whatsapp',
                    'type'              => $mimeType,
                ]);

            if ($response->successful()) {
                return $response->json('id');
            }

            Log::warning('WhatsappCloud uploadMedia: falha', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::warning('WhatsappCloud uploadMedia: exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Faz download de mídia recebida via webhook.
     * Cloud API só envia o media_id no webhook — precisa fazer 2 chamadas:
     *   1) GET /{media_id} → retorna URL temporária
     *   2) GET URL temporária → retorna o binário
     *
     * @return array{url:?string, mime:?string, size:?int}
     */
    public function getMediaInfo(string $mediaId): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/{$mediaId}", [
                'fields' => 'url,mime_type,sha256,file_size',
            ]);
            $data = $this->parse($response);
            return [
                'url'  => $data['url']       ?? null,
                'mime' => $data['mime_type'] ?? null,
                'size' => $data['file_size'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['url' => null, 'mime' => null, 'size' => null];
        }
    }

    /**
     * Faz download do binário de uma mídia (GET na URL retornada por getMediaInfo).
     * O endpoint exige Authorization header.
     */
    public function downloadMediaBinary(string $url): ?string
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(60)
                ->get($url);

            return $response->successful() ? $response->body() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Cloud API espera o número PURO (sem @c.us). Aceita também com @c.us
     * por compatibilidade com código que usa o pattern WAHA.
     */
    private function normalizeChatId(string $chatId): string
    {
        // Remove @c.us, @s.whatsapp.net, @lid e outros sufixos
        $clean = preg_replace('/@.*$/', '', $chatId);
        // Remove + e qualquer não-dígito
        return preg_replace('/\D/', '', (string) $clean);
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($this->accessToken)
            ->acceptJson()
            ->timeout(30);
    }

    private function parse(Response $response): array
    {
        if ($response->failed()) {
            Log::warning('WhatsappCloud HTTP error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return [
                'error'  => true,
                'status' => $response->status(),
                'body'   => $response->body(),
            ];
        }

        $body = $response->body();
        if (empty($body)) {
            return ['success' => true];
        }

        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : ['raw' => $body];
    }
}
