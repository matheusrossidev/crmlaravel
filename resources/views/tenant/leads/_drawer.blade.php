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
            </div>

            {{-- Notas --}}
            <div class="drawer-section-label" style="margin-top:18px;">Notas</div>
            <div class="drawer-group">
                <textarea id="fNotes" name="notes" placeholder="Observações sobre este lead..." class="drawer-input" rows="3" style="resize:vertical;"></textarea>
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
@endphp

<script>
// ── Dados de pipelines e campos personalizados injetados pelo servidor ────
const PIPELINES_DATA = {!! json_encode($_pipelinesJson) !!};
const CF_DEFS        = {!! json_encode($_cfDefsJson) !!};
const CF_UPLOAD_URL  = @json(route('leads.cf-upload'));

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
    document.getElementById('fNotes').value  = lead.notes || '';

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
        notes:         document.getElementById('fNotes').value.trim() || null,
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

    // Registrar upload automático para campos do tipo "file"
    defs.filter(d => d.field_type === 'file').forEach(def => {
        const fileInput = document.getElementById(`cf_file_input_${def.name}`);
        if (! fileInput) return;
        fileInput.addEventListener('change', async () => {
            const file = fileInput.files[0];
            if (! file) return;
            const statusEl  = document.getElementById(`cf_file_status_${def.name}`);
            const previewEl = document.getElementById(`cf_file_preview_${def.name}`);
            const hiddenEl  = document.getElementById(`cf_${def.name}`);
            statusEl.textContent = 'Enviando...';
            const fd = new FormData();
            fd.append('file', file);
            fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            try {
                const res  = await fetch(CF_UPLOAD_URL, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success && data.url) {
                    hiddenEl.value = data.url;
                    statusEl.textContent = '';
                    const name = decodeURIComponent(data.url.split('/').pop());
                    previewEl.style.display = '';
                    previewEl.innerHTML = `<a href="${escapeHtml(data.url)}" target="_blank" style="color:#3B82F6;word-break:break-all;"><i class="bi bi-paperclip"></i> ${escapeHtml(name)}</a>`;
                } else {
                    statusEl.textContent = 'Erro no upload.';
                }
            } catch {
                statusEl.textContent = 'Erro de conexão.';
            }
        });
    });
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
        const hasFile = val && String(val).startsWith('http');
        const fileName = hasFile ? decodeURIComponent(String(val).split('/').pop()) : '';
        const preview = hasFile
            ? `<div id="cf_file_preview_${def.name}" style="margin-bottom:6px;font-size:12px;">
                   <a href="${escapeHtml(String(val))}" target="_blank" style="color:#3B82F6;word-break:break-all;">
                       <i class="bi bi-paperclip"></i> ${escapeHtml(fileName)}
                   </a>
               </div>`
            : `<div id="cf_file_preview_${def.name}" style="display:none;margin-bottom:6px;font-size:12px;"></div>`;
        inputHtml = `${preview}
            <input type="hidden" id="${id}" value="${escapeHtml(String(val))}">
            <div style="display:flex;align-items:center;gap:8px;">
                <input type="file" id="cf_file_input_${def.name}" style="flex:1;font-size:13px;">
                <span id="cf_file_status_${def.name}" style="font-size:11px;color:#6B7280;"></span>
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

        } else {
            const el = document.getElementById(id);
            result[def.name] = el ? (el.value || null) : null;
        }
    });

    return result;
}
</script>
