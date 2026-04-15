<!DOCTYPE html>
<html lang="pt-BR">
<head>
    @include("partials._google-analytics")
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso negado — Syncro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9fafb;
            color: #1a1d23;
        }

        .error-container {
            text-align: center;
            padding: 48px 24px;
            max-width: 440px;
        }

        .error-brand {
            margin-bottom: 48px;
        }

        .error-brand img {
            height: 36px;
            object-fit: contain;
        }

        .error-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: #fef2f2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-icon i {
            font-size: 36px;
            color: #ef4444;
        }

        .error-code {
            font-size: 96px;
            font-weight: 700;
            color: #e5e7eb;
            line-height: 1;
            margin: 0 0 8px;
        }

        .error-title {
            font-size: 22px;
            font-weight: 700;
            color: #1a1d23;
            margin: 0 0 10px;
        }

        .error-desc {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
            margin: 0 0 32px;
        }

        .error-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .error-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: #0085f3;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s, transform .1s;
        }

        .error-btn:hover { background: #006acf; }
        .error-btn:active { transform: scale(.98); }

        .error-btn--outline {
            background: #fff;
            color: #374151;
            border: 1.5px solid #e5e7eb;
        }

        .error-btn--outline:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Syncro">
        </div>
        <div class="error-icon">
            <i class="bi bi-shield-lock"></i>
        </div>
        <div class="error-code">403</div>
        <h1 class="error-title">Acesso negado</h1>
        <p class="error-desc">{{ $exception->getMessage() ?: 'Você não tem permissão para acessar esta página. Entre em contato com o administrador da sua conta se acredita que isso é um erro.' }}</p>
        <div class="error-actions">
            <a href="javascript:history.back()" class="error-btn--outline error-btn">
                <i class="bi bi-arrow-left"></i>
                Voltar
            </a>
            <a href="{{ url('/') }}" class="error-btn">
                <i class="bi bi-house"></i>
                Início
            </a>
        </div>
    </div>
</body>
</html>
