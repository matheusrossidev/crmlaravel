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
</style>
@endpush

@section('content')
<div class="page-container" style="padding:0;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-bottom:1px solid #e8eaf0;background:#fff;">
        <div style="display:flex;align-items:center;gap:12px;">
            <a href="{{ route('forms.edit', $form) }}" style="color:#6b7280;font-size:18px;"><i class="bi bi-arrow-left"></i></a>
            <h2 style="font-size:16px;font-weight:700;color:#1a1d23;margin:0;">{{ __('forms.builder_title', ['name' => $form->name]) }}</h2>
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
            <h3 style="font-size:13px;font-weight:700;color:#1a1d23;margin:0 0 12px;">{{ __('forms.add_field') }}</h3>
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

        {{-- Canvas --}}
        <div class="fb-canvas" id="fbCanvas">
            <div class="fb-empty" id="fbEmpty">
                <i class="bi bi-arrow-left" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                {{ __('forms.builder_subtitle') }}
            </div>
        </div>

        {{-- Config panel --}}
        <div class="fb-config" id="fbConfig">
            <h3 style="font-size:14px;font-weight:700;color:#1a1d23;margin:0 0 4px;">Configuração</h3>
            <div id="fbConfigBody"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let fields = {!! json_encode($form->fields ?? []) !!};
let selectedIdx = null;

function render() {
    const canvas = document.getElementById('fbCanvas');
    const empty = document.getElementById('fbEmpty');
    if (!fields.length) { canvas.innerHTML = ''; canvas.appendChild(empty); empty.style.display = ''; return; }
    empty.style.display = 'none';

    let html = '';
    fields.forEach((f, i) => {
        const req = f.required ? '<span class="fb-field-req">*</span>' : '';
        html += `<div class="fb-field-card" onclick="selectField(${i})" style="${selectedIdx === i ? 'border-color:#0085f3;box-shadow:0 0 0 3px rgba(0,133,243,.1);' : ''}">
            <span class="fb-drag"><i class="bi bi-grip-vertical"></i></span>
            <div class="fb-field-label">${window.escapeHtml(f.label || 'Campo')} ${req}</div>
            <div class="fb-field-type">${f.type}</div>
            <div class="fb-actions">
                <button onclick="event.stopPropagation();moveField(${i},-1)" title="Subir"><i class="bi bi-chevron-up"></i></button>
                <button onclick="event.stopPropagation();moveField(${i},1)" title="Descer"><i class="bi bi-chevron-down"></i></button>
                <button onclick="event.stopPropagation();removeField(${i})" title="Remover"><i class="bi bi-trash3"></i></button>
            </div>
        </div>`;
    });
    // Keep empty div in DOM but hidden
    canvas.innerHTML = html;
    canvas.appendChild(empty);
}

function addField(type, label) {
    const id = 'f' + Date.now();
    const field = { id, type, label, required: false, placeholder: '', help_text: '', options: [], order: fields.length };
    fields.push(field);
    selectedIdx = fields.length - 1;
    render();
    openConfig(selectedIdx);
}

function removeField(idx) {
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

function openConfig(idx) {
    const f = fields[idx];
    const panel = document.getElementById('fbConfig');
    const body = document.getElementById('fbConfigBody');
    const hasOptions = ['select', 'radio', 'checkbox', 'multiselect'].includes(f.type);

    body.innerHTML = `
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
        <textarea class="form-control" rows="4" onchange="fields[${idx}].options=this.value.split('\\n').filter(Boolean)">${(f.options || []).join('\n')}</textarea>` : ''}
    `;
    panel.classList.add('open');
}

function closeConfig() {
    document.getElementById('fbConfig').classList.remove('open');
}

async function saveFields() {
    fields.forEach((f, i) => f.order = i);
    const res = await fetch('{{ route("forms.builder.save", $form) }}', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ fields }),
    });
    const data = await res.json();
    if (data.success) toastr.success('Campos salvos!');
    else toastr.error(data.message || 'Erro ao salvar');
}

render();
</script>
@endpush
@endsection
