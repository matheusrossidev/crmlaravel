@extends('tenant.layouts.app')

@php
    $title = __('forms.edit_title') . ': ' . $form->name;
    $pageIcon = 'bi-pencil';
@endphp

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────────────── */
.fe-layout { display:grid;grid-template-columns:240px 1fr 1fr;gap:16px;align-items:start; }
.fe-settings-col { display:flex;flex-direction:column;gap:16px;min-width:0; }
.fe-preview-col { position:sticky;top:80px;min-width:0; }

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.fe-sidebar { background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;padding:20px;position:sticky;top:80px; }
.fe-form-name { font-size:15px;font-weight:700;color:#1a1d23;margin-bottom:4px; }
.fe-badges { display:flex;gap:6px;margin-bottom:14px; }
.fe-badge { font-size:10px;font-weight:600;padding:2px 8px;border-radius:99px; }
.fe-links { display:flex;flex-direction:column;gap:4px;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid #f0f2f7; }
.fe-link { display:flex;align-items:center;gap:8px;padding:6px 10px;font-size:12px;font-weight:600;color:#0085f3;text-decoration:none;border-radius:8px;transition:.15s; }
.fe-link:hover { background:#eff6ff; }
.fe-link i { font-size:14px; }
.fe-tabs { display:flex;flex-direction:column;gap:2px; }
.fe-tab { display:flex;align-items:center;gap:10px;padding:10px 12px;border:none;background:none;border-radius:9px;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;width:100%;text-align:left;transition:.15s; }
.fe-tab i { font-size:17px;width:20px;text-align:center; }
.fe-tab:hover { background:#f4f6fb;color:#1a1d23; }
.fe-tab.active { background:#eff6ff;color:#0085f3; }

/* ── Main area ───────────────────────────────────────────────────────────── */
.fe-main { display:flex;flex-direction:column;gap:20px; }
.fe-pane { background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;padding:28px 32px;min-height:200px;display:none; }
.fe-pane.active { display:block;animation:feIn .25s ease; }
@keyframes feIn { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }

.fe-pane h3 { font-size:15px;font-weight:700;color:#1a1d23;margin:0 0 18px;display:flex;align-items:center;gap:8px; }
.fe-pane label { font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;margin-top:14px; }
.fe-pane label:first-child { margin-top:0; }

/* Preview */
.fe-preview-details { background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;overflow:hidden; }
.fe-preview-details[open] { display:flex;flex-direction:column;height:calc(100vh - 110px); }
.fe-preview-details > summary { display:none; }
@media (max-width:900px) {
    .fe-preview-details { display:block; }
    .fe-preview-details[open] { height:auto; }
    .fe-preview-details .fe-preview-header h4 { display:none; }
    .fe-preview-details .fe-preview-header { justify-content:flex-end;padding:8px 14px; }
    .fe-preview-details > summary {
        display:flex;align-items:center;justify-content:space-between;
        padding:14px 18px;cursor:pointer;list-style:none;
        font-size:13px;font-weight:700;color:#1a1d23;
    }
    .fe-preview-details > summary::-webkit-details-marker { display:none; }
    .fe-preview-details > summary::after { content:'\\203A';transform:rotate(90deg);transition:transform .2s;display:inline-block;color:#9ca3af;font-size:18px;font-weight:400; }
    .fe-preview-details[open] > summary::after { transform:rotate(-90deg); }
    .fe-preview-details[open] > summary { border-bottom:1px solid #f0f2f7; }
    .fe-preview-wrap { border:none;border-radius:0;height:auto; }
}
.fe-preview-wrap { background:#fff;overflow:hidden;display:flex;flex-direction:column;height:calc(100vh - 110px); }
.fe-preview-header { display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-bottom:1px solid #f0f2f7;flex-shrink:0; }
.fe-preview-header h4 { font-size:13px;font-weight:700;color:#1a1d23;margin:0; }
.fe-preview-toggle { display:flex;gap:4px; }
.fe-preview-toggle button { padding:4px 12px;font-size:11px;font-weight:600;border:1.5px solid #e8eaf0;border-radius:6px;background:#fff;color:#6b7280;cursor:pointer;transition:.15s; }
.fe-preview-toggle button.active { background:#eff6ff;color:#0085f3;border-color:#bfdbfe; }
.fe-preview-frame { width:100%;border:none;flex:1;transition:width .3s ease;margin:0 auto;display:block;background:#f9fafb; }

/* Toggle */
.toggle-wrap { display:flex;align-items:center;gap:14px;padding:14px 16px;background:#f8fafc;border:1px solid #e8eaf0;border-radius:10px;margin-bottom:10px;cursor:pointer;user-select:none; }
.toggle-switch { width:44px;height:24px;border-radius:12px;background:#e5e7eb;position:relative;flex-shrink:0;transition:background .2s; }
.toggle-switch.on { background:#0085f3; }
.toggle-switch::after { content:'';position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;transition:left .2s;box-shadow:0 1px 3px rgba(0,0,0,.1); }
.toggle-switch.on::after { left:23px; }

/* Per-color rows */
.fe-color-row { margin-bottom:14px; }
.fe-color-row-label { font-size:12px;font-weight:600;color:#374151;margin-bottom:7px;display:flex;align-items:center;justify-content:space-between; }
.fe-color-row-label .current { font-family:monospace;font-size:11px;color:#9ca3af; }
.fe-color-dots { display:flex;gap:8px;flex-wrap:wrap;align-items:center; }
.fe-color-dot { width:30px;height:30px;border-radius:50%;cursor:pointer;transition:all .15s;border:2px solid transparent;position:relative;box-shadow:0 0 0 1px rgba(0,0,0,.08); }
.fe-color-dot:hover { transform:scale(1.15); }
.fe-color-dot.selected { border-color:#0085f3;box-shadow:0 0 0 1px #fff inset,0 4px 10px rgba(0,133,243,.3); }
.fe-color-dot .dot-check { position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;opacity:0;text-shadow:0 1px 2px rgba(0,0,0,.3); }
.fe-color-dot.selected .dot-check { opacity:1; }
.fe-color-custom-btn {
    width:30px;height:30px;border-radius:50%;border:2px dashed #d1d5db;display:inline-flex;align-items:center;justify-content:center;
    cursor:pointer;color:#9ca3af;font-size:14px;line-height:1;transition:all .15s;position:relative;overflow:hidden;margin:0;padding:0;
}
.fe-color-custom-btn i { display:block;line-height:1; }
.fe-color-custom-btn:hover { border-color:#0085f3;color:#0085f3; }
.fe-color-custom-btn input[type=color] { position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;border:none;padding:0; }

/* Layout cards */
.layout-cards { display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px; }
.layout-card { display:flex;flex-direction:column;align-items:center;gap:8px;padding:14px 10px;border:2px solid #e8eaf0;border-radius:12px;cursor:pointer;transition:.15s;text-align:center; }
.layout-card:hover { border-color:#93c5fd;background:#f8faff; }
.layout-card.selected { border-color:#3B82F6;background:#eff6ff; }
.layout-card .card-label { font-size:12px;font-weight:600;color:#1a1d23; }
.layout-thumb { width:72px;height:48px;border-radius:6px;background:#f0f2f7;position:relative;overflow:hidden; }
.layout-thumb .lt-card { position:absolute;background:#fff;border:1.5px solid #d1d5db;border-radius:3px;width:28px;height:34px;top:7px; }
.layout-thumb.left .lt-card { left:6px; }
.layout-thumb.centered .lt-card { left:50%;transform:translateX(-50%); }
.layout-thumb.right .lt-card { right:6px; }

/* Dropzone */
.wiz-dropzone { border:2px dashed #d1d5db;border-radius:10px;padding:20px 16px;text-align:center;cursor:pointer;transition:all .2s; }
.wiz-dropzone:hover { border-color:#0085f3;background:#f8faff; }

/* Save bar */
.fe-save-bar { position:sticky;bottom:0;background:#fff;border-top:1px solid #f0f2f7;padding:16px 0 6px;margin-top:20px;display:flex;gap:10px;align-items:center;z-index:10; }
.fe-save-bar .btn-save { padding:10px 28px;border-radius:100px;border:none;background:#0085f3;color:#fff;font-size:13.5px;font-weight:600;cursor:pointer;transition:.15s; }
.fe-save-bar .btn-save:hover { background:#0070d1; }
.fe-save-bar .btn-cancel { padding:10px 20px;border-radius:100px;border:1.5px solid #e8eaf0;background:#fff;font-size:13.5px;font-weight:600;color:#6b7280;cursor:pointer;text-decoration:none; }
.fe-save-bar .btn-cancel:hover { background:#f0f2f7; }

/* ── Tablet (≤1200px): stack preview below, sidebar + settings side-by-side ── */
@media (max-width:1200px) {
    .fe-layout { grid-template-columns:220px 1fr; }
    .fe-preview-col { grid-column:1 / -1;position:static; }
    .fe-preview-wrap { height:auto;max-height:70vh; }
    .fe-preview-frame { min-height:500px; }
}

/* ── Mobile (≤900px): tudo em 1 coluna com header compacto ── */
@media (max-width:900px) {
    .fe-layout { grid-template-columns:1fr;gap:12px; }

    /* Sidebar vira header compacto no topo */
    .fe-sidebar { position:static;padding:14px 16px; }
    .fe-form-name { font-size:16px;margin-bottom:2px; }
    .fe-badges { margin-bottom:10px; }

    /* Quick links viram ícones horizontais */
    .fe-links {
        flex-direction:row;flex-wrap:wrap;gap:4px;margin-bottom:10px;padding-bottom:10px;
    }
    .fe-link {
        flex:1;min-width:auto;justify-content:center;gap:4px;padding:8px 6px;font-size:11px;
        background:#f8fafc;border:1px solid #f0f2f7;
    }
    .fe-link span { display:none; } /* esconde texto longo se tiver */

    /* Tabs horizontais com scroll */
    .fe-tabs {
        flex-direction:row;overflow-x:auto;gap:2px;
        margin:-4px -16px -4px;padding:4px 16px;
        -webkit-overflow-scrolling:touch;scrollbar-width:none;
    }
    .fe-tabs::-webkit-scrollbar { display:none; }
    .fe-tab { white-space:nowrap;padding:8px 14px;flex-shrink:0;font-size:12.5px; }
    .fe-tab i { font-size:14px;width:auto;margin-right:4px; }

    /* Panes com padding menor */
    .fe-pane { padding:18px 16px;border-radius:12px; }
    .fe-pane h3 { font-size:14px;margin-bottom:14px; }

    /* Preview collapsed (via details) */
    .fe-preview-col { grid-column:1;position:static; }
    .fe-preview-wrap { height:auto;max-height:none; }
    .fe-preview-frame { min-height:420px;height:420px; }

    /* Save bar: não sticky (menos atrito com teclado mobile) */
    .fe-save-bar { position:static;padding:14px 0;border-top:none;margin-top:12px;flex-wrap:wrap; }
    .fe-save-bar .btn-save, .fe-save-bar .btn-cancel { flex:1;text-align:center;padding:12px 20px; }

    /* Layout cards ficam confortáveis */
    .layout-cards { gap:8px; }
    .layout-card { padding:12px 6px; }
    .layout-thumb { width:56px;height:38px; }
    .layout-thumb .lt-card { width:20px;height:26px;top:6px; }

    /* Color rows: label quebra em cima do hex */
    .fe-color-row-label { flex-wrap:wrap;gap:4px; }

    /* Toggles com descrição quebra linha */
    .toggle-wrap { padding:12px 14px; }

    /* Distribution snippets: textarea menor */
    textarea[readonly] { font-size:11px !important;min-height:60px !important; }
}

/* ── Small mobile (≤480px) ── */
@media (max-width:480px) {
    .page-container { padding:10px !important; }
    .fe-pane { padding:16px 14px; }
    .fe-pane h3 { font-size:13.5px; }
    .layout-cards { grid-template-columns:1fr; }
    .layout-card { flex-direction:row;gap:12px;padding:10px 14px;text-align:left; }
    .layout-thumb { flex-shrink:0; }
}
</style>
@endpush

@section('content')
<div class="page-container">
    <div class="fe-layout">
        {{-- ── Sidebar ────────────────────────────────────── --}}
        <div class="fe-sidebar">
            <div class="fe-form-name">{{ $form->name }}</div>
            <div class="fe-badges">
                @php
                    $typeBg = match($form->type) { 'conversational' => 'background:#fef3c7;color:#92400e;', 'multistep' => 'background:#ede9fe;color:#5b21b6;', default => 'background:#eff6ff;color:#3b82f6;' };
                @endphp
                <span class="fe-badge" style="{{ $typeBg }}">{{ __('forms.type_' . $form->type) }}</span>
                <span class="fe-badge" style="{{ $form->is_active ? 'background:#ecfdf5;color:#059669;' : 'background:#f3f4f6;color:#6b7280;' }}">{{ $form->is_active ? __('forms.active') : __('forms.inactive') }}</span>
            </div>

            <div class="fe-links">
                <a href="{{ route('forms.builder', $form) }}" class="fe-link"><i class="bi bi-grid-3x3-gap"></i> Builder</a>
                <a href="{{ route('forms.mapping', $form) }}" class="fe-link"><i class="bi bi-arrow-left-right"></i> Mapeamento</a>
                <a href="{{ route('forms.submissions', $form) }}" class="fe-link"><i class="bi bi-inbox"></i> Envios</a>
                <a href="{{ $form->getPublicUrl() }}" target="_blank" class="fe-link"><i class="bi bi-box-arrow-up-right"></i> {{ __('forms.copy_link') }}</a>
            </div>

            <div class="fe-tabs">
                <button class="fe-tab active" onclick="switchPane('general',this)"><i class="bi bi-sliders"></i> {{ __('forms.edit_sect_general') }}</button>
                <button class="fe-tab" onclick="switchPane('layout',this)"><i class="bi bi-layout-split"></i> {{ __('forms.edit_sect_layout') }}</button>
                <button class="fe-tab" onclick="switchPane('colors',this)"><i class="bi bi-palette"></i> {{ __('forms.edit_sect_colors') }}</button>
                <button class="fe-tab" onclick="switchPane('branding',this)"><i class="bi bi-brush"></i> {{ __('forms.edit_sect_branding') }}</button>
                <button class="fe-tab" onclick="switchPane('destination',this)"><i class="bi bi-funnel"></i> {{ __('forms.edit_sect_destination') }}</button>
                <button class="fe-tab" onclick="switchPane('submission',this)"><i class="bi bi-send"></i> {{ __('forms.edit_sect_submission') }}</button>
                <button class="fe-tab" onclick="switchPane('distribution',this)"><i class="bi bi-share"></i> {{ __('forms.edit_sect_distribution') }}</button>
                <button class="fe-tab" onclick="switchPane('advanced',this)"><i class="bi bi-gear"></i> {{ __('forms.edit_sect_advanced') }}</button>
            </div>
        </div>

        {{-- ── Settings column (middle) ──────────────────── --}}
        <div class="fe-settings-col">
            {{-- General --}}
            <div class="fe-pane active" data-pane="general">
                <h3><i class="bi bi-sliders"></i> {{ __('forms.edit_sect_general') }}</h3>
                <label>{{ __('forms.form_name') }}</label>
                <input type="text" id="e_name" class="form-control" value="{{ $form->name }}" style="font-size:13px;">
                <label>{{ __('forms.form_type') }}</label>
                <select id="e_type" class="form-control" style="font-size:13px;">
                    <option value="classic" {{ $form->type === 'classic' ? 'selected' : '' }}>{{ __('forms.type_classic') }}</option>
                    <option value="conversational" {{ $form->type === 'conversational' ? 'selected' : '' }}>{{ __('forms.type_conversational') }}</option>
                    <option value="multistep" {{ $form->type === 'multistep' ? 'selected' : '' }}>{{ __('forms.type_multistep') }}</option>
                </select>
                <label>Slug (URL)</label>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:11px;color:#6b7280;">{{ rtrim(config('app.url'), '/') }}/f/</span>
                    <input type="text" id="e_slug" class="form-control" value="{{ $form->slug }}" style="font-size:13px;flex:1;">
                </div>
            </div>

            {{-- Layout --}}
            <div class="fe-pane" data-pane="layout">
                <h3><i class="bi bi-layout-split"></i> {{ __('forms.edit_sect_layout') }}</h3>
                <div class="layout-cards">
                    @foreach(['left' => __('forms.layout_left'), 'centered' => __('forms.layout_centered'), 'right' => __('forms.layout_right')] as $lk => $lv)
                    <div class="layout-card {{ ($form->layout ?? 'centered') === $lk ? 'selected' : '' }}" data-layout="{{ $lk }}" onclick="selectLayout(this,'{{ $lk }}')">
                        <div class="layout-thumb {{ $lk }}"><div class="lt-card"></div></div>
                        <span class="card-label">{{ $lv }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Colors --}}
            <div class="fe-pane" data-pane="colors">
                <h3><i class="bi bi-palette"></i> {{ __('forms.edit_sect_colors') }}</h3>
                <div id="editColorRows"></div>
            </div>

            {{-- Branding --}}
            <div class="fe-pane" data-pane="branding">
                <h3><i class="bi bi-brush"></i> {{ __('forms.edit_sect_branding') }}</h3>
                <div class="toggle-wrap" onclick="editToggle('enable_logo','eLogoToggle','eLogoWrap')">
                    <div class="toggle-switch {{ ($form->enable_logo ?? true) ? 'on' : '' }}" id="eLogoToggle"></div>
                    <div><div style="font-size:13px;font-weight:700;color:#1a1d23;">{{ __('forms.enable_logo') }}</div></div>
                </div>
                <div id="eLogoWrap" style="{{ ($form->enable_logo ?? true) ? '' : 'display:none;' }}">
                    <div id="eLogoPreview" style="{{ $form->logo_url ? '' : 'display:none;' }}text-align:center;margin-bottom:8px;">
                        <img id="eLogoImg" src="{{ $form->logo_url }}" style="max-height:60px;border-radius:8px;">
                        <br><button type="button" onclick="removeEditLogo()" style="font-size:11px;color:#dc2626;background:none;border:none;cursor:pointer;margin-top:4px;"><i class="bi bi-trash3"></i> {{ __('forms.remove') }}</button>
                    </div>
                    <div class="wiz-dropzone" id="eLogoDropzone" onclick="document.getElementById('eLogoFile').click()" style="{{ $form->logo_url ? 'display:none;' : '' }}">
                        <i class="bi bi-cloud-arrow-up" style="font-size:22px;color:#0085f3;display:block;margin-bottom:4px;"></i>
                        <div style="font-size:12px;font-weight:600;color:#374151;">{{ __('forms.upload_logo_hint') }}</div>
                    </div>
                    <input type="file" id="eLogoFile" accept="image/png,image/jpeg,image/webp" style="display:none;" onchange="editUploadLogo(this)">
                </div>

                <div style="margin-top:14px;">
                    <div class="toggle-wrap" onclick="editToggle('enable_background_image','eBgToggle','eBgWrap')">
                        <div class="toggle-switch {{ ($form->enable_background_image ?? false) ? 'on' : '' }}" id="eBgToggle"></div>
                        <div><div style="font-size:13px;font-weight:700;color:#1a1d23;">{{ __('forms.enable_bg_image') }}</div></div>
                    </div>
                    <div id="eBgWrap" style="{{ ($form->enable_background_image ?? false) ? '' : 'display:none;' }}">
                        <div id="eBgPreview" style="{{ $form->background_image_url ? '' : 'display:none;' }}text-align:center;margin-bottom:8px;">
                            <img id="eBgImg" src="{{ $form->background_image_url }}" style="max-height:80px;border-radius:8px;">
                            <br><button type="button" onclick="removeEditBg()" style="font-size:11px;color:#dc2626;background:none;border:none;cursor:pointer;margin-top:4px;"><i class="bi bi-trash3"></i> {{ __('forms.remove') }}</button>
                        </div>
                        <div class="wiz-dropzone" id="eBgDropzone" onclick="document.getElementById('eBgFile').click()" style="{{ $form->background_image_url ? 'display:none;' : '' }}">
                            <i class="bi bi-image" style="font-size:22px;color:#0085f3;display:block;margin-bottom:4px;"></i>
                            <div style="font-size:12px;font-weight:600;color:#374151;">{{ __('forms.upload_bg_hint') }}</div>
                        </div>
                        <input type="file" id="eBgFile" accept="image/png,image/jpeg,image/webp" style="display:none;" onchange="editUploadBg(this)">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px;">
                    <div>
                        <label style="margin-top:0;">{{ __('forms.font_family') }}</label>
                        <select id="e_font" class="form-control" style="font-size:13px;">
                            @foreach(['Inter', 'Plus Jakarta Sans', 'Poppins', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Georgia', 'Courier New'] as $font)
                                <option value="{{ $font }}" {{ ($form->font_family ?? 'Inter') === $font ? 'selected' : '' }}>{{ $font }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="margin-top:0;">Border radius</label>
                        <input type="range" id="e_radius" min="0" max="20" value="{{ $form->border_radius ?? 8 }}" style="width:100%;margin-top:8px;">
                        <div style="font-size:11px;color:#9ca3af;text-align:center;" id="eRadiusLabel">{{ $form->border_radius ?? 8 }}px</div>
                    </div>
                </div>
                <label>{{ __('forms.logo_alignment') }}</label>
                <select id="e_logoAlignment" class="form-control" style="font-size:13px;">
                    <option value="left" {{ ($form->logo_alignment ?? 'center') === 'left' ? 'selected' : '' }}>{{ __('forms.layout_left') }}</option>
                    <option value="center" {{ ($form->logo_alignment ?? 'center') === 'center' ? 'selected' : '' }}>{{ __('forms.layout_centered') }}</option>
                    <option value="right" {{ ($form->logo_alignment ?? 'center') === 'right' ? 'selected' : '' }}>{{ __('forms.layout_right') }}</option>
                </select>
            </div>

            {{-- Destination --}}
            <div class="fe-pane" data-pane="destination">
                <h3><i class="bi bi-funnel"></i> {{ __('forms.edit_sect_destination') }}</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="margin-top:0;">{{ __('forms.pipeline') }}</label>
                        <select id="e_pipeline" class="form-control" style="font-size:13px;" onchange="editUpdateStages()">
                            <option value="">—</option>
                            @foreach($pipelines as $p)
                                <option value="{{ $p->id }}" {{ $form->pipeline_id == $p->id ? 'selected' : '' }} data-stages="{{ $p->stages->toJson() }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="margin-top:0;">{{ __('forms.stage') }}</label>
                        <select id="e_stage" class="form-control" style="font-size:13px;"></select>
                    </div>
                </div>
                <label>{{ __('forms.assigned_to') }}</label>
                <select id="e_assigned" class="form-control" style="font-size:13px;">
                    <option value="">{{ __('forms.no_assignment') }}</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ $form->assigned_user_id == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
                <label>{{ __('forms.source_utm') }}</label>
                <input type="text" id="e_source" class="form-control" value="{{ $form->source_utm }}" placeholder="{{ __('forms.source_utm_ph') }}" style="font-size:13px;">
            </div>

            {{-- Submission --}}
            <div class="fe-pane" data-pane="submission">
                <h3><i class="bi bi-send"></i> {{ __('forms.edit_sect_submission') }}</h3>
                <label style="margin-top:0;">{{ __('forms.confirmation') }}</label>
                <select id="e_confType" class="form-control" style="font-size:13px;margin-bottom:8px;">
                    <option value="message" {{ $form->confirmation_type === 'message' ? 'selected' : '' }}>{{ __('forms.confirmation_message') }}</option>
                    <option value="redirect" {{ $form->confirmation_type === 'redirect' ? 'selected' : '' }}>{{ __('forms.confirmation_redirect') }}</option>
                </select>
                <input type="text" id="e_confValue" class="form-control" value="{{ $form->confirmation_value }}" placeholder="{{ __('forms.confirmation_value_ph') }}" style="font-size:13px;">
                <label>{{ __('forms.notify_emails') }}</label>
                <input type="text" id="e_notify" class="form-control" value="{{ implode(', ', $form->notify_emails ?? []) }}" placeholder="{{ __('forms.notify_emails_ph') }}" style="font-size:13px;">
                <label>{{ __('forms.max_submissions') }}</label>
                <input type="number" id="e_maxSubs" class="form-control" value="{{ $form->max_submissions }}" min="1" style="font-size:13px;">
                <label>{{ __('forms.expires_at') }}</label>
                <input type="date" id="e_expires" class="form-control" value="{{ $form->expires_at?->format('Y-m-d') }}" style="font-size:13px;">
            </div>

            {{-- Distribution --}}
            <div class="fe-pane" data-pane="distribution">
                <h3><i class="bi bi-share"></i> {{ __('forms.edit_sect_distribution') }}</h3>

                {{-- 6a. Public Link --}}
                <div style="margin-bottom:24px;">
                    <div style="font-size:13px;font-weight:700;color:#1a1d23;margin-bottom:8px;"><i class="bi bi-link-45deg" style="color:#0085f3;margin-right:4px;"></i> {{ __('forms.distribution_link') }}</div>
                    <div style="display:flex;gap:6px;">
                        <input type="text" readonly value="{{ $form->getPublicUrl() }}" id="publicUrlInput" class="form-control" style="font-size:12px;font-family:monospace;background:#f8fafc;">
                        <button type="button" onclick="copySnippet('publicUrlInput')" class="btn-primary-sm" style="border:none;"><i class="bi bi-clipboard"></i></button>
                    </div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:4px;">{{ $form->views_count_hosted ?? 0 }} {{ __('forms.views') }} · {{ $form->submissions_count ?? $form->submissions()->count() }} {{ __('forms.submissions') }}</div>
                </div>

                {{-- 6b. Embed inline --}}
                <div style="margin-bottom:24px;padding-top:20px;border-top:1px solid #f0f2f7;">
                    <div style="font-size:13px;font-weight:700;color:#1a1d23;margin-bottom:6px;"><i class="bi bi-code-slash" style="color:#0085f3;margin-right:4px;"></i> {{ __('forms.distribution_embed') }}</div>
                    <div style="font-size:12px;color:#6b7280;margin-bottom:10px;">{{ __('forms.distribution_hint_inline') }}</div>
                    @php
                        $embedScript = '<script src="' . rtrim(config('app.url'), '/') . '/api/form/' . $form->slug . '.js" data-form="' . $form->slug . '" data-mode="inline" async><' . '/script>';
                    @endphp
                    <textarea id="embedSnippet" readonly class="form-control" style="font-family:monospace;font-size:11.5px;min-height:70px;background:#f8fafc;resize:none;">{{ $embedScript }}</textarea>
                    <button type="button" onclick="copySnippet('embedSnippet')" class="btn-primary-sm" style="border:none;margin-top:6px;"><i class="bi bi-clipboard"></i> {{ __('forms.snippet_copy') }}</button>
                    <div style="font-size:11px;color:#9ca3af;margin-top:8px;">{{ $form->views_count_inline ?? 0 }} {{ __('forms.views') }}</div>
                </div>

                {{-- 6c. Popup widget --}}
                <div style="padding-top:20px;border-top:1px solid #f0f2f7;">
                    <div style="font-size:13px;font-weight:700;color:#1a1d23;margin-bottom:6px;"><i class="bi bi-window-dock" style="color:#0085f3;margin-right:4px;"></i> {{ __('forms.distribution_popup') }}</div>
                    <div style="font-size:12px;color:#6b7280;margin-bottom:12px;">{{ __('forms.distribution_hint_popup') }}</div>

                    <label style="margin-top:0;">{{ __('forms.widget_trigger') }}</label>
                    <select id="w_trigger" class="form-control" style="font-size:13px;" onchange="updatePopupSnippet()">
                        <option value="immediate" {{ ($form->widget_trigger ?? 'immediate') === 'immediate' ? 'selected' : '' }}>{{ __('forms.widget_trigger_immediate') }}</option>
                        <option value="time" {{ ($form->widget_trigger ?? '') === 'time' ? 'selected' : '' }}>{{ __('forms.widget_trigger_time') }}</option>
                        <option value="scroll" {{ ($form->widget_trigger ?? '') === 'scroll' ? 'selected' : '' }}>{{ __('forms.widget_trigger_scroll') }}</option>
                        <option value="exit" {{ ($form->widget_trigger ?? '') === 'exit' ? 'selected' : '' }}>{{ __('forms.widget_trigger_exit') }}</option>
                    </select>

                    <div id="delayRow" style="display:none;">
                        <label>{{ __('forms.widget_delay_seconds') }}</label>
                        <input type="number" id="w_delay" class="form-control" value="{{ $form->widget_delay ?? 5 }}" min="0" max="600" onchange="updatePopupSnippet()">
                    </div>

                    <div id="scrollRow" style="display:none;">
                        <label>{{ __('forms.widget_scroll_pct') }}</label>
                        <input type="number" id="w_scroll" class="form-control" value="{{ $form->widget_scroll_pct ?? 50 }}" min="0" max="100" onchange="updatePopupSnippet()">
                    </div>

                    <label>{{ __('forms.widget_position') }}</label>
                    <select id="w_position" class="form-control" style="font-size:13px;" onchange="updatePopupSnippet()">
                        <option value="center" {{ ($form->widget_position ?? 'center') === 'center' ? 'selected' : '' }}>{{ __('forms.widget_pos_center') }}</option>
                        <option value="bottom-right" {{ ($form->widget_position ?? '') === 'bottom-right' ? 'selected' : '' }}>{{ __('forms.widget_pos_bottom_right') }}</option>
                        <option value="bottom-left" {{ ($form->widget_position ?? '') === 'bottom-left' ? 'selected' : '' }}>{{ __('forms.widget_pos_bottom_left') }}</option>
                    </select>

                    <div class="toggle-wrap" onclick="toggleShowOnce()" style="margin-top:12px;">
                        <div class="toggle-switch {{ ($form->widget_show_once ?? true) ? 'on' : '' }}" id="wShowOnceToggle"></div>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#1a1d23;">{{ __('forms.widget_show_once') }}</div>
                            <div style="font-size:11.5px;color:#9ca3af;">{{ __('forms.widget_show_once_desc') }}</div>
                        </div>
                    </div>

                    <div style="margin-top:14px;">
                        <textarea id="popupSnippet" readonly class="form-control" style="font-family:monospace;font-size:11.5px;min-height:90px;background:#f8fafc;resize:none;"></textarea>
                        <button type="button" onclick="copySnippet('popupSnippet')" class="btn-primary-sm" style="border:none;margin-top:6px;"><i class="bi bi-clipboard"></i> {{ __('forms.snippet_copy') }}</button>
                    </div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:8px;">{{ $form->views_count_popup ?? 0 }} {{ __('forms.views') }}</div>
                </div>
            </div>

            {{-- Advanced --}}
            <div class="fe-pane" data-pane="advanced">
                <h3><i class="bi bi-gear"></i> {{ __('forms.edit_sect_advanced') }}</h3>
                <div class="toggle-wrap" onclick="editToggleActive()">
                    <div class="toggle-switch {{ $form->is_active ? 'on' : '' }}" id="eActiveToggle"></div>
                    <div>
                        <div style="font-size:13px;font-weight:700;color:#1a1d23;">{{ __('forms.form_active') }}</div>
                        <div style="font-size:11.5px;color:#9ca3af;">{{ __('forms.form_active_desc') }}</div>
                    </div>
                </div>

                {{-- Campos de telefone --}}
                @php
                    $allCountries = [
                        'BR' => ['Brasil', '🇧🇷'],
                        'US' => ['Estados Unidos', '🇺🇸'],
                        'PT' => ['Portugal', '🇵🇹'],
                        'AR' => ['Argentina', '🇦🇷'],
                        'ES' => ['Espanha', '🇪🇸'],
                        'MX' => ['México', '🇲🇽'],
                        'GB' => ['Reino Unido', '🇬🇧'],
                        'FR' => ['França', '🇫🇷'],
                        'DE' => ['Alemanha', '🇩🇪'],
                        'IT' => ['Itália', '🇮🇹'],
                        'CL' => ['Chile', '🇨🇱'],
                        'CO' => ['Colômbia', '🇨🇴'],
                        'PE' => ['Peru', '🇵🇪'],
                        'UY' => ['Uruguai', '🇺🇾'],
                        'PY' => ['Paraguai', '🇵🇾'],
                        'CA' => ['Canadá', '🇨🇦'],
                        'AU' => ['Austrália', '🇦🇺'],
                        'JP' => ['Japão', '🇯🇵'],
                    ];
                    $defaultCountry = $form->default_country ?? 'BR';
                    $allowedCountries = $form->allowed_countries ?? [];
                    $restrictCountries = ! empty($allowedCountries);
                @endphp
                <div style="margin-top:24px;padding-top:20px;border-top:1px solid #f0f2f7;">
                    <div style="font-size:13px;font-weight:700;color:#1a1d23;margin-bottom:4px;">
                        <i class="bi bi-telephone" style="color:#0085f3;"></i> Campos de telefone
                    </div>
                    <div style="font-size:12px;color:#6b7280;line-height:1.45;margin-bottom:14px;">
                        Configura máscara, bandeiras e validação dos campos do tipo "Telefone" deste formulário.
                    </div>

                    <label style="margin-top:0;">País que abre selecionado</label>
                    <select id="e_defaultCountry" class="form-control" style="font-size:13px;">
                        @foreach($allCountries as $iso => [$name, $flag])
                            <option value="{{ $iso }}" {{ $defaultCountry === $iso ? 'selected' : '' }}>{{ $flag }} {{ $name }}</option>
                        @endforeach
                    </select>

                    <label style="margin-top:14px;">Bandeiras disponíveis no seletor</label>
                    <div style="display:flex;gap:16px;margin-top:4px;">
                        <label style="display:flex;align-items:center;gap:6px;font-weight:500;cursor:pointer;">
                            <input type="radio" name="e_restrict" value="0" {{ $restrictCountries ? '' : 'checked' }} onchange="toggleCountriesSelector()">
                            Todas (~250 países)
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;font-weight:500;cursor:pointer;">
                            <input type="radio" name="e_restrict" value="1" {{ $restrictCountries ? 'checked' : '' }} onchange="toggleCountriesSelector()">
                            Só os que eu marcar
                        </label>
                    </div>

                    <div id="e_countriesBox" style="{{ $restrictCountries ? '' : 'display:none;' }}margin-top:10px;padding:12px;background:#f9fafb;border:1px solid #e8eaf0;border-radius:10px;">
                        <div style="display:grid;grid-template-columns:repeat(2, 1fr);gap:6px;">
                            @foreach($allCountries as $iso => [$name, $flag])
                                <label style="display:flex;align-items:center;gap:6px;font-size:12.5px;cursor:pointer;">
                                    <input type="checkbox" class="e-country-cb" value="{{ $iso }}" {{ in_array($iso, $allowedCountries) ? 'checked' : '' }}>
                                    {{ $flag }} {{ $name }}
                                </label>
                            @endforeach
                        </div>
                        <div style="font-size:11px;color:#9ca3af;margin-top:8px;">
                            Marque pelo menos 1. Se desmarcar tudo, volta pro modo "Todas".
                        </div>
                    </div>
                </div>

                <div style="margin-top:24px;padding-top:20px;border-top:1px solid #f0f2f7;">
                    <button onclick="deleteForm()" style="padding:10px 20px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                        <i class="bi bi-trash3"></i> {{ __('common.delete') }}
                    </button>
                </div>
            </div>

            {{-- Save bar --}}
            <div class="fe-save-bar">
                <button class="btn-save" onclick="saveForm()"><i class="bi bi-check-lg"></i> {{ __('forms.save') }}</button>
                <a href="{{ route('forms.index') }}" class="btn-cancel">{{ __('common.cancel') }}</a>
            </div>
        </div>

        {{-- ── Preview column (right, sticky) ──────────────── --}}
        <div class="fe-preview-col">
            <details class="fe-preview-details" open>
                <summary><span><i class="bi bi-eye" style="margin-right:6px;color:#0085f3;"></i> {{ __('forms.live_preview') }}</span></summary>
                <div class="fe-preview-wrap">
                    <div class="fe-preview-header">
                        <h4><i class="bi bi-eye" style="margin-right:6px;"></i> {{ __('forms.live_preview') }}</h4>
                        <div class="fe-preview-toggle">
                            <button class="active" onclick="setPreviewSize('100%',this)"><i class="bi bi-display"></i> Desktop</button>
                            <button onclick="setPreviewSize('375px',this)"><i class="bi bi-phone"></i> Mobile</button>
                        </div>
                    </div>
                    <iframe src="{{ $form->getPublicUrl() }}?preview=1" id="previewFrame" class="fe-preview-frame"></iframe>
                </div>
            </details>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const FORM_ID = {{ $form->id }};
const CURRENT_STAGE = {{ $form->stage_id ?? 'null' }};
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let editActiveState = {{ $form->is_active ? 'true' : 'false' }};
let editEnableLogo = {{ ($form->enable_logo ?? true) ? 'true' : 'false' }};
let editEnableBg = {{ ($form->enable_background_image ?? false) ? 'true' : 'false' }};
let editWidgetShowOnce = {{ ($form->widget_show_once ?? true) ? 'true' : 'false' }};
const FORM_SLUG = '{{ $form->slug }}';
const APP_URL = '{{ rtrim(config("app.url"), "/") }}';
const LANG = @json(__('forms'));

const COLOR_OPTIONS = {
    brand:       { label: LANG.color_brand,        presets: ['#0085f3','#10b981','#8b5cf6','#f43f5e','#f59e0b','#475569','#1a1d23'], key: 'brand_color' },
    background:  { label: LANG.color_bg,           presets: ['#ffffff','#f8fafc','#f0fdf4','#faf5ff','#fff1f2','#fffbeb','#0f172a'], key: 'background_color' },
    card:        { label: LANG.color_card,         presets: ['#ffffff','#f8fafc','#f1f5f9','#1e293b','#0f172a'], key: 'card_color' },
    button:      { label: LANG.color_button,       presets: ['#0085f3','#10b981','#8b5cf6','#f43f5e','#f59e0b','#475569','#1a1d23'], key: 'button_color' },
    buttonText:  { label: LANG.color_button_text,  presets: ['#ffffff','#f1f5f9','#1a1d23','#000000'], key: 'button_text_color' },
    label:       { label: LANG.color_label,        presets: ['#1a1d23','#374151','#6b7280','#ffffff','#e2e8f0'], key: 'label_color' },
    inputBorder: { label: LANG.color_input_border, presets: ['#e5e7eb','#d1d5db','#bfdbfe','#a7f3d0','#334155'], key: 'input_border_color' },
    inputBg:     { label: LANG.color_input_bg,     presets: ['#ffffff','#f8fafc','#f9fafb','#0f172a','#1e293b'], key: 'input_bg_color' },
    inputText:   { label: LANG.color_input_text,   presets: ['#1a1d23','#374151','#ffffff','#f1f5f9'], key: 'input_text_color' },
};

const editColors = {
    brand: '{{ $form->brand_color ?? "#0085f3" }}',
    background: '{{ $form->background_color ?? "#ffffff" }}',
    card: '{{ $form->card_color ?? "#ffffff" }}',
    button: '{{ $form->button_color ?? "#0085f3" }}',
    buttonText: '{{ $form->button_text_color ?? "#ffffff" }}',
    label: '{{ $form->label_color ?? "#374151" }}',
    inputBorder: '{{ $form->input_border_color ?? "#e5e7eb" }}',
    inputBg: '{{ $form->input_bg_color ?? "#ffffff" }}',
    inputText: '{{ $form->input_text_color ?? "#1a1d23" }}',
};

// ── Pane switching ──────────────────────────────────────────
function switchPane(name, btn) {
    document.querySelectorAll('.fe-pane').forEach(el => el.classList.remove('active'));
    document.querySelector(`.fe-pane[data-pane="${name}"]`).classList.add('active');
    document.querySelectorAll('.fe-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// ── Layout selection ────────────────────────────────────────
function selectLayout(el, value) {
    document.querySelectorAll('.layout-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
}

// ── Per-color rows ──────────────────────────────────────────
function buildColorRows() {
    const container = document.getElementById('editColorRows');
    let html = '';
    Object.entries(COLOR_OPTIONS).forEach(([key, cfg]) => {
        const current = editColors[key];
        let dotsHtml = cfg.presets.map(color => {
            const sel = current.toLowerCase() === color.toLowerCase() ? 'selected' : '';
            return `<div class="fe-color-dot ${sel}" style="background:${color};" onclick="editPickColor('${key}','${color}')"><span class="dot-check"><i class="bi bi-check"></i></span></div>`;
        }).join('');
        dotsHtml += `<label class="fe-color-custom-btn" title="Custom"><i class="bi bi-plus"></i><input type="color" value="${current}" oninput="editPickColor('${key}',this.value)"></label>`;
        html += `<div class="fe-color-row">
            <div class="fe-color-row-label"><span>${cfg.label}</span><span class="current">${current.toUpperCase()}</span></div>
            <div class="fe-color-dots">${dotsHtml}</div>
        </div>`;
    });
    container.innerHTML = html;
}

function editPickColor(key, color) {
    editColors[key] = color;
    buildColorRows();
    sendPreviewUpdate();
}

// ── Toggles ─────────────────────────────────────────────────
function editToggle(field, toggleId, wrapId) {
    if (field === 'enable_logo') { editEnableLogo = !editEnableLogo; document.getElementById(toggleId).classList.toggle('on', editEnableLogo); document.getElementById(wrapId).style.display = editEnableLogo ? '' : 'none'; }
    if (field === 'enable_background_image') { editEnableBg = !editEnableBg; document.getElementById(toggleId).classList.toggle('on', editEnableBg); document.getElementById(wrapId).style.display = editEnableBg ? '' : 'none'; }
}

function editToggleActive() {
    editActiveState = !editActiveState;
    document.getElementById('eActiveToggle').classList.toggle('on', editActiveState);
}

// ── File uploads ────────────────────────────────────────────
async function editUploadLogo(input) {
    if (!input.files[0]) return;
    const fd = new FormData();
    fd.append('logo', input.files[0]);
    const res = await fetch('{{ route("forms.upload-logo", $form) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd });
    const data = await res.json();
    if (data.success) {
        document.getElementById('eLogoImg').src = data.logo_url;
        document.getElementById('eLogoPreview').style.display = '';
        document.getElementById('eLogoDropzone').style.display = 'none';
        toastr.success('Logo enviada!');
    }
}

function removeEditLogo() {
    document.getElementById('eLogoFile').value = '';
    document.getElementById('eLogoPreview').style.display = 'none';
    document.getElementById('eLogoDropzone').style.display = '';
}

async function editUploadBg(input) {
    if (!input.files[0]) return;
    const fd = new FormData();
    fd.append('background', input.files[0]);
    const res = await fetch('{{ route("forms.upload-background", $form) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd });
    const data = await res.json();
    if (data.success) {
        document.getElementById('eBgImg').src = data.background_image_url;
        document.getElementById('eBgPreview').style.display = '';
        document.getElementById('eBgDropzone').style.display = 'none';
        toastr.success('Background enviado!');
    }
}

function removeEditBg() {
    document.getElementById('eBgFile').value = '';
    document.getElementById('eBgPreview').style.display = 'none';
    document.getElementById('eBgDropzone').style.display = '';
}

// ── Stages ──────────────────────────────────────────────────
function editUpdateStages() {
    const sel = document.getElementById('e_pipeline');
    const opt = sel.options[sel.selectedIndex];
    const stages = opt.dataset.stages ? JSON.parse(opt.dataset.stages) : [];
    document.getElementById('e_stage').innerHTML = stages.map(s => `<option value="${s.id}" ${s.id == CURRENT_STAGE ? 'selected' : ''}>${s.name}</option>`).join('');
}

// ── Preview ─────────────────────────────────────────────────
function setPreviewSize(width, btn) {
    document.getElementById('previewFrame').style.width = width;
    document.querySelectorAll('.fe-preview-toggle button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

function sendPreviewUpdate() {
    const iframe = document.getElementById('previewFrame');
    if (!iframe.contentWindow) return;
    iframe.contentWindow.postMessage({
        type: 'form-preview-update',
        styles: {
            brand_color: editColors.brand,
            background_color: editColors.background,
            card_color: editColors.card,
            button_color: editColors.button,
            button_text_color: editColors.buttonText,
            label_color: editColors.label,
            input_border_color: editColors.inputBorder,
            input_bg_color: editColors.inputBg,
            input_text_color: editColors.inputText,
            font_family: document.getElementById('e_font').value,
            border_radius: document.getElementById('e_radius').value,
            layout: document.querySelector('.layout-card.selected')?.dataset.layout || 'centered',
        }
    }, '*');
}


// ── Save ────────────────────────────────────────────────────
function toggleCountriesSelector() {
    const restrict = document.querySelector('input[name="e_restrict"]:checked')?.value === '1';
    document.getElementById('e_countriesBox').style.display = restrict ? '' : 'none';
}

function collectAllowedCountries() {
    const restrict = document.querySelector('input[name="e_restrict"]:checked')?.value === '1';
    if (!restrict) return [];
    const iso = [];
    document.querySelectorAll('.e-country-cb:checked').forEach(cb => iso.push(cb.value));
    return iso;
}

async function saveForm() {
    const notify = document.getElementById('e_notify').value.trim();
    const emails = notify ? notify.split(',').map(e => e.trim()).filter(Boolean) : [];
    const selectedLayout = document.querySelector('.layout-card.selected')?.dataset.layout || 'centered';

    const body = {
        name: document.getElementById('e_name').value,
        type: document.getElementById('e_type').value,
        slug: document.getElementById('e_slug').value,
        layout: selectedLayout,
        color_preset: 'custom',
        brand_color: editColors.brand,
        background_color: editColors.background,
        card_color: editColors.card,
        button_color: editColors.button,
        button_text_color: editColors.buttonText,
        label_color: editColors.label,
        input_border_color: editColors.inputBorder,
        input_bg_color: editColors.inputBg,
        input_text_color: editColors.inputText,
        font_family: document.getElementById('e_font').value,
        border_radius: parseInt(document.getElementById('e_radius').value),
        logo_alignment: document.getElementById('e_logoAlignment').value,
        enable_logo: editEnableLogo,
        enable_background_image: editEnableBg,
        logo_url: document.getElementById('eLogoImg')?.src || null,
        pipeline_id: document.getElementById('e_pipeline').value || null,
        stage_id: document.getElementById('e_stage').value || null,
        assigned_user_id: document.getElementById('e_assigned').value || null,
        source_utm: document.getElementById('e_source').value || null,
        confirmation_type: document.getElementById('e_confType').value,
        confirmation_value: document.getElementById('e_confValue').value || null,
        notify_emails: emails.length ? emails : null,
        max_submissions: document.getElementById('e_maxSubs').value || null,
        expires_at: document.getElementById('e_expires').value || null,
        is_active: editActiveState,
        widget_trigger: document.getElementById('w_trigger')?.value || 'immediate',
        widget_delay: parseInt(document.getElementById('w_delay')?.value || '5'),
        widget_scroll_pct: parseInt(document.getElementById('w_scroll')?.value || '50'),
        widget_position: document.getElementById('w_position')?.value || 'center',
        widget_show_once: editWidgetShowOnce,
        default_country: document.getElementById('e_defaultCountry')?.value || 'BR',
        allowed_countries: collectAllowedCountries(),
    };

    const res = await fetch('{{ route("forms.update", $form) }}', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(body),
    });
    const data = await res.json();
    if (data.success) {
        toastr.success('Salvo!');
        document.getElementById('previewFrame').src = document.getElementById('previewFrame').src; // Reload preview
    } else {
        toastr.error(data.message || Object.values(data.errors || {}).flat()[0] || 'Erro');
    }
}

async function deleteForm() {
    if (!confirm('Tem certeza que deseja excluir este formulário?')) return;
    const res = await fetch('{{ route("forms.destroy", $form) }}', {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
    });
    const data = await res.json();
    if (data.success) window.location.href = '{{ route("forms.index") }}';
    else toastr.error('Erro ao excluir');
}

document.getElementById('e_radius')?.addEventListener('input', function() {
    document.getElementById('eRadiusLabel').textContent = this.value + 'px';
});

// ── Distribution ────────────────────────────────────────────
function copySnippet(inputId) {
    const el = document.getElementById(inputId);
    el.select();
    el.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(el.value).then(() => toastr.success('{{ __("forms.snippet_copied") }}'));
}

function toggleShowOnce() {
    editWidgetShowOnce = !editWidgetShowOnce;
    document.getElementById('wShowOnceToggle').classList.toggle('on', editWidgetShowOnce);
    updatePopupSnippet();
}

function updatePopupSnippet() {
    const trigger = document.getElementById('w_trigger').value;
    const delay = document.getElementById('w_delay').value;
    const scroll = document.getElementById('w_scroll').value;
    const pos = document.getElementById('w_position').value;

    // Toggle conditional rows
    document.getElementById('delayRow').style.display = trigger === 'time' ? '' : 'none';
    document.getElementById('scrollRow').style.display = trigger === 'scroll' ? '' : 'none';

    let attrs = 'data-mode="popup"';
    attrs += ' data-trigger="' + trigger + '"';
    if (trigger === 'time') attrs += ' data-delay="' + delay + '"';
    if (trigger === 'scroll') attrs += ' data-scroll="' + scroll + '"';
    if (pos !== 'center') attrs += ' data-position="' + pos + '"';
    if (!editWidgetShowOnce) attrs += ' data-show-once="false"';

    const snippet = '<' + 'script src="' + APP_URL + '/api/form/' + FORM_SLUG + '.js" data-form="' + FORM_SLUG + '" ' + attrs + ' async><' + '/script>';
    document.getElementById('popupSnippet').value = snippet;
}

// Init
editUpdateStages();
buildColorRows();
updatePopupSnippet();
</script>
@endpush
