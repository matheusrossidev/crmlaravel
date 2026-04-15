<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    @include('partials._google-analytics')
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Relatório protegido — Syncro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #eff6ff 0%, #f4f6fb 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 24px;
            color: #1a1d23;
        }
        .card {
            background: #fff;
            width: 100%; max-width: 420px;
            border-radius: 16px;
            padding: 40px 36px;
            box-shadow: 0 20px 60px rgba(0,0,0,.08);
            text-align: center;
        }
        .logo { height: 36px; margin-bottom: 24px; }
        .lock-icon {
            width: 64px; height: 64px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: #eff6ff;
            color: #0085f3;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
        }
        h1 { font-size: 22px; font-weight: 700; color: #0a0f1a; margin-bottom: 6px; letter-spacing: -0.3px; }
        p.sub { font-size: 13.5px; color: #6b7280; margin-bottom: 26px; line-height: 1.5; }
        input[type=password] {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        input[type=password]:focus {
            border-color: #0085f3;
            box-shadow: 0 0 0 3px rgba(0,133,243,.12);
        }
        .err {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 14px;
            text-align: left;
        }
        button {
            width: 100%;
            margin-top: 14px;
            padding: 12px;
            background: #0085f3;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 14.5px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: background .15s;
        }
        button:hover { background: #0070d1; }
        .footer {
            margin-top: 24px;
            font-size: 11px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
<div class="card">
    <img src="{{ asset('images/logo.png') }}" alt="Syncro" class="logo" onerror="this.style.display='none'">
    <div class="lock-icon"><i class="bi bi-shield-lock-fill"></i></div>

    <h1>Relatório protegido</h1>
    <p class="sub">
        @if($title)
            <strong>{{ $title }}</strong><br>
        @endif
        Digite a senha pra visualizar o relatório.
    </p>

    @if($errors->any())
        <div class="err"><i class="bi bi-exclamation-circle"></i> {{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('public-report.unlock', $hash) }}">
        @csrf
        <input type="password" name="password" placeholder="Senha" autofocus required maxlength="100">
        <button type="submit"><i class="bi bi-unlock-fill"></i> Desbloquear</button>
    </form>

    <div class="footer">Syncro CRM · Plataforma 360 de Marketing e Vendas</div>
</div>
</body>
</html>
