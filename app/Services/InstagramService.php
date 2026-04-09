<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramService
{
    private string $accessToken;
    private string $apiVersion = 'v25.0';
    private string $baseUrl    = 'https://graph.instagram.com';

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    // ── Messaging ─────────────────────────────────────────────────────────────

    /**
     * Send a text DM to an Instagram user identified by IGSID.
     * Requires instagram_business_manage_messages permission.
     */
    public function sendMessage(string $igsid, string $text): array
    {
        return $this->post('/me/messages', [
            'recipient' => ['id' => $igsid],
            'message'   => ['text' => $text],
        ]);
    }

    /**
     * Send an image DM via a publicly-accessible URL.
     * Requires instagram_business_manage_messages permission.
     */
    public function sendImageAttachment(string $igsid, string $url): array
    {
        return $this->post('/me/messages', [
            'recipient' => ['id' => $igsid],
            'message'   => [
                'attachment' => [
                    'type'    => 'image',
                    'payload' => ['url' => $url],
                ],
            ],
        ]);
    }

    /**
     * Send a text DM with Quick Reply buttons (up to 13 buttons, titles max 20 chars).
     * Requires instagram_business_manage_messages permission.
     */
    public function sendMessageWithButtons(string $igsid, string $text, array $buttons): array
    {
        $quickReplies = array_values(array_map(
            fn (string $btn, int $i) => [
                'content_type' => 'text',
                'title'        => mb_substr($btn, 0, 20),
                'payload'      => 'BTN_' . $i,
            ],
            $buttons,
            array_keys($buttons),
        ));

        return $this->post('/me/messages', [
            'recipient' => ['id' => $igsid],
            'message'   => [
                'text'          => $text,
                'quick_replies' => $quickReplies,
            ],
        ]);
    }

    /**
     * Send a Button Template DM (up to 3 buttons: web_url or postback).
     * Each button: ['type' => 'web_url'|'postback', 'title' => '...', 'url' => '...' | 'payload' => '...']
     * @see https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/messaging-api/button-template/
     */
    public function sendButtonTemplate(string $igsid, string $text, array $buttons): array
    {
        return $this->post('/me/messages', [
            'recipient' => ['id' => $igsid],
            'message'   => [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text'          => mb_substr($text, 0, 640),
                        'buttons'       => array_slice($buttons, 0, 3),
                    ],
                ],
            ],
        ]);
    }

    /**
     * Send a Private Reply DM triggered by a comment.
     * Uses recipient.comment_id instead of recipient.id — required by Instagram
     * when the user has NOT messaged the page first (comment-triggered automations).
     * Only supports text (no images, no quick_replies).
     */
    public function sendPrivateReply(string $commentId, string $text): array
    {
        return $this->post('/me/messages', [
            'recipient' => ['comment_id' => $commentId],
            'message'   => ['text' => $text],
        ]);
    }

    // ── Profile ───────────────────────────────────────────────────────────────

    /**
     * Lista as conversations atuais da conta. UNICO caminho que funciona no fluxo
     * "Instagram API with Instagram Login" (graph.instagram.com + scopes
     * instagram_business_*) pra descobrir contatos.
     *
     * Combinado com getConversationParticipants($conversationId), permite mapear
     * IGSID -> username sem depender do endpoint GET /{IGSID} (que retorna
     * 100/33 "does not support this operation" no fluxo novo) nem do endpoint
     * GET /{message_id}?fields=from (que tambem retorna 100/33 — confirmado
     * empiricamente em prod 08/04/2026).
     *
     * Doc oficial:
     * developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/conversations-api
     *
     * Resposta:
     * {
     *   "data": [
     *     { "id": "aWdGGiblWZ...", "updated_time": "..." },
     *     ...
     *   ],
     *   "paging": { "cursors": { "before": "...", "after": "..." } }
     * }
     */
    public function listConversations(int $limit = 20, ?string $after = null): array
    {
        $params = [
            'platform' => 'instagram',
            'limit'    => $limit,
        ];
        if ($after) {
            $params['after'] = $after;
        }
        return $this->get('/me/conversations', $params);
    }

    /**
     * Busca os participantes de uma conversation. Retorna o array completo de
     * participants com `id` (IGSID) e `username` pra cada user (incluindo o
     * proprio business).
     *
     * Resposta:
     * {
     *   "id": "aWdGGiblWZ...",
     *   "participants": {
     *     "data": [
     *       { "id": "<IGSID_business>", "username": "syncrocrm" },
     *       { "id": "<IGSID_contato>",  "username": "mrodriguesrossi" }
     *     ]
     *   }
     * }
     *
     * Limitacao: NAO retorna name (display name) nem profile_pic — limitacao
     * tecnica documentada do flow Instagram Login. UI usa @username como label
     * e avatar fica fallback de letra.
     */
    public function getConversationParticipants(string $conversationId): array
    {
        return $this->get("/{$conversationId}", [
            'fields' => 'participants',
        ]);
    }

    /**
     * @deprecated Endpoint NAO funciona no fluxo Instagram Login (caminho novo).
     * Sempre retorna erro 100/33 "does not support this operation". Confirmado
     * empiricamente em prod (08/04/2026) com IGSID de DM real legitima.
     *
     * Use listConversations() + getConversationParticipants() que e o caminho
     * documentado oficialmente pra Instagram API with Instagram Login.
     */
    public function getProfile(string $igsid): array
    {
        return $this->get("/{$igsid}", [
            'fields' => 'name,username,profile_pic',
        ]);
    }

    /**
     * Returns the profile picture URL or null on failure.
     */
    public function getProfilePicture(string $igsid): ?string
    {
        try {
            $data = $this->getProfile($igsid);
            return $data['profile_pic'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Account ───────────────────────────────────────────────────────────────

    /**
     * Fetch the connected Instagram Business account (me).
     */
    public function getMe(): array
    {
        // Instagram Login API (v18+) usa user_id; fallback para id
        $result = $this->get('/me', ['fields' => 'user_id,username,name']);

        // Se falhou, tentar com campos antigos (Facebook Login flow)
        if (isset($result['error']) && $result['error'] === true) {
            Log::channel('instagram')->info('getMe() fallback: tentando campos antigos…');
            $result = $this->get('/me', ['fields' => 'id,username,profile_picture_url,name']);
        }

        return $result;
    }

    /**
     * Fetch the Facebook-platform Business Account ID (used by webhooks in entry.id).
     * This is different from the Instagram Login API user ID.
     * Returns null if the token doesn't have access to graph.facebook.com.
     */
    public function getBusinessAccountId(): ?string
    {
        try {
            $response = Http::timeout(15)->get('https://graph.facebook.com/' . $this->apiVersion . '/me', [
                'fields'       => 'id',
                'access_token' => $this->accessToken,
            ]);

            if ($response->successful()) {
                $id = $response->json('id');
                return $id ? (string) $id : null;
            }
        } catch (\Throwable) {
        }

        return null;
    }

    /**
     * Subscribe this account to receive webhook events (required after OAuth).
     * Without this call, Meta does NOT send DM/comment webhooks for this account.
     * Must be sent as form-urlencoded (not JSON) — that's why we use asForm().
     */
    public function subscribeToWebhooks(): array
    {
        return $this->postForm('/me/subscribed_apps', [
            'subscribed_fields' => 'messages,comments',
        ]);
    }

    /**
     * Fetch the user's media posts (feed) with cursor-based pagination.
     * Requires instagram_business_basic permission.
     */
    public function getUserMedia(?string $after = null): array
    {
        $params = [
            'fields' => 'id,caption,media_url,thumbnail_url,timestamp,media_type,permalink',
        ];
        if ($after) {
            $params['after'] = $after;
        }
        return $this->get('/me/media', $params);
    }

    /**
     * Reply to a comment on the account's media.
     * Requires instagram_business_manage_comments permission.
     */
    public function replyToComment(string $commentId, string $message): array
    {
        return $this->post("/{$commentId}/replies", ['message' => $message]);
    }

    // ── Token exchange ────────────────────────────────────────────────────────

    /**
     * Exchange a short-lived token for a long-lived token (60 days).
     */
    public static function exchangeToken(string $shortLived): array
    {
        $response = Http::get('https://graph.facebook.com/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => config('services.instagram.client_id'),
            'client_secret'     => config('services.instagram.client_secret'),
            'fb_exchange_token' => $shortLived,
        ]);

        return $response->successful() ? ($response->json() ?? []) : [];
    }

    // ── HTTP helpers ──────────────────────────────────────────────────────────

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl("{$this->baseUrl}/{$this->apiVersion}")
            ->acceptJson()
            ->timeout(30);
    }

    private function get(string $path, array $query = []): array
    {
        $query['access_token'] = $this->accessToken;
        $response = $this->client()->get($path, $query);

        if ($response->failed()) {
            Log::channel('instagram')->warning('InstagramService GET failed', [
                'path'   => $path,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return ['error' => true, 'status' => $response->status(), 'body' => $response->body()];
        }

        return $response->json() ?? [];
    }

    private function post(string $path, array $data): array
    {
        $data['access_token'] = $this->accessToken;
        $response = $this->client()->post($path, $data);

        if ($response->failed()) {
            Log::channel('instagram')->warning('InstagramService POST failed', [
                'path'   => $path,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return ['error' => true, 'status' => $response->status(), 'body' => $response->body()];
        }

        return $response->json() ?? [];
    }

    private function postForm(string $path, array $data): array
    {
        $data['access_token'] = $this->accessToken;
        $response = $this->client()->asForm()->post($path, $data);

        if ($response->failed()) {
            Log::channel('instagram')->warning('InstagramService POST (form) failed', [
                'path'   => $path,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return ['error' => true, 'status' => $response->status(), 'body' => $response->body()];
        }

        return $response->json() ?? [];
    }
}
