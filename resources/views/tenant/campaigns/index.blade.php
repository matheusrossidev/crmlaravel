@extends('tenant.layouts.app')
@php
    $title    = 'Campanhas';
    $pageIcon = 'megaphone';
@endphp

@section('topbar_actions')
<div class="topbar-actions" style="gap:8px;">
    <a href="{{ route('campaigns.reports') }}" class="btn-secondary-sm" style="text-decoration:none;">
        <i class="bi bi-bar-chart-line"></i> Relatórios
    </a>
    <button class="btn-primary-sm" id="btnNovaCampanha">
        <i class="bi bi-plus-lg"></i> Nova Campanha
    </button>
</div>
@endsection

@push('styles')
<style>
.campaigns-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 18px;
}

.campaign-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e8eaf0;
    overflow: hidden;
    transition: box-shadow .15s;
}

.campaign-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.07); }

.campaign-card-header {
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid #f0f2f7;
}

.platform-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
    font-weight: 700;
    color: #fff;
}

.platform-icon.facebook { background: #1877F2; }
.platform-icon.google   { background: linear-gradient(135deg, #4285F4, #EA4335, #FBBC04, #34A853); }
.platform-icon.manual   { background: #6366F1; }

.campaign-name {
    font-size: 14px;
    font-weight: 700;
    color: #1a1d23;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.campaign-name small {
    display: block;
    font-size: 11px;
    font-weight: 500;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-top: 1px;
}

.status-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 99px;
    white-space: nowrap;
    flex-shrink: 0;
}

.status-active   { background: #d1fae5; color: #065f46; }
.status-paused   { background: #fef3c7; color: #92400e; }
.status-archived { background: #f3f4f6; color: #6b7280; }

.campaign-metrics {
    padding: 16px 20px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.metric-item { text-align: center; }

.metric-value {
    font-size: 16px;
    font-weight: 700;
    color: #1a1d23;
    line-height: 1;
    margin-bottom: 3px;
}

.metric-label {
    font-size: 10px;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.metric-item.green  .metric-value { color: #10B981; }
.metric-item.blue   .metric-value { color: #3B82F6; }
.metric-item.purple .metric-value { color: #8B5CF6; }

.campaign-footer {
    padding: 10px 20px;
    border-top: 1px solid #f0f2f7;
    font-size: 11.5px;
    color: #9ca3af;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.camp-action-btn {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    border: 1px solid #e8eaf0;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 13px;
    color: #6b7280;
    transition: all .15s;
    flex-shrink: 0;
}
.camp-action-btn:hover { background: #f4f6fb; color: #374151; }
.camp-action-btn.link:hover { background: #ede9fe; color: #4f46e5; border-color: #c4b5fd; }
.camp-action-btn.danger:hover { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }

.utm-link-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    margin: 0 20px 12px;
    font-size: 12px;
    color: #475569;
    word-break: break-all;
    display: none;
}
.utm-link-box strong { display: block; font-size: 11px; font-weight: 700; color: #374151; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .04em; }

.empty-state {
    text-align: center;
    padding: 80px 20px;
    color: #9ca3af;
}
.empty-state i  { font-size: 52px; opacity: .2; margin-bottom: 14px; display: block; }
.empty-state h3 { font-size: 16px; color: #374151; margin: 0 0 6px; }
.empty-state p  { font-size: 13.5px; margin: 0 0 20px; }

.btn-secondary-sm {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 14px;
    border: 1.5px solid #e8eaf0;
    border-radius: 9px;
    background: #fff;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    transition: all .15s;
    text-decoration: none;
}
.btn-secondary-sm:hover { background: #f0f4ff; border-color: #dbeafe; color: #3B82F6; }

/* ── Drawer CSS ─────────────────────────────────────────────────── */
.drawer-section-label {
    font-size: 10.5px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 10px;
}

.drawer-group { margin-bottom: 12px; }

.drawer-group label {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 4px;
}

.drawer-input {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid #e8eaf0;
    border-radius: 9px;
    font-size: 13.5px;
    font-family: 'Inter', sans-serif;
    color: #1a1d23;
    background: #fafafa;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    box-sizing: border-box;
}

.drawer-input:focus {
    border-color: #3B82F6;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(59,130,246,.1);
}

.drawer-input.is-invalid { border-color: #EF4444; }

.drawer-error {
    font-size: 11.5px;
    color: #EF4444;
    margin-top: 3px;
    display: none;
}

.drawer-icon-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #e8eaf0;
    border-radius: 8px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    font-size: 14px;
    cursor: pointer;
    transition: all .15s;
}
.drawer-icon-btn:hover { background: #f4f6fb; color: #374151; }
.drawer-icon-btn.danger:hover { background: #fef2f2; color: #EF4444; border-color: #fecaca; }
</style>
@endpush

@section('content')
<div class="page-container">

    @if($campaigns->isEmpty())
    <div class="empty-state">
        <i class="bi bi-megaphone"></i>
        <h3>Nenhuma campanha encontrada</h3>
        <p>
            Crie sua primeira campanha para começar a rastrear seus leads e resultados.<br>
            <a href="#" onclick="openCreateDrawer();return false;" style="color:#3B82F6;font-weight:600;">
                Criar primeira campanha →
            </a>
        </p>
    </div>
    @else
    <div class="campaigns-grid">
        @foreach($campaigns as $item)
        @php
            $campaign = $item['campaign'];
            $pType    = $campaign->type ?? $campaign->platform ?? 'manual';
            $pLabel   = match($pType) { 'facebook' => 'Facebook Ads', 'google' => 'Google Ads', default => 'Manual' };
            $pIcon    = match($pType) { 'facebook' => 'f', 'google' => 'G', default => '' };
        @endphp
        <div class="campaign-card" id="card-{{ $campaign->id }}">
            <div class="campaign-card-header">
                <div class="platform-icon {{ $pType }}">
                    @if($pType === 'manual')
                        <i class="bi bi-megaphone-fill" style="font-size:16px"></i>
                    @else
                        {{ $pIcon }}
                    @endif
                </div>
                <div class="campaign-name">
                    {{ $campaign->name }}
                    <small>{{ $pLabel }}@if($campaign->campaign_type) · {{ $campaign->campaign_type }}@endif</small>
                </div>
                <span class="status-badge status-{{ $campaign->status }}">
                    {{ match($campaign->status) { 'active' => 'Ativo', 'paused' => 'Pausado', default => 'Arquivado' } }}
                </span>
            </div>

            <div class="campaign-metrics">
                <div class="metric-item green">
                    <div class="metric-value">R$ {{ $item['total_spend'] > 0 ? number_format($item['total_spend'], 0, ',', '.') : '—' }}</div>
                    <div class="metric-label">Investido</div>
                </div>
                <div class="metric-item blue">
                    <div class="metric-value">{{ $item['leads_count'] }}</div>
                    <div class="metric-label">Leads</div>
                </div>
                <div class="metric-item purple">
                    <div class="metric-value">{{ $item['conversions'] }}</div>
                    <div class="metric-label">Conversões</div>
                </div>
                <div class="metric-item">
                    <div class="metric-value">{{ $item['cost_per_lead'] !== null ? 'R$ ' . number_format($item['cost_per_lead'], 2, ',', '.') : '—' }}</div>
                    <div class="metric-label">Custo/Lead</div>
                </div>
                <div class="metric-item">
                    <div class="metric-value">{{ $item['roi'] !== null ? $item['roi'] . '%' : '—' }}</div>
                    <div class="metric-label">ROI</div>
                </div>
                <div class="metric-item">
                    <div class="metric-value">R$ {{ $item['revenue'] > 0 ? number_format($item['revenue'], 0, ',', '.') : '—' }}</div>
                    <div class="metric-label">Receita</div>
                </div>
            </div>

            @if($campaign->destination_url && $campaign->utm_campaign)
            <div class="utm-link-box" id="utm-box-{{ $campaign->id }}">
                <strong><i class="bi bi-link-45deg me-1"></i>Link UTM:</strong>
                <span id="utm-url-{{ $campaign->id }}">{{ $campaign->destination_url }}?{{ http_build_query(array_filter(['utm_source' => $campaign->utm_source, 'utm_medium' => $campaign->utm_medium, 'utm_campaign' => $campaign->utm_campaign, 'utm_term' => $campaign->utm_term, 'utm_content' => $campaign->utm_content])) }}</span>
                <br>
                <button type="button" style="font-size:12px;font-weight:600;color:#6366F1;background:none;border:none;cursor:pointer;padding:4px 0 0;display:inline-flex;align-items:center;gap:4px;" onclick="copyUtmLink({{ $campaign->id }})">
                    <i class="bi bi-clipboard"></i> Copiar
                </button>
            </div>
            @endif

            <div class="campaign-footer">
                <span>Criado {{ $campaign->created_at->diffForHumans() }}</span>
                <div style="display:flex;gap:5px;">
                    @if($campaign->destination_url && $campaign->utm_campaign)
                    <button class="camp-action-btn link" title="Ver link UTM" onclick="toggleUtmBox({{ $campaign->id }})">
                        <i class="bi bi-link-45deg"></i>
                    </button>
                    @endif
                    <button class="camp-action-btn" title="Editar" onclick="openEditDrawer({{ $campaign->id }})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="camp-action-btn danger" title="Excluir" onclick="deleteCampaign({{ $campaign->id }}, '{{ e($campaign->name) }}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>

{{-- ── Overlay ─────────────────────────────────────────────────────────── --}}
<div id="campDrawerOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:199;transition:opacity .25s;" onclick="closeCampDrawer()"></div>

{{-- ── Drawer ───────────────────────────────────────────────────────────── --}}
<aside id="campDrawer" style="
    position:fixed;top:0;right:0;
    width:460px;height:100vh;
    background:#fff;
    box-shadow:-4px 0 32px rgba(0,0,0,.1);
    z-index:200;
    display:flex;flex-direction:column;
    transform:translateX(100%);
    transition:transform .25s cubic-bezier(.4,0,.2,1);
    overflow:hidden;
">
    {{-- Header --}}
    <div style="padding:18px 22px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
        <div>
            <div id="campDrawerTitle" style="font-size:15px;font-weight:700;color:#1a1d23;">Nova Campanha</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px;">Preencha os dados da campanha</div>
        </div>
        <button onclick="closeCampDrawer()" class="drawer-icon-btn" title="Fechar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    {{-- Body --}}
    <div style="flex:1;overflow-y:auto;padding:22px;" id="campDrawerBody">
        <form id="campForm" novalidate>

            <div class="drawer-section-label">Informações da Campanha</div>

            <div class="drawer-group">
                <label>Nome <span style="color:#EF4444;">*</span></label>
                <input type="text" id="cf-name" class="drawer-input" placeholder="Ex: Black Friday 2024 - Leads" maxlength="500">
                <div class="drawer-error" id="err-name"></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="drawer-group">
                    <label>Plataforma <span style="color:#EF4444;">*</span></label>
                    <select id="cf-type" class="drawer-input" onchange="onPlatformChange(this.value)">
                        <option value="manual">Manual</option>
                        <option value="facebook">Facebook Ads</option>
                        <option value="google">Google Ads</option>
                    </select>
                </div>
                <div class="drawer-group" id="campTypeGroup" style="display:none;">
                    <label>Tipo de Campanha</label>
                    <select id="cf-campaign_type" class="drawer-input">
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div class="drawer-group" id="statusGroup">
                    <label>Status</label>
                    <select id="cf-status" class="drawer-input">
                        <option value="active">Ativo</option>
                        <option value="paused">Pausado</option>
                        <option value="archived">Arquivado</option>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="drawer-group">
                    <label>Orçamento Diário (R$)</label>
                    <input type="number" id="cf-budget_daily" class="drawer-input" step="0.01" min="0" placeholder="0,00">
                </div>
                <div class="drawer-group">
                    <label>Orçamento Total (R$)</label>
                    <input type="number" id="cf-budget_lifetime" class="drawer-input" step="0.01" min="0" placeholder="0,00">
                </div>
            </div>

            <div class="drawer-section-label" style="margin-top:18px;">Rastreamento UTM</div>

            <div class="drawer-group">
                <label>URL de Destino</label>
                <input type="url" id="cf-destination_url" class="drawer-input" placeholder="https://seusite.com/landing-page" oninput="updateUtmPreview()">
                <div style="font-size:11.5px;color:#9ca3af;margin-top:3px;">URL base da sua landing page (sem UTMs)</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="drawer-group">
                    <label>utm_source</label>
                    <input type="text" id="cf-utm_source" class="drawer-input" placeholder="facebook, google..." oninput="updateUtmPreview()">
                </div>
                <div class="drawer-group">
                    <label>utm_medium</label>
                    <input type="text" id="cf-utm_medium" class="drawer-input" placeholder="cpc, cpm..." oninput="updateUtmPreview()">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div class="drawer-group" style="grid-column:span 1">
                    <label>utm_campaign</label>
                    <input type="text" id="cf-utm_campaign" class="drawer-input" placeholder="nome-campanha" oninput="updateUtmPreview()">
                </div>
                <div class="drawer-group">
                    <label>utm_term</label>
                    <input type="text" id="cf-utm_term" class="drawer-input" placeholder="palavra-chave" oninput="updateUtmPreview()">
                </div>
                <div class="drawer-group">
                    <label>utm_content</label>
                    <input type="text" id="cf-utm_content" class="drawer-input" placeholder="banner-azul" oninput="updateUtmPreview()">
                </div>
            </div>

            {{-- Preview UTM --}}
            <div id="utmPreviewWrap" style="display:none;margin-top:4px;">
                <div style="font-size:10.5px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px;">Link gerado:</div>
                <div id="utmPreviewText" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;padding:10px 12px;font-size:12px;word-break:break-all;color:#475569;"></div>
                <button type="button" onclick="copyUtmPreview()" style="font-size:12px;font-weight:600;color:#6366F1;background:none;border:none;cursor:pointer;padding:6px 0 0;display:inline-flex;align-items:center;gap:4px;">
                    <i class="bi bi-clipboard"></i> Copiar link
                </button>
            </div>

        </form>
    </div>

    {{-- Footer --}}
    <div style="padding:16px 22px;border-top:1px solid #f0f2f7;display:flex;gap:10px;flex-shrink:0;">
        <button type="button" onclick="closeCampDrawer()" style="padding:9px 18px;background:#fff;color:#6b7280;border:1.5px solid #e8eaf0;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s;" onmouseover="this.style.background='#f4f6fb'" onmouseout="this.style.background='#fff'">
            Cancelar
        </button>
        <button type="button" id="campSubmitBtn" onclick="submitCampForm()" class="btn-primary-sm" style="flex:1;justify-content:center;">
            Criar Campanha
        </button>
    </div>
</aside>

@endsection

@push('scripts')
@php
    $campaignsJson = $campaigns->map(function ($item) {
        $c = $item['campaign'];
        return [
            'id'              => $c->id,
            'name'            => $c->name,
            'type'            => $c->type ?? $c->platform ?? 'manual',
            'campaign_type'   => $c->campaign_type,
            'status'          => $c->status,
            'budget_daily'    => $c->budget_daily,
            'budget_lifetime' => $c->budget_lifetime,
            'destination_url' => $c->destination_url,
            'utm_source'      => $c->utm_source,
            'utm_medium'      => $c->utm_medium,
            'utm_campaign'    => $c->utm_campaign,
            'utm_term'        => $c->utm_term,
            'utm_content'     => $c->utm_content,
        ];
    })->values()->toArray();
@endphp
<script>
const CAMPAIGNS_DATA = {!! json_encode($campaignsJson) !!};

const ROUTES = {
    store:  '{{ route('campaigns.store') }}',
    update: (id) => '{{ route('campaigns.update', ['campaign' => ':id']) }}'.replace(':id', id),
    delete: (id) => '{{ route('campaigns.destroy', ['campaign' => ':id']) }}'.replace(':id', id),
};

const FB_TYPES = [
    { value: 'awareness',     label: 'Reconhecimento (Awareness)' },
    { value: 'traffic',       label: 'Tráfego (Traffic)' },
    { value: 'engagement',    label: 'Engajamento (Engagement)' },
    { value: 'leads',         label: 'Geração de Leads' },
    { value: 'app_promotion', label: 'Promoção de App' },
    { value: 'sales',         label: 'Vendas (Sales)' },
];

const GOOGLE_TYPES = [
    { value: 'search',          label: 'Pesquisa (Search)' },
    { value: 'display',         label: 'Display' },
    { value: 'video',           label: 'Vídeo' },
    { value: 'shopping',        label: 'Shopping' },
    { value: 'performance_max', label: 'Performance Max' },
    { value: 'smart',           label: 'Smart' },
];

let currentEditId = null;

function openCampDrawer() {
    document.getElementById('campDrawer').style.transform = 'translateX(0)';
    document.getElementById('campDrawerOverlay').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeCampDrawer() {
    document.getElementById('campDrawer').style.transform = 'translateX(100%)';
    document.getElementById('campDrawerOverlay').style.display = 'none';
    document.body.style.overflow = '';
    currentEditId = null;
}

function openCreateDrawer() {
    currentEditId = null;
    document.getElementById('campDrawerTitle').textContent = 'Nova Campanha';
    document.getElementById('campSubmitBtn').textContent   = 'Criar Campanha';

    ['name','budget_daily','budget_lifetime','destination_url',
     'utm_source','utm_medium','utm_campaign','utm_term','utm_content']
        .forEach(f => { const el = document.getElementById('cf-' + f); if (el) el.value = ''; });

    document.getElementById('cf-type').value   = 'manual';
    document.getElementById('cf-status').value = 'active';
    onPlatformChange('manual');
    document.getElementById('utmPreviewWrap').style.display = 'none';
    clearCampErrors();
    openCampDrawer();
}

function openEditDrawer(id) {
    const c = CAMPAIGNS_DATA.find(x => x.id === id);
    if (!c) return;

    currentEditId = id;
    document.getElementById('campDrawerTitle').textContent = 'Editar Campanha';
    document.getElementById('campSubmitBtn').textContent   = 'Salvar Alterações';

    document.getElementById('cf-name').value            = c.name            || '';
    document.getElementById('cf-status').value          = c.status          || 'active';
    document.getElementById('cf-budget_daily').value    = c.budget_daily    || '';
    document.getElementById('cf-budget_lifetime').value = c.budget_lifetime || '';
    document.getElementById('cf-destination_url').value = c.destination_url || '';
    document.getElementById('cf-utm_source').value      = c.utm_source      || '';
    document.getElementById('cf-utm_medium').value      = c.utm_medium      || '';
    document.getElementById('cf-utm_campaign').value    = c.utm_campaign    || '';
    document.getElementById('cf-utm_term').value        = c.utm_term        || '';
    document.getElementById('cf-utm_content').value     = c.utm_content     || '';

    document.getElementById('cf-type').value = c.type || 'manual';
    onPlatformChange(c.type || 'manual');

    if (c.campaign_type) {
        document.getElementById('cf-campaign_type').value = c.campaign_type;
    }

    clearCampErrors();
    updateUtmPreview();
    openCampDrawer();
}

function onPlatformChange(platform) {
    const group = document.getElementById('campTypeGroup');
    const sel   = document.getElementById('cf-campaign_type');
    const statusGroup = document.getElementById('statusGroup');
    sel.innerHTML = '<option value="">Selecione...</option>';

    if (platform === 'facebook' || platform === 'google') {
        const types = platform === 'facebook' ? FB_TYPES : GOOGLE_TYPES;
        types.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.value;
            opt.textContent = t.label;
            sel.appendChild(opt);
        });
        group.style.display = 'block';
        // Adjust grid: 3 cols (type, type, status)
        group.parentElement.style.gridTemplateColumns = '1fr 1fr 1fr';
        statusGroup.style.display = 'block';
    } else {
        group.style.display = 'none';
        group.parentElement.style.gridTemplateColumns = '1fr 1fr';
        statusGroup.style.display = 'block';
        sel.value = '';
    }
}

function updateUtmPreview() {
    const base     = document.getElementById('cf-destination_url').value.trim();
    const source   = document.getElementById('cf-utm_source').value.trim();
    const medium   = document.getElementById('cf-utm_medium').value.trim();
    const campaign = document.getElementById('cf-utm_campaign').value.trim();
    const term     = document.getElementById('cf-utm_term').value.trim();
    const content  = document.getElementById('cf-utm_content').value.trim();
    const wrap     = document.getElementById('utmPreviewWrap');
    const preview  = document.getElementById('utmPreviewText');

    if (!base || (!source && !campaign)) { wrap.style.display = 'none'; return; }

    const params = new URLSearchParams();
    if (source)   params.set('utm_source',   source);
    if (medium)   params.set('utm_medium',   medium);
    if (campaign) params.set('utm_campaign', campaign);
    if (term)     params.set('utm_term',     term);
    if (content)  params.set('utm_content',  content);

    preview.textContent = base + '?' + params.toString();
    wrap.style.display = 'block';
}

function copyUtmPreview() {
    const text = document.getElementById('utmPreviewText').textContent;
    navigator.clipboard.writeText(text).then(() => toastr.success('Link copiado!'));
}

function toggleUtmBox(id) {
    const box = document.getElementById('utm-box-' + id);
    if (box) box.style.display = box.style.display === 'block' ? 'none' : 'block';
}

function copyUtmLink(id) {
    const text = document.getElementById('utm-url-' + id)?.textContent?.trim();
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => toastr.success('Link copiado!'));
}

function clearCampErrors() {
    document.querySelectorAll('#campForm .drawer-error').forEach(el => { el.textContent = ''; el.style.display = 'none'; });
    document.querySelectorAll('#campForm .drawer-input.is-invalid').forEach(el => el.classList.remove('is-invalid'));
}

function submitCampForm() {
    clearCampErrors();

    const name   = document.getElementById('cf-name').value.trim();
    const type   = document.getElementById('cf-type').value;
    const status = document.getElementById('cf-status').value;

    if (!name) {
        const el = document.getElementById('cf-name');
        el.classList.add('is-invalid');
        document.getElementById('err-name').textContent = 'O nome é obrigatório.';
        document.getElementById('err-name').style.display = 'block';
        el.focus();
        return;
    }

    const btn = document.getElementById('campSubmitBtn');
    btn.disabled = true;
    const origText = btn.textContent;
    btn.textContent = 'Salvando...';

    const payload = {
        name,
        type,
        status,
        campaign_type:   document.getElementById('cf-campaign_type')?.value   || null,
        budget_daily:    document.getElementById('cf-budget_daily').value      || null,
        budget_lifetime: document.getElementById('cf-budget_lifetime').value   || null,
        destination_url: document.getElementById('cf-destination_url').value.trim() || null,
        utm_source:      document.getElementById('cf-utm_source').value.trim()  || null,
        utm_medium:      document.getElementById('cf-utm_medium').value.trim()  || null,
        utm_campaign:    document.getElementById('cf-utm_campaign').value.trim()|| null,
        utm_term:        document.getElementById('cf-utm_term').value.trim()    || null,
        utm_content:     document.getElementById('cf-utm_content').value.trim() || null,
    };

    const url    = currentEditId ? ROUTES.update(currentEditId) : ROUTES.store;
    const method = currentEditId ? 'PUT' : 'POST';

    fetch(url, {
        method,
        headers: {
            'Content-Type':  'application/json',
            'Accept':        'application/json',
            'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            toastr.success(currentEditId ? 'Campanha atualizada!' : 'Campanha criada!');
            closeCampDrawer();
            setTimeout(() => location.reload(), 800);
        } else {
            toastr.error('Erro ao salvar campanha.');
        }
    })
    .catch(() => toastr.error('Erro de conexão.'))
    .finally(() => { btn.disabled = false; btn.textContent = origText; });
}

function deleteCampaign(id, name) {
    if (!confirm('Excluir a campanha "' + name + '"?\n\nEsta ação não pode ser desfeita.')) return;

    fetch(ROUTES.delete(id), {
        method: 'DELETE',
        headers: {
            'Accept':       'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('card-' + id)?.remove();
            toastr.success('Campanha excluída.');
            // Show empty state if no more cards
            const grid = document.querySelector('.campaigns-grid');
            if (grid && !grid.children.length) location.reload();
        }
    })
    .catch(() => toastr.error('Erro ao excluir.'));
}

// Bind buttons
document.getElementById('btnNovaCampanha')?.addEventListener('click', openCreateDrawer);

// Fechar com Esc
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeCampDrawer();
});
</script>
@endpush
