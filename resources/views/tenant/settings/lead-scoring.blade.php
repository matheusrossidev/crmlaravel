@extends('tenant.layouts.app')

@php
    $title = __('scoring.title');
    $pageIcon = 'speedometer2';
@endphp

@push('styles')
<style>
    .scoring-table-wrap { background: #fff; border: 1px solid #e8eaf0; border-radius: 12px; overflow: hidden; }
    .scoring-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
    .scoring-table thead th {
        padding: 11px 16px; font-size: 11.5px; font-weight: 700; color: #9ca3af;
        text-transform: uppercase; letter-spacing: .06em; background: #fafafa;
        border-bottom: 1px solid #f0f2f7;
    }
    .scoring-table tbody tr { border-bottom: 1px solid #f7f8fa; }
    .scoring-table tbody tr:last-child { border-bottom: none; }
    .scoring-table tbody td { padding: 12px 16px; color: #374151; vertical-align: middle; }

    .points-badge {
        display: inline-flex; align-items: center; gap: 3px;
        padding: 3px 10px; border-radius: 100px; font-size: 12.5px; font-weight: 700;
    }
    .points-badge.positive { background: #ecfdf5; color: #059669; }
    .points-badge.negative { background: #fef2f2; color: #dc2626; }

    .cat-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 100px; font-size: 12px; font-weight: 600;
    }
    .cat-badge.engagement { background: #eff6ff; color: #2563eb; }
    .cat-badge.pipeline   { background: #f0fdf4; color: #16a34a; }
    .cat-badge.profile    { background: #fdf4ff; color: #a855f7; }

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

    /* Drawer */
    .drawer-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.35); z-index: 300;
    }
    .drawer-overlay.open { display: block; }
    .drawer {
        position: fixed; top: 0; right: -480px;
        width: 480px; height: 100vh; background: #fff;
        z-index: 301; transition: right .25s cubic-bezier(.4,0,.2,1);
        display: flex; flex-direction: column;
        box-shadow: -4px 0 24px rgba(0,0,0,.1);
    }
    .drawer.open { right: 0; }
    .drawer-header {
        padding: 18px 22px; border-bottom: 1px solid #f0f2f7;
        display: flex; align-items: center; justify-content: space-between;
        font-size: 15px; font-weight: 700; color: #1a1d23;
    }
    .drawer-body { padding: 22px; flex: 1; overflow-y: auto; }
    .drawer-footer {
        padding: 16px 22px; border-top: 1px solid #f0f2f7;
        display: flex; gap: 10px; justify-content: flex-end;
    }

    .form-group { margin-bottom: 16px; }
    .form-label {
        display: block; font-size: 12.5px; font-weight: 600;
        color: #374151; margin-bottom: 6px;
    }
    .form-hint { font-size: 11.5px; color: #9ca3af; margin-top: 4px; }
    .form-input, .form-select {
        width: 100%; padding: 9px 12px;
        border: 1px solid #d1d5db; border-radius: 9px;
        font-size: 13.5px; color: #1a1d23;
        outline: none; transition: border-color .15s; background: #fff;
        font-family: inherit; box-sizing: border-box;
    }
    .form-input:focus, .form-select:focus { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }

    .form-row { display: flex; gap: 12px; }
    .form-row .form-group { flex: 1; }

    .btn-icon {
        width: 28px; height: 28px; border-radius: 7px; border: 1px solid #e8eaf0;
        background: #fff; color: #6b7280;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 13px; transition: all .15s;
    }
    .btn-icon:hover { background: #f0f4ff; color: #374151; }
    .btn-icon.danger:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

    .btn-save {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #0085f3; color: #fff;
        border: none; border-radius: 100px; font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
    }
    .btn-save:hover { background: #0070d1; }
    .btn-cancel {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #f4f6fb; color: #374151;
        border: 1px solid #e8eaf0; border-radius: 100px;
        font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s;
    }
    .btn-cancel:hover { background: #e8eaf0; }

    .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
    .section-title  { font-size: 15px; font-weight: 700; color: #1a1d23; }
    .section-subtitle { font-size: 13px; color: #6b7280; margin-top: 2px; }

    .empty-state {
        text-align: center; padding: 48px 24px;
    }
    .empty-state i { font-size: 40px; color: #d1d5db; display: block; margin-bottom: 12px; }
    .empty-state p { color: #9ca3af; font-size: 13.5px; margin: 0; }
    .empty-state .sub { font-size: 12.5px; margin-top: 4px; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">CRM</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('scoring.title') }}</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">{{ __('scoring.subtitle') }}</p>
            </div>
            <button class="btn-primary-sm" id="btnNewRule">
                <i class="bi bi-plus-lg"></i> {{ __('scoring.new_rule') }}
            </button>
        </div>
    </div>

    {{-- Limites globais de score (Fix 7) --}}
    <div style="background:#fff;border:1px solid #e8eaf0;border-radius:14px;margin-bottom:18px;">
        <div style="padding:14px 20px;border-bottom:1px solid #f0f2f7;font-size:14px;font-weight:700;color:#1a1d23;">
            <i class="bi bi-shield-check" style="color:#0085f3;"></i> {{ __('scoring.global_limits') }}
        </div>
        <div style="padding:16px 20px;display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;">
            <div style="flex:0 0 180px;">
                <label class="form-label">{{ __('scoring.score_min_label') }}</label>
                <input type="number" id="scoreMin" class="form-input" value="{{ $scoreSettings['min'] }}" placeholder="0">
            </div>
            <div style="flex:0 0 180px;">
                <label class="form-label">{{ __('scoring.score_max_label') }}</label>
                <input type="number" id="scoreMax" class="form-input" value="{{ $scoreSettings['max'] ?? '' }}" placeholder="{{ __('scoring.no_max') }}">
            </div>
            <button class="btn-save" onclick="saveScoreSettings()">
                <i class="bi bi-check2"></i> {{ __('scoring.save_limits') }}
            </button>
            <div style="flex:1;font-size:11.5px;color:#9ca3af;align-self:center;min-width:200px;">
                {{ __('scoring.global_limits_help') }}
            </div>
        </div>
    </div>

    <div class="scoring-table-wrap">
        <table class="scoring-table">
            <thead>
                <tr>
                    <th>{{ __('scoring.col_name') }}</th>
                    <th>{{ __('scoring.col_category') }}</th>
                    <th>{{ __('scoring.col_event') }}</th>
                    <th style="text-align:center;">{{ __('scoring.col_points') }}</th>
                    <th style="text-align:center;">{{ __('scoring.col_cooldown') }}</th>
                    <th style="text-align:center;width:80px;">{{ __('scoring.col_status') }}</th>
                    <th style="width:80px;"></th>
                </tr>
            </thead>
            <tbody id="rulesBody">
                @forelse($rules as $rule)
                <tr data-rule-id="{{ $rule->id }}">
                    <td class="rule-name-cell">{{ $rule->name }}</td>
                    <td><span class="cat-badge {{ $rule->category }}">{{ __('scoring.cat_' . $rule->category) }}</span></td>
                    <td style="font-size:12.5px;color:#6b7280;">{{ __('scoring.evt_' . $rule->event_type) }}</td>
                    <td style="text-align:center;">
                        <span class="points-badge {{ $rule->points >= 0 ? 'positive' : 'negative' }}">
                            {{ $rule->points >= 0 ? '+' : '' }}{{ $rule->points }}
                        </span>
                    </td>
                    <td style="text-align:center;font-size:12.5px;color:#6b7280;">
                        {{ $rule->cooldown_hours > 0 ? $rule->cooldown_hours . 'h' : '—' }}
                    </td>
                    <td style="text-align:center;">
                        <label class="toggle">
                            <input type="checkbox" {{ $rule->is_active ? 'checked' : '' }}
                                   onchange="toggleRule({{ $rule->id }}, this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </td>
                    <td>
                        <div style="display:flex;gap:5px;justify-content:flex-end;">
                            <button class="btn-icon" onclick="openEditRule({{ $rule->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn-icon danger" onclick="deleteRule({{ $rule->id }}, this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="emptyRules">
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="bi bi-speedometer2"></i>
                            <p>{{ __('scoring.no_rules') }}</p>
                            <p class="sub">{{ __('scoring.no_rules_sub') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

{{-- Drawer: Scoring Rule --}}
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="drawer">
    <div class="drawer-header">
        <span id="drawerTitle">{{ __('scoring.new_rule') }}</span>
        <button onclick="closeDrawer()" style="background:none;border:none;font-size:18px;color:#6b7280;cursor:pointer;">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="drawer-body">
        <input type="hidden" id="ruleId">

        <div class="form-group">
            <label class="form-label">{{ __('scoring.field_name') }}</label>
            <input type="text" id="ruleName" class="form-input" placeholder="{{ __('scoring.field_name_placeholder') }}" maxlength="100">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">{{ __('scoring.field_category') }}</label>
                <select id="ruleCategory" class="form-select">
                    <option value="engagement">{{ __('scoring.cat_engagement') }}</option>
                    <option value="pipeline">{{ __('scoring.cat_pipeline') }}</option>
                    <option value="profile">{{ __('scoring.cat_profile') }}</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('scoring.field_event') }}</label>
                <select id="ruleEvent" class="form-select">
                    <option value="message_received">{{ __('scoring.evt_message_received') }}</option>
                    <option value="message_sent_media">{{ __('scoring.evt_message_sent_media') }}</option>
                    <option value="fast_reply">{{ __('scoring.evt_fast_reply') }}</option>
                    <option value="stage_advanced">{{ __('scoring.evt_stage_advanced') }}</option>
                    <option value="stage_regressed">{{ __('scoring.evt_stage_regressed') }}</option>
                    <option value="lead_won">{{ __('scoring.evt_lead_won') }}</option>
                    <option value="lead_lost">{{ __('scoring.evt_lead_lost') }}</option>
                    <option value="profile_complete">{{ __('scoring.evt_profile_complete') }}</option>
                    <option value="inactive_3d">{{ __('scoring.evt_inactive_3d') }}</option>
                    <option value="inactive_7d">{{ __('scoring.evt_inactive_7d') }}</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">{{ __('scoring.field_points') }}</label>
                <input type="number" id="rulePoints" class="form-input" value="5" min="-100" max="100">
                <div class="form-hint">{{ __('scoring.field_points_help') }}</div>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('scoring.field_cooldown') }}</label>
                <input type="number" id="ruleCooldown" class="form-input" value="0" min="0" max="720">
                <div class="form-hint">{{ __('scoring.field_cooldown_help') }}</div>
            </div>
        </div>

        {{-- ===== Fase 1: Filtros estruturais e limites ===== --}}

        <div style="margin:18px 0 12px;padding-top:14px;border-top:1px dashed #e8eaf0;font-size:11.5px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;">
            {{ __('scoring.section_filters') }}
        </div>

        {{-- Filtro por Pipeline (Fix 1) --}}
        <div class="form-group">
            <label class="form-label">{{ __('scoring.pipeline_filter') }}</label>
            <select id="rulePipelineId" class="form-select">
                <option value="">{{ __('scoring.any_pipeline') }}</option>
                @foreach($pipelines as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
            <div class="form-hint">{{ __('scoring.pipeline_filter_help') }}</div>
        </div>

        {{-- Filtro por Etapa (Fix 2) — só visível quando event_type ∈ stage_advanced/regressed/inactive_7d --}}
        <div class="form-group" id="stageFilterGroup" style="display:none;">
            <label class="form-label">{{ __('scoring.stage_filter') }}</label>
            <select id="ruleStageId" class="form-select">
                <option value="">{{ __('scoring.any_stage') }}</option>
            </select>
            <div class="form-hint">{{ __('scoring.stage_filter_help') }}</div>
        </div>

        {{-- Validade (Fix 5) --}}
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">{{ __('scoring.valid_from') }}</label>
                <input type="date" id="ruleValidFrom" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('scoring.valid_until') }}</label>
                <input type="date" id="ruleValidUntil" class="form-input">
            </div>
        </div>

        {{-- Limite de disparos (Fix 6) --}}
        <div class="form-group">
            <label class="form-label">{{ __('scoring.max_triggers') }}</label>
            <input type="number" id="ruleMaxTriggers" class="form-input" min="1" max="1000" placeholder="{{ __('scoring.no_limit') }}">
            <div class="form-hint">{{ __('scoring.max_triggers_help') }}</div>
        </div>
    </div>
    <div class="drawer-footer">
        <button class="btn-cancel" onclick="closeDrawer()">{{ __('scoring.btn_cancel') }}</button>
        <button class="btn-save" onclick="saveRule()">
            <i class="bi bi-check2"></i> {{ __('scoring.btn_save') }}
        </button>
    </div>
</div>

@include('partials._drawer-as-modal')
@endsection

@push('scripts')
<script>
const SLANG = @json(__('scoring'));
const RULE_STORE = @json(route('settings.scoring.store'));
const RULE_UPD   = @json(route('settings.scoring.update',  ['rule' => '__ID__']));
const RULE_DEL   = @json(route('settings.scoring.destroy', ['rule' => '__ID__']));
const SCORE_SETTINGS_URL = @json(route('settings.scoring.score-settings'));
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;

// Rule data cache for editing (Fase 1: incluir novos campos)
const rulesData = {!! json_encode(
    $rules->keyBy('id')->map(fn($r) => $r->only([
        'id','name','category','event_type','pipeline_id','stage_id',
        'points','cooldown_hours','is_active','conditions',
        'valid_from','valid_until','max_triggers_per_lead'
    ]))
) !!};

// Pipelines + stages cache pra popular o select de stages dinamicamente
const PIPELINES_DATA = {!! json_encode(
    $pipelines->map(fn($p) => [
        'id'     => $p->id,
        'name'   => $p->name,
        'stages' => $p->stages->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values(),
    ])->keyBy('id')
) !!};

// Eventos onde o filtro por etapa faz sentido
const STAGE_FILTER_EVENTS = ['stage_advanced','stage_regressed','inactive_7d'];

const EVENT_LABELS = {
    message_received: SLANG.evt_message_received,
    message_sent_media: SLANG.evt_message_sent_media,
    fast_reply: SLANG.evt_fast_reply,
    stage_advanced: SLANG.evt_stage_advanced,
    stage_regressed: SLANG.evt_stage_regressed,
    lead_won: SLANG.evt_lead_won,
    lead_lost: SLANG.evt_lead_lost,
    profile_complete: SLANG.evt_profile_complete,
    inactive_3d: SLANG.evt_inactive_3d,
    inactive_7d: SLANG.evt_inactive_7d,
};

const CAT_LABELS = {
    engagement: SLANG.cat_engagement,
    pipeline: SLANG.cat_pipeline,
    profile: SLANG.cat_profile,
};

/* ---- Drawer ---- */
function openDrawer() {
    document.getElementById('drawerOverlay').classList.add('open');
    document.getElementById('drawer').classList.add('open');
    setTimeout(() => document.getElementById('ruleName').focus(), 200);
}

function closeDrawer() {
    document.getElementById('drawerOverlay').classList.remove('open');
    document.getElementById('drawer').classList.remove('open');
}

/* ---- New ---- */
document.getElementById('btnNewRule').addEventListener('click', () => {
    document.getElementById('drawerTitle').textContent = SLANG.new_rule;
    document.getElementById('ruleId').value = '';
    document.getElementById('ruleName').value = '';
    document.getElementById('ruleCategory').value = 'engagement';
    document.getElementById('ruleEvent').value = 'message_received';
    document.getElementById('rulePoints').value = '5';
    document.getElementById('ruleCooldown').value = '0';
    // Fase 1: zerar novos campos
    document.getElementById('rulePipelineId').value = '';
    document.getElementById('ruleStageId').innerHTML = `<option value="">${SLANG.any_stage}</option>`;
    document.getElementById('ruleValidFrom').value = '';
    document.getElementById('ruleValidUntil').value = '';
    document.getElementById('ruleMaxTriggers').value = '';
    updateStageFilterVisibility();
    openDrawer();
});

/* ---- Edit ---- */
function openEditRule(id) {
    const r = rulesData[id];
    if (!r) return;
    document.getElementById('drawerTitle').textContent = SLANG.edit_rule;
    document.getElementById('ruleId').value = r.id;
    document.getElementById('ruleName').value = r.name;
    document.getElementById('ruleCategory').value = r.category;
    document.getElementById('ruleEvent').value = r.event_type;
    document.getElementById('rulePoints').value = r.points;
    document.getElementById('ruleCooldown').value = r.cooldown_hours;
    // Fase 1
    document.getElementById('rulePipelineId').value = r.pipeline_id || '';
    populateStageOptions(r.pipeline_id, r.stage_id);
    document.getElementById('ruleValidFrom').value  = r.valid_from  ? r.valid_from.substring(0,10)  : '';
    document.getElementById('ruleValidUntil').value = r.valid_until ? r.valid_until.substring(0,10) : '';
    document.getElementById('ruleMaxTriggers').value = r.max_triggers_per_lead || '';
    updateStageFilterVisibility();
    openDrawer();
}

/* ---- Fase 1 helpers ---- */

// Popula o select de stages baseado no pipeline escolhido
function populateStageOptions(pipelineId, selectedStageId = null) {
    const stageSelect = document.getElementById('ruleStageId');
    stageSelect.innerHTML = `<option value="">${SLANG.any_stage}</option>`;
    if (!pipelineId || !PIPELINES_DATA[pipelineId]) return;
    PIPELINES_DATA[pipelineId].stages.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.name;
        if (selectedStageId && parseInt(selectedStageId) === parseInt(s.id)) opt.selected = true;
        stageSelect.appendChild(opt);
    });
}

// Mostra/esconde filtro de stage baseado no event_type E pipeline selecionado
function updateStageFilterVisibility() {
    const event = document.getElementById('ruleEvent').value;
    const pipelineId = document.getElementById('rulePipelineId').value;
    const showStage = STAGE_FILTER_EVENTS.includes(event) && pipelineId !== '';
    document.getElementById('stageFilterGroup').style.display = showStage ? 'block' : 'none';
}

// Listeners pros campos que controlam visibilidade do stage filter
document.getElementById('ruleEvent').addEventListener('change', updateStageFilterVisibility);
document.getElementById('rulePipelineId').addEventListener('change', () => {
    populateStageOptions(document.getElementById('rulePipelineId').value);
    updateStageFilterVisibility();
});

/* ---- Salvar limites globais (Fix 7) ---- */
async function saveScoreSettings() {
    const min = document.getElementById('scoreMin').value.trim();
    const max = document.getElementById('scoreMax').value.trim();
    const payload = {
        score_min: min === '' ? null : parseInt(min),
        score_max: max === '' ? null : parseInt(max),
    };
    try {
        const res = await fetch(SCORE_SETTINGS_URL, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.success) { toastr.error(data.message || SLANG.toast_error); return; }
        toastr.success(SLANG.limits_saved);
    } catch (e) {
        toastr.error(SLANG.toast_error);
    }
}

/* ---- Save ---- */
async function saveRule() {
    const id = document.getElementById('ruleId').value;
    const name = document.getElementById('ruleName').value.trim();
    if (!name) { document.getElementById('ruleName').focus(); return; }

    const pipelineId = document.getElementById('rulePipelineId').value;
    const stageId    = document.getElementById('ruleStageId').value;
    const validFrom  = document.getElementById('ruleValidFrom').value;
    const validUntil = document.getElementById('ruleValidUntil').value;
    const maxTrig    = document.getElementById('ruleMaxTriggers').value;

    const payload = {
        name,
        category: document.getElementById('ruleCategory').value,
        event_type: document.getElementById('ruleEvent').value,
        points: parseInt(document.getElementById('rulePoints').value) || 0,
        cooldown_hours: parseInt(document.getElementById('ruleCooldown').value) || 0,
        is_active: true,
        // Fase 1: filtros e limites
        pipeline_id: pipelineId === '' ? null : parseInt(pipelineId),
        stage_id:    stageId    === '' ? null : parseInt(stageId),
        valid_from:  validFrom  || null,
        valid_until: validUntil || null,
        max_triggers_per_lead: maxTrig === '' ? null : parseInt(maxTrig),
    };

    const url    = id ? RULE_UPD.replace('__ID__', id) : RULE_STORE;
    const method = id ? 'PUT' : 'POST';

    try {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.success) { toastr.error(data.message || SLANG.toast_error); return; }

        closeDrawer();
        const r = data.rule;
        rulesData[r.id] = r;

        toastr.success(id ? SLANG.toast_updated : SLANG.toast_created);

        const body = document.getElementById('rulesBody');
        document.getElementById('emptyRules')?.remove();

        if (id) {
            const row = body.querySelector(`tr[data-rule-id="${id}"]`);
            if (row) {
                row.querySelector('.rule-name-cell').textContent = r.name;
                row.cells[1].innerHTML = `<span class="cat-badge ${r.category}">${CAT_LABELS[r.category] || r.category}</span>`;
                row.cells[2].textContent = EVENT_LABELS[r.event_type] || r.event_type;
                const cls = r.points >= 0 ? 'positive' : 'negative';
                const prefix = r.points >= 0 ? '+' : '';
                row.cells[3].innerHTML = `<span class="points-badge ${cls}">${prefix}${r.points}</span>`;
                row.cells[4].textContent = r.cooldown_hours > 0 ? r.cooldown_hours + 'h' : '—';
            }
        } else {
            const cls = r.points >= 0 ? 'positive' : 'negative';
            const prefix = r.points >= 0 ? '+' : '';
            body.insertAdjacentHTML('beforeend', `<tr data-rule-id="${r.id}">
                <td class="rule-name-cell">${escapeHtml(r.name)}</td>
                <td><span class="cat-badge ${r.category}">${CAT_LABELS[r.category] || r.category}</span></td>
                <td style="font-size:12.5px;color:#6b7280;">${EVENT_LABELS[r.event_type] || r.event_type}</td>
                <td style="text-align:center;"><span class="points-badge ${cls}">${prefix}${r.points}</span></td>
                <td style="text-align:center;font-size:12.5px;color:#6b7280;">${r.cooldown_hours > 0 ? r.cooldown_hours + 'h' : '—'}</td>
                <td style="text-align:center;">
                    <label class="toggle">
                        <input type="checkbox" checked onchange="toggleRule(${r.id},this.checked)">
                        <span class="toggle-slider"></span>
                    </label>
                </td>
                <td>
                    <div style="display:flex;gap:5px;justify-content:flex-end;">
                        <button class="btn-icon" onclick="openEditRule(${r.id})"><i class="bi bi-pencil"></i></button>
                        <button class="btn-icon danger" onclick="deleteRule(${r.id},this)"><i class="bi bi-trash"></i></button>
                    </div>
                </td>
            </tr>`);
        }
    } catch (e) {
        toastr.error(SLANG.toast_error);
    }
}

/* ---- Toggle ---- */
async function toggleRule(id, active) {
    const r = rulesData[id];
    if (!r) return;
    await fetch(RULE_UPD.replace('__ID__', id), {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ ...r, is_active: active }),
    });
    r.is_active = active;
}

/* ---- Delete ---- */
function deleteRule(id, btn) {
    confirmAction({
        title: SLANG.confirm_delete_title,
        message: SLANG.confirm_delete_msg,
        confirmText: SLANG.confirm_delete_btn,
        onConfirm: async () => {
            const res = await fetch(RULE_DEL.replace('__ID__', id), {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF },
            });
            const data = await res.json();
            if (!data.success) { toastr.error(SLANG.toast_error); return; }
            btn.closest('tr').remove();
            delete rulesData[id];
            toastr.success(SLANG.toast_deleted);
        },
    });
}

/* ---- Helpers ---- */
function escapeHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush
