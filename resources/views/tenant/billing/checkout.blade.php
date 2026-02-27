@extends('tenant.layouts.app')

@php
    $title    = 'Assinar';
    $pageIcon = 'credit-card';
@endphp

@push('styles')
<style>
.checkout-wrap {
    max-width: 520px;
    margin: 0 auto;
    padding: 32px 16px 60px;
}

.checkout-header {
    text-align: center;
    margin-bottom: 32px;
}
.checkout-header h1 {
    font-size: 22px;
    font-weight: 800;
    color: #1a1d23;
    margin: 0 0 6px;
}
.checkout-header p {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
}

.checkout-plan-card {
    background: linear-gradient(135deg, #0085f3 0%, #006fd6 100%);
    border-radius: 14px;
    padding: 24px 28px;
    color: #fff;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.checkout-plan-name { font-size: 18px; font-weight: 700; }
.checkout-plan-sub  { font-size: 13px; color: #bfdbfe; margin-top: 4px; }
.checkout-plan-price {
    text-align: right;
}
.checkout-plan-price .amount {
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
}
.checkout-plan-price .period {
    font-size: 12px;
    color: #bfdbfe;
    margin-top: 3px;
}

.checkout-card {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 14px;
    padding: 28px;
    margin-bottom: 16px;
}
.checkout-card h3 {
    font-size: 13px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin: 0 0 20px;
}

.form-field {
    margin-bottom: 16px;
}
.form-field label {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.form-field input {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    color: #1a1d23;
    outline: none;
    transition: border-color .15s;
    box-sizing: border-box;
}
.form-field input:focus {
    border-color: #0085f3;
    box-shadow: 0 0 0 3px rgba(0,133,243,.1);
}
.form-field input.error {
    border-color: #ef4444;
}
.field-error {
    font-size: 11.5px;
    color: #ef4444;
    margin-top: 4px;
    display: none;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.btn-subscribe {
    width: 100%;
    padding: 14px;
    background: #0085f3;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s, transform .1s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-subscribe:hover:not(:disabled) { background: #006fd6; }
.btn-subscribe:active:not(:disabled) { transform: scale(.98); }
.btn-subscribe:disabled { opacity: .65; cursor: not-allowed; }

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

.alert-box {
    border-radius: 10px;
    padding: 14px 16px;
    font-size: 13.5px;
    font-weight: 500;
    margin-bottom: 16px;
    display: none;
}
.alert-box.success { background: #ecfdf5; color: #065f46; border: 1px solid #d1fae5; }
.alert-box.error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

.card-icons {
    display: flex;
    gap: 6px;
    margin-bottom: 18px;
}
.card-icon {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 5px;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
}
</style>
@endpush

@section('content')
<div class="checkout-wrap">

    <div class="checkout-header">
        <h1>Assinar Syncro</h1>
        <p>Acesso completo à plataforma, cobrado mensalmente</p>
    </div>

    {{-- Plano selecionado --}}
    <div class="checkout-plan-card">
        <div>
            <div class="checkout-plan-name">{{ $plan?->display_name ?? 'Plano Syncro' }}</div>
            <div class="checkout-plan-sub">Renovação mensal automática</div>
        </div>
        <div class="checkout-plan-price">
            <div class="amount">R$ {{ number_format($plan?->price_monthly ?? 0, 2, ',', '.') }}</div>
            <div class="period">por mês</div>
        </div>
    </div>

    {{-- Alertas --}}
    <div class="alert-box success" id="alertSuccess"></div>
    <div class="alert-box error"   id="alertError"></div>

    {{-- Dados pessoais --}}
    <div class="checkout-card">
        <h3>Dados do titular</h3>

        <div class="form-field">
            <label>Nome completo</label>
            <input type="text" id="holderName" placeholder="Como aparece no cartão" autocomplete="cc-name">
            <div class="field-error" id="errHolderName">Informe o nome do titular.</div>
        </div>

        <div class="form-row">
            <div class="form-field">
                <label>CPF / CNPJ</label>
                <input type="text" id="cpfCnpj" placeholder="000.000.000-00" autocomplete="off">
                <div class="field-error" id="errCpfCnpj">Informe o CPF ou CNPJ.</div>
            </div>
            <div class="form-field">
                <label>Telefone</label>
                <input type="tel" id="phone" placeholder="(11) 99999-9999" autocomplete="tel">
            </div>
        </div>

        <div class="form-row">
            <div class="form-field">
                <label>CEP</label>
                <input type="text" id="postalCode" placeholder="00000-000">
            </div>
            <div class="form-field">
                <label>Número</label>
                <input type="text" id="addressNumber" placeholder="123">
            </div>
        </div>

        <div class="form-field">
            <label>E-mail de cobrança</label>
            <input type="email" id="billingEmail" value="{{ auth()->user()->email }}" autocomplete="email">
            <div class="field-error" id="errEmail">Informe um e-mail válido.</div>
        </div>
    </div>

    {{-- Dados do cartão --}}
    <div class="checkout-card">
        <h3>Cartão de crédito</h3>

        <div class="card-icons">
            <span class="card-icon">VISA</span>
            <span class="card-icon">MASTER</span>
            <span class="card-icon">ELO</span>
            <span class="card-icon">AMEX</span>
            <span class="card-icon">HIPERCARD</span>
        </div>

        <div class="form-field">
            <label>Número do cartão</label>
            <input type="text" id="cardNumber" placeholder="0000 0000 0000 0000" maxlength="19" autocomplete="cc-number">
            <div class="field-error" id="errCardNumber">Informe um número de cartão válido.</div>
        </div>

        <div class="form-row">
            <div class="form-field">
                <label>Validade</label>
                <input type="text" id="cardExpiry" placeholder="MM/AAAA" maxlength="7" autocomplete="cc-exp">
                <div class="field-error" id="errCardExpiry">Informe no formato MM/AAAA.</div>
            </div>
            <div class="form-field">
                <label>CVV</label>
                <input type="text" id="cardCvv" placeholder="123" maxlength="4" autocomplete="cc-csc">
                <div class="field-error" id="errCardCvv">Informe o CVV.</div>
            </div>
        </div>
    </div>

    <button class="btn-subscribe" id="btnSubscribe" onclick="doSubscribe()">
        <i class="bi bi-shield-lock-fill"></i>
        Assinar — R$ {{ number_format($plan?->price_monthly ?? 0, 2, ',', '.') }}/mês
    </button>

    <div class="secure-badge">
        <i class="bi bi-lock-fill" style="color:#10b981;"></i>
        Pagamento seguro processado por Asaas
    </div>

</div>

<script>
// Máscara simples para número do cartão
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

function showErr(id, show) {
    document.getElementById(id).style.display = show ? 'block' : 'none';
    const inputId = id.replace('err', '').charAt(0).toLowerCase() + id.replace('err', '').slice(1);
}

function validate() {
    let ok = true;

    const holderName = document.getElementById('holderName').value.trim();
    document.getElementById('errHolderName').style.display = holderName ? 'none' : 'block';
    if (!holderName) ok = false;

    const cpfCnpj = document.getElementById('cpfCnpj').value.trim();
    document.getElementById('errCpfCnpj').style.display = cpfCnpj ? 'none' : 'block';
    if (!cpfCnpj) ok = false;

    const email = document.getElementById('billingEmail').value.trim();
    document.getElementById('errEmail').style.display = (email && email.includes('@')) ? 'none' : 'block';
    if (!email || !email.includes('@')) ok = false;

    const cardNum = document.getElementById('cardNumber').value.replace(/\s/g, '');
    document.getElementById('errCardNumber').style.display = (cardNum.length === 16) ? 'none' : 'block';
    if (cardNum.length !== 16) ok = false;

    const expiry = document.getElementById('cardExpiry').value;
    const expiryOk = /^\d{2}\/\d{4}$/.test(expiry);
    document.getElementById('errCardExpiry').style.display = expiryOk ? 'none' : 'block';
    if (!expiryOk) ok = false;

    const cvv = document.getElementById('cardCvv').value.trim();
    document.getElementById('errCardCvv').style.display = (cvv.length >= 3) ? 'none' : 'block';
    if (cvv.length < 3) ok = false;

    return ok;
}

async function doSubscribe() {
    if (!validate()) return;

    const btn = document.getElementById('btnSubscribe');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processando...';

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
        const res = await fetch('{{ route('billing.subscribe') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json();

        if (data.success) {
            const el = document.getElementById('alertSuccess');
            el.textContent = data.message;
            el.style.display = 'block';
            if (data.redirect) {
                setTimeout(() => window.location.href = data.redirect, 1500);
            }
        } else {
            const el = document.getElementById('alertError');
            el.textContent = data.message ?? 'Erro ao processar assinatura.';
            el.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-shield-lock-fill"></i> Assinar — R$ {{ number_format($plan?->price_monthly ?? 0, 2, ',', '.') }}/mês';
        }
    } catch (e) {
        const el = document.getElementById('alertError');
        el.textContent = 'Erro de conexão. Tente novamente.';
        el.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-shield-lock-fill"></i> Assinar — R$ {{ number_format($plan?->price_monthly ?? 0, 2, ',', '.') }}/mês';
    }
}
</script>
@endsection
