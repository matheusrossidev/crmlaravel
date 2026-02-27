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
            background: #f4f6fb;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            background: #fff;
            border-radius: 20px;
            padding: 48px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
            text-align: center;
        }
        .logo { margin-bottom: 32px; }
        .logo img { height: 36px; width: auto; }
        .icon-wrap {
            width: 72px; height: 72px;
            background: #eff6ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #0085f3;
            margin: 0 auto 24px;
        }
        h2 {
            font-size: 22px;
            font-weight: 700;
            color: #1a1d23;
            margin: 0 0 12px;
        }
        .sub {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 32px;
            line-height: 1.6;
        }
        .email-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 32px;
        }
        .info {
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
        .info i { font-size: 16px; flex-shrink: 0; margin-top: 2px; }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13.5px;
            color: #6b7280;
            text-decoration: none;
            transition: color .15s;
        }
        .back-link:hover { color: #0085f3; }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="logo">
        <img src="{{ asset('images/logo.png') }}" alt="Syncro" />
    </div>

    <div class="icon-wrap">
        <i class="bi bi-envelope-check"></i>
    </div>

    <h2>Verifique seu email</h2>
    <p class="sub">
        Enviamos um link de confirmação para o endereço abaixo.
        Clique no link para ativar sua conta e acessar a plataforma.
    </p>

    @if(session('email'))
    <div class="email-box">
        <i class="bi bi-envelope" style="color:#0085f3;margin-right:6px;"></i>
        {{ session('email') }}
    </div>
    @endif

    <div class="info">
        <i class="bi bi-info-circle-fill"></i>
        <div>
            <strong>Não recebeu o email?</strong><br/>
            Verifique sua caixa de spam. O link expira em 48 horas.
            Se necessário, entre em contato com
            <a href="mailto:suporte@syncro.chat" style="color:#0085f3;">suporte@syncro.chat</a>.
        </div>
    </div>

    <a href="{{ route('login') }}" class="back-link">
        <i class="bi bi-arrow-left"></i>
        Voltar para o login
    </a>
</div>

</body>
</html>
