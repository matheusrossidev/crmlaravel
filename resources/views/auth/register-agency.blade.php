<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Parceiro — Syncro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
        }

        .auth-wrapper {
            display: flex;
            flex-direction: row-reverse;
            width: 100%;
            min-height: 100vh;
        }

        /* ── Painel esquerdo — Formulário ── */
        .auth-left {
            flex: 1;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 64px;
            min-width: 0;
        }

        .auth-brand {
            width: 100%;
            max-width: 380px;
            margin-bottom: 32px;
        }

        .auth-brand img {
            height: 36px;
            object-fit: contain;
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 380px;
        }

        .partner-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eff6ff;
            color: #0085f3;
            border: 1px solid #bfdbfe;
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 12.5px;
            font-weight: 600;
            margin-bottom: 16px;
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
            margin: 0 0 20px;
            line-height: 1.5;
        }

        /* Error block */
        .auth-error {
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

        .auth-error i { font-size: 15px; flex-shrink: 0; margin-top: 1px; }

        /* Benefits */
        .benefits {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .benefits p {
            font-size: 12.5px;
            font-weight: 700;
            color: #0085f3;
            margin: 0 0 8px;
        }

        .benefits ul { margin: 0; padding: 0; list-style: none; display: grid; grid-template-columns: 1fr 1fr; gap: 2px 8px; }
        .benefits li { font-size: 12.5px; color: #374151; padding: 2px 0; }
        .benefits li::before { content: '✓ '; color: #0085f3; font-weight: 700; }

        .form-group { margin-bottom: 14px; }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .input-wrap { position: relative; }

        .input-wrap i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 15px;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 11px 14px 11px 38px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
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
        .form-control.code-input { font-family: monospace; font-size: 16px; font-weight: 700; letter-spacing: .06em; }

        .invalid-feedback {
            font-size: 12px;
            color: #ef4444;
            margin-top: 5px;
        }

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

        .btn-submit:hover { background: #006acf; }
        .btn-submit:active { transform: scale(.98); }

        .auth-footer-link {
            text-align: center;
            font-size: 13.5px;
            color: #6b7280;
            margin-top: 24px;
        }

        .auth-footer-link a {
            color: #0085f3;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-footer-link a:hover { text-decoration: underline; }

        /* ── Painel direito — Imagem ── */
        .auth-right {
            flex: 1;
            position: relative;
            background: url('{{ asset("images/split-screen-login.png") }}') center center / cover no-repeat;
            overflow: hidden;
            min-height: 100vh;
            border-radius: 0 50px 50px 0;
        }

        /* Responsivo */
        @media (max-width: 960px) {
            .auth-right { display: none; }
            .auth-left  { flex: none; width: 100%; padding: 40px 24px; }
        }
    </style>
</head>
<body>
<div class="auth-wrapper">

    {{-- ── Painel esquerdo — Formulário ── --}}
    <div class="auth-left">

        <div class="auth-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Syncro">
        </div>

        <div class="auth-form-wrap">

            <div class="partner-badge">
                <i class="bi bi-building-check"></i> Programa de Parceiros
            </div>

            <h2 class="auth-form-title">Cadastro de Parceiro</h2>
            <p class="auth-form-sub">Preencha os dados abaixo para ativar sua conta parceira. Você precisa do código fornecido pela Syncro.</p>

            <div class="benefits">
                <p>Benefícios incluídos no plano Parceiro:</p>
                <ul>
                    <li>Usuários ilimitados</li>
                    <li>Agentes de IA incluídos</li>
                    <li>Leads e pipelines ilimitados</li>
                    <li>Sem cobrança mensal</li>
                    <li>Acesso às contas dos clientes</li>
                    <li>Tokens de IA ilimitados</li>
                </ul>
            </div>

            @if($errors->any())
            <div class="auth-error">
                <i class="bi bi-exclamation-circle"></i>
                <div>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('agency.register.store') }}">
                @csrf

                <div class="form-group">
                    <label>Código de Parceiro <span style="color:#ef4444;">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-key"></i>
                        <input type="text" name="agency_code"
                               class="form-control code-input {{ $errors->has('agency_code') ? 'is-invalid' : '' }}"
                               value="{{ old('agency_code', $prefilledCode ?? '') }}"
                               placeholder="AGC-EXEMPLO" maxlength="20"
                               oninput="this.value=this.value.toUpperCase()" required>
                    </div>
                    @error('agency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Nome da empresa <span style="color:#ef4444;">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-building"></i>
                        <input type="text" name="tenant_name"
                               class="form-control {{ $errors->has('tenant_name') ? 'is-invalid' : '' }}"
                               value="{{ old('tenant_name') }}"
                               placeholder="Ex: Startup Marketing Ltda" required>
                    </div>
                    @error('tenant_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Seu nome <span style="color:#ef4444;">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-person"></i>
                        <input type="text" name="name"
                               class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                               value="{{ old('name') }}"
                               placeholder="Ex: João Silva" required>
                    </div>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>E-mail <span style="color:#ef4444;">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-envelope"></i>
                        <input type="email" name="email"
                               class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                               value="{{ old('email') }}"
                               placeholder="seu@email.com" required>
                    </div>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Senha <span style="color:#ef4444;">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="password"
                               class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                               placeholder="Mín. 8 caracteres"
                               autocomplete="new-password" required>
                    </div>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Confirmar senha <span style="color:#ef4444;">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" name="password_confirmation"
                               class="form-control"
                               placeholder="Repita a senha"
                               autocomplete="new-password" required>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="bi bi-building-check"></i>
                    Criar Conta Parceira
                </button>
            </form>

            <div class="auth-footer-link">
                Já tem uma conta? <a href="{{ route('login') }}">Entrar</a>
            </div>

        </div>
    </div>

    {{-- ── Painel direito — Imagem ── --}}
    <div class="auth-right"></div>

</div>
</body>
</html>
