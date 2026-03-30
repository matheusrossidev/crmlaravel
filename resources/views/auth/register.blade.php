<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('auth.register_title') }}</title>
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

        .auth-wrapper {
            display: flex;
            flex-direction: row-reverse;
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
            padding: 48px 64px;
            min-width: 0;
        }

        .auth-brand {
            width: 100%;
            max-width: 360px;
            margin-bottom: 36px;
        }

        .auth-brand img {
            height: 36px;
            object-fit: contain;
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 360px;
        }

        /* Language selector */
        .lang-selector { position: relative; margin-bottom: 24px; }
        .lang-selected { display: flex; align-items: center; gap: 10px; padding: 12px 20px; border: 1.5px solid #e2e8f0; border-radius: 100px; cursor: pointer; background: #fff; transition: border-color .15s; }
        .lang-selected:hover { border-color: #cbd5e1; }
        .lang-flag { width: 24px; height: 24px; border-radius: 50%; object-fit: cover; }
        .lang-name { font-size: 14px; font-weight: 500; color: #374151; }
        .lang-chevron { margin-left: auto; color: #9ca3af; font-size: 14px; transition: transform .2s; }
        .lang-selector.open .lang-chevron { transform: rotate(180deg); }
        .lang-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 20px; margin-top: 4px; z-index: 10; display: none; box-shadow: 0 4px 16px rgba(0,0,0,.08); overflow: hidden; }
        .lang-dropdown.open { display: block; }
        .lang-option { display: flex; align-items: center; gap: 10px; padding: 12px 20px; cursor: pointer; }
        .lang-option:hover { background: #f8fafc; }
        .lang-option:first-child { border-radius: 0; }
        .lang-option:last-child { border-radius: 0; }

        /* Indicador de progresso */
        .step-progress {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 28px;
        }

        .step-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #e5e7eb;
            transition: background .2s, width .2s;
        }

        .step-dot.active {
            background: #007DFF;
            width: 20px;
            border-radius: 4px;
        }

        .step-dot.done { background: #007DFF; opacity: .4; }

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
            margin: 0 0 24px;
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

        .form-group { margin-bottom: 6px; }

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

        .invalid-feedback {
            font-size: 12px;
            color: #ef4444;
            margin-top: 5px;
        }

        /* Chip de valor preenchido (clica para editar) */
        .value-chip {
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
            margin-bottom: 14px;
            cursor: pointer;
            transition: border-color .15s, background .15s;
            user-select: none;
        }

        .value-chip:hover { border-color: #007DFF; background: #eff6ff; }
        .value-chip .chip-icon { color: #6b7280; font-size: 14px; flex-shrink: 0; }
        .value-chip .chip-val  { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .value-chip .chip-edit { color: #97A3B7; font-size: 12px; flex-shrink: 0; }

        /* Botões */
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
            margin-top: 18px;
            transition: all .4s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover { background: #0066FF; }
        .btn-submit:active { transform: scale(.98); }

        .terms-text {
            font-size: 12px;
            color: #9ca3af;
            text-align: center;
            margin-top: 14px;
            line-height: 1.5;
        }

        .terms-text a { color: #007DFF; text-decoration: none; }
        .terms-text a:hover { text-decoration: underline; }

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

        /* ── Painel direito — Imagem ── */
        .auth-right {
            flex: 1;
            position: relative;
            background: url('{{ asset("images/split-screen-login.png") }}') center center / cover no-repeat;
            overflow: hidden;
            min-height: 100vh;
            border-radius: 0 50px 50px 0;
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

            {{-- Language selector --}}
            @php
                $currentLocale = app()->getLocale();
                $languages = [
                    'pt_BR' => ['name' => __('auth.lang_pt_BR'), 'flag' => 'pt-br.png'],
                    'en'    => ['name' => __('auth.lang_en'), 'flag' => 'en.png'],
                ];
                $currentLang = $languages[$currentLocale] ?? $languages['pt_BR'];
            @endphp
            <div class="lang-selector" id="lang-selector">
                <div class="lang-selected" onclick="toggleLangDropdown()">
                    <img class="lang-flag" src="{{ asset('images/languages/' . $currentLang['flag']) }}" alt="">
                    <span class="lang-name">{{ $currentLang['name'] }}</span>
                    <i class="bi bi-chevron-down lang-chevron"></i>
                </div>
                <div class="lang-dropdown" id="lang-dropdown">
                    @foreach($languages as $code => $lang)
                        <div class="lang-option" onclick="switchLang('{{ $code }}')">
                            <img class="lang-flag" src="{{ asset('images/languages/' . $lang['flag']) }}" alt="">
                            <span class="lang-name">{{ $lang['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <input type="hidden" name="locale" id="h-locale" form="regForm" value="{{ $currentLocale }}">

            {{-- Indicador de progresso --}}
            <div class="step-progress">
                <div class="step-dot active" id="dot-1"></div>
                <div class="step-dot" id="dot-2"></div>
                <div class="step-dot" id="dot-3"></div>
                <div class="step-dot" id="dot-4"></div>
            </div>

            <h2 class="auth-form-title" id="step-title">{{ __('auth.step1_title') }}</h2>
            <p class="auth-form-sub" id="step-sub">{{ __('auth.step1_sub') }}</p>

            @if($errors->any())
            <div class="auth-error">
                <i class="bi bi-exclamation-circle"></i>
                <div>{{ $errors->first() }}</div>
            </div>
            @endif

            <form method="POST" action="{{ route('register.post') }}" id="regForm">
                @csrf

                {{-- Campos hidden (enviados no POST) --}}
                <input type="hidden" name="tenant_name"            id="h-tenant">
                <input type="hidden" name="name"                   id="h-name">
                <input type="hidden" name="phone"                  id="h-phone">
                <input type="hidden" name="email"                  id="h-email">

                {{-- Etapa 1 — Empresa --}}
                <div id="step-1">
                    <div class="form-group">
                        <label for="d-tenant">{{ __('auth.company_label') }}</label>
                        <div class="input-wrap">
                            <i class="bi bi-building"></i>
                            <input type="text"
                                   id="d-tenant"
                                   class="form-control {{ $errors->has('tenant_name') ? 'is-invalid' : '' }}"
                                   placeholder="{{ __('auth.company_placeholder') }}"
                                   autocomplete="organization"
                                   autofocus
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();goStep(2);}">
                        </div>
                        @error('tenant_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="button" class="btn-submit" onclick="goStep(2)">
                        {{ __('auth.continue') }} <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

                {{-- Etapa 2 — Nome --}}
                <div id="step-2" style="display:none;">
                    <div class="value-chip" onclick="goStep(1)" title="{{ __('auth.change_company') }}">
                        <i class="bi bi-building chip-icon"></i>
                        <span class="chip-val" id="chip-tenant"></span>
                        <i class="bi bi-pencil chip-edit"></i>
                    </div>
                    <div class="form-group">
                        <label for="d-name">{{ __('auth.your_name_label') }}</label>
                        <div class="input-wrap">
                            <i class="bi bi-person"></i>
                            <input type="text"
                                   id="d-name"
                                   class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                   placeholder="{{ __('auth.your_name_placeholder') }}"
                                   autocomplete="name"
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('d-phone').focus();}">
                        </div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="d-phone">{{ __('auth.phone_label') ?? 'WhatsApp' }}</label>
                        <div class="input-wrap">
                            <i class="bi bi-whatsapp" style="color:#25D366;"></i>
                            <input type="tel"
                                   id="d-phone"
                                   class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}"
                                   placeholder="{{ __('auth.phone_placeholder') ?? '(11) 99999-9999' }}"
                                   autocomplete="tel"
                                   maxlength="20"
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();goStep(3);}">
                        </div>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="button" class="btn-submit" onclick="goStep(3)">
                        {{ __('auth.continue') }} <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

                {{-- Etapa 3 — E-mail --}}
                <div id="step-3" style="display:none;">
                    <div class="value-chip" onclick="goStep(1)" title="{{ __('auth.change_company') }}">
                        <i class="bi bi-building chip-icon"></i>
                        <span class="chip-val" id="chip-tenant-3"></span>
                        <i class="bi bi-pencil chip-edit"></i>
                    </div>
                    <div class="value-chip" onclick="goStep(2)" title="{{ __('auth.change_name') }}">
                        <i class="bi bi-person chip-icon"></i>
                        <span class="chip-val" id="chip-name-3"></span>
                        <i class="bi bi-pencil chip-edit"></i>
                    </div>
                    <div class="form-group">
                        <label for="d-email">{{ __('auth.email_label') }}</label>
                        <div class="input-wrap">
                            <i class="bi bi-envelope"></i>
                            <input type="email"
                                   id="d-email"
                                   class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                   placeholder="{{ __('auth.email_register_placeholder') }}"
                                   autocomplete="email"
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();goStep(4);}">
                        </div>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="button" class="btn-submit" onclick="goStep(4)">
                        {{ __('auth.continue') }} <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

                {{-- Etapa 4 — Senha --}}
                <div id="step-4" style="display:none;">
                    <div class="value-chip" onclick="goStep(1)" title="{{ __('auth.change_company') }}">
                        <i class="bi bi-building chip-icon"></i>
                        <span class="chip-val" id="chip-tenant-4"></span>
                        <i class="bi bi-pencil chip-edit"></i>
                    </div>
                    <div class="form-group">
                        <label for="password">{{ __('auth.password_label') }}</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock"></i>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                   placeholder="{{ __('auth.password_register_placeholder') }}"
                                   autocomplete="new-password"
                                   oninput="this.classList.remove('is-invalid');const fb=this.closest('.form-group').querySelector('.invalid-feedback');if(fb)fb.style.display='none';">
                            <i class="bi bi-eye toggle-pwd" onclick="togglePassword(this, 'password')"></i>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">{{ __('auth.confirm_password_label') }}</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock-fill"></i>
                            <input type="password"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   class="form-control {{ $errors->has('password_confirmation') ? 'is-invalid' : '' }}"
                                   placeholder="{{ __('auth.confirm_password_placeholder') }}"
                                   autocomplete="new-password"
                                   oninput="this.classList.remove('is-invalid');const fb=this.closest('.form-group').querySelector('.invalid-feedback');if(fb)fb.style.display='none';">
                            <i class="bi bi-eye toggle-pwd" onclick="togglePassword(this, 'password_confirmation')"></i>
                        </div>
                        @error('password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- Código de agência parceira (opcional) --}}
                    <div id="agency-code-wrap" style="margin-bottom:16px;">
                        <button type="button" onclick="toggleAgencyCode()" id="agency-toggle-btn"
                                style="background:none;border:none;padding:0;font-size:12.5px;color:#6b7280;cursor:pointer;display:flex;align-items:center;gap:5px;">
                            <i class="bi bi-building" id="agency-toggle-icon"></i>
                            <span id="agency-toggle-text">{{ __('auth.agency_code_question') }}</span>
                        </button>
                        <div id="agency-code-field" style="display:none;margin-top:10px;">
                            <div class="input-wrap">
                                <i class="bi bi-building"></i>
                                <input type="text" id="agency_code" name="agency_code"
                                       class="form-control" placeholder="{{ __('auth.agency_code_placeholder') }}"
                                       style="font-family:monospace;font-weight:600;letter-spacing:.04em;"
                                       value="{{ old('agency_code', request('agency')) }}"
                                       maxlength="20"
                                       oninput="this.value=this.value.toUpperCase()">
                            </div>
                            <div style="font-size:11.5px;color:#9ca3af;margin-top:4px;">{{ __('auth.agency_code_hint') }}</div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:13px;color:#374151;line-height:1.5;">
                            <input type="checkbox" name="accept_terms" value="1"
                                   {{ old('accept_terms') ? 'checked' : '' }}
                                   required
                                   style="margin-top:3px;accent-color:#007DFF;min-width:16px;">
                            <span>{{ __('auth.accept_terms_text') }}
                                <a href="{{ route('terms') }}" target="_blank" style="color:#007DFF;">{{ __('auth.terms_of_use') }}</a> {{ __('auth.accept_terms_and') }}
                                <a href="{{ route('privacy') }}" target="_blank" style="color:#007DFF;">{{ __('auth.privacy_policy') }}</a>.
                            </span>
                        </label>
                        @error('accept_terms')
                            <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="bi bi-rocket-takeoff"></i>
                        {{ __('auth.create_account_button') }}
                    </button>
                </div>

            </form>

            <div class="auth-footer-link">
                {{ __('auth.already_have_account') }} <a href="{{ route('login') }}">{{ __('auth.login_now') }}</a>
            </div>

        </div>
    </div>

    {{-- ── Painel direito — Imagem ── --}}
    <div class="auth-right"></div>

</div>

<script>
    // ── Language selector ──
    function toggleLangDropdown() {
        var sel = document.getElementById('lang-selector');
        var dd  = document.getElementById('lang-dropdown');
        sel.classList.toggle('open');
        dd.classList.toggle('open');
    }

    function switchLang(code) {
        document.getElementById('h-locale').value = code;
        var url = new URL(window.location.href);
        url.searchParams.set('lang', code);
        window.location.href = url.toString();
    }

    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        var sel = document.getElementById('lang-selector');
        if (!sel.contains(e.target)) {
            sel.classList.remove('open');
            document.getElementById('lang-dropdown').classList.remove('open');
        }
    });

    // ── Step wizard ──
    const STEPS = {
        1: { title: {!! json_encode(__('auth.step1_title')) !!}, sub: {!! json_encode(__('auth.step1_sub')) !!} },
        2: { title: {!! json_encode(__('auth.step2_title')) !!}, sub: {!! json_encode(__('auth.step2_sub')) !!} },
        3: { title: {!! json_encode(__('auth.step3_title')) !!}, sub: {!! json_encode(__('auth.step3_sub')) !!} },
        4: { title: {!! json_encode(__('auth.step4_title')) !!}, sub: {!! json_encode(__('auth.step4_sub')) !!} },
    };

    let currentStep = 1;

    // Se voltou com erros do servidor, restaurar dados do old() e ir para o step correto
    @if(old('tenant_name'))
        document.getElementById('d-tenant').value = '{{ old('tenant_name') }}';
        document.getElementById('h-tenant').value  = '{{ old('tenant_name') }}';
    @endif
    @if(old('name'))
        document.getElementById('d-name').value = '{{ old('name') }}';
        document.getElementById('h-name').value  = '{{ old('name') }}';
    @endif
    @if(old('phone'))
        document.getElementById('d-phone').value = '{{ old('phone') }}';
        document.getElementById('h-phone').value  = '{{ old('phone') }}';
    @endif
    @if(old('email'))
        document.getElementById('d-email').value = '{{ old('email') }}';
        document.getElementById('h-email').value  = '{{ old('email') }}';
    @endif

    // Determina o passo inicial com base nos dados já preenchidos
    @if($errors->has('password') || $errors->has('password_confirmation'))
        goStep(4, true);
    @elseif($errors->has('email'))
        goStep(3, true);
    @elseif($errors->has('name') || $errors->has('phone'))
        goStep(2, true);
    @elseif($errors->any())
        goStep(1, true);
    @endif

    function goStep(n, skipValidation) {
        if (!skipValidation && !validateStep(currentStep)) return;

        // Oculta o banner de erro ao navegar (cada step tem seu próprio bloco de erro inline)
        const errorBanner = document.querySelector('.auth-error');
        if (errorBanner) errorBanner.style.display = 'none';

        // Salva valor atual nos hidden inputs e chips
        if (currentStep === 1) {
            const v = document.getElementById('d-tenant').value.trim();
            document.getElementById('h-tenant').value = v;
            document.getElementById('chip-tenant').textContent   = v;
            document.getElementById('chip-tenant-3').textContent = v;
            document.getElementById('chip-tenant-4').textContent = v;
        } else if (currentStep === 2) {
            const v = document.getElementById('d-name').value.trim();
            document.getElementById('h-name').value = v;
            document.getElementById('chip-name-3').textContent = v;
            document.getElementById('h-phone').value = document.getElementById('d-phone').value.trim();
        } else if (currentStep === 3) {
            const v = document.getElementById('d-email').value.trim();
            document.getElementById('h-email').value = v;
        }

        // Oculta step atual, mostra o novo
        document.getElementById('step-' + currentStep).style.display = 'none';
        document.getElementById('step-' + n).style.display = 'block';
        currentStep = n;

        // Atualiza título e subtítulo
        document.getElementById('step-title').textContent = STEPS[n].title;
        document.getElementById('step-sub').textContent   = STEPS[n].sub;

        // Atualiza dots de progresso
        for (let i = 1; i <= 4; i++) {
            const dot = document.getElementById('dot-' + i);
            dot.className = 'step-dot' + (i === n ? ' active' : (i < n ? ' done' : ''));
        }

        // Focus no input do novo step
        const inputs = document.querySelectorAll('#step-' + n + ' input:not([type=hidden])');
        if (inputs.length) setTimeout(() => inputs[0].focus(), 50);
    }

    function validateStep(n) {
        if (n === 1) {
            const v = document.getElementById('d-tenant').value.trim();
            if (!v) { document.getElementById('d-tenant').focus(); return false; }
        } else if (n === 2) {
            const v = document.getElementById('d-name').value.trim();
            if (!v) { document.getElementById('d-name').focus(); return false; }
            const ph = document.getElementById('d-phone').value.replace(/\D/g, '');
            if (ph.length < 10) { document.getElementById('d-phone').focus(); return false; }
        } else if (n === 3) {
            const v = document.getElementById('d-email').value.trim();
            if (!v || !v.includes('@')) { document.getElementById('d-email').focus(); return false; }
        } else if (n === 4) {
            const pwd = document.getElementById('password').value;
            const conf = document.getElementById('password_confirmation').value;
            if (pwd.length < 8) {
                document.getElementById('password').classList.add('is-invalid');
                document.getElementById('password').focus();
                return false;
            }
            if (pwd !== conf) {
                document.getElementById('password_confirmation').classList.add('is-invalid');
                document.getElementById('password_confirmation').focus();
                return false;
            }
        }
        return true;
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

    var agencyQuestionText = {!! json_encode(__('auth.agency_code_question')) !!};
    var agencyRemoveText   = {!! json_encode(__('auth.agency_code_remove')) !!};

    function toggleAgencyCode() {
        const field  = document.getElementById('agency-code-field');
        const icon   = document.getElementById('agency-toggle-icon');
        const text   = document.getElementById('agency-toggle-text');
        const isOpen = field.style.display !== 'none';
        field.style.display = isOpen ? 'none' : 'block';
        icon.className = isOpen ? 'bi bi-building' : 'bi bi-x-circle';
        text.textContent = isOpen ? agencyQuestionText : agencyRemoveText;
        if (!isOpen) document.getElementById('agency_code').focus();
    }

    // Auto-expand if pre-filled from URL or old()
    (function () {
        const input = document.getElementById('agency_code');
        if (input && input.value.trim()) {
            document.getElementById('agency-code-field').style.display = 'block';
            document.getElementById('agency-toggle-icon').className = 'bi bi-x-circle';
            document.getElementById('agency-toggle-text').textContent = agencyRemoveText;
        }
    })();
</script>

@include('components.cookie-consent')

</body>
</html>
