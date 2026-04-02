<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // ── Challenge (login flow) ───────────────────────────────────────────────

    public function showChallenge(): View|RedirectResponse
    {
        if (!session('2fa:user_id')) {
            return redirect()->route('login');
        }

        return view('auth.2fa-challenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string']);

        $throttleKey = '2fa:' . session('2fa:user_id') . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors(['code' => "Muitas tentativas. Aguarde {$seconds} segundos."]);
        }

        RateLimiter::hit($throttleKey, 300); // 5 min decay

        $userId = session('2fa:user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user || !$user->totp_enabled || !$user->totp_secret) {
            session()->forget(['2fa:user_id', '2fa:remember', '2fa:attempts']);
            return redirect()->route('login');
        }

        $code     = $request->input('code');
        $attempts = (int) session('2fa:attempts', 0);

        // Try backup code first
        if ($this->verifyBackupCode($user, $code)) {
            return $this->completeLogin($user);
        }

        // Try TOTP
        $valid = $this->google2fa->verifyKey($user->totp_secret, $code);

        if ($valid) {
            return $this->completeLogin($user);
        }

        $attempts++;
        session(['2fa:attempts' => $attempts]);

        if ($attempts >= 5) {
            session()->forget(['2fa:user_id', '2fa:remember', '2fa:attempts']);
            return redirect()->route('login')
                ->withErrors(['email' => 'Muitas tentativas incorretas. Faça login novamente.']);
        }

        return back()->withErrors(['code' => 'Código inválido. Tentativa ' . $attempts . ' de 5.']);
    }

    private function completeLogin(User $user): RedirectResponse
    {
        $remember = session('2fa:remember', false);
        session()->forget(['2fa:user_id', '2fa:remember', '2fa:attempts']);

        Auth::login($user, $remember);
        session()->regenerate();
        session(['2fa:verified' => true]);

        $user->update(['last_login_at' => now()]);

        return redirect()->intended(route('master.dashboard'));
    }

    private function verifyBackupCode(User $user, string $code): bool
    {
        $codes = $user->totp_backup_codes ?? [];
        $code  = strtoupper(trim($code));

        foreach ($codes as $i => $stored) {
            if (strtoupper($stored) === $code) {
                unset($codes[$i]);
                $user->update(['totp_backup_codes' => array_values($codes)]);
                return true;
            }
        }

        return false;
    }

    // ── Setup (inside master panel) ──────────────────────────────────────────

    public function showSetup(): View
    {
        $user = auth()->user();

        $secret  = $user->totp_secret ?: $this->google2fa->generateSecretKey();
        $qrImage = null;

        if (!$user->totp_enabled) {
            // Temporarily store secret (not saved until confirmed)
            session(['2fa:setup_secret' => $secret]);

            $qrCodeUrl = $this->google2fa->getQRCodeUrl(
                'Syncro CRM',
                $user->email,
                $secret,
            );

            // Generate QR using bacon/bacon-qr-code
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(250),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd(),
            );
            $writer   = new \BaconQrCode\Writer($renderer);
            $qrImage  = 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($qrCodeUrl));
        }

        return view('master.2fa.setup', [
            'enabled'  => $user->totp_enabled,
            'qrImage'  => $qrImage,
            'secret'   => $secret,
        ]);
    }

    public function confirmSetup(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string|size:6']);

        $user   = auth()->user();
        $secret = session('2fa:setup_secret');

        if (!$secret) {
            return back()->withErrors(['code' => 'Sessão expirada. Tente novamente.']);
        }

        $valid = $this->google2fa->verifyKey($secret, $request->input('code'));

        if (!$valid) {
            return back()->withErrors(['code' => 'Código inválido. Tente novamente.']);
        }

        $backupCodes = $this->generateBackupCodes();

        $user->update([
            'totp_secret'       => $secret,
            'totp_enabled'      => true,
            'totp_backup_codes' => $backupCodes,
        ]);

        session()->forget('2fa:setup_secret');
        session(['2fa:verified' => true]);

        return redirect()->route('master.2fa.backup-codes')
            ->with('backup_codes', $backupCodes)
            ->with('just_enabled', true);
    }

    public function showBackupCodes(): View|RedirectResponse
    {
        $codes = session('backup_codes');
        $justEnabled = session('just_enabled', false);

        return view('master.2fa.backup-codes', [
            'codes'       => $codes,
            'justEnabled' => $justEnabled,
        ]);
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required|string']);

        $user = auth()->user();

        if (!Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Senha incorreta.']);
        }

        $user->update([
            'totp_secret'       => null,
            'totp_enabled'      => false,
            'totp_backup_codes' => null,
        ]);

        session()->forget('2fa:verified');

        return redirect()->route('master.2fa.setup')
            ->with('success', 'Autenticação em dois fatores desativada.');
    }

    public function regenerateBackupCodes(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required|string']);

        $user = auth()->user();

        if (!Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Senha incorreta.']);
        }

        $backupCodes = $this->generateBackupCodes();
        $user->update(['totp_backup_codes' => $backupCodes]);

        return redirect()->route('master.2fa.backup-codes')
            ->with('backup_codes', $backupCodes);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(4) . '-' . Str::random(4));
        }
        return $codes;
    }
}
