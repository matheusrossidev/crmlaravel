<!DOCTYPE html>
<html lang="{{ $form->tenant?->locale ?? 'pt_BR' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $form->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family={{ urlencode($form->font_family ?? 'Inter') }}:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: '{{ $form->font_family ?? 'Inter' }}', sans-serif;
            background: {{ $form->background_color ?? '#ffffff' }};
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .form-wrap {
            width: 100%;
            max-width: 560px;
        }
        .form-logo {
            text-align: {{ $form->logo_alignment ?? 'center' }};
            margin-bottom: 24px;
        }
        .form-logo img { max-width: 200px; height: auto; }
        .form-card {
            background: {{ $form->card_color ?? '#ffffff' }};
            border-radius: {{ ($form->border_radius ?? 8) + 4 }}px;
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
            padding: 32px;
        }
        .form-title {
            font-size: 22px;
            font-weight: 700;
            color: {{ $form->label_color ?? '#1a1d23' }};
            margin-bottom: 24px;
            text-align: center;
        }
        .field-group { margin-bottom: 18px; }
        .field-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: {{ $form->label_color ?? '#374151' }};
            margin-bottom: 6px;
        }
        .field-label .req { color: #dc2626; }
        .field-help {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 4px;
        }
        .field-input {
            width: 100%;
            padding: 11px 14px;
            font-size: 14px;
            font-family: inherit;
            color: {{ $form->input_text_color ?? '#1a1d23' }};
            background: {{ $form->input_bg_color ?? '#ffffff' }};
            border: 1.5px solid {{ $form->input_border_color ?? '#e5e7eb' }};
            border-radius: {{ $form->border_radius ?? 8 }}px;
            outline: none;
            transition: border-color .15s;
        }
        .field-input:focus { border-color: {{ $form->brand_color ?? '#0085f3' }}; }
        textarea.field-input { resize: vertical; min-height: 80px; }
        select.field-input { cursor: pointer; }
        .field-error { font-size: 12px; color: #dc2626; margin-top: 4px; display: none; }
        .btn-submit {
            display: block;
            width: 100%;
            padding: 13px;
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            color: {{ $form->button_text_color ?? '#ffffff' }};
            background: {{ $form->button_color ?? '#0085f3' }};
            border: none;
            border-radius: {{ $form->border_radius ?? 8 }}px;
            cursor: pointer;
            margin-top: 24px;
            transition: opacity .15s;
        }
        .btn-submit:hover { opacity: .9; }
        .btn-submit:disabled { opacity: .5; cursor: not-allowed; }
        .form-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
            color: #d1d5db;
        }
        .form-footer a { color: #d1d5db; text-decoration: none; }
        .form-divider { border: none; border-top: 1px solid #e8eaf0; margin: 20px 0; }
        .form-heading { font-size: 16px; font-weight: 700; color: {{ $form->label_color ?? '#1a1d23' }}; margin-bottom: 8px; }
        .honeypot { position: absolute; left: -9999px; }
        .checkbox-group label, .radio-group label { display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;margin-bottom:6px;cursor:pointer; }
        .alert-error { background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px;display:none; }
    </style>
</head>
<body>
    <div class="form-wrap">
        @if($form->logo_url)
        <div class="form-logo">
            <img src="{{ $form->logo_url }}" alt="{{ $form->name }}">
        </div>
        @endif

        <div class="form-card">
            <h1 class="form-title">{{ $form->name }}</h1>

            <div class="alert-error" id="formAlert"></div>

            <form id="publicForm" onsubmit="submitForm(event)">
                {{-- Honeypot --}}
                <input type="text" name="_website_url" class="honeypot" tabindex="-1" autocomplete="off">

                @foreach($form->fields ?? [] as $field)
                    @php
                        $fid = $field['id'];
                        $type = $field['type'];
                        $label = $field['label'] ?? '';
                        $ph = $field['placeholder'] ?? '';
                        $req = $field['required'] ?? false;
                        $help = $field['help_text'] ?? '';
                        $options = $field['options'] ?? [];
                    @endphp

                    @if($type === 'divider')
                        <hr class="form-divider">
                        @continue
                    @endif

                    @if($type === 'heading')
                        <div class="form-heading">{{ $label }}</div>
                        @continue
                    @endif

                    <div class="field-group">
                        <label class="field-label" for="field_{{ $fid }}">
                            {{ $label }} @if($req)<span class="req">*</span>@endif
                        </label>

                        @if($type === 'textarea')
                            <textarea class="field-input" id="field_{{ $fid }}" name="{{ $fid }}" placeholder="{{ $ph }}" {{ $req ? 'required' : '' }}></textarea>
                        @elseif($type === 'select')
                            <select class="field-input" id="field_{{ $fid }}" name="{{ $fid }}" {{ $req ? 'required' : '' }}>
                                <option value="">{{ $ph ?: '—' }}</option>
                                @foreach($options as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                        @elseif($type === 'checkbox')
                            <div class="checkbox-group">
                                @foreach($options as $opt)
                                    <label><input type="checkbox" name="{{ $fid }}[]" value="{{ $opt }}"> {{ $opt }}</label>
                                @endforeach
                                @if(empty($options))
                                    <label><input type="checkbox" name="{{ $fid }}" value="1"> {{ $label }}</label>
                                @endif
                            </div>
                        @elseif($type === 'radio')
                            <div class="radio-group">
                                @foreach($options as $opt)
                                    <label><input type="radio" name="{{ $fid }}" value="{{ $opt }}" {{ $req ? 'required' : '' }}> {{ $opt }}</label>
                                @endforeach
                            </div>
                        @elseif($type === 'file')
                            <input type="file" class="field-input" id="field_{{ $fid }}" name="{{ $fid }}" {{ $req ? 'required' : '' }} style="padding:8px;">
                        @else
                            <input type="{{ $type === 'tel' ? 'tel' : ($type === 'email' ? 'email' : ($type === 'number' ? 'number' : 'text')) }}"
                                   class="field-input" id="field_{{ $fid }}" name="{{ $fid }}"
                                   placeholder="{{ $ph }}" {{ $req ? 'required' : '' }}>
                        @endif

                        @if($help)
                            <div class="field-help">{{ $help }}</div>
                        @endif
                        <div class="field-error" id="err_{{ $fid }}"></div>
                    </div>
                @endforeach

                <button type="submit" class="btn-submit" id="btnSubmit">
                    {{ __('forms.submit_button') }}
                </button>
            </form>
        </div>

        <div class="form-footer">
            <a href="https://syncro.chat" target="_blank" style="display:inline-flex;align-items:center;gap:6px;">
                <span style="font-size:11px;color:#c0c5cf;">Criado com</span>
                <img src="{{ asset('images/logo.png') }}" alt="Syncro" style="height:16px;opacity:.35;filter:grayscale(1);" onerror="this.outerHTML='<span style=\'font-size:11px;font-weight:600;color:#c0c5cf;\'>Syncro</span>'">
            </a>
        </div>
    </div>

    <script>
    async function submitForm(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmit');
        const alert = document.getElementById('formAlert');
        btn.disabled = true;
        btn.textContent = '...';
        alert.style.display = 'none';

        // Collect data
        const formData = {};
        const form = document.getElementById('publicForm');
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(el => {
            if (el.name === '_website_url') { formData['_website_url'] = el.value; return; }
            if (!el.name) return;
            if (el.type === 'checkbox') {
                if (!formData[el.name.replace('[]', '')]) formData[el.name.replace('[]', '')] = [];
                if (el.checked) formData[el.name.replace('[]', '')].push(el.value);
            } else if (el.type === 'radio') {
                if (el.checked) formData[el.name] = el.value;
            } else {
                formData[el.name] = el.value;
            }
        });

        // Capturar UTMs da URL
        const urlParams = new URLSearchParams(window.location.search);
        ['utm_source','utm_medium','utm_campaign','utm_term','utm_content','fbclid','gclid'].forEach(key => {
            const val = urlParams.get(key);
            if (val) formData['_' + key] = val;
        });

        try {
            const res = await fetch(window.location.pathname, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify(formData),
            });
            const data = await res.json();

            if (data.success) {
                if (data.confirmation_type === 'redirect' && data.confirmation_value) {
                    let url = data.confirmation_value;
                    if (url && !url.match(/^https?:\/\//i)) url = 'https://' + url;
                    window.location.href = url;
                } else {
                    document.querySelector('.form-card').innerHTML = `
                        <div style="text-align:center;padding:40px 20px;">
                            <div style="width:56px;height:56px;border-radius:50%;background:#ecfdf5;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                                <svg width="24" height="24" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </div>
                            <h2 style="font-size:18px;font-weight:700;color:#1a1d23;margin-bottom:8px;">{{ __('forms.default_thanks') }}</h2>
                            <p style="font-size:14px;color:#6b7280;">${data.confirmation_value || ''}</p>
                        </div>`;
                }
            } else if (data.errors) {
                // Show validation errors
                Object.entries(data.errors).forEach(([field, msgs]) => {
                    const errEl = document.getElementById('err_' + field);
                    if (errEl) { errEl.textContent = msgs[0]; errEl.style.display = 'block'; }
                });
                btn.disabled = false;
                btn.textContent = '{{ __("forms.submit_button") }}';
            } else {
                alert.textContent = data.message || 'Erro ao enviar';
                alert.style.display = 'block';
                btn.disabled = false;
                btn.textContent = '{{ __("forms.submit_button") }}';
            }
        } catch (err) {
            alert.textContent = 'Erro de conexão. Tente novamente.';
            alert.style.display = 'block';
            btn.disabled = false;
            btn.textContent = '{{ __("forms.submit_button") }}';
        }
    }
    </script>
</body>
</html>
