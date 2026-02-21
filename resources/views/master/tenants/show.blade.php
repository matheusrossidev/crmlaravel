@extends('tenant.layouts.app')
@php
    $title    = 'Empresa: ' . $tenant->name;
    $pageIcon = 'buildings';
@endphp

@push('styles')
<style>
    .show-layout {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 22px;
        align-items: start;
    }
    @media (max-width: 960px) { .show-layout { grid-template-columns: 1fr; } }

    .show-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .show-card:last-child { margin-bottom: 0; }

    .show-card-header {
        padding: 16px 22px;
        border-bottom: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
    }
    .show-card-header h3 { margin: 0; display: flex; align-items: center; gap: 8px; }
    .show-card-body { padding: 22px; }

    .meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f7f8fa;
        font-size: 13px;
    }
    .meta-row:last-child { border-bottom: none; }
    .meta-label { color: #9ca3af; }
    .meta-value { font-weight: 600; color: #1a1d23; }

    .stat-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .stat-box {
        background: #f8fafc;
        border: 1px solid #e8eaf0;
        border-radius: 10px;
        padding: 16px;
        text-align: center;
    }
    .stat-box .stat-num { font-size: 26px; font-weight: 700; color: #1a1d23; }
    .stat-box .stat-lbl { font-size: 12px; color: #9ca3af; margin-top: 2px; }

    .users-table { width: 100%; border-collapse: collapse; }
    .users-table th {
        font-size: 11px; font-weight: 700; color: #9ca3af;
        text-transform: uppercase; letter-spacing: .06em;
        padding: 10px 22px; text-align: left;
        border-bottom: 1px solid #f0f2f7; background: #fafbfc;
    }
    .users-table td {
        padding: 12px 22px; font-size: 13px; color: #374151;
        border-bottom: 1px solid #f7f8fa; vertical-align: middle;
    }
    .users-table tr:last-child td { border-bottom: none; }

    .role-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 20px;
        font-size: 11px; font-weight: 600; text-transform: capitalize;
    }
    .role-admin   { background: #eff6ff; color: #2563EB; }
    .role-manager { background: #f0fdf4; color: #16a34a; }
    .role-viewer  { background: #fef9c3; color: #a16207; }

    .status-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 20px;
        font-size: 11px; font-weight: 600;
    }
    .status-active     { background: #f0fdf4; color: #16a34a; }
    .status-inactive   { background: #fef9c3; color: #a16207; }
    .status-suspended  { background: #fef2f2; color: #dc2626; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 6px; }
    .form-control {
        width: 100%; padding: 9px 12px;
        border: 1px solid #d1d5db; border-radius: 9px;
        font-size: 13.5px; color: #1a1d23;
        outline: none; transition: border-color .15s; background: #fff;
    }
    .form-control:focus { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }

    .btn-save {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #3B82F6; color: #fff;
        border: none; border-radius: 9px; font-size: 13.5px;
        font-weight: 600; cursor: pointer; transition: background .15s;
        width: 100%; justify-content: center;
    }
    .btn-save:hover { background: #2563EB; }
    .btn-save:disabled { opacity: .6; cursor: not-allowed; }

    .btn-danger {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #fef2f2; color: #dc2626;
        border: 1px solid #fecaca; border-radius: 9px; font-size: 13px;
        font-weight: 600; cursor: pointer; transition: all .15s;
        width: 100%; justify-content: center; margin-top: 10px;
    }
    .btn-danger:hover { background: #dc2626; color: #fff; }

    .btn-back {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 14px; background: #f4f6fb; color: #374151;
        border: 1px solid #e8eaf0; border-radius: 9px; font-size: 13px;
        font-weight: 600; cursor: pointer; text-decoration: none; margin-bottom: 18px;
        transition: background .15s;
    }
    .btn-back:hover { background: #e8eaf0; }

    .btn-add-user {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 6px 14px; background: #3B82F6; color: #fff;
        border: none; border-radius: 8px; font-size: 12.5px;
        font-weight: 600; cursor: pointer; transition: background .15s;
    }
    .btn-add-user:hover { background: #2563EB; }

    .btn-icon-act {
        display: inline-flex; align-items: center; justify-content: center;
        width: 30px; height: 30px; border-radius: 7px;
        border: 1px solid #e8eaf0; background: #f8fafc; color: #374151;
        cursor: pointer; transition: all .15s; font-size: 13px; margin-left: 4px;
    }
    .btn-icon-act:hover { background: #e8eaf0; }
    .btn-icon-danger { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
    .btn-icon-danger:hover { background: #dc2626; color: #fff; border-color: #dc2626; }

    /* Modal */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.45); z-index: 9000;
        align-items: center; justify-content: center;
    }
    .modal-overlay.show { display: flex; }
    .modal-box {
        background: #fff; border-radius: 16px; padding: 28px 30px;
        width: 100%; max-width: 440px; box-shadow: 0 20px 60px rgba(0,0,0,.18);
    }
    .modal-title {
        font-size: 17px; font-weight: 700; color: #1a1d23;
        margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
    }
    .modal-actions {
        display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;
    }
    .btn-modal-cancel {
        padding: 8px 18px; background: #f4f6fb; color: #374151;
        border: 1px solid #e8eaf0; border-radius: 9px; font-size: 13.5px;
        font-weight: 600; cursor: pointer;
    }
    .btn-modal-save {
        padding: 8px 20px; background: #3B82F6; color: #fff;
        border: none; border-radius: 9px; font-size: 13.5px;
        font-weight: 600; cursor: pointer; transition: background .15s;
    }
    .btn-modal-save:hover { background: #2563EB; }
    .btn-modal-save:disabled { opacity: .6; cursor: not-allowed; }
</style>
@endpush

@section('content')
<div class="page-container">

    <a href="{{ route('master.tenants') }}" class="btn-back">
        <i class="bi bi-arrow-left"></i> Voltar para empresas
    </a>

    <div class="show-layout">

        {{-- ── Coluna esquerda: stats + usuários ── --}}
        <div>

            {{-- Stats --}}
            <div class="show-card">
                <div class="show-card-header">
                    <h3><i class="bi bi-bar-chart-line" style="color:#3B82F6;"></i> Estatísticas</h3>
                </div>
                <div class="show-card-body">
                    <div class="stat-grid">
                        <div class="stat-box">
                            <div class="stat-num">{{ $leadsStats['total'] }}</div>
                            <div class="stat-lbl">Total de leads</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-num" style="color:#10B981;">{{ $leadsStats['won'] }}</div>
                            <div class="stat-lbl">Ganhos</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-num" style="color:#EF4444;">{{ $leadsStats['lost'] }}</div>
                            <div class="stat-lbl">Perdidos</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-num" style="color:#3B82F6;">{{ $leadsStats['active'] }}</div>
                            <div class="stat-lbl">Ativos</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Usuários --}}
            <div class="show-card">
                <div class="show-card-header">
                    <h3><i class="bi bi-people" style="color:#3B82F6;"></i> Usuários (<span id="usersCount">{{ count($users) }}</span>)</h3>
                    <button class="btn-add-user" onclick="openUserModal()">
                        <i class="bi bi-plus-lg"></i> Adicionar
                    </button>
                </div>
                @if(count($users) === 0)
                <div id="emptyUsers" style="text-align:center;padding:30px;color:#9ca3af;">Nenhum usuário</div>
                @else
                <div id="emptyUsers" style="display:none;text-align:center;padding:30px;color:#9ca3af;">Nenhum usuário</div>
                @endif
                <table class="users-table" id="usersTable" @if(count($users) === 0) style="display:none" @endif>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Papel</th>
                            <th>Desde</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="usersBody">
                        @foreach($users as $u)
                        <tr id="user-row-{{ $u->id }}">
                            <td style="font-weight:600;color:#1a1d23;">{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td><span class="role-badge role-{{ $u->role }}">{{ ucfirst($u->role) }}</span></td>
                            <td style="font-size:12px;color:#9ca3af;">{{ $u->created_at->format('d/m/Y') }}</td>
                            <td style="text-align:right;white-space:nowrap;">
                                <button class="btn-icon-act" title="Editar"
                                    onclick="openUserModal({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ addslashes($u->email) }}', '{{ $u->role }}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn-icon-act btn-icon-danger" title="Excluir"
                                    onclick="deleteUser({{ $u->id }}, '{{ addslashes($u->name) }}')">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>

        {{-- ── Coluna direita: info + editar ── --}}
        <div>

            {{-- Info da empresa --}}
            <div class="show-card">
                <div class="show-card-header">
                    <h3><i class="bi bi-buildings" style="color:#3B82F6;"></i> Dados da Empresa</h3>
                </div>
                <div class="show-card-body">
                    <div class="meta-row">
                        <span class="meta-label">Nome</span>
                        <span class="meta-value">{{ $tenant->name }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Slug</span>
                        <span class="meta-value" style="font-family:monospace;font-size:12px;">{{ $tenant->slug }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Status</span>
                        <span class="status-badge status-{{ $tenant->status }}">{{ ucfirst($tenant->status) }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Plano</span>
                        <span class="meta-value">{{ ucfirst($tenant->plan) }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Criada em</span>
                        <span class="meta-value">{{ $tenant->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Editar empresa --}}
            <div class="show-card">
                <div class="show-card-header">
                    <h3><i class="bi bi-pencil-square" style="color:#3B82F6;"></i> Editar</h3>
                </div>
                <div class="show-card-body">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" id="editStatus">
                            <option value="active"    {{ $tenant->status === 'active'    ? 'selected' : '' }}>Ativo</option>
                            <option value="inactive"  {{ $tenant->status === 'inactive'  ? 'selected' : '' }}>Inativo</option>
                            <option value="suspended" {{ $tenant->status === 'suspended' ? 'selected' : '' }}>Suspenso</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Plano</label>
                        <select class="form-control" id="editPlan">
                            <option value="free"       {{ $tenant->plan === 'free'       ? 'selected' : '' }}>Free</option>
                            <option value="starter"    {{ $tenant->plan === 'starter'    ? 'selected' : '' }}>Starter</option>
                            <option value="pro"        {{ $tenant->plan === 'pro'        ? 'selected' : '' }}>Pro</option>
                            <option value="enterprise" {{ $tenant->plan === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Máx. usuários <small style="color:#9ca3af;">(0 = ilimitado)</small></label>
                        <input type="number" class="form-control" id="editMaxUsers"
                               value="{{ $tenant->max_users ?? 0 }}" min="0">
                    </div>
                    <div class="form-group">
                        <label>Máx. leads <small style="color:#9ca3af;">(0 = ilimitado)</small></label>
                        <input type="number" class="form-control" id="editMaxLeads"
                               value="{{ $tenant->max_leads ?? 0 }}" min="0">
                    </div>
                    <div class="form-group">
                        <label>Máx. pipelines <small style="color:#9ca3af;">(0 = ilimitado)</small></label>
                        <input type="number" class="form-control" id="editMaxPipelines"
                               value="{{ $tenant->max_pipelines ?? 0 }}" min="0">
                    </div>
                    <button class="btn-save" id="btnUpdateTenant" onclick="updateTenant()">
                        <i class="bi bi-check2"></i> Salvar alterações
                    </button>

                    <button class="btn-danger" onclick="deleteTenant()">
                        <i class="bi bi-trash3"></i> Excluir empresa
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

{{-- Modal Criar/Editar Usuário --}}
<div class="modal-overlay" id="userModal">
    <div class="modal-box">
        <div class="modal-title">
            <i class="bi bi-person-gear" style="color:#3B82F6;"></i>
            <span id="userModalTitle">Adicionar usuário</span>
        </div>
        <div class="form-group">
            <label>Nome</label>
            <input type="text" class="form-control" id="uName" placeholder="Nome completo">
        </div>
        <div class="form-group">
            <label>E-mail</label>
            <input type="email" class="form-control" id="uEmail" placeholder="email@empresa.com">
        </div>
        <div class="form-group">
            <label>Papel</label>
            <select class="form-control" id="uRole">
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="viewer">Viewer</option>
            </select>
        </div>
        <div class="form-group" id="uPasswordGroup">
            <label id="uPasswordLabel">Senha</label>
            <input type="password" class="form-control" id="uPassword" placeholder="Mínimo 8 caracteres">
        </div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeUserModal()">Cancelar</button>
            <button class="btn-modal-save" id="btnSaveUser" onclick="saveUser()">Salvar</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const updateUrl = "{{ route('master.tenants.update', $tenant) }}";
const deleteUrl = "{{ route('master.tenants.destroy', $tenant) }}";
const indexUrl  = "{{ route('master.tenants') }}";
const csrf      = document.querySelector('meta[name=csrf-token]').content;

async function updateTenant() {
    const btn = document.getElementById('btnUpdateTenant');
    btn.disabled = true;

    try {
        const res  = await fetch(updateUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({
                status:        document.getElementById('editStatus').value,
                plan:          document.getElementById('editPlan').value,
                max_users:     parseInt(document.getElementById('editMaxUsers').value) || 0,
                max_leads:     parseInt(document.getElementById('editMaxLeads').value) || 0,
                max_pipelines: parseInt(document.getElementById('editMaxPipelines').value) || 0,
            }),
        });
        const data = await res.json();
        if (data.success) {
            toastr.success(data.message ?? 'Empresa atualizada!');
        } else {
            toastr.error(data.message ?? 'Erro ao atualizar.');
        }
    } catch { toastr.error('Erro de conexão.'); }
    btn.disabled = false;
}

function deleteTenant() {
    confirmAction({
        title: 'Excluir empresa',
        message: 'Excluir esta empresa e TODOS os seus dados?',
        confirmText: 'Excluir',
        onConfirm: async () => {
            try {
                const res  = await fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success('Empresa excluída.');
                    setTimeout(() => window.location.href = indexUrl, 800);
                } else {
                    toastr.error(data.message ?? 'Erro ao excluir.');
                }
            } catch { toastr.error('Erro de conexão.'); }
        },
    });
}

// ── Gerenciamento de usuários ──────────────────────────────────────────────────
const tenantId = {{ $tenant->id }};
const usersStoreUrl  = `/master/empresas/${tenantId}/usuarios`;

let editingUserId = null;

function openUserModal(id = null, name = '', email = '', role = 'manager') {
    editingUserId = id;
    document.getElementById('userModalTitle').textContent = id ? 'Editar usuário' : 'Adicionar usuário';
    document.getElementById('uName').value   = name;
    document.getElementById('uEmail').value  = email;
    document.getElementById('uRole').value   = role;
    document.getElementById('uPassword').value = '';
    document.getElementById('uPasswordLabel').textContent = id ? 'Nova senha (deixe em branco para não alterar)' : 'Senha';
    document.getElementById('uPasswordGroup').style.display = 'block';
    document.getElementById('userModal').classList.add('show');
    setTimeout(() => document.getElementById('uName').focus(), 80);
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('show');
    editingUserId = null;
}

document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) closeUserModal();
});

async function saveUser() {
    const btn  = document.getElementById('btnSaveUser');
    const name = document.getElementById('uName').value.trim();
    const email = document.getElementById('uEmail').value.trim();
    const role = document.getElementById('uRole').value;
    const pwd  = document.getElementById('uPassword').value;

    if (!name || !email) { toastr.warning('Preencha nome e e-mail.'); return; }
    if (!editingUserId && !pwd) { toastr.warning('Informe uma senha.'); return; }

    btn.disabled = true;
    const url    = editingUserId ? `/master/empresas/${tenantId}/usuarios/${editingUserId}` : usersStoreUrl;
    const method = editingUserId ? 'PUT' : 'POST';
    const body   = { name, email, role };
    if (pwd) body.password = pwd;

    try {
        const res  = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if (res.ok && data.success) {
            toastr.success(editingUserId ? 'Usuário atualizado.' : 'Usuário criado.');
            closeUserModal();

            if (editingUserId) {
                // Update row in-place
                const row = document.getElementById(`user-row-${editingUserId}`);
                if (row) {
                    const cells = row.querySelectorAll('td');
                    cells[0].textContent = name;
                    cells[1].textContent = email;
                    cells[2].innerHTML   = `<span class="role-badge role-${role}">${role.charAt(0).toUpperCase()+role.slice(1)}</span>`;
                    // Update edit button args
                    const editBtn = row.querySelector('.btn-icon-act:not(.btn-icon-danger)');
                    if (editBtn) editBtn.setAttribute('onclick',
                        `openUserModal(${editingUserId}, '${name.replace(/'/g,"\\'")}', '${email.replace(/'/g,"\\'")}', '${role}')`);
                }
            } else {
                // Append new row
                const u    = data.user;
                const tbody = document.getElementById('usersBody');
                const tr   = document.createElement('tr');
                tr.id = `user-row-${u.id}`;
                tr.innerHTML = `
                    <td style="font-weight:600;color:#1a1d23;">${escapeHtml(u.name)}</td>
                    <td>${escapeHtml(u.email)}</td>
                    <td><span class="role-badge role-${u.role}">${u.role.charAt(0).toUpperCase()+u.role.slice(1)}</span></td>
                    <td style="font-size:12px;color:#9ca3af;">${u.created_at}</td>
                    <td style="text-align:right;white-space:nowrap;">
                        <button class="btn-icon-act" title="Editar"
                            onclick="openUserModal(${u.id}, '${u.name.replace(/'/g,"\\'")}', '${u.email.replace(/'/g,"\\'")}', '${u.role}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-icon-act btn-icon-danger" title="Excluir"
                            onclick="deleteUser(${u.id}, '${u.name.replace(/'/g,"\\'")}')">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </td>`;
                tbody.appendChild(tr);
                // Show table, hide empty
                document.getElementById('usersTable').style.display = '';
                document.getElementById('emptyUsers').style.display  = 'none';
                document.getElementById('usersCount').textContent =
                    parseInt(document.getElementById('usersCount').textContent) + 1;
            }
        } else {
            const first = data.errors ? Object.values(data.errors)[0][0] : (data.message ?? 'Erro.');
            toastr.error(first);
        }
    } catch { toastr.error('Erro de conexão.'); }
    btn.disabled = false;
}

function deleteUser(id, name) {
    confirmAction({
        title: 'Excluir usuário',
        message: `Excluir o usuário <strong>${escapeHtml(name)}</strong>?`,
        confirmText: 'Excluir',
        onConfirm: async () => {
            try {
                const res  = await fetch(`/master/empresas/${tenantId}/usuarios/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success('Usuário excluído.');
                    const row = document.getElementById(`user-row-${id}`);
                    if (row) row.remove();
                    const count = parseInt(document.getElementById('usersCount').textContent) - 1;
                    document.getElementById('usersCount').textContent = count;
                    if (count === 0) {
                        document.getElementById('usersTable').style.display = 'none';
                        document.getElementById('emptyUsers').style.display  = '';
                    }
                } else {
                    toastr.error(data.message ?? 'Erro ao excluir.');
                }
            } catch { toastr.error('Erro de conexão.'); }
        },
    });
}
</script>
@endpush
