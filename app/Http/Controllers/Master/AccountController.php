<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

/**
 * Minha Conta — super_admin gerencia a própria senha.
 *
 * Step-up auth: apesar da sessão já estar com 2fa:verified (passou no
 * challenge do login), a alteração de senha exige o código TOTP AGORA.
 * Isso protege contra session hijacking + ataques com sessão ativa.
 */
class AccountController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        return view('master.account.index', compact('user'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'totp_code'        => 'required|string|size:6',
        ], [
            'totp_code.size'        => 'O código 2FA precisa ter 6 dígitos.',
            'new_password.confirmed' => 'A nova senha e a confirmação não batem.',
        ]);

        $user = auth()->user();

        // 1. Senha atual correta
        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Senha atual incorreta.']);
        }

        // 2. 2FA precisa estar habilitado (é obrigatório pra super_admin)
        if (! $user->totp_enabled || ! $user->totp_secret) {
            return back()->withErrors(['totp_code' => '2FA não está configurado. Configure antes de trocar a senha.']);
        }

        // 3. Código TOTP válido
        $google2fa = new Google2FA();
        if (! $google2fa->verifyKey($user->totp_secret, $request->input('totp_code'))) {
            return back()->withErrors(['totp_code' => 'Código 2FA inválido.']);
        }

        // 4. Nova senha não pode ser igual à atual
        if (Hash::check($request->input('new_password'), $user->password)) {
            return back()->withErrors(['new_password' => 'A nova senha precisa ser diferente da atual.']);
        }

        $user->password = $request->input('new_password'); // cast 'hashed' encripta
        $user->save();

        return back()->with('success', 'Senha alterada com sucesso.');
    }
}
