@extends('tenant.layouts.app')

@php
    $title    = 'Novo Agente de IA';
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
    background: linear-gradient(90deg, #3B82F6, #6366F1);
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
    background: #3B82F6;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}
.btn-wizard-next:hover { background: #2563EB; }
.btn-wizard-next:disabled { background: #93c5fd; cursor: not-allowed; }

.btn-wizard-create {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 28px;
    background: linear-gradient(135deg, #3B82F6, #6366F1);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 14.5px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .15s;
}
.btn-wizard-create:hover { opacity: .9; }
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
                <i class="bi bi-arrow-left"></i> Voltar
            </button>
            <span class="wizard-step-counter">Passo <span id="wStepNum">1</span> de 10</span>
        </div>

        {{-- Body: steps --}}
        <div class="wizard-body">

            {{-- STEP 1: Nome --}}
            <div class="wizard-step active" data-step="1">
                <div class="wizard-question">Como chamar seu agente? 🤖</div>
                <div class="wizard-subtitle">Dê um nome que represente a identidade do agente.</div>
                <input type="text" class="wizard-text-input" id="f_name"
                       placeholder="Ex: Ana, Victor, Bot de Vendas"
                       maxlength="100">
            </div>

            {{-- STEP 2: Empresa --}}
            <div class="wizard-step" data-step="2">
                <div class="wizard-question">Qual empresa vai usar esse agente?</div>
                <div class="wizard-subtitle">
                    Opcional — usado para o agente se apresentar corretamente.
                    <span class="wizard-skip" onclick="wizardSkip()">Pular este passo</span>
                </div>
                <input type="text" class="wizard-text-input" id="f_company_name"
                       placeholder="Ex: Loja do João, Clínica Bem-Estar"
                       maxlength="150">
            </div>

            {{-- STEP 3: Objetivo --}}
            <div class="wizard-step" data-step="3">
                <div class="wizard-question">Qual o objetivo principal?</div>
                <div class="wizard-subtitle">Define o foco das respostas do agente.</div>
                <div class="wizard-cards cols-3">
                    <div class="wizard-option-card" data-field="objective" data-value="sales" onclick="selectCard(this)">
                        <span class="card-icon">📈</span>
                        <span class="card-label">Vendas</span>
                        <span class="card-desc">Captura leads e conduz negociações</span>
                    </div>
                    <div class="wizard-option-card" data-field="objective" data-value="support" onclick="selectCard(this)">
                        <span class="card-icon">🤝</span>
                        <span class="card-label">Suporte</span>
                        <span class="card-desc">Resolve dúvidas e problemas</span>
                    </div>
                    <div class="wizard-option-card" data-field="objective" data-value="general" onclick="selectCard(this)">
                        <span class="card-icon">💬</span>
                        <span class="card-label">Geral</span>
                        <span class="card-desc">Atendimento sem foco específico</span>
                    </div>
                </div>
            </div>

            {{-- STEP 4: Estilo de comunicação --}}
            <div class="wizard-step" data-step="4">
                <div class="wizard-question">Como ele deve se comunicar?</div>
                <div class="wizard-subtitle">Define o tom das mensagens do agente.</div>
                <div class="wizard-cards cols-3">
                    <div class="wizard-option-card" data-field="communication_style" data-value="formal" onclick="selectCard(this)">
                        <span class="card-icon">👔</span>
                        <span class="card-label">Formal</span>
                        <span class="card-desc">Profissional e estruturado</span>
                    </div>
                    <div class="wizard-option-card" data-field="communication_style" data-value="normal" onclick="selectCard(this)">
                        <span class="card-icon">🙂</span>
                        <span class="card-label">Normal</span>
                        <span class="card-desc">Natural e cordial</span>
                    </div>
                    <div class="wizard-option-card" data-field="communication_style" data-value="casual" onclick="selectCard(this)">
                        <span class="card-icon">😎</span>
                        <span class="card-label">Informal</span>
                        <span class="card-desc">Descontraído e amigável</span>
                    </div>
                </div>
            </div>

            {{-- STEP 5: Idioma --}}
            <div class="wizard-step" data-step="5">
                <div class="wizard-question">Em qual idioma?</div>
                <div class="wizard-subtitle">Idioma padrão das respostas do agente.</div>
                <div class="wizard-cards cols-3">
                    <div class="wizard-option-card" data-field="language" data-value="pt-BR" onclick="selectCard(this)">
                        <span class="card-icon">🇧🇷</span>
                        <span class="card-label">Português</span>
                        <span class="card-desc">pt-BR</span>
                    </div>
                    <div class="wizard-option-card" data-field="language" data-value="en-US" onclick="selectCard(this)">
                        <span class="card-icon">🇺🇸</span>
                        <span class="card-label">Inglês</span>
                        <span class="card-desc">en-US</span>
                    </div>
                    <div class="wizard-option-card" data-field="language" data-value="es-ES" onclick="selectCard(this)">
                        <span class="card-icon">🇪🇸</span>
                        <span class="card-label">Espanhol</span>
                        <span class="card-desc">es-ES</span>
                    </div>
                </div>
            </div>

            {{-- STEP 6: Personalidade --}}
            <div class="wizard-step" data-step="6">
                <div class="wizard-question">Descreva a personalidade do agente</div>
                <div class="wizard-subtitle">
                    Como o agente deve se apresentar e se comportar?
                    <span class="wizard-skip" onclick="wizardSkip()">Pular</span>
                </div>
                <textarea class="wizard-text-input" id="f_persona_description" rows="5"
                    placeholder="Ex: Você é Ana, uma assistente virtual da Loja do João. Você é simpática, paciente e sempre focada em ajudar o cliente a encontrar o produto ideal..."></textarea>
            </div>

            {{-- STEP 7: Regras de comportamento --}}
            <div class="wizard-step" data-step="7">
                <div class="wizard-question">Regras de comportamento</div>
                <div class="wizard-subtitle">
                    O que o agente DEVE e NÃO DEVE fazer?
                    <span class="wizard-skip" onclick="wizardSkip()">Pular</span>
                </div>
                <textarea class="wizard-text-input" id="f_behavior" rows="5"
                    placeholder="Ex: Sempre cumprimente o cliente pelo nome. Nunca forneça preços sem confirmar disponibilidade. Encaminhe reclamações graves para um humano..."></textarea>
            </div>

            {{-- STEP 8: Mensagem de encerramento --}}
            <div class="wizard-step" data-step="8">
                <div class="wizard-question">Mensagem ao encerrar atendimento</div>
                <div class="wizard-subtitle">
                    O que o agente deve dizer ao finalizar a conversa?
                    <span class="wizard-skip" onclick="wizardSkip()">Pular</span>
                </div>
                <textarea class="wizard-text-input" id="f_on_finish_action" rows="4"
                    placeholder="Ex: Obrigado pelo contato! Se precisar de mais alguma coisa, é só chamar. Tenha um ótimo dia! 😊"></textarea>
            </div>

            {{-- STEP 9: Base de conhecimento --}}
            <div class="wizard-step" data-step="9">
                <div class="wizard-question">Base de conhecimento</div>
                <div class="wizard-subtitle">
                    Informações sobre sua empresa, produtos, preços, políticas…
                    <span class="wizard-skip" onclick="wizardSkip()">Pular</span>
                </div>
                <textarea class="wizard-text-input" id="f_knowledge_base" rows="7"
                    placeholder="Produto A: R$ 99,90, disponível em azul e vermelho.&#10;Produto B: R$ 149,00, prazo de entrega 5 dias.&#10;Política de troca: 7 dias após a compra..."></textarea>
            </div>

            {{-- STEP 10: Canal --}}
            <div class="wizard-step" data-step="10">
                <div class="wizard-question">Canal de atendimento</div>
                <div class="wizard-subtitle">Onde este agente vai operar?</div>
                <div class="wizard-cards cols-2">
                    <div class="wizard-option-card" data-field="channel" data-value="whatsapp" onclick="selectCard(this)">
                        <span class="card-icon">📱</span>
                        <span class="card-label">WhatsApp</span>
                        <span class="card-desc">Integração com WAHA / WhatsApp Web</span>
                    </div>
                    <div class="wizard-option-card" data-field="channel" data-value="web_chat" onclick="selectCard(this)">
                        <span class="card-icon">🌐</span>
                        <span class="card-label">Web Chat</span>
                        <span class="card-desc">Widget no site da empresa</span>
                    </div>
                </div>
            </div>

            {{-- STEP 11: Revisão --}}
            <div class="wizard-step" data-step="11">
                <div class="wizard-question">Tudo certo? Revise antes de criar ✅</div>
                <div class="wizard-subtitle">Confirme as informações do agente.</div>
                <div class="wizard-error" id="wError"></div>
                <div class="review-grid" id="wReviewGrid">
                    {{-- Preenchido via JS --}}
                </div>
            </div>

        </div>{{-- /wizard-body --}}

        {{-- Footer --}}
        <div class="wizard-footer">
            <button class="btn-wizard-next" id="wNextBtn" onclick="wizardNext()">
                Próximo <i class="bi bi-arrow-right"></i>
            </button>
            <button class="btn-wizard-create" id="wCreateBtn" style="display:none" onclick="wizardSubmit()">
                <i class="bi bi-check-circle"></i> Criar Agente
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
    document.getElementById('wStepNum').textContent = currentStep;

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
        toastr.warning('Por favor, dê um nome ao agente.');
        return false;
    }
    if (step === 3 && !state.objective) {
        toastr.warning('Selecione o objetivo do agente.');
        return false;
    }
    if (step === 4 && !state.communication_style) {
        toastr.warning('Selecione o estilo de comunicação.');
        return false;
    }
    if (step === 5 && !state.language) {
        toastr.warning('Selecione o idioma.');
        return false;
    }
    if (step === 10 && !state.channel) {
        toastr.warning('Selecione o canal de atendimento.');
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
    name:                 'Nome',
    company_name:         'Empresa',
    objective:            'Objetivo',
    communication_style:  'Estilo',
    language:             'Idioma',
    persona_description:  'Personalidade',
    behavior:             'Regras',
    on_finish_action:     'Encerramento',
    knowledge_base:       'Base de conhecimento',
    channel:              'Canal',
};

const DISPLAY = {
    objective:           { sales: 'Vendas 📈', support: 'Suporte 🤝', general: 'Geral 💬' },
    communication_style: { formal: 'Formal 👔', normal: 'Normal 🙂', casual: 'Informal 😎' },
    language:            { 'pt-BR': '🇧🇷 Português', 'en-US': '🇺🇸 Inglês', 'es-ES': '🇪🇸 Espanhol' },
    channel:             { whatsapp: '📱 WhatsApp', web_chat: '🌐 Web Chat' },
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
        grid.innerHTML = '<div style="color:#9ca3af;font-size:13px;">Nenhum campo preenchido.</div>';
    }
}

// ── Submit ─────────────────────────────────────────────────────────────────

async function wizardSubmit() {
    saveCurrentStep();

    const errEl  = document.getElementById('wError');
    const createBtn = document.getElementById('wCreateBtn');
    errEl.style.display = 'none';
    createBtn.disabled = true;
    createBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Criando…';

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
            toastr.success('Agente criado! Redirecionando para edição…');
            setTimeout(() => window.location.href = data.redirect, 800);
            return;
        }

        // Validation errors (422)
        if (res.status === 422 && data.errors) {
            const msgs = Object.values(data.errors).flat().join(' · ');
            errEl.textContent = msgs;
            errEl.style.display = 'block';
        } else {
            errEl.textContent = data.message || 'Erro ao criar o agente. Tente novamente.';
            errEl.style.display = 'block';
        }
    } catch (e) {
        errEl.textContent = 'Erro de conexão. Verifique sua internet e tente novamente.';
        errEl.style.display = 'block';
    } finally {
        createBtn.disabled = false;
        createBtn.innerHTML = '<i class="bi bi-check-circle"></i> Criar Agente';
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
