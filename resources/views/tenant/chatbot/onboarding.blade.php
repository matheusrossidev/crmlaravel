@extends('tenant.layouts.app')

@php
    $title    = 'Novo Chatbot';
    $pageIcon = 'diagram-3';
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
.wizard-progress-bar { height: 4px; background: #f0f2f7; }
.wizard-progress-fill {
    height: 4px;
    background: #0085f3;
    transition: width .4s ease;
    border-radius: 0 4px 4px 0;
}

.wizard-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 28px 0;
}
.wizard-step-counter {
    font-size: 12px; font-weight: 600; color: #9ca3af; letter-spacing: .04em;
}
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

/* ── Template step ────────────────────────────────────────────────────────── */
.tpl-tabs {
    display: flex; gap: 6px; overflow-x: auto; padding-bottom: 10px;
    margin-bottom: 14px; scrollbar-width: thin;
}
.tpl-tab {
    flex-shrink: 0; padding: 5px 12px; border-radius: 8px;
    font-size: 12px; font-weight: 600; color: #6b7280;
    background: #f4f6fb; border: 1px solid transparent;
    cursor: pointer; transition: all .15s; white-space: nowrap;
}
.tpl-tab:hover { color: #0085f3; background: #eff6ff; }
.tpl-tab.active { color: #0085f3; background: #eff6ff; border-color: #bfdbfe; }

.tpl-search {
    width: 100%; border: 1.5px solid #e8eaf0; border-radius: 9px;
    padding: 9px 12px 9px 34px; font-size: 13px; color: #1a1d23;
    outline: none; transition: border-color .15s; margin-bottom: 14px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%239ca3af' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/%3E%3C/svg%3E") no-repeat 10px center;
}
.tpl-search:focus { border-color: #0085f3; }

.tpl-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
    max-height: 340px; overflow-y: auto; padding-right: 4px;
}
.tpl-card {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 12px; border: 1.5px solid #e8eaf0; border-radius: 11px;
    cursor: pointer; transition: all .15s;
}
.tpl-card:hover { border-color: #93c5fd; background: #f8faff; }
.tpl-card.selected { border-color: #0085f3; background: #eff6ff; }
.tpl-card-icon {
    width: 34px; height: 34px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0; color: #fff;
}
.tpl-card-body { flex: 1; min-width: 0; }
.tpl-card-name { font-size: 13px; font-weight: 600; color: #1a1d23; margin-bottom: 2px; }
.tpl-card-desc { font-size: 11px; color: #9ca3af; line-height: 1.3; }

.from-scratch {
    border-style: dashed; border-color: #0085f3; background: #f8faff;
    grid-column: 1 / -1; flex-direction: row; align-items: center;
    justify-content: center; gap: 10px; padding: 14px;
}
.from-scratch:hover { background: #eff6ff; }
.from-scratch .scratch-icon { font-size: 22px; color: #0085f3; }
.from-scratch .scratch-label { font-size: 14px; font-weight: 600; color: #0085f3; }

/* ── Widget settings ──────────────────────────────────────────────────────── */
.widget-field { margin-bottom: 16px; }
.widget-label { font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .03em; }

.avatar-row { display: flex; gap: 10px; flex-wrap: wrap; }
.avatar-circle {
    width: 44px; height: 44px; border-radius: 50%; border: 2px solid #e8eaf0;
    cursor: pointer; transition: all .15s; object-fit: cover; display: flex;
    align-items: center; justify-content: center; background: #f4f6fb; color: #6b7280; font-size: 18px;
}
.avatar-circle:hover { border-color: #93c5fd; }
.avatar-circle.selected { border-color: #0085f3; box-shadow: 0 0 0 2px rgba(0,133,243,.25); }

.color-row { display: flex; align-items: center; gap: 10px; }
.color-row input[type="color"] {
    width: 40px; height: 40px; border: 2px solid #e8eaf0; border-radius: 9px;
    padding: 2px; cursor: pointer; background: #fff;
}
.color-row input[type="text"] {
    width: 100px; border: 1.5px solid #e8eaf0; border-radius: 8px;
    padding: 8px 10px; font-size: 13px; color: #1a1d23; font-family: monospace;
}

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
    .tpl-grid { grid-template-columns: 1fr; }
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
            <div class="wizard-progress-fill" id="wProgressFill" style="width:20%"></div>
        </div>

        {{-- Header --}}
        <div class="wizard-header">
            <button class="wizard-back-btn hidden" id="wBackBtn" onclick="wizardPrev()">
                <i class="bi bi-arrow-left"></i> Voltar
            </button>
            <span class="wizard-step-counter">Passo <span id="wStepNum">1</span> de <span id="wStepTotal">5</span></span>
        </div>

        {{-- Body --}}
        <div class="wizard-body">

            {{-- STEP: channel --}}
            <div class="wizard-step active" data-step="channel">
                <div class="wizard-question">Para qual canal?</div>
                <div class="wizard-subtitle">Escolha onde seu chatbot vai operar.</div>
                <div class="wizard-cards cols-3">
                    <div class="wizard-option-card" data-field="channel" data-value="whatsapp" onclick="selectCard(this)">
                        <span class="card-icon"><i class="bi bi-whatsapp" style="color:#25d366;font-size:28px"></i></span>
                        <span class="card-label">WhatsApp</span>
                        <span class="card-desc">Dispara por palavras-chave</span>
                    </div>
                    <div class="wizard-option-card" data-field="channel" data-value="instagram" onclick="selectCard(this)">
                        <span class="card-icon"><i class="bi bi-instagram" style="color:#e1306c;font-size:28px"></i></span>
                        <span class="card-label">Instagram</span>
                        <span class="card-desc">DMs e respostas automáticas</span>
                    </div>
                    <div class="wizard-option-card" data-field="channel" data-value="website" onclick="selectCard(this)">
                        <span class="card-icon"><i class="bi bi-globe" style="color:#0085f3;font-size:28px"></i></span>
                        <span class="card-label">Website</span>
                        <span class="card-desc">Widget de chat no seu site</span>
                    </div>
                </div>
            </div>

            {{-- STEP: name --}}
            <div class="wizard-step" data-step="name">
                <div class="wizard-question">Como quer chamar seu chatbot?</div>
                <div class="wizard-subtitle">Um nome curto e descritivo.</div>
                <input type="text" class="wizard-text-input" id="f_name"
                       placeholder="Ex: Qualificador de Leads, Atendimento..." maxlength="100">
                <div style="margin-top:14px;">
                    <div style="font-size:12px;color:#6b7280;font-weight:600;margin-bottom:6px;">DESCRIÇÃO <span class="wizard-skip" onclick="wizardSkip()">Pular</span></div>
                    <textarea class="wizard-text-input" id="f_description" rows="3"
                              placeholder="Descreva brevemente o objetivo deste fluxo..." maxlength="500"></textarea>
                </div>
            </div>

            {{-- STEP: template --}}
            <div class="wizard-step" data-step="template">
                <div class="wizard-question">Escolha um modelo ou comece do zero</div>
                <div class="wizard-subtitle">Modelos pré-prontos aceleram a criação.</div>
                <input type="text" class="tpl-search" id="tplSearch" placeholder="Buscar modelo..."
                       oninput="filterTemplates()">
                <div class="tpl-tabs" id="tplTabs"></div>
                <div class="tpl-grid" id="tplGrid"></div>
            </div>

            {{-- STEP: widget_settings (website only) --}}
            <div class="wizard-step" data-step="widget_settings">
                <div class="wizard-question">Configure seu widget</div>
                <div class="wizard-subtitle">Personalize a aparência do chat no seu site.</div>

                <div class="widget-field">
                    <div class="widget-label">Nome do bot</div>
                    <input type="text" class="wizard-text-input" id="f_bot_name"
                           placeholder="Ex: Ana, Sofia, Assistente..." maxlength="50"
                           style="padding:10px 14px;font-size:14px;">
                </div>

                <div class="widget-field">
                    <div class="widget-label">Avatar</div>
                    <div class="avatar-row" id="avatarRow">
                        <img src="{{ asset('images/avatars/agent-1.png') }}" class="avatar-circle selected" data-avatar="agent-1" onclick="selectAvatar(this)" onerror="this.innerHTML='🤖';this.style.fontSize='20px'">
                        <img src="{{ asset('images/avatars/agent-2.png') }}" class="avatar-circle" data-avatar="agent-2" onclick="selectAvatar(this)" onerror="this.innerHTML='👩';this.style.fontSize='20px'">
                        <img src="{{ asset('images/avatars/agent-3.png') }}" class="avatar-circle" data-avatar="agent-3" onclick="selectAvatar(this)" onerror="this.innerHTML='👨';this.style.fontSize='20px'">
                        <img src="{{ asset('images/avatars/agent-4.png') }}" class="avatar-circle" data-avatar="agent-4" onclick="selectAvatar(this)" onerror="this.innerHTML='🎧';this.style.fontSize='20px'">
                        <img src="{{ asset('images/avatars/agent-5.png') }}" class="avatar-circle" data-avatar="agent-5" onclick="selectAvatar(this)" onerror="this.innerHTML='💬';this.style.fontSize='20px'">
                        <div class="avatar-circle" onclick="document.getElementById('avatarUpload').click()" title="Upload personalizado">
                            <i class="bi bi-plus-lg"></i>
                        </div>
                        <input type="file" id="avatarUpload" accept="image/*" style="display:none">
                    </div>
                </div>

                <div class="widget-field">
                    <div class="widget-label">Mensagem de boas-vindas</div>
                    <textarea class="wizard-text-input" id="f_welcome" rows="2"
                              placeholder="Olá! 👋 Como posso te ajudar?" maxlength="300"
                              style="padding:10px 14px;font-size:14px;"></textarea>
                </div>

                <div class="widget-field">
                    <div class="widget-label">Tipo de widget</div>
                    <div class="wizard-cards cols-2">
                        <div class="wizard-option-card selected" data-field="widget_type" data-value="bubble" onclick="selectWidgetType(this)" style="padding:12px;">
                            <span class="card-icon"><i class="bi bi-chat-dots" style="font-size:22px;color:#0085f3;"></i></span>
                            <span class="card-label">Bubble</span>
                            <span class="card-desc">Bolha flutuante no canto</span>
                        </div>
                        <div class="wizard-option-card" data-field="widget_type" data-value="inline" onclick="selectWidgetType(this)" style="padding:12px;">
                            <span class="card-icon"><i class="bi bi-window" style="font-size:22px;color:#0085f3;"></i></span>
                            <span class="card-label">Inline / Página</span>
                            <span class="card-desc">Incorporado na página</span>
                        </div>
                    </div>
                </div>

                <div class="widget-field">
                    <div class="widget-label">Cor do widget</div>
                    <div class="color-row">
                        <input type="color" id="f_widget_color" value="#0085f3" oninput="document.getElementById('f_widget_color_hex').value=this.value">
                        <input type="text" id="f_widget_color_hex" value="#0085f3"
                               oninput="document.getElementById('f_widget_color').value=this.value">
                    </div>
                </div>
            </div>

            {{-- STEP: trigger_keywords (whatsapp/instagram only) --}}
            <div class="wizard-step" data-step="trigger_keywords">
                <div class="wizard-question">Palavras-chave de disparo</div>
                <div class="wizard-subtitle">
                    Quando o contato enviar uma dessas palavras, o fluxo inicia automaticamente. Separe por vírgula.
                    <span class="wizard-skip" onclick="wizardSkip()">Pular</span>
                </div>
                <input type="text" class="wizard-text-input" id="f_keywords"
                       placeholder="oi, olá, bom dia, menu, preço" maxlength="500">
                <div style="margin-top:10px;font-size:11.5px;color:#9ca3af;">
                    <i class="bi bi-info-circle"></i> Se não definir palavras-chave, o fluxo só será ativado manualmente.
                </div>
            </div>

            {{-- STEP: review --}}
            <div class="wizard-step" data-step="review">
                <div class="wizard-question">Tudo certo? Revise antes de criar</div>
                <div class="wizard-subtitle">Confirme as informações do seu chatbot.</div>
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
                <i class="bi bi-check-circle"></i> Criar Chatbot
            </button>
        </div>

    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
// ── Build Lead Flow Helper (same as builder.blade.php) ──────────────────────
function buildLeadFlow(greeting, extraQuestions, farewell) {
    var steps = [
        { id:'t1', type:'message', config:{ text: greeting } },
        { id:'t2', type:'input', config:{ input_type:'text', prompt:'Qual o seu nome?', save_to:'nome' }, branches:[], default_branch:{ steps:[] } },
    ];
    var vars = [{ name:'nome', default:'' }];
    var idx = 3;
    (extraQuestions || []).forEach(function(q) {
        var step = { id:'t'+(idx), type:'input', config:{ input_type: q.type || 'text', prompt: q.prompt, save_to: q.save_to }, branches:[], default_branch:{ steps:[] } };
        if (q.buttons) {
            step.config.input_type = 'buttons';
            step.branches = q.buttons.map(function(b, bi) {
                return { id:'b'+idx+'_'+bi, label: b, keywords:[b.toLowerCase()], steps:[] };
            });
        }
        steps.push(step);
        vars.push({ name: q.save_to, default:'' });
        idx++;
    });
    steps.push({ id:'t'+(idx++), type:'input', config:{ input_type:'phone', prompt:'Seu telefone com DDD:', save_to:'telefone' }, branches:[], default_branch:{ steps:[] } });
    vars.push({ name:'telefone', default:'' });
    steps.push({ id:'t'+(idx++), type:'action', config:{ action_type:'create_lead', source:'website' } });
    steps.push({ id:'t'+(idx++), type:'message', config:{ text: farewell } });
    steps.push({ id:'t'+(idx++), type:'end', config:{} });
    return { steps: steps, variables: vars };
}

// ── Templates (with steps & variables) ──────────────────────────────────────
const TEMPLATES = [
    (function(){var f=buildLeadFlow('Seja bem-vindo! Vou te ajudar com algumas perguntas rapidas.',[{prompt:'Qual o seu email?',save_to:'email',type:'email'}],'Obrigado! Em breve entraremos em contato.');return{id:'lead_capture',name:'Captura de Lead',category:'Geral',desc:'Coleta nome, email e telefone. Cria lead.',icon:'bi-person-plus',color:'#2563eb',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Quer receber um contato da nossa equipe?',[{prompt:'Qual o seu email?',save_to:'email',type:'email'},{prompt:'Como podemos te ajudar?',save_to:'interesse'}],'Perfeito! Nossa equipe vai entrar em contato em breve.');return{id:'callback',name:'Solicitar Callback',category:'Geral',desc:'Visitante solicita que a equipe entre em contato.',icon:'bi-telephone-inbound',color:'#0d9488',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Que bom que voce se interessou! Vou fazer perguntas rapidas.',[{prompt:'Que tipo de imovel procura?',save_to:'tipo_imovel',buttons:['Apartamento','Casa','Comercial','Terreno']},{prompt:'Qual a faixa de preco?',save_to:'faixa_preco',buttons:['Ate R$ 300 mil','R$ 300-600 mil','Acima R$ 600 mil']},{prompt:'Em qual regiao ou bairro?',save_to:'regiao'}],'Vou buscar as melhores opcoes para voce!');return{id:'real_estate',name:'Imobiliaria',category:'Imoveis',desc:'Tipo de imovel, faixa de preco, localizacao.',icon:'bi-building',color:'#7c3aed',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Procurando um imovel para alugar? Vou te ajudar!',[{prompt:'Tipo de imovel?',save_to:'tipo',buttons:['Apartamento','Casa','Kitnet','Comercial']},{prompt:'Quantos quartos?',save_to:'quartos',buttons:['1','2','3','4+']},{prompt:'Orcamento mensal maximo?',save_to:'orcamento'}],'Otimo! Vamos encontrar o lugar ideal pra voce.');return{id:'rental',name:'Aluguel de Imoveis',category:'Imoveis',desc:'Qualificacao para locacao: tipo, quartos, orcamento.',icon:'bi-house-door',color:'#6d28d9',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Bem-vindo a nossa clinica! Vou te ajudar a agendar.',[{prompt:'Qual a especialidade desejada?',save_to:'especialidade',buttons:['Clinico Geral','Ortopedia','Dermatologia','Outra']},{prompt:'Possui convenio?',save_to:'convenio',buttons:['Sim','Particular']},{prompt:'Melhor horario? (manha, tarde, noite)',save_to:'horario'}],'Agendamento solicitado! Confirmaremos por telefone.');return{id:'clinic',name:'Clinica Medica',category:'Saude',desc:'Agendamento: especialidade, convenio, horario.',icon:'bi-heart-pulse',color:'#dc2626',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Bem-vindo ao nosso consultorio odontologico!',[{prompt:'Qual o servico desejado?',save_to:'servico',buttons:['Limpeza','Clareamento','Ortodontia','Implante','Outro']},{prompt:'Possui convenio odontologico?',save_to:'convenio',buttons:['Sim','Nao']},{prompt:'Melhor horario para consulta?',save_to:'horario'}],'Perfeito! Vamos agendar sua consulta.');return{id:'dentist',name:'Dentista',category:'Saude',desc:'Servico odontologico, convenio, horario.',icon:'bi-emoji-smile',color:'#e11d48',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Vou te ajudar a agendar sua sessao.',[{prompt:'Qual o tipo de terapia?',save_to:'terapia',buttons:['Psicologia','Fisioterapia','Nutricao','Fonoaudiologia']},{prompt:'E a primeira consulta?',save_to:'primeira_vez',buttons:['Sim','Nao, retorno']},{prompt:'Prefere atendimento presencial ou online?',save_to:'modalidade',buttons:['Presencial','Online']}],'Sessao solicitada! Confirmaremos em breve.');return{id:'therapy',name:'Terapia / Psicologia',category:'Saude',desc:'Agendamento de sessao terapeutica.',icon:'bi-chat-heart',color:'#be185d',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Bem-vindo a nossa clinica veterinaria!',[{prompt:'Qual o tipo de animal?',save_to:'animal',buttons:['Cao','Gato','Ave','Outro']},{prompt:'Qual o motivo da consulta?',save_to:'motivo',buttons:['Check-up','Vacina','Emergencia','Outro']},{prompt:'Nome do pet?',save_to:'pet_nome'}],'Vamos cuidar bem do seu pet! Entraremos em contato.');return{id:'vet',name:'Veterinaria',category:'Saude',desc:'Agendamento veterinario: animal, motivo.',icon:'bi-bug',color:'#9333ea',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Agende seu horario no nosso salao.',[{prompt:'Qual o servico?',save_to:'servico',buttons:['Corte','Coloracao','Manicure','Sobrancelha','Outro']},{prompt:'Tem preferencia de profissional?',save_to:'profissional'},{prompt:'Melhor dia e horario?',save_to:'horario'}],'Agendamento solicitado! Confirmaremos seu horario.');return{id:'salon',name:'Salao de Beleza',category:'Estetica',desc:'Agendamento: servico, profissional, horario.',icon:'bi-scissors',color:'#ec4899',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Bem-vindo a nossa clinica de estetica.',[{prompt:'Qual procedimento te interessa?',save_to:'procedimento',buttons:['Botox','Preenchimento','Limpeza de pele','Depilacao a laser','Outro']},{prompt:'Ja fez esse procedimento antes?',save_to:'experiencia',buttons:['Sim','Primeira vez']}],'Otimo! Vamos agendar sua avaliacao.');return{id:'aesthetics',name:'Clinica de Estetica',category:'Estetica',desc:'Procedimentos esteticos: botox, preenchimento, laser.',icon:'bi-stars',color:'#d946ef',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Agende sua sessao de massagem ou spa.',[{prompt:'Qual servico?',save_to:'servico',buttons:['Massagem Relaxante','Drenagem','Day Spa','Reflexologia']},{prompt:'Prefere manha, tarde ou noite?',save_to:'horario',buttons:['Manha','Tarde','Noite']}],'Relaxe! Vamos confirmar seu agendamento.');return{id:'spa',name:'Spa / Massagem',category:'Estetica',desc:'Agendamento de massagem e spa.',icon:'bi-droplet-half',color:'#8b5cf6',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Quer conhecer nossa academia?',[{prompt:'Qual seu objetivo?',save_to:'objetivo',buttons:['Emagrecer','Ganhar massa','Condicionamento','Outro']},{prompt:'Ja treina ou vai comecar agora?',save_to:'experiencia',buttons:['Ja treino','Iniciante']},{prompt:'Melhor horario para visita?',save_to:'horario'}],'Perfeito! Vamos agendar sua visita e aula experimental.');return{id:'gym',name:'Academia / Fitness',category:'Fitness',desc:'Captacao: objetivo, experiencia, visita.',icon:'bi-activity',color:'#ea580c',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Que tal agendar uma aula experimental?',[{prompt:'Qual modalidade?',save_to:'modalidade',buttons:['Yoga','Pilates','Danca','Funcional','Luta']},{prompt:'Nivel de experiencia?',save_to:'nivel',buttons:['Iniciante','Intermediario','Avancado']}],'Aula experimental agendada! Te esperamos.');return{id:'studio',name:'Studio / Aulas',category:'Fitness',desc:'Aula experimental: modalidade, nivel.',icon:'bi-person-arms-up',color:'#f97316',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Que bom que voce quer estudar conosco!',[{prompt:'Qual curso te interessa?',save_to:'curso'},{prompt:'Qual sua escolaridade atual?',save_to:'escolaridade',buttons:['Fundamental','Medio','Superior','Pos-graduacao']},{prompt:'Seu email para enviarmos mais informacoes:',save_to:'email',type:'email'}],'Informacoes enviadas! Logo entraremos em contato.');return{id:'school',name:'Escola / Curso',category:'Educacao',desc:'Captacao de alunos: curso, escolaridade.',icon:'bi-mortarboard',color:'#0284c7',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Quer agendar uma aula de idiomas?',[{prompt:'Qual idioma?',save_to:'idioma',buttons:['Ingles','Espanhol','Frances','Alemao','Outro']},{prompt:'Seu nivel atual?',save_to:'nivel',buttons:['Iniciante','Intermediario','Avancado']},{prompt:'Prefere aulas individuais ou em grupo?',save_to:'formato',buttons:['Individual','Grupo','Tanto faz']}],'Otimo! Vamos agendar sua aula experimental.');return{id:'language',name:'Escola de Idiomas',category:'Educacao',desc:'Aula de idiomas: lingua, nivel, formato.',icon:'bi-translate',color:'#0369a1',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Bem-vindo ao nosso restaurante.',[{prompt:'Para quantas pessoas?',save_to:'pessoas',buttons:['1-2','3-4','5-8','9+']},{prompt:'Qual dia e horario da reserva?',save_to:'horario'},{prompt:'Alguma restricao alimentar ou observacao?',save_to:'observacao'}],'Reserva solicitada! Confirmaremos em breve.');return{id:'restaurant',name:'Restaurante',category:'Alimentacao',desc:'Reserva: pessoas, horario, restricoes.',icon:'bi-cup-hot',color:'#b45309',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Que bom que voce chegou! Faca seu pedido.',[{prompt:'O que deseja pedir?',save_to:'pedido'},{prompt:'Entrega ou retirada?',save_to:'tipo_entrega',buttons:['Entrega','Retirada']},{prompt:'Endereco de entrega (se aplicavel):',save_to:'endereco'}],'Pedido anotado! Ja estamos preparando.');return{id:'delivery',name:'Delivery / Lanchonete',category:'Alimentacao',desc:'Pedido, tipo de entrega, endereco.',icon:'bi-bag-check',color:'#a16207',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Como posso te ajudar hoje?',[{prompt:'Escolha uma opcao:',save_to:'assunto',buttons:['Acompanhar pedido','Troca/Devolucao','Duvida sobre produto','Outro']}],'Entendido! Um atendente vai te responder em breve.');return{id:'ecommerce',name:'E-commerce Geral',category:'Varejo',desc:'Atendimento: pedido, troca, duvida.',icon:'bi-cart3',color:'#ea580c',steps:f.steps,variables:f.variables}})(),
    (function(){var steps=[{id:'t1',type:'message',config:{text:'Ol\u00e1! Seja bem-vinda \u00e0 sua loja de cortinas \ud83c\udfe0\nVou te ajudar a encontrar a cortina perfeita para o seu espa\u00e7o.\n\u00c9 rapidinho, prometo! \ud83d\ude0a'}},{id:'t2',type:'input',config:{input_type:'buttons',prompt:'Para qual ambiente voc\u00ea est\u00e1 buscando cortinas?',save_to:'ambiente'},branches:[{id:'b2_0',label:'Sala de estar',keywords:['sala de estar','sala'],steps:[]},{id:'b2_1',label:'Quarto',keywords:['quarto'],steps:[]},{id:'b2_2',label:'Escrit\u00f3rio / Empresa',keywords:['escritorio','empresa'],steps:[]},{id:'b2_3',label:'Mais de um ambiente',keywords:['mais de um ambiente','mais de um'],steps:[]}],default_branch:{steps:[]}},{id:'t3',type:'input',config:{input_type:'buttons',prompt:'O que \u00e9 mais importante para voc\u00ea nessa cortina?',save_to:'necessidade'},branches:[{id:'b3_0',label:'Bloquear a luz (blackout)',keywords:['bloquear','blackout'],steps:[]},{id:'b3_1',label:'Privacidade sem escurecer',keywords:['privacidade'],steps:[]},{id:'b3_2',label:'Decora\u00e7\u00e3o e est\u00e9tica',keywords:['decoracao','estetica'],steps:[]},{id:'b3_3',label:'Ainda n\u00e3o sei, preciso de ajuda',keywords:['nao sei','ajuda'],steps:[]}],default_branch:{steps:[]}},{id:'t4',type:'input',config:{input_type:'buttons',prompt:'Voc\u00ea j\u00e1 tem ideia do modelo ou tecido que quer?',save_to:'estagio_decisao'},branches:[{id:'b4_0',label:'Sim, j\u00e1 sei o que quero',keywords:['sim','ja sei'],steps:[]},{id:'b4_1',label:'Mais ou menos, preciso de orienta\u00e7\u00e3o',keywords:['mais ou menos','orientacao'],steps:[]},{id:'b4_2',label:'N\u00e3o, quero uma indica\u00e7\u00e3o do zero',keywords:['nao','indicacao','zero'],steps:[]}],default_branch:{steps:[]}},{id:'t5',type:'input',config:{input_type:'buttons',prompt:'Qual \u00e9 o seu prazo para ter as cortinas prontas?',save_to:'urgencia'},branches:[{id:'b5_0',label:'Preciso o quanto antes',keywords:['quanto antes','urgente'],steps:[]},{id:'b5_1',label:'Tenho at\u00e9 30 dias',keywords:['30 dias'],steps:[]},{id:'b5_2',label:'Ainda estou planejando, sem pressa',keywords:['planejando','sem pressa'],steps:[]}],default_branch:{steps:[]}},{id:'t6',type:'input',config:{input_type:'text',prompt:'\u00d3timo! Para personalizar seu atendimento, qual \u00e9 o seu nome? \ud83d\ude0a',save_to:'nome'},branches:[],default_branch:{steps:[]}},{id:'t7',type:'message',config:{text:'@{{nome}}, voc\u00ea est\u00e1 no lugar certo! \u2728\nTrabalhamos com cortinas sob medida \u2014 do tecido \u00e0 entrega, tudo pensado para o seu espa\u00e7o.\nClique abaixo e nossa consultora j\u00e1 te atende agora no WhatsApp \ud83d\udc47'}},{id:'t8',type:'action',config:{action_type:'create_lead',source:'website'}},{id:'t9',type:'end',config:{}}];var variables=[{name:'ambiente',default:''},{name:'necessidade',default:''},{name:'estagio_decisao',default:''},{name:'urgencia',default:''},{name:'nome',default:''}];return{id:'curtains',name:'Cortinas e Persianas',category:'Varejo',desc:'Qualifica\u00e7\u00e3o: ambiente, necessidade, urg\u00eancia + WhatsApp.',icon:'bi-window',color:'#6366f1',steps:steps,variables:variables}})(),
    (function(){var f=buildLeadFlow('Ola! Precisa de um orcamento?',[{prompt:'Qual o servico?',save_to:'servico',buttons:['Eletrica','Hidraulica','Pintura','Reforma geral','Outro']},{prompt:'E residencial ou comercial?',save_to:'tipo',buttons:['Residencial','Comercial']},{prompt:'Descreva brevemente o servico:',save_to:'descricao'}],'Orcamento solicitado! Entraremos em contato.');return{id:'handyman',name:'Servicos / Manutencao',category:'Servicos',desc:'Eletrica, hidraulica, pintura, reforma.',icon:'bi-tools',color:'#d97706',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Precisa de assessoria juridica?',[{prompt:'Qual a area?',save_to:'area',buttons:['Trabalhista','Civil','Criminal','Familia','Empresarial','Outro']},{prompt:'Descreva brevemente seu caso:',save_to:'descricao'},{prompt:'Seu email:',save_to:'email',type:'email'}],'Analisaremos seu caso e retornaremos em breve.');return{id:'lawyer',name:'Advocacia',category:'Servicos',desc:'Area juridica, descricao do caso.',icon:'bi-bank',color:'#1e40af',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Bem-vindo a nossa oficina.',[{prompt:'Qual o servico?',save_to:'servico',buttons:['Revisao','Freios','Motor','Eletrica','Funilaria','Outro']},{prompt:'Marca e modelo do veiculo?',save_to:'veiculo'},{prompt:'Melhor dia para levar?',save_to:'data'}],'Agendamento solicitado! Confirmaremos o horario.');return{id:'mechanic',name:'Oficina Mecanica',category:'Automotivo',desc:'Servico, veiculo, agendamento.',icon:'bi-wrench',color:'#78350f',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Procurando um veiculo?',[{prompt:'Novo ou usado?',save_to:'condicao',buttons:['Novo','Seminovo','Tanto faz']},{prompt:'Tipo de veiculo?',save_to:'tipo',buttons:['Carro','Moto','Caminhonete','SUV']},{prompt:'Faixa de preco?',save_to:'preco'}],'Vamos encontrar o veiculo ideal pra voce!');return{id:'car_dealer',name:'Concessionaria / Veiculos',category:'Automotivo',desc:'Novo/usado, tipo, faixa de preco.',icon:'bi-car-front',color:'#92400e',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Vamos planejar seu evento?',[{prompt:'Tipo de evento?',save_to:'tipo',buttons:['Casamento','Aniversario','Corporativo','Formatura','Outro']},{prompt:'Numero estimado de convidados?',save_to:'convidados'},{prompt:'Data prevista?',save_to:'data_evento'},{prompt:'Orcamento estimado?',save_to:'orcamento'}],'Seu evento vai ser incrivel! Entraremos em contato.');return{id:'events',name:'Eventos / Festas',category:'Eventos',desc:'Tipo, convidados, data, orcamento.',icon:'bi-calendar-event',color:'#c026d3',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Quer fazer um orcamento de fotografia?',[{prompt:'Tipo de ensaio/evento?',save_to:'tipo',buttons:['Casamento','Ensaio','Aniversario','Corporativo','Produto']},{prompt:'Data estimada?',save_to:'data'},{prompt:'Local (cidade/bairro)?',save_to:'local'}],'Orcamento de fotografia solicitado!');return{id:'photography',name:'Fotografia / Video',category:'Eventos',desc:'Tipo de ensaio, data, local.',icon:'bi-camera',color:'#a21caf',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Vamos planejar sua viagem dos sonhos?',[{prompt:'Destino desejado?',save_to:'destino'},{prompt:'Quantas pessoas?',save_to:'pessoas',buttons:['1','2','3-4','5+']},{prompt:'Periodo da viagem?',save_to:'periodo'},{prompt:'Seu email:',save_to:'email',type:'email'}],'Pacote em analise! Enviaremos opcoes por email.');return{id:'travel',name:'Agencia de Viagem',category:'Turismo',desc:'Destino, pessoas, periodo.',icon:'bi-airplane',color:'#0284c7',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Quer conhecer nossa plataforma? Crie sua conta gratis!',[{prompt:'Seu email:',save_to:'email',type:'email'},{prompt:'Nome da empresa:',save_to:'empresa'},{prompt:'Segmento?',save_to:'segmento',buttons:['Tecnologia','Servicos','Varejo','Outro']}],'Conta trial solicitada! Verifique seu email.');return{id:'saas_trial',name:'SaaS / Teste Gratis',category:'Tecnologia',desc:'Onboarding: email, empresa, segmento.',icon:'bi-rocket-takeoff',color:'#0891b2',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Precisa de suporte tecnico?',[{prompt:'Qual o problema?',save_to:'problema',buttons:['Computador lento','Sem internet','Erro no sistema','Outro']},{prompt:'E urgente?',save_to:'urgencia',buttons:['Sim, urgente','Nao, pode agendar']},{prompt:'Seu email:',save_to:'email',type:'email'}],'Suporte registrado! Um tecnico vai te atender.');return{id:'tech_support',name:'Suporte Tecnico / TI',category:'Tecnologia',desc:'Problema, urgencia, contato.',icon:'bi-pc-display',color:'#0e7490',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Quer impulsionar sua presenca digital?',[{prompt:'O que voce precisa?',save_to:'servico',buttons:['Gestao de redes sociais','Trafego pago','Criacao de site','Branding','Outro']},{prompt:'Site ou Instagram da empresa (se tiver):',save_to:'site'},{prompt:'Seu email:',save_to:'email',type:'email'}],'Otimo! Vamos criar uma estrategia pra voce.');return{id:'marketing_agency',name:'Agencia de Marketing',category:'Tecnologia',desc:'Servico digital, site, contato.',icon:'bi-megaphone',color:'#7c3aed',steps:f.steps,variables:f.variables}})(),
    (function(){var f=buildLeadFlow('Ola! Quer economizar na conta de luz com energia solar?',[{prompt:'Valor medio da conta de luz?',save_to:'conta_luz'},{prompt:'Tipo de imovel?',save_to:'tipo',buttons:['Residencial','Comercial','Rural','Industrial']},{prompt:'Cidade?',save_to:'cidade'}],'Simulacao solicitada! Enviaremos a proposta.');return{id:'solar',name:'Energia Solar',category:'Servicos',desc:'Conta de luz, tipo de imovel, cidade.',icon:'bi-sun',color:'#eab308',steps:f.steps,variables:f.variables}})(),
];

// ── State ────────────────────────────────────────────────────────────────────
const state = {
    channel: '',
    name: '',
    description: '',
    template: null,
    template_name: '',
    bot_name: '',
    bot_avatar: 'agent-1',
    welcome_message: '',
    widget_type: 'bubble',
    widget_color: '#0085f3',
    trigger_keywords: '',
};

let stepOrder = ['channel', 'name', 'template', 'review'];
let currentIdx = 0;

function buildStepOrder() {
    const steps = ['channel', 'name', 'template'];
    if (state.channel === 'website') steps.push('widget_settings');
    else steps.push('trigger_keywords');
    steps.push('review');
    stepOrder = steps;
}

// ── Navigation ───────────────────────────────────────────────────────────────

function currentStepName() { return stepOrder[currentIdx]; }

function updateUI() {
    const stepName = currentStepName();
    document.querySelectorAll('.wizard-step').forEach(el => {
        el.classList.toggle('active', el.dataset.step === stepName);
    });

    const pct = Math.round(((currentIdx + 1) / stepOrder.length) * 100);
    document.getElementById('wProgressFill').style.width = pct + '%';
    document.getElementById('wStepNum').textContent = currentIdx + 1;
    document.getElementById('wStepTotal').textContent = stepOrder.length;
    document.getElementById('wBackBtn').classList.toggle('hidden', currentIdx === 0);

    const isLast = currentIdx === stepOrder.length - 1;
    document.getElementById('wNextBtn').style.display = isLast ? 'none' : '';
    document.getElementById('wCreateBtn').style.display = isLast ? '' : 'none';
    if (isLast) buildReview();

    // Focus input
    const activeStep = document.querySelector('.wizard-step.active');
    const inp = activeStep?.querySelector('input[type="text"]:not(.tpl-search), .wizard-text-input');
    if (inp && stepName !== 'template') setTimeout(() => inp.focus(), 100);

    // Render templates on template step
    if (stepName === 'template') renderTemplatesTabs();
}

function saveCurrentStep() {
    const step = currentStepName();
    if (step === 'name') {
        state.name = document.getElementById('f_name').value.trim();
        state.description = document.getElementById('f_description').value.trim();
    }
    if (step === 'trigger_keywords') {
        state.trigger_keywords = document.getElementById('f_keywords').value.trim();
    }
    if (step === 'widget_settings') {
        state.bot_name = document.getElementById('f_bot_name').value.trim();
        state.welcome_message = document.getElementById('f_welcome').value.trim();
        state.widget_color = document.getElementById('f_widget_color').value;
    }
}

function validateCurrentStep() {
    const step = currentStepName();
    if (step === 'channel' && !state.channel) {
        toastr.warning('Selecione um canal.');
        return false;
    }
    if (step === 'name' && !document.getElementById('f_name').value.trim()) {
        toastr.warning('Dê um nome ao seu chatbot.');
        return false;
    }
    return true;
}

function wizardNext() {
    saveCurrentStep();
    if (!validateCurrentStep()) return;
    if (currentIdx < stepOrder.length - 1) {
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
    if (currentIdx < stepOrder.length - 1) {
        currentIdx++;
        updateUI();
    }
}

// ── Card selection (channel) ─────────────────────────────────────────────────

function selectCard(el) {
    const field = el.dataset.field;
    const value = el.dataset.value;
    el.closest('.wizard-cards').querySelectorAll('.wizard-option-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    state[field] = value;
    if (field === 'channel') buildStepOrder();
    setTimeout(wizardNext, 280);
}

function selectWidgetType(el) {
    el.closest('.wizard-cards').querySelectorAll('.wizard-option-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    state.widget_type = el.dataset.value;
}

function selectAvatar(el) {
    document.querySelectorAll('.avatar-circle').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    state.bot_avatar = el.dataset.avatar || 'custom';
}

// ── Templates ────────────────────────────────────────────────────────────────

let _tplCategory = 'Todos';

function getCategories() {
    const cats = ['Todos'];
    TEMPLATES.forEach(t => { if (!cats.includes(t.category)) cats.push(t.category); });
    return cats;
}

function renderTemplatesTabs() {
    const tabs = document.getElementById('tplTabs');
    tabs.innerHTML = '';
    getCategories().forEach(cat => {
        const btn = document.createElement('div');
        btn.className = 'tpl-tab' + (cat === _tplCategory ? ' active' : '');
        btn.textContent = cat;
        btn.onclick = () => { _tplCategory = cat; renderTemplatesTabs(); renderTemplatesGrid(); };
        tabs.appendChild(btn);
    });
    renderTemplatesGrid();
}

function renderTemplatesGrid() {
    const grid = document.getElementById('tplGrid');
    const q = (document.getElementById('tplSearch').value || '').toLowerCase();
    grid.innerHTML = '';

    // "From scratch" card always first
    grid.insertAdjacentHTML('beforeend', `
        <div class="tpl-card from-scratch${!state.template ? ' selected' : ''}" onclick="selectTemplate(null, 'Do zero')">
            <span class="scratch-icon"><i class="bi bi-plus-circle"></i></span>
            <span class="scratch-label">Começar do zero</span>
        </div>
    `);

    const filtered = TEMPLATES.filter(t => {
        if (_tplCategory !== 'Todos' && t.category !== _tplCategory) return false;
        if (!q) return true;
        return (t.name + ' ' + t.desc + ' ' + t.category).toLowerCase().includes(q);
    });

    filtered.forEach(t => {
        const sel = state.template === t.id ? ' selected' : '';
        grid.insertAdjacentHTML('beforeend', `
            <div class="tpl-card${sel}" onclick="selectTemplate('${t.id}', '${escapeHtml(t.name)}')">
                <div class="tpl-card-icon" style="background:${t.color}"><i class="bi ${t.icon}"></i></div>
                <div class="tpl-card-body">
                    <div class="tpl-card-name">${escapeHtml(t.name)}</div>
                    <div class="tpl-card-desc">${escapeHtml(t.desc)}</div>
                </div>
            </div>
        `);
    });
}

function filterTemplates() {
    renderTemplatesGrid();
}

function selectTemplate(id, name) {
    state.template = id;
    state.template_name = name || 'Do zero';
    document.querySelectorAll('.tpl-card').forEach(c => c.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    setTimeout(wizardNext, 300);
}

// ── Review ───────────────────────────────────────────────────────────────────

const LABELS = {
    channel: 'Canal',
    name: 'Nome',
    description: 'Descrição',
    template_name: 'Modelo',
    bot_name: 'Nome do bot',
    bot_avatar: 'Avatar',
    welcome_message: 'Boas-vindas',
    widget_type: 'Tipo widget',
    widget_color: 'Cor',
    trigger_keywords: 'Palavras-chave',
};

const DISPLAY = {
    channel: { whatsapp: 'WhatsApp', instagram: 'Instagram', website: 'Website' },
    widget_type: { bubble: 'Bubble (flutuante)', inline: 'Inline (página)' },
};

function buildReview() {
    saveCurrentStep();
    const grid = document.getElementById('wReviewGrid');
    grid.innerHTML = '';

    const keys = ['channel', 'name', 'description', 'template_name'];
    if (state.channel === 'website') keys.push('bot_name', 'bot_avatar', 'welcome_message', 'widget_type', 'widget_color');
    else keys.push('trigger_keywords');

    keys.forEach(key => {
        let val = state[key];
        if (!val) return;
        const display = DISPLAY[key]?.[val] ?? val;
        grid.insertAdjacentHTML('beforeend', `
            <div class="review-item">
                <div class="review-label">${LABELS[key] || key}</div>
                <div class="review-value">${escapeHtml(String(display))}</div>
            </div>
        `);
    });

    if (!grid.children.length) {
        grid.innerHTML = '<div style="color:#9ca3af;font-size:13px;">Nenhum campo preenchido.</div>';
    }
}

// ── Submit (POST to real backend) ────────────────────────────────────────────

function wizardSubmit() {
    saveCurrentStep();
    const btn = document.getElementById('wCreateBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Criando...';

    // Build form data
    const fd = new FormData();
    fd.append('_token', '{{ csrf_token() }}');
    fd.append('name', state.name);
    fd.append('channel', state.channel);
    if (state.description) fd.append('description', state.description);
    if (state.trigger_keywords) fd.append('trigger_keywords', state.trigger_keywords);

    // Website-specific fields
    if (state.channel === 'website') {
        if (state.bot_name) fd.append('bot_name', state.bot_name);
        if (state.bot_avatar) fd.append('bot_avatar', state.bot_avatar);
        if (state.welcome_message) fd.append('welcome_message', state.welcome_message);
        fd.append('widget_type', state.widget_type || 'bubble');
        fd.append('widget_color', state.widget_color || '#0085f3');
    }

    // Template steps & variables
    if (state.template) {
        const tpl = TEMPLATES.find(t => t.id === state.template);
        if (tpl && tpl.steps) {
            fd.append('steps', JSON.stringify(tpl.steps));
            fd.append('template_variables', JSON.stringify(tpl.variables || []));
        }
    }

    fetch('{{ route("chatbot.flows.store") }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: fd,
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (ok && data.success) {
            toastr.success('Chatbot criado com sucesso!');
            window.location.href = data.redirect_url;
        } else {
            const msg = data.message || Object.values(data.errors || {}).flat().join(', ') || 'Erro ao criar chatbot.';
            toastr.error(msg);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Criar Chatbot';
        }
    })
    .catch(err => {
        console.error(err);
        toastr.error('Erro de conexão. Tente novamente.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Criar Chatbot';
    });
}

// ── Enter key ────────────────────────────────────────────────────────────────

document.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    if (e.target.tagName === 'TEXTAREA') return;
    const step = currentStepName();
    if (['channel', 'template'].includes(step)) return; // cards auto-advance
    e.preventDefault();
    if (currentIdx === stepOrder.length - 1) wizardSubmit();
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
