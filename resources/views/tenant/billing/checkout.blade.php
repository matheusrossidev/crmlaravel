<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Assinar — Syncro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
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
            justify-content: flex-start;
            padding: 48px 64px;
            min-width: 0;
            overflow-y: auto;
        }

        .auth-brand {
            width: 100%;
            max-width: 400px;
            margin-bottom: 32px;
        }

        .auth-brand img {
            height: 36px;
            object-fit: contain;
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 400px;
        }

        .auth-form-title {
            font-size: 26px;
            font-weight: 700;
            color: #1a1d23;
            margin: 0 0 6px;
        }

        .auth-form-sub {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 24px;
        }

        /* Plan card */
        .plan-card {
            background: linear-gradient(135deg, #0085f3 0%, #006fd6 100%);
            border-radius: 12px;
            padding: 18px 22px;
            color: #fff;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .plan-card-name  { font-size: 15px; font-weight: 700; }
        .plan-card-sub   { font-size: 12px; color: #bfdbfe; margin-top: 3px; }
        .plan-card-price .amount { font-size: 24px; font-weight: 800; line-height: 1; }
        .plan-card-price .period { font-size: 11px; color: #bfdbfe; text-align: right; margin-top: 2px; }

        /* Section label */
        .section-label {
            font-size: 11px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin: 0 0 14px;
        }

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
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #1a1d23;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            background: #fafafa;
        }

        .form-control.no-icon { padding-left: 14px; }

        .form-control:focus {
            border-color: #0085f3;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0,133,243,.1);
        }

        .form-control.is-invalid { border-color: #ef4444; }

        .invalid-feedback {
            font-size: 12px;
            color: #ef4444;
            margin-top: 4px;
            display: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        /* Card flags */
        .card-flags {
            display: flex;
            gap: 5px;
            margin-bottom: 14px;
        }
        .card-flag {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 3px 7px;
            font-size: 10.5px;
            font-weight: 700;
            color: #6b7280;
        }

        .divider {
            border: none;
            border-top: 1px solid #f3f4f6;
            margin: 20px 0;
        }

        /* Alerts */
        .alert-box {
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 16px;
            display: none;
            align-items: flex-start;
            gap: 8px;
        }
        .alert-box i { font-size: 15px; flex-shrink: 0; margin-top: 1px; }
        .alert-box.success { background: #ecfdf5; color: #065f46; border: 1px solid #d1fae5; }
        .alert-box.error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* Submit button */
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
            margin-top: 4px;
            transition: background .15s, transform .1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit:hover:not(:disabled) { background: #006acf; }
        .btn-submit:active:not(:disabled) { transform: scale(.98); }
        .btn-submit:disabled { opacity: .65; cursor: not-allowed; }

        .secure-badge {
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            margin-top: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .back-link {
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            margin-top: 20px;
        }
        .back-link a { color: #0085f3; font-weight: 600; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }

        /* ── Painel direito — Imagem ── */
        .auth-right {
            flex: 1;
            position: relative;
            background: url('{{ asset("images/split-screen-login.png") }}') center center / cover no-repeat;
            overflow: hidden;
            min-height: 100vh;
        }

        .auth-right::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to top,
                rgba(0,0,0,.72) 0%,
                rgba(0,0,0,.18) 50%,
                rgba(0,0,0,.05) 100%
            );
        }

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

            <h2 class="auth-form-title">Assinar Syncro</h2>
            <p class="auth-form-sub">Acesso completo à plataforma, cobrado mensalmente.</p>

            {{-- Plano --}}
            <div class="plan-card">
                <div>
                    <div class="plan-card-name">{{ $plan?->display_name ?? 'Plano Syncro' }}</div>
                    <div class="plan-card-sub">Renovação mensal automática</div>
                </div>
                <div class="plan-card-price">
                    <div class="amount">R$ {{ number_format($plan?->price_monthly ?? 0, 2, ',', '.') }}</div>
                    <div class="period">por mês</div>
                </div>
            </div>

            {{-- Alertas --}}
            <div class="alert-box success" id="alertSuccess">
                <i class="bi bi-check-circle-fill"></i>
                <span id="alertSuccessMsg"></span>
            </div>
            <div class="alert-box error" id="alertError">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span id="alertErrorMsg"></span>
            </div>

            {{-- Dados do titular --}}
            <p class="section-label">Dados do titular</p>

            <div class="form-group">
                <label>Nome completo</label>
                <div class="input-wrap">
                    <i class="bi bi-person"></i>
                    <input type="text" class="form-control" id="holderName" placeholder="Como aparece no cartão" autocomplete="cc-name">
                </div>
                <div class="invalid-feedback" id="errHolderName">Informe o nome do titular.</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>CPF / CNPJ</label>
                    <div class="input-wrap">
                        <i class="bi bi-person-badge"></i>
                        <input type="text" class="form-control" id="cpfCnpj" placeholder="000.000.000-00">
                    </div>
                    <div class="invalid-feedback" id="errCpfCnpj">Informe o CPF ou CNPJ.</div>
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <div class="input-wrap">
                        <i class="bi bi-telephone"></i>
                        <input type="tel" class="form-control" id="phone" placeholder="(11) 99999-9999" autocomplete="tel">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>CEP</label>
                    <div class="input-wrap">
                        <i class="bi bi-geo-alt"></i>
                        <input type="text" class="form-control" id="postalCode" placeholder="00000-000">
                    </div>
                </div>
                <div class="form-group">
                    <label>Número</label>
                    <div class="input-wrap">
                        <i class="bi bi-hash"></i>
                        <input type="text" class="form-control" id="addressNumber" placeholder="123">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>E-mail de cobrança</label>
                <div class="input-wrap">
                    <i class="bi bi-envelope"></i>
                    <input type="email" class="form-control" id="billingEmail" value="{{ auth()->user()->email }}" autocomplete="email">
                </div>
                <div class="invalid-feedback" id="errEmail">Informe um e-mail válido.</div>
            </div>

            <hr class="divider">

            {{-- Dados do cartão --}}
            <p class="section-label">Cartão de crédito</p>

            <div class="card-flags">
                <span class="card-flag">VISA</span>
                <span class="card-flag">MASTER</span>
                <span class="card-flag">ELO</span>
                <span class="card-flag">AMEX</span>
                <span class="card-flag">HIPERCARD</span>
            </div>

            <div class="form-group">
                <label>Número do cartão</label>
                <div class="input-wrap">
                    <i class="bi bi-credit-card"></i>
                    <input type="text" class="form-control" id="cardNumber" placeholder="0000 0000 0000 0000" maxlength="19" autocomplete="cc-number">
                </div>
                <div class="invalid-feedback" id="errCardNumber">Informe um número de cartão válido.</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Validade</label>
                    <div class="input-wrap">
                        <i class="bi bi-calendar3"></i>
                        <input type="text" class="form-control" id="cardExpiry" placeholder="MM/AAAA" maxlength="7" autocomplete="cc-exp">
                    </div>
                    <div class="invalid-feedback" id="errCardExpiry">Formato: MM/AAAA.</div>
                </div>
                <div class="form-group">
                    <label>CVV</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="text" class="form-control" id="cardCvv" placeholder="123" maxlength="4" autocomplete="cc-csc">
                    </div>
                    <div class="invalid-feedback" id="errCardCvv">Informe o CVV.</div>
                </div>
            </div>

            <button class="btn-submit" id="btnSubscribe" onclick="doSubscribe()">
                <i class="bi bi-shield-lock-fill"></i>
                Assinar — R$ {{ number_format($plan?->price_monthly ?? 0, 2, ',', '.') }}/mês
            </button>

            <div class="secure-badge">
                <i class="bi bi-lock-fill" style="color:#10b981;"></i>
                Pagamento seguro processado por Asaas
            </div>

            <div class="back-link">
                <a href="{{ route('dashboard') }}">← Voltar ao painel</a>
            </div>

        </div>
    </div>

    {{-- ── Painel direito — Imagem ── --}}
    <div class="auth-right"></div>

</div>

<script>
document.getElementById('cardNumber').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '').substring(0, 16);
    this.value = v.replace(/(.{4})/g, '$1 ').trim();
});

document.getElementById('cardExpiry').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '').substring(0, 6);
    if (v.length > 2) v = v.substring(0,2) + '/' + v.substring(2);
    this.value = v;
});

document.getElementById('cpfCnpj').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '');
    if (v.length <= 11) {
        v = v.replace(/(\d{3})(\d)/, '$1.$2')
             .replace(/(\d{3})(\d)/, '$1.$2')
             .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    } else {
        v = v.substring(0,14)
             .replace(/(\d{2})(\d)/, '$1.$2')
             .replace(/(\d{3})(\d)/, '$1.$2')
             .replace(/(\d{3})(\d)/, '$1/$2')
             .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    }
    this.value = v;
});

function setErr(id, show) {
    document.getElementById(id).style.display = show ? 'block' : 'none';
}

function validate() {
    let ok = true;
    const holderName = document.getElementById('holderName').value.trim();
    setErr('errHolderName', !holderName); if (!holderName) ok = false;

    const cpfCnpj = document.getElementById('cpfCnpj').value.trim();
    setErr('errCpfCnpj', !cpfCnpj); if (!cpfCnpj) ok = false;

    const email = document.getElementById('billingEmail').value.trim();
    const emailOk = email && email.includes('@');
    setErr('errEmail', !emailOk); if (!emailOk) ok = false;

    const cardNum = document.getElementById('cardNumber').value.replace(/\s/g, '');
    setErr('errCardNumber', cardNum.length !== 16); if (cardNum.length !== 16) ok = false;

    const expiry = document.getElementById('cardExpiry').value;
    const expiryOk = /^\d{2}\/\d{4}$/.test(expiry);
    setErr('errCardExpiry', !expiryOk); if (!expiryOk) ok = false;

    const cvv = document.getElementById('cardCvv').value.trim();
    setErr('errCardCvv', cvv.length < 3); if (cvv.length < 3) ok = false;

    return ok;
}

async function doSubscribe() {
    if (!validate()) return;

    const btn = document.getElementById('btnSubscribe');
    btn.disabled = true;
    btn.innerHTML = '<span style="width:16px;height:16px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;display:inline-block;animation:spin .7s linear infinite;"></span> Processando...';

    document.getElementById('alertSuccess').style.display = 'none';
    document.getElementById('alertError').style.display = 'none';

    const payload = {
        holder_name:    document.getElementById('holderName').value.trim(),
        cpf_cnpj:       document.getElementById('cpfCnpj').value.trim(),
        email:          document.getElementById('billingEmail').value.trim(),
        phone:          document.getElementById('phone').value.trim(),
        postal_code:    document.getElementById('postalCode').value.trim(),
        address_number: document.getElementById('addressNumber').value.trim(),
        card_number:    document.getElementById('cardNumber').value.replace(/\s/g, ''),
        card_expiry:    document.getElementById('cardExpiry').value.trim(),
        card_cvv:       document.getElementById('cardCvv').value.trim(),
    };

    try {
        const res  = await fetch('{{ route('billing.subscribe') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById('alertSuccessMsg').textContent = data.message;
            const el = document.getElementById('alertSuccess');
            el.style.display = 'flex';
            if (data.redirect) setTimeout(() => window.location.href = data.redirect, 1500);
        } else {
            document.getElementById('alertErrorMsg').textContent = data.message ?? 'Erro ao processar assinatura.';
            document.getElementById('alertError').style.display = 'flex';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-shield-lock-fill"></i> Assinar — R$ {{ number_format($plan?->price_monthly ?? 0, 2, ',', '.') }}/mês';
        }
    } catch (e) {
        document.getElementById('alertErrorMsg').textContent = 'Erro de conexão. Tente novamente.';
        document.getElementById('alertError').style.display = 'flex';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-shield-lock-fill"></i> Assinar — R$ {{ number_format($plan?->price_monthly ?? 0, 2, ',', '.') }}/mês';
    }
}
</script>
<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</body>
</html>
