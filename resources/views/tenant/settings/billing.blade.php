@extends('tenant.layouts.app')

@php
    $title    = 'Configurações';
    $pageIcon = 'gear';
@endphp

@push('styles')
<style>
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}
.section-title    { font-size: 15px; font-weight: 700; color: #1a1d23; }
.section-subtitle { font-size: 13px; color: #9ca3af; margin-top: 3px; }

/* ─── Status badges ─── */
.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
}
.status-badge.active   { background: #ecfdf5; color: #059669; }
.status-badge.trial    { background: #eff6ff; color: #2563eb; }
.status-badge.overdue  { background: #fef9c3; color: #b45309; }
.status-badge.inactive,
.status-badge.cancelled { background: #f3f4f6; color: #6b7280; }

/* ─── Current plan card ─── */
.billing-plan-card {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 14px;
    padding: 28px;
    margin-bottom: 20px;
}
.billing-plan-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}
.billing-plan-info h2 {
    font-size: 18px;
    font-weight: 700;
    color: #1a1d23;
    margin: 0 0 6px;
}
.billing-plan-info p {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
}
.billing-plan-price {
    text-align: right;
    flex-shrink: 0;
}
.billing-plan-price .amount {
    font-size: 26px;
    font-weight: 800;
    color: #1a1d23;
    line-height: 1;
}
.billing-plan-price .period {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 4px;
}
.billing-plan-meta {
    display: flex;
    gap: 24px;
    border-top: 1px solid #f3f4f6;
    padding-top: 16px;
    flex-wrap: wrap;
}
.billing-meta-item {
    font-size: 13px;
    color: #6b7280;
}
.billing-meta-item strong {
    color: #374151;
    font-weight: 600;
}

/* ─── Overdue banner ─── */
.overdue-banner {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.overdue-banner-text {
    font-size: 13.5px;
    color: #991b1b;
    font-weight: 500;
}

/* ─── Plans grid ─── */
.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.plan-card {
    background: #fff;
    border: 2px solid #e8eaf0;
    border-radius: 14px;
    padding: 24px;
    position: relative;
    transition: border-color .15s, box-shadow .15s;
}
.plan-card:hover { border-color: #0085f3; box-shadow: 0 4px 16px rgba(0,133,243,.1); }
.plan-card.current { border-color: #0085f3; background: #eff6ff; }
.plan-current-tag {
    position: absolute;
    top: -10px;
    left: 16px;
    background: #0085f3;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.plan-card h3 {
    font-size: 15px;
    font-weight: 700;
    color: #1a1d23;
    margin: 0 0 6px;
}
.plan-card .price {
    font-size: 22px;
    font-weight: 800;
    color: #0085f3;
    margin-bottom: 14px;
}
.plan-card .price span { font-size: 13px; font-weight: 500; color: #9ca3af; }
.plan-card ul {
    list-style: none;
    padding: 0;
    margin: 0 0 16px;
}
.plan-card ul li {
    font-size: 12.5px;
    color: #4b5563;
    padding: 3px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}
.plan-card ul li::before {
    content: '✓';
    color: #10b981;
    font-weight: 700;
    font-size: 11px;
}

/* ─── Cancel section ─── */
.cancel-section {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 14px;
    padding: 24px 28px;
}
.cancel-section h3 {
    font-size: 14px;
    font-weight: 700;
    color: #374151;
    margin: 0 0 8px;
}
.cancel-section p {
    font-size: 13px;
    color: #9ca3af;
    margin: 0 0 16px;
    line-height: 1.6;
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

    {{-- Banner overdue --}}
    @if($tenant->subscription_status === 'overdue')
    <div class="overdue-banner">
        <div class="overdue-banner-text">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Há uma falha no pagamento da sua assinatura. Regularize para manter o acesso completo.
        </div>
        <a href="{{ route('billing.checkout') }}" class="btn-primary-sm">
            Regularizar agora
        </a>
    </div>
    @endif

    {{-- Plano atual --}}
    <div class="billing-plan-card">
        <div class="billing-plan-top">
            <div class="billing-plan-info">
                <h2>{{ $plan?->display_name ?? 'Plano atual' }}</h2>
                <div style="margin-top:8px;">
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
            </div>
            <div class="billing-plan-price">
                @if($plan && $plan->price_monthly > 0)
                    <div class="amount">R$ {{ number_format($plan->price_monthly, 2, ',', '.') }}</div>
                    <div class="period">por mês</div>
                @else
                    <div class="amount" style="color:#10b981;">Grátis</div>
                    <div class="period">trial</div>
                @endif
            </div>
        </div>
        <div class="billing-plan-meta">
            @if($tenant->status === 'trial' && $tenant->trial_ends_at)
                <div class="billing-meta-item">
                    Trial expira em: <strong>{{ $tenant->trial_ends_at->format('d/m/Y') }}</strong>
                    ({{ $tenant->trial_ends_at->diffForHumans() }})
                </div>
            @elseif($tenant->subscription_status === 'active' && $tenant->asaas_subscription_id)
                <div class="billing-meta-item">
                    Assinatura ativa desde <strong>{{ $tenant->updated_at->format('d/m/Y') }}</strong>
                </div>
            @endif
            @if($tenant->asaas_subscription_id)
                <div class="billing-meta-item">
                    ID Asaas: <strong>{{ $tenant->asaas_subscription_id }}</strong>
                </div>
            @endif
        </div>
    </div>

    {{-- Upsell / planos disponíveis --}}
    @if($plans->isNotEmpty())
    <div class="section-header" style="margin-top:32px;">
        <div>
            <div class="section-title">Planos disponíveis</div>
            <div class="section-subtitle">Compare e faça upgrade quando quiser.</div>
        </div>
    </div>

    <div class="plans-grid">
        @foreach($plans as $p)
        <div class="plan-card {{ $tenant->plan === $p->name ? 'current' : '' }}">
            @if($tenant->plan === $p->name)
                <div class="plan-current-tag">Seu plano</div>
            @endif
            <h3>{{ $p->display_name }}</h3>
            <div class="price">
                @if($p->price_monthly > 0)
                    R$ {{ number_format($p->price_monthly, 2, ',', '.') }}
                    <span>/mês</span>
                @else
                    <span style="color:#10b981;font-size:16px;font-weight:700;">Grátis</span>
                @endif
            </div>
            @if(!empty($p->features_json))
            <ul>
                @foreach(array_slice($p->features_json, 0, 5) as $feature)
                    <li>{{ $feature }}</li>
                @endforeach
            </ul>
            @endif
            @if($tenant->plan !== $p->name && $p->price_monthly > 0)
                <a href="{{ route('billing.checkout') }}" class="btn-primary-sm" style="width:100%;text-align:center;box-sizing:border-box;">
                    Assinar
                </a>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Cancelamento --}}
    @if($tenant->subscription_status === 'active' && $tenant->asaas_subscription_id)
    <div class="cancel-section">
        <h3>Cancelar assinatura</h3>
        <p>
            Ao cancelar, seu acesso será encerrado imediatamente e não haverá cobranças futuras.
            Seus dados ficam salvos por 30 dias caso deseje reativar.
        </p>
        <button class="btn-icon danger" onclick="confirmCancel()" style="width:auto;padding:7px 16px;font-size:13px;">
            <i class="bi bi-x-circle"></i> Cancelar assinatura
        </button>
    </div>
    @endif

</div>

<div id="alertCancel" style="display:none;"></div>

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
