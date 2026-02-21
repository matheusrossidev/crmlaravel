@extends('tenant.layouts.app')
@php
    $title    = 'Painel Master — Empresas';
    $pageIcon = 'shield-check';
@endphp

@push('styles')
<style>
    .master-layout {
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 22px;
        align-items: start;
    }
    @media (max-width: 960px) { .master-layout { grid-template-columns: 1fr; } }

    .master-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
    }
    .master-card-header {
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
    .master-card-header h3 { margin: 0; display: flex; align-items: center; gap: 8px; }
    .master-card-body { padding: 22px; }

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
    .tenants-table tr:hover td { background: #fafbfc; cursor: pointer; }

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

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 6px; }
    .form-control {
        width: 100%; padding: 9px 12px;
        border: 1px solid #d1d5db; border-radius: 9px;
        font-size: 13.5px; color: #1a1d23;
        outline: none; transition: border-color .15s; background: #fff;
    }
    .form-control:focus { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
    .form-error { font-size: 12px; color: #EF4444; margin-top: 4px; }

    .btn-save {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; background: #3B82F6; color: #fff;
        border: none; border-radius: 9px; font-size: 13.5px;
        font-weight: 600; cursor: pointer; transition: background .15s; width: 100%;
        justify-content: center;
    }
    .btn-save:hover { background: #2563EB; }
    .btn-save:disabled { opacity: .6; cursor: not-allowed; }

    .stat-mini {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 12px; color: #6b7280;
    }

    .empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
    .empty-state i { font-size: 36px; margin-bottom: 12px; display: block; }
</style>
@endpush

@section('content')
<div class="page-container">
    <div class="master-layout">

        {{-- ── Lista de empresas ── --}}
        <div class="master-card">
            <div class="master-card-header">
                <h3><i class="bi bi-buildings" style="color:#3B82F6;"></i> Empresas ({{ $tenants->total() }})</h3>
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
                    <tr>
                        <td>
                            <div style="font-weight:600;color:#1a1d23;">{{ $tenant->name }}</div>
                            <div style="font-size:11px;color:#9ca3af;">{{ $tenant->slug }}</div>
                        </td>
                        <td>
                            <span class="status-badge status-{{ $tenant->status }}">
                                {{ ucfirst($tenant->status) }}
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
                        <td style="text-align:right;white-space:nowrap;">
                            <a href="{{ route('master.tenants.show', $tenant) }}"
                               style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;
                                      background:#eff6ff;color:#2563EB;border:1px solid #bfdbfe;
                                      border-radius:7px;font-size:12px;font-weight:600;text-decoration:none;
                                      transition:background .15s;"
                               onmouseover="this.style.background='#dbeafe'"
                               onmouseout="this.style.background='#eff6ff'">
                                <i class="bi bi-people-fill"></i> Gerenciar
                            </a>
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

        {{-- ── Form nova empresa ── --}}
        <div class="master-card">
            <div class="master-card-header">
                <h3><i class="bi bi-building-add" style="color:#3B82F6;"></i> Nova Empresa</h3>
            </div>
            <div class="master-card-body">
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
                <button class="btn-save" id="btnCreateTenant" onclick="createTenant()">
                    <i class="bi bi-plus-lg"></i> Criar empresa
                </button>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
const storeUrl = "{{ route('master.tenants.store') }}";
const csrf     = document.querySelector('meta[name=csrf-token]').content;

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
