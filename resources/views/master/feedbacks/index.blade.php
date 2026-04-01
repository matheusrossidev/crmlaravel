@extends('master.layouts.app')
@php $title = 'Feedbacks'; $pageIcon = 'lightbulb'; @endphp

@push('styles')
<style>
.fb-filters { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 16px; }
.fb-filters select { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 12px; font-family: inherit; background: #fff; }
.fb-type { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 99px; }
.fb-type.new_feature { background: #eff6ff; color: #1e40af; }
.fb-type.improvement { background: #f0fdf4; color: #065f46; }
.fb-type.bug { background: #fef2f2; color: #991b1b; }
.fb-type.ux_ui { background: #f5f3ff; color: #5b21b6; }
.fb-type.integration { background: #fffbeb; color: #92400e; }
.fb-type.other { background: #f3f4f6; color: #374151; }
.fb-status { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 99px; }
.fb-status.new { background: #dbeafe; color: #1e40af; }
.fb-status.reviewing { background: #fffbeb; color: #92400e; }
.fb-status.planned { background: #f0fdf4; color: #065f46; }
.fb-status.done { background: #ecfdf5; color: #059669; }
.fb-status.dismissed { background: #f3f4f6; color: #6b7280; }
.fb-impact { font-size: 11px; font-weight: 600; }
.fb-impact.blocker { color: #dc2626; }
.fb-impact.high { color: #f59e0b; }
.fb-impact.medium { color: #0085f3; }
.fb-impact.low { color: #6b7280; }
</style>
@endpush

@section('content')
<div class="m-section-header">
    <div class="m-section-title">Feedbacks</div>
    <div class="m-section-subtitle">Feedbacks enviados pelos usuários da plataforma</div>
</div>

<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-lightbulb"></i> Feedbacks dos Clientes @if($newCount > 0) <span style="background:#ef4444;color:#fff;padding:2px 8px;border-radius:99px;font-size:10px;margin-left:6px;">{{ $newCount }} novos</span> @endif</div>
    </div>

    <div style="padding:16px 20px 0;">
        <form method="GET" class="fb-filters">
            <select name="status"><option value="">Status</option>@foreach($statusLabels as $k => $v)<option value="{{ $k }}" {{ request('status')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select>
            <select name="type"><option value="">Tipo</option>@foreach($typeLabels as $k => $v)<option value="{{ $k }}" {{ request('type')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select>
            <select name="area"><option value="">Área</option>@foreach($areaLabels as $k => $v)<option value="{{ $k }}" {{ request('area')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select>
            <select name="impact"><option value="">Impacto</option>@foreach($impactLabels as $k => $v)<option value="{{ $k }}" {{ request('impact')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select>
            <button type="submit" style="padding:7px 14px;background:#0085f3;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;"><i class="bi bi-funnel"></i> Filtrar</button>
            @if(request()->hasAny(['status','type','area','impact']))
                <a href="{{ route('master.feedbacks.index') }}" style="font-size:12px;color:#6b7280;">Limpar</a>
            @endif
        </form>
    </div>

    @if($feedbacks->isEmpty())
        <div style="padding:50px;text-align:center;color:#9ca3af;">Nenhum feedback encontrado.</div>
    @else
        <div style="overflow-x:auto;">
            <table class="m-table" style="min-width:800px;">
                <thead><tr><th>Data</th><th>Empresa</th><th>Plano</th><th>Tipo</th><th>Área</th><th>Título</th><th>Impacto</th><th>Prio.</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach($feedbacks as $fb)
                    <tr>
                        <td style="font-size:12px;color:#6b7280;white-space:nowrap;">{{ $fb->created_at?->format('d/m/Y') }}</td>
                        <td style="font-weight:600;color:#1a1d23;">{{ $fb->tenant?->name ?? '—' }}</td>
                        <td><span style="font-size:11px;text-transform:capitalize;">{{ $fb->plan_name ?? '—' }}</span></td>
                        <td><span class="fb-type {{ $fb->type }}">{{ $typeLabels[$fb->type] ?? $fb->type }}</span></td>
                        <td style="font-size:12px;">{{ $areaLabels[$fb->area] ?? $fb->area ?? '—' }}</td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600;color:#1a1d23;">{{ $fb->title }}</td>
                        <td><span class="fb-impact {{ $fb->impact }}">{{ $impactLabels[$fb->impact] ?? '—' }}</span></td>
                        <td style="text-align:center;">{{ $fb->priority }}/5</td>
                        <td><span class="fb-status {{ $fb->status }}">{{ $statusLabels[$fb->status] ?? $fb->status }}</span></td>
                        <td>
                            <button style="background:#eff6ff;color:#0085f3;border:none;border-radius:6px;padding:5px 10px;font-size:12px;font-weight:600;cursor:pointer;" onclick="showFeedback({{ $fb->id }})"><i class="bi bi-eye"></i> Ver</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:12px 20px;">{{ $feedbacks->links('pagination::bootstrap-5') }}</div>
    @endif
</div>

{{-- Detail Modal --}}
<div id="fbModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;overflow-y:auto;padding:20px;" onclick="if(event.target===this)closeFbModal()">
    <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:600px;box-shadow:0 20px 60px rgba(0,0,0,.2);margin:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 id="fbModalTitle" style="margin:0;font-size:17px;font-weight:700;">Feedback</h3>
            <button onclick="closeFbModal()" style="background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;">&times;</button>
        </div>
        <div id="fbModalBody" style="font-size:14px;color:#374151;line-height:1.7;">Carregando...</div>
        <div id="fbModalActions" style="margin-top:20px;display:none;">
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Notas do admin</label>
                <textarea id="fbNotes" rows="2" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;box-sizing:border-box;"></textarea>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <button onclick="updateFb('reviewing')" style="padding:6px 14px;background:#fffbeb;color:#92400e;border:1px solid #fde68a;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Analisando</button>
                <button onclick="updateFb('planned')" style="padding:6px 14px;background:#f0fdf4;color:#065f46;border:1px solid #bbf7d0;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Planejado</button>
                <button onclick="updateFb('done')" style="padding:6px 14px;background:#ecfdf5;color:#059669;border:1px solid #6ee7b7;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Feito</button>
                <button onclick="updateFb('dismissed')" style="padding:6px 14px;background:#f3f4f6;color:#6b7280;border:1px solid #d1d5db;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Dispensar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let activeFbId = null;

function showFeedback(id) {
    activeFbId = id;
    document.getElementById('fbModal').style.display = 'flex';
    document.getElementById('fbModalBody').innerHTML = '<div style="text-align:center;padding:20px;color:#9ca3af;">Carregando...</div>';
    document.getElementById('fbModalActions').style.display = 'none';

    fetch('{{ route("master.feedbacks.show", "__ID__") }}'.replace('__ID__', id), { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF } })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        const f = data.feedback;
        let html = '';
        html += `<div style="margin-bottom:16px;"><span class="fb-type ${f.type}" style="font-size:12px;padding:4px 12px;">${f.type}</span> <span class="fb-status ${f.status || 'new'}" style="font-size:12px;padding:4px 12px;margin-left:4px;">${f.status || 'Novo'}</span></div>`;
        html += `<h2 style="font-size:18px;font-weight:700;color:#1a1d23;margin:0 0 12px;">${esc(f.title)}</h2>`;
        html += `<p style="color:#374151;line-height:1.7;margin:0 0 16px;white-space:pre-wrap;">${esc(f.description)}</p>`;
        html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;margin-bottom:16px;">';
        html += `<div><strong style="color:#6b7280;">Área:</strong> ${f.area || '—'}</div>`;
        html += `<div><strong style="color:#6b7280;">Impacto:</strong> ${f.impact || '—'}</div>`;
        html += `<div><strong style="color:#6b7280;">Prioridade:</strong> ${f.priority}/5</div>`;
        html += `<div><strong style="color:#6b7280;">Plano:</strong> ${f.plan_name || '—'}</div>`;
        html += `<div><strong style="color:#6b7280;">Usuário:</strong> ${esc(f.user_name || '—')}</div>`;
        html += `<div><strong style="color:#6b7280;">Empresa:</strong> ${esc(f.tenant_name || '—')}</div>`;
        html += `<div><strong style="color:#6b7280;">Role:</strong> ${f.user_role || '—'}</div>`;
        html += `<div><strong style="color:#6b7280;">Data:</strong> ${f.created_at}</div>`;
        if (f.can_contact) html += `<div><strong style="color:#6b7280;">Contato:</strong> ${esc(f.contact_email)}</div>`;
        if (f.url_origin) html += `<div><strong style="color:#6b7280;">Origem:</strong> ${esc(f.url_origin)}</div>`;
        html += '</div>';
        if (f.evidence_path) html += `<div style="margin-bottom:16px;"><img src="${f.evidence_path}" style="max-width:100%;border-radius:10px;border:1px solid #e5e7eb;"></div>`;
        if (f.admin_notes) html += `<div style="background:#f8fafc;border:1px solid #e8eaf0;border-radius:8px;padding:12px;font-size:13px;"><strong>Notas:</strong> ${esc(f.admin_notes)}</div>`;

        document.getElementById('fbModalTitle').textContent = 'Feedback #' + f.id;
        document.getElementById('fbModalBody').innerHTML = html;
        document.getElementById('fbNotes').value = f.admin_notes || '';
        document.getElementById('fbModalActions').style.display = 'block';
    });
}

function closeFbModal() { document.getElementById('fbModal').style.display = 'none'; }

async function updateFb(status) {
    const r = await fetch('{{ route("master.feedbacks.status", "__ID__") }}'.replace('__ID__', activeFbId), {
        method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
        body: JSON.stringify({ status, admin_notes: document.getElementById('fbNotes').value }),
    });
    const d = await r.json();
    if (d.success) { toastr.success('Status atualizado!'); setTimeout(() => location.reload(), 600); }
    else { toastr.error('Erro'); }
}

function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
</script>
@endpush
