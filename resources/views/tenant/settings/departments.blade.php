@extends('tenant.layouts.app')

@php
    $title    = 'Configurações';
    $pageIcon = 'gear';
@endphp

@push('styles')
<style>
    .dept-table-wrap {
        background: #fff;
        border: 1px solid #e8eaf0;
        border-radius: 12px;
        overflow: hidden;
    }
    .dept-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }
    .dept-table thead th {
        padding: 11px 16px;
        font-size: 11.5px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .06em;
        background: #fafafa;
        border-bottom: 1px solid #f0f2f7;
    }
    .dept-table tbody tr { border-bottom: 1px solid #f7f8fa; }
    .dept-table tbody tr:last-child { border-bottom: none; }
    .dept-table tbody td {
        padding: 12px 16px;
        color: #374151;
        vertical-align: middle;
    }

    .dept-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12.5px;
        font-weight: 600;
        white-space: nowrap;
    }
    .dept-chip i { font-size: 13px; }

    .badge-count {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11.5px;
        font-weight: 600;
        background: #f0f4ff;
        color: #3B82F6;
    }

    .badge-strategy {
        display: inline-flex;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        background: #f3f4f6;
        color: #6b7280;
    }

    .badge-agent {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        background: #ecfdf5;
        color: #059669;
    }

    .status-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        display: inline-block;
    }
    .status-dot.active { background: #10B981; }
    .status-dot.inactive { background: #d1d5db; }

    .btn-icon {
        width: 28px; height: 28px; border-radius: 7px;
        border: 1px solid #e8eaf0; background: #fff; color: #6b7280;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 13px; transition: all .15s;
    }
    .btn-icon:hover { background: #f0f4ff; color: #374151; }
    .btn-icon.danger:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
    }
    .section-title { font-size: 15px; font-weight: 700; color: #1a1d23; }
    .section-subtitle { font-size: 13px; color: #9ca3af; margin-top: 3px; }

    /* Drawer */
    .drawer-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.35); z-index: 300;
    }
    .drawer-overlay.open { display: block; }
    .drawer {
        position: fixed; top: 0; right: -480px;
        width: 480px; height: 100vh; background: #fff;
        z-index: 301; transition: right .25s cubic-bezier(.4,0,.2,1);
        display: flex; flex-direction: column;
        box-shadow: -4px 0 24px rgba(0,0,0,.1);
    }
    .drawer.open { right: 0; }
    .drawer-header {
        padding: 18px 22px; border-bottom: 1px solid #f0f2f7;
        display: flex; align-items: center; justify-content: space-between;
        font-size: 15px; font-weight: 700; color: #1a1d23;
    }
    .drawer-body { padding: 22px; flex: 1; overflow-y: auto; }
    .drawer-footer {
        padding: 16px 22px; border-top: 1px solid #f0f2f7;
        display: flex; gap: 10px; justify-content: flex-end;
    }

    .form-group { margin-bottom: 14px; }
    .form-label {
        display: block; font-size: 12.5px; font-weight: 600;
        color: #374151; margin-bottom: 6px;
    }
    .form-input {
        width: 100%; padding: 9px 12px;
        border: 1px solid #d1d5db; border-radius: 9px;
        font-size: 13.5px; color: #1a1d23;
        outline: none; transition: border-color .15s; background: #fff;
        box-sizing: border-box; font-family: inherit;
    }
    .form-input:focus { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }

    .form-row { display: flex; gap: 12px; }
    .form-row .form-group { flex: 1; }

    .color-row { display: flex; gap: 8px; align-items: center; }
    .color-picker-input {
        width: 46px; height: 38px; padding: 3px;
        border: 1.5px solid #e8eaf0; border-radius: 9px;
        cursor: pointer; background: #fff; flex-shrink: 0;
    }

    .preset-colors { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
    .preset-btn {
        width: 24px; height: 24px; border-radius: 6px;
        border: 2px solid transparent; cursor: pointer;
        transition: transform .1s, border-color .1s;
    }
    .preset-btn:hover { transform: scale(1.15); }
    .preset-btn.selected { border-color: #1a1d23; }

    .user-list { display: flex; flex-direction: column; gap: 4px; max-height: 180px; overflow-y: auto; }
    .user-item {
        display: flex; align-items: center; gap: 10px;
        padding: 7px 10px; border-radius: 8px;
        cursor: pointer; transition: background .1s;
        font-size: 13px;
    }
    .user-item:hover { background: #f0f4ff; }
    .user-item input[type="checkbox"] { accent-color: #3B82F6; }
    .user-item-name { font-weight: 600; color: #1a1d23; }
    .user-item-email { font-size: 11.5px; color: #9ca3af; }

    .btn-cancel {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #f4f6fb; color: #374151;
        border: 1px solid #e8eaf0; border-radius: 100px;
        font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s;
    }
    .btn-cancel:hover { background: #e8eaf0; }
    .btn-save {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #0085f3; color: #fff;
        border: none; border-radius: 100px; font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
    }
    .btn-save:hover { background: #0070d1; }
    .btn-save:disabled { opacity: .6; cursor: not-allowed; }

    .empty-state {
        text-align: center; padding: 56px 24px; color: #9ca3af;
    }
    .empty-state i { font-size: 36px; opacity: .25; display: block; margin-bottom: 12px; }
</style>
@endpush

@section('content')
<div class="page-container">

    @include('tenant.settings._tabs')

    <div class="section-header">
        <div>
            <div class="section-title">{{ __('settings.dept_title') }}</div>
            <div class="section-subtitle">{{ __('settings.dept_subtitle') }}</div>
        </div>
        <button class="btn-primary-sm" id="btnNewDept">
            <i class="bi bi-plus-lg"></i> {{ __('settings.dept_new') }}
        </button>
    </div>

    <div class="dept-table-wrap">
        <table class="dept-table">
            <thead>
                <tr>
                    <th>{{ __('settings.dept_col_dept') }}</th>
                    <th>{{ __('settings.dept_col_members') }}</th>
                    <th>{{ __('settings.dept_col_agent_bot') }}</th>
                    <th>{{ __('settings.dept_col_strategy') }}</th>
                    <th style="width:60px;">{{ __('settings.dept_col_status') }}</th>
                    <th style="width:80px;"></th>
                </tr>
            </thead>
            <tbody id="deptBody">
                @forelse($departments as $dept)
                @php
                    $c = $dept->color ?? '#3B82F6';
                    [$r, $g, $b] = sscanf($c, '#%02x%02x%02x') ?: [59, 130, 246];
                @endphp
                <tr data-id="{{ $dept->id }}">
                    <td>
                        <span class="dept-chip"
                              style="background:rgba({{ $r }},{{ $g }},{{ $b }},.10);color:{{ $c }};border:1px solid rgba({{ $r }},{{ $g }},{{ $b }},.25);">
                            <i class="bi {{ $dept->icon ?? 'bi-building' }}"></i>
                            {{ $dept->name }}
                        </span>
                    </td>
                    <td>
                        <span class="badge-count"><i class="bi bi-people"></i> {{ $dept->users->count() }}</span>
                    </td>
                    <td>
                        @if($dept->defaultAiAgent)
                            <span class="badge-agent"><i class="bi bi-robot"></i> {{ $dept->defaultAiAgent->name }}</span>
                        @elseif($dept->defaultChatbotFlow)
                            <span class="badge-agent"><i class="bi bi-diagram-3"></i> {{ $dept->defaultChatbotFlow->name }}</span>
                        @else
                            <span style="font-size:12px;color:#d1d5db;">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge-strategy">
                            {{ $dept->assignment_strategy === 'round_robin' ? __('settings.dept_round_robin') : __('settings.dept_least_busy') }}
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <span class="status-dot {{ $dept->is_active ? 'active' : 'inactive' }}"
                              title="{{ $dept->is_active ? __('settings.dept_active') : __('settings.dept_inactive') }}"></span>
                    </td>
                    <td>
                        <div style="display:flex;gap:5px;justify-content:flex-end;">
                            <button class="btn-icon" title="{{ __('settings.dept_edit') }}" onclick="openEdit({{ $dept->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn-icon danger" title="{{ __('settings.dept_delete') }}" onclick="deleteDept({{ $dept->id }},this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="emptyRow">
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="bi bi-building"></i>
                            {!! __('settings.dept_empty_cta') !!}
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

{{-- Drawer Criar/Editar --}}
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="drawer">
    <div class="drawer-header">
        <h4 style="margin:0;font-size:15px;font-weight:700;color:#1a1d23;" id="drawerTitle">{{ __('settings.dept_new_title') }}</h4>
        <button onclick="closeDrawer()" style="background:none;border:none;font-size:18px;color:#6b7280;cursor:pointer;">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="drawer-body">
        <input type="hidden" id="deptId">

        <div class="form-row">
            <div class="form-group" style="flex:2;">
                <label class="form-label">{{ __('settings.dept_name') }}</label>
                <input type="text" id="deptName" class="form-input" placeholder="{{ __('settings.dept_name_ph') }}">
            </div>
            <div class="form-group" style="flex:1;">
                <label class="form-label">{{ __('settings.dept_icon') }}</label>
                <select id="deptIcon" class="form-input">
                    <option value="bi-building">{{ __('settings.dept_icon_building') }}</option>
                    <option value="bi-headset">{{ __('settings.dept_icon_headset') }}</option>
                    <option value="bi-cash-stack">{{ __('settings.dept_icon_finance') }}</option>
                    <option value="bi-megaphone">{{ __('settings.dept_icon_marketing') }}</option>
                    <option value="bi-graph-up">{{ __('settings.dept_icon_sales') }}</option>
                    <option value="bi-tools">{{ __('settings.dept_icon_technical') }}</option>
                    <option value="bi-people">{{ __('settings.dept_icon_team') }}</option>
                    <option value="bi-shield-check">{{ __('settings.dept_icon_compliance') }}</option>
                    <option value="bi-truck">{{ __('settings.dept_icon_logistics') }}</option>
                    <option value="bi-briefcase">{{ __('settings.dept_icon_business') }}</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">{{ __('settings.dept_description') }}</label>
            <input type="text" id="deptDescription" class="form-input" placeholder="{{ __('settings.dept_description_ph') }}">
        </div>

        <div class="form-group">
            <label class="form-label">{{ __('settings.dept_color') }}</label>
            <div class="color-row">
                <input type="color" id="deptColorPicker" class="color-picker-input" value="#3B82F6"
                       oninput="document.getElementById('deptColorText').value=this.value; highlightPreset(this.value);">
                <input type="text" id="deptColorText" class="form-input"
                       value="#3B82F6" placeholder="#3B82F6"
                       oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){document.getElementById('deptColorPicker').value=this.value;highlightPreset(this.value);}"
                       style="flex:1;font-family:monospace;">
            </div>
            <div class="preset-colors">
                @foreach(['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#06B6D4','#F97316','#84CC16','#6B7280'] as $c)
                <button type="button" class="preset-btn" style="background:{{ $c }};"
                        data-color="{{ $c }}" onclick="setPreset('{{ $c }}')"></button>
                @endforeach
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">{{ __('settings.dept_ai_agent') }}</label>
                <select id="deptAiAgent" class="form-input">
                    <option value="">{{ __('settings.dept_none') }}</option>
                    @foreach($aiAgents as $agent)
                    <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('settings.dept_chatbot') }}</label>
                <select id="deptChatbot" class="form-input">
                    <option value="">{{ __('settings.dept_none') }}</option>
                    @foreach($chatbotFlows as $flow)
                    <option value="{{ $flow->id }}">{{ $flow->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">{{ __('settings.dept_strategy') }}</label>
            <select id="deptStrategy" class="form-input">
                <option value="round_robin">{{ __('settings.dept_round_robin_opt') }}</option>
                <option value="least_busy">{{ __('settings.dept_least_busy_opt') }}</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">{{ __('settings.dept_members') }}</label>
            <div class="user-list" id="userList">
                @foreach($users as $u)
                <label class="user-item">
                    <input type="checkbox" value="{{ $u->id }}" class="user-check">
                    <div>
                        <span class="user-item-name">{{ $u->name }}</span>
                        <span class="user-item-email">{{ $u->email }}</span>
                    </div>
                </label>
                @endforeach
            </div>
        </div>
    </div>
    <div class="drawer-footer">
        <button class="btn-cancel" onclick="closeDrawer()">{{ __('settings.dept_cancel') }}</button>
        <button class="btn-save" id="btnSave" onclick="saveDept()">
            <i class="bi bi-check2"></i> {{ __('settings.dept_save') }}
        </button>
    </div>
</div>

@include('partials._drawer-as-modal')
@endsection

@push('scripts')
<script>
const SLANG = @json(__('settings'));
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const URL_STORE   = '{{ route('settings.departments.store') }}';
const URL_UPDATE  = '{{ route('settings.departments.update', ['department' => '__ID__']) }}';
const URL_DESTROY = '{{ route('settings.departments.destroy', ['department' => '__ID__']) }}';

/* ── Dados dos departamentos para edição ── */
@php
    $deptsJson = $departments->map(function($d) {
        return [
            'id' => $d->id,
            'name' => $d->name,
            'description' => $d->description,
            'icon' => $d->icon,
            'color' => $d->color,
            'default_ai_agent_id' => $d->default_ai_agent_id,
            'default_chatbot_flow_id' => $d->default_chatbot_flow_id,
            'assignment_strategy' => $d->assignment_strategy,
            'is_active' => $d->is_active,
            'user_ids' => $d->users->pluck('id')->toArray(),
        ];
    });
@endphp
const DEPTS_DATA = {!! json_encode($deptsJson) !!};

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function hexToRgba(hex, a) {
    const r = parseInt(hex.slice(1,3),16), g = parseInt(hex.slice(3,5),16), b = parseInt(hex.slice(5,7),16);
    return `rgba(${r},${g},${b},${a})`;
}

function setPreset(hex) {
    document.getElementById('deptColorPicker').value = hex;
    document.getElementById('deptColorText').value   = hex;
    highlightPreset(hex);
}
function highlightPreset(hex) {
    document.querySelectorAll('.preset-btn').forEach(b => {
        b.classList.toggle('selected', b.dataset.color.toLowerCase() === hex.toLowerCase());
    });
}

/* ── Drawer ── */
function openDrawer() {
    document.getElementById('drawerOverlay').classList.add('open');
    document.getElementById('drawer').classList.add('open');
}

function closeDrawer() {
    document.getElementById('drawerOverlay').classList.remove('open');
    document.getElementById('drawer').classList.remove('open');
}

document.getElementById('btnNewDept').addEventListener('click', () => {
    document.getElementById('drawerTitle').textContent = SLANG.dept_new_title;
    document.getElementById('deptId').value = '';
    document.getElementById('deptName').value = '';
    document.getElementById('deptDescription').value = '';
    document.getElementById('deptIcon').value = 'bi-building';
    setPreset('#3B82F6');
    document.getElementById('deptAiAgent').value = '';
    document.getElementById('deptChatbot').value = '';
    document.getElementById('deptStrategy').value = 'round_robin';
    document.querySelectorAll('.user-check').forEach(cb => cb.checked = false);
    openDrawer();
    setTimeout(() => document.getElementById('deptName').focus(), 200);
});

function openEdit(id) {
    const d = DEPTS_DATA.find(x => x.id === id);
    if (!d) return;
    document.getElementById('drawerTitle').textContent = SLANG.dept_edit_title;
    document.getElementById('deptId').value = d.id;
    document.getElementById('deptName').value = d.name;
    document.getElementById('deptDescription').value = d.description || '';
    document.getElementById('deptIcon').value = d.icon || 'bi-building';
    setPreset(d.color || '#3B82F6');
    document.getElementById('deptAiAgent').value = d.default_ai_agent_id || '';
    document.getElementById('deptChatbot').value = d.default_chatbot_flow_id || '';
    document.getElementById('deptStrategy').value = d.assignment_strategy || 'round_robin';
    document.querySelectorAll('.user-check').forEach(cb => {
        cb.checked = d.user_ids.includes(parseInt(cb.value));
    });
    openDrawer();
}

/* ── CRUD ── */
async function saveDept() {
    const id   = document.getElementById('deptId').value;
    const name = document.getElementById('deptName').value.trim();
    if (!name) { document.getElementById('deptName').focus(); return; }

    const color = document.getElementById('deptColorText').value.trim()
               || document.getElementById('deptColorPicker').value;

    const userIds = [];
    document.querySelectorAll('.user-check:checked').forEach(cb => userIds.push(parseInt(cb.value)));

    const aiAgentVal = document.getElementById('deptAiAgent').value;
    const chatbotVal = document.getElementById('deptChatbot').value;

    const body = {
        name,
        description: document.getElementById('deptDescription').value.trim() || null,
        icon:  document.getElementById('deptIcon').value,
        color,
        default_ai_agent_id:     aiAgentVal ? parseInt(aiAgentVal) : null,
        default_chatbot_flow_id: chatbotVal ? parseInt(chatbotVal) : null,
        assignment_strategy: document.getElementById('deptStrategy').value,
        user_ids: userIds,
    };

    const btn = document.getElementById('btnSave');
    btn.disabled = true;

    try {
        const url    = id ? URL_UPDATE.replace('__ID__', id) : URL_STORE;
        const method = id ? 'PUT' : 'POST';
        const res    = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if (!data.success) {
            if (checkLimitReached(data)) return;
            toastr.error(data.message || Object.values(data.errors || {}).flat().join(', ') || SLANG.dept_error_save);
            return;
        }

        closeDrawer();
        const d = data.department;

        // Atualizar cache local
        const idx = DEPTS_DATA.findIndex(x => x.id === d.id);
        const cached = { ...d, user_ids: d.user_ids };
        if (idx >= 0) DEPTS_DATA[idx] = cached;
        else DEPTS_DATA.push(cached);

        // Recarregar página para refletir mudanças
        location.reload();
    } catch (e) {
        toastr.error(SLANG.dept_error_network);
    } finally {
        btn.disabled = false;
    }
}

let _deleteId  = null;
let _deleteBtn = null;

function deleteDept(id, btn) {
    _deleteId  = id;
    _deleteBtn = btn;
    confirmAction({
        title: SLANG.dept_del_title,
        message: SLANG.dept_del_msg,
        confirmText: SLANG.dept_del_confirm,
        onConfirm: () => _doDeleteDept(),
    });
}

async function _doDeleteDept() {
    if (!_deleteId) return;

    const row = _deleteBtn.closest('tr');
    const res = await fetch(URL_DESTROY.replace('__ID__', _deleteId), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF },
    });
    const data = await res.json();
    if (!data.success) { toastr.error(SLANG.dept_error_delete); return; }

    row.remove();
    toastr.success(SLANG.dept_deleted);

    // Remover do cache
    const idx = DEPTS_DATA.findIndex(x => x.id === _deleteId);
    if (idx >= 0) DEPTS_DATA.splice(idx, 1);

    if (!document.querySelector('#deptBody tr[data-id]')) {
        document.getElementById('deptBody').innerHTML = `
            <tr id="emptyRow"><td colspan="6">
                <div class="empty-state">
                    <i class="bi bi-building"></i>
                    ${SLANG.dept_empty}
                </div>
            </td></tr>`;
    }
}
</script>
@endpush
