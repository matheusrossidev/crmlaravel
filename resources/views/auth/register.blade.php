<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta — Syncro CRM</title>
    <meta name="description" content="CRM completo com atendimento automático via WhatsApp, agente de IA, funil de vendas e agenda integrada. Gerencie leads e converta mais com menos esforço.">

    {{-- Open Graph / Social Sharing --}}
    <meta property="og:type"         content="website">
    <meta property="og:site_name"    content="Syncro CRM">
    <meta property="og:title"        content="Syncro CRM — Gestão de Clientes e Atendimento via WhatsApp">
    <meta property="og:description"  content="CRM completo com atendimento automático via WhatsApp, agente de IA, funil de vendas e agenda integrada. Gerencie leads e converta mais com menos esforço.">
    <meta property="og:image"        content="{{ asset('images/shared-image.jpg') }}">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url"          content="{{ url('/') }}">
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="Syncro CRM — Gestão de Clientes e Atendimento via WhatsApp">
    <meta name="twitter:description" content="CRM completo com atendimento automático via WhatsApp, agente de IA, funil de vendas e agenda integrada. Gerencie leads e converta mais com menos esforço.">
    <meta name="twitter:image"       content="{{ asset('images/shared-image.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
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
            background: #0085f3;
            width: 20px;
            border-radius: 4px;
        }

        .step-dot.done { background: #0085f3; opacity: .4; }

        .auth-form-title {
            font-size: 26px;
            font-weight: 700;
            color: #1a1d23;
            margin: 0 0 6px;
        }

        .auth-form-sub {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 24px;
        }

        /* Error block */
        .auth-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
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
            color: #9ca3af;
            font-size: 15px;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 11px 14px 11px 38px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #1a1d23;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            background: #fafafa;
        }

        .form-control:focus {
            border-color: #0085f3;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0,133,243,.1);
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
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 13.5px;
            font-weight: 500;
            color: #1a1d23;
            margin-bottom: 14px;
            cursor: pointer;
            transition: border-color .15s, background .15s;
            user-select: none;
        }

        .value-chip:hover { border-color: #0085f3; background: #eff6ff; }
        .value-chip .chip-icon { color: #6b7280; font-size: 14px; flex-shrink: 0; }
        .value-chip .chip-val  { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .value-chip .chip-edit { color: #9ca3af; font-size: 12px; flex-shrink: 0; }

        /* Botões */
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #0085f3;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 14.5px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            margin-top: 18px;
            transition: background .15s, transform .1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover { background: #006acf; }
        .btn-submit:active { transform: scale(.98); }

        .terms-text {
            font-size: 12px;
            color: #9ca3af;
            text-align: center;
            margin-top: 14px;
            line-height: 1.5;
        }

        .terms-text a { color: #0085f3; text-decoration: none; }
        .terms-text a:hover { text-decoration: underline; }

        .auth-footer-link {
            text-align: center;
            font-size: 13.5px;
            color: #6b7280;
            margin-top: 28px;
        }

        .auth-footer-link a {
            color: #0085f3;
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
        }

        .auth-right::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to top,
                rgba(0,0,0,.72) 0%,
                rgba(0,0,0,.18) 50%,
                rgba(0,0,0,.05) 100%
            );
        }

        /* Responsivo */
        @media (max-width: 960px) {
            .auth-right { display: none; }
            .auth-left  { flex: none; width: 100%; padding: 40px 24px; }
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

            {{-- Indicador de progresso --}}
            <div class="step-progress">
                <div class="step-dot active" id="dot-1"></div>
                <div class="step-dot" id="dot-2"></div>
                <div class="step-dot" id="dot-3"></div>
                <div class="step-dot" id="dot-4"></div>
            </div>

            <h2 class="auth-form-title" id="step-title">Bem-vindo</h2>
            <p class="auth-form-sub" id="step-sub">Como se chama sua empresa?</p>

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
                <input type="hidden" name="email"                  id="h-email">

                {{-- Etapa 1 — Empresa --}}
                <div id="step-1">
                    <div class="form-group">
                        <label for="d-tenant">Nome da empresa / workspace</label>
                        <div class="input-wrap">
                            <i class="bi bi-building"></i>
                            <input type="text"
                                   id="d-tenant"
                                   class="form-control {{ $errors->has('tenant_name') ? 'is-invalid' : '' }}"
                                   placeholder="Ex: Agência XYZ"
                                   autocomplete="organization"
                                   autofocus
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();goStep(2);}">
                        </div>
                        @error('tenant_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="button" class="btn-submit" onclick="goStep(2)">
                        Continuar <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

                {{-- Etapa 2 — Nome --}}
                <div id="step-2" style="display:none;">
                    <div class="value-chip" onclick="goStep(1)" title="Alterar empresa">
                        <i class="bi bi-building chip-icon"></i>
                        <span class="chip-val" id="chip-tenant"></span>
                        <i class="bi bi-pencil chip-edit"></i>
                    </div>
                    <div class="form-group">
                        <label for="d-name">Seu nome</label>
                        <div class="input-wrap">
                            <i class="bi bi-person"></i>
                            <input type="text"
                                   id="d-name"
                                   class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                   placeholder="João Silva"
                                   autocomplete="name"
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();goStep(3);}">
                        </div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="button" class="btn-submit" onclick="goStep(3)">
                        Continuar <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

                {{-- Etapa 3 — E-mail --}}
                <div id="step-3" style="display:none;">
                    <div class="value-chip" onclick="goStep(1)" title="Alterar empresa">
                        <i class="bi bi-building chip-icon"></i>
                        <span class="chip-val" id="chip-tenant-3"></span>
                        <i class="bi bi-pencil chip-edit"></i>
                    </div>
                    <div class="value-chip" onclick="goStep(2)" title="Alterar nome">
                        <i class="bi bi-person chip-icon"></i>
                        <span class="chip-val" id="chip-name-3"></span>
                        <i class="bi bi-pencil chip-edit"></i>
                    </div>
                    <div class="form-group">
                        <label for="d-email">E-mail</label>
                        <div class="input-wrap">
                            <i class="bi bi-envelope"></i>
                            <input type="email"
                                   id="d-email"
                                   class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                   placeholder="joao@empresa.com"
                                   autocomplete="email"
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();goStep(4);}">
                        </div>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="button" class="btn-submit" onclick="goStep(4)">
                        Continuar <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

                {{-- Etapa 4 — Senha --}}
                <div id="step-4" style="display:none;">
                    <div class="value-chip" onclick="goStep(1)" title="Alterar empresa">
                        <i class="bi bi-building chip-icon"></i>
                        <span class="chip-val" id="chip-tenant-4"></span>
                        <i class="bi bi-pencil chip-edit"></i>
                    </div>
                    <div class="form-group">
                        <label for="password">Senha</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock"></i>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                   placeholder="Mín. 8 caracteres"
                                   autocomplete="new-password">
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirmar senha</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock-fill"></i>
                            <input type="password"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   class="form-control"
                                   placeholder="Repita a senha"
                                   autocomplete="new-password">
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-rocket-takeoff"></i>
                        Criar minha conta
                    </button>
                    <p class="terms-text">
                        Ao criar uma conta você concorda com nossos
                        <a href="#">Termos de Uso</a> e <a href="#">Política de Privacidade</a>.
                    </p>
                </div>

            </form>

            <div class="auth-footer-link">
                Já tem uma conta? <a href="{{ route('login') }}">Entrar agora</a>
            </div>

        </div>
    </div>

    {{-- ── Painel direito — Imagem ── --}}
    <div class="auth-right"></div>

</div>

<script>
    const STEPS = {
        1: { title: 'Bem-vindo',          sub: 'Como se chama sua empresa?' },
        2: { title: 'Quase lá',            sub: 'Como podemos te chamar?' },
        3: { title: 'Ótimo!',              sub: 'Qual é o seu e-mail?' },
        4: { title: 'Último passo',        sub: 'Crie uma senha de acesso' },
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
    @if(old('email'))
        document.getElementById('d-email').value = '{{ old('email') }}';
        document.getElementById('h-email').value  = '{{ old('email') }}';
    @endif

    // Determina o passo inicial com base nos dados já preenchidos
    @if($errors->has('password') || $errors->has('password_confirmation'))
        goStep(4, true);
    @elseif($errors->has('email'))
        goStep(3, true);
    @elseif($errors->has('name'))
        goStep(2, true);
    @elseif($errors->any())
        goStep(1, true);
    @endif

    function goStep(n, skipValidation) {
        if (!skipValidation && !validateStep(currentStep)) return;

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
        } else if (n === 3) {
            const v = document.getElementById('d-email').value.trim();
            if (!v || !v.includes('@')) { document.getElementById('d-email').focus(); return false; }
        }
        return true;
    }
</script>
</body>
</html>
