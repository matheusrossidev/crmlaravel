@extends('tenant.layouts.app')

@php
    $title    = __('ai_agents.create_title');
    $pageIcon = 'robot';
@endphp

@push('styles')
<style>
/* ── Wizard container ─────────────────────────────────────────────────────── */
.wizard-wrap {
    max-width: 680px;
    margin: 0 auto;
    padding: 28px 0 40px;
}

.wizard-card {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
}

/* ── Progress ─────────────────────────────────────────────────────────────── */
.wizard-progress-bar {
    height: 4px;
    background: #f0f2f7;
}
.wizard-progress-fill {
    height: 4px;
    background: #0085f3;
    transition: width .4s ease;
    border-radius: 0 4px 4px 0;
}

.wizard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 28px 0;
}
.wizard-step-counter {
    font-size: 12px;
    font-weight: 600;
    color: #9ca3af;
    letter-spacing: .04em;
}
.wizard-back-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    background: none;
    border: none;
    color: #9ca3af;
    font-size: 13px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 7px;
    transition: all .15s;
}
.wizard-back-btn:hover { background: #f4f6fb; color: #3B82F6; }
.wizard-back-btn.hidden { visibility: hidden; }

/* ── Steps ────────────────────────────────────────────────────────────────── */
.wizard-body {
    padding: 28px 28px 24px;
    min-height: 260px;
}

.wizard-step { display: none; }
.wizard-step.active { display: block; }

.wizard-question {
    font-size: 22px;
    font-weight: 700;
    color: #1a1d23;
    line-height: 1.35;
    margin-bottom: 6px;
}
.wizard-subtitle {
    font-size: 13.5px;
    color: #9ca3af;
    margin-bottom: 22px;
}
.wizard-skip {
    font-size: 12.5px;
    color: #9ca3af;
    text-decoration: underline;
    cursor: pointer;
    margin-left: 8px;
}
.wizard-skip:hover { color: #6b7280; }

/* Text / Textarea inputs */
.wizard-text-input {
    width: 100%;
    border: 2px solid #e8eaf0;
    border-radius: 10px;
    padding: 14px 16px;
    font-size: 15px;
    font-family: 'Inter', sans-serif;
    color: #1a1d23;
    transition: border-color .15s;
    resize: vertical;
    outline: none;
}
.wizard-text-input:focus { border-color: #3B82F6; }

/* Card options */
.wizard-cards {
    display: grid;
    gap: 12px;
    margin-bottom: 4px;
}
.wizard-cards.cols-2 { grid-template-columns: 1fr 1fr; }
.wizard-cards.cols-3 { grid-template-columns: 1fr 1fr 1fr; }

.wizard-option-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 18px 12px;
    border: 2px solid #e8eaf0;
    border-radius: 12px;
    cursor: pointer;
    transition: all .15s;
    text-align: center;
    user-select: none;
}
.wizard-option-card:hover { border-color: #93c5fd; background: #f8faff; }
.wizard-option-card.selected { border-color: #3B82F6; background: #eff6ff; }
.wizard-option-card .card-icon {
    font-size: 26px;
    line-height: 1;
}
.wizard-option-card .card-label {
    font-size: 13.5px;
    font-weight: 600;
    color: #1a1d23;
}
.wizard-option-card .card-desc {
    font-size: 11.5px;
    color: #9ca3af;
    line-height: 1.3;
}

/* ── Review step ─────────────────────────────────────────────────────────── */
.review-grid {
    display: grid;
    gap: 10px;
    margin-bottom: 8px;
}
.review-item {
    display: flex;
    gap: 10px;
    padding: 12px 14px;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid #f0f2f7;
}
.review-label {
    font-size: 11.5px;
    font-weight: 700;
    color: #9ca3af;
    min-width: 120px;
    text-transform: uppercase;
    letter-spacing: .04em;
    padding-top: 1px;
}
.review-value {
    font-size: 13.5px;
    color: #1a1d23;
    font-weight: 500;
    flex: 1;
    white-space: pre-wrap;
    word-break: break-word;
}
.review-empty { color: #9ca3af; font-style: italic; }

/* Error */
.wizard-error {
    margin-bottom: 14px;
    padding: 12px 16px;
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 10px;
    font-size: 13px;
    color: #dc2626;
    display: none;
}

/* ── Footer ──────────────────────────────────────────────────────────────── */
.wizard-footer {
    padding: 18px 28px;
    border-top: 1px solid #f0f2f7;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
}

.btn-wizard-next {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 24px;
    background: #0085f3;
    color: #fff;
    border: none;
    border-radius: 100px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}
.btn-wizard-next:hover { background: #0070d1; }
.btn-wizard-next:disabled { background: #93c5fd; cursor: not-allowed; }

.btn-wizard-create {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 28px;
    background: #0085f3;
    color: #fff;
    border: none;
    border-radius: 100px;
    font-size: 14.5px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
}
.btn-wizard-create:hover { background: #0070d1; }
.btn-wizard-create:disabled { opacity: .5; cursor: not-allowed; }

@media (max-width: 580px) {
    .wizard-cards.cols-3 { grid-template-columns: 1fr 1fr; }
    .wizard-question { font-size: 18px; }
    .wizard-body { padding: 22px 18px 18px; }
    .wizard-footer { padding: 14px 18px; }
    .wizard-header { padding: 14px 18px 0; }
}
</style>
@endpush

@section('content')
<div class="page-container">
<div class="wizard-wrap">

    <div class="wizard-card">

        {{-- Progress bar --}}
        <div class="wizard-progress-bar">
            <div class="wizard-progress-fill" id="wProgressFill" style="width:9%"></div>
        </div>

        {{-- Header --}}
        <div class="wizard-header">
            <button class="wizard-back-btn hidden" id="wBackBtn" onclick="wizardPrev()">
                <i class="bi bi-arrow-left"></i> {{ __('ai_agents.wizard_back') }}
            </button>
            <span class="wizard-step-counter" id="wStepCounter"></span>
        </div>

        {{-- Body: steps --}}
        <div class="wizard-body">

            {{-- STEP 1: Nome --}}
            <div class="wizard-step active" data-step="1">
                <div class="wizard-question">{{ __('ai_agents.step1_question') }} 🤖</div>
                <div class="wizard-subtitle">{{ __('ai_agents.step1_subtitle') }}</div>
                <input type="text" class="wizard-text-input" id="f_name"
                       placeholder="{{ __('ai_agents.step1_placeholder') }}"
                       maxlength="100">
            </div>

            {{-- STEP 2: Empresa --}}
            <div class="wizard-step" data-step="2">
                <div class="wizard-question">{{ __('ai_agents.step2_question') }}</div>
                <div class="wizard-subtitle">
                    {{ __('ai_agents.step2_subtitle') }}
                    <span class="wizard-skip" onclick="wizardSkip()">{{ __('ai_agents.wizard_skip') }}</span>
                </div>
                <input type="text" class="wizard-text-input" id="f_company_name"
                       placeholder="{{ __('ai_agents.step2_placeholder') }}"
                       maxlength="150">
            </div>

            {{-- STEP 3: Objetivo --}}
            <div class="wizard-step" data-step="3">
                <div class="wizard-question">{{ __('ai_agents.step3_question') }}</div>
                <div class="wizard-subtitle">{{ __('ai_agents.step3_subtitle') }}</div>
                <div class="wizard-cards cols-3">
                    <div class="wizard-option-card" data-field="objective" data-value="sales" onclick="selectCard(this)">
                        <span class="card-icon">📈</span>
                        <span class="card-label">{{ __('ai_agents.step3_sales') }}</span>
                        <span class="card-desc">{{ __('ai_agents.step3_sales_desc') }}</span>
                    </div>
                    <div class="wizard-option-card" data-field="objective" data-value="support" onclick="selectCard(this)">
                        <span class="card-icon">🤝</span>
                        <span class="card-label">{{ __('ai_agents.step3_support') }}</span>
                        <span class="card-desc">{{ __('ai_agents.step3_support_desc') }}</span>
                    </div>
                    <div class="wizard-option-card" data-field="objective" data-value="general" onclick="selectCard(this)">
                        <span class="card-icon">💬</span>
                        <span class="card-label">{{ __('ai_agents.step3_general') }}</span>
                        <span class="card-desc">{{ __('ai_agents.step3_general_desc') }}</span>
                    </div>
                </div>
            </div>

            {{-- STEP 4: Estilo de comunicação --}}
            <div class="wizard-step" data-step="4">
                <div class="wizard-question">{{ __('ai_agents.step4_question') }}</div>
                <div class="wizard-subtitle">{{ __('ai_agents.step4_subtitle') }}</div>
                <div class="wizard-cards cols-3">
                    <div class="wizard-option-card" data-field="communication_style" data-value="formal" onclick="selectCard(this)">
                        <span class="card-icon">👔</span>
                        <span class="card-label">{{ __('ai_agents.step4_formal') }}</span>
                        <span class="card-desc">{{ __('ai_agents.step4_formal_desc') }}</span>
                    </div>
                    <div class="wizard-option-card" data-field="communication_style" data-value="normal" onclick="selectCard(this)">
                        <span class="card-icon">🙂</span>
                        <span class="card-label">{{ __('ai_agents.step4_normal') }}</span>
                        <span class="card-desc">{{ __('ai_agents.step4_normal_desc') }}</span>
                    </div>
                    <div class="wizard-option-card" data-field="communication_style" data-value="casual" onclick="selectCard(this)">
                        <span class="card-icon">😎</span>
                        <span class="card-label">{{ __('ai_agents.step4_casual') }}</span>
                        <span class="card-desc">{{ __('ai_agents.step4_casual_desc') }}</span>
                    </div>
                </div>
            </div>

            {{-- STEP 5: Idioma --}}
            <div class="wizard-step" data-step="5">
                <div class="wizard-question">{{ __('ai_agents.step5_question') }}</div>
                <div class="wizard-subtitle">{{ __('ai_agents.step5_subtitle') }}</div>
                <div class="wizard-cards cols-3">
                    <div class="wizard-option-card" data-field="language" data-value="pt-BR" onclick="selectCard(this)">
                        <span class="card-icon">🇧🇷</span>
                        <span class="card-label">{{ __('ai_agents.step5_pt') }}</span>
                        <span class="card-desc">pt-BR</span>
                    </div>
                    <div class="wizard-option-card" data-field="language" data-value="en-US" onclick="selectCard(this)">
                        <span class="card-icon">🇺🇸</span>
                        <span class="card-label">{{ __('ai_agents.step5_en') }}</span>
                        <span class="card-desc">en-US</span>
                    </div>
                    <div class="wizard-option-card" data-field="language" data-value="es-ES" onclick="selectCard(this)">
                        <span class="card-icon">🇪🇸</span>
                        <span class="card-label">{{ __('ai_agents.step5_es') }}</span>
                        <span class="card-desc">es-ES</span>
                    </div>
                </div>
            </div>

            {{-- STEP 6: Personalidade --}}
            <div class="wizard-step" data-step="6">
                <div class="wizard-question">{{ __('ai_agents.step6_question') }}</div>
                <div class="wizard-subtitle">
                    {{ __('ai_agents.step6_subtitle') }}
                    <span class="wizard-skip" onclick="wizardSkip()">{{ __('ai_agents.wizard_skip_short') }}</span>
                </div>
                <textarea class="wizard-text-input" id="f_persona_description" rows="5"
                    placeholder="{{ __('ai_agents.step6_placeholder') }}"></textarea>
            </div>

            {{-- STEP 7: Regras de comportamento --}}
            <div class="wizard-step" data-step="7">
                <div class="wizard-question">{{ __('ai_agents.step7_question') }}</div>
                <div class="wizard-subtitle">
                    {{ __('ai_agents.step7_subtitle') }}
                    <span class="wizard-skip" onclick="wizardSkip()">{{ __('ai_agents.wizard_skip_short') }}</span>
                </div>
                <textarea class="wizard-text-input" id="f_behavior" rows="5"
                    placeholder="{{ __('ai_agents.step7_placeholder') }}"></textarea>
            </div>

            {{-- STEP 8: Mensagem de encerramento --}}
            <div class="wizard-step" data-step="8">
                <div class="wizard-question">{{ __('ai_agents.step8_question') }}</div>
                <div class="wizard-subtitle">
                    {{ __('ai_agents.step8_subtitle') }}
                    <span class="wizard-skip" onclick="wizardSkip()">{{ __('ai_agents.wizard_skip_short') }}</span>
                </div>
                <textarea class="wizard-text-input" id="f_on_finish_action" rows="4"
                    placeholder="{{ __('ai_agents.step8_placeholder') }}"></textarea>
            </div>

            {{-- STEP 9: Base de conhecimento --}}
            <div class="wizard-step" data-step="9">
                <div class="wizard-question">{{ __('ai_agents.step9_question') }}</div>
                <div class="wizard-subtitle">
                    {{ __('ai_agents.step9_subtitle') }}
                    <span class="wizard-skip" onclick="wizardSkip()">{{ __('ai_agents.wizard_skip_short') }}</span>
                </div>
                <textarea class="wizard-text-input" id="f_knowledge_base" rows="7"
                    placeholder="{{ __('ai_agents.step9_placeholder') }}"></textarea>
            </div>

            {{-- STEP 10: Canal --}}
            <div class="wizard-step" data-step="10">
                <div class="wizard-question">{{ __('ai_agents.step10_question') }}</div>
                <div class="wizard-subtitle">{{ __('ai_agents.step10_subtitle') }}</div>
                <div class="wizard-cards cols-2">
                    <div class="wizard-option-card" data-field="channel" data-value="whatsapp" onclick="selectCard(this)">
                        <span class="card-icon">📱</span>
                        <span class="card-label">{{ __('ai_agents.step10_whatsapp') }}</span>
                        <span class="card-desc">{{ __('ai_agents.step10_whatsapp_desc') }}</span>
                    </div>
                    <div class="wizard-option-card" data-field="channel" data-value="web_chat" onclick="selectCard(this)">
                        <span class="card-icon">🌐</span>
                        <span class="card-label">{{ __('ai_agents.step10_web_chat') }}</span>
                        <span class="card-desc">{{ __('ai_agents.step10_web_chat_desc') }}</span>
                    </div>
                </div>
            </div>

            {{-- STEP 11: Revisão --}}
            <div class="wizard-step" data-step="11">
                <div class="wizard-question">{{ __('ai_agents.step11_question') }} ✅</div>
                <div class="wizard-subtitle">{{ __('ai_agents.step11_subtitle') }}</div>
                <div class="wizard-error" id="wError"></div>
                <div class="review-grid" id="wReviewGrid">
                    {{-- Preenchido via JS --}}
                </div>
            </div>

        </div>{{-- /wizard-body --}}

        {{-- Footer --}}
        <div class="wizard-footer">
            <button class="btn-wizard-next" id="wNextBtn" onclick="wizardNext()">
                {{ __('ai_agents.wizard_next') }} <i class="bi bi-arrow-right"></i>
            </button>
            <button class="btn-wizard-create" id="wCreateBtn" style="display:none" onclick="wizardSubmit()">
                <i class="bi bi-check-circle"></i> {{ __('ai_agents.wizard_create_agent') }}
            </button>
        </div>

    </div>{{-- /wizard-card --}}
</div>{{-- /wizard-wrap --}}
</div>{{-- /page-container --}}
@endsection

@push('scripts')
<script>
const TOTAL_STEPS = 11;
const STORE_URL   = @json(route('ai.agents.store'));
const CSRF        = document.querySelector('meta[name="csrf-token"]').content;
const AILANG      = @json(__('ai_agents'));

let currentStep = 1;

// State: field values
const state = {
    name: '',
    company_name: '',
    objective: '',
    communication_style: '',
    language: '',
    persona_description: '',
    behavior: '',
    on_finish_action: '',
    knowledge_base: '',
    channel: '',
};

// ── Navigation ─────────────────────────────────────────────────────────────

function updateUI() {
    // Steps
    document.querySelectorAll('.wizard-step').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.step) === currentStep);
    });

    // Progress
    const pct = Math.round((currentStep / TOTAL_STEPS) * 100);
    document.getElementById('wProgressFill').style.width = pct + '%';
    document.getElementById('wStepCounter').textContent =
        AILANG.wizard_step_counter.replace(':current', currentStep).replace(':total', TOTAL_STEPS);

    // Back button
    const backBtn = document.getElementById('wBackBtn');
    backBtn.classList.toggle('hidden', currentStep === 1);

    // Next / Create button
    const nextBtn   = document.getElementById('wNextBtn');
    const createBtn = document.getElementById('wCreateBtn');
    if (currentStep === TOTAL_STEPS) {
        nextBtn.style.display   = 'none';
        createBtn.style.display = '';
        buildReview();
    } else {
        nextBtn.style.display   = '';
        createBtn.style.display = 'none';
    }

    // Focus input on text steps
    const activeStep = document.querySelector('.wizard-step.active');
    const inp = activeStep?.querySelector('.wizard-text-input');
    if (inp) setTimeout(() => inp.focus(), 100);
}

function saveCurrentStep() {
    const step = currentStep;
    if (step === 1)  state.name                = document.getElementById('f_name').value.trim();
    if (step === 2)  state.company_name        = document.getElementById('f_company_name').value.trim();
    if (step === 6)  state.persona_description = document.getElementById('f_persona_description').value.trim();
    if (step === 7)  state.behavior            = document.getElementById('f_behavior').value.trim();
    if (step === 8)  state.on_finish_action    = document.getElementById('f_on_finish_action').value.trim();
    if (step === 9)  state.knowledge_base      = document.getElementById('f_knowledge_base').value.trim();
}

function validateCurrentStep() {
    const step = currentStep;
    if (step === 1 && !document.getElementById('f_name').value.trim()) {
        toastr.warning(AILANG.toast_name_required);
        return false;
    }
    if (step === 3 && !state.objective) {
        toastr.warning(AILANG.toast_objective_required);
        return false;
    }
    if (step === 4 && !state.communication_style) {
        toastr.warning(AILANG.toast_style_required);
        return false;
    }
    if (step === 5 && !state.language) {
        toastr.warning(AILANG.toast_language_required);
        return false;
    }
    if (step === 10 && !state.channel) {
        toastr.warning(AILANG.toast_channel_required);
        return false;
    }
    return true;
}

function wizardNext() {
    saveCurrentStep();
    if (!validateCurrentStep()) return;
    if (currentStep < TOTAL_STEPS) {
        currentStep++;
        updateUI();
    }
}

function wizardPrev() {
    saveCurrentStep();
    if (currentStep > 1) {
        currentStep--;
        updateUI();
    }
}

function wizardSkip() {
    saveCurrentStep();
    if (currentStep < TOTAL_STEPS) {
        currentStep++;
        updateUI();
    }
}

// ── Card selection ─────────────────────────────────────────────────────────

function selectCard(el) {
    const field = el.dataset.field;
    const value = el.dataset.value;
    // Deselect siblings
    el.closest('.wizard-cards').querySelectorAll('.wizard-option-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    state[field] = value;
    // Auto-advance after card selection (small delay for visual feedback)
    setTimeout(wizardNext, 280);
}

// ── Review ─────────────────────────────────────────────────────────────────

const LABELS = {
    name:                 AILANG.review_name,
    company_name:         AILANG.review_company,
    objective:            AILANG.review_objective,
    communication_style:  AILANG.review_style,
    language:             AILANG.review_language,
    persona_description:  AILANG.review_persona,
    behavior:             AILANG.review_behavior,
    on_finish_action:     AILANG.review_finish_action,
    knowledge_base:       AILANG.review_knowledge,
    channel:              AILANG.review_channel,
};

const DISPLAY = {
    objective:           { sales: AILANG.step3_sales + ' 📈', support: AILANG.step3_support + ' 🤝', general: AILANG.step3_general + ' 💬' },
    communication_style: { formal: AILANG.step4_formal + ' 👔', normal: AILANG.step4_normal + ' 🙂', casual: AILANG.step4_casual + ' 😎' },
    language:            { 'pt-BR': '🇧🇷 ' + AILANG.step5_pt, 'en-US': '🇺🇸 ' + AILANG.step5_en, 'es-ES': '🇪🇸 ' + AILANG.step5_es },
    channel:             { whatsapp: '📱 ' + AILANG.step10_whatsapp, web_chat: '🌐 ' + AILANG.step10_web_chat },
};

function buildReview() {
    // Ensure latest text values are saved
    saveCurrentStep();

    const grid = document.getElementById('wReviewGrid');
    grid.innerHTML = '';

    Object.keys(LABELS).forEach(key => {
        let val = state[key];
        if (!val) return;

        const display = DISPLAY[key]?.[val] ?? val;
        const truncated = display.length > 200 ? display.substring(0, 200) + '…' : display;

        grid.insertAdjacentHTML('beforeend', `
            <div class="review-item">
                <div class="review-label">${LABELS[key]}</div>
                <div class="review-value">${escapeHtml(truncated)}</div>
            </div>
        `);
    });

    if (!grid.children.length) {
        grid.innerHTML = '<div style="color:#9ca3af;font-size:13px;">' + AILANG.review_empty + '</div>';
    }
}

// ── Submit ─────────────────────────────────────────────────────────────────

async function wizardSubmit() {
    saveCurrentStep();

    const errEl  = document.getElementById('wError');
    const createBtn = document.getElementById('wCreateBtn');
    errEl.style.display = 'none';
    createBtn.disabled = true;
    createBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + AILANG.wizard_creating;

    const body = {
        name:                 state.name,
        company_name:         state.company_name || null,
        objective:            state.objective || 'general',
        communication_style:  state.communication_style || 'normal',
        language:             state.language || 'pt-BR',
        persona_description:  state.persona_description || null,
        behavior:             state.behavior || null,
        on_finish_action:     state.on_finish_action || null,
        knowledge_base:       state.knowledge_base || null,
        channel:              state.channel || 'whatsapp',
        is_active:            1,
        max_message_length:   500,
        response_delay_seconds: 2,
    };

    try {
        const res  = await fetch(STORE_URL, {
            method:  'POST',
            headers: {
                'Content-Type':  'application/json',
                'Accept':        'application/json',
                'X-CSRF-TOKEN':  CSRF,
            },
            body: JSON.stringify(body),
        });

        const data = await res.json();

        if (res.ok && data.success) {
            toastr.success(AILANG.toast_agent_created);
            setTimeout(() => window.location.href = data.redirect, 800);
            return;
        }

        // Validation errors (422)
        if (res.status === 422 && data.errors) {
            const msgs = Object.values(data.errors).flat().join(' · ');
            errEl.textContent = msgs;
            errEl.style.display = 'block';
        } else {
            errEl.textContent = data.message || AILANG.toast_create_error;
            errEl.style.display = 'block';
        }
    } catch (e) {
        errEl.textContent = AILANG.toast_connection_error_create;
        errEl.style.display = 'block';
    } finally {
        createBtn.disabled = false;
        createBtn.innerHTML = '<i class="bi bi-check-circle"></i> ' + AILANG.wizard_create_agent;
    }
}

// ── Enter key to advance ───────────────────────────────────────────────────

document.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    if (e.target.tagName === 'TEXTAREA') return; // Allow Enter in textarea
    const step = currentStep;
    // Don't auto-advance on card steps (they auto-advance on click)
    if ([3, 4, 5, 10].includes(step)) return;
    e.preventDefault();
    if (step === TOTAL_STEPS) {
        wizardSubmit();
    } else {
        wizardNext();
    }
});

// Init
updateUI();
</script>
@endpush
