@extends('tenant.layouts.app')

@php
    $title    = __('nav.lists') ?? 'Listas';
    $pageIcon = 'list-check';
@endphp

@push('styles')
<style>
    .list-type-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600;
    }
    .list-type-static  { background: #eff6ff; color: #1d4ed8; }
    .list-type-dynamic { background: #f0fdf4; color: #16a34a; }

    /* Drawer */
    .list-drawer-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.3); z-index: 5000; }
    .list-drawer-overlay.open { display: block; }
    .list-drawer {
        position: fixed; top: 0; right: -480px; width: 480px; height: 100vh;
        background: #fff; z-index: 5001; box-shadow: -4px 0 24px rgba(0,0,0,.1);
        display: flex; flex-direction: column;
        transition: right .25s cubic-bezier(.4,0,.2,1);
    }
    .list-drawer.open { right: 0; }
    .list-drawer-header {
        padding: 18px 24px; border-bottom: 1px solid #f0f2f7;
        display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
    }
    .list-drawer-body { flex: 1; overflow-y: auto; padding: 24px; }
    .list-drawer-footer {
        padding: 16px 24px; border-top: 1px solid #f0f2f7;
        display: flex; gap: 10px; justify-content: flex-end; flex-shrink: 0;
    }

    @media (max-width: 520px) { .list-drawer { width: 100%; right: -100%; } }

    /* Condition builder */
    .cond-row { display: flex; gap: 8px; align-items: center; margin-bottom: 10px; }
    .cond-row select, .cond-row input {
        padding: 9px 10px; border: 1.5px solid #e5e7eb; border-radius: 8px;
        font-size: 13px; outline: none; font-family: inherit; background: #fff;
    }
    .cond-row select:focus, .cond-row input:focus { border-color: #0085f3; }
    .cond-field { min-width: 140px; flex: 1; }
    .cond-op   { min-width: 120px; }
    .cond-val  { min-width: 120px; flex: 1; }
    .cond-remove {
        width: 30px; height: 30px; border: none; background: #fef2f2; color: #ef4444;
        border-radius: 6px; cursor: pointer; font-size: 14px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .cond-remove:hover { background: #fee2e2; }

    .op-toggle {
        display: inline-flex; border: 1.5px solid #e5e7eb; border-radius: 8px; overflow: hidden; font-size: 12px;
    }
    .op-toggle button {
        padding: 6px 14px; border: none; background: #fff; cursor: pointer; font-weight: 600; color: #6b7280;
    }
    .op-toggle button.active { background: #0085f3; color: #fff; }

    .preview-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 14px; background: #eff6ff; border-radius: 8px;
        font-size: 13px; font-weight: 600; color: #1d4ed8; margin-top: 4px;
    }

    .type-card {
        border: 2px solid #e5e7eb; border-radius: 10px; padding: 14px; text-align: center;
        cursor: pointer; transition: all .15s; flex: 1;
    }
    .type-card.selected { border-color: #0085f3; background: #f8fbff; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">CRM</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">Listas de Leads</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">Organize seus leads em listas estáticas ou dinâmicas.</p>
            </div>
            <button class="btn-primary-sm" onclick="openDrawer()">
                <i class="bi bi-plus-lg"></i> Nova Lista
            </button>
        </div>
    </div>

    <div class="content-card">
        @if($lists->isEmpty())
            <div style="padding:60px;text-align:center;color:#9ca3af;">
                <i class="bi bi-list-check" style="font-size:40px;display:block;margin-bottom:12px;"></i>
                <p style="font-size:14px;font-weight:600;color:#374151;margin:0 0 4px;">Nenhuma lista criada</p>
                <p style="font-size:13px;margin:0;">Crie listas para organizar e segmentar seus leads.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13.5px;">
                    <thead>
                        <tr>
                            <th style="padding:12px 16px;text-align:left;font-size:11.5px;font-weight:600;color:#677489;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f0f2f7;">Nome</th>
                            <th style="padding:12px 16px;text-align:left;font-size:11.5px;font-weight:600;color:#677489;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f0f2f7;">Tipo</th>
                            <th style="padding:12px 16px;text-align:left;font-size:11.5px;font-weight:600;color:#677489;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f0f2f7;">Leads</th>
                            <th style="padding:12px 16px;text-align:left;font-size:11.5px;font-weight:600;color:#677489;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f0f2f7;">Criado por</th>
                            <th style="padding:12px 16px;text-align:left;font-size:11.5px;font-weight:600;color:#677489;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f0f2f7;">Data</th>
                            <th style="padding:12px 16px;border-bottom:1px solid #f0f2f7;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lists as $list)
                        <tr style="cursor:pointer;" onclick="window.location='{{ route('lists.show', $list) }}'">
                            <td style="padding:12px 16px;border-bottom:1px solid #f7f8fa;">
                                <div style="font-weight:600;color:#1a1d23;">{{ $list->name }}</div>
                                @if($list->description)
                                    <div style="font-size:12px;color:#9ca3af;margin-top:2px;">{{ \Illuminate\Support\Str::limit($list->description, 60) }}</div>
                                @endif
                            </td>
                            <td style="padding:12px 16px;border-bottom:1px solid #f7f8fa;">
                                <span class="list-type-badge list-type-{{ $list->type }}">
                                    <i class="bi bi-{{ $list->type === 'static' ? 'pin-angle' : 'lightning' }}"></i>
                                    {{ $list->type === 'static' ? 'Estática' : 'Dinâmica' }}
                                </span>
                            </td>
                            <td style="padding:12px 16px;border-bottom:1px solid #f7f8fa;font-weight:700;">{{ number_format($list->lead_count) }}</td>
                            <td style="padding:12px 16px;border-bottom:1px solid #f7f8fa;font-size:13px;color:#6b7280;">{{ $list->createdBy?->name ?? '—' }}</td>
                            <td style="padding:12px 16px;border-bottom:1px solid #f7f8fa;font-size:12.5px;color:#9ca3af;">{{ $list->created_at->format('d/m/Y') }}</td>
                            <td style="padding:12px 16px;border-bottom:1px solid #f7f8fa;text-align:right;" onclick="event.stopPropagation();">
                                <button onclick="deleteList({{ $list->id }}, '{{ addslashes($list->name) }}')"
                                    style="background:#fef2f2;color:#ef4444;border:none;border-radius:6px;padding:5px 8px;cursor:pointer;font-size:13px;">
                                    <i class="bi bi-trash3"></i>
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

{{-- ===== DRAWER ===== --}}
<div class="list-drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="list-drawer" id="listDrawer">
    <div class="list-drawer-header">
        <h3 style="margin:0;font-size:16px;font-weight:700;color:#1a1d23;display:flex;align-items:center;gap:8px;">
            <i class="bi bi-list-check" style="color:#0085f3;"></i> Nova Lista
        </h3>
        <button onclick="closeDrawer()" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;"><i class="bi bi-x-lg"></i></button>
    </div>

    <div class="list-drawer-body">
        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Nome *</label>
            <input type="text" id="listName" placeholder="Ex: Leads quentes"
                style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
        </div>
        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Descrição</label>
            <textarea id="listDesc" rows="2" placeholder="Opcional"
                style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;resize:vertical;font-family:inherit;"></textarea>
        </div>

        {{-- Type selector --}}
        <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">Tipo</label>
            <div style="display:flex;gap:10px;">
                <div class="type-card selected" id="typeStatic" onclick="selectType('static')">
                    <i class="bi bi-pin-angle" style="font-size:18px;color:#0085f3;display:block;margin-bottom:4px;"></i>
                    <div style="font-size:13px;font-weight:600;color:#1a1d23;">Estática</div>
                    <div style="font-size:11px;color:#6b7280;">Adicione leads manualmente</div>
                </div>
                <div class="type-card" id="typeDynamic" onclick="selectType('dynamic')">
                    <i class="bi bi-lightning" style="font-size:18px;color:#10B981;display:block;margin-bottom:4px;"></i>
                    <div style="font-size:13px;font-weight:600;color:#1a1d23;">Dinâmica</div>
                    <div style="font-size:11px;color:#6b7280;">Filtros automáticos</div>
                </div>
            </div>
        </div>

        {{-- Dynamic filters --}}
        <div id="filtersSection" style="display:none;">
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">Condições</label>
            <div style="margin-bottom:10px;">
                <div class="op-toggle">
                    <button id="opAnd" class="active" onclick="setOp('AND')">E (AND)</button>
                    <button id="opOr" onclick="setOp('OR')">OU (OR)</button>
                </div>
            </div>
            <div id="condBox"></div>
            <button onclick="addCond()" style="background:#eff6ff;color:#0085f3;border:1.5px dashed #bfdbfe;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;cursor:pointer;width:100%;margin-bottom:12px;">
                <i class="bi bi-plus-lg"></i> Adicionar condição
            </button>
            <div id="previewArea" style="display:none;">
                <span class="preview-pill">
                    <i class="bi bi-people"></i> <span id="previewCount">0</span> leads correspondem
                </span>
            </div>
        </div>
    </div>

    <div class="list-drawer-footer">
        <button class="btn-outline-sm" onclick="closeDrawer()">Cancelar</button>
        <button class="btn-primary-sm" id="btnCreate" onclick="createList()">
            <i class="bi bi-check-lg"></i> Criar Lista
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const STORE_URL   = @json(route('lists.store'));
const PREVIEW_URL = @json(route('lists.preview'));
const DELETE_URL  = @json(route('lists.destroy', ['list' => '__ID__']));

// Dynamic data from server
const PIPELINES  = {!! json_encode($pipelines) !!};
const STAGES     = {!! json_encode($stages) !!};
const USERS      = {!! json_encode($users) !!};
const CAMPAIGNS  = {!! json_encode($campaigns) !!};
const TAGS       = {!! json_encode($tags->map(fn($t) => ['name' => $t->name, 'color' => $t->color ?? null])) !!};
const SOURCES    = {!! json_encode($sources) !!};

const FIELDS = [
    { value: 'score',        label: 'Score',               valType: 'number' },
    { value: 'source',       label: 'Origem',              valType: 'select', options: SOURCES.map(s => ({v: s, l: s})) },
    { value: 'pipeline_id',  label: 'Funil',               valType: 'select', options: PIPELINES.map(p => ({v: p.id, l: p.name})) },
    { value: 'stage_id',     label: 'Etapa do funil',      valType: 'select', options: STAGES.map(s => ({v: s.id, l: s.name})) },
    { value: 'tag',          label: 'Tag',                 valType: 'select', options: TAGS.map(t => ({v: t.name, l: t.name})), ops: ['eq'] },
    { value: 'assigned_to',  label: 'Responsável',         valType: 'select', options: USERS.map(u => ({v: u.id, l: u.name})) },
    { value: 'campaign_id',  label: 'Campanha',            valType: 'select', options: CAMPAIGNS.map(c => ({v: c.id, l: c.name})), ops: ['eq'] },
    { value: 'created_at',   label: 'Data de criação',     valType: 'date' },
    { value: 'value',        label: 'Valor (R$)',          valType: 'number' },
    { value: 'email',        label: 'Tem email',           valType: 'exists', ops: ['not_null','is_null'] },
    { value: 'phone',        label: 'Tem telefone',        valType: 'exists', ops: ['not_null','is_null'] },
    { value: 'has_open_conversation', label: 'Conversa WA aberta', valType: 'boolean', ops: ['eq'] },
];

const DEFAULT_OPS = ['eq','neq','gte','lte','contains'];
const OP_LABELS = { eq:'= igual', neq:'≠ diferente', gte:'≥ maior/igual', lte:'≤ menor/igual', contains:'contém', is_null:'está vazio', not_null:'está preenchido' };

let listType = 'static';
let filterOp = 'AND';
let conds = [];
let previewTimer = null;

// ── Drawer ─────────────────────────────
function openDrawer() {
    listType = 'static'; filterOp = 'AND'; conds = [];
    document.getElementById('listName').value = '';
    document.getElementById('listDesc').value = '';
    selectType('static');
    document.getElementById('condBox').innerHTML = '';
    document.getElementById('previewArea').style.display = 'none';
    document.getElementById('drawerOverlay').classList.add('open');
    document.getElementById('listDrawer').classList.add('open');
    setTimeout(() => document.getElementById('listName').focus(), 200);
}
function closeDrawer() {
    document.getElementById('drawerOverlay').classList.remove('open');
    document.getElementById('listDrawer').classList.remove('open');
}

function selectType(t) {
    listType = t;
    document.getElementById('typeStatic').className  = 'type-card' + (t === 'static' ? ' selected' : '');
    document.getElementById('typeDynamic').className = 'type-card' + (t === 'dynamic' ? ' selected' : '');
    document.getElementById('filtersSection').style.display = t === 'dynamic' ? 'block' : 'none';
    if (t === 'dynamic' && conds.length === 0) addCond();
}

function setOp(op) {
    filterOp = op;
    document.getElementById('opAnd').className = op === 'AND' ? 'active' : '';
    document.getElementById('opOr').className  = op === 'OR'  ? 'active' : '';
    triggerPreview();
}

// ── Conditions ─────────────────────────
function addCond() {
    conds.push({ field: 'score', op: 'gte', value: '' });
    render();
}

function removeCond(i) {
    conds.splice(i, 1);
    render();
    triggerPreview();
}

function render() {
    document.getElementById('condBox').innerHTML = conds.map((c, i) => {
        const fd = FIELDS.find(f => f.value === c.field) || FIELDS[0];
        const ops = fd.ops || DEFAULT_OPS;

        // Field select
        const fieldSel = `<select class="cond-field" onchange="updCond(${i},'field',this.value)">${FIELDS.map(f => `<option value="${f.value}" ${f.value===c.field?'selected':''}>${f.label}</option>`).join('')}</select>`;

        // Op select
        const opSel = `<select class="cond-op" onchange="updCond(${i},'op',this.value)">${ops.map(o => `<option value="${o}" ${o===c.op?'selected':''}>${OP_LABELS[o]}</option>`).join('')}</select>`;

        // Value input
        let valHtml = '';
        if (['is_null','not_null'].includes(c.op)) {
            valHtml = '';
        } else if (fd.valType === 'select' && fd.options) {
            valHtml = `<select class="cond-val" onchange="updCond(${i},'value',this.value)"><option value="">Selecione...</option>${fd.options.map(o => `<option value="${o.v}" ${String(o.v)===String(c.value)?'selected':''}>${o.l}</option>`).join('')}</select>`;
        } else if (fd.valType === 'boolean') {
            valHtml = `<select class="cond-val" onchange="updCond(${i},'value',this.value)"><option value="1" ${c.value=='1'?'selected':''}>Sim</option><option value="0" ${c.value=='0'?'selected':''}>Não</option></select>`;
        } else if (fd.valType === 'date') {
            valHtml = `<input type="date" class="cond-val" value="${c.value||''}" onchange="updCond(${i},'value',this.value)">`;
        } else if (fd.valType === 'exists') {
            valHtml = '';
        } else {
            valHtml = `<input type="number" class="cond-val" value="${c.value||''}" placeholder="Valor" oninput="updCond(${i},'value',this.value)">`;
        }

        return `<div class="cond-row">${fieldSel}${opSel}${valHtml}<button class="cond-remove" onclick="removeCond(${i})"><i class="bi bi-x"></i></button></div>`;
    }).join('');
}

function updCond(i, k, v) {
    conds[i][k] = v;
    if (k === 'field') {
        const fd = FIELDS.find(f => f.value === v) || FIELDS[0];
        conds[i].op = (fd.ops || DEFAULT_OPS)[0];
        conds[i].value = '';
        render();
    }
    triggerPreview();
}

// ── Preview ────────────────────────────
function triggerPreview() {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(runPreview, 400);
}

async function runPreview() {
    const valid = conds.filter(c => c.value !== '' || ['is_null','not_null'].includes(c.op));
    if (!valid.length) { document.getElementById('previewArea').style.display = 'none'; return; }
    try {
        const res = await fetch(PREVIEW_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body: JSON.stringify({ filters: { operator: filterOp, conditions: valid } }),
        });
        const data = await res.json();
        document.getElementById('previewCount').textContent = data.count;
        document.getElementById('previewArea').style.display = 'block';
    } catch (e) {}
}

// ── Create ─────────────────────────────
async function createList() {
    const name = document.getElementById('listName').value.trim();
    if (!name) { document.getElementById('listName').focus(); return; }

    const body = { name, description: document.getElementById('listDesc').value.trim(), type: listType };
    if (listType === 'dynamic') {
        body.filters = { operator: filterOp, conditions: conds.filter(c => c.value !== '' || ['is_null','not_null'].includes(c.op)) };
    }

    document.getElementById('btnCreate').disabled = true;
    try {
        const res = await fetch(STORE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = '{{ route("lists.show", "__ID__") }}'.replace('__ID__', data.list.id);
        } else {
            toastr.error(data.message || 'Erro ao criar lista');
            document.getElementById('btnCreate').disabled = false;
        }
    } catch (e) {
        toastr.error('Erro de rede');
        document.getElementById('btnCreate').disabled = false;
    }
}

function deleteList(id, name) {
    if (!confirm('Excluir a lista "' + name + '"?')) return;
    fetch(DELETE_URL.replace('__ID__', id), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
    }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
        else toastr.error('Erro ao excluir');
    });
}
</script>
@endpush
