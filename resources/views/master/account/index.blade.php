@extends('master.layouts.app')
@php
    $title    = 'Minha Conta';
    $pageIcon = 'person-circle';
@endphp

@section('content')
<div style="max-width:640px;margin:0 auto;">

    @if(session('success'))
        <div style="background:#d1fae5;border:1px solid #86efac;border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#065f46;display:flex;align-items:center;gap:10px;">
            <i class="bi bi-check-circle-fill"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- ── Info básica ────────────────────────────────────────────── --}}
    <div class="m-card" style="margin-bottom:18px;">
        <div class="m-card-header">
            <div class="m-card-title">
                <i class="bi bi-person-badge"></i>
                Informações da conta
            </div>
        </div>
        <div class="m-card-body">
            <div style="display:grid;grid-template-columns:120px 1fr;gap:10px 14px;font-size:13.5px;">
                <div style="color:#6b7280;font-weight:600;">Nome</div>
                <div style="color:#1a1d23;">{{ $user->name }}</div>

                <div style="color:#6b7280;font-weight:600;">E-mail</div>
                <div style="color:#1a1d23;">{{ $user->email }}</div>

                <div style="color:#6b7280;font-weight:600;">Papel</div>
                <div>
                    <span style="background:#eff6ff;color:#1d4ed8;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600;">
                        Super Admin
                    </span>
                </div>

                <div style="color:#6b7280;font-weight:600;">2FA</div>
                <div>
                    @if($user->totp_enabled)
                        <span style="background:#d1fae5;color:#065f46;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600;">
                            <i class="bi bi-shield-check"></i> Ativado
                        </span>
                        <a href="{{ route('master.2fa.setup') }}" style="margin-left:8px;font-size:12.5px;color:#0085f3;text-decoration:none;font-weight:600;">
                            Gerenciar →
                        </a>
                    @else
                        <span style="background:#fef2f2;color:#991b1b;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600;">
                            <i class="bi bi-shield-x"></i> Desativado
                        </span>
                        <a href="{{ route('master.2fa.setup') }}" style="margin-left:8px;font-size:12.5px;color:#dc2626;text-decoration:none;font-weight:600;">
                            Ativar agora →
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Trocar senha ───────────────────────────────────────────── --}}
    <div class="m-card">
        <div class="m-card-header">
            <div class="m-card-title">
                <i class="bi bi-key"></i>
                Alterar senha
            </div>
        </div>

        <div class="m-card-body">
            @if(! $user->totp_enabled)
                <div style="background:#fef2f2;border:1.5px solid #fecaca;border-radius:10px;padding:14px 18px;font-size:13px;color:#991b1b;">
                    <div style="font-weight:700;margin-bottom:4px;"><i class="bi bi-exclamation-triangle-fill"></i> 2FA obrigatório</div>
                    Pra alterar a senha da conta master é necessário ter 2FA configurado primeiro.
                    <a href="{{ route('master.2fa.setup') }}" style="display:inline-block;margin-top:8px;background:#dc2626;color:#fff;border-radius:8px;padding:8px 14px;font-size:12.5px;font-weight:600;text-decoration:none;">
                        <i class="bi bi-shield-lock"></i> Configurar 2FA
                    </a>
                </div>
            @else
                <p style="font-size:13.5px;color:#6b7280;margin-bottom:20px;line-height:1.6;">
                    Por segurança, a alteração de senha exige a <strong>senha atual</strong> e um <strong>código 2FA</strong> válido do seu app autenticador no momento da confirmação — mesmo que você já tenha feito login com 2FA hoje.
                </p>

                @if($errors->any())
                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#dc2626;">
                        @foreach($errors->all() as $err)
                            <div><i class="bi bi-exclamation-circle"></i> {{ $err }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('master.account.password') }}">
                    @csrf

                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:5px;">
                            Senha atual
                        </label>
                        <input type="password" name="current_password" required autocomplete="current-password"
                               style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:14px;outline:none;"
                               onfocus="this.style.borderColor='#0085f3'" onblur="this.style.borderColor='#e5e7eb'">
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:5px;">
                                Nova senha
                            </label>
                            <input type="password" name="new_password" required autocomplete="new-password" minlength="8"
                                   style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:14px;outline:none;"
                                   onfocus="this.style.borderColor='#0085f3'" onblur="this.style.borderColor='#e5e7eb'">
                            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Min 8 caracteres, maiúsculas/minúsculas e números.</div>
                        </div>
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:5px;">
                                Confirmar nova senha
                            </label>
                            <input type="password" name="new_password_confirmation" required autocomplete="new-password"
                                   style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:14px;outline:none;"
                                   onfocus="this.style.borderColor='#0085f3'" onblur="this.style.borderColor='#e5e7eb'">
                        </div>
                    </div>

                    <div style="margin-bottom:20px;padding-top:14px;border-top:1px dashed #e5e7eb;">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:5px;">
                            <i class="bi bi-shield-lock" style="color:#0085f3;"></i>
                            Código 2FA agora
                        </label>
                        <input type="text" name="totp_code" required maxlength="6" inputmode="numeric"
                               autocomplete="one-time-code" placeholder="000000"
                               style="width:180px;padding:12px 16px;border:2px solid #e5e7eb;border-radius:10px;font-size:20px;font-weight:700;letter-spacing:6px;text-align:center;outline:none;"
                               onfocus="this.style.borderColor='#0085f3'" onblur="this.style.borderColor='#e5e7eb'">
                        <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Abra seu app autenticador e digite o código atual de 6 dígitos.</div>
                    </div>

                    <button type="submit" class="m-btn m-btn-primary">
                        <i class="bi bi-check-lg"></i> Alterar senha
                    </button>
                </form>
            @endif
        </div>
    </div>

</div>
@endsection
