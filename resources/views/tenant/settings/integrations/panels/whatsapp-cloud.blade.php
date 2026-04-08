{{--
    Painel WhatsApp Cloud API (Meta Oficial) — recebe (do parent):
        $cloudApiInstances (Collection<WhatsappInstance> com provider=cloud_api)
--}}
<div class="panel-header">
    <div>
        <h3 class="panel-title">
            WhatsApp Cloud API
            <span style="font-size:11px;color:#fff;background:#0085f3;padding:2px 7px;border-radius:99px;font-weight:600;margin-left:6px;vertical-align:middle;">BETA</span>
        </h3>
        <p class="panel-subtitle">API oficial da Meta com modo Coexistência (use no celular e API ao mesmo tempo)</p>
    </div>
    @if($cloudApiInstances->isNotEmpty())
        <span class="conn-badge conn-active">Conectado</span>
    @else
        <span class="conn-badge conn-none">Não conectado</span>
    @endif
</div>

<ul class="integration-features">
    <li>API oficial Meta — risco zero de banimento</li>
    <li>Coexistência: WhatsApp no celular continua funcionando normalmente</li>
    <li>Sincronização bidirecional entre app e Syncro</li>
    <li>Templates aprovados, broadcast e métricas oficiais</li>
</ul>

@if($cloudApiInstances->isNotEmpty())
    <div style="margin:10px 0;padding:10px 14px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;font-size:12.5px;">
        <strong style="color:#1a1d23;">{{ $cloudApiInstances->count() }} número(s) conectado(s)</strong>
        @foreach($cloudApiInstances as $ci)
        <div style="margin-top:6px;display:flex;align-items:center;gap:6px;color:#6b7280;">
            <i class="bi bi-whatsapp" style="color:#25D366;"></i>
            <span style="flex:1;min-width:0;">
                <strong>{{ $ci->label ?: ('+' . $ci->phone_number) }}</strong>
                <small style="color:#9ca3af;display:block;font-size:11px;">phone_number_id: {{ $ci->phone_number_id }}</small>
            </span>
            <button type="button" onclick="disconnectWaCloud({{ $ci->id }}, this)" title="Desconectar" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:6px;padding:4px 9px;cursor:pointer;font-size:11px;">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        @endforeach
    </div>
    <div class="integration-actions">
        <button type="button" class="btn-connect" onclick="connectWhatsappCloud()" style="background:#25D366;">
            <i class="bi bi-plus-lg"></i> Adicionar outro número
        </button>
    </div>
@else
    <div class="conn-detail" style="color:#9ca3af;">Conecte sua conta Meta Business para começar a receber leads via API oficial.</div>
    <div class="integration-actions">
        <button type="button" class="btn-connect" onclick="connectWhatsappCloud()" style="background:#25D366;">
            <i class="bi bi-whatsapp"></i> Conectar com Meta
        </button>
    </div>
@endif
