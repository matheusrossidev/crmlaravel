@extends('tenant.layouts.app')

@php($title = 'Inteligência Artificial')
@php($pageIcon = 'robot')

@push('styles')
<style>
    .ai-card {
        background: #fff;
        border: 1px solid #e8eaf0;
        border-radius: 14px;
        padding: 28px;
        max-width: 640px;
    }
    .ai-card-title {
        font-size: 15px; font-weight: 700; color: #1a1d23;
        margin-bottom: 6px;
    }
    .ai-card-subtitle {
        font-size: 13px; color: #9ca3af;
        margin-bottom: 24px;
    }
    .form-group { margin-bottom: 18px; }
    .form-label {
        display: block; font-size: 11.5px; font-weight: 700;
        color: #6b7280; margin-bottom: 5px;
        text-transform: uppercase; letter-spacing: .05em;
    }
    .form-control {
        width: 100%; padding: 9px 12px;
        border: 1.5px solid #e8eaf0; border-radius: 9px;
        font-size: 13.5px; outline: none; font-family: inherit;
        transition: border-color .15s; box-sizing: border-box;
        background: #fff;
    }
    .form-control:focus { border-color: #3B82F6; }

    .key-wrap { display: flex; gap: 8px; }
    .key-wrap .form-control { flex: 1; }
    .btn-eye {
        width: 38px; height: 38px; flex-shrink: 0;
        border: 1.5px solid #e8eaf0; border-radius: 9px;
        background: #fff; cursor: pointer; color: #6b7280;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; transition: all .15s;
    }
    .btn-eye:hover { background: #f0f4ff; }

    .provider-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;
    }
    .provider-card {
        border: 2px solid #e8eaf0; border-radius: 10px;
        padding: 14px 12px; text-align: center; cursor: pointer;
        transition: all .15s; position: relative;
    }
    .provider-card:hover { border-color: #3B82F6; background: #f0f8ff; }
    .provider-card.selected { border-color: #3B82F6; background: #eff6ff; }
    .provider-card input[type=radio] { position: absolute; opacity: 0; }
    .provider-name { font-size: 13px; font-weight: 700; color: #374151; margin-top: 4px; }
    .provider-logo { font-size: 22px; }

    .form-actions { display: flex; gap: 10px; margin-top: 24px; align-items: center; }
    .btn-primary {
        padding: 9px 24px; border-radius: 9px; border: none;
        background: #3B82F6; color: #fff;
        font-size: 13.5px; font-weight: 600; cursor: pointer;
        transition: background .15s;
    }
    .btn-primary:hover { background: #2563eb; }
    .btn-primary:disabled { opacity: .6; cursor: not-allowed; }
    .btn-secondary {
        padding: 9px 20px; border-radius: 9px;
        border: 1.5px solid #e8eaf0; background: #fff;
        font-size: 13.5px; font-weight: 600; color: #6b7280;
        cursor: pointer; transition: all .15s;
    }
    .btn-secondary:hover { background: #f0f2f7; }
    .test-result {
        font-size: 12.5px; padding: 6px 12px; border-radius: 7px;
        display: none;
    }
    .test-result.ok  { background: #d1fae5; color: #065f46; }
    .test-result.err { background: #fee2e2; color: #991b1b; }
</style>
@endpush

@section('content')
<div class="page-container">

    <div style="margin-bottom:24px;">
        <div style="font-size:15px;font-weight:700;color:#1a1d23;">Inteligência Artificial — Configuração</div>
        <div style="font-size:13px;color:#9ca3af;margin-top:3px;">
            Configure o provedor de LLM para uso nos agentes de IA.
        </div>
    </div>

    <div class="ai-card">
        <div class="ai-card-title">Provedor de LLM</div>
        <div class="ai-card-subtitle">Escolha qual serviço de IA será usado pelos seus agentes.</div>

        {{-- Provider select --}}
        <div class="form-group">
            <label class="form-label">Serviço</label>
            <div class="provider-grid" id="providerGrid">
                @foreach([
                    ['openai',    '🤖', 'OpenAI'],
                    ['anthropic', '🧠', 'Anthropic'],
                    ['google',    '💡', 'Google'],
                ] as [$val, $icon, $label])
                <label class="provider-card {{ ($config->llm_provider ?? 'openai') === $val ? 'selected' : '' }}">
                    <input type="radio" name="llm_provider" value="{{ $val }}"
                           {{ ($config->llm_provider ?? 'openai') === $val ? 'checked' : '' }}
                           onchange="onProviderChange()">
                    <div class="provider-logo">{{ $icon }}</div>
                    <div class="provider-name">{{ $label }}</div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- API Key --}}
        <div class="form-group">
            <label class="form-label">API Key</label>
            <div class="key-wrap">
                <input type="password" id="llm_api_key" class="form-control"
                       value="{{ $config->llm_api_key ? str_repeat('•', 20) : '' }}"
                       placeholder="Insira sua chave de API"
                       autocomplete="off">
                <button type="button" class="btn-eye" onclick="toggleKey()" title="Mostrar/ocultar">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
            <div style="font-size:11.5px;color:#9ca3af;margin-top:5px;">
                A chave é armazenada de forma segura e nunca é exposta ao navegador.
            </div>
        </div>

        {{-- Model --}}
        <div class="form-group">
            <label class="form-label">Modelo</label>
            <select id="llm_model" class="form-control">
                @foreach($modelOptions as $provider => $models)
                @foreach($models as $m)
                <option value="{{ $m }}"
                        data-provider="{{ $provider }}"
                        {{ ($config->llm_model ?? '') === $m ? 'selected' : '' }}>
                    {{ $m }}
                </option>
                @endforeach
                @endforeach
            </select>
        </div>

        <div class="form-actions">
            <button class="btn-primary" id="btnSaveConfig" onclick="saveConfig()">
                <i class="bi bi-floppy"></i> Salvar
            </button>
            <button class="btn-secondary" id="btnTest" onclick="testConn()">
                <i class="bi bi-lightning"></i> Testar conexão
            </button>
            <span class="test-result" id="testResult"></span>
        </div>
    </div>

    <div style="margin-top:28px;padding:16px 20px;background:#f8fafc;border-radius:12px;border:1px solid #e8eaf0;max-width:640px;">
        <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:10px;">
            <i class="bi bi-arrow-right-circle" style="color:#3B82F6;"></i> Próximo passo
        </div>
        <div style="font-size:13px;color:#6b7280;">
            Após configurar o provedor, acesse
            <a href="{{ route('ai.agents.index') }}" style="color:#3B82F6;font-weight:600;">Agentes</a>
            para criar seu primeiro agente de IA.
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const URL_SAVE   = '{{ route('master.ai.config.update') }}';
const URL_TEST   = '{{ route('master.ai.test') }}';
const MODEL_OPTS = @json($modelOptions);

let keyChanged = false;
document.getElementById('llm_api_key').addEventListener('input', () => { keyChanged = true; });

function toggleKey() {
    const inp  = document.getElementById('llm_api_key');
    const icon = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function getProvider() {
    return document.querySelector('input[name=llm_provider]:checked')?.value ?? 'openai';
}

function onProviderChange() {
    // Highlight selected card
    document.querySelectorAll('.provider-card').forEach(c => {
        c.classList.toggle('selected', c.querySelector('input').checked);
    });
    // Filtra modelos
    const provider = getProvider();
    const sel = document.getElementById('llm_model');
    sel.innerHTML = '';
    (MODEL_OPTS[provider] || []).forEach(m => {
        const opt = document.createElement('option');
        opt.value = m; opt.textContent = m;
        sel.appendChild(opt);
    });
}

async function saveConfig() {
    const btn = document.getElementById('btnSaveConfig');
    btn.disabled = true;
    try {
        const body = {
            llm_provider: getProvider(),
            llm_model:    document.getElementById('llm_model').value,
        };
        if (keyChanged) {
            body.llm_api_key = document.getElementById('llm_api_key').value;
        }
        const res  = await fetch(URL_SAVE, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body),
        });
        const data = await res.json();
        if (data.success) { toastr.success(data.message); keyChanged = false; }
        else toastr.error(data.message ?? 'Erro ao salvar.');
    } finally { btn.disabled = false; }
}

async function testConn() {
    const key = document.getElementById('llm_api_key').value;
    if (!key || key.includes('•')) { toastr.warning('Insira a API key antes de testar.'); return; }
    const btn = document.getElementById('btnTest');
    btn.disabled = true;
    const res = document.getElementById('testResult');
    res.style.display = 'none';
    try {
        const r = await fetch(URL_TEST, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                llm_provider: getProvider(),
                llm_api_key:  key,
                llm_model:    document.getElementById('llm_model').value,
            }),
        });
        const d = await r.json();
        res.className = 'test-result ' + (d.success ? 'ok' : 'err');
        res.textContent = d.success ? '✓ Conexão OK' : '✗ ' + d.message;
        res.style.display = 'inline-block';
    } finally { btn.disabled = false; }
}

// Inicializa filtro de modelos para o provider salvo
onProviderChange();
// Restaura modelo salvo se houver
const savedModel = '{{ $config->llm_model ?? '' }}';
if (savedModel) {
    const sel = document.getElementById('llm_model');
    for (const opt of sel.options) { if (opt.value === savedModel) { sel.value = savedModel; break; } }
}
</script>
@endpush
