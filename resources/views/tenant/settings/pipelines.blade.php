@extends('tenant.layouts.app')

@php($title = 'Configurações')
@php($pageIcon = 'gear')

@push('styles')
<style>
    .settings-tabs {
        display: flex;
        gap: 4px;
        border-bottom: 2px solid #e8eaf0;
        margin-bottom: 24px;
    }
    .settings-tab {
        padding: 10px 20px;
        font-size: 13.5px;
        font-weight: 600;
        color: #6b7280;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        cursor: pointer;
        background: none;
        border-top: none;
        border-left: none;
        border-right: none;
        transition: color .15s;
    }
    .settings-tab:hover { color: #374151; }
    .settings-tab.active { color: #3B82F6; border-bottom-color: #3B82F6; }

    .tab-pane { display: none; }
    .tab-pane.active { display: block; }

    .pipeline-card {
        background: #fff;
        border: 1px solid #e8eaf0;
        border-radius: 12px;
        margin-bottom: 12px;
        overflow: hidden;
    }
    .pipeline-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 18px;
        cursor: pointer;
        user-select: none;
        background: #fff;
        transition: background .1s;
    }
    .pipeline-header:hover { background: #f9fafb; }
    .pipeline-color-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
    .pipeline-name { font-weight: 600; font-size: 14px; color: #1a1d23; flex: 1; }
    .default-badge {
        font-size: 10px; font-weight: 700; padding: 2px 7px;
        border-radius: 99px; background: #dbeafe; color: #3B82F6;
        text-transform: uppercase; letter-spacing: .04em;
    }
    .pipeline-actions { display: flex; gap: 6px; align-items: center; }
    .btn-icon {
        width: 28px; height: 28px; border-radius: 7px; border: 1px solid #e8eaf0;
        background: #fff; color: #6b7280;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 13px; transition: all .15s;
    }
    .btn-icon:hover { background: #f0f4ff; color: #374151; }
    .btn-icon.danger:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

    .pipeline-body { display: none; border-top: 1px solid #f0f2f7; }
    .pipeline-body.open { display: block; }

    .stages-list { list-style: none; margin: 0; padding: 8px; }
    .stage-item {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px; border-radius: 8px; transition: background .1s;
    }
    .stage-item:hover { background: #f8faff; }
    .stage-drag-handle { color: #d1d5db; cursor: grab; font-size: 14px; }
    .stage-color-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .stage-name { flex: 1; font-size: 13px; font-weight: 600; color: #374151; }
    .stage-badge { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 99px; text-transform: uppercase; letter-spacing: .04em; }
    .won-badge  { background: #d1fae5; color: #059669; }
    .lost-badge { background: #fee2e2; color: #ef4444; }

    .add-stage-btn {
        display: flex; align-items: center; gap: 6px;
        padding: 10px 20px; font-size: 12.5px; font-weight: 600; color: #6b7280;
        cursor: pointer; border-top: 1px solid #f0f2f7; background: #fafafa;
        transition: background .1s; border: none; width: 100%; text-align: left;
    }
    .add-stage-btn:hover { background: #f0f4ff; color: #3B82F6; }

    .reason-table-wrap { background: #fff; border: 1px solid #e8eaf0; border-radius: 12px; overflow: hidden; }
    .reason-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
    .reason-table thead th {
        padding: 11px 16px; font-size: 11.5px; font-weight: 700; color: #9ca3af;
        text-transform: uppercase; letter-spacing: .06em; background: #fafafa;
        border-bottom: 1px solid #f0f2f7;
    }
    .reason-table tbody tr { border-bottom: 1px solid #f7f8fa; }
    .reason-table tbody tr:last-child { border-bottom: none; }
    .reason-table tbody td { padding: 12px 16px; color: #374151; vertical-align: middle; }

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

    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.45); z-index: 1000;
        align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
        background: #fff; border-radius: 14px; padding: 28px;
        width: 440px; max-width: 95vw;
        box-shadow: 0 20px 60px rgba(0,0,0,.18);
    }
    .modal-title { font-size: 16px; font-weight: 700; color: #1a1d23; margin-bottom: 18px; }
    .form-group { margin-bottom: 14px; }
    .form-label { display: block; font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .04em; }
    .form-control {
        width: 100%; padding: 9px 12px; border: 1.5px solid #e8eaf0;
        border-radius: 9px; font-size: 13.5px; outline: none;
        font-family: inherit; transition: border-color .15s; box-sizing: border-box;
    }
    .form-control:focus { border-color: #3B82F6; }
    .color-row { display: flex; gap: 8px; align-items: center; }
    .color-input { width: 42px; height: 36px; padding: 2px; border: 1.5px solid #e8eaf0; border-radius: 9px; cursor: pointer; }
    .checkbox-row { display: flex; gap: 16px; }
    .checkbox-label { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #374151; cursor: pointer; }
    .modal-footer { display: flex; gap: 8px; justify-content: flex-end; margin-top: 20px; }
    .btn-cancel {
        padding: 8px 18px; border-radius: 8px; border: 1.5px solid #e8eaf0;
        background: #fff; font-size: 13px; font-weight: 600; color: #6b7280;
        cursor: pointer; transition: all .15s;
    }
    .btn-cancel:hover { background: #f0f2f7; }
    .btn-save {
        padding: 8px 20px; border-radius: 8px; border: none;
        background: #3B82F6; color: #fff; font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
    }
    .btn-save:hover { background: #2563eb; }
    .btn-save:disabled { opacity: .6; cursor: not-allowed; }

    .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
    .section-title  { font-size: 15px; font-weight: 700; color: #1a1d23; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div class="settings-tabs">
        <button class="settings-tab active" data-tab="pipelines">Funis &amp; Etapas</button>
        <button class="settings-tab" data-tab="reasons">Motivos de Perda</button>
    </div>

    {{-- TAB: PIPELINES --}}
    <div class="tab-pane active" id="tab-pipelines">
        <div class="section-header">
            <div class="section-title">Funis</div>
            <button class="btn-primary-sm" id="btnNovoPipeline">
                <i class="bi bi-plus-lg"></i> Novo Funil
            </button>
        </div>

        <div id="pipelinesContainer">
            @forelse($pipelines as $pipeline)
            <div class="pipeline-card" data-pipeline-id="{{ $pipeline->id }}">
                <div class="pipeline-header" onclick="togglePipeline(this)">
                    <span class="pipeline-color-dot" style="background: {{ $pipeline->color }};"></span>
                    <span class="pipeline-name">{{ $pipeline->name }}</span>
                    @if($pipeline->is_default)
                    <span class="default-badge">Padrão</span>
                    @endif
                    <div class="pipeline-actions" onclick="event.stopPropagation()">
                        <button class="btn-icon" title="Definir como padrão" onclick="setDefaultPipeline({{ $pipeline->id }}, '{{ addslashes($pipeline->name) }}', '{{ $pipeline->color }}')">
                            <i class="bi bi-star{{ $pipeline->is_default ? '-fill' : '' }}" style="{{ $pipeline->is_default ? 'color:#f59e0b;' : '' }}"></i>
                        </button>
                        <button class="btn-icon" title="Editar" onclick="openEditPipeline({{ $pipeline->id }}, '{{ addslashes($pipeline->name) }}', '{{ $pipeline->color }}', {{ $pipeline->auto_create_lead ? 'true' : 'false' }}, {{ $pipeline->auto_create_from_whatsapp ? 'true' : 'false' }}, {{ $pipeline->auto_create_from_instagram ? 'true' : 'false' }})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-icon danger" title="Excluir" onclick="deletePipeline({{ $pipeline->id }}, this)">
                            <i class="bi bi-trash"></i>
                        </button>
                        <i class="bi bi-chevron-down" style="font-size:13px;color:#9ca3af;transition:transform .2s;" id="chevron-{{ $pipeline->id }}"></i>
                    </div>
                </div>
                <div class="pipeline-body" id="body-{{ $pipeline->id }}">
                    <ul class="stages-list" data-pipeline-id="{{ $pipeline->id }}" id="stages-{{ $pipeline->id }}">
                        @foreach($pipeline->stages as $stage)
                        <li class="stage-item" data-stage-id="{{ $stage->id }}">
                            <i class="bi bi-grip-vertical stage-drag-handle"></i>
                            <span class="stage-color-dot" style="background: {{ $stage->color }};"></span>
                            <span class="stage-name">{{ $stage->name }}</span>
                            @if($stage->is_won)  <span class="stage-badge won-badge">Ganho</span>  @endif
                            @if($stage->is_lost) <span class="stage-badge lost-badge">Perdido</span> @endif
                            <div style="display:flex;gap:5px;">
                                <button class="btn-icon" onclick="openEditStage({{ $pipeline->id }}, {{ $stage->id }}, '{{ addslashes($stage->name) }}', '{{ $stage->color }}', {{ $stage->is_won ? 'true' : 'false' }}, {{ $stage->is_lost ? 'true' : 'false' }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn-icon danger" onclick="deleteStage({{ $pipeline->id }}, {{ $stage->id }}, this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    <button class="add-stage-btn" onclick="openAddStage({{ $pipeline->id }})">
                        <i class="bi bi-plus-lg"></i> Adicionar etapa
                    </button>
                </div>
            </div>
            @empty
            <div id="emptyPipelines" style="text-align:center;padding:60px 20px;color:#9ca3af;">
                <i class="bi bi-diagram-3" style="font-size:40px;opacity:.3;display:block;margin-bottom:12px;"></i>
                <p style="font-size:14px;margin:0;">Nenhum funil criado ainda.</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- TAB: MOTIVOS DE PERDA --}}
    <div class="tab-pane" id="tab-reasons">
        <div class="section-header">
            <div class="section-title">Motivos de Perda</div>
            <button class="btn-primary-sm" id="btnNovoMotivo">
                <i class="bi bi-plus-lg"></i> Novo Motivo
            </button>
        </div>

        <div class="reason-table-wrap">
            <table class="reason-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th style="width:100px;text-align:center;">Ativo</th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody id="reasonsBody">
                    @forelse($reasons ?? [] as $reason)
                    <tr data-reason-id="{{ $reason->id }}">
                        <td class="reason-name-cell">{{ $reason->name }}</td>
                        <td style="text-align:center;">
                            <label class="toggle">
                                <input type="checkbox" {{ $reason->is_active ? 'checked' : '' }}
                                       onchange="toggleReason({{ $reason->id }}, '{{ addslashes($reason->name) }}', this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </td>
                        <td>
                            <div style="display:flex;gap:5px;justify-content:flex-end;">
                                <button class="btn-icon" onclick="openEditReason({{ $reason->id }}, '{{ addslashes($reason->name) }}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn-icon danger" onclick="deleteReason({{ $reason->id }}, this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr id="emptyReasons">
                        <td colspan="3" style="text-align:center;padding:40px;color:#9ca3af;">Nenhum motivo cadastrado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- MODAL: Pipeline --}}
<div class="modal-overlay" id="modalPipeline">
    <div class="modal-box">
        <div class="modal-title" id="modalPipelineTitle">Novo Funil</div>
        <input type="hidden" id="pipelineId">
        <div class="form-group">
            <label class="form-label">Nome do Funil</label>
            <input type="text" id="pipelineName" class="form-control" placeholder="Ex: Vendas Principais">
        </div>
        <div class="form-group">
            <label class="form-label">Cor</label>
            <div class="color-row">
                <input type="color" id="pipelineColor" class="color-input" value="#3B82F6">
                <input type="text" id="pipelineColorText" class="form-control" value="#3B82F6" placeholder="#3B82F6" style="flex:1;">
            </div>
        </div>
        <div class="form-group" style="margin-top:14px;">
            <label class="form-label" style="margin-bottom:8px;">Auto-criar lead</label>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <label class="toggle">
                    <input type="checkbox" id="autoCreateLead" checked onchange="toggleChannelToggles()">
                    <span class="toggle-slider"></span>
                </label>
                <span style="font-size:12px;color:#374151;">Criar lead ao receber mensagem</span>
            </div>
            <div id="channelToggles" style="display:flex;gap:16px;padding-left:4px;">
                <div style="display:flex;align-items:center;gap:6px;">
                    <label class="toggle">
                        <input type="checkbox" id="autoCreateWhatsapp" checked>
                        <span class="toggle-slider"></span>
                    </label>
                    <span style="font-size:12px;color:#6b7280;"><i class="bi bi-whatsapp" style="color:#25D366;"></i> WhatsApp</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <label class="toggle">
                        <input type="checkbox" id="autoCreateInstagram" checked>
                        <span class="toggle-slider"></span>
                    </label>
                    <span style="font-size:12px;color:#6b7280;"><i class="bi bi-instagram" style="color:#E1306C;"></i> Instagram</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closePipelineModal()">Cancelar</button>
            <button class="btn-save" id="btnSavePipeline" onclick="savePipeline()">Salvar</button>
        </div>
    </div>
</div>

{{-- MODAL: Stage --}}
<div class="modal-overlay" id="modalStage">
    <div class="modal-box">
        <div class="modal-title" id="modalStageTitle">Nova Etapa</div>
        <input type="hidden" id="stagePipelineId">
        <input type="hidden" id="stageId">
        <div class="form-group">
            <label class="form-label">Nome da Etapa</label>
            <input type="text" id="stageName" class="form-control" placeholder="Ex: Qualificação">
        </div>
        <div class="form-group">
            <label class="form-label">Cor</label>
            <div class="color-row">
                <input type="color" id="stageColor" class="color-input" value="#6366F1">
                <input type="text" id="stageColorText" class="form-control" value="#6366F1" placeholder="#6366F1" style="flex:1;">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Tipo da Etapa</label>
            <div class="checkbox-row">
                <label class="checkbox-label"><input type="checkbox" id="stageIsWon"> Etapa de Ganho</label>
                <label class="checkbox-label"><input type="checkbox" id="stageIsLost"> Etapa de Perda</label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeStageModal()">Cancelar</button>
            <button class="btn-save" id="btnSaveStage" onclick="saveStage()">Salvar</button>
        </div>
    </div>
</div>

{{-- MODAL: Motivo de Perda --}}
<div class="modal-overlay" id="modalReason">
    <div class="modal-box">
        <div class="modal-title" id="modalReasonTitle">Novo Motivo</div>
        <input type="hidden" id="reasonId">
        <div class="form-group">
            <label class="form-label">Nome do Motivo</label>
            <input type="text" id="reasonName" class="form-control" placeholder="Ex: Sem orçamento">
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeReasonModal()">Cancelar</button>
            <button class="btn-save" onclick="saveReason()">Salvar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const PIPE_STORE  = @json(route('settings.pipelines.store'));
const PIPE_UPD    = @json(route('settings.pipelines.update',  ['pipeline' => '__ID__']));
const PIPE_DEL    = @json(route('settings.pipelines.destroy', ['pipeline' => '__ID__']));
const STAGE_STORE = @json(route('settings.pipelines.stages.store',  ['pipeline' => '__ID__']));
const STAGE_UPD   = @json(route('settings.pipelines.stages.update', ['pipeline' => '__P__', 'stage' => '__S__']));
const STAGE_DEL   = @json(route('settings.pipelines.stages.destroy',['pipeline' => '__P__', 'stage' => '__S__']));
const STAGE_REORD = @json(route('settings.pipelines.stages.reorder',['pipeline' => '__ID__']));
const REASON_STORE = @json(route('settings.lost-reasons.store'));
const REASON_UPD   = @json(route('settings.lost-reasons.update',  ['reason' => '__ID__']));
const REASON_DEL   = @json(route('settings.lost-reasons.destroy', ['reason' => '__ID__']));
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;

/* ---- Tabs ---- */
document.querySelectorAll('.settings-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
    });
});

/* ---- Accordion ---- */
function togglePipeline(header) {
    const card    = header.closest('.pipeline-card');
    const id      = card.dataset.pipelineId;
    const body    = document.getElementById('body-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const isOpen  = body.classList.contains('open');
    body.classList.toggle('open', !isOpen);
    chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
}

/* ---- Sortable ---- */
document.querySelectorAll('.stages-list').forEach(el => initSortable(el));

function initSortable(el) {
    if (el._sortable) return;
    el._sortable = Sortable.create(el, {
        handle: '.stage-drag-handle',
        animation: 150,
        onEnd() {
            const pipelineId = el.dataset.pipelineId;
            const order = [...el.querySelectorAll('.stage-item')].map(li => li.dataset.stageId);
            fetch(STAGE_REORD.replace('__ID__', pipelineId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ order })
            });
        }
    });
}

/* ---- Pipeline Modal ---- */
document.getElementById('btnNovoPipeline').addEventListener('click', () => {
    document.getElementById('modalPipelineTitle').textContent = 'Novo Funil';
    document.getElementById('pipelineId').value = '';
    document.getElementById('pipelineName').value = '';
    document.getElementById('pipelineColor').value = '#3B82F6';
    document.getElementById('pipelineColorText').value = '#3B82F6';
    document.getElementById('modalPipeline').classList.add('open');
    setTimeout(() => document.getElementById('pipelineName').focus(), 100);
});

function openEditPipeline(id, name, color, autoCreate = true, autoWa = true, autoIg = true) {
    document.getElementById('modalPipelineTitle').textContent = 'Editar Funil';
    document.getElementById('pipelineId').value = id;
    document.getElementById('pipelineName').value = name;
    document.getElementById('pipelineColor').value = color;
    document.getElementById('pipelineColorText').value = color;
    document.getElementById('autoCreateLead').checked = autoCreate;
    document.getElementById('autoCreateWhatsapp').checked = autoWa;
    document.getElementById('autoCreateInstagram').checked = autoIg;
    toggleChannelToggles();
    document.getElementById('modalPipeline').classList.add('open');
}

function toggleChannelToggles() {
    const enabled = document.getElementById('autoCreateLead').checked;
    document.getElementById('channelToggles').style.opacity = enabled ? '1' : '.4';
    document.getElementById('autoCreateWhatsapp').disabled = !enabled;
    document.getElementById('autoCreateInstagram').disabled = !enabled;
}

function closePipelineModal() { document.getElementById('modalPipeline').classList.remove('open'); }

document.getElementById('pipelineColor').addEventListener('input', e => {
    document.getElementById('pipelineColorText').value = e.target.value;
});
document.getElementById('pipelineColorText').addEventListener('input', e => {
    if (/^#[0-9a-f]{6}$/i.test(e.target.value)) document.getElementById('pipelineColor').value = e.target.value;
});

async function savePipeline() {
    const id    = document.getElementById('pipelineId').value;
    const name  = document.getElementById('pipelineName').value.trim();
    const color = document.getElementById('pipelineColorText').value.trim() || document.getElementById('pipelineColor').value;
    if (!name) { document.getElementById('pipelineName').focus(); return; }

    const btn = document.getElementById('btnSavePipeline');
    btn.disabled = true;

    const url    = id ? PIPE_UPD.replace('__ID__', id) : PIPE_STORE;
    const method = id ? 'PUT' : 'POST';

    try {
        const res  = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                name, color,
                auto_create_lead:           document.getElementById('autoCreateLead').checked,
                auto_create_from_whatsapp:  document.getElementById('autoCreateWhatsapp').checked,
                auto_create_from_instagram: document.getElementById('autoCreateInstagram').checked,
            })
        });
        const data = await res.json();
        if (!data.success) { alert(data.message || 'Erro ao salvar.'); return; }

        closePipelineModal();
        if (id) {
            const card = document.querySelector(`.pipeline-card[data-pipeline-id="${id}"]`);
            if (card) {
                card.querySelector('.pipeline-color-dot').style.background = color;
                card.querySelector('.pipeline-name').textContent = name;
            }
        } else {
            document.getElementById('emptyPipelines')?.remove();
            const container = document.getElementById('pipelinesContainer');
            container.insertAdjacentHTML('beforeend', buildPipelineCard(data.pipeline));
        }
    } finally {
        btn.disabled = false;
    }
}

function buildPipelineCard(p) {
    return `<div class="pipeline-card" data-pipeline-id="${p.id}">
        <div class="pipeline-header" onclick="togglePipeline(this)">
            <span class="pipeline-color-dot" style="background:${p.color};"></span>
            <span class="pipeline-name">${escapeHtml(p.name)}</span>
            <div class="pipeline-actions" onclick="event.stopPropagation()">
                <button class="btn-icon" onclick="setDefaultPipeline(${p.id},'${escapeJs(p.name)}','${p.color}')"><i class="bi bi-star"></i></button>
                <button class="btn-icon" onclick="openEditPipeline(${p.id},'${escapeJs(p.name)}','${p.color}',${p.auto_create_lead !== false},${p.auto_create_from_whatsapp !== false},${p.auto_create_from_instagram !== false})"><i class="bi bi-pencil"></i></button>
                <button class="btn-icon danger" onclick="deletePipeline(${p.id},this)"><i class="bi bi-trash"></i></button>
                <i class="bi bi-chevron-down" style="font-size:13px;color:#9ca3af;transition:transform .2s;" id="chevron-${p.id}"></i>
            </div>
        </div>
        <div class="pipeline-body" id="body-${p.id}">
            <ul class="stages-list" data-pipeline-id="${p.id}" id="stages-${p.id}"></ul>
            <button class="add-stage-btn" onclick="openAddStage(${p.id})"><i class="bi bi-plus-lg"></i> Adicionar etapa</button>
        </div>
    </div>`;
}

async function setDefaultPipeline(id, name, color) {
    const res  = await fetch(PIPE_UPD.replace('__ID__', id), {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, color, is_default: true })
    });
    const data = await res.json();
    if (data.success) location.reload();
}

function deletePipeline(id, btn) {
    confirmAction({
        title: 'Excluir funil',
        message: 'Excluir este funil? Todos os leads devem ser movidos primeiro.',
        confirmText: 'Excluir',
        onConfirm: async () => {
            const res  = await fetch(PIPE_DEL.replace('__ID__', id), {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF }
            });
            const data = await res.json();
            if (!data.success) { toastr.error(data.message || 'Não foi possível excluir.'); return; }
            btn.closest('.pipeline-card').remove();
        },
    });
}

/* ---- Stage Modal ---- */
function openAddStage(pipelineId) {
    document.getElementById('modalStageTitle').textContent = 'Nova Etapa';
    document.getElementById('stagePipelineId').value = pipelineId;
    document.getElementById('stageId').value = '';
    document.getElementById('stageName').value = '';
    document.getElementById('stageColor').value = '#6366F1';
    document.getElementById('stageColorText').value = '#6366F1';
    document.getElementById('stageIsWon').checked  = false;
    document.getElementById('stageIsLost').checked = false;
    document.getElementById('modalStage').classList.add('open');
    setTimeout(() => document.getElementById('stageName').focus(), 100);
}

function openEditStage(pipelineId, stageId, name, color, isWon, isLost) {
    document.getElementById('modalStageTitle').textContent = 'Editar Etapa';
    document.getElementById('stagePipelineId').value = pipelineId;
    document.getElementById('stageId').value = stageId;
    document.getElementById('stageName').value = name;
    document.getElementById('stageColor').value = color;
    document.getElementById('stageColorText').value = color;
    document.getElementById('stageIsWon').checked  = isWon;
    document.getElementById('stageIsLost').checked = isLost;
    document.getElementById('modalStage').classList.add('open');
}

function closeStageModal() { document.getElementById('modalStage').classList.remove('open'); }

document.getElementById('stageColor').addEventListener('input', e => {
    document.getElementById('stageColorText').value = e.target.value;
});
document.getElementById('stageColorText').addEventListener('input', e => {
    if (/^#[0-9a-f]{6}$/i.test(e.target.value)) document.getElementById('stageColor').value = e.target.value;
});

async function saveStage() {
    const pipelineId = document.getElementById('stagePipelineId').value;
    const stageId    = document.getElementById('stageId').value;
    const name       = document.getElementById('stageName').value.trim();
    const color      = document.getElementById('stageColorText').value.trim() || document.getElementById('stageColor').value;
    const isWon      = document.getElementById('stageIsWon').checked;
    const isLost     = document.getElementById('stageIsLost').checked;
    if (!name) { document.getElementById('stageName').focus(); return; }

    const btn = document.getElementById('btnSaveStage');
    btn.disabled = true;

    try {
        const url    = stageId
            ? STAGE_UPD.replace('__P__', pipelineId).replace('__S__', stageId)
            : STAGE_STORE.replace('__ID__', pipelineId);
        const method = stageId ? 'PUT' : 'POST';

        const res  = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ name, color, is_won: isWon, is_lost: isLost })
        });
        const data = await res.json();
        if (!data.success) { alert(data.message || 'Erro ao salvar.'); return; }

        closeStageModal();
        const s = data.stage;
        const list = document.getElementById('stages-' + pipelineId);

        if (stageId) {
            const li = list.querySelector(`[data-stage-id="${stageId}"]`);
            if (li) {
                li.querySelector('.stage-color-dot').style.background = s.color;
                li.querySelector('.stage-name').textContent = s.name;
                // refresh badges
                li.querySelectorAll('.stage-badge').forEach(b => b.remove());
                if (s.is_won)  li.querySelector('.stage-name').insertAdjacentHTML('afterend', '<span class="stage-badge won-badge">Ganho</span>');
                if (s.is_lost) li.querySelector('.stage-name').insertAdjacentHTML('afterend', '<span class="stage-badge lost-badge">Perdido</span>');
            }
        } else {
            const li = document.createElement('li');
            li.className = 'stage-item';
            li.dataset.stageId = s.id;
            li.innerHTML = `<i class="bi bi-grip-vertical stage-drag-handle"></i>
                <span class="stage-color-dot" style="background:${s.color};"></span>
                <span class="stage-name">${escapeHtml(s.name)}</span>
                ${s.is_won  ? '<span class="stage-badge won-badge">Ganho</span>'   : ''}
                ${s.is_lost ? '<span class="stage-badge lost-badge">Perdido</span>' : ''}
                <div style="display:flex;gap:5px;">
                    <button class="btn-icon" onclick="openEditStage(${pipelineId},${s.id},'${escapeJs(s.name)}','${s.color}',${!!s.is_won},${!!s.is_lost})"><i class="bi bi-pencil"></i></button>
                    <button class="btn-icon danger" onclick="deleteStage(${pipelineId},${s.id},this)"><i class="bi bi-trash"></i></button>
                </div>`;
            list.appendChild(li);
            initSortable(list);
        }
    } finally {
        btn.disabled = false;
    }
}

function deleteStage(pipelineId, stageId, btn) {
    confirmAction({
        title: 'Excluir etapa',
        message: 'Tem certeza que deseja excluir esta etapa?',
        confirmText: 'Excluir',
        onConfirm: async () => {
            const res  = await fetch(STAGE_DEL.replace('__P__', pipelineId).replace('__S__', stageId), {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF }
            });
            const data = await res.json();
            if (!data.success) { toastr.error(data.message || 'Não foi possível excluir.'); return; }
            btn.closest('.stage-item').remove();
        },
    });
}

/* ---- Reasons ---- */
document.getElementById('btnNovoMotivo').addEventListener('click', () => {
    document.getElementById('modalReasonTitle').textContent = 'Novo Motivo';
    document.getElementById('reasonId').value = '';
    document.getElementById('reasonName').value = '';
    document.getElementById('modalReason').classList.add('open');
    setTimeout(() => document.getElementById('reasonName').focus(), 100);
});

function openEditReason(id, name) {
    document.getElementById('modalReasonTitle').textContent = 'Editar Motivo';
    document.getElementById('reasonId').value = id;
    document.getElementById('reasonName').value = name;
    document.getElementById('modalReason').classList.add('open');
}

function closeReasonModal() { document.getElementById('modalReason').classList.remove('open'); }

async function saveReason() {
    const id   = document.getElementById('reasonId').value;
    const name = document.getElementById('reasonName').value.trim();
    if (!name) { document.getElementById('reasonName').focus(); return; }

    const url    = id ? REASON_UPD.replace('__ID__', id) : REASON_STORE;
    const method = id ? 'PUT' : 'POST';

    const res  = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, is_active: true })
    });
    const data = await res.json();
    if (!data.success) { alert(data.message || 'Erro.'); return; }

    closeReasonModal();
    const r = data.reason;
    const body = document.getElementById('reasonsBody');
    document.getElementById('emptyReasons')?.remove();

    if (id) {
        const row = body.querySelector(`tr[data-reason-id="${id}"]`);
        if (row) row.querySelector('.reason-name-cell').textContent = r.name;
    } else {
        body.insertAdjacentHTML('beforeend', `<tr data-reason-id="${r.id}">
            <td class="reason-name-cell">${escapeHtml(r.name)}</td>
            <td style="text-align:center;">
                <label class="toggle">
                    <input type="checkbox" checked onchange="toggleReason(${r.id},'${escapeJs(r.name)}',this.checked)">
                    <span class="toggle-slider"></span>
                </label>
            </td>
            <td>
                <div style="display:flex;gap:5px;justify-content:flex-end;">
                    <button class="btn-icon" onclick="openEditReason(${r.id},'${escapeJs(r.name)}')"><i class="bi bi-pencil"></i></button>
                    <button class="btn-icon danger" onclick="deleteReason(${r.id},this)"><i class="bi bi-trash"></i></button>
                </div>
            </td>
        </tr>`);
    }
}

async function toggleReason(id, name, active) {
    await fetch(REASON_UPD.replace('__ID__', id), {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, is_active: active })
    });
}

function deleteReason(id, btn) {
    confirmAction({
        title: 'Excluir motivo',
        message: 'Tem certeza que deseja excluir este motivo de perda?',
        confirmText: 'Excluir',
        onConfirm: async () => {
            const res  = await fetch(REASON_DEL.replace('__ID__', id), {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF }
            });
            const data = await res.json();
            if (!data.success) { toastr.error(data.message || 'Erro ao excluir.'); return; }
            if (data.deactivated) {
                toastr.warning('Este motivo possui registros e foi desativado.');
                const row = document.querySelector(`tr[data-reason-id="${id}"]`);
                if (row) row.querySelector('input[type=checkbox]').checked = false;
            } else {
                btn.closest('tr').remove();
            }
        },
    });
}

/* ---- Helpers ---- */
function escapeHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escapeJs(s) { return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.classList.remove('open');
    });
});
</script>
@endpush
