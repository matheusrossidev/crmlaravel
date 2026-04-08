{{--
    Painel Botão WhatsApp para sites — recebe (do parent):
        $waButtons (Collection<WhatsappButton>)
--}}
<div class="panel-header">
    <div>
        <h3 class="panel-title">{{ __('integrations.wabtn_title') }}</h3>
        <p class="panel-subtitle">{{ __('integrations.wabtn_subtitle') }}</p>
    </div>
    <span class="conn-badge {{ $waButtons->where('is_active', true)->count() > 0 ? 'conn-active' : 'conn-none' }}">
        {{ $waButtons->count() }}/3
    </span>
</div>

<ul class="integration-features">
    <li>{{ __('integrations.wabtn_feat_1') }}</li>
    <li>{{ __('integrations.wabtn_feat_2') }}</li>
    <li>{{ __('integrations.wabtn_feat_3') }}</li>
    <li>{{ __('integrations.wabtn_feat_4') }}</li>
</ul>

{{-- Lista de botões existentes --}}
@forelse($waButtons as $waBtn)
    @php $clicks7d = $waBtn->clicks()->where('clicked_at', '>=', now()->subDays(7))->count(); @endphp
    <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:#f9fafb;border:1px solid #e8eaf0;border-radius:10px;margin-bottom:8px;">
        <div style="width:36px;height:36px;border-radius:8px;background:#dcfce7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-whatsapp" style="color:#25D366;font-size:16px;"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:600;color:#1a1d23;">{{ $waBtn->phone_number }}</div>
            <div style="font-size:11px;color:#9ca3af;">{{ $clicks7d }} cliques (7 dias) · {{ $waBtn->button_label }}</div>
        </div>
        <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:99px;{{ $waBtn->is_active ? 'background:#ecfdf5;color:#059669;' : 'background:#f3f4f6;color:#6b7280;' }}">
            {{ $waBtn->is_active ? 'Ativo' : 'Inativo' }}
        </span>
        <button onclick="openWaBtnDrawer({{ $waBtn->id }})" style="background:none;border:none;color:#0085f3;cursor:pointer;font-size:14px;padding:4px;" title="Editar">
            <i class="bi bi-pencil"></i>
        </button>
        <button onclick="deleteWaButton({{ $waBtn->id }}, '{{ $waBtn->phone_number }}')" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:14px;padding:4px;" title="Remover">
            <i class="bi bi-trash3"></i>
        </button>
    </div>
@empty
    <div class="conn-detail" style="color:#9ca3af;">{{ __('integrations.wabtn_no_button') }}</div>
@endforelse

@if($waButtons->count() < 3)
<div class="integration-actions">
    <button class="btn-connect" style="background:#25D366;" onclick="openWaBtnDrawer(null)">
        <i class="bi bi-plus-lg"></i> Adicionar botão ({{ $waButtons->count() }}/3)
    </button>
</div>
@endif
