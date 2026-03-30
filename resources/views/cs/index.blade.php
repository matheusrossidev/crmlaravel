@extends('cs.layouts.app')
@php $title = 'Contas'; @endphp

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">
            <i class="bi bi-people" style="color:#0085f3;"></i> Contas de Clientes
        </h1>
        <p style="font-size:13px;color:#6b7280;margin:0;">{{ $tenantsData->count() }} contas encontradas</p>
    </div>
</div>

{{-- Filters --}}
<div class="m-card" style="margin-bottom:20px;">
    <div style="padding:16px 22px;">
        <form method="GET" action="{{ route('cs.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1;min-width:180px;">
                <label style="display:block;font-size:11.5px;font-weight:600;color:#677489;margin-bottom:4px;">Buscar</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nome da empresa..."
                    style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
            </div>
            <div style="min-width:130px;">
                <label style="display:block;font-size:11.5px;font-weight:600;color:#677489;margin-bottom:4px;">Status</label>
                <select name="status" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                    <option value="">Todos</option>
                    <option value="trial" {{ ($filters['status'] ?? '') === 'trial' ? 'selected' : '' }}>Trial</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Ativo</option>
                    <option value="suspended" {{ ($filters['status'] ?? '') === 'suspended' ? 'selected' : '' }}>Suspenso</option>
                    <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inativo</option>
                </select>
            </div>
            <div style="min-width:130px;">
                <label style="display:block;font-size:11.5px;font-weight:600;color:#677489;margin-bottom:4px;">Health</label>
                <select name="health" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                    <option value="">Todos</option>
                    <option value="red" {{ ($filters['health'] ?? '') === 'red' ? 'selected' : '' }}>🔴 Inativo (0-3)</option>
                    <option value="yellow" {{ ($filters['health'] ?? '') === 'yellow' ? 'selected' : '' }}>🟡 Atenção (4-6)</option>
                    <option value="green" {{ ($filters['health'] ?? '') === 'green' ? 'selected' : '' }}>🟢 Ativo (7-10)</option>
                </select>
            </div>
            <div style="min-width:130px;">
                <label style="display:block;font-size:11.5px;font-weight:600;color:#677489;margin-bottom:4px;">Ordenar</label>
                <select name="sort" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                    <option value="health" {{ ($filters['sort'] ?? '') === 'health' ? 'selected' : '' }}>Health (piores primeiro)</option>
                    <option value="inactive" {{ ($filters['sort'] ?? '') === 'inactive' ? 'selected' : '' }}>Dias inativos</option>
                    <option value="name" {{ ($filters['sort'] ?? '') === 'name' ? 'selected' : '' }}>Nome</option>
                </select>
            </div>
            <button type="submit" class="m-btn m-btn-primary m-btn-sm" style="height:37px;">
                <i class="bi bi-search"></i> Filtrar
            </button>
            @if(array_filter($filters))
                <a href="{{ route('cs.index') }}" class="m-btn m-btn-ghost m-btn-sm" style="height:37px;text-decoration:none;">Limpar</a>
            @endif
        </form>
    </div>
</div>

{{-- Table --}}
<div class="m-card">
    <div style="overflow-x:auto;">
        <table class="m-table">
            <thead>
                <tr>
                    <th>Health</th>
                    <th>Empresa</th>
                    <th>WhatsApp</th>
                    <th>Plano</th>
                    <th>Status</th>
                    <th>Último Login</th>
                    <th>Dias Inativo</th>
                    <th>Msgs WA (30d)</th>
                    <th>Leads (30d)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenantsData as $data)
                @php
                    $t = $data['tenant'];
                    $healthClass = $data['health'] >= 7 ? 'health-green' : ($data['health'] >= 4 ? 'health-yellow' : 'health-red');
                @endphp
                <tr>
                    <td style="text-align:center;">
                        <span class="health-dot {{ $healthClass }}" title="Score: {{ $data['health'] }}/10"></span>
                        <span style="font-size:11px;color:#6b7280;margin-left:4px;">{{ $data['health'] }}</span>
                    </td>
                    <td>
                        <div style="font-weight:600;color:#1a1d23;">{{ $t->name }}</div>
                        <div style="font-size:11.5px;color:#9ca3af;">ID: {{ $t->id }}</div>
                    </td>
                    <td>
                        @if($t->phone)
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $t->phone) }}" target="_blank" style="color:#25D366;text-decoration:none;font-size:13px;display:inline-flex;align-items:center;gap:4px;">
                                <i class="bi bi-whatsapp"></i> {{ $t->phone }}
                            </a>
                        @else
                            <span style="color:#9ca3af;font-size:12px;">—</span>
                        @endif
                    </td>
                    <td><span style="font-size:12px;font-weight:600;color:#374151;text-transform:capitalize;">{{ $t->plan }}</span></td>
                    <td>
                        @if($t->status === 'active')
                            <span class="m-badge m-badge-active">Ativo</span>
                        @elseif($t->status === 'trial')
                            <span class="m-badge m-badge-trial">Trial</span>
                        @elseif($t->status === 'suspended')
                            <span class="m-badge m-badge-suspended">Suspenso</span>
                        @else
                            <span class="m-badge m-badge-inactive">{{ ucfirst($t->status) }}</span>
                        @endif
                    </td>
                    <td style="font-size:12.5px;">
                        @if($data['last_login'])
                            {{ \Carbon\Carbon::parse($data['last_login'])->format('d/m/Y H:i') }}
                        @else
                            <span style="color:#9ca3af;">Nunca</span>
                        @endif
                    </td>
                    <td>
                        @if($data['days_inactive'] >= 999)
                            <span style="color:#ef4444;font-weight:600;">Nunca logou</span>
                        @elseif($data['days_inactive'] > 14)
                            <span style="color:#ef4444;font-weight:600;">{{ $data['days_inactive'] }}d</span>
                        @elseif($data['days_inactive'] > 7)
                            <span style="color:#f59e0b;font-weight:600;">{{ $data['days_inactive'] }}d</span>
                        @else
                            <span style="color:#10b981;">{{ $data['days_inactive'] }}d</span>
                        @endif
                    </td>
                    <td style="font-weight:600;">{{ number_format($data['wa_messages']) }}</td>
                    <td style="font-weight:600;">{{ number_format($data['leads_created']) }}</td>
                    <td>
                        <a href="{{ route('cs.show', $t->id) }}" class="m-btn m-btn-ghost m-btn-sm" style="text-decoration:none;">
                            <i class="bi bi-eye"></i> Ver
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:40px;color:#9ca3af;">
                        <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                        Nenhuma conta encontrada.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
