@extends('tenant.layouts.app')

@php
    $title    = 'Automações';
    $pageIcon = 'gear';
    $isEdit   = isset($automation);

    $pipelinesJs  = $pipelines->map(fn($p) => [
        'id'     => $p->id,
        'name'   => $p->name,
        'stages' => $p->stages->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values(),
    ])->values();

    $noteVarsHint = '{{contact_name}}, {{phone}}, {{stage}}, {{pipeline}}, {{birthday}}, {{days_until}}, {{custom_field_label}}';
    $msgVarsHint  = '{{contact_name}}, {{phone}}, {{stage}}, {{birthday}}, {{days_until}}';
@endphp

@push('styles')
<style>
/* ── Page layout ──────────────────────────────────────────────────── */
.af-page { display: flex; flex-direction: column; height: calc(100vh - 64px); overflow: hidden; }

.af-header {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 24px;
    background: #fff;
    border-bottom: 1px solid #e8eaf0;
    flex-shrink: 0;
    z-index: 10;
}
.af-back {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1.5px solid #e8eaf0; background: #fff; color: #374151;
    display: inline-flex; align-items: center; justify-content: center;
    text-decoration: none; font-size: 15px; flex-shrink: 0;
    transition: all .15s;
}
.af-back:hover { background: #f3f4f6; border-color: #d1d5db; color: #111827; }
.af-name-input {
    flex: 1; min-width: 0;
    border: 1.5px solid #e8eaf0; border-radius: 9px;
    padding: 8px 14px; font-size: 14px; font-weight: 600;
    color: #1a1d23; outline: none; transition: border-color .15s;
    font-family: inherit;
}
.af-name-input:focus { border-color: #0085f3; }
.af-name-input::placeholder { font-weight: 400; color: #9ca3af; }
.af-header-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.af-status-badge {
    padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
    background: #f3f4f6; color: #6b7280;
}
.af-status-badge.active { background: #dcfce7; color: #16a34a; }

/* ── Builder body ─────────────────────────────────────────────────── */
.af-builder { display: flex; flex: 1; overflow: hidden; }

/* ── Left sidebar ─────────────────────────────────────────────────── */
.af-sidebar {
    width: 230px; flex-shrink: 0;
    background: #fafafa;
    border-right: 1px solid #e8eaf0;
    overflow-y: auto;
    padding: 16px 0 24px;
}
.af-sidebar-section { margin-bottom: 6px; }
.af-sidebar-section-title {
    padding: 8px 16px 4px;
    font-size: 10.5px; font-weight: 700; color: #9ca3af;
    text-transform: uppercase; letter-spacing: .07em;
}
.af-block-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 16px; cursor: pointer;
    font-size: 13px; color: #374151; font-weight: 500;
    transition: background .12s, color .12s;
    border-radius: 0;
}
.af-block-item:hover { background: #eff6ff; color: #2563eb; }
.af-block-item .af-block-icon {
    width: 26px; height: 26px; border-radius: 7px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 12px; flex-shrink: 0;
}
.af-block-item.trigger .af-block-icon { background: #dbeafe; color: #2563eb; }
.af-block-item.condition .af-block-icon { background: #fef9c3; color: #b45309; }
.af-block-item.action .af-block-icon { background: #dcfce7; color: #16a34a; }
.af-sidebar-divider { height: 1px; background: #f0f2f7; margin: 8px 16px; }

/* ── Canvas ───────────────────────────────────────────────────────── */
.af-canvas {
    flex: 1; overflow-y: auto;
    background: #f4f6fb;
    padding: 36px 24px 60px;
    display: flex; justify-content: center;
}
.af-flow { width: 100%; max-width: 520px; }

/* ── Node ─────────────────────────────────────────────────────────── */
.af-node {
    background: #fff;
    border: 1.5px solid #e8eaf0;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    overflow: hidden;
    position: relative;
    transition: box-shadow .15s;
}
.af-node:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); }
.af-node-bar {
    position: absolute; left: 0; top: 0; bottom: 0;
    width: 4px;
}
.af-node.trigger  .af-node-bar { background: #0085f3; }
.af-node.condition .af-node-bar { background: #f59e0b; }
.af-node.action   .af-node-bar { background: #10b981; }

.af-node-head {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 14px 12px 18px;
    border-bottom: 1px solid #f3f4f6;
}
.af-node-icon {
    width: 30px; height: 30px; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 13px; flex-shrink: 0;
}
.af-node.trigger  .af-node-icon { background: #dbeafe; color: #2563eb; }
.af-node.condition .af-node-icon { background: #fef9c3; color: #b45309; }
.af-node.action   .af-node-icon { background: #dcfce7; color: #16a34a; }

.af-node-label { flex: 1; min-width: 0; }
.af-node-type {
    font-size: 10.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: #9ca3af;
}
.af-node-name { font-size: 13px; font-weight: 600; color: #1a1d23; margin-top: 1px; }

.af-node-remove {
    width: 26px; height: 26px; border-radius: 6px;
    border: 1px solid #e8eaf0; background: transparent; color: #9ca3af;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 12px; flex-shrink: 0;
    transition: all .15s;
}
.af-node-remove:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

.af-node-body { padding: 14px 18px; }
.af-node-body label {
    font-size: 12px; font-weight: 600; color: #6b7280;
    display: block; margin-bottom: 5px;
}

/* ── Connector ────────────────────────────────────────────────────── */
.af-connector {
    width: 2px; height: 30px;
    background: #d1d5db;
    margin: 0 auto;
    position: relative;
}
.af-connector::after {
    content: '';
    position: absolute; bottom: -7px; left: 50%;
    transform: translateX(-50%);
    border: 7px solid transparent;
    border-top-color: #d1d5db;
}

/* ── Empty trigger placeholder ────────────────────────────────────── */
.af-trigger-placeholder {
    border: 2px dashed #c7d2fe;
    border-radius: 12px;
    padding: 28px;
    text-align: center;
    color: #6366f1;
    font-size: 13px; font-weight: 500;
    background: #eef2ff;
    cursor: default;
}
.af-trigger-placeholder i { font-size: 28px; display: block; margin-bottom: 8px; opacity: .7; }

/* ── Add action button ────────────────────────────────────────────── */
.af-add-action {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 12px;
    border: 2px dashed #d1d5db; border-radius: 12px;
    background: transparent; color: #6b7280;
    font-size: 13px; font-weight: 500;
    cursor: pointer; transition: all .15s;
    margin-top: 4px;
}
.af-add-action:hover { border-color: #0085f3; color: #0085f3; background: #eff6ff; }

/* ── Conditions group header ──────────────────────────────────────── */
.af-group-label {
    font-size: 10.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: #9ca3af;
    text-align: center; margin: 4px 0 8px;
}

/* ── Form controls inside nodes ──────────────────────────────────── */
.af-node-body .form-control,
.af-node-body .form-select {
    border: 1.5px solid #e8eaf0;
    border-radius: 8px;
    padding: 7px 11px;
    font-size: 13px;
    color: #374151;
    background: #fff;
    outline: none;
    width: 100%;
    box-sizing: border-box;
    font-family: inherit;
    transition: border-color .15s;
}
.af-node-body .form-control:focus,
.af-node-body .form-select:focus { border-color: #0085f3; }
.af-node-body textarea.form-control { resize: vertical; min-height: 64px; }
.af-node-body .row-pair { display: flex; gap: 8px; }
.af-node-body .row-pair > * { flex: 1; min-width: 0; }

/* ── Tag multi-select (reused from drawer) ────────────────────────── */
.tag-select-wrap {
    display: flex; flex-wrap: wrap; align-items: center; gap: 4px;
    border: 1.5px solid #e8eaf0; border-radius: 8px;
    padding: 5px 8px; cursor: text; min-height: 36px;
    background: #fff; transition: border-color .15s;
}
.tag-select-wrap:focus-within { border-color: #0085f3; }
.tag-chip-item {
    display: inline-flex; align-items: center; gap: 4px;
    background: #eff6ff; color: #2563eb;
    border: 1px solid #bfdbfe; border-radius: 6px;
    padding: 2px 8px; font-size: 12px; font-weight: 500;
}
.tag-chip-item button {
    background: none; border: none; color: #93c5fd;
    cursor: pointer; font-size: 13px; line-height: 1; padding: 0;
}
.tag-chip-item button:hover { color: #2563eb; }
.tag-input-ghost {
    border: none; outline: none; font-size: 13px;
    min-width: 80px; flex: 1; padding: 2px 4px;
    font-family: inherit; color: #374151; background: transparent;
}
.tag-suggestions {
    position: absolute; z-index: 200; background: #fff;
    border: 1px solid #e5e7eb; border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,.1);
    max-height: 160px; overflow-y: auto; min-width: 200px;
    left: 0; top: 100%; margin-top: 2px;
}
.tag-sug-item { padding: 7px 12px; font-size: 13px; cursor: pointer; color: #374151; }
.tag-sug-item:hover { background: #eff6ff; color: #2563eb; }

/* ── Buttons ──────────────────────────────────────────────────────── */
.btn-primary-sm {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; background: #0085f3; color: #fff;
    border: none; border-radius: 100px; font-size: 13.5px;
    font-weight: 600; cursor: pointer; transition: background .15s;
    text-decoration: none; font-family: inherit;
}
.btn-primary-sm:hover { background: #0070d1; color: #fff; }
.btn-cancel-sm {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; background: #fff; color: #374151;
    border: 1.5px solid #e8eaf0; border-radius: 100px; font-size: 13.5px;
    font-weight: 600; cursor: pointer; transition: all .15s;
    text-decoration: none; font-family: inherit;
}
.btn-cancel-sm:hover { background: #f3f4f6; color: #111827; }

/* ── Mobile ── */
@media (max-width: 768px) {
    .af-builder { flex-direction: column; }
    .af-sidebar {
        width: 100%; max-height: 180px;
        border-right: none; border-bottom: 1px solid #e8eaf0;
        display: flex; flex-direction: row; overflow-x: auto; overflow-y: hidden;
        -webkit-overflow-scrolling: touch; padding: 8px 0; gap: 0;
    }
    .af-sidebar-section { display: flex; flex-direction: row; gap: 4px; padding: 0 8px; margin-bottom: 0; flex-shrink: 0; }
    .af-sidebar-section-title { display: none; }
    .af-sidebar-divider { display: none; }
    .af-block-item {
        white-space: nowrap; padding: 6px 12px; border-radius: 20px;
        border: 1px solid #e8eaf0; background: #fff; font-size: 12px;
    }
    .af-canvas { padding: 20px 12px 40px; }
    .af-flow { max-width: 100%; }
    .af-header { padding: 10px 14px; flex-wrap: wrap; gap: 8px; }
    .af-name-input { min-width: 0; width: 100%; }
}
</style>
@endpush

@section('content')
<div class="af-page">

    @include('tenant.settings._tabs')

    {{-- Header --}}
    <div class="af-header">
        <a href="{{ route('settings.automations') }}" class="af-back" title="Voltar">
            <i class="bi bi-arrow-left"></i>
        </a>
        <input type="text" class="af-name-input" id="afName"
            placeholder="Nome da automação..."
            value="{{ $isEdit ? $automation->name : '' }}">
        <div class="af-header-right">
            @if($isEdit)
                <span class="af-status-badge {{ $automation->is_active ? 'active' : '' }}" id="afStatusBadge">
                    {{ $automation->is_active ? 'Ativa' : 'Inativa' }}
                </span>
            @endif
            <a href="{{ route('settings.automations') }}" class="btn-cancel-sm">Cancelar</a>
            <button class="btn-primary-sm" onclick="saveAutomation()">
                <i class="bi bi-check2"></i> Salvar automação
            </button>
        </div>
    </div>

    {{-- Builder --}}
    <div class="af-builder">

        {{-- Sidebar --}}
        <div class="af-sidebar">

            <div class="af-sidebar-section">
                <div class="af-sidebar-section-title">Gatilho</div>
                <div class="af-block-item trigger" onclick="setTrigger('message_received')">
                    <span class="af-block-icon"><i class="bi bi-chat-dots"></i></span>Mensagem recebida
                </div>
                <div class="af-block-item trigger" onclick="setTrigger('conversation_created')">
                    <span class="af-block-icon"><i class="bi bi-plus-circle"></i></span>Nova conversa
                </div>
                <div class="af-block-item trigger" onclick="setTrigger('lead_created')">
                    <span class="af-block-icon"><i class="bi bi-person-plus"></i></span>Lead criado
                </div>
                <div class="af-block-item trigger" onclick="setTrigger('lead_stage_changed')">
                    <span class="af-block-icon"><i class="bi bi-arrow-right-circle"></i></span>Lead movido de etapa
                </div>
                <div class="af-block-item trigger" onclick="setTrigger('lead_won')">
                    <span class="af-block-icon"><i class="bi bi-trophy"></i></span>Lead ganho
                </div>
                <div class="af-block-item trigger" onclick="setTrigger('lead_lost')">
                    <span class="af-block-icon"><i class="bi bi-x-circle"></i></span>Lead perdido
                </div>
                <div class="af-block-item trigger" onclick="setTrigger('date_field')">
                    <span class="af-block-icon"><i class="bi bi-calendar-event"></i></span>Data / Aniversário
                </div>
                <div class="af-block-item trigger" onclick="setTrigger('recurring')">
                    <span class="af-block-icon"><i class="bi bi-arrow-repeat"></i></span>Recorrente
                </div>
            </div>

            <div class="af-sidebar-divider"></div>

            <div class="af-sidebar-section">
                <div class="af-sidebar-section-title">Condições</div>
                <div class="af-block-item condition" onclick="addConditionBlock('message_body')">
                    <span class="af-block-icon"><i class="bi bi-chat-text"></i></span>Corpo da mensagem
                </div>
                <div class="af-block-item condition" onclick="addConditionBlock('lead_source')">
                    <span class="af-block-icon"><i class="bi bi-pin-map"></i></span>Origem do lead
                </div>
                <div class="af-block-item condition" onclick="addConditionBlock('lead_tag')">
                    <span class="af-block-icon"><i class="bi bi-tag"></i></span>Tag do lead
                </div>
                <div class="af-block-item condition" onclick="addConditionBlock('conversation_tag')">
                    <span class="af-block-icon"><i class="bi bi-chat-square-text"></i></span>Tag da conversa
                </div>
            </div>

            <div class="af-sidebar-divider"></div>

            <div class="af-sidebar-section">
                <div class="af-sidebar-section-title">Ações</div>
                <div class="af-block-item action" onclick="addActionBlock('add_tag_lead')">
                    <span class="af-block-icon"><i class="bi bi-tag-fill"></i></span>Adicionar tag ao lead
                </div>
                <div class="af-block-item action" onclick="addActionBlock('remove_tag_lead')">
                    <span class="af-block-icon"><i class="bi bi-tag"></i></span>Remover tag do lead
                </div>
                <div class="af-block-item action" onclick="addActionBlock('add_tag_conversation')">
                    <span class="af-block-icon"><i class="bi bi-chat-square-dots"></i></span>Tag na conversa
                </div>
                <div class="af-block-item action" onclick="addActionBlock('move_to_stage')">
                    <span class="af-block-icon"><i class="bi bi-arrow-right-short"></i></span>Mover para etapa
                </div>
                <div class="af-block-item action" onclick="addActionBlock('set_lead_source')">
                    <span class="af-block-icon"><i class="bi bi-pin-angle"></i></span>Definir origem do lead
                </div>
                <div class="af-block-item action" onclick="addActionBlock('assign_to_user')">
                    <span class="af-block-icon"><i class="bi bi-person-check"></i></span>Atribuir a usuário
                </div>
                <div class="af-block-item action" onclick="addActionBlock('add_note')">
                    <span class="af-block-icon"><i class="bi bi-sticky"></i></span>Adicionar nota
                </div>
                <div class="af-block-item action" onclick="addActionBlock('assign_ai_agent')">
                    <span class="af-block-icon"><i class="bi bi-robot"></i></span>Atribuir agente de IA
                </div>
                <div class="af-block-item action" onclick="addActionBlock('assign_chatbot_flow')">
                    <span class="af-block-icon"><i class="bi bi-diagram-3"></i></span>Atribuir chatbot
                </div>
                <div class="af-block-item action" onclick="addActionBlock('transfer_to_department')">
                    <span class="af-block-icon"><i class="bi bi-building"></i></span>Transferir p/ departamento
                </div>
                <div class="af-block-item action" onclick="addActionBlock('close_conversation')">
                    <span class="af-block-icon"><i class="bi bi-lock"></i></span>Fechar conversa
                </div>
                <div class="af-block-item action" onclick="addActionBlock('send_whatsapp_message')">
                    <span class="af-block-icon"><i class="bi bi-whatsapp"></i></span>Enviar msg WhatsApp
                </div>
                <div class="af-block-item action" onclick="addActionBlock('schedule_whatsapp_message')">
                    <span class="af-block-icon"><i class="bi bi-clock"></i></span>Agendar msg WhatsApp
                </div>
                <div class="af-block-item action" onclick="addActionBlock('assign_campaign')">
                    <span class="af-block-icon"><i class="bi bi-megaphone"></i></span>Atribuir campanha
                </div>
                <div class="af-block-item action" onclick="addActionBlock('set_utm_params')">
                    <span class="af-block-icon"><i class="bi bi-link-45deg"></i></span>Definir parâmetros UTM
                </div>
                <div class="af-block-item action" onclick="addActionBlock('create_task')">
                    <span class="af-block-icon"><i class="bi bi-check2-square"></i></span>Criar tarefa
                </div>
            </div>
        </div>

        {{-- Canvas --}}
        <div class="af-canvas">
            <div class="af-flow" id="afFlow">

                {{-- Trigger slot --}}
                <div id="afTriggerSlot">
                    <div class="af-trigger-placeholder" id="afTriggerPlaceholder">
                        <i class="bi bi-lightning-charge"></i>
                        Selecione um <strong>Gatilho</strong> no painel esquerdo para começar
                    </div>
                </div>

                {{-- Conditions area --}}
                <div id="afConditionsArea" style="display:none;">
                    <div class="af-connector"></div>
                    <div class="af-group-label">SE as condições forem atendidas...</div>
                    <div id="afConditionsList"></div>
                </div>

                {{-- Actions area --}}
                <div id="afActionsArea" style="display:none;">
                    <div class="af-connector"></div>
                    <div class="af-group-label">ENTÃO executar...</div>
                    <div id="afActionsList"></div>
                    <div class="af-connector" style="height:16px;"></div>
                    <button type="button" class="af-add-action" onclick="showActionPicker()">
                        <i class="bi bi-plus-circle"></i> Adicionar ação
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

@php
    $automation_json = $isEdit ? $automation->toJson() : 'null';
@endphp

<script>
const PIPELINES      = @json($pipelinesJs);
const USERS          = @json($users);
const AI_AGENTS      = @json($aiAgents);
const CHATBOT_FLOWS  = @json($chatbotFlows);
const DEPARTMENTS    = @json($departments);
const WAHA_CONNECTED = {{ $wahaConnected ? 'true' : 'false' }};
const LEAD_TAGS      = @json($leadTags->values());
const LEAD_SOURCES   = @json($leadSources->values());
const WAPP_TAGS           = @json($whatsappTags->pluck('name')->values());
const DATE_CUSTOM_FIELDS  = @json($dateCustomFields->map(fn($f) => ['id' => $f->id, 'label' => $f->label])->values());
const ALL_LEAD_SOURCES    = @json($allLeadSources);
const CAMPAIGNS           = @json($campaigns);
const NOTE_VARS_HINT = @json($noteVarsHint);
const MSG_VARS_HINT  = @json($msgVarsHint);
const AUTOMATION_DATA = {!! $automation_json !!};
const IS_EDIT        = {{ $isEdit ? 'true' : 'false' }};
const EDIT_ID        = {{ $isEdit ? $automation->id : 'null' }};

let _nodeIdx = 0;
const _tagSugCache = {};

// ─────────────────────────────────────────────────────────────────────
// Trigger
// ─────────────────────────────────────────────────────────────────────
const TRIGGER_META = {
    message_received:    { icon:'bi-chat-dots',          label:'Mensagem recebida' },
    conversation_created:{ icon:'bi-plus-circle',        label:'Nova conversa' },
    lead_created:        { icon:'bi-person-plus',        label:'Lead criado' },
    lead_stage_changed:  { icon:'bi-arrow-right-circle', label:'Lead movido de etapa' },
    lead_won:            { icon:'bi-trophy',             label:'Lead ganho' },
    lead_lost:           { icon:'bi-x-circle',           label:'Lead perdido' },
    date_field:          { icon:'bi-calendar-event',     label:'Data / Aniversário' },
    recurring:           { icon:'bi-arrow-repeat',       label:'Recorrente (Semanal/Mensal)' },
};

function setTrigger(type, prefillConfig) {
    const slot = document.getElementById('afTriggerSlot');
    const meta = TRIGGER_META[type] || { icon:'bi-lightning-charge', label: type };

    const configHtml = buildTriggerConfig(type, prefillConfig || {});

    slot.innerHTML = `
        <div class="af-node trigger" id="afTriggerNode" data-trigger-type="${type}">
            <div class="af-node-bar"></div>
            <div class="af-node-head">
                <span class="af-node-icon"><i class="bi ${meta.icon}"></i></span>
                <div class="af-node-label">
                    <div class="af-node-type">Gatilho</div>
                    <div class="af-node-name">${h(meta.label)}</div>
                </div>
            </div>
            <div class="af-node-body" id="afTriggerBody">
                ${configHtml}
            </div>
        </div>`;

    // Show actions area when trigger is set
    document.getElementById('afActionsArea').style.display = '';
}

function buildTriggerConfig(type, prefill) {
    let html = '';
    if (['message_received','conversation_created'].includes(type)) {
        const opts = [['both','WhatsApp e Instagram'],['whatsapp','Somente WhatsApp'],['instagram','Somente Instagram']]
            .map(([v,l]) => `<option value="${v}" ${prefill.channel===v?'selected':''}>${l}</option>`).join('');
        html += `<label>Canal</label>
            <select class="form-select" id="tcChannel"><option value="both">WhatsApp e Instagram</option>
            <option value="whatsapp" ${prefill.channel==='whatsapp'?'selected':''}>Somente WhatsApp</option>
            <option value="instagram" ${prefill.channel==='instagram'?'selected':''}>Somente Instagram</option></select>`;
    }
    if (type === 'lead_stage_changed') {
        const pOpts = PIPELINES.map(p => `<option value="${p.id}" ${prefill.pipeline_id==p.id?'selected':''}>${h(p.name)}</option>`).join('');
        html += `<label>Funil <small style="font-weight:400;color:#9ca3af;">(opcional)</small></label>
            <select class="form-select" id="tcPipeline" onchange="onTcPipelineChange()">
                <option value="">Qualquer funil</option>${pOpts}
            </select>
            <label style="margin-top:10px;">Etapa destino <small style="font-weight:400;color:#9ca3af;">(opcional)</small></label>
            <select class="form-select" id="tcStage"><option value="">Qualquer etapa</option></select>`;
        if (prefill.pipeline_id) {
            setTimeout(() => {
                const pEl = document.getElementById('tcPipeline');
                if (pEl) { pEl.value = prefill.pipeline_id; onTcPipelineChange(); }
                setTimeout(() => {
                    const sEl = document.getElementById('tcStage');
                    if (sEl && prefill.stage_id) sEl.value = prefill.stage_id;
                }, 60);
            }, 30);
        }
    }
    if (['lead_created','lead_won','lead_lost'].includes(type)) {
        const pOpts = PIPELINES.map(p => `<option value="${p.id}" ${prefill.pipeline_id==p.id?'selected':''}>${h(p.name)}</option>`).join('');
        html += `<label>Funil <small style="font-weight:400;color:#9ca3af;">(opcional)</small></label>
            <select class="form-select" id="tcPipeline">
                <option value="">Qualquer funil</option>${pOpts}
            </select>`;
    }
    if (type === 'lead_created') {
        const srcOpts = LEAD_SOURCES.map(s => `<option value="${s}" ${prefill.source===s?'selected':''}>${h(s)}</option>`).join('');
        html += `<label style="margin-top:10px;">Origem <small style="font-weight:400;color:#9ca3af;">(opcional)</small></label>
            <select class="form-select" id="tcSource">
                <option value="">Qualquer origem</option>${srcOpts}
            </select>`;
    }
    if (type === 'date_field') {
        const nativeOpts = `<option value="birthday" ${prefill.date_field==='birthday'?'selected':''}>Aniversário (campo nativo)</option>`;
        const cfOpts = DATE_CUSTOM_FIELDS
            .map(f => `<option value="cf:${f.id}" ${prefill.date_field===`cf:${f.id}`?'selected':''}>Campo: ${h(f.label)}</option>`)
            .join('');
        const dbVal  = prefill.days_before  ?? 0;
        const repVal = prefill.repeat_yearly !== undefined ? prefill.repeat_yearly : true;
        html += `<label>Campo de data</label>
            <select class="form-select" id="tcDateField">
                <optgroup label="Campo nativo">${nativeOpts}</optgroup>
                ${DATE_CUSTOM_FIELDS.length ? `<optgroup label="Campos personalizados">${cfOpts}</optgroup>` : ''}
            </select>
            <label style="margin-top:10px;">Dias de antecedência <small style="font-weight:400;color:#9ca3af;">(0 = no próprio dia)</small></label>
            <input type="number" class="form-control" id="tcDaysBefore"
                   min="0" max="365" value="${h(String(dbVal))}" style="margin-bottom:10px;">
            <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                <input type="checkbox" id="tcRepeatYearly" ${repVal ? 'checked' : ''} style="cursor:pointer;">
                <label for="tcRepeatYearly" style="margin:0;cursor:pointer;font-weight:500;font-size:13px;color:#374151;">
                    Repetir anualmente (ex: aniversários)
                </label>
            </div>`;
    }
    if (type === 'recurring') {
        const rt  = prefill.recurrence_type || 'monthly';
        const rds = (prefill.days || []).join(', ');
        const rtm = prefill.time || '09:00';
        const ft  = prefill.filter_type || 'tag';
        const fv  = prefill.filter_value || '';
        const dl  = prefill.daily_limit ?? 100;
        const ds  = prefill.delay_seconds ?? 8;

        const pOpts = PIPELINES.map(p => p.stages.map(s => `<option value="${s.id}" ${fv==s.id?'selected':''}>${h(p.name)} → ${h(s.name)}</option>`).join('')).join('');

        html += `<label>Tipo de recorrência</label>
            <div style="display:flex;gap:8px;margin-bottom:12px;">
                <label style="display:flex;align-items:center;gap:4px;padding:6px 14px;border:1px solid ${rt==='weekly'?'#0085f3':'#e2e8f0'};border-radius:8px;cursor:pointer;background:${rt==='weekly'?'#eff6ff':'#fff'};font-size:13px;">
                    <input type="radio" name="recType" value="weekly" ${rt==='weekly'?'checked':''} onchange="toggleRecDays()" style="display:none;"> Semanal
                </label>
                <label style="display:flex;align-items:center;gap:4px;padding:6px 14px;border:1px solid ${rt==='monthly'?'#0085f3':'#e2e8f0'};border-radius:8px;cursor:pointer;background:${rt==='monthly'?'#eff6ff':'#fff'};font-size:13px;">
                    <input type="radio" name="recType" value="monthly" ${rt==='monthly'?'checked':''} onchange="toggleRecDays()" style="display:none;"> Mensal
                </label>
            </div>
            <div id="recWeekly" style="display:${rt==='weekly'?'flex':'none'};gap:6px;flex-wrap:wrap;margin-bottom:12px;">
                ${['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'].map((d,i) => {
                    const chk = (prefill.days||[]).includes(i);
                    return `<label style="display:flex;align-items:center;gap:4px;padding:4px 10px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;font-size:12.5px;">
                        <input type="checkbox" class="recDayCheck" value="${i}" ${chk?'checked':''}> ${d}
                    </label>`;
                }).join('')}
            </div>
            <div id="recMonthly" style="display:${rt==='monthly'?'block':'none'};margin-bottom:12px;">
                <label>Dias do mês <small style="font-weight:400;color:#9ca3af;">(separe por vírgula: 10, 20)</small></label>
                <input type="text" class="form-control" id="tcRecDays" value="${h(rds)}" placeholder="10, 20">
            </div>
            <label>Horário de envio</label>
            <input type="time" class="form-control" id="tcRecTime" value="${h(rtm)}" style="margin-bottom:12px;">
            <label>Filtrar leads por</label>
            <select class="form-select" id="tcRecFilter" onchange="toggleRecFilter()" style="margin-bottom:8px;">
                <option value="all" ${ft==='all'?'selected':''}>Todos os leads</option>
                <option value="tag" ${ft==='tag'?'selected':''}>Tag específica</option>
                <option value="stage" ${ft==='stage'?'selected':''}>Etapa do funil</option>
            </select>
            <div id="recFilterTag" style="display:${ft==='tag'?'block':'none'};margin-bottom:12px;">
                <input type="text" class="form-control" id="tcRecTagValue" value="${h(fv)}" placeholder="Nome da tag (ex: Pais)">
            </div>
            <div id="recFilterStage" style="display:${ft==='stage'?'block':'none'};margin-bottom:12px;">
                <select class="form-select" id="tcRecStageValue">${pOpts}</select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px;">
                <div>
                    <label>Limite diário</label>
                    <input type="number" class="form-control" id="tcRecLimit" min="1" max="500" value="${dl}">
                </div>
                <div>
                    <label>Delay entre envios (s)</label>
                    <input type="number" class="form-control" id="tcRecDelay" min="1" max="60" value="${ds}">
                </div>
            </div>
            <p style="margin-top:10px;font-size:11.5px;color:#9ca3af;">
                <i class="bi bi-shield-check"></i> Só envia para leads com conversa WhatsApp existente. Delay entre envios para evitar bloqueio.
            </p>`;
    }
    if (!html) {
        html = `<p style="font-size:12px;color:#9ca3af;margin:0;">Nenhuma configuração necessária para este gatilho.</p>`;
    }
    return html;
}

function toggleRecDays() {
    const t = document.querySelector('input[name="recType"]:checked')?.value;
    document.querySelectorAll('label:has(input[name="recType"])').forEach(l => {
        const v = l.querySelector('input').value;
        l.style.borderColor = v === t ? '#0085f3' : '#e2e8f0';
        l.style.background = v === t ? '#eff6ff' : '#fff';
    });
    document.getElementById('recWeekly').style.display = t === 'weekly' ? 'flex' : 'none';
    document.getElementById('recMonthly').style.display = t === 'monthly' ? 'block' : 'none';
}
function toggleRecFilter() {
    const f = document.getElementById('tcRecFilter')?.value;
    document.getElementById('recFilterTag').style.display = f === 'tag' ? 'block' : 'none';
    document.getElementById('recFilterStage').style.display = f === 'stage' ? 'block' : 'none';
}

function onTcPipelineChange() {
    const pId = parseInt(document.getElementById('tcPipeline')?.value);
    const sel  = document.getElementById('tcStage');
    if (!sel) return;
    const p = PIPELINES.find(p => p.id === pId);
    sel.innerHTML = '<option value="">Qualquer etapa</option>' +
        (p ? p.stages.map(s => `<option value="${s.id}">${h(s.name)}</option>`).join('') : '');
}

// ─────────────────────────────────────────────────────────────────────
// Conditions
// ─────────────────────────────────────────────────────────────────────
const CONDITION_META = {
    message_body:     { icon:'bi-chat-text',        label:'Corpo da mensagem' },
    lead_source:      { icon:'bi-pin-map',          label:'Origem do lead' },
    lead_tag:         { icon:'bi-tag',              label:'Tag do lead' },
    conversation_tag: { icon:'bi-chat-square-text', label:'Tag da conversa' },
};

function addConditionBlock(field, prefill) {
    const idx  = _nodeIdx++;
    const meta = CONDITION_META[field] || { icon:'bi-filter', label: field };
    const area = document.getElementById('afConditionsArea');
    const list = document.getElementById('afConditionsList');

    area.style.display = '';

    const node = document.createElement('div');
    node.className = 'af-node condition';
    node.id = `condNode-${idx}`;
    node.style.marginBottom = '8px';
    node.innerHTML = `
        <div class="af-node-bar"></div>
        <div class="af-node-head">
            <span class="af-node-icon"><i class="bi ${meta.icon}"></i></span>
            <div class="af-node-label">
                <div class="af-node-type">Condição</div>
                <div class="af-node-name">${h(meta.label)}</div>
            </div>
            <button type="button" class="af-node-remove" onclick="removeCondNode(${idx})">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="af-node-body" id="condBody-${idx}" data-field="${field}">
            ${buildConditionBody(field, idx, prefill || {})}
        </div>`;
    list.appendChild(node);
}

function buildConditionBody(field, idx, prefill) {
    if (field === 'message_body') {
        const opOpts = [['contains','contém'],['not_contains','não contém'],['equals','é igual a'],['starts_with','começa com']]
            .map(([v,l]) => `<option value="${v}" ${prefill.operator===v?'selected':''}>${l}</option>`).join('');
        return `<div class="row-pair">
            <div><label>Operador</label><select class="form-select" id="cop-${idx}">${opOpts}</select></div>
            <div><label>Valor</label><input type="text" class="form-control" id="cval-${idx}" placeholder="Palavra-chave..." value="${h(prefill.value||'')}"></div>
        </div>`;
    }
    if (field === 'lead_source') {
        const srcOpts = ALL_LEAD_SOURCES.map(s => `<option value="${s}" ${prefill.value===s?'selected':''}>${h(s)}</option>`).join('');
        const opOpts = [['equals','é'],['not_equals','não é']]
            .map(([v,l]) => `<option value="${v}" ${prefill.operator===v?'selected':''}>${l}</option>`).join('');
        return `<div class="row-pair">
            <div><label>Operador</label><select class="form-select" id="cop-${idx}">${opOpts}</select></div>
            <div><label>Origem</label><select class="form-select" id="cval-${idx}">
                <option value="">Selecione...</option>${srcOpts}</select></div>
        </div>`;
    }
    if (field === 'lead_tag') {
        const opOpts = [['contains','contém'],['not_contains','não contém']]
            .map(([v,l]) => `<option value="${v}" ${prefill.operator===v?'selected':''}>${l}</option>`).join('');
        const tagWidget = buildTagSelect(`cval-${idx}`, LEAD_TAGS, prefill.value ? [prefill.value] : []);
        return `<label>Operador</label><select class="form-select" id="cop-${idx}" style="margin-bottom:8px;">${opOpts}</select>
            <label>Tag</label>${tagWidget}`;
    }
    if (field === 'conversation_tag') {
        const opOpts = [['contains','contém'],['not_contains','não contém']]
            .map(([v,l]) => `<option value="${v}" ${prefill.operator===v?'selected':''}>${l}</option>`).join('');
        const tagWidget = buildTagSelect(`cval-${idx}`, WAPP_TAGS, prefill.value ? [prefill.value] : []);
        return `<label>Operador</label><select class="form-select" id="cop-${idx}" style="margin-bottom:8px;">${opOpts}</select>
            <label>Tag</label>${tagWidget}`;
    }
    return '';
}

function removeCondNode(idx) {
    document.getElementById(`condNode-${idx}`)?.remove();
    if (!document.getElementById('afConditionsList').children.length) {
        document.getElementById('afConditionsArea').style.display = 'none';
    }
}

// ─────────────────────────────────────────────────────────────────────
// Actions
// ─────────────────────────────────────────────────────────────────────
const ACTION_META = {
    add_tag_lead:          { icon:'bi-tag-fill',          label:'Adicionar tag ao lead' },
    remove_tag_lead:       { icon:'bi-tag',               label:'Remover tag do lead' },
    add_tag_conversation:  { icon:'bi-chat-square-dots',  label:'Tag na conversa' },
    move_to_stage:         { icon:'bi-arrow-right-short', label:'Mover para etapa' },
    set_lead_source:       { icon:'bi-pin-angle',         label:'Definir origem do lead' },
    assign_to_user:        { icon:'bi-person-check',      label:'Atribuir a usuário' },
    add_note:              { icon:'bi-sticky',            label:'Adicionar nota' },
    assign_ai_agent:       { icon:'bi-robot',             label:'Atribuir agente de IA' },
    assign_chatbot_flow:   { icon:'bi-diagram-3',         label:'Atribuir chatbot' },
    transfer_to_department:{ icon:'bi-building',           label:'Transferir p/ departamento' },
    close_conversation:    { icon:'bi-lock',              label:'Fechar conversa' },
    send_whatsapp_message:     { icon:'bi-whatsapp',    label:'Enviar msg WhatsApp' },
    schedule_whatsapp_message: { icon:'bi-clock',       label:'Agendar msg WhatsApp' },
    assign_campaign:           { icon:'bi-megaphone',   label:'Atribuir campanha' },
    set_utm_params:            { icon:'bi-link-45deg',  label:'Definir parâmetros UTM' },
    create_task:               { icon:'bi-check2-square', label:'Criar tarefa' },
};

function addActionBlock(type, prefill) {
    const idx  = _nodeIdx++;
    const meta = ACTION_META[type] || { icon:'bi-gear', label: type };
    const area = document.getElementById('afActionsArea');
    const list = document.getElementById('afActionsList');

    area.style.display = '';

    const node = document.createElement('div');
    node.className = 'af-node action';
    node.id = `actNode-${idx}`;
    node.style.marginBottom = '8px';
    node.innerHTML = `
        <div class="af-node-bar"></div>
        <div class="af-node-head">
            <span class="af-node-icon"><i class="bi ${meta.icon}"></i></span>
            <div class="af-node-label">
                <div class="af-node-type">Ação</div>
                <div class="af-node-name">${h(meta.label)}</div>
            </div>
            <button type="button" class="af-node-remove" onclick="removeActNode(${idx})">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="af-node-body" id="actBody-${idx}" data-action-type="${type}">
            ${buildActionBody(type, idx, prefill || {})}
        </div>`;
    list.appendChild(node);
}

function buildActionBody(type, idx, prefill) {
    if (type === 'add_tag_lead' || type === 'remove_tag_lead') {
        return `<label>Tags</label>${buildTagSelect(`aval-${idx}`, LEAD_TAGS, prefill.tags || [])}`;
    }
    if (type === 'add_tag_conversation') {
        return `<label>Tags</label>${buildTagSelect(`aval-${idx}`, WAPP_TAGS, prefill.tags || [])}`;
    }
    if (type === 'move_to_stage') {
        const pOpts = PIPELINES.map(p => `<option value="${p.id}">${h(p.name)}</option>`).join('');
        let stageOpts = '';
        if (prefill.stage_id) {
            const p = PIPELINES.find(p => p.stages.some(s => s.id == prefill.stage_id));
            if (p) stageOpts = p.stages.map(s => `<option value="${s.id}" ${s.id==prefill.stage_id?'selected':''}>${h(s.name)}</option>`).join('');
        }
        const selPipe = prefill.stage_id
            ? (PIPELINES.find(p => p.stages.some(s => s.id == prefill.stage_id))?.id || '')
            : '';
        return `<div class="row-pair">
            <div><label>Funil</label>
                <select class="form-select" id="apipe-${idx}" onchange="onActPipelineChange(${idx})">
                    <option value="">Funil...</option>${pOpts}
                </select></div>
            <div><label>Etapa</label>
                <select class="form-select" id="astage-${idx}">
                    <option value="">Etapa...</option>${stageOpts}
                </select></div>
        </div>` + (selPipe ? `<script>setTimeout(()=>{const e=document.getElementById('apipe-${idx}');if(e){e.value=${selPipe};onActPipelineChange(${idx});setTimeout(()=>{const s=document.getElementById('astage-${idx}');if(s)s.value=${prefill.stage_id||0};},60);}},30);<\/script>` : '');
    }
    if (type === 'set_lead_source') {
        const srcOpts = ALL_LEAD_SOURCES.map(s => `<option value="${s}" ${prefill.source===s?'selected':''}>${h(s)}</option>`).join('');
        return `<label>Origem</label><select class="form-select" id="aval-${idx}">
            <option value="">Selecione...</option>${srcOpts}</select>`;
    }
    if (type === 'assign_to_user') {
        const uOpts = USERS.map(u => `<option value="${u.id}" ${prefill.user_id==u.id?'selected':''}>${h(u.name)}</option>`).join('');
        return `<label>Usuário</label><select class="form-select" id="aval-${idx}">
            <option value="">Selecione...</option>${uOpts}</select>`;
    }
    if (type === 'add_note') {
        return `<label>Texto da nota <small style="font-weight:400;color:#9ca3af;">(${NOTE_VARS_HINT})</small></label>
            <textarea class="form-control" id="aval-${idx}" rows="2" placeholder="Digite a nota...">${h(prefill.body||'')}</textarea>`;
    }
    if (type === 'assign_ai_agent') {
        if (!AI_AGENTS.length) return `<p style="font-size:12px;color:#9ca3af;margin:0;">Nenhum agente de IA ativo (WhatsApp).</p>`;
        const aOpts = AI_AGENTS.map(a => `<option value="${a.id}" ${prefill.ai_agent_id==a.id?'selected':''}>${h(a.name)}</option>`).join('');
        return `<label>Agente de IA</label><select class="form-select" id="aval-${idx}">
            <option value="">Selecione...</option>${aOpts}</select>`;
    }
    if (type === 'assign_chatbot_flow') {
        if (!CHATBOT_FLOWS.length) return `<p style="font-size:12px;color:#9ca3af;margin:0;">Nenhum fluxo de chatbot ativo.</p>`;
        const fOpts = CHATBOT_FLOWS.map(f => `<option value="${f.id}" ${prefill.chatbot_flow_id==f.id?'selected':''}>${h(f.name)}</option>`).join('');
        return `<label>Fluxo</label><select class="form-select" id="aval-${idx}">
            <option value="">Selecione...</option>${fOpts}</select>`;
    }
    if (type === 'transfer_to_department') {
        if (!DEPARTMENTS.length) return `<p style="font-size:12px;color:#9ca3af;margin:0;">Nenhum departamento ativo.</p>`;
        const dOpts = DEPARTMENTS.map(d => `<option value="${d.id}" ${prefill.department_id==d.id?'selected':''}>${h(d.name)}</option>`).join('');
        return `<label>Departamento</label><select class="form-select" id="aval-${idx}">
            <option value="">Selecione...</option>${dOpts}</select>`;
    }
    if (type === 'close_conversation') {
        return `<p style="font-size:12px;color:#6b7280;margin:0;"><i class="bi bi-info-circle me-1"></i>A conversa vinculada ao lead será fechada automaticamente.</p>`;
    }
    if (type === 'assign_campaign') {
        if (!CAMPAIGNS.length) return `<p style="font-size:12px;color:#9ca3af;margin:0;">Nenhuma campanha cadastrada.</p>`;
        const cOpts = CAMPAIGNS.map(c => `<option value="${c.id}" ${prefill.campaign_id==c.id?'selected':''}>${h(c.name)}</option>`).join('');
        return `<label>Campanha</label><select class="form-select" id="aval-${idx}">
            <option value="">Selecione...</option>${cOpts}</select>`;
    }
    if (type === 'set_utm_params') {
        const fields = [
            ['utm_source',   'UTM Source',   'ex: google'],
            ['utm_medium',   'UTM Medium',   'ex: cpc'],
            ['utm_campaign', 'UTM Campaign', 'ex: black-friday'],
            ['utm_term',     'UTM Term',     'ex: crm+software'],
            ['utm_content',  'UTM Content',  'ex: banner-topo'],
        ];
        return fields.map(([name, label, ph]) =>
            `<label style="margin-top:6px;">${label} <small style="font-weight:400;color:#9ca3af;">(opcional)</small></label>
            <input type="text" class="form-control utm-field" id="autm_${name}_${idx}"
                   data-utm="${name}" placeholder="${ph}" value="${h(prefill[name]||'')}">`
        ).join('') + `<p style="font-size:11px;color:#9ca3af;margin-top:6px;margin-bottom:0;">Deixe em branco os campos que não deseja alterar.</p>`;
    }
    if (type === 'send_whatsapp_message') {
        if (!WAHA_CONNECTED) return `<p style="font-size:12px;color:#f59e0b;margin:0;"><i class="bi bi-exclamation-triangle me-1"></i>Nenhuma instância WhatsApp conectada.</p>`;
        return `<label>Mensagem <small style="font-weight:400;color:#9ca3af;">(${MSG_VARS_HINT})</small></label>
            <textarea class="form-control" id="aval-${idx}" rows="2" placeholder="Digite a mensagem...">${h(prefill.message||'')}</textarea>`;
    }
    if (type === 'schedule_whatsapp_message') {
        if (!WAHA_CONNECTED) return `<p style="font-size:12px;color:#f59e0b;margin:0;"><i class="bi bi-exclamation-triangle me-1"></i>Nenhuma instância WhatsApp conectada.</p>`;
        return `<label>Mensagem <small style="font-weight:400;color:#9ca3af;">(${MSG_VARS_HINT})</small></label>
            <textarea class="form-control" id="aval-${idx}" rows="2" placeholder="Digite a mensagem...">${h(prefill.message||'')}</textarea>
            <div style="display:flex;gap:8px;margin-top:8px;">
                <div style="flex:1;">
                    <label>Enviar após</label>
                    <input type="number" class="form-control" id="adelay-${idx}" min="1" max="365" value="${prefill.delay_value||1}">
                </div>
                <div style="flex:1;">
                    <label>Unidade</label>
                    <select class="form-control" id="adelayunit-${idx}">
                        <option value="hours" ${prefill.delay_unit==='hours'?'selected':''}>Horas</option>
                        <option value="days"  ${prefill.delay_unit==='days'||!prefill.delay_unit?'selected':''}>Dias</option>
                    </select>
                </div>
            </div>`;
    }
    if (type === 'create_task') {
        const ttypes = [['call','Ligar'],['email','Email'],['task','Tarefa'],['visit','Visita'],['whatsapp','WhatsApp'],['meeting','Reunião']];
        const prios  = [['low','Baixa'],['medium','Média'],['high','Alta']];
        return `<label>Assunto <small style="font-weight:400;color:#9ca3af;">(${MSG_VARS_HINT})</small></label>
            <input type="text" class="form-control" id="aval-${idx}" placeholder="Ligar para @{{contact_name}}" value="${h(prefill.subject||'')}">
            <label style="margin-top:6px;">Descrição</label>
            <textarea class="form-control" id="ataskdesc-${idx}" rows="2" placeholder="Detalhes da tarefa...">${h(prefill.description||'')}</textarea>
            <div style="display:flex;gap:8px;margin-top:6px;">
                <div style="flex:1;">
                    <label>Tipo</label>
                    <select class="form-control" id="atasktype-${idx}">
                        ${ttypes.map(t => `<option value="${t[0]}" ${(prefill.task_type||'task')===t[0]?'selected':''}>${t[1]}</option>`).join('')}
                    </select>
                </div>
                <div style="flex:1;">
                    <label>Prioridade</label>
                    <select class="form-control" id="ataskprio-${idx}">
                        ${prios.map(p => `<option value="${p[0]}" ${(prefill.priority||'medium')===p[0]?'selected':''}>${p[1]}</option>`).join('')}
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:6px;">
                <div style="flex:1;">
                    <label>Prazo (dias)</label>
                    <input type="number" class="form-control" id="ataskdays-${idx}" min="0" max="365" value="${prefill.due_date_offset??1}">
                </div>
                <div style="flex:1;">
                    <label>Horário</label>
                    <input type="time" class="form-control" id="atasktime-${idx}" value="${h(prefill.due_time||'09:00')}">
                </div>
            </div>
            <label style="margin-top:6px;">Atribuir a</label>
            <select class="form-control" id="ataskuser-${idx}">
                <option value="">Automático (responsável do lead)</option>
                ${USERS.map(u => `<option value="${u.id}" ${prefill.assigned_to==u.id?'selected':''}>${h(u.name)}</option>`).join('')}
            </select>`;
    }
    return '';
}

function removeActNode(idx) {
    document.getElementById(`actNode-${idx}`)?.remove();
    const list = document.getElementById('afActionsList');
    if (!list.children.length) {
        document.getElementById('afActionsArea').style.display = 'none';
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

function showActionPicker() {
    // Scroll sidebar to action section visually — or just show a toast hint
    toastr.info('Selecione uma ação no painel esquerdo.', '', {timeOut:2000});
    document.querySelector('.af-sidebar').scrollTo({ top: 9999, behavior: 'smooth' });
}

// ─────────────────────────────────────────────────────────────────────
// Tag multi-select widget
// ─────────────────────────────────────────────────────────────────────
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

function showTagSugs(id, query) {
    const sug = document.getElementById(`${id}-sug`);
    if (!sug) return;
    const suggestions = _tagSugCache[id] || [];
    const lower    = query.toLowerCase().trim();
    const existing = getTagValues(id);
    const filtered = suggestions.filter(s => !existing.includes(s) && (!lower || s.toLowerCase().includes(lower)));
    let html = filtered.map(s => `<div class="tag-sug-item" onmousedown="addTagChip('${id}','${h(s)}')">${h(s)}</div>`).join('');
    if (lower && !suggestions.some(s => s.toLowerCase() === lower) && !existing.includes(lower)) {
        html += `<div class="tag-sug-item" style="color:#0085f3;" onmousedown="addTagChip('${id}','${lower}')"><i class="bi bi-plus me-1"></i>Adicionar "${lower}"</div>`;
    }
    sug.innerHTML = html || '<div class="tag-sug-item" style="color:#9ca3af;font-size:12px;">Sem sugestões</div>';
    sug.style.display = 'block';
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
    const wrap = document.getElementById(`${id}-wrap`);
    const input = document.getElementById(`${id}-input`);
    const sug   = document.getElementById(`${id}-sug`);
    if (!wrap || !input) return;
    if (getTagValues(id).includes(value)) { input.value=''; if(sug) sug.style.display='none'; return; }
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

// ─────────────────────────────────────────────────────────────────────
// Save
// ─────────────────────────────────────────────────────────────────────
function saveAutomation() {
    const name = document.getElementById('afName').value.trim();
    if (!name) { toastr.warning('Informe o nome da automação.'); return; }

    const triggerNode = document.getElementById('afTriggerNode');
    if (!triggerNode) { toastr.warning('Selecione um gatilho.'); return; }

    const triggerType = triggerNode.dataset.triggerType;
    const tc = {};
    const chanEl = document.getElementById('tcChannel');  if (chanEl && chanEl.value) tc.channel = chanEl.value;
    const pipeEl = document.getElementById('tcPipeline'); if (pipeEl && pipeEl.value) tc.pipeline_id = parseInt(pipeEl.value);
    const stgEl  = document.getElementById('tcStage');    if (stgEl  && stgEl.value)  tc.stage_id   = parseInt(stgEl.value);
    const srcEl  = document.getElementById('tcSource');   if (srcEl  && srcEl.value)  tc.source     = srcEl.value;
    const dfEl   = document.getElementById('tcDateField');
    if (dfEl && dfEl.value) {
        tc.date_field    = dfEl.value;
        tc.days_before   = parseInt(document.getElementById('tcDaysBefore')?.value ?? '0', 10) || 0;
        tc.repeat_yearly = document.getElementById('tcRepeatYearly')?.checked ?? true;
    }
    // Recurring trigger config
    if (triggerType === 'recurring') {
        tc.recurrence_type = document.querySelector('input[name="recType"]:checked')?.value || 'monthly';
        if (tc.recurrence_type === 'weekly') {
            tc.days = [...document.querySelectorAll('.recDayCheck:checked')].map(c => parseInt(c.value));
        } else {
            tc.days = (document.getElementById('tcRecDays')?.value || '').split(',').map(d => parseInt(d.trim())).filter(d => !isNaN(d) && d >= 1 && d <= 31);
        }
        tc.time = document.getElementById('tcRecTime')?.value || '09:00';
        tc.filter_type = document.getElementById('tcRecFilter')?.value || 'all';
        if (tc.filter_type === 'tag') tc.filter_value = document.getElementById('tcRecTagValue')?.value || '';
        if (tc.filter_type === 'stage') tc.filter_value = document.getElementById('tcRecStageValue')?.value || '';
        tc.daily_limit = parseInt(document.getElementById('tcRecLimit')?.value || '100');
        tc.delay_seconds = parseInt(document.getElementById('tcRecDelay')?.value || '8');
        if (!tc.days.length) { toastr.warning('Selecione pelo menos um dia para a recorrência.'); return; }
    }

    const conditions = [];
    document.querySelectorAll('[id^="condBody-"]').forEach(body => {
        const idx   = body.id.replace('condBody-','');
        const field = body.dataset.field;
        const op    = document.getElementById(`cop-${idx}`)?.value || 'contains';
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
    document.querySelectorAll('[id^="actBody-"]').forEach(body => {
        if (err) return;
        const idx  = body.id.replace('actBody-','');
        const type = body.dataset.actionType;
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
        } else if (type === 'transfer_to_department') {
            config.department_id = parseInt(document.getElementById(`aval-${idx}`)?.value || 0);
            if (!config.department_id) { toastr.warning('Selecione o departamento.'); err = true; return; }
        } else if (type === 'send_whatsapp_message') {
            config.message = (document.getElementById(`aval-${idx}`)?.value || '').trim();
            if (!config.message) { toastr.warning('Informe a mensagem.'); err = true; return; }
        } else if (type === 'schedule_whatsapp_message') {
            config.message = (document.getElementById(`aval-${idx}`)?.value || '').trim();
            if (!config.message) { toastr.warning('Informe a mensagem para agendar.'); err = true; return; }
            config.delay_value = parseInt(document.getElementById(`adelay-${idx}`)?.value || '1');
            config.delay_unit  = document.getElementById(`adelayunit-${idx}`)?.value || 'days';
            if (config.delay_value < 1) { toastr.warning('O delay deve ser ao menos 1.'); err = true; return; }
        } else if (type === 'assign_campaign') {
            config.campaign_id = parseInt(document.getElementById(`aval-${idx}`)?.value || 0);
            if (!config.campaign_id) { toastr.warning('Selecione a campanha.'); err = true; return; }
        } else if (type === 'set_utm_params') {
            document.querySelectorAll(`#actBody-${idx} .utm-field`).forEach(el => {
                const name = el.dataset.utm;
                const val  = el.value.trim();
                if (val) config[name] = val;
            });
            if (!Object.keys(config).length) { toastr.warning('Preencha ao menos um campo UTM.'); err = true; return; }
        } else if (type === 'create_task') {
            config.subject = (document.getElementById(`aval-${idx}`)?.value || '').trim();
            if (!config.subject) { toastr.warning('Informe o assunto da tarefa.'); err = true; return; }
            config.description     = (document.getElementById(`ataskdesc-${idx}`)?.value || '').trim();
            config.task_type       = document.getElementById(`atasktype-${idx}`)?.value || 'task';
            config.priority        = document.getElementById(`ataskprio-${idx}`)?.value || 'medium';
            config.due_date_offset = parseInt(document.getElementById(`ataskdays-${idx}`)?.value || '1');
            config.due_time        = document.getElementById(`atasktime-${idx}`)?.value || '09:00';
            config.assigned_to     = document.getElementById(`ataskuser-${idx}`)?.value || '';
        }
        actions.push({ type, config });
    });
    if (err) return;
    if (!actions.length) { toastr.warning('Adicione ao menos uma ação.'); return; }

    const url    = IS_EDIT ? `/configuracoes/automacoes/${EDIT_ID}` : '/configuracoes/automacoes';
    const method = IS_EDIT ? 'PUT' : 'POST';

    fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
        },
        body: JSON.stringify({ name, trigger_type: triggerType, trigger_config: tc, conditions, actions }),
    }).then(r => r.json()).then(res => {
        if (res.success) {
            toastr.success(IS_EDIT ? 'Automação atualizada.' : 'Automação criada.');
            setTimeout(() => { window.location.href = '{{ route("settings.automations") }}'; }, 600);
        } else {
            toastr.error(res.message || 'Erro ao salvar.');
        }
    }).catch(() => toastr.error('Erro de comunicação.'));
}

// ─────────────────────────────────────────────────────────────────────
// XSS escape helper
// ─────────────────────────────────────────────────────────────────────
function h(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ─────────────────────────────────────────────────────────────────────
// Pre-fill on edit
// ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (!AUTOMATION_DATA) return;

    const a = AUTOMATION_DATA;
    document.getElementById('afName').value = a.name || '';

    const tc = a.trigger_config || {};
    if (a.trigger_type) setTrigger(a.trigger_type, tc);

    (a.conditions || []).forEach(c => addConditionBlock(c.field, c));
    (a.actions    || []).forEach(ac => addActionBlock(ac.type, ac.config || {}));
});
</script>
@endsection
