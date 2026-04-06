<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Jobs\ImportWhatsappHistory;
use App\Jobs\SyncCampaignsJob;
use App\Models\FacebookLeadFormConnection;
use App\Models\FeatureFlag;
use App\Models\InstagramInstance;
use App\Models\OAuthConnection;
use App\Models\Pipeline;
use App\Models\CustomFieldDefinition;
use App\Models\WhatsappButton;
use App\Models\WhatsappInstance;
use App\Services\FacebookLeadAdsService;
use App\Services\InstagramService;
use App\Services\PlanLimitChecker;
use App\Services\WahaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        $facebookLeadAds = $connections->get('facebook_leadads');
        // Card "WhatsApp Business" lista APENAS instâncias WAHA. Cloud API
        // é mostrada no card próprio "WhatsApp Cloud API" mais abaixo.
        // (NULL provider = rows legadas pré-refactor multi-provider, todas WAHA)
        $whatsappInstances = WhatsappInstance::query()
            ->where(function ($q) {
                $q->where('provider', 'waha')->orWhereNull('provider');
            })
            ->orderBy('id')
            ->get();
        $whatsapp          = $whatsappInstances->first(); // retrocompat
        $instagram         = InstagramInstance::where('status', '!=', 'disconnected')->first();

        $tenant = activeTenant();
        $s = $tenant->settings_json ?? [];
        $enabledIntegrations = [
            'whatsapp'         => $s['integration_whatsapp']        ?? true,
            'google_calendar'  => $s['integration_google_calendar'] ?? true,
            'instagram'        => $s['integration_instagram']       ?? true,
            'facebook_ads'     => $s['integration_facebook_ads']    ?? false,
            'google_ads'       => $s['integration_google_ads']      ?? false,
            'facebook_leadads'   => FeatureFlag::isEnabled('facebook_leadads', $tenant->id),
            'whatsapp_cloud_api' => FeatureFlag::isEnabled('whatsapp_cloud_api', $tenant->id),
        ];

        // Cloud API instances (provider='cloud_api') — separadas das WAHA
        $cloudApiInstances = WhatsappInstance::where('provider', 'cloud_api')->orderBy('id')->get();

        $maxWhatsappInstances    = $tenant->max_whatsapp_instances > 0 ? $tenant->max_whatsapp_instances : null;
        $whatsappInstancesRemain = PlanLimitChecker::remaining('whatsapp_instances');
        $waButtons = WhatsappButton::orderBy('id')->get();

        // Facebook Lead Ads form connections
        $fbLeadConnections = $facebookLeadAds
            ? FacebookLeadFormConnection::where('is_active', true)->with('pipeline', 'stage')->get()
            : collect();

        $pipelines    = Pipeline::with('stages:id,pipeline_id,name,position')->orderBy('sort_order')->get(['id', 'name']);
        $customFields = CustomFieldDefinition::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'label', 'field_type']);

        return view('tenant.settings.integrations', compact(
            'facebook', 'google', 'facebookLeadAds', 'whatsapp', 'whatsappInstances', 'instagram',
            'enabledIntegrations', 'maxWhatsappInstances', 'whatsappInstancesRemain', 'waButtons',
            'fbLeadConnections', 'pipelines', 'customFields', 'cloudApiInstances'
        ));
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

        $tenant = activeTenant();

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

        $tenant = activeTenant();

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
                'scopes_json'        => ['openid', 'email', 'profile', 'https://www.googleapis.com/auth/calendar'],
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

        SyncCampaignsJob::dispatch(activeTenant(), $platform);

        return response()->json(['success' => true, 'message' => 'Sincronização iniciada.']);
    }

    // ── WhatsApp ──────────────────────────────────────────────────────────────

    public function connectWhatsapp(Request $request): JsonResponse
    {
        $tenant = activeTenant();
        $label  = $request->input('label', '');

        // Se já existe alguma instância WAHA, verificar limite do plano
        // (Cloud API tem o card próprio e não conta pro limite do WAHA)
        $existingCount = WhatsappInstance::where('tenant_id', $tenant->id)
            ->where(function ($q) {
                $q->where('provider', 'waha')->orWhereNull('provider');
            })
            ->count();
        if ($existingCount > 0) {
            $limitMsg = PlanLimitChecker::check('whatsapp_instances');
            if ($limitMsg) {
                return response()->json(['success' => false, 'message' => $limitMsg, 'limit_reached' => true], 422);
            }
        }

        $suffix  = $existingCount > 0 ? '_' . ($existingCount + 1) : '';
        $session = Str::slug($tenant->name, '_') . '_' . $tenant->id . $suffix;

        try {
            $instance = WhatsappInstance::create([
                'tenant_id'    => $tenant->id,
                'session_name' => $session,
                'status'       => 'disconnected',
                'label'        => $label ?: null,
            ]);

            // Monta webhook URL usando APP_URL para garantir https://
            $webhookUrl    = rtrim(config('app.url'), '/') . '/webhook/whatsapp';
            $webhookSecret = (string) config('services.waha.webhook_secret', '');

            $waha   = new WahaService($instance->session_name);
            $result = $waha->createSession($webhookUrl, $webhookSecret);

            if (isset($result['error'])) {
                if (($result['status'] ?? 0) === 422) {
                    $waha->patchSession($webhookUrl, $webhookSecret);
                    $waha->stopSession();
                    $waha->startSession();
                } else {
                    $instance->delete();
                    return response()->json([
                        'success' => false,
                        'message' => 'Falha ao criar sessão no WAHA: ' . ($result['body'] ?? 'erro desconhecido'),
                    ], 500);
                }
            } else {
                $waha->startSession();
            }

            $instance->update(['status' => 'qr']);

            return response()->json([
                'success'      => true,
                'instance_id'  => $instance->id,
                'session_name' => $instance->session_name,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao conectar com o WAHA: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restart WAHA session (stop + start) to generate a new QR code after expiration.
     */
    public function restartWhatsapp(WhatsappInstance $instance): JsonResponse
    {
        try {
            $waha = new WahaService($instance->session_name);
            $waha->stopSession();
            $waha->startSession();
            $instance->update(['status' => 'qr']);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao reiniciar sessão: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getWhatsappQr(WhatsappInstance $instance): JsonResponse
    {
        $waha     = new WahaService($instance->session_name);
        $response = $waha->getQrResponse();

        if ($response->failed()) {
            $session    = $waha->getSession();
            $wahaStatus = $session['status'] ?? null;

            if ($wahaStatus === 'WORKING') {
                $instance->update(['status' => 'connected']);
                return response()->json(['status' => 'connected']);
            }

            return response()->json(['status' => $instance->status, 'qr_base64' => null]);
        }

        if ($instance->status !== 'qr') {
            $instance->update(['status' => 'qr']);
        }

        $contentType = $response->header('Content-Type') ?? '';
        if (str_contains($contentType, 'image/')) {
            return response()->json([
                'status'    => 'qr',
                'qr_base64' => base64_encode($response->body()),
            ]);
        }

        $json     = $response->json() ?? [];
        $qrBase64 = $json['value'] ?? $json['qr'] ?? $json['data'] ?? null;
        return response()->json([
            'status'    => 'qr',
            'qr_base64' => $qrBase64,
        ]);
    }

    public function importHistoryWhatsapp(Request $request, ?WhatsappInstance $instance = null): JsonResponse
    {
        // Import de histórico só faz sentido pra WAHA (Cloud API não expõe
        // histórico via API). Filtra fallback pra WAHA.
        $instance ??= WhatsappInstance::query()
            ->where(function ($q) {
                $q->where('provider', 'waha')->orWhereNull('provider');
            })
            ->first();

        if (! $instance || $instance->status !== 'connected') {
            return response()->json(['success' => false, 'message' => 'WhatsApp não está conectado.'], 422);
        }

        $days = min((int) $request->input('days', 30), 30);

        ImportWhatsappHistory::dispatch($instance, $days);

        return response()->json([
            'success' => true,
            'message' => "Importação dos últimos {$days} dias iniciada em segundo plano.",
        ]);
    }

    public function importProgress(WhatsappInstance $instance): JsonResponse
    {
        $data = Cache::get("wa_import:{$instance->id}");

        if (! $data) {
            return response()->json(['status' => 'idle']);
        }

        return response()->json($data);
    }

    public function disconnectWhatsapp(WhatsappInstance $instance): JsonResponse
    {
        $waha = new WahaService($instance->session_name);
        $waha->stopSession();
        $waha->deleteSession();
        $instance->update(['status' => 'disconnected', 'phone_number' => null, 'display_name' => null, 'label' => null]);

        return response()->json(['success' => true]);
    }

    public function deleteWhatsappInstance(WhatsappInstance $instance): JsonResponse
    {
        if ($instance->status === 'connected') {
            $waha = new WahaService($instance->session_name);
            $waha->stopSession();
            $waha->deleteSession();
        }

        $instance->delete();

        return response()->json(['success' => true]);
    }

    public function updateWhatsappInstance(Request $request, WhatsappInstance $instance): JsonResponse
    {
        $request->validate([
            'label' => 'required|string|max:100',
        ]);

        $instance->update(['label' => $request->input('label')]);

        return response()->json(['success' => true, 'label' => $instance->label]);
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

            $tenant = activeTenant();

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
        $tenant = activeTenant();

        // Soft disconnect: mark as disconnected instead of deleting
        // Deleting would cascade-delete ALL conversations and messages
        InstagramInstance::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->update([
                'status'       => 'disconnected',
                'access_token' => null,
            ]);

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

    // ── WhatsApp Button (Botão de Página) ─────────────────────────────────

    public function storeWaButton(Request $request): JsonResponse
    {
        if (WhatsappButton::count() >= 3) {
            return response()->json(['success' => false, 'message' => 'Limite de 3 botões atingido.'], 422);
        }

        $data = $request->validate([
            'phone_number'    => 'required|string|max:30',
            'default_message' => 'nullable|string|max:500',
            'button_label'    => 'nullable|string|max:100',
            'show_floating'   => 'nullable|boolean',
        ]);

        $btn = WhatsappButton::create([
            'tenant_id'       => activeTenantId(),
            'phone_number'    => preg_replace('/\D/', '', $data['phone_number']),
            'default_message' => $data['default_message'] ?? 'Olá! Vi seu site e gostaria de saber mais.',
            'button_label'    => $data['button_label'] ?? 'Fale no WhatsApp',
            'show_floating'   => $data['show_floating'] ?? true,
        ]);

        return response()->json(['success' => true, 'button' => $btn]);
    }

    public function updateWaButton(Request $request, WhatsappButton $waButton): JsonResponse
    {
        $data = $request->validate([
            'phone_number'    => 'sometimes|string|max:30',
            'default_message' => 'nullable|string|max:500',
            'button_label'    => 'nullable|string|max:100',
            'show_floating'   => 'nullable|boolean',
            'is_active'       => 'nullable|boolean',
        ]);

        if (isset($data['phone_number'])) {
            $data['phone_number'] = preg_replace('/\D/', '', $data['phone_number']);
        }

        $waButton->update($data);

        return response()->json(['success' => true, 'button' => $waButton->fresh()]);
    }

    public function destroyWaButton(WhatsappButton $waButton): JsonResponse
    {
        $waButton->delete();

        return response()->json(['success' => true]);
    }

    // ── Facebook Lead Ads ────────────────────────────────────────────────────

    public function redirectFacebookLeadAds(): RedirectResponse
    {
        return Socialite::driver('facebook')
            ->scopes(['pages_show_list', 'pages_manage_metadata', 'leads_retrieval', 'pages_manage_ads', 'business_management'])
            ->redirectUrl(config('services.facebook.leadgen_redirect'))
            ->redirect();
    }

    public function callbackFacebookLeadAds(Request $request): RedirectResponse
    {
        try {
            $fbUser = Socialite::driver('facebook')
                ->redirectUrl(config('services.facebook.leadgen_redirect'))
                ->user();
        } catch (\Throwable $e) {
            return redirect()->route('settings.integrations.index')
                ->with('error', 'Falha na autenticação com o Facebook: ' . $e->getMessage());
        }

        // Exchange for long-lived token
        $exchanged   = $this->exchangeFacebookToken($fbUser->token);
        $accessToken = $exchanged['access_token'] ?? $fbUser->token;
        $expiresIn   = $exchanged['expires_in'] ?? 5184000;

        OAuthConnection::updateOrCreate(
            ['tenant_id' => activeTenantId(), 'platform' => 'facebook_leadads'],
            [
                'platform_user_id'   => $fbUser->getId(),
                'platform_user_name' => $fbUser->getName(),
                'access_token'       => encrypt($accessToken),
                'token_expires_at'   => now()->addSeconds((int) $expiresIn),
                'scopes_json'        => ['pages_show_list', 'pages_manage_metadata', 'leads_retrieval', 'pages_manage_ads', 'business_management'],
                'status'             => 'active',
            ],
        );

        return redirect()->route('settings.integrations.index')
            ->with('success', 'Facebook Lead Ads conectado com sucesso!');
    }

    public function getFacebookLeadAdsPages(): JsonResponse
    {
        $conn = OAuthConnection::where('platform', 'facebook_leadads')
            ->where('status', 'active')
            ->first();

        if (! $conn) {
            return response()->json(['success' => false, 'message' => 'Não conectado'], 422);
        }

        $service = new FacebookLeadAdsService(decrypt($conn->access_token));
        $pages   = $service->getPages();

        return response()->json([
            'success'      => true,
            'pages'        => $pages,
            'needs_search' => empty($pages), // Business Login: /me/accounts empty
        ]);
    }

    public function searchFacebookLeadAdsPage(Request $request): JsonResponse
    {
        $request->validate(['query' => 'required|string|max:500']);

        $conn = OAuthConnection::where('platform', 'facebook_leadads')
            ->where('status', 'active')
            ->first();

        if (! $conn) {
            return response()->json(['success' => false, 'message' => 'Não conectado'], 422);
        }

        $service = new FacebookLeadAdsService(decrypt($conn->access_token));
        $page    = $service->searchPage($request->query('query'));

        if (! $page || empty($page['id'])) {
            return response()->json(['success' => false, 'message' => 'Página não encontrada. Verifique o ID ou URL.'], 404);
        }

        return response()->json(['success' => true, 'page' => $page]);
    }

    public function getFacebookLeadAdsForms(Request $request): JsonResponse
    {
        $request->validate(['page_id' => 'required|string']);

        $conn = OAuthConnection::where('platform', 'facebook_leadads')
            ->where('status', 'active')
            ->first();

        if (! $conn) {
            return response()->json(['success' => false, 'message' => 'Não conectado'], 422);
        }

        // Get page access token — try /me/accounts first, fallback to direct lookup
        $service = new FacebookLeadAdsService(decrypt($conn->access_token));
        $pages   = $service->getPages();
        $page    = collect($pages)->firstWhere('id', $request->page_id);

        if (! $page) {
            // Business Login fallback: fetch page directly by ID
            $page = $service->searchPage($request->page_id);
        }

        if (! $page || empty($page['access_token'])) {
            return response()->json(['success' => false, 'message' => 'Página não encontrada'], 404);
        }

        $forms = $service->getPageForms($page['id'], $page['access_token']);

        return response()->json([
            'success'          => true,
            'page_name'        => $page['name'],
            'page_access_token' => encrypt($page['access_token']),
            'forms'            => $forms,
        ]);
    }

    public function storeFbLeadConnection(Request $request): JsonResponse
    {
        $data = $request->validate([
            'page_id'            => 'required|string|max:64',
            'page_name'          => 'required|string|max:191',
            'page_access_token'  => 'required|string',
            'form_id'            => 'required|string|max:64',
            'form_name'          => 'required|string|max:191',
            'form_fields_json'   => 'nullable|array',
            'pipeline_id'        => 'required|exists:pipelines,id',
            'stage_id'           => 'required|integer',
            'field_mapping'      => 'required|array',
            'default_tags'       => 'nullable|array',
            'auto_assign_to'     => 'nullable|integer',
            'allow_duplicates'   => 'nullable|boolean',
        ]);

        $conn = OAuthConnection::where('platform', 'facebook_leadads')
            ->where('status', 'active')
            ->first();

        if (! $conn) {
            return response()->json(['success' => false, 'message' => 'Não conectado'], 422);
        }

        // Subscribe page to leadgen webhooks
        try {
            $pageToken = decrypt($data['page_access_token']);
            $service   = new FacebookLeadAdsService(decrypt($conn->access_token));
            $service->subscribePage($data['page_id'], $pageToken);
        } catch (\Throwable $e) {
            Log::warning('FacebookLeadAds: subscribePage failed on store', ['error' => $e->getMessage()]);
        }

        $connection = FacebookLeadFormConnection::updateOrCreate(
            ['tenant_id' => activeTenantId(), 'form_id' => $data['form_id']],
            [
                'oauth_connection_id' => $conn->id,
                'page_id'             => $data['page_id'],
                'page_name'           => $data['page_name'],
                'page_access_token'   => decrypt($data['page_access_token']), // decrypt JS encrypt(), model cast re-encrypts
                'form_name'           => $data['form_name'],
                'form_fields_json'    => $data['form_fields_json'] ?? null,
                'pipeline_id'         => $data['pipeline_id'],
                'stage_id'            => $data['stage_id'],
                'field_mapping'       => $data['field_mapping'],
                'default_tags'        => $data['default_tags'] ?? null,
                'auto_assign_to'      => $data['auto_assign_to'] ?? null,
                'is_active'           => true,
                'allow_duplicates'    => $data['allow_duplicates'] ?? true,
            ],
        );

        $connection->load('pipeline', 'stage');

        return response()->json(['success' => true, 'connection' => $connection]);
    }

    public function updateFbLeadConnection(Request $request, FacebookLeadFormConnection $connection): JsonResponse
    {
        $data = $request->validate([
            'pipeline_id'      => 'sometimes|exists:pipelines,id',
            'stage_id'         => 'sometimes|integer',
            'field_mapping'    => 'sometimes|array',
            'default_tags'     => 'nullable|array',
            'auto_assign_to'   => 'nullable|integer',
            'is_active'        => 'sometimes|boolean',
            'allow_duplicates' => 'sometimes|boolean',
        ]);

        $connection->update($data);

        return response()->json(['success' => true, 'connection' => $connection->fresh()->load('pipeline', 'stage')]);
    }

    public function destroyFbLeadConnection(FacebookLeadFormConnection $connection): JsonResponse
    {
        $connection->delete();

        return response()->json(['success' => true]);
    }

    public function disconnectFacebookLeadAds(): JsonResponse
    {
        FacebookLeadFormConnection::where('tenant_id', activeTenantId())
            ->update(['is_active' => false]);

        OAuthConnection::where('tenant_id', activeTenantId())
            ->where('platform', 'facebook_leadads')
            ->delete();

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // WhatsApp Cloud API (Meta oficial) — Embedded Signup com Coexistence
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Inicia o Embedded Signup do WhatsApp Cloud API.
     * Redireciona pro Facebook Login com escopos do WhatsApp + config_id
     * do Embedded Signup configurado no Meta Developer Console (modo Coexistence).
     */
    public function redirectWhatsappCloud(): RedirectResponse
    {
        $clientId    = (string) config('services.whatsapp_cloud.app_id');
        $configId    = (string) config('services.whatsapp_cloud.config_id');
        $redirectUri = (string) config('services.whatsapp_cloud.redirect');
        $apiVersion  = (string) config('services.whatsapp_cloud.api_version', 'v21.0');

        // Apenas app_id e redirect são obrigatórios.
        // config_id é opcional — só usado se Embedded Signup configurado.
        if (! $clientId || ! $redirectUri) {
            return redirect()
                ->route('settings.integrations.index')
                ->with('error', 'WhatsApp Cloud API não está configurado no servidor (WHATSAPP_CLOUD_APP_ID ou WHATSAPP_CLOUD_REDIRECT vazios).');
        }

        $state = bin2hex(random_bytes(16));
        session(['whatsapp_cloud_oauth_state' => $state]);

        $params = [
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'scope'         => 'whatsapp_business_management,whatsapp_business_messaging,business_management',
            'response_type' => 'code',
            'state'         => $state,
        ];

        // Embedded Signup (popup com Coexistence) só com config_id setado
        if ($configId) {
            $params['config_id'] = $configId;
            $params['override_default_response_type'] = true;
        }

        return redirect("https://www.facebook.com/{$apiVersion}/dialog/oauth?" . http_build_query($params));
    }

    /**
     * Callback do OAuth do WhatsApp Cloud.
     * Recebe `code`, troca por access_token, busca phone_number_id + waba_id,
     * salva como WhatsappInstance com provider='cloud_api', subscribe webhook.
     *
     * IMPORTANTE: este endpoint é chamado dentro de uma janelinha pop-up
     * (window.open) — então sempre retorna a view _wacloud-callback que
     * fecha a janelinha sozinha + recarrega a página pai.
     */
    public function callbackWhatsappCloud(Request $request)
    {
        $code  = $request->get('code');
        $state = $request->get('state');

        if (! $code || $state !== session('whatsapp_cloud_oauth_state')) {
            return $this->wacloudPopupResponse(false, 'Erro de autenticação', 'OAuth state inválido ou code ausente. Tente novamente.');
        }
        session()->forget('whatsapp_cloud_oauth_state');

        $clientId     = (string) config('services.whatsapp_cloud.app_id');
        $clientSecret = (string) config('services.whatsapp_cloud.app_secret');
        $redirectUri  = (string) config('services.whatsapp_cloud.redirect');
        $apiVersion   = (string) config('services.whatsapp_cloud.api_version', 'v22.0');

        try {
            // 1. Trocar code por access_token
            $tokenResponse = \Illuminate\Support\Facades\Http::timeout(15)
                ->get("https://graph.facebook.com/{$apiVersion}/oauth/access_token", [
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri'  => $redirectUri,
                    'code'          => $code,
                ]);

            if (! $tokenResponse->successful()) {
                Log::warning('WhatsappCloud: token exchange failed', ['body' => $tokenResponse->body()]);
                return $this->wacloudPopupResponse(false, 'Falha ao autenticar', 'Não foi possível trocar o código por um token de acesso.');
            }

            $accessToken = (string) $tokenResponse->json('access_token');
            $expiresIn   = (int) ($tokenResponse->json('expires_in') ?? 0);
            $expiresAt   = $expiresIn > 0 ? now()->addSeconds($expiresIn) : null;

            // 2. Descobrir as WABAs autorizadas pelo usuário via debug_token.
            //    Isso retorna granular_scopes, que contém EXATAMENTE as WABAs
            //    que o usuário marcou na tela de seleção de ativos do Meta.
            //    Funciona pra WABAs owned E shared — diferente de /me/businesses
            //    que só pega owned com permissão business_management.
            $debugResponse = \Illuminate\Support\Facades\Http::timeout(15)
                ->get("https://graph.facebook.com/{$apiVersion}/debug_token", [
                    'input_token'  => $accessToken,
                    'access_token' => "{$clientId}|{$clientSecret}", // app token
                ]);

            $wabaId     = null;
            $businessId = null;

            if ($debugResponse->successful()) {
                $granularScopes = $debugResponse->json('data.granular_scopes') ?? [];
                Log::info('WhatsappCloud: granular_scopes', ['scopes' => $granularScopes]);

                foreach ($granularScopes as $scope) {
                    if (($scope['scope'] ?? '') === 'whatsapp_business_management'
                        && ! empty($scope['target_ids'])) {
                        $wabaId = (string) $scope['target_ids'][0];
                        break;
                    }
                }

                // Tenta também pegar o business_id via business_management scope
                foreach ($granularScopes as $scope) {
                    if (($scope['scope'] ?? '') === 'business_management'
                        && ! empty($scope['target_ids'])) {
                        $businessId = (string) $scope['target_ids'][0];
                        break;
                    }
                }
            }

            // Fallback: tenta o método antigo (owned_whatsapp_business_accounts)
            // caso o debug_token não funcione ou granular_scopes esteja vazio
            if (! $wabaId) {
                $bizResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
                    ->timeout(15)
                    ->get("https://graph.facebook.com/{$apiVersion}/me/businesses", ['fields' => 'id,name']);
                $businesses = $bizResponse->successful() ? ($bizResponse->json('data') ?? []) : [];

                foreach ($businesses as $biz) {
                    // Tenta owned + client (WABA pode estar em qualquer um)
                    foreach (['owned_whatsapp_business_accounts', 'client_whatsapp_business_accounts'] as $endpoint) {
                        $wabaListResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
                            ->timeout(15)
                            ->get("https://graph.facebook.com/{$apiVersion}/{$biz['id']}/{$endpoint}", ['fields' => 'id,name']);
                        $wabas = $wabaListResponse->successful() ? ($wabaListResponse->json('data') ?? []) : [];
                        if (! empty($wabas)) {
                            $wabaId     = $wabas[0]['id'];
                            $businessId = $biz['id'];
                            break 2;
                        }
                    }
                }
            }

            if (! $wabaId) {
                Log::warning('WhatsappCloud: WABA não encontrada via granular_scopes nem fallback', [
                    'debug_response' => $debugResponse->successful() ? $debugResponse->json() : $debugResponse->body(),
                ]);
                return $this->wacloudPopupResponse(
                    false,
                    'WABA não encontrado',
                    'Nenhum WhatsApp Business Account autorizado. Verifique se você selecionou um WhatsApp Business Account na tela de autorização do Meta.'
                );
            }

            // 3. Listar phone numbers do WABA
            $phoneResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->timeout(15)
                ->get("https://graph.facebook.com/{$apiVersion}/{$wabaId}/phone_numbers", [
                    'fields' => 'id,display_phone_number,verified_name,quality_rating',
                ]);

            $phones = $phoneResponse->successful() ? ($phoneResponse->json('data') ?? []) : [];
            if (empty($phones)) {
                return $this->wacloudPopupResponse(false, 'Nenhum número', 'Nenhum número de telefone conectado neste WhatsApp Business Account.');
            }

            $phone = $phones[0];
            $phoneNumberId  = (string) $phone['id'];
            $displayPhone   = preg_replace('/\D/', '', (string) ($phone['display_phone_number'] ?? ''));
            $verifiedName   = $phone['verified_name'] ?? null;

            // 4. Cria/atualiza instância com provider='cloud_api'
            $instance = WhatsappInstance::updateOrCreate(
                [
                    'tenant_id'       => activeTenantId(),
                    'phone_number_id' => $phoneNumberId,
                ],
                [
                    'session_name'        => 'cloud_' . $phoneNumberId,
                    'status'              => 'connected',
                    'provider'            => 'cloud_api',
                    'phone_number'        => $displayPhone,
                    'waba_id'             => $wabaId,
                    'business_account_id' => $businessId,
                    'access_token'        => $accessToken,
                    'token_expires_at'    => $expiresAt,
                    'display_name'        => $verifiedName,
                    'label'               => $verifiedName ?: ('+' . $displayPhone),
                ],
            );

            // 5. Subscribe ao webhook (1x por phone_number_id)
            try {
                $service = new \App\Services\WhatsappCloudService($instance->fresh());
                $service->subscribeApp();
            } catch (\Throwable $e) {
                Log::warning('WhatsappCloud: subscribeApp failed', ['error' => $e->getMessage()]);
            }

            return $this->wacloudPopupResponse(
                true,
                'WhatsApp conectado!',
                ($verifiedName ?: '+' . $displayPhone) . ' está pronto pra receber mensagens.'
            );

        } catch (\Throwable $e) {
            Log::error('WhatsappCloud: callback exception', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);
            return $this->wacloudPopupResponse(false, 'Erro ao conectar', $e->getMessage());
        }
    }

    /**
     * Embedded Signup AJAX exchange endpoint.
     *
     * Recebe { code, phone_number_id, waba_id, business_id } do FB.login()
     * (featureType=whatsapp_business_app_onboarding) no frontend, troca code
     * por access_token, registra o número, cria WhatsappInstance(provider=cloud_api)
     * e faz subscribe do webhook.
     *
     * Diferente do callback OAuth velho, NÃO precisa adivinhar o WABA via
     * granular_scopes — o frontend já entrega phone_number_id + waba_id
     * direto do evento WA_EMBEDDED_SIGNUP.
     */
    public function exchangeWhatsappCloud(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'            => 'required|string',
            'phone_number_id' => 'required|string',
            'waba_id'         => 'required|string',
            'business_id'     => 'nullable|string',
        ]);

        $clientId     = (string) config('services.whatsapp_cloud.app_id');
        $clientSecret = (string) config('services.whatsapp_cloud.app_secret');
        $apiVersion   = (string) config('services.whatsapp_cloud.api_version', 'v22.0');

        if (! $clientId || ! $clientSecret) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp Cloud não está configurado no servidor.',
            ], 500);
        }

        try {
            // 1. Trocar code por access_token (sem redirect_uri — fluxo Embedded Signup)
            $tokenResponse = \Illuminate\Support\Facades\Http::timeout(15)
                ->get("https://graph.facebook.com/{$apiVersion}/oauth/access_token", [
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'code'          => $data['code'],
                ]);

            if (! $tokenResponse->successful()) {
                Log::warning('WhatsappCloud(exchange): token exchange failed', ['body' => $tokenResponse->body()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível trocar o código por um token de acesso.',
                ], 422);
            }

            $accessToken = (string) $tokenResponse->json('access_token');
            $expiresIn   = (int) ($tokenResponse->json('expires_in') ?? 0);
            $expiresAt   = $expiresIn > 0 ? now()->addSeconds($expiresIn) : null;

            // 2. Buscar dados do número (display_phone_number, verified_name)
            $phoneResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->timeout(15)
                ->get("https://graph.facebook.com/{$apiVersion}/{$data['phone_number_id']}", [
                    'fields' => 'id,display_phone_number,verified_name,quality_rating',
                ]);

            $displayPhone = '';
            $verifiedName = null;
            if ($phoneResponse->successful()) {
                $payload = $phoneResponse->json();
                $displayPhone = preg_replace('/\D/', '', (string) ($payload['display_phone_number'] ?? ''));
                $verifiedName = $payload['verified_name'] ?? null;
            }

            // 3. Cria/atualiza instância com provider='cloud_api'
            $instance = WhatsappInstance::updateOrCreate(
                [
                    'tenant_id'       => activeTenantId(),
                    'phone_number_id' => $data['phone_number_id'],
                ],
                [
                    'session_name'        => 'cloud_' . $data['phone_number_id'],
                    'status'              => 'connected',
                    'provider'            => 'cloud_api',
                    'phone_number'        => $displayPhone,
                    'waba_id'             => $data['waba_id'],
                    'business_account_id' => $data['business_id'] ?? null,
                    'access_token'        => $accessToken,
                    'token_expires_at'    => $expiresAt,
                    'display_name'        => $verifiedName,
                    'label'               => $verifiedName ?: ('+' . $displayPhone),
                ],
            );

            // 4. Subscribe ao webhook
            try {
                $service = new \App\Services\WhatsappCloudService($instance->fresh());
                $service->subscribeApp();
            } catch (\Throwable $e) {
                Log::warning('WhatsappCloud(exchange): subscribeApp failed', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success'     => true,
                'instance_id' => $instance->id,
                'label'       => $verifiedName ?: ('+' . $displayPhone),
            ]);

        } catch (\Throwable $e) {
            Log::error('WhatsappCloud(exchange): exception', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao conectar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: retorna a view minimal que fecha a janelinha pop-up
     * e recarrega a página pai (settings/integrations).
     */
    private function wacloudPopupResponse(bool $success, string $message, ?string $detail = null)
    {
        return response()->view('tenant.settings._wacloud-callback', [
            'success' => $success,
            'message' => $message,
            'detail'  => $detail,
        ]);
    }

    /**
     * Desconecta uma instância Cloud API do tenant atual.
     */
    public function disconnectWhatsappCloud(WhatsappInstance $instance): JsonResponse
    {
        if ($instance->tenant_id !== activeTenantId() || ! $instance->isCloudApi()) {
            return response()->json(['success' => false, 'message' => 'Não permitido'], 403);
        }

        $instance->delete();
        return response()->json(['success' => true]);
    }
}
