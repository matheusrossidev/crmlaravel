@extends('tenant.layouts.app')

@php
    $title    = __('audit.title');
    $pageIcon = 'shield-check';
@endphp

@push('styles')
<style>
.audit-filters { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 20px; }
.audit-filters .fg { margin: 0; min-width: 140px; }
.audit-filters .fg label { font-size: 11px; font-weight: 600; color: #97A3B7; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 3px; display: block; }
.audit-filters .fg select,
.audit-filters .fg input { padding: 8px 10px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 12.5px; font-family: inherit; outline: none; width: 100%; background: #fff; color: #1a1d23; }
.audit-filters .fg select:focus,
.audit-filters .fg input:focus { border-color: #0085f3; }

.audit-tbl-wrap { background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.audit-tbl { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 700px; }
.audit-tbl th { text-align: left; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #97A3B7; text-transform: uppercase; letter-spacing: .04em; background: #f8fafc; border-bottom: 1px solid #e8eaf0; white-space: nowrap; }
.audit-tbl td { padding: 12px 16px; border-top: 1px solid #f3f4f6; vertical-align: top; }
.audit-tbl tr:hover td { background: #fafbfc; }

.audit-action { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 99px; display: inline-block; white-space: nowrap; }
.audit-action.created { background: #ecfdf5; color: #065f46; }
.audit-action.updated { background: #eff6ff; color: #1e40af; }
.audit-action.deleted { background: #fef2f2; color: #991b1b; }
.audit-action.login { background: #f3f4f6; color: #374151; }
.audit-action.logout { background: #f3f4f6; color: #6b7280; }
.audit-action.login_failed { background: #fef2f2; color: #dc2626; }
.audit-action.password_reset { background: #fffbeb; color: #92400e; }

.audit-entity { font-size: 12px; color: #374151; }
.audit-entity strong { color: #1a1d23; font-weight: 600; }
.audit-entity .eid { color: #97A3B7; font-size: 11px; }

.audit-desc { font-size: 12px; color: #6b7280; max-width: 320px; line-height: 1.5; }
.audit-desc .changed { color: #1a1d23; font-weight: 500; }

.audit-view-btn { width: 28px; height: 28px; border-radius: 8px; background: #eff6ff; color: #0085f3; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; transition: background .12s; }
.audit-view-btn:hover { background: #dbeafe; }

.audit-time { font-size: 12px; color: #97A3B7; white-space: nowrap; }
.audit-user { font-size: 13px; font-weight: 600; color: #1a1d23; }

/* Pagination */
.audit-pag { display: flex; justify-content: center; gap: 4px; margin-top: 16px; }
.audit-pag a, .audit-pag span { padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; color: #374151; border: 1px solid #e5e7eb; }
.audit-pag a:hover { background: #f3f4f6; }
.audit-pag .active span { background: #0085f3; color: #fff; border-color: #0085f3; }
.audit-pag .disabled span { color: #d1d5db; }

/* Detail drawer */
.page-drawer-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.3); z-index: 5000; }
.page-drawer-overlay.open { display: block; }
.page-drawer { position: fixed; top: 0; right: -520px; width: 480px; height: 100vh; background: #fff; z-index: 5001; box-shadow: -4px 0 24px rgba(0,0,0,.1); display: flex; flex-direction: column; transition: right .25s cubic-bezier(.4,0,.2,1); }
.page-drawer.open { right: 0; }
@media (max-width: 540px) { .page-drawer { width: 100%; right: -100%; } }
.dw-header { padding: 18px 24px; border-bottom: 1px solid #f0f2f7; display: flex; align-items: center; justify-content: space-between; }
.dw-header h3 { margin: 0; font-size: 15px; font-weight: 700; color: #1a1d23; }
.dw-body { flex: 1; overflow-y: auto; padding: 24px; }

.detail-row { margin-bottom: 16px; }
.detail-label { font-size: 11px; font-weight: 600; color: #97A3B7; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 3px; }
.detail-value { font-size: 13px; color: #1a1d23; }

.diff-section { margin-top: 20px; }
.diff-title { font-size: 12px; font-weight: 700; color: #1a1d23; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
.diff-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.diff-table th { text-align: left; padding: 8px 10px; background: #f8fafc; color: #97A3B7; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.diff-table td { padding: 8px 10px; border-top: 1px solid #f3f4f6; word-break: break-word; }
.diff-table .field { font-weight: 600; color: #374151; width: 120px; }
.diff-old { color: #dc2626; background: #fef2f2; border-radius: 4px; padding: 2px 6px; }
.diff-new { color: #059669; background: #ecfdf5; border-radius: 4px; padding: 2px 6px; }
.diff-added { background: #ecfdf5; }
.diff-removed { background: #fef2f2; }

.audit-empty { padding: 60px 24px; text-align: center; background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; }
.audit-empty i { font-size: 40px; color: #d1d5db; display: block; margin-bottom: 14px; }
.audit-empty .t { font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 4px; }
.audit-empty .s { font-size: 13px; color: #97A3B7; }
</style>
@endpush

@section('content')
<div class="page-container">

    @include('tenant.settings._tabs')

    {{-- Header --}}
    <div style="margin-bottom:24px;">
        <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:20px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('audit.page_title') }}</h1>
        <p style="font-size:13px;color:#677489;margin:0;">{{ __('audit.page_desc') }}</p>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('settings.audit-log') }}" class="audit-filters">
        <div class="fg">
            <label>{{ __('audit.user') }}</label>
            <select name="user_id">
                <option value="">{{ __('audit.all') }}</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fg">
            <label>{{ __('audit.entity') }}</label>
            <select name="entity_type">
                <option value="">{{ __('audit.all_f') }}</option>
                @foreach($entityTypes as $key => $label)
                    <option value="{{ $key }}" {{ request('entity_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="fg">
            <label>{{ __('audit.action') }}</label>
            <select name="action">
                <option value="">{{ __('audit.all_f') }}</option>
                @foreach($actionLabels as $key => $label)
                    <option value="{{ $key }}" {{ request('action') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="fg">
            <label>{{ __('audit.from') }}</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}">
        </div>
        <div class="fg">
            <label>{{ __('audit.to') }}</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}">
        </div>
        <button type="submit" class="btn-primary-sm" style="height:36px;"><i class="bi bi-funnel"></i> {{ __('audit.filter') }}</button>
        @if(request()->hasAny(['user_id','entity_type','action','date_from','date_to']))
            <a href="{{ route('settings.audit-log') }}" style="font-size:12px;color:#6b7280;text-decoration:none;padding:8px 0;">{{ __('audit.clear') }}</a>
        @endif
    </form>

    {{-- Table --}}
    @if($logs->isEmpty())
        <div class="audit-empty">
            <i class="bi bi-shield-check"></i>
            <div class="t">{{ __('audit.no_records') }}</div>
            <div class="s">{{ __('audit.records_auto') }}</div>
        </div>
    @else
        <div class="audit-tbl-wrap">
            <table class="audit-tbl">
                <thead>
                    <tr>
                        <th>{{ __('audit.date') }}</th>
                        <th>{{ __('audit.user') }}</th>
                        <th>{{ __('audit.action') }}</th>
                        <th>{{ __('audit.entity') }}</th>
                        <th>{{ __('audit.changes') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        @php
                            $actionClass = $log->action;
                            $actionLabel = $actionLabels[$log->action] ?? $log->action;
                            $entityLabel = $entityTypes[$log->entity_type] ?? $log->entity_type;
                            $desc = $log->human_desc ?? '';
                        @endphp
                        <tr>
                            <td class="audit-time">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="audit-user">{{ $log->user?->name ?? __('audit.system') }}</td>
                            <td><span class="audit-action {{ $actionClass }}">{{ $actionLabel }}</span></td>
                            <td class="audit-entity">
                                <strong>{{ $entityLabel }}</strong>
                                @if($log->entity_id) <span class="eid">#{{ $log->entity_id }}</span> @endif
                            </td>
                            <td class="audit-desc">{!! $desc !!}</td>
                            <td>
                                @if($log->action !== 'login' && $log->action !== 'logout')
                                    <button class="audit-view-btn" onclick="showDetail({{ $log->id }})" title="{{ __('audit.view_details') }}"><i class="bi bi-eye"></i></button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="audit-pag">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

{{-- Detail Drawer --}}
<div class="page-drawer-overlay" id="detailOverlay" onclick="closeDetail()"></div>
<div class="page-drawer" id="detailDrawer">
    <div class="dw-header">
        <h3>{{ __('audit.detail_title') }}</h3>
        <button onclick="closeDetail()" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="dw-body" id="detailBody">
        <div style="text-align:center;padding:40px;color:#97A3B7;">{{ __('audit.loading') }}</div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const ALANG = @json(__('audit'));

function showDetail(id) {
    document.getElementById('detailOverlay').classList.add('open');
    document.getElementById('detailDrawer').classList.add('open');
    document.getElementById('detailBody').innerHTML = '<div style="text-align:center;padding:40px;color:#97A3B7;"><i class="bi bi-hourglass-split" style="font-size:20px;"></i><p style="margin-top:8px;">' + ALANG.loading + '</p></div>';

    fetch('{{ route("settings.audit-log.show", "__ID__") }}'.replace('__ID__', id), {
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { document.getElementById('detailBody').innerHTML = '<p>' + ALANG.load_error + '</p>'; return; }
        const l = data.log;
        let html = '';

        // Meta info
        html += `<div class="detail-row"><div class="detail-label">${esc(ALANG.user)}</div><div class="detail-value">${esc(l.user)}</div></div>`;
        html += `<div class="detail-row"><div class="detail-label">${esc(ALANG.action)}</div><div class="detail-value">${esc(l.action)}</div></div>`;
        html += `<div class="detail-row"><div class="detail-label">${esc(ALANG.entity)}</div><div class="detail-value">${esc(l.entity_type)} ${l.entity_id ? '#' + l.entity_id : ''}</div></div>`;
        html += `<div class="detail-row"><div class="detail-label">${esc(ALANG.date)}</div><div class="detail-value">${esc(l.created_at)}</div></div>`;
        if (l.ip_address) html += `<div class="detail-row"><div class="detail-label">${esc(ALANG.ip)}</div><div class="detail-value">${esc(l.ip_address)}</div></div>`;
        if (l.user_agent) html += `<div class="detail-row"><div class="detail-label">${esc(ALANG.user_agent)}</div><div class="detail-value" style="font-size:11px;color:#6b7280;word-break:break-all;">${esc(l.user_agent)}</div></div>`;

        // Diff table
        const oldD = l.old_data || {};
        const newD = l.new_data || {};
        const allKeys = [...new Set([...Object.keys(oldD), ...Object.keys(newD)])];

        if (allKeys.length > 0) {
            html += '<div class="diff-section"><div class="diff-title"><i class="bi bi-arrow-left-right" style="color:#0085f3;"></i> ' + esc(ALANG.diff_title) + '</div>';
            html += '<table class="diff-table"><thead><tr><th>' + esc(ALANG.field) + '</th><th>' + esc(ALANG.before) + '</th><th>' + esc(ALANG.after) + '</th></tr></thead><tbody>';
            for (const key of allKeys) {
                const ov = oldD[key] !== undefined ? fmt(oldD[key]) : '';
                const nv = newD[key] !== undefined ? fmt(newD[key]) : '';
                const added = ov === '' && nv !== '';
                const removed = nv === '' && ov !== '';
                html += `<tr class="${added ? 'diff-added' : ''} ${removed ? 'diff-removed' : ''}">`;
                html += `<td class="field">${esc(key)}</td>`;
                html += `<td>${ov ? '<span class="diff-old">' + esc(ov) + '</span>' : '<span style="color:#d1d5db;">—</span>'}</td>`;
                html += `<td>${nv ? '<span class="diff-new">' + esc(nv) + '</span>' : '<span style="color:#d1d5db;">—</span>'}</td>`;
                html += '</tr>';
            }
            html += '</tbody></table></div>';
        }

        document.getElementById('detailBody').innerHTML = html;
    })
    .catch(() => {
        document.getElementById('detailBody').innerHTML = '<p style="color:#ef4444;">' + ALANG.load_error + '</p>';
    });
}

function closeDetail() {
    document.getElementById('detailOverlay').classList.remove('open');
    document.getElementById('detailDrawer').classList.remove('open');
}

function esc(s) {
    if (s === null || s === undefined) return '';
    const d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
}

function fmt(v) {
    if (v === null || v === undefined) return '';
    if (typeof v === 'object') return JSON.stringify(v);
    return String(v);
}
</script>
@endpush
