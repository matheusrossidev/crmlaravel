{{--
    Modal de envio de Message Template HSM (Cloud API only).
    Aberto via openTemplateModal() do attach menu ou do notice de janela fechada.
--}}
<div id="tplModalOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;"
     onclick="if(event.target===this) closeTemplateModal()">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
                width:min(560px,94vw);max-height:88vh;background:#fff;border-radius:14px;
                box-shadow:0 20px 60px rgba(0,0,0,.2);display:flex;flex-direction:column;overflow:hidden;">
        <div style="padding:16px 22px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:15px;font-weight:700;color:#1a1d23;">{{ __('wa_templates.modal_title') }}</div>
                <div style="font-size:12.5px;color:#9ca3af;margin-top:2px;">{{ __('wa_templates.modal_select') }}</div>
            </div>
            <button onclick="closeTemplateModal()" style="background:none;border:0;font-size:18px;color:#6b7280;cursor:pointer;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div style="flex:1;overflow-y:auto;padding:16px 22px;">
            {{-- Step 1: search + list --}}
            <div id="tplSearchBox">
                <input type="text" id="tplSearchInput"
                       placeholder="{{ __('wa_templates.modal_search') }}"
                       oninput="filterTemplates(this.value)"
                       style="width:100%;padding:10px 12px;border:1.5px solid #e8eaf0;border-radius:9px;font-size:13.5px;margin-bottom:12px;">
                <div id="tplList" style="max-height:280px;overflow-y:auto;border:1px solid #e8eaf0;border-radius:10px;"></div>
                <div id="tplEmpty" style="display:none;padding:30px 20px;text-align:center;color:#9ca3af;font-size:13px;">
                    {{ __('wa_templates.modal_no_approved') }}
                </div>
            </div>

            {{-- Step 2: preencher vars + preview --}}
            <div id="tplFillBox" style="display:none;">
                <button onclick="backToTemplateList()"
                        style="background:none;border:0;color:#0085f3;font-size:12.5px;font-weight:600;cursor:pointer;margin-bottom:12px;">
                    <i class="bi bi-arrow-left"></i> Voltar
                </button>

                <div style="background:#e5ddd5;padding:16px;border-radius:10px;margin-bottom:14px;">
                    <div style="background:#dcf8c6;border-radius:10px;padding:10px 13px;font-size:13.5px;line-height:1.45;box-shadow:0 1px 2px rgba(0,0,0,.12);color:#1a1d23;">
                        <div id="tplPvHeader" style="font-weight:700;margin-bottom:6px;display:none;"></div>
                        <div id="tplPvBody" style="white-space:pre-wrap;word-wrap:break-word;"></div>
                        <div id="tplPvFooter" style="font-size:11.5px;color:#64748b;margin-top:6px;display:none;"></div>
                    </div>
                </div>

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
    };

    async function openTemplateModal() {
        closeAttachMenu();
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
        const listEl  = document.getElementById('tplList');
        const emptyEl = document.getElementById('tplEmpty');
        listEl.innerHTML = '<div style="padding:20px;text-align:center;color:#9ca3af;font-size:13px;">Carregando...</div>';
        emptyEl.style.display = 'none';

        const url = @json(route('chats.templates.list'))
            + (window.__tplState.activeInstanceId ? ('?instance_id=' + window.__tplState.activeInstanceId) : '');

        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            window.__tplState.list = data.templates || [];
            renderTemplateList(window.__tplState.list);
        } catch (e) {
            listEl.innerHTML = '<div style="padding:20px;text-align:center;color:#ef4444;font-size:13px;">Erro ao carregar templates.</div>';
        }
    }

    function renderTemplateList(items) {
        const listEl  = document.getElementById('tplList');
        const emptyEl = document.getElementById('tplEmpty');

        if (!items || items.length === 0) {
            listEl.innerHTML = '';
            emptyEl.style.display = 'block';
            return;
        }
        emptyEl.style.display = 'none';

        listEl.innerHTML = items.map(t => {
            const cat = (t.category || '').toLowerCase();
            return `
                <div onclick="selectTemplate(${t.id})"
                     style="padding:11px 14px;border-bottom:1px solid #f0f2f7;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:10px;"
                     onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13.5px;font-weight:600;color:#1a1d23;">${window.escapeHtml(t.name)}</div>
                        <div style="font-size:11.5px;color:#9ca3af;font-family:monospace;">${window.escapeHtml(t.language)} · ${cat}</div>
                    </div>
                    <i class="bi bi-chevron-right" style="color:#cbd5e1;"></i>
                </div>
            `;
        }).join('');
    }

    function filterTemplates(q) {
        const needle = (q || '').toLowerCase().trim();
        if (!needle) { renderTemplateList(window.__tplState.list); return; }
        const filtered = window.__tplState.list.filter(t =>
            t.name.toLowerCase().includes(needle) || (t.language || '').toLowerCase().includes(needle)
        );
        renderTemplateList(filtered);
    }

    function selectTemplate(id) {
        const t = window.__tplState.list.find(x => x.id === id);
        if (!t) return;
        window.__tplState.selected = t;

        document.getElementById('tplSearchBox').style.display = 'none';
        document.getElementById('tplFillBox').style.display = 'block';
        document.getElementById('tplFooterActions').style.display = 'block';

        // Find components
        let bodyTxt = '', headerText = '', headerFormat = null, footerTxt = '';
        (t.components || []).forEach(c => {
            const type = (c.type || '').toUpperCase();
            if (type === 'BODY')   bodyTxt   = c.text || '';
            if (type === 'HEADER') { headerText = c.text || ''; headerFormat = (c.format || 'TEXT').toUpperCase(); }
            if (type === 'FOOTER') footerTxt = c.text || '';
        });

        // Vars inputs
        const varsBox = document.getElementById('tplVarsBox');
        const ids = t.variables || [];
        if (ids.length === 0) {
            varsBox.innerHTML = '';
        } else {
            varsBox.innerHTML = `<div style="font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">{{ __('wa_templates.modal_variables') }}</div>` +
                ids.map(id => `
                    <div style="margin-bottom:8px;">
                        <label style="font-family:monospace;font-size:11px;color:#6b7280;">${'{' + '{'}${id}${'}' + '}'}</label>
                        <input type="text" class="tpl-var-input" data-vid="${id}"
                               oninput="updateTemplatePreview()"
                               placeholder="Valor pra ${'{' + '{'}${id}${'}' + '}'}"
                               style="width:100%;padding:8px 10px;border:1.5px solid #e8eaf0;border-radius:8px;font-size:13px;">
                    </div>
                `).join('');
        }

        // Header media box
        if (['IMAGE', 'VIDEO', 'DOCUMENT'].includes(headerFormat)) {
            document.getElementById('tplHeaderMediaBox').style.display = 'block';
        } else {
            document.getElementById('tplHeaderMediaBox').style.display = 'none';
        }

        // Store raw body/header/footer pra preview
        window.__tplState.bodyTxt     = bodyTxt;
        window.__tplState.headerText  = headerText;
        window.__tplState.headerFormat= headerFormat;
        window.__tplState.footerTxt   = footerTxt;

        updateTemplatePreview();
    }

    function updateTemplatePreview() {
        const vars = {};
        document.querySelectorAll('.tpl-var-input').forEach(inp => {
            vars[inp.dataset.vid] = inp.value;
        });

        const pvBody = document.getElementById('tplPvBody');
        const pvHead = document.getElementById('tplPvHeader');
        const pvFoot = document.getElementById('tplPvFooter');

        const replace = (txt) => (txt || '').replace(/\{\{\s*(\d+)\s*\}\}/g, (_, k) => (vars[k] || '{{' + k + '}}'));

        pvBody.textContent = replace(window.__tplState.bodyTxt || '');

        const hfmt = window.__tplState.headerFormat;
        if (hfmt === 'TEXT' && window.__tplState.headerText) {
            pvHead.textContent = replace(window.__tplState.headerText);
            pvHead.style.display = 'block';
        } else {
            pvHead.style.display = 'none';
        }

        if (window.__tplState.footerTxt) {
            pvFoot.textContent = window.__tplState.footerTxt;
            pvFoot.style.display = 'block';
        } else {
            pvFoot.style.display = 'none';
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
        const t = window.__tplState.selected;
        if (!t || !activeConvId) return;

        const vars = {};
        document.querySelectorAll('.tpl-var-input').forEach(inp => {
            vars[inp.dataset.vid] = inp.value.trim();
        });

        const headerUrl = document.getElementById('tplHeaderMediaUrl').value.trim();
        const headerMedia = headerUrl ? { link: headerUrl } : null;

        const btn = document.getElementById('tplSendBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando...';

        try {
            const url = @json(url('/chats/conversations')) + '/' + activeConvId + '/send-template';
            const res = await fetch(url, {
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

            const data = await res.json();
            if (!res.ok || !data.success) {
                toastr.error(data.error || 'Falha ao enviar template.');
            } else {
                toastr.success(@json(__('wa_templates.toast_sent')));
                closeTemplateModal();
                // recarrega a conversa pra mostrar a mensagem nova
                if (typeof openConversation === 'function') {
                    openConversation(activeConvId, activeConvChannel || 'whatsapp');
                }
            }
        } catch (e) {
            toastr.error('Erro: ' + (e.message || 'falha na requisição'));
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send"></i> {{ __('wa_templates.modal_send') }}';
        }
    }

    /**
     * Atualiza UI do compose baseado nos dados da conversa (provider + janela 24h).
     * Chamada quando uma conversa é aberta via openConversation() — o hook é feito
     * mais abaixo no script principal via setConvCloudApiState().
     */
    window.setConvCloudApiState = function (data) {
        const btnTemplate = document.getElementById('btnTemplate');
        const notice      = document.getElementById('waWindowClosedNotice');
        const normalRow   = document.getElementById('normalRow');

        const isCloud = (data && data.provider === 'cloud_api');

        if (!btnTemplate || !notice) return;

        window.__tplState.activeInstanceId = data?.instance_id || null;

        if (!isCloud) {
            btnTemplate.style.display = 'none';
            notice.style.display = 'none';
            if (normalRow) normalRow.style.display = '';
            return;
        }

        // Cloud API: sempre mostra botão "Template" no attach menu
        btnTemplate.style.display = '';

        // Detecta janela 24h
        let closed = true;
        if (data.last_inbound_at) {
            const diffMs = Date.now() - new Date(data.last_inbound_at).getTime();
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
