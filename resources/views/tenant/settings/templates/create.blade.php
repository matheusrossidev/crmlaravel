@extends('tenant.layouts.app')

@php
    $title    = __('wa_templates.create');
    $pageIcon = 'chat-dots';
@endphp

@push('styles')
<style>
    .tpl-grid { display: grid; grid-template-columns: 70% 30%; gap: 24px; }
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
    .sample-table .vlabel {
        font-size: 12px; color: #1a1d23; font-weight: 600;
        width: 150px; padding-right: 12px;
        display: flex; align-items: center; gap: 6px;
    }
    .sample-table .vlabel i { color: #0085f3; font-size: 14px; }

    /* Botões de variáveis */
    .var-btn {
        display: inline-flex; align-items: center; gap: 5px;
        background: #eff6ff; color: #0085f3;
        border: 1.5px solid #bfdbfe;
        padding: 6px 12px; border-radius: 8px;
        font-size: 12px; font-weight: 600;
        cursor: pointer; transition: all .15s;
        font-family: inherit;
    }
    .var-btn:hover { background: #dbeafe; border-color: #93c5fd; }
    .var-btn i { font-size: 13px; }
    .var-btn-custom { background: #f3f4f6; color: #374151; border-color: #d1d5db; }
    .var-btn-custom:hover { background: #e5e7eb; }

    /* Destaque visual das variáveis no preview */
    .wa-var {
        background: #fef08a;
        padding: 1px 5px;
        border-radius: 4px;
        font-weight: 600;
        color: #713f12;
    }

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
    .btn-item > input,
    .btn-item > select {
        width: 100%;
        padding: 8px 10px;
        border: 1.5px solid #e8eaf0;
        border-radius: 8px;
        font-size: 13px;
        color: #1a1d23;
        background: #fff;
        font-family: inherit;
    }
    .btn-item > input:focus,
    .btn-item > select:focus { border-color: #0085f3; outline: none; }
    .btn-item .rm {
        width: 28px; height: 28px; border-radius: 7px;
        border: 0; background: #fee2e2; color: #dc2626;
        cursor: pointer; font-size: 16px; line-height: 1;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .btn-item .rm:hover { background: #fecaca; }

    /* Preview (live) — sticky + iPhone clay */
    .preview-sticky { position: sticky; top: 80px; align-self: start; }

    /* iPhone clay (plano, arredondado, sombras suaves) */
    .iphone-frame {
        width: 100%;
        max-width: 280px;
        margin: 0 auto;
        aspect-ratio: 9 / 18.5;
        background: linear-gradient(145deg, #e6e9f0 0%, #d7dce8 100%);
        border-radius: 38px;
        padding: 10px;
        box-shadow:
            0 30px 50px -20px rgba(15, 23, 42, .25),
            0 12px 24px -8px rgba(15, 23, 42, .15),
            inset 0 2px 4px rgba(255, 255, 255, .6),
            inset 0 -2px 4px rgba(15, 23, 42, .08);
        position: relative;
    }
    .iphone-frame::before {
        content: '';
        position: absolute;
        top: 14px; left: 50%; transform: translateX(-50%);
        width: 72px; height: 20px;
        background: #1a1d23;
        border-radius: 14px;
        z-index: 3;
    }
    .iphone-screen {
        width: 100%; height: 100%;
        border-radius: 30px;
        overflow: hidden;
        background: #f0f2f5;
        position: relative;
        display: flex;
        flex-direction: column;
    }
    .iphone-topbar {
        flex-shrink: 0;
        background: #075e54;
        padding: 36px 14px 8px;
        display: flex; align-items: center; gap: 9px;
        color: #fff;
    }
    .iphone-topbar .avatar {
        width: 28px; height: 28px; border-radius: 50%;
        background: rgba(255, 255, 255, .25);
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 700;
    }
    .iphone-topbar .contact-name { font-size: 11.5px; font-weight: 600; line-height: 1.2; }
    .iphone-topbar .contact-status { font-size: 9px; opacity: .8; }

    .wa-bubble-wrap {
        flex: 1;
        background-image: url('{{ asset('images/mocks/whatsapp-background.png') }}');
        background-size: cover;
        background-position: center;
        padding: 14px 10px;
        overflow-y: auto;
    }

    .wa-bubble {
        background: #dcf8c6;
        border-radius: 8px;
        padding: 7px 9px 5px;
        font-size: 11.5px;
        line-height: 1.38;
        max-width: 88%;
        box-shadow: 0 1px 1px rgba(0, 0, 0, .12);
        color: #1a1d23;
        position: relative;
        margin-left: auto;
    }
    .wa-bubble::after {
        content: '';
        position: absolute;
        right: -5px; top: 0;
        width: 0; height: 0;
        border-top: 8px solid #dcf8c6;
        border-right: 6px solid transparent;
    }
    .wa-header { font-weight: 700; margin-bottom: 4px; color: #0f172a; font-size: 11.5px; }
    .wa-body   { white-space: pre-wrap; word-wrap: break-word; }
    .wa-footer { font-size: 9.5px; color: #64748b; margin-top: 4px; }
    .wa-buttons {
        display: flex; flex-direction: column; gap: 2px;
        margin-top: 6px;
        border-top: 1px solid rgba(0,0,0,.07);
        padding-top: 4px;
    }
    .wa-btn { text-align: center; color: #0085f3; font-size: 11px; padding: 4px 0; font-weight: 500; }
    .wa-var { background: #fef08a; padding: 0 3px; border-radius: 3px; font-weight: 600; }

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
                                    <span class="ico"><i class="bi bi-envelope" style="color:#0085f3;"></i></span>
                                    <div class="title">{{ __('wa_templates.cat_utility') }}</div>
                                    <div class="desc">{{ __('wa_templates.cat_utility_desc') }}</div>
                                </div>
                                <div class="cat-card" data-cat="MARKETING">
                                    <span class="ico"><i class="bi bi-megaphone" style="color:#9333ea;"></i></span>
                                    <div class="title">{{ __('wa_templates.cat_marketing') }}</div>
                                    <div class="desc">{{ __('wa_templates.cat_marketing_desc') }}</div>
                                </div>
                                <div class="cat-card" data-cat="AUTHENTICATION">
                                    <span class="ico"><i class="bi bi-shield-lock" style="color:#ea580c;"></i></span>
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
                            <div class="hint">Máx 60 chars. Pode usar 1 variável @{{1}}.</div>
                        </div>
                        <div class="form-row" id="headerSampleRow" style="display:none;">
                            <label>Exemplo da variável do cabeçalho</label>
                            <input type="text" name="header[sample]" maxlength="60">
                        </div>
                        <div class="form-row" id="headerMediaRow" style="display:none;">
                            <label>Mídia de exemplo (pra aprovação da Meta)</label>

                            <div id="sampleDropzone"
                                 style="border:2px dashed #d1d5db;border-radius:10px;padding:22px 16px;text-align:center;cursor:pointer;transition:all .2s;"
                                 onclick="document.getElementById('sampleFileInput').click()"
                                 ondragover="event.preventDefault();this.style.borderColor='#0085f3';this.style.background='#eff6ff';"
                                 ondragleave="this.style.borderColor='#d1d5db';this.style.background='';"
                                 ondrop="handleSampleDrop(event)">
                                <i class="bi bi-cloud-arrow-up" style="font-size:26px;color:#9ca3af;display:block;margin-bottom:6px;"></i>
                                <div style="font-size:13px;color:#6b7280;font-weight:600;">Arraste o arquivo ou clique pra escolher</div>
                                <div style="font-size:11.5px;color:#9ca3af;margin-top:3px;">JPG, PNG, WEBP, MP4 ou PDF — máx 16 MB</div>
                            </div>
                            <input type="file" id="sampleFileInput" style="display:none;"
                                   accept="image/jpeg,image/png,image/webp,video/mp4,application/pdf"
                                   onchange="uploadSampleFile(this.files[0])">

                            <div id="sampleUploadedBox" style="display:none;margin-top:10px;padding:10px 12px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:9px;display:none;align-items:center;gap:10px;">
                                <i class="bi bi-check-circle-fill" style="color:#059669;font-size:18px;"></i>
                                <div style="flex:1;min-width:0;">
                                    <div id="sampleUploadedName" style="font-size:12.5px;font-weight:600;color:#065f46;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                                    <div id="sampleUploadedSize" style="font-size:11px;color:#047857;"></div>
                                </div>
                                <button type="button" onclick="removeSample()"
                                        style="background:#fee2e2;color:#dc2626;border:0;width:26px;height:26px;border-radius:7px;cursor:pointer;font-size:14px;">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>

                            <input type="hidden" name="header[sample_handle]" id="headerMediaUrl">

                            <div class="hint" style="margin-top:8px;">
                                <i class="bi bi-info-circle"></i>
                                A Meta precisa de uma mídia de exemplo pra revisar. Ao enviar pro cliente no chat você escolhe a mídia real.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head">3. {{ __('wa_templates.form_body') }}</div>
                    <div class="card-body">
                        {{-- Botões de variáveis predefinidas: clica e insere no cursor --}}
                        <div style="margin-bottom:10px;">
                            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">
                                <i class="bi bi-plus-circle" style="color:#0085f3;"></i>
                                Inserir dado dinâmico no texto
                            </label>
                            <div id="varButtons" style="display:flex;flex-wrap:wrap;gap:6px;">
                                <button type="button" class="var-btn" data-label="Nome do cliente" onclick="insertVariable('Nome do cliente')">
                                    <i class="bi bi-person"></i> Nome do cliente
                                </button>
                                <button type="button" class="var-btn" data-label="Data" onclick="insertVariable('Data')">
                                    <i class="bi bi-calendar3"></i> Data
                                </button>
                                <button type="button" class="var-btn" data-label="Hora" onclick="insertVariable('Hora')">
                                    <i class="bi bi-clock"></i> Hora
                                </button>
                                <button type="button" class="var-btn" data-label="Empresa" onclick="insertVariable('Empresa')">
                                    <i class="bi bi-building"></i> Empresa
                                </button>
                                <button type="button" class="var-btn" data-label="Valor" onclick="insertVariable('Valor')">
                                    <i class="bi bi-currency-dollar"></i> Valor
                                </button>
                                <button type="button" class="var-btn" data-label="Código" onclick="insertVariable('Código')">
                                    <i class="bi bi-hash"></i> Código
                                </button>
                                <button type="button" class="var-btn" data-label="Link" onclick="insertVariable('Link')">
                                    <i class="bi bi-link-45deg"></i> Link
                                </button>
                                <button type="button" class="var-btn var-btn-custom" onclick="insertCustomVariable()">
                                    <i class="bi bi-plus"></i> Outro
                                </button>
                            </div>
                            <div style="font-size:11px;color:#9ca3af;margin-top:6px;line-height:1.4;">
                                Clique num botão pra inserir o dado no texto. Depois preencha um exemplo do que vai no lugar (é pra Meta entender seu template).
                            </div>
                        </div>

                        <div class="form-row">
                            <label>
                                Corpo
                                <span class="char-counter"><span id="bodyLen">0</span>/1024</span>
                            </label>
                            <textarea name="body" id="inputBody" required maxlength="1024"
                                      placeholder="Ex: Olá [Nome do cliente], sua consulta é dia [Data] às [Hora].">{{ old('body') }}</textarea>
                            <div class="hint">Os dados em colchetes [Nome do cliente] são substituídos pelo valor real quando você enviar a mensagem.</div>
                        </div>

                        <div class="form-row" id="samplesRow" style="display:none;">
                            <label>Exemplos do que vai em cada dado dinâmico</label>
                            <div style="font-size:11.5px;color:#6b7280;margin-bottom:8px;line-height:1.45;">
                                Pra Meta aprovar o template, preencha um exemplo real de cada dado dinâmico.
                            </div>
                            <table class="sample-table" id="samplesTable"></table>
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
                    <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;text-align:center;margin-bottom:14px;">
                        {{ __('wa_templates.preview_title') }}
                    </div>
                    <div class="iphone-frame">
                        <div class="iphone-screen">
                            <div class="iphone-topbar">
                                <div class="avatar"><i class="bi bi-person-fill"></i></div>
                                <div>
                                    <div class="contact-name">Cliente</div>
                                    <div class="contact-status">online</div>
                                </div>
                            </div>
                            <div class="wa-bubble-wrap">
                                <div class="wa-bubble">
                                    <div class="wa-header" id="pvHeader" style="display:none;"></div>
                                    <div id="pvMediaBox" style="display:none;background:rgba(0,0,0,.06);border-radius:6px;padding:10px;text-align:center;color:#64748b;font-size:10px;margin-bottom:4px;"></div>
                                    <div class="wa-body" id="pvBody">Digite o corpo da mensagem...</div>
                                    <div class="wa-footer" id="pvFooter" style="display:none;"></div>
                                    <div class="wa-buttons" id="pvButtons" style="display:none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="font-size:11.5px;color:#9ca3af;text-align:center;margin-top:14px;line-height:1.4;">
                        {{ __('wa_templates.preview_hint') }}
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Modal de variável custom (sem prompt nativo) --}}
<div id="customVarModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;align-items:center;justify-content:center;padding:20px;"
     onclick="if(event.target===this) closeCustomVarModal()">
    <div style="width:min(420px,100%);background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden;">
        <div style="padding:16px 22px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:15px;font-weight:700;color:#1a1d23;">Novo dado dinâmico</div>
                <div style="font-size:12.5px;color:#9ca3af;margin-top:2px;">Dê um nome claro pra esse dado (ex: Número do pedido).</div>
            </div>
            <button type="button" onclick="closeCustomVarModal()" style="background:none;border:0;font-size:18px;color:#6b7280;cursor:pointer;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div style="padding:18px 22px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Nome do dado</label>
            <input type="text" id="customVarInput" maxlength="40"
                   placeholder="Ex: Número do pedido"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();confirmCustomVariable();}else if(event.key==='Escape'){closeCustomVarModal();}"
                   style="width:100%;padding:10px 12px;border:1.5px solid #e8eaf0;border-radius:9px;font-size:13.5px;color:#1a1d23;background:#fff;font-family:inherit;">
            <div style="font-size:11px;color:#9ca3af;margin-top:6px;">Máx 40 caracteres.</div>
        </div>
        <div style="padding:14px 22px;border-top:1px solid #f0f2f7;display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" onclick="closeCustomVarModal()"
                    style="background:#f3f4f6;color:#374151;border:0;padding:9px 16px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                Cancelar
            </button>
            <button type="button" onclick="confirmCustomVariable()"
                    style="background:#0085f3;color:#fff;border:0;padding:9px 20px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="bi bi-plus-lg"></i> Adicionar
            </button>
        </div>
    </div>
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
    const headerTextEl    = document.getElementById('headerText');

    function updateHeaderUi() {
        const v = headerType.value;
        const isMedia = (v === 'IMAGE' || v === 'VIDEO' || v === 'DOCUMENT');
        headerTextRow.style.display  = v === 'TEXT' ? 'block' : 'none';
        document.getElementById('headerMediaRow').style.display = isMedia ? 'block' : 'none';
        updateHeaderSample();
        updatePreview();
    }
    headerType.addEventListener('change', updateHeaderUi);

    function updateHeaderSample() {
        const show = headerType.value === 'TEXT' && /\{\{\s*1\s*\}\}/.test(headerTextEl.value);
        headerSampleRow.style.display = show ? 'block' : 'none';
    }
    headerTextEl.addEventListener('input', () => { updateHeaderSample(); updatePreview(); });

    // Upload de mídia de exemplo (dropzone)
    function handleSampleDrop(ev) {
        ev.preventDefault();
        ev.currentTarget.style.borderColor = '#d1d5db';
        ev.currentTarget.style.background = '';
        const file = ev.dataTransfer.files[0];
        if (file) uploadSampleFile(file);
    }

    async function uploadSampleFile(file) {
        if (!file) return;
        const dz = document.getElementById('sampleDropzone');
        dz.style.opacity = '.6';

        const fd = new FormData();
        fd.append('file', file);

        try {
            const res = await fetch(@json(route('settings.whatsapp-templates.upload-sample')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: fd,
            });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || 'falha');

            document.getElementById('headerMediaUrl').value = data.url;
            document.getElementById('sampleUploadedName').textContent = data.original_name;
            document.getElementById('sampleUploadedSize').textContent = formatFileSize(data.size) + ' · ' + (data.mime || '');
            document.getElementById('sampleUploadedBox').style.display = 'flex';
            dz.style.display = 'none';
            updatePreview();
        } catch (e) {
            toastr.error('Erro no upload: ' + (e.message || 'falha'));
        } finally {
            dz.style.opacity = '1';
        }
    }

    function removeSample() {
        document.getElementById('headerMediaUrl').value = '';
        document.getElementById('sampleUploadedBox').style.display = 'none';
        document.getElementById('sampleDropzone').style.display = 'block';
        document.getElementById('sampleFileInput').value = '';
        updatePreview();
    }

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    // Body variables: detecta placeholders e cria inputs de exemplo dinamicamente
    const bodyEl      = document.getElementById('inputBody');
    const bodyLen     = document.getElementById('bodyLen');
    const samplesRow  = document.getElementById('samplesRow');
    const samplesTbl  = document.getElementById('samplesTable');

    // Mapping id -> label amigavel. Popula ao clicar em "Inserir dado dinamico".
    // Persiste em hidden input pra survive submit (Meta so precisa das chaves
    // duplas com numero no body, mas a UI mostra "Nome do cliente").
    const varLabels = {};  // { 1: 'Nome do cliente', 2: 'Data', ... }

    // Ícone por tipo de label
    const labelIcons = {
        'Nome do cliente': 'bi-person',
        'Data': 'bi-calendar3',
        'Hora': 'bi-clock',
        'Empresa': 'bi-building',
        'Valor': 'bi-currency-dollar',
        'Código': 'bi-hash',
        'Link': 'bi-link-45deg',
    };

    function insertVariable(label) {
        // Próximo ID disponível (1, 2, 3...)
        const ids = extractVars(bodyEl.value);
        const nextId = ids.length === 0 ? 1 : Math.max(...ids) + 1;
        varLabels[nextId] = label;

        // Insere no cursor (ou no fim se nenhum foco)
        const placeholder = '[' + label + ']';
        insertAtCursor(bodyEl, placeholder);

        // Troca o placeholder pela chave dupla numerada "real" — user ve
        // [Nome do cliente] mas no submit vai o formato que Meta aceita. Usa
        // data-attribute pra manter o mapping visual.
        bodyEl.value = bodyEl.value.replace(placeholder, '{' + '{' + nextId + '}' + '}');

        bodyLen.textContent = bodyEl.value.length;
        updateSamples();
        updatePreview();
    }

    function insertCustomVariable() {
        const modal = document.getElementById('customVarModal');
        const input = document.getElementById('customVarInput');
        input.value = '';
        modal.style.display = 'flex';
        setTimeout(() => input.focus(), 50);
    }

    function closeCustomVarModal() {
        document.getElementById('customVarModal').style.display = 'none';
    }

    function confirmCustomVariable() {
        const input = document.getElementById('customVarInput');
        const val = input.value.trim();
        if (!val) {
            input.focus();
            return;
        }
        closeCustomVarModal();
        insertVariable(val.substring(0, 40));
    }

    function insertAtCursor(el, text) {
        el.focus();
        const start = el.selectionStart;
        const end   = el.selectionEnd;
        el.value = el.value.substring(0, start) + text + el.value.substring(end);
        el.selectionStart = el.selectionEnd = start + text.length;
    }

    function extractVars(txt) {
        const m = txt.matchAll(/\{\{\s*(\d+)\s*\}\}/g);
        const set = new Set();
        for (const mm of m) set.add(parseInt(mm[1], 10));
        return [...set].sort((a, b) => a - b);
    }

    function labelFor(id) {
        return varLabels[id] || 'Dado ' + id;
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
        samplesTbl.querySelectorAll('input[type=text]').forEach(inp => {
            const k = inp.dataset.vid;
            if (k) existing[k] = inp.value;
        });

        samplesTbl.innerHTML = '';
        ids.forEach(id => {
            const label = labelFor(id);
            const icon  = labelIcons[label] || 'bi-braces';
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="vlabel"><i class="bi ${icon}"></i> ${label}</td>
                <td>
                    <input type="text" name="samples[${id}]" data-vid="${id}"
                           value="${(existing[id] || '').replace(/"/g, '&quot;')}"
                           placeholder="Ex: ${exampleFor(label)}"
                           style="width:100%;padding:7px 10px;border:1.5px solid #e8eaf0;border-radius:7px;font-size:13px;">
                    <input type="hidden" name="sample_labels[${id}]" value="${label.replace(/"/g, '&quot;')}">
                </td>
            `;
            samplesTbl.appendChild(tr);
        });
    }

    function exampleFor(label) {
        return {
            'Nome do cliente': 'Maria Silva',
            'Data':            '22/04/2026',
            'Hora':            '14h30',
            'Empresa':         'Acme Ltda',
            'Valor':           'R$ 150,00',
            'Código':          'ABC123',
            'Link':            'https://seusite.com/confirmar',
        }[label] || 'Exemplo real';
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
        // Usa labels amigaveis em vez do placeholder tecnico.
        return escaped.replace(/\{\{\s*(\d+)\s*\}\}/g, (_, id) => {
            const lbl = labelFor(parseInt(id, 10));
            const sample = exampleFor(lbl);
            return `<span class="wa-var">${sample}</span>`;
        });
    }

    function updatePreview() {
        // Header
        const hType = headerType.value;
        const mediaUrl = document.getElementById('headerMediaUrl')?.value.trim() || '';

        if (hType === 'TEXT' && headerTextEl.value.trim() !== '') {
            pvHeader.innerHTML = highlightVars(headerTextEl.value);
            pvHeader.style.display = 'block';
            pvMediaBox.style.display = 'none';
        } else if (hType === 'IMAGE' && mediaUrl) {
            pvMediaBox.innerHTML = `<img src="${mediaUrl}" style="width:100%;border-radius:4px;display:block;" alt="" onerror="this.replaceWith(Object.assign(document.createElement('div'),{textContent:'imagem (preview)',style:'padding:12px;color:#94a3b8;font-size:10px;'}))">`;
            pvMediaBox.style.display = 'block';
            pvMediaBox.style.padding = '0';
            pvMediaBox.style.background = 'transparent';
            pvHeader.style.display = 'none';
        } else if (hType === 'IMAGE' || hType === 'VIDEO' || hType === 'DOCUMENT') {
            const ico = hType === 'VIDEO' ? 'bi-camera-video' : (hType === 'DOCUMENT' ? 'bi-file-earmark-text' : 'bi-image');
            pvMediaBox.innerHTML = `<i class="bi ${ico}" style="font-size:22px;color:#94a3b8;"></i><div style="margin-top:4px;">${hType.toLowerCase()}</div>`;
            pvMediaBox.style.display = 'block';
            pvMediaBox.style.padding = '14px';
            pvMediaBox.style.background = 'rgba(0,0,0,.06)';
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
