@extends('tenant.layouts.app')

@php
    $title    = 'Builder: ' . $flow->name;
    $pageIcon = 'diagram-3';
@endphp

@push('styles')
<style>
    .content-wrapper { display: flex; flex-direction: column; }

    .builder-topbar {
        display: flex; align-items: center; gap: 12px;
        padding: 10px 20px; background: #fff;
        border-bottom: 1px solid #e8eaf0; flex-shrink: 0;
        font-family: 'Inter', sans-serif;
    }

    .builder-wrap {
        flex: 1; overflow: hidden;
        height: calc(100vh - 110px);
    }

    #chatbot-builder-root { height: 100%; }

    .builder-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 12px;
        border: 1.5px solid #e8eaf0; border-radius: 8px;
        background: #fff; color: #374151;
        font-size: 12.5px; font-weight: 600;
        font-family: 'Inter', sans-serif;
        text-decoration: none; cursor: pointer;
        transition: border-color .15s, background .15s;
        white-space: nowrap;
    }
    .builder-btn:hover { border-color: #c7cbd4; background: #f9fafb; color: #1a1d23; }
    .builder-btn i { font-size: 12px; }

    /* ── Override ReactFlow default node wrapper styles ────────────────────
       ReactFlow adds padding, border, background and hover shadows by default.
       Our BaseNode handles its own visual appearance, so we neutralize these. */
    .react-flow__node-input,
    .react-flow__node-default,
    .react-flow__node-output,
    .react-flow__node-group,
    .react-flow__node-message,
    .react-flow__node-condition,
    .react-flow__node-action,
    .react-flow__node-end {
        padding: 0 !important;
        border: none !important;
        background: transparent !important;
        width: auto !important;
    }

    .react-flow__node-input.selectable:hover,
    .react-flow__node-default.selectable:hover,
    .react-flow__node-output.selectable:hover,
    .react-flow__node-group.selectable:hover {
        box-shadow: none !important;
    }

    /* Remove ReactFlow default selection/focus outlines */
    .react-flow__node:focus,
    .react-flow__node:focus-visible { outline: none !important; }
    .react-flow__node.selected { box-shadow: none !important; }
</style>
@endpush

@section('content')
<div class="builder-topbar">

    {{-- Voltar --}}
    <a href="{{ route('chatbot.flows.index') }}"
       style="color:#2563eb;font-size:13.5px;font-weight:500;text-decoration:underline;display:inline-flex;align-items:center;gap:5px;font-family:'Inter',sans-serif;white-space:nowrap;">
        <i class="bi bi-arrow-left" style="font-size:12px;"></i> Voltar
    </a>

    <div style="width:1px;height:18px;background:#e8eaf0;flex-shrink:0;"></div>

    {{-- Nome + status --}}
    <div style="display:flex;align-items:center;gap:8px;min-width:0;">
        <span style="font-size:14px;font-weight:700;color:#1a1d23;font-family:'Inter',sans-serif;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            {{ $flow->name }}
        </span>
        <span class="{{ $flow->is_active ? 'badge-active' : 'badge-inactive' }}">
            {{ $flow->is_active ? 'Ativo' : 'Inativo' }}
        </span>
    </div>

    {{-- Ações --}}
    <div style="margin-left:auto;display:flex;gap:8px;align-items:center;">
        @if($flow->channel === 'website' && $flow->website_token)
        <button type="button" class="builder-btn" onclick="showEmbedCode()" title="Código de incorporação">
            <i class="bi bi-code-slash"></i> Embed
        </button>
        @endif
        <a href="{{ route('chatbot.flows.edit', $flow) }}?settings=1" class="builder-btn">
            <i class="bi bi-gear"></i> Configurações do fluxo
        </a>
        <a href="{{ route('chatbot.flows.index') }}" class="builder-btn">
            <i class="bi bi-grid"></i> Todos os fluxos
        </a>
    </div>

</div>

<div class="builder-wrap">
    <div id="chatbot-builder-root"></div>
</div>
@endsection

@if($flow->channel === 'website' && $flow->website_token)
{{-- Modal: Código de incorporação --}}
<div id="embedModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px 32px;max-width:560px;width:90%;box-shadow:0 8px 40px rgba(0,0,0,.18);font-family:'Inter',sans-serif;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h2 style="font-size:16px;font-weight:700;color:#1a1d23;margin:0;">Código de Incorporação</h2>
            <button onclick="closeEmbedModal()" style="background:none;border:none;cursor:pointer;font-size:18px;color:#6b7280;">&times;</button>
        </div>
        <p style="font-size:13px;color:#6b7280;margin-bottom:14px;">Cole este código antes do <code>&lt;/body&gt;</code> no seu site:</p>
        <div style="position:relative;">
            <textarea id="embedCode" readonly style="width:100%;height:90px;font-family:monospace;font-size:12px;padding:10px 12px;border:1.5px solid #e8eaf0;border-radius:8px;background:#f9fafb;color:#374151;resize:none;box-sizing:border-box;outline:none;">&lt;script src="{{ config('app.url') }}/widget.js" data-token="{{ $flow->website_token }}"&gt;&lt;/script&gt;</textarea>
            <button onclick="copyEmbed()" id="copyEmbedBtn" style="position:absolute;top:8px;right:8px;padding:4px 10px;background:#0085f3;color:#fff;border:none;border-radius:6px;font-size:11.5px;font-weight:600;cursor:pointer;">Copiar</button>
        </div>
        <p style="font-size:11.5px;color:#9ca3af;margin-top:10px;">O widget aparecerá no canto inferior direito do seu site.</p>
    </div>
</div>
@endif

@push('scripts')
<script>
window.chatbotBuilderData = {!! json_encode($builderData) !!};

function showEmbedCode() {
    const m = document.getElementById('embedModal');
    if (m) { m.style.display = 'flex'; }
}
function closeEmbedModal() {
    const m = document.getElementById('embedModal');
    if (m) { m.style.display = 'none'; }
}
function copyEmbed() {
    const ta = document.getElementById('embedCode');
    if (!ta) return;
    ta.select();
    document.execCommand('copy');
    const btn = document.getElementById('copyEmbedBtn');
    if (btn) { btn.textContent = 'Copiado!'; setTimeout(() => btn.textContent = 'Copiar', 2000); }
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEmbedModal(); });
</script>
@vite('resources/js/chatbot-builder.jsx')
@endpush
