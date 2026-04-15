<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    @include('partials._google-analytics')
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Relatório expirado — Syncro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f6fb;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 24px;
            color: #1a1d23;
            text-align: center;
        }
        .card {
            background: #fff;
            width: 100%; max-width: 440px;
            border-radius: 16px;
            padding: 40px 36px;
            box-shadow: 0 20px 60px rgba(0,0,0,.08);
        }
        .icon {
            width: 72px; height: 72px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: #fef3c7;
            color: #d97706;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px;
        }
        h1 { font-size: 22px; font-weight: 700; color: #0a0f1a; margin-bottom: 10px; }
        p { font-size: 14px; color: #6b7280; line-height: 1.55; }
        .footer { margin-top: 28px; font-size: 11px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon"><i class="bi bi-clock-history"></i></div>
    <h1>Relatório expirado</h1>
    <p>
        Este link de relatório não está mais disponível.<br>
        Entre em contato com quem te enviou para solicitar um novo.
    </p>
    <div class="footer">Syncro CRM</div>
</div>
</body>
</html>
