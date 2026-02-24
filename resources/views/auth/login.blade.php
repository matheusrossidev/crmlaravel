<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — Syncro</title>
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
            font-size: 26px;
            font-weight: 700;
            color: #1a1d23;
            margin: 0 0 6px;
        }

        .auth-form-sub {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 28px;
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

        /* E-mail chip (step 2) */
        .email-chip {
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
            margin-bottom: 20px;
            cursor: pointer;
            transition: border-color .15s, background .15s;
            user-select: none;
        }

        .email-chip:hover {
            border-color: #0085f3;
            background: #eff6ff;
        }

        .email-chip .chip-icon { color: #6b7280; font-size: 14px; flex-shrink: 0; }
        .email-chip .chip-email { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .email-chip .chip-edit { color: #9ca3af; font-size: 12px; flex-shrink: 0; }

        /* Buttons */
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
            margin-top: 6px;
            transition: background .15s, transform .1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover { background: #006acf; }
        .btn-submit:active { transform: scale(.98); }

        /* Footer link */
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

        /* Gradiente escuro para legibilidade do texto */
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

        /* Textos no canto inferior esquerdo */
        .auth-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 48px 56px;
            z-index: 1;
        }

        .auth-overlay h1 {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            line-height: 1.3;
            margin: 0 0 14px;
            max-width: 480px;
        }

        .auth-overlay .auth-subtitle {
            font-size: 13px;
            color: rgba(255,255,255,.78);
            line-height: 1.65;
            margin: 0 0 8px;
            max-width: 460px;
        }

        .auth-features {
            margin-top: 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .auth-feature {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .auth-feature-icon {
            width: 30px;
            height: 30px;
            background: rgba(255,255,255,.15);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #fff;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .auth-feature-text strong {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 2px;
        }

        .auth-feature-text span {
            font-size: 12px;
            color: rgba(255,255,255,.65);
            line-height: 1.5;
        }

        .auth-tagline {
            margin-top: 28px;
            font-size: 14px;
            font-weight: 700;
            color: rgba(255,255,255,.95);
            letter-spacing: .01em;
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

            <h2 class="auth-form-title">Bem-vindo</h2>
            <p class="auth-form-sub">Informe seu e-mail para entrar</p>

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
                        <label for="email-display">E-mail</label>
                        <div class="input-wrap">
                            <i class="bi bi-envelope"></i>
                            <input type="email"
                                   id="email-display"
                                   class="form-control"
                                   placeholder="seuemail@empresa.com"
                                   autocomplete="email"
                                   autofocus
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();nextStep();}">
                        </div>
                    </div>
                    <button type="button" class="btn-submit" onclick="nextStep()">
                        Avançar
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

                {{-- Etapa 2: Senha --}}
                <div id="step-password" style="display:none;">
                    <div class="email-chip" onclick="backStep()" title="Alterar e-mail">
                        <i class="bi bi-envelope chip-icon"></i>
                        <span class="chip-email" id="chip-email-text"></span>
                        <i class="bi bi-pencil chip-edit"></i>
                    </div>

                    <div class="form-group">
                        <label for="password">Senha</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock"></i>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control"
                                   placeholder="••••••••"
                                   autocomplete="current-password">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Entrar
                    </button>
                </div>

            </form>

            <div class="auth-footer-link">
                É novo por aqui?<br>
                <a href="{{ route('register') }}">Crie uma conta e teste gratuitamente</a>
            </div>

        </div>
    </div>

    {{-- ── Painel direito — Imagem ── --}}
    <div class="auth-right">
        <div class="auth-overlay">

            <h1>Controle Total do Seu Marketing e Vendas em Uma Única Plataforma</h1>

            <div class="auth-features">
                <div class="auth-feature">
                    <div class="auth-feature-icon">
                        <i class="bi bi-kanban"></i>
                    </div>
                    <div class="auth-feature-text">
                        <strong>CRM visual com Kanban e pipelines totalmente personalizáveis</strong>
                        <span>Organize oportunidades, acompanhe negociações em tempo real e adapte o funil ao seu processo comercial.</span>
                    </div>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon">
                        <i class="bi bi-bar-chart-line"></i>
                    </div>
                    <div class="auth-feature-text">
                        <strong>Relatórios completos de ROAS, vendas e conversão</strong>
                        <span>Tome decisões baseadas em dados com dashboards claros e métricas que realmente importam para o crescimento.</span>
                    </div>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon">
                        <i class="bi bi-whatsapp"></i>
                    </div>
                    <div class="auth-feature-text">
                        <strong>Integração oficial com WhatsApp Cloud API</strong>
                        <span>Converse com leads e clientes direto da plataforma, mantendo histórico, contexto e produtividade do time.</span>
                    </div>
                </div>
            </div>


        </div>
    </div>

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

    function backStep() {
        emailDisplay.value = emailHidden.value;
        stepPwd.style.display   = 'none';
        stepEmail.style.display = 'block';
        emailDisplay.focus();
    }
</script>
</body>
</html>
