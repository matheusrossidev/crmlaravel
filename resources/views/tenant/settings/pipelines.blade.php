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

    /* ---- Drawer ---- */
    .drawer-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.45);
        z-index: 300;
    }
    .drawer-overlay.open { display: block; }

    .drawer {
        position: fixed;
        top: 0;
        right: -480px;
        width: 480px;
        max-width: 95vw;
        height: 100vh;
        z-index: 301;
        background: #fff;
        display: flex;
        flex-direction: column;
        box-shadow: -8px 0 30px rgba(0,0,0,.12);
        transition: right .25s cubic-bezier(.4,0,.2,1);
    }
    .drawer.open { right: 0; }

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

    /* ---- Required tasks per stage ---- */
    .stage-req-toggle {
        width: 26px; height: 26px; border-radius: 6px; border: 1px solid #e8eaf0;
        background: #fff; color: #9ca3af; display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 12px; flex-shrink: 0; transition: all .15s; position: relative;
    }
    .stage-req-toggle:hover { background: #eff6ff; color: #0085f3; border-color: #bfdbfe; }
    .stage-req-toggle.has-reqs { color: #0085f3; border-color: #bfdbfe; background: #eff6ff; }
    .stage-req-toggle .req-count {
        position: absolute; top: -5px; right: -5px; width: 14px; height: 14px;
        border-radius: 50%; background: #0085f3; color: #fff; font-size: 8px; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
    }
    .stage-req-panel {
        display: none; padding: 10px 10px 10px 30px; background: #f8fafc;
        border: 1px solid #e8eaf0; border-top: none; border-radius: 0 0 8px 8px;
        margin-top: -7px; margin-bottom: 6px;
    }
    .stage-req-panel.open { display: block; }
    .stage-req-panel .req-title {
        font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;
        letter-spacing: .04em; margin-bottom: 8px;
    }
    .req-row {
        display: flex; gap: 6px; align-items: center; margin-bottom: 6px;
    }
    .req-row input, .req-row select {
        padding: 5px 8px; font-size: 12px; border: 1px solid #e5e7eb;
        border-radius: 6px; background: #fff; color: #1a1d23;
    }
    .req-row input:focus, .req-row select:focus { outline: none; border-color: #0085f3; }
    .req-row .req-subject { flex: 1; min-width: 0; }
    .req-row .req-type { width: 90px; }
    .req-row .req-priority { width: 80px; }
    .req-row .req-days { width: 55px; text-align: center; }
    .req-del {
        width: 22px; height: 22px; border-radius: 5px; border: none;
        background: transparent; color: #d1d5db; cursor: pointer; font-size: 11px;
        display: flex; align-items: center; justify-content: center;
    }
    .req-del:hover { color: #ef4444; background: #fee2e2; }
    .btn-add-req {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px; font-size: 11px; font-weight: 600; color: #0085f3;
        cursor: pointer; border: 1px dashed #bfdbfe; border-radius: 6px;
        background: #eff6ff; margin-top: 4px;
    }
    .btn-add-req:hover { background: #dbeafe; }
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

{{-- DRAWER: Pipeline + Stages --}}
<div class="drawer-overlay" id="drawerOverlay"></div>
<div class="drawer" id="drawerPipeline">
    <div class="drawer-header">
        <h4 id="drawerTitle">{{ __('pipelines.new_pipeline_title') }}</h4>
        <button class="drawer-close" onclick="closeDrawer()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="drawer-body">
        <input type="hidden" id="pipelineId">

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
    <div class="drawer-footer">
        <button class="btn-cancel" onclick="closeDrawer()">{{ __('pipelines.cancel') }}</button>
        <button class="btn-save" id="btnSaveDrawer" onclick="saveDrawer()">{{ __('pipelines.save') }}</button>
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

/* ---- Pipelines data from server (for edit drawer) ---- */
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
        { id: {{ $stage->id }}, name: @json($stage->name), color: @json($stage->color), is_won: {{ $stage->is_won ? 'true' : 'false' }}, is_lost: {{ $stage->is_lost ? 'true' : 'false' }} },
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

/* ---- Drawer open / close ---- */
function openDrawer() {
    document.getElementById('drawerOverlay').classList.add('open');
    document.getElementById('drawerPipeline').classList.add('open');
}

function closeDrawer() {
    document.getElementById('drawerOverlay').classList.remove('open');
    document.getElementById('drawerPipeline').classList.remove('open');
}

document.getElementById('drawerOverlay').addEventListener('click', () => closeDrawer());

/* ---- Drawer stage row counter ---- */
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

    const li = document.createElement('div');
    li.className = 'drawer-stage-row';
    li.dataset.stageId = stageId;
    li.dataset.idx = idx;

    const reqCount = reqs.length;
    li.innerHTML = `
        <i class="bi bi-grip-vertical stage-drag-handle"></i>
        <input type="text" class="form-control" style="flex:1;min-width:0;" placeholder="${escapeHtml(PLANG.stage_name_ph)}" value="${escapeHtml(name)}" data-field="name">
        <input type="color" class="color-input" value="${color}" data-field="color">
        <label class="drawer-stage-checkbox" title="${escapeHtml(PLANG.won_title)}"><input type="checkbox" data-field="is_won" ${isWon ? 'checked' : ''}> ${PLANG.won_abbr}</label>
        <label class="drawer-stage-checkbox" title="${escapeHtml(PLANG.lost_title)}"><input type="checkbox" data-field="is_lost" ${isLost ? 'checked' : ''}> ${PLANG.lost_abbr}</label>
        <button type="button" class="stage-req-toggle ${reqCount > 0 ? 'has-reqs' : ''}" onclick="toggleReqPanel(this)" title="${PLANG.req_title}">
            <i class="bi bi-list-check"></i>
            ${reqCount > 0 ? '<span class="req-count">' + reqCount + '</span>' : ''}
        </button>
        <button type="button" class="drawer-stage-delete" onclick="removeDrawerStageRow(this)" title="${escapeHtml(PLANG.remove_stage)}"><i class="bi bi-trash"></i></button>
    `;

    const panel = document.createElement('div');
    panel.className = 'stage-req-panel';
    panel.dataset.idx = idx;
    panel.innerHTML = `
        <div class="req-title"><i class="bi bi-list-check"></i> ${PLANG.req_title}</div>
        <div class="req-list"></div>
        <button type="button" class="btn-add-req" onclick="addReqRow(this.closest('.stage-req-panel'))">
            <i class="bi bi-plus"></i> ${PLANG.req_add}
        </button>
    `;

    wrapper.appendChild(li);
    wrapper.appendChild(panel);
    list.appendChild(wrapper);

    // Populate existing requirements
    reqs.forEach(r => addReqRow(panel, r));

    if (!list._sortable) {
        list._sortable = Sortable.create(list, {
            handle: '.stage-drag-handle',
            animation: 150,
            draggable: '.drawer-stage-wrapper',
        });
    }
}

function toggleReqPanel(btn) {
    const wrapper = btn.closest('.drawer-stage-wrapper');
    const panel = wrapper.querySelector('.stage-req-panel');
    panel.classList.toggle('open');
}

function addReqRow(panel, data) {
    const rl = panel.querySelector('.req-list');
    const div = document.createElement('div');
    div.className = 'req-row';
    div.innerHTML = `
        <input type="text" class="req-subject" placeholder="${PLANG.req_subject_ph}" value="${escapeHtml((data && data.subject) || '')}" data-req="subject">
        <select class="req-type" data-req="task_type">
            <option value="call" ${data?.task_type === 'call' ? 'selected' : ''}>${PLANG.req_type_call}</option>
            <option value="email" ${data?.task_type === 'email' ? 'selected' : ''}>${PLANG.req_type_email}</option>
            <option value="task" ${(!data || data.task_type === 'task') ? 'selected' : ''}>${PLANG.req_type_task}</option>
            <option value="visit" ${data?.task_type === 'visit' ? 'selected' : ''}>${PLANG.req_type_visit}</option>
            <option value="whatsapp" ${data?.task_type === 'whatsapp' ? 'selected' : ''}>${PLANG.req_type_whatsapp}</option>
            <option value="meeting" ${data?.task_type === 'meeting' ? 'selected' : ''}>${PLANG.req_type_meeting}</option>
        </select>
        <select class="req-priority" data-req="priority">
            <option value="low" ${data?.priority === 'low' ? 'selected' : ''}>${PLANG.req_priority_low}</option>
            <option value="medium" ${(!data || data.priority === 'medium') ? 'selected' : ''}>${PLANG.req_priority_medium}</option>
            <option value="high" ${data?.priority === 'high' ? 'selected' : ''}>${PLANG.req_priority_high}</option>
        </select>
        <input type="number" class="req-days" min="0" max="365" value="${(data && data.due_date_offset != null) ? data.due_date_offset : 1}" data-req="due_date_offset" title="Prazo em dias">
        <button type="button" class="req-del" onclick="this.closest('.req-row').remove();updateReqCount(this)"><i class="bi bi-x-lg"></i></button>
    `;
    rl.appendChild(div);
    updateReqCount(div);
}

function updateReqCount(el) {
    const wrapper = el.closest('.drawer-stage-wrapper');
    if (!wrapper) return;
    const count = wrapper.querySelectorAll('.req-row').length;
    const toggle = wrapper.querySelector('.stage-req-toggle');
    toggle.classList.toggle('has-reqs', count > 0);
    const badge = toggle.querySelector('.req-count');
    if (count > 0) {
        if (badge) { badge.textContent = count; }
        else { toggle.insertAdjacentHTML('beforeend', '<span class="req-count">' + count + '</span>'); }
    } else if (badge) { badge.remove(); }
}

function getStageRequiredTasks(wrapper) {
    const rows = wrapper.querySelectorAll('.req-row');
    const tasks = [];
    rows.forEach(row => {
        const subject = row.querySelector('[data-req="subject"]').value.trim();
        if (!subject) return;
        tasks.push({
            subject: subject,
            task_type: row.querySelector('[data-req="task_type"]').value,
            priority: row.querySelector('[data-req="priority"]').value,
            due_date_offset: parseInt(row.querySelector('[data-req="due_date_offset"]').value) || 1,
        });
    });
    return tasks;
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

/* ---- Open drawer: New pipeline ---- */
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
    openDrawer();
    setTimeout(() => document.getElementById('pipelineName').focus(), 100);
});

/* ---- Open drawer: Edit existing pipeline ---- */
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

    openDrawer();
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

        closeDrawer();

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
