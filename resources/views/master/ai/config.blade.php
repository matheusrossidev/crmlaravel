@extends('tenant.layouts.app')

@php
    $title    = 'Config. Inteligencia Artificial';
    $pageIcon = 'cpu';
@endphp

@push('styles')
<style>
    .master-card {
        background: #fff;
        border: 1px solid #e8eaf0;
        border-radius: 14px;
        padding: 28px 32px;
        max-width: 580px;
    }
    .master-card-title {
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
        margin-bottom: 4px;
    }
    .master-card-subtitle {
        font-size: 12.5px;
        color: #9ca3af;
        margin-bottom: 24px;
    }
    .form-group { margin-bottom: 18px; }
    .form-label-sm {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .input-field {
        width: 100%;
        padding: 9px 12px;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        font-size: 13.5px;
        outline: none;
        font-family: inherit;
        transition: border-color .15s;
        box-sizing: border-box;
        background: #fff;
        color: #1a1d23;
    }
    .input-field:focus { border-color: #3B82F6; }
    select.input-field { cursor: pointer; }

    .key-wrap { display: flex; gap: 8px; }
    .key-wrap .input-field { flex: 1; }
    .btn-eye {
        width: 38px; height: 38px; flex-shrink: 0;
        border: 1.5px solid #e8eaf0; border-radius: 9px;
        background: #fff; cursor: pointer; color: #6b7280;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; transition: all .15s;
    }
    .btn-eye:hover { background: #f0f4ff; border-color: #c7d2fe; }

    .form-hint {
        font-size: 11.5px;
        color: #9ca3af;
        margin-top: 5px;
    }
    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 26px;
        align-items: center;
        flex-wrap: wrap;
    }
    .btn-primary {
        padding: 9px 22px; border-radius: 9px; border: none;
        background: #3B82F6; color: #fff;
        font-size: 13px; font-weight: 600; cursor: pointer;
        transition: background .15s;
    }
    .btn-primary:hover { background: #2563eb; }
    .btn-primary:disabled { opacity: .55; cursor: not-allowed; }
    .btn-secondary {
        padding: 9px 18px; border-radius: 9px;
        border: 1.5px solid #e8eaf0; background: #fff;
        font-size: 13px; font-weight: 600; color: #6b7280;
        cursor: pointer; transition: all .15s;
    }
    .btn-secondary:hover { background: #f0f2f7; }
    .btn-secondary:disabled { opacity: .55; cursor: not-allowed; }

    .test-result {
        font-size: 12px;
        padding: 6px 12px;
        border-radius: 7px;
        display: none;
        font-weight: 500;
    }
    .test-result.ok  { background: #d1fae5; color: #065f46; }
    .test-result.err { background: #fee2e2; color: #991b1b; }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11.5px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        vertical-align: middle;
    }
    .badge-configured { background: #d1fae5; color: #065f46; }
    .badge-not-configured { background: #fef3c7; color: #92400e; }

    .divider {
        border: none;
        border-top: 1px solid #f0f0f5;
        margin: 22px 0;
    }
</style>
@endpush

@section('content')
<div class="page-container">

    <div style="margin-bottom:24px;display:flex;align-items:center;gap:12px;">
        <div>
            <div style="font-size:15px;font-weight:700;color:#1a1d23;">Inteligencia Artificial — Configuracao Global</div>
            <div style="font-size:13px;color:#9ca3af;margin-top:3px;">
                Provedor LLM usado por todos os agentes de IA da plataforma.
            </div>
        </div>
        @if($config->exists && $config->llm_api_key)
        <span class="status-badge badge-configured">
            <i class="bi bi-check-circle-fill"></i> Configurado
        </span>
        @else
        <span class="status-badge badge-not-configured">
            <i class="bi bi-exclamation-triangle-fill"></i> Nao configurado
        </span>
        @endif
    </div>

    <div class="master-card">
        <div class="master-card-title">Provedor LLM</div>
        <div class="master-card-subtitle">Escolha o servico de IA e insira a chave de API correspondente.</div>

        {{-- Provider --}}
        <div class="form-group">
            <label class="form-label-sm" for="llm_provider">Provedor</label>
            <select id="llm_provider" class="input-field" onchange="onProviderChange()">
                <option value="openai"    {{ ($config->llm_provider ?? 'openai') === 'openai'    ? 'selected' : '' }}>OpenAI</option>
                <option value="anthropic" {{ ($config->llm_provider ?? '')       === 'anthropic' ? 'selected' : '' }}>Anthropic</option>
                <option value="google"    {{ ($config->llm_provider ?? '')       === 'google'    ? 'selected' : '' }}>Google (Gemini)</option>
            </select>
        </div>

        {{-- API Key --}}
        <div class="form-group">
            <label class="form-label-sm" for="llm_api_key">Chave de API</label>
            <div class="key-wrap">
                <input type="password" id="llm_api_key" class="input-field"
                       value="{{ $config->llm_api_key ? str_repeat('•', 24) : '' }}"
                       placeholder="Insira sua chave de API"
                       autocomplete="off">
                <button type="button" class="btn-eye" onclick="toggleKey()" title="Mostrar/ocultar chave">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
            <div class="form-hint">
                @if($config->llm_api_key)
                    Chave configurada. Para alterar, apague o campo e digite a nova chave.
                @else
                    A chave e armazenada de forma segura e nunca e exposta ao navegador.
                @endif
            </div>
        </div>

        {{-- Model --}}
        <div class="form-group">
            <label class="form-label-sm" for="llm_model">Modelo</label>
            <select id="llm_model" class="input-field">
                {{-- Populado via JS (onProviderChange) --}}
            </select>
        </div>

        <hr class="divider">

        <div class="form-actions">
            <button class="btn-primary" id="btnSave" onclick="saveConfig()">
                <i class="bi bi-floppy"></i> Salvar configuracao
            </button>
            <button class="btn-secondary" id="btnTest" onclick="testConn()">
                <i class="bi bi-lightning-charge"></i> Testar conexao
            </button>
            <span class="test-result" id="testResult"></span>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const URL_SAVE = '{{ route('master.ai.config.update') }}';
const URL_TEST = '{{ route('master.ai.test') }}';

@php
    $modelOpts = $modelOptions;
@endphp
const MODEL_OPTS = {!! json_encode($modelOpts) !!};

const SAVED_PROVIDER = '{{ $config->llm_provider ?? 'openai' }}';
const SAVED_MODEL    = '{{ $config->llm_model ?? '' }}';

let keyChanged = false;

document.getElementById('llm_api_key').addEventListener('input', function () {
    keyChanged = true;
});

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

function onProviderChange() {
    const provider = document.getElementById('llm_provider').value;
    const sel = document.getElementById('llm_model');
    const current = sel.value;
    sel.innerHTML = '';
    const models = MODEL_OPTS[provider] || [];
    models.forEach(function (m) {
        const opt = document.createElement('option');
        opt.value = m;
        opt.textContent = m;
        if (m === current || m === SAVED_MODEL) opt.selected = true;
        sel.appendChild(opt);
    });
}

async function saveConfig() {
    const btn = document.getElementById('btnSave');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Salvando...';

    try {
        const keyVal = document.getElementById('llm_api_key').value;
        const body = {
            llm_provider: document.getElementById('llm_provider').value,
            llm_model:    document.getElementById('llm_model').value,
        };

        // Envia a chave apenas se o usuario a modificou e o campo nao esta vazio/mascara
        if (keyChanged && keyVal && !keyVal.includes('•')) {
            body.llm_api_key = keyVal;
        }

        const res = await fetch(URL_SAVE, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        });

        let data;
        try {
            data = await res.json();
        } catch (_) {
            toastr.error('Resposta inesperada do servidor (HTTP ' + res.status + ').');
            return;
        }

        if (res.ok && data.success) {
            toastr.success(data.message || 'Configuracao salva com sucesso.');
            keyChanged = false;
        } else {
            const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Erro ao salvar.');
            toastr.error(msg);
        }
    } catch (err) {
        toastr.error('Falha na requisicao: ' + (err.message || 'verifique sua conexao.'));
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-floppy"></i> Salvar configuracao';
    }
}

async function testConn() {
    const keyVal = document.getElementById('llm_api_key').value;
    if (!keyVal || keyVal.includes('•')) {
        toastr.warning('Insira a API key antes de testar. Apague os pontos e cole a chave real.');
        return;
    }

    const btn = document.getElementById('btnTest');
    const res = document.getElementById('testResult');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Testando...';
    res.style.display = 'none';

    try {
        const r = await fetch(URL_TEST, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                llm_provider: document.getElementById('llm_provider').value,
                llm_api_key:  keyVal,
                llm_model:    document.getElementById('llm_model').value,
            }),
        });

        let data;
        try {
            data = await r.json();
        } catch (_) {
            res.className = 'test-result err';
            res.textContent = 'Resposta invalida do servidor (HTTP ' + r.status + ')';
            res.style.display = 'inline-block';
            return;
        }

        res.className = 'test-result ' + (data.success ? 'ok' : 'err');
        res.textContent = data.success
            ? 'Conexao OK — ' + (data.response || 'resposta recebida')
            : 'Falha: ' + (data.message || 'erro desconhecido');
        res.style.display = 'inline-block';
    } catch (err) {
        res.className = 'test-result err';
        res.textContent = 'Erro: ' + (err.message || 'falha de rede');
        res.style.display = 'inline-block';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning-charge"></i> Testar conexao';
    }
}

// Inicializa o dropdown de modelos para o provider salvo
onProviderChange();
</script>
@endpush
