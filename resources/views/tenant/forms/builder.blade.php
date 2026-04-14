@extends('tenant.layouts.app')

@php
    $title = __('forms.builder_title', ['name' => $form->name]);
    $pageIcon = 'bi-grid-3x3-gap';
@endphp

@push('styles')
<style>
    .fb-sidebar { width:260px; background:#fff; border-right:1.5px solid #e8eaf0; padding:20px; overflow-y:auto; }
    .fb-canvas { flex:1; padding:24px; overflow-y:auto; background:#f9fafb; min-height:500px; }
    .fb-field-btn { display:flex;align-items:center;gap:8px;width:100%;padding:10px 14px;background:#f9fafb;border:1.5px solid #e8eaf0;border-radius:10px;font-size:13px;color:#374151;cursor:pointer;margin-bottom:6px;transition:.15s; }
    .fb-field-btn:hover { border-color:#0085f3;color:#0085f3; }
    .fb-field-card { background:#fff;border:1.5px solid #e8eaf0;border-radius:12px;padding:16px;margin-bottom:10px;cursor:grab;position:relative; }
    .fb-field-card:hover { border-color:#0085f3; }
    .fb-field-card.selected { border-color:#0085f3;box-shadow:0 0 0 3px rgba(0,133,243,.1); }
    .fb-field-card .fb-drag { position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#d1d5db;font-size:16px;cursor:grab; }
    .fb-field-card .fb-actions { position:absolute;right:8px;top:8px;display:flex;gap:4px; }
    .fb-field-card .fb-actions button { background:none;border:none;color:#9ca3af;cursor:pointer;font-size:13px;padding:2px 4px; }
    .fb-field-card .fb-actions button:hover { color:#dc2626; }
    .fb-field-label { font-size:13px;font-weight:600;color:#1a1d23;margin-left:20px; }
    .fb-field-type { font-size:11px;color:#9ca3af;margin-left:20px; }
    .fb-field-req { display:inline-block;color:#dc2626;font-weight:700;margin-left:2px; }
    .fb-empty { text-align:center;padding:60px 20px;color:#9ca3af;font-size:14px; }
    .fb-config { width:300px;background:#fff;border-left:1.5px solid #e8eaf0;padding:20px;overflow-y:auto;display:none; }
    .fb-config.open { display:block; }
    .fb-config label { font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;margin-top:12px; }
    .fb-config input, .fb-config textarea, .fb-config select { font-size:13px; }

    /* Step groups (multi-step) */
    .fb-step-group { background:#f0f7ff;border:2px dashed #bfdbfe;border-radius:14px;padding:16px;margin-bottom:14px; }
    .fb-step-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:12px; }
    .fb-step-title { font-size:13px;font-weight:700;color:#0085f3;display:flex;align-items:center;gap:6px; }
    .fb-step-actions button { background:none;border:none;cursor:pointer;font-size:12px;color:#6b7280;padding:2px 6px; }
    .fb-step-actions button:hover { color:#dc2626; }
    .fb-add-step-btn { display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:12px;background:#eff6ff;border:2px dashed #bfdbfe;border-radius:12px;color:#0085f3;font-size:13px;font-weight:600;cursor:pointer;margin-top:8px;transition:.15s; }
    .fb-add-step-btn:hover { background:#dbeafe;border-color:#0085f3; }

    /* Conditional logic badge */
    .fb-cond-badge { display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:600;color:#7c3aed;background:#ede9fe;padding:2px 8px;border-radius:20px;margin-left:20px;margin-top:4px; }

    /* Sidebar sections */
    .fb-sidebar-section { margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid #f0f2f7; }
    .fb-sidebar-section:last-child { border-bottom:none; }
    .fb-sidebar-section h4 { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;margin:0 0 8px; }
</style>
@endpush

@section('content')
<div class="page-container" style="padding:0;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-bottom:1px solid #e8eaf0;background:#fff;">
        <div style="display:flex;align-items:center;gap:12px;">
            <a href="{{ route('forms.edit', $form) }}" style="color:#6b7280;font-size:18px;"><i class="bi bi-arrow-left"></i></a>
            <h2 style="font-size:16px;font-weight:700;color:#1a1d23;margin:0;">{{ __('forms.builder_title', ['name' => $form->name]) }}</h2>
            <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:{{ $form->type === 'conversational' ? '#fef3c7' : ($form->type === 'multistep' ? '#ede9fe' : '#ecfdf5') }};color:{{ $form->type === 'conversational' ? '#92400e' : ($form->type === 'multistep' ? '#5b21b6' : '#065f46') }};">
                {{ __('forms.type_' . $form->type) }}
            </span>
        </div>
        <div style="display:flex;gap:8px;">
            <button onclick="saveFields()" style="padding:8px 20px;background:#0085f3;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="bi bi-check-lg"></i> {{ __('forms.save_fields') }}
            </button>
            <a href="{{ route('forms.mapping', $form) }}" style="padding:8px 20px;background:#eff6ff;color:#0085f3;border:1.5px solid #bfdbfe;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;">
                {{ __('forms.next_mapping') }} <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>

    <div style="display:flex;height:calc(100vh - 140px);">
        {{-- Sidebar: field types --}}
        <div class="fb-sidebar">
            <div class="fb-sidebar-section">
                <h4>{{ __('forms.add_field') }}</h4>
                @php
                    $fieldTypes = [
                        'text' => ['icon' => 'bi-type', 'label' => __('forms.field_text')],
                        'textarea' => ['icon' => 'bi-text-paragraph', 'label' => __('forms.field_textarea')],
                        'email' => ['icon' => 'bi-envelope', 'label' => __('forms.field_email')],
                        'tel' => ['icon' => 'bi-phone', 'label' => __('forms.field_tel')],
                        'number' => ['icon' => 'bi-hash', 'label' => __('forms.field_number')],
                        'select' => ['icon' => 'bi-chevron-down', 'label' => __('forms.field_select')],
                        'checkbox' => ['icon' => 'bi-check-square', 'label' => __('forms.field_checkbox')],
                        'radio' => ['icon' => 'bi-circle', 'label' => __('forms.field_radio')],
                        'file' => ['icon' => 'bi-paperclip', 'label' => __('forms.field_file')],
                        'heading' => ['icon' => 'bi-type-h1', 'label' => __('forms.field_heading')],
                        'divider' => ['icon' => 'bi-dash-lg', 'label' => __('forms.field_divider')],
                    ];
                @endphp
                @foreach($fieldTypes as $type => $info)
                    <button class="fb-field-btn" onclick="addField('{{ $type }}', '{{ $info['label'] }}')">
                        <i class="bi {{ $info['icon'] }}"></i> {{ $info['label'] }}
                    </button>
                @endforeach
            </div>

            @if($form->type === 'multistep')
            <div class="fb-sidebar-section">
                <h4>{{ __('forms.steps_section') }}</h4>
                <button class="fb-field-btn" onclick="addStep()" style="border-color:#bfdbfe;color:#0085f3;">
                    <i class="bi bi-plus-square-dotted"></i> {{ __('forms.add_step') }}
                </button>
            </div>
            @endif
        </div>

        {{-- Canvas --}}
        <div class="fb-canvas" id="fbCanvas">
            <div class="fb-empty" id="fbEmpty">
                <i class="bi bi-arrow-left" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                {{ __('forms.builder_subtitle') }}
            </div>
        </div>

        {{-- Config panel --}}
        <div class="fb-config" id="fbConfig">
            <h3 style="font-size:14px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('forms.field_config') }}</h3>
            <div id="fbConfigBody"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const FORM_TYPE = '{{ $form->type }}';
let fields = {!! json_encode($form->fields ?? []) !!};
let steps = {!! json_encode($form->steps ?? []) !!};
let conditions = {!! json_encode($form->conditional_logic ?? []) !!};
let selectedIdx = null;

// ── Render ──────────────────────────────────────────────
function render() {
    const canvas = document.getElementById('fbCanvas');
    const empty = document.getElementById('fbEmpty');

    if (!fields.length) {
        canvas.innerHTML = '';
        canvas.appendChild(empty);
        empty.style.display = '';
        return;
    }
    empty.style.display = 'none';

    let html = '';

    if (FORM_TYPE === 'multistep') {
        html = renderMultistep();
    } else {
        html = renderFlat();
    }

    canvas.innerHTML = html;
    canvas.appendChild(empty);
}

function renderFlat() {
    let html = '';
    fields.forEach((f, i) => {
        html += renderFieldCard(f, i);
    });
    return html;
}

function renderMultistep() {
    // Ensure at least one step exists
    if (!steps.length) {
        steps.push({ id: 's' + Date.now(), title: '{{ __("forms.step") }} 1' });
    }

    // Assign unassigned fields to first step
    fields.forEach(f => {
        if (!f.step_id) f.step_id = steps[0].id;
    });

    let html = '';
    steps.forEach((step, si) => {
        const stepFields = fields.map((f, i) => ({ field: f, idx: i })).filter(x => x.field.step_id === step.id);
        html += `<div class="fb-step-group">
            <div class="fb-step-header">
                <div class="fb-step-title">
                    <i class="bi bi-layers"></i>
                    <input type="text" value="${window.escapeHtml(step.title)}" onchange="steps[${si}].title=this.value"
                        style="border:none;background:transparent;font-size:13px;font-weight:700;color:#0085f3;width:200px;">
                    <span style="font-size:11px;color:#6b7280;font-weight:400;">(${stepFields.length} {{ __('forms.fields_count') }})</span>
                </div>
                <div class="fb-step-actions">
                    ${si > 0 ? `<button onclick="moveStep(${si},-1)" title="{{ __('forms.move_up') }}"><i class="bi bi-chevron-up"></i></button>` : ''}
                    ${si < steps.length - 1 ? `<button onclick="moveStep(${si},1)" title="{{ __('forms.move_down') }}"><i class="bi bi-chevron-down"></i></button>` : ''}
                    ${steps.length > 1 ? `<button onclick="removeStep(${si})" title="{{ __('forms.remove_step') }}"><i class="bi bi-trash3"></i></button>` : ''}
                </div>
            </div>`;

        stepFields.forEach(({ field, idx }) => {
            html += renderFieldCard(field, idx);
        });

        html += `</div>`;
    });

    html += `<button class="fb-add-step-btn" onclick="addStep()">
        <i class="bi bi-plus-lg"></i> {{ __('forms.add_step') }}
    </button>`;

    return html;
}

function renderFieldCard(f, i) {
    const req = f.required ? '<span class="fb-field-req">*</span>' : '';
    const cond = getConditionForField(f.id);
    const condBadge = cond ? `<div class="fb-cond-badge"><i class="bi bi-lightning-charge-fill"></i> {{ __('forms.has_condition') }}</div>` : '';

    return `<div class="fb-field-card ${selectedIdx === i ? 'selected' : ''}" onclick="selectField(${i})">
        <span class="fb-drag"><i class="bi bi-grip-vertical"></i></span>
        <div class="fb-field-label">${window.escapeHtml(f.label || 'Campo')} ${req}</div>
        <div class="fb-field-type">${f.type}</div>
        ${condBadge}
        <div class="fb-actions">
            <button onclick="event.stopPropagation();moveField(${i},-1)" title="{{ __('forms.move_up') }}"><i class="bi bi-chevron-up"></i></button>
            <button onclick="event.stopPropagation();moveField(${i},1)" title="{{ __('forms.move_down') }}"><i class="bi bi-chevron-down"></i></button>
            <button onclick="event.stopPropagation();removeField(${i})" title="{{ __('forms.remove') }}"><i class="bi bi-trash3"></i></button>
        </div>
    </div>`;
}

// ── Field CRUD ──────────────────────────────────────────
function addField(type, label) {
    const id = 'f' + Date.now();
    const field = { id, type, label, required: false, placeholder: '', help_text: '', options: [], order: fields.length };

    // For multistep, assign to last step
    if (FORM_TYPE === 'multistep' && steps.length) {
        field.step_id = steps[steps.length - 1].id;
    }

    fields.push(field);
    selectedIdx = fields.length - 1;
    render();
    openConfig(selectedIdx);
}

function removeField(idx) {
    // Remove conditions referencing this field
    const fid = fields[idx].id;
    conditions = conditions.filter(c => c.field_id !== fid && c.target_field_id !== fid);
    fields.splice(idx, 1);
    if (selectedIdx === idx) { selectedIdx = null; closeConfig(); }
    else if (selectedIdx > idx) selectedIdx--;
    render();
}

function moveField(idx, dir) {
    const newIdx = idx + dir;
    if (newIdx < 0 || newIdx >= fields.length) return;
    [fields[idx], fields[newIdx]] = [fields[newIdx], fields[idx]];
    if (selectedIdx === idx) selectedIdx = newIdx;
    else if (selectedIdx === newIdx) selectedIdx = idx;
    render();
}

function selectField(idx) {
    selectedIdx = idx;
    render();
    openConfig(idx);
}

// ── Steps (multi-step) ─────────────────────────────────
function addStep() {
    const num = steps.length + 1;
    steps.push({ id: 's' + Date.now(), title: '{{ __("forms.step") }} ' + num });
    render();
}

function removeStep(si) {
    const stepId = steps[si].id;
    // Move fields to first step
    fields.forEach(f => { if (f.step_id === stepId) f.step_id = steps[0].id; });
    steps.splice(si, 1);
    render();
}

function moveStep(si, dir) {
    const newIdx = si + dir;
    if (newIdx < 0 || newIdx >= steps.length) return;
    [steps[si], steps[newIdx]] = [steps[newIdx], steps[si]];
    render();
}

// ── Conditional Logic ───────────────────────────────────
function getConditionForField(fieldId) {
    return conditions.find(c => c.target_field_id === fieldId);
}

function getInputFields() {
    return fields.filter(f => !['heading', 'divider'].includes(f.type));
}

function renderConditionConfig(fieldId, idx) {
    const cond = getConditionForField(fieldId) || null;
    const inputFields = getInputFields().filter(f => f.id !== fieldId);

    if (!inputFields.length) return '<div style="font-size:12px;color:#9ca3af;margin-top:12px;">{{ __("forms.no_fields_for_condition") }}</div>';

    let html = `<div style="margin-top:16px;padding-top:14px;border-top:1px solid #f0f2f7;">
        <label style="display:flex;align-items:center;gap:6px;margin-top:0;">
            <input type="checkbox" id="condToggle" ${cond ? 'checked' : ''} onchange="toggleCondition('${fieldId}', this.checked)">
            <i class="bi bi-lightning-charge-fill" style="color:#7c3aed;"></i>
            {{ __('forms.conditional_logic') }}
        </label>`;

    if (cond) {
        const sourceField = inputFields.find(f => f.id === cond.field_id);
        const sourceOptions = sourceField?.options || [];
        const operators = [
            { val: 'equals', label: '{{ __("forms.cond_equals") }}' },
            { val: 'not_equals', label: '{{ __("forms.cond_not_equals") }}' },
            { val: 'contains', label: '{{ __("forms.cond_contains") }}' },
            { val: 'not_empty', label: '{{ __("forms.cond_not_empty") }}' },
            { val: 'is_empty', label: '{{ __("forms.cond_is_empty") }}' },
        ];

        html += `<div style="margin-top:10px;background:#faf5ff;border:1px solid #e9d5ff;border-radius:10px;padding:12px;">
            <label style="margin-top:0;">{{ __('forms.cond_show_when') }}</label>
            <select class="form-control" style="font-size:12px;margin-bottom:6px;" onchange="updateCondition('${fieldId}','field_id',this.value);render();openConfig(${idx})">
                <option value="">{{ __('forms.cond_select_field') }}</option>
                ${inputFields.map(f => `<option value="${f.id}" ${cond.field_id === f.id ? 'selected' : ''}>${window.escapeHtml(f.label)}</option>`).join('')}
            </select>
            <select class="form-control" style="font-size:12px;margin-bottom:6px;" onchange="updateCondition('${fieldId}','operator',this.value)">
                ${operators.map(o => `<option value="${o.val}" ${cond.operator === o.val ? 'selected' : ''}>${o.label}</option>`).join('')}
            </select>`;

        // Value field - show options dropdown if source has options, otherwise text
        if (!['not_empty', 'is_empty'].includes(cond.operator)) {
            if (sourceOptions.length) {
                html += `<select class="form-control" style="font-size:12px;" onchange="updateCondition('${fieldId}','value',this.value)">
                    <option value="">—</option>
                    ${sourceOptions.map(o => `<option value="${o}" ${cond.value === o ? 'selected' : ''}>${window.escapeHtml(o)}</option>`).join('')}
                </select>`;
            } else {
                html += `<input type="text" class="form-control" style="font-size:12px;" value="${window.escapeHtml(cond.value || '')}" placeholder="{{ __('forms.cond_value_ph') }}" onchange="updateCondition('${fieldId}','value',this.value)">`;
            }
        }

        html += `</div>`;
    }

    html += `</div>`;
    return html;
}

function toggleCondition(fieldId, enabled) {
    conditions = conditions.filter(c => c.target_field_id !== fieldId);
    if (enabled) {
        conditions.push({ target_field_id: fieldId, field_id: '', operator: 'equals', value: '' });
    }
    render();
    if (selectedIdx !== null) openConfig(selectedIdx);
}

function updateCondition(targetFieldId, key, value) {
    const cond = conditions.find(c => c.target_field_id === targetFieldId);
    if (cond) cond[key] = value;
}

// ── Config panel ────────────────────────────────────────
function openConfig(idx) {
    const f = fields[idx];
    const panel = document.getElementById('fbConfig');
    const body = document.getElementById('fbConfigBody');
    const hasOptions = ['select', 'radio', 'checkbox', 'multiselect'].includes(f.type);

    let html = `
        <label>{{ __('forms.field_label') }}</label>
        <input type="text" class="form-control" value="${window.escapeHtml(f.label || '')}" onchange="fields[${idx}].label=this.value;render()">
        <label>{{ __('forms.field_placeholder') }}</label>
        <input type="text" class="form-control" value="${window.escapeHtml(f.placeholder || '')}" onchange="fields[${idx}].placeholder=this.value">
        <label>{{ __('forms.field_help') }}</label>
        <input type="text" class="form-control" value="${window.escapeHtml(f.help_text || '')}" onchange="fields[${idx}].help_text=this.value">
        <label style="margin-top:14px;">
            <input type="checkbox" ${f.required ? 'checked' : ''} onchange="fields[${idx}].required=this.checked;render()" style="margin-right:6px;">
            {{ __('forms.field_required') }}
        </label>
        ${hasOptions ? `<label style="margin-top:14px;">{{ __('forms.field_options') }}</label>
        <textarea class="form-control" rows="4" onchange="fields[${idx}].options=this.value.split('\\n').filter(Boolean);render()">${(f.options || []).join('\n')}</textarea>` : ''}`;

    // Step assignment for multistep
    if (FORM_TYPE === 'multistep' && steps.length) {
        html += `<label style="margin-top:14px;">{{ __('forms.assign_to_step') }}</label>
        <select class="form-control" onchange="fields[${idx}].step_id=this.value;render()">
            ${steps.map(s => `<option value="${s.id}" ${f.step_id === s.id ? 'selected' : ''}>${window.escapeHtml(s.title)}</option>`).join('')}
        </select>`;
    }

    // Conditional logic (skip for headings/dividers)
    if (!['heading', 'divider'].includes(f.type)) {
        html += renderConditionConfig(f.id, idx);
    }

    body.innerHTML = html;
    panel.classList.add('open');
}

function closeConfig() {
    document.getElementById('fbConfig').classList.remove('open');
}

// ── Save ────────────────────────────────────────────────
async function saveFields() {
    fields.forEach((f, i) => f.order = i);

    const payload = { fields };
    if (FORM_TYPE === 'multistep') payload.steps = steps;
    if (conditions.length) payload.conditional_logic = conditions;

    const res = await fetch('{{ route("forms.builder.save", $form) }}', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) toastr.success('{{ __("forms.fields_saved") }}');
    else toastr.error(data.message || 'Erro ao salvar');
}

// ── Init ────────────────────────────────────────────────
render();
</script>
@endpush
@endsection
