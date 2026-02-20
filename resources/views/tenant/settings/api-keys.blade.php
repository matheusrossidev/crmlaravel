@extends('tenant.layouts.app')
@php
    $title    = 'API / Webhooks';
    $pageIcon = 'key';
@endphp

@push('styles')
<style>
    .api-layout {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 22px;
        align-items: start;
        max-width: 1100px;
    }

    @media (max-width: 900px) { .api-layout { grid-template-columns: 1fr; } }

    .api-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
    }

    .api-card-header {
        padding: 16px 22px;
        border-bottom: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .api-card-header h3 {
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .api-card-body { padding: 20px 22px; }

    /* Key item */
    .key-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 0;
        border-bottom: 1px solid #f7f8fa;
    }

    .key-item:last-child { border-bottom: none; }

    .key-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #eff6ff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #3B82F6;
        font-size: 16px;
        flex-shrink: 0;
    }

    .key-info { flex: 1; min-width: 0; }

    .key-name {
        font-size: 13.5px;
        font-weight: 600;
        color: #1a1d23;
        margin-bottom: 3px;
    }

    .key-prefix {
        font-size: 12px;
        color: #9ca3af;
        font-family: monospace;
    }

    .key-meta {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 2px;
    }

    .badge-active   { background: #d1fae5; color: #065f46; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 99px; }
    .badge-inactive { background: #f3f4f6; color: #6b7280; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 99px; }

    .btn-revoke {
        padding: 6px 12px;
        background: #fff;
        color: #EF4444;
        border: 1.5px solid #fecaca;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
    }

    .btn-revoke:hover { background: #fef2f2; }

    /* Empty state */
    .keys-empty {
        text-align: center;
        padding: 40px 20px;
        color: #9ca3af;
        font-size: 13px;
    }

    .keys-empty i { font-size: 32px; opacity: .3; display: block; margin-bottom: 8px; }

    /* Docs */
    .docs-section { margin-top: 0; }

    .endpoint-block {
        background: #f8fafc;
        border: 1px solid #e8eaf0;
        border-radius: 10px;
        margin-bottom: 12px;
        overflow: hidden;
    }

    .endpoint-method {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 5px;
        margin-right: 6px;
        font-family: monospace;
    }

    .method-post   { background: #d1fae5; color: #065f46; }
    .method-get    { background: #dbeafe; color: #1d4ed8; }
    .method-put    { background: #fef3c7; color: #92400e; }
    .method-delete { background: #fee2e2; color: #991b1b; }

    .endpoint-path {
        font-family: monospace;
        font-size: 12.5px;
        color: #374151;
        font-weight: 600;
    }

    .endpoint-desc {
        font-size: 12px;
        color: #6b7280;
        margin-top: 2px;
    }

    .endpoint-header {
        padding: 10px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
    }

    pre.code-block {
        margin: 0;
        padding: 12px 14px;
        background: #1e2130;
        color: #e2e8f0;
        font-size: 11.5px;
        font-family: monospace;
        overflow-x: auto;
        border-top: 1px solid #e8eaf0;
        display: none;
    }

    .endpoint-block.open pre.code-block { display: block; }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.45);
        z-index: 500;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.open { display: flex; }

    .modal-box {
        background: #fff;
        border-radius: 14px;
        padding: 28px;
        width: 460px;
        max-width: 94vw;
        box-shadow: 0 20px 60px rgba(0,0,0,.18);
    }

    .modal-title {
        font-size: 15px;
        font-weight: 700;
        color: #1a1d23;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .key-reveal-box {
        background: #f8fafc;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        padding: 12px 14px;
        font-family: monospace;
        font-size: 13px;
        color: #1a1d23;
        word-break: break-all;
        margin-bottom: 12px;
        position: relative;
    }

    .btn-copy {
        position: absolute;
        top: 8px;
        right: 8px;
        padding: 4px 10px;
        background: #3B82F6;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }

    .btn-copy:hover { background: #2563EB; }
    .btn-copy.copied { background: #10B981; }

    .warning-box {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 12.5px;
        color: #92400e;
        margin-bottom: 16px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
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
        box-sizing: border-box;
        margin-bottom: 14px;
    }

    .drawer-input:focus { border-color: #3B82F6; background: #fff; }
</style>
@endpush

@section('content')
<div class="page-container">
<div class="api-layout">

    {{-- ── Coluna esquerda: Keys ──────────────────────────────────────── --}}
    <div>

        {{-- Lista de keys --}}
        <div class="api-card" style="margin-bottom:22px;">
            <div class="api-card-header">
                <h3><i class="bi bi-key" style="color:#3B82F6;"></i> Suas API Keys</h3>
                <button class="btn-primary-sm" onclick="openNewKeyModal()">
                    <i class="bi bi-plus-lg"></i> Nova API Key
                </button>
            </div>
            <div class="api-card-body" style="padding:0 22px;">
                @forelse($apiKeys as $key)
                <div class="key-item" id="key-row-{{ $key->id }}">
                    <div class="key-icon"><i class="bi bi-key"></i></div>
                    <div class="key-info">
                        <div class="key-name">{{ $key->name }}</div>
                        <div class="key-prefix">{{ $key->key_prefix }}</div>
                        <div class="key-meta">
                            Criada em {{ $key->created_at?->format('d/m/Y') }}
                            @if($key->last_used_at)
                                · Último uso: {{ $key->last_used_at->diffForHumans() }}
                            @else
                                · Nunca utilizada
                            @endif
                        </div>
                    </div>
                    <span class="{{ $key->is_active ? 'badge-active' : 'badge-inactive' }}">
                        {{ $key->is_active ? 'Ativa' : 'Revogada' }}
                    </span>
                    @if($key->is_active)
                    <button class="btn-revoke" onclick="revokeKey({{ $key->id }}, this)">
                        <i class="bi bi-trash"></i> Revogar
                    </button>
                    @endif
                </div>
                @empty
                <div class="keys-empty">
                    <i class="bi bi-key"></i>
                    Nenhuma API Key criada ainda.<br>
                    Clique em <strong>Nova API Key</strong> para criar.
                </div>
                @endforelse
            </div>
        </div>

        {{-- Documentação dos endpoints --}}
        <div class="api-card docs-section">
            <div class="api-card-header">
                <h3><i class="bi bi-book" style="color:#8B5CF6;"></i> Documentação dos Endpoints</h3>
            </div>
            <div class="api-card-body">
                <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">
                    Todas as requisições devem incluir o header <code style="background:#f0f4ff;color:#6366f1;padding:2px 6px;border-radius:4px;font-size:12px;">X-API-Key: sua_key</code>
                </p>

                @php
                $base = url('/api/v1');
                $endpoints = [
                    ['POST',   '/leads',              'Criar novo lead',             "curl -X POST {$base}/leads \\\n  -H 'X-API-Key: crm_sua_key_aqui' \\\n  -H 'Content-Type: application/json' \\\n  -d '{\n    \"name\": \"João Silva\",\n    \"phone\": \"(11) 99999-9999\",\n    \"email\": \"joao@exemplo.com\",\n    \"pipeline_id\": 1,\n    \"stage_id\": 1,\n    \"source\": \"site\",\n    \"value\": 1500.00\n  }'"],
                    ['GET',    '/leads/{id}',         'Buscar lead por ID',          "curl -X GET {$base}/leads/1 \\\n  -H 'X-API-Key: crm_sua_key_aqui'"],
                    ['PUT',    '/leads/{id}/stage',   'Mover lead para etapa',       "curl -X PUT {$base}/leads/1/stage \\\n  -H 'X-API-Key: crm_sua_key_aqui' \\\n  -H 'Content-Type: application/json' \\\n  -d '{\"stage_id\": 2, \"pipeline_id\": 1}'"],
                    ['PUT',    '/leads/{id}/won',     'Marcar lead como ganho',      "curl -X PUT {$base}/leads/1/won \\\n  -H 'X-API-Key: crm_sua_key_aqui' \\\n  -H 'Content-Type: application/json' \\\n  -d '{\"stage_id\": 5, \"value\": 2000.00}'"],
                    ['PUT',    '/leads/{id}/lost',    'Marcar lead como perdido',    "curl -X PUT {$base}/leads/1/lost \\\n  -H 'X-API-Key: crm_sua_key_aqui' \\\n  -H 'Content-Type: application/json' \\\n  -d '{\"stage_id\": 6, \"reason_id\": 1}'"],
                    ['DELETE', '/leads/{id}',         'Deletar lead',                "curl -X DELETE {$base}/leads/1 \\\n  -H 'X-API-Key: crm_sua_key_aqui'"],
                    ['GET',    '/pipelines',          'Listar pipelines e etapas',   "curl -X GET {$base}/pipelines \\\n  -H 'X-API-Key: crm_sua_key_aqui'"],
                ];
                @endphp

                @foreach($endpoints as $i => $ep)
                @php
                    $methodClass = match($ep[0]) {
                        'POST'   => 'method-post',
                        'GET'    => 'method-get',
                        'PUT'    => 'method-put',
                        'DELETE' => 'method-delete',
                        default  => 'method-get',
                    };
                @endphp
                <div class="endpoint-block" id="ep-{{ $i }}">
                    <div class="endpoint-header" onclick="toggleEndpoint('ep-{{ $i }}')">
                        <div>
                            <span class="endpoint-method {{ $methodClass }}">{{ $ep[0] }}</span>
                            <span class="endpoint-path">{{ $ep[1] }}</span>
                            <div class="endpoint-desc">{{ $ep[2] }}</div>
                        </div>
                        <i class="bi bi-chevron-down" style="color:#9ca3af;font-size:12px;"></i>
                    </div>
                    <pre class="code-block">{{ $ep[3] }}</pre>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- ── Coluna direita: Info ───────────────────────────────────────── --}}
    <div>
        <div class="api-card">
            <div class="api-card-header">
                <h3><i class="bi bi-info-circle" style="color:#F59E0B;"></i> Como usar</h3>
            </div>
            <div class="api-card-body" style="font-size:13px;color:#374151;line-height:1.6;">
                <p><strong>1. Gere uma API Key</strong><br>
                Clique em <em>Nova API Key</em>, dê um nome para identificar onde será usada (ex: "Site", "Automação") e copie a key.</p>

                <p style="margin-top:12px;"><strong>2. Salve a key com segurança</strong><br>
                A key completa é exibida <strong>apenas uma vez</strong>. Guarde em um local seguro.</p>

                <p style="margin-top:12px;"><strong>3. Inclua no header</strong><br>
                Em toda requisição à API, adicione:</p>
                <div style="background:#f8fafc;border:1px solid #e8eaf0;border-radius:8px;padding:10px 12px;font-family:monospace;font-size:12px;margin:8px 0;">
                    X-API-Key: crm_sua_key_aqui
                </div>

                <p style="margin-top:12px;"><strong>4. URL base da API</strong></p>
                <div style="background:#f8fafc;border:1px solid #e8eaf0;border-radius:8px;padding:10px 12px;font-family:monospace;font-size:12px;word-break:break-all;">
                    {{ url('/api/v1') }}
                </div>

                <hr style="border:none;border-top:1px solid #f0f2f7;margin:16px 0;">

                <p style="font-size:12px;color:#9ca3af;">
                    <i class="bi bi-shield-check" style="color:#10B981;"></i>
                    Todas as requisições são isoladas por conta. Leads criados via API aparecem apenas no seu painel.
                </p>
            </div>
        </div>
    </div>

</div>
</div>

{{-- Modal: Nova API Key --}}
<div class="modal-overlay" id="modalNewKey">
    <div class="modal-box">
        <div class="modal-title">
            <i class="bi bi-key" style="color:#3B82F6;"></i> Nova API Key
        </div>
        <div id="newKeyForm">
            <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Nome da key</label>
            <input type="text" id="newKeyName" class="drawer-input" placeholder="Ex: Site, Landing Page, Automação...">
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button onclick="closeNewKeyModal()" style="padding:9px 18px;border:1.5px solid #e8eaf0;border-radius:9px;background:#fff;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;">Cancelar</button>
                <button onclick="createKey()" id="btnCreateKey" style="padding:9px 22px;border:none;border-radius:9px;background:#3B82F6;color:#fff;font-size:13px;font-weight:600;cursor:pointer;">
                    <i class="bi bi-plus-lg"></i> Criar
                </button>
            </div>
        </div>
        <div id="keyRevealSection" style="display:none;">
            <div class="warning-box">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Copie agora! Esta key <strong>não será exibida novamente</strong>.</span>
            </div>
            <div class="key-reveal-box">
                <span id="rawKeyText"></span>
                <button class="btn-copy" onclick="copyKey()" id="btnCopy">Copiar</button>
            </div>
            <button onclick="closeAndReload()" style="width:100%;padding:10px;border:none;border-radius:9px;background:#10B981;color:#fff;font-size:13.5px;font-weight:600;cursor:pointer;">
                <i class="bi bi-check-lg"></i> Feito, já copiei
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const API_KEY_STORE   = @json(route('settings.api-keys.store'));
const API_KEY_DESTROY = @json(route('settings.api-keys.destroy', ['apiKey' => '__ID__']));

// ── Modal nova key ─────────────────────────────────────────────────────────
function openNewKeyModal() {
    document.getElementById('newKeyName').value = '';
    document.getElementById('newKeyForm').style.display = '';
    document.getElementById('keyRevealSection').style.display = 'none';
    document.getElementById('modalNewKey').classList.add('open');
    setTimeout(() => document.getElementById('newKeyName').focus(), 100);
}

function closeNewKeyModal() {
    document.getElementById('modalNewKey').classList.remove('open');
}

function closeAndReload() {
    document.getElementById('modalNewKey').classList.remove('open');
    location.reload();
}

async function createKey() {
    const name = document.getElementById('newKeyName').value.trim();
    if (!name) {
        toastr.warning('Informe um nome para a API Key.');
        return;
    }

    const btn = document.getElementById('btnCreateKey');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Criando...';

    try {
        const res  = await fetch(API_KEY_STORE, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name }),
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById('rawKeyText').textContent = data.raw_key;
            document.getElementById('newKeyForm').style.display = 'none';
            document.getElementById('keyRevealSection').style.display = '';
        } else {
            toastr.error('Erro ao criar API Key.');
        }
    } catch (e) {
        toastr.error('Erro de conexão.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plus-lg"></i> Criar';
    }
}

function copyKey() {
    const text = document.getElementById('rawKeyText').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.getElementById('btnCopy');
        btn.textContent = 'Copiado!';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = 'Copiar'; btn.classList.remove('copied'); }, 2000);
    });
}

// ── Revogar key ────────────────────────────────────────────────────────────
function revokeKey(id, btn) {
    confirmAction({
        title: 'Revogar API Key',
        message: 'Sistemas que utilizam esta chave perderão acesso imediatamente.',
        confirmText: 'Revogar',
        onConfirm: async () => {
            btn.disabled = true;
            try {
                const res  = await fetch(API_KEY_DESTROY.replace('__ID__', id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success('API Key revogada.');
                    document.getElementById(`key-row-${id}`)?.remove();
                } else {
                    toastr.error('Erro ao revogar.');
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error('Erro de conexão.');
                btn.disabled = false;
            }
        },
    });
}

// ── Toggle exemplos de código ──────────────────────────────────────────────
function toggleEndpoint(id) {
    document.getElementById(id)?.classList.toggle('open');
}

// Fechar modal com Esc
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeNewKeyModal();
});
</script>
@endpush
