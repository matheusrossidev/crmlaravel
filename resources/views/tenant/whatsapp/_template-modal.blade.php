{{--
    Modal de envio de Message Template HSM (Cloud API only).
    Aberto via openTemplateModal() do notice de janela fechada ou attach menu.
    JS encapsulado — usa var activeConvId (global do index via var).
--}}
<div id="tplModalOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;"
     onclick="if(event.target===this) closeTemplateModal()">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
                width:min(620px,94vw);max-height:88vh;background:#fff;border-radius:14px;
                box-shadow:0 20px 60px rgba(0,0,0,.2);display:flex;flex-direction:column;overflow:hidden;">

        {{-- Header --}}
        <div style="padding:16px 22px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:15px;font-weight:700;color:#1a1d23;">{{ __('wa_templates.modal_title') }}</div>
                <div style="font-size:12.5px;color:#9ca3af;margin-top:2px;">{{ __('wa_templates.modal_select') }}</div>
            </div>
            <button onclick="closeTemplateModal()" style="background:none;border:0;font-size:18px;color:#6b7280;cursor:pointer;"><i class="bi bi-x-lg"></i></button>
        </div>

        <div style="flex:1;overflow-y:auto;padding:16px 22px;">

            {{-- Step 1: Tabs + search + list --}}
            <div id="tplSearchBox">
                {{-- Category tabs --}}
                <div id="tplCategoryTabs" style="display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap;">
                    <button type="button" data-cat="all" onclick="filterByCategory('all')" class="tpl-cat-tab tpl-cat-active" style="padding:6px 14px;border-radius:100px;border:1.5px solid #e5e7eb;background:#1a1d23;color:#fff;font-size:12px;font-weight:600;cursor:pointer;">
                        Todos <span class="tpl-cat-count" data-cat-count="all"></span>
                    </button>
                    <button type="button" data-cat="MARKETING" onclick="filterByCategory('MARKETING')" class="tpl-cat-tab" style="padding:6px 14px;border-radius:100px;border:1.5px solid #e5e7eb;background:#fff;color:#374151;font-size:12px;font-weight:600;cursor:pointer;">
                        Marketing <span class="tpl-cat-count" data-cat-count="MARKETING"></span>
                    </button>
                    <button type="button" data-cat="UTILITY" onclick="filterByCategory('UTILITY')" class="tpl-cat-tab" style="padding:6px 14px;border-radius:100px;border:1.5px solid #e5e7eb;background:#fff;color:#374151;font-size:12px;font-weight:600;cursor:pointer;">
                        Utility <span class="tpl-cat-count" data-cat-count="UTILITY"></span>
                    </button>
                    <button type="button" data-cat="AUTHENTICATION" onclick="filterByCategory('AUTHENTICATION')" class="tpl-cat-tab" style="padding:6px 14px;border-radius:100px;border:1.5px solid #e5e7eb;background:#fff;color:#374151;font-size:12px;font-weight:600;cursor:pointer;">
                        Auth <span class="tpl-cat-count" data-cat-count="AUTHENTICATION"></span>
                    </button>
                </div>

                <input type="text" id="tplSearchInput"
                       placeholder="{{ __('wa_templates.modal_search') }}"
                       oninput="filterTemplates()"
                       style="width:100%;padding:10px 12px;border:1.5px solid #e8eaf0;border-radius:9px;font-size:13.5px;margin-bottom:12px;">

                <div id="tplList" style="max-height:280px;overflow-y:auto;border:1px solid #e8eaf0;border-radius:10px;"></div>
                <div id="tplEmpty" style="display:none;padding:30px 20px;text-align:center;color:#9ca3af;font-size:13px;">
                    {{ __('wa_templates.modal_no_approved') }}
                </div>
            </div>

            {{-- Step 2: preview + vars --}}
            <div id="tplFillBox" style="display:none;">
                <button onclick="backToTemplateList()"
                        style="background:none;border:0;color:#0085f3;font-size:12.5px;font-weight:600;cursor:pointer;margin-bottom:12px;">
                    <i class="bi bi-arrow-left"></i> Voltar
                </button>

                <div style="display:flex;gap:16px;flex-wrap:wrap;">
                    {{-- Preview bolha WA --}}
                    <div style="flex:1;min-width:240px;">
                        <div style="background:#e5ddd5;padding:16px;border-radius:12px;min-height:120px;">
                            <div style="background:#dcf8c6;border-radius:10px;padding:12px 14px;font-size:13.5px;line-height:1.5;box-shadow:0 1px 2px rgba(0,0,0,.12);color:#1a1d23;max-width:320px;">
                                <div id="tplPvHeader" style="font-weight:700;margin-bottom:6px;display:none;"></div>
                                <div id="tplPvMediaIndicator" style="display:none;padding:8px;background:rgba(0,0,0,.06);border-radius:6px;margin-bottom:8px;font-size:12px;color:#64748b;text-align:center;">
                                    <i class="bi bi-image"></i> Midia
                                </div>
                                <div id="tplPvBody" style="white-space:pre-wrap;word-wrap:break-word;"></div>
                                <div id="tplPvFooter" style="font-size:11px;color:#64748b;margin-top:6px;display:none;"></div>
                            </div>
                            <div id="tplPvButtons" style="margin-top:6px;display:flex;flex-direction:column;gap:4px;max-width:320px;"></div>
                        </div>
                    </div>

                    {{-- Vars + media --}}
                    <div style="flex:1;min-width:220px;">
                        <div id="tplVarsBox"></div>
                        <div id="tplHeaderMediaBox" style="display:none;margin-top:10px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">
                                {{ __('wa_templates.modal_header_media') }} (URL)
                            </label>
                            <input type="url" id="tplHeaderMediaUrl"
                                   placeholder="https://..."
                                   style="width:100%;padding:9px 12px;border:1.5px solid #e8eaf0;border-radius:9px;font-size:13px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer actions --}}
        <div id="tplFooterActions" style="display:none;padding:14px 22px;border-top:1px solid #f0f2f7;text-align:right;">
            <button onclick="closeTemplateModal()"
                    style="background:#f3f4f6;color:#374151;border:0;padding:9px 16px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;margin-right:8px;">
                Cancelar
            </button>
            <button id="tplSendBtn" onclick="submitTemplate()"
                    style="background:#0085f3;color:#fff;border:0;padding:9px 20px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="bi bi-send"></i> {{ __('wa_templates.modal_send') }}
            </button>
        </div>
    </div>
</div>

<script>
    window.__tplState = {
        list: [],
        selected: null,
        activeInstanceId: null,
        activeCategory: 'all',
    };

    async function openTemplateModal() {
        if (typeof closeAttachMenu === 'function') closeAttachMenu();
        if (!activeConvId) return;
        document.getElementById('tplModalOverlay').style.display = 'block';
        backToTemplateList();
        await loadTemplatesForCurrentConv();
    }

    function closeTemplateModal() {
        document.getElementById('tplModalOverlay').style.display = 'none';
        window.__tplState.selected = null;
    }

    async function loadTemplatesForCurrentConv() {
        var listEl  = document.getElementById('tplList');
        var emptyEl = document.getElementById('tplEmpty');
        listEl.innerHTML = '<div style="padding:20px;text-align:center;color:#9ca3af;font-size:13px;">Carregando...</div>';
        emptyEl.style.display = 'none';

        var url = @json(route('chats.templates.list'))
            + (window.__tplState.activeInstanceId ? ('?instance_id=' + window.__tplState.activeInstanceId) : '');

        try {
            var res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            var data = await res.json();
            window.__tplState.list = data.templates || [];
            window.__tplState.activeCategory = 'all';
            updateCategoryTabs();
            filterTemplates();
        } catch (e) {
            listEl.innerHTML = '<div style="padding:20px;text-align:center;color:#ef4444;font-size:13px;">Erro ao carregar templates.</div>';
        }
    }

    function updateCategoryTabs() {
        var all = window.__tplState.list;
        var counts = { all: all.length, MARKETING: 0, UTILITY: 0, AUTHENTICATION: 0 };
        all.forEach(function(t) { var c = (t.category || '').toUpperCase(); if (counts[c] !== undefined) counts[c]++; });

        document.querySelectorAll('.tpl-cat-count').forEach(function(el) {
            var cat = el.dataset.catCount;
            el.textContent = counts[cat] !== undefined ? '(' + counts[cat] + ')' : '';
        });

        document.querySelectorAll('.tpl-cat-tab').forEach(function(btn) {
            var isActive = btn.dataset.cat === window.__tplState.activeCategory;
            btn.style.background = isActive ? '#1a1d23' : '#fff';
            btn.style.color = isActive ? '#fff' : '#374151';
        });
    }

    function filterByCategory(cat) {
        window.__tplState.activeCategory = cat;
        updateCategoryTabs();
        filterTemplates();
    }

    function filterTemplates() {
        var needle = (document.getElementById('tplSearchInput').value || '').toLowerCase().trim();
        var cat    = window.__tplState.activeCategory;
        var items  = window.__tplState.list;

        if (cat !== 'all') {
            items = items.filter(function(t) { return (t.category || '').toUpperCase() === cat; });
        }
        if (needle) {
            items = items.filter(function(t) {
                return t.name.toLowerCase().includes(needle) || (t.language || '').toLowerCase().includes(needle);
            });
        }
        renderTemplateList(items);
    }

    function renderTemplateList(items) {
        var listEl  = document.getElementById('tplList');
        var emptyEl = document.getElementById('tplEmpty');

        if (!items || items.length === 0) {
            listEl.innerHTML = '';
            emptyEl.style.display = 'block';
            return;
        }
        emptyEl.style.display = 'none';

        var catColors = { marketing: '#8b5cf6', utility: '#0ea5e9', authentication: '#f59e0b' };

        listEl.innerHTML = items.map(function(t) {
            var cat = (t.category || '').toLowerCase();
            var catColor = catColors[cat] || '#6b7280';
            var bodyPreview = '';
            (t.components || []).forEach(function(c) {
                if ((c.type || '').toUpperCase() === 'BODY') bodyPreview = (c.text || '').substring(0, 80);
            });
            if (bodyPreview.length >= 80) bodyPreview += '...';

            return '<div onclick="selectTemplate(' + t.id + ')"'
                + ' style="padding:12px 14px;border-bottom:1px solid #f0f2f7;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:10px;"'
                + ' onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'#fff\'">'
                + '<div style="flex:1;min-width:0;">'
                + '<div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">'
                + '<span style="font-size:13.5px;font-weight:600;color:#1a1d23;">' + escapeHtml(t.name) + '</span>'
                + '<span style="font-size:10px;font-weight:600;color:' + catColor + ';background:' + catColor + '15;padding:2px 7px;border-radius:4px;text-transform:uppercase;">' + escapeHtml(cat) + '</span>'
                + '</div>'
                + (bodyPreview ? '<div style="font-size:12px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(bodyPreview) + '</div>' : '')
                + '<div style="font-size:11px;color:#9ca3af;margin-top:2px;">' + escapeHtml(t.language) + '</div>'
                + '</div>'
                + '<i class="bi bi-chevron-right" style="color:#cbd5e1;flex-shrink:0;"></i>'
                + '</div>';
        }).join('');
    }

    function selectTemplate(id) {
        var t = window.__tplState.list.find(function(x) { return x.id === id; });
        if (!t) return;
        window.__tplState.selected = t;

        document.getElementById('tplSearchBox').style.display = 'none';
        document.getElementById('tplFillBox').style.display = 'block';
        document.getElementById('tplFooterActions').style.display = 'block';

        var bodyTxt = '', headerText = '', headerFormat = null, footerTxt = '', buttons = [];
        (t.components || []).forEach(function(c) {
            var type = (c.type || '').toUpperCase();
            if (type === 'BODY')    bodyTxt    = c.text || '';
            if (type === 'HEADER')  { headerText = c.text || ''; headerFormat = (c.format || 'TEXT').toUpperCase(); }
            if (type === 'FOOTER')  footerTxt  = c.text || '';
            if (type === 'BUTTONS') buttons    = c.buttons || [];
        });

        // Vars inputs
        var varsBox = document.getElementById('tplVarsBox');
        var ids = t.variables || [];
        if (ids.length === 0) {
            varsBox.innerHTML = '';
        } else {
            varsBox.innerHTML = '<div style="font-size:12px;font-weight:600;color:#374151;margin-bottom:8px;">{{ __("wa_templates.modal_variables") }}</div>'
                + ids.map(function(vid) {
                    return '<div style="margin-bottom:8px;">'
                        + '<label style="font-family:monospace;font-size:11px;color:#6b7280;">{{' + vid + '}}</label>'
                        + '<input type="text" class="tpl-var-input" data-vid="' + vid + '" oninput="updateTemplatePreview()"'
                        + ' placeholder="Valor pra {{' + vid + '}}"'
                        + ' style="width:100%;padding:8px 10px;border:1.5px solid #e8eaf0;border-radius:8px;font-size:13px;margin-top:3px;">'
                        + '</div>';
                }).join('');
        }

        // Header media
        if (['IMAGE', 'VIDEO', 'DOCUMENT'].includes(headerFormat)) {
            document.getElementById('tplHeaderMediaBox').style.display = 'block';
        } else {
            document.getElementById('tplHeaderMediaBox').style.display = 'none';
        }

        window.__tplState.bodyTxt      = bodyTxt;
        window.__tplState.headerText   = headerText;
        window.__tplState.headerFormat = headerFormat;
        window.__tplState.footerTxt    = footerTxt;
        window.__tplState.buttons      = buttons;

        updateTemplatePreview();
    }

    function updateTemplatePreview() {
        var vars = {};
        document.querySelectorAll('.tpl-var-input').forEach(function(inp) {
            vars[inp.dataset.vid] = inp.value;
        });

        var replace = function(txt) { return (txt || '').replace(/\{\{\s*(\d+)\s*\}\}/g, function(_, k) { return vars[k] || '{{' + k + '}}'; }); };

        var pvBody = document.getElementById('tplPvBody');
        var pvHead = document.getElementById('tplPvHeader');
        var pvFoot = document.getElementById('tplPvFooter');
        var pvMedia = document.getElementById('tplPvMediaIndicator');
        var pvBtns = document.getElementById('tplPvButtons');

        pvBody.textContent = replace(window.__tplState.bodyTxt || '');

        var hfmt = window.__tplState.headerFormat;
        if (hfmt === 'TEXT' && window.__tplState.headerText) {
            pvHead.textContent = replace(window.__tplState.headerText);
            pvHead.style.display = 'block';
            pvMedia.style.display = 'none';
        } else if (['IMAGE', 'VIDEO', 'DOCUMENT'].includes(hfmt)) {
            pvHead.style.display = 'none';
            pvMedia.style.display = 'block';
            var icons = { IMAGE: 'bi-image', VIDEO: 'bi-camera-video', DOCUMENT: 'bi-file-earmark' };
            pvMedia.innerHTML = '<i class="bi ' + (icons[hfmt] || 'bi-image') + '"></i> ' + hfmt.charAt(0) + hfmt.slice(1).toLowerCase();
        } else {
            pvHead.style.display = 'none';
            pvMedia.style.display = 'none';
        }

        if (window.__tplState.footerTxt) {
            pvFoot.textContent = window.__tplState.footerTxt;
            pvFoot.style.display = 'block';
        } else {
            pvFoot.style.display = 'none';
        }

        // Botoes
        var btns = window.__tplState.buttons || [];
        if (btns.length > 0) {
            pvBtns.innerHTML = btns.map(function(b) {
                var icon = b.type === 'URL' ? 'bi-box-arrow-up-right' : (b.type === 'PHONE_NUMBER' ? 'bi-telephone' : 'bi-reply');
                return '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:8px 12px;text-align:center;font-size:12.5px;font-weight:600;color:#0085f3;display:flex;align-items:center;justify-content:center;gap:6px;">'
                    + '<i class="bi ' + icon + '" style="font-size:11px;"></i> ' + escapeHtml(b.text || '')
                    + '</div>';
            }).join('');
            pvBtns.style.display = 'flex';
        } else {
            pvBtns.innerHTML = '';
            pvBtns.style.display = 'none';
        }
    }

    function backToTemplateList() {
        document.getElementById('tplSearchBox').style.display = 'block';
        document.getElementById('tplFillBox').style.display = 'none';
        document.getElementById('tplFooterActions').style.display = 'none';
        window.__tplState.selected = null;
        document.getElementById('tplSearchInput').value = '';
    }

    async function submitTemplate() {
        var t = window.__tplState.selected;
        if (!t || !activeConvId) return;

        var vars = {};
        document.querySelectorAll('.tpl-var-input').forEach(function(inp) {
            vars[inp.dataset.vid] = inp.value.trim();
        });

        var headerUrl = document.getElementById('tplHeaderMediaUrl').value.trim();
        var headerMedia = headerUrl ? { link: headerUrl } : null;

        var btn = document.getElementById('tplSendBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando...';

        try {
            var url = @json(url('/chats/conversations')) + '/' + activeConvId + '/send-template';
            var res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    template_id: t.id,
                    variables: vars,
                    header_media: headerMedia,
                }),
            });

            var data = await res.json();
            if (!res.ok || !data.success) {
                toastr.error(data.error || 'Falha ao enviar template.');
            } else {
                toastr.success(@json(__('wa_templates.toast_sent')));
                closeTemplateModal();
                if (typeof openConversation === 'function') {
                    openConversation(activeConvId, activeConvChannel || 'whatsapp');
                }
            }
        } catch (e) {
            toastr.error('Erro: ' + (e.message || 'falha na requisicao'));
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send"></i> {{ __("wa_templates.modal_send") }}';
        }
    }

    /**
     * Atualiza UI do compose baseado nos dados da conversa (provider + janela 24h).
     */
    window.setConvCloudApiState = function (data) {
        var btnTemplate = document.getElementById('btnTemplate');
        var notice      = document.getElementById('waWindowClosedNotice');
        var normalRow   = document.getElementById('normalRow');

        var isCloud = (data && data.provider === 'cloud_api');

        if (!btnTemplate || !notice) return;

        window.__tplState.activeInstanceId = data?.instance_id || null;

        if (!isCloud) {
            btnTemplate.style.display = 'none';
            notice.style.display = 'none';
            if (normalRow) normalRow.style.display = '';
            return;
        }

        btnTemplate.style.display = '';

        var closed = true;
        if (data.last_inbound_at) {
            var diffMs = Date.now() - new Date(data.last_inbound_at).getTime();
            closed = diffMs >= 24 * 60 * 60 * 1000;
        }

        if (closed) {
            notice.style.display = 'block';
            if (normalRow) normalRow.style.display = 'none';
        } else {
            notice.style.display = 'none';
            if (normalRow) normalRow.style.display = '';
        }
    };
</script>
