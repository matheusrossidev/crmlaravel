@extends('tenant.layouts.app')

@php($title = 'Contatos')
@php($pageIcon = 'people')

@section('topbar_actions')
<div class="topbar-actions" style="gap:8px;">
    <form method="GET" action="{{ route('leads.index') }}" id="filterForm" style="display:flex;align-items:center;">
        <div style="position:relative;">
            <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:13px;"></i>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Buscar nome, e-mail, telefone..."
                   style="padding:7px 12px 7px 30px;border:1.5px solid #e8eaf0;border-radius:9px;font-size:13px;font-family:inherit;outline:none;width:220px;background:#fafafa;"
                   id="searchInput">
        </div>
    </form>

    <a href="{{ route('leads.export') }}"
       class="btn-secondary-sm" style="display:flex;align-items:center;gap:5px;text-decoration:none;">
        <i class="bi bi-download"></i> Exportar
    </a>

    <button class="btn-secondary-sm" id="btnImportLead" style="display:flex;align-items:center;gap:5px;">
        <i class="bi bi-upload"></i> Importar
    </button>

    <button class="btn-primary-sm" id="btnNovoLead">
        <i class="bi bi-plus-lg"></i> Novo Lead
    </button>
</div>
@endsection

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
        background: #3B82F6;
        border-color: #3B82F6;
        color: #fff;
    }

    .pagination-wrap nav .pagination .page-item .page-link:hover {
        background: #f0f4ff;
        border-color: #dbeafe;
    }

    .btn-secondary-sm {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 7px 14px;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        background: #fff;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        transition: all .15s;
        font-family: inherit;
    }
    .btn-secondary-sm:hover { background: #f0f4ff; border-color: #dbeafe; color: #3B82F6; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div class="leads-table-wrap">
        <table class="leads-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th>Etapa</th>
                    <th>Valor</th>
                    <th>Origem</th>
                    <th>Campanha</th>
                    <th>Cadastro</th>
                </tr>
            </thead>
            <tbody id="leadsTableBody">
                @php
                $srcMeta = [
                    'facebook'  => ['icon' => 'bi-facebook',    'color' => '#1877F2', 'label' => 'Facebook Ads'],
                    'google'    => ['icon' => 'bi-google',       'color' => '#4285F4', 'label' => 'Google Ads'],
                    'instagram' => ['icon' => 'bi-instagram',    'color' => '#E1306C', 'label' => 'Instagram'],
                    'whatsapp'  => ['icon' => 'bi-whatsapp',     'color' => '#25D366', 'label' => 'WhatsApp'],
                    'site'      => ['icon' => 'bi-globe',        'color' => '#6366F1', 'label' => 'Site'],
                    'indicacao' => ['icon' => 'bi-people-fill',  'color' => '#F59E0B', 'label' => 'Indicação'],
                    'api'       => ['icon' => 'bi-code-slash',   'color' => '#8B5CF6', 'label' => 'API'],
                    'manual'    => ['icon' => 'bi-pencil',       'color' => '#6B7280', 'label' => 'Manual'],
                    'outro'     => ['icon' => 'bi-three-dots',   'color' => '#9CA3AF', 'label' => 'Outro'],
                ];
                @endphp
                @forelse($leads as $lead)
                @php $s = $srcMeta[$lead->source ?? 'manual'] ?? $srcMeta['outro']; @endphp
                <tr class="lead-row" data-lead-id="{{ $lead->id }}">
                    <td class="lead-name-cell">
                        {{ $lead->name }}
                        @if($lead->pipeline)
                        <small>{{ $lead->pipeline->name }}</small>
                        @endif
                    </td>
                    <td>{{ $lead->phone ?? '—' }}</td>
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
                        {{ $lead->value ? 'R$ ' . number_format((float)$lead->value, 2, ',', '.') : '—' }}
                    </td>
                    <td><span class="source-pill"><i class="bi {{ $s['icon'] }}" style="color:{{ $s['color'] }};margin-right:4px;"></i>{{ $s['label'] }}</span></td>
                    <td>{{ $lead->campaign?->name ?? '—' }}</td>
                    <td style="white-space:nowrap;color:#9ca3af;">{{ $lead->created_at->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-table">
                            <div><i class="bi bi-people"></i></div>
                            <p>Nenhum lead encontrado.<br>
                                <a href="#" id="emptyAddLead" style="color:#3B82F6;">Adicionar o primeiro lead</a>
                            </p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($leads->hasPages())
        <div class="pagination-wrap">
            <span>{{ $leads->firstItem() }}–{{ $leads->lastItem() }} de {{ $leads->total() }} leads</span>
            {{ $leads->links() }}
        </div>
        @endif
    </div>

</div>

{{-- Drawer compartilhado --}}
@include('tenant.leads._drawer', ['pipelines' => $pipelines, 'customFieldDefs' => $customFieldDefs])

{{-- Modal Import --}}
<div id="importModalOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:500;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px;width:440px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.18);">
        <div style="font-size:16px;font-weight:700;color:#1a1d23;margin-bottom:6px;">Importar Leads</div>
        <p style="font-size:13px;color:#6b7280;margin-bottom:18px;">
            Envie um arquivo <strong>.xlsx</strong> ou <strong>.csv</strong> com as colunas:<br>
            <code style="font-size:12px;background:#f4f6fb;padding:2px 6px;border-radius:5px;">nome, telefone, email, valor, origem</code>
        </p>
        <form id="importForm" enctype="multipart/form-data">
            <input type="file" id="importFile" name="file" accept=".xlsx,.xls,.csv"
                   style="width:100%;padding:10px;border:1.5px dashed #e8eaf0;border-radius:9px;font-size:13px;background:#fafafa;cursor:pointer;box-sizing:border-box;">
        </form>
        <div id="importResult" style="display:none;margin-top:14px;padding:10px 14px;border-radius:9px;font-size:13px;"></div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px;">
            <button onclick="closeImportModal()" style="padding:8px 18px;border-radius:8px;border:1.5px solid #e8eaf0;background:#fff;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;">Cancelar</button>
            <button id="btnDoImport" onclick="doImport()" style="padding:8px 20px;border-radius:8px;border:none;background:#3B82F6;color:#fff;font-size:13px;font-weight:600;cursor:pointer;">Importar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
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
document.getElementById('emptyAddLead')?.addEventListener('click', e => { e.preventDefault(); openNewLeadDrawer(); });

const SOURCE_META = {
    facebook:  { icon: 'bi-facebook',    color: '#1877F2', label: 'Facebook Ads' },
    google:    { icon: 'bi-google',       color: '#4285F4', label: 'Google Ads' },
    instagram: { icon: 'bi-instagram',   color: '#E1306C', label: 'Instagram' },
    whatsapp:  { icon: 'bi-whatsapp',    color: '#25D366', label: 'WhatsApp' },
    site:      { icon: 'bi-globe',       color: '#6366F1', label: 'Site' },
    indicacao: { icon: 'bi-people-fill', color: '#F59E0B', label: 'Indicação' },
    api:       { icon: 'bi-code-slash',  color: '#8B5CF6', label: 'API' },
    manual:    { icon: 'bi-pencil',      color: '#6B7280', label: 'Manual' },
    outro:     { icon: 'bi-three-dots',  color: '#9CA3AF', label: 'Outro' },
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
                <td class="lead-name-cell">${escapeHtml(lead.name)}</td>
                <td>${escapeHtml(lead.phone || '—')}</td>
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
            row.cells[1].textContent = lead.phone || '—';
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
    if (!file) { alert('Selecione um arquivo.'); return; }

    const btn = document.getElementById('btnDoImport');
    btn.disabled = true;
    btn.textContent = 'Importando...';

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
            resultEl.textContent = `${data.imported} lead(s) importado(s). ${data.skipped ? data.skipped + ' ignorado(s).' : ''}`;
            setTimeout(() => { closeImportModal(); location.reload(); }, 2000);
        } else {
            resultEl.style.background = '#fee2e2';
            resultEl.style.color = '#991b1b';
            resultEl.textContent = data.message || 'Erro ao importar.';
        }
    } catch(e) {
        alert('Erro de conexão.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Importar';
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
