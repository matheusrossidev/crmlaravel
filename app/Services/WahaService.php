<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WahaService
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
                'events' => ['message', 'message.any', 'message.reaction', 'message.ack', 'message.revoked', 'session.status'],
            ];
            if ($webhookSecret) {
                $webhook['hmac'] = ['key' => $webhookSecret];
            }
            $webhooks[] = $webhook;
        }

        return $this->post('/api/sessions', [
            'name'   => $this->session,
            'config' => ['webhooks' => $webhooks],
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
            'events' => ['message', 'message.any', 'message.reaction', 'message.ack', 'message.revoked', 'session.status'],
        ];
        if ($webhookSecret) {
            $webhook['hmac'] = ['key' => $webhookSecret];
        }

        return $this->patch("/api/sessions/{$this->session}", [
            'config' => ['webhooks' => [$webhook]],
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
     * Fetches the profile picture URL for a contact or group.
     * Returns null if the picture is private or unavailable.
     * contactJid format: "556192008997@c.us" or "120363...@g.us"
     */
    public function getContactPicture(string $contactJid): ?string
    {
        try {
            $result = $this->get('/api/contacts/profile-picture', [
                'session'   => $this->session,
                'contactId' => $contactJid,
            ]);
            return $result['profilePictureURL'] ?? $result['url'] ?? $result['eurl'] ?? null;
        } catch (\Throwable) {
            return null;
        }
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
            'file'    => ['data' => "data:{$mimeType};base64,{$base64}", 'mimetype' => $mimeType],
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
        return $this->post('/api/sendVoice', [
            'session' => $this->session,
            'chatId'  => $chatId,
            'file'    => ['data' => "data:{$mimeType};base64,{$base64}", 'mimetype' => $mimeType],
            'convert' => true,
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

    public function getChatMessages(string $chatId, int $limit = 50): array
    {
        return $this->get("/api/{$this->session}/chats/{$chatId}/messages", [
            'limit'         => $limit,
            'downloadMedia' => 'true',
        ]);
    }

    // ── Webhook ───────────────────────────────────────────────────────────────

    public function setWebhook(string $url, array $events = []): array
    {
        if (empty($events)) {
            $events = ['message', 'message.any', 'message.reaction', 'message.ack', 'message.revoked', 'session.status'];
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
}
