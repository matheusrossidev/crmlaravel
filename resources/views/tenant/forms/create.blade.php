@extends('tenant.layouts.app')

@php
    $title = __('forms.create_title');
    $pageIcon = 'bi-plus-lg';
@endphp

@push('styles')
<style>
    /* Layout fullpage — esconde padding padrão da página */
    .page-container { padding:0 !important;max-width:none !important; }

    .wz-shell {
        background: linear-gradient(180deg,#f6f9fd 0%,#fafbfd 100%);
        min-height: calc(100vh - 70px);
        padding: 32px 24px 60px;
        animation: wz-fade-in .35s ease;
    }
    @keyframes wz-fade-in { from { opacity:0;transform:translateY(8px); } to { opacity:1;transform:translateY(0); } }

    .wz-container { max-width:none;width:100%;margin:0; }

    .wz-back-inside {
        position:absolute;top:24px;left:24px;width:40px;height:40px;border-radius:12px;
        background:#fff;border:1.5px solid #e8eaf0;display:flex;align-items:center;justify-content:center;
        color:#6b7280;cursor:pointer;text-decoration:none;font-size:16px;transition:all .15s;z-index:2;
    }
    .wz-back-inside:hover { background:#f0f4ff;color:#0085f3;border-color:#bfdbfe; }

    .wz-progress { position:absolute;top:36px;right:28px;display:flex;align-items:center;gap:10px;z-index:2; }
    .wz-dot { width:10px;height:10px;border-radius:50%;background:#e5e7eb;transition:all .35s cubic-bezier(.4,0,.2,1); }
    .wz-dot.active { background:#0085f3;width:32px;border-radius:100px;box-shadow:0 0 0 4px rgba(0,133,243,.12); }
    .wz-dot.done { background:#0085f3;opacity:.55; }

    .wz-card {
        background:#fff;border-radius:18px;box-shadow:0 6px 32px rgba(15,23,42,.06);
        border:1px solid #f0f2f7;padding:96px 48px 36px;position:relative;overflow:hidden;
        width:100%;max-width:none;
    }
    .wz-step-content { max-width:760px;margin:0 auto; }

    .wz-step { display:none;animation:wz-step-in .35s cubic-bezier(.4,0,.2,1); }
    .wz-step.active { display:block; }
    @keyframes wz-step-in { from { opacity:0;transform:translateX(20px); } to { opacity:1;transform:translateX(0); } }

    .wz-step-title { font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 6px; }
    .wz-step-sub { font-size:14px;color:#677489;margin:0 0 26px; }

    /* Form fields */
    .wz-field { margin-bottom:18px; }
    .wz-label { display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:7px; }
    .wz-input, .wz-select, .wz-textarea {
        width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;outline:none;
        font-family:inherit;color:#1a1d23;background:#fff;transition:all .15s;box-sizing:border-box;
    }
    .wz-input:focus, .wz-select:focus, .wz-textarea:focus { border-color:#0085f3;box-shadow:0 0 0 4px rgba(0,133,243,.10); }
    .wz-textarea { resize:vertical;min-height:100px; }
    .wz-row { display:grid;grid-template-columns:1fr 1fr;gap:14px; }
    .wz-row-3 { display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px; }

    /* Cards de seleção */
    .wz-cards { display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px; }
    .wz-card-option {
        cursor:pointer;padding:18px 14px;border:2px solid #e8eaf0;border-radius:14px;background:#fff;
        text-align:center;transition:all .2s;position:relative;
    }
    .wz-card-option:hover { border-color:#93c5fd;background:#f8fbff;transform:translateY(-2px); }
    .wz-card-option.selected { border-color:#0085f3;background:#eff6ff;box-shadow:0 6px 20px rgba(0,133,243,.15); }
    .wz-card-option .ic {
        width:44px;height:44px;border-radius:12px;background:#eff6ff;color:#0085f3;
        display:flex;align-items:center;justify-content:center;font-size:20px;margin:0 auto 8px;transition:transform .2s;
    }
    .wz-card-option:hover .ic { transform:scale(1.08); }
    .wz-card-option .nm { font-size:13px;font-weight:700;color:#1a1d23;margin-bottom:3px; }
    .wz-card-option .de { font-size:11.5px;color:#6b7280;line-height:1.4; }
    .wz-card-option.selected .ic { background:#0085f3;color:#fff; }

    /* Layout thumbnails */
    .wz-layout-thumb { width:72px;height:48px;border-radius:6px;background:#f0f2f7;position:relative;overflow:hidden;margin:0 auto 8px; }
    .wz-layout-thumb .lt-card { position:absolute;background:#fff;border:1.5px solid #d1d5db;border-radius:3px;width:28px;height:34px;top:7px; }
    .wz-layout-thumb.left .lt-card { left:6px; }
    .wz-layout-thumb.centered .lt-card { left:50%;transform:translateX(-50%); }
    .wz-layout-thumb.right .lt-card { right:6px; }

    /* Color rows (per-color palette) */
    .wz-color-row { margin-bottom:14px; }
    .wz-color-row-label { font-size:12px;font-weight:600;color:#374151;margin-bottom:7px;display:flex;align-items:center;justify-content:space-between; }
    .wz-color-row-label .current { font-family:monospace;font-size:11px;color:#9ca3af; }
    .wz-color-dots { display:flex;gap:8px;flex-wrap:wrap;align-items:center; }
    .wz-color-dot { width:30px;height:30px;border-radius:50%;cursor:pointer;transition:all .15s;border:2px solid transparent;position:relative;box-shadow:0 0 0 1px rgba(0,0,0,.08); }
    .wz-color-dot:hover { transform:scale(1.15); }
    .wz-color-dot.selected { border-color:#0085f3;box-shadow:0 0 0 1px #fff inset,0 4px 10px rgba(0,133,243,.3); }
    .wz-color-dot .dot-check { position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;opacity:0;text-shadow:0 1px 2px rgba(0,0,0,.3); }
    .wz-color-dot.selected .dot-check { opacity:1; }
    .wz-color-custom-btn {
        width:30px;height:30px;border-radius:50%;border:2px dashed #d1d5db;display:inline-flex;align-items:center;justify-content:center;
        cursor:pointer;color:#9ca3af;font-size:14px;line-height:1;transition:all .15s;position:relative;overflow:hidden;margin:0;padding:0;
    }
    .wz-color-custom-btn i { display:block;line-height:1; }
    .wz-color-custom-btn:hover { border-color:#0085f3;color:#0085f3; }
    .wz-color-custom-btn input[type=color] { position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;border:none;padding:0; }

    /* Toggles */
    .wz-toggle-row {
        display:flex;align-items:center;gap:14px;padding:14px 16px;background:#f8fafc;
        border:1.5px solid #e8eaf0;border-radius:12px;margin-bottom:12px;cursor:pointer;user-select:none;
    }
    .wz-toggle { width:44px;height:24px;border-radius:12px;background:#d1d5db;position:relative;cursor:pointer;transition:background .2s;flex-shrink:0; }
    .wz-toggle.on { background:#0085f3; }
    .wz-toggle::after { content:'';position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;transition:left .2s;box-shadow:0 1px 3px rgba(0,0,0,.2); }
    .wz-toggle.on::after { left:23px; }
    .wz-toggle-text { flex:1; }
    .wz-toggle-title { font-size:13.5px;font-weight:700;color:#1a1d23; }
    .wz-toggle-desc { font-size:11.5px;color:#6b7280;margin-top:1px; }

    /* Dropzone */
    .wz-dropzone {
        border:2px dashed #d1d5db;border-radius:12px;padding:22px 16px;text-align:center;cursor:pointer;transition:all .2s;margin-top:8px;
    }
    .wz-dropzone:hover { border-color:#0085f3;background:#f8fbff; }
    .wz-dropzone-preview { margin-top:8px;text-align:center; }
    .wz-dropzone-preview img { max-height:70px;border-radius:10px;border:1.5px solid #e8eaf0;padding:4px;background:#fff; }

    /* Footer */
    .wz-footer { display:flex;justify-content:space-between;align-items:center;margin-top:30px;padding-top:22px;border-top:1px solid #f0f2f7; }
    .wz-btn { padding:11px 24px;border-radius:100px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s;border:none;display:inline-flex;align-items:center;gap:8px; }
    .wz-btn-back { background:#fff;color:#6b7280;border:1.5px solid #e8eaf0; }
    .wz-btn-back:hover { background:#f3f4f6;color:#374151; }
    .wz-btn-next { background:#0085f3;color:#fff;box-shadow:0 4px 14px rgba(0,133,243,.25); }
    .wz-btn-next:hover { background:#0070d1;transform:translateY(-1px);box-shadow:0 6px 20px rgba(0,133,243,.32); }
    .wz-btn-next:active { transform:scale(.97); }
    .wz-btn-create { background:#0085f3;box-shadow:0 4px 14px rgba(0,133,243,.25);animation:wz-pulse 2s ease-in-out infinite; }
    .wz-btn-create:hover { background:#0070d1;animation:none; }
    @keyframes wz-pulse {
        0%, 100% { box-shadow:0 4px 14px rgba(0,133,243,.25), 0 0 0 0 rgba(0,133,243,.45); }
        50%      { box-shadow:0 4px 14px rgba(0,133,243,.25), 0 0 0 8px rgba(0,133,243,0); }
    }

    @media (max-width:720px) {
        .wz-card { padding:80px 22px 24px; }
        .wz-row, .wz-row-3, .wz-cards { grid-template-columns:1fr; }
        .wz-custom-pickers.open { grid-template-columns:1fr 1fr; }
        .wz-input, .wz-select, .wz-textarea { font-size:16px; }
        .wz-step-title { font-size:18px; }
        .wz-back-inside { top:18px;left:18px;width:36px;height:36px; }
        .wz-progress { top:26px;right:18px;gap:8px; }
        .wz-dot { width:8px;height:8px; }
        .wz-dot.active { width:26px; }
    }
</style>
@endpush

@section('content')
<div class="wz-shell">
    <div class="wz-container">

        <div class="wz-card">

            <a href="{{ route('forms.index') }}" class="wz-back-inside" title="{{ __('common.cancel') }}">
                <i class="bi bi-arrow-left"></i>
            </a>

            {{-- Dots --}}
            <div class="wz-progress">
                @for($i = 1; $i <= 5; $i++)
                    <div class="wz-dot {{ $i === 1 ? 'active' : '' }}" id="wz-dot-{{ $i }}"></div>
                @endfor
            </div>

            {{-- ══════════════════════════════════════════════════
                 STEP 1 — Identidade: nome, tipo, layout
                 ══════════════════════════════════════════════════ --}}
            <div class="wz-step active" data-step="1">
                <div class="wz-step-content">
                    <h2 class="wz-step-title">{{ __('forms.wz_step1_title') }}</h2>
                    <p class="wz-step-sub">{{ __('forms.wz_step1_sub') }}</p>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('forms.form_name') }} *</label>
                        <input type="text" id="f_name" class="wz-input" placeholder="{{ __('forms.form_name_ph') }}" maxlength="100">
                    </div>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('forms.form_type') }}</label>
                        <div class="wz-cards">
                            <div class="wz-card-option selected" data-field="type" data-value="classic" onclick="wzSelectCard(this,'type')">
                                <div class="ic"><i class="bi bi-ui-checks-grid"></i></div>
                                <div class="nm">{{ __('forms.type_classic') }}</div>
                                <div class="de">{{ __('forms.type_classic_desc') }}</div>
                            </div>
                            <div class="wz-card-option" data-field="type" data-value="conversational" onclick="wzSelectCard(this,'type')">
                                <div class="ic"><i class="bi bi-chat-dots"></i></div>
                                <div class="nm">{{ __('forms.type_conversational') }}</div>
                                <div class="de">{{ __('forms.type_conversational_desc') }}</div>
                            </div>
                            <div class="wz-card-option" data-field="type" data-value="multistep" onclick="wzSelectCard(this,'type')">
                                <div class="ic"><i class="bi bi-layers"></i></div>
                                <div class="nm">{{ __('forms.type_multistep') }}</div>
                                <div class="de">{{ __('forms.type_multistep_desc') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('forms.step3_question') }}</label>
                        <div class="wz-cards">
                            <div class="wz-card-option" data-field="layout" data-value="left" onclick="wzSelectCard(this,'layout')">
                                <div class="wz-layout-thumb left"><div class="lt-card"></div></div>
                                <div class="nm">{{ __('forms.layout_left') }}</div>
                            </div>
                            <div class="wz-card-option selected" data-field="layout" data-value="centered" onclick="wzSelectCard(this,'layout')">
                                <div class="wz-layout-thumb centered"><div class="lt-card"></div></div>
                                <div class="nm">{{ __('forms.layout_centered') }}</div>
                            </div>
                            <div class="wz-card-option" data-field="layout" data-value="right" onclick="wzSelectCard(this,'layout')">
                                <div class="wz-layout-thumb right"><div class="lt-card"></div></div>
                                <div class="nm">{{ __('forms.layout_right') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════
                 STEP 2 — Visual: cores, fonte, radius
                 ══════════════════════════════════════════════════ --}}
            <div class="wz-step" data-step="2">
                <div class="wz-step-content">
                    <h2 class="wz-step-title">{{ __('forms.wz_step2_title') }}</h2>
                    <p class="wz-step-sub">{{ __('forms.wz_step2_sub') }}</p>

                    <div class="wz-field" id="colorRows">
                        {{-- rendered by JS --}}
                    </div>

                    <div class="wz-row">
                        <div class="wz-field">
                            <label class="wz-label">{{ __('forms.font_family') }}</label>
                            <select id="f_font" class="wz-select">
                                @foreach(['Inter', 'Plus Jakarta Sans', 'Poppins', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Georgia', 'Courier New'] as $font)
                                    <option value="{{ $font }}">{{ $font }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="wz-field">
                            <label class="wz-label">Border radius</label>
                            <input type="range" id="f_radius" min="0" max="20" value="8" style="width:100%;margin-top:12px;">
                            <div style="font-size:12px;color:#9ca3af;text-align:center;margin-top:4px;" id="radiusLabel">8px</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════
                 STEP 3 — Branding: logo + bg image
                 ══════════════════════════════════════════════════ --}}
            <div class="wz-step" data-step="3">
                <div class="wz-step-content">
                    <h2 class="wz-step-title">{{ __('forms.wz_step3_title') }}</h2>
                    <p class="wz-step-sub">{{ __('forms.wz_step3_sub') }}</p>

                    <div class="wz-field">
                        <div class="wz-toggle-row" onclick="toggleField('enable_logo','logoToggle','logoUploadWrap')">
                            <div class="wz-toggle on" id="logoToggle"></div>
                            <div class="wz-toggle-text">
                                <div class="wz-toggle-title">{{ __('forms.enable_logo') }}</div>
                                <div class="wz-toggle-desc">{{ __('forms.enable_logo_desc') }}</div>
                            </div>
                        </div>
                        <div id="logoUploadWrap">
                            <div class="wz-dropzone" id="logoDropzone" onclick="document.getElementById('logoFile').click()">
                                <i class="bi bi-cloud-arrow-up" style="font-size:24px;color:#0085f3;display:block;margin-bottom:6px;"></i>
                                <div style="font-size:13px;font-weight:600;color:#374151;">{{ __('forms.upload_logo_hint') }}</div>
                                <div style="font-size:11px;color:#9ca3af;margin-top:2px;">JPG, PNG, WebP · max 2MB</div>
                            </div>
                            <input type="file" id="logoFile" accept="image/png,image/jpeg,image/webp" style="display:none;" onchange="previewFile(this,'logoPreview','logoDropzone')">
                            <div class="wz-dropzone-preview" id="logoPreview" style="display:none;">
                                <img id="logoPreviewImg" src="">
                                <br><button type="button" onclick="clearUpload('logoFile','logoPreview','logoDropzone')" style="font-size:12px;color:#dc2626;background:none;border:none;cursor:pointer;margin-top:6px;"><i class="bi bi-trash3"></i> {{ __('forms.remove') }}</button>
                            </div>
                        </div>
                    </div>

                    <div class="wz-field">
                        <div class="wz-toggle-row" onclick="toggleField('enable_background_image','bgToggle','bgUploadWrap')">
                            <div class="wz-toggle" id="bgToggle"></div>
                            <div class="wz-toggle-text">
                                <div class="wz-toggle-title">{{ __('forms.enable_bg_image') }}</div>
                                <div class="wz-toggle-desc">{{ __('forms.enable_bg_image_desc') }}</div>
                            </div>
                        </div>
                        <div id="bgUploadWrap" style="display:none;">
                            <div class="wz-dropzone" id="bgDropzone" onclick="document.getElementById('bgFile').click()">
                                <i class="bi bi-image" style="font-size:24px;color:#0085f3;display:block;margin-bottom:6px;"></i>
                                <div style="font-size:13px;font-weight:600;color:#374151;">{{ __('forms.upload_bg_hint') }}</div>
                                <div style="font-size:11px;color:#9ca3af;margin-top:2px;">JPG, PNG, WebP · max 5MB</div>
                            </div>
                            <input type="file" id="bgFile" accept="image/png,image/jpeg,image/webp" style="display:none;" onchange="previewFile(this,'bgPreview','bgDropzone')">
                            <div class="wz-dropzone-preview" id="bgPreview" style="display:none;">
                                <img id="bgPreviewImg" src="">
                                <br><button type="button" onclick="clearUpload('bgFile','bgPreview','bgDropzone')" style="font-size:12px;color:#dc2626;background:none;border:none;cursor:pointer;margin-top:6px;"><i class="bi bi-trash3"></i> {{ __('forms.remove') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════
                 STEP 4 — Destino: pipeline / stage / user / UTM
                 ══════════════════════════════════════════════════ --}}
            <div class="wz-step" data-step="4">
                <div class="wz-step-content">
                    <h2 class="wz-step-title">{{ __('forms.wz_step4_title') }}</h2>
                    <p class="wz-step-sub">{{ __('forms.wz_step4_sub') }}</p>

                    <div class="wz-row">
                        <div class="wz-field">
                            <label class="wz-label">{{ __('forms.pipeline') }}</label>
                            <select id="f_pipeline" class="wz-select" onchange="updateStages()">
                                <option value="">—</option>
                                @foreach($pipelines as $p)
                                    <option value="{{ $p->id }}" data-stages="{{ $p->stages->toJson() }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="wz-field">
                            <label class="wz-label">{{ __('forms.stage') }}</label>
                            <select id="f_stage" class="wz-select"></select>
                        </div>
                    </div>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('forms.assigned_to') }}</label>
                        <select id="f_assigned" class="wz-select">
                            <option value="">{{ __('forms.no_assignment') }}</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('forms.source_utm') }}</label>
                        <input type="text" id="f_source" class="wz-input" placeholder="{{ __('forms.source_utm_ph') }}">
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════
                 STEP 5 — Envio: confirmation + notify
                 ══════════════════════════════════════════════════ --}}
            <div class="wz-step" data-step="5">
                <div class="wz-step-content">
                    <h2 class="wz-step-title">{{ __('forms.wz_step5_title') }}</h2>
                    <p class="wz-step-sub">{{ __('forms.wz_step5_sub') }}</p>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('forms.confirmation') }}</label>
                        <select id="f_confType" class="wz-select" style="margin-bottom:10px;">
                            <option value="message">{{ __('forms.confirmation_message') }}</option>
                            <option value="redirect">{{ __('forms.confirmation_redirect') }}</option>
                        </select>
                        <input type="text" id="f_confValue" class="wz-input" placeholder="{{ __('forms.confirmation_value_ph') }}">
                    </div>

                    <div class="wz-field">
                        <label class="wz-label">{{ __('forms.notify_emails') }}</label>
                        <input type="text" id="f_notify" class="wz-input" placeholder="{{ __('forms.notify_emails_ph') }}">
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="wz-footer">
                <button type="button" class="wz-btn wz-btn-back" id="wzBackBtn" onclick="wzBack()" style="visibility:hidden;">
                    <i class="bi bi-arrow-left"></i> {{ __('forms.wizard_back') }}
                </button>
                <div style="flex:1;"></div>
                <button type="button" class="wz-btn wz-btn-next" id="wzNextBtn" onclick="wzNext()">
                    {{ __('forms.wizard_next') }} <i class="bi bi-arrow-right"></i>
                </button>
                <button type="button" class="wz-btn wz-btn-create" id="wzCreateBtn" style="display:none;" onclick="wizardSubmit()">
                    <i class="bi bi-check-circle"></i> {{ __('forms.wizard_create') }}
                </button>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const TOTAL_STEPS = 5;
const STORE_URL = @json(route('forms.store'));
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const LANG = @json(__('forms'));
let currentStep = 1;

// Per-color palettes: each row shows these presets + "+" custom picker
const COLOR_OPTIONS = {
    brand:       { label: LANG.color_brand,        presets: ['#0085f3','#10b981','#8b5cf6','#f43f5e','#f59e0b','#475569','#1a1d23'], default: '#0085f3' },
    background:  { label: LANG.color_bg,           presets: ['#ffffff','#f8fafc','#f0fdf4','#faf5ff','#fff1f2','#fffbeb','#0f172a'], default: '#ffffff' },
    card:        { label: LANG.color_card,         presets: ['#ffffff','#f8fafc','#f1f5f9','#1e293b','#0f172a'], default: '#ffffff' },
    button:      { label: LANG.color_button,       presets: ['#0085f3','#10b981','#8b5cf6','#f43f5e','#f59e0b','#475569','#1a1d23'], default: '#0085f3' },
    buttonText:  { label: LANG.color_button_text,  presets: ['#ffffff','#f1f5f9','#1a1d23','#000000'], default: '#ffffff' },
    label:       { label: LANG.color_label,        presets: ['#1a1d23','#374151','#6b7280','#ffffff','#e2e8f0'], default: '#374151' },
    inputBorder: { label: LANG.color_input_border, presets: ['#e5e7eb','#d1d5db','#bfdbfe','#a7f3d0','#334155'], default: '#e5e7eb' },
    inputBg:     { label: LANG.color_input_bg,     presets: ['#ffffff','#f8fafc','#f9fafb','#0f172a','#1e293b'], default: '#ffffff' },
    inputText:   { label: LANG.color_input_text,   presets: ['#1a1d23','#374151','#ffffff','#f1f5f9'], default: '#1a1d23' },
};

const state = {
    name: '', type: 'classic', layout: 'centered',
    enable_logo: true, enable_background_image: false,
    colors: {
        brand: '#0085f3', background: '#ffffff', card: '#ffffff',
        button: '#0085f3', buttonText: '#ffffff', label: '#374151',
        inputBorder: '#e5e7eb', inputBg: '#ffffff', inputText: '#1a1d23',
    },
};

// ── Navigation (wz pattern) ─────────────────────────────────
function wzGoStep(n) {
    document.querySelectorAll('.wz-step').forEach(s => s.classList.remove('active'));
    document.querySelector('.wz-step[data-step="' + n + '"]').classList.add('active');
    currentStep = n;

    for (let i = 1; i <= TOTAL_STEPS; i++) {
        const dot = document.getElementById('wz-dot-' + i);
        dot.className = 'wz-dot' + (i === n ? ' active' : (i < n ? ' done' : ''));
    }

    document.getElementById('wzBackBtn').style.visibility = (n === 1) ? 'hidden' : 'visible';
    if (n === TOTAL_STEPS) {
        document.getElementById('wzNextBtn').style.display = 'none';
        document.getElementById('wzCreateBtn').style.display = 'inline-flex';
    } else {
        document.getElementById('wzNextBtn').style.display = 'inline-flex';
        document.getElementById('wzCreateBtn').style.display = 'none';
    }

    document.querySelector('.wz-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function wzValidateStep(n) {
    if (n === 1) {
        const name = document.getElementById('f_name').value.trim();
        if (!name) {
            (window.toastr?.warning || alert)(LANG.toast_name_required || 'Nome obrigatório');
            document.getElementById('f_name').focus();
            return false;
        }
    }
    return true;
}

function wzNext() {
    if (!wzValidateStep(currentStep)) return;
    if (currentStep < TOTAL_STEPS) wzGoStep(currentStep + 1);
}

function wzBack() { if (currentStep > 1) wzGoStep(currentStep - 1); }

function wzSelectCard(el, fieldName) {
    el.parentElement.querySelectorAll('.wz-card-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    state[fieldName] = el.dataset.value;
}

// ── Per-color rows ──────────────────────────────────────────
function buildColorRows() {
    const container = document.getElementById('colorRows');
    let html = '';
    Object.entries(COLOR_OPTIONS).forEach(([key, cfg]) => {
        const current = state.colors[key];
        let dotsHtml = cfg.presets.map(color => {
            const sel = current.toLowerCase() === color.toLowerCase() ? 'selected' : '';
            return `<div class="wz-color-dot ${sel}" style="background:${color};" onclick="pickColor('${key}','${color}')"><span class="dot-check"><i class="bi bi-check"></i></span></div>`;
        }).join('');
        // "+" custom button (native color picker)
        dotsHtml += `<label class="wz-color-custom-btn" title="Custom"><i class="bi bi-plus"></i><input type="color" value="${current}" oninput="pickColor('${key}',this.value)"></label>`;

        html += `<div class="wz-color-row">
            <div class="wz-color-row-label"><span>${cfg.label}</span><span class="current">${current.toUpperCase()}</span></div>
            <div class="wz-color-dots">${dotsHtml}</div>
        </div>`;
    });
    container.innerHTML = html;
}

function pickColor(key, color) {
    state.colors[key] = color;
    buildColorRows();
}

// ── Toggles ─────────────────────────────────────────────────
function toggleField(field, toggleId, wrapId) {
    state[field] = !state[field];
    document.getElementById(toggleId).classList.toggle('on', state[field]);
    if (wrapId) document.getElementById(wrapId).style.display = state[field] ? '' : 'none';
}

// ── File upload ─────────────────────────────────────────────
function previewFile(input, previewId, dropzoneId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const preview = document.getElementById(previewId);
            preview.querySelector('img').src = e.target.result;
            preview.style.display = '';
            document.getElementById(dropzoneId).style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function clearUpload(inputId, previewId, dropzoneId) {
    document.getElementById(inputId).value = '';
    document.getElementById(previewId).style.display = 'none';
    document.getElementById(dropzoneId).style.display = '';
}

// ── Stages ──────────────────────────────────────────────────
function updateStages() {
    const sel = document.getElementById('f_pipeline');
    const opt = sel.options[sel.selectedIndex];
    const stages = opt.dataset.stages ? JSON.parse(opt.dataset.stages) : [];
    document.getElementById('f_stage').innerHTML = stages.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
}

// ── Radius label ────────────────────────────────────────────
document.getElementById('f_radius')?.addEventListener('input', function() {
    document.getElementById('radiusLabel').textContent = this.value + 'px';
});

// ── Submit ──────────────────────────────────────────────────
async function wizardSubmit() {
    state.name = document.getElementById('f_name').value.trim();
    const notify = document.getElementById('f_notify').value.trim();
    const emails = notify ? notify.split(',').map(e => e.trim()).filter(Boolean) : [];

    const btn = document.getElementById('wzCreateBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + (LANG.wizard_creating || 'Criando...');

    const body = {
        name: state.name,
        type: state.type,
        layout: state.layout,
        color_preset: 'custom',
        brand_color: state.colors.brand,
        background_color: state.colors.background,
        card_color: state.colors.card,
        button_color: state.colors.button,
        button_text_color: state.colors.buttonText,
        label_color: state.colors.label,
        input_border_color: state.colors.inputBorder,
        input_bg_color: state.colors.inputBg,
        input_text_color: state.colors.inputText,
        font_family: document.getElementById('f_font').value,
        border_radius: parseInt(document.getElementById('f_radius').value),
        enable_logo: state.enable_logo,
        enable_background_image: state.enable_background_image,
        pipeline_id: document.getElementById('f_pipeline').value || null,
        stage_id: document.getElementById('f_stage').value || null,
        assigned_user_id: document.getElementById('f_assigned').value || null,
        source_utm: document.getElementById('f_source').value.trim() || null,
        confirmation_type: document.getElementById('f_confType').value,
        confirmation_value: document.getElementById('f_confValue').value.trim() || null,
        notify_emails: emails.length ? emails : null,
    };

    try {
        const res = await fetch(STORE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if (res.ok && data.success) {
            const logoFile = document.getElementById('logoFile').files[0];
            if (logoFile && data.form?.id) {
                const fd = new FormData(); fd.append('logo', logoFile);
                await fetch(`{{ url('formularios') }}/${data.form.id}/upload-logo`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd });
            }
            const bgFile = document.getElementById('bgFile').files[0];
            if (bgFile && data.form?.id) {
                const fd = new FormData(); fd.append('background', bgFile);
                await fetch(`{{ url('formularios') }}/${data.form.id}/upload-background`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd });
            }
            toastr.success(LANG.toast_created || 'Formulário criado!');
            setTimeout(() => window.location.href = data.redirect, 500);
            return;
        }

        const msg = (data.errors ? Object.values(data.errors).flat().join(' · ') : data.message) || 'Erro';
        toastr.error(msg);
    } catch (e) {
        toastr.error('Erro de conexão');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> ' + (LANG.wizard_create || 'Criar formulário');
    }
}

// Init
buildColorRows();
wzGoStep(1);
</script>
@endpush
