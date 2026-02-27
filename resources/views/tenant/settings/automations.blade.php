@extends('tenant.layouts.app')

@php
    $title    = 'Configurações';
    $pageIcon = 'gear';
@endphp

@push('styles')
<style>
/* ── List ──────────────────────────────────────────────────────── */
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

/* ── Trigger badge ─────────────────────────────────────────────── */
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

/* ── Action chips in list ──────────────────────────────────────── */
.action-chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 12px;
    background: #f3f4f6; color: #4b5563;
    font-size: 11.5px; font-weight: 500; margin: 2px 2px 2px 0;
}

/* ── Buttons ────────────────────────────────────────────────────── */
.btn-icon {
    width: 28px; height: 28px; border-radius: 7px;
    border: 1px solid #e8eaf0; background: #fff; color: #6b7280;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 13px; transition: all .15s;
    flex-shrink: 0;
}
.btn-icon:hover         { background: #f3f4f6; color: #374151; border-color: #d1d5db; }
.btn-icon.danger:hover  { background: #fef2f2; color: #ef4444; border-color: #fca5a5; }

/* ── Drawer ─────────────────────────────────────────────────────── */
.at-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.35); z-index: 1040;
}
.at-overlay.open { display: block; }
.at-drawer {
    position: fixed; top: 0; right: -560px; width: 540px; height: 100%;
    background: #fff; z-index: 1050; box-shadow: -4px 0 28px rgba(0,0,0,.14);
    transition: right .25s ease; display: flex; flex-direction: column;
}
.at-drawer.open { right: 0; }
.at-drawer-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid #f0f2f7;
    background: #fafafa; flex-shrink: 0;
}
.at-drawer-head h5 { margin: 0; font-size: 15px; font-weight: 700; color: #1f2937; }
.at-drawer-body { flex: 1; overflow-y: auto; padding: 20px 20px 8px; }
.at-drawer-foot {
    padding: 14px 20px; border-top: 1px solid #f0f2f7; background: #fafafa;
    flex-shrink: 0; display: flex; justify-content: flex-end; gap: 8px;
}

/* ── Form sections ──────────────────────────────────────────────── */
.f-section { margin-bottom: 22px; }
.f-section-label {
    font-size: 11px; font-weight: 700; color: #9ca3af;
    text-transform: uppercase; letter-spacing: .06em;
    margin-bottom: 10px;
}

/* ── Rule cards (conditions & actions) ─────────────────────────── */
.rule-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 12px 12px 10px;
    margin-bottom: 8px;
    position: relative;
}
.rule-card .rule-head {
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
}
.rule-card .rule-head select,
.rule-card .rule-head input { flex: 1; min-width: 100px; }
.rule-card .rule-extra { margin-top: 10px; }
.rule-card .btn-remove {
    position: absolute; top: 9px; right: 9px;
}
.btn-add-rule {
    width: 100%; border: 1.5px dashed #d1d5db; border-radius: 8px;
    background: transparent; color: #6b7280; font-size: 13px;
    padding: 8px; cursor: pointer; transition: all .15s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.btn-add-rule:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }

/* ── Tag multi-select ────────────────────────────────────────────── */
.tag-select-wrap {
    display: flex; flex-wrap: wrap; gap: 6px; align-items: center;
    border: 1px solid #d1d5db; border-radius: 6px;
    padding: 5px 10px; background: #fff; cursor: text; min-height: 36px;
}
.tag-chip-item {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 2px 8px 2px 10px; border-radius: 16px;
    background: #3b82f6; color: #fff;
    font-size: 12px; font-weight: 600;
}
.tag-chip-item button {
    background: none; border: none; color: inherit; padding: 0;
    cursor: pointer; line-height: 1; font-size: 13px; opacity: .8;
}
.tag-chip-item button:hover { opacity: 1; }
.tag-input-ghost {
    border: none; outline: none; font-size: 13px; flex: 1; min-width: 80px;
    background: transparent; color: #374151;
}

/* ── Dropdown suggestions ───────────────────────────────────────── */
.tag-suggestions {
    position: absolute; z-index: 200; background: #fff;
    border: 1px solid #e5e7eb; border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,.1);
    max-height: 180px; overflow-y: auto; min-width: 200px;
    left: 0; top: 100%; margin-top: 2px;
}
.tag-sug-item {
    padding: 8px 14px; font-size: 13px; cursor: pointer; color: #374151;
}
.tag-sug-item:hover { background: #eff6ff; color: #2563eb; }

/* ── Empty state ─────────────────────────────────────────────────── */
.at-empty { text-align: center; padding: 60px 20px; }
.at-empty i { font-size: 44px; color: #d1d5db; }
.at-empty p  { color: #9ca3af; font-size: 13.5px; margin-top: 12px; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-11">

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 fw-bold" style="font-size:17px;">Automações</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        Regras que executam ações automaticamente quando eventos ocorrem no CRM.
                    </p>
                </div>
                <button class="btn btn-primary btn-sm d-flex align-items-center gap-2" onclick="openDrawer()">
                    <i class="bi bi-plus-lg"></i> Nova Automação
                </button>
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
                                <th style="width:100px;">Execuções</th>
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
                                        @php $actionLabels = [
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
                                        ]; @endphp
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
                                        <button class="btn-icon" title="Editar" onclick="editAutomation({{ $auto->id }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn-icon danger" title="Excluir" onclick="deleteAutomation({{ $auto->id }})">
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
    </div>
</div>

{{-- Overlay + Drawer --}}
<div class="at-overlay" id="atOverlay" onclick="closeDrawer()"></div>

<div class="at-drawer" id="atDrawer">
    <div class="at-drawer-head">
        <h5 id="atDrawerTitle">Nova Automação</h5>
        <button class="btn-icon" onclick="closeDrawer()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="at-drawer-body">
        <input type="hidden" id="atEditId">

        {{-- Nome --}}
        <div class="f-section">
            <div class="f-section-label">Nome da automação</div>
            <input type="text" class="form-control form-control-sm" id="atName"
                placeholder="Ex: Qualificar lead quando mencionar 'comprar'">
        </div>

        {{-- Gatilho --}}
        <div class="f-section">
            <div class="f-section-label">Quando (Gatilho)</div>
            <select class="form-select form-select-sm" id="atTriggerType" onchange="onTriggerChange()">
                <option value="">Selecione um gatilho...</option>
                <option value="message_received">💬 Mensagem recebida</option>
                <option value="conversation_created">🆕 Nova conversa criada</option>
                <option value="lead_created">👤 Lead criado</option>
                <option value="lead_stage_changed">➡️ Lead movido de etapa</option>
                <option value="lead_won">🏆 Lead marcado como ganho</option>
                <option value="lead_lost">❌ Lead marcado como perdido</option>
            </select>
            <div id="atTriggerConfig" class="mt-3"></div>
        </div>

        {{-- Condições --}}
        <div class="f-section" id="atConditionsSection" style="display:none;">
            <div class="f-section-label">Condições (opcional)</div>
            <div id="atConditionsList"></div>
            <button type="button" class="btn-add-rule mt-1" onclick="addCondition()">
                <i class="bi bi-plus-circle"></i> Adicionar condição
            </button>
        </div>

        {{-- Ações --}}
        <div class="f-section">
            <div class="f-section-label">Então fazer (Ações)</div>
            <div id="atActionsList"></div>
            <button type="button" class="btn-add-rule mt-1" onclick="addAction()">
                <i class="bi bi-plus-circle"></i> Adicionar ação
            </button>
        </div>
    </div>
    <div class="at-drawer-foot">
        <button class="btn btn-light btn-sm" onclick="closeDrawer()">Cancelar</button>
        <button class="btn btn-primary btn-sm px-4" onclick="saveAutomation()">
            <i class="bi bi-check2 me-1"></i> Salvar
        </button>
    </div>
</div>

@php
    $pipelinesJs  = $pipelines->map(fn($p) => [
        'id'     => $p->id,
        'name'   => $p->name,
        'stages' => $p->stages->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values(),
    ])->values();
    $noteVarsHint = 'Vars: {{contact_name}}, {{phone}}, {{stage}}, {{pipeline}}';
    $msgVarsHint  = 'Vars: {{contact_name}}, {{phone}}, {{stage}}';
@endphp

<script>
const PIPELINES      = @json($pipelinesJs);
const USERS          = @json($users);
const AI_AGENTS      = @json($aiAgents);
const CHATBOT_FLOWS  = @json($chatbotFlows);
const WAHA_CONNECTED = {{ $wahaConnected ? 'true' : 'false' }};
const LEAD_TAGS      = @json($leadTags->values());
const LEAD_SOURCES   = @json($leadSources->values());
const WAPP_TAGS      = @json($whatsappTags->pluck('name')->values());
const NOTE_VARS_HINT = @json($noteVarsHint);
const MSG_VARS_HINT  = @json($msgVarsHint);

let atIdx = 0;
let automationsData = {};
@foreach($automations as $auto)
automationsData[{{ $auto->id }}] = @json($auto);
@endforeach

// ── Drawer ────────────────────────────────────────────────────────────────────
function openDrawer(editId = null) {
    document.getElementById('atDrawerTitle').textContent = editId ? 'Editar Automação' : 'Nova Automação';
    document.getElementById('atEditId').value = editId || '';
    document.getElementById('atName').value = '';
    document.getElementById('atTriggerType').value = '';
    document.getElementById('atTriggerConfig').innerHTML = '';
    document.getElementById('atConditionsList').innerHTML = '';
    document.getElementById('atActionsList').innerHTML = '';
    document.getElementById('atConditionsSection').style.display = 'none';
    atIdx = 0;

    if (editId && automationsData[editId]) {
        const a = automationsData[editId];
        document.getElementById('atName').value = a.name;
        document.getElementById('atTriggerType').value = a.trigger_type;
        onTriggerChange();

        const tc = a.trigger_config || {};
        if (tc.channel) { const el = document.getElementById('tcChannel'); if (el) el.value = tc.channel; }
        if (tc.pipeline_id) {
            const el = document.getElementById('tcPipeline');
            if (el) { el.value = tc.pipeline_id; onTcPipelineChange(); }
        }
        if (tc.stage_id) { setTimeout(() => { const el = document.getElementById('tcStage'); if (el) el.value = tc.stage_id; }, 60); }
        if (tc.source) { const el = document.getElementById('tcSource'); if (el) el.value = tc.source; }

        (a.conditions || []).forEach(c => addCondition(c));
        (a.actions    || []).forEach(ac => addAction(ac));
    }

    document.getElementById('atOverlay').classList.add('open');
    document.getElementById('atDrawer').classList.add('open');
}
function closeDrawer() {
    document.getElementById('atOverlay').classList.remove('open');
    document.getElementById('atDrawer').classList.remove('open');
}
function editAutomation(id) { openDrawer(id); }

// ── Trigger config ─────────────────────────────────────────────────────────────
function onTriggerChange() {
    const type = document.getElementById('atTriggerType').value;
    const cont = document.getElementById('atTriggerConfig');
    const condSec = document.getElementById('atConditionsSection');
    cont.innerHTML = '';
    condSec.style.display = type ? 'block' : 'none';
    if (!type) return;

    let html = '';
    if (['message_received','conversation_created'].includes(type)) {
        html += `<div class="mb-2">
            <label class="form-label form-label-sm mb-1" style="font-size:12.5px;">Canal</label>
            <select class="form-select form-select-sm" id="tcChannel">
                <option value="both">WhatsApp e Instagram</option>
                <option value="whatsapp">Somente WhatsApp</option>
                <option value="instagram">Somente Instagram</option>
            </select></div>`;
    }
    if (type === 'lead_stage_changed') {
        const pOpts = PIPELINES.map(p => `<option value="${p.id}">${h(p.name)}</option>`).join('');
        html += `<div class="mb-2">
            <label class="form-label form-label-sm mb-1" style="font-size:12.5px;">Funil <small class="text-muted">(opcional)</small></label>
            <select class="form-select form-select-sm" id="tcPipeline" onchange="onTcPipelineChange()">
                <option value="">Qualquer funil</option>${pOpts}
            </select></div>
            <div class="mb-2">
            <label class="form-label form-label-sm mb-1" style="font-size:12.5px;">Etapa destino <small class="text-muted">(opcional)</small></label>
            <select class="form-select form-select-sm" id="tcStage">
                <option value="">Qualquer etapa</option>
            </select></div>`;
    }
    if (['lead_created','lead_won','lead_lost'].includes(type)) {
        const pOpts = PIPELINES.map(p => `<option value="${p.id}">${h(p.name)}</option>`).join('');
        html += `<div class="mb-2">
            <label class="form-label form-label-sm mb-1" style="font-size:12.5px;">Funil <small class="text-muted">(opcional)</small></label>
            <select class="form-select form-select-sm" id="tcPipeline">
                <option value="">Qualquer funil</option>${pOpts}
            </select></div>`;
    }
    if (type === 'lead_created') {
        const srcOpts = LEAD_SOURCES.map(s => `<option value="${s}">${h(s)}</option>`).join('');
        html += `<div class="mb-2">
            <label class="form-label form-label-sm mb-1" style="font-size:12.5px;">Origem <small class="text-muted">(opcional)</small></label>
            <select class="form-select form-select-sm" id="tcSource">
                <option value="">Qualquer origem</option>${srcOpts}
            </select></div>`;
    }
    cont.innerHTML = html;
}
function onTcPipelineChange() {
    const pId = parseInt(document.getElementById('tcPipeline')?.value);
    const sel  = document.getElementById('tcStage');
    if (!sel) return;
    const p = PIPELINES.find(p => p.id === pId);
    sel.innerHTML = '<option value="">Qualquer etapa</option>' +
        (p ? p.stages.map(s => `<option value="${s.id}">${h(s.name)}</option>`).join('') : '');
}

// ── Conditions ─────────────────────────────────────────────────────────────────
function addCondition(existing = null) {
    const idx  = atIdx++;
    const list = document.getElementById('atConditionsList');
    const div  = document.createElement('div');
    div.className = 'rule-card';
    div.id = `cond-${idx}`;
    div.innerHTML = `
        <button type="button" class="btn-icon danger btn-remove" onclick="document.getElementById('cond-${idx}').remove()">
            <i class="bi bi-x"></i>
        </button>
        <div class="rule-head pe-4">
            <select class="form-select form-select-sm" id="cf-${idx}" onchange="onCondFieldChange(${idx})" style="max-width:185px;">
                <option value="message_body">Corpo da mensagem</option>
                <option value="lead_source">Origem do lead</option>
                <option value="lead_tag">Tag do lead</option>
                <option value="conversation_tag">Tag da conversa</option>
            </select>
            <select class="form-select form-select-sm" id="cop-${idx}" style="max-width:150px;">
                <option value="contains">contém</option>
                <option value="not_contains">não contém</option>
                <option value="equals">é igual a</option>
                <option value="starts_with">começa com</option>
            </select>
        </div>
        <div class="rule-extra" id="cval-wrap-${idx}">
            <input type="text" class="form-control form-control-sm" id="cval-${idx}" placeholder="Valor...">
        </div>`;
    list.appendChild(div);

    if (existing) {
        document.getElementById(`cf-${idx}`).value  = existing.field    || 'message_body';
        document.getElementById(`cop-${idx}`).value = existing.operator || 'contains';
        onCondFieldChange(idx);
        setTimeout(() => {
            if (['lead_tag','conversation_tag'].includes(existing.field) && existing.value) {
                addTagChip(`cval-${idx}`, existing.value);
            } else {
                const el = document.getElementById(`cval-${idx}`);
                if (el) el.value = existing.value || '';
            }
        }, 30);
    }
}
function onCondFieldChange(idx) {
    const field = document.getElementById(`cf-${idx}`)?.value;
    const opSel = document.getElementById(`cop-${idx}`);
    const wrap  = document.getElementById(`cval-wrap-${idx}`);
    if (!opSel || !wrap) return;

    if (field === 'lead_source') {
        opSel.innerHTML = `<option value="equals">é</option><option value="not_equals">não é</option>`;
        const srcOpts = LEAD_SOURCES.map(s => `<option value="${s}">${h(s)}</option>`).join('');
        wrap.innerHTML = `<select class="form-select form-select-sm" id="cval-${idx}">
            <option value="">Selecione a origem...</option>${srcOpts}</select>`;
    } else if (field === 'lead_tag') {
        opSel.innerHTML = `<option value="contains">contém</option><option value="not_contains">não contém</option>`;
        wrap.innerHTML  = buildTagSelect(`cval-${idx}`, LEAD_TAGS, []);
    } else if (field === 'conversation_tag') {
        opSel.innerHTML = `<option value="contains">contém</option><option value="not_contains">não contém</option>`;
        wrap.innerHTML  = buildTagSelect(`cval-${idx}`, WAPP_TAGS, []);
    } else {
        opSel.innerHTML = `
            <option value="contains">contém</option>
            <option value="not_contains">não contém</option>
            <option value="equals">é igual a</option>
            <option value="starts_with">começa com</option>`;
        wrap.innerHTML = `<input type="text" class="form-control form-control-sm" id="cval-${idx}" placeholder="Valor...">`;
    }
}

// ── Actions ─────────────────────────────────────────────────────────────────────
function addAction(existing = null) {
    const idx  = atIdx++;
    const list = document.getElementById('atActionsList');
    const opts = [
        ['add_tag_lead',          '🏷️ Adicionar tag ao lead'],
        ['remove_tag_lead',       '🗑️ Remover tag do lead'],
        ['add_tag_conversation',  '💬 Adicionar tag à conversa'],
        ['move_to_stage',         '➡️ Mover para etapa do funil'],
        ['set_lead_source',       '📌 Definir origem do lead'],
        ['assign_to_user',        '👤 Atribuir a usuário'],
        ['add_note',              '📝 Adicionar nota ao lead'],
        ['assign_ai_agent',       '🤖 Atribuir agente de IA'],
        ['assign_chatbot_flow',   '🔀 Atribuir fluxo de chatbot'],
        ['close_conversation',    '🔒 Fechar conversa'],
        ['send_whatsapp_message', '📲 Enviar mensagem WhatsApp'],
    ].map(([v,l]) => `<option value="${v}">${l}</option>`).join('');

    const div = document.createElement('div');
    div.className = 'rule-card';
    div.id = `act-${idx}`;
    div.innerHTML = `
        <button type="button" class="btn-icon danger btn-remove" onclick="document.getElementById('act-${idx}').remove()">
            <i class="bi bi-x"></i>
        </button>
        <div class="rule-head pe-4">
            <select class="form-select form-select-sm" id="atype-${idx}" onchange="onActionTypeChange(${idx})" style="max-width:280px;">
                ${opts}
            </select>
        </div>
        <div class="rule-extra" id="abody-${idx}"></div>`;
    list.appendChild(div);

    if (existing?.type) document.getElementById(`atype-${idx}`).value = existing.type;
    onActionTypeChange(idx, existing?.config || null);
}

function onActionTypeChange(idx, prefill = null) {
    const type = document.getElementById(`atype-${idx}`)?.value;
    const body = document.getElementById(`abody-${idx}`);
    if (!body) return;
    body.innerHTML = '';

    if (type === 'add_tag_lead' || type === 'remove_tag_lead') {
        body.innerHTML = buildTagSelect(`aval-${idx}`, LEAD_TAGS, prefill?.tags || []);
    }
    else if (type === 'add_tag_conversation') {
        body.innerHTML = buildTagSelect(`aval-${idx}`, WAPP_TAGS, prefill?.tags || []);
    }
    else if (type === 'move_to_stage') {
        const pOpts = PIPELINES.map(p => `<option value="${p.id}">${h(p.name)}</option>`).join('');
        body.innerHTML = `
            <div class="d-flex gap-2 flex-wrap">
                <select class="form-select form-select-sm" id="apipe-${idx}" onchange="onActPipelineChange(${idx})" style="flex:1;min-width:130px;">
                    <option value="">Funil...</option>${pOpts}
                </select>
                <select class="form-select form-select-sm" id="astage-${idx}" style="flex:1;min-width:130px;">
                    <option value="">Etapa...</option>
                </select>
            </div>`;
        if (prefill?.stage_id) {
            const p = PIPELINES.find(p => p.stages.some(s => s.id == prefill.stage_id));
            if (p) {
                document.getElementById(`apipe-${idx}`).value = p.id;
                onActPipelineChange(idx);
                setTimeout(() => { document.getElementById(`astage-${idx}`).value = prefill.stage_id; }, 60);
            }
        }
    }
    else if (type === 'set_lead_source') {
        const srcOpts = LEAD_SOURCES.map(s => `<option value="${s}">${h(s)}</option>`).join('');
        body.innerHTML = `<select class="form-select form-select-sm" id="aval-${idx}">
            <option value="">Selecione a origem...</option>${srcOpts}
        </select>`;
        if (prefill?.source) document.getElementById(`aval-${idx}`).value = prefill.source;
    }
    else if (type === 'assign_to_user') {
        const uOpts = USERS.map(u => `<option value="${u.id}">${h(u.name)}</option>`).join('');
        body.innerHTML = `<select class="form-select form-select-sm" id="aval-${idx}">
            <option value="">Selecione o usuário...</option>${uOpts}
        </select>`;
        if (prefill?.user_id) document.getElementById(`aval-${idx}`).value = prefill.user_id;
    }
    else if (type === 'add_note') {
        body.innerHTML = `<textarea class="form-control form-control-sm" id="aval-${idx}" rows="2"
            placeholder="${NOTE_VARS_HINT}"></textarea>`;
        if (prefill?.body) document.getElementById(`aval-${idx}`).value = prefill.body;
    }
    else if (type === 'assign_ai_agent') {
        if (!AI_AGENTS.length) { body.innerHTML = `<p class="text-muted" style="font-size:12px;margin-top:6px;">Nenhum agente de IA ativo (WhatsApp).</p>`; return; }
        const aOpts = AI_AGENTS.map(a => `<option value="${a.id}">${h(a.name)}</option>`).join('');
        body.innerHTML = `<select class="form-select form-select-sm" id="aval-${idx}">
            <option value="">Selecione o agente...</option>${aOpts}
        </select>`;
        if (prefill?.ai_agent_id) document.getElementById(`aval-${idx}`).value = prefill.ai_agent_id;
    }
    else if (type === 'assign_chatbot_flow') {
        if (!CHATBOT_FLOWS.length) { body.innerHTML = `<p class="text-muted" style="font-size:12px;margin-top:6px;">Nenhum fluxo de chatbot ativo.</p>`; return; }
        const fOpts = CHATBOT_FLOWS.map(f => `<option value="${f.id}">${h(f.name)}</option>`).join('');
        body.innerHTML = `<select class="form-select form-select-sm" id="aval-${idx}">
            <option value="">Selecione o fluxo...</option>${fOpts}
        </select>`;
        if (prefill?.chatbot_flow_id) document.getElementById(`aval-${idx}`).value = prefill.chatbot_flow_id;
    }
    else if (type === 'close_conversation') {
        body.innerHTML = `<p class="text-muted" style="font-size:12px;margin-top:6px;"><i class="bi bi-info-circle me-1"></i>A conversa vinculada ao lead será fechada automaticamente.</p>`;
    }
    else if (type === 'send_whatsapp_message') {
        if (!WAHA_CONNECTED) { body.innerHTML = `<p class="text-warning" style="font-size:12px;margin-top:6px;"><i class="bi bi-exclamation-triangle me-1"></i>Nenhuma instância WhatsApp conectada.</p>`; return; }
        body.innerHTML = `<textarea class="form-control form-control-sm" id="aval-${idx}" rows="2"
            placeholder="${MSG_VARS_HINT}"></textarea>`;
        if (prefill?.message) document.getElementById(`aval-${idx}`).value = prefill.message;
    }
}

function onActPipelineChange(idx) {
    const pId = parseInt(document.getElementById(`apipe-${idx}`)?.value);
    const sel  = document.getElementById(`astage-${idx}`);
    if (!sel) return;
    const p = PIPELINES.find(p => p.id === pId);
    sel.innerHTML = '<option value="">Etapa...</option>' +
        (p ? p.stages.map(s => `<option value="${s.id}">${h(s.name)}</option>`).join('') : '');
}

// ── Tag multi-select widget ────────────────────────────────────────────────────
function buildTagSelect(inputId, suggestions, selectedTags) {
    const chips = (selectedTags || []).map(t =>
        `<span class="tag-chip-item" data-val="${h(t)}">${h(t)} <button type="button" onclick="removeTagChip(this)">&times;</button></span>`
    ).join('');
    const sugsJson = JSON.stringify(suggestions);
    return `
        <div class="position-relative">
            <div class="tag-select-wrap" id="${inputId}-wrap" onclick="document.getElementById('${inputId}-input').focus()">
                ${chips}
                <input type="text" id="${inputId}-input" class="tag-input-ghost" placeholder="Digite ou selecione..."
                    autocomplete="off"
                    oninput="showTagSugs('${inputId}', this.value)"
                    onfocus="showTagSugs('${inputId}', this.value)"
                    onkeydown="tagKeydown(event,'${inputId}')">
            </div>
            <div class="tag-suggestions" id="${inputId}-sug" style="display:none;"></div>
        </div>`;
}

// Store suggestions per widget
const _tagSugCache = {};
function showTagSugs(id, query) {
    // Find suggestions from the DOM context by looking at parent widget
    // We'll use a data attribute on the input for suggestions
    const inputEl = document.getElementById(`${id}-input`);
    if (!inputEl) return;
    // Get suggestions from closure — find which array to use
    // We determine by checking which LEAD_TAGS or WAPP_TAGS list contains items
    let suggestions = _tagSugCache[id] || [];
    const sug  = document.getElementById(`${id}-sug`);
    if (!sug) return;
    const lower = query.toLowerCase().trim();
    const existing = getTagValues(id);
    const filtered = suggestions.filter(s => !existing.includes(s) && (!lower || s.toLowerCase().includes(lower)));

    let html = filtered.map(s => `<div class="tag-sug-item" onmousedown="addTagChip('${id}','${h(s)}')">${h(s)}</div>`).join('');
    if (lower && !suggestions.some(s => s.toLowerCase() === lower) && !existing.includes(lower)) {
        html += `<div class="tag-sug-item" style="color:#3b82f6;" onmousedown="addTagChip('${id}','${lower}')"><i class="bi bi-plus me-1"></i>Adicionar "${lower}"</div>`;
    }
    sug.innerHTML = html || '<div class="tag-sug-item text-muted" style="font-size:12px;">Sem sugestões</div>';
    sug.style.display = 'block';
}

// Override buildTagSelect to register suggestions in cache
function buildTagSelect(inputId, suggestions, selectedTags) {
    _tagSugCache[inputId] = suggestions;
    const chips = (selectedTags || []).map(t =>
        `<span class="tag-chip-item" data-val="${h(t)}">${h(t)} <button type="button" onclick="removeTagChip(this)">&times;</button></span>`
    ).join('');
    return `
        <div class="position-relative">
            <div class="tag-select-wrap" id="${inputId}-wrap" onclick="document.getElementById('${inputId}-input').focus()">
                ${chips}
                <input type="text" id="${inputId}-input" class="tag-input-ghost" placeholder="Digite ou selecione..."
                    autocomplete="off"
                    oninput="showTagSugs('${inputId}', this.value)"
                    onfocus="showTagSugs('${inputId}', this.value)"
                    onkeydown="tagKeydown(event,'${inputId}')">
            </div>
            <div class="tag-suggestions" id="${inputId}-sug" style="display:none;"></div>
        </div>`;
}

function tagKeydown(e, id) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        const val = e.target.value.trim();
        if (val) addTagChip(id, val);
    } else if (e.key === 'Escape') {
        const sug = document.getElementById(`${id}-sug`);
        if (sug) sug.style.display = 'none';
    }
}
function addTagChip(id, value) {
    const wrap  = document.getElementById(`${id}-wrap`);
    const input = document.getElementById(`${id}-input`);
    const sug   = document.getElementById(`${id}-sug`);
    if (!wrap || !input) return;
    if (getTagValues(id).includes(value)) { input.value = ''; if (sug) sug.style.display = 'none'; return; }
    const chip = document.createElement('span');
    chip.className = 'tag-chip-item';
    chip.dataset.val = value;
    chip.innerHTML = `${h(value)} <button type="button" onclick="removeTagChip(this)">&times;</button>`;
    wrap.insertBefore(chip, input);
    input.value = '';
    if (sug) sug.style.display = 'none';
}
function removeTagChip(btn) { btn.closest('.tag-chip-item').remove(); }
function getTagValues(id) {
    const wrap = document.getElementById(`${id}-wrap`);
    if (!wrap) return [];
    return [...wrap.querySelectorAll('.tag-chip-item')].map(c => c.dataset.val).filter(Boolean);
}

document.addEventListener('click', e => {
    document.querySelectorAll('.tag-suggestions').forEach(el => {
        if (!el.parentElement?.contains(e.target)) el.style.display = 'none';
    });
});

// ── Save ──────────────────────────────────────────────────────────────────────
function saveAutomation() {
    const editId      = document.getElementById('atEditId').value;
    const name        = document.getElementById('atName').value.trim();
    const triggerType = document.getElementById('atTriggerType').value;

    if (!name)        { toastr.warning('Informe o nome da automação.'); return; }
    if (!triggerType) { toastr.warning('Selecione um gatilho.'); return; }

    const tc = {};
    const chanEl = document.getElementById('tcChannel');  if (chanEl && chanEl.value) tc.channel = chanEl.value;
    const pipeEl = document.getElementById('tcPipeline'); if (pipeEl && pipeEl.value) tc.pipeline_id = parseInt(pipeEl.value);
    const stgEl  = document.getElementById('tcStage');    if (stgEl  && stgEl.value)  tc.stage_id   = parseInt(stgEl.value);
    const srcEl  = document.getElementById('tcSource');   if (srcEl  && srcEl.value)  tc.source     = srcEl.value;

    const conditions = [];
    document.querySelectorAll('[id^="cond-"]').forEach(el => {
        const idx   = el.id.replace('cond-','');
        const field = document.getElementById(`cf-${idx}`)?.value;
        const op    = document.getElementById(`cop-${idx}`)?.value;
        if (!field) return;
        let value = '';
        if (['lead_tag','conversation_tag'].includes(field)) {
            const vals = getTagValues(`cval-${idx}`);
            if (!vals.length) return;
            value = vals[0];
        } else {
            value = (document.getElementById(`cval-${idx}`)?.value || '').trim();
        }
        if (value) conditions.push({ field, operator: op, value });
    });

    const actions = [];
    let err = false;
    document.querySelectorAll('[id^="act-"]').forEach(el => {
        if (err) return;
        const idx  = el.id.replace('act-','');
        const type = document.getElementById(`atype-${idx}`)?.value;
        if (!type) return;
        const config = {};
        if (['add_tag_lead','remove_tag_lead','add_tag_conversation'].includes(type)) {
            config.tags = getTagValues(`aval-${idx}`);
            if (!config.tags.length) { toastr.warning('Selecione ao menos uma tag.'); err = true; return; }
        } else if (type === 'move_to_stage') {
            const v = document.getElementById(`astage-${idx}`)?.value;
            if (!v) { toastr.warning('Selecione a etapa destino.'); err = true; return; }
            config.stage_id = parseInt(v);
        } else if (type === 'set_lead_source') {
            config.source = document.getElementById(`aval-${idx}`)?.value || '';
            if (!config.source) { toastr.warning('Selecione a origem.'); err = true; return; }
        } else if (type === 'assign_to_user') {
            config.user_id = parseInt(document.getElementById(`aval-${idx}`)?.value || 0);
            if (!config.user_id) { toastr.warning('Selecione o usuário.'); err = true; return; }
        } else if (type === 'add_note') {
            config.body = (document.getElementById(`aval-${idx}`)?.value || '').trim();
            if (!config.body) { toastr.warning('Informe o texto da nota.'); err = true; return; }
        } else if (type === 'assign_ai_agent') {
            config.ai_agent_id = parseInt(document.getElementById(`aval-${idx}`)?.value || 0);
            if (!config.ai_agent_id) { toastr.warning('Selecione o agente de IA.'); err = true; return; }
        } else if (type === 'assign_chatbot_flow') {
            config.chatbot_flow_id = parseInt(document.getElementById(`aval-${idx}`)?.value || 0);
            if (!config.chatbot_flow_id) { toastr.warning('Selecione o fluxo.'); err = true; return; }
        } else if (type === 'send_whatsapp_message') {
            config.message = (document.getElementById(`aval-${idx}`)?.value || '').trim();
            if (!config.message) { toastr.warning('Informe a mensagem.'); err = true; return; }
        }
        actions.push({ type, config });
    });
    if (err) return;
    if (!actions.length) { toastr.warning('Adicione ao menos uma ação.'); return; }

    const isEdit = !!editId;
    fetch(isEdit ? `/configuracoes/automacoes/${editId}` : '/configuracoes/automacoes', {
        method: isEdit ? 'PUT' : 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
        body: JSON.stringify({ name, trigger_type: triggerType, trigger_config: tc, conditions, actions }),
    }).then(r => r.json()).then(res => {
        if (res.success) { toastr.success(isEdit ? 'Automação atualizada.' : 'Automação criada.'); closeDrawer(); setTimeout(() => location.reload(), 500); }
        else { toastr.error(res.message || 'Erro ao salvar.'); }
    }).catch(() => toastr.error('Erro de comunicação.'));
}

// ── Toggle / Delete ───────────────────────────────────────────────────────────
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

function h(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endsection
