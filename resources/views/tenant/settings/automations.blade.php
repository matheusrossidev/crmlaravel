@extends('tenant.layouts.app')

@php
    $title    = __('automations.title');
    $pageIcon = 'lightning-charge';

    $actionLabels = [
        'add_tag_lead'          => __('automations.action_add_tag_lead'),
        'remove_tag_lead'       => __('automations.action_remove_tag_lead'),
        'add_tag_conversation'  => __('automations.action_add_tag_conversation'),
        'move_to_stage'         => __('automations.action_move_to_stage'),
        'set_lead_source'       => __('automations.action_set_lead_source'),
        'assign_to_user'        => __('automations.action_assign_to_user'),
        'add_note'              => __('automations.action_add_note'),
        'assign_ai_agent'       => __('automations.action_assign_ai_agent'),
        'assign_chatbot_flow'   => __('automations.action_assign_chatbot_flow'),
        'close_conversation'    => __('automations.action_close_conversation'),
        'send_whatsapp_message' => __('automations.action_send_whatsapp_message'),
        'create_task'           => __('automations.action_create_task'),
    ];
@endphp

@push('styles')
<style>
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}
.section-title    { font-size: 15px; font-weight: 700; color: #1a1d23; }
.section-subtitle { font-size: 13px; color: #9ca3af; margin-top: 3px; }

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
.trigger-badge.date  { background: #e0f2fe; color: #0369a1; }
.trigger-badge.recurring { background: #fef3c7; color: #92400e; }

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

    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">{{ __('nav.automation') ?? 'AUTOMAÇÃO' }}</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('automations.title') }}</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">{{ __('automations.subtitle') }}</p>
            </div>
            <a href="{{ route('settings.automations.create') }}" class="btn-primary-sm" style="text-decoration:none;">
                <i class="bi bi-plus-lg"></i> {{ __('automations.new_automation') }}
            </a>
        </div>
    </div>

    <div class="at-wrap">
        @if($automations->isEmpty())
            <div class="at-empty">
                <i class="bi bi-lightning-charge"></i>
                <p>{{ __('automations.empty_icon') }}<br>{!! __('automations.empty_hint') !!}</p>
            </div>
        @else
            <table class="at-table">
                <thead>
                    <tr>
                        <th>{{ __('automations.col_name') }}</th>
                        <th>{{ __('automations.col_trigger') }}</th>
                        <th>{{ __('automations.col_actions') }}</th>
                        <th style="width:110px;">{{ __('automations.col_runs') }}</th>
                        <th style="width:80px;">{{ __('automations.col_active') }}</th>
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
                                   class="btn-icon" title="{{ __('automations.btn_edit') }}">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button class="btn-icon danger" title="{{ __('automations.btn_delete') }}"
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
const AUTLANG = @json(__('automations'));

function toggleAutomation(id, cb) {
    fetch(`/configuracoes/automacoes/${id}/toggle`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
    }).then(r => r.json()).then(res => {
        if (res.success) toastr.success(res.is_active ? AUTLANG.toast_activated : AUTLANG.toast_deactivated);
        else { cb.checked = !cb.checked; toastr.error(AUTLANG.toast_error); }
    });
}
function deleteAutomation(id) {
    if (!confirm(AUTLANG.confirm_delete)) return;
    fetch(`/configuracoes/automacoes/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
    }).then(r => r.json()).then(res => {
        if (res.success) { document.getElementById(`at-row-${id}`)?.remove(); toastr.success(AUTLANG.toast_deleted); }
        else toastr.error(AUTLANG.toast_delete_error);
    });
}
</script>
@endsection
