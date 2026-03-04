<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Agência Parceira — Syncro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; margin: 0; min-height: 100vh; background: #f9fafb; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .auth-card { background: #fff; border-radius: 20px; box-shadow: 0 4px 24px rgba(0,0,0,.08); padding: 44px 48px; width: 100%; max-width: 460px; }
        .auth-logo { margin-bottom: 28px; }
        .auth-logo img { height: 34px; }
        .partner-badge { display: inline-flex; align-items: center; gap: 6px; background: #F5F3FF; color: #7C3AED; border: 1px solid #DDD6FE; border-radius: 20px; padding: 5px 14px; font-size: 12.5px; font-weight: 600; margin-bottom: 20px; }
        h1 { font-size: 22px; font-weight: 700; color: #111827; margin: 0 0 8px; }
        .auth-sub { font-size: 14px; color: #6b7280; margin: 0 0 28px; line-height: 1.5; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        input[type=text], input[type=email], input[type=password] {
            width: 100%; padding: 10px 13px; border: 1.5px solid #e5e7eb; border-radius: 9px;
            font-size: 14px; font-family: 'Inter', sans-serif; outline: none; transition: border .15s;
        }
        input:focus { border-color: #7C3AED; box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
        input.is-invalid { border-color: #EF4444; }
        .invalid-feedback { font-size: 12px; color: #EF4444; margin-top: 4px; }
        .code-input { font-family: monospace; font-size: 15px; font-weight: 700; letter-spacing: .06em; background: #FAFAF9; }
        .btn-submit { width: 100%; padding: 12px; background: #7C3AED; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 8px; transition: background .15s; }
        .btn-submit:hover { background: #6D28D9; }
        .divider { border: none; border-top: 1px solid #f3f4f6; margin: 24px 0; }
        .login-link { text-align: center; font-size: 13.5px; color: #6b7280; }
        .login-link a { color: #7C3AED; font-weight: 600; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }
        .benefits { background: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px; }
        .benefits p { font-size: 12.5px; font-weight: 700; color: #5B21B6; margin: 0 0 8px; }
        .benefits ul { margin: 0; padding: 0; list-style: none; }
        .benefits li { font-size: 13px; color: #374151; padding: 2px 0; }
        .benefits li::before { content: '✓ '; color: #7C3AED; font-weight: 700; }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="auth-logo">
        <img src="{{ asset('images/logo.png') }}" alt="Syncro" onerror="this.style.display='none'">
        <span style="font-size:22px;font-weight:800;color:#111827;display:none;" id="logoFallback">Syncro</span>
    </div>

    <div class="partner-badge"><i class="bi bi-building-check"></i> Programa de Parceiros</div>

    <h1>Cadastro de Agência Parceira</h1>
    <p class="auth-sub">Preencha os dados abaixo para ativar sua conta parceira. Você precisará do código fornecido pela Syncro.</p>

    <div class="benefits">
        <p>Benefícios incluídos no plano Parceiro:</p>
        <ul>
            <li>Usuários e leads ilimitados</li>
            <li>Agentes de IA e chatbots incluídos</li>
            <li>Acesso às contas dos seus clientes</li>
            <li>Sem cobrança mensal</li>
        </ul>
    </div>

    @if($errors->any())
    <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 14px;margin-bottom:20px;font-size:13.5px;color:#B91C1C;">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('agency.register.store') }}">
        @csrf

        <div class="form-group">
            <label>Código de Agência Parceira <span style="color:#EF4444;">*</span></label>
            <input type="text" name="agency_code" class="code-input {{ $errors->has('agency_code') ? 'is-invalid' : '' }}"
                   value="{{ old('agency_code', $prefilledCode ?? '') }}"
                   placeholder="AGC-EXEMPLO" maxlength="20"
                   oninput="this.value=this.value.toUpperCase()" required>
            @error('agency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label>Nome da Agência <span style="color:#EF4444;">*</span></label>
            <input type="text" name="tenant_name" class="{{ $errors->has('tenant_name') ? 'is-invalid' : '' }}"
                   value="{{ old('tenant_name') }}" placeholder="Ex: Startup Marketing Ltda" required>
            @error('tenant_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label>Seu Nome <span style="color:#EF4444;">*</span></label>
            <input type="text" name="name" class="{{ $errors->has('name') ? 'is-invalid' : '' }}"
                   value="{{ old('name') }}" placeholder="Ex: João Silva" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label>E-mail <span style="color:#EF4444;">*</span></label>
            <input type="email" name="email" class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                   value="{{ old('email') }}" placeholder="seu@email.com" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label>Senha <span style="color:#EF4444;">*</span></label>
            <input type="password" name="password" placeholder="Mínimo 8 caracteres" required>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label>Confirmar Senha <span style="color:#EF4444;">*</span></label>
            <input type="password" name="password_confirmation" placeholder="Repita a senha" required>
        </div>

        <button type="submit" class="btn-submit">
            <i class="bi bi-building-check me-1"></i> Criar Conta Parceira
        </button>
    </form>

    <hr class="divider">
    <div class="login-link">
        Já tem uma conta? <a href="{{ route('login') }}">Entrar</a>
    </div>
</div>
<script>
    // Show fallback logo text if image fails
    document.querySelector('.auth-logo img').addEventListener('error', function() {
        this.style.display = 'none';
        document.getElementById('logoFallback').style.display = 'inline';
    });
</script>
</body>
</html>
