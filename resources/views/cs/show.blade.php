@extends('cs.layouts.app')
@php $title = $tenant->name; @endphp

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
    .cs-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    @media (max-width: 768px) { .cs-detail-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

{{-- Back + Header --}}
<div style="margin-bottom:24px;">
    <a href="{{ route('cs.index') }}" style="font-size:13px;color:#6b7280;text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;">
        <i class="bi bi-arrow-left"></i> Voltar para lista
    </a>
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <div>
            <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:24px;font-weight:700;color:#1a1d23;margin:0 0 4px;">
                {{ $tenant->name }}
            </h1>
            <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#6b7280;">
                <span style="text-transform:capitalize;font-weight:600;">{{ $tenant->plan }}</span>
                <span style="color:#d1d5db;">|</span>
                @if($tenant->status === 'active')
                    <span class="m-badge m-badge-active">Ativo</span>
                @elseif($tenant->status === 'trial')
                    <span class="m-badge m-badge-trial">Trial
                        @if($tenant->trial_ends_at)
                            ({{ (int) now()->diffInDays($tenant->trial_ends_at, false) }}d restantes)
                        @endif
                    </span>
                @elseif($tenant->status === 'suspended')
                    <span class="m-badge m-badge-suspended">Suspenso</span>
                @else
                    <span class="m-badge m-badge-inactive">{{ ucfirst($tenant->status) }}</span>
                @endif
                @if($tenant->phone)
                    <span style="color:#d1d5db;">|</span>
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $tenant->phone) }}" target="_blank" style="color:#25D366;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                        <i class="bi bi-whatsapp"></i> {{ $tenant->phone }}
                    </a>
                @endif
                <span style="color:#d1d5db;">|</span>
                <span>Criado em {{ $tenant->created_at->format('d/m/Y') }}</span>
                <span style="color:#d1d5db;">|</span>
                <span>Último login:
                    @if($lastLogin)
                        {{ \Carbon\Carbon::parse($lastLogin)->format('d/m/Y H:i') }}
                    @else
                        <span style="color:#ef4444;">Nunca</span>
                    @endif
                </span>
            </div>
        </div>
    </div>
</div>

{{-- KPIs --}}
<div class="m-stats" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
    <div class="m-stat">
        <div class="m-stat-label"><i class="bi bi-person-lines-fill" style="color:#0085f3;"></i> Leads (30d)</div>
        <div class="m-stat-value">{{ number_format($leads30d) }}</div>
    </div>
    <div class="m-stat">
        <div class="m-stat-label"><i class="bi bi-whatsapp" style="color:#25D366;"></i> Msgs WA (30d)</div>
        <div class="m-stat-value">{{ number_format($waMessages30d) }}</div>
    </div>
    <div class="m-stat">
        <div class="m-stat-label"><i class="bi bi-trophy" style="color:#F59E0B;"></i> Vendas (30d)</div>
        <div class="m-stat-value">{{ $sales30d->cnt ?? 0 }}</div>
        <div style="font-size:12px;color:#6b7280;margin-top:2px;">R$ {{ number_format($sales30d->total ?? 0, 2, ',', '.') }}</div>
    </div>
    <div class="m-stat">
        <div class="m-stat-label"><i class="bi bi-cpu" style="color:#8B5CF6;"></i> Tokens IA (30d)</div>
        <div class="m-stat-value">{{ number_format($tokensUsed) }}</div>
    </div>
    <div class="m-stat">
        <div class="m-stat-label"><i class="bi bi-gear" style="color:#6B7280;"></i> Automações (30d)</div>
        <div class="m-stat-value">{{ number_format($automationsRun) }}</div>
    </div>
    <div class="m-stat">
        <div class="m-stat-label"><i class="bi bi-people" style="color:#0085f3;"></i> Usuários ativos (7d)</div>
        <div class="m-stat-value">{{ $activeUsers7d }}</div>
    </div>
</div>

<div class="cs-detail-grid">
    {{-- Feature Adoption --}}
    <div class="m-card">
        <div class="m-card-header">
            <div class="m-card-title"><i class="bi bi-check2-square"></i> Adoção de Features</div>
        </div>
        <div class="m-card-body" style="padding:16px 22px;">
            @php
                $features = [
                    ['Onboarding completo', (bool) $tenant->onboarding_completed_at],
                    ['WhatsApp conectado', $waConnected],
                    ['Instagram conectado', $igConnected],
                    ['Pelo menos 1 lead', $hasLeads],
                    ['Chatbot ativo', $hasChatbot],
                    ['Agente IA ativo', $hasAiAgent],
                    ['Automação ativa', $hasAutomation],
                    ['Calendário conectado', $hasCalendar],
                ];
                $adoptedCount = collect($features)->filter(fn($f) => $f[1])->count();
            @endphp
            <div style="margin-bottom:12px;font-size:13px;color:#6b7280;">
                {{ $adoptedCount }}/{{ count($features) }} features adotadas
                <div style="height:6px;background:#f3f4f6;border-radius:99px;margin-top:6px;overflow:hidden;">
                    <div style="height:100%;width:{{ ($adoptedCount / count($features)) * 100 }}%;background:#0085f3;border-radius:99px;"></div>
                </div>
            </div>
            @foreach($features as [$label, $active])
                <div style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:13px;{{ $active ? 'color:#065f46;' : 'color:#9ca3af;' }}">
                    <i class="bi {{ $active ? 'bi-check-circle-fill' : 'bi-circle' }}" style="{{ $active ? 'color:#10B981;' : 'color:#d1d5db;' }}"></i>
                    {{ $label }}
                </div>
            @endforeach
        </div>
    </div>

    {{-- Activity Chart --}}
    <div class="m-card">
        <div class="m-card-header">
            <div class="m-card-title"><i class="bi bi-graph-up"></i> Atividade (30 dias)</div>
        </div>
        <div class="m-card-body">
            <canvas id="activityChart" height="220"></canvas>
        </div>
    </div>
</div>

{{-- Users --}}
<div class="m-card" style="margin-bottom:20px;">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-people"></i> Usuários ({{ $users->count() }})</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="m-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Último Login</th>
                    <th>Email Verificado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr>
                    <td style="font-weight:600;">{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td><span style="text-transform:capitalize;font-size:12px;font-weight:600;">{{ $u->role }}</span></td>
                    <td style="font-size:12.5px;">
                        @if($u->last_login_at)
                            {{ $u->last_login_at->format('d/m/Y H:i') }}
                        @else
                            <span style="color:#9ca3af;">Nunca</span>
                        @endif
                    </td>
                    <td>
                        @if($u->email_verified_at)
                            <i class="bi bi-check-circle-fill" style="color:#10B981;"></i>
                        @else
                            <i class="bi bi-x-circle" style="color:#ef4444;"></i>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Payments --}}
<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-credit-card"></i> Últimos Pagamentos</div>
    </div>
    @if($payments->isEmpty())
        <div style="padding:40px;text-align:center;color:#9ca3af;font-size:13px;">
            <i class="bi bi-inbox" style="font-size:28px;display:block;margin-bottom:8px;"></i>
            Nenhum pagamento registrado.
        </div>
    @else
        <div style="overflow-x:auto;">
            <table class="m-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $p)
                    <tr>
                        <td style="font-size:12.5px;">{{ $p->paid_at ? \Carbon\Carbon::parse($p->paid_at)->format('d/m/Y') : '-' }}</td>
                        <td><span style="font-size:12px;font-weight:600;text-transform:capitalize;">{{ $p->type }}</span></td>
                        <td>{{ $p->description }}</td>
                        <td style="font-weight:600;">R$ {{ number_format($p->amount, 2, ',', '.') }}</td>
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:#065f46;">
                                <i class="bi bi-check-circle-fill" style="color:#10B981;"></i> {{ ucfirst($p->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
const ctx = document.getElementById('activityChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartLabels) !!},
            datasets: [
                {
                    label: 'Mensagens WA',
                    data: {!! json_encode($chartMessages) !!},
                    backgroundColor: 'rgba(37, 211, 102, 0.3)',
                    borderColor: '#25D366',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Leads',
                    data: {!! json_encode($chartLeads) !!},
                    backgroundColor: 'rgba(0, 133, 243, 0.3)',
                    borderColor: '#0085f3',
                    borderWidth: 1,
                    borderRadius: 4,
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 0 } },
                y: { beginAtZero: true, grid: { color: '#f0f2f7' }, ticks: { font: { size: 11 } } },
            },
        },
    });
}
</script>
@endpush
