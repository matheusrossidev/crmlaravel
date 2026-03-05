@extends('tenant.layouts.app')

@php
    $title    = 'Resultados — ' . $flow->name;
    $pageIcon = 'bar-chart-line';
@endphp

@section('topbar_actions')
<div class="topbar-actions">
    <a href="{{ route('chatbot.flows.index') }}" class="btn-secondary-sm" style="text-decoration:none;display:flex;align-items:center;gap:6px;">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>
@endsection

@push('styles')
<style>
    .results-kpi-row {
        display: flex; gap: 14px; margin-bottom: 22px; flex-wrap: wrap;
    }
    .results-kpi {
        background: #fff; border: 1.5px solid #e8eaf0; border-radius: 12px;
        padding: 16px 22px; flex: 1; min-width: 140px;
    }
    .results-kpi-value { font-size: 26px; font-weight: 800; color: #1a1d23; }
    .results-kpi-label { font-size: 12px; color: #6b7280; margin-top: 2px; }

    .results-card {
        background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px;
        overflow: hidden;
    }
    .results-card-header {
        padding: 16px 22px; border-bottom: 1px solid #f0f2f7;
        display: flex; align-items: center; justify-content: space-between;
    }
    .results-card-header h3 {
        font-size: 14px; font-weight: 700; color: #1a1d23; margin: 0;
    }

    .results-filters {
        display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
    }
    .results-filters input, .results-filters select {
        border: 1.5px solid #e8eaf0; border-radius: 8px; padding: 6px 12px;
        font-size: 12.5px; color: #374151; background: #fff; outline: none;
    }
    .results-filters input:focus, .results-filters select:focus {
        border-color: #3B82F6;
    }

    .results-table-wrap {
        overflow-x: auto;
    }
    .results-table {
        width: 100%; border-collapse: collapse; font-size: 12.5px;
    }
    .results-table th {
        background: #f8fafc; color: #6b7280; font-weight: 700; font-size: 11px;
        text-transform: uppercase; letter-spacing: .04em;
        padding: 10px 14px; text-align: left; white-space: nowrap;
        border-bottom: 1px solid #f0f2f7;
    }
    .results-table td {
        padding: 10px 14px; border-bottom: 1px solid #f8f9fa;
        color: #374151; vertical-align: middle;
    }
    .results-table tr:hover td { background: #f8fafc; }

    .status-dot {
        display: inline-block; width: 7px; height: 7px; border-radius: 50%;
        margin-right: 5px;
    }
    .status-dot.open   { background: #22c55e; }
    .status-dot.closed { background: #9ca3af; }

    .var-cell { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .btn-export {
        background: #eff6ff; color: #0085f3; border: 1.5px solid #bfdbfe;
        border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600;
        cursor: pointer; display: inline-flex; align-items: center; gap: 5px;
    }
    .btn-export:hover { background: #dbeafe; }

    .empty-results {
        padding: 60px 20px; text-align: center; color: #9ca3af;
    }
    .empty-results i { font-size: 42px; opacity: .2; display: block; margin-bottom: 10px; }

    .transcript-row td { padding: 0 !important; }
    .transcript-body {
        padding: 12px 20px; background: #f8fafc;
        display: flex; flex-direction: column; gap: 6px;
        max-height: 300px; overflow-y: auto;
    }
    .transcript-msg {
        font-size: 12px; padding: 6px 10px; border-radius: 10px;
        max-width: 80%; word-break: break-word;
    }
    .transcript-msg.bot  { background: #fff; border: 1px solid #e8eaf0; align-self: flex-start; color: #374151; }
    .transcript-msg.user { background: #3B82F6; color: #fff; align-self: flex-end; }
</style>
@endpush

@push('scripts')
<script>
function filterResults() {
    const q      = document.getElementById('resultSearch').value.toLowerCase();
    const status = document.getElementById('resultStatus').value;

    document.querySelectorAll('.result-row').forEach(row => {
        let visible = true;
        if (status && row.dataset.status !== status) visible = false;
        if (q) {
            const text = row.textContent.toLowerCase();
            if (!text.includes(q)) visible = false;
        }
        row.style.display = visible ? '' : 'none';
        // Hide transcript if filter hides parent
        const tr = row.nextElementSibling;
        if (tr && tr.classList.contains('transcript-row')) tr.style.display = 'none';
    });
}

function toggleTranscript(btn, convId, channel) {
    const existing = document.getElementById('transcript-' + convId);
    if (existing) {
        existing.style.display = existing.style.display === 'none' ? '' : 'none';
        return;
    }

    btn.disabled = true;
    const url = channel === 'website'
        ? `/chats/website-conversations/${convId}`
        : `/chats/conversations/${convId}`;

    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            const messages = data.messages || [];
            const tr = document.createElement('tr');
            tr.id = 'transcript-' + convId;
            tr.className = 'transcript-row';
            const colspan = btn.closest('tr').children.length;
            tr.innerHTML = `<td colspan="${colspan}">
                <div class="transcript-body">
                    ${messages.length
                        ? messages.map(m => {
                            const side = m.direction === 'outbound' || m.from_bot ? 'bot' : 'user';
                            return `<div class="transcript-msg ${side}">${escapeHtml(m.content || m.body || '')}</div>`;
                        }).join('')
                        : '<span style="color:#9ca3af;font-size:12px;">Nenhuma mensagem encontrada.</span>'
                    }
                </div>
            </td>`;
            btn.closest('tr').after(tr);
        })
        .catch(() => {
            if (typeof toastr !== 'undefined') toastr.error('Erro ao carregar conversa.');
        })
        .finally(() => { btn.disabled = false; });
}

function exportCSV() {
    const table = document.getElementById('resultsTable');
    if (!table) return;

    const rows = [];
    table.querySelectorAll('tr').forEach(tr => {
        if (tr.classList.contains('transcript-row')) return;
        const cells = [];
        tr.querySelectorAll('th, td').forEach(cell => {
            // Skip action column
            if (cell.classList.contains('col-actions')) return;
            let text = cell.textContent.trim().replace(/"/g, '""');
            cells.push('"' + text + '"');
        });
        if (cells.length) rows.push(cells.join(','));
    });

    const csv = '\uFEFF' + rows.join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'resultados-{{ Str::slug($flow->name) }}-' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>
@endpush

@section('content')
<div class="page-container">

    {{-- KPIs --}}
    <div class="results-kpi-row">
        <div class="results-kpi">
            <div class="results-kpi-value">{{ $totalCount }}</div>
            <div class="results-kpi-label">Total de conversas</div>
        </div>
        <div class="results-kpi">
            <div class="results-kpi-value">{{ $rows->where('status', 'closed')->count() }}</div>
            <div class="results-kpi-label">Finalizadas</div>
        </div>
        <div class="results-kpi">
            <div class="results-kpi-value">{{ $rows->where('status', 'open')->count() }}</div>
            <div class="results-kpi-label">Em andamento</div>
        </div>
        @if($flow->channel === 'website')
        <div class="results-kpi">
            <div class="results-kpi-value">{{ $rows->whereNotNull('lead_id')->count() }}</div>
            <div class="results-kpi-label">Leads criados</div>
        </div>
        @endif
    </div>

    {{-- Table card --}}
    <div class="results-card">
        <div class="results-card-header">
            <h3><i class="bi bi-table" style="margin-right:6px;"></i> Respostas</h3>
            <div class="results-filters">
                <input type="text" id="resultSearch" placeholder="Buscar..." oninput="filterResults()">
                <select id="resultStatus" onchange="filterResults()">
                    <option value="">Todos status</option>
                    <option value="open">Em andamento</option>
                    <option value="closed">Finalizado</option>
                </select>
                <button class="btn-export" onclick="exportCSV()">
                    <i class="bi bi-download"></i> CSV
                </button>
            </div>
        </div>

        @if($rows->isEmpty())
            <div class="empty-results">
                <i class="bi bi-inbox"></i>
                <p>Nenhum resultado ainda. As respostas aparecem aqui quando visitantes interagem com o chatbot.</p>
            </div>
        @else
            <div class="results-table-wrap">
                <table class="results-table" id="resultsTable">
                    <thead>
                        <tr>
                            @foreach($fixedColumns as $col)
                            <th>{{ $col }}</th>
                            @endforeach
                            @foreach($variableKeys as $vk)
                            <th>{{ ucfirst(str_replace('_', ' ', $vk)) }}</th>
                            @endforeach
                            <th>Status</th>
                            <th class="col-actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        <tr class="result-row" data-status="{{ $row['status'] }}">
                            {{-- Fixed columns --}}
                            <td>{{ $row['date'] ?? '—' }}</td>
                            <td>{{ $row['name'] ?? '—' }}</td>
                            @if($flow->channel === 'website')
                                <td>{{ $row['email'] ?? '—' }}</td>
                                <td>{{ $row['phone'] ?? '—' }}</td>
                                <td>{{ $row['utm_source'] ?? '—' }}</td>
                                <td>{{ $row['utm_medium'] ?? '—' }}</td>
                                <td>{{ $row['utm_campaign'] ?? '—' }}</td>
                            @else
                                <td>{{ $row['phone'] ?? '—' }}</td>
                            @endif

                            {{-- Dynamic variable columns --}}
                            @foreach($variableKeys as $vk)
                            <td class="var-cell" title="{{ $row['variables'][$vk] ?? '' }}">{{ $row['variables'][$vk] ?? '—' }}</td>
                            @endforeach

                            {{-- Status --}}
                            <td>
                                <span class="status-dot {{ $row['status'] }}"></span>
                                {{ $row['status'] === 'closed' ? 'Finalizado' : 'Em andamento' }}
                            </td>

                            {{-- Actions --}}
                            <td class="col-actions">
                                <button type="button"
                                        onclick="toggleTranscript(this, {{ $row['id'] }}, '{{ $flow->channel }}')"
                                        style="background:none;border:none;color:#3B82F6;cursor:pointer;font-size:12px;font-weight:600;padding:4px 8px;border-radius:6px;"
                                        title="Ver conversa">
                                    <i class="bi bi-chat-text"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
