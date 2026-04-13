@extends('tenant.layouts.app')

@php
    $title = __('forms.mapping_title', ['name' => $form->name]);
    $pageIcon = 'bi-arrow-left-right';
@endphp

@section('content')
<div class="page-container" style="max-width:750px;margin:0 auto;">
    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">CRM</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('forms.mapping_title', ['name' => $form->name]) }}</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">{{ __('forms.mapping_subtitle') }}</p>
            </div>
            <a href="{{ route('forms.builder', $form) }}" style="color:#6b7280;font-size:13px;text-decoration:none;"><i class="bi bi-arrow-left"></i> Builder</a>
    </div>

    <div style="background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;padding:24px;">
        @php
            $fields = $form->fields ?? [];
            $mappings = $form->mappings ?? [];
            $destinations = [
                '' => __('forms.dest_none'),
                'name' => __('forms.dest_name'),
                'phone' => __('forms.dest_phone'),
                'email' => __('forms.dest_email'),
                'company' => __('forms.dest_company'),
                'value' => __('forms.dest_value'),
                'source' => __('forms.dest_source'),
                'tags' => __('forms.dest_tags'),
                'notes' => __('forms.dest_notes'),
            ];
        @endphp

        <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:10px;align-items:center;font-size:12px;font-weight:700;color:#6b7280;margin-bottom:12px;padding:0 4px;">
            <div>{{ __('forms.form_field') }}</div>
            <div></div>
            <div>{{ __('forms.crm_destination') }}</div>
        </div>

        @foreach($fields as $field)
            @if(in_array($field['type'] ?? '', ['heading', 'divider']))
                @continue
            @endif
            <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:10px;align-items:center;margin-bottom:8px;">
                <div style="padding:10px 14px;background:#f9fafb;border:1px solid #e8eaf0;border-radius:8px;font-size:13px;color:#1a1d23;">
                    {{ $field['label'] ?? $field['id'] }}
                    <span style="font-size:11px;color:#9ca3af;margin-left:4px;">({{ $field['type'] }})</span>
                </div>
                <div style="color:#9ca3af;"><i class="bi bi-arrow-right"></i></div>
                <select class="form-control mapping-select" data-field-id="{{ $field['id'] }}" style="font-size:13px;">
                    @foreach($destinations as $val => $label)
                        <option value="{{ $val }}" {{ ($mappings[$field['id']] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                    @foreach($customFields as $cf)
                        <option value="custom:{{ $cf->id }}" {{ ($mappings[$field['id']] ?? '') === 'custom:'.$cf->id ? 'selected' : '' }}>
                            {{ $cf->label ?? $cf->name }} ({{ $cf->field_type }})
                        </option>
                    @endforeach
                </select>
            </div>
        @endforeach

        <div style="display:flex;justify-content:flex-end;margin-top:20px;gap:8px;">
            <a href="{{ $form->getPublicUrl() }}" target="_blank" style="padding:10px 20px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;">
                <i class="bi bi-eye"></i> Preview
            </a>
            <button onclick="saveMapping()" style="padding:10px 20px;background:#0085f3;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="bi bi-check-lg"></i> {{ __('forms.save_mapping') }}
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
async function saveMapping() {
    const mappings = {};
    document.querySelectorAll('.mapping-select').forEach(sel => {
        if (sel.value) mappings[sel.dataset.fieldId] = sel.value;
    });

    const res = await fetch('{{ route("forms.mapping.save", $form) }}', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ mappings }),
    });
    const data = await res.json();
    if (data.success) toastr.success('Mapeamento salvo!');
    else toastr.error('Erro ao salvar');
}
</script>
@endpush
@endsection
