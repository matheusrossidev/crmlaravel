@extends('tenant.layouts.app')

@php
    $title    = __('tasks.title');
    $pageIcon = 'check2-square';
@endphp

{{-- topbar_actions removido — botão movido para page header --}}

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
    .task-item.completed { border-color: #10b981; box-shadow: 0 0 0 2px rgba(16,185,129,.15); }
    .task-item.completed .task-subject { text-decoration: line-through; color: #6b7280; }
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

    /* ── Modal central (migrado de drawer lateral) ── */
    #taskDrawerOverlay {
        display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5);
        z-index: 300; animation: taskModalFade .15s ease-out;
    }
    #taskDrawerOverlay.open { display: block; }
    @keyframes taskModalFade { from { opacity: 0; } to { opacity: 1; } }
    #taskDrawer {
        position: fixed; top: 50%; left: 50%;
        transform: translate(-50%, -50%) scale(.97);
        width: 560px; max-width: calc(100vw - 40px); max-height: 88vh;
        height: auto;
        background: #fff; border-radius: 14px;
        box-shadow: 0 20px 60px rgba(0,0,0,.25); z-index: 301;
        display: flex; flex-direction: column;
        overflow: hidden;
        visibility: hidden; opacity: 0;
        transition: opacity .2s ease, transform .2s ease, visibility 0s linear .2s;
    }
    #taskDrawer.open {
        visibility: visible; opacity: 1;
        transform: translate(-50%, -50%) scale(1);
        transition: opacity .2s ease, transform .2s ease, visibility 0s linear;
    }
    @media (max-width: 640px) {
        #taskDrawer { width: calc(100vw - 24px); max-height: 92vh; }
    }
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
    .lead-detail-card {
        background: #f8fafc; border: 1px solid #e8eaf0; border-radius: 10px;
        padding: 12px 14px; margin-top: 10px;
    }
    .lead-detail-row {
        display: flex; align-items: center; gap: 8px;
        font-size: 12px; color: #374151; padding: 3px 0;
    }
    .lead-detail-row i { color: #9ca3af; font-size: 13px; width: 16px; text-align: center; }
    .lead-detail-row span { color: #6b7280; }

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
    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">CRM</div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('tasks.title') }}</h1>
                <p style="font-size:13.5px;color:#677489;margin:0;">{{ __('tasks.index_subtitle') }}</p>
            </div>
            <button class="btn-primary-sm" onclick="openTaskDrawer()">
                <i class="bi bi-plus-lg"></i> {{ __('tasks.new_task') }}
            </button>
        </div>
    </div>
    <div class="task-filters">
        <div style="position:relative;flex:1;max-width:260px;">
            <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:13px;"></i>
            <input type="text" id="filterSearch" placeholder="Buscar atividade..." style="width:100%;padding:7px 10px 7px 32px;font-size:12px;border-radius:8px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-family:inherit;" oninput="loadTasks()">
        </div>
        <select class="task-filter-select" id="filterStatus">
            <option value="">Status</option>
            <option value="pending">Pendente</option>
            <option value="overdue">Atrasado</option>
            <option value="completed">Concluído</option>
        </select>
        <select class="task-filter-select" id="filterType">
            <option value="">{{ __('tasks.type_filter') }}</option>
            @foreach($typeLabels as $key => $label)
            <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        <select class="task-filter-select" id="filterAssigned">
            <option value="">{{ __('tasks.responsible') }}</option>
            @foreach($users as $u)
            <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>
        <label class="my-tasks-toggle">
            <input type="checkbox" id="myTasksToggle"> {{ __('tasks.my_tasks') }}
        </label>
    </div>
    <div id="taskCounter" style="font-size:13px;color:#6b7280;margin-bottom:12px;"></div>
    <div style="background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;" id="taskTable">
            <thead>
                <tr style="border-bottom:1px solid #f0f2f7;">
                    <th style="width:44px;padding:10px 14px;text-align:center;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;background:#fafbfc;"></th>
                    <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;background:#fafbfc;">Atividade</th>
                    <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;background:#fafbfc;">Status</th>
                    <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;background:#fafbfc;">Data e Hora</th>
                    <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;background:#fafbfc;">Contato</th>
                    <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;background:#fafbfc;">Responsável</th>
                </tr>
            </thead>
            <tbody id="taskList"></tbody>
        </table>
    </div>
    <div id="taskEmpty" class="task-empty" style="display:none;">
        <i class="bi bi-check2-square"></i>
        <p>{{ __('tasks.no_tasks') }}</p>
    </div>
</div>

{{-- Drawer lateral --}}
<div id="taskDrawerOverlay" onclick="closeTaskDrawer()"></div>
<aside id="taskDrawer">
    <div class="td-header">
        <h3 id="tdTitle">{{ __('tasks.create_task') }}</h3>
        <button class="td-close" onclick="closeTaskDrawer()">&times;</button>
    </div>
    <div class="td-body">
        <input type="hidden" id="tdEditId" value="">

        <div class="td-group">
            <label>{{ __('tasks.subject') }} <span class="req">*</span></label>
            <input type="text" id="tdSubject" placeholder="{{ __('tasks.subject_ph') }}" maxlength="191">
        </div>

        <div class="td-group">
            <label>{{ __('tasks.description') }}</label>
            <textarea id="tdDescription" placeholder="{{ __('tasks.description_ph') }}"></textarea>
        </div>

        <div class="td-group">
            <label>{{ __('tasks.assigned_to') }} <span class="req">*</span></label>
            <select id="tdAssignedTo">
                <option value="">{{ __('tasks.select') }}</option>
                @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="td-row">
            <div class="td-group">
                <label>{{ __('tasks.task_type') }} <span class="req">*</span></label>
                <select id="tdType">
                    @foreach($typeLabels as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="td-group">
                <label>{{ __('tasks.priority') }}</label>
                <select id="tdPriority">
                    <option value="low">{{ __('tasks.priority_low') }}</option>
                    <option value="medium" selected>{{ __('tasks.priority_medium') }}</option>
                    <option value="high">{{ __('tasks.priority_high') }}</option>
                </select>
            </div>
        </div>

        <div class="td-row">
            <div class="td-group">
                <label>{{ __('tasks.due_date') }} <span class="req">*</span></label>
                <input type="date" id="tdDueDate">
            </div>
            <div class="td-group">
                <label>{{ __('tasks.due_time') }}</label>
                <input type="time" id="tdDueTime">
            </div>
        </div>

        <div class="td-group">
            <label>{{ __('tasks.linked_contact') }}</label>
            <div id="tdLeadBadge" style="display:none;margin-bottom:8px;">
                <span class="lead-selected-badge">
                    <i class="bi bi-person"></i>
                    <span id="tdLeadName"></span>
                    <button onclick="clearLeadSelection()" title="{{ __('tasks.remove') }}">&times;</button>
                </span>
                <div class="lead-detail-card" id="tdLeadDetails" style="display:none;">
                    <div class="lead-detail-row" id="tdLeadPhone" style="display:none;"><i class="bi bi-telephone"></i> <span></span></div>
                    <div class="lead-detail-row" id="tdLeadEmail" style="display:none;"><i class="bi bi-envelope"></i> <span></span></div>
                    <div class="lead-detail-row" id="tdLeadCompany" style="display:none;"><i class="bi bi-building"></i> <span></span></div>
                </div>
            </div>
            <div class="lead-search-wrap" id="tdLeadSearchWrap">
                <input type="text" class="lead-search-input" id="tdLeadSearch" placeholder="{{ __('tasks.search_contact') }}" autocomplete="off">
                <input type="hidden" id="tdLeadId" value="">
                <div class="lead-search-results" id="tdLeadResults"></div>
            </div>
        </div>
    </div>
    <div class="td-footer">
        <button class="btn-cancel" onclick="closeTaskDrawer()">{{ __('tasks.cancel') }}</button>
        <button class="btn-save" id="btnSaveTask" onclick="saveTask()">{{ __('tasks.save') }}</button>
    </div>
</aside>
@endsection

@push('scripts')
<script>
(function() {
    const TLANG = @json(__('tasks'));
    const STORE_URL      = @json(route('tasks.store'));
    const DATA_URL       = @json(route('tasks.data'));
    const BASE_URL       = @json(url('/tarefas'));
    const LEAD_SEARCH_URL = @json(route('tasks.search-leads'));
    const TYPES          = @json($typeLabels);
    const ICONS          = @json($typeIcons);
    const priorityLabels = { low: TLANG.priority_low, medium: TLANG.priority_medium, high: TLANG.priority_high };

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
        const search = document.getElementById('filterSearch').value.trim();
        if (search) params.set('search', search);

        window.API.get(DATA_URL + '?' + params.toString()).then(function(res) {
            _tasks = res.data || [];
            renderTasks();
        });
    }

    function renderTasks() {
        const list = document.getElementById('taskList');
        const empty = document.getElementById('taskEmpty');
        const counter = document.getElementById('taskCounter');
        const table = document.getElementById('taskTable');

        if (!_tasks.length) {
            list.innerHTML = '';
            empty.style.display = 'block';
            table.style.display = 'none';
            counter.textContent = '';
            return;
        }
        empty.style.display = 'none';
        table.style.display = '';

        // Counter
        const leadIds = new Set(_tasks.filter(t => t.lead_id).map(t => t.lead_id));
        counter.innerHTML = '<strong>' + _tasks.length + '</strong> atividade' + (_tasks.length !== 1 ? 's' : '') +
            (leadIds.size > 0 ? ' de <strong>' + leadIds.size + '</strong> contato' + (leadIds.size !== 1 ? 's' : '') : '');

        const statusLabels = { overdue: 'Atrasado', today: 'Hoje', pending: 'Em dia', completed: 'Concluído' };
        const statusColors = { overdue: '#ef4444', today: '#f59e0b', pending: '#10b981', completed: '#10b981' };
        const statusIcons  = { overdue: 'exclamation-triangle-fill', today: 'calendar-event', pending: 'calendar-check', completed: 'check-circle-fill' };
        const PROFILE_URL = '{{ url("/contatos") }}';

        list.innerHTML = _tasks.map(function(t) {
            const done = t.status === 'completed';
            const icon = ICONS[t.type] || 'bi-check2-square';
            const uc = t.urgency_color;

            // Status logic
            let statusKey = 'pending';
            if (done) statusKey = 'completed';
            else if (t.is_overdue) statusKey = 'overdue';
            else if (t.due_date_fmt && t.due_date === new Date().toISOString().split('T')[0]) statusKey = 'today';

            const sc = statusColors[statusKey];
            const statusBadge = '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;background:' + sc + '15;color:' + sc + ';">' +
                '<i class="bi bi-' + statusIcons[statusKey] + '" style="font-size:10px;"></i> ' + statusLabels[statusKey] + '</span>';

            // Date formatted
            const dateStr = t.due_date_fmt ? t.due_date_fmt + (t.due_time ? ' às ' + t.due_time : '') : '—';

            // Contact
            const contactHtml = t.lead_name
                ? '<a href="' + PROFILE_URL + '/' + t.lead_id + '/perfil" onclick="event.stopPropagation()" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;color:inherit;">' +
                    '<div style="width:28px;height:28px;border-radius:50%;background:#eff6ff;color:#0085f3;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;">' +
                    window.escapeHtml((t.lead_name || '?').substring(0, 2).toUpperCase()) + '</div>' +
                    '<span style="font-weight:500;">' + window.escapeHtml(t.lead_name) + '</span></a>'
                : '<span style="color:#d1d5db;">—</span>';

            return '<tr style="border-bottom:1px solid #f7f8fa;cursor:pointer;transition:background .1s;' + (done ? 'opacity:.6;' : '') + '" onclick="editTask(' + t.id + ')" onmouseenter="this.style.background=\'#f9fafb\'" onmouseleave="this.style.background=\'\'">' +
                '<td style="padding:10px 14px;text-align:center;vertical-align:middle;">' +
                    '<div class="task-checkbox' + (done ? ' checked' : '') + '" onclick="event.stopPropagation();toggleTask(' + t.id + ')"></div>' +
                '</td>' +
                '<td style="padding:10px 14px;vertical-align:middle;">' +
                    '<div style="display:flex;align-items:center;gap:10px;">' +
                        '<div style="width:28px;height:28px;border-radius:7px;background:' + uc + '15;color:' + uc + ';display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;"><i class="bi ' + icon + '"></i></div>' +
                        '<span style="font-weight:600;color:' + (done ? '#9ca3af' : '#1a1d23') + ';' + (done ? 'text-decoration:line-through;' : '') + '">' + window.escapeHtml(t.subject) + '</span>' +
                    '</div>' +
                '</td>' +
                '<td style="padding:10px 14px;vertical-align:middle;">' + statusBadge + '</td>' +
                '<td style="padding:10px 14px;vertical-align:middle;color:#6b7280;font-size:12.5px;white-space:nowrap;">' + dateStr + '</td>' +
                '<td style="padding:10px 14px;vertical-align:middle;">' + contactHtml + '</td>' +
                '<td style="padding:10px 14px;vertical-align:middle;font-size:12.5px;color:' + (t.assigned_name ? '#374151' : '#d1d5db') + ';">' + (t.assigned_name ? window.escapeHtml(t.assigned_name) : 'Sem dono') + '</td>' +
            '</tr>';
        }).join('');
    }

    // ── Filters ─────────────────────────────────────────────────────────
    document.getElementById('filterStatus').addEventListener('change', function() {
        _currentStatus = this.value;
        loadTasks();
    });
    document.getElementById('filterType').addEventListener('change', loadTasks);
    document.getElementById('filterAssigned').addEventListener('change', loadTasks);
    document.getElementById('myTasksToggle').addEventListener('change', loadTasks);

    // ── Drawer ──────────────────────────────────────────────────────────
    window.openTaskDrawer = function(leadId, leadName) {
        document.getElementById('tdEditId').value = '';
        document.getElementById('tdTitle').textContent = TLANG.create_task;
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
            setLeadSelection(leadId, leadName, '', '', '');
        } else {
            clearLeadSelection();
        }

        document.getElementById('taskDrawerOverlay').classList.add('open');
        requestAnimationFrame(function() {
            document.getElementById('taskDrawer').classList.add('open');
        });
    };

    window.closeTaskDrawer = function() {
        document.getElementById('taskDrawer').classList.remove('open');
        setTimeout(function() {
            document.getElementById('taskDrawerOverlay').classList.remove('open');
        }, 250);
    };

    // ── Lead search ─────────────────────────────────────────────────────
    function setLeadSelection(id, name, phone, email, company) {
        document.getElementById('tdLeadId').value = id;
        document.getElementById('tdLeadName').textContent = name;
        document.getElementById('tdLeadBadge').style.display = 'block';
        document.getElementById('tdLeadSearchWrap').style.display = 'none';

        // Show lead details card
        var details = document.getElementById('tdLeadDetails');
        var hasAny = false;
        ['Phone','Email','Company'].forEach(function(f) {
            var val = f === 'Phone' ? phone : (f === 'Email' ? email : company);
            var row = document.getElementById('tdLead' + f);
            if (val) { row.querySelector('span').textContent = val; row.style.display = 'flex'; hasAny = true; }
            else { row.style.display = 'none'; }
        });
        details.style.display = hasAny ? 'block' : 'none';
    }

    window.clearLeadSelection = function() {
        document.getElementById('tdLeadId').value = '';
        document.getElementById('tdLeadName').textContent = '';
        document.getElementById('tdLeadBadge').style.display = 'none';
        document.getElementById('tdLeadDetails').style.display = 'none';
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
                    results.innerHTML = '<div class="lead-search-item" style="color:#9ca3af;">' + TLANG.no_contact_found + '</div>';
                } else {
                    results.innerHTML = items.map(function(l) {
                        const sub = [l.phone, l.email, l.company].filter(Boolean).join(' · ');
                        const esc = function(s) { return s ? window.escapeHtml(s).replace(/'/g, "\\'") : ''; };
                        return '<div class="lead-search-item" onclick="selectLead(' + l.id + ', \'' + esc(l.name) + '\', \'' + esc(l.phone) + '\', \'' + esc(l.email) + '\', \'' + esc(l.company) + '\')">' +
                            window.escapeHtml(l.name) +
                            (l.company ? ' <small style="color:#0085f3;">(' + window.escapeHtml(l.company) + ')</small>' : '') +
                            (sub ? '<br><small>' + window.escapeHtml(sub) + '</small>' : '') +
                        '</div>';
                    }).join('');
                }
                results.classList.add('show');
            });
        }, 300);
    });

    window.selectLead = function(id, name, phone, email, company) {
        setLeadSelection(id, name, phone, email, company);
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

        if (!payload.subject) { toastr.error(TLANG.subject_required); return; }
        if (!payload.due_date) { toastr.error(TLANG.date_required); return; }

        const btn = document.getElementById('btnSaveTask');
        btn.disabled = true;

        const url = editId ? BASE_URL + '/' + editId : STORE_URL;
        const method = editId ? 'put' : 'post';

        window.API[method](url, payload)
            .then(function() {
                btn.disabled = false;
                toastr.success(editId ? TLANG.task_updated : TLANG.task_created);
                closeTaskDrawer();
                loadTasks();
            })
            .catch(function(err) {
                btn.disabled = false;
                const msg = err?.responseJSON?.message || err?.responseJSON?.error || TLANG.error_save;
                toastr.error(msg);
            });
    };

    window.editTask = function(id) {
        const t = _tasks.find(function(x) { return x.id === id; });
        if (!t) return;
        document.getElementById('tdEditId').value = t.id;
        document.getElementById('tdTitle').textContent = TLANG.edit_task;
        document.getElementById('tdSubject').value = t.subject || '';
        document.getElementById('tdDescription').value = t.description || '';
        document.getElementById('tdAssignedTo').value = t.assigned_to || '';
        document.getElementById('tdType').value = t.type || 'task';
        document.getElementById('tdPriority').value = t.priority || 'medium';
        document.getElementById('tdDueDate').value = t.due_date || '';
        document.getElementById('tdDueTime').value = t.due_time || '';

        if (t.lead_id && t.lead_name) {
            setLeadSelection(t.lead_id, t.lead_name, t.lead_phone || '', t.lead_email || '', t.lead_company || '');
        } else {
            clearLeadSelection();
        }

        document.getElementById('taskDrawerOverlay').classList.add('open');
        requestAnimationFrame(function() { document.getElementById('taskDrawer').classList.add('open'); });
    };

    window.toggleTask = function(id) {
        $.ajax({ url: BASE_URL + '/' + id + '/toggle', method: 'PATCH', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' } })
            .done(function() { loadTasks(); });
    };

    window.deleteTask = function(id) {
        window.confirmAction({
            title: TLANG.delete_title,
            message: TLANG.delete_confirm,
            confirmText: TLANG.delete,
            onConfirm: function() {
                window.API.delete(BASE_URL + '/' + id).then(function() {
                    toastr.success(TLANG.task_deleted);
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
