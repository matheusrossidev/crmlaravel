@extends('tenant.layouts.app')

@php
    $title = __('sequences.title');
    $pageIcon = 'arrow-repeat';
@endphp

@push('styles')
<style>
    .seq-table-wrap { background: #fff; border: 1px solid #e8eaf0; border-radius: 12px; overflow: hidden; }
    .seq-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
    .seq-table thead th {
        padding: 11px 16px; font-size: 11.5px; font-weight: 700; color: #9ca3af;
        text-transform: uppercase; letter-spacing: .06em; background: #fafafa;
        border-bottom: 1px solid #f0f2f7;
    }
    .seq-table tbody tr { border-bottom: 1px solid #f7f8fa; transition: background .1s; }
    .seq-table tbody tr:last-child { border-bottom: none; }
    .seq-table tbody tr:hover { background: #fafbfc; }
    .seq-table tbody td { padding: 12px 16px; color: #374151; vertical-align: middle; }

    .stat-chip {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 10px; border-radius: 100px; font-size: 12px; font-weight: 600;
    }
    .stat-chip.steps    { background: #f0f4ff; color: #3b82f6; }
    .stat-chip.enrolled { background: #eff6ff; color: #2563eb; }
    .stat-chip.done     { background: #ecfdf5; color: #059669; }
    .stat-chip.active   { background: #fef3c7; color: #d97706; }

    .toggle { position: relative; display: inline-block; width: 36px; height: 20px; }
    .toggle input { display: none; }
    .toggle-slider {
        position: absolute; inset: 0; background: #d1d5db;
        border-radius: 99px; cursor: pointer; transition: .2s;
    }
    .toggle-slider::before {
        content: ''; position: absolute;
        width: 14px; height: 14px; left: 3px; bottom: 3px;
        background: #fff; border-radius: 50%; transition: .2s;
    }
    .toggle input:checked + .toggle-slider { background: #10B981; }
    .toggle input:checked + .toggle-slider::before { transform: translateX(16px); }

    .btn-icon {
        width: 28px; height: 28px; border-radius: 7px; border: 1px solid #e8eaf0;
        background: #fff; color: #6b7280;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 13px; transition: all .15s;
    }
    .btn-icon:hover { background: #f0f4ff; color: #374151; }
    .btn-icon.danger:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

    .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
    .section-title  { font-size: 15px; font-weight: 700; color: #1a1d23; }
    .section-subtitle { font-size: 13px; color: #6b7280; margin-top: 2px; }

    .empty-state { text-align: center; padding: 48px 24px; }
    .empty-state i { font-size: 40px; color: #d1d5db; display: block; margin-bottom: 12px; }
    .empty-state p { color: #9ca3af; font-size: 13.5px; margin: 0; }
    .empty-state .sub { font-size: 12.5px; margin-top: 4px; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">{{ __('nav.automation') ?? 'AUTOMAÇÃO' }}</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('sequences.title') }}</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">{{ __('sequences.subtitle') }}</p>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <button type="button" class="btn-templates-trigger" onclick="openTplLibrary('tplSequenceLibrary')">
                    <i class="bi bi-collection"></i> {{ __('templates.btn_templates') }}
                </button>
                <a href="{{ route('settings.sequences.create') }}" class="btn-primary-sm" style="text-decoration:none;">
                    <i class="bi bi-plus-lg"></i> {{ __('sequences.new') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Modal de biblioteca de templates --}}
    @include('tenant.settings._template_library_modal', [
        'modalId'      => 'tplSequenceLibrary',
        'title'        => __('templates.sequence_modal_title'),
        'subtitle'     => __('templates.sequence_modal_subtitle'),
        'templates'    => $templates,
        'categories'   => $templateCategories,
        'installRoute' => 'settings.sequences.templates.install',
        'onInstallJs'  => 'onSequenceTemplateInstalled',
        'installedKey' => 'sequence',
    ])

    {{-- JS de integração com a biblioteca de templates --}}
    <script>
    window.tplLibraryRoutes = window.tplLibraryRoutes || {};
    window.tplLibraryRoutes['tplSequenceLibrary'] = @json(route('settings.sequences.templates.install', ['slug' => '__SLUG__']));

    window.onSequenceTemplateInstalled = function(sequence) {
        if (!sequence) return;
        // Recarrega a página pra mostrar a nova sequência
        setTimeout(() => location.reload(), 600);
    };
    </script>

    <div class="seq-table-wrap">
        <table class="seq-table">
            <thead>
                <tr>
                    <th>{{ __('sequences.col_name') }}</th>
                    <th style="text-align:center;">{{ __('sequences.col_steps') }}</th>
                    <th style="text-align:center;">{{ __('sequences.col_active') }}</th>
                    <th style="text-align:center;">{{ __('sequences.col_enrolled') }}</th>
                    <th style="text-align:center;">{{ __('sequences.col_completed') }}</th>
                    <th style="text-align:center;width:80px;">{{ __('sequences.col_status') }}</th>
                    <th style="width:80px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sequences as $seq)
                <tr data-seq-id="{{ $seq->id }}">
                    <td>
                        <div style="font-weight:600;color:#1a1d23;">{{ $seq->name }}</div>
                        @if($seq->description)
                        <div style="font-size:12px;color:#9ca3af;margin-top:2px;">{{ $seq->description }}</div>
                        @endif
                    </td>
                    <td style="text-align:center;"><span class="stat-chip steps">{{ $seq->steps_count }}</span></td>
                    <td style="text-align:center;"><span class="stat-chip active">{{ $seq->active_count }}</span></td>
                    <td style="text-align:center;"><span class="stat-chip enrolled">{{ $seq->stats_enrolled }}</span></td>
                    <td style="text-align:center;"><span class="stat-chip done">{{ $seq->stats_completed }}</span></td>
                    <td style="text-align:center;">
                        <label class="toggle">
                            <input type="checkbox" {{ $seq->is_active ? 'checked' : '' }}
                                   onchange="toggleSeq({{ $seq->id }}, this)">
                            <span class="toggle-slider"></span>
                        </label>
                    </td>
                    <td>
                        <div style="display:flex;gap:5px;justify-content:flex-end;">
                            <a href="{{ route('settings.sequences.edit', $seq) }}" class="btn-icon">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button class="btn-icon danger" onclick="deleteSeq({{ $seq->id }}, this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="bi bi-arrow-repeat"></i>
                            <p>{{ __('sequences.no_sequences') }}</p>
                            <p class="sub">{{ __('sequences.no_sequences_sub') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection

@push('scripts')
<script>
const SLANG = {!! json_encode(__('sequences')) !!};
const TOGGLE_URL = {!! json_encode(route('settings.sequences.toggle', ['sequence' => '__ID__'])) !!};
const DELETE_URL = {!! json_encode(route('settings.sequences.destroy', ['sequence' => '__ID__'])) !!};
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;

async function toggleSeq(id, cb) {
    const res = await fetch(TOGGLE_URL.replace('__ID__', id), {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    const data = await res.json();
    if (data.success) {
        toastr.success(data.is_active ? SLANG.toast_toggled_on : SLANG.toast_toggled_off);
    }
}

function deleteSeq(id, btn) {
    confirmAction({
        title: SLANG.confirm_delete_title,
        message: SLANG.confirm_delete_msg,
        confirmText: SLANG.confirm_delete_btn,
        onConfirm: async () => {
            const res = await fetch(DELETE_URL.replace('__ID__', id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                btn.closest('tr').remove();
                toastr.success(SLANG.toast_deleted);
            }
        },
    });
}
</script>
@endpush
