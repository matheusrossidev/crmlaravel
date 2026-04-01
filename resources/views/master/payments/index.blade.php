@extends('master.layouts.app')
@php
    $title    = 'Recebimentos';
    $pageIcon = 'cash-stack';
@endphp

@section('content')

<div class="m-section-header">
    <div class="m-section-title">Recebimentos</div>
    <div class="m-section-subtitle">Histórico de pagamentos recebidos</div>
</div>

{{-- Stats --}}
<div class="m-stats" style="margin-bottom:20px;">
    <div class="m-stat" style="border-left:3px solid #10B981;">
        <div class="m-stat-label">Receita este mês</div>
        <div class="m-stat-value" style="color:#10B981;">R$ {{ number_format($stats['revenue_month'], 2, ',', '.') }}</div>
    </div>
    <div class="m-stat" style="border-left:3px solid #6b7280;">
        <div class="m-stat-label">Receita mês anterior</div>
        <div class="m-stat-value">R$ {{ number_format($stats['revenue_last_month'], 2, ',', '.') }}</div>
    </div>
    <div class="m-stat" style="border-left:3px solid {{ $stats['variation'] >= 0 ? '#10B981' : '#EF4444' }};">
        <div class="m-stat-label">Variação</div>
        <div class="m-stat-value" style="color:{{ $stats['variation'] >= 0 ? '#10B981' : '#EF4444' }};">
            {{ $stats['variation'] >= 0 ? '+' : '' }}{{ $stats['variation'] }}%
        </div>
    </div>
    <div class="m-stat" style="border-left:3px solid #3B82F6;">
        <div class="m-stat-label">Transações este mês</div>
        <div class="m-stat-value" style="color:#3B82F6;">{{ $stats['transactions'] }}</div>
    </div>
</div>

{{-- Filtros --}}
<div class="m-card" style="margin-bottom:16px;">
    <div class="m-card-body" style="padding:14px 20px;">
        <form method="GET" action="{{ route('master.payments') }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div>
                <label style="font-size:12px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">Tipo</label>
                <select name="type" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;min-width:140px;">
                    <option value="">Todos</option>
                    <option value="subscription" {{ request('type') === 'subscription' ? 'selected' : '' }}>Assinatura</option>
                    <option value="token_increment" {{ request('type') === 'token_increment' ? 'selected' : '' }}>Tokens</option>
                </select>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">De</label>
                <input type="date" name="from" value="{{ request('from') }}"
                       style="padding:6px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px;">Até</label>
                <input type="date" name="to" value="{{ request('to') }}"
                       style="padding:6px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;">
            </div>
            <button type="submit" class="m-btn m-btn-primary m-btn-sm">
                <i class="bi bi-funnel"></i> Filtrar
            </button>
            @if(request()->hasAny(['type', 'from', 'to', 'tenant_id']))
                <a href="{{ route('master.payments') }}" class="m-btn m-btn-ghost m-btn-sm">Limpar</a>
            @endif
        </form>
    </div>
</div>

{{-- Tabela --}}
<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title">
            <i class="bi bi-receipt"></i>
            Pagamentos Recebidos
        </div>
        <span style="font-size:12.5px;color:#6b7280;">{{ $payments->total() }} registro(s)</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="m-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Empresa</th>
                    <th>Tipo</th>
                    <th>Descrição</th>
                    <th style="text-align:right;">Valor</th>
                    <th>Status</th>
                    <th>Asaas ID</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                <tr>
                    <td style="font-size:12.5px;color:#374151;white-space:nowrap;">
                        {{ $p->paid_at->format('d/m/Y H:i') }}
                    </td>
                    <td>
                        @if($p->tenant)
                            <a href="{{ route('master.tenants.show', $p->tenant_id) }}"
                               style="font-weight:600;color:#1a1d23;text-decoration:none;">
                                {{ $p->tenant->name }}
                            </a>
                        @else
                            <span style="color:#9ca3af;">Removida</span>
                        @endif
                    </td>
                    <td>
                        @if($p->type === 'subscription')
                            <span class="m-badge m-badge-active">Assinatura</span>
                        @else
                            <span class="m-badge m-badge-partner">Tokens</span>
                        @endif
                    </td>
                    <td style="font-size:13px;color:#374151;">{{ $p->description ?? '—' }}</td>
                    <td style="text-align:right;font-weight:700;color:#10B981;white-space:nowrap;">
                        R$ {{ number_format($p->amount, 2, ',', '.') }}
                    </td>
                    <td>
                        <span class="m-badge m-badge-active">{{ ucfirst($p->status) }}</span>
                    </td>
                    <td style="font-size:11px;color:#9ca3af;font-family:monospace;">
                        {{ $p->asaas_payment_id ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:#9ca3af;">
                        <i class="bi bi-inbox" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                        Nenhum pagamento registrado ainda.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div style="padding:14px 20px;border-top:1px solid #f0f2f7;">
        {{ $payments->links() }}
    </div>
    @endif
</div>

@endsection
