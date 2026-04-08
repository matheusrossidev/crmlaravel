@extends('tenant.layouts.app')

@php
    $title = $sequence ? __('sequences.edit') : __('sequences.new');
    $pageIcon = 'arrow-repeat';
@endphp

@push('styles')
<style>
    .seq-page { max-width: 820px; margin: 0 auto; }

    /* ── Cards ── */
    .seq-card {
        background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px;
        margin-bottom: 20px; overflow: hidden;
    }
    .seq-card-header {
        padding: 16px 22px; border-bottom: 1px solid #f0f2f7;
        display: flex; align-items: center; gap: 10px;
        font-size: 14px; font-weight: 700; color: #1a1d23;
    }
    .seq-card-header i { color: #0085f3; font-size: 16px; }
    .seq-card-body { padding: 22px; }

    /* ── Form ── */
    .fg { margin-bottom: 18px; }
    .fg:last-child { margin-bottom: 0; }
    .fg-label {
        display: block; font-size: 12.5px; font-weight: 600;
        color: #374151; margin-bottom: 6px;
    }
    .fg-hint { font-size: 11.5px; color: #9ca3af; margin-top: 4px; }
    .fg-input, .fg-select, .fg-textarea {
        width: 100%; padding: 10px 14px;
        border: 1.5px solid #e5e7eb; border-radius: 10px;
        font-size: 13.5px; color: #1a1d23;
        outline: none; transition: border-color .15s, box-shadow .15s;
        background: #fff; font-family: inherit; box-sizing: border-box;
    }
    .fg-textarea { min-height: 90px; resize: vertical; line-height: 1.5; }
    .fg-input:focus, .fg-select:focus, .fg-textarea:focus {
        border-color: #0085f3; box-shadow: 0 0 0 3px rgba(0,133,243,.08);
    }
    .fg-row { display: flex; gap: 14px; }
    .fg-row .fg { flex: 1; }

    /* ── Checkboxes ── */
    .check-group { display: flex; flex-direction: column; gap: 8px; }
    .check-item {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 16px; background: #f9fafb; border-radius: 10px;
        border: 1.5px solid transparent; cursor: pointer; transition: all .15s;
    }
    .check-item:hover { border-color: #e5e7eb; }
    .check-item input[type="checkbox"] {
        width: 18px; height: 18px; accent-color: #0085f3;
        border-radius: 4px; flex-shrink: 0;
    }
    .check-item-text { font-size: 13px; color: #374151; font-weight: 500; }
    .check-item-hint { font-size: 11.5px; color: #9ca3af; margin-top: 1px; }

    /* ── Variables pill ── */
    .var-pills {
        display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 18px;
    }
    .var-pill {
        padding: 3px 10px; background: #f0f4ff; color: #3b82f6;
        border-radius: 100px; font-size: 11.5px; font-weight: 600;
        cursor: pointer; transition: all .15s; border: 1px solid transparent;
    }
    .var-pill:hover { background: #dbeafe; border-color: #93c5fd; }

    /* ── Timeline / Steps ── */
    .steps-container { position: relative; }

    .step-item { position: relative; display: flex; gap: 16px; }

    /* Timeline line */
    .step-timeline {
        display: flex; flex-direction: column; align-items: center;
        width: 36px; flex-shrink: 0; position: relative;
    }
    .step-dot {
        width: 36px; height: 36px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 700; color: #fff;
        z-index: 1; flex-shrink: 0;
    }
    .step-dot.message    { background: #0085f3; }
    .step-dot.wait_reply { background: #f59e0b; }
    .step-dot.action     { background: #8b5cf6; }
    .step-dot.condition  { background: #10b981; }

    .step-line {
        width: 2px; flex: 1; background: #e5e7eb; min-height: 16px;
    }

    /* Step card */
    .step-content {
        flex: 1; background: #fff; border: 1.5px solid #e8eaf0;
        border-radius: 12px; padding: 16px 18px; margin-bottom: 12px;
        transition: border-color .15s, box-shadow .15s;
    }
    .step-content:hover { border-color: #cbd5e1; box-shadow: 0 2px 8px rgba(0,0,0,.04); }

    .step-top {
        display: flex; align-items: center; gap: 10px; margin-bottom: 12px;
    }
    .step-type-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 12px; border-radius: 100px; font-size: 12px; font-weight: 600;
    }
    .step-type-badge.message    { background: #eff6ff; color: #2563eb; }
    .step-type-badge.wait_reply { background: #fffbeb; color: #d97706; }
    .step-type-badge.action     { background: #f5f3ff; color: #7c3aed; }
    .step-type-badge.condition  { background: #ecfdf5; color: #059669; }

    .step-type-select {
        padding: 4px 10px; border: 1.5px solid #e5e7eb; border-radius: 8px;
        font-size: 12.5px; background: #fff; color: #374151; outline: none;
        cursor: pointer;
    }
    .step-type-select:focus { border-color: #0085f3; }

    .step-remove-btn {
        margin-left: auto; background: none; border: none;
        color: #d1d5db; cursor: pointer; font-size: 16px;
        padding: 4px 6px; border-radius: 6px; transition: all .15s;
    }
    .step-remove-btn:hover { color: #ef4444; background: #fef2f2; }

    /* Delay chip between steps */
    .step-delay-chip {
        display: flex; align-items: center; gap: 6px;
        margin: -4px 0 4px 50px; /* aligned with content */
        font-size: 12px; color: #9ca3af;
    }
    .step-delay-chip input {
        width: 60px; padding: 4px 8px; border: 1.5px solid #e5e7eb;
        border-radius: 7px; font-size: 12px; text-align: center; outline: none;
    }
    .step-delay-chip input:focus { border-color: #0085f3; }
    .step-delay-chip select {
        padding: 4px 8px; border: 1.5px solid #e5e7eb;
        border-radius: 7px; font-size: 12px; background: #fff; outline: none;
    }

    /* Add step button */
    .btn-add-step {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%; padding: 14px; border: 2px dashed #d1d5db; border-radius: 12px;
        background: none; color: #6b7280; font-size: 13px; font-weight: 600;
        cursor: pointer; transition: all .15s; margin-top: 8px;
    }
    .btn-add-step:hover { border-color: #0085f3; color: #0085f3; background: #f0f7ff; }

    /* ── Footer ── */
    .seq-footer {
        display: flex; gap: 10px; justify-content: flex-end;
        padding: 20px 0;
    }
    .btn-seq-save {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 10px 28px; background: #0085f3; color: #fff;
        border: none; border-radius: 100px; font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
    }
    .btn-seq-save:hover { background: #0070d1; }
    .btn-seq-cancel {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 10px 24px; background: #fff; color: #374151;
        border: 1.5px solid #e8eaf0; border-radius: 100px;
        font-size: 13px; font-weight: 600; cursor: pointer;
    }
    .btn-seq-cancel:hover { background: #f4f6fb; }

    @media (max-width: 640px) {
        .fg-row { flex-direction: column; gap: 0; }
    }
</style>
@endpush

@section('content')
<div class="page-container seq-page">

    {{-- Configurações --}}
    <div class="seq-card">
        <div class="seq-card-header">
            <i class="bi bi-gear"></i> {{ __('sequences.section_settings') }}
        </div>
        <div class="seq-card-body">
            <div class="fg">
                <label class="fg-label">{{ __('sequences.field_name') }}</label>
                <input type="text" id="seqName" class="fg-input" placeholder="{{ __('sequences.field_name_ph') }}"
                       value="{{ $sequence->name ?? '' }}" maxlength="100">
            </div>

            <div class="fg">
                <label class="fg-label">{{ __('sequences.field_desc') }}</label>
                <input type="text" id="seqDesc" class="fg-input" placeholder="{{ __('sequences.field_desc_ph') }}"
                       value="{{ $sequence->description ?? '' }}" maxlength="191">
            </div>

            <div class="check-group">
                <label class="check-item">
                    <input type="checkbox" id="exitOnReply" {{ ($sequence->exit_on_reply ?? true) ? 'checked' : '' }}>
                    <div>
                        <div class="check-item-text">{{ __('sequences.field_exit_reply') }}</div>
                        <div class="check-item-hint">Se o lead responder ou o atendente enviar uma mensagem, a sequência para automaticamente.</div>
                    </div>
                </label>
                <label class="check-item">
                    <input type="checkbox" id="exitOnStage" {{ ($sequence->exit_on_stage_change ?? false) ? 'checked' : '' }}>
                    <div>
                        <div class="check-item-text">{{ __('sequences.field_exit_stage') }}</div>
                        <div class="check-item-hint">Se o lead mudar de etapa no funil, a sequência para.</div>
                    </div>
                </label>
            </div>
        </div>
    </div>

    {{-- Steps --}}
    <div class="seq-card">
        <div class="seq-card-header">
            <i class="bi bi-list-ol"></i> {{ __('sequences.section_steps') }}
        </div>
        <div class="seq-card-body">
            <div class="var-pills">
                <span class="var-pill" onclick="insertVar('@{{nome}}')">@{{nome}}</span>
                <span class="var-pill" onclick="insertVar('@{{empresa}}')">@{{empresa}}</span>
                <span class="var-pill" onclick="insertVar('@{{email}}')">@{{email}}</span>
                <span class="var-pill" onclick="insertVar('@{{phone}}')">@{{phone}}</span>
                <span class="var-pill" onclick="insertVar('@{{etapa}}')">@{{etapa}}</span>
                <span class="var-pill" onclick="insertVar('@{{score}}')">@{{score}}</span>
            </div>

            <div class="steps-container" id="stepList"></div>

            <button class="btn-add-step" onclick="addStep()">
                <i class="bi bi-plus-circle"></i> {{ __('sequences.step_add') }}
            </button>
        </div>
    </div>

    {{-- Footer --}}
    <div class="seq-footer">
        <a href="{{ route('settings.sequences') }}" class="btn-seq-cancel">{{ __('sequences.btn_cancel') }}</a>
        <button class="btn-seq-save" onclick="saveSequence()">
            <i class="bi bi-check2"></i> {{ __('sequences.btn_save') }}
        </button>
    </div>

</div>
@endsection

@push('scripts')
<script>
const SLANG = {!! json_encode(__('sequences')) !!};
const CSRF  = document.querySelector('meta[name="csrf-token"]')?.content;
const IS_EDIT    = {{ $sequence ? 'true' : 'false' }};
const STORE_URL  = {!! json_encode(route('settings.sequences.store')) !!};
const UPDATE_URL = {!! json_encode($sequence ? route('settings.sequences.update', $sequence) : '') !!};
const INDEX_URL  = {!! json_encode(route('settings.sequences')) !!};

const STEP_ICONS = { message: 'chat-dots', wait_reply: 'hourglass-split', action: 'lightning', condition: 'signpost-split' };
const STEP_LABELS = { message: SLANG.step_message, wait_reply: SLANG.step_wait_reply, action: SLANG.step_action, condition: SLANG.step_condition };
const WHATSAPP_INSTANCES = @json($whatsappInstances ?? []);

let steps = [];
let lastFocusedTextarea = null;

@if($sequence && $sequence->steps)
steps = {!! json_encode($sequence->steps->map(fn($s) => [
    'type' => $s->type,
    'delay_minutes' => $s->delay_minutes,
    'config' => $s->config,
])->values()) !!};
@else
steps = [{ type: 'message', delay_minutes: 0, config: { body: '' } }];
@endif

function renderSteps() {
    const list = document.getElementById('stepList');
    list.innerHTML = '';

    steps.forEach((step, i) => {
        // Delay chip (not on first step)
        if (i > 0) {
            const delayVal = step.delay_minutes;
            let displayVal = delayVal;
            let displayUnit = 'minutes';
            if (delayVal >= 1440 && delayVal % 1440 === 0) { displayVal = delayVal / 1440; displayUnit = 'days'; }
            else if (delayVal >= 60 && delayVal % 60 === 0) { displayVal = delayVal / 60; displayUnit = 'hours'; }

            list.insertAdjacentHTML('beforeend', `
                <div class="step-delay-chip">
                    <i class="bi bi-clock"></i>
                    ${SLANG.step_delay}
                    <input type="number" min="0" value="${displayVal}" data-step="${i}" data-unit="${displayUnit}" onchange="updateDelay(${i}, this)">
                    <select data-step="${i}" onchange="updateDelayUnit(${i}, this)">
                        <option value="minutes" ${displayUnit==='minutes'?'selected':''}>${SLANG.minutes}</option>
                        <option value="hours" ${displayUnit==='hours'?'selected':''}>${SLANG.hours}</option>
                        <option value="days" ${displayUnit==='days'?'selected':''}>${SLANG.days}</option>
                    </select>
                </div>`);
        }

        // Step config HTML
        let configHtml = '';
        if (step.type === 'message') {
            const insOpts = WHATSAPP_INSTANCES.map(ins => {
                const lbl = (ins.label || ins.phone_number || ('#' + ins.id)) + (ins.is_primary ? ' ★' : '');
                const sel = (step.config.instance_id == ins.id) ? 'selected' : '';
                return `<option value="${ins.id}" ${sel}>${escapeHtml(lbl)}</option>`;
            }).join('');
            const insBlock = WHATSAPP_INSTANCES.length > 0 ? `
                <label class="fg-label">${SLANG.step_send_via || 'Enviar via'}</label>
                <select class="fg-select" data-step="${i}"
                        onchange="steps[${i}].config.instance_id = this.value ? parseInt(this.value) : null">
                    <option value="">${SLANG.step_send_via_auto || 'Automático (conversa atual ou padrão do tenant)'}</option>
                    ${insOpts}
                </select>` : '';
            configHtml = `
                ${insBlock}
                <label class="fg-label" style="margin-top:8px;">${SLANG.step_body}</label>
                <textarea class="fg-textarea step-textarea" data-step="${i}" placeholder="${SLANG.step_body_ph}"
                    onfocus="lastFocusedTextarea=this">${escapeHtml(step.config.body || '')}</textarea>`;
        } else if (step.type === 'wait_reply') {
            configHtml = `
                <label class="fg-label">${SLANG.step_timeout}</label>
                <div style="display:flex;align-items:center;gap:8px;">
                    <input type="number" class="fg-input" style="width:120px;" min="1" value="${step.config.timeout_minutes || 1440}"
                           onchange="steps[${i}].config.timeout_minutes=parseInt(this.value)||1440">
                    <span style="font-size:12.5px;color:#9ca3af;">${SLANG.minutes}</span>
                </div>`;
        } else if (step.type === 'action') {
            configHtml = `
                <label class="fg-label">Tipo de ação</label>
                <select class="fg-select" onchange="steps[${i}].config.type=this.value">
                    <option value="add_tag" ${(step.config.type||'')==='add_tag'?'selected':''}>Adicionar Tag</option>
                    <option value="move_stage" ${(step.config.type||'')==='move_stage'?'selected':''}>Mover Etapa</option>
                    <option value="assign_user" ${(step.config.type||'')==='assign_user'?'selected':''}>Atribuir Usuário</option>
                </select>`;
        }

        const typeOptions = ['message','wait_reply','action'].map(t =>
            `<option value="${t}" ${step.type===t?'selected':''}>${STEP_LABELS[t]}</option>`
        ).join('');

        list.insertAdjacentHTML('beforeend', `
            <div class="step-item" data-index="${i}">
                <div class="step-timeline">
                    <div class="step-dot ${step.type}">
                        <i class="bi bi-${STEP_ICONS[step.type] || 'circle'}"></i>
                    </div>
                    ${i < steps.length - 1 ? '<div class="step-line"></div>' : ''}
                </div>
                <div class="step-content">
                    <div class="step-top">
                        <select class="step-type-select" onchange="changeStepType(${i}, this.value)">
                            ${typeOptions}
                        </select>
                        ${steps.length > 1 ? `<button class="step-remove-btn" onclick="removeStep(${i})" title="${SLANG.step_remove}"><i class="bi bi-x-lg"></i></button>` : ''}
                    </div>
                    ${configHtml}
                </div>
            </div>`);
    });
}

function updateDelay(i, input) {
    const unit = input.parentElement.querySelector('select').value;
    const val = parseInt(input.value) || 0;
    steps[i].delay_minutes = unit === 'days' ? val * 1440 : unit === 'hours' ? val * 60 : val;
}

function updateDelayUnit(i, select) {
    const input = select.parentElement.querySelector('input');
    const val = parseInt(input.value) || 0;
    steps[i].delay_minutes = select.value === 'days' ? val * 1440 : select.value === 'hours' ? val * 60 : val;
}

function addStep() {
    steps.push({ type: 'message', delay_minutes: 60, config: { body: '' } });
    renderSteps();
}

function removeStep(i) {
    steps.splice(i, 1);
    renderSteps();
}

function changeStepType(i, type) {
    syncTextareas();
    const defaults = {
        message: { body: '' },
        wait_reply: { timeout_minutes: 1440 },
        action: { type: 'add_tag', params: {} },
        condition: { field: 'score', operator: 'gt', value: 50 },
    };
    steps[i].type = type;
    steps[i].config = defaults[type] || {};
    renderSteps();
}

function insertVar(varName) {
    if (lastFocusedTextarea) {
        const ta = lastFocusedTextarea;
        const start = ta.selectionStart;
        const end = ta.selectionEnd;
        ta.value = ta.value.substring(0, start) + varName + ta.value.substring(end);
        ta.focus();
        ta.selectionStart = ta.selectionEnd = start + varName.length;
        // sync
        const idx = parseInt(ta.dataset.step);
        if (!isNaN(idx) && steps[idx]?.type === 'message') {
            steps[idx].config.body = ta.value;
        }
    }
}

function syncTextareas() {
    document.querySelectorAll('.step-textarea').forEach(ta => {
        const idx = parseInt(ta.dataset.step);
        if (!isNaN(idx) && steps[idx]?.type === 'message') {
            steps[idx].config.body = ta.value;
        }
    });
}

async function saveSequence() {
    const name = document.getElementById('seqName').value.trim();
    if (!name) { document.getElementById('seqName').focus(); return; }
    if (!steps.length) { toastr.error('Adicione pelo menos 1 step.'); return; }

    syncTextareas();

    // Sync numeric inputs
    document.querySelectorAll('.step-content input[type="number"]').forEach(el => {
        const idx = el.closest('.step-item')?.dataset?.index;
        if (idx !== undefined && steps[idx]?.type === 'wait_reply') {
            steps[idx].config.timeout_minutes = parseInt(el.value) || 1440;
        }
    });

    const payload = {
        name,
        description: document.getElementById('seqDesc').value.trim() || null,
        exit_on_reply: document.getElementById('exitOnReply').checked,
        exit_on_stage_change: document.getElementById('exitOnStage').checked,
        steps: steps.map(s => ({
            type: s.type,
            delay_minutes: s.delay_minutes || 0,
            config: s.config,
        })),
    };

    const url = IS_EDIT ? UPDATE_URL : STORE_URL;
    const method = IS_EDIT ? 'PUT' : 'POST';

    try {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            toastr.success(IS_EDIT ? SLANG.toast_updated : SLANG.toast_created);
            setTimeout(() => window.location.href = INDEX_URL, 800);
        } else {
            toastr.error(data.message || SLANG.toast_error);
        }
    } catch (e) {
        toastr.error(SLANG.toast_error);
    }
}

function escapeHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

renderSteps();
</script>
@endpush
