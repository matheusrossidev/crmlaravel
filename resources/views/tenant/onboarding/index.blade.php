<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include("partials._google-analytics")
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('onboarding.page_title') }}</title>
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

        .onb-section-label {
            font-size: 11px;
            font-weight: 700;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 10px;
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
            border-radius: 100px;
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
            background: #0085f3;
            color: #fff;
            border: none;
            border-radius: 100px;
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

        .btn-next:hover { background: #0070d1; }
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
        .spin { animation: spin 1s linear infinite; display: inline-block; }

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
            border-radius: 100px;
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
            border-radius: 100px;
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
        <button class="onb-skip" onclick="openSkipModal()" title="{{ __('onboarding.skip_button_title') }}">
            {{ __('onboarding.skip_button') }} <i class="bi bi-skip-forward"></i>
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
                <div class="onb-dot" data-dot="5"></div>
                <div class="onb-dot" data-dot="6"></div>
            </div>

            <!-- Error alert -->
            <div class="alert-error" id="alertError"></div>

            <!-- ─────────────────────────────────────────────── -->
            <!-- STEP 1: Nome da empresa + logo -->
            <!-- ─────────────────────────────────────────────── -->
            <div class="onb-step active fade-in" id="step1">
                <div class="onb-step-label">{{ __('onboarding.step_1_of_6') }}</div>
                <h1 class="onb-title">{{ __('onboarding.step1_title') }}</h1>
                <p class="onb-subtitle">{{ __('onboarding.step1_subtitle') }}</p>

                <div style="margin-bottom: 20px;">
                    <label class="form-label">{{ __('onboarding.company_name_label') }}</label>
                    <input
                        type="text"
                        class="form-control"
                        id="companyName"
                        placeholder="{{ __('onboarding.company_name_placeholder') }}"
                        value="{{ $tenant->name ?? '' }}"
                        maxlength="150"
                    >
                </div>

                <div>
                    <label class="form-label">{{ __('onboarding.logo_label') }} <span style="font-weight:400;color:#9CA3AF">{{ __('onboarding.logo_optional') }}</span></label>
                    <div class="upload-zone" id="logoZone">
                        <input type="file" id="logoInput" accept="image/*" onchange="handleLogoUpload(this)">
                        <img id="logoPreview" class="upload-preview" alt="Preview">
                        <div id="logoPlaceholder">
                            <div class="upload-icon"><i class="bi bi-image"></i></div>
                            <p class="upload-text"><span>{{ __('onboarding.upload_click') }}</span> {{ __('onboarding.upload_drag') }}</p>
                            <p style="font-size:12px;color:#D1D5DB;margin-top:4px">{{ __('onboarding.upload_hint') }}</p>
                        </div>
                    </div>
                </div>

                <div class="onb-nav">
                    <button class="btn-next" onclick="goNext()">
                        {{ __('onboarding.continue') }} <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────── -->
            <!-- STEP 2: Nicho de mercado -->
            <!-- ─────────────────────────────────────────────── -->
            <div class="onb-step" id="step2">
                <div class="onb-step-label">{{ __('onboarding.step_2_of_6') }}</div>
                <h1 class="onb-title">{{ __('onboarding.step2_title') }}</h1>
                <p class="onb-subtitle">{{ __('onboarding.step2_subtitle') }}</p>

                <div class="niche-grid">
                    <div class="niche-card" data-niche="imobiliario" onclick="selectNiche('imobiliario')">
                        <div class="niche-card-icon"><i class="bi bi-building"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.niche_imobiliario') }}</div>
                            <div class="niche-card-desc">{{ __('onboarding.niche_imobiliario_desc') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>

                    <div class="niche-card" data-niche="estetica" onclick="selectNiche('estetica')">
                        <div class="niche-card-icon"><i class="bi bi-stars"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.niche_estetica') }}</div>
                            <div class="niche-card-desc">{{ __('onboarding.niche_estetica_desc') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>

                    <div class="niche-card" data-niche="educacao" onclick="selectNiche('educacao')">
                        <div class="niche-card-icon"><i class="bi bi-book"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.niche_educacao') }}</div>
                            <div class="niche-card-desc">{{ __('onboarding.niche_educacao_desc') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>

                    <div class="niche-card" data-niche="saude" onclick="selectNiche('saude')">
                        <div class="niche-card-icon"><i class="bi bi-heart-pulse"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.niche_saude') }}</div>
                            <div class="niche-card-desc">{{ __('onboarding.niche_saude_desc') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>

                    <div class="niche-card" data-niche="varejo" onclick="selectNiche('varejo')">
                        <div class="niche-card-icon"><i class="bi bi-bag"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.niche_varejo') }}</div>
                            <div class="niche-card-desc">{{ __('onboarding.niche_varejo_desc') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>

                    <div class="niche-card" data-niche="b2b" onclick="selectNiche('b2b')">
                        <div class="niche-card-icon"><i class="bi bi-briefcase"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.niche_b2b') }}</div>
                            <div class="niche-card-desc">{{ __('onboarding.niche_b2b_desc') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>

                    <div class="niche-card" data-niche="tecnologia" onclick="selectNiche('tecnologia')">
                        <div class="niche-card-icon"><i class="bi bi-cpu"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.niche_tecnologia') }}</div>
                            <div class="niche-card-desc">{{ __('onboarding.niche_tecnologia_desc') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>

                    <div class="niche-card" data-niche="outro" onclick="selectNiche('outro')">
                        <div class="niche-card-icon"><i class="bi bi-three-dots"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.niche_outro') }}</div>
                            <div class="niche-card-desc">{{ __('onboarding.niche_outro_desc') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                </div>

                <div class="onb-nav">
                    <button class="btn-back" onclick="goBack()"><i class="bi bi-arrow-left"></i> {{ __('onboarding.back') }}</button>
                    <button class="btn-next" onclick="goNext()">
                        {{ __('onboarding.continue') }} <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────── -->
            <!-- STEP 3: Template picker + Sales process (NOVA) -->
            <!-- ─────────────────────────────────────────────── -->
            <div class="onb-step" id="step3">
                <div class="onb-step-label">{{ __('onboarding.step_3_of_6') }}</div>
                <h1 class="onb-title">{{ __('onboarding.step3_title') }}</h1>
                <p class="onb-subtitle">{{ __('onboarding.step3_subtitle') }}</p>

                {{-- Template picker — populated via JS based on selected niche --}}
                <div id="templatePickerContainer">
                    <div class="onb-section-label">{{ __('onboarding.template_picker_label') }}</div>
                    <div class="niche-grid" id="templateGrid">
                        {{-- cards injetados via JS conforme nicho selecionado --}}
                    </div>
                    <div id="templatePickerNoMatch" style="display:none;background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:10px 14px;font-size:13px;color:#92400E;margin-top:8px;">
                        <i class="bi bi-info-circle" style="margin-right:4px;"></i>{{ __('onboarding.template_picker_no_match') }}
                    </div>

                    {{-- Always-available "use AI" card --}}
                    <div class="niche-card" data-template-slug="" onclick="selectTemplate(null, this)" style="margin-top:10px;width:100%;">
                        <div class="niche-card-icon" style="color:#8b5cf6;"><i class="bi bi-stars"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.template_use_ai') }}</div>
                            <div class="niche-card-desc">{{ __('onboarding.template_use_ai_desc') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                </div>

                {{-- Sales process textarea (optional) --}}
                <div style="margin-top:20px;">
                    <label for="salesProcessTextarea" style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">
                        {{ __('onboarding.sales_process_label') }}
                    </label>
                    <textarea id="salesProcessTextarea" rows="3" maxlength="500"
                        placeholder="{{ __('onboarding.sales_process_ph') }}"
                        style="width:100%;border:1.5px solid #e8eaf0;border-radius:10px;padding:10px 14px;font-size:13.5px;font-family:inherit;resize:vertical;box-sizing:border-box;outline:none;transition:border-color .15s;"
                        onfocus="this.style.borderColor='#0085f3'"
                        onblur="this.style.borderColor='#e8eaf0'"></textarea>
                    <p style="font-size:11.5px;color:#9ca3af;margin-top:4px;">{{ __('onboarding.sales_process_hint') }}</p>
                </div>

                <div class="onb-nav">
                    <button class="btn-back" onclick="goBack()"><i class="bi bi-arrow-left"></i> {{ __('onboarding.back') }}</button>
                    <button class="btn-next" onclick="goNext()">
                        {{ __('onboarding.continue') }} <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────── -->
            <!-- STEP 4: Canais de entrada -->
            <!-- ─────────────────────────────────────────────── -->
            <div class="onb-step" id="step4">
                <div class="onb-step-label">{{ __('onboarding.step_4_of_6') }}</div>
                <h1 class="onb-title">{{ __('onboarding.step2_title') }}</h1>
                <p class="onb-subtitle">{{ __('onboarding.step2_subtitle') }}</p>

                <div class="niche-grid" id="channelsGrid">
                    <div class="niche-card" data-channel="whatsapp" onclick="toggleChannel(this)">
                        <div class="niche-card-icon" style="color:#25D366;"><i class="bi bi-whatsapp"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.channel_whatsapp') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                    <div class="niche-card" data-channel="instagram" onclick="toggleChannel(this)">
                        <div class="niche-card-icon" style="color:#E1306C;"><i class="bi bi-instagram"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.channel_instagram') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                    <div class="niche-card" data-channel="facebook_ads" onclick="toggleChannel(this)">
                        <div class="niche-card-icon" style="color:#1877F2;"><i class="bi bi-facebook"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.channel_facebook_ads') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                    <div class="niche-card" data-channel="google_ads" onclick="toggleChannel(this)">
                        <div class="niche-card-icon" style="color:#EA4335;"><i class="bi bi-google"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.channel_google_ads') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                    <div class="niche-card" data-channel="site" onclick="toggleChannel(this)">
                        <div class="niche-card-icon"><i class="bi bi-globe"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.channel_site') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                    <div class="niche-card" data-channel="indicacao" onclick="toggleChannel(this)">
                        <div class="niche-card-icon"><i class="bi bi-people"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.channel_indicacao') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                </div>

                <div id="channelWarning" style="display:none;background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:10px 14px;font-size:13px;color:#92400E;margin-top:12px;">
                    <i class="bi bi-exclamation-triangle" style="margin-right:4px;"></i> {{ __('onboarding.channel_warning_no_whatsapp') }}
                </div>

                <div class="onb-nav">
                    <button class="btn-back" onclick="goBack()"><i class="bi bi-arrow-left"></i> {{ __('onboarding.back') }}</button>
                    <button class="btn-next" onclick="goNext()">
                        {{ __('onboarding.continue') }} <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────── -->
            <!-- STEP 5: Maior dificuldade -->
            <!-- ─────────────────────────────────────────────── -->
            <div class="onb-step" id="step5">
                <div class="onb-step-label">{{ __('onboarding.step_5_of_6') }}</div>
                <h1 class="onb-title">{{ __('onboarding.step4_title') }}</h1>
                <p class="onb-subtitle">{{ __('onboarding.step4_subtitle') }}</p>

                <div style="display:flex;flex-direction:column;gap:10px;" id="difficultyGrid">
                    <div class="niche-card" data-difficulty="followup" onclick="toggleDifficulty(this)" style="width:100%;">
                        <div class="niche-card-icon"><i class="bi bi-alarm"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.difficulty_followup') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                    <div class="niche-card" data-difficulty="disappear" onclick="toggleDifficulty(this)" style="width:100%;">
                        <div class="niche-card-icon"><i class="bi bi-person-dash"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.difficulty_disappear') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                    <div class="niche-card" data-difficulty="priority" onclick="toggleDifficulty(this)" style="width:100%;">
                        <div class="niche-card-icon"><i class="bi bi-sort-down"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.difficulty_priority') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                    <div class="niche-card" data-difficulty="slow" onclick="toggleDifficulty(this)" style="width:100%;">
                        <div class="niche-card-icon"><i class="bi bi-hourglass-split"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.difficulty_slow') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                    <div class="niche-card" data-difficulty="team" onclick="toggleDifficulty(this)" style="width:100%;">
                        <div class="niche-card-icon"><i class="bi bi-people"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.difficulty_team') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                </div>

                <div class="onb-nav">
                    <button class="btn-back" onclick="goBack()"><i class="bi bi-arrow-left"></i> {{ __('onboarding.back') }}</button>
                    <button class="btn-next" onclick="goNext()">
                        {{ __('onboarding.continue') }} <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────── -->
            <!-- STEP 6: Equipe + Resumo + Gerar -->
            <!-- ─────────────────────────────────────────────── -->
            <div class="onb-step" id="step6">
                <div class="onb-step-label">{{ __('onboarding.step_6_of_6') }}</div>
                <h1 class="onb-title">{{ __('onboarding.step5_title') }}</h1>
                <p class="onb-subtitle">{{ __('onboarding.step5_subtitle') }}</p>

                <div class="niche-grid" id="teamGrid">
                    <div class="niche-card" data-team="solo" onclick="selectTeam(this)">
                        <div class="niche-card-icon"><i class="bi bi-person"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.team_solo') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                    <div class="niche-card" data-team="small" onclick="selectTeam(this)">
                        <div class="niche-card-icon"><i class="bi bi-people"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.team_small') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                    <div class="niche-card" data-team="mid" onclick="selectTeam(this)">
                        <div class="niche-card-icon"><i class="bi bi-people-fill"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.team_mid') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                    <div class="niche-card" data-team="large" onclick="selectTeam(this)">
                        <div class="niche-card-icon"><i class="bi bi-building"></i></div>
                        <div class="niche-card-body">
                            <div class="niche-card-name">{{ __('onboarding.team_large') }}</div>
                        </div>
                        <div class="niche-check"><i class="bi bi-check"></i></div>
                    </div>
                </div>

                {{-- Summary box --}}
                <div style="background:#F0F7FF;border:1.5px solid #BFDBFE;border-radius:12px;padding:16px 18px;margin-top:20px;">
                    <div style="font-size:13px;font-weight:600;color:#1a1d23;margin-bottom:6px;">
                        <i class="bi bi-stars" style="color:#0085f3;margin-right:4px;"></i> {{ __('onboarding.summary_title') }}
                    </div>
                    <p style="font-size:13px;color:#374151;line-height:1.6;margin:0;">{{ __('onboarding.summary_items') }}</p>
                </div>

                <div class="onb-nav">
                    <button class="btn-back" onclick="goBack()"><i class="bi bi-arrow-left"></i> {{ __('onboarding.back') }}</button>
                    <button class="btn-next" id="btnFinish" onclick="submitOnboarding()">
                        <span class="spinner" id="submitSpinner"></span>
                        <span id="btnFinishText">{{ __('onboarding.generate_button') }} <i class="bi bi-arrow-right"></i></span>
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
        <div class="skip-modal-title">{{ __('onboarding.skip_modal_title') }}</div>
        <div class="skip-modal-body">
            {!! __('onboarding.skip_modal_body') !!}<br><br>
            {{ __('onboarding.skip_modal_note') }}
        </div>
        <div class="skip-modal-actions">
            <button class="btn-skip-cancel" onclick="closeSkipModal()">{{ __('onboarding.skip_modal_cancel') }}</button>
            <form method="POST" action="{{ route('onboarding.skip') }}" style="flex:1;">
                @csrf
                <button type="submit" class="btn-skip-confirm" style="width:100%;">
                    {{ __('onboarding.skip_modal_confirm') }}
                </button>
            </form>
        </div>
    </div>
</div>

<script>
const OBLANG = @json(__('onboarding'));

const NICHE_DATA = {
    imobiliario: {
        pipeline_name: OBLANG.nd_imobiliario_pipeline,
        stages: [
            { name: OBLANG.nd_imobiliario_stage_1, color: '#6B7280' },
            { name: OBLANG.nd_imobiliario_stage_2, color: '#3B82F6' },
            { name: OBLANG.nd_imobiliario_stage_3, color: '#F59E0B' },
            { name: OBLANG.nd_imobiliario_stage_4, color: '#8B5CF6' },
            { name: OBLANG.nd_imobiliario_stage_5, color: '#10B981' },
            { name: OBLANG.nd_imobiliario_stage_6, color: '#EF4444' },
        ],
        tags: [OBLANG.nd_imobiliario_tag_1, OBLANG.nd_imobiliario_tag_2, OBLANG.nd_imobiliario_tag_3, OBLANG.nd_imobiliario_tag_4, OBLANG.nd_imobiliario_tag_5],
        loss_reasons: [OBLANG.nd_imobiliario_loss_1, OBLANG.nd_imobiliario_loss_2, OBLANG.nd_imobiliario_loss_3, OBLANG.nd_imobiliario_loss_4, OBLANG.nd_imobiliario_loss_5],
    },
    estetica: {
        pipeline_name: OBLANG.nd_estetica_pipeline,
        stages: [
            { name: OBLANG.nd_estetica_stage_1, color: '#6B7280' },
            { name: OBLANG.nd_estetica_stage_2, color: '#3B82F6' },
            { name: OBLANG.nd_estetica_stage_3, color: '#F59E0B' },
            { name: OBLANG.nd_estetica_stage_4, color: '#8B5CF6' },
            { name: OBLANG.nd_estetica_stage_5, color: '#10B981' },
            { name: OBLANG.nd_estetica_stage_6, color: '#EF4444' },
        ],
        tags: [OBLANG.nd_estetica_tag_1, OBLANG.nd_estetica_tag_2, OBLANG.nd_estetica_tag_3, OBLANG.nd_estetica_tag_4, OBLANG.nd_estetica_tag_5],
        loss_reasons: [OBLANG.nd_estetica_loss_1, OBLANG.nd_estetica_loss_2, OBLANG.nd_estetica_loss_3, OBLANG.nd_estetica_loss_4, OBLANG.nd_estetica_loss_5],
    },
    educacao: {
        pipeline_name: OBLANG.nd_educacao_pipeline,
        stages: [
            { name: OBLANG.nd_educacao_stage_1, color: '#6B7280' },
            { name: OBLANG.nd_educacao_stage_2, color: '#3B82F6' },
            { name: OBLANG.nd_educacao_stage_3, color: '#F59E0B' },
            { name: OBLANG.nd_educacao_stage_4, color: '#10B981' },
            { name: OBLANG.nd_educacao_stage_5, color: '#EF4444' },
        ],
        tags: [OBLANG.nd_educacao_tag_1, OBLANG.nd_educacao_tag_2, OBLANG.nd_educacao_tag_3, OBLANG.nd_educacao_tag_4, OBLANG.nd_educacao_tag_5],
        loss_reasons: [OBLANG.nd_educacao_loss_1, OBLANG.nd_educacao_loss_2, OBLANG.nd_educacao_loss_3, OBLANG.nd_educacao_loss_4, OBLANG.nd_educacao_loss_5],
    },
    saude: {
        pipeline_name: OBLANG.nd_saude_pipeline,
        stages: [
            { name: OBLANG.nd_saude_stage_1, color: '#6B7280' },
            { name: OBLANG.nd_saude_stage_2, color: '#3B82F6' },
            { name: OBLANG.nd_saude_stage_3, color: '#F59E0B' },
            { name: OBLANG.nd_saude_stage_4, color: '#8B5CF6' },
            { name: OBLANG.nd_saude_stage_5, color: '#10B981' },
            { name: OBLANG.nd_saude_stage_6, color: '#EF4444' },
        ],
        tags: [OBLANG.nd_saude_tag_1, OBLANG.nd_saude_tag_2, OBLANG.nd_saude_tag_3, OBLANG.nd_saude_tag_4, OBLANG.nd_saude_tag_5],
        loss_reasons: [OBLANG.nd_saude_loss_1, OBLANG.nd_saude_loss_2, OBLANG.nd_saude_loss_3, OBLANG.nd_saude_loss_4, OBLANG.nd_saude_loss_5],
    },
    varejo: {
        pipeline_name: OBLANG.nd_varejo_pipeline,
        stages: [
            { name: OBLANG.nd_varejo_stage_1, color: '#6B7280' },
            { name: OBLANG.nd_varejo_stage_2, color: '#3B82F6' },
            { name: OBLANG.nd_varejo_stage_3, color: '#F59E0B' },
            { name: OBLANG.nd_varejo_stage_4, color: '#8B5CF6' },
            { name: OBLANG.nd_varejo_stage_5, color: '#10B981' },
            { name: OBLANG.nd_varejo_stage_6, color: '#EF4444' },
        ],
        tags: [OBLANG.nd_varejo_tag_1, OBLANG.nd_varejo_tag_2, OBLANG.nd_varejo_tag_3, OBLANG.nd_varejo_tag_4, OBLANG.nd_varejo_tag_5],
        loss_reasons: [OBLANG.nd_varejo_loss_1, OBLANG.nd_varejo_loss_2, OBLANG.nd_varejo_loss_3, OBLANG.nd_varejo_loss_4, OBLANG.nd_varejo_loss_5],
    },
    b2b: {
        pipeline_name: OBLANG.nd_b2b_pipeline,
        stages: [
            { name: OBLANG.nd_b2b_stage_1, color: '#6B7280' },
            { name: OBLANG.nd_b2b_stage_2, color: '#3B82F6' },
            { name: OBLANG.nd_b2b_stage_3, color: '#F59E0B' },
            { name: OBLANG.nd_b2b_stage_4, color: '#8B5CF6' },
            { name: OBLANG.nd_b2b_stage_5, color: '#10B981' },
            { name: OBLANG.nd_b2b_stage_6, color: '#EF4444' },
        ],
        tags: [OBLANG.nd_b2b_tag_1, OBLANG.nd_b2b_tag_2, OBLANG.nd_b2b_tag_3, OBLANG.nd_b2b_tag_4, OBLANG.nd_b2b_tag_5],
        loss_reasons: [OBLANG.nd_b2b_loss_1, OBLANG.nd_b2b_loss_2, OBLANG.nd_b2b_loss_3, OBLANG.nd_b2b_loss_4, OBLANG.nd_b2b_loss_5],
    },
    tecnologia: {
        pipeline_name: OBLANG.nd_tecnologia_pipeline,
        stages: [
            { name: OBLANG.nd_tecnologia_stage_1, color: '#6B7280' },
            { name: OBLANG.nd_tecnologia_stage_2, color: '#3B82F6' },
            { name: OBLANG.nd_tecnologia_stage_3, color: '#F59E0B' },
            { name: OBLANG.nd_tecnologia_stage_4, color: '#8B5CF6' },
            { name: OBLANG.nd_tecnologia_stage_5, color: '#10B981' },
            { name: OBLANG.nd_tecnologia_stage_6, color: '#EF4444' },
        ],
        tags: [OBLANG.nd_tecnologia_tag_1, OBLANG.nd_tecnologia_tag_2, OBLANG.nd_tecnologia_tag_3, OBLANG.nd_tecnologia_tag_4, OBLANG.nd_tecnologia_tag_5],
        loss_reasons: [OBLANG.nd_tecnologia_loss_1, OBLANG.nd_tecnologia_loss_2, OBLANG.nd_tecnologia_loss_3, OBLANG.nd_tecnologia_loss_4, OBLANG.nd_tecnologia_loss_5],
    },
    outro: {
        pipeline_name: OBLANG.nd_outro_pipeline,
        stages: [
            { name: OBLANG.nd_outro_stage_1, color: '#6B7280' },
            { name: OBLANG.nd_outro_stage_2, color: '#3B82F6' },
            { name: OBLANG.nd_outro_stage_3, color: '#F59E0B' },
            { name: OBLANG.nd_outro_stage_4, color: '#8B5CF6' },
            { name: OBLANG.nd_outro_stage_5, color: '#10B981' },
            { name: OBLANG.nd_outro_stage_6, color: '#EF4444' },
        ],
        tags: [OBLANG.nd_outro_tag_1, OBLANG.nd_outro_tag_2, OBLANG.nd_outro_tag_3, OBLANG.nd_outro_tag_4, OBLANG.nd_outro_tag_5],
        loss_reasons: [OBLANG.nd_outro_loss_1, OBLANG.nd_outro_loss_2, OBLANG.nd_outro_loss_3, OBLANG.nd_outro_loss_4, OBLANG.nd_outro_loss_5],
    },
};

let currentStep      = 1;
const totalSteps     = 6;
let selectedNiche    = null;
let logoFile         = null;
let selectedChannels    = [];
let selectedDifficulties = [];
let selectedTeam        = null;
let selectedTemplateSlug = null;          // null = "deixar IA criar"
let templateSelectionMade = false;        // user confirmou explicitamente uma escolha?

// Pipeline templates carregados do PipelineTemplates::all() via OnboardingController
const PIPELINE_TEMPLATES = @json($pipelineTemplates ?? []);
const NICHE_TO_CATEGORY  = @json($nicheToCategory ?? []);

function toggleChannel(card) {
    card.classList.toggle('selected');
    selectedChannels = [];
    document.querySelectorAll('#channelsGrid .niche-card.selected').forEach(c => {
        selectedChannels.push(c.dataset.channel);
    });
    const warn = document.getElementById('channelWarning');
    warn.style.display = selectedChannels.length > 0 && !selectedChannels.includes('whatsapp') ? '' : 'none';
}

function toggleDifficulty(card) {
    card.classList.toggle('selected');
    selectedDifficulties = [];
    document.querySelectorAll('#difficultyGrid .niche-card.selected').forEach(c => {
        selectedDifficulties.push(c.dataset.difficulty);
    });
}

function selectTeam(card) {
    selectedTeam = card.dataset.team;
    document.querySelectorAll('#teamGrid .niche-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
}

function goNext() {
    hideError();

    if (currentStep === 1) {
        const name = document.getElementById('companyName').value.trim();
        if (!name) {
            showError(OBLANG.error_company_name);
            document.getElementById('companyName').focus();
            return;
        }
    }

    if (currentStep === 2) {
        if (!selectedNiche) {
            showError(OBLANG.error_select_niche);
            return;
        }
        // Pré-popula templates pra step 3 (caso o user já tenha escolhido nicho antes)
        populateTemplatesForNiche(selectedNiche);
    }

    // Step 3 (template picker + sales process): nada é obrigatório.
    // Se o user não clicou em nada, assume "deixar IA criar do zero" (selectedTemplateSlug = null).

    if (currentStep === 4) {
        if (selectedChannels.length === 0) {
            showError(OBLANG.error_select_channel || 'Selecione pelo menos um canal.');
            return;
        }
    }

    if (currentStep === 5) {
        if (selectedDifficulties.length === 0) {
            showError(OBLANG.error_difficulty || 'Selecione pelo menos uma dificuldade.');
            return;
        }
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
    document.querySelectorAll('.niche-card[data-niche]').forEach(card => {
        card.classList.toggle('selected', card.dataset.niche === key);
    });
    // Limpa seleção de template quando muda de nicho (templates de outro nicho não fazem sentido)
    selectedTemplateSlug = null;
    templateSelectionMade = false;
    populateTemplatesForNiche(key);
}

/**
 * Renderiza os cards de templates do PipelineTemplates filtrados pelo nicho
 * selecionado. Chamado quando o user escolhe um nicho ou avança pra step 3.
 */
function populateTemplatesForNiche(niche) {
    const grid     = document.getElementById('templateGrid');
    const noMatch  = document.getElementById('templatePickerNoMatch');
    if (!grid) return;

    const category = NICHE_TO_CATEGORY[niche] ?? null;
    const filtered = category
        ? PIPELINE_TEMPLATES.filter(t => t.category === category)
        : [];

    grid.innerHTML = '';

    if (filtered.length === 0) {
        noMatch.style.display = '';
        return;
    }
    noMatch.style.display = 'none';

    filtered.forEach(t => {
        const stagesCount = (t.stages || []).length;
        const card = document.createElement('div');
        card.className = 'niche-card';
        card.dataset.templateSlug = t.slug;
        card.style.width = '100%';
        card.onclick = () => selectTemplate(t.slug, card);
        card.innerHTML = `
            <div class="niche-card-icon" style="color:${t.color || '#0085f3'};"><i class="bi ${t.icon || 'bi-diagram-3'}"></i></div>
            <div class="niche-card-body">
                <div class="niche-card-name">${escapeHtmlSimple(t.name)}</div>
                <div class="niche-card-desc">${escapeHtmlSimple(t.description || '')} · ${stagesCount} ${OBLANG.template_card_stages.replace(':count', '').trim()}</div>
            </div>
            <div class="niche-check"><i class="bi bi-check"></i></div>
        `;
        grid.appendChild(card);
    });
}

function selectTemplate(slug, cardEl) {
    selectedTemplateSlug = slug; // null pra "deixar IA criar"
    templateSelectionMade = true;
    // Visual: limpa todas as seleções e marca o clicado
    document.querySelectorAll('#step3 .niche-card').forEach(c => c.classList.remove('selected'));
    if (cardEl) cardEl.classList.add('selected');
}

function escapeHtmlSimple(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
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

async function submitOnboarding() {
    if (!selectedTeam) {
        showError(OBLANG.error_team_size || 'Selecione o tamanho da equipe.');
        return;
    }

    // Save answers to sessionStorage and redirect to loading page
    const salesProcessText = (document.getElementById('salesProcessTextarea')?.value || '').trim();
    const answers = {
        company_name: document.getElementById('companyName').value.trim(),
        niche: selectedNiche || 'outro',
        channels: selectedChannels,
        sales_process: salesProcessText, // string vazia se user não preencheu (campo é opcional agora)
        difficulty: selectedDifficulties.join(',') || 'followup',
        team_size: selectedTeam || 'solo',
        pipeline_template_slug: selectedTemplateSlug, // null se "deixar IA criar do zero"
    };
    sessionStorage.setItem('onboarding_answers', JSON.stringify(answers));
    if (logoFile) {
        // Can't store file in sessionStorage, upload separately
        sessionStorage.setItem('onboarding_has_logo', '1');
    }

    // Redirect to full-screen loading page
    window.location.href = '{{ route("onboarding.loading") }}';
    return;

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
        formData.append('_token',         document.querySelector('meta[name="csrf-token"]').content);
        formData.append('company_name',   document.getElementById('companyName').value.trim());
        formData.append('niche',          selectedNiche || 'outro');
        formData.append('sales_process',  selectedNiche || 'outro');
        formData.append('difficulty',     selectedDifficulties.join(',') || 'followup');
        formData.append('team_size',      selectedTeam || 'solo');
        selectedChannels.forEach(ch => formData.append('channels[]', ch));
        if (logoFile) formData.append('logo', logoFile);

        const resp = await fetch('{{ route('onboarding.generate') }}', {
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
            showError(OBLANG.error_server.replace(':status', resp.status));
            resetBtn();
            return;
        }

        if (json.success) {
            window.location.href = json.redirect || '{{ route('onboarding.loading') }}';
        } else {
            const msgs = json.errors
                ? Object.values(json.errors).flat().join(' ')
                : (json.message || OBLANG.error_generic);
            showError(msgs);
            resetBtn();
        }
    } catch (e) {
        console.error('[Onboarding] Fetch error:', e);
        showError(OBLANG.error_connection);
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
