@extends('tenant.layouts.app')

@php
    $title    = 'Novo Agente de IA';
    $pageIcon = 'robot';
@endphp

@push('styles')
<style>
/* ── Wizard container ─────────────────────────────────────────────────────── */
.wizard-wrap { max-width: 680px; margin: 0 auto; padding: 28px 0 40px; }
.wizard-card {
    background: #fff; border: 1px solid #e8eaf0; border-radius: 16px;
    overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.06);
}

/* ── Progress ─────────────────────────────────────────────────────────────── */
.wizard-progress-bar { height: 4px; background: #f0f2f7; }
.wizard-progress-fill {
    height: 4px; background: #0085f3; transition: width .4s ease;
    border-radius: 0 4px 4px 0;
}

.wizard-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 28px 0;
}
.wizard-step-counter { font-size: 12px; font-weight: 600; color: #9ca3af; letter-spacing: .04em; }
.wizard-back-btn {
    display: flex; align-items: center; gap: 5px;
    background: none; border: none; color: #9ca3af;
    font-size: 13px; cursor: pointer; padding: 4px 8px;
    border-radius: 7px; transition: all .15s;
}
.wizard-back-btn:hover { background: #f4f6fb; color: #0085f3; }
.wizard-back-btn.hidden { visibility: hidden; }

/* ── Steps ────────────────────────────────────────────────────────────────── */
.wizard-body { padding: 28px 28px 24px; min-height: 260px; }
.wizard-step { display: none; }
.wizard-step.active { display: block; }
.wizard-question { font-size: 22px; font-weight: 700; color: #1a1d23; line-height: 1.35; margin-bottom: 6px; }
.wizard-subtitle { font-size: 13.5px; color: #9ca3af; margin-bottom: 22px; }
.wizard-skip { font-size: 12.5px; color: #9ca3af; text-decoration: underline; cursor: pointer; margin-left: 8px; }
.wizard-skip:hover { color: #6b7280; }

/* Text / Textarea inputs */
.wizard-text-input {
    width: 100%; border: 2px solid #e8eaf0; border-radius: 10px;
    padding: 14px 16px; font-size: 15px; font-family: 'Inter', sans-serif;
    color: #1a1d23; transition: border-color .15s; resize: vertical; outline: none;
}
.wizard-text-input:focus { border-color: #0085f3; }

/* Card options */
.wizard-cards { display: grid; gap: 12px; margin-bottom: 4px; }
.wizard-cards.cols-2 { grid-template-columns: 1fr 1fr; }
.wizard-cards.cols-3 { grid-template-columns: 1fr 1fr 1fr; }

.wizard-option-card {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    padding: 18px 12px; border: 2px solid #e8eaf0; border-radius: 12px;
    cursor: pointer; transition: all .15s; text-align: center; user-select: none;
}
.wizard-option-card:hover { border-color: #93c5fd; background: #f8faff; }
.wizard-option-card.selected { border-color: #0085f3; background: #eff6ff; }
.wizard-option-card .card-icon { font-size: 26px; line-height: 1; }
.wizard-option-card .card-label { font-size: 13.5px; font-weight: 600; color: #1a1d23; }
.wizard-option-card .card-desc { font-size: 11.5px; color: #9ca3af; line-height: 1.3; }

/* ── Media upload step ────────────────────────────────────────────────────── */
.media-dropzone {
    border: 2px dashed #d1d5db; border-radius: 12px; padding: 28px 16px;
    text-align: center; cursor: pointer; transition: all .15s; margin-bottom: 16px;
}
.media-dropzone:hover, .media-dropzone.dragover {
    border-color: #0085f3; background: #f8faff;
}
.media-dropzone-icon { font-size: 28px; color: #9ca3af; margin-bottom: 6px; }
.media-dropzone-text { font-size: 13px; color: #6b7280; }
.media-dropzone-hint { font-size: 11px; color: #9ca3af; margin-top: 4px; }

.media-list { display: flex; flex-direction: column; gap: 8px; max-height: 240px; overflow-y: auto; }
.media-item {
    display: flex; align-items: center; gap: 10px; padding: 10px 12px;
    background: #f8fafc; border: 1px solid #e8eaf0; border-radius: 10px;
}
.media-item-icon { font-size: 18px; flex-shrink: 0; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.media-item-icon.img { background: #f3e8ff; color: #9333ea; }
.media-item-icon.doc { background: #fee2e2; color: #dc2626; }
.media-item-icon.file { background: #dbeafe; color: #2563eb; }
.media-item-body { flex: 1; min-width: 0; }
.media-item-name { font-size: 12.5px; font-weight: 600; color: #1a1d23; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.media-item-desc { font-size: 11px; color: #6b7280; }
.media-item-del {
    background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 14px; padding: 4px;
    border-radius: 6px; transition: all .15s;
}
.media-item-del:hover { color: #dc2626; background: #fee2e2; }

.media-desc-row { display: flex; gap: 8px; margin-bottom: 12px; }
.media-desc-input {
    flex: 1; border: 1.5px solid #e8eaf0; border-radius: 8px;
    padding: 8px 12px; font-size: 13px; color: #1a1d23; outline: none;
    transition: border-color .15s;
}
.media-desc-input:focus { border-color: #0085f3; }
.media-upload-btn {
    padding: 8px 16px; background: #0085f3; color: #fff; border: none;
    border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
    transition: background .15s; white-space: nowrap;
}
.media-upload-btn:hover { background: #0070d1; }
.media-upload-btn:disabled { opacity: .5; cursor: not-allowed; }

/* ── Review step ──────────────────────────────────────────────────────────── */
.review-grid { display: grid; gap: 10px; margin-bottom: 8px; }
.review-item {
    display: flex; gap: 10px; padding: 12px 14px;
    background: #f8fafc; border-radius: 10px; border: 1px solid #f0f2f7;
}
.review-label {
    font-size: 11.5px; font-weight: 700; color: #9ca3af;
    min-width: 120px; text-transform: uppercase; letter-spacing: .04em; padding-top: 1px;
}
.review-value {
    font-size: 13.5px; color: #1a1d23; font-weight: 500;
    flex: 1; white-space: pre-wrap; word-break: break-word;
}

/* Error */
.wizard-error {
    margin-bottom: 14px; padding: 12px 16px;
    background: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px;
    font-size: 13px; color: #dc2626; display: none;
}

/* ── Footer ───────────────────────────────────────────────────────────────── */
.wizard-footer {
    padding: 18px 28px; border-top: 1px solid #f0f2f7;
    display: flex; align-items: center; justify-content: flex-end; gap: 10px;
}
.btn-wizard-next {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 24px; background: #0085f3; color: #fff;
    border: none; border-radius: 10px; font-size: 14px; font-weight: 600;
    cursor: pointer; transition: background .15s;
}
.btn-wizard-next:hover { background: #0070d1; }
.btn-wizard-next:disabled { background: #93c5fd; cursor: not-allowed; }
.btn-wizard-create {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 28px; background: #0085f3; color: #fff;
    border: none; border-radius: 10px; font-size: 14.5px; font-weight: 700;
    cursor: pointer; transition: background .15s;
}
.btn-wizard-create:hover { background: #0070d1; }
.btn-wizard-create:disabled { opacity: .5; cursor: not-allowed; }
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin .6s linear infinite; display: inline-block; }

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
            <div class="wizard-progress-fill" id="wProgressFill" style="width:8%"></div>
        </div>

        {{-- Header --}}
        <div class="wizard-header">
            <button class="wizard-back-btn hidden" id="wBackBtn" onclick="wizardPrev()">
                <i class="bi bi-arrow-left"></i> Voltar
            </button>
            <span class="wizard-step-counter">Passo <span id="wStepNum">1</span> de <span id="wStepTotal">12</span></span>
        </div>

        {{-- Body --}}
        <div class="wizard-body">

            {{-- STEP 1: name --}}
            <div class="wizard-step active" data-step="name">
                <div class="wizard-question">Como quer chamar seu agente?</div>
                <div class="wizard-subtitle">Um nome que identifique o agente (ex: Ana Vendas, Suporte Bot).</div>
                <input type="text" class="wizard-text-input" id="f_name"
                       placeholder="Ex: Ana, Assistente Comercial..." maxlength="100">
            </div>

            {{-- STEP 2: company --}}
            <div class="wizard-step" data-step="company">
                <div class="wizard-question">Qual a empresa? <span class="wizard-skip" onclick="wizardSkip()">Pular</span></div>
                <div class="wizard-subtitle">O agente vai se apresentar representando esta empresa.</div>
                <input type="text" class="wizard-text-input" id="f_company"
                       placeholder="Ex: Loja do João, Clínica Saúde Total..." maxlength="150">
            </div>

            {{-- STEP 3: objective --}}
            <div class="wizard-step" data-step="objective">
                <div class="wizard-question">Qual o objetivo do agente?</div>
                <div class="wizard-subtitle">Isso define o comportamento base das respostas.</div>
                <div class="wizard-cards cols-3">
                    <div class="wizard-option-card" data-field="objective" data-value="sales" onclick="selectCard(this)">
                        <span class="card-icon"><i class="bi bi-graph-up" style="font-size:26px;color:#0085f3"></i></span>
                        <span class="card-label">Vendas</span>
                        <span class="card-desc">Qualificar leads e fechar negócios</span>
                    </div>
                    <div class="wizard-option-card" data-field="objective" data-value="support" onclick="selectCard(this)">
                        <span class="card-icon"><i class="bi bi-headset" style="font-size:26px;color:#0085f3"></i></span>
                        <span class="card-label">Suporte</span>
                        <span class="card-desc">Atender dúvidas e resolver problemas</span>
                    </div>
                    <div class="wizard-option-card" data-field="objective" data-value="general" onclick="selectCard(this)">
                        <span class="card-icon"><i class="bi bi-chat-dots" style="font-size:26px;color:#0085f3"></i></span>
                        <span class="card-label">Geral</span>
                        <span class="card-desc">Atendimento versátil e informativo</span>
                    </div>
                </div>
            </div>

            {{-- STEP 4: style --}}
            <div class="wizard-step" data-step="style">
                <div class="wizard-question">Estilo de comunicação</div>
                <div class="wizard-subtitle">Como o agente deve se comunicar com os contatos.</div>
                <div class="wizard-cards cols-3">
                    <div class="wizard-option-card" data-field="communication_style" data-value="formal" onclick="selectCard(this)">
                        <span class="card-icon"><i class="bi bi-briefcase" style="font-size:26px;color:#0085f3"></i></span>
                        <span class="card-label">Formal</span>
                        <span class="card-desc">Profissional e objetivo</span>
                    </div>
                    <div class="wizard-option-card" data-field="communication_style" data-value="normal" onclick="selectCard(this)">
                        <span class="card-icon"><i class="bi bi-people" style="font-size:26px;color:#0085f3"></i></span>
                        <span class="card-label">Normal</span>
                        <span class="card-desc">Natural e cordial</span>
                    </div>
                    <div class="wizard-option-card" data-field="communication_style" data-value="casual" onclick="selectCard(this)">
                        <span class="card-icon"><i class="bi bi-emoji-smile" style="font-size:26px;color:#0085f3"></i></span>
                        <span class="card-label">Casual</span>
                        <span class="card-desc">Descontraído e amigável</span>
                    </div>
                </div>
            </div>

            {{-- STEP 5: language --}}
            <div class="wizard-step" data-step="language">
                <div class="wizard-question">Idioma de resposta</div>
                <div class="wizard-subtitle">Em qual idioma o agente deve responder.</div>
                <div class="wizard-cards cols-3">
                    <div class="wizard-option-card" data-field="language" data-value="pt-BR" onclick="selectCard(this)">
                        <span class="card-icon"><i class="bi bi-translate" style="font-size:26px;color:#0085f3"></i></span>
                        <span class="card-label">Português</span>
                    </div>
                    <div class="wizard-option-card" data-field="language" data-value="en-US" onclick="selectCard(this)">
                        <span class="card-icon"><i class="bi bi-translate" style="font-size:26px;color:#0085f3"></i></span>
                        <span class="card-label">English</span>
                    </div>
                    <div class="wizard-option-card" data-field="language" data-value="es-ES" onclick="selectCard(this)">
                        <span class="card-icon"><i class="bi bi-translate" style="font-size:26px;color:#0085f3"></i></span>
                        <span class="card-label">Español</span>
                    </div>
                </div>
            </div>

            {{-- STEP 6: persona --}}
            <div class="wizard-step" data-step="persona">
                <div class="wizard-question">Persona do agente <span class="wizard-skip" onclick="wizardSkip()">Pular</span></div>
                <div class="wizard-subtitle">Descreva a personalidade e perfil do atendente virtual.</div>
                <textarea class="wizard-text-input" id="f_persona" rows="5"
                          placeholder="Ex: Sou a Ana, consultora de vendas com 5 anos de experiência no mercado imobiliário. Sou simpática, atenciosa e sempre busco entender as necessidades do cliente..." maxlength="2000"></textarea>
            </div>

            {{-- STEP 7: behavior --}}
            <div class="wizard-step" data-step="behavior">
                <div class="wizard-question">Regras de comportamento <span class="wizard-skip" onclick="wizardSkip()">Pular</span></div>
                <div class="wizard-subtitle">Defina o que o agente DEVE e NÃO DEVE fazer.</div>
                <textarea class="wizard-text-input" id="f_behavior" rows="5"
                          placeholder="Ex: DEVE sempre perguntar o nome do cliente. NÃO DEVE dar descontos sem aprovação. DEVE transferir para humano quando o cliente ficar irritado..." maxlength="2000"></textarea>
            </div>

            {{-- STEP 8: finish action --}}
            <div class="wizard-step" data-step="finish">
                <div class="wizard-question">Mensagem de finalização <span class="wizard-skip" onclick="wizardSkip()">Pular</span></div>
                <div class="wizard-subtitle">O que o agente deve dizer ao encerrar o atendimento.</div>
                <textarea class="wizard-text-input" id="f_finish" rows="3"
                          placeholder="Ex: Obrigado pelo contato! Se tiver mais dúvidas, é só chamar. 😊" maxlength="1000"></textarea>
            </div>

            {{-- STEP 9: knowledge --}}
            <div class="wizard-step" data-step="knowledge">
                <div class="wizard-question">Base de conhecimento <span class="wizard-skip" onclick="wizardSkip()">Pular</span></div>
                <div class="wizard-subtitle">Cole aqui informações sobre sua empresa, produtos, preços, FAQ, etc.</div>
                <textarea class="wizard-text-input" id="f_knowledge" rows="6"
                          placeholder="Ex: Nossa empresa oferece planos a partir de R$ 49/mês. Horário de funcionamento: segunda a sexta, 9h-18h. Endereço: Rua..." maxlength="10000"></textarea>
            </div>

            {{-- STEP 10: media --}}
            <div class="wizard-step" data-step="media">
                <div class="wizard-question">Mídias para envio <span class="wizard-skip" onclick="wizardSkip()">Pular</span></div>
                <div class="wizard-subtitle">Envie imagens, PDFs e catálogos que o agente pode enviar aos contatos durante a conversa.</div>

                <div class="media-dropzone" id="mediaDropzone"
                     onclick="document.getElementById('mediaFileInput').click()"
                     ondragover="event.preventDefault();this.classList.add('dragover')"
                     ondragleave="this.classList.remove('dragover')"
                     ondrop="handleMediaDrop(event)">
                    <div class="media-dropzone-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                    <div class="media-dropzone-text">Clique ou arraste arquivos aqui</div>
                    <div class="media-dropzone-hint">PNG, JPG, PDF, DOC — máx. 20 MB</div>
                </div>
                <input type="file" id="mediaFileInput" style="display:none"
                       accept=".png,.jpg,.jpeg,.webp,.gif,.pdf,.doc,.docx"
                       onchange="prepareMediaFile(this.files[0])">

                <div id="mediaDescRow" class="media-desc-row" style="display:none">
                    <input type="text" class="media-desc-input" id="mediaDescInput"
                           placeholder="Descreva quando enviar (ex: catálogo de produtos, tabela de preços)" maxlength="500">
                    <button class="media-upload-btn" id="mediaUploadBtn" onclick="uploadMediaFile()">Enviar</button>
                </div>

                <div class="media-list" id="mediaList"></div>
            </div>

            {{-- STEP 11: channel --}}
            <div class="wizard-step" data-step="channel">
                <div class="wizard-question">Canal de atendimento</div>
                <div class="wizard-subtitle">Onde o agente vai atuar.</div>
                <div class="wizard-cards cols-2">
                    <div class="wizard-option-card" data-field="channel" data-value="whatsapp" onclick="selectCard(this)">
                        <span class="card-icon"><i class="bi bi-whatsapp" style="color:#25d366;font-size:28px"></i></span>
                        <span class="card-label">WhatsApp</span>
                        <span class="card-desc">Atendimento via WhatsApp</span>
                    </div>
                    <div class="wizard-option-card" data-field="channel" data-value="web_chat" onclick="selectCard(this)">
                        <span class="card-icon"><i class="bi bi-chat-dots" style="color:#0085f3;font-size:28px"></i></span>
                        <span class="card-label">Web Chat</span>
                        <span class="card-desc">Widget de chat no site</span>
                    </div>
                </div>
            </div>

            {{-- STEP 12: review --}}
            <div class="wizard-step" data-step="review">
                <div class="wizard-question">Tudo certo? Revise antes de criar</div>
                <div class="wizard-subtitle">Confirme as informações do seu agente.</div>
                <div class="wizard-error" id="wError"></div>
                <div class="review-grid" id="wReviewGrid"></div>
            </div>

        </div>

        {{-- Footer --}}
        <div class="wizard-footer">
            <button class="btn-wizard-next" id="wNextBtn" onclick="wizardNext()">
                Próximo <i class="bi bi-arrow-right"></i>
            </button>
            <button class="btn-wizard-create" id="wCreateBtn" style="display:none" onclick="wizardSubmit()">
                <i class="bi bi-check-circle"></i> Criar Agente
            </button>
        </div>

    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
// ── State ────────────────────────────────────────────────────────────────────
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
    agent_id: null,
    media_files: [],
};

const STEPS = ['name','company','objective','style','language','persona','behavior','finish','knowledge','media','channel','review'];
let currentIdx = 0;

// ── Navigation ───────────────────────────────────────────────────────────────

function currentStepName() { return STEPS[currentIdx]; }

function updateUI() {
    const stepName = currentStepName();
    document.querySelectorAll('.wizard-step').forEach(el => {
        el.classList.toggle('active', el.dataset.step === stepName);
    });

    const pct = Math.round(((currentIdx + 1) / STEPS.length) * 100);
    document.getElementById('wProgressFill').style.width = pct + '%';
    document.getElementById('wStepNum').textContent = currentIdx + 1;
    document.getElementById('wStepTotal').textContent = STEPS.length;
    document.getElementById('wBackBtn').classList.toggle('hidden', currentIdx === 0);

    const isLast = currentIdx === STEPS.length - 1;
    document.getElementById('wNextBtn').style.display = isLast ? 'none' : '';
    document.getElementById('wCreateBtn').style.display = isLast ? '' : 'none';
    if (isLast) buildReview();

    // Focus input on text steps
    const activeStep = document.querySelector('.wizard-step.active');
    const inp = activeStep?.querySelector('input[type="text"]:not(.media-desc-input), .wizard-text-input');
    if (inp && !['objective','style','language','channel','media','review'].includes(stepName)) {
        setTimeout(() => inp.focus(), 100);
    }
}

function saveCurrentStep() {
    const step = currentStepName();
    if (step === 'name')      state.name = document.getElementById('f_name').value.trim();
    if (step === 'company')   state.company_name = document.getElementById('f_company').value.trim();
    if (step === 'persona')   state.persona_description = document.getElementById('f_persona').value.trim();
    if (step === 'behavior')  state.behavior = document.getElementById('f_behavior').value.trim();
    if (step === 'finish')    state.on_finish_action = document.getElementById('f_finish').value.trim();
    if (step === 'knowledge') state.knowledge_base = document.getElementById('f_knowledge').value.trim();
}

function validateCurrentStep() {
    const step = currentStepName();
    if (step === 'name' && !document.getElementById('f_name').value.trim()) {
        toastr.warning('Dê um nome ao seu agente.');
        return false;
    }
    if (step === 'objective' && !state.objective) {
        toastr.warning('Selecione o objetivo do agente.');
        return false;
    }
    if (step === 'style' && !state.communication_style) {
        toastr.warning('Selecione o estilo de comunicação.');
        return false;
    }
    if (step === 'language' && !state.language) {
        toastr.warning('Selecione o idioma.');
        return false;
    }
    if (step === 'channel' && !state.channel) {
        toastr.warning('Selecione o canal de atendimento.');
        return false;
    }
    return true;
}

function wizardNext() {
    saveCurrentStep();
    if (!validateCurrentStep()) return;

    // Ensure agent exists before media step
    if (STEPS[currentIdx + 1] === 'media' && !state.agent_id) {
        createAgentFirst(() => {
            currentIdx++;
            updateUI();
        });
        return;
    }

    if (currentIdx < STEPS.length - 1) {
        currentIdx++;
        updateUI();
    }
}

function wizardPrev() {
    saveCurrentStep();
    if (currentIdx > 0) {
        currentIdx--;
        updateUI();
    }
}

function wizardSkip() {
    saveCurrentStep();
    // If skipping media step, still need agent created for later
    if (currentIdx < STEPS.length - 1) {
        currentIdx++;
        updateUI();
    }
}

// ── Card selection ───────────────────────────────────────────────────────────

function selectCard(el) {
    const field = el.dataset.field;
    const value = el.dataset.value;
    el.closest('.wizard-cards').querySelectorAll('.wizard-option-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    state[field] = value;
    setTimeout(wizardNext, 280);
}

// ── Create agent (two-phase for media upload) ────────────────────────────────

function createAgentFirst(callback) {
    saveCurrentStep();
    const btn = document.getElementById('wNextBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Preparando...';

    const body = {
        name: state.name,
        objective: state.objective,
        communication_style: state.communication_style,
        language: state.language,
        channel: state.channel || 'whatsapp',
        is_active: false,
    };
    if (state.company_name) body.company_name = state.company_name;
    if (state.persona_description) body.persona_description = state.persona_description;
    if (state.behavior) body.behavior = state.behavior;
    if (state.on_finish_action) body.on_finish_action = state.on_finish_action;
    if (state.knowledge_base) body.knowledge_base = state.knowledge_base;

    fetch('{{ route("ai.agents.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        btn.disabled = false;
        btn.innerHTML = 'Próximo <i class="bi bi-arrow-right"></i>';
        if (ok && data.success) {
            // Extract agent ID from redirect URL
            const m = data.redirect.match(/agentes\/(\d+)\//);
            if (m) state.agent_id = parseInt(m[1]);
            if (callback) callback();
        } else {
            const msg = data.message || Object.values(data.errors || {}).flat().join(', ') || 'Erro ao preparar agente.';
            toastr.error(msg);
        }
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = 'Próximo <i class="bi bi-arrow-right"></i>';
        toastr.error('Erro de conexão.');
    });
}

// ── Media upload ─────────────────────────────────────────────────────────────

let _pendingFile = null;

function handleMediaDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
    if (e.dataTransfer.files.length) prepareMediaFile(e.dataTransfer.files[0]);
}

function prepareMediaFile(file) {
    if (!file) return;
    if (file.size > 20 * 1024 * 1024) {
        toastr.error('Arquivo muito grande (máx. 20 MB).');
        return;
    }
    _pendingFile = file;
    document.getElementById('mediaDescRow').style.display = 'flex';
    document.getElementById('mediaDescInput').value = '';
    document.getElementById('mediaDescInput').placeholder = `Descreva "${file.name}" (ex: catálogo de produtos)`;
    document.getElementById('mediaDescInput').focus();
    document.getElementById('mediaFileInput').value = '';
}

function uploadMediaFile() {
    if (!_pendingFile) return;
    const desc = document.getElementById('mediaDescInput').value.trim();
    if (!desc) {
        toastr.warning('Descreva quando o agente deve enviar este arquivo.');
        return;
    }

    if (!state.agent_id) {
        // Agent not created yet — create first then retry
        createAgentFirst(() => uploadMediaFile());
        return;
    }

    const btn = document.getElementById('mediaUploadBtn');
    btn.disabled = true;
    btn.textContent = 'Enviando...';

    const fd = new FormData();
    fd.append('file', _pendingFile);
    fd.append('description', desc);
    fd.append('_token', '{{ csrf_token() }}');

    const url = '{{ url("/ia/agentes") }}/' + state.agent_id + '/media';

    fetch(url, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: fd,
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        btn.disabled = false;
        btn.textContent = 'Enviar';
        if (ok && data.id) {
            state.media_files.push({
                id: data.id,
                name: data.original_name,
                description: data.description,
                mime_type: data.mime_type,
            });
            renderMediaList();
            _pendingFile = null;
            document.getElementById('mediaDescRow').style.display = 'none';
            toastr.success('Arquivo enviado!');
        } else {
            const msg = data.message || Object.values(data.errors || {}).flat().join(', ') || 'Erro ao enviar arquivo.';
            toastr.error(msg);
        }
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
        btn.textContent = 'Enviar';
        toastr.error('Erro de conexão.');
    });
}

function deleteMediaFile(mediaId) {
    if (!confirm('Remover este arquivo?')) return;

    const url = '{{ url("/ia/agentes") }}/' + state.agent_id + '/media/' + mediaId;
    fetch(url, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            state.media_files = state.media_files.filter(m => m.id !== mediaId);
            renderMediaList();
        }
    })
    .catch(console.error);
}

function renderMediaList() {
    const list = document.getElementById('mediaList');
    list.innerHTML = '';
    state.media_files.forEach(m => {
        const isImg = m.mime_type && m.mime_type.startsWith('image/');
        const isPdf = m.mime_type && m.mime_type.includes('pdf');
        const iconClass = isImg ? 'img' : (isPdf ? 'doc' : 'file');
        const iconBi = isImg ? 'bi-file-earmark-image' : (isPdf ? 'bi-file-earmark-pdf' : 'bi-file-earmark-text');

        list.insertAdjacentHTML('beforeend', `
            <div class="media-item">
                <div class="media-item-icon ${iconClass}"><i class="bi ${iconBi}"></i></div>
                <div class="media-item-body">
                    <div class="media-item-name">${escapeHtml(m.name)}</div>
                    <div class="media-item-desc">${escapeHtml(m.description)}</div>
                </div>
                <button class="media-item-del" onclick="deleteMediaFile(${m.id})" title="Remover">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        `);
    });
}

// ── Review ───────────────────────────────────────────────────────────────────

const LABELS = {
    name: 'Nome',
    company_name: 'Empresa',
    objective: 'Objetivo',
    communication_style: 'Estilo',
    language: 'Idioma',
    persona_description: 'Persona',
    behavior: 'Comportamento',
    on_finish_action: 'Finalização',
    knowledge_base: 'Conhecimento',
    channel: 'Canal',
    media_count: 'Mídias',
};

const DISPLAY = {
    objective: { sales: 'Vendas', support: 'Suporte', general: 'Geral' },
    communication_style: { formal: 'Formal', normal: 'Normal', casual: 'Casual' },
    language: { 'pt-BR': 'Português', 'en-US': 'English', 'es-ES': 'Español' },
    channel: { whatsapp: 'WhatsApp', web_chat: 'Web Chat' },
};

function buildReview() {
    saveCurrentStep();
    const grid = document.getElementById('wReviewGrid');
    grid.innerHTML = '';

    const keys = ['name','company_name','objective','communication_style','language',
                  'persona_description','behavior','on_finish_action','knowledge_base','channel'];

    keys.forEach(key => {
        let val = state[key];
        if (!val) return;
        const display = DISPLAY[key]?.[val] ?? val;
        const truncated = String(display).length > 200 ? String(display).substring(0, 200) + '…' : String(display);
        grid.insertAdjacentHTML('beforeend', `
            <div class="review-item">
                <div class="review-label">${LABELS[key] || key}</div>
                <div class="review-value">${escapeHtml(truncated)}</div>
            </div>
        `);
    });

    if (state.media_files.length > 0) {
        const names = state.media_files.map(m => m.name).join(', ');
        grid.insertAdjacentHTML('beforeend', `
            <div class="review-item">
                <div class="review-label">${LABELS.media_count}</div>
                <div class="review-value">${state.media_files.length} arquivo(s): ${escapeHtml(names)}</div>
            </div>
        `);
    }
}

// ── Submit ───────────────────────────────────────────────────────────────────

function wizardSubmit() {
    saveCurrentStep();
    const btn = document.getElementById('wCreateBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Finalizando...';

    if (state.agent_id) {
        // Agent already created — just update with final data + activate
        const body = {
            name: state.name,
            objective: state.objective,
            communication_style: state.communication_style,
            language: state.language,
            channel: state.channel,
            is_active: true,
        };
        if (state.company_name) body.company_name = state.company_name;
        if (state.persona_description) body.persona_description = state.persona_description;
        if (state.behavior) body.behavior = state.behavior;
        if (state.on_finish_action) body.on_finish_action = state.on_finish_action;
        if (state.knowledge_base) body.knowledge_base = state.knowledge_base;

        fetch('{{ url("/ia/agentes") }}/' + state.agent_id, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        })
        .then(r => {
            toastr.success('Agente criado com sucesso!');
            window.location.href = '{{ url("/ia/agentes") }}/' + state.agent_id + '/editar';
        })
        .catch(err => {
            console.error(err);
            // Even on error, agent exists — redirect anyway
            window.location.href = '{{ url("/ia/agentes") }}/' + state.agent_id + '/editar';
        });
    } else {
        // Agent not yet created — create now
        const body = {
            name: state.name,
            objective: state.objective,
            communication_style: state.communication_style,
            language: state.language,
            channel: state.channel,
            is_active: true,
        };
        if (state.company_name) body.company_name = state.company_name;
        if (state.persona_description) body.persona_description = state.persona_description;
        if (state.behavior) body.behavior = state.behavior;
        if (state.on_finish_action) body.on_finish_action = state.on_finish_action;
        if (state.knowledge_base) body.knowledge_base = state.knowledge_base;

        fetch('{{ route("ai.agents.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (ok && data.success) {
                toastr.success('Agente criado com sucesso!');
                window.location.href = data.redirect;
            } else {
                const msg = data.message || Object.values(data.errors || {}).flat().join(', ') || 'Erro ao criar agente.';
                toastr.error(msg);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Criar Agente';
            }
        })
        .catch(err => {
            console.error(err);
            toastr.error('Erro de conexão.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Criar Agente';
        });
    }
}

// ── Enter key ────────────────────────────────────────────────────────────────

document.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    if (e.target.tagName === 'TEXTAREA') return;
    const step = currentStepName();
    if (['objective','style','language','channel'].includes(step)) return; // cards auto-advance
    if (e.target.classList.contains('media-desc-input')) {
        e.preventDefault();
        uploadMediaFile();
        return;
    }
    e.preventDefault();
    if (currentIdx === STEPS.length - 1) wizardSubmit();
    else wizardNext();
});

// ── Helpers ──────────────────────────────────────────────────────────────────

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ── Init ─────────────────────────────────────────────────────────────────────
updateUI();
</script>
@endpush
