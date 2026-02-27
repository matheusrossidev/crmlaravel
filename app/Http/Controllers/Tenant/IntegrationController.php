<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Jobs\SyncCampaignsJob;
use App\Models\InstagramInstance;
use App\Models\OAuthConnection;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\InstagramService;
use App\Services\WahaService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class IntegrationController extends Controller
{
    public function index(): View
    {
        $connections = OAuthConnection::whereIn('status', ['active', 'expired'])
            ->get()
            ->keyBy('platform');

        $facebook  = $connections->get('facebook');
        $google    = $connections->get('google');
        $whatsapp  = WhatsappInstance::first();
        $instagram = InstagramInstance::first();

        return view('tenant.settings.integrations', compact('facebook', 'google', 'whatsapp', 'instagram'));
    }

    // ── Facebook ──────────────────────────────────────────────────────────────

    public function redirectFacebook(): RedirectResponse
    {
        return Socialite::driver('facebook')
            ->setScopes(['public_profile', 'ads_read'])
            ->redirect();
    }

    public function callbackFacebook(): RedirectResponse
    {
        try {
            $user = Socialite::driver('facebook')->user();
        } catch (\Throwable) {
            return redirect()->route('settings.integrations.index')
                ->with('error', 'Autenticação com o Facebook falhou. Tente novamente.');
        }

        // Troca short-lived token por long-lived (60 dias)
        $longLived = $this->exchangeFacebookToken($user->token);

        $tenant = auth()->user()->tenant;

        OAuthConnection::updateOrCreate(
            ['tenant_id' => $tenant->id, 'platform' => 'facebook'],
            [
                'platform_user_id'   => $user->getId(),
                'platform_user_name' => $user->getName(),
                'access_token'       => $longLived['token'] ?? $user->token,
                'refresh_token'      => null,
                'token_expires_at'   => isset($longLived['expires_in'])
                    ? now()->addSeconds((int) $longLived['expires_in'])
                    : now()->addDays(60),
                'scopes_json'        => ['public_profile', 'ads_read'],
                'status'             => 'active',
            ]
        );

        SyncCampaignsJob::dispatch($tenant, 'facebook');

        return redirect()->route('settings.integrations.index')
            ->with('success', 'Facebook Ads conectado com sucesso!');
    }

    // ── Google ────────────────────────────────────────────────────────────────

    public function redirectGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes([
                'openid',
                'email',
                'profile',
                'https://www.googleapis.com/auth/adwords',
                'https://www.googleapis.com/auth/calendar',
            ])
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }

    public function callbackGoogle(): RedirectResponse
    {
        try {
            $user = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('settings.integrations.index')
                ->with('error', 'Autenticação com o Google falhou. Tente novamente.');
        }

        $tenant = auth()->user()->tenant;

        OAuthConnection::updateOrCreate(
            ['tenant_id' => $tenant->id, 'platform' => 'google'],
            [
                'platform_user_id'   => $user->getId(),
                'platform_user_name' => $user->getName(),
                'access_token'       => $user->token,
                'refresh_token'      => $user->refreshToken,
                'token_expires_at'   => $user->expiresIn
                    ? now()->addSeconds((int) $user->expiresIn)
                    : now()->addHour(),
                'scopes_json'        => ['openid', 'email', 'profile', 'https://www.googleapis.com/auth/adwords', 'https://www.googleapis.com/auth/calendar'],
                'status'             => 'active',
            ]
        );

        SyncCampaignsJob::dispatch($tenant, 'google');

        return redirect()->route('settings.integrations.index')
            ->with('success', 'Google Ads conectado com sucesso!');
    }

    // ── Disconnect / Sync ─────────────────────────────────────────────────────

    public function disconnect(string $platform): JsonResponse
    {
        OAuthConnection::where('platform', $platform)
            ->update(['status' => 'revoked']);

        return response()->json(['success' => true]);
    }

    public function syncNow(string $platform): JsonResponse
    {
        $conn = OAuthConnection::where('platform', $platform)
            ->whereIn('status', ['active', 'expired'])
            ->first();

        if (! $conn) {
            return response()->json(['success' => false, 'message' => 'Nenhuma conexão ativa encontrada.'], 404);
        }

        SyncCampaignsJob::dispatch(auth()->user()->tenant, $platform);

        return response()->json(['success' => true, 'message' => 'Sincronização iniciada.']);
    }

    // ── WhatsApp ──────────────────────────────────────────────────────────────

    public function connectWhatsapp(): JsonResponse
    {
        $tenant  = auth()->user()->tenant;
        $session = 'tenant_' . $tenant->id;

        try {
            $instance = WhatsappInstance::firstOrCreate(
                ['tenant_id' => $tenant->id],
                ['session_name' => $session, 'status' => 'disconnected']
            );

            // Monta webhook URL usando APP_URL para garantir https://
            $webhookUrl    = rtrim(config('app.url'), '/') . '/webhook/whatsapp';
            $webhookSecret = (string) config('services.waha.webhook_secret', '');

            $waha   = new WahaService($instance->session_name);
            $result = $waha->createSession($webhookUrl, $webhookSecret);

            if (isset($result['error'])) {
                if (($result['status'] ?? 0) === 422) {
                    // Sessão já existe no WAHA — atualiza webhook e (re)inicia
                    $waha->patchSession($webhookUrl, $webhookSecret);
                    $waha->startSession();
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Falha ao criar sessão no WAHA: ' . ($result['body'] ?? 'erro desconhecido'),
                    ], 500);
                }
            } else {
                // Sessão criada com sucesso — precisa iniciar (WAHA cria em estado STOPPED)
                $waha->startSession();
            }

            $instance->update(['status' => 'qr']);

            return response()->json(['success' => true, 'session_name' => $instance->session_name]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao conectar com o WAHA: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getWhatsappQr(): JsonResponse
    {
        $instance = WhatsappInstance::first();

        if (! $instance) {
            return response()->json(['error' => 'Instância não encontrada.'], 404);
        }

        $waha     = new WahaService($instance->session_name);
        $response = $waha->getQrResponse();

        if ($response->failed()) {
            // QR indisponível — verificar se a sessão já está conectada
            $session    = $waha->getSession();
            $wahaStatus = $session['status'] ?? null;

            if ($wahaStatus === 'WORKING') {
                $instance->update(['status' => 'connected']);
                return response()->json(['status' => 'connected']);
            }

            return response()->json(['status' => $instance->status, 'qr_base64' => null]);
        }

        // Atualizar status se necessário
        if ($instance->status !== 'qr') {
            $instance->update(['status' => 'qr']);
        }

        // WAHA retorna PNG binário quando format=image
        $contentType = $response->header('Content-Type') ?? '';
        if (str_contains($contentType, 'image/')) {
            return response()->json([
                'status'    => 'qr',
                'qr_base64' => base64_encode($response->body()),
            ]);
        }

        // Fallback: JSON com campo "value", "qr" ou "data" (varia conforme versão do WAHA)
        $json     = $response->json() ?? [];
        $qrBase64 = $json['value'] ?? $json['qr'] ?? $json['data'] ?? null;
        return response()->json([
            'status'    => 'qr',
            'qr_base64' => $qrBase64,
        ]);
    }

    public function importHistoryWhatsapp(): JsonResponse
    {
        $instance = WhatsappInstance::first();

        if (! $instance || $instance->status !== 'connected') {
            return response()->json(['success' => false, 'message' => 'WhatsApp não está conectado.'], 422);
        }

        try {
            $waha             = new WahaService($instance->session_name);
            $importedChats    = 0;
            $importedMessages = 0;
            $skipped          = 0;

            $chatLimit = 50;
            $chatOffset = 0;

            do {
                $chats = $waha->getChats($chatLimit, $chatOffset);

                if (isset($chats['error']) || ! is_array($chats) || empty($chats)) {
                    break;
                }

                foreach ($chats as $chat) {
                    if (! is_array($chat) || empty($chat['id'])) {
                        continue;
                    }

                    $chatId      = $chat['id'];
                    $isGroup     = (bool) ($chat['isGroup'] ?? false);
                    $contactName = $chat['name'] ?? null;
                    $phone       = $this->normalizePhone($chatId);

                    if ($phone === '') {
                        continue;
                    }

                    // Busca ou cria conversa
                    $conv = WhatsappConversation::withoutGlobalScope('tenant')
                        ->where('tenant_id', $instance->tenant_id)
                        ->where('phone', $phone)
                        ->first();

                    if (! $conv) {
                        $conv = WhatsappConversation::withoutGlobalScope('tenant')->create([
                            'tenant_id'      => $instance->tenant_id,
                            'instance_id'    => $instance->id,
                            'phone'          => $phone,
                            'is_group'       => $isGroup,
                            'contact_name'   => $contactName,
                            'status'         => 'open',
                            'started_at'     => now(),
                            'last_message_at'=> now(),
                            'unread_count'   => 0,
                        ]);
                        $importedChats++;
                    }

                    // Buscar mensagens (sem download de mídia, máx 200 por chat)
                    $msgs = $waha->getChatMessages($chatId, 200, 0, false);

                    if (is_array($msgs) && ! isset($msgs['error'])) {
                        foreach ($msgs as $msg) {
                            if (! is_array($msg) || empty($msg['id'])) {
                                continue;
                            }

                            $rawType  = $msg['type'] ?? 'chat';
                            $type     = match ($rawType) {
                                'image'               => 'image',
                                'audio', 'ptt'        => 'audio',
                                'video'               => 'video',
                                'document', 'sticker' => 'document',
                                default               => 'text',
                            };

                            $ts = isset($msg['timestamp']) ? (int) $msg['timestamp'] : null;
                            $sentAt = $ts
                                ? Carbon::createFromTimestamp($ts, config('app.timezone', 'America/Sao_Paulo'))
                                : now();

                            try {
                                WhatsappMessage::withoutGlobalScope('tenant')->create([
                                    'tenant_id'       => $instance->tenant_id,
                                    'conversation_id' => $conv->id,
                                    'waha_message_id' => $msg['id'],
                                    'direction'       => ($msg['fromMe'] ?? false) ? 'outbound' : 'inbound',
                                    'type'            => $type,
                                    'body'            => $msg['body'] ?? null,
                                    'ack'             => 'delivered',
                                    'sent_at'         => $sentAt,
                                ]);
                                $importedMessages++;
                            } catch (QueryException) {
                                $skipped++;
                            }
                        }

                        // Atualiza last_message_at com a mensagem mais recente
                        $latestSentAt = WhatsappMessage::withoutGlobalScope('tenant')
                            ->where('conversation_id', $conv->id)
                            ->orderByDesc('sent_at')
                            ->value('sent_at');

                        if ($latestSentAt) {
                            WhatsappConversation::withoutGlobalScope('tenant')
                                ->where('id', $conv->id)
                                ->update(['last_message_at' => $latestSentAt]);
                        }
                    }
                }

                $chatOffset += $chatLimit;
            } while (count($chats) >= $chatLimit);

            Log::info('WhatsApp history import', [
                'tenant_id'        => $instance->tenant_id,
                'imported_chats'   => $importedChats,
                'imported_messages'=> $importedMessages,
                'skipped'          => $skipped,
            ]);

            return response()->json([
                'success'          => true,
                'imported_chats'   => $importedChats,
                'imported_messages'=> $importedMessages,
                'skipped'          => $skipped,
            ]);

        } catch (\Throwable $e) {
            Log::error('WhatsApp history import failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao importar histórico: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function disconnectWhatsapp(): JsonResponse
    {
        $instance = WhatsappInstance::first();

        if ($instance) {
            $waha = new WahaService($instance->session_name);
            $waha->stopSession();
            $waha->deleteSession();
            $instance->update(['status' => 'disconnected', 'phone_number' => null, 'display_name' => null]);
        }

        return response()->json(['success' => true]);
    }

    // ── Instagram ─────────────────────────────────────────────────────────────

    public function redirectInstagram(): RedirectResponse
    {
        $clientId    = (string) config('services.instagram.client_id');
        $redirectUri = (string) config('services.instagram.redirect');

        $params = http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'scope'         => 'instagram_business_basic,instagram_business_manage_messages,instagram_business_manage_comments',
            'response_type' => 'code',
        ]);

        return redirect("https://www.instagram.com/oauth/authorize?{$params}");
    }

    public function callbackInstagram(): RedirectResponse
    {
        Log::channel('instagram')->info('OAuth callback iniciado', [
            'has_code'  => (bool) request()->get('code'),
            'has_error' => (bool) request()->get('error'),
            'error_msg' => request()->get('error_description'),
        ]);

        $code = request()->get('code');

        if (! $code) {
            $errorDesc = request()->get('error_description', 'Autorização negada pelo Instagram.');
            Log::channel('instagram')->warning('OAuth callback sem code', ['error' => $errorDesc]);
            return redirect()->route('settings.integrations.index')
                ->with('error', 'Autorização negada pelo Instagram.');
        }

        try {
            // 1. Trocar code por short-lived token
            Log::channel('instagram')->info('Trocando code por token…');
            $tokenResponse = Http::timeout(15)->asForm()->post('https://www.instagram.com/oauth/access_token', [
                'client_id'     => config('services.instagram.client_id'),
                'client_secret' => config('services.instagram.client_secret'),
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => config('services.instagram.redirect'),
                'code'          => $code,
            ]);

            Log::channel('instagram')->info('Resposta token exchange', [
                'status' => $tokenResponse->status(),
                'body'   => $tokenResponse->body(),
            ]);

            if ($tokenResponse->failed()) {
                return redirect()->route('settings.integrations.index')
                    ->with('error', 'Falha ao obter token do Instagram: ' . $tokenResponse->body());
            }

            $shortToken  = $tokenResponse->json('access_token');
            $igAccountId = (string) ($tokenResponse->json('user_id') ?? '');

            if (! $shortToken) {
                Log::channel('instagram')->error('Token não retornado pela API', ['body' => $tokenResponse->body()]);
                return redirect()->route('settings.integrations.index')
                    ->with('error', 'Instagram não retornou token de acesso.');
            }

            // 2. Trocar por long-lived token (60 dias)
            Log::channel('instagram')->info('Trocando por long-lived token…');
            $longTokenResponse = Http::timeout(15)->get('https://graph.instagram.com/access_token', [
                'grant_type'    => 'ig_exchange_token',
                'client_secret' => config('services.instagram.client_secret'),
                'access_token'  => $shortToken,
            ]);

            Log::channel('instagram')->info('Resposta long-lived token', [
                'status' => $longTokenResponse->status(),
                'body'   => $longTokenResponse->body(),
            ]);

            $accessToken = $longTokenResponse->successful()
                ? ($longTokenResponse->json('access_token') ?? $shortToken)
                : $shortToken;

            $expiresIn = $longTokenResponse->json('expires_in');

            // 3. Buscar username/foto da conta
            Log::channel('instagram')->info('Buscando perfil da conta…');
            $service = new InstagramService($accessToken);
            $me      = $service->getMe();

            Log::channel('instagram')->info('Perfil obtido', [
                'ig_account_id' => $igAccountId,
                'me_response'   => $me,
            ]);

            if (isset($me['error']) && $me['error'] === true) {
                return redirect()->route('settings.integrations.index')
                    ->with('error', 'Token obtido mas falhou ao buscar perfil da conta: ' . ($me['body'] ?? ''));
            }

            $username   = $me['username'] ?? null;
            $pictureUrl = $me['profile_picture_url'] ?? null;

            // Buscar o ID no formato Facebook/Meta (usado pelo webhook em entry.id)
            $businessAccountId = $service->getBusinessAccountId();
            Log::channel('instagram')->info('IDs da conta', [
                'ig_login_id'       => $igAccountId,
                'ig_business_id'    => $businessAccountId,
            ]);

            $tenant = auth()->user()->tenant;

            InstagramInstance::withoutGlobalScope('tenant')->updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'instagram_account_id'   => $igAccountId,
                    'ig_business_account_id' => $businessAccountId,
                    'username'               => $username,
                    'profile_picture_url'    => $pictureUrl,
                    'access_token'           => encrypt($accessToken),
                    'token_expires_at'       => $expiresIn
                        ? now()->addSeconds((int) $expiresIn)
                        : now()->addDays(60),
                    'status'                 => 'connected',
                ]
            );

            Log::channel('instagram')->info('Instagram conectado com sucesso', [
                'tenant_id' => $tenant->id,
                'username'  => $username,
            ]);

            // Subscrever conta para receber eventos de webhook (form-urlencoded)
            try {
                $subResult = $service->subscribeToWebhooks();
                if (! empty($subResult['error'])) {
                    Log::channel('instagram')->warning('Falha ao subscrever webhooks (não crítico)', [
                        'response' => $subResult,
                    ]);
                } else {
                    Log::channel('instagram')->info('Webhook subscribed', ['result' => $subResult]);
                }
            } catch (\Throwable $subEx) {
                Log::channel('instagram')->warning('Exceção ao subscrever webhooks (não crítico)', [
                    'error' => $subEx->getMessage(),
                ]);
            }

            return redirect()->route('settings.integrations.index')
                ->with('success', 'Instagram conectado com sucesso!');

        } catch (\Throwable $e) {
            Log::channel('instagram')->error('OAuth callback falhou', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile() . ':' . $e->getLine(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 1000),
            ]);
            return redirect()->route('settings.integrations.index')
                ->with('error', 'Erro ao conectar Instagram: ' . $e->getMessage());
        }
    }

    public function disconnectInstagram(): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        InstagramInstance::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->delete();

        return response()->json(['success' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function normalizePhone(string $jid): string
    {
        $phone = preg_replace('/[:@].+$/', '', $jid);
        return ltrim((string) $phone, '+');
    }

    private function exchangeFacebookToken(string $shortLived): array
    {
        $response = Http::get('https://graph.facebook.com/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => config('services.facebook.client_id'),
            'client_secret'     => config('services.facebook.client_secret'),
            'fb_exchange_token' => $shortLived,
        ]);

        return $response->successful() ? $response->json() : ['token' => $shortLived];
    }
}
