@extends('tenant.layouts.app')

@php
    $title    = 'Configurações';
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
    background: linear-gradient(135deg, #0085f3 0%, #0066cc 100%);
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
    border-radius: 10px;
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
    border-radius: 10px;
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
    border-radius: 8px;
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
    border-radius: 9px;
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
</style>
@endpush

@section('content')
<div class="page-container">

    <div class="section-header">
        <div>
            <div class="section-title">Cobrança</div>
            <div class="section-subtitle">Gerencie sua assinatura e plano de acesso.</div>
        </div>
    </div>

    <div class="billing-layout">

        {{-- ── Coluna esquerda: plano atual ── --}}
        <div class="billing-sidebar">
            <div class="current-plan-card">
                <div class="current-plan-header">
                    <div class="current-plan-label">Seu plano atual</div>
                    <div class="current-plan-name">{{ $plan?->display_name ?? 'Plano Atual' }}</div>
                    <div class="current-plan-price">
                        @if($plan && $plan->price_monthly > 0)
                            <span class="amount">R$ {{ number_format($plan->price_monthly, 2, ',', '.') }}</span>
                            <span class="period">/mês</span>
                        @else
                            <span class="amount" style="font-size:22px;">Grátis</span>
                            <span class="period">trial</span>
                        @endif
                    </div>
                </div>

                <div class="current-plan-body">
                    {{-- Status --}}
                    <div class="plan-status-row">
                        <span style="font-size:12.5px;color:#6b7280;font-weight:500;">Status</span>
                        @php
                            $statusLabel = match($tenant->subscription_status ?? $tenant->status) {
                                'active'    => ['Ativo', 'active'],
                                'trial'     => ['Em Trial', 'trial'],
                                'overdue'   => ['Pagamento pendente', 'overdue'],
                                'inactive'  => ['Inativo', 'inactive'],
                                'cancelled' => ['Cancelado', 'cancelled'],
                                default     => ['Trial', 'trial'],
                            };
                        @endphp
                        <span class="status-badge {{ $statusLabel[1] }}">
                            <i class="bi bi-circle-fill" style="font-size:7px;"></i>
                            {{ $statusLabel[0] }}
                        </span>
                    </div>

                    {{-- Features list --}}
                    @php $featuresList = $plan?->features_json['features_list'] ?? []; @endphp
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
                        Nenhuma funcionalidade cadastrada para este plano.
                    </p>
                    @endif

                    {{-- Meta info --}}
                    <div class="plan-meta">
                        @if($tenant->status === 'trial' && $tenant->trial_ends_at)
                            Trial expira em <strong>{{ $tenant->trial_ends_at->format('d/m/Y') }}</strong>
                            ({{ $tenant->trial_ends_at->diffForHumans() }})
                        @elseif($tenant->subscription_status === 'active' && $tenant->asaas_subscription_id)
                            Assinatura ativa desde <strong>{{ $tenant->updated_at->format('d/m/Y') }}</strong>
                        @endif
                        @if($tenant->asaas_subscription_id)
                            <br>ID: <strong>{{ $tenant->asaas_subscription_id }}</strong>
                        @endif
                    </div>

                    {{-- Ações --}}
                    @if(!$tenant->hasActiveSubscription())
                        <a href="{{ route('billing.checkout') }}" class="btn-subscribe">
                            <i class="bi bi-lightning-charge-fill me-1"></i>
                            Assinar agora
                        </a>
                    @elseif($tenant->subscription_status === 'active' && $tenant->asaas_subscription_id)
                        <button class="btn-cancel-sub" onclick="confirmCancel()">
                            <i class="bi bi-x-circle me-1"></i>
                            Cancelar assinatura
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
                    Há uma falha no pagamento. Regularize para manter o acesso completo.
                </div>
                <a href="{{ route('billing.checkout') }}" class="btn-primary-sm">
                    Regularizar agora
                </a>
            </div>
            @endif

            <div class="section-header" style="margin-bottom:16px;">
                <div>
                    <div class="section-title" style="font-size:14px;">Outros planos disponíveis</div>
                    <div class="section-subtitle">Faça upgrade ou downgrade a qualquer momento.</div>
                </div>
            </div>

            @php
                $otherPlans = $plans->filter(fn($p) => $p->name !== $tenant->plan && $p->price_monthly > 0);
            @endphp

            @if($otherPlans->isNotEmpty())
                @foreach($otherPlans as $p)
                <div class="other-plan-card">
                    <div class="other-plan-left">
                        <div class="other-plan-name">{{ $p->display_name }}</div>
                        <div class="other-plan-price">
                            R$ {{ number_format($p->price_monthly, 2, ',', '.') }}
                            <span>/mês</span>
                        </div>
                        @php $pFeatures = $p->features_json['features_list'] ?? []; @endphp
                        @if(count($pFeatures) > 0)
                        <div class="other-plan-features">
                            @foreach(array_slice($pFeatures, 0, 4) as $feat)
                                <span class="other-plan-feat-tag">✓ {{ $feat }}</span>
                            @endforeach
                            @if(count($pFeatures) > 4)
                                <span class="other-plan-feat-tag" style="color:#9ca3af;">+{{ count($pFeatures) - 4 }} mais</span>
                            @endif
                        </div>
                        @endif
                    </div>
                    <div class="other-plan-right">
                        <a href="{{ route('billing.checkout') }}" class="btn-upgrade">
                            {{ $p->price_monthly > ($plan?->price_monthly ?? 0) ? 'Fazer upgrade' : 'Selecionar' }}
                        </a>
                    </div>
                </div>
                @endforeach
            @else
                <div class="no-other-plans">
                    <i class="bi bi-trophy-fill" style="font-size:32px;color:#fbbf24;display:block;margin-bottom:12px;"></i>
                    <div style="font-weight:600;color:#374151;margin-bottom:4px;">Você está no melhor plano!</div>
                    <div>Não há outros planos disponíveis no momento.</div>
                </div>
            @endif
        </div>

    </div>

</div>

<script>
async function confirmCancel() {
    if (!confirm('Tem certeza que deseja cancelar sua assinatura? O acesso será encerrado imediatamente.')) return;

    try {
        const res = await fetch('{{ route('billing.cancel') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
            },
        });
        const data = await res.json();
        if (data.success) {
            toastr.success(data.message ?? 'Assinatura cancelada.');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            toastr.error(data.message ?? 'Erro ao cancelar.');
        }
    } catch (e) {
        toastr.error('Erro de conexão. Tente novamente.');
    }
}
</script>
@endsection
