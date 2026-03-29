@extends('tenant.layouts.app')

@php
    $title = __('leads.contacts_title');
    $pageIcon = 'people';
@endphp

{{-- topbar_actions removido — botões movidos para page header --}}

@push('styles')
<style>
    .leads-table-wrap {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
    }

    .leads-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }

    .leads-table thead th {
        padding: 12px 16px;
        font-size: 11.5px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .06em;
        border-bottom: 1px solid #f0f2f7;
        background: #fafafa;
        white-space: nowrap;
    }

    .leads-table tbody tr {
        border-bottom: 1px solid #f7f8fa;
        transition: background .12s;
        cursor: pointer;
    }

    .leads-table tbody tr:hover { background: #f8faff; }
    .leads-table tbody tr:last-child { border-bottom: none; }

    .leads-table tbody td {
        padding: 12px 16px;
        color: #374151;
        vertical-align: middle;
    }

    .lead-name-cell {
        font-weight: 600;
        color: #1a1d23;
        position: relative;
    }

    .lead-name-cell small {
        display: block;
        font-weight: 400;
        color: #9ca3af;
        font-size: 12px;
    }

    .stage-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11.5px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 99px;
        white-space: nowrap;
    }

    .stage-badge .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .source-pill {
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 99px;
        background: #f0f2f7;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .value-cell {
        font-weight: 700;
        color: #10B981;
        white-space: nowrap;
    }

    .empty-table {
        text-align: center;
        padding: 60px 20px;
        color: #9ca3af;
    }

    .empty-table i { font-size: 48px; opacity: .3; margin-bottom: 12px; }
    .empty-table p { font-size: 14px; margin: 0; }

    /* Paginação */
    .pagination-wrap {
        padding: 16px 22px;
        border-top: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 13px;
        color: #6b7280;
    }

    .pagination-wrap nav .pagination {
        display: flex;
        gap: 4px;
        margin: 0;
        list-style: none;
        padding: 0;
    }

    .pagination-wrap nav .pagination .page-item .page-link {
        padding: 5px 10px;
        border: 1px solid #e8eaf0;
        border-radius: 7px;
        font-size: 13px;
        color: #374151;
        text-decoration: none;
        transition: all .15s;
    }

    .pagination-wrap nav .pagination .page-item.active .page-link {
        background: #0085f3;
        border-color: #0085f3;
        color: #fff;
    }

    .pagination-wrap nav .pagination .page-item .page-link:hover {
        background: #f0f4ff;
        border-color: #dbeafe;
    }

    /* Esconder texto "Showing X to Y of Z results" do Laravel */
    .pagination-wrap nav p.text-muted,
    .pagination-wrap nav p.small {
        display: none !important;
    }

    .btn-secondary-sm {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 7px 14px;
        border: 1.5px solid #e8eaf0;
        border-radius: 100px;
        background: #fff;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        transition: all .15s;
        font-family: inherit;
    }
    .btn-secondary-sm:hover { background: #f0f4ff; border-color: #dbeafe; color: #0085f3; }

    .resp-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 10.5px;
        font-weight: 600;
        color: #6b7280;
        background: #f0f2f7;
        border-radius: 99px;
        padding: 2px 7px;
        margin-top: 4px;
    }

    .filter-select {
        padding: 7px 10px;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        font-size: 13px;
        font-family: inherit;
        outline: none;
        background: #fafafa;
        color: #374151;
        cursor: pointer;
    }
    .filter-select:focus { border-color: #3B82F6; background: #fff; }

    .fab-novo-lead {
        display: none;
        position: fixed; bottom: 80px; right: 20px; z-index: 90;
        width: 52px; height: 52px; border-radius: 50%;
        background: #0085f3; color: #fff; border: none;
        align-items: center; justify-content: center;
        font-size: 22px; cursor: pointer;
        box-shadow: 0 4px 14px rgba(0,133,243,.4);
    }

    /* ── Mobile ── */
    @media (max-width: 768px) {
        .leads-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .leads-table { min-width: 700px; }
        #searchInput { width: 120px !important; font-size: 12px !important; padding: 6px 10px 6px 28px !important; }
        .leads-hide-mobile { display: none !important; }
        .fab-novo-lead { display: flex; }

        /* Primeira coluna fixa */
        .leads-table thead th:first-child,
        .leads-table tbody td:first-child {
            position: sticky;
            left: 0;
            z-index: 2;
            background: #fff;
            min-width: 160px;
            max-width: 200px;
        }
        .leads-table thead th:first-child {
            background: #fafafa;
            z-index: 3;
        }
        .leads-table tbody tr:hover td:first-child {
            background: #f8faff;
        }
        /* Sombra na borda da coluna fixa */
        .leads-table thead th:first-child::after,
        .leads-table tbody td:first-child::after {
            content: '';
            position: absolute;
            top: 0;
            right: -6px;
            bottom: 0;
            width: 6px;
            background: linear-gradient(to right, rgba(0,0,0,.06), transparent);
            pointer-events: none;
        }
    }
    @media (max-width: 480px) {
        .leads-table { min-width: 500px; }
        #searchInput { width: 100px !important; }
    }
</style>
@endpush

@section('content')
<div class="page-container">

    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:8px;">
            <i class="bi bi-people" style="color:#3B82F6;font-size:16px;"></i>
            <span style="font-size:15px;font-weight:700;color:#1a1d23;">{{ __('leads.contacts_title') }}</span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;margin-left:auto;flex-wrap:wrap;">
            <form method="GET" action="{{ route('leads.index') }}" id="filterForm" style="display:flex;align-items:center;gap:6px;">
                <div style="position:relative;">
                    <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:13px;"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="{{ __('leads.search') }}" style="padding:6px 12px 6px 30px;border:1.5px solid #e8eaf0;border-radius:9px;font-size:12px;font-family:inherit;outline:none;width:180px;background:#fafafa;"
                           id="searchInput">
                </div>
                <select name="assigned_to" class="filter-select leads-hide-mobile" onchange="this.form.submit()" title="Filtrar por responsável"
                        style="padding:6px 10px;border:1.5px solid #e8eaf0;border-radius:9px;font-size:12px;background:#fafafa;color:#374151;cursor:pointer;">
                    <option value="">{{ __('leads.responsible_filter') }}</option>
                    <option value="ai" {{ request('assigned_to') === 'ai' ? 'selected' : '' }}>{{ __('leads.ai_agent_filter') }}</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('assigned_to') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('leads.export') }}" class="btn-secondary-sm leads-hide-mobile" style="display:flex;align-items:center;gap:5px;text-decoration:none;font-size:12px;padding:6px 12px;">
                <i class="bi bi-download"></i> {{ __('leads.export') }}
            </a>
            <button class="btn-secondary-sm leads-hide-mobile" id="btnImportLead" style="display:flex;align-items:center;gap:5px;font-size:12px;padding:6px 12px;" {{ auth()->user()->isViewer() ? 'disabled' : '' }}>
                <i class="bi bi-upload"></i> {{ __('leads.import') }}
            </button>
            <button class="btn-primary-sm leads-hide-mobile" id="btnNovoLead" style="font-size:12px;padding:6px 14px;" {{ auth()->user()->isViewer() ? 'disabled style=opacity:.5;pointer-events:none;' : '' }}>
                <i class="bi bi-plus-lg"></i> {{ __('leads.new_lead') }}
            </button>
        </div>
    </div>

    <div class="leads-table-wrap">
        <table class="leads-table">
            <thead>
                <tr>
                    <th>{{ __('leads.col_name') }}</th>
                    <th>{{ __('leads.col_phone') }}</th>
                    <th>{{ __('leads.col_email') }}</th>
                    <th>{{ __('leads.col_stage') }}</th>
                    <th>{{ __('leads.col_value') }}</th>
                    <th style="text-align:center;">{{ __('scoring.score_label') }}</th>
                    <th>{{ __('leads.col_source') }}</th>
                    <th>{{ __('leads.col_campaign') }}</th>
                    <th>{{ __('leads.col_created') }}</th>
                </tr>
            </thead>
            <tbody id="leadsTableBody">
                @forelse($leads as $lead)
                <tr class="lead-row" data-lead-id="{{ $lead->id }}">
                    <td class="lead-name-cell">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:32px;height:32px;border-radius:50%;flex-shrink:0;background:#e0ecff;color:#0085f3;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                                @if($lead->whatsappConversation?->contact_picture_url)
                                <img src="{{ $lead->whatsappConversation->contact_picture_url }}" alt="" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.parentElement.textContent='{{ strtoupper(substr($lead->name,0,1)) }}';">
                                @else
                                {{ strtoupper(substr($lead->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $lead->name)[1] ?? '', 0, 1)) }}
                                @endif
                            </div>
                            <span>{{ $lead->name }}</span>
                            <a href="{{ route('leads.profile', $lead) }}"
                               title="{{ __('leads.view_profile') }}"
                               onclick="event.stopPropagation();"
                               style="color:#d1d5db;font-size:12px;flex-shrink:0;line-height:1;transition:color .15s;"
                               onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='#d1d5db'">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </div>
                        @if($lead->pipeline)
                        <small>{{ $lead->pipeline->name }}</small>
                        @endif
                        @if($lead->assignedTo?->name ?? $lead->whatsappConversation?->aiAgent?->name)
                        <span class="resp-badge">
                            <i class="bi bi-person-fill"></i> {{ Str::limit($lead->assignedTo?->name ?? $lead->whatsappConversation?->aiAgent?->name, 18) }}
                        </span>
                        @endif
                    </td>
                    <td>
                        @if($lead->phone)
                        <a href="{{ whatsappUrl($lead->phone) }}" target="_blank" rel="noopener"
                           style="color:inherit;text-decoration:none;white-space:nowrap;">
                            {{ formatBrPhone($lead->phone) }}
                        </a>
                        @else
                        —
                        @endif
                    </td>
                    <td>{{ $lead->email ?? '—' }}</td>
                    <td>
                        @if($lead->stage)
                        <span class="stage-badge" style="background: {{ $lead->stage->color }}22; color: {{ $lead->stage->color }};">
                            <span class="dot" style="background: {{ $lead->stage->color }};"></span>
                            {{ $lead->stage->name }}
                        </span>
                        @else
                        —
                        @endif
                    </td>
                    <td class="value-cell">
                        {{ $lead->value ? __('common.currency') . ' ' . number_format((float)$lead->value, 2, __('common.decimal_sep'), __('common.thousands_sep')) : '—' }}
                    </td>
                    <td style="text-align:center;">
                        @if($lead->score > 0)
                        @php
                            $sCls = $lead->score >= 70 ? 'hot' : ($lead->score >= 30 ? 'warm' : 'cold');
                            $sColors = ['hot' => ['#ecfdf5','#059669'], 'warm' => ['#fffbeb','#d97706'], 'cold' => ['#f3f4f6','#9ca3af']];
                        @endphp
                        <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 10px;border-radius:100px;font-size:12px;font-weight:700;background:{{ $sColors[$sCls][0] }};color:{{ $sColors[$sCls][1] }};">
                            <i class="bi bi-lightning-fill" style="font-size:10px;"></i> {{ $lead->score }}
                        </span>
                        @else
                        <span style="color:#d1d5db;">—</span>
                        @endif
                    </td>
                    <td><span class="source-pill">{{ $lead->source ?? 'manual' }}</span></td>
                    <td>{{ $lead->campaign?->name ?? '—' }}</td>
                    <td style="white-space:nowrap;color:#9ca3af;">{{ $lead->created_at->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9">
                        <div class="empty-table">
                            <div><i class="bi bi-people"></i></div>
                            <p>{{ __('leads.no_leads') }}<br>
                                <a href="#" id="emptyAddLead" style="color:#3B82F6;">{{ __('leads.add_first_lead') }}</a>
                            </p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($leads->hasPages())
        <div class="pagination-wrap">
            <span>{{ $leads->firstItem() }}–{{ $leads->lastItem() }} {{ __('leads.of_leads', ['total' => $leads->total()]) }}</span>
            {{ $leads->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>

</div>

{{-- Drawer compartilhado --}}
@include('tenant.leads._drawer', ['pipelines' => $pipelines, 'customFieldDefs' => $customFieldDefs])

{{-- Modal Import --}}
<div id="importModalOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:500;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px;width:440px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.18);">
        <div style="font-size:16px;font-weight:700;color:#1a1d23;margin-bottom:6px;">{{ __('leads.import_title') }}</div>
        <p style="font-size:13px;color:#6b7280;margin-bottom:18px;">
            {!! __('leads.import_desc') !!}<br>
            <code style="font-size:12px;background:#f4f6fb;padding:2px 6px;border-radius:5px;">{{ __('leads.import_columns') }}</code>
        </p>
        <form id="importForm" enctype="multipart/form-data">
            <input type="file" id="importFile" name="file" accept=".xlsx,.xls,.csv"
                   style="width:100%;padding:10px;border:1.5px dashed #e8eaf0;border-radius:9px;font-size:13px;background:#fafafa;cursor:pointer;box-sizing:border-box;">
        </form>
        <div id="importResult" style="display:none;margin-top:14px;padding:10px 14px;border-radius:9px;font-size:13px;"></div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px;">
            <button onclick="closeImportModal()" style="padding:8px 18px;border-radius:100px;border:1.5px solid #e8eaf0;background:#fff;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;">{{ __('leads.cancel') }}</button>
            <button id="btnDoImport" onclick="doImport()" style="padding:8px 20px;border-radius:100px;border:none;background:#0085f3;color:#fff;font-size:13px;font-weight:600;cursor:pointer;">{{ __('leads.import_btn') }}</button>
        </div>
    </div>
</div>

<button class="fab-novo-lead" id="fabNovoLead" title="{{ __('leads.new_lead') }}" {{ auth()->user()->isViewer() ? 'disabled style=opacity:.5;pointer-events:none;' : '' }}>
    <i class="bi bi-plus-lg"></i>
</button>
@endsection

@push('scripts')
<script>
const CLANG = @json(__('leads'));

function formatBrPhone(phone) {
    let d = (phone || '').replace(/\D/g, '');
    if (d.startsWith('55') && d.length >= 12) d = d.slice(2);
    if (d.length === 11) return `(${d.slice(0,2)}) ${d.slice(2,7)}-${d.slice(7)}`;
    if (d.length === 10) return `(${d.slice(0,2)}) ${d.slice(2,6)}-${d.slice(6)}`;
    return phone || '';
}

function phoneCell(phone) {
    if (!phone) return '—';
    const digits = phone.replace(/\D/g, '');
    const waNum  = digits.startsWith('55') ? digits : '55' + digits;
    return `<a href="https://wa.me/${waNum}" target="_blank" rel="noopener" style="color:inherit;text-decoration:none;white-space:nowrap;">${escapeHtml(formatBrPhone(phone))}</a>`;
}

const LEAD_SHOW  = @json(route('leads.show',   ['lead' => '__ID__']));
const LEAD_STORE = @json(route('leads.store'));
const LEAD_UPD   = @json(route('leads.update', ['lead' => '__ID__']));
const LEAD_DEL   = @json(route('leads.destroy',['lead' => '__ID__']));

// Clicar em linha da tabela → abrir drawer
document.getElementById('leadsTableBody').addEventListener('click', e => {
    const row = e.target.closest('.lead-row');
    if (!row) return;
    openLeadDrawer(row.dataset.leadId);
});

document.getElementById('btnNovoLead')?.addEventListener('click', () => openNewLeadDrawer());
document.getElementById('fabNovoLead')?.addEventListener('click', () => openNewLeadDrawer());
document.getElementById('emptyAddLead')?.addEventListener('click', e => { e.preventDefault(); openNewLeadDrawer(); });

const SOURCE_META = {
    facebook:  { icon: 'bi-facebook',    color: '#1877F2', label: CLANG.source_facebook },
    google:    { icon: 'bi-google',       color: '#4285F4', label: CLANG.source_google },
    instagram: { icon: 'bi-instagram',   color: '#E1306C', label: CLANG.source_instagram },
    whatsapp:  { icon: 'bi-whatsapp',    color: '#25D366', label: CLANG.source_whatsapp },
    site:      { icon: 'bi-globe',       color: '#6366F1', label: CLANG.source_site },
    indicacao: { icon: 'bi-people-fill', color: '#F59E0B', label: CLANG.source_indicacao },
    api:       { icon: 'bi-code-slash',  color: '#8B5CF6', label: CLANG.source_api },
    manual:    { icon: 'bi-pencil',      color: '#6B7280', label: CLANG.source_manual },
    outro:     { icon: 'bi-three-dots',  color: '#9CA3AF', label: CLANG.source_outro },
};
function renderSourceBadge(source, cls = 'source-pill') {
    const m = SOURCE_META[source] || SOURCE_META.outro;
    return `<span class="${cls}"><i class="bi ${m.icon}" style="color:${m.color};margin-right:4px;"></i>${escapeHtml(m.label)}</span>`;
}

// Após salvar: atualiza DOM da tabela
window.onLeadSaved = function(lead, isNew) {
    if (isNew) {
        const stage = lead.stage;
        const stageHtml = stage
            ? `<span class="stage-badge" style="background:${stage.color}22;color:${stage.color};">
                   <span class="dot" style="background:${stage.color};"></span>${escapeHtml(stage.name)}
               </span>`
            : '—';
        const valueHtml = lead.value_fmt ? lead.value_fmt : '—';
        const tbody = document.getElementById('leadsTableBody');
        // Remove empty state se existir
        tbody.querySelector('.empty-table')?.closest('tr')?.remove();
        tbody.insertAdjacentHTML('afterbegin', `
            <tr class="lead-row" data-lead-id="${lead.id}">
                <td class="lead-name-cell"><div style="display:flex;align-items:center;gap:8px;"><div style="width:32px;height:32px;border-radius:50%;flex-shrink:0;background:#e0ecff;color:#0085f3;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;overflow:hidden;">${lead.contact_picture_url ? `<img src="${lead.contact_picture_url}" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.parentElement.textContent='${escapeHtml(lead.name).split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase()}';">` : escapeHtml(lead.name).split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase()}</div><span>${escapeHtml(lead.name)}</span></div></td>
                <td>${phoneCell(lead.phone)}</td>
                <td>${escapeHtml(lead.email || '—')}</td>
                <td>${stageHtml}</td>
                <td class="value-cell">${escapeHtml(valueHtml)}</td>
                <td>${renderSourceBadge(lead.source || 'manual')}</td>
                <td>${lead.campaign ? escapeHtml(lead.campaign.name) : '—'}</td>
                <td style="white-space:nowrap;color:#9ca3af;">${escapeHtml(lead.created_at || '')}</td>
            </tr>`);
    } else {
        // Atualiza linha existente: mais simples recarregar a linha
        const row = document.querySelector(`.lead-row[data-lead-id="${lead.id}"]`);
        if (row) {
            const stage = lead.stage;
            const stageHtml = stage
                ? `<span class="stage-badge" style="background:${stage.color}22;color:${stage.color};">
                       <span class="dot" style="background:${stage.color};"></span>${escapeHtml(stage.name)}
                   </span>`
                : '—';
            row.querySelector('.lead-name-cell').textContent = lead.name;
            row.cells[1].innerHTML = phoneCell(lead.phone);
            row.cells[2].textContent = lead.email || '—';
            row.cells[3].innerHTML = stageHtml;
            row.cells[4].textContent = lead.value_fmt || '—';
            row.cells[5].innerHTML = renderSourceBadge(lead.source || 'manual');
            row.cells[6].textContent = lead.campaign ? lead.campaign.name : '—';
        }
    }
};

window.onLeadDeleted = function(leadId) {
    document.querySelector(`.lead-row[data-lead-id="${leadId}"]`)?.remove();
};

// Busca com debounce
let searchTimer;
document.getElementById('searchInput')?.addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => document.getElementById('filterForm').submit(), 500);
});

// ── Import modal ──────────────────────────────────────────────────────────
const IMPORT_URL = @json(route('leads.import'));

document.getElementById('btnImportLead')?.addEventListener('click', () => {
    document.getElementById('importModalOverlay').style.display = 'flex';
    document.getElementById('importResult').style.display = 'none';
    document.getElementById('importFile').value = '';
});

function closeImportModal() {
    document.getElementById('importModalOverlay').style.display = 'none';
}

document.getElementById('importModalOverlay')?.addEventListener('click', e => {
    if (e.target === document.getElementById('importModalOverlay')) closeImportModal();
});

async function doImport() {
    const file = document.getElementById('importFile').files[0];
    if (!file) { alert(CLANG.select_file_alert); return; }

    const btn = document.getElementById('btnDoImport');
    btn.disabled = true;
    btn.textContent = CLANG.importing;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

    try {
        const res  = await fetch(IMPORT_URL, { method: 'POST', body: formData });
        const data = await res.json();

        const resultEl = document.getElementById('importResult');
        resultEl.style.display = 'block';
        if (data.success) {
            resultEl.style.background = '#d1fae5';
            resultEl.style.color = '#065f46';
            resultEl.textContent = CLANG.import_success.replace(':imported', data.imported) + (data.skipped ? ' ' + CLANG.import_skipped.replace(':skipped', data.skipped) : '');
            setTimeout(() => { closeImportModal(); location.reload(); }, 2000);
        } else {
            resultEl.style.background = '#fee2e2';
            resultEl.style.color = '#991b1b';
            resultEl.textContent = data.message || CLANG.import_error;
        }
    } catch(e) {
        alert(CLANG.error_connection);
    } finally {
        btn.disabled = false;
        btn.textContent = CLANG.import_btn;
    }
}

// Auto-open drawer se URL contém ?lead=X (ex: redirect de /contatos/{id})
(function () {
    const params = new URLSearchParams(window.location.search);
    const leadId = params.get('lead');
    if (leadId) {
        // Limpa o param da URL sem reload
        const url = new URL(window.location.href);
        url.searchParams.delete('lead');
        history.replaceState(null, '', url.toString());
        // Espera o drawer estar pronto (scripts carregados)
        setTimeout(() => { if (typeof openLeadDrawer === 'function') openLeadDrawer(leadId); }, 200);
    }
}());
</script>
@endpush
