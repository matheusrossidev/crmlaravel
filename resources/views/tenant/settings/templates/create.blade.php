@extends('tenant.layouts.app')

@php
    $title    = __('wa_templates.create');
    $pageIcon = 'chat-dots';
@endphp

@push('styles')
<style>
    .tpl-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 20px; align-items: start; }
    @media (max-width: 900px) { .tpl-grid { grid-template-columns: 1fr; } }

    .card {
        background: #fff; border: 1px solid #e8eaf0;
        border-radius: 14px; overflow: hidden;
        margin-bottom: 16px;
    }
    .card-head {
        padding: 14px 20px;
        border-bottom: 1px solid #f0f2f7;
        font-size: 14px; font-weight: 700; color: #1a1d23;
    }
    .card-body { padding: 18px 20px; }

    .form-row { margin-bottom: 14px; }
    .form-row label {
        display: block;
        font-size: 12px; font-weight: 600; color: #374151;
        margin-bottom: 5px;
    }
    .form-row .hint { font-size: 11.5px; color: #9ca3af; margin-top: 4px; }
    .form-row input[type=text],
    .form-row input[type=tel],
    .form-row input[type=url],
    .form-row select,
    .form-row textarea {
        width: 100%;
        padding: 9px 12px;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        font-size: 13.5px;
        color: #1a1d23;
        background: #fff;
        transition: border-color .15s;
        font-family: inherit;
    }
    .form-row input:focus,
    .form-row select:focus,
    .form-row textarea:focus {
        border-color: #0085f3; outline: none;
    }
    .form-row textarea { resize: vertical; min-height: 100px; }
    .char-counter { font-size: 11px; color: #9ca3af; float: right; }

    .cat-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    @media (max-width: 640px) { .cat-cards { grid-template-columns: 1fr; } }
    .cat-card {
        padding: 14px 12px;
        border: 1.5px solid #e8eaf0;
        border-radius: 10px;
        cursor: pointer;
        text-align: center;
        transition: all .15s;
    }
    .cat-card:hover { border-color: #bfdbfe; background: #f8fafc; }
    .cat-card.active { border-color: #0085f3; background: #eff6ff; }
    .cat-card .ico { font-size: 22px; margin-bottom: 6px; display: block; }
    .cat-card .title { font-size: 13px; font-weight: 700; color: #1a1d23; margin-bottom: 3px; }
    .cat-card .desc  { font-size: 11px; color: #6b7280; line-height: 1.35; }

    .btn-primary-sm {
        background: #0085f3; color: #fff; border: 0;
        padding: 10px 20px; border-radius: 9px;
        font-size: 13.5px; font-weight: 600;
        display: inline-flex; align-items: center; gap: 7px;
        cursor: pointer; transition: background .15s;
    }
    .btn-primary-sm:hover { background: #0070d1; }
    .btn-primary-sm:disabled { opacity: .6; cursor: not-allowed; }

    .btn-secondary-sm {
        background: #eff6ff; color: #0085f3;
        border: 1.5px solid #bfdbfe;
        padding: 8px 14px; border-radius: 9px;
        font-size: 12.5px; font-weight: 600;
        cursor: pointer;
    }
    .btn-text {
        background: transparent; border: 0; color: #0085f3;
        font-size: 12.5px; font-weight: 600; cursor: pointer;
    }

    .toggle-row {
        display: flex; align-items: center; gap: 8px;
        font-size: 13px; color: #374151; font-weight: 500;
    }
    .sample-table { width: 100%; border-collapse: collapse; }
    .sample-table td { padding: 5px 0; }
    .sample-table .vlabel { font-family: monospace; font-size: 12px; color: #6b7280; width: 50px; }

    .btn-item {
        background: #f9fafb;
        border: 1px solid #e8eaf0;
        border-radius: 9px;
        padding: 10px 12px;
        margin-bottom: 8px;
        display: grid;
        grid-template-columns: 130px 1fr 1fr 28px;
        gap: 8px;
        align-items: center;
    }
    .btn-item .rm {
        width: 26px; height: 26px; border-radius: 6px;
        border: 0; background: #fee2e2; color: #dc2626;
        cursor: pointer;
    }

    /* Preview (live) */
    .preview-sticky { position: sticky; top: 20px; }
    .wa-bubble-wrap {
        background: #e5ddd5;
        padding: 24px 18px;
        border-radius: 12px;
        min-height: 360px;
    }
    .wa-bubble {
        background: #dcf8c6;
        border-radius: 12px;
        padding: 10px 13px;
        font-size: 14px;
        line-height: 1.45;
        max-width: 86%;
        box-shadow: 0 1px 2px rgba(0,0,0,.12);
        color: #1a1d23;
    }
    .wa-header { font-weight: 700; margin-bottom: 6px; color: #0f172a; }
    .wa-body   { white-space: pre-wrap; word-wrap: break-word; }
    .wa-footer { font-size: 12px; color: #64748b; margin-top: 6px; }
    .wa-buttons {
        display: flex; flex-direction: column; gap: 3px;
        margin-top: 8px;
        border-top: 1px solid rgba(0,0,0,.07);
        padding-top: 6px;
    }
    .wa-btn { text-align: center; color: #0085f3; font-size: 14px; padding: 6px 0; }
    .wa-var { background: #fef08a; padding: 1px 4px; border-radius: 3px; font-weight: 600; }

    .alert-err {
        background: #fef2f2; border: 1px solid #fecaca;
        border-radius: 10px; padding: 12px 14px; color: #991b1b;
        font-size: 13px; margin-bottom: 16px;
    }
    .alert-err ul { margin: 4px 0 0 18px; padding: 0; }
</style>
@endpush

@section('content')
<div class="page-container">

    @include('tenant.settings._tabs')

    <div style="margin-bottom:20px;display:flex;align-items:center;gap:12px;">
        <a href="{{ route('settings.whatsapp-templates.index') }}" style="color:#6b7280;text-decoration:none;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 2px;">
                {{ __('wa_templates.create') }}
            </h1>
            <p style="font-size:13px;color:#677489;margin:0;">{{ __('wa_templates.subtitle') }}</p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert-err">
            <strong>Erro:</strong>
            <ul>
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.whatsapp-templates.store') }}" id="tplForm">
        @csrf

        <div class="tpl-grid">
            <div>
                <div class="card">
                    <div class="card-head">1. Informações básicas</div>
                    <div class="card-body">
                        @if($instances->count() > 1)
                            <div class="form-row">
                                <label>{{ __('wa_templates.form_instance') }}</label>
                                <select name="whatsapp_instance_id" required>
                                    @foreach($instances as $i)
                                        <option value="{{ $i->id }}">{{ $i->label ?: $i->phone_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="whatsapp_instance_id" value="{{ $instances->first()->id }}">
                        @endif

                        <div class="form-row">
                            <label>{{ __('wa_templates.form_name') }}</label>
                            <input type="text" name="name" id="inputName" value="{{ old('name') }}"
                                   required maxlength="64"
                                   pattern="[a-z0-9_]+"
                                   placeholder="lembrete_consulta">
                            <div class="hint">{{ __('wa_templates.form_name_hint') }}</div>
                        </div>

                        <div class="form-row">
                            <label>{{ __('wa_templates.form_language') }}</label>
                            <select name="language" required>
                                <option value="pt_BR" selected>Português (BR)</option>
                                <option value="en_US">English (US)</option>
                                <option value="es_ES">Español (ES)</option>
                                <option value="es_MX">Español (MX)</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <label>{{ __('wa_templates.form_category') }}</label>
                            <div class="cat-cards">
                                <div class="cat-card active" data-cat="UTILITY">
                                    <span class="ico">✉️</span>
                                    <div class="title">{{ __('wa_templates.cat_utility') }}</div>
                                    <div class="desc">{{ __('wa_templates.cat_utility_desc') }}</div>
                                </div>
                                <div class="cat-card" data-cat="MARKETING">
                                    <span class="ico">🎁</span>
                                    <div class="title">{{ __('wa_templates.cat_marketing') }}</div>
                                    <div class="desc">{{ __('wa_templates.cat_marketing_desc') }}</div>
                                </div>
                                <div class="cat-card" data-cat="AUTHENTICATION">
                                    <span class="ico">🔒</span>
                                    <div class="title">{{ __('wa_templates.cat_authentication') }}</div>
                                    <div class="desc">{{ __('wa_templates.cat_authentication_desc') }}</div>
                                </div>
                            </div>
                            <input type="hidden" name="category" id="inputCategory" value="UTILITY">
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head">2. {{ __('wa_templates.form_header') }}</div>
                    <div class="card-body">
                        <div class="form-row">
                            <label>Tipo</label>
                            <select name="header[type]" id="headerType">
                                <option value="">{{ __('wa_templates.form_header_none') }}</option>
                                <option value="TEXT">{{ __('wa_templates.form_header_text') }}</option>
                                <option value="IMAGE">{{ __('wa_templates.form_header_image') }}</option>
                                <option value="VIDEO">{{ __('wa_templates.form_header_video') }}</option>
                                <option value="DOCUMENT">{{ __('wa_templates.form_header_doc') }}</option>
                            </select>
                        </div>
                        <div class="form-row" id="headerTextRow" style="display:none;">
                            <label>Texto do cabeçalho</label>
                            <input type="text" name="header[text]" id="headerText" maxlength="60">
                            <div class="hint">Máx 60 chars. Pode usar 1 variável {{ '{{1}}' }}.</div>
                        </div>
                        <div class="form-row" id="headerSampleRow" style="display:none;">
                            <label>Exemplo da variável do cabeçalho</label>
                            <input type="text" name="header[sample]" maxlength="60">
                        </div>
                        <div class="hint" id="headerMediaHint" style="display:none;">
                            {{ __('wa_templates.form_header_hint_media') }}
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head">3. {{ __('wa_templates.form_body') }}</div>
                    <div class="card-body">
                        <div class="form-row">
                            <label>
                                Corpo
                                <span class="char-counter"><span id="bodyLen">0</span>/1024</span>
                            </label>
                            <textarea name="body" id="inputBody" required maxlength="1024"
                                      placeholder="Olá {{ '{{1}}' }}, sua consulta é dia {{ '{{2}}' }}.">{{ old('body') }}</textarea>
                            <div class="hint">{{ __('wa_templates.form_body_hint') }}</div>
                        </div>

                        <div class="form-row" id="samplesRow" style="display:none;">
                            <label>{{ __('wa_templates.form_samples') }}</label>
                            <table class="sample-table" id="samplesTable"></table>
                            <div class="hint">{{ __('wa_templates.form_samples_hint') }}</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head">4. {{ __('wa_templates.form_footer') }}</div>
                    <div class="card-body">
                        <div class="form-row">
                            <input type="text" name="footer" id="inputFooter" maxlength="60" value="{{ old('footer') }}">
                            <div class="hint">{{ __('wa_templates.form_footer_hint') }}</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head">5. {{ __('wa_templates.form_buttons') }}</div>
                    <div class="card-body">
                        <div id="buttonsList"></div>
                        <button type="button" class="btn-secondary-sm" onclick="addButton()">
                            <i class="bi bi-plus-lg"></i> {{ __('wa_templates.form_add_button') }}
                        </button>
                    </div>
                </div>

                <div style="text-align:right;padding: 0 0 30px;">
                    <button type="submit" class="btn-primary-sm">
                        <i class="bi bi-send"></i> {{ __('wa_templates.form_submit') }}
                    </button>
                </div>
            </div>

            <div>
                <div class="preview-sticky">
                    <div class="card">
                        <div class="card-head">{{ __('wa_templates.preview_title') }}</div>
                        <div class="card-body" style="padding: 0;">
                            <div class="wa-bubble-wrap">
                                <div class="wa-bubble">
                                    <div class="wa-header" id="pvHeader" style="display:none;"></div>
                                    <div id="pvMediaBox" style="display:none;background:rgba(0,0,0,.06);border-radius:8px;padding:14px;text-align:center;color:#64748b;font-size:12px;margin-bottom:6px;"></div>
                                    <div class="wa-body" id="pvBody">Digite o corpo da mensagem...</div>
                                    <div class="wa-footer" id="pvFooter" style="display:none;"></div>
                                    <div class="wa-buttons" id="pvButtons" style="display:none;"></div>
                                </div>
                            </div>
                            <div style="padding: 10px 14px;font-size: 11.5px; color: #9ca3af;border-top:1px solid #f0f2f7;">
                                {{ __('wa_templates.preview_hint') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // Categoria — toggle cards
    document.querySelectorAll('.cat-card').forEach(el => {
        el.addEventListener('click', () => {
            document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('inputCategory').value = el.dataset.cat;
        });
    });

    // Header toggle
    const headerType      = document.getElementById('headerType');
    const headerTextRow   = document.getElementById('headerTextRow');
    const headerSampleRow = document.getElementById('headerSampleRow');
    const headerMediaHint = document.getElementById('headerMediaHint');
    const headerTextEl    = document.getElementById('headerText');

    function updateHeaderUi() {
        const v = headerType.value;
        headerTextRow.style.display   = v === 'TEXT' ? 'block' : 'none';
        headerMediaHint.style.display = (v === 'IMAGE' || v === 'VIDEO' || v === 'DOCUMENT') ? 'block' : 'none';
        updateHeaderSample();
        updatePreview();
    }
    headerType.addEventListener('change', updateHeaderUi);

    function updateHeaderSample() {
        const show = headerType.value === 'TEXT' && headerTextEl.value.includes('{{1}}');
        headerSampleRow.style.display = show ? 'block' : 'none';
    }
    headerTextEl.addEventListener('input', () => { updateHeaderSample(); updatePreview(); });

    // Body variables — detecta {{N}}, cria inputs de exemplo
    const bodyEl      = document.getElementById('inputBody');
    const bodyLen     = document.getElementById('bodyLen');
    const samplesRow  = document.getElementById('samplesRow');
    const samplesTbl  = document.getElementById('samplesTable');

    function extractVars(txt) {
        const m = txt.matchAll(/\{\{\s*(\d+)\s*\}\}/g);
        const set = new Set();
        for (const mm of m) set.add(parseInt(mm[1], 10));
        return [...set].sort((a, b) => a - b);
    }

    function updateSamples() {
        const ids = extractVars(bodyEl.value);
        if (ids.length === 0) {
            samplesRow.style.display = 'none';
            samplesTbl.innerHTML = '';
            return;
        }
        samplesRow.style.display = 'block';
        // Preserva valores já digitados
        const existing = {};
        samplesTbl.querySelectorAll('input').forEach(inp => {
            const k = inp.dataset.vid;
            if (k) existing[k] = inp.value;
        });
        samplesTbl.innerHTML = '';
        ids.forEach(id => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="vlabel">{{ '{{' }}${id}{{ '}}' }}</td>
                <td><input type="text" name="samples[${id}]" data-vid="${id}"
                           value="${(existing[id] || '').replace(/"/g, '&quot;')}"
                           placeholder="Exemplo pra variável ${id}"
                           style="width:100%;padding:7px 10px;border:1.5px solid #e8eaf0;border-radius:7px;font-size:13px;"></td>
            `;
            samplesTbl.appendChild(tr);
        });
    }

    bodyEl.addEventListener('input', () => {
        bodyLen.textContent = bodyEl.value.length;
        updateSamples();
        updatePreview();
    });

    // Footer preview
    document.getElementById('inputFooter').addEventListener('input', updatePreview);

    // Buttons
    let btnCounter = 0;
    function addButton() {
        const box = document.getElementById('buttonsList');
        if (box.children.length >= 10) return;
        const i = btnCounter++;
        const div = document.createElement('div');
        div.className = 'btn-item';
        div.dataset.idx = i;
        div.innerHTML = `
            <select name="buttons[${i}][type]" class="btn-type" onchange="updatePreview()">
                <option value="QUICK_REPLY">{{ __('wa_templates.form_btn_quick') }}</option>
                <option value="URL">{{ __('wa_templates.form_btn_url') }}</option>
                <option value="PHONE_NUMBER">{{ __('wa_templates.form_btn_phone') }}</option>
                <option value="COPY_CODE">{{ __('wa_templates.form_btn_copy') }}</option>
            </select>
            <input type="text" name="buttons[${i}][text]" placeholder="Texto do botão" maxlength="25" oninput="updatePreview()">
            <input type="text" name="buttons[${i}][url]" placeholder="URL / telefone / código" oninput="updatePreview()">
            <button type="button" class="rm" onclick="this.parentElement.remove(); updatePreview();">&times;</button>
        `;
        box.appendChild(div);
        updatePreview();
    }

    // Preview ao vivo
    const pvHeader  = document.getElementById('pvHeader');
    const pvBody    = document.getElementById('pvBody');
    const pvFooter  = document.getElementById('pvFooter');
    const pvButtons = document.getElementById('pvButtons');
    const pvMediaBox= document.getElementById('pvMediaBox');

    function highlightVars(txt) {
        const escaped = txt
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        return escaped.replace(/\{\{\s*(\d+)\s*\}\}/g, '<span class="wa-var">{{ '{{' }}$1{{ '}}' }}</span>');
    }

    function updatePreview() {
        // Header
        const hType = headerType.value;
        if (hType === 'TEXT' && headerTextEl.value.trim() !== '') {
            pvHeader.innerHTML = highlightVars(headerTextEl.value);
            pvHeader.style.display = 'block';
            pvMediaBox.style.display = 'none';
        } else if (hType === 'IMAGE' || hType === 'VIDEO' || hType === 'DOCUMENT') {
            pvMediaBox.textContent = hType.toLowerCase() + ' (a ser enviada pelo chat)';
            pvMediaBox.style.display = 'block';
            pvHeader.style.display = 'none';
        } else {
            pvHeader.style.display = 'none';
            pvMediaBox.style.display = 'none';
        }

        // Body
        const b = bodyEl.value.trim();
        pvBody.innerHTML = b ? highlightVars(b) : '<em style="color:#9ca3af;">Digite o corpo da mensagem...</em>';

        // Footer
        const f = document.getElementById('inputFooter').value.trim();
        if (f) { pvFooter.textContent = f; pvFooter.style.display = 'block'; }
        else   { pvFooter.style.display = 'none'; }

        // Buttons
        const items = document.querySelectorAll('.btn-item');
        if (items.length === 0) { pvButtons.style.display = 'none'; pvButtons.innerHTML = ''; }
        else {
            pvButtons.innerHTML = '';
            items.forEach(it => {
                const txt = it.querySelector('input[name$="[text]"]').value.trim() || 'Botão';
                const div = document.createElement('div');
                div.className = 'wa-btn';
                div.textContent = txt;
                pvButtons.appendChild(div);
            });
            pvButtons.style.display = 'flex';
        }
    }

    // Init
    bodyLen.textContent = bodyEl.value.length;
    updateSamples();
    updatePreview();
</script>
@endsection
