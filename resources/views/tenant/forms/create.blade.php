@extends('tenant.layouts.app')

@php
    $title = __('forms.create_title');
    $pageIcon = 'bi-plus-lg';
@endphp

@section('content')
<div class="page-container" style="max-width:680px;margin:0 auto;">
    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">CRM</div>
        <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0;">{{ __('forms.create_title') }}</h1>
    </div>

    <div style="background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;padding:28px;">
        <div style="margin-bottom:18px;">
            <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('forms.form_name') }}</label>
            <input type="text" id="formName" class="form-control" placeholder="{{ __('forms.form_name_ph') }}" style="font-size:13px;">
        </div>

        <div style="margin-bottom:18px;">
            <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('forms.form_type') }}</label>
            <select id="formType" class="form-control" style="font-size:13px;">
                <option value="classic">{{ __('forms.type_classic') }}</option>
                <option value="conversational" disabled>{{ __('forms.type_conversational') }} (Fase 2)</option>
                <option value="multistep" disabled>{{ __('forms.type_multistep') }} (Fase 2)</option>
            </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
            <div>
                <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('forms.pipeline') }}</label>
                <select id="formPipeline" class="form-control" style="font-size:13px;" onchange="updateStages()">
                    <option value="">—</option>
                    @foreach($pipelines as $p)
                        <option value="{{ $p->id }}" data-stages="{{ $p->stages->toJson() }}">{{ $p->name }}</option>
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
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        <div style="margin-bottom:18px;">
            <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('forms.source_utm') }}</label>
            <input type="text" id="formSource" class="form-control" placeholder="{{ __('forms.source_utm_ph') }}" style="font-size:13px;">
        </div>

        <div style="margin-bottom:18px;">
            <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('forms.confirmation') }}</label>
            <select id="formConfType" class="form-control" style="font-size:13px;margin-bottom:8px;">
                <option value="message">{{ __('forms.confirmation_message') }}</option>
                <option value="redirect">{{ __('forms.confirmation_redirect') }}</option>
            </select>
            <input type="text" id="formConfValue" class="form-control" placeholder="{{ __('forms.confirmation_value_ph') }}" style="font-size:13px;">
        </div>

        <div style="margin-bottom:18px;">
            <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('forms.notify_emails') }}</label>
            <input type="text" id="formNotify" class="form-control" placeholder="{{ __('forms.notify_emails_ph') }}" style="font-size:13px;">
        </div>

        {{-- Branding --}}
        <div style="border-top:1px solid #f0f2f7;margin-top:24px;padding-top:20px;">
            <h3 style="font-size:14px;font-weight:700;color:#1a1d23;margin:0 0 16px;"><i class="bi bi-palette" style="margin-right:6px;"></i> Personalização visual</h3>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor primária</label>
                    <input type="color" id="brandColor" value="#0085f3" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor de fundo</label>
                    <input type="color" id="bgColor" value="#ffffff" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor do card</label>
                    <input type="color" id="cardColor" value="#ffffff" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor do botão</label>
                    <input type="color" id="buttonColor" value="#0085f3" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor texto do botão</label>
                    <input type="color" id="buttonTextColor" value="#ffffff" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor das labels</label>
                    <input type="color" id="labelColor" value="#374151" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor borda dos campos</label>
                    <input type="color" id="inputBorderColor" value="#e5e7eb" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Fundo dos campos</label>
                    <input type="color" id="inputBgColor" value="#ffffff" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Cor texto dos campos</label>
                    <input type="color" id="inputTextColor" value="#1a1d23" style="width:100%;height:38px;border:1.5px solid #e8eaf0;border-radius:8px;padding:2px;cursor:pointer;">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Fonte</label>
                    <select id="fontFamily" class="form-control" style="font-size:13px;">
                        @foreach(['Inter', 'Plus Jakarta Sans', 'Poppins', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Georgia', 'Courier New'] as $font)
                            <option value="{{ $font }}">{{ $font }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Border radius (px)</label>
                    <input type="range" id="borderRadius" min="0" max="20" value="8" style="width:100%;margin-top:8px;">
                    <div style="font-size:11px;color:#9ca3af;text-align:center;" id="radiusLabel">8px</div>
                </div>
            </div>

            <div style="margin-top:14px;">
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:8px;">Logomarca</label>
                <div id="logoPreview" style="display:none;margin-bottom:12px;text-align:center;">
                    <img id="logoImg" src="" style="max-height:60px;border-radius:10px;">
                    <button type="button" onclick="removeLogo()" style="display:block;margin:8px auto 0;font-size:11px;color:#dc2626;background:none;border:none;cursor:pointer;"><i class="bi bi-trash3"></i> Remover</button>
                </div>
                <div id="logoDropzone" onclick="document.getElementById('logoFile').click()" style="border:2px dashed #bfdbfe;border-radius:12px;padding:24px;text-align:center;cursor:pointer;background:#f8fafc;transition:border-color .15s;" onmouseover="this.style.borderColor='#0085f3'" onmouseout="this.style.borderColor='#bfdbfe'">
                    <i class="bi bi-cloud-arrow-up" style="font-size:24px;color:#0085f3;display:block;margin-bottom:6px;"></i>
                    <div style="font-size:13px;font-weight:600;color:#374151;">Clique para enviar o logo</div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:2px;">JPG, PNG ou WebP · máx. 2MB</div>
                </div>
                <input type="file" id="logoFile" accept="image/png,image/jpeg,image/svg+xml,image/webp" style="display:none;" onchange="previewLogo(this)">
                <input type="hidden" id="logoUrl" value="">
            </div>

            <div style="margin-top:10px;">
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Alinhamento da logo</label>
                <select id="logoAlignment" class="form-control" style="font-size:13px;">
                    <option value="left">Esquerda</option>
                    <option value="center" selected>Centro</option>
                    <option value="right">Direita</option>
                </select>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:24px;">
            <a href="{{ route('forms.index') }}" style="padding:10px 20px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;">{{ __('common.cancel') }}</a>
            <button onclick="saveForm()" style="padding:10px 20px;background:#0085f3;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                {{ __('forms.next_builder') }} <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function updateStages() {
    const sel = document.getElementById('formPipeline');
    const opt = sel.options[sel.selectedIndex];
    const stages = opt.dataset.stages ? JSON.parse(opt.dataset.stages) : [];
    const s = document.getElementById('formStage');
    s.innerHTML = stages.map(st => `<option value="${st.id}">${st.name}</option>`).join('');
}

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
    }
}

function removeLogo() {
    document.getElementById('logoFile').value = '';
    document.getElementById('logoUrl').value = '';
    document.getElementById('logoPreview').style.display = 'none';
    document.getElementById('logoDropzone').style.display = 'block';
}

async function saveForm() {
    const notify = document.getElementById('formNotify').value.trim();
    const emails = notify ? notify.split(',').map(e => e.trim()).filter(Boolean) : [];

    const res = await fetch('{{ route("forms.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({
            name: document.getElementById('formName').value,
            type: document.getElementById('formType').value,
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
    if (data.success && data.redirect) {
        // Upload logo if file selected
        const logoFile = document.getElementById('logoFile').files[0];
        if (logoFile && data.form?.id) {
            const fd = new FormData();
            fd.append('logo', logoFile);
            await fetch(`{{ url('formularios') }}/${data.form.id}/upload-logo`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: fd,
            });
        }
        window.location.href = data.redirect;
    } else if (data.errors) {
        const first = Object.values(data.errors)[0];
        toastr.error(first[0] || 'Erro de validação');
    } else {
        toastr.error(data.message || 'Erro ao criar formulário');
    }
}
</script>
@endpush
@endsection
