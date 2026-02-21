@extends('tenant.layouts.app')
@php
    $title    = 'Painel Master — Empresas';
    $pageIcon = 'shield-check';
@endphp

@push('styles')
<style>
    .tenants-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
    }
    .tenants-card-header {
        padding: 16px 22px;
        border-bottom: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .tenants-card-header h3 {
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .tenants-table { width: 100%; border-collapse: collapse; }
    .tenants-table th {
        font-size: 11px; font-weight: 700; color: #9ca3af;
        text-transform: uppercase; letter-spacing: .06em;
        padding: 10px 22px; text-align: left;
        border-bottom: 1px solid #f0f2f7; background: #fafbfc;
    }
    .tenants-table td {
        padding: 13px 22px; font-size: 13.5px; color: #374151;
        border-bottom: 1px solid #f7f8fa; vertical-align: middle;
    }
    .tenants-table tr:last-child td { border-bottom: none; }
    .tenants-table tr:hover td { background: #fafbfc; }

    .status-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 20px;
        font-size: 11px; font-weight: 600;
    }
    .status-active     { background: #f0fdf4; color: #16a34a; }
    .status-inactive   { background: #fef9c3; color: #a16207; }
    .status-suspended  { background: #fef2f2; color: #dc2626; }

    .plan-badge {
        display: inline-flex; align-items: center;
        padding: 3px 10px; border-radius: 20px;
        font-size: 11px; font-weight: 600;
        background: #eff6ff; color: #2563EB;
        text-transform: capitalize;
    }

    .stat-mini {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 12px; color: #6b7280;
    }

    .btn-icon {
        width: 32px; height: 32px;
        border: 1px solid #e8eaf0;
        border-radius: 8px;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        font-size: 14px;
        cursor: pointer;
        transition: all .15s;
    }
    .btn-icon:hover { background: #f4f6fb; color: #3B82F6; border-color: #dbeafe; }
    .btn-icon.danger:hover { background: #fef2f2; color: #EF4444; border-color: #fecaca; }

    .btn-new {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 16px;
        background: #3B82F6; color: #fff;
        border: none; border-radius: 9px;
        font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
    }
    .btn-new:hover { background: #2563EB; }

    .empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
    .empty-state i { font-size: 36px; margin-bottom: 12px; display: block; }

    /* Drawer */
    .drawer-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,.35);
        z-index: 300;
    }
    .drawer-overlay.open { display: block; }
    .drawer {
        position: fixed;
        top: 0; right: -440px;
        width: 440px; height: 100vh;
        background: #fff;
        z-index: 301;
        transition: right .25s cubic-bezier(.4,0,.2,1);
        display: flex;
        flex-direction: column;
        box-shadow: -4px 0 24px rgba(0,0,0,.1);
    }
    .drawer.open { right: 0; }
    .drawer-header {
        padding: 18px 22px;
        border-bottom: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 15px;
        font-weight: 700;
        color: #1a1d23;
    }
    .drawer-body { padding: 22px; flex: 1; overflow-y: auto; }
    .drawer-footer {
        padding: 16px 22px;
        border-top: 1px solid #f0f2f7;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 6px; }
    .form-control {
        width: 100%; padding: 9px 12px;
        border: 1px solid #d1d5db; border-radius: 9px;
        font-size: 13.5px; color: #1a1d23;
        outline: none; transition: border-color .15s; background: #fff;
        box-sizing: border-box;
    }
    .form-control:focus { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
    .form-error { font-size: 12px; color: #EF4444; margin-top: 4px; }

    .btn-save {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #3B82F6; color: #fff;
        border: none; border-radius: 9px; font-size: 13.5px;
        font-weight: 600; cursor: pointer; transition: background .15s;
    }
    .btn-save:hover { background: #2563EB; }
    .btn-save:disabled { opacity: .6; cursor: not-allowed; }
    .btn-cancel {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #f4f6fb; color: #374151;
        border: 1px solid #e8eaf0; border-radius: 9px;
        font-size: 13.5px; font-weight: 600; cursor: pointer;
    }
    .btn-cancel:hover { background: #e8eaf0; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div class="tenants-card">
        <div class="tenants-card-header">
            <h3><i class="bi bi-buildings" style="color:#3B82F6;"></i> Empresas ({{ $tenants->total() }})</h3>
            <button class="btn-new" onclick="openDrawer()">
                <i class="bi bi-plus-lg"></i> Nova empresa
            </button>
        </div>

        @if($tenants->isEmpty())
        <div class="empty-state">
            <i class="bi bi-buildings"></i>
            <p style="font-weight:600;color:#374151;">Nenhuma empresa cadastrada</p>
        </div>
        @else
        <table class="tenants-table">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Status</th>
                    <th>Plano</th>
                    <th>Usuários</th>
                    <th>Leads</th>
                    <th>Criada em</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($tenants as $tenant)
                <tr id="tenant-row-{{ $tenant->id }}">
                    <td>
                        <div style="font-weight:600;color:#1a1d23;">{{ $tenant->name }}</div>
                        <div style="font-size:11px;color:#9ca3af;">{{ $tenant->slug }}</div>
                    </td>
                    <td>
                        <span class="status-badge status-{{ $tenant->status }}">
                            {{ ['active'=>'Ativo','inactive'=>'Inativo','suspended'=>'Suspenso'][$tenant->status] ?? ucfirst($tenant->status) }}
                        </span>
                    </td>
                    <td><span class="plan-badge">{{ $tenant->plan }}</span></td>
                    <td>
                        <span class="stat-mini">
                            <i class="bi bi-people"></i> {{ $tenant->users_count }}
                        </span>
                    </td>
                    <td>
                        <span class="stat-mini">
                            <i class="bi bi-person-lines-fill"></i> {{ $tenant->leads_count }}
                        </span>
                    </td>
                    <td style="font-size:12px;color:#9ca3af;">{{ $tenant->created_at->format('d/m/Y') }}</td>
                    <td style="text-align:right;">
                        <button class="btn-icon danger" title="Excluir empresa"
                            onclick="deleteTenant({{ $tenant->id }}, '{{ addslashes($tenant->name) }}')">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($tenants->hasPages())
        <div style="padding:16px 22px;border-top:1px solid #f0f2f7;">
            {{ $tenants->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection

{{-- Drawer Nova Empresa --}}
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="drawer">
    <div class="drawer-header">
        <span><i class="bi bi-building-add" style="color:#3B82F6;margin-right:8px;"></i> Nova Empresa</span>
        <button onclick="closeDrawer()" style="background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;line-height:1;">&times;</button>
    </div>
    <div class="drawer-body">
        <div class="form-group">
            <label>Nome da empresa</label>
            <input type="text" class="form-control" id="tenantName" placeholder="Acme Corp">
            <div class="form-error d-none" id="errTName"></div>
        </div>
        <div class="form-group">
            <label>Nome do administrador</label>
            <input type="text" class="form-control" id="adminName" placeholder="João Silva">
        </div>
        <div class="form-group">
            <label>E-mail do administrador</label>
            <input type="email" class="form-control" id="adminEmail" placeholder="admin@acme.com">
            <div class="form-error d-none" id="errTEmail"></div>
        </div>
        <div class="form-group">
            <label>Senha do administrador</label>
            <input type="password" class="form-control" id="adminPassword" placeholder="Mínimo 8 caracteres">
            <div class="form-error d-none" id="errTPwd"></div>
        </div>
        <div class="form-group">
            <label>Plano</label>
            <select class="form-control" id="tenantPlan">
                <option value="free">Free</option>
                <option value="starter">Starter</option>
                <option value="pro" selected>Pro</option>
                <option value="enterprise">Enterprise</option>
            </select>
        </div>
    </div>
    <div class="drawer-footer">
        <button class="btn-cancel" onclick="closeDrawer()">Cancelar</button>
        <button class="btn-save" id="btnCreateTenant" onclick="createTenant()">
            <i class="bi bi-plus-lg"></i> Criar empresa
        </button>
    </div>
</div>

@push('scripts')
<script>
const storeUrl = "{{ route('master.tenants.store') }}";
const csrf     = document.querySelector('meta[name=csrf-token]').content;

function openDrawer() {
    document.getElementById('drawerOverlay').classList.add('open');
    document.getElementById('drawer').classList.add('open');
    setTimeout(() => document.getElementById('tenantName').focus(), 80);
}
function closeDrawer() {
    document.getElementById('drawerOverlay').classList.remove('open');
    document.getElementById('drawer').classList.remove('open');
}

async function createTenant() {
    clearErrors(['errTName','errTEmail','errTPwd']);
    const btn = document.getElementById('btnCreateTenant');
    btn.disabled = true;

    const body = {
        name:       document.getElementById('tenantName').value,
        admin_name: document.getElementById('adminName').value,
        email:      document.getElementById('adminEmail').value,
        password:   document.getElementById('adminPassword').value,
        plan:       document.getElementById('tenantPlan').value,
    };

    try {
        const res  = await fetch(storeUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if ((res.status === 200 || res.status === 201) && data.success) {
            toastr.success('Empresa criada com sucesso!');
            setTimeout(() => location.reload(), 900);
        } else if (data.errors) {
            showErrors(data.errors);
        } else {
            toastr.error(data.message ?? 'Erro ao criar empresa.');
        }
    } catch { toastr.error('Erro de conexão.'); }
    btn.disabled = false;
}

function deleteTenant(id, name) {
    confirmAction({
        title: 'Excluir empresa',
        message: `Excluir a empresa <strong>${escapeHtml(name)}</strong> e TODOS os seus dados?`,
        confirmText: 'Excluir',
        onConfirm: async () => {
            try {
                const res  = await fetch(`/master/empresas/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success('Empresa excluída.');
                    const row = document.getElementById(`tenant-row-${id}`);
                    if (row) row.remove();
                } else {
                    toastr.error(data.message ?? 'Erro ao excluir.');
                }
            } catch { toastr.error('Erro de conexão.'); }
        },
    });
}

function clearErrors(ids) {
    ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.textContent = ''; el.classList.add('d-none'); }
    });
}

function showErrors(errors) {
    const map = { name: 'errTName', email: 'errTEmail', password: 'errTPwd' };
    Object.keys(map).forEach(f => {
        if (errors[f]) {
            const el = document.getElementById(map[f]);
            if (el) { el.textContent = errors[f][0]; el.classList.remove('d-none'); }
        }
    });
}
</script>
@endpush
