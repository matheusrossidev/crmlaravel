<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sugira Melhorias — Syncro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; min-height: 100vh; background: #f4f6fb; display: flex; flex-direction: column; align-items: center; padding: 40px 20px; }

        .fb-logo { margin-bottom: 32px; }
        .fb-logo img { height: 32px; }

        .fb-card { background: #fff; border-radius: 20px; border: 1px solid #e8eaf0; width: 100%; max-width: 560px; padding: 36px 32px; box-shadow: 0 4px 24px rgba(0,0,0,.04); }

        /* Progress dots */
        .fb-dots { display: flex; align-items: center; gap: 6px; margin-bottom: 24px; }
        .fb-dot { width: 8px; height: 8px; border-radius: 50%; background: #e5e7eb; transition: all .2s; }
        .fb-dot.active { background: #0085f3; width: 20px; border-radius: 4px; }
        .fb-dot.done { background: #0085f3; opacity: .4; }

        .fb-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 22px; font-weight: 700; color: #1a1d23; margin-bottom: 4px; }
        .fb-sub { font-size: 14px; color: #677489; margin-bottom: 24px; }

        /* Steps */
        .fb-step { display: none; }
        .fb-step.active { display: block; }

        /* Type cards */
        .type-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .type-card { padding: 16px; border: 1.5px solid #e5e7eb; border-radius: 12px; cursor: pointer; transition: all .15s; text-align: center; }
        .type-card:hover { border-color: #0085f3; background: #eff6ff; }
        .type-card.selected { border-color: #0085f3; background: #e0f0ff; box-shadow: 0 0 0 3px rgba(0,133,243,.12); }
        .type-card-icon { font-size: 24px; margin-bottom: 6px; display: block; color: #0085f3; }
        .type-card-label { font-size: 13px; font-weight: 600; color: #1a1d23; }
        .type-card-desc { font-size: 11px; color: #6b7280; margin-top: 2px; }

        /* Form fields */
        .fb-field { margin-bottom: 16px; }
        .fb-field label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .fb-field select, .fb-field input[type="text"], .fb-field textarea {
            width: 100%; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 10px;
            font-size: 14px; font-family: inherit; outline: none; color: #1a1d23; background: #fff;
            transition: border-color .15s;
        }
        .fb-field select:focus, .fb-field input:focus, .fb-field textarea:focus { border-color: #0085f3; box-shadow: 0 0 0 3px rgba(0,133,243,.08); }
        .fb-field textarea { resize: vertical; min-height: 120px; }

        /* Impact cards */
        .impact-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
        @media (max-width: 480px) { .impact-grid { grid-template-columns: 1fr 1fr; } }
        .impact-card { padding: 12px 8px; border: 1.5px solid #e5e7eb; border-radius: 10px; cursor: pointer; text-align: center; transition: all .15s; }
        .impact-card:hover { border-color: #0085f3; }
        .impact-card.selected { border-color: #0085f3; background: #e0f0ff; }
        .impact-card-label { font-size: 12px; font-weight: 600; color: #1a1d23; }
        .impact-card-desc { font-size: 10px; color: #6b7280; margin-top: 2px; }

        /* Upload */
        .fb-upload { border: 2px dashed #e5e7eb; border-radius: 12px; padding: 24px; text-align: center; cursor: pointer; transition: all .15s; }
        .fb-upload:hover { border-color: #0085f3; background: #eff6ff; }
        .fb-upload.has-file { border-color: #0085f3; background: #e0f0ff; }
        .fb-upload i { font-size: 28px; color: #d1d5db; display: block; margin-bottom: 8px; }
        .fb-upload.has-file i { color: #0085f3; }
        .fb-upload-text { font-size: 13px; color: #6b7280; }
        .fb-upload-name { font-size: 12px; font-weight: 600; color: #0085f3; margin-top: 4px; }

        /* Slider */
        .fb-slider { width: 100%; -webkit-appearance: none; height: 6px; border-radius: 3px; background: #e5e7eb; outline: none; }
        .fb-slider::-webkit-slider-thumb { -webkit-appearance: none; width: 20px; height: 20px; border-radius: 50%; background: #0085f3; cursor: pointer; }
        .fb-slider-labels { display: flex; justify-content: space-between; font-size: 11px; color: #6b7280; margin-top: 6px; }

        /* Toggle */
        .fb-toggle { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .fb-toggle input { width: 18px; height: 18px; accent-color: #0085f3; }
        .fb-toggle label { font-size: 13px; font-weight: 600; color: #374151; cursor: pointer; }

        /* Buttons */
        .fb-btns { display: flex; gap: 10px; margin-top: 24px; }
        .fb-btn-back { padding: 10px 20px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; color: #374151; font-family: inherit; }
        .fb-btn-next { padding: 10px 24px; background: #0085f3; color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; margin-left: auto; transition: background .12s; }
        .fb-btn-next:hover { background: #0070d1; }
        .fb-btn-next:disabled { opacity: .5; cursor: not-allowed; }

        /* Success */
        .fb-success { text-align: center; padding: 20px 0; }
        .fb-success i { font-size: 52px; color: #0085f3; display: block; margin-bottom: 16px; }
        .fb-success h2 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 22px; font-weight: 700; color: #1a1d23; margin-bottom: 8px; }
        .fb-success p { font-size: 14px; color: #6b7280; margin-bottom: 24px; line-height: 1.6; }
        .fb-success a { display: inline-flex; align-items: center; gap: 6px; padding: 10px 24px; background: #0085f3; color: #fff; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; }

        .fb-footer { margin-top: 24px; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="fb-logo"><img src="{{ asset('images/logo.png') }}" alt="Syncro"></div>

    @if(session('success'))
        <div class="fb-card">
            <div class="fb-success">
                <i class="bi bi-check-circle-fill"></i>
                <h2>Obrigado pelo feedback!</h2>
                <p>Sua sugestão foi enviada com sucesso. Nossa equipe vai analisar e, se necessário, entraremos em contato.</p>
                <a href="{{ route('inicio') }}"><i class="bi bi-arrow-left"></i> Voltar ao CRM</a>
            </div>
        </div>
    @else
        <div class="fb-card">
            <div class="fb-dots">
                <div class="fb-dot active" id="dot-1"></div>
                <div class="fb-dot" id="dot-2"></div>
                <div class="fb-dot" id="dot-3"></div>
            </div>

            <h1 class="fb-title" id="stepTitle">O que você quer sugerir?</h1>
            <p class="fb-sub" id="stepSub">Selecione o tipo da sua sugestão.</p>

            <form method="POST" action="{{ route('feedback.store') }}" enctype="multipart/form-data" id="fbForm">
                @csrf
                <input type="hidden" name="url_origin" value="{{ $urlOrigin }}">
                <input type="hidden" name="type" id="fType" value="">
                <input type="hidden" name="impact" id="fImpact" value="">

                {{-- ═══ STEP 1 — Tipo ═══ --}}
                <div class="fb-step active" id="step-1">
                    <div class="type-grid">
                        <div class="type-card" onclick="selectType('new_feature', this)">
                            <span class="type-card-icon">✦</span>
                            <div class="type-card-label">Nova funcionalidade</div>
                            <div class="type-card-desc">Algo que não existe ainda</div>
                        </div>
                        <div class="type-card" onclick="selectType('improvement', this)">
                            <span class="type-card-icon"><i class="bi bi-wrench"></i></span>
                            <div class="type-card-label">Melhoria existente</div>
                            <div class="type-card-desc">Tornar algo melhor</div>
                        </div>
                        <div class="type-card" onclick="selectType('bug', this)">
                            <span class="type-card-icon"><i class="bi bi-exclamation-triangle"></i></span>
                            <div class="type-card-label">Problema / Bug</div>
                            <div class="type-card-desc">Algo que não funciona direito</div>
                        </div>
                        <div class="type-card" onclick="selectType('ux_ui', this)">
                            <span class="type-card-icon"><i class="bi bi-palette"></i></span>
                            <div class="type-card-label">Interface / UX</div>
                            <div class="type-card-desc">Visual, navegação, usabilidade</div>
                        </div>
                        <div class="type-card" onclick="selectType('integration', this)">
                            <span class="type-card-icon"><i class="bi bi-link-45deg"></i></span>
                            <div class="type-card-label">Integração</div>
                            <div class="type-card-desc">Conectar com outra ferramenta</div>
                        </div>
                        <div class="type-card" onclick="selectType('other', this)">
                            <span class="type-card-icon"><i class="bi bi-chat-dots"></i></span>
                            <div class="type-card-label">Outro</div>
                            <div class="type-card-desc">Qualquer outra coisa</div>
                        </div>
                    </div>
                    <div class="fb-btns">
                        <a href="{{ route('inicio') }}" class="fb-btn-back"><i class="bi bi-arrow-left"></i> Cancelar</a>
                        <button type="button" class="fb-btn-next" id="btn1" disabled onclick="goStep(2)">Continuar <i class="bi bi-arrow-right"></i></button>
                    </div>
                </div>

                {{-- ═══ STEP 2 — Detalhes ═══ --}}
                <div class="fb-step" id="step-2">
                    <div class="fb-field">
                        <label>Área relacionada</label>
                        <select name="area">
                            <option value="">Selecione...</option>
                            <option value="crm">CRM / Kanban</option>
                            <option value="chat">Chat / Inbox</option>
                            <option value="automations">Automações</option>
                            <option value="sequences">Sequências</option>
                            <option value="ai_agents">Agentes de IA</option>
                            <option value="chatbot">Chatbot Builder</option>
                            <option value="reports">Relatórios</option>
                            <option value="goals">Metas</option>
                            <option value="settings">Configurações</option>
                            <option value="onboarding">Onboarding</option>
                            <option value="other">Outro</option>
                        </select>
                    </div>
                    <div class="fb-field">
                        <label>Título da sugestão *</label>
                        <input type="text" name="title" id="fTitle" maxlength="100" placeholder="Resumo em uma frase" required>
                    </div>
                    <div class="fb-field">
                        <label>Descreva sua sugestão *</label>
                        <textarea name="description" id="fDesc" maxlength="5000" placeholder="Como funcionaria? Qual problema resolve?" required></textarea>
                    </div>
                    <div class="fb-field">
                        <label>Impacto pra você</label>
                        <div class="impact-grid">
                            <div class="impact-card" onclick="selectImpact('blocker', this)">
                                <div class="impact-card-label" style="color:#dc2626;">Bloqueante</div>
                                <div class="impact-card-desc">Impede meu trabalho</div>
                            </div>
                            <div class="impact-card" onclick="selectImpact('high', this)">
                                <div class="impact-card-label" style="color:#f59e0b;">Alto</div>
                                <div class="impact-card-desc">Usaria todo dia</div>
                            </div>
                            <div class="impact-card" onclick="selectImpact('medium', this)">
                                <div class="impact-card-label" style="color:#0085f3;">Médio</div>
                                <div class="impact-card-desc">Seria útil</div>
                            </div>
                            <div class="impact-card" onclick="selectImpact('low', this)">
                                <div class="impact-card-label" style="color:#6b7280;">Baixo</div>
                                <div class="impact-card-desc">Seria legal ter</div>
                            </div>
                        </div>
                    </div>
                    <div class="fb-btns">
                        <button type="button" class="fb-btn-back" onclick="goStep(1)"><i class="bi bi-arrow-left"></i> Voltar</button>
                        <button type="button" class="fb-btn-next" id="btn2" onclick="goStep(3)">Continuar <i class="bi bi-arrow-right"></i></button>
                    </div>
                </div>

                {{-- ═══ STEP 3 — Contexto ═══ --}}
                <div class="fb-step" id="step-3">
                    <div class="fb-field">
                        <label>Evidência (print ou imagem)</label>
                        <div class="fb-upload" id="uploadZone" onclick="document.getElementById('fEvidence').click()">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <div class="fb-upload-text">Clique ou arraste uma imagem aqui</div>
                            <div class="fb-upload-name" id="uploadName" style="display:none;"></div>
                        </div>
                        <input type="file" name="evidence" id="fEvidence" accept="image/*" style="display:none;" onchange="handleFile(this)">
                    </div>

                    <div class="fb-field">
                        <label>Prioridade percebida</label>
                        <input type="range" name="priority" class="fb-slider" min="1" max="5" value="3" id="fPriority">
                        <div class="fb-slider-labels">
                            <span>Seria legal</span>
                            <span>Preciso muito</span>
                        </div>
                    </div>

                    <div class="fb-field" style="margin-top:16px;">
                        <div class="fb-toggle">
                            <input type="checkbox" name="can_contact" value="1" id="fContact">
                            <label for="fContact">Posso ser contatado sobre isso</label>
                        </div>
                        <div style="font-size:12px;color:#9ca3af;margin-top:4px;">E-mail: {{ auth()->user()->email }}</div>
                    </div>

                    <div class="fb-btns">
                        <button type="button" class="fb-btn-back" onclick="goStep(2)"><i class="bi bi-arrow-left"></i> Voltar</button>
                        <button type="submit" class="fb-btn-next" id="btnSubmit"><i class="bi bi-send"></i> Enviar sugestão</button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <div class="fb-footer">Syncro CRM — Seu feedback faz a diferença.</div>

<script>
const STEPS = {
    1: { title: 'O que você quer sugerir?', sub: 'Selecione o tipo da sua sugestão.' },
    2: { title: 'Conte mais', sub: 'Detalhe sua sugestão para entendermos melhor.' },
    3: { title: 'Contexto final', sub: 'Informações opcionais para ajudar na análise.' },
};

const PLACEHOLDERS = {
    new_feature: 'Como funcionaria? Qual problema resolve?',
    improvement: 'O que está ruim hoje? Como deveria ser?',
    bug: 'O que aconteceu? O que você esperava que acontecesse?',
    ux_ui: 'O que te incomoda? Como deveria ficar?',
    integration: 'Qual ferramenta? Como deveria funcionar?',
    other: 'Descreva sua sugestão...',
};

function goStep(n) {
    if (n === 2 && !document.getElementById('fType').value) return;
    document.querySelectorAll('.fb-step').forEach(s => s.classList.remove('active'));
    document.getElementById('step-' + n).classList.add('active');
    document.getElementById('stepTitle').textContent = STEPS[n].title;
    document.getElementById('stepSub').textContent = STEPS[n].sub;
    for (let i = 1; i <= 3; i++) {
        const dot = document.getElementById('dot-' + i);
        dot.className = 'fb-dot' + (i === n ? ' active' : (i < n ? ' done' : ''));
    }
}

function selectType(type, el) {
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('fType').value = type;
    document.getElementById('btn1').disabled = false;
    document.getElementById('fDesc').placeholder = PLACEHOLDERS[type] || '';
}

function selectImpact(impact, el) {
    document.querySelectorAll('.impact-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('fImpact').value = impact;
}

function handleFile(input) {
    const zone = document.getElementById('uploadZone');
    const nameEl = document.getElementById('uploadName');
    if (input.files.length) {
        zone.classList.add('has-file');
        nameEl.textContent = input.files[0].name;
        nameEl.style.display = 'block';
    } else {
        zone.classList.remove('has-file');
        nameEl.style.display = 'none';
    }
}

// Drag and drop
const zone = document.getElementById('uploadZone');
if (zone) {
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.borderColor = '#0085f3'; });
    zone.addEventListener('dragleave', () => { zone.style.borderColor = ''; });
    zone.addEventListener('drop', e => {
        e.preventDefault(); zone.style.borderColor = '';
        const input = document.getElementById('fEvidence');
        input.files = e.dataTransfer.files;
        handleFile(input);
    });
}
</script>
</body>
</html>
