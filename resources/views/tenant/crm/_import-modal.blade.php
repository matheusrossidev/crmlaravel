{{-- Modal: Importar Leads (partial isolado — zero interferencia com kanban JS) --}}
<div id="modalImport" style="display:none;position:fixed;inset:0;z-index:1060;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div id="importModalBox" style="background:#fff;border-radius:16px;width:720px;max-width:96vw;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,.18);display:flex;flex-direction:column;max-height:90vh;">

        {{-- ── TELA A: Upload ─────────────────────────────────────────────── --}}
        <div id="importScreenUpload">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;">
                <div>
                    <h3 style="font-size:16px;font-weight:700;color:#1a1d23;margin:0 0 3px;">{{ __('crm.import_title') }}</h3>
                    <p id="importModalPipeline" style="font-size:12px;color:#9ca3af;margin:0;"></p>
                </div>
                <button onclick="closeImportModal()" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;line-height:1;padding:0;"><i class="bi bi-x-lg"></i></button>
            </div>

            <div style="background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:10px;padding:14px;margin-bottom:18px;display:flex;align-items:center;gap:12px;">
                <i class="bi bi-file-earmark-spreadsheet" style="font-size:24px;color:#0ea5e9;flex-shrink:0;"></i>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:12.5px;font-weight:600;color:#0369a1;margin:0 0 2px;">{{ __('crm.template_title') }}</p>
                    <p style="font-size:11.5px;color:#6b7280;margin:0;">{{ __('crm.template_desc') }}</p>
                </div>
                <a id="btnDownloadTemplate" href="#" class="btn-primary-sm" style="font-size:12px;padding:6px 14px;white-space:nowrap;text-decoration:none;">
                    <i class="bi bi-download"></i> {{ __('crm.download') }}
                </a>
            </div>

            <div style="margin-bottom:20px;">
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">{{ __('crm.select_file') }}</label>
                <input type="file" id="importFileInput" accept=".xlsx,.xls,.csv"
                       style="width:100%;padding:10px;border:1.5px dashed #d1d5db;border-radius:9px;font-size:13px;box-sizing:border-box;cursor:pointer;background:#fafafa;font-family:inherit;">
                <p style="font-size:11px;color:#9ca3af;margin:5px 0 0;">{{ __('crm.file_formats') }}</p>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button onclick="closeImportModal()" style="padding:9px 20px;border-radius:100px;border:1.5px solid #e8eaf0;background:#fff;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;font-family:inherit;">{{ __('crm.cancel') }}</button>
                <button id="btnImportUploadNext" onclick="window._importWizard.submitUpload()" style="padding:9px 24px;border-radius:100px;border:none;background:#0085f3;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:6px;">
                    <i class="bi bi-arrow-right"></i> {{ __('crm.preview') }}
                </button>
            </div>
        </div>

        {{-- ── TELA B: Mapeamento ─────────────────────────────────────────── --}}
        <div id="importScreenMapping" style="display:none;flex-direction:column;flex:1;min-height:0;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;flex-shrink:0;">
                <div>
                    <h3 style="font-size:16px;font-weight:700;color:#1a1d23;margin:0 0 3px;">Mapeamento de Colunas</h3>
                    <p style="font-size:12px;color:#6b7280;margin:0;">Associe cada coluna da planilha ao campo correspondente no CRM.</p>
                </div>
                <button onclick="closeImportModal()" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;line-height:1;padding:0;"><i class="bi bi-x-lg"></i></button>
            </div>
            <div id="mappingFieldsContainer" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:10px;padding-right:4px;"></div>
            <div style="display:flex;justify-content:space-between;margin-top:14px;flex-shrink:0;">
                <button type="button" onclick="window._importWizard.goToUpload()" style="padding:9px 18px;border:1.5px solid #e5e7eb;border-radius:100px;background:#fff;color:#6b7280;font-size:13px;font-weight:600;cursor:pointer;"><i class="bi bi-arrow-left"></i> Voltar</button>
                <button type="button" id="btnMappingNext" onclick="window._importWizard.submitMapping()" style="padding:9px 22px;border:none;border-radius:100px;background:#0085f3;color:#fff;font-size:13px;font-weight:600;cursor:pointer;"><i class="bi bi-arrow-right"></i> Continuar</button>
            </div>
        </div>

        {{-- ── TELA C: Review com acoes ───────────────────────────────────── --}}
        <div id="importScreenReview" style="display:none;flex-direction:column;flex:1;min-height:0;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px;flex-shrink:0;">
                <div>
                    <h3 style="font-size:16px;font-weight:700;color:#1a1d23;margin:0 0 3px;">{{ __('crm.preview_title') }}</h3>
                    <p id="importReviewSummary" style="font-size:12px;color:#6b7280;margin:0;"></p>
                </div>
                <button onclick="closeImportModal()" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;line-height:1;padding:0;"><i class="bi bi-x-lg"></i></button>
            </div>

            {{-- Barra bulk --}}
            <div id="importBulkBar" style="display:none;background:#f0f6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:10px 14px;margin-bottom:10px;flex-shrink:0;gap:8px;align-items:center;flex-wrap:wrap;">
                <span id="importBulkCount" style="font-size:12px;font-weight:600;color:#0085f3;white-space:nowrap;"></span>
                <select id="bulkStageSelect" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:12px;background:#fff;max-width:180px;"><option value="">Definir etapa...</option></select>
                <button type="button" onclick="window._importWizard.applyBulkStage()" style="padding:5px 12px;border:none;border-radius:8px;background:#0085f3;color:#fff;font-size:11.5px;font-weight:600;cursor:pointer;">Aplicar</button>
                <input id="bulkTagsInput" type="text" placeholder="Adicionar tags..." style="padding:6px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:12px;max-width:160px;">
                <button type="button" onclick="window._importWizard.applyBulkTags()" style="padding:5px 12px;border:none;border-radius:8px;background:#0085f3;color:#fff;font-size:11.5px;font-weight:600;cursor:pointer;">Aplicar</button>
                <button type="button" onclick="window._importWizard.applyBulkRemove()" style="padding:5px 12px;border:none;border-radius:8px;background:#ef4444;color:#fff;font-size:11.5px;font-weight:600;cursor:pointer;margin-left:auto;">Remover</button>
            </div>

            <div style="flex:1;overflow-y:auto;min-height:0;max-height:380px;border:1.5px solid #e8eaf0;border-radius:10px;">
                <table style="width:100%;border-collapse:collapse;font-size:12.5px;">
                    <thead style="position:sticky;top:0;background:#f8fafc;z-index:1;">
                        <tr>
                            <th style="padding:9px 8px;border-bottom:1.5px solid #e8eaf0;width:32px;"><input type="checkbox" id="importCheckAll" onchange="window._importWizard.toggleAll(this)"></th>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;">{{ __('crm.col_name') }}</th>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;">{{ __('crm.col_phone') }}</th>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;">{{ __('crm.col_email') }}</th>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;">{{ __('crm.col_value') }}</th>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;">{{ __('crm.col_stage') }}</th>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;">{{ __('crm.col_tags') }}</th>
                            <th style="padding:9px 12px;text-align:left;font-weight:700;color:#374151;border-bottom:1.5px solid #e8eaf0;">{{ __('crm.col_source') }}</th>
                        </tr>
                    </thead>
                    <tbody id="importReviewTbody"></tbody>
                </table>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px;flex-shrink:0;">
                <button onclick="window._importWizard.goToMapping()" style="padding:9px 20px;border-radius:9px;border:1.5px solid #e8eaf0;background:#fff;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:6px;"><i class="bi bi-arrow-left"></i> {{ __('crm.back') }}</button>
                <button id="btnImportConfirm" onclick="window._importWizard.confirmImport()" style="padding:9px 24px;border-radius:9px;border:none;background:#10B981;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:6px;"><i class="bi bi-check-circle"></i> {{ __('crm.confirm_import') }}</button>
            </div>
        </div>

    </div>
</div>

<script>
(function() {
    'use strict';

    const CFG = {
        previewUrl:  @json(route('crm.import.preview')),
        importUrl:   @json(route('crm.import')),
        templateUrl: @json(route('crm.template')),
        csrf: document.querySelector('meta[name="csrf-token"]').content,
    };

    const CRM_FIELDS = {
        nome:      'Nome *',
        telefone:  'Telefone',
        email:     'Email',
        valor:     'Valor',
        etapa:     'Etapa',
        tags:      'Tags',
        origem:    'Origem',
        empresa:   'Empresa',
        criado_em: 'Data de criacao',
    };

    // Le stages do DOM do kanban (zero @php)
    const STAGES = Array.from(document.querySelectorAll('.kanban-col[data-stage-id]')).map(function(col) {
        var titleEl = col.querySelector('.col-title');
        var name = '';
        if (titleEl) {
            var clone = titleEl.cloneNode(true);
            clone.querySelectorAll('i, .col-dot').forEach(function(el) { el.remove(); });
            name = clone.textContent.trim();
        }
        return { id: parseInt(col.dataset.stageId), name: name };
    });

    var _pipelineId = null;
    var _token      = null;
    var _rows       = [];
    var _overrides  = {};
    var _mappingData = null;

    function esc(s) { return typeof escapeHtml === 'function' ? escapeHtml(String(s || '')) : String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function showScreen(name) {
        ['Upload', 'Mapping', 'Review'].forEach(function(s) {
            var el = document.getElementById('importScreen' + s);
            if (el) el.style.display = (s === name) ? (s === 'Upload' ? '' : 'flex') : 'none';
        });
        document.getElementById('importModalBox').style.width = (name === 'Review') ? '960px' : '720px';
    }

    // ── OPEN / CLOSE (exposed via window) ──

    window.openImportModal = function() {
        var sel = document.getElementById('pipelineSelect');
        _pipelineId = sel ? sel.value : null;
        _token = null; _rows = []; _overrides = {};

        var pName = sel ? (sel.options[sel.selectedIndex]?.text || '') : '';
        document.getElementById('importModalPipeline').textContent = pName ? (typeof LANG !== 'undefined' ? LANG.pipeline_prefix.replace(':name', pName) : 'Funil: ' + pName) : '';
        document.getElementById('btnDownloadTemplate').href = CFG.templateUrl + '?pipeline_id=' + _pipelineId;
        document.getElementById('importFileInput').value = '';

        var btn = document.getElementById('btnImportUploadNext');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-right"></i> ' + (typeof LANG !== 'undefined' ? LANG.preview : 'Continuar');

        showScreen('Upload');
        document.getElementById('importBulkBar').style.display = 'none';
        document.getElementById('modalImport').style.display = 'flex';
    };

    window.closeImportModal = function() {
        document.getElementById('modalImport').style.display = 'none';
        _token = null;
    };

    document.getElementById('modalImport').addEventListener('click', function(e) {
        if (e.target === this) window.closeImportModal();
    });

    // ── STEP 1: Upload → headers ──

    window._importWizard = {};

    window._importWizard.submitUpload = async function() {
        var file = document.getElementById('importFileInput').files[0];
        if (!file) { toastr.warning(typeof LANG !== 'undefined' ? LANG.select_file_first : 'Selecione um arquivo.'); return; }
        if (!_pipelineId) { toastr.error('Selecione um funil.'); return; }

        var btn = document.getElementById('btnImportUploadNext');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Analisando...';

        var fd = new FormData();
        fd.append('file', file);
        fd.append('pipeline_id', _pipelineId);
        fd.append('_token', CFG.csrf);

        try {
            var res = await fetch(CFG.previewUrl, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
            var data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || 'Erro ao analisar arquivo.');
            _token = data.token;
            _mappingData = data;
            renderMapping(data);
        } catch (e) {
            toastr.error(e.message || 'Erro ao analisar arquivo. Verifique o formato.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-right"></i> Continuar';
        }
    };

    // ── STEP 2: Mapping ──

    function renderMapping(data) {
        var container = document.getElementById('mappingFieldsContainer');
        container.innerHTML = '';
        var headers = data.file_headers || [];
        var suggested = data.suggested_mapping || {};
        var fields = data.crm_fields || Object.keys(CRM_FIELDS);
        var customFields = data.custom_fields || [];

        function makeOpts(selected) {
            var html = '<option value="">-- Nao importar --</option>';
            headers.forEach(function(h) {
                html += '<option value="' + esc(h) + '"' + (h === selected ? ' selected' : '') + '>' + esc(h) + '</option>';
            });
            return html;
        }

        function addRow(key, label, required, selected) {
            var row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;gap:12px;padding:10px 14px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;';
            row.innerHTML = '<div style="min-width:160px;font-size:13px;font-weight:600;color:#1a1d23;">' + esc(label) + (required ? ' <span style="color:#ef4444;">*</span>' : '') + '</div>'
                + '<div style="flex:1;display:flex;align-items:center;gap:8px;"><i class="bi bi-arrow-left-right" style="color:#9ca3af;font-size:12px;"></i>'
                + '<select data-crm-field="' + key + '" style="flex:1;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:#fff;color:#1a1d23;">' + makeOpts(selected) + '</select></div>';
            container.appendChild(row);
        }

        fields.forEach(function(f) { addRow(f, CRM_FIELDS[f] || f, f === 'nome', suggested[f] || ''); });

        if (customFields.length) {
            var sep = document.createElement('div');
            sep.style.cssText = 'padding:8px 0 4px;font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;border-top:1px solid #e5e7eb;margin-top:6px;padding-top:14px;';
            sep.textContent = 'Campos extras';
            container.appendChild(sep);
            customFields.forEach(function(cf) { addRow(cf.key, cf.label, false, ''); });
        }

        showScreen('Mapping');
        document.getElementById('btnImportUploadNext').disabled = false;
        document.getElementById('btnImportUploadNext').innerHTML = '<i class="bi bi-arrow-right"></i> Continuar';
    }

    window._importWizard.goToUpload = function() { showScreen('Upload'); };

    window._importWizard.submitMapping = async function() {
        var selects = document.querySelectorAll('#mappingFieldsContainer select[data-crm-field]');
        var mapping = {};
        var nameOk = false;
        selects.forEach(function(sel) {
            if (sel.value) mapping[sel.dataset.crmField] = sel.value;
            if (sel.dataset.crmField === 'nome' && sel.value) nameOk = true;
        });
        if (!nameOk) { toastr.warning('Selecione qual coluna corresponde ao Nome.'); return; }

        var btn = document.getElementById('btnMappingNext');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Analisando...';

        try {
            var res = await fetch(CFG.previewUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CFG.csrf },
                body: JSON.stringify({ token: _token, mapping: mapping, pipeline_id: _pipelineId }),
            });
            var data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || 'Erro ao processar mapeamento.');
            _token = data.token;
            _rows = data.rows || [];
            _overrides = {};
            renderReview();
        } catch (e) {
            toastr.error(e.message || 'Erro ao processar.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-right"></i> Continuar';
        }
    };

    // ── STEP 3: Review ──

    function renderReview() {
        var bulkSel = document.getElementById('bulkStageSelect');
        bulkSel.innerHTML = '<option value="">Definir etapa...</option>';
        STAGES.forEach(function(s) { bulkSel.innerHTML += '<option value="' + s.id + '">' + esc(s.name) + '</option>'; });
        updateReviewTable();
        showScreen('Review');
    }

    function updateReviewTable() {
        var active = _rows.filter(function(r, i) { return !r.will_skip && !(_overrides[i] && _overrides[i].skip); });
        var skipped = _rows.length - active.length;

        var summary = '<strong style="color:#10B981;">' + active.length + '</strong> lead' + (active.length !== 1 ? 's' : '') + ' para importar';
        if (skipped) summary += ' — <strong style="color:#DC2626;">' + skipped + ' ignorado' + (skipped !== 1 ? 's' : '') + '</strong>';
        document.getElementById('importReviewSummary').innerHTML = summary;

        var stageOpts = '<option value="">--</option>';
        STAGES.forEach(function(s) { stageOpts += '<option value="' + s.id + '">' + esc(s.name) + '</option>'; });

        var tbody = document.getElementById('importReviewTbody');
        tbody.innerHTML = '';

        _rows.forEach(function(row, i) {
            var ov = _overrides[i] || {};
            var isSkip = row.will_skip || ov.skip;
            var tr = document.createElement('tr');
            tr.dataset.index = i;
            if (isSkip) { tr.style.background = '#FEF2F2'; tr.style.opacity = '0.5'; }
            else if (i % 2 === 0) { tr.style.background = '#fafafa'; }

            var nameHtml = row.will_skip ? '<span style="color:#DC2626;font-style:italic;">(sem nome)</span>' : (ov.skip ? '<s style="color:#9ca3af;">' + esc(row.name) + '</s>' : esc(row.name));

            var curStage = ov.stage_id || '';
            var stageSel = '<select onchange="window._importWizard.setRowStage(' + i + ',this.value)" style="padding:4px 8px;border:1px solid #e5e7eb;border-radius:6px;font-size:11.5px;background:#fff;max-width:140px;">';
            stageSel += '<option value="">' + (row.stage_raw || '(primeira)') + '</option>';
            STAGES.forEach(function(s) { stageSel += '<option value="' + s.id + '"' + (String(s.id) === String(curStage) ? ' selected' : '') + '>' + esc(s.name) + '</option>'; });
            stageSel += '</select>';

            var tags = ov.tags || row.tags || [];
            var tagsHtml = '<span onclick="window._importWizard.editRowTags(' + i + ')" style="cursor:pointer;color:#6b7280;font-size:11.5px;" title="Clique pra editar">' + (tags.length ? esc(tags.join(', ')) : '<span style=color:#d1d5db>--</span>') + '</span>';

            tr.innerHTML = '<td style="padding:6px 8px;border-bottom:1px solid #f0f2f7;text-align:center;">' + (isSkip ? '' : '<input type="checkbox" class="import-row-check" data-idx="' + i + '" onchange="window._importWizard.updateBulkBar()">') + '</td>'
                + '<td style="padding:6px 12px;border-bottom:1px solid #f0f2f7;">' + nameHtml + '</td>'
                + '<td style="padding:6px 12px;border-bottom:1px solid #f0f2f7;color:#6b7280;font-size:12px;">' + esc(row.phone) + '</td>'
                + '<td style="padding:6px 12px;border-bottom:1px solid #f0f2f7;color:#6b7280;font-size:12px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + esc(row.email) + '</td>'
                + '<td style="padding:6px 12px;border-bottom:1px solid #f0f2f7;font-weight:600;color:#10B981;font-size:12px;">' + esc(row.value_fmt) + '</td>'
                + '<td style="padding:6px 12px;border-bottom:1px solid #f0f2f7;">' + (isSkip ? '-' : stageSel) + '</td>'
                + '<td style="padding:6px 12px;border-bottom:1px solid #f0f2f7;">' + tagsHtml + '</td>'
                + '<td style="padding:6px 12px;border-bottom:1px solid #f0f2f7;color:#6b7280;font-size:12px;">' + esc(ov.source || row.source) + '</td>';
            tbody.appendChild(tr);
        });

        document.getElementById('importCheckAll').checked = false;
        window._importWizard.updateBulkBar();
        var confirmBtn = document.getElementById('btnImportConfirm');
        confirmBtn.innerHTML = '<i class="bi bi-check-circle"></i> Importar ' + active.length + ' lead' + (active.length !== 1 ? 's' : '');
        confirmBtn.disabled = false;
    }

    function getChecked() {
        return Array.from(document.querySelectorAll('.import-row-check:checked')).map(function(cb) { return parseInt(cb.dataset.idx); });
    }

    window._importWizard.updateBulkBar = function() {
        var n = document.querySelectorAll('.import-row-check:checked').length;
        var bar = document.getElementById('importBulkBar');
        bar.style.display = n > 0 ? 'flex' : 'none';
        document.getElementById('importBulkCount').textContent = n + ' selecionado' + (n !== 1 ? 's' : '');
    };

    window._importWizard.toggleAll = function(master) {
        document.querySelectorAll('.import-row-check').forEach(function(cb) { cb.checked = master.checked; });
        window._importWizard.updateBulkBar();
    };

    window._importWizard.applyBulkStage = function() {
        var val = document.getElementById('bulkStageSelect').value;
        if (!val) { toastr.warning('Selecione uma etapa.'); return; }
        getChecked().forEach(function(idx) { if (!_overrides[idx]) _overrides[idx] = {}; _overrides[idx].stage_id = parseInt(val); });
        updateReviewTable();
        toastr.success('Etapa aplicada.');
    };

    window._importWizard.applyBulkTags = function() {
        var val = document.getElementById('bulkTagsInput').value.trim();
        if (!val) { toastr.warning('Digite ao menos uma tag.'); return; }
        var newTags = val.split(',').map(function(t) { return t.trim(); }).filter(Boolean);
        getChecked().forEach(function(idx) {
            if (!_overrides[idx]) _overrides[idx] = {};
            var existing = _overrides[idx].tags || _rows[idx].tags || [];
            _overrides[idx].tags = Array.from(new Set(existing.concat(newTags)));
        });
        document.getElementById('bulkTagsInput').value = '';
        updateReviewTable();
        toastr.success('Tags aplicadas.');
    };

    window._importWizard.applyBulkRemove = function() {
        getChecked().forEach(function(idx) { if (!_overrides[idx]) _overrides[idx] = {}; _overrides[idx].skip = true; });
        updateReviewTable();
        toastr.success('Leads removidos da importacao.');
    };

    window._importWizard.setRowStage = function(idx, val) {
        if (!_overrides[idx]) _overrides[idx] = {};
        _overrides[idx].stage_id = val ? parseInt(val) : undefined;
    };

    window._importWizard.editRowTags = function(idx) {
        var current = (_overrides[idx] && _overrides[idx].tags) ? _overrides[idx].tags : (_rows[idx].tags || []);
        var val = prompt('Tags (separadas por virgula):', current.join(', '));
        if (val === null) return;
        if (!_overrides[idx]) _overrides[idx] = {};
        _overrides[idx].tags = val.split(',').map(function(t) { return t.trim(); }).filter(Boolean);
        updateReviewTable();
    };

    window._importWizard.goToMapping = function() { showScreen('Mapping'); };

    // ── CONFIRM ──

    window._importWizard.confirmImport = async function() {
        if (!_token) { toastr.error('Token expirado. Envie o arquivo novamente.'); return; }
        if (!_pipelineId) { toastr.error('Funil nao selecionado.'); return; }

        var btn = document.getElementById('btnImportConfirm');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + (typeof LANG !== 'undefined' ? LANG.importing : 'Importando...');

        try {
            var res = await fetch(CFG.importUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CFG.csrf },
                body: JSON.stringify({ token: _token, pipeline_id: _pipelineId, overrides: _overrides }),
            });
            var data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || 'Erro ao importar.');

            var msg = data.imported + ' lead' + (data.imported !== 1 ? 's' : '') + ' importado' + (data.imported !== 1 ? 's' : '');
            if (data.skipped > 0) msg += ' (' + data.skipped + ' ignorado' + (data.skipped !== 1 ? 's' : '') + ')';
            toastr.success(msg, '', { timeOut: 4000 });
            window.closeImportModal();
            setTimeout(function() { window.location.reload(); }, 1500);
        } catch (e) {
            toastr.error(e.message || 'Erro ao importar.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> ' + (typeof LANG !== 'undefined' ? LANG.confirm_import : 'Confirmar importacao');
        }
    };

})();
</script>
