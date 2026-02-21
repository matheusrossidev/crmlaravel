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

@push('scripts')
<script>
window.chatbotBuilderData = {!! json_encode($builderData) !!};
</script>
@vite('resources/js/chatbot-builder.jsx')
@endpush
