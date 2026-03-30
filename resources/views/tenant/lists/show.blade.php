@extends('tenant.layouts.app')

@php
    $title    = $list->name;
    $pageIcon = 'list-check';
@endphp

@push('styles')
<style>
    .list-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
    .list-meta { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #6b7280; flex-wrap: wrap; }
    .filter-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px; background: #eff6ff; border-radius: 6px;
        font-size: 12px; font-weight: 500; color: #1d4ed8;
    }
    .lead-row:hover { background: #f9fafb; }
</style>
@endpush

@section('content')
<div class="page-container">

    {{-- Header --}}
    <div class="list-header">
        <div>
            <a href="{{ route('lists.index') }}" style="font-size:13px;color:#6b7280;text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:8px;">
                <i class="bi bi-arrow-left"></i> Listas
            </a>
            <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:20px;font-weight:700;color:#1a1d23;margin:0 0 6px;display:flex;align-items:center;gap:10px;">
                {{ $list->name }}
                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:600;{{ $list->type === 'static' ? 'background:#eff6ff;color:#1d4ed8;' : 'background:#f0fdf4;color:#16a34a;' }}">
                    <i class="bi bi-{{ $list->type === 'static' ? 'pin-angle' : 'lightning' }}"></i>
                    {{ $list->type === 'static' ? 'Estática' : 'Dinâmica' }}
                </span>
            </h1>
            <div class="list-meta">
                <span><i class="bi bi-people"></i> {{ number_format($leads->total()) }} leads</span>
                @if($list->description)
                    <span style="color:#d1d5db;">|</span>
                    <span>{{ $list->description }}</span>
                @endif
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            @if($list->type === 'static')
                <button class="btn-primary-sm" onclick="openAddModal()">
                    <i class="bi bi-plus-lg"></i> Adicionar Leads
                </button>
            @else
                {{-- Show active filters --}}
                @if($list->filters && !empty($list->filters['conditions']))
                    <button class="btn-outline-sm" onclick="refreshCount()">
                        <i class="bi bi-arrow-clockwise"></i> Atualizar
                    </button>
                @endif
            @endif
        </div>
    </div>

    {{-- Dynamic filters display --}}
    @if($list->type === 'dynamic' && $list->filters && !empty($list->filters['conditions']))
        <div style="margin-bottom:16px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <span style="font-size:12px;font-weight:600;color:#6b7280;">Filtros:</span>
            <span style="font-size:11px;font-weight:600;color:#9ca3af;background:#f3f4f6;padding:2px 8px;border-radius:4px;">
                {{ $list->filters['operator'] ?? 'AND' }}
            </span>
            @foreach($list->filters['conditions'] as $cond)
                <span class="filter-badge">
                    {{ $cond['field'] }}
                    {{ $cond['op'] }}
                    {{ $cond['value'] ?? '' }}
                </span>
            @endforeach
        </div>
    @endif

    {{-- Search --}}
    <div style="margin-bottom:16px;">
        <form method="GET" action="{{ route('lists.show', $list) }}" style="display:flex;gap:8px;max-width:400px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar lead..."
                style="flex:1;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
            <button type="submit" class="btn-outline-sm"><i class="bi bi-search"></i></button>
        </form>
    </div>

    {{-- Leads table --}}
    <div class="content-card">
        @if($leads->isEmpty())
            <div style="padding:60px;text-align:center;color:#9ca3af;">
                <i class="bi bi-people" style="font-size:36px;display:block;margin-bottom:10px;"></i>
                <p style="font-size:14px;font-weight:600;color:#374151;margin:0 0 4px;">Nenhum lead nesta lista</p>
                @if($list->type === 'static')
                    <p style="font-size:13px;margin:0;">Clique em "Adicionar Leads" para começar.</p>
                @else
                    <p style="font-size:13px;margin:0;">Ajuste os filtros para encontrar leads.</p>
                @endif
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13.5px;">
                    <thead>
                        <tr>
                            <th style="padding:10px 16px;text-align:left;font-size:11.5px;font-weight:600;color:#677489;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f0f2f7;">Nome</th>
                            <th style="padding:10px 16px;text-align:left;font-size:11.5px;font-weight:600;color:#677489;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f0f2f7;">Email</th>
                            <th style="padding:10px 16px;text-align:left;font-size:11.5px;font-weight:600;color:#677489;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f0f2f7;">Telefone</th>
                            <th style="padding:10px 16px;text-align:left;font-size:11.5px;font-weight:600;color:#677489;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f0f2f7;">Origem</th>
                            <th style="padding:10px 16px;text-align:left;font-size:11.5px;font-weight:600;color:#677489;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f0f2f7;">Etapa</th>
                            <th style="padding:10px 16px;text-align:left;font-size:11.5px;font-weight:600;color:#677489;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f0f2f7;">Score</th>
                            @if($list->type === 'static')
                                <th style="padding:10px 16px;border-bottom:1px solid #f0f2f7;"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leads as $lead)
                        <tr class="lead-row">
                            <td style="padding:10px 16px;border-bottom:1px solid #f7f8fa;font-weight:600;color:#1a1d23;">{{ $lead->name ?: '—' }}</td>
                            <td style="padding:10px 16px;border-bottom:1px solid #f7f8fa;color:#374151;">{{ $lead->email ?: '—' }}</td>
                            <td style="padding:10px 16px;border-bottom:1px solid #f7f8fa;color:#374151;">{{ $lead->phone ?: '—' }}</td>
                            <td style="padding:10px 16px;border-bottom:1px solid #f7f8fa;font-size:12.5px;color:#6b7280;">{{ $lead->source ?: '—' }}</td>
                            <td style="padding:10px 16px;border-bottom:1px solid #f7f8fa;font-size:12.5px;">{{ $lead->stage?->name ?? '—' }}</td>
                            <td style="padding:10px 16px;border-bottom:1px solid #f7f8fa;font-weight:600;">{{ $lead->score ?? 0 }}</td>
                            @if($list->type === 'static')
                                <td style="padding:10px 16px;border-bottom:1px solid #f7f8fa;text-align:right;">
                                    <button onclick="removeMember({{ $lead->id }})" title="Remover da lista"
                                        style="background:#fef2f2;color:#ef4444;border:none;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:16px;border-top:1px solid #f0f2f7;">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
</div>

@if($list->type === 'static')
{{-- Add Members Modal --}}
<div id="addModal" style="display:none;position:fixed;inset:0;z-index:5000;background:rgba(0,0,0,.3);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:560px;margin:16px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.18);">
        <div style="padding:18px 24px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;">
            <h3 style="margin:0;font-size:16px;font-weight:700;color:#1a1d23;">Adicionar Leads</h3>
            <button onclick="closeAddModal()" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;"><i class="bi bi-x-lg"></i></button>
        </div>
        <div style="padding:16px 24px;border-bottom:1px solid #f0f2f7;">
            <div style="display:flex;gap:8px;">
                <input type="text" id="addSearch" placeholder="Buscar por nome, email ou telefone..."
                    style="flex:1;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                <select id="addPipeline" style="padding:8px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                    <option value="">Todos os funis</option>
                    @foreach($pipelines as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div id="addResults" style="flex:1;overflow-y:auto;padding:8px 24px;min-height:200px;max-height:400px;">
            <div style="padding:40px;text-align:center;color:#9ca3af;font-size:13px;">Busque para encontrar leads</div>
        </div>
        <div style="padding:14px 24px;border-top:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;">
            <span id="addSelectedCount" style="font-size:13px;color:#6b7280;">0 selecionados</span>
            <button class="btn-primary-sm" id="btnAddMembers" onclick="submitAddMembers()" disabled>
                <i class="bi bi-plus-lg"></i> Adicionar
            </button>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
const CSRF    = document.querySelector('meta[name="csrf-token"]').content;
const LIST_ID = {{ $list->id }};
const IS_STATIC = {{ $list->type === 'static' ? 'true' : 'false' }};

@if($list->type === 'static')
const SEARCH_URL = @json(route('lists.search-leads'));
const ADD_URL    = @json(route('lists.members.add', $list));
const REMOVE_URL = @json(route('lists.members.remove', ['list' => $list->id, 'lead' => '__LEAD__']));

let selectedLeads = new Set();
let searchTimer = null;

function openAddModal() {
    selectedLeads.clear();
    document.getElementById('addSearch').value = '';
    document.getElementById('addPipeline').value = '';
    document.getElementById('addResults').innerHTML = '<div style="padding:40px;text-align:center;color:#9ca3af;font-size:13px;">Busque para encontrar leads</div>';
    updateSelectedCount();
    document.getElementById('addModal').style.display = 'flex';
    document.getElementById('addSearch').focus();
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
}

document.getElementById('addSearch')?.addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(searchLeads, 300);
});
document.getElementById('addPipeline')?.addEventListener('change', searchLeads);

async function searchLeads() {
    const q = document.getElementById('addSearch').value.trim();
    const pipeline = document.getElementById('addPipeline').value;
    if (!q && !pipeline) return;

    const params = new URLSearchParams();
    if (q) params.set('q', q);
    if (pipeline) params.set('pipeline_id', pipeline);

    const res = await fetch(SEARCH_URL + '?' + params.toString(), { headers: { Accept: 'application/json' } });
    const leads = await res.json();

    if (!leads.length) {
        document.getElementById('addResults').innerHTML = '<div style="padding:30px;text-align:center;color:#9ca3af;font-size:13px;">Nenhum lead encontrado</div>';
        return;
    }

    document.getElementById('addResults').innerHTML = leads.map(l => `
        <label style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f7f8fa;cursor:pointer;">
            <input type="checkbox" value="${l.id}" ${selectedLeads.has(l.id) ? 'checked' : ''} onchange="toggleLead(${l.id})"
                style="width:16px;height:16px;cursor:pointer;">
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;color:#1a1d23;">${l.name || '—'}</div>
                <div style="font-size:12px;color:#9ca3af;">${l.email || ''} ${l.phone ? '• ' + l.phone : ''}</div>
            </div>
            <span style="font-size:11px;color:#9ca3af;">${l.source || ''}</span>
        </label>
    `).join('');
}

function toggleLead(id) {
    selectedLeads.has(id) ? selectedLeads.delete(id) : selectedLeads.add(id);
    updateSelectedCount();
}

function updateSelectedCount() {
    const count = selectedLeads.size;
    document.getElementById('addSelectedCount').textContent = count + ' selecionado' + (count !== 1 ? 's' : '');
    document.getElementById('btnAddMembers').disabled = count === 0;
}

async function submitAddMembers() {
    if (!selectedLeads.size) return;
    const btn = document.getElementById('btnAddMembers');
    btn.disabled = true;

    const res = await fetch(ADD_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
        body: JSON.stringify({ lead_ids: [...selectedLeads] }),
    });
    const data = await res.json();
    if (data.success) {
        toastr.success(data.added + ' lead(s) adicionado(s)');
        setTimeout(() => location.reload(), 800);
    } else {
        toastr.error(data.message || 'Erro');
        btn.disabled = false;
    }
}

async function removeMember(leadId) {
    if (!confirm('Remover este lead da lista?')) return;
    const res = await fetch(REMOVE_URL.replace('__LEAD__', leadId), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
    });
    const data = await res.json();
    if (data.success) location.reload();
    else toastr.error('Erro ao remover');
}
@endif

async function refreshCount() {
    const res = await fetch('{{ route("lists.update", $list) }}', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
        body: JSON.stringify({ name: '{{ addslashes($list->name) }}', description: '{{ addslashes($list->description ?? '') }}' }),
    });
    if ((await res.json()).success) location.reload();
}
</script>
@endpush
