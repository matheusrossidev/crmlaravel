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
            background: #f4f6fb;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* ── Painel esquerdo ── */
        .auth-left {
            flex: 1;
            background: linear-gradient(160deg, #006acf 0%, #0085f3 55%, #189fff 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 56px 64px;
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            top: -120px; right: -120px;
            width: 420px; height: 420px;
            border-radius: 50%;
            background: rgba(255,255,255,.05);
            pointer-events: none;
        }

        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -100px; left: -100px;
            width: 340px; height: 340px;
            border-radius: 50%;
            background: rgba(255,255,255,.05);
            pointer-events: none;
        }

        /* Logo */
        .auth-brand {
            margin-bottom: 48px;
            position: relative;
            z-index: 1;
        }

        .auth-brand img {
            height: 38px;
            object-fit: contain;
        }

        /* Headline */
        .auth-left h1 {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            line-height: 1.25;
            margin: 0 0 20px;
            position: relative;
            z-index: 1;
            max-width: 520px;
        }

        /* Subtítulo */
        .auth-left .auth-subtitle {
            font-size: 14px;
            color: rgba(255,255,255,.78);
            line-height: 1.7;
            margin: 0 0 10px;
            max-width: 480px;
            position: relative;
            z-index: 1;
        }

        /* Features */
        .auth-features {
            margin-top: 40px;
            display: flex;
            flex-direction: column;
            gap: 18px;
            position: relative;
            z-index: 1;
        }

        .auth-feature {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .auth-feature-icon {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,.15);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #fff;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .auth-feature-text strong {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 3px;
            line-height: 1.3;
        }

        .auth-feature-text span {
            font-size: 12.5px;
            color: rgba(255,255,255,.7);
            line-height: 1.5;
        }

        /* Tagline final */
        .auth-tagline {
            margin-top: 44px !important;
            font-size: 15px !important;
            font-weight: 700 !important;
            color: rgba(255,255,255,.95) !important;
            letter-spacing: .01em;
            position: relative;
            z-index: 1;
        }

        /* ── Painel direito ── */
        .auth-right {
            width: 480px;
            flex-shrink: 0;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 360px;
        }

        .auth-form-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1d23;
            margin: 0 0 6px;
        }

        .auth-form-sub {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 32px;
        }

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

        .form-control.is-invalid { border-color: #EF4444; }

        .invalid-feedback {
            font-size: 12px;
            color: #EF4444;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: #0085f3;
            cursor: pointer;
        }

        .form-check label {
            font-size: 13px;
            color: #6b7280;
            cursor: pointer;
            margin: 0;
        }

        .forgot-link {
            font-size: 13px;
            color: #0085f3;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-link:hover { text-decoration: underline; }

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
            margin-top: 22px;
            transition: background .15s, transform .1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover { background: #006acf; }
        .btn-submit:active { transform: scale(.98); }

        .auth-footer-link {
            text-align: center;
            font-size: 13.5px;
            color: #6b7280;
            margin-top: 20px;
        }

        .auth-footer-link a {
            color: #0085f3;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-footer-link a:hover { text-decoration: underline; }

        @media (max-width: 960px) {
            .auth-left { display: none; }
            .auth-right { width: 100%; }
        }
    </style>
</head>
<body>
<div class="auth-wrapper">

    {{-- Painel esquerdo --}}
    <div class="auth-left">

        <div class="auth-brand">
            <img src="{{ asset('images/logo-white.png') }}" alt="Syncro">
        </div>

        <h1>Controle Total do Seu Marketing e Vendas em Uma Única Plataforma</h1>

        <p class="auth-subtitle">
            Centralize toda a sua operação comercial e tenha visão completa do seu funil — do primeiro contato até o fechamento e retenção.
        </p>
        <p class="auth-subtitle">
            Com a Syncro, você elimina ferramentas isoladas e passa a gerenciar leads, campanhas, conversas e resultados em um único ambiente inteligente e integrado.
        </p>

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

        <p class="auth-tagline">Mais controle. Mais clareza. Mais resultado.</p>

    </div>

    {{-- Painel direito --}}
    <div class="auth-right">
        <div class="auth-form-wrap">

            <h2 class="auth-form-title">Bem-vindo de volta</h2>
            <p class="auth-form-sub">Entre na sua conta para continuar</p>

            @if($errors->any())
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#dc2626;display:flex;gap:8px;align-items:flex-start;">
                <i class="bi bi-exclamation-circle" style="font-size:15px;flex-shrink:0;margin-top:1px;"></i>
                <div>{{ $errors->first() }}</div>
            </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                <div class="form-group">
                    <label for="email">E-mail</label>
                    <div class="input-wrap">
                        <i class="bi bi-envelope"></i>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                               placeholder="seuemail@empresa.com"
                               autocomplete="email"
                               autofocus>
                    </div>
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

                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <div class="form-check">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Lembrar de mim</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="forgot-link">Esqueceu a senha?</a>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Entrar
                </button>
            </form>

            <div class="auth-footer-link">
                Não tem uma conta? <a href="{{ route('register') }}">Crie gratuitamente</a>
            </div>

        </div>
    </div>

</div>
</body>
</html>
