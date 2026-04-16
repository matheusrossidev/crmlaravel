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

/* ── Current plan (horizontal, full-width quando assinado) ── */
.current-plan-hero {
    background: linear-gradient(135deg, #0085f3 0%, #0070d1 100%);
    border-radius: 16px;
    padding: 28px 32px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}
.cph-left { flex: 1; min-width: 260px; }
.cph-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: rgba(255,255,255,.78);
    margin-bottom: 6px;
}
.cph-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 26px;
    font-weight: 800;
    margin-bottom: 4px;
    letter-spacing: -0.5px;
}
.cph-meta {
    font-size: 13px;
    color: rgba(255,255,255,.88);
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
    margin-top: 10px;
}
.cph-meta-item { display: flex; align-items: center; gap: 6px; }
.cph-meta-item i { opacity: .85; }
.cph-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
    min-width: 220px;
}
.cph-price {
    display: flex;
    align-items: baseline;
    gap: 4px;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.cph-price .amount { font-size: 30px; font-weight: 800; line-height: 1; }
.cph-price .period { font-size: 13px; color: rgba(255,255,255,.82); }
.cph-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.cph-btn {
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.3);
    color: #fff;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background .15s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-family: inherit;
}
.cph-btn:hover { background: rgba(255,255,255,.28); color: #fff; }
.cph-btn.primary {
    background: #fff;
    color: #0085f3;
    border-color: #fff;
}
.cph-btn.primary:hover { background: #f0f6ff; color: #0070d1; }
.cph-btn.danger:hover { background: rgba(239,68,68,.2); border-color: rgba(255,255,255,.5); }

/* ── Tabs + grid de planos (pro estado NOT subscribed) ── */
.cycle-tabs-wrap { display: flex; justify-content: center; margin-bottom: 28px; }
.cycle-tabs {
    display: inline-flex;
    background: #fff;
    border: 1.5px solid #e5e7eb;
    border-radius: 999px;
    padding: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.cycle-tab {
    padding: 9px 22px;
    border-radius: 999px;
    font-size: 13.5px;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all .18s;
    border: none;
    background: transparent;
    font-family: inherit;
}
.cycle-tab:hover { color: #1a1d23; }
.cycle-tab.active {
    background: #1a1d23;
    color: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,.15);
}

.plans-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 28px;
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
    font-size: 10.5px;
    font-weight: 700;
    padding: 5px 14px;
    border-radius: 999px;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    box-shadow: 0 4px 12px rgba(0,133,243,.3);
}
.plan-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 4px;
    gap: 10px;
}
.plan-n {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 17px;
    font-weight: 700;
    color: #1a1d23;
}
.discount-badge {
    display: none;
    background: #dcfce7;
    color: #15803d;
    font-size: 10.5px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 999px;
    align-items: center;
    gap: 4px;
}
.cycle-yearly .discount-badge[data-has-discount="1"] { display: inline-flex; }

.plan-price-block { margin: 16px 0 6px; }
.plan-price-old {
    font-size: 15px;
    color: #9ca3af;
    text-decoration: line-through;
    margin-right: 8px;
    display: none;
}
.cycle-yearly .plan-price-old[data-has="1"] { display: inline; }
.plan-price-big {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 40px;
    font-weight: 800;
    color: #0a0f1a;
    letter-spacing: -1px;
}
.plan-desc {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.5;
    margin: 16px 0 22px;
    min-height: 44px;
}
.plan-price-suffix {
    font-size: 13px;
    font-weight: 500;
    color: #6b7280;
    margin-left: 2px;
}
.plan-price-note {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
    min-height: 16px;
}

.plan-btn-pill {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    font-family: inherit;
    font-size: 13.5px;
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
.plan-btn-pill:hover { border-color: #0085f3; color: #0085f3; }
.plan-btn-pill:disabled { opacity: .6; cursor: not-allowed; }
.plan-card.featured .plan-btn-pill {
    background: #0085f3;
    color: #fff;
    border-color: #0085f3;
}
.plan-card.featured .plan-btn-pill:hover { background: #0070d1; border-color: #0070d1; }
.plan-btn-pill .spin {
    width: 13px; height: 13px;
    border: 2px solid rgba(255,255,255,.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    display: inline-block;
}
.plan-card:not(.featured) .plan-btn-pill .spin {
    border: 2px solid rgba(0,0,0,.15);
    border-top-color: #1a1d23;
}
@keyframes spin { to { transform: rotate(360deg); } }

.plan-feats {
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px solid #f0f2f7;
}
.plan-feats-title {
    font-size: 11.5px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}
.plan-feats ul { list-style: none; display: flex; flex-direction: column; gap: 9px; margin: 0; padding: 0; }
.plan-feats li {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    font-size: 13px;
    color: #374151;
    line-height: 1.4;
}
.plan-feats li i { color: #10b981; font-size: 12px; margin-top: 3px; flex-shrink: 0; }

@media (max-width: 900px) {
    .plans-grid { grid-template-columns: 1fr; gap: 16px; }
    .plan-card.featured { transform: none; }
    .current-plan-hero { flex-direction: column; align-items: flex-start; }
    .cph-right { align-items: flex-start; width: 100%; }
    .cph-actions { justify-content: flex-start; }
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

    @php
        $isStripe    = ($tenant->billing_provider ?? 'asaas') === 'stripe';
        $isUSD       = strtoupper($tenant->billing_currency ?? 'BRL') === 'USD';
        $isEN        = $isUSD || app()->getLocale() === 'en';
        $bCurrency   = $isUSD ? '$' : __('common.currency');
        $bDecSep     = $isUSD ? '.' : __('common.decimal_sep');
        $bThSep      = $isUSD ? ',' : __('common.thousands_sep');
        $bPerMonth   = $isUSD ? '/mo' : __('settings.billing_per_month');
        $bPerYear    = $isUSD ? '/yr' : '/ano';
        $bPlanPrice  = $isUSD ? ($plan?->price_usd ?? $plan?->price_monthly ?? 0) : ($plan?->price_monthly ?? 0);

        $fmtPrice = fn($v) => $isUSD
            ? '$ ' . number_format((float) $v, 2, '.', ',')
            : 'R$ ' . number_format((float) $v, 2, ',', '.');

        $hasActiveSub = $tenant->hasActiveSubscription();
        $isCycleYearly = ($tenant->billing_cycle ?? 'monthly') === 'yearly';

        $statusLabel = match($tenant->subscription_status ?? $tenant->status) {
            'active'    => [__('settings.billing_active'), 'active'],
            'trial'     => [__('settings.billing_in_trial'), 'trial'],
            'overdue'   => [__('settings.billing_overdue'), 'overdue'],
            'inactive'  => [__('settings.billing_inactive'), 'inactive'],
            'cancelled' => [__('settings.billing_cancelled'), 'cancelled'],
            default     => [__('settings.billing_trial'), 'trial'],
        };
    @endphp

    {{-- Banner overdue --}}
    @if($tenant->subscription_status === 'overdue')
    <div class="overdue-banner" style="margin-bottom:20px;">
        <div class="overdue-text">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            {{ __('settings.billing_overdue_msg') }}
        </div>
        <a href="{{ route('billing.checkout') }}" class="btn-primary-sm">
            {{ __('settings.billing_regularize') }}
        </a>
    </div>
    @endif

    @if($hasActiveSub && $plan)
        {{-- ── Estado: ASSINADO. Card horizontal full-width ── --}}
        <div class="current-plan-hero">
            <div class="cph-left">
                <div class="cph-label">{{ __('settings.billing_current_plan') }}</div>
                <div class="cph-name">{{ $plan->display_name }}</div>

                <div class="cph-meta">
                    <div class="cph-meta-item">
                        <i class="bi bi-circle-fill" style="font-size:7px;color:#10b981;"></i>
                        {{ $statusLabel[0] }}
                    </div>
                    <div class="cph-meta-item">
                        @if($isCycleYearly)
                            <i class="bi bi-calendar-check"></i> {{ __('settings.billing_cycle_yearly') }}
                        @else
                            <i class="bi bi-calendar-month"></i> {{ __('settings.billing_cycle_monthly') }}
                        @endif
                    </div>
                    @if($tenant->subscription_status === 'active')
                        <div class="cph-meta-item">
                            <i class="bi bi-check2-circle"></i>
                            {{ __('settings.billing_active_since', ['date' => $tenant->updated_at->format($isUSD ? 'M d, Y' : 'd/m/Y')]) }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="cph-right">
                <div class="cph-price">
                    @if($bPlanPrice > 0)
                        <span class="amount">{{ $bCurrency }} {{ number_format($bPlanPrice, 2, $bDecSep, $bThSep) }}</span>
                        <span class="period">{{ $isCycleYearly ? $bPerYear : $bPerMonth }}</span>
                    @else
                        <span class="amount" style="font-size:22px;">{{ __('settings.billing_free') }}</span>
                    @endif
                </div>
                <div class="cph-actions">
                    @if($isStripe && $tenant->stripe_subscription_id)
                        <a href="{{ route('billing.checkout', ['plan' => $tenant->plan]) }}" class="cph-btn primary">
                            <i class="bi bi-arrow-left-right"></i> {{ __('settings.billing_change_plan') }}
                        </a>
                        <a href="{{ route('billing.stripe.portal') }}" class="cph-btn">
                            <i class="bi bi-gear"></i> {{ $isUSD ? 'Manage' : __('settings.billing_manage_sub') }}
                        </a>
                        <button type="button" class="cph-btn danger" onclick="confirmCancel()">
                            <i class="bi bi-x-circle"></i> {{ __('settings.billing_cancel_sub') }}
                        </button>
                    @elseif($tenant->asaas_subscription_id)
                        <a href="{{ route('billing.checkout', ['plan' => $tenant->plan]) }}" class="cph-btn primary">
                            <i class="bi bi-arrow-left-right"></i> {{ __('settings.billing_change_plan') }}
                        </a>
                        <button type="button" class="cph-btn danger" onclick="confirmCancel()">
                            <i class="bi bi-x-circle"></i> {{ __('settings.billing_cancel_sub') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @else
        {{-- ── Estado: NÃO ASSINADO. Tabs + grid de planos (estilo checkout) ── --}}

        @php
            $hasAnyYearly = false;
            foreach (($groups ?? []) as $g) {
                if ($g['yearly'] && $g['yearly']->priceFor($currency ?? 'BRL') > 0) { $hasAnyYearly = true; break; }
            }
        @endphp

        @if($hasAnyYearly)
        <div class="cycle-tabs-wrap">
            <div class="cycle-tabs" role="tablist">
                <button type="button" class="cycle-tab active" data-cycle="monthly" onclick="setBillingCycle('monthly')">
                    {{ __('settings.billing_cycle_monthly') }}
                </button>
                <button type="button" class="cycle-tab" data-cycle="yearly" onclick="setBillingCycle('yearly')">
                    {{ __('settings.billing_cycle_yearly') }}
                </button>
            </div>
        </div>
        @endif

        @if(!empty($groups))
        <div class="plans-grid" id="billingPlansGrid">
            @foreach($groups as $g)
                @php
                    $monthly = $g['monthly'];
                    $yearly  = $g['yearly'];
                    $display = ($monthly ?? $yearly)?->display_name ?? $g['display_name'];

                    $langJson    = $isEN ? 'features_en_json' : 'features_json';
                    $featureList = $monthly?->{$langJson}['features_list']
                        ?? $yearly?->{$langJson}['features_list']
                        ?? $monthly?->features_json['features_list']
                        ?? $yearly?->features_json['features_list']
                        ?? [];

                    $priceMonthlyNum = $monthly ? $monthly->priceFor($currency ?? 'BRL') : null;
                    $priceYearlyNum  = $yearly  ? $yearly->priceFor($currency ?? 'BRL')  : null;
                    $hasMonthly      = $monthly && $priceMonthlyNum > 0;
                    $hasYearly       = $yearly  && $priceYearlyNum  > 0;

                    $priceMonthlyLabel = $hasMonthly ? $fmtPrice($priceMonthlyNum) : '—';
                    $priceYearlyLabel  = $hasYearly  ? $fmtPrice($priceYearlyNum)  : '—';

                    $oldPriceYearly = ($hasMonthly && $hasYearly && $priceYearlyNum < $priceMonthlyNum * 12)
                        ? $fmtPrice($priceMonthlyNum * 12)
                        : null;

                    $discountPct = ($hasMonthly && $hasYearly && $yearly)
                        ? $yearly->yearlyDiscountPctVs($monthly, $currency ?? 'BRL')
                        : null;

                    $noteMonthly = __('settings.checkout_billed_monthly');
                    $noteYearly  = $hasYearly
                        ? __('settings.checkout_billed_yearly', ['price' => $fmtPrice($priceYearlyNum / 12)])
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

                    <div class="plan-head">
                        <span class="plan-n">{{ $display }}</span>
                        <span class="discount-badge" data-has-discount="{{ $discountPct !== null ? '1' : '0' }}">
                            @if($discountPct !== null)
                                <i class="bi bi-tag-fill" style="font-size:10px;"></i>
                                {{ __('settings.checkout_save_pct', ['pct' => $discountPct]) }}
                            @endif
                        </span>
                    </div>

                    <div class="plan-price-block">
                        <span class="plan-price-old" data-has="{{ $oldPriceYearly ? '1' : '0' }}">{{ $oldPriceYearly }}</span>
                        <span class="plan-price-big"
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

                    <button type="button" class="plan-btn-pill subscribe-card-btn"
                            data-default-plan="{{ $defaultPlanName }}"
                            onclick="subscribeFromCard(this)">
                        <span class="btn-label">{{ __('settings.checkout_subscribe') }}</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>

                    @if(count($featureList) > 0)
                    <div class="plan-feats">
                        <div class="plan-feats-title">{{ __('settings.checkout_included') }}</div>
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
        @else
            <div class="no-other-plans">
                <i class="bi bi-info-circle" style="font-size:32px;color:#9ca3af;display:block;margin-bottom:12px;"></i>
                <div style="font-weight:600;color:#374151;margin-bottom:4px;">{{ __('settings.billing_no_features') }}</div>
            </div>
        @endif
    @endif

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
                    <td>{{ \Carbon\Carbon::parse($charge['dateCreated'] ?? $charge['created_at'] ?? now())->format($isUSD ? 'M d, Y' : 'd/m/Y') }}</td>
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
const MSG_REDIRECTING = @json(__('settings.checkout_redirecting'));
const MSG_CHECKOUT_ERR = @json(__('settings.checkout_checkout_error'));
const MSG_CONN_ERR     = @json(__('settings.checkout_connection_error'));
const MSG_UNAVAILABLE  = @json(__('settings.checkout_unavailable_cycle'));

// Tabs mensal/anual no estado NOT subscribed — troca preço/nota/botao do card
function setBillingCycle(cycle) {
    document.querySelectorAll('.cycle-tab').forEach(t => t.classList.toggle('active', t.dataset.cycle === cycle));
    const grid = document.getElementById('billingPlansGrid');
    if (grid) grid.classList.toggle('cycle-yearly', cycle === 'yearly');

    document.querySelectorAll('#billingPlansGrid .plan-card').forEach(card => {
        const priceEl  = card.querySelector('.plan-price-big');
        const suffixEl = card.querySelector('.plan-price-suffix');
        const noteEl   = card.querySelector('.plan-price-note');
        const btn      = card.querySelector('.subscribe-card-btn');
        if (!priceEl) return;

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

async function subscribeFromCard(btn) {
    const planName = btn.dataset.resolvedPlan || btn.dataset.defaultPlan;
    if (!planName) { toastr.error(MSG_UNAVAILABLE); return; }
    return goStripeCheckout(planName, btn);
}

// Inicializa estado das tabs (se existirem) — resolve resolvedPlan default
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('billingPlansGrid')) setBillingCycle('monthly');
});

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
