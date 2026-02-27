<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPassword as ResetPasswordMail;
use App\Mail\VerifyEmail;
use App\Mail\WelcomeUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'E-mail ou senha incorretos.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        // Bloqueia login se email não foi confirmado
        if (!$user->isSuperAdmin() && $user->email_verified_at === null) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Confirme seu email antes de continuar. Verifique sua caixa de entrada.',
            ])->onlyInput('email');
        }

        // Verifica se a conta está associada a um tenant ativo
        if (!$user->isSuperAdmin() && $user->tenant) {
            if (!in_array($user->tenant->status, ['active', 'trial', 'partner'])) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Sua conta está suspensa. Entre em contato com o suporte.',
                ])->onlyInput('email');
            }
        }

        $user->update(['last_login_at' => now()]);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_name' => 'required|string|max:255',
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8|confirmed',
        ]);

        $token = Str::random(64);

        // Cria o tenant
        $tenant = Tenant::create([
            'name'   => $data['tenant_name'],
            'slug'   => Str::slug($data['tenant_name']) . '-' . Str::random(4),
            'plan'   => 'free',
            'status' => 'trial',
        ]);

        // Cria o usuário admin — email não verificado ainda
        $user = User::create([
            'tenant_id'          => $tenant->id,
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => $data['password'],
            'role'               => 'admin',
            'email_verified_at'  => null,
            'verification_token' => $token,
        ]);

        // Envia email de verificação (ignora falhas para não bloquear o cadastro)
        try {
            Mail::to($user->email)->send(new VerifyEmail($user, $tenant));
        } catch (\Throwable $e) {
            \Log::warning('Falha ao enviar email de verificação', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        return redirect()->route('register.pending')
            ->with('email', $user->email);
    }

    public function verifyEmail(string $token): RedirectResponse
    {
        $user = User::where('verification_token', $token)->first();

        if (!$user) {
            return redirect()->route('login')->withErrors([
                'email' => 'Link de verificação inválido ou já utilizado.',
            ]);
        }

        $user->update([
            'email_verified_at'  => now(),
            'verification_token' => null,
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
        $request->validate(['email' => 'required|email']);

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
        return back()->with('status', 'Se este e-mail estiver cadastrado, você receberá o link em breve. Verifique também o spam.');
    }

    public function showResetPassword(Request $request, string $token): View|RedirectResponse
    {
        $email = $request->query('email', '');

        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$record || now()->diffInMinutes($record->created_at) > 15) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Este link de redefinição expirou. Solicite um novo.',
            ]);
        }

        if (!hash_equals($record->token, hash('sha256', $token))) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Link inválido. Solicite um novo.',
            ]);
        }

        return view('auth.reset-password', compact('token', 'email'));
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token'                 => 'required|string',
            'email'                 => 'required|email',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        if (!$record) {
            return back()->withErrors(['password' => 'Link inválido ou expirado.']);
        }

        if (now()->diffInMinutes($record->created_at) > 15) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
            return back()->withErrors(['password' => 'Este link expirou. Solicite um novo.']);
        }

        if (!hash_equals($record->token, hash('sha256', $data['token']))) {
            return back()->withErrors(['password' => 'Link inválido.']);
        }

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return back()->withErrors(['password' => 'Usuário não encontrado.']);
        }

        $user->update(['password' => $data['password']]);

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return redirect()->route('login')
            ->with('status', 'Senha redefinida com sucesso! Faça login com sua nova senha.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
