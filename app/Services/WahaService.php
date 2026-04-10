<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\WhatsappServiceContract;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WahaService implements WhatsappServiceContract
{
    private string $baseUrl;
    private string $apiKey;
    private string $session;

    public function __construct(string $sessionName)
    {
        $this->baseUrl = rtrim((string) config('services.waha.base_url'), '/');
        $this->apiKey  = (string) config('services.waha.api_key');
        $this->session = $sessionName;
    }

    // ── Sessions ──────────────────────────────────────────────────────────────

    public function createSession(string $webhookUrl = '', string $webhookSecret = ''): array
    {
        $webhooks = [];
        if ($webhookUrl) {
            $webhook = [
                'url'    => $webhookUrl,
                'events' => ['message', 'message.any', 'message.ack', 'session.status'],
            ];
            if ($webhookSecret) {
                $webhook['hmac'] = ['key' => $webhookSecret];
            }
            $webhooks[] = $webhook;
        }

        return $this->post('/api/sessions', [
            'name'   => $this->session,
            'config' => [
                'webhooks' => $webhooks,
                'chats' => [
                    'filters' => [
                        'statuses'  => false,
                        'groups'    => true,
                        'channels'  => false,
                        'broadcast' => false,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Atualiza configuração de uma sessão existente (inclui webhook).
     * WAHA Plus: PATCH /api/sessions/{session}
     */
    public function patchSession(string $webhookUrl, string $webhookSecret = ''): array
    {
        $webhook = [
            'url'    => $webhookUrl,
            'events' => ['message', 'message.any', 'message.ack', 'session.status'],
        ];
        if ($webhookSecret) {
            $webhook['hmac'] = ['key' => $webhookSecret];
        }

        return $this->patch("/api/sessions/{$this->session}", [
            'config' => [
                'webhooks' => [$webhook],
                'chats' => [
                    'filters' => [
                        'statuses'  => false,
                        'groups'    => true,
                        'channels'  => false,
                        'broadcast' => false,
                    ],
                ],
            ],
        ]);
    }

    public function getSession(): array
    {
        return $this->get("/api/sessions/{$this->session}");
    }

    public function startSession(): array
    {
        return $this->post("/api/sessions/{$this->session}/start", []);
    }

    public function stopSession(): void
    {
        $this->post("/api/sessions/{$this->session}/stop", []);
    }

    public function deleteSession(): void
    {
        $this->delete("/api/sessions/{$this->session}");
    }

    // ── QR ────────────────────────────────────────────────────────────────────

    /**
     * Returns the raw HTTP response for the QR endpoint.
     * Uses a client WITHOUT acceptJson() so WAHA can return PNG binary with format=image.
     */
    public function getQrResponse(): \Illuminate\Http\Client\Response
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeader('X-Api-Key', $this->apiKey)
            ->timeout(30)
            ->get("/api/{$this->session}/auth/qr", ['format' => 'image']);
    }

    // ── Groups ────────────────────────────────────────────────────────────────

    /**
     * Fetches group metadata (name/subject) from WAHA.
     * groupJid format: "120363181130044902@g.us"
     */
    public function getGroupInfo(string $groupJid): array
    {
        $groupId = rawurlencode($groupJid);
        return $this->get("/api/{$this->session}/groups/{$groupId}");
    }

    // ── Contacts ──────────────────────────────────────────────────────────────

    /**
     * Fetches contact info from WAHA.
     * Used to resolve @lid JIDs to real phone numbers.
     * Response may contain 'id' field with the real JID (e.g. "556192...@c.us").
     */
    public function getContactInfo(string $contactJid): array
    {
        return $this->get('/api/contacts', [
            'session'   => $this->session,
            'contactId' => $contactJid,
        ]);
    }

    /**
     * Fetch picture for any chat (contact or group) via the correct WAHA endpoint.
     * GET /api/{session}/chats/{chatId}/picture
     * chatId format: "556192008997@c.us", "120363xxx@g.us", or "123456@lid"
     */
    public function getChatPicture(string $chatId): ?string
    {
        try {
            $encodedChatId = rawurlencode($chatId);
            $result = $this->get("/api/{$this->session}/chats/{$encodedChatId}/picture");
            return $result['profilePictureURL'] ?? $result['url'] ?? $result['eurl'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @deprecated Use getChatPicture() instead */
    public function getContactPicture(string $contactJid): ?string
    {
        return $this->getChatPicture($contactJid);
    }

    /** @deprecated Use getChatPicture() instead */
    public function getGroupPicture(string $groupJid): ?string
    {
        return $this->getChatPicture($groupJid);
    }

    // ── LIDs (WhatsApp internal IDs) ────────────────────────────────────────

    /**
     * Get ALL known LID→phone mappings in one request.
     * GET /api/{session}/lids
     */
    public function getAllLids(): array
    {
        return $this->get("/api/{$this->session}/lids");
    }

    /**
     * Resolve a single LID to phone number.
     * GET /api/{session}/lids/{lid}
     * @param string $lid e.g. "123456789@lid"
     */
    public function getPhoneByLid(string $lid): array
    {
        $encodedLid = rawurlencode($lid);
        return $this->get("/api/{$this->session}/lids/{$encodedLid}");
    }

    // ── Presence ─────────────────────────────────────────────────────────────

    public function setPresence(string $chatId, string $presence = 'typing'): array
    {
        return $this->post("/api/{$this->session}/presence", [
            'chatId'   => $chatId,
            'presence' => $presence,
        ]);
    }

    // ── Send Messages ─────────────────────────────────────────────────────────

    public function sendText(string $chatId, string $text): array
    {
        return $this->post('/api/sendText', [
            'session' => $this->session,
            'chatId'  => $chatId,
            'text'    => $text,
        ]);
    }

    /**
     * Send image via URL (WAHA fetches it). URL must be publicly reachable from the WAHA container.
     */
    public function sendImage(string $chatId, string $url, string $caption = ''): array
    {
        return $this->post('/api/sendImage', [
            'session' => $this->session,
            'chatId'  => $chatId,
            'file'    => ['url' => $url],
            'caption' => $caption,
        ]);
    }

    /**
     * Send image by uploading file content directly to WAHA (base64).
     * Use this when the public URL may not be reachable from the WAHA container.
     */
    public function sendImageBase64(string $chatId, string $filePath, string $mimeType, string $caption = ''): array
    {
        $base64 = base64_encode(file_get_contents($filePath));
        return $this->post('/api/sendImage', [
            'session' => $this->session,
            'chatId'  => $chatId,
            'file'    => [
                'data'     => $base64,
                'mimetype' => $mimeType,
                'filename' => basename($filePath),
            ],
            'caption' => $caption,
        ]);
    }

    /**
     * Send voice note via URL (WAHA fetches it).
     * convert: true asks WAHA to convert WebM/Opus to OGG/Opus (WhatsApp format).
     */
    public function sendVoice(string $chatId, string $url): array
    {
        return $this->post('/api/sendVoice', [
            'session' => $this->session,
            'chatId'  => $chatId,
            'file'    => ['url' => $url],
            'convert' => true,
        ]);
    }

    /**
     * Send voice note by uploading file content directly to WAHA (base64).
     * Use this when the public URL may not be reachable from the WAHA container.
     * convert: true asks WAHA to convert WebM/Opus (browser recording) to OGG/Opus (WhatsApp format).
     */
    public function sendVoiceBase64(string $chatId, string $filePath, string $mimeType): array
    {
        $base64 = base64_encode(file_get_contents($filePath));
        $response = $this->client()->timeout(90)->post('/api/sendVoice', [
            'session' => $this->session,
            'chatId'  => $chatId,
            'file'    => ['data' => $base64, 'mimetype' => $mimeType, 'filename' => 'audio.ogg'],
            'convert' => true,
        ]);
        return $this->parse($response);
    }

    /**
     * Send a file/document by uploading content directly to WAHA (base64).
     * Endpoint: POST /api/sendFile
     * Supports PDF, Word, Excel, ZIP, etc.
     */
    public function sendFileBase64(string $chatId, string $filePath, string $mimeType, string $filename, string $caption = ''): array
    {
        $base64 = base64_encode(file_get_contents($filePath));
        return $this->post('/api/sendFile', [
            'session' => $this->session,
            'chatId'  => $chatId,
            'file'    => [
                'data'     => "data:{$mimeType};base64,{$base64}",
                'mimetype' => $mimeType,
                'filename' => $filename,
            ],
            'caption' => $caption,
        ]);
    }

    /**
     * Send an interactive list message (menu with selectable options).
     * User taps the button to open the list and selects a row.
     * Response body = selected row title.
     */
    public function sendList(string $chatId, string $description, array $rows, ?string $title = null, string $buttonText = 'Ver opções', ?string $footer = null): array
    {
        return $this->post('/api/sendList', [
            'session' => $this->session,
            'chatId'  => $chatId,
            'message' => [
                'title'       => $title ?? '',
                'description' => $description,
                'footer'      => $footer ?? '',
                'button'      => $buttonText,
                'sections'    => [
                    [
                        'title' => '',
                        'rows'  => $rows,
                    ],
                ],
            ],
        ]);
    }

    public function sendReaction(string $messageId, string $emoji): array
    {
        return $this->put('/api/reaction', [
            'session'   => $this->session,
            'messageId' => $messageId,
            'reaction'  => $emoji,
        ]);
    }

    // ── Chats ─────────────────────────────────────────────────────────────────

    public function getChats(int $limit = 50, int $offset = 0): array
    {
        return $this->get("/api/{$this->session}/chats", [
            'limit'  => $limit,
            'offset' => $offset,
        ]);
    }

    public function getChatMessages(
        string $chatId,
        int $limit = 50,
        int $offset = 0,
        bool $downloadMedia = true,
        ?int $timestampGte = null,
    ): array {
        $params = [
            'limit'         => $limit,
            'offset'        => $offset,
            'downloadMedia' => $downloadMedia,
        ];

        if ($timestampGte !== null) {
            $params['filter.timestamp.gte'] = $timestampGte;
        }

        $encodedChatId = rawurlencode($chatId);

        // Timeout maior para import de histórico (pode retornar muitas mensagens)
        $response = Http::baseUrl($this->baseUrl)
            ->withHeader('X-Api-Key', $this->apiKey)
            ->acceptJson()
            ->timeout(60)
            ->get("/api/{$this->session}/chats/{$encodedChatId}/messages", $params);

        return $this->parse($response);
    }

    // ── Webhook ───────────────────────────────────────────────────────────────

    public function setWebhook(string $url, array $events = []): array
    {
        if (empty($events)) {
            $events = ['message', 'message.any', 'message.ack', 'session.status'];
        }

        return $this->put("/api/{$this->session}/webhooks", [
            'url'    => $url,
            'events' => $events,
        ]);
    }

    // ── HTTP helpers ──────────────────────────────────────────────────────────

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeader('X-Api-Key', $this->apiKey)
            ->acceptJson()
            ->timeout(30);
    }

    private function get(string $path, array $query = []): array
    {
        $response = $this->client()->get($path, $query);
        return $this->parse($response);
    }

    private function post(string $path, array $data): array
    {
        $response = $this->client()->post($path, $data);
        return $this->parse($response);
    }

    private function put(string $path, array $data): array
    {
        $response = $this->client()->put($path, $data);
        return $this->parse($response);
    }

    private function patch(string $path, array $data): array
    {
        $response = $this->client()->patch($path, $data);
        return $this->parse($response);
    }

    private function delete(string $path): void
    {
        $this->client()->delete($path);
    }

    private function parse(Response $response): array
    {
        if ($response->failed()) {
            return ['error' => true, 'status' => $response->status(), 'body' => $response->body()];
        }

        $body = $response->body();
        if (empty($body)) {
            return ['success' => true];
        }

        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : ['raw' => $body];
    }

    // ── WhatsappServiceContract ───────────────────────────────────────────────

    public function getProviderName(): string
    {
        return 'waha';
    }
}
