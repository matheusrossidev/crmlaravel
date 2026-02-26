@extends('master.layouts.app')
@php
    $title    = 'Logs do Sistema';
    $pageIcon = 'file-text';
@endphp

@section('content')

<div style="display:grid;grid-template-columns:280px 1fr;gap:20px;height:calc(100vh - 160px);">

    {{-- Sidebar de arquivos --}}
    <div class="m-card" style="overflow-y:auto;padding:0;">
        <div style="padding:14px 16px;border-bottom:1px solid #f1f5f9;font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">
            Arquivos de Log
        </div>
        <div id="fileList">
            @forelse($files as $file)
            <div class="log-file-item" data-file="{{ $file['name'] }}" onclick="loadLog('{{ $file['name'] }}')">
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="bi bi-file-text" style="color:#9ca3af;flex-shrink:0;"></i>
                    <div>
                        <div style="font-size:12.5px;font-weight:600;word-break:break-all;">{{ $file['name'] }}</div>
                        <div style="font-size:11px;color:#9ca3af;">{{ $file['size'] }} · {{ $file['modified'] }}</div>
                    </div>
                </div>
            </div>
            @empty
            <div style="padding:24px;text-align:center;color:#9ca3af;font-size:13px;">
                Nenhum arquivo de log encontrado.
            </div>
            @endforelse
        </div>
    </div>

    {{-- Viewer --}}
    <div class="m-card" style="display:flex;flex-direction:column;padding:0;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:12px;flex-shrink:0;">
            <div id="currentFile" style="font-size:13px;font-weight:600;color:#374151;flex:1;">Selecione um arquivo</div>
            <div style="display:flex;align-items:center;gap:8px;">
                <label style="font-size:12px;color:#6b7280;">Linhas:</label>
                <select id="linesSelect" onchange="reloadLog()" style="border:1px solid #e5e7eb;border-radius:7px;padding:5px 8px;font-size:12px;">
                    <option value="100">100</option>
                    <option value="200" selected>200</option>
                    <option value="500">500</option>
                    <option value="1000">1000</option>
                </select>
                <button onclick="reloadLog()" class="m-btn m-btn-ghost m-btn-sm" title="Atualizar">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button onclick="scrollToBottom()" class="m-btn m-btn-ghost m-btn-sm" title="Ir ao fim">
                    <i class="bi bi-arrow-down-circle"></i>
                </button>
            </div>
        </div>
        <div id="logContent" style="flex:1;overflow-y:auto;background:#0f172a;padding:16px;font-family:'Courier New',monospace;font-size:12px;line-height:1.6;color:#e2e8f0;white-space:pre-wrap;word-break:break-all;">
            <span style="color:#4b5563;">← Selecione um arquivo na lista ao lado para visualizar o conteúdo.</span>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.log-file-item {
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f8fafc;
    transition: background .12s;
}
.log-file-item:hover { background: #f8fafc; }
.log-file-item.active { background: #eff6ff; border-left: 3px solid #3B82F6; }

/* Colorize log levels */
#logContent .log-error  { color: #f87171; }
#logContent .log-warn   { color: #fbbf24; }
#logContent .log-info   { color: #60a5fa; }
#logContent .log-debug  { color: #a3e635; }
</style>
@endpush

@push('scripts')
<script>
const ROUTE_LOG_CONTENT = "{{ route('master.logs.content') }}";
const CSRF = document.querySelector('meta[name=csrf-token]')?.content;

let currentFile = null;

async function loadLog(filename) {
    currentFile = filename;

    // Highlight active item
    document.querySelectorAll('.log-file-item').forEach(el => el.classList.remove('active'));
    document.querySelector(`[data-file="${filename}"]`)?.classList.add('active');

    document.getElementById('currentFile').textContent = filename;
    document.getElementById('logContent').textContent = 'Carregando...';

    await fetchLog(filename);
}

async function fetchLog(filename) {
    const lines = document.getElementById('linesSelect').value;
    try {
        const res = await fetch(`${ROUTE_LOG_CONTENT}?file=${encodeURIComponent(filename)}&lines=${lines}`, {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.error) {
            document.getElementById('logContent').textContent = 'Erro: ' + data.error;
            return;
        }
        renderLog(data.content || '');
    } catch (e) {
        document.getElementById('logContent').textContent = 'Erro de conexão.';
    }
}

function renderLog(raw) {
    const el = document.getElementById('logContent');
    // Basic colorization via HTML
    const escaped = raw
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    const colored = escaped
        .replace(/(\.ERROR|ERROR|CRITICAL|emergency)/g, '<span style="color:#f87171;font-weight:700;">$1</span>')
        .replace(/(\.WARNING|WARNING|WARN)/g, '<span style="color:#fbbf24;font-weight:600;">$1</span>')
        .replace(/(\.INFO|INFO)/g, '<span style="color:#60a5fa;">$1</span>')
        .replace(/(\.DEBUG|DEBUG)/g, '<span style="color:#a3e635;">$1</span>')
        .replace(/(\[\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*\])/g, '<span style="color:#94a3b8;">$1</span>');

    el.innerHTML = colored;
    scrollToBottom();
}

function reloadLog() {
    if (currentFile) fetchLog(currentFile);
}

function scrollToBottom() {
    const el = document.getElementById('logContent');
    el.scrollTop = el.scrollHeight;
}

// Auto-reload every 30s if a file is selected
setInterval(() => { if (currentFile) fetchLog(currentFile); }, 30000);
</script>
@endpush
