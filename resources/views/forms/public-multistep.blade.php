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
        * { margin:0;padding:0;box-sizing:border-box; }
        body {
            font-family:'{{ $form->font_family ?? 'Inter' }}',sans-serif;
            background:{{ $form->background_color ?? '#ffffff' }};
            @if(($form->enable_background_image ?? false) && $form->background_image_url)
            background-image: url('{{ $form->background_image_url }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            @endif
            min-height:100vh;display:flex;align-items:center;
            @php
                $msJustify = match($form->layout ?? 'centered') { 'left' => 'flex-start', 'right' => 'flex-end', default => 'center' };
                $msPad = match($form->layout ?? 'centered') { 'left' => '10%', 'right' => '10%', default => '24px' };
            @endphp
            justify-content:{{ $msJustify }};padding:24px {{ $msPad }};
        }
        .ms-wrap { width:100%;max-width:600px; }
        .ms-logo { text-align:{{ $form->logo_alignment ?? 'center' }};margin-bottom:24px; }
        .ms-logo img { max-width:200px;height:auto; }

        .ms-card {
            background:{{ $form->card_color ?? '#ffffff' }};
            border-radius:{{ ($form->border_radius ?? 8) + 4 }}px;
            box-shadow:0 4px 24px rgba(0,0,0,.06);
            padding:32px;
        }
        .ms-title { font-size:22px;font-weight:700;color:{{ $form->label_color ?? '#1a1d23' }};margin-bottom:8px;text-align:center; }

        /* Progress steps */
        .ms-progress { display:flex;align-items:center;justify-content:center;gap:0;margin-bottom:28px;padding:0 16px; }
        .ms-step-dot {
            width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;
            font-size:13px;font-weight:700;flex-shrink:0;transition:.3s;
            border:2px solid #e5e7eb;color:#9ca3af;background:#fff;
        }
        .ms-step-dot.active { border-color:{{ $form->brand_color ?? '#0085f3' }};color:{{ $form->brand_color ?? '#0085f3' }};background:{{ $form->brand_color ?? '#0085f3' }}10; }
        .ms-step-dot.done { border-color:{{ $form->brand_color ?? '#0085f3' }};background:{{ $form->brand_color ?? '#0085f3' }};color:#fff; }
        .ms-step-line { flex:1;height:2px;background:#e5e7eb;transition:.3s;margin:0 4px; }
        .ms-step-line.done { background:{{ $form->brand_color ?? '#0085f3' }}; }

        .ms-step-title { text-align:center;font-size:14px;font-weight:600;color:{{ $form->label_color ?? '#1a1d23' }};margin-bottom:20px; }

        /* Fields */
        .ms-page { display:none; }
        .ms-page.active { display:block;animation:fadeIn .3s ease; }
        .field-group { margin-bottom:18px; }
        .field-label { display:block;font-size:13px;font-weight:600;color:{{ $form->label_color ?? '#374151' }};margin-bottom:6px; }
        .field-label .req { color:#dc2626; }
        .field-help { font-size:11px;color:#9ca3af;margin-top:4px; }
        .field-input {
            width:100%;padding:11px 14px;font-size:14px;font-family:inherit;
            color:{{ $form->input_text_color ?? '#1a1d23' }};
            background:{{ $form->input_bg_color ?? '#ffffff' }};
            border:1.5px solid {{ $form->input_border_color ?? '#e5e7eb' }};
            border-radius:{{ $form->border_radius ?? 8 }}px;
            outline:none;transition:border-color .15s;
        }
        .field-input:focus { border-color:{{ $form->brand_color ?? '#0085f3' }}; }
        textarea.field-input { resize:vertical;min-height:80px; }
        select.field-input { cursor:pointer; }
        .field-error { font-size:12px;color:#dc2626;margin-top:4px;display:none; }
        .checkbox-group label,.radio-group label { display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;margin-bottom:6px;cursor:pointer; }
        .form-divider { border:none;border-top:1px solid #e8eaf0;margin:20px 0; }
        .form-heading { font-size:16px;font-weight:700;color:{{ $form->label_color ?? '#1a1d23' }};margin-bottom:8px; }

        /* Navigation */
        .ms-nav { display:flex;justify-content:space-between;align-items:center;margin-top:24px;gap:12px; }
        .ms-btn {
            padding:12px 24px;font-size:14px;font-weight:600;font-family:inherit;border:none;
            border-radius:{{ $form->border_radius ?? 8 }}px;cursor:pointer;transition:.2s;
            display:flex;align-items:center;gap:6px;
        }
        .ms-btn-primary { background:{{ $form->button_color ?? '#0085f3' }};color:{{ $form->button_text_color ?? '#ffffff' }}; }
        .ms-btn-primary:hover { opacity:.9; }
        .ms-btn-primary:disabled { opacity:.4;cursor:not-allowed; }
        .ms-btn-back { background:#f3f4f6;color:#374151; }
        .ms-btn-back:hover { background:#e5e7eb; }

        .ms-footer { text-align:center;margin-top:20px;font-size:11px;color:#d1d5db; }
        .ms-footer a { color:#d1d5db;text-decoration:none; }

        .honeypot { position:absolute;left:-9999px; }
        .alert-error { background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px;display:none; }

        @keyframes fadeIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
    </style>
</head>
<body>
    <div class="ms-wrap">
        @if(($form->enable_logo ?? true) && $form->logo_url)
        <div class="ms-logo"><img src="{{ $form->logo_url }}" alt="{{ $form->name }}"></div>
        @endif

        <div class="ms-card">
            <h1 class="ms-title">{{ $form->name }}</h1>

            {{-- Step progress --}}
            <div class="ms-progress" id="progressDots"></div>
            <div class="ms-step-title" id="stepTitle"></div>

            <div class="alert-error" id="formAlert"></div>

            <form id="multiStepForm" onsubmit="return false;">
                <input type="text" name="_website_url" class="honeypot" tabindex="-1" autocomplete="off">
                <div id="pagesContainer"></div>
            </form>

            <div class="ms-nav">
                <button class="ms-btn ms-btn-back" id="btnPrev" onclick="prevStep()" style="visibility:hidden;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M10 12L6 8l4-4"/></svg>
                    {{ __('forms.prev_step') }}
                </button>
                <button class="ms-btn ms-btn-primary" id="btnNextStep" onclick="nextStep()">
                    {{ __('forms.next_step') }}
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 4l4 4-4 4"/></svg>
                </button>
            </div>
        </div>

        <div class="ms-footer">
            <a href="https://syncro.chat" target="_blank" style="display:inline-flex;align-items:center;gap:6px;">
                <span>Criado com</span>
                <img src="{{ asset('images/logo.png') }}" alt="Syncro" style="height:14px;opacity:.35;filter:grayscale(1);" onerror="this.outerHTML='<span style=\'font-weight:600;\'>Syncro</span>'">
            </a>
        </div>
    </div>

    <script>
    const allFields = {!! json_encode($form->fields ?? []) !!};
    const formSteps = {!! json_encode($form->steps ?? []) !!};
    const conditions = {!! json_encode($form->conditional_logic ?? []) !!};
    let currentStep = 0;
    const formData = {};

    // ── Build step pages ────────────────────────────────
    function init() {
        // If no steps defined, create a single step with all fields
        if (!formSteps.length) {
            formSteps.push({ id: 'default', title: '{{ $form->name }}' });
            allFields.forEach(f => f.step_id = 'default');
        }

        // Assign orphan fields to first step
        allFields.forEach(f => { if (!f.step_id) f.step_id = formSteps[0].id; });

        buildPages();
        updateProgress();
        showStep(0);
    }

    function buildPages() {
        const container = document.getElementById('pagesContainer');
        container.innerHTML = '';

        formSteps.forEach((step, si) => {
            const page = document.createElement('div');
            page.className = 'ms-page';
            page.id = 'page_' + si;

            const stepFields = allFields.filter(f => f.step_id === step.id);
            let html = '';

            stepFields.forEach(f => {
                html += renderField(f);
            });

            page.innerHTML = html;
            container.appendChild(page);
        });
    }

    function renderField(f) {
        const fid = f.id;
        const type = f.type;
        const label = f.label || '';
        const ph = f.placeholder || '';
        const req = f.required || false;
        const help = f.help_text || '';
        const options = f.options || [];

        // Check conditional visibility
        const cond = conditions.find(c => c.target_field_id === fid);
        let hidden = false;
        if (cond && cond.field_id) {
            hidden = !evaluateCondition(cond);
        }

        if (type === 'divider') return `<hr class="form-divider" data-field-id="${fid}" ${hidden ? 'style="display:none"' : ''}>`;
        if (type === 'heading') return `<div class="form-heading" data-field-id="${fid}" ${hidden ? 'style="display:none"' : ''}>${escapeHtml(label)}</div>`;

        let inputHtml = '';
        const currentVal = formData[fid] || '';

        if (type === 'textarea') {
            inputHtml = `<textarea class="field-input" id="field_${fid}" name="${fid}" placeholder="${escapeHtml(ph)}" ${req ? 'required' : ''}>${escapeHtml(currentVal)}</textarea>`;
        } else if (type === 'select') {
            inputHtml = `<select class="field-input" id="field_${fid}" name="${fid}" ${req ? 'required' : ''}>
                <option value="">${escapeHtml(ph || '—')}</option>
                ${options.map(o => `<option value="${escapeHtml(o)}" ${currentVal === o ? 'selected' : ''}>${escapeHtml(o)}</option>`).join('')}
            </select>`;
        } else if (type === 'checkbox') {
            const vals = Array.isArray(formData[fid]) ? formData[fid] : [];
            inputHtml = '<div class="checkbox-group">';
            if (options.length) {
                options.forEach(o => {
                    inputHtml += `<label><input type="checkbox" name="${fid}[]" value="${escapeHtml(o)}" ${vals.includes(o) ? 'checked' : ''}> ${escapeHtml(o)}</label>`;
                });
            } else {
                inputHtml += `<label><input type="checkbox" name="${fid}" value="1" ${currentVal ? 'checked' : ''}> ${escapeHtml(label)}</label>`;
            }
            inputHtml += '</div>';
        } else if (type === 'radio') {
            inputHtml = '<div class="radio-group">';
            options.forEach(o => {
                inputHtml += `<label><input type="radio" name="${fid}" value="${escapeHtml(o)}" ${currentVal === o ? 'checked' : ''} ${req ? 'required' : ''}> ${escapeHtml(o)}</label>`;
            });
            inputHtml += '</div>';
        } else if (type === 'file') {
            inputHtml = `<input type="file" class="field-input" id="field_${fid}" name="${fid}" ${req ? 'required' : ''} style="padding:8px;">`;
        } else {
            const inputType = type === 'tel' ? 'tel' : (type === 'email' ? 'email' : (type === 'number' ? 'number' : 'text'));
            inputHtml = `<input type="${inputType}" class="field-input" id="field_${fid}" name="${fid}" value="${escapeHtml(currentVal)}" placeholder="${escapeHtml(ph)}" ${req ? 'required' : ''}>`;
        }

        return `<div class="field-group" data-field-id="${fid}" ${hidden ? 'style="display:none"' : ''}>
            <label class="field-label" for="field_${fid}">${escapeHtml(label)} ${req ? '<span class="req">*</span>' : ''}</label>
            ${inputHtml}
            ${help ? `<div class="field-help">${escapeHtml(help)}</div>` : ''}
            <div class="field-error" id="err_${fid}"></div>
        </div>`;
    }

    // ── Conditional logic ───────────────────────────────
    function evaluateCondition(cond) {
        const val = formData[cond.field_id];
        const valStr = Array.isArray(val) ? val.join(',') : (val || '');
        switch (cond.operator) {
            case 'equals': return valStr === cond.value;
            case 'not_equals': return valStr !== cond.value;
            case 'contains': return valStr.toLowerCase().includes((cond.value || '').toLowerCase());
            case 'not_empty': return valStr !== '';
            case 'is_empty': return valStr === '';
            default: return true;
        }
    }

    function updateConditionalVisibility() {
        conditions.forEach(cond => {
            const el = document.querySelector(`[data-field-id="${cond.target_field_id}"]`);
            if (!el) return;
            const visible = !cond.field_id || evaluateCondition(cond);
            el.style.display = visible ? '' : 'none';
        });
    }

    // ── Collect form data from current step ─────────────
    function collectStepData() {
        const step = formSteps[currentStep];
        const stepFields = allFields.filter(f => f.step_id === step.id && !['heading', 'divider'].includes(f.type));

        stepFields.forEach(f => {
            const el = document.getElementById('field_' + f.id);
            if (f.type === 'checkbox') {
                const checked = document.querySelectorAll(`input[name="${f.id}[]"]:checked, input[name="${f.id}"]:checked`);
                formData[f.id] = Array.from(checked).map(c => c.value);
            } else if (f.type === 'radio') {
                const checked = document.querySelector(`input[name="${f.id}"]:checked`);
                formData[f.id] = checked ? checked.value : '';
            } else if (el) {
                formData[f.id] = el.value;
            }
        });

        // Update conditional visibility after data collection
        updateConditionalVisibility();
    }

    // ── Validate current step ───────────────────────────
    function validateStep() {
        const step = formSteps[currentStep];
        const stepFields = allFields.filter(f => f.step_id === step.id && !['heading', 'divider'].includes(f.type));
        let valid = true;

        // Clear errors
        stepFields.forEach(f => {
            const errEl = document.getElementById('err_' + f.id);
            if (errEl) { errEl.textContent = ''; errEl.style.display = 'none'; }
        });

        stepFields.forEach(f => {
            // Skip hidden fields (conditional)
            const fieldEl = document.querySelector(`[data-field-id="${f.id}"]`);
            if (fieldEl && fieldEl.style.display === 'none') return;

            const val = formData[f.id];
            if (f.required) {
                const isEmpty = !val || (Array.isArray(val) && val.length === 0);
                if (isEmpty) {
                    const errEl = document.getElementById('err_' + f.id);
                    if (errEl) { errEl.textContent = (f.label || 'Campo') + ' é obrigatório.'; errEl.style.display = 'block'; }
                    valid = false;
                }
            }
            if (f.type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                const errEl = document.getElementById('err_' + f.id);
                if (errEl) { errEl.textContent = 'E-mail inválido.'; errEl.style.display = 'block'; }
                valid = false;
            }
        });

        return valid;
    }

    // ── Navigation ──────────────────────────────────────
    function showStep(idx) {
        document.querySelectorAll('.ms-page').forEach(p => p.classList.remove('active'));
        const page = document.getElementById('page_' + idx);
        if (page) page.classList.add('active');

        document.getElementById('stepTitle').textContent = formSteps[idx]?.title || '';
        document.getElementById('btnPrev').style.visibility = idx > 0 ? 'visible' : 'hidden';

        const isLast = idx >= formSteps.length - 1;
        const btn = document.getElementById('btnNextStep');
        btn.innerHTML = isLast
            ? '{{ __("forms.submit_button") }} <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="4 7 7 10 11 5"/></svg>'
            : '{{ __("forms.next_step") }} <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 4l4 4-4 4"/></svg>';

        updateProgress();
        updateConditionalVisibility();
    }

    function updateProgress() {
        const dots = document.getElementById('progressDots');
        let html = '';
        formSteps.forEach((s, i) => {
            const cls = i < currentStep ? 'done' : (i === currentStep ? 'active' : '');
            const icon = i < currentStep ? '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="4 7 6 9 10 5"/></svg>' : (i + 1);
            html += `<div class="ms-step-dot ${cls}">${icon}</div>`;
            if (i < formSteps.length - 1) {
                html += `<div class="ms-step-line ${i < currentStep ? 'done' : ''}"></div>`;
            }
        });
        dots.innerHTML = html;
    }

    function nextStep() {
        collectStepData();
        if (!validateStep()) return;

        if (currentStep >= formSteps.length - 1) {
            submitForm();
            return;
        }

        currentStep++;
        showStep(currentStep);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function prevStep() {
        if (currentStep <= 0) return;
        collectStepData();
        currentStep--;
        showStep(currentStep);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ── Submit ──────────────────────────────────────────
    async function submitForm() {
        const btn = document.getElementById('btnNextStep');
        const alert = document.getElementById('formAlert');
        btn.disabled = true;
        alert.style.display = 'none';

        // Collect all data from all steps
        allFields.forEach(f => {
            if (['heading', 'divider'].includes(f.type)) return;
            if (formData[f.id] === undefined) {
                const el = document.getElementById('field_' + f.id);
                if (el) formData[f.id] = el.value;
            }
        });

        // Honeypot
        const hp = document.querySelector('input[name="_website_url"]');
        if (hp) formData['_website_url'] = hp.value;

        // UTMs
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
                    if (!url.match(/^https?:\/\//i)) url = 'https://' + url;
                    window.location.href = url;
                } else {
                    document.querySelector('.ms-card').innerHTML = `
                        <div style="text-align:center;padding:40px 20px;">
                            <div style="width:56px;height:56px;border-radius:50%;background:#ecfdf5;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                                <svg width="24" height="24" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <h2 style="font-size:18px;font-weight:700;color:#1a1d23;margin-bottom:8px;">{{ __('forms.default_thanks') }}</h2>
                            <p style="font-size:14px;color:#6b7280;">${escapeHtml(data.confirmation_value || '')}</p>
                        </div>`;
                }
            } else if (data.errors) {
                Object.entries(data.errors).forEach(([field, msgs]) => {
                    const errEl = document.getElementById('err_' + field);
                    if (errEl) { errEl.textContent = msgs[0]; errEl.style.display = 'block'; }
                });
                btn.disabled = false;
            } else {
                alert.textContent = data.message || 'Erro ao enviar';
                alert.style.display = 'block';
                btn.disabled = false;
            }
        } catch (e) {
            alert.textContent = 'Erro de conexão. Tente novamente.';
            alert.style.display = 'block';
            btn.disabled = false;
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Listen for input changes to update conditional logic
    document.addEventListener('change', function(e) {
        if (e.target.closest('.field-group')) {
            collectStepData();
        }
    });

    // ── Preview listener ────────────────────────────────
    if (new URLSearchParams(window.location.search).get('preview') === '1') {
        window.addEventListener('message', function(e) {
            if (e.data?.type !== 'form-preview-update') return;
            const s = e.data.styles;
            if (s.font_family) document.body.style.fontFamily = `'${s.font_family}', sans-serif`;
            if (s.background_color) document.body.style.background = s.background_color;
            const card = document.querySelector('.ms-card');
            if (card && s.card_color) card.style.background = s.card_color;
            if (s.button_color) document.querySelectorAll('.ms-btn-primary').forEach(el => { el.style.background = s.button_color; el.style.color = s.button_text_color || '#fff'; });
            if (s.label_color) document.querySelectorAll('.ms-title, .field-label').forEach(el => el.style.color = s.label_color);
            if (s.input_bg_color) document.querySelectorAll('.field-input').forEach(el => el.style.background = s.input_bg_color);
            if (s.input_border_color) document.querySelectorAll('.field-input').forEach(el => el.style.borderColor = s.input_border_color);
            if (s.input_text_color) document.querySelectorAll('.field-input').forEach(el => el.style.color = s.input_text_color);
            if (s.brand_color) document.querySelectorAll('.ms-step-dot.active, .ms-step-dot.done').forEach(el => el.style.borderColor = s.brand_color);
            if (s.layout) {
                const map = { left: ['flex-start', '10%'], right: ['flex-end', '10%'], centered: ['center', '24px'] };
                const [j, p] = map[s.layout] || map.centered;
                document.body.style.justifyContent = j;
                document.body.style.paddingLeft = p;
                document.body.style.paddingRight = p;
            }
        });
    }

    // ── Init ────────────────────────────────────────────
    init();
    </script>

    @include('forms._phone-lib', [
        'defaultCountry'  => $form->default_country ?? 'BR',
        'allowedCountries' => $form->allowed_countries ?? [],
    ])
</body>
</html>
