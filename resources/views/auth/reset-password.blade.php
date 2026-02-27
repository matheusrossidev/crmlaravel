<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha — Syncro</title>
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
            line-height: 1.5;
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

        .requirements {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 6px;
        }

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

            <h2 class="auth-form-title">Criar nova senha</h2>
            <p class="auth-form-sub">Escolha uma nova senha segura para sua conta.</p>

            @if($errors->any())
            <div class="auth-error">
                <i class="bi bi-exclamation-circle"></i>
                <div>{{ $errors->first() }}</div>
            </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="form-group">
                    <label for="password">Nova senha</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                               placeholder="Mínimo 8 caracteres"
                               autocomplete="new-password"
                               autofocus>
                    </div>
                    <div class="requirements">Mínimo de 8 caracteres</div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirmar nova senha</label>
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
                    <i class="bi bi-check-circle"></i>
                    Salvar nova senha
                </button>
            </form>

            <div class="auth-footer-link">
                <a href="{{ route('login') }}">
                    <i class="bi bi-arrow-left"></i>
                    Voltar para o login
                </a>
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
</body>
</html>
