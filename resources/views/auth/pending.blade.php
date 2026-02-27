<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirme seu email — Syncro</title>
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

        /* ── Painel esquerdo — Conteúdo ── */
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
            text-align: left;
        }

        .icon-wrap {
            width: 64px;
            height: 64px;
            background: #eff6ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #0085f3;
            margin: 0 auto 24px;
        }

        .auth-form-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1d23;
            margin: 0 0 8px;
        }

        .auth-form-sub {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 24px;
            line-height: 1.6;
        }

        .email-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .info-box {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            padding: 16px;
            font-size: 13px;
            color: #1e40af;
            line-height: 1.6;
            margin-bottom: 28px;
            text-align: left;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .info-box i { font-size: 16px; flex-shrink: 0; margin-top: 2px; }

        /* Footer link */
        .auth-footer-link {
            font-size: 13.5px;
            color: #6b7280;
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

    {{-- ── Painel esquerdo — Conteúdo ── --}}
    <div class="auth-left">

        <div class="auth-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Syncro">
        </div>

        <div class="auth-form-wrap">

            <h2 class="auth-form-title">Verifique seu email</h2>
            <p class="auth-form-sub">
                Enviamos um link de confirmação para o endereço abaixo.
                Clique no link para ativar sua conta e acessar a plataforma.
            </p>

            @if(session('email'))
            <div class="email-box">
                <i class="bi bi-envelope" style="color:#0085f3;"></i>
                {{ session('email') }}
            </div>
            @endif

            <div class="info-box">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    <strong>Não recebeu o email?</strong><br>
                    Verifique sua caixa de spam. O link expira em 48 horas.
                    Se necessário, entre em contato com
                    <a href="mailto:suporte@syncro.chat" style="color:#0085f3;">suporte@syncro.chat</a>.
                </div>
            </div>

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
