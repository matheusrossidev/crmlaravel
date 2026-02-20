<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha — Plataforma 360</title>
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
            max-width: 440px;
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
        }

        .auth-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 32px;
        }

        .auth-logo .brand-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #1e40af, #3B82F6);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            font-weight: 700;
            color: #fff;
        }

        .auth-logo .brand-name {
            font-size: 17px;
            font-weight: 700;
            color: #1a1d23;
        }

        .icon-wrap {
            width: 56px; height: 56px;
            background: #eff6ff;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #3B82F6;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 22px;
            font-weight: 700;
            color: #1a1d23;
            margin: 0 0 8px;
        }

        .sub {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 28px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
        }

        .input-wrap {
            position: relative;
        }

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
            border-color: #3B82F6;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(59,130,246,.1);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #3B82F6;
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

        .btn-submit:hover { background: #2563EB; }
        .btn-submit:active { transform: scale(.98); }

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 13.5px;
            color: #6b7280;
            text-decoration: none;
            margin-top: 20px;
            transition: color .15s;
        }

        .back-link:hover { color: #3B82F6; }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 14px 16px;
            font-size: 13px;
            color: #15803d;
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .alert-success i { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
    </style>
</head>
<body>

<div class="auth-card">

    <div class="auth-logo">
        <div class="brand-icon">P</div>
        <span class="brand-name">Plataforma 360</span>
    </div>

    <div class="icon-wrap">
        <i class="bi bi-shield-lock"></i>
    </div>

    <h2>Recuperar senha</h2>
    <p class="sub">Informe seu e-mail cadastrado e enviaremos um link para criar uma nova senha.</p>

    @if(session('status'))
    <div class="alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('status') }}</div>
    </div>
    @endif

    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#dc2626;display:flex;gap:8px;align-items:flex-start;">
        <i class="bi bi-exclamation-circle" style="font-size:15px;flex-shrink:0;margin-top:1px;"></i>
        <div>{{ $errors->first() }}</div>
    </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group">
            <label for="email">E-mail cadastrado</label>
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

        <button type="submit" class="btn-submit">
            <i class="bi bi-send"></i>
            Enviar link de recuperação
        </button>
    </form>

    <a href="{{ route('login') }}" class="back-link">
        <i class="bi bi-arrow-left"></i>
        Voltar para o login
    </a>

</div>

</body>
</html>
