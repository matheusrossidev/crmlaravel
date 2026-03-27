<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Assinar — Syncro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body { font-family: 'DM Sans', sans-serif; min-height: 100vh; display: flex; }

        .auth-wrapper { display: flex; flex-direction: row-reverse; width: 100%; min-height: 100vh; }

        /* ── Painel esquerdo — Wizard ── */
        .auth-left {
            flex: 1; background: #fff; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 48px 64px; min-width: 0; overflow-y: auto;
        }

        .auth-brand { width: 100%; max-width: 420px; margin-bottom: 36px; }
        .auth-brand img { height: 36px; object-fit: contain; }

        .auth-form-wrap { width: 100%; max-width: 420px; }

        /* ── Progress dots (igual registro) ── */
        .step-progress { display: flex; align-items: center; gap: 6px; margin-bottom: 28px; }
        .step-dot-p {
            width: 8px; height: 8px; border-radius: 50%;
            background: #e5e7eb; transition: background .2s, width .2s;
        }
        .step-dot-p.active { background: #007DFF; width: 20px; border-radius: 4px; }
        .step-dot-p.done { background: #007DFF; opacity: .4; }

        /* ── Titles ── */
        .auth-form-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 26px; font-weight: 700; color: #1a1d23; margin: 0 0 6px;
        }
        .auth-form-sub { font-size: 14px; color: #677489; margin: 0 0 24px; }

        /* ── Steps ── */
        .wizard-step { display: none; }
        .wizard-step.active { display: block; }

        /* ── Plan Cards ── */
        .plan-list {
            display: flex; flex-direction: column; gap: 10px;
            max-height: 400px; overflow-y: auto; padding-right: 4px;
        }
        .plan-card {
            border: 1.5px solid #CDDEF6; border-radius: 16px;
            padding: 16px 18px; cursor: pointer; transition: all .15s; position: relative;
        }
        .plan-card:hover { border-color: #007DFF; box-shadow: 0 2px 12px rgba(0,125,255,.08); }
        .plan-card.selected { border-color: #007DFF; background: #f0f6ff; box-shadow: 0 2px 16px rgba(0,125,255,.12); }

        .plan-card-check {
            position: absolute; top: 14px; right: 14px;
            width: 22px; height: 22px; background: #007DFF; border-radius: 50%;
            display: none; align-items: center; justify-content: center; color: #fff; font-size: 12px;
        }
        .plan-card.selected .plan-card-check { display: flex; }

        .plan-card-header { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 10px; padding-right: 28px; }
        .plan-card-name { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 16px; font-weight: 700; color: #1a1d23; }
        .plan-card-price { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 20px; font-weight: 800; color: #007DFF; }
        .plan-card-price span { font-size: 13px; font-weight: 500; color: #97A3B7; }

        .plan-card-features { display: flex; flex-direction: column; gap: 4px; }
        .plan-card-feat { font-size: 12.5px; color: #4b5563; display: flex; align-items: center; gap: 6px; }
        .plan-card-feat i { color: #10b981; font-size: 11px; flex-shrink: 0; }

        /* ── Form ── */
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .input-wrap { position: relative; }
        .input-wrap i.input-icon {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            color: #97A3B7; font-size: 15px; pointer-events: none;
        }
        .form-control {
            width: 100%; padding: 11px 14px 11px 38px;
            border: 1px solid #CDDEF6; border-radius: 100px;
            font-size: 14px; font-family: 'DM Sans', sans-serif; color: #1a1d23;
            outline: none; transition: border-color .15s, box-shadow .15s; background: #fff;
        }
        .form-control:focus { border-color: #007DFF; box-shadow: 0 0 0 3px rgba(0,125,255,.12); }
        .form-control.is-invalid { border-color: #ef4444; }
        .invalid-feedback { font-size: 12px; color: #ef4444; margin-top: 4px; display: none; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        /* ── Credit Card Art ── */
        .cc-art {
            width: 100%; height: 200px; border-radius: 14px; padding: 20px 22px;
            margin: 0 0 20px; position: relative; overflow: hidden;
            color: #fff; display: flex; flex-direction: column; justify-content: space-between;
            background: #007DFF;
            box-shadow: 0 8px 24px rgba(0,125,255,.25);
        }
        .cc-art::before {
            content: ''; position: absolute; top: -60%; right: -30%;
            width: 70%; height: 140%; background: rgba(255,255,255,.06);
            border-radius: 50%; pointer-events: none;
        }
        .cc-art::after {
            content: ''; position: absolute; bottom: -50%; left: -20%;
            width: 60%; height: 120%; background: rgba(255,255,255,.04);
            border-radius: 50%; pointer-events: none;
        }
        .cc-art-top { display: flex; justify-content: space-between; align-items: flex-start; position: relative; z-index: 1; }
        .cc-chip {
            width: 38px; height: 28px;
            background: linear-gradient(135deg, #fbbf24, #d97706);
            border-radius: 5px; position: relative;
        }
        .cc-chip::after {
            content: ''; position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%); width: 22px; height: 16px;
            border: 1.5px solid rgba(255,255,255,.35); border-radius: 3px;
        }
        .cc-price { font-size: 18px; font-weight: 800; color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; position: relative; z-index: 1; }
        .cc-brand { font-size: 16px; font-weight: 800; letter-spacing: 1px; color: rgba(255,255,255,.9); }
        .cc-number {
            font-size: 18px; font-weight: 600; letter-spacing: 2.5px;
            color: #fff; font-family: 'Courier New', monospace; position: relative; z-index: 1;
        }
        .cc-bottom { display: flex; justify-content: space-between; align-items: flex-end; position: relative; z-index: 1; }
        .cc-holder-label, .cc-expiry-label { font-size: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,.6); margin-bottom: 2px; }
        .cc-holder-name { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #fff; }
        .cc-expiry-val { font-size: 13px; font-weight: 600; text-align: right; color: #fff; }

        /* ── Alerts ── */
        .auth-error {
            background: #fef2f2; border: 1px solid #fecaca; border-radius: 14px;
            padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #dc2626;
            display: none; align-items: flex-start; gap: 8px;
        }
        .auth-error i { font-size: 15px; flex-shrink: 0; margin-top: 1px; }
        .auth-success {
            background: #ecfdf5; border: 1px solid #d1fae5; border-radius: 14px;
            padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #065f46;
            display: none; align-items: flex-start; gap: 8px;
        }
        .auth-success i { font-size: 15px; flex-shrink: 0; margin-top: 1px; }

        /* ── Buttons ── */
        .btn-submit {
            width: 100%; padding: 13px 30px;
            background: linear-gradient(148deg, #2C83FB 0%, #1970EA 100%);
            color: #fff; border: none; border-radius: 100px;
            font-size: 14.5px; font-weight: 600; font-family: 'DM Sans', sans-serif;
            cursor: pointer; margin-top: 18px; transition: all .4s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit:hover { background: #0066FF; }
        .btn-submit:active { transform: scale(.98); }
        .btn-submit:disabled { opacity: .55; cursor: not-allowed; }

        .secure-badge {
            text-align: center; font-size: 12px; color: #97A3B7;
            margin-top: 14px; display: flex; align-items: center; justify-content: center; gap: 5px;
        }
        .secure-badge i { color: #10b981; }

        .auth-footer-link { text-align: center; font-size: 13.5px; color: #6b7280; margin-top: 24px; }
        .auth-footer-link a { color: #007DFF; font-weight: 600; text-decoration: none; }
        .auth-footer-link a:hover { text-decoration: underline; }

        /* ── Painel direito — Imagem ── */
        .auth-right {
            flex: 1; position: relative;
            background: url('{{ asset("images/split-screen-login.png") }}') center center / cover no-repeat;
            overflow: hidden; min-height: 100vh; border-radius: 0 50px 50px 0;
        }

        @media (max-width: 960px) {
            .auth-right { display: none; }
            .auth-left  { flex: none; width: 100%; padding: 40px 24px; }
        }
        @media (max-width: 480px) {
            .cc-art { padding: 16px; }
            .cc-number { font-size: 15px; letter-spacing: 2px; }
        }
        @media (max-width: 768px) { .form-control { font-size: 16px !important; } }

        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div class="auth-wrapper">

    {{-- ── Painel esquerdo — Wizard ── --}}
    <div class="auth-left">

        <div class="auth-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Syncro">
        </div>

        <div class="auth-form-wrap">

            {{-- Progress dots --}}
            <div class="step-progress">
                <div class="step-dot-p active" id="dot-1"></div>
                <div class="step-dot-p" id="dot-2"></div>
                <div class="step-dot-p" id="dot-3"></div>
            </div>

            <h2 class="auth-form-title" id="stepTitle">Escolha seu plano</h2>
            <p class="auth-form-sub" id="stepSub">Acesso completo à plataforma. Cancele quando quiser.</p>

            {{-- Alerts --}}
            <div class="auth-success" id="alertSuccess">
                <i class="bi bi-check-circle-fill"></i>
                <span id="alertSuccessMsg"></span>
            </div>
            <div class="auth-error" id="alertError">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span id="alertErrorMsg"></span>
            </div>

            {{-- ══ STEP 1: Plano ══ --}}
            <div class="wizard-step active" data-step="plan">
                <div class="plan-list">
                    @foreach($plans as $p)
                    @php
                        $isSelected = ($plan?->name === $p->name) || ($loop->first && !$plan);
                        $pFeatures  = $p->features_json['features_list'] ?? [];
                    @endphp
                    <div class="plan-card {{ $isSelected ? 'selected' : '' }}"
                         data-plan-name="{{ $p->name }}"
                         data-plan-price="{{ number_format($p->price_monthly, 2, ',', '.') }}"
                         onclick="selectPlan(this)">
                        <div class="plan-card-check"><i class="bi bi-check-lg"></i></div>
                        <div class="plan-card-header">
                            <div class="plan-card-name">{{ $p->display_name }}</div>
                            <div class="plan-card-price">
                                {{ __('common.currency') }} {{ number_format($p->price_monthly, 2, __('common.decimal_sep'), __('common.thousands_sep')) }}<span>{{ __('common.per_month') }}</span>
                            </div>
                        </div>
                        @if(count($pFeatures) > 0)
                        <div class="plan-card-features">
                            @foreach($pFeatures as $feat)
                                <div class="plan-card-feat"><i class="bi bi-check-circle-fill"></i> {{ $feat }}</div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ══ STEP 2: Dados do titular ══ --}}
            <div class="wizard-step" data-step="holder">
                <div class="form-group">
                    <label>Nome completo</label>
                    <div class="input-wrap">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" class="form-control" id="holderName" placeholder="Como aparece no cartão" autocomplete="cc-name">
                    </div>
                    <div class="invalid-feedback" id="errHolderName">Informe o nome do titular.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>CPF / CNPJ</label>
                        <div class="input-wrap">
                            <i class="bi bi-person-badge input-icon"></i>
                            <input type="text" class="form-control" id="cpfCnpj" placeholder="000.000.000-00">
                        </div>
                        <div class="invalid-feedback" id="errCpfCnpj">Informe o CPF ou CNPJ.</div>
                    </div>
                    <div class="form-group">
                        <label>Telefone</label>
                        <div class="input-wrap">
                            <i class="bi bi-telephone input-icon"></i>
                            <input type="tel" class="form-control" id="phone" placeholder="(11) 99999-9999" autocomplete="tel">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>CEP</label>
                        <div class="input-wrap">
                            <i class="bi bi-geo-alt input-icon"></i>
                            <input type="text" class="form-control" id="postalCode" placeholder="00000-000">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Número</label>
                        <div class="input-wrap">
                            <i class="bi bi-hash input-icon"></i>
                            <input type="text" class="form-control" id="addressNumber" placeholder="123">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>E-mail de cobrança</label>
                    <div class="input-wrap">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email" class="form-control" id="billingEmail" value="{{ auth()->user()->email }}" autocomplete="email">
                    </div>
                    <div class="invalid-feedback" id="errEmail">Informe um e-mail válido.</div>
                </div>
            </div>

            {{-- ══ STEP 3: Cartão de crédito ══ --}}
            <div class="wizard-step" data-step="card">
                <div class="cc-art" id="ccArt">
                    <div class="cc-art-top">
                        <div class="cc-price" id="ccPrice">{{ __('common.currency') }} {{ number_format($plan?->price_monthly ?? ($plans->first()?->price_monthly ?? 0), 2, __('common.decimal_sep'), __('common.thousands_sep')) }}</div>
                        <img src="{{ asset('images/logo-white.png') }}" alt="" style="height:18px;opacity:.85;">
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;position:relative;z-index:1;">
                        <div class="cc-number" id="ccNumber">•••• •••• •••• ••••</div>
                        <div class="cc-brand" id="ccBrand"></div>
                    </div>
                    <div class="cc-bottom">
                        <div>
                            <div class="cc-holder-label">TITULAR</div>
                            <div class="cc-holder-name" id="ccHolder">SEU NOME AQUI</div>
                        </div>
                        <div style="text-align:right;">
                            <div class="cc-expiry-label">VALIDADE</div>
                            <div class="cc-expiry-val" id="ccExpiry">MM/AA</div>
                        </div>
                        <div style="text-align:right;">
                            <div class="cc-expiry-label">CVV</div>
                            <div class="cc-expiry-val" id="ccCvv">•••</div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Número do cartão</label>
                    <div class="input-wrap">
                        <i class="bi bi-credit-card input-icon"></i>
                        <input type="text" class="form-control" id="cardNumber" placeholder="0000 0000 0000 0000" maxlength="19" autocomplete="cc-number">
                    </div>
                    <div class="invalid-feedback" id="errCardNumber">Informe um número de cartão válido.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Validade</label>
                        <div class="input-wrap">
                            <i class="bi bi-calendar3 input-icon"></i>
                            <input type="text" class="form-control" id="cardExpiry" placeholder="MM/AAAA" maxlength="7" autocomplete="cc-exp">
                        </div>
                        <div class="invalid-feedback" id="errCardExpiry">Formato: MM/AAAA.</div>
                    </div>
                    <div class="form-group">
                        <label>CVV</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="text" class="form-control" id="cardCvv" placeholder="123" maxlength="4" autocomplete="cc-csc">
                        </div>
                        <div class="invalid-feedback" id="errCardCvv">Informe o CVV.</div>
                    </div>
                </div>
            </div>

            {{-- Botão principal --}}
            <button class="btn-submit" id="btnMain" onclick="handleMain()">
                <span id="btnLabel">Continuar</span>
                <i class="bi bi-arrow-right" id="btnIcon"></i>
            </button>

            <div class="secure-badge">
                <i class="bi bi-shield-check"></i>
                Ambiente seguro com dados criptografados
            </div>

            <div class="auth-footer-link">
                <a href="{{ route('dashboard') }}">← Voltar ao painel</a>
            </div>
        </div>
    </div>

    {{-- ── Painel direito — Imagem ── --}}
    <div class="auth-right"></div>

</div>

<input type="hidden" id="selectedPlan" value="{{ $plan?->name ?? ($plans->first()?->name ?? '') }}">

<script>
const STEPS = ['plan', 'holder', 'card'];
const TITLES = ['Escolha seu plano', 'Dados do titular', 'Pagamento'];
const SUBS = [
    'Acesso completo à plataforma. Cancele quando quiser.',
    'Informações para emissão da nota fiscal.',
    'Seus dados estão protegidos com criptografia.'
];
let currentIdx = 0;
let selectedPrice = '{{ number_format($plan?->price_monthly ?? ($plans->first()?->price_monthly ?? 0), 2, ",", ".") }}';

function updateUI() {
    const step = STEPS[currentIdx];

    // Steps visibility
    document.querySelectorAll('.wizard-step').forEach(el => {
        el.classList.toggle('active', el.dataset.step === step);
    });

    // Titles
    document.getElementById('stepTitle').textContent = TITLES[currentIdx];
    document.getElementById('stepSub').textContent = SUBS[currentIdx];

    // Progress dots
    for (let i = 0; i < STEPS.length; i++) {
        const dot = document.getElementById('dot-' + (i + 1));
        dot.className = 'step-dot-p';
        if (i < currentIdx) dot.classList.add('done');
        else if (i === currentIdx) dot.classList.add('active');
    }

    // Button
    const isLast = currentIdx === STEPS.length - 1;
    const lbl = document.getElementById('btnLabel');
    const ico = document.getElementById('btnIcon');
    if (isLast) {
        lbl.textContent = 'Assinar — ' + CURRENCY + ' ' + selectedPrice + '{{ __('common.per_month') }}';
        ico.className = 'bi bi-shield-lock-fill';
    } else {
        lbl.textContent = 'Continuar';
        ico.className = 'bi bi-arrow-right';
    }

    // Auto focus
    const activeStep = document.querySelector('.wizard-step.active');
    const inp = activeStep?.querySelector('input[type="text"], input[type="email"], input[type="tel"]');
    if (inp) setTimeout(() => inp.focus(), 100);

    if (step === 'card') updateCardArt();
    hideAlerts();
}

function handleMain() {
    const isLast = currentIdx === STEPS.length - 1;
    if (isLast) { doSubscribe(); return; }
    if (!validateStep()) return;
    currentIdx++;
    updateUI();
}

function goBack() {
    if (currentIdx > 0) { currentIdx--; updateUI(); }
}

// ── Plan selector ──
function selectPlan(card) {
    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('selectedPlan').value = card.dataset.planName;
    selectedPrice = card.dataset.planPrice;
    document.getElementById('ccPrice').textContent = CURRENCY + ' ' + selectedPrice;
}

// ── Validation ──
function setErr(id, show, msg) {
    const el = document.getElementById(id);
    el.style.display = show ? 'block' : 'none';
    if (msg) el.textContent = msg;
}

function validaCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    if (cpf.length !== 11) return false;
    if (/^(\d)\1{10}$/.test(cpf)) return false;
    for (let t = 9; t < 11; t++) {
        let d = 0;
        for (let c = 0; c < t; c++) d += parseInt(cpf[c]) * ((t + 1) - c);
        d = ((10 * d) % 11) % 10;
        if (parseInt(cpf[t]) !== d) return false;
    }
    return true;
}

function validaCNPJ(cnpj) {
    cnpj = cnpj.replace(/\D/g, '');
    if (cnpj.length !== 14) return false;
    if (/^(\d)\1{13}$/.test(cnpj)) return false;
    const pesos1 = [5,4,3,2,9,8,7,6,5,4,3,2];
    const pesos2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
    let soma = 0;
    for (let i = 0; i < 12; i++) soma += parseInt(cnpj[i]) * pesos1[i];
    let resto = soma % 11;
    if (parseInt(cnpj[12]) !== (resto < 2 ? 0 : 11 - resto)) return false;
    soma = 0;
    for (let i = 0; i < 13; i++) soma += parseInt(cnpj[i]) * pesos2[i];
    resto = soma % 11;
    if (parseInt(cnpj[13]) !== (resto < 2 ? 0 : 11 - resto)) return false;
    return true;
}

function validateStep() {
    const step = STEPS[currentIdx];

    if (step === 'plan') {
        if (!document.querySelector('.plan-card.selected')) {
            showError('Selecione um plano para continuar.');
            return false;
        }
        return true;
    }

    if (step === 'holder') {
        let ok = true;

        // Nome: mínimo 2 palavras, só letras e espaços
        const name = document.getElementById('holderName').value.trim();
        const nameLetters = /^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/.test(name);
        const nameOk = name && nameLetters && name.split(/\s+/).length >= 2;
        setErr('errHolderName', !nameOk, !name ? 'Informe o nome do titular.' : !nameLetters ? 'Nome deve conter apenas letras.' : 'Informe nome e sobrenome.');
        if (!nameOk) ok = false;

        // CPF/CNPJ: validação matemática
        const raw = document.getElementById('cpfCnpj').value.replace(/\D/g, '');
        let cpfOk = false;
        let cpfMsg = 'Informe o CPF ou CNPJ.';
        if (raw.length === 11) {
            cpfOk = validaCPF(raw);
            if (!cpfOk) cpfMsg = 'CPF inválido.';
        } else if (raw.length === 14) {
            cpfOk = validaCNPJ(raw);
            if (!cpfOk) cpfMsg = 'CNPJ inválido.';
        }
        setErr('errCpfCnpj', !cpfOk, cpfMsg);
        if (!cpfOk) ok = false;

        // Email
        const email = document.getElementById('billingEmail').value.trim();
        const emailOk = email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        setErr('errEmail', !emailOk, 'Informe um e-mail válido.');
        if (!emailOk) ok = false;

        return ok;
    }

    return true;
}

function validateCard() {
    let ok = true;
    const num = document.getElementById('cardNumber').value.replace(/\s/g, '');
    setErr('errCardNumber', num.length !== 16); if (num.length !== 16) ok = false;
    const exp = document.getElementById('cardExpiry').value;
    setErr('errCardExpiry', !/^\d{2}\/\d{4}$/.test(exp)); if (!/^\d{2}\/\d{4}$/.test(exp)) ok = false;
    const cvv = document.getElementById('cardCvv').value.trim();
    setErr('errCardCvv', cvv.length < 3); if (cvv.length < 3) ok = false;
    return ok;
}

// ── Alerts ──
function showError(msg) { document.getElementById('alertErrorMsg').textContent = msg; document.getElementById('alertError').style.display = 'flex'; }
function hideAlerts() { document.getElementById('alertSuccess').style.display = 'none'; document.getElementById('alertError').style.display = 'none'; }

// ── Input masks ──
document.getElementById('cardNumber').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '').substring(0, 16);
    this.value = v.replace(/(.{4})/g, '$1 ').trim();
    updateCardArt();
});
document.getElementById('cardExpiry').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '').substring(0, 6);
    if (v.length > 2) v = v.substring(0, 2) + '/' + v.substring(2);
    this.value = v;
    updateCardArt();
});
document.getElementById('cardCvv').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').substring(0, 4);
    updateCardArt();
});
document.getElementById('cpfCnpj').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '');
    if (v.length <= 11) {
        v = v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    } else {
        v = v.substring(0,14).replace(/(\d{2})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1/$2').replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    }
    this.value = v;
});
document.getElementById('holderName').addEventListener('input', updateCardArt);

// ── Card Art ──
function detectBrand(num) {
    if (/^4/.test(num)) return { name: 'VISA', color: '#1a1f71' };
    if (/^5[1-5]/.test(num)) return { name: 'MASTERCARD', color: '#eb001b' };
    if (/^3[47]/.test(num)) return { name: 'AMEX', color: '#006fcf' };
    if (/^(636368|438935|504175|451416|636297|5067|4576|4011)/.test(num)) return { name: 'ELO', color: '#00a4e0' };
    if (/^(606282|3841)/.test(num)) return { name: 'HIPERCARD', color: '#822124' };
    return { name: '', color: '#007DFF' };
}

function updateCardArt() {
    const raw = document.getElementById('cardNumber').value.replace(/\s/g, '');
    const brand = detectBrand(raw);

    // Brand text
    document.getElementById('ccBrand').textContent = brand.name;
    document.getElementById('ccBrand').style.color = brand.color;

    // Number
    const padded = (raw + '????????????????').substring(0, 16).replace(/\?/g, '\u2022');
    document.getElementById('ccNumber').textContent = padded.match(/.{1,4}/g).join(' ');

    // Holder (from step 2)
    const holder = document.getElementById('holderName').value.trim().toUpperCase() || 'SEU NOME AQUI';
    document.getElementById('ccHolder').textContent = holder;

    // Expiry
    const exp = document.getElementById('cardExpiry').value;
    if (exp.length >= 3) {
        const parts = exp.split('/');
        document.getElementById('ccExpiry').textContent = parts[0] + '/' + (parts[1] || 'AA').substring(2);
    } else {
        document.getElementById('ccExpiry').textContent = 'MM/AA';
    }

    // CVV
    const cvv = document.getElementById('cardCvv').value;
    document.getElementById('ccCvv').textContent = cvv || '\u2022\u2022\u2022';
}

// ── Submit ──
async function doSubscribe() {
    if (!validateCard()) return;
    const btn = document.getElementById('btnMain');
    const lbl = document.getElementById('btnLabel');
    const orig = lbl.textContent;
    btn.disabled = true;
    lbl.innerHTML = '<span style="width:16px;height:16px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;display:inline-block;animation:spin .7s linear infinite;vertical-align:middle;margin-right:6px;"></span>Processando...';
    hideAlerts();

    try {
        const res = await fetch('{{ route('billing.subscribe') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({
                plan_name: document.getElementById('selectedPlan').value,
                holder_name: document.getElementById('holderName').value.trim(),
                cpf_cnpj: document.getElementById('cpfCnpj').value.trim(),
                email: document.getElementById('billingEmail').value.trim(),
                phone: document.getElementById('phone').value.trim(),
                postal_code: document.getElementById('postalCode').value.trim(),
                address_number: document.getElementById('addressNumber').value.trim(),
                card_number: document.getElementById('cardNumber').value.replace(/\s/g, ''),
                card_expiry: document.getElementById('cardExpiry').value.trim(),
                card_cvv: document.getElementById('cardCvv').value.trim(),
            }),
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('alertSuccessMsg').textContent = data.message;
            document.getElementById('alertSuccess').style.display = 'flex';
            if (data.redirect) setTimeout(() => window.location.href = data.redirect, 1500);
        } else {
            showError(data.message ?? 'Erro ao processar assinatura.');
            btn.disabled = false; lbl.textContent = orig;
        }
    } catch (e) {
        showError('Erro de conexão. Tente novamente.');
        btn.disabled = false; lbl.textContent = orig;
    }
}

// ── Keyboard ──
document.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); handleMain(); }
    if (e.key === 'Backspace' && !['INPUT','TEXTAREA'].includes(e.target.tagName) && currentIdx > 0) goBack();
});

updateUI();
</script>
</body>
</html>
