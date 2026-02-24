{{--
    Drawer compartilhado de Lead (criação + edição)
    Incluído no Kanban e na tela de Contatos.
    Depende de: jQuery, Bootstrap 5, Toastr, window.escapeHtml
    Espera que a página defina: LEAD_SHOW, LEAD_STORE, LEAD_UPD, LEAD_DEL
--}}

{{-- Overlay --}}
<div id="drawerOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:199;transition:opacity .25s;" onclick="closeLeadDrawer()"></div>

{{-- Drawer --}}
<aside id="leadDrawer" style="
    position:fixed;
    top:0;right:0;
    width:440px;
    height:100vh;
    background:#fff;
    box-shadow:-4px 0 32px rgba(0,0,0,.1);
    z-index:200;
    display:flex;
    flex-direction:column;
    transform:translateX(100%);
    transition:transform .25s cubic-bezier(.4,0,.2,1);
    overflow:hidden;
">

    {{-- Header --}}
    <div style="padding:18px 22px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
        <div>
            <div id="drawerTitle" style="font-size:15px;font-weight:700;color:#1a1d23;">Novo Lead</div>
            <div id="drawerSub" style="font-size:12px;color:#9ca3af;margin-top:2px;"></div>
        </div>
        <div style="display:flex;gap:6px;align-items:center;">
            <button id="btnDeleteLead" style="display:none;" class="drawer-icon-btn danger" title="Excluir lead">
                <i class="bi bi-trash"></i>
            </button>
            <button onclick="closeLeadDrawer()" class="drawer-icon-btn" title="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    {{-- Conteúdo (scrollável) --}}
    <div style="flex:1;overflow-y:auto;padding:22px;" id="drawerBody">

        <form id="leadForm" novalidate>
            <input type="hidden" id="leadId" value="">

            {{-- Informações Básicas --}}
            <div class="drawer-section-label">Informações Básicas</div>

            <div class="drawer-group">
                <label>Nome <span style="color:#EF4444;">*</span></label>
                <input type="text" id="fName" name="name" placeholder="Nome do lead" class="drawer-input">
                <div class="drawer-error" id="err-name"></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="drawer-group">
                    <label>Telefone / WhatsApp</label>
                    <input type="text" id="fPhone" name="phone" placeholder="(11) 99999-9999" class="drawer-input">
                </div>
                <div class="drawer-group">
                    <label>E-mail</label>
                    <input type="email" id="fEmail" name="email" placeholder="email@exemplo.com" class="drawer-input">
                </div>
            </div>

            {{-- Pipeline / Etapa --}}
            <div class="drawer-section-label" style="margin-top:18px;">Pipeline & Etapa</div>

            <div class="drawer-group">
                <label>Pipeline <span style="color:#EF4444;">*</span></label>
                <select id="fPipeline" name="pipeline_id" class="drawer-input" onchange="loadStagesForPipeline(this.value)">
                    <option value="">Selecione o pipeline</option>
                    @foreach($pipelines as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
                <div class="drawer-error" id="err-pipeline_id"></div>
            </div>

            <div class="drawer-group">
                <label>Etapa <span style="color:#EF4444;">*</span></label>
                <select id="fStage" name="stage_id" class="drawer-input">
                    <option value="">Selecione primeiro o pipeline</option>
                </select>
                <div class="drawer-error" id="err-stage_id"></div>
            </div>

            {{-- Negócio --}}
            <div class="drawer-section-label" style="margin-top:18px;">Negócio</div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="drawer-group">
                    <label>Valor (R$)</label>
                    <input type="number" id="fValue" name="value" placeholder="0,00" min="0" step="0.01" class="drawer-input">
                </div>
                <div class="drawer-group">
                    <label>Origem</label>
                    <select id="fSource" name="source" class="drawer-input">
                        <option value="manual">Manual</option>
                        <option value="facebook">Facebook Ads</option>
                        <option value="google">Google Ads</option>
                        <option value="instagram">Instagram</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="site">Site</option>
                        <option value="indicacao">Indicação</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
            </div>

            <div class="drawer-group">
                <label>Campanha</label>
                <select id="fCampaign" name="campaign_id" class="drawer-input">
                    <option value="">Nenhuma</option>
                </select>
            </div>

            {{-- Tags --}}
            <div class="drawer-group">
                <label>Tags</label>
                <input type="hidden" id="fTagsHidden" name="tags_json" value="[]">
                <div class="tag-input-wrap drawer-input" id="tagInputWrap" onclick="document.getElementById('tagRawInput').focus()">
                    <div id="tagBadgesContainer" style="display:flex;flex-wrap:wrap;gap:4px;"></div>
                    <input type="text" id="tagRawInput" placeholder="Digite e pressione Enter ou vírgula..." style="border:none;outline:none;font-size:13px;font-family:inherit;background:transparent;min-width:140px;padding:2px 0;">
                </div>
                {{-- Sugestões de tags pré-configuradas --}}
                <div id="tagSuggestions" style="display:flex;flex-wrap:wrap;gap:5px;margin-top:6px;"></div>
            </div>

            {{-- Notas (múltiplas — só em modo edição) --}}
            <div id="notesSection" style="display:none;margin-top:18px;">
                <div class="drawer-section-label">Notas</div>
                <div id="notesList" style="margin-bottom:8px;"></div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <textarea id="fNoteInput" placeholder="Escreva uma nota..." class="drawer-input" rows="2" style="resize:vertical;min-height:58px;"></textarea>
                    <button type="button" onclick="addNote()" class="drawer-add-note-btn">
                        <i class="bi bi-plus-lg"></i> Adicionar Nota
                    </button>
                </div>
            </div>

            {{-- Campos Personalizados (dinâmico via JS) --}}
            <div id="customFieldsSection" style="display:none;margin-top:4px;">
                <div class="drawer-section-label" style="margin-top:18px;">Campos Personalizados</div>
                <div id="customFieldsContainer"></div>
            </div>

        </form>

        {{-- Histórico de eventos (só na edição) --}}
        <div id="eventsSection" style="display:none;margin-top:22px;">
            <div class="drawer-section-label">Histórico</div>
            <div id="eventsList" style="display:flex;flex-direction:column;gap:0;"></div>
        </div>

    </div>

    {{-- Footer com ações --}}
    <div style="padding:16px 22px;border-top:1px solid #f0f2f7;display:flex;gap:10px;flex-shrink:0;">
        <button type="button" onclick="closeLeadDrawer()" style="flex:0;padding:10px 18px;border:1.5px solid #e8eaf0;border-radius:9px;background:#fff;font-size:13.5px;font-weight:600;color:#6b7280;cursor:pointer;">
            Cancelar
        </button>
        <button type="button" id="btnSaveLead" style="flex:1;padding:10px;background:#3B82F6;color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:background .15s;">
            <i class="bi bi-check-lg"></i>
            Salvar
        </button>
    </div>

</aside>

<style>
    .drawer-section-label {
        font-size: 10.5px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 10px;
    }

    .drawer-group {
        margin-bottom: 12px;
    }

    .drawer-group label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 4px;
    }

    .drawer-input {
        width: 100%;
        padding: 9px 12px;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        font-size: 13.5px;
        font-family: 'Inter', sans-serif;
        color: #1a1d23;
        background: #fafafa;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
        box-sizing: border-box;
    }

    .drawer-input:focus {
        border-color: #3B82F6;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(59,130,246,.1);
    }

    .drawer-input.is-invalid {
        border-color: #EF4444;
    }

    .drawer-error {
        font-size: 11.5px;
        color: #EF4444;
        margin-top: 3px;
        display: none;
    }

    .drawer-icon-btn {
        width: 32px;
        height: 32px;
        border: 1px solid #e8eaf0;
        border-radius: 8px;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        font-size: 14px;
        cursor: pointer;
        transition: all .15s;
    }

    .drawer-icon-btn:hover { background: #f4f6fb; color: #374151; }
    .drawer-icon-btn.danger:hover { background: #fef2f2; color: #EF4444; border-color: #fecaca; }

    .event-item {
        display: flex;
        gap: 10px;
        padding: 10px 0;
        border-bottom: 1px solid #f7f8fa;
    }

    .event-item:last-child { border-bottom: none; }

    .event-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #3B82F6;
        flex-shrink: 0;
        margin-top: 4px;
    }

    .event-text {
        font-size: 12.5px;
        color: #374151;
        line-height: 1.4;
    }

    .event-meta {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 2px;
    }

    /* Tag input */
    .tag-input-wrap {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px;
        cursor: text;
        padding: 6px 10px;
        min-height: 40px;
    }
    .tag-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #f0f4ff;
        color: #6366f1;
        font-size: 12px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 99px;
        white-space: nowrap;
    }
    .tag-chip button {
        background: none;
        border: none;
        padding: 0;
        font-size: 12px;
        color: #6366f1;
        cursor: pointer;
        line-height: 1;
        opacity: .7;
        display: flex;
        align-items: center;
    }
    .tag-chip button:hover { opacity: 1; }

    .tag-suggestion-chip {
        display: inline-flex;
        align-items: center;
        padding: 3px 9px;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        border: 1.5px solid transparent;
        transition: opacity .15s, transform .1s;
        white-space: nowrap;
        user-select: none;
    }
    .tag-suggestion-chip:hover { opacity: .8; transform: scale(1.03); }
    .tag-suggestion-chip.active { opacity: .4; cursor: default; pointer-events: none; }

    .note-item {
        background: #f8f9fb;
        border: 1px solid #e8eaf0;
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 7px;
    }
    .note-header {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 5px;
    }
    .note-author { font-size: 12px; font-weight: 700; color: #374151; }
    .note-date   { font-size: 11px; color: #9ca3af; flex: 1; }
    .note-del-btn {
        background: none; border: none; padding: 0;
        cursor: pointer; color: #d1d5db; font-size: 13px; line-height: 1;
    }
    .note-del-btn:hover { color: #ef4444; }
    .note-body {
        font-size: 13px; color: #4b5563;
        white-space: pre-wrap; word-break: break-word; line-height: 1.5;
    }
    .drawer-add-note-btn {
        display: flex; align-items: center; justify-content: center; gap: 5px;
        padding: 8px 14px; background: #f0f4ff; color: #3B82F6;
        border: 1.5px dashed #3B82F6; border-radius: 8px;
        font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s;
    }
    .drawer-add-note-btn:hover { background: #dbeafe; }
    .drawer-add-note-btn:disabled { opacity: .6; cursor: not-allowed; }
</style>

@php
$_pipelinesJson = $pipelines->map(function($p) {
    return [
        'id'     => $p->id,
        'name'   => $p->name,
        'stages' => $p->stages->map(function($s) {
            return ['id' => $s->id, 'name' => $s->name, 'color' => $s->color];
        })->values()->toArray(),
    ];
})->values()->toArray();

$_cfDefsJson = isset($customFieldDefs)
    ? $customFieldDefs->map(fn($d) => [
        'id'           => $d->id,
        'name'         => $d->name,
        'label'        => $d->label,
        'field_type'   => $d->field_type,
        'options_json' => $d->options_json ?? [],
        'is_required'  => (bool) $d->is_required,
        'default_value'=> $d->default_value,
    ])->values()->toArray()
    : [];

$_configuredTagsJson = isset($_configuredTags)
    ? $_configuredTags->map(fn($t) => ['name' => $t->name, 'color' => $t->color])->values()->toArray()
    : [];
@endphp

<script>
// ── Dados de pipelines e campos personalizados injetados pelo servidor ────
const PIPELINES_DATA   = {!! json_encode($_pipelinesJson) !!};
const CF_DEFS          = {!! json_encode($_cfDefsJson) !!};
const CF_UPLOAD_URL    = '{{ route('leads.cf-upload') }}';
const LEAD_TAGS        = {!! json_encode($_configuredTagsJson) !!};
const LEAD_NOTE_STORE  = '{{ route('leads.notes.store',   ['lead' => '__ID__']) }}';
const LEAD_NOTE_DEL    = '{{ route('leads.notes.destroy', ['lead' => '__LEAD__', 'note' => '__NOTE__']) }}';

// ── Tag input logic ───────────────────────────────────────────────────────
let _currentTags = [];

function syncTagsHidden() {
    document.getElementById('fTagsHidden').value = JSON.stringify(_currentTags);
}

function renderTagBadges() {
    const container = document.getElementById('tagBadgesContainer');
    container.innerHTML = _currentTags.map((tag, i) =>
        `<span class="tag-chip">${escapeHtml(tag)}<button type="button" onclick="removeTag(${i})">×</button></span>`
    ).join('');
    syncTagsHidden();
    renderTagSuggestions();
}

function isColorDark(hex) {
    const c = (hex || '#6366f1').replace('#', '');
    const r = parseInt(c.substring(0, 2), 16);
    const g = parseInt(c.substring(2, 4), 16);
    const b = parseInt(c.substring(4, 6), 16);
    return (r * 299 + g * 587 + b * 114) / 1000 < 128;
}

function renderTagSuggestions() {
    const container = document.getElementById('tagSuggestions');
    if (!container || !LEAD_TAGS.length) return;
    container.innerHTML = LEAD_TAGS.map(t => {
        const hex    = t.color || '#6366f1';
        const txt    = isColorDark(hex) ? '#fff' : '#1a1d23';
        const active = _currentTags.includes(t.name) ? ' active' : '';
        return `<button type="button" class="tag-suggestion-chip${active}"
            style="background:${hex};color:${txt};border-color:${hex};"
            data-tag="${escapeHtml(t.name)}"
            onclick="addTagFromSuggestion(this.dataset.tag)">${escapeHtml(t.name)}</button>`;
    }).join('');
}

function addTagFromSuggestion(name) {
    addTag(name);
}

function addTag(raw) {
    const val = raw.trim().replace(/,/g, '').substring(0, 50);
    if (!val || _currentTags.includes(val)) return;
    _currentTags.push(val);
    renderTagBadges();
}

function removeTag(idx) {
    _currentTags.splice(idx, 1);
    renderTagBadges();
}

function setTags(arr) {
    _currentTags = Array.isArray(arr) ? [...arr] : [];
    renderTagBadges();
}

document.getElementById('tagRawInput')?.addEventListener('keydown', e => {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        const val = e.target.value;
        addTag(val);
        e.target.value = '';
    } else if (e.key === 'Backspace' && e.target.value === '' && _currentTags.length) {
        removeTag(_currentTags.length - 1);
    }
});
document.getElementById('tagRawInput')?.addEventListener('blur', e => {
    if (e.target.value.trim()) {
        addTag(e.target.value);
        e.target.value = '';
    }
});

let _drawerMode = 'new'; // 'new' | 'edit'
let _drawerLeadId = null;

// ── Abrir drawer (novo lead) ──────────────────────────────────────────────
function openNewLeadDrawer(defaults = {}) {
    _drawerMode   = 'new';
    _drawerLeadId = null;

    resetDrawerForm();

    document.getElementById('drawerTitle').textContent = 'Novo Lead';
    document.getElementById('drawerSub').textContent   = '';
    document.getElementById('btnDeleteLead').style.display = 'none';
    document.getElementById('eventsSection').style.display = 'none';

    // Pré-selecionar pipeline/stage se passado (ex: clique em coluna do kanban)
    if (defaults.pipeline_id) {
        document.getElementById('fPipeline').value = defaults.pipeline_id;
        loadStagesForPipeline(defaults.pipeline_id, defaults.stage_id);
    } else if (PIPELINES_DATA.length === 1) {
        document.getElementById('fPipeline').value = PIPELINES_DATA[0].id;
        loadStagesForPipeline(PIPELINES_DATA[0].id);
    }

    // Carregar campanhas
    loadCampaigns(null);

    // Campos personalizados com valores default/vazios
    renderCustomFields(CF_DEFS, {});

    showDrawer();
    setTimeout(() => document.getElementById('fName').focus(), 300);
}

// ── Abrir drawer (edição de lead existente) ───────────────────────────────
function openLeadDrawer(leadId) {
    _drawerMode   = 'edit';
    _drawerLeadId = leadId;

    resetDrawerForm();
    document.getElementById('drawerTitle').textContent = 'Carregando...';
    document.getElementById('drawerSub').textContent   = '';
    showDrawer();

    $.ajax({
        url: LEAD_SHOW.replace('__ID__', leadId),
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success(res) {
            populateDrawer(res);
        },
        error() {
            toastr.error('Erro ao carregar lead.');
            closeLeadDrawer();
        }
    });
}

// ── Preencher campos com dados do lead ────────────────────────────────────
function populateDrawer(res) {
    const lead = res.lead;

    document.getElementById('drawerTitle').textContent = lead.name;
    document.getElementById('drawerSub').textContent   = `Criado em ${lead.created_at || ''}`;
    document.getElementById('leadId').value            = lead.id;
    document.getElementById('btnDeleteLead').style.display = '';

    document.getElementById('fName').value   = lead.name  || '';
    document.getElementById('fPhone').value  = lead.phone || '';
    document.getElementById('fEmail').value  = lead.email || '';
    document.getElementById('fValue').value  = lead.value || '';

    // Notas múltiplas
    renderNotes(lead.notes_list || []);
    document.getElementById('notesSection').style.display = '';

    document.getElementById('fSource').value = lead.source || 'manual';

    // Tags
    setTags(lead.tags || []);

    // Pipeline + Stages dinâmicos (vem do servidor já carregado)
    if (res.pipelines && res.pipelines.length) {
        const sel = document.getElementById('fPipeline');
        sel.innerHTML = '<option value="">Selecione o pipeline</option>';
        res.pipelines.forEach(p => {
            sel.insertAdjacentHTML('beforeend', `<option value="${p.id}">${escapeHtml(p.name)}</option>`);
        });
        sel.value = lead.pipeline_id || '';
        // Popula stages baseado nos dados vindos do servidor
        const pipeline = res.pipelines.find(p => p.id == lead.pipeline_id);
        populateStages(pipeline ? pipeline.stages : [], lead.stage_id);
    } else {
        loadStagesForPipeline(lead.pipeline_id, lead.stage_id);
    }

    // Campanhas
    const campSel = document.getElementById('fCampaign');
    campSel.innerHTML = '<option value="">Nenhuma</option>';
    if (res.campaigns) {
        res.campaigns.forEach(c => {
            campSel.insertAdjacentHTML('beforeend', `<option value="${c.id}">${escapeHtml(c.name)}</option>`);
        });
    }
    campSel.value = lead.campaign_id || '';

    // Campos personalizados — flatten {name: {label, type, value}} → {name: value}
    const cfDefs = res.custom_field_defs || CF_DEFS;
    const rawCf  = lead.custom_fields || {};
    const cfValues = {};
    Object.entries(rawCf).forEach(([k, v]) => {
        cfValues[k] = (v && typeof v === 'object' && 'value' in v) ? v.value : v;
    });
    renderCustomFields(cfDefs, cfValues);

    // Histórico
    if (res.events && res.events.length) {
        const list = document.getElementById('eventsList');
        list.innerHTML = res.events.map(e => `
            <div class="event-item">
                <div class="event-dot"></div>
                <div>
                    <div class="event-text">${escapeHtml(e.description || e.type)}</div>
                    <div class="event-meta">${escapeHtml(e.performed_by)} · ${escapeHtml(e.created_at)}</div>
                </div>
            </div>`).join('');
        document.getElementById('eventsSection').style.display = '';
    }
}

// ── Carregar etapas no select quando pipeline muda ────────────────────────
function loadStagesForPipeline(pipelineId, selectedStageId = null) {
    const pipeline = PIPELINES_DATA.find(p => p.id == pipelineId);
    populateStages(pipeline ? pipeline.stages : [], selectedStageId);
}

function populateStages(stages, selectedId = null) {
    const sel = document.getElementById('fStage');
    if (!stages || !stages.length) {
        sel.innerHTML = '<option value="">Nenhuma etapa disponível</option>';
        return;
    }
    sel.innerHTML = stages.map(s =>
        `<option value="${s.id}" ${selectedId == s.id ? 'selected' : ''}>${escapeHtml(s.name)}</option>`
    ).join('');
}

// ── Carregar campanhas via AJAX (simplificado) ────────────────────────────
function loadCampaigns(selectedId) {
    // Campanhas já são carregadas no show() para edição
    // Para novo lead, o select fica vazio (OK para MVP)
}

// ── Notas múltiplas ───────────────────────────────────────────────────────
function renderNotes(notes) {
    const list = document.getElementById('notesList');
    if (!notes.length) {
        list.innerHTML = '<p style="font-size:12px;color:#9ca3af;text-align:center;padding:6px 0 10px;">Nenhuma nota ainda.</p>';
        return;
    }
    list.innerHTML = notes.map(n => `
        <div class="note-item" data-note-id="${n.id}">
            <div class="note-header">
                <span class="note-author">${escapeHtml(n.author)}</span>
                <span class="note-date">${escapeHtml(n.created_at || '')}</span>
                ${n.is_mine ? `<button type="button" class="note-del-btn" onclick="deleteNote(${n.id})" title="Excluir nota"><i class="bi bi-trash3"></i></button>` : ''}
            </div>
            <div class="note-body">${escapeHtml(n.body)}</div>
        </div>
    `).join('');
}

function addNote() {
    const input = document.getElementById('fNoteInput');
    const body  = input.value.trim();
    if (!body) { input.focus(); return; }

    const btn = document.querySelector('.drawer-add-note-btn');
    btn.disabled = true;

    $.ajax({
        url:         LEAD_NOTE_STORE.replace('__ID__', _drawerLeadId),
        method:      'POST',
        contentType: 'application/json',
        headers:     { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data:        JSON.stringify({ body }),
        success(res) {
            if (!res.success) return;
            input.value = '';
            const list     = document.getElementById('notesList');
            const emptyMsg = list.querySelector('p');
            if (emptyMsg) emptyMsg.remove();
            const html = `
                <div class="note-item" data-note-id="${res.note.id}">
                    <div class="note-header">
                        <span class="note-author">${escapeHtml(res.note.author)}</span>
                        <span class="note-date">${escapeHtml(res.note.created_at || '')}</span>
                        <button type="button" class="note-del-btn" onclick="deleteNote(${res.note.id})" title="Excluir nota"><i class="bi bi-trash3"></i></button>
                    </div>
                    <div class="note-body">${escapeHtml(res.note.body)}</div>
                </div>`;
            list.insertAdjacentHTML('afterbegin', html);
        },
        error() { toastr.error('Erro ao adicionar nota.'); },
        complete() { btn.disabled = false; },
    });
}

function deleteNote(noteId) {
    if (!confirm('Excluir esta nota?')) return;
    $.ajax({
        url:    LEAD_NOTE_DEL.replace('__LEAD__', _drawerLeadId).replace('__NOTE__', noteId),
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success(res) {
            if (!res.success) return;
            const el = document.querySelector(`.note-item[data-note-id="${noteId}"]`);
            if (el) el.remove();
            if (!document.querySelectorAll('.note-item').length) {
                document.getElementById('notesList').innerHTML =
                    '<p style="font-size:12px;color:#9ca3af;text-align:center;padding:6px 0 10px;">Nenhuma nota ainda.</p>';
            }
        },
        error(xhr) { toastr.error(xhr.responseJSON?.message || 'Erro ao excluir nota.'); },
    });
}

// ── Salvar lead ───────────────────────────────────────────────────────────
document.getElementById('btnSaveLead')?.addEventListener('click', () => {
    clearDrawerErrors();

    const payload = {
        name:          document.getElementById('fName').value.trim(),
        phone:         document.getElementById('fPhone').value.trim() || null,
        email:         document.getElementById('fEmail').value.trim() || null,
        value:         document.getElementById('fValue').value || null,
        source:        document.getElementById('fSource').value,
        tags:          _currentTags,
        pipeline_id:   document.getElementById('fPipeline').value,
        stage_id:      document.getElementById('fStage').value,
        campaign_id:   document.getElementById('fCampaign').value || null,
        custom_fields: collectCustomFields(),
    };

    if (!payload.name) {
        showDrawerError('name', 'Nome é obrigatório');
        return;
    }
    if (!payload.pipeline_id) {
        showDrawerError('pipeline_id', 'Selecione um pipeline');
        return;
    }
    if (!payload.stage_id) {
        showDrawerError('stage_id', 'Selecione uma etapa');
        return;
    }

    const isNew  = _drawerMode === 'new';
    const url    = isNew ? LEAD_STORE : LEAD_UPD.replace('__ID__', _drawerLeadId);
    const method = isNew ? 'POST' : 'PUT';

    const btn = document.getElementById('btnSaveLead');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Salvando...';

    $.ajax({
        url, method,
        contentType: 'application/json',
        data: JSON.stringify(payload),
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
        success(res) {
            if (res.success) {
                toastr.success(isNew ? 'Lead criado!' : 'Lead atualizado!');
                closeLeadDrawer();
                window.onLeadSaved && window.onLeadSaved(res.lead, isNew);
            }
        },
        error(xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON?.errors || {};
                Object.entries(errors).forEach(([field, msgs]) => showDrawerError(field, msgs[0]));
            } else {
                toastr.error('Erro ao salvar lead.');
            }
        },
        complete() {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Salvar';
        }
    });
});

// ── Excluir lead ──────────────────────────────────────────────────────────
document.getElementById('btnDeleteLead')?.addEventListener('click', () => {
    confirmAction({
        title: 'Excluir lead',
        message: 'Tem certeza que deseja excluir este lead?',
        confirmText: 'Excluir',
        onConfirm: () => {
            $.ajax({
                url: LEAD_DEL.replace('__ID__', _drawerLeadId),
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
                success(res) {
                    if (res.success) {
                        toastr.success('Lead excluído.');
                        closeLeadDrawer();
                        window.onLeadDeleted && window.onLeadDeleted(_drawerLeadId);
                    }
                },
                error() { toastr.error('Erro ao excluir lead.'); }
            });
        },
    });
});

// ── Helpers ───────────────────────────────────────────────────────────────
function showDrawer() {
    document.getElementById('drawerOverlay').style.display = 'block';
    document.getElementById('leadDrawer').style.transform  = 'translateX(0)';
    document.body.style.overflow = 'hidden';
}

function closeLeadDrawer() {
    document.getElementById('drawerOverlay').style.display = 'none';
    document.getElementById('leadDrawer').style.transform  = 'translateX(100%)';
    document.body.style.overflow = '';
    resetDrawerForm();
}

function resetDrawerForm() {
    document.getElementById('leadForm').reset();
    document.getElementById('leadId').value = '';
    document.getElementById('fPipeline').innerHTML = '<option value="">Selecione o pipeline</option>';
    PIPELINES_DATA.forEach(p => {
        document.getElementById('fPipeline').insertAdjacentHTML('beforeend', `<option value="${p.id}">${escapeHtml(p.name)}</option>`);
    });
    document.getElementById('fStage').innerHTML = '<option value="">Selecione primeiro o pipeline</option>';
    document.getElementById('fCampaign').innerHTML = '<option value="">Nenhuma</option>';
    document.getElementById('eventsSection').style.display = 'none';
    document.getElementById('eventsList').innerHTML = '';
    document.getElementById('notesSection').style.display = 'none';
    document.getElementById('notesList').innerHTML = '';
    document.getElementById('fNoteInput').value = '';
    document.getElementById('customFieldsSection').style.display = 'none';
    document.getElementById('customFieldsContainer').innerHTML = '';
    setTags([]);
    document.getElementById('tagRawInput').value = '';
    clearDrawerErrors();
}

function showDrawerError(field, msg) {
    const el = document.getElementById(`err-${field}`);
    const input = document.getElementById(`f${field.charAt(0).toUpperCase() + field.slice(1)}`);
    if (el) { el.textContent = msg; el.style.display = 'block'; }
    if (input) input.classList.add('is-invalid');
}

function clearDrawerErrors() {
    document.querySelectorAll('.drawer-error').forEach(el => { el.textContent = ''; el.style.display = 'none'; });
    document.querySelectorAll('.drawer-input.is-invalid').forEach(el => el.classList.remove('is-invalid'));
}

// Fechar com Esc
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeLeadDrawer();
});

// ── Campos Personalizados ─────────────────────────────────────────────────

function renderCustomFields(defs, values) {
    const section   = document.getElementById('customFieldsSection');
    const container = document.getElementById('customFieldsContainer');

    if (!defs || defs.length === 0) {
        section.style.display = 'none';
        container.innerHTML   = '';
        return;
    }

    section.style.display = '';
    container.innerHTML   = defs.map(d => buildFieldHtml(d, values[d.name])).join('');
}

function buildFieldHtml(def, currentValue) {
    const id       = `cf_${def.name}`;
    const required = def.is_required ? '<span style="color:#EF4444;">*</span>' : '';
    const val      = currentValue !== undefined && currentValue !== null ? currentValue : (def.default_value ?? '');

    let inputHtml = '';

    if (def.field_type === 'textarea') {
        inputHtml = `<textarea id="${id}" class="drawer-input" rows="3" style="resize:vertical;">${escapeHtml(String(val))}</textarea>`;

    } else if (def.field_type === 'select') {
        const opts = (def.options_json || []).map(o =>
            `<option value="${escapeHtml(o)}" ${val == o ? 'selected' : ''}>${escapeHtml(o)}</option>`
        ).join('');
        inputHtml = `<select id="${id}" class="drawer-input"><option value="">Selecione...</option>${opts}</select>`;

    } else if (def.field_type === 'multiselect') {
        const selected = Array.isArray(val) ? val : [];
        inputHtml = `<div style="display:flex;flex-direction:column;gap:4px;">` +
            (def.options_json || []).map(o =>
                `<label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:400;cursor:pointer;">
                    <input type="checkbox" class="cf-multi-${def.name}" value="${escapeHtml(o)}" ${selected.includes(o) ? 'checked' : ''}>
                    ${escapeHtml(o)}
                </label>`
            ).join('') + `</div>`;

    } else if (def.field_type === 'checkbox') {
        const checked = val === true || val === 1 || val === '1' ? 'checked' : '';
        inputHtml = `<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" id="${id}" ${checked} style="width:16px;height:16px;">
            <span style="font-size:13px;color:#374151;">${escapeHtml(def.label)}</span>
        </label>`;

    } else if (def.field_type === 'number' || def.field_type === 'currency') {
        const step = def.field_type === 'currency' ? '0.01' : 'any';
        inputHtml = `<input type="number" id="${id}" class="drawer-input" step="${step}" min="0" value="${escapeHtml(String(val))}">`;

    } else if (def.field_type === 'date') {
        inputHtml = `<input type="date" id="${id}" class="drawer-input" value="${escapeHtml(String(val))}">`;

    } else if (def.field_type === 'url') {
        inputHtml = `<input type="url" id="${id}" class="drawer-input" placeholder="https://" value="${escapeHtml(String(val))}">`;

    } else if (def.field_type === 'phone') {
        inputHtml = `<input type="tel" id="${id}" class="drawer-input" placeholder="(11) 99999-9999" value="${escapeHtml(String(val))}">`;

    } else if (def.field_type === 'email') {
        inputHtml = `<input type="email" id="${id}" class="drawer-input" placeholder="email@exemplo.com" value="${escapeHtml(String(val))}">`;

    } else if (def.field_type === 'file') {
        // Input oculto guarda a URL após upload; input[file] dispara o envio
        const existingLink = val
            ? `<a href="${escapeHtml(String(val))}" target="_blank" rel="noopener"
                  style="font-size:12px;color:#006acf;word-break:break-all;">${escapeHtml(String(val).split('/').pop())}</a>
               <button type="button" onclick="removeCfFile('${def.name}','${id}')"
                       style="background:none;border:none;color:#9ca3af;cursor:pointer;padding:0 4px;font-size:14px;line-height:1;" title="Remover arquivo">✕</button>`
            : '';
        inputHtml = `
            <input type="hidden" id="${id}" value="${escapeHtml(String(val))}">
            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;
                          border:1px solid #e2e8f0;border-radius:6px;padding:7px 12px;
                          font-size:13px;color:#374151;background:#f8fafc;width:100%;">
                <input type="file" id="cf_file_${def.name}" style="display:none;"
                       onchange="handleCfFileUpload(this,'${def.name}','${id}')">
                <span style="color:#006acf;font-size:13px;">&#128206;</span>
                <span>Escolher arquivo</span>
            </label>
            <div id="cf_status_${def.name}" style="margin-top:4px;display:flex;align-items:center;gap:6px;">
                ${existingLink}
            </div>`;

    } else {
        // text (default)
        inputHtml = `<input type="text" id="${id}" class="drawer-input" value="${escapeHtml(String(val))}">`;
    }

    // Checkbox tem label embutido, outros têm label separado
    const labelHtml = def.field_type === 'checkbox'
        ? ''
        : `<label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:4px;">${escapeHtml(def.label)} ${required}</label>`;

    return `<div class="drawer-group" data-cf-name="${def.name}">${labelHtml}${inputHtml}</div>`;
}

function collectCustomFields() {
    const result = {};
    const container = document.getElementById('customFieldsContainer');
    if (!container) return result;

    const defs = Array.isArray(window._currentCfDefs) ? window._currentCfDefs : CF_DEFS;

    defs.forEach(def => {
        const id = `cf_${def.name}`;

        if (def.field_type === 'checkbox') {
            const el = document.getElementById(id);
            result[def.name] = el ? el.checked : false;

        } else if (def.field_type === 'multiselect') {
            const checked = Array.from(container.querySelectorAll(`.cf-multi-${def.name}:checked`)).map(el => el.value);
            result[def.name] = checked;

        } else if (def.field_type === 'file') {
            // Lê a URL armazenada no hidden input (já foi feito upload assíncrono)
            const el = document.getElementById(id);
            result[def.name] = el ? (el.value || null) : null;

        } else {
            const el = document.getElementById(id);
            result[def.name] = el ? (el.value || null) : null;
        }
    });

    return result;
}

// ── Upload de arquivo para campo personalizado tipo "file" ────────────────

function handleCfFileUpload(fileInput, fieldName, hiddenId) {
    const file = fileInput.files[0];
    if (!file) return;

    const statusEl = document.getElementById(`cf_status_${fieldName}`);
    statusEl.innerHTML = '<span style="font-size:12px;color:#64748b;">Enviando...</span>';

    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    fetch(CF_UPLOAD_URL, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById(hiddenId).value = data.url;
                const name = escapeHtml(file.name);
                statusEl.innerHTML = `
                    <a href="${escapeHtml(data.url)}" target="_blank" rel="noopener"
                       style="font-size:12px;color:#006acf;word-break:break-all;">${name}</a>
                    <button type="button" onclick="removeCfFile('${fieldName}','${hiddenId}')"
                            style="background:none;border:none;color:#9ca3af;cursor:pointer;padding:0 4px;font-size:14px;line-height:1;" title="Remover">✕</button>`;
            } else {
                statusEl.innerHTML = '<span style="font-size:12px;color:#ef4444;">Erro ao enviar arquivo.</span>';
            }
        })
        .catch(() => {
            statusEl.innerHTML = '<span style="font-size:12px;color:#ef4444;">Erro ao enviar arquivo.</span>';
        });
}

function removeCfFile(fieldName, hiddenId) {
    document.getElementById(hiddenId).value = '';
    const fileInput = document.getElementById(`cf_file_${fieldName}`);
    if (fileInput) fileInput.value = '';
    const statusEl = document.getElementById(`cf_status_${fieldName}`);
    if (statusEl) statusEl.innerHTML = '';
}
</script>
