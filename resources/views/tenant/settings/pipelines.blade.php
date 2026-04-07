@extends('tenant.layouts.app')

@php
    $title = __('pipelines.title');
    $pageIcon = 'funnel';
@endphp

@push('styles')
<style>
    .pipeline-card {
        background: #fff;
        border: 1px solid #e8eaf0;
        border-radius: 12px;
        margin-bottom: 12px;
        overflow: hidden;
    }
    .pipeline-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 18px;
        cursor: pointer;
        user-select: none;
        background: #fff;
        transition: background .1s;
    }
    .pipeline-header:hover { background: #f9fafb; }
    .pipeline-color-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
    .pipeline-name { font-weight: 600; font-size: 14px; color: #1a1d23; flex: 1; }
    .default-badge {
        font-size: 10px; font-weight: 700; padding: 2px 7px;
        border-radius: 99px; background: #dbeafe; color: #3B82F6;
        text-transform: uppercase; letter-spacing: .04em;
    }
    .pipeline-actions { display: flex; gap: 6px; align-items: center; }
    .btn-icon {
        width: 28px; height: 28px; border-radius: 7px; border: 1px solid #e8eaf0;
        background: #fff; color: #6b7280;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 13px; transition: all .15s;
    }
    .btn-icon:hover { background: #f0f4ff; color: #374151; }
    .btn-icon.danger:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

    .pipeline-body { display: none; border-top: 1px solid #f0f2f7; }
    .pipeline-body.open { display: block; }

    .stages-list { list-style: none; margin: 0; padding: 8px; }
    .stage-item {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px; border-radius: 8px; transition: background .1s;
    }
    .stage-item:hover { background: #f8faff; }
    .stage-drag-handle { color: #d1d5db; cursor: grab; font-size: 14px; }
    .stage-color-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .stage-name { flex: 1; font-size: 13px; font-weight: 600; color: #374151; }
    .stage-badge { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 99px; text-transform: uppercase; letter-spacing: .04em; }
    .won-badge  { background: #d1fae5; color: #059669; }
    .lost-badge { background: #fee2e2; color: #ef4444; }

    .add-stage-btn {
        display: flex; align-items: center; gap: 6px;
        padding: 10px 20px; font-size: 12.5px; font-weight: 600; color: #6b7280;
        cursor: pointer; border-top: 1px solid #f0f2f7; background: #fafafa;
        transition: background .1s; border: none; width: 100%; text-align: left;
    }
    .add-stage-btn:hover { background: #f0f4ff; color: #0085f3; }

    .toggle { position: relative; display: inline-block; width: 36px; height: 20px; }
    .toggle input { display: none; }
    .toggle-slider {
        position: absolute; inset: 0; background: #d1d5db;
        border-radius: 99px; cursor: pointer; transition: .2s;
    }
    .toggle-slider::before {
        content: ''; position: absolute;
        width: 14px; height: 14px; left: 3px; bottom: 3px;
        background: #fff; border-radius: 50%; transition: .2s;
    }
    .toggle input:checked + .toggle-slider { background: #10B981; }
    .toggle input:checked + .toggle-slider::before { transform: translateX(16px); }

    .form-group { margin-bottom: 14px; }
    .form-label { display: block; font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .04em; }
    .form-control {
        width: 100%; padding: 9px 12px; border: 1.5px solid #e8eaf0;
        border-radius: 9px; font-size: 13.5px; outline: none;
        font-family: inherit; transition: border-color .15s; box-sizing: border-box;
    }
    .form-control:focus { border-color: #3B82F6; }
    .color-row { display: flex; gap: 8px; align-items: center; }
    .color-input { width: 42px; height: 36px; padding: 2px; border: 1.5px solid #e8eaf0; border-radius: 9px; cursor: pointer; }
    .checkbox-row { display: flex; gap: 16px; }
    .checkbox-label { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #374151; cursor: pointer; }

    .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
    .section-title  { font-size: 15px; font-weight: 700; color: #1a1d23; }

    /* ---- Modal (centralizado) ---- */
    .pipeline-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.5);
        z-index: 300;
        align-items: center;
        justify-content: center;
        padding: 20px;
        animation: fadeIn .15s ease-out;
    }
    .pipeline-modal-overlay.open { display: flex; }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    .pipeline-modal {
        background: #fff;
        width: 720px;
        max-width: 100%;
        max-height: 88vh;
        border-radius: 14px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0,0,0,.25);
        animation: slideUp .25s cubic-bezier(.4,0,.2,1);
        overflow: hidden;
    }

    .drawer-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 22px;
        border-bottom: 1px solid #f0f2f7;
        flex-shrink: 0;
    }
    .drawer-header h4 {
        font-size: 16px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0;
    }
    .drawer-close {
        width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e8eaf0;
        background: #fff; color: #6b7280;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 16px; transition: all .15s;
    }
    .drawer-close:hover { background: #f0f2f7; color: #374151; }

    .drawer-body {
        flex: 1;
        overflow-y: auto;
        padding: 22px;
    }

    .drawer-footer {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        padding: 16px 22px;
        border-top: 1px solid #f0f2f7;
        flex-shrink: 0;
    }

    /* ---- Quick start (top section, só ao criar) ---- */
    .quick-start-section { margin-bottom: 18px; }
    .quick-start-title {
        font-size: 13px; font-weight: 700; color: #1a1d23;
        margin-bottom: 12px;
    }
    .quick-start-cards {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .quick-card {
        padding: 18px 16px;
        border: 2px solid #e8eaf0;
        border-radius: 12px;
        background: #fff;
        cursor: pointer;
        transition: all .15s;
        text-align: center;
    }
    .quick-card:hover { border-color: #0085f3; background: #f0f8ff; transform: translateY(-2px); }
    .quick-card .qc-icon {
        width: 44px; height: 44px; border-radius: 12px;
        background: #eff6ff; color: #0085f3;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 20px; margin-bottom: 10px;
    }
    .quick-card .qc-title {
        font-size: 14px; font-weight: 700; color: #1a1d23;
        margin-bottom: 4px;
    }
    .quick-card .qc-desc {
        font-size: 12px; color: #6b7280; line-height: 1.4;
    }
    @media (max-width: 540px) {
        .quick-start-cards { grid-template-columns: 1fr; }
    }

    /* ---- Template browser ---- */
    .template-browser-header {
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 14px;
    }
    .template-back-btn {
        background: #fff; border: 1.5px solid #e8eaf0;
        border-radius: 9px; padding: 6px 12px;
        font-size: 12.5px; color: #374151; font-weight: 600;
        cursor: pointer; display: inline-flex; align-items: center; gap: 4px;
        transition: all .15s;
    }
    .template-back-btn:hover { background: #f0f2f7; }
    .template-browser-title { font-size: 15px; font-weight: 700; color: #1a1d23; flex: 1; }

    .template-categories-row {
        display: flex; gap: 6px; flex-wrap: wrap;
        margin-bottom: 14px;
    }
    .template-cat-chip {
        padding: 5px 12px; border-radius: 99px;
        background: #f3f4f6; color: #6b7280;
        border: 1px solid transparent;
        font-size: 11.5px; font-weight: 600; cursor: pointer;
        transition: all .12s;
        white-space: nowrap;
    }
    .template-cat-chip:hover { background: #eff6ff; color: #0085f3; }
    .template-cat-chip.active { background: #0085f3; color: #fff; border-color: #0085f3; }

    .templates-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 10px;
    }
    .template-card {
        border: 1.5px solid #e8eaf0;
        border-radius: 12px;
        padding: 14px 12px 12px;
        background: #fff;
        display: flex; flex-direction: column;
        transition: all .15s;
    }
    .template-card:hover { border-color: #93c5fd; box-shadow: 0 4px 14px rgba(0,133,243,.1); }
    .template-card-icon {
        width: 36px; height: 36px; border-radius: 9px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 16px; margin-bottom: 8px;
    }
    .template-card-name {
        font-size: 13px; font-weight: 700; color: #1a1d23;
        margin-bottom: 3px; line-height: 1.3;
    }
    .template-card-desc {
        font-size: 11px; color: #6b7280; line-height: 1.4;
        flex: 1; margin-bottom: 8px;
    }
    .template-card-meta {
        font-size: 10.5px; color: #9ca3af;
        margin-bottom: 8px;
    }
    .template-card-btn {
        background: #eff6ff; color: #0085f3;
        border: 1px solid #bfdbfe; border-radius: 7px;
        padding: 6px 10px; font-size: 11.5px; font-weight: 600;
        cursor: pointer; transition: all .12s;
        display: inline-flex; align-items: center; justify-content: center; gap: 4px;
        width: 100%;
    }
    .template-card-btn:hover { background: #dbeafe; }

    /* ---- Botão chamativo de tarefas obrigatórias por stage ---- */
    .btn-req-prominent {
        display: flex; align-items: center; justify-content: space-between;
        width: 100%; padding: 9px 12px; margin-top: 8px;
        background: #eff6ff; color: #1d4ed8;
        border: 1.5px dashed #93c5fd; border-radius: 9px;
        font-size: 12.5px; font-weight: 600; cursor: pointer;
        transition: all .15s;
    }
    .btn-req-prominent:hover { background: #dbeafe; border-color: #60a5fa; border-style: solid; }
    .btn-req-prominent.has-reqs {
        background: #ecfdf5; color: #047857; border-color: #6ee7b7;
        border-style: solid;
    }
    .btn-req-prominent.has-reqs:hover { background: #d1fae5; }
    .btn-req-prominent .req-label {
        display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-req-prominent .req-arrow { font-size: 11px; opacity: .7; }

    /* ---- Sub-modal de tarefas obrigatórias ---- */
    .req-modal-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,.55);
        z-index: 400;
        align-items: center; justify-content: center;
        padding: 20px;
    }
    .req-modal-overlay.open { display: flex; }
    .req-modal {
        background: #fff;
        width: 600px; max-width: 100%;
        max-height: 86vh;
        border-radius: 14px;
        display: flex; flex-direction: column;
        box-shadow: 0 20px 60px rgba(0,0,0,.3);
        animation: slideUp .2s cubic-bezier(.4,0,.2,1);
        overflow: hidden;
    }
    .req-modal-header {
        padding: 18px 22px;
        border-bottom: 1px solid #f0f2f7;
        display: flex; align-items: center; justify-content: space-between;
        flex-shrink: 0;
    }
    .req-modal-header h4 { margin: 0; font-size: 15px; font-weight: 700; color: #1a1d23; }
    .req-modal-header h4 small { font-size: 12px; font-weight: 500; color: #6b7280; margin-left: 6px; }
    .req-modal-body { flex: 1; overflow-y: auto; padding: 18px 22px; }
    .req-modal-subtitle { font-size: 12.5px; color: #6b7280; margin-bottom: 14px; }
    .req-modal-list { display: flex; flex-direction: column; gap: 8px; }
    .req-modal-list .req-row {
        background: #f9fafb;
        border: 1px solid #e8eaf0;
        border-radius: 9px;
        padding: 10px 12px;
        display: grid;
        grid-template-columns: 1fr 110px 100px 70px 30px;
        gap: 6px;
        align-items: center;
        margin-bottom: 0;
    }
    .req-modal-list .req-row input,
    .req-modal-list .req-row select {
        padding: 7px 10px; font-size: 12.5px;
        border: 1px solid #e5e7eb; border-radius: 7px;
        background: #fff; color: #1a1d23;
    }
    .req-modal-list .req-row input:focus,
    .req-modal-list .req-row select:focus { outline: none; border-color: #0085f3; }
    .req-modal-footer {
        padding: 14px 22px; border-top: 1px solid #f0f2f7;
        display: flex; gap: 8px; justify-content: space-between; align-items: center;
        flex-shrink: 0;
    }
    @media (max-width: 640px) {
        .req-modal-list .req-row { grid-template-columns: 1fr 1fr; }
    }

    .btn-cancel {
        padding: 8px 18px; border-radius: 100px; border: 1.5px solid #e8eaf0;
        background: #fff; font-size: 13px; font-weight: 600; color: #6b7280;
        cursor: pointer; transition: all .15s;
    }
    .btn-cancel:hover { background: #f0f2f7; }
    .btn-save {
        padding: 8px 20px; border-radius: 100px; border: none;
        background: #0085f3; color: #fff; font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
    }
    .btn-save:hover { background: #0070d1; }
    .btn-save:disabled { opacity: .6; cursor: not-allowed; }

    /* ---- Drawer Stages List ---- */
    .drawer-stages-section {
        margin-top: 20px;
        border-top: 1px solid #f0f2f7;
        padding-top: 16px;
    }
    .drawer-stages-title {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 10px;
    }
    .drawer-stages-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .drawer-stage-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 6px;
        border: 1px solid #e8eaf0;
        border-radius: 8px;
        margin-bottom: 6px;
        background: #fff;
        transition: background .1s;
    }
    .drawer-stage-row:hover { background: #f9fafb; }
    .drawer-stage-row .stage-drag-handle { color: #d1d5db; cursor: grab; font-size: 14px; flex-shrink: 0; }
    .drawer-stage-row .form-control { padding: 6px 10px; font-size: 13px; }
    .drawer-stage-row .color-input { width: 34px; height: 30px; padding: 1px; flex-shrink: 0; }
    .drawer-stage-checkbox {
        display: flex; align-items: center; gap: 3px;
        font-size: 10px; color: #6b7280; white-space: nowrap; flex-shrink: 0;
    }
    .drawer-stage-checkbox input { margin: 0; }
    .drawer-stage-delete {
        width: 26px; height: 26px; border-radius: 6px; border: 1px solid #e8eaf0;
        background: #fff; color: #9ca3af;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 12px; transition: all .15s; flex-shrink: 0;
    }
    .drawer-stage-delete:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

    .btn-add-stage-drawer {
        display: flex; align-items: center; gap: 6px;
        padding: 8px 14px; font-size: 12.5px; font-weight: 600; color: #0085f3;
        cursor: pointer; border: 1.5px dashed #bfdbfe; border-radius: 8px;
        background: #eff6ff; transition: all .15s; width: 100%; margin-top: 4px;
    }
    .btn-add-stage-drawer:hover { background: #dbeafe; border-color: #93c5fd; }

    /* ---- Required tasks helpers (sub-modal) ---- */
    .req-del {
        width: 28px; height: 28px; border-radius: 6px; border: 1px solid #e8eaf0;
        background: #fff; color: #9ca3af; cursor: pointer; font-size: 12px;
        display: flex; align-items: center; justify-content: center;
    }
    .req-del:hover { color: #ef4444; background: #fee2e2; border-color: #fca5a5; }
    .btn-add-req {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 14px; font-size: 12.5px; font-weight: 600; color: #0085f3;
        cursor: pointer; border: 1.5px dashed #bfdbfe; border-radius: 9px;
        background: #eff6ff; margin-top: 12px;
        transition: all .15s;
    }
    .btn-add-req:hover { background: #dbeafe; border-color: #93c5fd; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">CRM</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('pipelines.title') }}</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">{{ __('pipelines.index_subtitle') }}</p>
            </div>
            <button class="btn-primary-sm" id="btnNovoPipeline">
                <i class="bi bi-plus-lg"></i> {{ __('pipelines.new_pipeline') }}
            </button>
        </div>
    </div>

    <div id="pipelinesContainer">
        @forelse($pipelines as $pipeline)
        <div class="pipeline-card" data-pipeline-id="{{ $pipeline->id }}">
            <div class="pipeline-header" onclick="togglePipeline(this)">
                <span class="pipeline-color-dot" style="background: {{ $pipeline->color }};"></span>
                <span class="pipeline-name">{{ $pipeline->name }}</span>
                @if($pipeline->is_default)
                <span class="default-badge">{{ __('pipelines.default') }}</span>
                @endif
                <div class="pipeline-actions" onclick="event.stopPropagation()">
                    <button class="btn-icon" title="{{ __('pipelines.set_default') }}" onclick="setDefaultPipeline({{ $pipeline->id }}, '{{ addslashes($pipeline->name) }}', '{{ $pipeline->color }}')">
                        <i class="bi bi-star{{ $pipeline->is_default ? '-fill' : '' }}" style="{{ $pipeline->is_default ? 'color:#f59e0b;' : '' }}"></i>
                    </button>
                    <button class="btn-icon" title="{{ __('pipelines.edit') }}" onclick="openEditPipelineDrawer({{ $pipeline->id }})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-icon danger" title="{{ __('pipelines.delete') }}" onclick="deletePipeline({{ $pipeline->id }}, this)">
                        <i class="bi bi-trash"></i>
                    </button>
                    <i class="bi bi-chevron-down" style="font-size:13px;color:#9ca3af;transition:transform .2s;" id="chevron-{{ $pipeline->id }}"></i>
                </div>
            </div>
            <div class="pipeline-body" id="body-{{ $pipeline->id }}">
                <ul class="stages-list" data-pipeline-id="{{ $pipeline->id }}" id="stages-{{ $pipeline->id }}">
                    @foreach($pipeline->stages as $stage)
                    <li class="stage-item" data-stage-id="{{ $stage->id }}">
                        <i class="bi bi-grip-vertical stage-drag-handle"></i>
                        <span class="stage-color-dot" style="background: {{ $stage->color }};"></span>
                        <span class="stage-name">{{ $stage->name }}</span>
                        @if($stage->is_won)  <span class="stage-badge won-badge">{{ __('pipelines.won') }}</span>  @endif
                        @if($stage->is_lost) <span class="stage-badge lost-badge">{{ __('pipelines.lost') }}</span> @endif
                        @if($stage->requiredTasks->isNotEmpty())
                            <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;background:#eff6ff;color:#0085f3;font-size:10px;font-weight:600;border-radius:99px;" title="{{ __('pipelines.req_title') }}">
                                <i class="bi bi-list-check" style="font-size:10px;"></i> {{ $stage->requiredTasks->count() }}
                            </span>
                        @endif
                        <div style="display:flex;gap:5px;">
                            <button class="btn-icon" onclick="openEditPipelineDrawer({{ $pipeline->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn-icon danger" onclick="deleteStage({{ $pipeline->id }}, {{ $stage->id }}, this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </li>
                    @endforeach
                </ul>
                <button class="add-stage-btn" onclick="openEditPipelineDrawer({{ $pipeline->id }})">
                    <i class="bi bi-plus-lg"></i> {{ __('pipelines.add_stage') }}
                </button>
            </div>
        </div>
        @empty
        <div id="emptyPipelines" style="text-align:center;padding:60px 20px;color:#9ca3af;">
            <i class="bi bi-diagram-3" style="font-size:40px;opacity:.3;display:block;margin-bottom:12px;"></i>
            <p style="font-size:14px;margin:0;">{{ __('pipelines.no_pipelines') }}</p>
        </div>
        @endforelse
    </div>

</div>

{{-- MODAL: Pipeline + Stages --}}
<div class="pipeline-modal-overlay" id="pipelineModalOverlay">
    <div class="pipeline-modal" id="pipelineModal">
        <div class="drawer-header">
            <h4 id="drawerTitle">{{ __('pipelines.new_pipeline_title') }}</h4>
            <button class="drawer-close" onclick="closePipelineModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="drawer-body">
            <input type="hidden" id="pipelineId">

            {{-- Quick start (só ao criar) --}}
            <div class="quick-start-section" id="quickStartSection" style="display:none;">
                <div class="quick-start-title">{{ __('pipelines.quick_start_title') }}</div>
                <div class="quick-start-cards">
                    <div class="quick-card" onclick="startBlankPipeline()">
                        <div class="qc-icon"><i class="bi bi-pencil-square"></i></div>
                        <div class="qc-title">{{ __('pipelines.quick_start_blank_title') }}</div>
                        <div class="qc-desc">{{ __('pipelines.quick_start_blank_desc') }}</div>
                    </div>
                    <div class="quick-card" onclick="openTemplateBrowser()">
                        <div class="qc-icon" style="background:#f3e8ff;color:#9333EA;"><i class="bi bi-collection"></i></div>
                        <div class="qc-title">{{ __('pipelines.quick_start_template_title') }}</div>
                        <div class="qc-desc">{{ __('pipelines.quick_start_template_desc') }}</div>
                    </div>
                </div>
            </div>

            {{-- Template browser (substitui o body temporariamente) --}}
            <div id="templateBrowser" style="display:none;">
                <div class="template-browser-header">
                    <button type="button" class="template-back-btn" onclick="closeTemplateBrowser()">
                        <i class="bi bi-arrow-left"></i> {{ __('pipelines.template_back') }}
                    </button>
                    <div class="template-browser-title">{{ __('pipelines.template_library_title') }}</div>
                </div>
                <div class="template-categories-row" id="templateCategories"></div>
                <div class="templates-grid" id="templatesGrid"></div>
            </div>

            {{-- Pipeline form (escondido até escolher quick start) --}}
            <div id="pipelineFormBody" style="display:none;">
                <div class="form-group">
                    <label class="form-label">{{ __('pipelines.pipeline_name') }}</label>
                    <input type="text" id="pipelineName" class="form-control" placeholder="{{ __('pipelines.pipeline_name_ph') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('pipelines.color') }}</label>
                    <div class="color-row">
                        <input type="color" id="pipelineColor" class="color-input" value="#3B82F6">
                        <input type="text" id="pipelineColorText" class="form-control" value="#3B82F6" placeholder="#3B82F6" style="flex:1;">
                    </div>
                </div>
                <div class="form-group" style="margin-top:14px;">
                    <label class="form-label" style="margin-bottom:8px;">{{ __('pipelines.auto_create_lead') }}</label>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                        <label class="toggle">
                            <input type="checkbox" id="autoCreateLead" checked onchange="toggleChannelToggles()">
                            <span class="toggle-slider"></span>
                        </label>
                        <span style="font-size:12px;color:#374151;">{{ __('pipelines.create_on_message') }}</span>
                    </div>
                    <div id="channelToggles" style="display:flex;gap:16px;padding-left:4px;">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <label class="toggle">
                                <input type="checkbox" id="autoCreateWhatsapp" checked>
                                <span class="toggle-slider"></span>
                            </label>
                            <span style="font-size:12px;color:#6b7280;"><i class="bi bi-whatsapp" style="color:#25D366;"></i> WhatsApp</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <label class="toggle">
                                <input type="checkbox" id="autoCreateInstagram" checked>
                                <span class="toggle-slider"></span>
                            </label>
                            <span style="font-size:12px;color:#6b7280;"><i class="bi bi-instagram" style="color:#E1306C;"></i> Instagram</span>
                        </div>
                    </div>
                </div>

                {{-- Stages section --}}
                <div class="drawer-stages-section">
                    <div class="drawer-stages-title">{{ __('pipelines.stages') }}</div>
                    <ul class="drawer-stages-list" id="drawerStagesList"></ul>
                    <button type="button" class="btn-add-stage-drawer" onclick="addDrawerStageRow()">
                        <i class="bi bi-plus-lg"></i> {{ __('pipelines.new_stage') }}
                    </button>
                </div>
            </div>
        </div>
        <div class="drawer-footer">
            <button class="btn-cancel" onclick="closePipelineModal()">{{ __('pipelines.cancel') }}</button>
            <button class="btn-save" id="btnSaveDrawer" onclick="saveDrawer()">{{ __('pipelines.save') }}</button>
        </div>
    </div>
</div>

{{-- SUB-MODAL: Tarefas obrigatórias da etapa --}}
<div class="req-modal-overlay" id="reqModalOverlay">
    <div class="req-modal">
        <div class="req-modal-header">
            <h4>
                {{ __('pipelines.req_modal_title') }}
                <small id="reqModalStageName"></small>
            </h4>
            <button class="drawer-close" onclick="closeReqModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="req-modal-body">
            <div class="req-modal-subtitle">{{ __('pipelines.req_modal_subtitle') }}</div>
            <div class="req-modal-list" id="reqModalList"></div>
            <button type="button" class="btn-add-req" onclick="addReqRow()">
                <i class="bi bi-plus-lg"></i> {{ __('pipelines.req_add') }}
            </button>
        </div>
        <div class="req-modal-footer">
            <button class="btn-cancel" onclick="closeReqModal()">{{ __('pipelines.cancel') }}</button>
            <button class="btn-save" onclick="saveReqModal()">{{ __('pipelines.req_modal_save') }}</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const PLANG = @json(__('pipelines'));
const PIPE_STORE  = @json(route('settings.pipelines.store'));
const PIPE_UPD    = @json(route('settings.pipelines.update',  ['pipeline' => '__ID__']));
const PIPE_DEL    = @json(route('settings.pipelines.destroy', ['pipeline' => '__ID__']));
const STAGE_STORE = @json(route('settings.pipelines.stages.store',  ['pipeline' => '__ID__']));
const STAGE_UPD   = @json(route('settings.pipelines.stages.update', ['pipeline' => '__P__', 'stage' => '__S__']));
const STAGE_DEL   = @json(route('settings.pipelines.stages.destroy',['pipeline' => '__P__', 'stage' => '__S__']));
const STAGE_REORD = @json(route('settings.pipelines.stages.reorder',['pipeline' => '__ID__']));
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;

@php
    $templatesJs = collect($templates)->map(fn($t) => [
        'slug' => $t['slug'],
        'category' => $t['category'],
        'name' => $t['name'],
        'icon' => $t['icon'],
        'color' => $t['color'],
        'description' => $t['description'],
        'stages' => $t['stages'],
    ])->values();
@endphp
const TEMPLATES = {!! json_encode($templatesJs) !!};
const TEMPLATE_CATEGORIES = {!! json_encode($templateCategories) !!};

/* ---- Pipelines data from server (for edit modal) ---- */
const pipelinesData = {};
@foreach($pipelines as $pipeline)
pipelinesData[{{ $pipeline->id }}] = {
    id: {{ $pipeline->id }},
    name: @json($pipeline->name),
    color: @json($pipeline->color),
    auto_create_lead: {{ $pipeline->auto_create_lead ? 'true' : 'false' }},
    auto_create_from_whatsapp: {{ $pipeline->auto_create_from_whatsapp ? 'true' : 'false' }},
    auto_create_from_instagram: {{ $pipeline->auto_create_from_instagram ? 'true' : 'false' }},
    stages: [
        @foreach($pipeline->stages as $stage)
        @php
            $stageTasksJs = $stage->requiredTasks->map(fn($t) => [
                'subject' => $t->subject,
                'description' => $t->description,
                'task_type' => $t->task_type,
                'priority' => $t->priority,
                'due_date_offset' => $t->due_date_offset,
            ])->values();
        @endphp
        { id: {{ $stage->id }}, name: @json($stage->name), color: @json($stage->color), is_won: {{ $stage->is_won ? 'true' : 'false' }}, is_lost: {{ $stage->is_lost ? 'true' : 'false' }}, required_tasks: {!! json_encode($stageTasksJs) !!} },
        @endforeach
    ]
};
@endforeach

/* ---- Accordion ---- */
function togglePipeline(header) {
    const card    = header.closest('.pipeline-card');
    const id      = card.dataset.pipelineId;
    const body    = document.getElementById('body-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const isOpen  = body.classList.contains('open');
    body.classList.toggle('open', !isOpen);
    chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
}

/* ---- Sortable (accordion stages) ---- */
document.querySelectorAll('.stages-list').forEach(el => initSortable(el));

function initSortable(el) {
    if (el._sortable) return;
    el._sortable = Sortable.create(el, {
        handle: '.stage-drag-handle',
        animation: 150,
        onEnd() {
            const pipelineId = el.dataset.pipelineId;
            const order = [...el.querySelectorAll('.stage-item')].map(li => li.dataset.stageId);
            fetch(STAGE_REORD.replace('__ID__', pipelineId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ order })
            });
        }
    });
}

/* ---- Pipeline modal open / close ---- */
function openPipelineModal() {
    document.getElementById('pipelineModalOverlay').classList.add('open');
}

function closePipelineModal() {
    document.getElementById('pipelineModalOverlay').classList.remove('open');
}

document.getElementById('pipelineModalOverlay').addEventListener('click', e => {
    if (e.target.id === 'pipelineModalOverlay') closePipelineModal();
});

/* ---- Quick start panels ---- */
function showQuickStart() {
    document.getElementById('quickStartSection').style.display = 'block';
    document.getElementById('templateBrowser').style.display = 'none';
    document.getElementById('pipelineFormBody').style.display = 'none';
}

function startBlankPipeline() {
    document.getElementById('quickStartSection').style.display = 'none';
    document.getElementById('templateBrowser').style.display = 'none';
    document.getElementById('pipelineFormBody').style.display = 'block';
    setTimeout(() => document.getElementById('pipelineName').focus(), 50);
}

/* ---- Template browser ---- */
let _activeCategory = 'all';

function openTemplateBrowser() {
    document.getElementById('quickStartSection').style.display = 'none';
    document.getElementById('templateBrowser').style.display = 'block';
    document.getElementById('pipelineFormBody').style.display = 'none';
    _activeCategory = 'all';
    renderCategoryChips();
    renderTemplatesGrid();
}

function closeTemplateBrowser() {
    showQuickStart();
}

function renderCategoryChips() {
    const row = document.getElementById('templateCategories');
    let html = `<div class="template-cat-chip ${_activeCategory==='all'?'active':''}" onclick="filterTemplatesByCategory('all')">${escapeHtml(PLANG.template_all)}</div>`;
    for (const [slug, label] of Object.entries(TEMPLATE_CATEGORIES)) {
        html += `<div class="template-cat-chip ${_activeCategory===slug?'active':''}" onclick="filterTemplatesByCategory('${slug}')">${escapeHtml(label)}</div>`;
    }
    row.innerHTML = html;
}

function filterTemplatesByCategory(slug) {
    _activeCategory = slug;
    renderCategoryChips();
    renderTemplatesGrid();
}

function renderTemplatesGrid() {
    const grid = document.getElementById('templatesGrid');
    const list = _activeCategory === 'all'
        ? TEMPLATES
        : TEMPLATES.filter(t => t.category === _activeCategory);

    if (list.length === 0) {
        grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:40px;color:#9ca3af;font-size:13px;">${escapeHtml(PLANG.template_empty || 'Sem templates')}</div>`;
        return;
    }

    grid.innerHTML = list.map(t => {
        const stagesLbl = (PLANG.template_n_stages || ':n etapas').replace(':n', t.stages.length);
        return `
            <div class="template-card">
                <div class="template-card-icon" style="background:${hexToRgba(t.color, 0.12)};color:${t.color};">
                    <i class="bi ${t.icon}"></i>
                </div>
                <div class="template-card-name">${escapeHtml(t.name)}</div>
                <div class="template-card-desc">${escapeHtml(t.description)}</div>
                <div class="template-card-meta"><i class="bi bi-list-ol"></i> ${escapeHtml(stagesLbl)}</div>
                <button type="button" class="template-card-btn" onclick="applyTemplate('${t.slug}')">
                    ${escapeHtml(PLANG.template_use)} <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        `;
    }).join('');
}

function hexToRgba(hex, a) {
    if (!hex || hex[0] !== '#') return `rgba(0,133,243,${a})`;
    const h = hex.slice(1);
    const r = parseInt(h.substring(0,2), 16);
    const g = parseInt(h.substring(2,4), 16);
    const b = parseInt(h.substring(4,6), 16);
    return `rgba(${r},${g},${b},${a})`;
}

function applyTemplate(slug) {
    const t = TEMPLATES.find(x => x.slug === slug);
    if (!t) return;

    document.getElementById('templateBrowser').style.display = 'none';
    document.getElementById('pipelineFormBody').style.display = 'block';

    document.getElementById('pipelineName').value = t.name;
    document.getElementById('pipelineColor').value = t.color;
    document.getElementById('pipelineColorText').value = t.color;
    document.getElementById('autoCreateLead').checked = true;
    document.getElementById('autoCreateWhatsapp').checked = true;
    document.getElementById('autoCreateInstagram').checked = true;
    toggleChannelToggles();

    clearDrawerStages();
    t.stages.forEach(s => addDrawerStageRow({
        name: s.name,
        color: s.color,
        is_won: s.is_won,
        is_lost: s.is_lost,
        required_tasks: s.required_tasks || [],
    }));

    if (typeof toastr !== 'undefined') {
        toastr.success((PLANG.template_applied || 'Modelo aplicado') + ': ' + t.name);
    }
}

/* ---- Stage row counter ---- */
let drawerStageCounter = 0;

function addDrawerStageRow(data) {
    const list = document.getElementById('drawerStagesList');
    const idx = drawerStageCounter++;
    const stageId = (data && data.id) ? data.id : '';
    const name = (data && data.name) ? data.name : '';
    const color = (data && data.color) ? data.color : '#6366F1';
    const isWon = (data && data.is_won) ? true : false;
    const isLost = (data && data.is_lost) ? true : false;
    const reqs = (data && data.required_tasks) ? data.required_tasks : [];

    const wrapper = document.createElement('div');
    wrapper.className = 'drawer-stage-wrapper';
    wrapper.dataset.idx = idx;
    wrapper.dataset.reqTasks = JSON.stringify(reqs);

    const li = document.createElement('div');
    li.className = 'drawer-stage-row';
    li.dataset.stageId = stageId;
    li.dataset.idx = idx;

    li.innerHTML = `
        <i class="bi bi-grip-vertical stage-drag-handle"></i>
        <input type="text" class="form-control" style="flex:1;min-width:0;" placeholder="${escapeHtml(PLANG.stage_name_ph)}" value="${escapeHtml(name)}" data-field="name">
        <input type="color" class="color-input" value="${color}" data-field="color">
        <label class="drawer-stage-checkbox" title="${escapeHtml(PLANG.won_title)}"><input type="checkbox" data-field="is_won" ${isWon ? 'checked' : ''}> ${PLANG.won_abbr}</label>
        <label class="drawer-stage-checkbox" title="${escapeHtml(PLANG.lost_title)}"><input type="checkbox" data-field="is_lost" ${isLost ? 'checked' : ''}> ${PLANG.lost_abbr}</label>
        <button type="button" class="drawer-stage-delete" onclick="removeDrawerStageRow(this)" title="${escapeHtml(PLANG.remove_stage)}"><i class="bi bi-trash"></i></button>
    `;

    const reqBtn = document.createElement('button');
    reqBtn.type = 'button';
    reqBtn.className = 'btn-req-prominent' + (reqs.length > 0 ? ' has-reqs' : '');
    reqBtn.dataset.idx = idx;
    reqBtn.onclick = () => openReqModal(idx);
    reqBtn.innerHTML = `
        <span class="req-label">
            <i class="bi bi-list-check"></i>
            ${escapeHtml(PLANG.req_button_label || 'Tarefas obrigatórias')} (<span class="req-count">${reqs.length}</span>)
        </span>
        <span class="req-arrow"><i class="bi bi-arrow-right"></i></span>
    `;

    wrapper.appendChild(li);
    wrapper.appendChild(reqBtn);
    list.appendChild(wrapper);

    if (!list._sortable) {
        list._sortable = Sortable.create(list, {
            handle: '.stage-drag-handle',
            animation: 150,
            draggable: '.drawer-stage-wrapper',
        });
    }
}

/* ---- Required Tasks Sub-Modal ---- */
let _editingReqStageIdx = null;
let _reqDraft = [];

function openReqModal(stageIdx) {
    _editingReqStageIdx = stageIdx;
    const wrapper = document.querySelector(`.drawer-stage-wrapper[data-idx="${stageIdx}"]`);
    if (!wrapper) return;
    try {
        _reqDraft = JSON.parse(wrapper.dataset.reqTasks || '[]');
    } catch {
        _reqDraft = [];
    }

    // Subtitle: stage name
    const stageName = wrapper.querySelector('[data-field="name"]')?.value || '';
    document.getElementById('reqModalStageName').textContent = stageName ? '— ' + stageName : '';

    renderReqModalList();
    document.getElementById('reqModalOverlay').classList.add('open');
}

function closeReqModal() {
    _editingReqStageIdx = null;
    _reqDraft = [];
    document.getElementById('reqModalOverlay').classList.remove('open');
}

document.getElementById('reqModalOverlay').addEventListener('click', e => {
    if (e.target.id === 'reqModalOverlay') closeReqModal();
});

function renderReqModalList() {
    const list = document.getElementById('reqModalList');
    list.innerHTML = '';
    _reqDraft.forEach((task, i) => {
        const row = document.createElement('div');
        row.className = 'req-row';
        row.dataset.taskIdx = i;
        row.innerHTML = `
            <input type="text" placeholder="${escapeHtml(PLANG.req_subject_ph)}" value="${escapeHtml(task.subject || '')}" data-req="subject" oninput="onReqDraftChange(${i}, 'subject', this.value)">
            <select data-req="task_type" onchange="onReqDraftChange(${i}, 'task_type', this.value)">
                <option value="call" ${task.task_type === 'call' ? 'selected' : ''}>${PLANG.req_type_call}</option>
                <option value="email" ${task.task_type === 'email' ? 'selected' : ''}>${PLANG.req_type_email}</option>
                <option value="task" ${(!task.task_type || task.task_type === 'task') ? 'selected' : ''}>${PLANG.req_type_task}</option>
                <option value="visit" ${task.task_type === 'visit' ? 'selected' : ''}>${PLANG.req_type_visit}</option>
                <option value="whatsapp" ${task.task_type === 'whatsapp' ? 'selected' : ''}>${PLANG.req_type_whatsapp}</option>
                <option value="meeting" ${task.task_type === 'meeting' ? 'selected' : ''}>${PLANG.req_type_meeting}</option>
            </select>
            <select data-req="priority" onchange="onReqDraftChange(${i}, 'priority', this.value)">
                <option value="low" ${task.priority === 'low' ? 'selected' : ''}>${PLANG.req_priority_low}</option>
                <option value="medium" ${(!task.priority || task.priority === 'medium') ? 'selected' : ''}>${PLANG.req_priority_medium}</option>
                <option value="high" ${task.priority === 'high' ? 'selected' : ''}>${PLANG.req_priority_high}</option>
            </select>
            <input type="number" min="0" max="365" value="${task.due_date_offset != null ? task.due_date_offset : 1}" data-req="due_date_offset" oninput="onReqDraftChange(${i}, 'due_date_offset', parseInt(this.value)||0)" title="${escapeHtml(PLANG.req_days_title || 'Dias')}">
            <button type="button" class="req-del" onclick="removeReqDraft(${i})"><i class="bi bi-x-lg"></i></button>
        `;
        list.appendChild(row);
    });
}

function addReqRow() {
    _reqDraft.push({ subject: '', task_type: 'task', priority: 'medium', due_date_offset: 1 });
    renderReqModalList();
}

function removeReqDraft(i) {
    _reqDraft.splice(i, 1);
    renderReqModalList();
}

function onReqDraftChange(i, field, value) {
    if (!_reqDraft[i]) return;
    _reqDraft[i][field] = value;
}

function saveReqModal() {
    if (_editingReqStageIdx === null) { closeReqModal(); return; }
    const wrapper = document.querySelector(`.drawer-stage-wrapper[data-idx="${_editingReqStageIdx}"]`);
    if (!wrapper) { closeReqModal(); return; }

    // Filter out empty subjects
    const cleaned = _reqDraft.filter(t => (t.subject || '').trim() !== '');

    wrapper.dataset.reqTasks = JSON.stringify(cleaned);

    // Update button label
    const btn = wrapper.querySelector('.btn-req-prominent');
    if (btn) {
        btn.classList.toggle('has-reqs', cleaned.length > 0);
        const cntEl = btn.querySelector('.req-count');
        if (cntEl) cntEl.textContent = cleaned.length;
    }

    closeReqModal();
}

function getStageRequiredTasks(wrapper) {
    try {
        const tasks = JSON.parse(wrapper.dataset.reqTasks || '[]');
        return tasks
            .filter(t => (t.subject || '').trim() !== '')
            .map(t => ({
                subject: t.subject,
                task_type: t.task_type || 'task',
                priority: t.priority || 'medium',
                due_date_offset: parseInt(t.due_date_offset) || 1,
            }));
    } catch {
        return [];
    }
}

function removeDrawerStageRow(btn) {
    const wrapper = btn.closest('.drawer-stage-wrapper');
    const row = wrapper.querySelector('.drawer-stage-row');
    const stageId = row.dataset.stageId;
    if (stageId) {
        row.dataset.deleted = 'true';
        wrapper.style.display = 'none';
    } else {
        wrapper.remove();
    }
}

function clearDrawerStages() {
    const list = document.getElementById('drawerStagesList');
    list.innerHTML = '';
    if (list._sortable) { list._sortable.destroy(); list._sortable = null; }
    drawerStageCounter = 0;
}

/* ---- Open modal: New pipeline ---- */
document.getElementById('btnNovoPipeline').addEventListener('click', () => {
    document.getElementById('drawerTitle').textContent = PLANG.new_pipeline_title;
    document.getElementById('pipelineId').value = '';
    document.getElementById('pipelineName').value = '';
    document.getElementById('pipelineColor').value = '#3B82F6';
    document.getElementById('pipelineColorText').value = '#3B82F6';
    document.getElementById('autoCreateLead').checked = true;
    document.getElementById('autoCreateWhatsapp').checked = true;
    document.getElementById('autoCreateInstagram').checked = true;
    toggleChannelToggles();
    clearDrawerStages();
    showQuickStart();
    openPipelineModal();
});

/* ---- Auto-open modal when arriving from another page with ?new=1
       (e.g. CRM kanban empty state link) ---- */
if (new URLSearchParams(location.search).get('new') === '1') {
    document.getElementById('btnNovoPipeline').click();
    history.replaceState(null, '', location.pathname);
}

/* ---- Open modal: Edit existing pipeline (skip quick start) ---- */
function openEditPipelineDrawer(pipelineId) {
    const p = pipelinesData[pipelineId];
    if (!p) return;

    document.getElementById('drawerTitle').textContent = PLANG.edit_pipeline_title;
    document.getElementById('pipelineId').value = p.id;
    document.getElementById('pipelineName').value = p.name;
    document.getElementById('pipelineColor').value = p.color;
    document.getElementById('pipelineColorText').value = p.color;
    document.getElementById('autoCreateLead').checked = p.auto_create_lead;
    document.getElementById('autoCreateWhatsapp').checked = p.auto_create_from_whatsapp;
    document.getElementById('autoCreateInstagram').checked = p.auto_create_from_instagram;
    toggleChannelToggles();

    clearDrawerStages();
    p.stages.forEach(s => addDrawerStageRow(s));

    // Skip quick start — abre direto no form
    document.getElementById('quickStartSection').style.display = 'none';
    document.getElementById('templateBrowser').style.display = 'none';
    document.getElementById('pipelineFormBody').style.display = 'block';
    openPipelineModal();
}

/* ---- Toggle channel toggles ---- */
function toggleChannelToggles() {
    const enabled = document.getElementById('autoCreateLead').checked;
    document.getElementById('channelToggles').style.opacity = enabled ? '1' : '.4';
    document.getElementById('autoCreateWhatsapp').disabled = !enabled;
    document.getElementById('autoCreateInstagram').disabled = !enabled;
}

/* ---- Color sync ---- */
document.getElementById('pipelineColor').addEventListener('input', e => {
    document.getElementById('pipelineColorText').value = e.target.value;
});
document.getElementById('pipelineColorText').addEventListener('input', e => {
    if (/^#[0-9a-f]{6}$/i.test(e.target.value)) document.getElementById('pipelineColor').value = e.target.value;
});

/* ---- Save drawer (pipeline + stages) ---- */
async function saveDrawer() {
    const id    = document.getElementById('pipelineId').value;
    const name  = document.getElementById('pipelineName').value.trim();
    const color = document.getElementById('pipelineColorText').value.trim() || document.getElementById('pipelineColor').value;
    if (!name) { document.getElementById('pipelineName').focus(); return; }

    const btn = document.getElementById('btnSaveDrawer');
    btn.disabled = true;

    try {
        /* -- 1. Save pipeline -- */
        const pipeUrl    = id ? PIPE_UPD.replace('__ID__', id) : PIPE_STORE;
        const pipeMethod = id ? 'PUT' : 'POST';

        const pipeRes  = await fetch(pipeUrl, {
            method: pipeMethod,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                name, color,
                auto_create_lead:           document.getElementById('autoCreateLead').checked,
                auto_create_from_whatsapp:  document.getElementById('autoCreateWhatsapp').checked,
                auto_create_from_instagram: document.getElementById('autoCreateInstagram').checked,
            })
        });
        const pipeData = await pipeRes.json();
        if (!pipeData.success) {
            if (checkLimitReached(pipeData)) return;
            alert(pipeData.message || PLANG.error_save); return;
        }

        const pipelineId = pipeData.pipeline.id;

        /* -- 2. Process stages -- */
        const wrappers = [...document.querySelectorAll('#drawerStagesList .drawer-stage-wrapper')];
        const stageOrder = [];

        for (let i = 0; i < wrappers.length; i++) {
            const wrapper = wrappers[i];
            const row = wrapper.querySelector('.drawer-stage-row');
            const existingId = row.dataset.stageId;
            const deleted = row.dataset.deleted === 'true';

            if (deleted && existingId) {
                // Delete existing stage
                await fetch(STAGE_DEL.replace('__P__', pipelineId).replace('__S__', existingId), {
                    method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF }
                });
                continue;
            }

            if (deleted) continue;

            const stageName  = row.querySelector('[data-field="name"]').value.trim();
            const stageColor = row.querySelector('[data-field="color"]').value;
            const stageIsWon = row.querySelector('[data-field="is_won"]').checked;
            const stageIsLost = row.querySelector('[data-field="is_lost"]').checked;

            if (!stageName) continue; // skip empty rows

            const reqTasks = getStageRequiredTasks(wrapper);
            const payload = { name: stageName, color: stageColor, is_won: stageIsWon, is_lost: stageIsLost, required_tasks: reqTasks };

            if (existingId) {
                // Update existing stage
                const stageRes = await fetch(STAGE_UPD.replace('__P__', pipelineId).replace('__S__', existingId), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify(payload)
                });
                const stageData = await stageRes.json();
                if (stageData.success) stageOrder.push(stageData.stage.id);
            } else {
                // Create new stage
                const stageRes = await fetch(STAGE_STORE.replace('__ID__', pipelineId), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify(payload)
                });
                const stageData = await stageRes.json();
                if (stageData.success) stageOrder.push(stageData.stage.id);
            }
        }

        // Reorder stages
        if (stageOrder.length > 0) {
            await fetch(STAGE_REORD.replace('__ID__', pipelineId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ order: stageOrder })
            });
        }

        closePipelineModal();

        /* -- 3. Update DOM -- */
        if (id) {
            // Update existing pipeline card
            const card = document.querySelector(`.pipeline-card[data-pipeline-id="${id}"]`);
            if (card) {
                card.querySelector('.pipeline-color-dot').style.background = color;
                card.querySelector('.pipeline-name').textContent = name;

                // Rebuild stages list in accordion
                const stagesList = document.getElementById('stages-' + id);
                if (stagesList) {
                    stagesList.innerHTML = '';
                    if (stagesList._sortable) { stagesList._sortable.destroy(); stagesList._sortable = null; }
                }
            }

            // Update local data + reload to get fresh stages
            pipelinesData[id].name = name;
            pipelinesData[id].color = color;
            pipelinesData[id].auto_create_lead = document.getElementById('autoCreateLead').checked;
            pipelinesData[id].auto_create_from_whatsapp = document.getElementById('autoCreateWhatsapp').checked;
            pipelinesData[id].auto_create_from_instagram = document.getElementById('autoCreateInstagram').checked;
            location.reload();
        } else {
            // New pipeline — reload to get fresh rendered card
            location.reload();
        }
    } finally {
        btn.disabled = false;
    }
}

/* ---- Set default pipeline ---- */
async function setDefaultPipeline(id, name, color) {
    const res  = await fetch(PIPE_UPD.replace('__ID__', id), {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, color, is_default: true })
    });
    const data = await res.json();
    if (data.success) location.reload();
}

/* ---- Delete pipeline ---- */
function deletePipeline(id, btn) {
    confirmAction({
        title: PLANG.delete_pipeline_title,
        message: PLANG.delete_pipeline_msg,
        confirmText: PLANG.delete,
        onConfirm: async () => {
            const res  = await fetch(PIPE_DEL.replace('__ID__', id), {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF }
            });
            const data = await res.json();
            if (!data.success) { toastr.error(data.message || PLANG.error_delete); return; }
            btn.closest('.pipeline-card').remove();
            delete pipelinesData[id];
        },
    });
}

/* ---- Delete stage (from accordion) ---- */
function deleteStage(pipelineId, stageId, btn) {
    confirmAction({
        title: PLANG.delete_stage_title,
        message: PLANG.delete_stage_msg,
        confirmText: PLANG.delete,
        onConfirm: async () => {
            const res  = await fetch(STAGE_DEL.replace('__P__', pipelineId).replace('__S__', stageId), {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF }
            });
            const data = await res.json();
            if (!data.success) { toastr.error(data.message || PLANG.error_delete); return; }
            btn.closest('.stage-item').remove();
            // Update local data
            if (pipelinesData[pipelineId]) {
                pipelinesData[pipelineId].stages = pipelinesData[pipelineId].stages.filter(s => s.id !== stageId);
            }
        },
    });
}

/* ---- Helpers ---- */
function escapeHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escapeJs(s) { return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }

/* Legacy compat: keep buildPipelineCard for any external callers */
function buildPipelineCard(p) {
    return `<div class="pipeline-card" data-pipeline-id="${p.id}">
        <div class="pipeline-header" onclick="togglePipeline(this)">
            <span class="pipeline-color-dot" style="background:${p.color};"></span>
            <span class="pipeline-name">${escapeHtml(p.name)}</span>
            <div class="pipeline-actions" onclick="event.stopPropagation()">
                <button class="btn-icon" onclick="setDefaultPipeline(${p.id},'${escapeJs(p.name)}','${p.color}')"><i class="bi bi-star"></i></button>
                <button class="btn-icon" onclick="openEditPipelineDrawer(${p.id})"><i class="bi bi-pencil"></i></button>
                <button class="btn-icon danger" onclick="deletePipeline(${p.id},this)"><i class="bi bi-trash"></i></button>
                <i class="bi bi-chevron-down" style="font-size:13px;color:#9ca3af;transition:transform .2s;" id="chevron-${p.id}"></i>
            </div>
        </div>
        <div class="pipeline-body" id="body-${p.id}">
            <ul class="stages-list" data-pipeline-id="${p.id}" id="stages-${p.id}"></ul>
            <button class="add-stage-btn" onclick="openEditPipelineDrawer(${p.id})"><i class="bi bi-plus-lg"></i> ${escapeHtml(PLANG.add_stage)}</button>
        </div>
    </div>`;
}
</script>
@endpush
