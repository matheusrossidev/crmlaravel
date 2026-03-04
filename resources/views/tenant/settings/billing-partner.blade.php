@extends('tenant.layouts.app')

@php
    $title    = 'Configurações';
    $pageIcon = 'gear';
@endphp

@push('styles')
<style>
.partner-layout {
    display: flex;
    gap: 24px;
    align-items: flex-start;
    width: 100%;
    flex-direction: column;
}
.partner-main {
    flex: 1;
    width: 100%;
    min-width: 0;
}

/* Plan card */
.partner-plan-card {
    background: #fff;
    border: 2px solid #0085f3;
    border-radius: 16px;
    overflow: hidden;
}
.partner-plan-header {
    background: #0085f3;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.partner-plan-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.partner-plan-icon {
    width: 44px;
    height: 44px;
    background: rgba(255,255,255,.2);
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.partner-plan-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: rgba(255,255,255,.75);
    margin-bottom: 3px;
}
.partner-plan-name { font-size: 18px; font-weight: 800; color: #fff; }
.partner-status-badge {
    background: rgba(255,255,255,.2);
    color: #fff;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}
.partner-plan-body { padding: 20px 24px; }
.partner-since {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f3f4f6;
}
.partner-since strong { color: #374151; }

/* Benefits list */
.benefit-list { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
@media (max-width: 600px) { .benefit-list { grid-template-columns: 1fr; } }
.benefit-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: #374151;
    padding: 6px 0;
    border-bottom: 1px solid #f9fafb;
}
.benefit-list li:nth-last-child(-n+2) { border-bottom: none; }
.benefit-check {
    width: 20px;
    height: 20px;
    background: #eff6ff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 10px;
    color: #0085f3;
    font-weight: 700;
}

/* Info cards */
.partner-card {
    background: #fff;
    border: 1.5px solid #e8eaf0;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 16px;
}
.partner-card:last-child { margin-bottom: 0; }
.partner-card-header {
    padding: 14px 20px;
    border-bottom: 1px solid #f0f2f7;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 700;
    color: #1a1d23;
}
.partner-card-body { padding: 20px; }

/* Link input */
.link-input-group {
    display: flex;
    border: 1.5px solid #d1d5db;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 12px;
}
.link-input-group input {
    flex: 1;
    padding: 10px 13px;
    background: #f9fafb;
    border: none;
    font-size: 13px;
    color: #374151;
    outline: none;
    min-width: 0;
}
.link-input-group button {
    padding: 10px 16px;
    background: #0085f3;
    color: #fff;
    border: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s;
}
.link-input-group button:hover { background: #0070d1; }

/* Code row */
.code-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.code-badge {
    padding: 7px 14px;
    background: #eff6ff;
    border: 1.5px solid #bfdbfe;
    border-radius: 8px;
    font-family: monospace;
    font-size: 14px;
    font-weight: 700;
    color: #1d4ed8;
    letter-spacing: .06em;
}
.btn-copy-code {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 13px;
    background: #fff;
    color: #0085f3;
    border: 1.5px solid #bfdbfe;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}
.btn-copy-code:hover { background: #eff6ff; }

/* Client counter */
.client-counter-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}
.client-counter-info .label { font-size: 14px; font-weight: 700; color: #1a1d23; }
.client-counter-info .sub   { font-size: 12.5px; color: #9ca3af; margin-top: 2px; }
.client-counter-number { font-size: 40px; font-weight: 800; color: #0085f3; line-height: 1; }
.btn-view-clients {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: #eff6ff;
    color: #0085f3;
    border: 1.5px solid #bfdbfe;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none;
    transition: background .15s;
}
.btn-view-clients:hover { background: #dbeafe; color: #0070d1; }
</style>
@endpush

@section('content')
<div class="page-container">
<div class="partner-layout">

    <div class="partner-main">

        {{-- ── Plano Parceiro ── --}}
        <div class="partner-plan-card" style="margin-bottom:16px;">
            <div class="partner-plan-header">
                <div class="partner-plan-header-left">
                    <div class="partner-plan-icon">
                        <i class="bi bi-building-check" style="font-size:20px;color:#fff;"></i>
                    </div>
                    <div>
                        <div class="partner-plan-label">Plano atual</div>
                        <div class="partner-plan-name">Parceiro</div>
                    </div>
                </div>
                <span class="partner-status-badge">
                    <i class="bi bi-circle-fill" style="font-size:7px;margin-right:4px;"></i>Ativo
                </span>
            </div>
            <div class="partner-plan-body">
                <div class="partner-since">
                    Parceiro desde <strong>{{ $partnerSince->translatedFormat('d \d\e F \d\e Y') }}</strong>
                </div>
                <ul class="benefit-list">
                    @foreach([
                        'Usuários ilimitados',
                        'Leads e pipelines ilimitados',
                        'Agentes de IA incluídos',
                        'Sem cobrança mensal',
                        'Acesso a contas de clientes',
                        'Tokens de IA ilimitados',
                    ] as $benefit)
                    <li>
                        <span class="benefit-check">✓</span>
                        {{ $benefit }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="partner-card">
            <div class="partner-card-header">
                <i class="bi bi-link-45deg" style="color:#0085f3;"></i>
                Link de Indicação
            </div>
            <div class="partner-card-body">
                @if($partnerCode && $registerLink)
                <p style="font-size:13px;color:#6b7280;margin:0 0 14px;">
                    Compartilhe este link com seus clientes para que eles se cadastrem vinculados ao seu código de parceiro.
                </p>
                <div class="link-input-group">
                    <input type="text" value="{{ $registerLink }}" readonly>
                    <button onclick="copyLink()">
                        <i class="bi bi-clipboard me-1"></i> Copiar Link
                    </button>
                </div>
                <div class="code-row">
                    <span class="code-badge">{{ $partnerCode->code }}</span>
                    <button class="btn-copy-code" onclick="copyCode()">
                        <i class="bi bi-clipboard"></i> Copiar Código
                    </button>
                    <span style="font-size:12.5px;color:#9ca3af;">Código único do seu plano</span>
                </div>
                @else
                <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;background:#f9fafb;border-radius:10px;border:1.5px dashed #e5e7eb;">
                    <i class="bi bi-info-circle" style="color:#9ca3af;font-size:18px;flex-shrink:0;"></i>
                    <span style="font-size:13px;color:#6b7280;">Nenhum código de parceiro atribuído ainda. Solicite ao administrador para vincular seu código.</span>
                </div>
                @endif
            </div>
        </div>

        <div class="partner-card">
            <div class="partner-card-header">
                <i class="bi bi-building" style="color:#0085f3;"></i>
                Clientes Indicados
            </div>
            <div class="partner-card-body">
                <div class="client-counter-row">
                    <div class="client-counter-info">
                        <div class="label">Total de empresas cadastradas</div>
                        <div class="sub">com o seu código de parceiro</div>
                    </div>
                    <div class="client-counter-number">{{ $clientCount }}</div>
                </div>
                <a href="{{ route('agency.clients') }}" class="btn-view-clients">
                    <i class="bi bi-building"></i>
                    Ver todos os clientes
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>

    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
function copyLink() {
    navigator.clipboard.writeText('{{ $registerLink ?? '' }}').then(() => toastr.success('Link copiado!'));
}
function copyCode() {
    navigator.clipboard.writeText('{{ $partnerCode->code ?? '' }}').then(() => toastr.success('Código copiado!'));
}
</script>
@endpush
