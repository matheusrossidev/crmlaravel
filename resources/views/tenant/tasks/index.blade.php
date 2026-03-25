@extends('tenant.layouts.app')

@php
    $title    = 'Tarefas';
    $pageIcon = 'check2-square';
@endphp

@section('topbar_actions')
<div class="topbar-actions">
    <button class="btn-primary-sm" onclick="openTaskDrawer()" style="display:flex;align-items:center;gap:6px;">
        <i class="bi bi-plus-lg"></i> Nova Tarefa
    </button>
</div>
@endsection

@push('styles')
<style>
    .task-filters {
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;
    }
    .task-tab {
        padding: 6px 14px; font-size: 12px; font-weight: 600; border-radius: 8px;
        border: 1px solid #e5e7eb; background: #fff; color: #6b7280; cursor: pointer; transition: all .15s;
    }
    .task-tab:hover { border-color: #bfdbfe; color: #0085f3; }
    .task-tab.active { background: #0085f3; color: #fff; border-color: #0085f3; }
    .task-filter-select {
        padding: 6px 10px; font-size: 12px; border-radius: 8px;
        border: 1px solid #e5e7eb; background: #fff; color: #374151; cursor: pointer;
    }
    .my-tasks-toggle {
        display: flex; align-items: center; gap: 6px; font-size: 12px;
        font-weight: 600; color: #6b7280; cursor: pointer; margin-left: auto;
    }
    .my-tasks-toggle input { accent-color: #0085f3; }
    .task-list { display: flex; flex-direction: column; gap: 6px; }
    .task-item {
        display: flex; align-items: center; gap: 12px; padding: 12px 16px;
        background: #fff; border: 1px solid #e8eaf0; border-radius: 10px;
        transition: all .15s; cursor: pointer;
    }
    .task-item:hover { border-color: #bfdbfe; box-shadow: 0 2px 8px rgba(0,0,0,.04); }
    .task-item.completed { opacity: .55; }
    .task-item.completed .task-subject { text-decoration: line-through; }
    .task-checkbox {
        width: 18px; height: 18px; border-radius: 50%; border: 2px solid #d1d5db;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; transition: all .15s;
    }
    .task-checkbox:hover { border-color: #0085f3; }
    .task-checkbox.checked { background: #10b981; border-color: #10b981; }
    .task-checkbox.checked::after { content: '\2713'; color: #fff; font-size: 11px; font-weight: 700; }
    .task-type-icon {
        width: 32px; height: 32px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0;
    }
    .task-main { flex: 1; min-width: 0; }
    .task-subject { font-size: 13px; font-weight: 600; color: #1a1d23; }
    .task-meta { font-size: 11px; color: #9ca3af; margin-top: 2px; display: flex; gap: 8px; align-items: center; }
    .task-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .task-due { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px; white-space: nowrap; }
    .priority-badge { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 4px; text-transform: uppercase; letter-spacing: .3px; }
    .priority-low    { background: #f0fdf4; color: #10b981; }
    .priority-medium { background: #fff7ed; color: #f59e0b; }
    .priority-high   { background: #fef2f2; color: #ef4444; }
    .task-actions { display: flex; gap: 4px; }
    .task-actions button {
        width: 28px; height: 28px; border-radius: 6px; border: none;
        background: transparent; color: #9ca3af; cursor: pointer;
        display: flex; align-items: center; justify-content: center; font-size: 14px;
    }
    .task-actions button:hover { background: #f3f4f6; color: #374151; }
    .task-empty { text-align: center; padding: 48px 20px; color: #9ca3af; }
    .task-empty i { font-size: 36px; display: block; margin-bottom: 10px; }

    /* ── Drawer lateral ── */
    #taskDrawerOverlay {
        display: none; position: fixed; inset: 0; background: rgba(0,0,0,.35);
        z-index: 199; transition: opacity .25s;
    }
    #taskDrawer {
        position: fixed; top: 0; right: 0; width: 440px; height: 100vh;
        background: #fff; box-shadow: -4px 0 32px rgba(0,0,0,.1); z-index: 200;
        display: flex; flex-direction: column;
        transform: translateX(100%); transition: transform .25s cubic-bezier(.4,0,.2,1);
        overflow: hidden;
    }
    #taskDrawer.open { transform: translateX(0); }
    .td-header {
        padding: 18px 22px; border-bottom: 1px solid #f0f2f7;
        display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
    }
    .td-header h3 { font-size: 15px; font-weight: 700; color: #1a1d23; margin: 0; }
    .td-close {
        width: 30px; height: 30px; border-radius: 8px; border: none;
        background: #f3f4f6; color: #6b7280; cursor: pointer; font-size: 16px;
        display: flex; align-items: center; justify-content: center;
    }
    .td-body { flex: 1; overflow-y: auto; padding: 22px; }
    .td-footer {
        padding: 14px 22px; border-top: 1px solid #f0f2f7;
        display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0;
    }
    .td-group { margin-bottom: 14px; }
    .td-group label {
        display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px;
    }
    .td-group label .req { color: #ef4444; }
    .td-group input, .td-group select, .td-group textarea {
        width: 100%; padding: 8px 12px; font-size: 13px;
        border: 1px solid #e5e7eb; border-radius: 8px; color: #1a1d23; background: #fff;
    }
    .td-group input:focus, .td-group select:focus, .td-group textarea:focus {
        outline: none; border-color: #0085f3; box-shadow: 0 0 0 3px rgba(0,133,243,.1);
    }
    .td-group textarea { resize: vertical; min-height: 70px; }
    .td-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .btn-cancel {
        padding: 8px 18px; border-radius: 8px; border: 1px solid #e5e7eb;
        background: #fff; color: #6b7280; font-size: 13px; font-weight: 600; cursor: pointer;
    }
    .btn-save {
        padding: 8px 22px; border-radius: 8px; border: none; background: #0085f3;
        color: #fff; font-size: 13px; font-weight: 600; cursor: pointer;
    }
    .btn-save:hover { background: #0070d1; }
    .btn-save:disabled { background: #93c5fd; cursor: not-allowed; }

    /* Lead search */
    .lead-search-wrap { position: relative; }
    .lead-search-input {
        width: 100%; padding: 8px 12px; font-size: 13px;
        border: 1px solid #e5e7eb; border-radius: 8px; color: #1a1d23;
    }
    .lead-search-input:focus { outline: none; border-color: #0085f3; box-shadow: 0 0 0 3px rgba(0,133,243,.1); }
    .lead-search-results {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 10;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;
        max-height: 200px; overflow-y: auto; box-shadow: 0 4px 16px rgba(0,0,0,.1);
        display: none;
    }
    .lead-search-results.show { display: block; }
    .lead-search-item {
        padding: 8px 12px; cursor: pointer; font-size: 13px; color: #374151;
        border-bottom: 1px solid #f5f5f5;
    }
    .lead-search-item:hover { background: #f0f4ff; }
    .lead-search-item:last-child { border-bottom: none; }
    .lead-search-item small { color: #9ca3af; font-size: 11px; }
    .lead-selected-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 10px; background: #eff6ff; color: #0085f3;
        border-radius: 6px; font-size: 12px; font-weight: 600;
    }
    .lead-selected-badge button {
        background: none; border: none; color: #0085f3; cursor: pointer;
        font-size: 14px; padding: 0; line-height: 1;
    }

    @media (max-width: 640px) {
        .task-filters { flex-direction: column; align-items: stretch; }
        .my-tasks-toggle { margin-left: 0; }
        .task-item { flex-wrap: wrap; gap: 8px; }
        .task-right { width: 100%; justify-content: flex-end; }
        #taskDrawer { width: 100%; }
        .td-row { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="page-container">
    <div class="task-filters">
        <button class="task-tab active" data-status="">Todas</button>
        <button class="task-tab" data-status="pending">Pendentes</button>
        <button class="task-tab" data-status="overdue">Atrasadas</button>
        <button class="task-tab" data-status="completed">Concluídas</button>
        <select class="task-filter-select" id="filterType">
            <option value="">Tipo</option>
            @foreach($typeLabels as $key => $label)
            <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        <select class="task-filter-select" id="filterAssigned">
            <option value="">Responsável</option>
            @foreach($users as $u)
            <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>
        <label class="my-tasks-toggle">
            <input type="checkbox" id="myTasksToggle"> Minhas Tarefas
        </label>
    </div>
    <div id="taskList" class="task-list"></div>
    <div id="taskEmpty" class="task-empty" style="display:none;">
        <i class="bi bi-check2-square"></i>
        <p>Nenhuma tarefa encontrada.</p>
    </div>
</div>

{{-- Drawer lateral --}}
<div id="taskDrawerOverlay" onclick="closeTaskDrawer()"></div>
<aside id="taskDrawer">
    <div class="td-header">
        <h3 id="tdTitle">Criar Tarefa</h3>
        <button class="td-close" onclick="closeTaskDrawer()">&times;</button>
    </div>
    <div class="td-body">
        <input type="hidden" id="tdEditId" value="">

        <div class="td-group">
            <label>Assunto da tarefa <span class="req">*</span></label>
            <input type="text" id="tdSubject" placeholder="Assunto da tarefa" maxlength="191">
        </div>

        <div class="td-group">
            <label>Descrição da tarefa</label>
            <textarea id="tdDescription" placeholder="Descrição da tarefa"></textarea>
        </div>

        <div class="td-group">
            <label>Responsável <span class="req">*</span></label>
            <select id="tdAssignedTo">
                <option value="">Selecione...</option>
                @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="td-row">
            <div class="td-group">
                <label>Tipo de tarefa <span class="req">*</span></label>
                <select id="tdType">
                    @foreach($typeLabels as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="td-group">
                <label>Prioridade</label>
                <select id="tdPriority">
                    <option value="low">Baixa</option>
                    <option value="medium" selected>Média</option>
                    <option value="high">Alta</option>
                </select>
            </div>
        </div>

        <div class="td-row">
            <div class="td-group">
                <label>Data do agendamento <span class="req">*</span></label>
                <input type="date" id="tdDueDate">
            </div>
            <div class="td-group">
                <label>Horário da tarefa</label>
                <input type="time" id="tdDueTime">
            </div>
        </div>

        <div class="td-group">
            <label>Contato vinculado</label>
            <div id="tdLeadBadge" style="display:none;margin-bottom:8px;">
                <span class="lead-selected-badge">
                    <i class="bi bi-person"></i>
                    <span id="tdLeadName"></span>
                    <button onclick="clearLeadSelection()" title="Remover">&times;</button>
                </span>
            </div>
            <div class="lead-search-wrap" id="tdLeadSearchWrap">
                <input type="text" class="lead-search-input" id="tdLeadSearch" placeholder="Buscar contato por nome, telefone ou email..." autocomplete="off">
                <input type="hidden" id="tdLeadId" value="">
                <div class="lead-search-results" id="tdLeadResults"></div>
            </div>
        </div>
    </div>
    <div class="td-footer">
        <button class="btn-cancel" onclick="closeTaskDrawer()">Cancelar</button>
        <button class="btn-save" id="btnSaveTask" onclick="saveTask()">Salvar</button>
    </div>
</aside>
@endsection

@push('scripts')
<script>
(function() {
    const STORE_URL      = @json(route('tasks.store'));
    const DATA_URL       = @json(route('tasks.data'));
    const BASE_URL       = @json(url('/tarefas'));
    const LEAD_SEARCH_URL = @json(route('tasks.search-leads'));
    const TYPES          = @json($typeLabels);
    const ICONS          = @json($typeIcons);
    const priorityLabels = { low: 'Baixa', medium: 'Média', high: 'Alta' };

    let _currentStatus = '';
    let _tasks = [];
    let _searchTimer;

    // ── Load & render ───────────────────────────────────────────────────
    function loadTasks() {
        const params = new URLSearchParams();
        if (_currentStatus) params.set('status', _currentStatus);
        const type = document.getElementById('filterType').value;
        if (type) params.set('type', type);
        const assigned = document.getElementById('filterAssigned').value;
        if (assigned) params.set('assigned_to', assigned);
        if (document.getElementById('myTasksToggle').checked) params.set('my_tasks', '1');

        window.API.get(DATA_URL + '?' + params.toString()).then(function(res) {
            _tasks = res.data || [];
            renderTasks();
        });
    }

    function renderTasks() {
        const list = document.getElementById('taskList');
        const empty = document.getElementById('taskEmpty');
        if (!_tasks.length) { list.innerHTML = ''; empty.style.display = 'block'; return; }
        empty.style.display = 'none';

        list.innerHTML = _tasks.map(function(t) {
            const done = t.status === 'completed';
            const icon = ICONS[t.type] || 'bi-check2-square';
            const uc = t.urgency_color;
            const dueBadge = t.due_date_fmt
                ? '<span class="task-due" style="background:' + uc + '20;color:' + uc + ';">' +
                  (t.due_time ? t.due_time + ' ' : '') + t.due_date_fmt +
                  (t.is_overdue ? ' (atrasada)' : '') + '</span>' : '';
            return '<div class="task-item' + (done ? ' completed' : '') + '" data-id="' + t.id + '">' +
                '<div class="task-checkbox' + (done ? ' checked' : '') + '" onclick="event.stopPropagation();toggleTask(' + t.id + ')"></div>' +
                '<div class="task-type-icon" style="background:' + uc + '15;color:' + uc + ';"><i class="bi ' + icon + '"></i></div>' +
                '<div class="task-main">' +
                    '<div class="task-subject">' + window.escapeHtml(t.subject) + '</div>' +
                    '<div class="task-meta">' +
                        '<span>' + window.escapeHtml(TYPES[t.type] || t.type) + '</span>' +
                        (t.lead_name ? '<span><i class="bi bi-person"></i> ' + window.escapeHtml(t.lead_name) + '</span>' : '') +
                        (t.assigned_name ? '<span><i class="bi bi-person-check"></i> ' + window.escapeHtml(t.assigned_name) + '</span>' : '') +
                    '</div>' +
                '</div>' +
                '<div class="task-right">' +
                    '<span class="priority-badge priority-' + (t.priority || 'medium') + '">' + priorityLabels[t.priority || 'medium'] + '</span>' +
                    dueBadge +
                    '<div class="task-actions">' +
                        '<button onclick="event.stopPropagation();editTask(' + t.id + ')" title="Editar"><i class="bi bi-pencil"></i></button>' +
                        '<button onclick="event.stopPropagation();deleteTask(' + t.id + ')" title="Excluir"><i class="bi bi-trash3"></i></button>' +
                    '</div>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    // ── Tabs & filters ──────────────────────────────────────────────────
    document.querySelectorAll('.task-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.task-tab').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            _currentStatus = btn.dataset.status;
            loadTasks();
        });
    });
    document.getElementById('filterType').addEventListener('change', loadTasks);
    document.getElementById('filterAssigned').addEventListener('change', loadTasks);
    document.getElementById('myTasksToggle').addEventListener('change', loadTasks);

    // ── Drawer ──────────────────────────────────────────────────────────
    window.openTaskDrawer = function(leadId, leadName) {
        document.getElementById('tdEditId').value = '';
        document.getElementById('tdTitle').textContent = 'Criar Tarefa';
        document.getElementById('tdSubject').value = '';
        document.getElementById('tdDescription').value = '';
        document.getElementById('tdAssignedTo').value = '{{ auth()->id() }}';
        document.getElementById('tdType').value = 'task';
        document.getElementById('tdPriority').value = 'medium';
        document.getElementById('tdDueDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('tdDueTime').value = '';
        document.getElementById('tdLeadId').value = leadId || '';
        document.getElementById('tdLeadSearch').value = '';

        if (leadId && leadName) {
            setLeadSelection(leadId, leadName);
        } else {
            clearLeadSelection();
        }

        document.getElementById('taskDrawerOverlay').style.display = 'block';
        requestAnimationFrame(function() {
            document.getElementById('taskDrawer').classList.add('open');
        });
    };

    window.closeTaskDrawer = function() {
        document.getElementById('taskDrawer').classList.remove('open');
        setTimeout(function() {
            document.getElementById('taskDrawerOverlay').style.display = 'none';
        }, 250);
    };

    // ── Lead search ─────────────────────────────────────────────────────
    function setLeadSelection(id, name) {
        document.getElementById('tdLeadId').value = id;
        document.getElementById('tdLeadName').textContent = name;
        document.getElementById('tdLeadBadge').style.display = 'block';
        document.getElementById('tdLeadSearchWrap').style.display = 'none';
    }

    window.clearLeadSelection = function() {
        document.getElementById('tdLeadId').value = '';
        document.getElementById('tdLeadName').textContent = '';
        document.getElementById('tdLeadBadge').style.display = 'none';
        document.getElementById('tdLeadSearchWrap').style.display = 'block';
        document.getElementById('tdLeadSearch').value = '';
    };

    document.getElementById('tdLeadSearch').addEventListener('input', function() {
        clearTimeout(_searchTimer);
        const q = this.value.trim();
        const results = document.getElementById('tdLeadResults');
        if (q.length < 2) { results.classList.remove('show'); return; }

        _searchTimer = setTimeout(function() {
            window.API.get(LEAD_SEARCH_URL + '?q=' + encodeURIComponent(q)).then(function(res) {
                const items = res.data || [];
                if (!items.length) {
                    results.innerHTML = '<div class="lead-search-item" style="color:#9ca3af;">Nenhum contato encontrado</div>';
                } else {
                    results.innerHTML = items.map(function(l) {
                        const sub = [l.phone, l.email].filter(Boolean).join(' · ');
                        return '<div class="lead-search-item" onclick="selectLead(' + l.id + ', \'' + window.escapeHtml(l.name).replace(/'/g, "\\'") + '\')">' +
                            window.escapeHtml(l.name) +
                            (sub ? '<br><small>' + window.escapeHtml(sub) + '</small>' : '') +
                        '</div>';
                    }).join('');
                }
                results.classList.add('show');
            });
        }, 300);
    });

    window.selectLead = function(id, name) {
        setLeadSelection(id, name);
        document.getElementById('tdLeadResults').classList.remove('show');
    };

    // Hide results on click outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.lead-search-wrap')) {
            document.getElementById('tdLeadResults').classList.remove('show');
        }
    });

    // ── Save ─────────────────────────────────────────────────────────────
    window.saveTask = function() {
        const editId = document.getElementById('tdEditId').value;
        const payload = {
            subject:     document.getElementById('tdSubject').value,
            description: document.getElementById('tdDescription').value,
            assigned_to: document.getElementById('tdAssignedTo').value || null,
            type:        document.getElementById('tdType').value,
            priority:    document.getElementById('tdPriority').value,
            due_date:    document.getElementById('tdDueDate').value,
            due_time:    document.getElementById('tdDueTime').value || null,
            lead_id:     document.getElementById('tdLeadId').value || null,
        };

        if (!payload.subject) { toastr.error('Informe o assunto da tarefa.'); return; }
        if (!payload.due_date) { toastr.error('Informe a data de vencimento.'); return; }

        const btn = document.getElementById('btnSaveTask');
        btn.disabled = true;

        const url = editId ? BASE_URL + '/' + editId : STORE_URL;
        const method = editId ? 'put' : 'post';

        window.API[method](url, payload)
            .then(function() {
                btn.disabled = false;
                toastr.success(editId ? 'Tarefa atualizada!' : 'Tarefa criada!');
                closeTaskDrawer();
                loadTasks();
            })
            .catch(function(err) {
                btn.disabled = false;
                const msg = err?.responseJSON?.message || err?.responseJSON?.error || 'Erro ao salvar.';
                toastr.error(msg);
            });
    };

    window.editTask = function(id) {
        const t = _tasks.find(function(x) { return x.id === id; });
        if (!t) return;
        document.getElementById('tdEditId').value = t.id;
        document.getElementById('tdTitle').textContent = 'Editar Tarefa';
        document.getElementById('tdSubject').value = t.subject || '';
        document.getElementById('tdDescription').value = t.description || '';
        document.getElementById('tdAssignedTo').value = t.assigned_to || '';
        document.getElementById('tdType').value = t.type || 'task';
        document.getElementById('tdPriority').value = t.priority || 'medium';
        document.getElementById('tdDueDate').value = t.due_date || '';
        document.getElementById('tdDueTime').value = t.due_time || '';

        if (t.lead_id && t.lead_name) {
            setLeadSelection(t.lead_id, t.lead_name);
        } else {
            clearLeadSelection();
        }

        document.getElementById('taskDrawerOverlay').style.display = 'block';
        requestAnimationFrame(function() { document.getElementById('taskDrawer').classList.add('open'); });
    };

    window.toggleTask = function(id) {
        $.ajax({ url: BASE_URL + '/' + id + '/toggle', method: 'PATCH', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' } })
            .done(function() { loadTasks(); });
    };

    window.deleteTask = function(id) {
        window.confirmAction({
            title: 'Excluir tarefa',
            message: 'Tem certeza que deseja excluir esta tarefa?',
            confirmText: 'Excluir',
            onConfirm: function() {
                window.API.delete(BASE_URL + '/' + id).then(function() {
                    toastr.success('Tarefa excluída.');
                    loadTasks();
                });
            }
        });
    };

    // ── Init ─────────────────────────────────────────────────────────────
    loadTasks();

    // Auto-open drawer if redirected from lead page
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open_modal') === '1') {
        const lid = urlParams.get('lead_id');
        const lname = decodeURIComponent(urlParams.get('lead_name') || '');
        setTimeout(function() { openTaskDrawer(lid, lname); }, 300);
    }
})();
</script>
@endpush
