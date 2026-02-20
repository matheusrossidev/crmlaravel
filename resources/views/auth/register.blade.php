<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta — Plataforma 360</title>
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

        .auth-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .auth-left {
            flex: 1;
            background: linear-gradient(135deg, #1e40af 0%, #3B82F6 50%, #6366F1 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            top: -100px; right: -100px;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
        }

        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -80px;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
        }

        .auth-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 60px;
            position: relative;
            z-index: 1;
        }

        .auth-brand .brand-icon {
            width: 44px; height: 44px;
            background: rgba(255,255,255,.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            color: #fff;
        }

        .auth-brand .brand-name {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
        }

        .auth-left h1 {
            font-size: 34px;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
            margin: 0 0 16px;
            position: relative;
            z-index: 1;
        }

        .auth-left p {
            font-size: 16px;
            color: rgba(255,255,255,.75);
            line-height: 1.6;
            margin: 0;
            max-width: 380px;
            position: relative;
            z-index: 1;
        }

        .auth-steps {
            margin-top: 48px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            position: relative;
            z-index: 1;
        }

        .auth-step {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .auth-step .step-num {
            width: 28px; height: 28px;
            background: rgba(255,255,255,.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .auth-step .step-text {
            color: rgba(255,255,255,.85);
            font-size: 14px;
            line-height: 1.5;
        }

        .auth-step .step-text strong {
            color: #fff;
            display: block;
            font-size: 13px;
            margin-bottom: 2px;
        }

        .auth-right {
            width: 520px;
            flex-shrink: 0;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
            overflow-y: auto;
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 400px;
        }

        .auth-form-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1d23;
            margin: 0 0 6px;
        }

        .auth-form-sub {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 28px;
        }

        .form-section {
            font-size: 11px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin: 22px 0 12px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .form-group {
            margin-bottom: 14px;
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
            padding: 10px 14px 10px 38px;
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

        .form-control.is-invalid {
            border-color: #EF4444;
        }

        .invalid-feedback {
            font-size: 12px;
            color: #EF4444;
            margin-top: 4px;
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
            margin-top: 20px;
            transition: background .15s, transform .1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover { background: #2563EB; }
        .btn-submit:active { transform: scale(.98); }

        .terms-text {
            font-size: 12px;
            color: #9ca3af;
            text-align: center;
            margin-top: 14px;
            line-height: 1.5;
        }

        .terms-text a {
            color: #3B82F6;
            text-decoration: none;
        }

        .auth-footer-link {
            text-align: center;
            font-size: 13.5px;
            color: #6b7280;
            margin-top: 20px;
        }

        .auth-footer-link a {
            color: #3B82F6;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-footer-link a:hover { text-decoration: underline; }

        @media (max-width: 900px) {
            .auth-left { display: none; }
            .auth-right { width: 100%; }
        }
    </style>
</head>
<body>
<div class="auth-wrapper">

    {{-- Painel esquerdo --}}
    <div class="auth-left">
        <div class="auth-brand">
            <div class="brand-icon">P</div>
            <span class="brand-name">Plataforma 360</span>
        </div>

        <h1>Comece agora,<br>é gratuito</h1>
        <p>Configure sua conta em minutos e tenha visibilidade total do seu funil de marketing e vendas.</p>

        <div class="auth-steps">
            <div class="auth-step">
                <div class="step-num">1</div>
                <div class="step-text">
                    <strong>Crie sua conta</strong>
                    Preencha os dados da sua empresa e acesso
                </div>
            </div>
            <div class="auth-step">
                <div class="step-num">2</div>
                <div class="step-text">
                    <strong>Configure seu pipeline</strong>
                    Personalize as etapas do seu funil de vendas
                </div>
            </div>
            <div class="auth-step">
                <div class="step-num">3</div>
                <div class="step-text">
                    <strong>Importe seus leads</strong>
                    Via planilha, formulário ou integração com anúncios
                </div>
            </div>
        </div>
    </div>

    {{-- Painel direito --}}
    <div class="auth-right">
        <div class="auth-form-wrap">

            <h2 class="auth-form-title">Criar conta gratuita</h2>
            <p class="auth-form-sub">Sem cartão de crédito — comece hoje mesmo</p>

            @if($errors->any())
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#dc2626;display:flex;gap:8px;align-items:flex-start;">
                <i class="bi bi-exclamation-circle" style="font-size:15px;flex-shrink:0;margin-top:1px;"></i>
                <div>{{ $errors->first() }}</div>
            </div>
            @endif

            <form method="POST" action="{{ route('register.post') }}">
                @csrf

                <div class="form-section">Dados da empresa</div>

                <div class="form-group">
                    <label for="tenant_name">Nome da empresa / workspace</label>
                    <div class="input-wrap">
                        <i class="bi bi-building"></i>
                        <input type="text"
                               id="tenant_name"
                               name="tenant_name"
                               value="{{ old('tenant_name') }}"
                               class="form-control {{ $errors->has('tenant_name') ? 'is-invalid' : '' }}"
                               placeholder="Ex: Agência XYZ"
                               autofocus>
                    </div>
                    @error('tenant_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-section">Seus dados de acesso</div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Seu nome</label>
                        <div class="input-wrap">
                            <i class="bi bi-person"></i>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                   placeholder="João Silva">
                        </div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <div class="input-wrap">
                            <i class="bi bi-envelope"></i>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                   placeholder="joao@empresa.com">
                        </div>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Senha</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock"></i>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                   placeholder="Mín. 8 caracteres">
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirmar senha</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock-fill"></i>
                            <input type="password"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   class="form-control"
                                   placeholder="Repita a senha">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="bi bi-rocket-takeoff"></i>
                    Criar minha conta
                </button>

                <p class="terms-text">
                    Ao criar uma conta você concorda com nossos
                    <a href="#">Termos de Uso</a> e <a href="#">Política de Privacidade</a>.
                </p>
            </form>

            <div class="auth-footer-link">
                Já tem uma conta? <a href="{{ route('login') }}">Entrar agora</a>
            </div>

        </div>
    </div>

</div>
</body>
</html>
