@extends('master.layouts.app')
@php
    $title    = 'Feature Flags';
    $pageIcon = 'toggles';
@endphp

@section('content')

<div class="m-section-header">
    <div class="m-section-title">Gerenciamento de Features</div>
    <div class="m-section-subtitle">Controle quais funcionalidades estão disponíveis para cada empresa</div>
</div>

<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-toggles"></i> Features</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="m-table" id="featuresTable">
            <thead>
                <tr>
                    <th style="width:30px;"></th>
                    <th>Feature</th>
                    <th>Descrição</th>
                    <th style="text-align:center;">Liberação</th>
                    <th style="text-align:center;">Empresas</th>
                    <th style="width:100px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($features as $f)
                <tr data-feature-id="{{ $f->id }}" data-slug="{{ $f->slug }}">
                    <td style="text-align:center;font-size:11px;color:#9ca3af;">#{{ $f->id }}</td>
                    <td>
                        <div style="font-weight:700;font-size:13px;color:#1a1d23;">{{ $f->label }}</div>
                        <div style="font-size:11px;color:#9ca3af;font-family:monospace;">{{ $f->slug }}</div>
                    </td>
                    <td style="color:#6b7280;font-size:12.5px;max-width:280px;">{{ $f->description }}</td>
                    <td style="text-align:center;">
                        @if($f->is_enabled_globally)
                            <span class="m-badge" style="background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;font-weight:600;">
                                <i class="bi bi-globe"></i> Todos
                            </span>
                        @else
                            <span class="m-badge" style="background:#fef3c7;color:#d97706;border:1px solid #fde68a;">
                                <i class="bi bi-people"></i> Selecionados
                            </span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($f->is_enabled_globally)
                            <span style="font-size:12px;color:#9ca3af;">—</span>
                        @else
                            <span style="font-size:13px;font-weight:700;color:#3B82F6;">{{ count($f->enabled_tenant_ids) }}</span>
                        @endif
                    </td>
                    <td>
                        <button class="m-btn m-btn-sm m-btn-outline" onclick="openFeatureDrawer({{ $f->id }})">
                            <i class="bi bi-gear"></i> Configurar
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ── Drawer: Configurar Feature ─────────────────────────────── --}}
<div id="featureDrawerOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1040;" onclick="closeFeatureDrawer()"></div>
<div id="featureDrawer" style="display:none;position:fixed;top:0;right:-560px;width:540px;height:100vh;background:#fff;z-index:1050;box-shadow:-4px 0 24px rgba(0,0,0,.12);transition:right .3s ease;overflow-y:auto;">

    <div style="padding:20px 24px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;">
        <div>
            <div style="font-size:16px;font-weight:700;color:#1a1d23;" id="drawerTitle">Configurar Feature</div>
            <div style="font-size:12px;color:#9ca3af;" id="drawerSlug">slug</div>
        </div>
        <button onclick="closeFeatureDrawer()" style="background:none;border:none;font-size:20px;color:#6b7280;cursor:pointer;">&times;</button>
    </div>

    <div style="padding:24px;">
        {{-- Toggle Global --}}
        <div style="background:#f9fafb;border-radius:12px;padding:16px 20px;margin-bottom:24px;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <div style="font-size:13px;font-weight:700;color:#1a1d23;">Liberação global</div>
                    <div style="font-size:12px;color:#6b7280;margin-top:2px;">Ativar para TODAS as empresas</div>
                </div>
                <label class="m-toggle" style="margin:0;">
                    <input type="checkbox" id="globalToggle" onchange="toggleGlobal()">
                    <span class="m-toggle-slider"></span>
                </label>
            </div>
        </div>

        {{-- Tenant Selection (hidden when global) --}}
        <div id="tenantSelectionArea">
            <div style="font-size:13px;font-weight:700;color:#1a1d23;margin-bottom:12px;">
                <i class="bi bi-building"></i> Empresas com acesso
            </div>

            <div style="margin-bottom:12px;">
                <input type="text" id="tenantSearch" placeholder="Buscar empresa..."
                    style="width:100%;padding:8px 12px;border:1.5px solid #e8eaf0;border-radius:8px;font-size:13px;"
                    oninput="filterTenants()">
            </div>

            <div id="tenantCheckboxes" style="max-height:400px;overflow-y:auto;border:1.5px solid #e8eaf0;border-radius:10px;padding:8px;">
                @foreach($tenants as $t)
                <label class="tenant-checkbox-row" data-name="{{ strtolower($t->name) }}" style="display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;cursor:pointer;transition:background .15s;">
                    <input type="checkbox" class="tenant-cb" value="{{ $t->id }}" style="width:16px;height:16px;accent-color:#3B82F6;">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#1a1d23;">{{ $t->name }}</div>
                        @if($t->slug)
                        <div style="font-size:11px;color:#9ca3af;">{{ $t->slug }}</div>
                        @endif
                    </div>
                </label>
                @endforeach
            </div>

            <div style="display:flex;gap:8px;margin-top:12px;">
                <button class="m-btn m-btn-sm m-btn-outline" onclick="selectAllTenants(true)">Selecionar todos</button>
                <button class="m-btn m-btn-sm m-btn-outline" onclick="selectAllTenants(false)">Limpar</button>
            </div>

            <div style="margin-top:16px;text-align:right;">
                <button class="m-btn m-btn-primary" onclick="saveTenants()">
                    <i class="bi bi-check-lg"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.tenant-checkbox-row:hover { background: #f0f4ff; }
.m-toggle { position:relative;display:inline-block;width:44px;height:24px; }
.m-toggle input { opacity:0;width:0;height:0; }
.m-toggle-slider { position:absolute;cursor:pointer;inset:0;background:#d1d5db;border-radius:24px;transition:.3s; }
.m-toggle-slider:before { content:'';position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s; }
.m-toggle input:checked + .m-toggle-slider { background:#3B82F6; }
.m-toggle input:checked + .m-toggle-slider:before { transform:translateX(20px); }
</style>

@php
    $featuresJs = $features->map(function ($f) {
        return ['id' => $f->id, 'slug' => $f->slug, 'label' => $f->label, 'is_enabled_globally' => $f->is_enabled_globally, 'enabled_tenant_ids' => $f->enabled_tenant_ids];
    });
@endphp
<script>
let currentFeatureId = null;
const featuresData = {!! json_encode($featuresJs) !!};

function openFeatureDrawer(featureId) {
    currentFeatureId = featureId;
    const f = featuresData.find(x => x.id === featureId);
    if (!f) return;

    document.getElementById('drawerTitle').textContent = f.label;
    document.getElementById('drawerSlug').textContent = f.slug;
    document.getElementById('globalToggle').checked = f.is_enabled_globally;

    // Show/hide tenant selection
    document.getElementById('tenantSelectionArea').style.display = f.is_enabled_globally ? 'none' : 'block';

    // Check tenant checkboxes
    document.querySelectorAll('.tenant-cb').forEach(cb => {
        cb.checked = f.enabled_tenant_ids.includes(parseInt(cb.value));
    });

    document.getElementById('featureDrawerOverlay').style.display = 'block';
    const drawer = document.getElementById('featureDrawer');
    drawer.style.display = 'block';
    requestAnimationFrame(() => { drawer.style.right = '0'; });
}

function closeFeatureDrawer() {
    const drawer = document.getElementById('featureDrawer');
    drawer.style.right = '-560px';
    setTimeout(() => {
        drawer.style.display = 'none';
        document.getElementById('featureDrawerOverlay').style.display = 'none';
    }, 300);
}

const FEATURE_TOGGLE_URL = @json(route('master.features.toggle-global', ['feature' => '__ID__']));
const FEATURE_TENANTS_URL = @json(route('master.features.update-tenants', ['feature' => '__ID__']));

function toggleGlobal() {
    const isGlobal = document.getElementById('globalToggle').checked;
    document.getElementById('tenantSelectionArea').style.display = isGlobal ? 'none' : 'block';

    fetch(FEATURE_TOGGLE_URL.replace('__ID__', currentFeatureId), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Update local state
            const f = featuresData.find(x => x.id === currentFeatureId);
            if (f) f.is_enabled_globally = data.is_enabled_globally;
            location.reload();
        }
    });
}

function saveTenants() {
    const tenantIds = [];
    document.querySelectorAll('.tenant-cb:checked').forEach(cb => tenantIds.push(parseInt(cb.value)));

    fetch(FEATURE_TENANTS_URL.replace('__ID__', currentFeatureId), {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ tenant_ids: tenantIds }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const f = featuresData.find(x => x.id === currentFeatureId);
            if (f) f.enabled_tenant_ids = data.tenant_ids;
            toastr.success('Empresas atualizadas!');
            location.reload();
        }
    });
}

function filterTenants() {
    const q = document.getElementById('tenantSearch').value.toLowerCase();
    document.querySelectorAll('.tenant-checkbox-row').forEach(row => {
        row.style.display = row.dataset.name.includes(q) ? 'flex' : 'none';
    });
}

function selectAllTenants(check) {
    document.querySelectorAll('.tenant-checkbox-row').forEach(row => {
        if (row.style.display !== 'none') {
            row.querySelector('.tenant-cb').checked = check;
        }
    });
}
</script>

@endsection
