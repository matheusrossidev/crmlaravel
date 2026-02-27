@extends('tenant.layouts.app')

@php
    $title    = 'Configurações';
    $pageIcon = 'gear';

    $actionLabels = [
        'add_tag_lead'          => 'Tag no lead',
        'remove_tag_lead'       => 'Remover tag',
        'add_tag_conversation'  => 'Tag na conversa',
        'move_to_stage'         => 'Mover etapa',
        'set_lead_source'       => 'Definir origem',
        'assign_to_user'        => 'Atribuir usuário',
        'add_note'              => 'Adicionar nota',
        'assign_ai_agent'       => 'Agente IA',
        'assign_chatbot_flow'   => 'Chatbot',
        'close_conversation'    => 'Fechar conversa',
        'send_whatsapp_message' => 'Enviar msg WA',
    ];
@endphp

@push('styles')
<style>
.at-wrap {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 12px;
    overflow: hidden;
}
.at-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.at-table thead th {
    padding: 11px 18px;
    font-size: 11px; font-weight: 700; color: #9ca3af;
    text-transform: uppercase; letter-spacing: .06em;
    background: #fafafa; border-bottom: 1px solid #f0f2f7;
}
.at-table tbody tr { border-bottom: 1px solid #f7f8fa; transition: background .12s; }
.at-table tbody tr:last-child { border-bottom: none; }
.at-table tbody tr:hover { background: #fafbfc; }
.at-table tbody td { padding: 14px 18px; color: #374151; vertical-align: middle; }

.trigger-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11.5px; font-weight: 600; white-space: nowrap;
}
.trigger-badge.msg   { background: #ecfdf5; color: #059669; }
.trigger-badge.conv  { background: #eff6ff; color: #2563eb; }
.trigger-badge.lead  { background: #fef9c3; color: #b45309; }
.trigger-badge.stage { background: #f3e8ff; color: #7c3aed; }
.trigger-badge.won   { background: #dcfce7; color: #16a34a; }
.trigger-badge.lost  { background: #fee2e2; color: #dc2626; }

.action-chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 12px;
    background: #f3f4f6; color: #4b5563;
    font-size: 11.5px; font-weight: 500; margin: 2px 2px 2px 0;
}

.btn-icon {
    width: 28px; height: 28px; border-radius: 7px;
    border: 1px solid #e8eaf0; background: #fff; color: #6b7280;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 13px; transition: all .15s;
    flex-shrink: 0; text-decoration: none;
}
.btn-icon:hover         { background: #f3f4f6; color: #374151; border-color: #d1d5db; }
.btn-icon.danger:hover  { background: #fef2f2; color: #ef4444; border-color: #fca5a5; }

.at-empty { text-align: center; padding: 60px 20px; }
.at-empty i { font-size: 44px; color: #d1d5db; }
.at-empty p  { color: #9ca3af; font-size: 13.5px; margin-top: 12px; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div class="section-header">
        <div>
            <div class="section-title">Automações</div>
            <div class="section-subtitle">Crie regras para automatizar ações quando eventos ocorrerem no CRM.</div>
        </div>
        <a href="{{ route('settings.automations.create') }}" class="btn-primary-sm">
            <i class="bi bi-plus-lg"></i> Nova Automação
        </a>
    </div>

    <div class="at-wrap">
        @if($automations->isEmpty())
            <div class="at-empty">
                <i class="bi bi-lightning-charge"></i>
                <p>Nenhuma automação criada ainda.<br>Clique em <strong>Nova Automação</strong> para começar.</p>
            </div>
        @else
            <table class="at-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Gatilho</th>
                        <th>Ações</th>
                        <th style="width:110px;">Execuções</th>
                        <th style="width:80px;">Ativo</th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($automations as $auto)
                    <tr id="at-row-{{ $auto->id }}">
                        <td style="font-weight:600;">{{ $auto->name }}</td>
                        <td>@include('tenant.settings._automation_trigger_badge', ['auto' => $auto])</td>
                        <td>
                            @foreach($auto->actions as $act)
                                <span class="action-chip">
                                    <i class="bi bi-check2"></i>
                                    {{ $actionLabels[$act['type']] ?? $act['type'] }}
                                </span>
                            @endforeach
                        </td>
                        <td>
                            <span style="font-size:13px;font-weight:600;">{{ $auto->run_count }}</span>
                            @if($auto->last_run_at)
                                <br><span style="font-size:11px;color:#9ca3af;">{{ $auto->last_run_at->diffForHumans() }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox"
                                    {{ $auto->is_active ? 'checked' : '' }}
                                    onchange="toggleAutomation({{ $auto->id }}, this)">
                            </div>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('settings.automations.edit', $auto) }}"
                                   class="btn-icon" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button class="btn-icon danger" title="Excluir"
                                    onclick="deleteAutomation({{ $auto->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>

<script>
function toggleAutomation(id, cb) {
    fetch(`/configuracoes/automacoes/${id}/toggle`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
    }).then(r => r.json()).then(res => {
        if (res.success) toastr.success(res.is_active ? 'Ativada.' : 'Desativada.');
        else { cb.checked = !cb.checked; toastr.error('Erro.'); }
    });
}
function deleteAutomation(id) {
    if (!confirm('Excluir esta automação?')) return;
    fetch(`/configuracoes/automacoes/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
    }).then(r => r.json()).then(res => {
        if (res.success) { document.getElementById(`at-row-${id}`)?.remove(); toastr.success('Excluída.'); }
        else toastr.error('Erro ao excluir.');
    });
}
</script>
@endsection
