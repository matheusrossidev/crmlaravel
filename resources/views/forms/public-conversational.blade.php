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
        html, body { height: 100%; margin: 0; padding: 0; }
        body {
            font-family: '{{ $form->font_family ?? 'Inter' }}', sans-serif;
            background: {{ $form->background_color ?? '#ffffff' }};
            @if(($form->enable_background_image ?? false) && $form->background_image_url)
            background-image: url('{{ $form->background_image_url }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            @endif
            position: fixed;
            inset: 0;
            height: 100dvh;
            min-height: 100vh;
            width: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Progress bar */
        .conv-progress { position:fixed;top:0;left:0;right:0;height:4px;background:rgba(0,0,0,.06);z-index:100; }
        .conv-progress-bar { height:100%;background:{{ $form->brand_color ?? '#0085f3' }};transition:width .4s cubic-bezier(.4,0,.2,1);width:0; }

        /* Header */
        .conv-header { position:fixed;top:4px;left:0;right:0;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;z-index:90; }
        .conv-header .logo img { max-height:40px; }
        .conv-counter { font-size:13px;font-weight:600;color:{{ $form->label_color ?? '#6b7280' }};opacity:.7; }

        /* Slides container */
        .conv-slides { flex:1;display:flex;align-items:center;justify-content:center;position:relative;padding:80px 24px 120px; }
        .conv-slide { position:absolute;width:100%;max-width:600px;left:50%;transform:translateX(-50%);opacity:0;pointer-events:none;transition:all .5s cubic-bezier(.4,0,.2,1); }
        .conv-slide.active { opacity:1;pointer-events:auto;transform:translateX(-50%) translateY(0); }
        .conv-slide.prev { opacity:0;transform:translateX(-50%) translateY(-40px); }
        .conv-slide.next { opacity:0;transform:translateX(-50%) translateY(40px); }

        /* Question */
        .conv-question-num { font-size:13px;font-weight:700;color:{{ $form->brand_color ?? '#0085f3' }};margin-bottom:8px;display:flex;align-items:center;gap:6px; }
        .conv-question-num span { width:24px;height:24px;border-radius:6px;background:{{ $form->brand_color ?? '#0085f3' }};color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:12px; }
        .conv-question { font-size:24px;font-weight:700;color:{{ $form->label_color ?? '#1a1d23' }};margin-bottom:24px;line-height:1.3; }
        .conv-question .req-star { color:#dc2626;margin-left:2px; }
        .conv-help { font-size:14px;color:#9ca3af;margin-bottom:16px;margin-top:-16px; }

        /* Inputs */
        .conv-input {
            width:100%;padding:16px 20px;font-size:18px;font-family:inherit;
            color:{{ $form->input_text_color ?? '#1a1d23' }};
            background:{{ $form->input_bg_color ?? '#ffffff' }};
            border:2px solid {{ $form->input_border_color ?? '#e5e7eb' }};
            border-radius:{{ $form->border_radius ?? 8 }}px;
            outline:none;transition:border-color .2s;
        }
        .conv-input:focus { border-color:{{ $form->brand_color ?? '#0085f3' }}; }
        textarea.conv-input { resize:vertical;min-height:100px; }

        .conv-select-grid { display:flex;flex-direction:column;gap:8px; }
        .conv-select-opt {
            padding:14px 18px;font-size:16px;font-family:inherit;
            background:{{ $form->input_bg_color ?? '#ffffff' }};
            border:2px solid {{ $form->input_border_color ?? '#e5e7eb' }};
            border-radius:{{ $form->border_radius ?? 8 }}px;
            cursor:pointer;display:flex;align-items:center;gap:10px;transition:.2s;
            color:{{ $form->input_text_color ?? '#1a1d23' }};
        }
        .conv-select-opt:hover { border-color:{{ $form->brand_color ?? '#0085f3' }}; }
        .conv-select-opt.selected { border-color:{{ $form->brand_color ?? '#0085f3' }};background:{{ $form->brand_color ?? '#0085f3' }}10; }
        .conv-select-key { width:24px;height:24px;border-radius:6px;border:1.5px solid #d1d5db;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#9ca3af;flex-shrink:0; }
        .conv-select-opt.selected .conv-select-key { border-color:{{ $form->brand_color ?? '#0085f3' }};color:{{ $form->brand_color ?? '#0085f3' }}; }

        .conv-checkbox-grid { display:flex;flex-direction:column;gap:8px; }
        .conv-checkbox-opt {
            padding:14px 18px;font-size:16px;font-family:inherit;
            background:{{ $form->input_bg_color ?? '#ffffff' }};
            border:2px solid {{ $form->input_border_color ?? '#e5e7eb' }};
            border-radius:{{ $form->border_radius ?? 8 }}px;
            cursor:pointer;display:flex;align-items:center;gap:10px;transition:.2s;
            color:{{ $form->input_text_color ?? '#1a1d23' }};
        }
        .conv-checkbox-opt:hover { border-color:{{ $form->brand_color ?? '#0085f3' }}; }
        .conv-checkbox-opt.selected { border-color:{{ $form->brand_color ?? '#0085f3' }};background:{{ $form->brand_color ?? '#0085f3' }}10; }
        .conv-check-box { width:22px;height:22px;border-radius:6px;border:1.5px solid #d1d5db;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:.15s; }
        .conv-checkbox-opt.selected .conv-check-box { border-color:{{ $form->brand_color ?? '#0085f3' }};background:{{ $form->brand_color ?? '#0085f3' }};color:#fff; }

        /* Error */
        .conv-error { color:#dc2626;font-size:13px;margin-top:8px;min-height:20px; }

        /* Navigation */
        .conv-nav { position:fixed;bottom:0;left:0;right:0;padding:20px 24px;display:flex;align-items:center;justify-content:center;gap:12px;z-index:90;background:linear-gradient(transparent, {{ $form->background_color ?? '#ffffff' }} 30%); }
        .conv-btn {
            padding:14px 32px;font-size:16px;font-weight:700;font-family:inherit;
            border:none;border-radius:{{ $form->border_radius ?? 8 }}px;
            cursor:pointer;transition:.2s;display:flex;align-items:center;gap:8px;
        }
        .conv-btn-primary {
            background:{{ $form->button_color ?? '#0085f3' }};
            color:{{ $form->button_text_color ?? '#ffffff' }};
        }
        .conv-btn-primary:hover { opacity:.9; }
        .conv-btn-primary:disabled { opacity:.4;cursor:not-allowed; }
        .conv-btn-back { background:transparent;color:{{ $form->label_color ?? '#6b7280' }};font-size:14px; }
        .conv-btn-back:hover { opacity:.7; }

        .conv-enter-hint { font-size:12px;color:#9ca3af;margin-top:8px; }

        /* Footer */
        .conv-footer { position:fixed;bottom:70px;left:0;right:0;text-align:center;font-size:11px;color:#d1d5db;z-index:80; }
        .conv-footer a { color:#d1d5db;text-decoration:none; }

        /* Success */
        .conv-success { text-align:center; }
        .conv-success-icon { width:72px;height:72px;border-radius:50%;background:#ecfdf5;display:flex;align-items:center;justify-content:center;margin:0 auto 20px; }
        .conv-success h2 { font-size:26px;font-weight:700;color:{{ $form->label_color ?? '#1a1d23' }};margin-bottom:10px; }
        .conv-success p { font-size:16px;color:#6b7280; }

        .honeypot { position:absolute;left:-9999px; }

        @keyframes slideUp {
            from { opacity:0;transform:translateX(-50%) translateY(30px); }
            to { opacity:1;transform:translateX(-50%) translateY(0); }
        }
    </style>
</head>
<body>
    {{-- Progress --}}
    <div class="conv-progress"><div class="conv-progress-bar" id="progressBar"></div></div>

    {{-- Header --}}
    <div class="conv-header">
        <div class="logo">
            @if(($form->enable_logo ?? true) && $form->logo_url)
                <img src="{{ $form->logo_url }}" alt="{{ $form->name }}">
            @endif
        </div>
        <div class="conv-counter" id="counter"></div>
    </div>

    {{-- Slides --}}
    <div class="conv-slides" id="slidesContainer">
        {{-- Rendered by JS --}}
    </div>

    {{-- Navigation --}}
    <div class="conv-nav" id="navBar">
        <button class="conv-btn conv-btn-back" id="btnBack" onclick="goBack()" style="display:none;">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M10 12L6 8l4-4"/></svg>
        </button>
        <button class="conv-btn conv-btn-primary" id="btnNext" onclick="goNext()">
            OK
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 4l4 4-4 4"/></svg>
        </button>
    </div>

    <div class="conv-footer">
        <a href="https://syncro.chat" target="_blank" style="display:inline-flex;align-items:center;gap:6px;">
            <span>Criado com</span>
            <img src="{{ asset('images/logo.png') }}" alt="Syncro" style="height:14px;opacity:.35;filter:grayscale(1);" onerror="this.outerHTML='<span style=\'font-weight:600;\'>Syncro</span>'">
        </a>
    </div>

    <input type="text" name="_website_url" class="honeypot" tabindex="-1" autocomplete="off" id="honeypot">

    <script>
    const allFields = {!! json_encode($form->fields ?? []) !!};
    const conditions = {!! json_encode($form->conditional_logic ?? []) !!};
    const formData = {};
    let visibleFields = [];
    let currentIdx = 0;
    let submitted = false;

    // Filter out headings/dividers for conversational
    const inputFields = allFields.filter(f => !['heading', 'divider'].includes(f.type));

    // ── Conditional logic evaluation ────────────────────
    function evaluateConditions() {
        visibleFields = inputFields.filter(f => {
            const cond = conditions.find(c => c.target_field_id === f.id);
            if (!cond || !cond.field_id) return true;

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
        });
    }

    // ── Build slides ────────────────────────────────────
    function buildSlides() {
        evaluateConditions();
        const container = document.getElementById('slidesContainer');
        container.innerHTML = '';

        visibleFields.forEach((f, i) => {
            const slide = document.createElement('div');
            slide.className = 'conv-slide' + (i === currentIdx ? ' active' : (i < currentIdx ? ' prev' : ' next'));
            slide.id = 'slide_' + i;
            slide.innerHTML = buildSlideContent(f, i);
            container.appendChild(slide);
        });

        // Success slide
        const successSlide = document.createElement('div');
        successSlide.className = 'conv-slide' + (currentIdx >= visibleFields.length ? ' active' : ' next');
        successSlide.id = 'slide_success';
        container.appendChild(successSlide);

        updateUI();
    }

    function buildSlideContent(f, num) {
        const req = f.required ? '<span class="req-star">*</span>' : '';
        const helpHtml = f.help_text ? `<div class="conv-help">${escapeHtml(f.help_text)}</div>` : '';
        const currentVal = formData[f.id] || '';

        let inputHtml = '';

        switch (f.type) {
            case 'text':
            case 'email':
            case 'tel':
            case 'number':
                inputHtml = `<input type="${f.type === 'tel' ? 'tel' : (f.type === 'email' ? 'email' : (f.type === 'number' ? 'number' : 'text'))}"
                    class="conv-input" id="input_${f.id}" value="${escapeHtml(currentVal)}"
                    placeholder="${escapeHtml(f.placeholder || '')}" autofocus
                    onkeydown="if(event.key==='Enter'){event.preventDefault();goNext();}">`;
                break;

            case 'textarea':
                inputHtml = `<textarea class="conv-input" id="input_${f.id}" placeholder="${escapeHtml(f.placeholder || '')}">${escapeHtml(currentVal)}</textarea>`;
                break;

            case 'select':
                inputHtml = '<div class="conv-select-grid">';
                (f.options || []).forEach((opt, oi) => {
                    const letter = String.fromCharCode(65 + oi);
                    const sel = currentVal === opt ? 'selected' : '';
                    inputHtml += `<div class="conv-select-opt ${sel}" data-field="${f.id}" data-value="${escapeHtml(opt)}" onclick="selectOption(this,'${f.id}','${escapeHtml(opt)}')">
                        <span class="conv-select-key">${letter}</span>
                        ${escapeHtml(opt)}
                    </div>`;
                });
                inputHtml += '</div>';
                break;

            case 'radio':
                inputHtml = '<div class="conv-select-grid">';
                (f.options || []).forEach((opt, oi) => {
                    const letter = String.fromCharCode(65 + oi);
                    const sel = currentVal === opt ? 'selected' : '';
                    inputHtml += `<div class="conv-select-opt ${sel}" data-field="${f.id}" data-value="${escapeHtml(opt)}" onclick="selectOption(this,'${f.id}','${escapeHtml(opt)}')">
                        <span class="conv-select-key">${letter}</span>
                        ${escapeHtml(opt)}
                    </div>`;
                });
                inputHtml += '</div>';
                break;

            case 'checkbox':
                const vals = Array.isArray(formData[f.id]) ? formData[f.id] : [];
                inputHtml = '<div class="conv-checkbox-grid">';
                (f.options || []).forEach((opt) => {
                    const sel = vals.includes(opt) ? 'selected' : '';
                    inputHtml += `<div class="conv-checkbox-opt ${sel}" data-field="${f.id}" data-value="${escapeHtml(opt)}" onclick="toggleCheckbox(this,'${f.id}','${escapeHtml(opt)}')">
                        <span class="conv-check-box">${vals.includes(opt) ? '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="11 5 6 10 3 7"/></svg>' : ''}</span>
                        ${escapeHtml(opt)}
                    </div>`;
                });
                inputHtml += '</div>';
                break;

            case 'file':
                inputHtml = `<input type="file" class="conv-input" id="input_${f.id}" style="padding:12px;">`;
                break;

            default:
                inputHtml = `<input type="text" class="conv-input" id="input_${f.id}" value="${escapeHtml(currentVal)}"
                    placeholder="${escapeHtml(f.placeholder || '')}"
                    onkeydown="if(event.key==='Enter'){event.preventDefault();goNext();}">`;
        }

        return `
            <div class="conv-question-num"><span>${num + 1}</span></div>
            <div class="conv-question">${escapeHtml(f.label)} ${req}</div>
            ${helpHtml}
            ${inputHtml}
            <div class="conv-error" id="err_${f.id}"></div>
        `;
    }

    // ── Interactions ────────────────────────────────────
    function selectOption(el, fieldId, value) {
        formData[fieldId] = value;
        el.parentElement.querySelectorAll('.conv-select-opt').forEach(o => o.classList.remove('selected'));
        el.classList.add('selected');
        // Auto-advance after brief delay
        setTimeout(() => goNext(), 300);
    }

    function toggleCheckbox(el, fieldId, value) {
        if (!Array.isArray(formData[fieldId])) formData[fieldId] = [];
        const arr = formData[fieldId];
        const idx = arr.indexOf(value);
        if (idx >= 0) { arr.splice(idx, 1); el.classList.remove('selected'); }
        else { arr.push(value); el.classList.add('selected'); }
        // Update check icon
        const box = el.querySelector('.conv-check-box');
        box.innerHTML = el.classList.contains('selected') ? '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="11 5 6 10 3 7"/></svg>' : '';
    }

    // ── Collect input value ─────────────────────────────
    function collectCurrentValue() {
        if (currentIdx >= visibleFields.length) return;
        const f = visibleFields[currentIdx];
        if (['select', 'radio', 'checkbox'].includes(f.type)) return; // Already handled by click
        const input = document.getElementById('input_' + f.id);
        if (input) formData[f.id] = input.value;
    }

    // ── Validate current ────────────────────────────────
    function validateCurrent() {
        if (currentIdx >= visibleFields.length) return true;
        const f = visibleFields[currentIdx];
        const val = formData[f.id];
        const errEl = document.getElementById('err_' + f.id);

        if (f.required) {
            const isEmpty = !val || (Array.isArray(val) && val.length === 0);
            if (isEmpty) {
                if (errEl) errEl.textContent = (f.label || 'Este campo') + ' é obrigatório.';
                return false;
            }
        }

        if (f.type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
            if (errEl) errEl.textContent = 'E-mail inválido.';
            return false;
        }

        if (errEl) errEl.textContent = '';
        return true;
    }

    // ── Navigation ──────────────────────────────────────
    function goNext() {
        if (submitted) return;
        collectCurrentValue();
        if (!validateCurrent()) return;

        // Re-evaluate conditions after collecting value (answer may show/hide future fields)
        const oldLen = visibleFields.length;
        evaluateConditions();

        if (currentIdx >= visibleFields.length - 1) {
            // Last field — submit
            submitForm();
            return;
        }

        currentIdx++;
        buildSlides();
        focusCurrentInput();
    }

    function goBack() {
        if (currentIdx <= 0) return;
        collectCurrentValue();
        currentIdx--;
        evaluateConditions();
        buildSlides();
        focusCurrentInput();
    }

    function focusCurrentInput() {
        setTimeout(() => {
            const f = visibleFields[currentIdx];
            if (!f) return;
            const input = document.getElementById('input_' + f.id);
            if (input && input.focus) input.focus();
        }, 400);
    }

    // ── Update UI state ─────────────────────────────────
    function updateUI() {
        const total = visibleFields.length;
        const pct = total > 0 ? ((currentIdx + 1) / total) * 100 : 0;

        document.getElementById('progressBar').style.width = pct + '%';
        document.getElementById('counter').textContent = (currentIdx + 1) + ' / ' + total;
        document.getElementById('btnBack').style.display = currentIdx > 0 ? '' : 'none';

        const isLast = currentIdx >= total - 1;
        const btnNext = document.getElementById('btnNext');
        btnNext.innerHTML = isLast
            ? '{{ __("forms.submit_button") }} <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="4 8 7 11 12 5"/></svg>'
            : 'OK <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 4l4 4-4 4"/></svg>';
    }

    // ── Submit ──────────────────────────────────────────
    async function submitForm() {
        submitted = true;
        const btnNext = document.getElementById('btnNext');
        btnNext.disabled = true;
        btnNext.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite"><path d="M12 2a10 10 0 1 0 10 10"/></svg>';

        // Add honeypot
        formData['_website_url'] = document.getElementById('honeypot').value;

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
                    return;
                }

                // Show success
                document.getElementById('progressBar').style.width = '100%';
                document.getElementById('counter').textContent = '';
                document.getElementById('navBar').style.display = 'none';

                const successSlide = document.getElementById('slide_success');
                if (successSlide) {
                    document.querySelectorAll('.conv-slide.active').forEach(s => { s.classList.remove('active'); s.classList.add('prev'); });
                    successSlide.innerHTML = `<div class="conv-success">
                        <div class="conv-success-icon">
                            <svg width="32" height="32" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round"><polyline points="24 8 10 22 4 16"/></svg>
                        </div>
                        <h2>{{ __('forms.default_thanks') }}</h2>
                        <p>${escapeHtml(data.confirmation_value || '')}</p>
                    </div>`;
                    successSlide.classList.add('active');
                }
            } else if (data.errors) {
                const msgs = Object.values(data.errors).flat();
                const errEl = document.getElementById('err_' + visibleFields[currentIdx]?.id);
                if (errEl) errEl.textContent = msgs[0] || 'Erro';
                submitted = false;
                btnNext.disabled = false;
                updateUI();
            } else {
                submitted = false;
                btnNext.disabled = false;
                updateUI();
            }
        } catch (e) {
            submitted = false;
            btnNext.disabled = false;
            updateUI();
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Preview listener ────────────────────────────────
    if (new URLSearchParams(window.location.search).get('preview') === '1') {
        window.addEventListener('message', function(e) {
            if (e.data?.type !== 'form-preview-update') return;
            const s = e.data.styles;
            if (s.font_family) document.body.style.fontFamily = `'${s.font_family}', sans-serif`;
            if (s.background_color) document.body.style.background = s.background_color;
            // Update CSS custom properties for live colors
            const root = document.documentElement;
            if (s.brand_color) {
                document.querySelectorAll('.conv-progress-bar').forEach(el => el.style.background = s.brand_color);
                document.querySelectorAll('.conv-question-num span').forEach(el => el.style.background = s.brand_color);
                document.querySelectorAll('.conv-input').forEach(el => el.style.borderColor = s.input_border_color || '#e5e7eb');
            }
            if (s.button_color) document.querySelectorAll('.conv-btn-primary').forEach(el => { el.style.background = s.button_color; el.style.color = s.button_text_color || '#fff'; });
            if (s.label_color) document.querySelectorAll('.conv-question').forEach(el => el.style.color = s.label_color);
            if (s.input_bg_color) document.querySelectorAll('.conv-input, .conv-select-opt, .conv-checkbox-opt').forEach(el => el.style.background = s.input_bg_color);
            if (s.input_text_color) document.querySelectorAll('.conv-input, .conv-select-opt, .conv-checkbox-opt').forEach(el => el.style.color = s.input_text_color);
        });
    }

    // ── Init ────────────────────────────────────────────
    buildSlides();
    focusCurrentInput();
    </script>

    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>

    @include('forms._phone-lib', [
        'defaultCountry'  => $form->default_country ?? 'BR',
        'allowedCountries' => $form->allowed_countries ?? [],
    ])
</body>
</html>
