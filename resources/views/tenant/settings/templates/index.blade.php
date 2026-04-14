@extends('tenant.layouts.app')

@php
    $title    = __('wa_templates.title');
    $pageIcon = 'chat-dots';
@endphp

@push('styles')
<style>
    .wt-table-wrap {
        background: #fff;
        border: 1px solid #e8eaf0;
        border-radius: 14px;
        overflow: hidden;
    }
    .wt-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }
    .wt-table thead th {
        padding: 11px 16px;
        font-size: 11.5px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .06em;
        background: #fafafa;
        border-bottom: 1px solid #f0f2f7;
        text-align: left;
    }
    .wt-table tbody tr { border-bottom: 1px solid #f7f8fa; }
    .wt-table tbody tr:last-child { border-bottom: none; }
    .wt-table tbody td {
        padding: 13px 16px;
        color: #374151;
        vertical-align: middle;
    }
    .wt-table tbody td.name-cell { font-weight: 600; color: #1a1d23; }
    .wt-table tbody td.name-cell a { color: inherit; text-decoration: none; }
    .wt-table tbody td.name-cell a:hover { color: #0085f3; }

    .badge-pill {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 600;
        white-space: nowrap;
    }
    .badge-cat-UTILITY        { background: #eff6ff; color: #0085f3; border: 1px solid #bfdbfe; }
    .badge-cat-MARKETING      { background: #f3e8ff; color: #9333ea; border: 1px solid #d8b4fe; }
    .badge-cat-AUTHENTICATION { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }

    .badge-st-APPROVED { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .badge-st-PENDING,
    .badge-st-IN_APPEAL { background: #fefce8; color: #ca8a04; border: 1px solid #fde68a; }
    .badge-st-REJECTED { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-st-PAUSED,
    .badge-st-DISABLED { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }

    .spinner-dot {
        width: 7px; height: 7px; border-radius: 50%;
        background: currentColor;
        animation: pulse 1.4s infinite ease-in-out both;
    }
    @keyframes pulse { 0%, 80%, 100% { opacity: .3 } 40% { opacity: 1 } }

    .btn-icon {
        width: 30px; height: 30px; border-radius: 8px;
        border: 1px solid #e8eaf0; background: #fff; color: #6b7280;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 13px; transition: all .15s;
    }
    .btn-icon:hover { background: #f0f4ff; color: #0085f3; border-color: #bfdbfe; }
    .btn-icon.danger:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

    .btn-primary-sm {
        background: #0085f3; color: #fff; border: 0;
        padding: 8px 16px; border-radius: 9px;
        font-size: 13px; font-weight: 600;
        display: inline-flex; align-items: center; gap: 7px;
        cursor: pointer; transition: background .15s;
    }
    .btn-primary-sm:hover { background: #0070d1; }

    .btn-secondary-sm {
        background: #eff6ff; color: #0085f3;
        border: 1.5px solid #bfdbfe;
        padding: 8px 14px; border-radius: 9px;
        font-size: 13px; font-weight: 600;
        display: inline-flex; align-items: center; gap: 7px;
        cursor: pointer; transition: all .15s;
    }
    .btn-secondary-sm:hover { background: #dbeafe; }
    .btn-secondary-sm:disabled { opacity: .6; cursor: not-allowed; }

    .empty-state {
        padding: 60px 30px;
        text-align: center;
        color: #6b7280;
    }
    .empty-state i { font-size: 42px; color: #cbd5e1; margin-bottom: 14px; display: block; }
    .empty-state h3 { font-size: 16px; color: #374151; margin: 0 0 6px; font-weight: 700; }
    .empty-state p { font-size: 13.5px; margin: 0 auto 18px; max-width: 380px; }

    .rejected-row-detail {
        background: #fef2f2;
        padding: 11px 18px;
        border-top: 1px dashed #fecaca;
        font-size: 12.5px;
        color: #991b1b;
    }
</style>
@endpush

@section('content')
<div class="page-container">

    @include('tenant.settings._tabs')

    <div style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('wa_templates.title') }}</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">{{ __('wa_templates.subtitle') }}</p>
            </div>
            <div style="display:flex;gap:8px;">
                <button id="btnSync" class="btn-secondary-sm" onclick="syncTemplates()">
                    <i class="bi bi-arrow-clockwise"></i> <span>{{ __('wa_templates.sync') }}</span>
                </button>
                <a href="{{ route('settings.whatsapp-templates.create') }}" class="btn-primary-sm">
                    <i class="bi bi-plus-lg"></i> {{ __('wa_templates.create') }}
                </a>
            </div>
        </div>
    </div>

    <div class="wt-table-wrap">
        @if($templates->isEmpty())
            <div class="empty-state">
                <i class="bi bi-chat-square-dots"></i>
                <h3>{{ __('wa_templates.empty_title') }}</h3>
                <p>{{ __('wa_templates.empty_body') }}</p>
                <a href="{{ route('settings.whatsapp-templates.create') }}" class="btn-primary-sm" style="display:inline-flex;">
                    <i class="bi bi-plus-lg"></i> {{ __('wa_templates.create') }}
                </a>
            </div>
        @else
            <table class="wt-table">
                <thead>
                    <tr>
                        <th>{{ __('wa_templates.col_name') }}</th>
                        @if($instances->count() > 1)
                            <th>{{ __('wa_templates.col_instance') }}</th>
                        @endif
                        <th style="width:90px;">{{ __('wa_templates.col_language') }}</th>
                        <th style="width:130px;">{{ __('wa_templates.col_category') }}</th>
                        <th style="width:130px;">{{ __('wa_templates.col_status') }}</th>
                        <th style="width:140px;">{{ __('wa_templates.col_last_sync') }}</th>
                        <th style="width:90px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($templates as $t)
                        <tr>
                            <td class="name-cell">
                                <a href="{{ route('settings.whatsapp-templates.show', $t) }}">{{ $t->name }}</a>
                            </td>
                            @if($instances->count() > 1)
                                <td>{{ $t->instance?->label ?: $t->instance?->phone_number }}</td>
                            @endif
                            <td style="color:#6b7280;font-family:monospace;font-size:12px;">{{ $t->language }}</td>
                            <td>
                                <span class="badge-pill badge-cat-{{ $t->category }}">
                                    @switch($t->category)
                                        @case('UTILITY')        {{ __('wa_templates.cat_utility') }} @break
                                        @case('MARKETING')      {{ __('wa_templates.cat_marketing') }} @break
                                        @case('AUTHENTICATION') {{ __('wa_templates.cat_authentication') }} @break
                                    @endswitch
                                </span>
                            </td>
                            <td>
                                <span class="badge-pill badge-st-{{ $t->status }}">
                                    @if($t->isPending())<span class="spinner-dot"></span>@endif
                                    @switch($t->status)
                                        @case('APPROVED')   {{ __('wa_templates.status_approved') }} @break
                                        @case('PENDING')    {{ __('wa_templates.status_pending') }} @break
                                        @case('IN_APPEAL')  {{ __('wa_templates.status_in_appeal') }} @break
                                        @case('REJECTED')   {{ __('wa_templates.status_rejected') }} @break
                                        @case('PAUSED')     {{ __('wa_templates.status_paused') }} @break
                                        @case('DISABLED')   {{ __('wa_templates.status_disabled') }} @break
                                        @default            {{ $t->status }}
                                    @endswitch
                                </span>
                            </td>
                            <td style="color:#9ca3af;font-size:12px;">
                                {{ $t->last_synced_at?->diffForHumans() ?? '—' }}
                            </td>
                            <td>
                                <div style="display:flex;gap:5px;justify-content:flex-end;">
                                    <a href="{{ route('settings.whatsapp-templates.show', $t) }}" class="btn-icon" title="Ver">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form method="POST" action="{{ route('settings.whatsapp-templates.destroy', $t) }}"
                                          onsubmit="return confirm('{{ __('wa_templates.show_delete_confirm') }}');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon danger" title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @if($t->isRejected() && $t->rejected_reason)
                            <tr>
                                <td colspan="{{ $instances->count() > 1 ? 7 : 6 }}" class="rejected-row-detail">
                                    <strong>{{ __('wa_templates.show_rejected_reason') }}:</strong> {{ $t->rejected_reason }}
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<script>
async function syncTemplates() {
    const btn = document.getElementById('btnSync');
    const label = btn.querySelector('span');
    const originalText = label.textContent;
    btn.disabled = true;
    label.textContent = @json(__('wa_templates.sync_running'));

    try {
        const res = await window.API.post(@json(route('settings.whatsapp-templates.sync')), {});
        if (res.success) {
            toastr.success(@json(__('wa_templates.toast_sync_done')) +
                ` (+${res.totals.created} ~${res.totals.updated} -${res.totals.removed})`);
            setTimeout(() => location.reload(), 800);
        } else {
            const msg = (res.errors || []).join(' | ') || 'erro';
            toastr.error(@json(__('wa_templates.toast_sync_error', ['msg' => '%s'])).replace('%s', msg));
        }
    } catch (e) {
        toastr.error(@json(__('wa_templates.toast_sync_error', ['msg' => ''])) + (e.message || ''));
    } finally {
        btn.disabled = false;
        label.textContent = originalText;
    }
}
</script>
@endsection
