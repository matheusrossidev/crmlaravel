@extends('tenant.layouts.app')

@php
    $title    = __('ai_agents.form_heading_create');
    $pageIcon = 'robot';
    $avatars  = \App\Support\AgentAvatars::all();
@endphp

@push('styles')
<style>
    /* Layout fullpage — esconde elementos padrão da página pra wizard ocupar tudo */
    .page-container { padding: 0 !important; max-width: none !important; }

    .wz-shell {
        background: linear-gradient(180deg, #f6f9fd 0%, #fafbfd 100%);
        min-height: calc(100vh - 70px);
        padding: 32px 24px 60px;
        animation: wz-fade-in .35s ease;
    }
    @keyframes wz-fade-in {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .wz-container {
        max-width: none;
        width: 100%;
        margin: 0;
    }

    .wz-back-inside {
        position: absolute;
        top: 24px; left: 24px;
        width: 40px; height: 40px; border-radius: 12px;
        background: #fff; border: 1.5px solid #e8eaf0;
        display: flex; align-items: center; justify-content: center;
        color: #6b7280; cursor: pointer; text-decoration: none;
        font-size: 16px; transition: all .15s;
        z-index: 2;
    }
    .wz-back-inside:hover { background: #f0f4ff; color: #0085f3; border-color: #bfdbfe; }

    /* Step indicator — alinhado à direita, no extremo */
    .wz-progress {
        position: absolute;
        top: 36px; right: 28px;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 2;
    }
    .wz-dot {
        width: 10px; height: 10px; border-radius: 50%;
        background: #e5e7eb;
        transition: all .35s cubic-bezier(.4, 0, .2, 1);
    }
    .wz-dot.active {
        background: #0085f3;
        width: 32px; border-radius: 100px;
        box-shadow: 0 0 0 4px rgba(0,133,243,.12);
    }
    .wz-dot.done {
        background: #0085f3;
        opacity: .55;
    }

    /* Card principal */
    .wz-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 6px 32px rgba(15,23,42,.06);
        border: 1px solid #f0f2f7;
        padding: 96px 48px 36px;
        position: relative;
        overflow: hidden;
        width: 100%;
        max-width: none;
    }
    .wz-step-content { max-width: 760px; margin: 0 auto; }

    .wz-step {
        display: none;
        animation: wz-step-in .35s cubic-bezier(.4, 0, .2, 1);
    }
    .wz-step.active { display: block; }
    @keyframes wz-step-in {
        from { opacity: 0; transform: translateX(20px); }
        to   { opacity: 1; transform: translateX(0); }
    }

    .wz-step-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 22px; font-weight: 700; color: #1a1d23; margin: 0 0 6px;
    }
    .wz-step-sub { font-size: 14px; color: #677489; margin: 0 0 26px; }

    /* Avatares */
    .wz-avatar-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(82px, 1fr));
        gap: 12px;
        margin-bottom: 12px;
    }
    .wz-avatar-option {
        cursor: pointer;
        text-align: center;
        padding: 6px 4px 8px;
        border-radius: 12px;
        border: 2px solid transparent;
        transition: all .2s;
        background: #fafbfd;
    }
    .wz-avatar-option:hover {
        background: #eff6ff;
        transform: translateY(-2px);
    }
    .wz-avatar-option.selected {
        background: #eff6ff;
        border-color: #0085f3;
        box-shadow: 0 4px 14px rgba(0,133,243,.18);
    }
    .wz-avatar-option img {
        width: 56px; height: 56px; border-radius: 50%; object-fit: cover;
        display: block; margin: 0 auto 4px;
        border: 2px solid #fff;
    }
    .wz-avatar-option .av-name {
        font-size: 11px; font-weight: 600; color: #6b7280;
    }
    .wz-avatar-option.selected .av-name { color: #0085f3; }

    /* Form fields */
    .wz-field { margin-bottom: 18px; }
    .wz-label {
        display: block; font-size: 13px; font-weight: 600;
        color: #374151; margin-bottom: 7px;
    }
    .wz-input, .wz-select, .wz-textarea {
        width: 100%; padding: 11px 14px;
        border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-size: 14px; outline: none;
        font-family: inherit;
        color: #1a1d23; background: #fff;
        transition: all .15s;
        box-sizing: border-box;
    }
    .wz-input:focus, .wz-select:focus, .wz-textarea:focus {
        border-color: #0085f3;
        box-shadow: 0 0 0 4px rgba(0,133,243,.10);
    }
    .wz-textarea { resize: vertical; min-height: 100px; }
    .wz-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .wz-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }

    /* Cards de seleção (objective, style, channel) */
    .wz-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 18px;
    }
    .wz-card-option {
        cursor: pointer;
        padding: 18px 14px;
        border: 2px solid #e8eaf0;
        border-radius: 14px;
        background: #fff;
        text-align: center;
        transition: all .2s;
        position: relative;
    }
    .wz-card-option:hover {
        border-color: #93c5fd;
        background: #f8fbff;
        transform: translateY(-2px);
    }
    .wz-card-option.selected {
        border-color: #0085f3;
        background: #eff6ff;
        box-shadow: 0 6px 20px rgba(0,133,243,.15);
    }
    .wz-card-option .ic {
        width: 44px; height: 44px; border-radius: 12px;
        background: #eff6ff; color: #0085f3;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; margin: 0 auto 8px;
        transition: transform .2s;
    }
    .wz-card-option:hover .ic { transform: scale(1.08); }
    .wz-card-option .nm {
        font-size: 13px; font-weight: 700; color: #1a1d23; margin-bottom: 3px;
    }
    .wz-card-option .de {
        font-size: 11.5px; color: #6b7280; line-height: 1.4;
    }
    .wz-card-option.selected .ic { background: #0085f3; color: #fff; }

    /* Toggles */
    .wz-toggle-row {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 16px; background: #f8fafc;
        border: 1.5px solid #e8eaf0; border-radius: 12px;
        margin-bottom: 12px;
    }
    .wz-toggle-row.highlight {
        background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);
        border-color: #bfdbfe;
    }
    .wz-toggle {
        width: 44px; height: 24px; border-radius: 12px;
        background: #d1d5db; position: relative; cursor: pointer;
        transition: background .2s; flex-shrink: 0;
    }
    .wz-toggle.on { background: #0085f3; }
    .wz-toggle::after {
        content: ''; position: absolute; top: 3px; left: 3px;
        width: 18px; height: 18px; border-radius: 50%; background: #fff;
        transition: left .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
    }
    .wz-toggle.on::after { left: 23px; }
    .wz-toggle-text { flex: 1; }
    .wz-toggle-title { font-size: 13.5px; font-weight: 700; color: #1a1d23; }
    .wz-toggle-desc { font-size: 11.5px; color: #6b7280; margin-top: 1px; }

    /* Footer com botões */
    .wz-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 30px;
        padding-top: 22px;
        border-top: 1px solid #f0f2f7;
    }
    .wz-btn {
        padding: 11px 24px;
        border-radius: 100px;
        font-size: 14px; font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: all .15s;
        border: none;
        display: inline-flex; align-items: center; gap: 8px;
    }
    .wz-btn-back {
        background: #fff;
        color: #6b7280;
        border: 1.5px solid #e8eaf0;
    }
    .wz-btn-back:hover { background: #f3f4f6; color: #374151; }
    .wz-btn-next {
        background: #0085f3;
        color: #fff;
        box-shadow: 0 4px 14px rgba(0,133,243,.25);
    }
    .wz-btn-next:hover { background: #0070d1; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,133,243,.32); }
    .wz-btn-next:active { transform: scale(.97); }
    .wz-btn-create { background: #16a34a; box-shadow: 0 4px 14px rgba(22,163,74,.25); }
    .wz-btn-create:hover { background: #15803d; }

    /* Instances pra channel=whatsapp */
    .wz-instances {
        background: #f9fafb;
        border: 1px solid #e8eaf0;
        border-radius: 12px;
        padding: 14px;
        margin-top: 8px;
    }
    .wz-instance-item {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 0;
    }
    .wz-instance-item input { accent-color: #0085f3; width: 16px; height: 16px; }

    /* Stages dinâmicos */
    .wz-stages-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px; }
    .wz-stage-item {
        display: flex; gap: 8px; align-items: flex-start;
        padding: 10px; background: #f8fafc;
        border: 1px solid #e8eaf0; border-radius: 9px;
    }
    .wz-stage-num {
        width: 24px; height: 24px; border-radius: 6px;
        background: #eff6ff; color: #0085f3;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 700; flex-shrink: 0; margin-top: 8px;
    }
    .wz-stage-inputs { flex: 1; display: flex; flex-direction: column; gap: 6px; }
    .wz-stage-del {
        width: 28px; height: 28px; border-radius: 7px;
        border: 1px solid #e8eaf0; background: #fff; color: #9ca3af;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        font-size: 13px; flex-shrink: 0; margin-top: 5px;
    }
    .wz-stage-del:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }
    .wz-add-stage {
        padding: 8px 16px; border-radius: 8px;
        border: 1.5px dashed #d1d5db; background: transparent;
        font-size: 12.5px; font-weight: 600; color: #6b7280;
        cursor: pointer;
    }
    .wz-add-stage:hover { border-color: #0085f3; color: #0085f3; background: #f0f8ff; }

    @media (max-width: 720px) {
        .wz-card { padding: 80px 22px 24px; }
        .wz-row, .wz-row-3, .wz-cards { grid-template-columns: 1fr; }
        .wz-input, .wz-select, .wz-textarea { font-size: 16px; }
        .wz-step-title { font-size: 18px; }
        .wz-back-inside { top: 18px; left: 18px; width: 36px; height: 36px; }
        .wz-progress { top: 26px; right: 18px; gap: 8px; }
        .wz-dot { width: 8px; height: 8px; }
        .wz-dot.active { width: 26px; }
    }
</style>
@endpush

@section('content')
<div class="wz-shell">
    <div class="wz-container">

        @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:13px;max-width:760px;margin:0 auto 16px;">
            <i class="bi bi-exclamation-circle"></i> {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('ai.agents.store') }}" id="wzForm">
            @csrf

            <div class="wz-card">

                {{-- Botão voltar dentro do card (canto superior esquerdo) --}}
                <a href="{{ route('ai.agents.index') }}" class="wz-back-inside" title="{{ __('ai_agents.form_cancel') }}">
                    <i class="bi bi-arrow-left"></i>
                </a>

                {{-- Indicador de progresso (centralizado no topo do card) --}}
                <div class="wz-progress">
                    @for($i = 1; $i <= 5; $i++)
                        <div class="wz-dot {{ $i === 1 ? 'active' : '' }}" id="wz-dot-{{ $i }}"></div>
                    @endfor
                </div>

                {{-- ═══════════════════════════════════════════════════════════════
                     STEP 1 — Identidade & Avatar
                     ═══════════════════════════════════════════════════════════════ --}}
                <div class="wz-step active" data-step="1">
                    <h2 class="wz-step-title">{{ __('ai_agents.wz_step1_title') }}</h2>
                    <p class="wz-step-sub">{{ __('ai_agents.wz_step1_sub') }}</p>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('ai_agents.wz_avatar_label') }}</label>
                        <div class="wz-avatar-grid" id="wzAvatarGrid">
                            @foreach($avatars as $i => $av)
                                <div class="wz-avatar-option {{ $i === 0 ? 'selected' : '' }}"
                                     data-file="{{ $av['file'] }}"
                                     onclick="wzSelectAvatar('{{ $av['file'] }}', this)">
                                    <img src="{{ asset($av['file']) }}" alt="{{ $av['name'] }}">
                                    <div class="av-name">{{ $av['name'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="display_avatar" id="wzDisplayAvatar" value="{{ $avatars[0]['file'] }}">
                    </div>

                    <div class="wz-row">
                        <div class="wz-field">
                            <label class="wz-label">{{ __('ai_agents.s1_name') }} *</label>
                            <input type="text" name="name" class="wz-input" required
                                   value="{{ old('name') }}"
                                   placeholder="{{ __('ai_agents.s1_name_placeholder') }}">
                        </div>
                        <div class="wz-field">
                            <label class="wz-label">{{ __('ai_agents.s1_company') }}</label>
                            <input type="text" name="company_name" class="wz-input"
                                   value="{{ old('company_name') }}"
                                   placeholder="{{ __('ai_agents.s1_company_placeholder') }}">
                        </div>
                    </div>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('ai_agents.s1_language') }}</label>
                        <select name="language" class="wz-select">
                            @foreach(['pt-BR' => __('ai_agents.s1_lang_pt'), 'en-US' => __('ai_agents.s1_lang_en'), 'es-ES' => __('ai_agents.s1_lang_es')] as $v => $l)
                                <option value="{{ $v }}" {{ old('language', 'pt-BR') === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════════════════
                     STEP 2 — Objetivo & Personalidade
                     ═══════════════════════════════════════════════════════════════ --}}
                <div class="wz-step" data-step="2">
                    <h2 class="wz-step-title">{{ __('ai_agents.wz_step2_title') }}</h2>
                    <p class="wz-step-sub">{{ __('ai_agents.wz_step2_sub') }}</p>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('ai_agents.wz_objective_label') }}</label>
                        <div class="wz-cards">
                            @php
                                $objectives = [
                                    ['v' => 'sales',   'i' => 'cart-check',     'n' => __('ai_agents.s1_objective_sales'),   'd' => __('ai_agents.wz_obj_sales_desc')],
                                    ['v' => 'support', 'i' => 'headset',        'n' => __('ai_agents.s1_objective_support'), 'd' => __('ai_agents.wz_obj_support_desc')],
                                    ['v' => 'general', 'i' => 'chat-square-text','n' => __('ai_agents.s1_objective_general'), 'd' => __('ai_agents.wz_obj_general_desc')],
                                ];
                            @endphp
                            @foreach($objectives as $idx => $opt)
                                <div class="wz-card-option {{ $idx === 0 ? 'selected' : '' }}"
                                     data-value="{{ $opt['v'] }}"
                                     onclick="wzSelectCard(this, 'objective')">
                                    <div class="ic"><i class="bi bi-{{ $opt['i'] }}"></i></div>
                                    <div class="nm">{{ $opt['n'] }}</div>
                                    <div class="de">{{ $opt['d'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="objective" id="wzObjective" value="{{ old('objective', 'sales') }}">
                    </div>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('ai_agents.wz_style_label') }}</label>
                        <div class="wz-cards">
                            @php
                                $styles = [
                                    ['v' => 'formal', 'i' => 'briefcase',    'n' => __('ai_agents.s1_style_formal'), 'd' => __('ai_agents.wz_style_formal_desc')],
                                    ['v' => 'normal', 'i' => 'chat-dots',    'n' => __('ai_agents.s1_style_normal'), 'd' => __('ai_agents.wz_style_normal_desc')],
                                    ['v' => 'casual', 'i' => 'emoji-smile',  'n' => __('ai_agents.s1_style_casual'), 'd' => __('ai_agents.wz_style_casual_desc')],
                                ];
                            @endphp
                            @foreach($styles as $idx => $opt)
                                <div class="wz-card-option {{ $idx === 1 ? 'selected' : '' }}"
                                     data-value="{{ $opt['v'] }}"
                                     onclick="wzSelectCard(this, 'communication_style')">
                                    <div class="ic"><i class="bi bi-{{ $opt['i'] }}"></i></div>
                                    <div class="nm">{{ $opt['n'] }}</div>
                                    <div class="de">{{ $opt['d'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="communication_style" id="wzCommunicationStyle" value="{{ old('communication_style', 'normal') }}">
                    </div>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('ai_agents.s1_industry') }}</label>
                        <input type="text" name="industry" class="wz-input"
                               value="{{ old('industry') }}"
                               placeholder="{{ __('ai_agents.s1_industry_placeholder') }}">
                    </div>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('ai_agents.s2_persona') }}</label>
                        <textarea name="persona_description" class="wz-textarea" rows="3"
                                  placeholder="{{ __('ai_agents.s2_persona_placeholder') }}">{{ old('persona_description') }}</textarea>
                    </div>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('ai_agents.s2_behavior') }}</label>
                        <textarea name="behavior" class="wz-textarea" rows="3"
                                  placeholder="{{ __('ai_agents.s2_behavior_placeholder') }}">{{ old('behavior') }}</textarea>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════════════════
                     STEP 3 — Canal & Atribuição
                     ═══════════════════════════════════════════════════════════════ --}}
                <div class="wz-step" data-step="3">
                    <h2 class="wz-step-title">{{ __('ai_agents.wz_step3_title') }}</h2>
                    <p class="wz-step-sub">{{ __('ai_agents.wz_step3_sub') }}</p>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('ai_agents.channel_label') }}</label>
                        <div class="wz-cards">
                            @php
                                $channels = [
                                    ['v' => 'whatsapp',  'i' => 'whatsapp', 'n' => __('ai_agents.channel_whatsapp')],
                                    ['v' => 'instagram', 'i' => 'instagram','n' => __('ai_agents.channel_instagram')],
                                    ['v' => 'web_chat',  'i' => 'globe',    'n' => __('ai_agents.channel_web_chat')],
                                ];
                            @endphp
                            @foreach($channels as $idx => $opt)
                                <div class="wz-card-option {{ $idx === 0 ? 'selected' : '' }}"
                                     data-value="{{ $opt['v'] }}"
                                     onclick="wzSelectChannel(this)">
                                    <div class="ic"><i class="bi bi-{{ $opt['i'] }}"></i></div>
                                    <div class="nm">{{ $opt['n'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="channel" id="wzChannel" value="{{ old('channel', 'whatsapp') }}">
                    </div>

                    @if(isset($whatsappInstances) && $whatsappInstances->count() > 0)
                    <div class="wz-instances" id="wzInstances">
                        <div style="font-size:12.5px;font-weight:700;color:#374151;margin-bottom:8px;">
                            <i class="bi bi-telephone" style="margin-right:4px;"></i> {{ __('ai_agents.wa_instances_title') }}
                        </div>
                        @foreach($whatsappInstances as $inst)
                            <label class="wz-instance-item" style="cursor:pointer;">
                                <input type="checkbox" name="whatsapp_instance_ids[]" value="{{ $inst->id }}">
                                <span style="font-size:13px;color:#1a1d23;font-weight:500;">{{ $inst->label ?: $inst->session_name }}</span>
                                @if($inst->phone_number)
                                    <span style="font-size:11px;color:#6b7280;">({{ $inst->phone_number }})</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                    @endif

                    <div class="wz-toggle-row" style="margin-top:14px;" onclick="wzToggle('wzActive')">
                        <div class="wz-toggle on" id="wzActiveSwitch"></div>
                        <div class="wz-toggle-text">
                            <div class="wz-toggle-title">{{ __('ai_agents.toggle_active_on') }}</div>
                            <div class="wz-toggle-desc">{{ __('ai_agents.toggle_active_desc') }}</div>
                        </div>
                    </div>
                    <input type="hidden" name="is_active" id="wzActive" value="1">

                    <div class="wz-toggle-row" onclick="wzToggle('wzAutoAssign')">
                        <div class="wz-toggle on" id="wzAutoAssignSwitch"></div>
                        <div class="wz-toggle-text">
                            <div class="wz-toggle-title">{{ __('ai_agents.toggle_auto_assign_on') }}</div>
                            <div class="wz-toggle-desc">{{ __('ai_agents.toggle_auto_assign_desc') }}</div>
                        </div>
                    </div>
                    <input type="hidden" name="auto_assign" id="wzAutoAssign" value="1">
                </div>

                {{-- ═══════════════════════════════════════════════════════════════
                     STEP 4 — Conhecimento & Ferramentas
                     ═══════════════════════════════════════════════════════════════ --}}
                <div class="wz-step" data-step="4">
                    <h2 class="wz-step-title">{{ __('ai_agents.wz_step4_title') }}</h2>
                    <p class="wz-step-sub">{{ __('ai_agents.wz_step4_sub') }}</p>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('ai_agents.s5_title') }}</label>
                        <textarea name="knowledge_base" class="wz-textarea" rows="6"
                                  placeholder="{{ __('ai_agents.s5_kb_placeholder') }}">{{ old('knowledge_base') }}</textarea>
                        <div style="font-size:11.5px;color:#9ca3af;margin-top:5px;">{{ __('ai_agents.wz_kb_hint') }}</div>
                    </div>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('ai_agents.s4_title') }}</label>
                        <div class="wz-stages-list" id="wzStagesList"></div>
                        <button type="button" class="wz-add-stage" onclick="wzAddStage()">
                            <i class="bi bi-plus"></i> {{ __('ai_agents.s4_add_stage') }}
                        </button>
                    </div>

                    <div class="wz-field">
                        <label class="wz-label" style="margin-bottom:10px;">{{ __('ai_agents.wz_tools_label') }}</label>
                        <div class="wz-toggle-row" onclick="wzToggle('wzPipelineTool')">
                            <div class="wz-toggle on" id="wzPipelineToolSwitch"></div>
                            <div class="wz-toggle-text">
                                <div class="wz-toggle-title">{{ __('ai_agents.s6_pipeline_on') }}</div>
                                <div class="wz-toggle-desc">{{ __('ai_agents.s6_pipeline_desc') }}</div>
                            </div>
                        </div>
                        <input type="hidden" name="enable_pipeline_tool" id="wzPipelineTool" value="1">

                        <div class="wz-toggle-row" onclick="wzToggle('wzTagsTool')">
                            <div class="wz-toggle on" id="wzTagsToolSwitch"></div>
                            <div class="wz-toggle-text">
                                <div class="wz-toggle-title">{{ __('ai_agents.s6_tags_on') }}</div>
                                <div class="wz-toggle-desc">{{ __('ai_agents.s6_tags_desc') }}</div>
                            </div>
                        </div>
                        <input type="hidden" name="enable_tags_tool" id="wzTagsTool" value="1">

                        <div class="wz-toggle-row" onclick="wzToggle('wzIntentNotify')">
                            <div class="wz-toggle" id="wzIntentNotifySwitch"></div>
                            <div class="wz-toggle-text">
                                <div class="wz-toggle-title">{{ __('ai_agents.s6_intent_on') }}</div>
                                <div class="wz-toggle-desc">{{ __('ai_agents.s6_intent_desc') }}</div>
                            </div>
                        </div>
                        <input type="hidden" name="enable_intent_notify" id="wzIntentNotify" value="0">

                        <div class="wz-toggle-row" onclick="wzToggle('wzCalendarTool')">
                            <div class="wz-toggle" id="wzCalendarToolSwitch"></div>
                            <div class="wz-toggle-text">
                                <div class="wz-toggle-title">{{ __('ai_agents.s6_calendar_on') }}</div>
                                <div class="wz-toggle-desc">{{ __('ai_agents.s6_calendar_desc') }}</div>
                            </div>
                        </div>
                        <input type="hidden" name="enable_calendar_tool" id="wzCalendarTool" value="0">

                        <div class="wz-toggle-row" onclick="wzToggle('wzProductsTool')">
                            <div class="wz-toggle" id="wzProductsToolSwitch"></div>
                            <div class="wz-toggle-text">
                                <div class="wz-toggle-title">{{ __('ai_agents.s6_products_on') }}</div>
                                <div class="wz-toggle-desc">{{ __('ai_agents.s6_products_desc') }}</div>
                            </div>
                        </div>
                        <input type="hidden" name="enable_products_tool" id="wzProductsTool" value="0">
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════════════════
                     STEP 5 — Follow-up & Avançado
                     ═══════════════════════════════════════════════════════════════ --}}
                <div class="wz-step" data-step="5">
                    <h2 class="wz-step-title">{{ __('ai_agents.wz_step5_title') }}</h2>
                    <p class="wz-step-sub">{{ __('ai_agents.wz_step5_sub') }}</p>

                    {{-- Follow-up — destaque + ON por padrão --}}
                    <div class="wz-toggle-row highlight" onclick="wzToggle('wzFollowup')">
                        <div class="wz-toggle on" id="wzFollowupSwitch"></div>
                        <div class="wz-toggle-text">
                            <div class="wz-toggle-title">
                                <i class="bi bi-arrow-repeat" style="color:#0085f3;margin-right:4px;"></i>
                                {{ __('ai_agents.wz_followup_title') }}
                            </div>
                            <div class="wz-toggle-desc">{{ __('ai_agents.wz_followup_desc') }}</div>
                        </div>
                    </div>
                    <input type="hidden" name="followup_enabled" id="wzFollowup" value="1">

                    <div id="wzFollowupOptions">
                        <div class="wz-row">
                            <div class="wz-field">
                                <label class="wz-label">{{ __('ai_agents.s8_delay_minutes') }}</label>
                                <input type="number" name="followup_delay_minutes" class="wz-input"
                                       value="{{ old('followup_delay_minutes', 40) }}" min="5" max="1440">
                            </div>
                            <div class="wz-field">
                                <label class="wz-label">{{ __('ai_agents.s8_max_count') }}</label>
                                <input type="number" name="followup_max_count" class="wz-input"
                                       value="{{ old('followup_max_count', 3) }}" min="1" max="10">
                            </div>
                        </div>
                        <div class="wz-row">
                            <div class="wz-field">
                                <label class="wz-label">{{ __('ai_agents.s8_hour_start') }}</label>
                                <input type="number" name="followup_hour_start" class="wz-input"
                                       value="{{ old('followup_hour_start', 8) }}" min="0" max="23">
                            </div>
                            <div class="wz-field">
                                <label class="wz-label">{{ __('ai_agents.s8_hour_end') }}</label>
                                <input type="number" name="followup_hour_end" class="wz-input"
                                       value="{{ old('followup_hour_end', 18) }}" min="1" max="23">
                            </div>
                        </div>
                    </div>

                    <div class="wz-field" style="margin-top:18px;">
                        <label class="wz-label">{{ __('ai_agents.wz_advanced_label') }}</label>
                        <div class="wz-row-3">
                            <div>
                                <label class="wz-label" style="font-size:11.5px;">{{ __('ai_agents.s7_max_message_length') }}</label>
                                <input type="number" name="max_message_length" class="wz-input"
                                       value="{{ old('max_message_length', 500) }}" min="50" max="4000" step="50">
                            </div>
                            <div>
                                <label class="wz-label" style="font-size:11.5px;">{{ __('ai_agents.s7_response_delay') }}</label>
                                <input type="number" name="response_delay_seconds" class="wz-input"
                                       value="{{ old('response_delay_seconds', 2) }}" min="0" max="30">
                            </div>
                            <div>
                                <label class="wz-label" style="font-size:11.5px;">{{ __('ai_agents.s7_response_wait') }}</label>
                                <input type="number" name="response_wait_seconds" class="wz-input"
                                       value="{{ old('response_wait_seconds', 0) }}" min="0" max="30">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer com botões --}}
                <div class="wz-footer">
                    <button type="button" class="wz-btn wz-btn-back" id="wzBackBtn" onclick="wzBack()" style="visibility:hidden;">
                        <i class="bi bi-arrow-left"></i> {{ __('ai_agents.wz_back') }}
                    </button>
                    <button type="button" class="wz-btn wz-btn-next" id="wzNextBtn" onclick="wzNext()">
                        {{ __('ai_agents.wz_continue') }} <i class="bi bi-arrow-right"></i>
                    </button>
                    <button type="submit" class="wz-btn wz-btn-next wz-btn-create" id="wzCreateBtn" style="display:none;">
                        <i class="bi bi-check-circle"></i> {{ __('ai_agents.wz_create_agent') }}
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const TOTAL_STEPS = 5;
let currentStep = 1;

function wzGoStep(n) {
    document.querySelectorAll('.wz-step').forEach(s => s.classList.remove('active'));
    document.querySelector('.wz-step[data-step="' + n + '"]').classList.add('active');
    currentStep = n;

    // Atualiza dots
    for (let i = 1; i <= TOTAL_STEPS; i++) {
        const dot = document.getElementById('wz-dot-' + i);
        dot.className = 'wz-dot' + (i === n ? ' active' : (i < n ? ' done' : ''));
    }

    // Botões
    document.getElementById('wzBackBtn').style.visibility = (n === 1) ? 'hidden' : 'visible';
    if (n === TOTAL_STEPS) {
        document.getElementById('wzNextBtn').style.display = 'none';
        document.getElementById('wzCreateBtn').style.display = 'inline-flex';
    } else {
        document.getElementById('wzNextBtn').style.display = 'inline-flex';
        document.getElementById('wzCreateBtn').style.display = 'none';
    }

    // Scroll smooth pro topo do card
    document.querySelector('.wz-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function wzValidateStep(n) {
    if (n === 1) {
        const name = document.querySelector('input[name="name"]').value.trim();
        if (!name) {
            toastr?.warning('{{ __('ai_agents.wz_name_required') }}') ?? alert('{{ __('ai_agents.wz_name_required') }}');
            document.querySelector('input[name="name"]').focus();
            return false;
        }
    }
    return true;
}

function wzNext() {
    if (!wzValidateStep(currentStep)) return;
    if (currentStep < TOTAL_STEPS) wzGoStep(currentStep + 1);
}

function wzBack() {
    if (currentStep > 1) wzGoStep(currentStep - 1);
}

function wzSelectAvatar(file, el) {
    document.getElementById('wzDisplayAvatar').value = file;
    document.querySelectorAll('#wzAvatarGrid .wz-avatar-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
}

function wzSelectCard(el, fieldName) {
    const inputId = 'wz' + fieldName.split('_').map(s => s.charAt(0).toUpperCase() + s.slice(1)).join('');
    el.parentElement.querySelectorAll('.wz-card-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById(inputId).value = el.dataset.value;
}

function wzSelectChannel(el) {
    el.parentElement.querySelectorAll('.wz-card-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    const v = el.dataset.value;
    document.getElementById('wzChannel').value = v;
    // Mostra/esconde instâncias WhatsApp
    const instancesDiv = document.getElementById('wzInstances');
    if (instancesDiv) instancesDiv.style.display = (v === 'whatsapp') ? 'block' : 'none';
}

function wzToggle(inputId) {
    const input = document.getElementById(inputId);
    const sw    = document.getElementById(inputId + 'Switch');
    const isOn  = input.value === '1';
    input.value = isOn ? '0' : '1';
    sw.classList.toggle('on', !isOn);
    // Followup options visibility
    if (inputId === 'wzFollowup') {
        document.getElementById('wzFollowupOptions').style.display = isOn ? 'none' : 'block';
    }
}

/* ── Stages dinâmicos ── */
let wzStageCount = 0;
function wzAddStage() {
    const list = document.getElementById('wzStagesList');
    const i = wzStageCount++;
    const item = document.createElement('div');
    item.className = 'wz-stage-item';
    item.innerHTML = `
        <div class="wz-stage-num">${list.children.length + 1}</div>
        <div class="wz-stage-inputs">
            <input type="text" name="conversation_stages[${i}][name]"
                   class="wz-input" style="padding:8px 12px;font-size:13px;"
                   placeholder="{{ __('ai_agents.s4_stage_name_placeholder') }}">
            <input type="text" name="conversation_stages[${i}][description]"
                   class="wz-input" style="padding:8px 12px;font-size:13px;"
                   placeholder="{{ __('ai_agents.s4_stage_desc_placeholder') }}">
        </div>
        <button type="button" class="wz-stage-del" onclick="wzRemoveStage(this)">×</button>
    `;
    list.appendChild(item);
}
function wzRemoveStage(btn) {
    btn.closest('.wz-stage-item').remove();
    document.querySelectorAll('#wzStagesList .wz-stage-num').forEach((el, i) => {
        el.textContent = i + 1;
    });
}

// Inicialização
wzGoStep(1);
</script>
@endpush
