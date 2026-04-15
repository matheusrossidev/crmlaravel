<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include("partials._google-analytics")
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('auth.login_title') }}</title>
    <meta name="description" content="{{ __('auth.login_meta_description') }}">

    {{-- Open Graph / Social Sharing --}}
    <meta property="og:type"         content="website">
    <meta property="og:site_name"    content="Syncro CRM">
    <meta property="og:title"        content="{{ __('auth.login_title') }}">
    <meta property="og:description"  content="{{ __('auth.login_meta_description') }}">
    <meta property="og:image"        content="{{ asset('images/shared-image.jpg') }}">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url"          content="{{ url('/') }}">
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="{{ __('auth.login_title') }}">
    <meta name="twitter:description" content="{{ __('auth.login_meta_description') }}">
    <meta name="twitter:image"       content="{{ asset('images/shared-image.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
        }

        /* ── Wrapper ── */
        .auth-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* ── Painel esquerdo — Formulário ── */
        .auth-left {
            flex: 1;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 56px 64px;
            min-width: 0;
        }

        .auth-brand {
            width: 100%;
            max-width: 360px;
            margin-bottom: 40px;
        }

        .auth-brand img {
            height: 36px;
            object-fit: contain;
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 360px;
        }

        .auth-form-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 26px;
            font-weight: 700;
            color: #1a1d23;
            margin: 0 0 6px;
        }

        .auth-form-sub {
            font-size: 14px;
            color: #677489;
            margin: 0 0 28px;
        }

        /* Error block */
        .auth-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 14px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #dc2626;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }

        .auth-error i { font-size: 15px; flex-shrink: 0; margin-top: 1px; }

        .form-group { margin-bottom: 18px; }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .input-wrap { position: relative; }

        .input-wrap i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #97A3B7;
            font-size: 15px;
            pointer-events: none;
        }

        .input-wrap .toggle-pwd {
            left: auto;
            right: 13px;
            pointer-events: auto;
            cursor: pointer;
            font-size: 16px;
        }

        .input-wrap .toggle-pwd:hover { color: #374151; }

        .form-control {
            width: 100%;
            padding: 11px 14px 11px 38px;
            border: 1px solid #CDDEF6;
            border-radius: 100px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            color: #1a1d23;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            background: #fff;
        }

        .form-control:focus {
            border-color: #007DFF;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0,125,255,.12);
        }

        .form-control.is-invalid { border-color: #ef4444; }

        /* E-mail chip (step 2) */
        .email-chip {
            display: flex;
            align-items: center;
            gap: 9px;
            background: #f3f4f6;
            border: 1px solid #CDDEF6;
            border-radius: 100px;
            padding: 9px 14px;
            font-size: 13.5px;
            font-weight: 500;
            color: #1a1d23;
            margin-bottom: 20px;
            cursor: pointer;
            transition: border-color .15s, background .15s;
            user-select: none;
        }

        .email-chip:hover {
            border-color: #007DFF;
            background: #eff6ff;
        }

        .email-chip .chip-icon { color: #6b7280; font-size: 14px; flex-shrink: 0; }
        .email-chip .chip-email { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .email-chip .chip-edit { color: #97A3B7; font-size: 12px; flex-shrink: 0; }

        /* Buttons */
        .btn-submit {
            width: 100%;
            padding: 13px 30px;
            background: linear-gradient(148deg, #2C83FB 0%, #1970EA 100%);
            color: #fff;
            border: none;
            border-radius: 100px;
            font-size: 14.5px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            margin-top: 6px;
            transition: all .4s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover { background: #0066FF; }
        .btn-submit:active { transform: scale(.98); }

        /* Footer link */
        .auth-footer-link {
            text-align: center;
            font-size: 13.5px;
            color: #6b7280;
            margin-top: 28px;
        }

        .auth-footer-link a {
            color: #007DFF;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-footer-link a:hover { text-decoration: underline; }

        /* Language toggle */
        .lang-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 24px;
        }

        .lang-toggle-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 100px;
            background: #fff;
            cursor: pointer;
            font-size: 12.5px;
            font-weight: 500;
            color: #6b7280;
            text-decoration: none;
            transition: border-color .15s, background .15s;
        }

        .lang-toggle-btn:hover { border-color: #007DFF; background: #f8fafc; }
        .lang-toggle-btn.active { border-color: #007DFF; background: #eff6ff; color: #007DFF; }

        .lang-toggle-btn img {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* ── Painel direito — Imagem ── */
        .auth-right {
            flex: 1;
            position: relative;
            background: url('{{ asset("images/split-screen-login.png") }}') center center / cover no-repeat;
            overflow: hidden;
            min-height: 100vh;
            border-radius: 50px 0 0 50px;
        }


        /* Responsivo */
        @media (max-width: 960px) {
            .auth-right { display: none; }
            .auth-left  { flex: none; width: 100%; padding: 40px 24px; }
        }
        @media (max-width: 768px) {
            .form-control { font-size: 16px !important; }
        }
    </style>
</head>
<body>
<div class="auth-wrapper">

    {{-- ── Painel esquerdo — Formulário ── --}}
    <div class="auth-left">

        <div class="auth-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Syncro">
        </div>

        <div class="auth-form-wrap">

            <h2 class="auth-form-title">{{ __('auth.welcome') }}</h2>
            <p class="auth-form-sub">{{ __('auth.enter_email_to_login') }}</p>

            @if($errors->any())
            <div class="auth-error">
                <i class="bi bi-exclamation-circle"></i>
                <div>{{ $errors->first() }}</div>
            </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                {{-- Hidden email (sempre enviado no POST) --}}
                <input type="hidden" id="email-hidden" name="email" value="{{ old('email') }}">

                {{-- Etapa 1: E-mail --}}
                <div id="step-email">
                    <div class="form-group">
                        <label for="email-display">{{ __('auth.email_label') }}</label>
                        <div class="input-wrap">
                            <i class="bi bi-envelope"></i>
                            <input type="email"
                                   id="email-display"
                                   class="form-control"
                                   placeholder="{{ __('auth.email_placeholder') }}"
                                   autocomplete="email"
                                   autofocus
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();nextStep();}">
                        </div>
                    </div>
                    <button type="button" class="btn-submit" onclick="nextStep()">
                        {{ __('auth.advance') }}
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

                {{-- Etapa 2: Senha --}}
                <div id="step-password" style="display:none;">
                    <div class="email-chip" onclick="backStep()" title="{{ __('auth.change_email') }}">
                        <i class="bi bi-envelope chip-icon"></i>
                        <span class="chip-email" id="chip-email-text"></span>
                        <i class="bi bi-pencil chip-edit"></i>
                    </div>

                    <div class="form-group">
                        <label for="password">{{ __('auth.password_label') }}</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock"></i>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control"
                                   placeholder="{{ __('auth.password_placeholder') }}"
                                   autocomplete="current-password">
                            <i class="bi bi-eye toggle-pwd" onclick="togglePassword(this, 'password')"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="bi bi-box-arrow-in-right"></i>
                        {{ __('auth.login_button') }}
                    </button>
                </div>

            </form>

            <div class="auth-footer-link">
                {{ __('auth.new_here') }}<br>
                <a href="{{ route('register') }}">{{ __('auth.create_account_free') }}</a>
            </div>

            <div class="auth-footer-link" style="margin-top: 12px;">
                <a href="{{ route('password.request') }}">{{ __('auth.forgot_password_link') }}</a>
            </div>

            <div class="auth-footer-link" style="margin-top: 24px; font-size: 12px; color: #9ca3af;">
                <a href="{{ route('privacy') }}" target="_blank" style="color: #9ca3af;">{{ __('auth.privacy_policy') }}</a>
                &nbsp;&middot;&nbsp;
                <a href="{{ route('terms') }}" target="_blank" style="color: #9ca3af;">{{ __('auth.terms_of_use') }}</a>
            </div>

            {{-- Language toggle --}}
            @php
                $currentLocale = app()->getLocale();
            @endphp
            <div class="lang-toggle">
                <a href="{{ request()->fullUrlWithQuery(['lang' => 'pt_BR']) }}"
                   class="lang-toggle-btn {{ $currentLocale === 'pt_BR' ? 'active' : '' }}">
                    <img src="{{ asset('images/languages/pt-br.png') }}" alt="PT-BR">
                    PT
                </a>
                <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}"
                   class="lang-toggle-btn {{ $currentLocale === 'en' ? 'active' : '' }}">
                    <img src="{{ asset('images/languages/en.png') }}" alt="EN">
                    EN
                </a>
            </div>

        </div>
    </div>

    {{-- ── Painel direito — Imagem ── --}}
    <div class="auth-right"></div>

</div>

<script>
    const emailDisplay = document.getElementById('email-display');
    const emailHidden  = document.getElementById('email-hidden');
    const chipText     = document.getElementById('chip-email-text');
    const stepEmail    = document.getElementById('step-email');
    const stepPwd      = document.getElementById('step-password');

    // Se voltou com old('email') após erro de login, ir direto para step 2
    if (emailHidden.value) {
        showStep2(emailHidden.value);
    }

    function nextStep() {
        const val = emailDisplay.value.trim();
        if (!val || !val.includes('@')) {
            emailDisplay.classList.add('is-invalid');
            emailDisplay.focus();
            return;
        }
        emailDisplay.classList.remove('is-invalid');
        showStep2(val);
    }

    function showStep2(email) {
        emailHidden.value  = email;
        chipText.textContent = email;
        stepEmail.style.display = 'none';
        stepPwd.style.display   = 'block';
        document.getElementById('password').focus();
    }

    function togglePassword(icon, inputId) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }

    function backStep() {
        emailDisplay.value = emailHidden.value;
        stepPwd.style.display   = 'none';
        stepEmail.style.display = 'block';
        emailDisplay.focus();
    }
</script>

@include('components.cookie-consent')

</body>
</html>
