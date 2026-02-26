@extends('master.layouts.app')
@php
    $title    = 'Notificações para Tenants';
    $pageIcon = 'bell';
@endphp

@section('topbar_actions')
<button class="m-btn m-btn-primary" onclick="openComposeModal()">
    <i class="bi bi-send"></i> Disparar Notificação
</button>
@endsection

@section('content')

{{-- Histórico --}}
<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-clock-history"></i> Histórico de Notificações</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="m-table" id="notifTable">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Título</th>
                    <th>Mensagem</th>
                    <th>Destino</th>
                    <th>Enviado em</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications as $n)
                <tr>
                    <td>
                        @if($n->type === 'info')
                            <span class="m-badge" style="background:#eff6ff;color:#3B82F6;border:1px solid #bfdbfe;">Info</span>
                        @elseif($n->type === 'warning')
                            <span class="m-badge" style="background:#fffbeb;color:#d97706;border:1px solid #fde68a;">Aviso</span>
                        @else
                            <span class="m-badge" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">Alerta</span>
                        @endif
                    </td>
                    <td style="font-weight:600;">{{ $n->title }}</td>
                    <td style="color:#6b7280;font-size:12.5px;max-width:280px;">{{ Str::limit($n->body, 80) }}</td>
                    <td>
                        @if($n->tenant_id)
                            <span style="font-size:12px;background:#f3f4f6;padding:3px 8px;border-radius:6px;">
                                Empresa #{{ $n->tenant_id }}
                            </span>
                        @else
                            <span style="font-size:12px;background:#ecfdf5;color:#059669;padding:3px 8px;border-radius:6px;font-weight:600;">
                                Todos
                            </span>
                        @endif
                    </td>
                    <td style="color:#9ca3af;font-size:12px;">{{ $n->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;color:#9ca3af;padding:32px;">
                        Nenhuma notificação enviada ainda.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal de composição --}}
<div id="composeModal" style="display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:520px;max-width:95vw;padding:28px;box-shadow:0 8px 48px rgba(0,0,0,.2);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="font-size:16px;font-weight:700;margin:0;"><i class="bi bi-send" style="color:#3B82F6;margin-right:8px;"></i>Disparar Notificação</h3>
            <button onclick="closeModal()" style="background:none;border:none;cursor:pointer;font-size:22px;color:#9ca3af;">×</button>
        </div>

        <div style="margin-bottom:14px;">
            <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Destinatário</label>
            <select id="nTenantId" style="border:1px solid #d1d5db;border-radius:8px;padding:9px 11px;width:100%;font-size:13.5px;">
                <option value="">Todos os tenants ativos</option>
                @foreach($tenants as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </select>
        </div>

        <div style="margin-bottom:14px;">
            <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Tipo</label>
            <div style="display:flex;gap:10px;">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                    <input type="radio" name="nType" value="info" checked> Info
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                    <input type="radio" name="nType" value="warning"> Aviso
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                    <input type="radio" name="nType" value="alert"> Alerta
                </label>
            </div>
        </div>

        <div style="margin-bottom:14px;">
            <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Título</label>
            <input type="text" id="nTitle" maxlength="200" placeholder="Ex: Manutenção programada" style="border:1px solid #d1d5db;border-radius:8px;padding:9px 11px;width:100%;font-size:13.5px;">
        </div>

        <div style="margin-bottom:20px;">
            <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Mensagem</label>
            <textarea id="nBody" rows="4" maxlength="2000" placeholder="Texto da notificação..." style="border:1px solid #d1d5db;border-radius:8px;padding:9px 11px;width:100%;font-size:13.5px;resize:vertical;"></textarea>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="closeModal()" style="display:inline-flex;align-items:center;padding:9px 18px;background:transparent;color:#6b7280;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;">Cancelar</button>
            <button onclick="sendNotification()" id="btnSend" style="display:inline-flex;align-items:center;gap:7px;padding:9px 22px;background:#3B82F6;color:#fff;border:none;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;">
                <i class="bi bi-send"></i> Enviar
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const ROUTE_STORE = "{{ route('master.notifications.store') }}";
const CSRF = document.querySelector('meta[name=csrf-token]').content;

function openComposeModal() {
    document.getElementById('nTitle').value = '';
    document.getElementById('nBody').value = '';
    document.getElementById('nTenantId').value = '';
    document.querySelector('input[name=nType][value=info]').checked = true;
    document.getElementById('composeModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('composeModal').style.display = 'none';
}

async function sendNotification() {
    const btn = document.getElementById('btnSend');
    const title = document.getElementById('nTitle').value.trim();
    const body  = document.getElementById('nBody').value.trim();
    const type  = document.querySelector('input[name=nType]:checked')?.value || 'info';
    const tenantId = document.getElementById('nTenantId').value;

    if (!title || !body) {
        toastr.warning('Preencha título e mensagem.');
        return;
    }

    btn.disabled = true;
    try {
        const res = await fetch(ROUTE_STORE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ title, body, type, tenant_id: tenantId || null }),
        });
        const data = await res.json();
        if (res.ok && data.success) {
            toastr.success('Notificação enviada com sucesso!');
            closeModal();
            setTimeout(() => location.reload(), 900);
        } else {
            toastr.error(data.message || 'Erro ao enviar.');
        }
    } catch { toastr.error('Erro de conexão.'); }
    btn.disabled = false;
}

// Close on backdrop click
document.getElementById('composeModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endpush
