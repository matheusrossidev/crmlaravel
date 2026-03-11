<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessão expirada — Syncro</title>
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
            max-width: 480px;
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
            background: #fef3c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-icon i {
            font-size: 36px;
            color: #d97706;
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
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Syncro">
        </div>
        <div class="error-icon">
            <i class="bi bi-clock-history"></i>
        </div>
        <div class="error-code">419</div>
        <h1 class="error-title">Sessão expirada</h1>
        <p class="error-desc">Sua sessão expirou por inatividade. Isso é normal e acontece para proteger sua conta. Atualize a página para continuar.</p>
        <a href="javascript:location.reload()" class="error-btn">
            <i class="bi bi-arrow-clockwise"></i>
            Atualizar página
        </a>
    </div>
</body>
</html>
