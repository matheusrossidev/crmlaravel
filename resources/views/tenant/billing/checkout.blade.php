<!DOCTYPE html>
@php
    $isEN   = ($currency ?? 'BRL') === 'USD' || app()->getLocale() === 'en';
    $locale = $isEN ? 'en' : 'pt-BR';
@endphp
<html lang="{{ $locale }}">
<head>
    @include('partials._google-analytics')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $isEN ? 'Subscribe' : 'Assinar' }} — Syncro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: #f4f6fb;
            min-height: 100vh;
            color: #1a1d23;
            padding: 48px 24px;
        }
        .checkout-shell {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .top-bar {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .back-link {
            color: #6b7280;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color .15s;
        }
        .back-link:hover { color: #1a1d23; }
        .logo-wrap { display: flex; align-items: center; gap: 10px; }
        .logo-wrap img { height: 38px; object-fit: contain; }
        .logout-btn {
            color: #6b7280;
            background: transparent;
            border: none;
            font-size: 13.5px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: inherit;
        }
        .logout-btn:hover { color: #ef4444; }
        .title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 34px;
            color: #0a0f1a;
            text-align: center;
            margin-bottom: 32px;
            letter-spacing: -0.8px;
        }
        .cycle-tabs {
            display: inline-flex;
            background: #fff;
            border: 1.5px solid #e5e7eb;
            border-radius: 999px;
            padding: 4px;
            margin-bottom: 44px;
            box-shadow: 0 2px 8px rgba(0,0,0,.04);
        }
        .cycle-tab {
            padding: 10px 22px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            cursor: pointer;
            transition: all .18s;
            border: none;
            background: transparent;
            font-family: inherit;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cycle-tab:hover { color: #1a1d23; }
        .cycle-tab.active {
            background: #1a1d23;
            color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,.15);
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat({{ min(max(count($groups), 1), 3) }}, 1fr);
            gap: 20px;
            width: 100%;
            align-items: start;
        }
        .plan-card {
            background: #fff;
            border: 1.5px solid #e5e7eb;
            border-radius: 18px;
            padding: 28px 26px;
            display: flex;
            flex-direction: column;
            transition: all .2s;
            position: relative;
        }
        .plan-card:hover {
            border-color: #bfdbfe;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,.06);
        }
        .plan-card.featured {
            background: linear-gradient(180deg, #fff 0%, #f0f6ff 100%);
            border-color: #0085f3;
            box-shadow: 0 20px 50px rgba(0,133,243,.15);
            transform: scale(1.03);
        }
        .featured-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #0085f3;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 5px 14px;
            border-radius: 999px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            box-shadow: 0 4px 12px rgba(0,133,243,.3);
        }

        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
            gap: 10px;
        }
        .plan-name {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #1a1d23;
        }

        /* Badge de desconto — só aparece quando tab anual ativa e o card tem desconto */
        .discount-badge {
            display: none;
            background: #dcfce7;
            color: #15803d;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
            align-items: center;
            gap: 4px;
        }
        .cycle-yearly .discount-badge[data-has-discount="1"] { display: inline-flex; }

        .plan-price-block { margin: 20px 0 6px; }
        .plan-price-old {
            font-size: 16px;
            color: #9ca3af;
            text-decoration: line-through;
            margin-right: 8px;
            display: none;
        }
        .cycle-yearly .plan-price-old[data-has="1"] { display: inline; }

        .plan-price {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 40px;
            font-weight: 800;
            color: #0a0f1a;
            letter-spacing: -1px;
        }
        .plan-price-suffix {
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            margin-left: 2px;
        }
        .plan-price-note {
            font-size: 12.5px;
            color: #6b7280;
            margin-top: 6px;
            min-height: 18px;
        }

        .plan-desc {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.5;
            margin: 16px 0 22px;
            min-height: 44px;
        }

        .plan-btn {
            width: 100%;
            padding: 13px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            border: 1.5px solid #e5e7eb;
            background: #fff;
            color: #1a1d23;
            cursor: pointer;
            transition: all .15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .plan-btn:hover { border-color: #0085f3; color: #0085f3; }
        .plan-btn:disabled { opacity: .6; cursor: not-allowed; }
        .plan-card.featured .plan-btn {
            background: #0085f3;
            color: #fff;
            border-color: #0085f3;
        }
        .plan-card.featured .plan-btn:hover { background: #0070d1; border-color: #0070d1; }
        .plan-btn .spin {
            width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: inline-block;
        }
        .plan-card:not(.featured) .plan-btn .spin {
            border: 2px solid rgba(0,0,0,.15);
            border-top-color: #1a1d23;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .plan-features {
            margin-top: 24px;
            padding-top: 22px;
            border-top: 1px solid #f0f2f7;
        }
        .plan-features-title {
            font-size: 12px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        .plan-features ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
        .plan-features li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 13.5px;
            color: #374151;
            line-height: 1.45;
        }
        .plan-features li i {
            color: #10b981;
            font-size: 13px;
            margin-top: 3px;
            flex-shrink: 0;
        }

        .footer-note {
            margin-top: 36px;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }
        .footer-note a { color: #0085f3; text-decoration: none; font-weight: 600; }
        .footer-note a:hover { text-decoration: underline; }

        .alert-box {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #b91c1c;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13.5px;
            margin-bottom: 20px;
            display: none;
            max-width: 560px;
            width: 100%;
            text-align: center;
        }

        @media (max-width: 900px) {
            body { padding: 24px 14px; }
            .plans-grid { grid-template-columns: 1fr; gap: 18px; }
            .plan-card.featured { transform: none; }
            .title { font-size: 26px; }
            .top-bar { margin-bottom: 24px; }
        }
    </style>
</head>
<body>
<div class="checkout-shell" id="shell">

    <div class="top-bar">
        <a href="{{ route('settings.billing') }}" class="back-link">
            <i class="bi bi-arrow-left"></i> {{ __('settings.checkout_back') }}
        </a>
        <div class="logo-wrap">
            <img src="{{ asset('images/logo.png') }}" alt="Syncro" onerror="this.outerHTML='<span style=&quot;font-family:Plus Jakarta Sans,sans-serif;font-weight:800;font-size:22px;color:#0085f3;&quot;>Syncro</span>'">
        </div>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" class="logout-btn">
                {{ __('settings.checkout_logout') }} <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>

    <h1 class="title">{{ __('settings.checkout_title') }}</h1>

    @php
        $hasAnyYearly = false;
        foreach ($groups as $g) {
            if ($g['yearly'] && $g['yearly']->priceFor($currency) > 0) { $hasAnyYearly = true; break; }
        }

        $symbol = $currency === 'USD' ? '$' : 'R$';
        $fmt    = fn($v) => $currency === 'USD'
            ? $symbol . ' ' . number_format((float) $v, 2, '.', ',')
            : $symbol . ' ' . number_format((float) $v, 2, ',', '.');
    @endphp

    @if($hasAnyYearly)
    <div class="cycle-tabs" role="tablist">
        <button type="button" class="cycle-tab active" data-cycle="monthly" onclick="setCycle('monthly')">
            {{ __('settings.billing_cycle_monthly') }}
        </button>
        <button type="button" class="cycle-tab" data-cycle="yearly" onclick="setCycle('yearly')">
            {{ __('settings.billing_cycle_yearly') }}
        </button>
    </div>
    @endif

    <div id="globalAlert" class="alert-box"></div>

    <div class="plans-grid">
        @foreach($groups as $g)
            @php
                $monthly = $g['monthly'];
                $yearly  = $g['yearly'];
                $display = ($monthly ?? $yearly)?->display_name ?? $g['display_name'];

                $langJson = $isEN ? 'features_en_json' : 'features_json';
                $featureList = $monthly?->{$langJson}['features_list']
                    ?? $yearly?->{$langJson}['features_list']
                    ?? $monthly?->features_json['features_list']
                    ?? $yearly?->features_json['features_list']
                    ?? [];

                $priceMonthlyNum = $monthly ? $monthly->priceFor($currency) : null;
                $priceYearlyNum  = $yearly  ? $yearly->priceFor($currency)  : null;

                $hasMonthly = $monthly && $priceMonthlyNum > 0;
                $hasYearly  = $yearly  && $priceYearlyNum  > 0;

                $priceMonthlyLabel = $hasMonthly ? $fmt($priceMonthlyNum) : '—';
                $priceYearlyLabel  = $hasYearly  ? $fmt($priceYearlyNum)  : '—';

                $oldPriceYearly = ($hasMonthly && $hasYearly && $priceYearlyNum < $priceMonthlyNum * 12)
                    ? $fmt($priceMonthlyNum * 12)
                    : null;

                // Desconto per-card (comparado a 12× mensal)
                $discountPct = ($hasMonthly && $hasYearly && $yearly)
                    ? $yearly->yearlyDiscountPctVs($monthly, $currency)
                    : null;

                $noteMonthly = __('settings.checkout_billed_monthly');
                $noteYearly  = $hasYearly
                    ? __('settings.checkout_billed_yearly', ['price' => $fmt($priceYearlyNum / 12)])
                    : '—';

                $defaultPlanName = $monthly?->name ?? $yearly?->name;
            @endphp

            <div class="plan-card {{ $g['is_recommended'] ? 'featured' : '' }}"
                 data-group="{{ $g['slug'] }}"
                 data-plan-monthly="{{ $monthly?->name }}"
                 data-plan-yearly="{{ $yearly?->name }}">

                @if($g['is_recommended'])
                    <div class="featured-badge">{{ __('settings.checkout_most_popular') }}</div>
                @endif

                <div class="plan-header">
                    <span class="plan-name">{{ $display }}</span>
                    <span class="discount-badge" data-has-discount="{{ $discountPct !== null ? '1' : '0' }}">
                        @if($discountPct !== null)
                            <i class="bi bi-tag-fill" style="font-size:10px;"></i>
                            {{ __('settings.checkout_save_pct', ['pct' => $discountPct]) }}
                        @endif
                    </span>
                </div>

                <div class="plan-price-block">
                    <span class="plan-price-old" data-has="{{ $oldPriceYearly ? '1' : '0' }}">{{ $oldPriceYearly }}</span>
                    <span class="plan-price"
                          data-monthly="{{ $priceMonthlyLabel }}"
                          data-yearly="{{ $priceYearlyLabel }}">{{ $priceMonthlyLabel }}</span>
                    <span class="plan-price-suffix"
                          data-suffix-monthly="{{ __('settings.checkout_per_month') }}"
                          data-suffix-yearly="{{ __('settings.checkout_per_year') }}">{{ __('settings.checkout_per_month') }}</span>
                    <div class="plan-price-note"
                         data-note-monthly="{{ $noteMonthly }}"
                         data-note-yearly="{{ $noteYearly }}">{{ $noteMonthly }}</div>
                </div>

                <p class="plan-desc">{{ __('settings.checkout_plan_desc', ['plan' => $display]) }}</p>

                <button type="button" class="plan-btn subscribe-btn"
                        data-default-plan="{{ $defaultPlanName }}"
                        onclick="subscribe(this)">
                    <span class="btn-label">{{ __('settings.checkout_subscribe') }}</span>
                    <i class="bi bi-arrow-right"></i>
                </button>

                @if(count($featureList) > 0)
                <div class="plan-features">
                    <div class="plan-features-title">{{ __('settings.checkout_included') }}</div>
                    <ul>
                        @foreach($featureList as $feat)
                            <li><i class="bi bi-check-circle-fill"></i>{{ $feat }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="footer-note">
        {{ __('settings.checkout_footer_help') }}
        <a href="https://wa.me/5561998585487?text={{ urlencode($isEN ? 'Hi! I would like to know more about Syncro plans!' : 'Olá! Gostaria de conhecer mais sobre os planos da Syncro!') }}"
           target="_blank" rel="noopener">{{ __('settings.checkout_footer_contact') }}</a>
    </div>
</div>

<script>
    const SUBSCRIBE_URL = @json(route('billing.stripe.subscribe'));
    const CSRF          = document.querySelector('meta[name=csrf-token]').content;
    const MSG = {
        unavailable: @json(__('settings.checkout_unavailable_cycle')),
        checkoutErr: @json(__('settings.checkout_checkout_error')),
        connErr:     @json(__('settings.checkout_connection_error')),
        redirecting: @json(__('settings.checkout_redirecting')),
        subscribe:   @json(__('settings.checkout_subscribe')),
    };

    function setCycle(cycle) {
        document.querySelectorAll('.cycle-tab').forEach(t => t.classList.toggle('active', t.dataset.cycle === cycle));
        document.getElementById('shell').classList.toggle('cycle-yearly', cycle === 'yearly');

        document.querySelectorAll('.plan-card').forEach(card => {
            const priceEl  = card.querySelector('.plan-price');
            const suffixEl = card.querySelector('.plan-price-suffix');
            const noteEl   = card.querySelector('.plan-price-note');
            const btn      = card.querySelector('.subscribe-btn');

            priceEl.textContent  = priceEl.dataset[cycle] ?? priceEl.textContent;
            suffixEl.textContent = suffixEl.dataset['suffix' + cycle.charAt(0).toUpperCase() + cycle.slice(1)] ?? suffixEl.textContent;
            noteEl.textContent   = noteEl.dataset['note' + cycle.charAt(0).toUpperCase() + cycle.slice(1)] ?? noteEl.textContent;

            const planForCycle = cycle === 'yearly'
                ? (card.dataset.planYearly || card.dataset.planMonthly)
                : (card.dataset.planMonthly || card.dataset.planYearly);

            btn.dataset.resolvedPlan = planForCycle || card.dataset.defaultPlan || '';
            btn.disabled = !planForCycle;
        });
    }

    function showAlert(msg) {
        const box = document.getElementById('globalAlert');
        box.textContent = msg;
        box.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async function subscribe(btn) {
        const planName = btn.dataset.resolvedPlan || btn.dataset.defaultPlan;
        if (!planName) { showAlert(MSG.unavailable); return; }

        document.querySelectorAll('.subscribe-btn').forEach(b => b.disabled = true);
        const label = btn.querySelector('.btn-label');
        const origLabel = label.textContent;
        label.innerHTML = '<span class="spin"></span> ' + MSG.redirecting;

        try {
            const res  = await fetch(SUBSCRIBE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ plan_name: planName }),
            });
            const data = await res.json();
            if (data.checkout_url) {
                window.location.href = data.checkout_url;
                return;
            }
            showAlert(data.message || MSG.checkoutErr);
        } catch {
            showAlert(MSG.connErr);
        }

        document.querySelectorAll('.subscribe-btn').forEach(b => b.disabled = false);
        label.textContent = origLabel;
    }

    setCycle('monthly');
</script>
</body>
</html>
