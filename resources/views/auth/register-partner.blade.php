<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programa de Parceiros — Syncro CRM</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; margin: 0; min-height: 100vh; display: flex; }

        .auth-wrapper { display: flex; flex-direction: row-reverse; width: 100%; min-height: 100vh; }

        .auth-left {
            flex: 1; background: #fff;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 48px 64px; min-width: 0;
        }

        .auth-brand { width: 100%; max-width: 360px; margin-bottom: 36px; }
        .auth-brand img { height: 36px; object-fit: contain; }

        .auth-form-wrap { width: 100%; max-width: 360px; }

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

        .step-progress { display: flex; align-items: center; gap: 6px; margin-bottom: 28px; }
        .step-dot { width: 8px; height: 8px; border-radius: 50%; background: #e5e7eb; transition: background .2s, width .2s; }
        .step-dot.active { background: #007DFF; width: 20px; border-radius: 4px; }
        .step-dot.done { background: #007DFF; opacity: .4; }

        .auth-form-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 26px; font-weight: 700; color: #1a1d23; margin: 0 0 6px; }
        .auth-form-sub { font-size: 14px; color: #677489; margin: 0 0 24px; }

        .auth-error { background: #fef2f2; border: 1px solid #fecaca; border-radius: 14px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #dc2626; display: flex; gap: 8px; align-items: flex-start; }
        .auth-error i { font-size: 15px; flex-shrink: 0; margin-top: 1px; }

        .form-group { margin-bottom: 6px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }

        .input-wrap { position: relative; }
        .input-wrap i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #97A3B7; font-size: 15px; pointer-events: none; }
        .input-wrap .toggle-pwd { left: auto; right: 13px; pointer-events: auto; cursor: pointer; font-size: 16px; }

        .form-control {
            width: 100%; padding: 11px 14px 11px 38px;
            border: 1px solid #CDDEF6; border-radius: 100px;
            font-size: 14px; font-family: 'DM Sans', sans-serif; color: #1a1d23;
            outline: none; transition: border-color .15s, box-shadow .15s; background: #fff;
        }
        .form-control:focus { border-color: #007DFF; box-shadow: 0 0 0 3px rgba(0,125,255,.12); }
        .form-control.is-invalid { border-color: #ef4444; }
        .invalid-feedback { font-size: 12px; color: #ef4444; margin-top: 5px; }

        select.form-control { padding-right: 14px; cursor: pointer; appearance: none; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%239ca3af' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") no-repeat right 14px center; }

        .input-row { display: flex; gap: 10px; }
        .input-row .form-group { flex: 1; }

        .value-chip {
            display: flex; align-items: center; gap: 9px;
            background: #f3f4f6; border: 1px solid #CDDEF6; border-radius: 100px;
            padding: 9px 14px; font-size: 13.5px; font-weight: 500; color: #1a1d23;
            margin-bottom: 14px; cursor: pointer; transition: border-color .15s, background .15s; user-select: none;
        }
        .value-chip:hover { border-color: #007DFF; background: #eff6ff; }
        .value-chip .chip-icon { color: #6b7280; font-size: 14px; flex-shrink: 0; }
        .value-chip .chip-val { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .value-chip .chip-edit { color: #97A3B7; font-size: 12px; flex-shrink: 0; }

        .btn-submit {
            width: 100%; padding: 13px 30px;
            background: linear-gradient(148deg, #2C83FB 0%, #1970EA 100%);
            color: #fff; border: none; border-radius: 100px;
            font-size: 14.5px; font-weight: 600; font-family: 'DM Sans', sans-serif;
            cursor: pointer; margin-top: 18px; transition: all .4s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit:hover { background: #0066FF; }
        .btn-submit:active { transform: scale(.98); }
        .btn-submit:disabled { opacity: .6; cursor: not-allowed; }

        .terms-text { font-size: 12px; color: #9ca3af; text-align: center; margin-top: 14px; line-height: 1.5; }
        .terms-text a { color: #007DFF; text-decoration: none; }

        .auth-footer-link { text-align: center; font-size: 13.5px; color: #6b7280; margin-top: 28px; }
        .auth-footer-link a { color: #007DFF; font-weight: 600; text-decoration: none; }

        .auth-right {
            flex: 1; position: relative;
            background: url('{{ asset("images/split-screen-login.png") }}') center center / cover no-repeat;
            overflow: hidden; min-height: 100vh; border-radius: 0 50px 50px 0;
        }

        @media (max-width: 960px) { .auth-right { display: none; } .auth-left { flex: none; width: 100%; padding: 40px 24px; } }
        @media (max-width: 768px) { .form-control { font-size: 16px !important; } }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-left">
        <div class="auth-brand"><img src="{{ asset('images/logo.png') }}" alt="Syncro"></div>
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

            <div class="step-progress">
                <div class="step-dot active" id="dot-1"></div>
                <div class="step-dot" id="dot-2"></div>
                <div class="step-dot" id="dot-3"></div>
                <div class="step-dot" id="dot-4"></div>
            </div>

            <h2 class="auth-form-title" id="step-title">Sua agência</h2>
            <p class="auth-form-sub" id="step-sub">Informe os dados da sua empresa.</p>

            @if($errors->any())
            <div class="auth-error"><i class="bi bi-exclamation-circle"></i><div>{{ $errors->first() }}</div></div>
            @endif

            <form method="POST" action="{{ route('agency.register.store') }}" id="regForm">
                @csrf
                <input type="hidden" name="tenant_name" id="h-tenant">
                <input type="hidden" name="cnpj"        id="h-cnpj">
                <input type="hidden" name="name"        id="h-name">
                <input type="hidden" name="phone"       id="h-phone">
                <input type="hidden" name="segment"     id="h-segment">
                <input type="hidden" name="email"       id="h-email">
                <input type="hidden" name="website"     id="h-website">
                <input type="hidden" name="city"        id="h-city">
                <input type="hidden" name="state"       id="h-state">

                {{-- Step 1 — Empresa --}}
                <div id="step-1">
                    <div class="form-group">
                        <label>Nome da empresa *</label>
                        <div class="input-wrap">
                            <i class="bi bi-building"></i>
                            <input type="text" id="d-tenant" class="form-control" placeholder="Ex: Digital Labs Marketing" autofocus
                                onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('d-cnpj').focus();}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>CNPJ</label>
                        <div class="input-wrap">
                            <i class="bi bi-file-earmark-text"></i>
                            <input type="text" id="d-cnpj" class="form-control" placeholder="00.000.000/0000-00" maxlength="18"
                                onkeydown="if(event.key==='Enter'){event.preventDefault();goStep(2);}">
                        </div>
                    </div>
                    <button type="button" class="btn-submit" onclick="goStep(2)">Continuar <i class="bi bi-arrow-right"></i></button>
                </div>

                {{-- Step 2 — Dados pessoais --}}
                <div id="step-2" style="display:none;">
                    <div class="value-chip" onclick="goStep(1)">
                        <i class="bi bi-building chip-icon"></i>
                        <span class="chip-val" id="chip-tenant"></span>
                        <i class="bi bi-pencil chip-edit"></i>
                    </div>
                    <div class="form-group">
                        <label>Seu nome *</label>
                        <div class="input-wrap">
                            <i class="bi bi-person"></i>
                            <input type="text" id="d-name" class="form-control" placeholder="Nome completo"
                                onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('d-phone').focus();}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>WhatsApp *</label>
                        <div class="input-wrap">
                            <i class="bi bi-whatsapp" style="color:#25D366;"></i>
                            <input type="tel" id="d-phone" class="form-control" placeholder="(11) 99999-9999" maxlength="20"
                                onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('d-segment').focus();}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Segmento *</label>
                        <div class="input-wrap">
                            <i class="bi bi-briefcase"></i>
                            <select id="d-segment" class="form-control">
                                <option value="">Selecione...</option>
                                <option>Agência de Marketing</option>
                                <option>Gestão de Tráfego</option>
                                <option>Assessoria de Marketing</option>
                                <option>Agência de Social Media</option>
                                <option>Consultoria Comercial</option>
                                <option>Agência Full Service</option>
                                <option>Freelancer / Autônomo</option>
                                <option>Outro</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" class="btn-submit" onclick="goStep(3)">Continuar <i class="bi bi-arrow-right"></i></button>
                </div>

                {{-- Step 3 — Contato --}}
                <div id="step-3" style="display:none;">
                    <div class="value-chip" onclick="goStep(1)">
                        <i class="bi bi-building chip-icon"></i>
                        <span class="chip-val" id="chip-tenant-3"></span>
                        <i class="bi bi-pencil chip-edit"></i>
                    </div>
                    <div class="value-chip" onclick="goStep(2)">
                        <i class="bi bi-person chip-icon"></i>
                        <span class="chip-val" id="chip-name-3"></span>
                        <i class="bi bi-pencil chip-edit"></i>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <div class="input-wrap">
                            <i class="bi bi-envelope"></i>
                            <input type="email" id="d-email" class="form-control" placeholder="contato@agencia.com"
                                onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('d-website').focus();}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Site</label>
                        <div class="input-wrap">
                            <i class="bi bi-globe"></i>
                            <input type="text" id="d-website" class="form-control" placeholder="www.agencia.com"
                                onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('d-city').focus();}">
                        </div>
                    </div>
                    <div class="input-row">
                        <div class="form-group" style="flex:2;">
                            <label>Cidade</label>
                            <div class="input-wrap">
                                <i class="bi bi-geo-alt"></i>
                                <input type="text" id="d-city" class="form-control" placeholder="São Paulo"
                                    onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('d-state').focus();}">
                            </div>
                        </div>
                        <div class="form-group" style="flex:1;max-width:90px;">
                            <label>UF</label>
                            <div class="input-wrap">
                                <i class="bi bi-map" style="font-size:12px;"></i>
                                <input type="text" id="d-state" class="form-control" placeholder="SP" maxlength="2" style="text-transform:uppercase;"
                                    onkeydown="if(event.key==='Enter'){event.preventDefault();goStep(4);}">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-submit" onclick="goStep(4)">Continuar <i class="bi bi-arrow-right"></i></button>
                </div>

                {{-- Step 4 — Senha --}}
                <div id="step-4" style="display:none;">
                    <div class="value-chip" onclick="goStep(1)">
                        <i class="bi bi-building chip-icon"></i>
                        <span class="chip-val" id="chip-tenant-4"></span>
                        <i class="bi bi-pencil chip-edit"></i>
                    </div>
                    <div class="form-group">
                        <label>Senha *</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock"></i>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Mín. 8 caracteres" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirmar senha *</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock-fill"></i>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Repita a senha" autocomplete="new-password">
                        </div>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:8px;margin-top:14px;">
                        <input type="checkbox" name="accept_terms" id="accept_terms" value="1" style="margin-top:3px;width:16px;height:16px;cursor:pointer;">
                        <label for="accept_terms" style="font-size:12.5px;color:#677489;cursor:pointer;line-height:1.5;margin-bottom:0;">
                            Aceito os <a href="/termos-de-uso" target="_blank" style="color:#007DFF;text-decoration:none;">Termos de Uso</a> e a <a href="/politica-de-privacidade" target="_blank" style="color:#007DFF;text-decoration:none;">Política de Privacidade</a>.
                        </label>
                    </div>
                    <button type="submit" class="btn-submit" id="btnSubmit">
                        <i class="bi bi-building-check"></i> Criar conta de parceiro
                    </button>
                </div>
            </form>

            <div class="auth-footer-link">
                Já tem uma conta? <a href="{{ route('login') }}">Entrar agora</a>
            </div>
        </div>
    </div>

    <div class="auth-right"></div>
</div>

<script>
function toggleLangDropdown() {
    document.getElementById('lang-selector').classList.toggle('open');
    document.getElementById('lang-dropdown').classList.toggle('open');
}
function switchLang(code) {
    var url = new URL(window.location.href);
    url.searchParams.set('lang', code);
    window.location.href = url.toString();
}
document.addEventListener('click', function(e) {
    var sel = document.getElementById('lang-selector');
    if (!sel.contains(e.target)) { sel.classList.remove('open'); document.getElementById('lang-dropdown').classList.remove('open'); }
});

const STEPS = {
    1: { title: 'Sua agência',      sub: 'Informe os dados da sua empresa.' },
    2: { title: 'Dados pessoais',    sub: 'Conte-nos sobre você.' },
    3: { title: 'Contato',           sub: 'Como podemos te encontrar.' },
    4: { title: 'Crie sua senha',    sub: 'Proteja sua conta.' },
};

let currentStep = 1;

function goStep(n, skip) {
    if (!skip && !validateStep(currentStep)) return;
    saveStep(currentStep);

    document.getElementById('step-' + currentStep).style.display = 'none';
    document.getElementById('step-' + n).style.display = 'block';
    currentStep = n;

    document.getElementById('step-title').textContent = STEPS[n].title;
    document.getElementById('step-sub').textContent = STEPS[n].sub;

    for (let i = 1; i <= 4; i++) {
        document.getElementById('dot-' + i).className = 'step-dot' + (i === n ? ' active' : (i < n ? ' done' : ''));
    }

    const inputs = document.querySelectorAll('#step-' + n + ' input:not([type=hidden]):not([type=checkbox]), #step-' + n + ' select');
    if (inputs.length) setTimeout(() => inputs[0].focus(), 50);
}

function saveStep(n) {
    if (n === 1) {
        const v = document.getElementById('d-tenant').value.trim();
        document.getElementById('h-tenant').value = v;
        document.getElementById('h-cnpj').value = document.getElementById('d-cnpj').value.trim();
        document.getElementById('chip-tenant').textContent = v;
        document.getElementById('chip-tenant-3').textContent = v;
        document.getElementById('chip-tenant-4').textContent = v;
    } else if (n === 2) {
        document.getElementById('h-name').value = document.getElementById('d-name').value.trim();
        document.getElementById('h-phone').value = document.getElementById('d-phone').value.trim();
        document.getElementById('h-segment').value = document.getElementById('d-segment').value;
        document.getElementById('chip-name-3').textContent = document.getElementById('d-name').value.trim();
    } else if (n === 3) {
        document.getElementById('h-email').value = document.getElementById('d-email').value.trim();
        document.getElementById('h-website').value = document.getElementById('d-website').value.trim();
        document.getElementById('h-city').value = document.getElementById('d-city').value.trim();
        document.getElementById('h-state').value = document.getElementById('d-state').value.trim();
    }
}

function validateStep(n) {
    if (n === 1) {
        if (!document.getElementById('d-tenant').value.trim()) { document.getElementById('d-tenant').focus(); return false; }
    } else if (n === 2) {
        if (!document.getElementById('d-name').value.trim()) { document.getElementById('d-name').focus(); return false; }
        if (document.getElementById('d-phone').value.replace(/\D/g, '').length < 10) { document.getElementById('d-phone').focus(); return false; }
        if (!document.getElementById('d-segment').value) { document.getElementById('d-segment').focus(); return false; }
    } else if (n === 3) {
        const email = document.getElementById('d-email').value.trim();
        if (!email || !email.includes('@')) { document.getElementById('d-email').focus(); return false; }
    }
    return true;
}

document.getElementById('regForm').addEventListener('submit', function(e) {
    saveStep(currentStep);
    if (!document.getElementById('accept_terms').checked) { e.preventDefault(); alert('Aceite os termos para continuar.'); return; }
    document.getElementById('btnSubmit').disabled = true;
    document.getElementById('btnSubmit').innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Criando conta...';
});
</script>
</body>
</html>
