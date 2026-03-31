@extends('tenant.layouts.app')

@php
    $title    = 'Meus Clientes';
    $pageIcon = 'building';
@endphp

@push('styles')
<style>
.clients-card {
    background: #fff;
    border: 1.5px solid #e8eaf0;
    border-radius: 14px;
    overflow: hidden;
}
.clients-card-header {
    padding: 16px 22px;
    border-bottom: 1px solid #f0f2f7;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.clients-table { width: 100%; border-collapse: collapse; }
.clients-table thead th {
    padding: 11px 20px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #9ca3af;
    border-bottom: 1px solid #f0f2f7;
    white-space: nowrap;
}
.clients-table tbody tr { border-bottom: 1px solid #f9fafb; transition: background .1s; }
.clients-table tbody tr:last-child { border-bottom: none; }
.clients-table tbody tr:hover { background: #fafafa; }
.clients-table tbody td { padding: 13px 20px; }

.tenant-avatar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #0085f3;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.status-badge.active   { background: #ecfdf5; color: #059669; }
.status-badge.trial    { background: #eff6ff; color: #2563eb; }
.status-badge.suspended,
.status-badge.inactive { background: #f3f4f6; color: #6b7280; }
.status-badge.partner  { background: #eff6ff; color: #0085f3; }

.btn-access {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 13px;
    background: #eff6ff;
    color: #0085f3;
    border: 1.5px solid #bfdbfe;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}
.btn-access:hover { background: #dbeafe; }

.code-badge {
    padding: 5px 12px;
    background: #eff6ff;
    border: 1.5px solid #bfdbfe;
    border-radius: 8px;
    font-family: monospace;
    font-size: 13px;
    font-weight: 700;
    color: #1d4ed8;
    letter-spacing: .05em;
}
.btn-copy-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: #0085f3;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}
.btn-copy-link:hover { background: #0070d1; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">PORTAL DO PARCEIRO</div>
        <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">Meus Clientes</h1>
        <p style="font-size:13.5px;color:#677489;margin:0;">Empresas que se cadastraram com seu código de indicação.</p>
    </div>

    <div class="clients-card">
        <div class="clients-card-header">
            <div>
                <span style="font-size:14px;font-weight:700;color:#1a1d23;">Clientes Indicados</span>
                <span style="font-size:13px;color:#9ca3af;margin-left:8px;">{{ $clients->count() }} empresa(s)</span>
            </div>
            @if($agencyCode)
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="code-badge">{{ $agencyCode->code }}</span>
                <button class="btn-copy-link"
                        onclick="navigator.clipboard.writeText('{{ url('/register?agency=' . $agencyCode->code) }}').then(()=>toastr.success('Link copiado!'))">
                    <i class="bi bi-clipboard"></i> Copiar Link
                </button>
            </div>
            @endif
        </div>

        @if($clients->isEmpty())
        <div style="text-align:center;padding:56px 24px;">
            <i class="bi bi-building" style="font-size:44px;color:#d1d5db;display:block;margin-bottom:12px;"></i>
            <p style="font-size:15px;font-weight:600;color:#374151;margin:0 0 6px;">Nenhum cliente ainda</p>
            <p style="font-size:13px;color:#9ca3af;margin:0;">Compartilhe seu link de indicação para trazer clientes.</p>
        </div>
        @else
        <div style="overflow-x:auto;">
            <table class="clients-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Plano</th>
                        <th>Status</th>
                        <th>Cadastro</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $client)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="tenant-avatar">{{ strtoupper(substr($client->name, 0, 1)) }}</div>
                                <span style="font-size:14px;font-weight:600;color:#1a1d23;">{{ $client->name }}</span>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;color:#374151;text-transform:capitalize;">{{ $client->plan }}</span>
                        </td>
                        <td>
                            @php
                                $st = $client->status;
                                $stLabel = match($st) {
                                    'active'    => 'Ativo',
                                    'trial'     => 'Trial',
                                    'suspended' => 'Suspenso',
                                    'inactive'  => 'Inativo',
                                    'partner'   => 'Parceiro',
                                    default     => ucfirst($st),
                                };
                                $stClass = in_array($st, ['active','trial','suspended','inactive','partner']) ? $st : 'inactive';
                            @endphp
                            <span class="status-badge {{ $stClass }}">
                                <i class="bi bi-circle-fill" style="font-size:6px;"></i>
                                {{ $stLabel }}
                            </span>
                        </td>
                        <td style="font-size:13px;color:#6b7280;white-space:nowrap;">
                            {{ $client->created_at->translatedFormat('d M Y') }}
                        </td>
                        <td>
                            <form method="POST" action="{{ route('agency.access.enter', $client->id) }}" style="margin:0;display:inline;">
                                @csrf
                                <button type="submit" class="btn-access">
                                    <i class="bi bi-box-arrow-in-right"></i> Acessar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
@endsection
