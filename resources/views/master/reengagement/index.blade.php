@extends('master.layouts.app')
@php
    $title    = 'Reengajamento';
    $pageIcon = 'arrow-repeat';
@endphp

@section('content')

<div class="m-section-header">
    <div>
        <div class="m-section-title">Reengajamento</div>
        <div class="m-section-subtitle">Configure mensagens automáticas para usuários inativos</div>
    </div>
</div>

{{-- Language tabs (PT_BR / EN) --}}
<div style="display:flex;gap:8px;margin-bottom:18px;">
    @foreach($availableLocales as $loc => $label)
    <a href="{{ route('master.reengagement') }}?locale={{ $loc }}"
       class="re-locale-tab {{ $currentLocale === $loc ? 'active' : '' }}">
        @if($loc === 'pt_BR')
            🇧🇷 {{ $label }}
        @else
            🇺🇸 {{ $label }}
        @endif
    </a>
    @endforeach
</div>

{{-- Stage Tabs --}}
<div style="display:flex;gap:0;border-bottom:2px solid #e8eaf0;margin-bottom:20px;">
    @foreach(['7d' => '7 dias sem login', '14d' => '14 dias sem login', '30d' => '30 dias sem login'] as $key => $label)
    <button class="re-tab {{ $key === '7d' ? 'active' : '' }}" data-stage="{{ $key }}" onclick="switchReTab(this)">
        {{ $label }}
    </button>
    @endforeach
</div>

@foreach(['7d', '14d', '30d'] as $stage)
@php
    $stageTemplates = $templates[$stage] ?? collect();
    $emailT = $stageTemplates->firstWhere('channel', 'email');
    $waT    = $stageTemplates->firstWhere('channel', 'whatsapp');
@endphp
<div class="re-panel {{ $stage === '7d' ? 'active' : '' }}" id="panel-{{ $stage }}">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

        {{-- Email --}}
        <div class="m-card">
            <div class="m-card-header" style="display:flex;align-items:center;justify-content:space-between;">
                <div class="m-card-title"><i class="bi bi-envelope" style="color:#0085f3;"></i> Email</div>
                @if($emailT)
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;color:#6b7280;">
                    <input type="checkbox" class="re-toggle" data-id="{{ $emailT->id }}" {{ $emailT->is_active ? 'checked' : '' }}
                           style="width:16px;height:16px;accent-color:#0085f3;">
                    Ativo
                </label>
                @endif
            </div>
            @if($emailT)
            <div style="padding:16px 20px;">
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:11.5px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Assunto</label>
                    <input type="text" class="re-input re-subject" data-id="{{ $emailT->id }}" value="{{ $emailT->subject }}">
                </div>
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:11.5px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Corpo do email</label>
                    <textarea class="re-textarea re-body" data-id="{{ $emailT->id }}" rows="10">{{ $emailT->body }}</textarea>
                </div>
                <div style="display:flex;gap:8px;">
                    <button class="m-btn m-btn-outline" onclick="sendTest('{{ $stage }}', 'email')">
                        <i class="bi bi-send"></i> Enviar teste
                    </button>
                    <a href="{{ route('master.reengagement.preview', ['stage' => $stage, 'locale' => $currentLocale]) }}" target="_blank" class="m-btn m-btn-outline">
                        <i class="bi bi-eye"></i> Preview
                    </a>
                </div>
            </div>
            @else
            <div style="padding:40px;text-align:center;color:#9ca3af;font-size:13px;">Template não encontrado. Execute o seeder.</div>
            @endif
        </div>

        {{-- WhatsApp --}}
        <div class="m-card">
            <div class="m-card-header" style="display:flex;align-items:center;justify-content:space-between;">
                <div class="m-card-title"><i class="bi bi-whatsapp" style="color:#25d366;"></i> WhatsApp</div>
                @if($waT)
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;color:#6b7280;">
                    <input type="checkbox" class="re-toggle" data-id="{{ $waT->id }}" {{ $waT->is_active ? 'checked' : '' }}
                           style="width:16px;height:16px;accent-color:#25d366;">
                    Ativo
                </label>
                @endif
            </div>
            @if($waT)
            <div style="padding:16px 20px;">
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:11.5px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Mensagem</label>
                    <textarea class="re-textarea re-body" data-id="{{ $waT->id }}" rows="12">{{ $waT->body }}</textarea>
                </div>
                <div style="font-size:11px;color:#9ca3af;margin-bottom:14px;">
                    <strong>Formatação WhatsApp:</strong> *negrito*, _itálico_, ~tachado~, ```código```
                </div>
                <button class="m-btn m-btn-outline" style="color:#25d366;border-color:#25d366;" onclick="sendTest('{{ $stage }}', 'whatsapp')">
                    <i class="bi bi-whatsapp"></i> Enviar teste
                </button>
            </div>
            @else
            <div style="padding:40px;text-align:center;color:#9ca3af;font-size:13px;">Template não encontrado. Execute o seeder.</div>
            @endif
        </div>
    </div>
</div>
@endforeach

{{-- Variables Reference --}}
<div class="m-card" style="margin-top:20px;">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-braces"></i> Variáveis disponíveis</div>
    </div>
    <div style="padding:16px 20px;display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
        @foreach($variables as $var => $desc)
        <div style="display:flex;align-items:center;gap:6px;">
            <code style="background:#eff6ff;color:#0085f3;padding:2px 8px;border-radius:4px;font-size:11.5px;font-weight:600;white-space:nowrap;">{{ $var }}</code>
            <span style="font-size:11.5px;color:#6b7280;">{{ $desc }}</span>
        </div>
        @endforeach
    </div>
</div>

{{-- Save floating button --}}
<div style="position:fixed;bottom:24px;right:24px;z-index:100;">
    <button class="m-btn m-btn-primary" onclick="saveTemplates()" style="box-shadow:0 4px 16px rgba(0,133,243,.3);padding:10px 24px;">
        <i class="bi bi-check-lg"></i> Salvar alterações
    </button>
</div>

{{-- Test Modal --}}
<div style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center;" id="testModal">
    <div style="background:#fff;border-radius:14px;width:380px;max-width:95vw;overflow:hidden;">
        <div style="padding:18px 22px;border-bottom:1px solid #f0f2f7;">
            <h4 style="margin:0;font-size:15px;font-weight:700;color:#1a1d23;" id="testModalTitle">Enviar teste</h4>
        </div>
        <div style="padding:18px 22px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;" id="testModalLabel">Destinatário</label>
            <input type="text" class="re-input" id="testTarget" placeholder="">
        </div>
        <div style="padding:14px 22px;border-top:1px solid #f0f2f7;display:flex;justify-content:flex-end;gap:8px;">
            <button class="m-btn m-btn-outline" onclick="document.getElementById('testModal').style.display='none'">Cancelar</button>
            <button class="m-btn m-btn-primary" onclick="confirmSendTest()"><i class="bi bi-send"></i> Enviar</button>
        </div>
    </div>
</div>

<style>
    .re-tab {
        padding:10px 20px;font-size:13px;font-weight:600;color:#6b7280;background:none;border:none;
        border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;transition:all .15s;
    }
    .re-tab:hover { color:#374151; }
    .re-tab.active { color:#0085f3;border-bottom-color:#0085f3; }
    .re-locale-tab {
        display:inline-flex;align-items:center;gap:8px;
        padding:8px 16px;font-size:13px;font-weight:600;color:#6b7280;
        background:#f3f4f6;border:1.5px solid #e8eaf0;border-radius:9px;
        text-decoration:none;cursor:pointer;transition:all .15s;
    }
    .re-locale-tab:hover { background:#eff6ff;color:#0085f3;border-color:#bfdbfe; }
    .re-locale-tab.active {
        background:#0085f3;color:#fff;border-color:#0085f3;
    }
    .re-panel { display:none; }
    .re-panel.active { display:block; }
    .re-input {
        width:100%;padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;
        font-family:inherit;color:#1a1d23;outline:none;transition:border-color .15s;background:#fafafa;
    }
    .re-input:focus { border-color:#0085f3;background:#fff; }
    .re-textarea {
        width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:12px;
        font-family:'DM Mono',monospace,inherit;color:#1a1d23;outline:none;resize:vertical;
        background:#fafafa;line-height:1.6;
    }
    .re-textarea:focus { border-color:#0085f3;background:#fff; }
    @media (max-width:768px) {
        .re-panel > div { grid-template-columns:1fr !important; }
    }
</style>
@endsection

@push('scripts')
<script>
function getCSRF() { return document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'; }
let testStage = '', testChannel = '';

function switchReTab(el) {
    document.querySelectorAll('.re-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.re-panel').forEach(p => p.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('panel-' + el.dataset.stage).classList.add('active');
}

function saveTemplates() {
    const templates = [];
    document.querySelectorAll('.re-body').forEach(function(el) {
        const id = el.dataset.id;
        const subjectEl = document.querySelector('.re-subject[data-id="' + id + '"]');
        const activeEl = document.querySelector('.re-toggle[data-id="' + id + '"]');
        templates.push({
            id: parseInt(id),
            subject: subjectEl ? subjectEl.value : null,
            body: el.value,
            is_active: activeEl ? activeEl.checked : true,
        });
    });

    fetch('{{ route("master.reengagement.update") }}', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': getCSRF() },
        body: JSON.stringify({ templates: templates })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) toastr.success(data.message);
        else toastr.error(data.message || 'Erro ao salvar.');
    })
    .catch(() => toastr.error('Erro de conexão.'));
}

function sendTest(stage, channel) {
    testStage = stage;
    testChannel = channel;
    var localeLabel = '{{ $availableLocales[$currentLocale] }}';
    document.getElementById('testModalTitle').textContent = 'Enviar teste — ' + stage + ' (' + localeLabel + ')';
    document.getElementById('testModalLabel').textContent = channel === 'email' ? 'Email de teste' : 'Número WhatsApp (qualquer formato — ex: (11) 99999-9999, +1 415 555 0100)';
    document.getElementById('testTarget').placeholder = channel === 'email' ? 'email@exemplo.com' : '+55 11 99999-9999';
    document.getElementById('testTarget').value = '';
    document.getElementById('testModal').style.display = 'flex';
}

function confirmSendTest() {
    const target = document.getElementById('testTarget').value.trim();
    if (!target) { toastr.warning('Informe o destinatário.'); return; }

    fetch('{{ route("master.reengagement.test") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': getCSRF() },
        body: JSON.stringify({ stage: testStage, channel: testChannel, target: target, locale: '{{ $currentLocale }}' })
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('testModal').style.display = 'none';
        if (data.success) toastr.success(data.message);
        else toastr.error(data.message || 'Erro.');
    })
    .catch(() => toastr.error('Erro de conexão.'));
}
</script>
@endpush
