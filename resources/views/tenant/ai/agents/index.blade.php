@extends('tenant.layouts.app')

@php
    $title    = 'Agente de IA';
    $pageIcon = 'robot';
@endphp

@push('styles')
<style>
    .agents-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 24px;
    }
    .agents-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 16px;
    }
    .agent-card {
        background: #fff; border: 1px solid #e8eaf0;
        border-radius: 14px; padding: 20px;
        display: flex; flex-direction: column; gap: 12px;
        transition: box-shadow .15s;
    }
    .agent-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.06); }

    .agent-card-top {
        display: flex; align-items: flex-start; gap: 12px;
    }
    .agent-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; flex-shrink: 0;
        background: #eff6ff;
    }
    .agent-name {
        font-size: 14px; font-weight: 700; color: #1a1d23;
        margin-bottom: 2px;
    }
    .agent-meta { font-size: 12px; color: #9ca3af; }

    .badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 9px; border-radius: 20px;
        font-size: 11px; font-weight: 700;
    }
    .badge-active   { background: #d1fae5; color: #065f46; }
    .badge-inactive { background: #f3f4f6; color: #6b7280; }
    .badge-channel  { background: #eff6ff; color: #3B82F6; }
    .badge-obj      { background: #fef3c7; color: #92400e; }

    .agent-badges { display: flex; flex-wrap: wrap; gap: 5px; }

    .agent-actions {
        display: flex; gap: 7px; margin-top: auto;
        padding-top: 12px; border-top: 1px solid #f0f2f7;
    }
    .btn-action {
        padding: 6px 14px; border-radius: 8px;
        font-size: 12.5px; font-weight: 600; cursor: pointer;
        border: 1.5px solid #e8eaf0; background: #fff; color: #374151;
        transition: all .15s; display: flex; align-items: center; gap: 5px;
    }
    .btn-action:hover { background: #f0f4ff; border-color: #bfdbfe; color: #3B82F6; }
    .btn-action.primary { background: #3B82F6; border-color: #3B82F6; color: #fff; }
    .btn-action.primary:hover { background: #2563eb; }
    .btn-action.danger:hover { background: #fee2e2; border-color: #fca5a5; color: #ef4444; }

    .empty-state {
        text-align: center; padding: 64px 24px; color: #9ca3af;
        background: #fff; border: 1px solid #e8eaf0; border-radius: 14px;
    }
    .empty-state i { font-size: 42px; opacity: .2; display: block; margin-bottom: 14px; }
    .empty-state p { font-size: 14px; margin-bottom: 18px; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div class="agents-header">
        <div>
            <div style="font-size:15px;font-weight:700;color:#1a1d23;">Agentes de IA</div>
            <div style="font-size:13px;color:#9ca3af;margin-top:3px;">Crie e gerencie agentes conversacionais.</div>
        </div>
        <a href="{{ route('ai.agents.create') }}" class="btn-primary-sm">
            <i class="bi bi-plus-lg"></i> Novo Agente
        </a>
    </div>

    @if($agents->isEmpty())
    <div class="empty-state">
        <i class="bi bi-robot"></i>
        <p>Nenhum agente criado ainda.</p>
        <a href="{{ route('ai.agents.create') }}" class="btn-primary-sm">
            <i class="bi bi-plus-lg"></i> Criar primeiro agente
        </a>
    </div>
    @else
    <div class="agents-grid">
        @foreach($agents as $agent)
        @php
            $objLabel = ['sales' => 'Vendas', 'support' => 'Suporte', 'general' => 'Geral'][$agent->objective] ?? $agent->objective;
            $chLabel  = $agent->channel === 'whatsapp' ? 'WhatsApp' : 'Web Chat';
            $chIcon   = $agent->channel === 'whatsapp' ? 'whatsapp' : 'chat-dots';
        @endphp
        <div class="agent-card">
            <div class="agent-card-top">
                <div class="agent-icon">🤖</div>
                <div style="flex:1;min-width:0;">
                    <div class="agent-name">{{ $agent->name }}</div>
                    @if($agent->company_name)
                    <div class="agent-meta">{{ $agent->company_name }}</div>
                    @endif
                </div>
            </div>

            <div class="agent-badges">
                <span class="badge {{ $agent->is_active ? 'badge-active' : 'badge-inactive' }}">
                    <i class="bi bi-circle-fill" style="font-size:7px;"></i>
                    {{ $agent->is_active ? 'Ativo' : 'Inativo' }}
                </span>
                <span class="badge badge-channel">
                    <i class="bi bi-{{ $chIcon }}"></i> {{ $chLabel }}
                </span>
                <span class="badge badge-obj">{{ $objLabel }}</span>
            </div>

            @if($agent->persona_description)
            <div style="font-size:12px;color:#6b7280;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                {{ $agent->persona_description }}
            </div>
            @endif

            <div class="agent-actions">
                <a href="{{ route('ai.agents.edit', $agent) }}" class="btn-action primary">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                <button class="btn-action"
                        onclick="toggleActive({{ $agent->id }}, {{ $agent->is_active ? 'true' : 'false' }}, this)"
                        title="{{ $agent->is_active ? 'Desativar' : 'Ativar' }}">
                    <i class="bi bi-{{ $agent->is_active ? 'pause' : 'play' }}"></i>
                    {{ $agent->is_active ? 'Pausar' : 'Ativar' }}
                </button>
                <button class="btn-action danger" onclick="deleteAgent({{ $agent->id }}, this)" title="Excluir">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

async function toggleActive(id, isActive, btn) {
    const res  = await fetch(`/ia/agentes/${id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
    });
    const data = await res.json();
    if (!data.success) { toastr.error('Erro.'); return; }
    // Reload para simplicidade
    location.reload();
}

async function deleteAgent(id, btn) {
    if (!confirm('Excluir este agente?')) return;
    const res  = await fetch(`/ia/agentes/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF },
    });
    const data = await res.json();
    if (!data.success) { toastr.error('Erro ao excluir.'); return; }
    btn.closest('.agent-card').remove();
    toastr.success('Agente excluído.');
}
</script>
@endpush
