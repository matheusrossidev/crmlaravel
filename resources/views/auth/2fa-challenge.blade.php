<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include("partials._google-analytics")
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação em Dois Fatores — Syncro CRM</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f6fb;
        }

        .tfa-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e8eaf0;
            padding: 48px 40px;
            width: 100%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
        }

        .tfa-icon {
            width: 64px; height: 64px;
            border-radius: 16px;
            background: #eff6ff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #0085f3;
            margin-bottom: 20px;
        }

        .tfa-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: #1a1d23;
            margin: 0 0 8px;
        }

        .tfa-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 28px;
            line-height: 1.5;
        }

        .tfa-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e8eaf0;
            border-radius: 12px;
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            letter-spacing: 8px;
            color: #1a1d23;
            outline: none;
            transition: border-color .2s;
            font-family: 'DM Sans', monospace;
        }
        .tfa-input:focus { border-color: #0085f3; }
        .tfa-input::placeholder { letter-spacing: 4px; font-size: 16px; font-weight: 400; color: #9ca3af; }

        .tfa-btn {
            width: 100%;
            padding: 14px;
            background: #0085f3;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 16px;
            transition: background .15s;
        }
        .tfa-btn:hover { background: #0070d1; }
        .tfa-btn:disabled { opacity: .6; cursor: not-allowed; }

        .tfa-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            color: #dc2626;
            margin-bottom: 16px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tfa-toggle {
            margin-top: 20px;
        }
        .tfa-toggle button {
            background: none;
            border: none;
            color: #0085f3;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
        }
        .tfa-toggle button:hover { text-decoration: underline; }

        .tfa-back {
            margin-top: 16px;
        }
        .tfa-back a {
            color: #6b7280;
            font-size: 13px;
            text-decoration: none;
        }
        .tfa-back a:hover { color: #374151; }

        @media (max-width: 480px) {
            .tfa-card { padding: 36px 24px; margin: 16px; }
            .tfa-input { font-size: 20px; letter-spacing: 6px; }
        }
    </style>
</head>
<body>

<div class="tfa-card">
    <div class="tfa-icon">
        <i class="bi bi-shield-lock"></i>
    </div>

    <h1 class="tfa-title">Verificação em Dois Fatores</h1>
    <p class="tfa-subtitle" id="tfaSubtitle">
        Abra o app autenticador no seu celular e digite o código de 6 dígitos.
    </p>

    @if($errors->any())
        <div class="tfa-error">
            <i class="bi bi-exclamation-circle"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('2fa.verify') }}" id="tfaForm">
        @csrf
        <input
            type="text"
            name="code"
            id="tfaCode"
            class="tfa-input"
            placeholder="000000"
            maxlength="9"
            autocomplete="one-time-code"
            inputmode="numeric"
            autofocus
            required
        >
        <button type="submit" class="tfa-btn" id="tfaBtn">
            <i class="bi bi-check-lg"></i> Verificar
        </button>
    </form>

    <div class="tfa-toggle">
        <button onclick="toggleBackupMode()" id="tfaToggle">Usar código de backup</button>
    </div>

    <div class="tfa-back">
        <a href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> Voltar ao login</a>
    </div>
</div>

<script>
let backupMode = false;
function toggleBackupMode() {
    backupMode = !backupMode;
    const input = document.getElementById('tfaCode');
    const sub = document.getElementById('tfaSubtitle');
    const toggle = document.getElementById('tfaToggle');

    if (backupMode) {
        input.maxLength = 9;
        input.inputMode = 'text';
        input.placeholder = 'XXXX-XXXX';
        input.style.letterSpacing = '4px';
        input.style.fontSize = '20px';
        sub.textContent = 'Digite um dos seus códigos de backup (formato XXXX-XXXX).';
        toggle.textContent = 'Usar código do app';
    } else {
        input.maxLength = 6;
        input.inputMode = 'numeric';
        input.placeholder = '000000';
        input.style.letterSpacing = '8px';
        input.style.fontSize = '24px';
        sub.textContent = 'Abra o app autenticador no seu celular e digite o código de 6 dígitos.';
        toggle.textContent = 'Usar código de backup';
    }
    input.value = '';
    input.focus();
}

// Auto-submit when 6 digits are typed (TOTP mode only)
document.getElementById('tfaCode').addEventListener('input', function() {
    if (!backupMode && this.value.length === 6 && /^\d{6}$/.test(this.value)) {
        document.getElementById('tfaBtn').disabled = true;
        document.getElementById('tfaForm').submit();
    }
});
</script>

</body>
</html>
