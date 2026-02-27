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
        .logo { margin-bottom: 32px; }
        .logo img { height: 36px; width: auto; }
        .icon-wrap {
            width: 56px; height: 56px;
            background: #eff6ff;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #0085f3;
            margin-bottom: 20px;
        }
        h2 { font-size: 22px; font-weight: 700; color: #1a1d23; margin: 0 0 8px; }
        .sub { font-size: 14px; color: #6b7280; margin: 0 0 28px; line-height: 1.5; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 5px; }
        .input-wrap { position: relative; }
        .input-wrap i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 15px; }
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
        .btn-submit:hover { background: #006fd6; }
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
        .back-link:hover { color: #0085f3; }
        .alert-error {
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
        .alert-error i { font-size: 15px; flex-shrink: 0; margin-top: 1px; }
        .requirements {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 6px;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="logo">
        <img src="{{ asset('images/logo.png') }}" alt="Syncro" />
    </div>

    <div class="icon-wrap">
        <i class="bi bi-shield-lock"></i>
    </div>

    <h2>Criar nova senha</h2>
    <p class="sub">Escolha uma nova senha segura para sua conta.</p>

    @if($errors->any())
    <div class="alert-error">
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

    <a href="{{ route('login') }}" class="back-link">
        <i class="bi bi-arrow-left"></i>
        Voltar para o login
    </a>
</div>

</body>
</html>
