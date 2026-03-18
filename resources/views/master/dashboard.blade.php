@extends('master.layouts.app')
@php
    $title    = 'Dashboard';
    $pageIcon = 'grid-1x2';
@endphp

@section('content')

{{-- Revenue Cards --}}
<div class="m-stats" style="margin-bottom:12px;">
    <div class="m-stat" style="border-left:3px solid #10B981;">
        <div class="m-stat-label">MRR (Assinaturas)</div>
        <div class="m-stat-value" style="color:#10B981;">R$ {{ number_format($revenue['mrr'], 2, ',', '.') }}</div>
    </div>
    <div class="m-stat" style="border-left:3px solid #8B5CF6;">
        <div class="m-stat-label">Tokens este mês</div>
        <div class="m-stat-value" style="color:#8B5CF6;">R$ {{ number_format($revenue['tokens_month'], 2, ',', '.') }}</div>
    </div>
    <div class="m-stat" style="border-left:3px solid #3B82F6;">
        <div class="m-stat-label">Receita Total Mês</div>
        <div class="m-stat-value" style="color:#3B82F6;">R$ {{ number_format($revenue['total_mrr'], 2, ',', '.') }}</div>
    </div>
    <div class="m-stat" style="border-left:3px solid #F59E0B;">
        <div class="m-stat-label">ARR Projetado</div>
        <div class="m-stat-value" style="color:#F59E0B;">R$ {{ number_format($revenue['arr'], 2, ',', '.') }}</div>
    </div>
    <div class="m-stat" style="border-left:3px solid #EF4444;">
        <div class="m-stat-label">Churn este mês</div>
        <div class="m-stat-value" style="color:#EF4444;">{{ $revenue['churn_month'] }}</div>
    </div>
</div>

{{-- Tenant Stats --}}
<div class="m-stats">
    <div class="m-stat">
        <div class="m-stat-label">Total de Empresas</div>
        <div class="m-stat-value">{{ $stats['total'] }}</div>
    </div>
    <div class="m-stat">
        <div class="m-stat-label">Ativas</div>
        <div class="m-stat-value" style="color:#10B981;">{{ $stats['active'] }}</div>
    </div>
    <div class="m-stat">
        <div class="m-stat-label">Pagantes</div>
        <div class="m-stat-value" style="color:#059669;">{{ $stats['paying'] }}</div>
    </div>
    <div class="m-stat">
        <div class="m-stat-label">Em Trial</div>
        <div class="m-stat-value" style="color:#F59E0B;">{{ $stats['trial'] }}</div>
    </div>
    <div class="m-stat">
        <div class="m-stat-label">Parceiros</div>
        <div class="m-stat-value" style="color:#8B5CF6;">{{ $stats['partner'] }}</div>
    </div>
    <div class="m-stat">
        <div class="m-stat-label">Suspensas/Inativas</div>
        <div class="m-stat-value" style="color:#EF4444;">{{ $stats['suspended'] }}</div>
    </div>
    <div class="m-stat">
        <div class="m-stat-label">Novas este mês</div>
        <div class="m-stat-value" style="color:#3B82F6;">{{ $stats['new_month'] }}</div>
    </div>
</div>

{{-- Últimos Pagamentos --}}
<div class="m-card" style="margin-bottom:20px;">
    <div class="m-card-header">
        <div class="m-card-title">
            <i class="bi bi-cash-stack"></i>
            Últimos Recebimentos
        </div>
        <a href="{{ route('master.payments') }}" class="m-btn m-btn-ghost m-btn-sm">Ver todos</a>
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
                </tr>
            </thead>
            <tbody>
                @forelse($recentPayments as $p)
                <tr>
                    <td style="font-size:12.5px;color:#6b7280;">{{ $p->paid_at->format('d/m/Y H:i') }}</td>
                    <td style="font-weight:600;">{{ $p->tenant?->name ?? '—' }}</td>
                    <td>
                        @if($p->type === 'subscription')
                            <span class="m-badge m-badge-active">Assinatura</span>
                        @else
                            <span class="m-badge m-badge-partner">Tokens</span>
                        @endif
                    </td>
                    <td style="font-size:13px;color:#374151;">{{ $p->description ?? '—' }}</td>
                    <td style="text-align:right;font-weight:700;color:#10B981;">R$ {{ number_format($p->amount, 2, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:24px;color:#9ca3af;">Nenhum pagamento registrado ainda.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Recentes --}}
<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title">
            <i class="bi bi-building"></i>
            Empresas Recentes
        </div>
        <a href="{{ route('master.tenants') }}" class="m-btn m-btn-ghost m-btn-sm">Ver todas</a>
    </div>
    <div style="overflow-x:auto;">
        <table class="m-table">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Slug</th>
                    <th>Plano</th>
                    <th>Status</th>
                    <th>Trial até</th>
                    <th>Criada em</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTenants as $t)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:8px;background:#2a84ef;
                                        display:flex;align-items:center;justify-content:center;
                                        color:#fff;font-weight:700;font-size:13px;overflow:hidden;flex-shrink:0;">
                                @if($t->logo)
                                    <img src="{{ $t->logo }}" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
                                @else
                                    {{ strtoupper(substr($t->name, 0, 1)) }}
                                @endif
                            </div>
                            <span style="font-weight:600;">{{ $t->name }}</span>
                        </div>
                    </td>
                    <td style="color:#9ca3af;">{{ $t->slug }}</td>
                    <td>
                        <span class="m-badge" style="background:#EFF6FF;color:#1D4ED8;">{{ $t->plan }}</span>
                    </td>
                    <td>
                        @php
                            $badgeClass = match($t->status) {
                                'active'    => 'm-badge-active',
                                'trial'     => 'm-badge-trial',
                                'partner'   => 'm-badge-partner',
                                'suspended' => 'm-badge-suspended',
                                default     => 'm-badge-inactive',
                            };
                            $statusLabel = match($t->status) {
                                'active'    => 'Ativo',
                                'trial'     => 'Trial',
                                'partner'   => 'Parceiro',
                                'suspended' => 'Suspenso',
                                'inactive'  => 'Inativo',
                                default     => ucfirst($t->status),
                            };
                        @endphp
                        <span class="m-badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td style="font-size:12.5px;color:#6b7280;">
                        {{ $t->trial_ends_at ? $t->trial_ends_at->format('d/m/Y') : '—' }}
                    </td>
                    <td style="font-size:12.5px;color:#6b7280;">{{ $t->created_at->format('d/m/Y') }}</td>
                    <td>
                        <a href="{{ route('master.tenants.show', $t->id) }}" class="m-btn m-btn-ghost m-btn-sm">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:32px;color:#9ca3af;">Nenhuma empresa cadastrada.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
