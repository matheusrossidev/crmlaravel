@extends('master.layouts.app')
@php
    $title    = 'Ferramentas';
    $pageIcon = 'tools';
@endphp

@push('styles')
<style>
    .tool-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }

    .tool-card {
        background: #fff;
        border: 1.5px solid #e8eaf0;
        border-radius: 14px;
        padding: 20px;
        cursor: pointer;
        transition: box-shadow .15s, transform .1s, border-color .15s;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .tool-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,.08);
        transform: translateY(-2px);
        border-color: #d1d5db;
    }

    .tool-card-header {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .tool-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .tool-info h6 {
        margin: 0 0 4px;
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
    }

    .tool-info p {
        margin: 0;
        font-size: 12.5px;
        color: #6b7280;
        line-height: 1.5;
    }

    .tool-badge {
        align-self: flex-end;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 99px;
        letter-spacing: .4px;
        text-transform: uppercase;
    }

    .badge-blue   { background: #dbeafe; color: #1d4ed8; }
    .badge-red    { background: #fee2e2; color: #b91c1c; }
    .badge-gray   { background: #f3f4f6; color: #374151; }
    .badge-amber  { background: #fef3c7; color: #92400e; }
    .badge-purple { background: #ede9fe; color: #6d28d9; }
    .badge-green  { background: #d1fae5; color: #065f46; }

    /* Modal output */
    .tool-output {
        background: #0f172a;
        color: #86efac;
        font-family: 'Courier New', monospace;
        font-size: 12.5px;
        border-radius: 8px;
        padding: 14px 16px;
        min-height: 80px;
        max-height: 340px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-break: break-word;
        line-height: 1.6;
    }

    .tool-output .err { color: #f87171; }
    .tool-output .dim { color: #64748b; }

    .tool-param-group { margin-bottom: 14px; }
    .tool-param-group label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 5px;
    }
    .tool-param-group select,
    .tool-param-group input[type=text],
    .tool-param-group input[type=password] {
        width: 100%;
        padding: 8px 12px;
        border: 1.5px solid #e5e7eb;
        border-radius: 8px;
        font-size: 13px;
        color: #1a1d23;
        background: #fff;
        outline: none;
        transition: border-color .15s;
    }
    .tool-param-group select:focus,
    .tool-param-group input:focus { border-color: #3B82F6; }

    .tool-param-group .param-hint {
        font-size: 11.5px;
        color: #9ca3af;
        margin-top: 4px;
    }

    .tool-check-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
    }
    .tool-check-row input[type=checkbox] { width: 16px; height: 16px; cursor: pointer; }
    .tool-check-row label { margin: 0; font-size: 13px; font-weight: 500; color: #374151; cursor: pointer; }
</style>
@endpush

@section('content')

<p style="margin: 0 0 20px; font-size: 13.5px; color: #6b7280;">
    Ferramentas administrativas para manutenção e diagnóstico do sistema. Todas as ações são executadas diretamente no servidor.
</p>

<div class="tool-grid">

    {{-- 1. Sincronizar Nomes de Grupos --}}
    <div class="tool-card" onclick="openTool('sync-group-names')">
        <div class="tool-card-header">
            <div class="tool-icon" style="background:#eff6ff;">
                <i class="bi bi-arrow-repeat" style="color:#3B82F6;"></i>
            </div>
            <div class="tool-info">
                <h6>Sincronizar Nomes de Grupos</h6>
                <p>Busca e atualiza o nome dos grupos WhatsApp nas conversas cadastradas via WAHA API.</p>
            </div>
        </div>
        <span class="tool-badge badge-blue">WhatsApp</span>
    </div>

    {{-- 2. Limpar Leads de Tenant --}}
    <div class="tool-card" onclick="openTool('clear-leads')">
        <div class="tool-card-header">
            <div class="tool-icon" style="background:#fef2f2;">
                <i class="bi bi-trash3" style="color:#ef4444;"></i>
            </div>
            <div class="tool-info">
                <h6>Limpar Leads de Tenant</h6>
                <p>Remove permanentemente todos os leads e contatos de um tenant específico. Ação irreversível.</p>
            </div>
        </div>
        <span class="tool-badge badge-red">Dados</span>
    </div>

    {{-- 3. Limpar Cache --}}
    <div class="tool-card" onclick="openTool('clear-cache')">
        <div class="tool-card-header">
            <div class="tool-icon" style="background:#f9fafb;">
                <i class="bi bi-lightning-charge" style="color:#6b7280;"></i>
            </div>
            <div class="tool-info">
                <h6>Limpar Cache do Sistema</h6>
                <p>Executa <code>cache:clear</code>, <code>config:clear</code>, <code>route:clear</code> e <code>view:clear</code>.</p>
            </div>
        </div>
        <span class="tool-badge badge-gray">Sistema</span>
    </div>

    {{-- 4. Corrigir Não Lidos --}}
    <div class="tool-card" onclick="openTool('fix-unread-counts')">
        <div class="tool-card-header">
            <div class="tool-icon" style="background:#eff6ff;">
                <i class="bi bi-chat-dots" style="color:#3B82F6;"></i>
            </div>
            <div class="tool-info">
                <h6>Zerar Contadores Não Lidos</h6>
                <p>Reseta o contador de mensagens não lidas em todas as conversas de um tenant ou de toda a plataforma.</p>
            </div>
        </div>
        <span class="tool-badge badge-blue">WhatsApp</span>
    </div>

    {{-- 5. Resetar Senha --}}
    <div class="tool-card" onclick="openTool('reset-password')">
        <div class="tool-card-header">
            <div class="tool-icon" style="background:#fffbeb;">
                <i class="bi bi-key" style="color:#d97706;"></i>
            </div>
            <div class="tool-info">
                <h6>Resetar Senha de Usuário</h6>
                <p>Redefine a senha de qualquer usuário da plataforma diretamente no banco de dados.</p>
            </div>
        </div>
        <span class="tool-badge badge-amber">Segurança</span>
    </div>

    {{-- 6. Status das Instâncias WA --}}
    <div class="tool-card" onclick="openTool('wa-status')">
        <div class="tool-card-header">
            <div class="tool-icon" style="background:#eff6ff;">
                <i class="bi bi-phone" style="color:#3B82F6;"></i>
            </div>
            <div class="tool-info">
                <h6>Status das Instâncias WA</h6>
                <p>Lista todas as instâncias WhatsApp cadastradas com seu status de conexão atual.</p>
            </div>
        </div>
        <span class="tool-badge badge-blue">WhatsApp</span>
    </div>

    {{-- 7. Fechar Conversas Abertas --}}
    <div class="tool-card" onclick="openTool('close-conversations')">
        <div class="tool-card-header">
            <div class="tool-icon" style="background:#f5f3ff;">
                <i class="bi bi-x-circle" style="color:#7c3aed;"></i>
            </div>
            <div class="tool-info">
                <h6>Fechar Conversas Abertas</h6>
                <p>Fecha todas as conversas com status "aberta" de um tenant específico de uma vez.</p>
            </div>
        </div>
        <span class="tool-badge badge-purple">WhatsApp</span>
    </div>

    {{-- 8. Exportar Stats do Tenant --}}
    <div class="tool-card" onclick="openTool('export-tenant-stats')">
        <div class="tool-card-header">
            <div class="tool-icon" style="background:#f0fdf4;">
                <i class="bi bi-bar-chart" style="color:#16a34a;"></i>
            </div>
            <div class="tool-info">
                <h6>Exportar Stats do Tenant</h6>
                <p>Exibe um relatório rápido com contagens de leads, usuários, conversas e mensagens.</p>
            </div>
        </div>
        <span class="tool-badge badge-green">Relatório</span>
    </div>

</div>

{{-- ── MODAL ──────────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="toolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
            <div class="modal-header" style="border-bottom:1px solid #f0f2f7;padding:18px 24px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div id="modalToolIcon" class="tool-icon" style="width:36px;height:36px;font-size:16px;"></div>
                    <h5 class="modal-title" id="modalToolTitle" style="margin:0;font-size:16px;font-weight:700;color:#1a1d23;"></h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px;">
                <div id="modalParams"></div>
                <div id="modalOutput" style="display:none;">
                    <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px;">
                        <i class="bi bi-terminal"></i> Saída
                    </div>
                    <pre class="tool-output" id="toolOutputPre"></pre>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f2f7;padding:14px 24px;gap:8px;">
                <button type="button" class="m-btn m-btn-ghost m-btn-sm" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="m-btn m-btn-primary m-btn-sm" id="btnRunTool" onclick="runTool()">
                    <span id="btnRunSpinner" style="display:none;">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </span>
                    <i class="bi bi-play-fill" id="btnRunIcon"></i>
                    <span id="btnRunLabel">Executar</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Data for JS --}}
<script>
var TENANTS = <?php echo json_encode($tenants->toArray()); ?>;
var USERS   = <?php echo json_encode($users->toArray()); ?>;
</script>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const TOOL_DEFS = {
        'sync-group-names': {
            label: 'Sincronizar Nomes de Grupos',
            iconHtml: '<i class="bi bi-arrow-repeat" style="color:#3B82F6;"></i>',
            iconBg: '#eff6ff',
            params: [
                { name: 'tenant_id', label: 'Tenant (vazio = todos)', type: 'select-tenant', required: false },
                { name: 'all',       label: 'Forçar atualização mesmo com nome já preenchido', type: 'checkbox' },
            ],
        },
        'clear-leads': {
            label: 'Limpar Leads de Tenant',
            iconHtml: '<i class="bi bi-trash3" style="color:#ef4444;"></i>',
            iconBg: '#fef2f2',
            params: [
                { name: 'tenant_id', label: 'Tenant', type: 'select-tenant', required: true },
                { name: 'confirm',   label: 'Confirmação', type: 'confirm', hint: 'Digite CONFIRMAR (em maiúsculas) para prosseguir', required: true },
            ],
        },
        'clear-cache': {
            label: 'Limpar Cache do Sistema',
            iconHtml: '<i class="bi bi-lightning-charge" style="color:#6b7280;"></i>',
            iconBg: '#f9fafb',
            params: [],
        },
        'fix-unread-counts': {
            label: 'Zerar Contadores Não Lidos',
            iconHtml: '<i class="bi bi-chat-dots" style="color:#3B82F6;"></i>',
            iconBg: '#eff6ff',
            params: [
                { name: 'tenant_id', label: 'Tenant (vazio = todos)', type: 'select-tenant', required: false },
            ],
        },
        'reset-password': {
            label: 'Resetar Senha de Usuário',
            iconHtml: '<i class="bi bi-key" style="color:#d97706;"></i>',
            iconBg: '#fffbeb',
            params: [
                { name: 'tenant_id',    label: 'Tenant', type: 'select-tenant', required: true },
                { name: 'user_id',      label: 'Usuário', type: 'select-user', required: true },
                { name: 'new_password', label: 'Nova Senha', type: 'password', hint: 'Mínimo 6 caracteres', required: true },
            ],
        },
        'wa-status': {
            label: 'Status das Instâncias WA',
            iconHtml: '<i class="bi bi-phone" style="color:#3B82F6;"></i>',
            iconBg: '#eff6ff',
            params: [],
        },
        'close-conversations': {
            label: 'Fechar Conversas Abertas',
            iconHtml: '<i class="bi bi-x-circle" style="color:#7c3aed;"></i>',
            iconBg: '#f5f3ff',
            params: [
                { name: 'tenant_id', label: 'Tenant', type: 'select-tenant', required: true },
            ],
        },
        'export-tenant-stats': {
            label: 'Exportar Stats do Tenant',
            iconHtml: '<i class="bi bi-bar-chart" style="color:#16a34a;"></i>',
            iconBg: '#f0fdf4',
            params: [
                { name: 'tenant_id', label: 'Tenant', type: 'select-tenant', required: true },
            ],
        },
    };

    let _currentTool = null;
    let _modal       = null;

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    window.openTool = function (slug) {
        const def = TOOL_DEFS[slug];
        if (! def) return;

        _currentTool = slug;

        // Icon + title
        document.getElementById('modalToolIcon').style.background = def.iconBg;
        document.getElementById('modalToolIcon').innerHTML = def.iconHtml;
        document.getElementById('modalToolTitle').textContent = def.label;

        // Reset output
        document.getElementById('modalOutput').style.display = 'none';
        document.getElementById('toolOutputPre').innerHTML = '';

        // Reset run button
        const btn = document.getElementById('btnRunTool');
        btn.disabled = false;
        document.getElementById('btnRunSpinner').style.display = 'none';
        document.getElementById('btnRunIcon').style.display = '';
        document.getElementById('btnRunLabel').textContent = 'Executar';

        // Build params form
        document.getElementById('modalParams').innerHTML = renderParams(def.params);

        // Wire up tenant → user dependency
        const tenantSel = document.getElementById('param-tenant_id');
        if (tenantSel) {
            tenantSel.addEventListener('change', function () {
                const userSel = document.getElementById('param-user_id');
                if (userSel) populateUsers(userSel, this.value);
            });
        }

        _modal = _modal || new bootstrap.Modal(document.getElementById('toolModal'));
        _modal.show();
    };

    function renderParams(params) {
        if (! params.length) {
            return '<p style="font-size:13px;color:#6b7280;margin:0 0 4px;">Nenhum parâmetro necessário. Clique em Executar para prosseguir.</p>';
        }

        return params.map(function (p) {
            let input = '';

            if (p.type === 'select-tenant') {
                const req = p.required ? '' : '<option value="">— Todos os tenants —</option>';
                const opts = TENANTS.map(t => '<option value="' + t.id + '">' + esc(t.name) + '</option>').join('');
                input = '<select id="param-' + p.name + '" name="' + p.name + '">' + req + opts + '</select>';

            } else if (p.type === 'select-user') {
                input = '<select id="param-user_id" name="user_id"><option value="">— Selecione um tenant primeiro —</option></select>';

            } else if (p.type === 'checkbox') {
                return '<div class="tool-param-group"><div class="tool-check-row">'
                     + '<input type="checkbox" id="param-' + p.name + '" name="' + p.name + '">'
                     + '<label for="param-' + p.name + '">' + esc(p.label) + '</label>'
                     + '</div></div>';

            } else if (p.type === 'confirm') {
                input = '<input type="text" id="param-' + p.name + '" name="' + p.name + '" placeholder="CONFIRMAR" autocomplete="off">';

            } else if (p.type === 'password') {
                input = '<input type="password" id="param-' + p.name + '" name="' + p.name + '" autocomplete="new-password">';

            } else {
                input = '<input type="text" id="param-' + p.name + '" name="' + p.name + '">';
            }

            const hint = p.hint ? '<div class="param-hint">' + esc(p.hint) + '</div>' : '';

            return '<div class="tool-param-group">'
                 + '<label for="param-' + p.name + '">' + esc(p.label) + (p.required ? ' <span style="color:#ef4444;">*</span>' : '') + '</label>'
                 + input
                 + hint
                 + '</div>';
        }).join('');
    }

    function populateUsers(sel, tenantId) {
        const filtered = tenantId
            ? USERS.filter(u => String(u.tenant_id) === String(tenantId))
            : [];

        if (! filtered.length) {
            sel.innerHTML = '<option value="">— Nenhum usuário neste tenant —</option>';
            return;
        }

        sel.innerHTML = '<option value="">— Selecione um usuário —</option>'
            + filtered.map(u => '<option value="' + u.id + '">' + esc(u.name) + ' (' + esc(u.email) + ')</option>').join('');
    }

    window.runTool = async function () {
        const def = TOOL_DEFS[_currentTool];
        if (! def) return;

        // Collect params
        const data = {};
        def.params.forEach(function (p) {
            const el = document.getElementById('param-' + p.name);
            if (! el) return;
            if (p.type === 'checkbox') {
                data[p.name] = el.checked ? '1' : '0';
            } else {
                data[p.name] = el.value;
            }
        });

        // Basic validation
        for (const p of def.params) {
            if (p.required && ! data[p.name]) {
                alert('Campo obrigatório: ' + p.label);
                return;
            }
        }

        // UI: show spinner
        const btn = document.getElementById('btnRunTool');
        btn.disabled = true;
        document.getElementById('btnRunSpinner').style.display = '';
        document.getElementById('btnRunIcon').style.display = 'none';
        document.getElementById('btnRunLabel').textContent = 'Executando...';

        // Show output area with loading
        const pre = document.getElementById('toolOutputPre');
        document.getElementById('modalOutput').style.display = 'block';
        pre.innerHTML = '<span class="dim">Aguardando resposta do servidor...</span>';

        try {
            const resp = await fetch('{{ route("master.toolbox.run", "TOOLSLUG") }}'.replace('TOOLSLUG', _currentTool), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });

            const json = await resp.json();
            const lines = json.lines || (json.error ? [json.error] : ['Sem resposta do servidor.']);

            pre.innerHTML = lines.map(function (line) {
                if (line.startsWith('[ERRO]')) {
                    return '<span class="err">' + esc(line.replace('[ERRO]', '').trim()) + '</span>';
                }
                return esc(line);
            }).join('\n');

        } catch (err) {
            pre.innerHTML = '<span class="err">Erro de rede: ' + esc(err.message) + '</span>';
        }

        // Restore button
        btn.disabled = false;
        document.getElementById('btnRunSpinner').style.display = 'none';
        document.getElementById('btnRunIcon').style.display = '';
        document.getElementById('btnRunLabel').textContent = 'Executar novamente';
    };

}());
</script>
@endpush
