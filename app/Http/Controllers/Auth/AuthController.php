<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        // Verifica se a conta está associada a um tenant ativo
        if (!$user->isSuperAdmin() && $user->tenant) {
            if (!in_array($user->tenant->status, ['active', 'trial'])) {
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

        // Cria o tenant
        $tenant = Tenant::create([
            'name'   => $data['tenant_name'],
            'slug'   => Str::slug($data['company_name']) . '-' . Str::random(4),
            'plan'   => 'free',
            'status' => 'trial',
        ]);

        // Cria o usuário admin do tenant
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => 'admin',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        // Placeholder: em produção integrar com Laravel Password Reset
        return back()->with('status', 'Se este e-mail estiver cadastrado, você receberá o link em breve.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
