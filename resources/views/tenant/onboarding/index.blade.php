<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bem-vindo — Syncro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            background: #fff;
        }

        /* ── Layout split-screen ── */
        .onb-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Lado esquerdo — wizard */
        .onb-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 64px;
            min-width: 0;
            overflow-y: auto;
            position: relative;
        }

        .onb-brand {
            width: 100%;
            max-width: 480px;
            margin-bottom: 36px;
        }

        .onb-brand img {
            height: 34px;
            object-fit: contain;
        }

        .onb-form-wrap {
            width: 100%;
            max-width: 480px;
        }

        /* ── Progress dots ── */
        .onb-dots {
            display: flex;
            gap: 8px;
            margin-bottom: 32px;
        }

        .onb-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #E5E7EB;
            transition: all .3s;
        }

        .onb-dot.active {
            background: #3B82F6;
            width: 24px;
            border-radius: 4px;
        }

        .onb-dot.done {
            background: #93C5FD;
        }

        /* ── Step heading ── */
        .onb-step-label {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #3B82F6;
            margin-bottom: 8px;
        }

        .onb-title {
            font-size: 26px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 6px;
            line-height: 1.25;
        }

        .onb-subtitle {
            font-size: 14px;
            color: #6B7280;
            margin-bottom: 28px;
        }

        /* ── Form elements ── */
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #E5E7EB;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            color: #111827;
            outline: none;
            transition: border-color .2s;
            background: #fff;
        }

        .form-control:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.12);
        }

        /* ── Upload zone ── */
        .upload-zone {
            border: 2px dashed #D1D5DB;
            border-radius: 12px;
            padding: 28px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
            position: relative;
        }

        .upload-zone:hover, .upload-zone.dragover {
            border-color: #3B82F6;
            background: #EFF6FF;
        }

        .upload-zone input[type=file] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .upload-icon {
            font-size: 32px;
            color: #9CA3AF;
            margin-bottom: 8px;
        }

        .upload-text {
            font-size: 14px;
            color: #6B7280;
        }

        .upload-text span {
            color: #3B82F6;
            font-weight: 600;
            cursor: pointer;
        }

        .upload-preview {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 8px;
            margin-bottom: 8px;
            display: none;
        }

        .upload-preview.avatar-preview {
            border-radius: 50%;
            object-fit: cover;
        }

        /* ── Niche grid ── */
        .niche-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .niche-card {
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: border-color .2s, background .2s, box-shadow .2s;
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .niche-card:hover {
            border-color: #93C5FD;
            background: #F8FAFF;
        }

        .niche-card.selected {
            border-color: #3B82F6;
            background: #EFF6FF;
        }

        .niche-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #F3F4F6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #6B7280;
            flex-shrink: 0;
            transition: background .2s, color .2s;
        }

        .niche-card.selected .niche-card-icon {
            background: #DBEAFE;
            color: #3B82F6;
        }

        .niche-card-body { flex: 1; min-width: 0; }

        .niche-card-name {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 2px;
        }

        .niche-card-desc {
            font-size: 11px;
            color: #9CA3AF;
            line-height: 1.4;
        }

        .niche-check {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #3B82F6;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .niche-card.selected .niche-check {
            display: flex;
        }

        .niche-check i {
            font-size: 10px;
            color: #fff;
        }

        /* ── Preview step ── */
        .preview-block {
            border: 1.5px solid #E5E7EB;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 12px;
        }

        .preview-block-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #9CA3AF;
            margin-bottom: 10px;
        }

        .preview-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .preview-tag {
            background: #EFF6FF;
            color: #3B82F6;
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 12px;
            font-weight: 500;
        }

        .preview-stages {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .preview-stage {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #374151;
        }

        .preview-stage-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* ── Navigation buttons ── */
        .onb-nav {
            display: flex;
            gap: 12px;
            margin-top: 28px;
            align-items: center;
        }

        .btn-back {
            padding: 10px 20px;
            border: 1.5px solid #E5E7EB;
            border-radius: 10px;
            background: #fff;
            color: #6B7280;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: border-color .2s, color .2s;
        }

        .btn-back:hover {
            border-color: #9CA3AF;
            color: #374151;
        }

        .btn-next {
            flex: 1;
            padding: 12px 24px;
            background: #3B82F6;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s, transform .1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-next:hover { background: #2563EB; }
        .btn-next:active { transform: scale(.98); }
        .btn-next:disabled { background: #93C5FD; cursor: not-allowed; }

        .btn-next .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: none;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Steps ── */
        .onb-step { display: none; }
        .onb-step.active { display: block; }

        /* ── Fade transition ── */
        .onb-step.fade-in {
            animation: fadeIn .25s ease forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Right panel — image ── */
        .onb-right {
            flex: 1;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow: hidden;
        }

        .onb-right img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ── Error alert ── */
        .alert-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            color: #DC2626;
            margin-bottom: 16px;
            display: none;
        }

        .alert-error.show { display: block; }

        /* ── Skip button ── */
        .onb-skip {
            position: absolute;
            top: 24px;
            right: 24px;
            font-size: 13px;
            color: #9CA3AF;
            text-decoration: none;
            font-weight: 500;
            background: none;
            border: none;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: color .2s;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .onb-skip:hover {
            color: #6B7280;
            background: #F3F4F6;
        }

        /* ── Skip confirm modal ── */
        .skip-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .skip-modal-backdrop.show {
            display: flex;
        }

        .skip-modal {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            width: 100%;
            max-width: 400px;
            margin: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.15);
        }

        .skip-modal-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .skip-modal-body {
            font-size: 14px;
            color: #6B7280;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .skip-modal-actions {
            display: flex;
            gap: 10px;
        }

        .btn-skip-cancel {
            flex: 1;
            padding: 10px;
            border: 1.5px solid #E5E7EB;
            border-radius: 10px;
            background: #fff;
            color: #374151;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: border-color .2s;
        }

        .btn-skip-cancel:hover { border-color: #9CA3AF; }

        .btn-skip-confirm {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 10px;
            background: #F3F4F6;
            color: #374151;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
        }

        .btn-skip-confirm:hover { background: #E5E7EB; }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .onb-right { display: none; }
            .onb-left { padding: 32px 24px; justify-content: flex-start; }
        }

        @media (max-width: 480px) {
            .niche-grid { grid-template-columns: 1fr; }
            .onb-title { font-size: 22px; }
        }
    </style>
</head>
<body>
<div class="onb-wrapper">

    <!-- ── Lado esquerdo: wizard ── -->
    <div class="onb-left">

        <!-- Botão pular -->
        <button class="onb-skip" onclick="openSkipModal()" title="Pular configuração">
            Pular <i class="bi bi-skip-forward"></i>
        </button>

        <div class="onb-brand">
            <img src="{{ asset('images/logo-dark.png') }}" alt="Syncro" onerror="this.style.display='none'">
        </div>

        <div class="onb-form-wrap">

            <!-- Progress dots -->
            <div class="onb-dots" id="onbDots">
                <div class="onb-dot active" data-dot="1"></div>
                <div class="onb-dot" data-dot="2"></div>
                <div class="onb-dot" data-dot="3"></div>
                <div class="onb-dot" data-dot="4"></div>
            </div>

            <!-- Error alert -->
            <div class="alert-error" id="alertError"></div>

            <!-- ─────────────────────────────────────────────── -->
            <!-- STEP 1: Nome da empresa + logo -->
            <!-- ─────────────────────────────────────────────── -->
            <div class="onb-step active fade-in" id="step1">
                <div class="onb-step-label">Passo 1 de 4</div>
                <h1 class="onb-title">Vamos configurar sua empresa</h1>
                <p class="onb-subtitle">Comece pelo básico: nome e logo da sua empresa.</p>

                <div style="margin-bottom: 20px;">
                    <label class="form-label">Nome da empresa *</label>
                    <input
                        type="text"
                        class="form-control"
                        id="companyName"
                        placeholder="Ex: Imobiliária Silva & Filhos"
                        value="{{ $tenant->name ?? '' }}"
                        maxlength="150"
                    >
                </div>

                <div>
                    <label class="form-label">Logo da empresa <span style="font-weight:400;color:#9CA3AF">(opcional)</span></label>
                    <div class="upload-zone" id="logoZone">
                        <input type="file" id="logoInput" accept="image/*" onchange="handleLogoUpload(this)">
                        <img id="logoPreview" class="upload-preview" alt="Preview">
                        <div id="logoPlaceholder">
                            <div class="upload-icon"><i class="bi bi-image"></i></div>
                            <p class="upload-text"><span>Clique para enviar</span> ou arraste aqui</p>
                            <p style="font-size:12px;color:#D1D5DB;margin-top:4px">PNG, JPG até 10 MB</p>
                        </div>
                    </div>
                </div>

                <div class="onb-nav">
                    <button class="btn-next" onclick="goNext()">
                        Continuar <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────── -->
            <!-- STEP 2: Nicho de mercado -->
            <!-- ─────────────────────────────────────────────── -->
            <div class="onb-step" id="step2">
                <div class="onb-step-label">Passo 2 de 4</div>
                <h1 class="onb-title">Qual é o seu nicho de mercado?</h1>
                <p class="onb-subtitle">Vamos pré-configurar seu funil e etapas automaticamente.</p>

                <div class="niche-grid">
                    <div class="niche-card" data-niche="imobiliario" onclick="selectNiche('imobiliario')">
                        <div class="niche-card-icon"><i class="bi bi-building"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">Imobiliário</div>
                            <div class="niche-card-desc">Corretores, imobiliárias e incorporadoras</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>

                    <div class="niche-card" data-niche="estetica" onclick="selectNiche('estetica')">
                        <div class="niche-card-icon"><i class="bi bi-stars"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">Estética e Beleza</div>
                            <div class="niche-card-desc">Clínicas, salões e profissionais</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>

                    <div class="niche-card" data-niche="educacao" onclick="selectNiche('educacao')">
                        <div class="niche-card-icon"><i class="bi bi-book"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">Educação / Cursos</div>
                            <div class="niche-card-desc">Escolas, cursos online e presenciais</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>

                    <div class="niche-card" data-niche="saude" onclick="selectNiche('saude')">
                        <div class="niche-card-icon"><i class="bi bi-heart-pulse"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">Saúde / Clínicas</div>
                            <div class="niche-card-desc">Consultórios, clínicas e laboratórios</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>

                    <div class="niche-card" data-niche="varejo" onclick="selectNiche('varejo')">
                        <div class="niche-card-icon"><i class="bi bi-bag"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">Varejo / E-commerce</div>
                            <div class="niche-card-desc">Lojas físicas e online</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>

                    <div class="niche-card" data-niche="b2b" onclick="selectNiche('b2b')">
                        <div class="niche-card-icon"><i class="bi bi-briefcase"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">Serviços B2B</div>
                            <div class="niche-card-desc">Agências, consultorias e serviços</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>

                    <div class="niche-card" data-niche="tecnologia" onclick="selectNiche('tecnologia')">
                        <div class="niche-card-icon"><i class="bi bi-cpu"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">Tecnologia / SaaS</div>
                            <div class="niche-card-desc">Startups, SaaS e software</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>

                    <div class="niche-card" data-niche="outro" onclick="selectNiche('outro')">
                        <div class="niche-card-icon"><i class="bi bi-three-dots"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">Outro</div>
                            <div class="niche-card-desc">Qualquer outro segmento de mercado</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                </div>

                <div class="onb-nav">
                    <button class="btn-back" onclick="goBack()"><i class="bi bi-arrow-left"></i> Voltar</button>
                    <button class="btn-next" onclick="goNext()">
                        Continuar <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────── -->
            <!-- STEP 3: Foto de perfil -->
            <!-- ─────────────────────────────────────────────── -->
            <div class="onb-step" id="step3">
                <div class="onb-step-label">Passo 3 de 4</div>
                <h1 class="onb-title">Sua foto de perfil</h1>
                <p class="onb-subtitle">Adicione uma foto para personalizar sua conta.</p>

                <div style="display:flex;flex-direction:column;align-items:center;gap:16px;">
                    <div class="upload-zone" id="avatarZone" style="width:160px;height:160px;border-radius:50%;padding:0;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;">
                        <input type="file" id="avatarInput" accept="image/*" onchange="handleAvatarUpload(this)" style="border-radius:50%;">
                        <img id="avatarPreview" class="upload-preview avatar-preview" alt="Preview" style="width:100%;height:100%;border-radius:50%;display:none;">
                        <div id="avatarPlaceholder" style="text-align:center;padding:16px;">
                            <div style="font-size:40px;color:#9CA3AF;margin-bottom:4px;"><i class="bi bi-person-circle"></i></div>
                            <p style="font-size:12px;color:#9CA3AF;">Clique para<br>adicionar foto</p>
                        </div>
                    </div>
                    <p style="font-size:13px;color:#6B7280;text-align:center;">PNG, JPG até 10 MB — opcional</p>
                </div>

                <div class="onb-nav">
                    <button class="btn-back" onclick="goBack()"><i class="bi bi-arrow-left"></i> Voltar</button>
                    <button class="btn-next" onclick="goNext()">
                        Continuar <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────── -->
            <!-- STEP 4: Preview e finalizar -->
            <!-- ─────────────────────────────────────────────── -->
            <div class="onb-step" id="step4">
                <div class="onb-step-label">Passo 4 de 4</div>
                <h1 class="onb-title">Tudo pronto!</h1>
                <p class="onb-subtitle">Veja o que criamos para você com base no seu nicho:</p>

                <div class="preview-block">
                    <div class="preview-block-title"><i class="bi bi-funnel" style="margin-right:6px;color:#3B82F6;"></i>Funil criado</div>
                    <div id="previewPipelineName" style="font-size:14px;font-weight:600;color:#111827;margin-bottom:10px;"></div>
                    <div class="preview-stages" id="previewStages"></div>
                </div>

                <div class="preview-block">
                    <div class="preview-block-title"><i class="bi bi-tags" style="margin-right:6px;color:#3B82F6;"></i>Tags de conversa</div>
                    <div class="preview-tags" id="previewTags"></div>
                </div>

                <div class="preview-block">
                    <div class="preview-block-title"><i class="bi bi-x-circle" style="margin-right:6px;color:#3B82F6;"></i>Motivos de perda</div>
                    <div id="previewLossReasons" style="display:flex;flex-direction:column;gap:4px;"></div>
                </div>

                <div class="onb-nav">
                    <button class="btn-back" onclick="goBack()"><i class="bi bi-arrow-left"></i> Voltar</button>
                    <button class="btn-next" id="btnFinish" onclick="submitOnboarding()">
                        <span class="spinner" id="submitSpinner"></span>
                        <span id="btnFinishText">Finalizar e começar <i class="bi bi-rocket-takeoff"></i></span>
                    </button>
                </div>
            </div>

        </div><!-- /onb-form-wrap -->
    </div><!-- /onb-left -->

    <!-- ── Lado direito: imagem ── -->
    <div class="onb-right">
        <img src="{{ asset('images/split-screen-onboarding.jpg') }}" alt="Onboarding">
    </div>

</div><!-- /onb-wrapper -->

<!-- Modal de confirmação para pular onboarding -->
<div class="skip-modal-backdrop" id="skipModal" onclick="closeSkipModal(event)">
    <div class="skip-modal">
        <div class="skip-modal-title">Pular configuração?</div>
        <div class="skip-modal-body">
            Você pode montar seu funil e configurar tags manualmente depois em <strong>Configurações</strong>.<br><br>
            Esta tela não vai aparecer novamente.
        </div>
        <div class="skip-modal-actions">
            <button class="btn-skip-cancel" onclick="closeSkipModal()">Voltar</button>
            <form method="POST" action="{{ route('onboarding.skip') }}" style="flex:1;">
                @csrf
                <button type="submit" class="btn-skip-confirm" style="width:100%;">
                    Sim, pular
                </button>
            </form>
        </div>
    </div>
</div>

<script>
const NICHE_DATA = {
    imobiliario: {
        pipeline_name: 'Funil Imobiliário',
        stages: [
            { name: 'Novo Lead',        color: '#6B7280' },
            { name: 'Visita Agendada',  color: '#3B82F6' },
            { name: 'Proposta Enviada', color: '#F59E0B' },
            { name: 'Negociação',       color: '#8B5CF6' },
            { name: 'Fechado',          color: '#10B981' },
            { name: 'Perdido',          color: '#EF4444' },
        ],
        tags: ['Comprador', 'Locatário', 'Investidor', 'Urgente', 'Alto Padrão'],
        loss_reasons: ['Preço alto', 'Não encontrou o imóvel ideal', 'Financiamento negado', 'Comprou com outro corretor', 'Sem interesse'],
    },
    estetica: {
        pipeline_name: 'Agendamentos',
        stages: [
            { name: 'Lead Novo',          color: '#6B7280' },
            { name: 'Consulta Agendada',  color: '#3B82F6' },
            { name: 'Consulta Realizada', color: '#F59E0B' },
            { name: 'Proposta Enviada',   color: '#8B5CF6' },
            { name: 'Fechado',            color: '#10B981' },
            { name: 'Perdido',            color: '#EF4444' },
        ],
        tags: ['Facial', 'Corporal', 'Capilar', 'Retorno', 'Novo Cliente'],
        loss_reasons: ['Preço alto', 'Sem tempo', 'Optou por outra clínica', 'Não respondeu', 'Mudou de ideia'],
    },
    educacao: {
        pipeline_name: 'Matrículas',
        stages: [
            { name: 'Interessado',        color: '#6B7280' },
            { name: 'Apresentação Feita', color: '#3B82F6' },
            { name: 'Proposta Enviada',   color: '#F59E0B' },
            { name: 'Matriculado',        color: '#10B981' },
            { name: 'Desistiu',           color: '#EF4444' },
        ],
        tags: ['Curso Online', 'Presencial', 'Bolsa', 'Graduação', 'Pós-Graduação'],
        loss_reasons: ['Preço alto', 'Sem tempo', 'Optou por outro curso', 'Não respondeu', 'Não passou no processo'],
    },
    saude: {
        pipeline_name: 'Pacientes',
        stages: [
            { name: 'Primeiro Contato',       color: '#6B7280' },
            { name: 'Consulta Agendada',      color: '#3B82F6' },
            { name: 'Avaliação Realizada',    color: '#F59E0B' },
            { name: 'Proposta de Tratamento', color: '#8B5CF6' },
            { name: 'Em Tratamento',          color: '#10B981' },
            { name: 'Perdido',                color: '#EF4444' },
        ],
        tags: ['Plano de Saúde', 'Particular', 'Urgente', 'Retorno', 'Novo Paciente'],
        loss_reasons: ['Plano não aceito', 'Preço alto', 'Optou por outra clínica', 'Não respondeu', 'Falta de transporte'],
    },
    varejo: {
        pipeline_name: 'Vendas',
        stages: [
            { name: 'Carrinho Abandonado',  color: '#6B7280' },
            { name: 'Interesse Confirmado', color: '#3B82F6' },
            { name: 'Pedido Realizado',     color: '#F59E0B' },
            { name: 'Em Processamento',     color: '#8B5CF6' },
            { name: 'Entregue',             color: '#10B981' },
            { name: 'Devolvido',            color: '#EF4444' },
        ],
        tags: ['Cliente Novo', 'Recorrente', 'VIP', 'Atacado', 'Promoção'],
        loss_reasons: ['Preço alto', 'Frete caro', 'Produto indisponível', 'Optou por concorrente', 'Desistiu no checkout'],
    },
    b2b: {
        pipeline_name: 'Oportunidades B2B',
        stages: [
            { name: 'Prospecção',   color: '#6B7280' },
            { name: 'Qualificação', color: '#3B82F6' },
            { name: 'Proposta',     color: '#F59E0B' },
            { name: 'Negociação',   color: '#8B5CF6' },
            { name: 'Fechado',      color: '#10B981' },
            { name: 'Perdido',      color: '#EF4444' },
        ],
        tags: ['Pequena Empresa', 'Média Empresa', 'Grande Empresa', 'Urgente', 'Parceria'],
        loss_reasons: ['Preço alto', 'Sem orçamento', 'Optou por concorrente', 'Projeto cancelado', 'Timing errado'],
    },
    tecnologia: {
        pipeline_name: 'Demo e Vendas SaaS',
        stages: [
            { name: 'Lead Novo',      color: '#6B7280' },
            { name: 'Demo Agendada',  color: '#3B82F6' },
            { name: 'Demo Realizada', color: '#F59E0B' },
            { name: 'Trial Ativo',    color: '#8B5CF6' },
            { name: 'Fechado',        color: '#10B981' },
            { name: 'Churned',        color: '#EF4444' },
        ],
        tags: ['Startup', 'Corporativo', 'Trial', 'Demo Solicitada', 'Enterprise'],
        loss_reasons: ['Sem budget', 'Optou por concorrente', 'Feature não disponível', 'Projeto pausado', 'Saiu do trial sem converter'],
    },
    outro: {
        pipeline_name: 'Funil de Vendas',
        stages: [
            { name: 'Novo Lead',        color: '#6B7280' },
            { name: 'Em Contato',       color: '#3B82F6' },
            { name: 'Proposta Enviada', color: '#F59E0B' },
            { name: 'Negociação',       color: '#8B5CF6' },
            { name: 'Fechado',          color: '#10B981' },
            { name: 'Perdido',          color: '#EF4444' },
        ],
        tags: ['Quente', 'Morno', 'Frio', 'Prioritário', 'Retorno'],
        loss_reasons: ['Preço alto', 'Sem interesse', 'Sem retorno', 'Optou por concorrente', 'Timing errado'],
    },
};

let currentStep    = 1;
const totalSteps   = 4;
let selectedNiche  = null;
let logoFile       = null;
let avatarFile     = null;

function goNext() {
    hideError();

    if (currentStep === 1) {
        const name = document.getElementById('companyName').value.trim();
        if (!name) {
            showError('Por favor, informe o nome da empresa.');
            document.getElementById('companyName').focus();
            return;
        }
    }

    if (currentStep === 2) {
        if (!selectedNiche) {
            showError('Por favor, selecione um nicho de mercado.');
            return;
        }
        buildPreview(selectedNiche);
    }

    if (currentStep >= totalSteps) return;

    navigateTo(currentStep + 1);
}

function goBack() {
    if (currentStep <= 1) return;
    hideError();
    navigateTo(currentStep - 1);
}

function navigateTo(step) {
    document.getElementById('step' + currentStep).classList.remove('active', 'fade-in');
    currentStep = step;
    const el = document.getElementById('step' + currentStep);
    el.classList.add('active');
    // Trigger reflow for animation restart
    void el.offsetWidth;
    el.classList.add('fade-in');
    updateDots();
}

function updateDots() {
    document.querySelectorAll('.onb-dot').forEach((dot, i) => {
        const n = i + 1;
        dot.classList.remove('active', 'done');
        if (n === currentStep) dot.classList.add('active');
        else if (n < currentStep) dot.classList.add('done');
    });
}

function selectNiche(key) {
    selectedNiche = key;
    document.querySelectorAll('.niche-card').forEach(card => {
        card.classList.toggle('selected', card.dataset.niche === key);
    });
}

function handleLogoUpload(input) {
    if (!input.files[0]) return;
    logoFile = input.files[0];
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('logoPreview');
        preview.src = e.target.result;
        preview.style.display = 'block';
        document.getElementById('logoPlaceholder').style.display = 'none';
    };
    reader.readAsDataURL(logoFile);
}

function handleAvatarUpload(input) {
    if (!input.files[0]) return;
    avatarFile = input.files[0];
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('avatarPreview');
        preview.src = e.target.result;
        preview.style.display = 'block';
        document.getElementById('avatarPlaceholder').style.display = 'none';
    };
    reader.readAsDataURL(avatarFile);
}

function buildPreview(niche) {
    const data = NICHE_DATA[niche] || NICHE_DATA['outro'];

    document.getElementById('previewPipelineName').textContent = data.pipeline_name;

    const stagesEl = document.getElementById('previewStages');
    stagesEl.innerHTML = data.stages.map(s =>
        `<div class="preview-stage">
            <span class="preview-stage-dot" style="background:${s.color}"></span>
            <span>${s.name}</span>
        </div>`
    ).join('');

    const tagsEl = document.getElementById('previewTags');
    tagsEl.innerHTML = data.tags.map(t =>
        `<span class="preview-tag">${t}</span>`
    ).join('');

    const lossEl = document.getElementById('previewLossReasons');
    lossEl.innerHTML = data.loss_reasons.map(r =>
        `<div style="font-size:13px;color:#6B7280;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-dash" style="color:#D1D5DB;"></i>${r}
        </div>`
    ).join('');
}

async function submitOnboarding() {
    const btn     = document.getElementById('btnFinish');
    const spinner = document.getElementById('submitSpinner');
    const btnText = document.getElementById('btnFinishText');

    const resetBtn = () => {
        btn.disabled          = false;
        spinner.style.display = 'none';
        btnText.style.display = '';
    };

    btn.disabled          = true;
    spinner.style.display = 'block';
    btnText.style.display = 'none';

    try {
        const formData = new FormData();
        formData.append('_token',       document.querySelector('meta[name="csrf-token"]').content);
        formData.append('company_name', document.getElementById('companyName').value.trim());
        formData.append('niche',        selectedNiche || 'outro');
        if (logoFile)   formData.append('logo',   logoFile);
        if (avatarFile) formData.append('avatar', avatarFile);

        const resp = await fetch('{{ route('onboarding.complete') }}', {
            method:  'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body:    formData,
        });

        // Lê como texto primeiro para poder inspecionar em caso de erro HTML
        const rawText = await resp.text();

        let json;
        try {
            json = JSON.parse(rawText);
        } catch (_) {
            // Servidor retornou HTML (erro 500, redirect, etc.)
            console.error('[Onboarding] Resposta não-JSON (HTTP ' + resp.status + '):', rawText.substring(0, 800));
            showError('Erro do servidor (HTTP ' + resp.status + '). Verifique o console para detalhes.');
            resetBtn();
            return;
        }

        if (json.success) {
            window.location.href = json.redirect;
        } else {
            const msgs = json.errors
                ? Object.values(json.errors).flat().join(' ')
                : (json.message || 'Ocorreu um erro. Tente novamente.');
            showError(msgs);
            resetBtn();
        }
    } catch (e) {
        console.error('[Onboarding] Fetch error:', e);
        showError('Erro de conexão. Verifique sua internet e tente novamente.');
        resetBtn();
    }
}

function showError(msg) {
    const el = document.getElementById('alertError');
    el.textContent = msg;
    el.classList.add('show');
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function hideError() {
    document.getElementById('alertError').classList.remove('show');
}

function openSkipModal() {
    document.getElementById('skipModal').classList.add('show');
}

function closeSkipModal(e) {
    if (!e || e.target === document.getElementById('skipModal')) {
        document.getElementById('skipModal').classList.remove('show');
    }
}

// Drag-and-drop for logo zone
const logoZone = document.getElementById('logoZone');
logoZone.addEventListener('dragover', e => { e.preventDefault(); logoZone.classList.add('dragover'); });
logoZone.addEventListener('dragleave', () => logoZone.classList.remove('dragover'));
logoZone.addEventListener('drop', e => {
    e.preventDefault();
    logoZone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        logoFile = file;
        const reader = new FileReader();
        reader.onload = ev => {
            document.getElementById('logoPreview').src = ev.target.result;
            document.getElementById('logoPreview').style.display = 'block';
            document.getElementById('logoPlaceholder').style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});
</script>
</body>
</html>
