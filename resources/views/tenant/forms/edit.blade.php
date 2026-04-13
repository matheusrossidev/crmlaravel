@extends('tenant.layouts.app')

@php
    $title = __('forms.edit_title');
    $pageIcon = 'bi-pencil';
@endphp

@section('content')
<div class="page-container" style="max-width:680px;margin:0 auto;">
    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">CRM</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0;">{{ __('forms.edit_title') }}: {{ $form->name }}</h1>
            <div style="display:flex;gap:8px;">
                <a href="{{ route('forms.builder', $form) }}" class="btn-primary-sm" style="background:#eff6ff;color:#0085f3;border:1.5px solid #bfdbfe;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    <i class="bi bi-grid-3x3-gap"></i> Builder
                </a>
                <a href="{{ route('forms.mapping', $form) }}" class="btn-primary-sm" style="background:#eff6ff;color:#0085f3;border:1.5px solid #bfdbfe;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    <i class="bi bi-arrow-left-right"></i> Mapeamento
                </a>
            </div>
        </div>
    </div>

    <div style="background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;padding:28px;">
        {{-- Same form as create but with values pre-filled --}}
        <div style="margin-bottom:18px;">
            <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('forms.form_name') }}</label>
            <input type="text" id="formName" class="form-control" value="{{ $form->name }}" style="font-size:13px;">
        </div>

        <div style="margin-bottom:18px;">
            <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">Slug (URL)</label>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:12px;color:#6b7280;">{{ rtrim(config('app.url'), '/') }}/f/</span>
                <input type="text" id="formSlug" class="form-control" value="{{ $form->slug }}" style="font-size:13px;flex:1;">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
            <div>
                <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('forms.pipeline') }}</label>
                <select id="formPipeline" class="form-control" style="font-size:13px;" onchange="updateStages()">
                    <option value="">—</option>
                    @foreach($pipelines as $p)
                        <option value="{{ $p->id }}" {{ $form->pipeline_id == $p->id ? 'selected' : '' }} data-stages="{{ $p->stages->toJson() }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('forms.stage') }}</label>
                <select id="formStage" class="form-control" style="font-size:13px;"></select>
            </div>
        </div>

        <div style="margin-bottom:18px;">
            <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('forms.assigned_to') }}</label>
            <select id="formAssigned" class="form-control" style="font-size:13px;">
                <option value="">{{ __('forms.no_assignment') }}</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ $form->assigned_user_id == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        <div style="margin-bottom:18px;">
            <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('forms.source_utm') }}</label>
            <input type="text" id="formSource" class="form-control" value="{{ $form->source_utm }}" placeholder="{{ __('forms.source_utm_ph') }}" style="font-size:13px;">
        </div>

        <div style="margin-bottom:18px;">
            <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('forms.confirmation') }}</label>
            <select id="formConfType" class="form-control" style="font-size:13px;margin-bottom:8px;">
                <option value="message" {{ $form->confirmation_type === 'message' ? 'selected' : '' }}>{{ __('forms.confirmation_message') }}</option>
                <option value="redirect" {{ $form->confirmation_type === 'redirect' ? 'selected' : '' }}>{{ __('forms.confirmation_redirect') }}</option>
            </select>
            <input type="text" id="formConfValue" class="form-control" value="{{ $form->confirmation_value }}" style="font-size:13px;">
        </div>

        <div style="margin-bottom:18px;">
            <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('forms.notify_emails') }}</label>
            <input type="text" id="formNotify" class="form-control" value="{{ implode(', ', $form->notify_emails ?? []) }}" placeholder="{{ __('forms.notify_emails_ph') }}" style="font-size:13px;">
        </div>

        {{-- Branding --}}
        <div style="border-top:1px solid #f0f2f7;margin-top:24px;padding-top:20px;">
            <h3 style="font-size:14px;font-weight:700;color:#1a1d23;margin:0 0 16px;"><i class="bi bi-palette" style="margin-right:6px;"></i> Personalização visual</h3>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor primária</label>
                    <input type="color" id="brandColor" value="{{ $form->brand_color ?? '#0085f3' }}" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor de fundo</label>
                    <input type="color" id="bgColor" value="{{ $form->background_color ?? '#ffffff' }}" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor do card</label>
                    <input type="color" id="cardColor" value="{{ $form->card_color ?? '#ffffff' }}" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor do botão</label>
                    <input type="color" id="buttonColor" value="{{ $form->button_color ?? '#0085f3' }}" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor texto do botão</label>
                    <input type="color" id="buttonTextColor" value="{{ $form->button_text_color ?? '#ffffff' }}" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor das labels</label>
                    <input type="color" id="labelColor" value="{{ $form->label_color ?? '#374151' }}" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor borda dos campos</label>
                    <input type="color" id="inputBorderColor" value="{{ $form->input_border_color ?? '#e5e7eb' }}" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Fundo dos campos</label>
                    <input type="color" id="inputBgColor" value="{{ $form->input_bg_color ?? '#ffffff' }}" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor texto dos campos</label>
                    <input type="color" id="inputTextColor" value="{{ $form->input_text_color ?? '#1a1d23' }}" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Fonte</label>
                    <select id="fontFamily" class="form-control" style="font-size:13px;">
                        @foreach(['Inter', 'Plus Jakarta Sans', 'Poppins', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Georgia', 'Courier New'] as $font)
                            <option value="{{ $font }}" {{ ($form->font_family ?? 'Inter') === $font ? 'selected' : '' }}>{{ $font }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Border radius (px)</label>
                    <input type="range" id="borderRadius" min="0" max="20" value="{{ $form->border_radius ?? 8 }}" style="width:100%;margin-top:8px;">
                    <div style="font-size:11px;color:#9ca3af;text-align:center;" id="radiusLabel">{{ $form->border_radius ?? 8 }}px</div>
                </div>
            </div>

            <div style="margin-top:14px;">
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:8px;">Logomarca</label>
                <div id="logoPreview" style="{{ $form->logo_url ? '' : 'display:none;' }}margin-bottom:12px;text-align:center;">
                    <img id="logoImg" src="{{ $form->logo_url }}" style="max-height:60px;border-radius:10px;">
                    <button type="button" onclick="removeLogo()" style="display:block;margin:8px auto 0;font-size:11px;color:#dc2626;background:none;border:none;cursor:pointer;"><i class="bi bi-trash3"></i> Remover</button>
                </div>
                <div id="logoDropzone" onclick="document.getElementById('logoFile').click()" style="{{ $form->logo_url ? 'display:none;' : '' }}border:2px dashed #bfdbfe;border-radius:12px;padding:24px;text-align:center;cursor:pointer;background:#f8fafc;transition:border-color .15s;" onmouseover="this.style.borderColor='#0085f3'" onmouseout="this.style.borderColor='#bfdbfe'">
                    <i class="bi bi-cloud-arrow-up" style="font-size:24px;color:#0085f3;display:block;margin-bottom:6px;"></i>
                    <div style="font-size:13px;font-weight:600;color:#374151;">Clique para enviar o logo</div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:2px;">JPG, PNG ou WebP · máx. 2MB</div>
                </div>
                <input type="file" id="logoFile" accept="image/png,image/jpeg,image/svg+xml,image/webp" style="display:none;" onchange="previewLogo(this)">
                <input type="hidden" id="logoUrl" value="{{ $form->logo_url }}">
            </div>

            <div style="margin-top:10px;">
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Alinhamento da logo</label>
                <select id="logoAlignment" class="form-control" style="font-size:13px;">
                    <option value="left" {{ ($form->logo_alignment ?? 'center') === 'left' ? 'selected' : '' }}>Esquerda</option>
                    <option value="center" {{ ($form->logo_alignment ?? 'center') === 'center' ? 'selected' : '' }}>Centro</option>
                    <option value="right" {{ ($form->logo_alignment ?? 'center') === 'right' ? 'selected' : '' }}>Direita</option>
                </select>
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;margin-top:24px;">
            <button onclick="deleteForm()" style="padding:10px 20px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="bi bi-trash3"></i> {{ __('common.delete') }}
            </button>
            <button onclick="updateForm()" style="padding:10px 20px;background:#0085f3;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="bi bi-check-lg"></i> {{ __('forms.save') }}
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const FORM_ID = {{ $form->id }};
const CURRENT_STAGE = {{ $form->stage_id ?? 'null' }};

function updateStages() {
    const sel = document.getElementById('formPipeline');
    const opt = sel.options[sel.selectedIndex];
    const stages = opt.dataset.stages ? JSON.parse(opt.dataset.stages) : [];
    const s = document.getElementById('formStage');
    s.innerHTML = stages.map(st => `<option value="${st.id}" ${st.id == CURRENT_STAGE ? 'selected' : ''}>${st.name}</option>`).join('');
}
updateStages();

document.getElementById('borderRadius')?.addEventListener('input', function() {
    document.getElementById('radiusLabel').textContent = this.value + 'px';
});

function previewLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('logoImg').src = e.target.result;
            document.getElementById('logoPreview').style.display = 'block';
            document.getElementById('logoDropzone').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
        // Auto upload
        uploadLogo();
    }
}

function removeLogo() {
    document.getElementById('logoFile').value = '';
    document.getElementById('logoUrl').value = '';
    document.getElementById('logoPreview').style.display = 'none';
    document.getElementById('logoDropzone').style.display = 'block';
}

async function uploadLogo() {
    const file = document.getElementById('logoFile').files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('logo', file);
    const res = await fetch('{{ route("forms.upload-logo", $form) }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: fd,
    });
    const data = await res.json();
    if (data.success) {
        document.getElementById('logoUrl').value = data.logo_url;
        toastr.success('Logo enviada!');
    }
}

async function updateForm() {
    const notify = document.getElementById('formNotify').value.trim();
    const emails = notify ? notify.split(',').map(e => e.trim()).filter(Boolean) : [];

    const res = await fetch('{{ route("forms.update", $form) }}', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({
            name: document.getElementById('formName').value,
            slug: document.getElementById('formSlug').value,
            pipeline_id: document.getElementById('formPipeline').value || null,
            stage_id: document.getElementById('formStage').value || null,
            assigned_user_id: document.getElementById('formAssigned').value || null,
            source_utm: document.getElementById('formSource').value || null,
            confirmation_type: document.getElementById('formConfType').value,
            confirmation_value: document.getElementById('formConfValue').value || null,
            notify_emails: emails.length ? emails : null,
            brand_color: document.getElementById('brandColor').value,
            background_color: document.getElementById('bgColor').value,
            card_color: document.getElementById('cardColor').value,
            button_color: document.getElementById('buttonColor').value,
            button_text_color: document.getElementById('buttonTextColor').value,
            label_color: document.getElementById('labelColor').value,
            input_border_color: document.getElementById('inputBorderColor').value,
            input_bg_color: document.getElementById('inputBgColor').value,
            input_text_color: document.getElementById('inputTextColor').value,
            font_family: document.getElementById('fontFamily').value,
            border_radius: parseInt(document.getElementById('borderRadius').value),
            logo_url: document.getElementById('logoUrl').value || null,
            logo_alignment: document.getElementById('logoAlignment').value,
        }),
    });
    const data = await res.json();
    if (data.success) toastr.success('Salvo!');
    else toastr.error(data.message || 'Erro');
}

async function deleteForm() {
    if (!confirm('Tem certeza que deseja excluir este formulário?')) return;
    const res = await fetch('{{ route("forms.destroy", $form) }}', {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
    });
    const data = await res.json();
    if (data.success) window.location.href = '{{ route("forms.index") }}';
    else toastr.error('Erro ao excluir');
}
</script>
@endpush
@endsection
