<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\AgencyReferralNotification;
use App\Mail\ResetPassword as ResetPasswordMail;
use App\Mail\VerifyEmail;
use App\Mail\WelcomeUser;
use App\Models\PartnerAgencyCode;
use App\Models\PlanDefinition;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => __('auth.validation.email_required'),
            'email.email'       => __('auth.validation.email_email'),
            'password.required' => __('auth.validation.password_required'),
        ]);

        // Rate limiting — 5 tentativas por minuto por email+IP
        $throttleKey = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => __('auth.validation.too_many_attempts', ['seconds' => $seconds]),
            ])->onlyInput('email');
        }

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'email' => __('auth.validation.invalid_credentials'),
            ])->onlyInput('email');
        }

        $user = Auth::user();

        // Bloqueia login se email não foi confirmado
        if (!$user->isSuperAdmin() && $user->email_verified_at === null) {
            Auth::logout();
            return back()->withErrors([
                'email' => __('auth.validation.email_not_verified'),
            ])->onlyInput('email');
        }

        // Verifica se a conta está associada a um tenant ativo
        if (!$user->isSuperAdmin() && $user->tenant) {
            if (!in_array($user->tenant->status, ['active', 'trial', 'partner'])) {
                Auth::logout();
                return back()->withErrors([
                    'email' => __('auth.validation.account_suspended'),
                ])->onlyInput('email');
            }
        }

        RateLimiter::clear($throttleKey);

        // 2FA check for super admin
        if ($user->isSuperAdmin() && $user->totp_enabled) {
            Auth::logout();
            $request->session()->regenerate();
            session([
                '2fa:user_id'  => $user->id,
                '2fa:remember' => $request->boolean('remember'),
            ]);
            return redirect('/2fa/challenge');
        }

        $user->update(['last_login_at' => now()]);

        $request->session()->regenerate();

        if ($user->isSuperAdmin()) {
            return redirect()->intended(route('master.dashboard'));
        }

        if ($user->isCsAgent()) {
            return redirect()->intended(route('cs.index'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_name'  => 'required|string|max:255',
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
            'phone'        => 'required|string|min:10|max:20',
            'agency_code'  => 'nullable|string|max:20',
            'accept_terms' => 'accepted',
            'locale'       => 'nullable|string|in:pt_BR,en',
        ], [
            'tenant_name.required' => __('auth.validation.tenant_name_required'),
            'tenant_name.max'      => __('auth.validation.tenant_name_max'),
            'name.required'        => __('auth.validation.name_required'),
            'name.max'             => __('auth.validation.name_max'),
            'email.required'       => __('auth.validation.email_required'),
            'email.email'          => __('auth.validation.email_email'),
            'email.unique'         => __('auth.validation.email_unique'),
            'password.required'    => __('auth.validation.password_create'),
            'password.min'         => __('auth.validation.password_min'),
            'password.confirmed'   => __('auth.validation.password_confirmed'),
            'accept_terms.accepted' => __('auth.validation.accept_terms'),
        ]);

        $token = Str::random(64);

        // Resolver código de agência parceira (opcional)
        $agencyCode = null;
        if (!empty($data['agency_code'])) {
            $agencyCode = PartnerAgencyCode::where('code', strtoupper($data['agency_code']))
                ->where('is_active', true)
                ->whereNotNull('tenant_id')
                ->first();
        }

        // Cria o tenant
        $freePlan  = PlanDefinition::where('name', 'free')->first();
        $trialDays = $freePlan?->trial_days ?? 14;

        $tenant = Tenant::create([
            'name'                  => $data['tenant_name'],
            'phone'                 => preg_replace('/\D/', '', $data['phone']),
            'slug'                  => Str::slug($data['tenant_name']) . '-' . Str::random(4),
            'plan'                  => 'free',
            'status'                => 'trial',
            'trial_ends_at'         => now()->addDays($trialDays),
            'referred_by_agency_id' => $agencyCode?->tenant_id,
            'locale'                => $request->input('locale', 'pt_BR'),
            'billing_provider'      => $request->input('locale', 'pt_BR') === 'pt_BR' ? 'asaas' : 'stripe',
            'billing_country'       => $request->input('locale', 'pt_BR') === 'pt_BR' ? 'BR' : 'US',
            'billing_currency'      => $request->input('locale', 'pt_BR') === 'pt_BR' ? 'BRL' : 'USD',
        ]);

        // Cria o usuário admin — email não verificado ainda
        $user = User::create([
            'tenant_id'          => $tenant->id,
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => $data['password'],
            'role'               => 'admin',
            'email_verified_at'              => null,
            'verification_token'             => $token,
            'verification_token_expires_at'  => now()->addHours(48),
        ]);

        // Registra consentimento LGPD
        UserConsent::create([
            'user_id'        => $user->id,
            'consent_type'   => 'terms_and_privacy',
            'policy_version' => '2026-03',
            'accepted_at'    => now(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        // Envia email de verificação (ignora falhas para não bloquear o cadastro)
        try {
            Mail::to($user->email)->send(new VerifyEmail($user, $tenant));
        } catch (\Throwable $e) {
            \Log::warning('Falha ao enviar email de verificação', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        // Notifica a agência parceira sobre o novo cliente indicado
        if ($agencyCode?->tenant_id) {
            try {
                $agencyTenant     = Tenant::find($agencyCode->tenant_id);
                $agencyAdminUser  = $agencyTenant?->users()->where('role', 'admin')->first();
                $totalClients     = Tenant::where('referred_by_agency_id', $agencyCode->tenant_id)->count();

                if ($agencyAdminUser && $agencyTenant) {
                    Mail::to($agencyAdminUser->email)->send(
                        new AgencyReferralNotification($agencyAdminUser, $agencyTenant, $tenant, $totalClients)
                    );
                    // In-app notification
                    $agencyAdminUser->notify(new \App\Notifications\PartnerNotification(
                        'Novo cliente indicado!',
                        "{$tenant->name} se cadastrou com seu código. Total: {$totalClients} clientes.",
                    ));
                }
            } catch (\Throwable $e) {
                \Log::warning('Falha ao notificar agência sobre novo cliente', [
                    'agency_tenant_id' => $agencyCode->tenant_id,
                    'error'            => $e->getMessage(),
                ]);
            }
        }

        // Notifica grupo master via WhatsApp
        $agencyName = $agencyCode?->tenant_id
            ? Tenant::find($agencyCode->tenant_id)?->name
            : null;
        \App\Services\MasterWhatsappNotifier::newRegistration($tenant, $user, $agencyName);

        return redirect()->route('register.pending')
            ->with('email', $user->email);
    }

    public function verifyEmail(string $token): RedirectResponse
    {
        $user = User::where('verification_token', $token)->first();

        if (!$user) {
            return redirect()->route('login')->withErrors([
                'email' => __('auth.validation.verification_invalid'),
            ]);
        }

        // Verificar se o token expirou
        if ($user->verification_token_expires_at && $user->verification_token_expires_at->isPast()) {
            $user->update(['verification_token' => null, 'verification_token_expires_at' => null]);
            return redirect()->route('login')->withErrors([
                'email' => __('auth.validation.verification_expired'),
            ]);
        }

        $user->update([
            'email_verified_at'              => now(),
            'verification_token'             => null,
            'verification_token_expires_at'  => null,
        ]);

        // Envia email de boas-vindas
        try {
            Mail::to($user->email)->send(new WelcomeUser($user, $user->tenant));
        } catch (\Throwable $e) {
            \Log::warning('Falha ao enviar email de boas-vindas', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email'], [
            'email.required' => __('auth.validation.email_required'),
            'email.email'    => __('auth.validation.email_email'),
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if ($user) {
            $plainToken = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token'      => hash('sha256', $plainToken),
                    'created_at' => now(),
                ]
            );

            try {
                Mail::to($user->email)->send(new ResetPasswordMail($user, $plainToken));
            } catch (\Throwable $e) {
                \Log::warning('Falha ao enviar email de reset', ['email' => $user->email, 'error' => $e->getMessage()]);
            }
        }

        // Sempre retorna a mesma mensagem (não revela se o email existe)
        return back()->with('status', __('auth.validation.reset_link_sent'));
    }

    public function showResetPassword(Request $request, string $token): View|RedirectResponse
    {
        $email = $request->query('email', '');

        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$record || now()->diffInMinutes($record->created_at) > 15) {
            return redirect()->route('password.request')->withErrors([
                'email' => __('auth.validation.reset_link_expired'),
            ]);
        }

        if (!hash_equals($record->token, hash('sha256', $token))) {
            return redirect()->route('password.request')->withErrors([
                'email' => __('auth.validation.reset_link_invalid'),
            ]);
        }

        return view('auth.reset-password', compact('token', 'email'));
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token'                 => 'required|string',
            'email'                 => 'required|email',
            'password'              => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
        ], [
            'email.required'       => __('auth.validation.email_required'),
            'email.email'          => __('auth.validation.email_email'),
            'password.required'    => __('auth.validation.password_create'),
            'password.min'         => __('auth.validation.password_min'),
            'password.confirmed'   => __('auth.validation.password_confirmed'),
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        if (!$record) {
            return back()->withErrors(['password' => __('auth.validation.reset_invalid_or_expired')]);
        }

        if (now()->diffInMinutes($record->created_at) > 15) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
            return back()->withErrors(['password' => __('auth.validation.reset_expired')]);
        }

        if (!hash_equals($record->token, hash('sha256', $data['token']))) {
            return back()->withErrors(['password' => __('auth.validation.reset_invalid')]);
        }

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return back()->withErrors(['password' => __('auth.validation.user_not_found')]);
        }

        $user->update(['password' => $data['password']]);

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return redirect()->route('login')
            ->with('status', __('auth.validation.password_reset_success'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
