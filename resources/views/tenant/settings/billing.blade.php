@extends('tenant.layouts.app')

@php
    $title    = __('settings.billing_title');
    $pageIcon = 'gear';
@endphp

@push('styles')
<style>
/* ── Layout ── */
.billing-layout {
    display: flex;
    gap: 24px;
    align-items: flex-start;
}
.billing-sidebar {
    width: 340px;
    flex-shrink: 0;
}
.billing-main {
    flex: 1;
    min-width: 0;
}

@media (max-width: 768px) {
    .billing-layout { flex-direction: column; }
    .billing-sidebar { width: 100%; }
}

/* ── Current plan card ── */
.current-plan-card {
    background: #fff;
    border: 2px solid #0085f3;
    border-radius: 16px;
    overflow: hidden;
}
.current-plan-header {
    background: #0085f3;
    padding: 24px;
    color: #fff;
}
.current-plan-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: rgba(255,255,255,.75);
    margin-bottom: 8px;
}
.current-plan-name {
    font-size: 22px;
    font-weight: 800;
    margin-bottom: 8px;
}
.current-plan-price {
    display: flex;
    align-items: baseline;
    gap: 4px;
}
.current-plan-price .amount {
    font-size: 32px;
    font-weight: 800;
    line-height: 1;
}
.current-plan-price .period {
    font-size: 13px;
    color: rgba(255,255,255,.8);
}
.current-plan-body {
    padding: 20px 24px;
}
.plan-status-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f3f4f6;
}

/* ── Status badges ── */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.status-badge.active   { background: #ecfdf5; color: #059669; }
.status-badge.trial    { background: #eff6ff; color: #2563eb; }
.status-badge.overdue  { background: #fef9c3; color: #b45309; }
.status-badge.inactive,
.status-badge.cancelled { background: #f3f4f6; color: #6b7280; }

/* ── Features list ── */
.plan-features {
    list-style: none;
    padding: 0;
    margin: 0 0 20px;
}
.plan-features li {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #374151;
    padding: 5px 0;
    border-bottom: 1px solid #f9fafb;
}
.plan-features li:last-child { border-bottom: none; }
.feat-check {
    width: 18px;
    height: 18px;
    background: #ecfdf5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 10px;
    color: #059669;
    font-weight: 700;
}
.plan-meta {
    font-size: 12px;
    color: #9ca3af;
    margin-bottom: 20px;
    line-height: 1.6;
}
.plan-meta strong { color: #6b7280; }

/* ── Botões ── */
.btn-subscribe {
    display: block;
    width: 100%;
    padding: 11px;
    background: #0085f3;
    color: #fff;
    border: none;
    border-radius: 100px;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    transition: background .15s;
    margin-bottom: 8px;
}
.btn-subscribe:hover { background: #0070d1; color: #fff; }
.btn-cancel-sub {
    display: block;
    width: 100%;
    padding: 10px;
    background: transparent;
    color: #ef4444;
    border: 1.5px solid #fecaca;
    border-radius: 100px;
    font-size: 13px;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    transition: all .15s;
}
.btn-cancel-sub:hover { background: #fef2f2; }

/* ── Overdue banner ── */
.overdue-banner {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.overdue-text {
    font-size: 13px;
    color: #991b1b;
    font-weight: 500;
}
.btn-primary-sm {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: #0085f3;
    color: #fff;
    border: none;
    border-radius: 100px;
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s;
}
.btn-primary-sm:hover { background: #0070d1; color: #fff; }

/* ── Section header ── */
.section-header { margin-bottom: 20px; }
.section-title { font-size: 15px; font-weight: 700; color: #1a1d23; }
.section-subtitle { font-size: 13px; color: #9ca3af; margin-top: 3px; }

/* ── Other plans ── */
.other-plan-card {
    background: #fff;
    border: 1.5px solid #e8eaf0;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    transition: border-color .15s, box-shadow .15s;
    flex-wrap: wrap;
}
.other-plan-card:hover {
    border-color: #0085f3;
    box-shadow: 0 2px 12px rgba(0,133,243,.08);
}
.other-plan-left { flex: 1; min-width: 0; }
.other-plan-name {
    font-size: 15px;
    font-weight: 700;
    color: #1a1d23;
    margin-bottom: 4px;
}
.other-plan-price {
    font-size: 20px;
    font-weight: 800;
    color: #0085f3;
    line-height: 1;
    margin-bottom: 10px;
}
.other-plan-price span { font-size: 13px; font-weight: 500; color: #9ca3af; }
.other-plan-features {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.other-plan-feat-tag {
    font-size: 12px;
    color: #4b5563;
    background: #f3f4f6;
    border-radius: 20px;
    padding: 2px 10px;
}
.other-plan-right { flex-shrink: 0; }
.btn-upgrade {
    padding: 9px 20px;
    background: #0085f3;
    color: #fff;
    border: none;
    border-radius: 100px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: background .15s;
    display: inline-block;
    white-space: nowrap;
}
.btn-upgrade:hover { background: #0070d1; color: #fff; }
.no-other-plans {
    background: #f9fafb;
    border: 1.5px dashed #e5e7eb;
    border-radius: 14px;
    padding: 40px;
    text-align: center;
    color: #9ca3af;
    font-size: 14px;
}

/* ── Charges table ── */
.charges-card {
    background: #fff;
    border: 1.5px solid #e8eaf0;
    border-radius: 14px;
    overflow: hidden;
    margin-top: 24px;
}
.charges-header {
    padding: 16px 22px;
    border-bottom: 1px solid #f0f2f7;
}
.charges-title {
    font-size: 14px;
    font-weight: 700;
    color: #1a1d23;
}
.charges-subtitle {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 2px;
}
.charges-table {
    width: 100%;
    border-collapse: collapse;
}
.charges-table th {
    font-size: 11px;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .04em;
    padding: 10px 16px;
    text-align: left;
    border-bottom: 1px solid #f3f4f6;
    background: #fafbfc;
}
.charges-table td {
    font-size: 13px;
    color: #374151;
    padding: 12px 16px;
    border-bottom: 1px solid #f9fafb;
}
.charges-table tr:last-child td { border-bottom: none; }
.charges-table tr:hover td { background: #fafbfc; }
.charge-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.charge-status.st-received,
.charge-status.st-confirmed { background: #ecfdf5; color: #059669; }
.charge-status.st-pending   { background: #eff6ff; color: #2563eb; }
.charge-status.st-overdue   { background: #fef9c3; color: #b45309; }
.charge-status.st-refunded  { background: #f3f4f6; color: #6b7280; }
.charge-status.st-deleted,
.charge-status.st-cancelled { background: #fef2f2; color: #ef4444; }
.charge-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11.5px;
    font-weight: 600;
    color: #6b7280;
}
.charges-empty {
    padding: 40px 20px;
    text-align: center;
    color: #9ca3af;
    font-size: 13px;
}
</style>
@endpush

@section('content')
<div class="page-container">

    @include('tenant.settings._tabs')

    <div class="section-header">
        <div>
            <div class="section-title">{{ __('settings.billing_title') }}</div>
            <div class="section-subtitle">{{ __('settings.billing_subtitle') }}</div>
        </div>
    </div>

    <div class="billing-layout">

        {{-- ── Coluna esquerda: plano atual ── --}}
        <div class="billing-sidebar">
            <div class="current-plan-card">
                <div class="current-plan-header">
                    <div class="current-plan-label">{{ __('settings.billing_current_plan') }}</div>
                    <div class="current-plan-name">{{ $plan?->display_name ?? __('settings.billing_current_plan') }}</div>
                    @php
                        $isStripe    = ($tenant->billing_provider ?? 'asaas') === 'stripe';
                        $bCurrency   = $isStripe ? '$' : __('common.currency');
                        $bDecSep     = $isStripe ? '.' : __('common.decimal_sep');
                        $bThSep      = $isStripe ? ',' : __('common.thousands_sep');
                        $bPerMonth   = $isStripe ? '/mo' : __('settings.billing_per_month');
                        $bPlanPrice  = $isStripe ? ($plan?->price_usd ?? $plan?->price_monthly ?? 0) : ($plan?->price_monthly ?? 0);
                    @endphp
                    <div class="current-plan-price">
                        @if($plan && $bPlanPrice > 0)
                            <span class="amount">{{ $bCurrency }} {{ number_format($bPlanPrice, 2, $bDecSep, $bThSep) }}</span>
                            <span class="period">{{ $bPerMonth }}</span>
                        @else
                            <span class="amount" style="font-size:22px;">{{ __('settings.billing_free') }}</span>
                            <span class="period">{{ __('settings.billing_trial') }}</span>
                        @endif
                    </div>
                </div>

                <div class="current-plan-body">
                    {{-- Status --}}
                    <div class="plan-status-row">
                        <span style="font-size:12.5px;color:#6b7280;font-weight:500;">{{ __('settings.billing_col_status') }}</span>
                        @php
                            $statusLabel = match($tenant->subscription_status ?? $tenant->status) {
                                'active'    => [__('settings.billing_active'), 'active'],
                                'trial'     => [__('settings.billing_in_trial'), 'trial'],
                                'overdue'   => [__('settings.billing_overdue'), 'overdue'],
                                'inactive'  => [__('settings.billing_inactive'), 'inactive'],
                                'cancelled' => [__('settings.billing_cancelled'), 'cancelled'],
                                default     => [__('settings.billing_trial'), 'trial'],
                            };
                        @endphp
                        <span class="status-badge {{ $statusLabel[1] }}">
                            <i class="bi bi-circle-fill" style="font-size:7px;"></i>
                            {{ $statusLabel[0] }}
                        </span>
                    </div>

                    {{-- Features list --}}
                    @php
                        $featuresList = $isStripe
                            ? (($plan?->features_en_json['features_list'] ?? null) ?: ($plan?->features_json['features_list'] ?? []))
                            : ($plan?->features_json['features_list'] ?? []);
                    @endphp
                    @if(count($featuresList) > 0)
                    <ul class="plan-features">
                        @foreach($featuresList as $feat)
                        <li>
                            <span class="feat-check">✓</span>
                            {{ $feat }}
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p style="font-size:12.5px;color:#9ca3af;margin-bottom:20px;">
                        {{ __('settings.billing_no_features') }}
                    </p>
                    @endif

                    {{-- Meta info --}}
                    <div class="plan-meta">
                        @if($tenant->status === 'trial' && $tenant->trial_ends_at)
                            {{ __('settings.billing_trial_expires', ['date' => $tenant->trial_ends_at->format($isStripe ? 'M d, Y' : 'd/m/Y')]) }}
                            ({{ $tenant->trial_ends_at->diffForHumans() }})
                        @elseif($tenant->subscription_status === 'active' && ($tenant->asaas_subscription_id || $tenant->stripe_subscription_id))
                            {{ __('settings.billing_active_since', ['date' => $tenant->updated_at->format($isStripe ? 'M d, Y' : 'd/m/Y')]) }}
                        @endif
                        @if($tenant->asaas_subscription_id)
                            <br>ID: <strong>{{ $tenant->asaas_subscription_id }}</strong>
                        @elseif($tenant->stripe_subscription_id)
                            <br>ID: <strong>{{ $tenant->stripe_subscription_id }}</strong>
                        @endif
                    </div>

                    {{-- Ações --}}
                    @if(!$tenant->hasActiveSubscription())
                        <a href="{{ route('billing.checkout') }}" class="btn-subscribe">
                            <i class="bi bi-lightning-charge-fill me-1"></i>
                            {{ __('settings.billing_subscribe') }}
                        </a>
                    @elseif($tenant->subscription_status === 'active' && $isStripe && $tenant->stripe_subscription_id)
                        <a href="{{ route('billing.checkout', ['plan' => $tenant->plan]) }}" class="btn-subscribe" style="margin-bottom:8px;">
                            <i class="bi bi-arrow-left-right me-1"></i>
                            {{ __('settings.billing_change_plan') }}
                        </a>
                        <a href="{{ route('billing.stripe.portal') }}" class="btn-subscribe" style="margin-bottom:8px;background:#f3f4f6;color:#374151;">
                            <i class="bi bi-gear me-1"></i>
                            {{ $isStripe ? 'Manage Subscription' : __('settings.billing_manage_sub') }}
                        </a>
                        <button class="btn-cancel-sub" onclick="confirmCancel()">
                            <i class="bi bi-x-circle me-1"></i>
                            {{ __('settings.billing_cancel_sub') }}
                        </button>
                    @elseif($tenant->subscription_status === 'active' && $tenant->asaas_subscription_id)
                        <a href="{{ route('billing.checkout', ['plan' => $tenant->plan]) }}" class="btn-subscribe" style="margin-bottom:8px;">
                            <i class="bi bi-arrow-left-right me-1"></i>
                            {{ __('settings.billing_change_plan') }}
                        </a>
                        <button class="btn-cancel-sub" onclick="confirmCancel()">
                            <i class="bi bi-x-circle me-1"></i>
                            {{ __('settings.billing_cancel_sub') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Coluna direita: outros planos ── --}}
        <div class="billing-main">

            {{-- Banner overdue --}}
            @if($tenant->subscription_status === 'overdue')
            <div class="overdue-banner">
                <div class="overdue-text">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    {{ __('settings.billing_overdue_msg') }}
                </div>
                <a href="{{ route('billing.checkout') }}" class="btn-primary-sm">
                    {{ __('settings.billing_regularize') }}
                </a>
            </div>
            @endif

            <div class="section-header" style="margin-bottom:16px;">
                <div>
                    <div class="section-title" style="font-size:14px;">{{ __('settings.billing_other_plans') }}</div>
                    <div class="section-subtitle">{{ __('settings.billing_other_sub') }}</div>
                </div>
            </div>

            @php
                $otherPlans = $plans->filter(fn($p) => $p->name !== $tenant->plan && ($isStripe ? ($p->price_usd ?? $p->price_monthly) : $p->price_monthly) > 0);
            @endphp

            @if($otherPlans->isNotEmpty())
                @foreach($otherPlans as $p)
                @php
                    $opPrice    = $isStripe ? ($p->price_usd ?? $p->price_monthly) : $p->price_monthly;
                    $pFeatures  = $isStripe
                        ? (($p->features_en_json['features_list'] ?? null) ?: ($p->features_json['features_list'] ?? []))
                        : ($p->features_json['features_list'] ?? []);
                @endphp
                <div class="other-plan-card">
                    <div class="other-plan-left">
                        <div class="other-plan-name">{{ $p->display_name }}</div>
                        <div class="other-plan-price">
                            {{ $bCurrency }} {{ number_format($opPrice, 2, $bDecSep, $bThSep) }}
                            <span>{{ $bPerMonth }}</span>
                        </div>
                        @if(count($pFeatures) > 0)
                        <div class="other-plan-features">
                            @foreach(array_slice($pFeatures, 0, 4) as $feat)
                                <span class="other-plan-feat-tag">✓ {{ $feat }}</span>
                            @endforeach
                            @if(count($pFeatures) > 4)
                                <span class="other-plan-feat-tag" style="color:#9ca3af;">{{ __('settings.billing_more_features', ['count' => count($pFeatures) - 4]) }}</span>
                            @endif
                        </div>
                        @endif
                    </div>
                    <div class="other-plan-right">
                        <button type="button"
                                onclick="goStripeCheckout('{{ $p->name }}', this)"
                                class="btn-upgrade">
                            {{ $opPrice > $bPlanPrice ? __('settings.billing_upgrade') : __('settings.billing_select') }}
                        </button>
                    </div>
                </div>
                @endforeach
            @else
                <div class="no-other-plans">
                    <i class="bi bi-trophy-fill" style="font-size:32px;color:#fbbf24;display:block;margin-bottom:12px;"></i>
                    <div style="font-weight:600;color:#374151;margin-bottom:4px;">{{ __('settings.billing_best_plan') }}</div>
                    <div>{{ __('settings.billing_best_msg') }}</div>
                </div>
            @endif
        </div>

    </div>

    {{-- ── Histórico de cobranças ── --}}
    <div class="charges-card">
        <div class="charges-header">
            <div class="charges-title">{{ __('settings.billing_charges') }}</div>
            <div class="charges-subtitle">{{ __('settings.billing_charges_sub') }}</div>
        </div>

        @if(isset($charges) && $charges->count() > 0)
        <table class="charges-table">
            <thead>
                <tr>
                    <th>{{ __('settings.billing_col_date') }}</th>
                    <th>{{ __('settings.billing_col_desc') }}</th>
                    <th>{{ __('settings.billing_col_value') }}</th>
                    <th>{{ __('settings.billing_col_type') }}</th>
                    <th>{{ __('settings.billing_col_status') }}</th>
                    <th>{{ __('settings.billing_col_invoice') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($charges as $charge)
                @php
                    $statusMap = [
                        'PENDING'            => [__('settings.billing_pending'), 'st-pending'],
                        'RECEIVED'           => [__('settings.billing_received'), 'st-received'],
                        'CONFIRMED'          => [__('settings.billing_confirmed'), 'st-confirmed'],
                        'OVERDUE'            => [__('settings.billing_charge_overdue'), 'st-overdue'],
                        'REFUNDED'           => [__('settings.billing_refunded'), 'st-refunded'],
                        'RECEIVED_IN_CASH'   => [__('settings.billing_received'), 'st-received'],
                        'REFUND_REQUESTED'   => [__('settings.billing_refund_requested'), 'st-refunded'],
                        'CHARGEBACK_REQUESTED' => [__('settings.billing_chargeback'), 'st-overdue'],
                        'CHARGEBACK_DISPUTE' => [__('settings.billing_dispute'), 'st-overdue'],
                        'DUNNING_REQUESTED'  => [__('settings.billing_dunning_req'), 'st-overdue'],
                        'DUNNING_RECEIVED'   => [__('settings.billing_dunning_recv'), 'st-received'],
                    ];
                    $st = $statusMap[$charge['status'] ?? ''] ?? [$charge['status'] ?? '-', 'st-pending'];
                    $typeLabel = match($charge['billingType'] ?? '') {
                        'PIX'         => __('settings.billing_pix'),
                        'CREDIT_CARD' => __('settings.billing_credit_card'),
                        'BOLETO'      => __('settings.billing_boleto'),
                        default       => $charge['billingType'] ?? '-',
                    };
                    $typeIcon = match($charge['billingType'] ?? '') {
                        'PIX'         => 'bi-qr-code',
                        'CREDIT_CARD' => 'bi-credit-card',
                        'BOLETO'      => 'bi-upc-scan',
                        default       => 'bi-receipt',
                    };
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($charge['dateCreated'] ?? $charge['created_at'] ?? now())->format($isStripe ? 'M d, Y' : 'd/m/Y') }}</td>
                    <td>{{ $charge['description'] ?? '-' }}</td>
                    <td style="font-weight:600;">{{ $bCurrency }} {{ number_format((float)($charge['value'] ?? $charge['amount'] ?? 0), 2, $bDecSep, $bThSep) }}</td>
                    <td>
                        <span class="charge-type-badge">
                            <i class="bi {{ $typeIcon }}"></i> {{ $typeLabel }}
                        </span>
                    </td>
                    <td><span class="charge-status {{ $st[1] }}">{{ $st[0] }}</span></td>
                    <td>
                        @if(!empty($charge['invoiceUrl']))
                            <a href="{{ $charge['invoiceUrl'] }}" target="_blank"
                               style="font-size:12px;color:#0085f3;font-weight:600;text-decoration:none;">
                                <i class="bi bi-box-arrow-up-right"></i> {{ __('settings.billing_view_invoice') }}
                            </a>
                        @else
                            <span style="color:#d1d5db;">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="charges-empty">
            <i class="bi bi-receipt" style="font-size:28px;display:block;margin-bottom:10px;color:#d1d5db;"></i>
            {{ __('settings.billing_no_charges') }}
        </div>
        @endif
    </div>

</div>

<script>
const SLANG = @json(__('settings'));
const IS_STRIPE_BILLING = {{ $isStripe ? 'true' : 'false' }};

// ── Click no card de plano vai DIRETO pro Stripe Checkout (sem passar pela
// pagina /cobranca/checkout). UX = 1 click do dashboard de cobranca pro Stripe.
async function goStripeCheckout(planName, btn) {
    if (!planName) return;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.innerHTML = '<span style="display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;vertical-align:middle;"></span>';

    try {
        const res = await fetch('{{ route('billing.stripe.subscribe') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({ plan_name: planName }),
        });
        const data = await res.json();
        if (data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            toastr.error(data.message || 'Erro ao iniciar checkout.');
            btn.disabled = false;
            btn.textContent = originalText;
        }
    } catch (e) {
        toastr.error('Erro de conexão. Tente novamente.');
        btn.disabled = false;
        btn.textContent = originalText;
    }
}

async function confirmCancel() {
    const msg = SLANG.billing_confirm_cancel
        || (IS_STRIPE_BILLING
            ? 'Are you sure? Your account will be blocked immediately.'
            : 'Tem certeza? Sua conta será bloqueada imediatamente.');
    if (!confirm(msg)) return;

    // Sempre usa POST /cobranca/cancelar — o backend trata stripe E asaas no
    // mesmo endpoint, baseado em billing_provider/subscription_id atual.
    try {
        const res = await fetch('{{ route('billing.cancel') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
            },
            body: JSON.stringify({ confirm: true }),
        });
        const data = await res.json();
        if (data.success) {
            toastr.success(data.message ?? (IS_STRIPE_BILLING ? 'Subscription cancelled.' : SLANG.billing_cancelled_toast));
            // Backend retorna redirect_url pra /conta/suspensa
            setTimeout(() => {
                window.location.href = data.redirect_url || '/conta/suspensa';
            }, 1200);
        } else {
            toastr.error(data.message ?? (IS_STRIPE_BILLING ? 'Error cancelling subscription.' : SLANG.billing_cancel_error));
        }
    } catch (e) {
        toastr.error(IS_STRIPE_BILLING ? 'Connection error. Try again.' : SLANG.billing_conn_error);
    }
}
</script>
@endsection
