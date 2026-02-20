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
                    <h3><i class="bi bi-people" style="color:#3B82F6;"></i> Usuários ({{ count($users) }})</h3>
                </div>
                @if(count($users) === 0)
                <div style="text-align:center;padding:30px;color:#9ca3af;">Nenhum usuário</div>
                @else
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Papel</th>
                            <th>Desde</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $u)
                        <tr>
                            <td style="font-weight:600;color:#1a1d23;">{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td><span class="role-badge role-{{ $u->role }}">{{ ucfirst($u->role) }}</span></td>
                            <td style="font-size:12px;color:#9ca3af;">{{ $u->created_at->format('d/m/Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
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
</script>
@endpush
