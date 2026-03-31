@extends('tenant.layouts.app')

@php
    $title    = 'Chatbot';
    $pageIcon = 'robot';

    $pipelinesJs = $pipelines->map(fn($p) => [
        'id'     => $p->id,
        'name'   => $p->name,
        'stages' => $p->stages->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values(),
    ])->values();
@endphp

@push('styles')
<style>
/* ── Page layout ──────────────────────────────────────────────────── */
.cb-page { display: flex; flex-direction: column; height: calc(100vh - 64px); overflow: hidden; }

.cb-header {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 24px;
    background: #fff;
    border-bottom: 1px solid #e8eaf0;
    flex-shrink: 0;
    z-index: 10;
}
.cb-back {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1.5px solid #e8eaf0; background: #fff; color: #374151;
    display: inline-flex; align-items: center; justify-content: center;
    text-decoration: none; font-size: 15px; flex-shrink: 0;
    transition: all .15s;
}
.cb-back:hover { background: #f3f4f6; border-color: #d1d5db; color: #111827; }
.cb-name-input {
    flex: 1; min-width: 0;
    border: 1.5px solid #e8eaf0; border-radius: 9px;
    padding: 8px 14px; font-size: 14px; font-weight: 600;
    color: #1a1d23; outline: none; transition: border-color .15s;
    font-family: inherit;
}
.cb-name-input:focus { border-color: #3b82f6; }
.cb-name-input::placeholder { font-weight: 400; color: #9ca3af; }
.cb-header-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.cb-status-badge {
    padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
    background: #f3f4f6; color: #6b7280; cursor: pointer;
}
.cb-status-badge.active { background: #dcfce7; color: #16a34a; }

/* ── Builder body ─────────────────────────────────────────────────── */
.cb-builder { display: flex; flex: 1; overflow: hidden; }

/* ── Left sidebar ─────────────────────────────────────────────────── */
.cb-sidebar {
    width: 230px; flex-shrink: 0;
    background: #fafafa;
    border-right: 1px solid #e8eaf0;
    overflow-y: auto;
    padding: 16px 0 24px;
}
.cb-sidebar-section { margin-bottom: 6px; }
.cb-sidebar-section-title {
    padding: 8px 16px 4px;
    font-size: 10.5px; font-weight: 700; color: #9ca3af;
    text-transform: uppercase; letter-spacing: .07em;
}
.cb-block-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 16px; cursor: pointer;
    font-size: 13px; color: #374151; font-weight: 500;
    transition: background .12s, color .12s;
}
.cb-block-item:hover { background: #eff6ff; color: #2563eb; }
.cb-block-item .cb-block-icon {
    width: 26px; height: 26px; border-radius: 7px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 12px; flex-shrink: 0;
}
.cb-block-item.message  .cb-block-icon { background: #dbeafe; color: #2563eb; }
.cb-block-item.input    .cb-block-icon { background: #e0e7ff; color: #4f46e5; }
.cb-block-item.condition .cb-block-icon { background: #fef9c3; color: #b45309; }
.cb-block-item.action   .cb-block-icon { background: #dcfce7; color: #16a34a; }
.cb-block-item.delay    .cb-block-icon { background: #fce7f3; color: #db2777; }
.cb-block-item.end      .cb-block-icon { background: #f3f4f6; color: #6b7280; }
.cb-block-item.cards    .cb-block-icon { background: #f0fdf4; color: #16a34a; }
.cb-node.cards  .cb-node-bar  { background: #16a34a; }
.cb-node.cards  .cb-node-icon { background: #f0fdf4; color: #16a34a; }
.cb-sidebar-divider { height: 1px; background: #f0f2f7; margin: 8px 16px; }

/* ── Edit Panel (drawer lateral) ─────────────────────────────────── */
.cb-edit-panel {
    position: fixed; right: 0; top: 64px; bottom: 0; width: 380px;
    background: #fff; border-left: 1px solid #e8eaf0;
    box-shadow: -4px 0 20px rgba(0,0,0,.08); z-index: 20;
    overflow-y: auto; transform: translateX(100%); transition: transform .2s ease;
}
.cb-edit-panel.open { transform: translateX(0); }
.cb-edit-panel-header {
    display: flex; align-items: center; gap: 10px;
    padding: 16px 20px; border-bottom: 1px solid #f0f2f7;
    position: sticky; top: 0; background: #fff; z-index: 1;
}
.cb-edit-panel-body { padding: 16px 20px; }
.cb-edit-panel-body label { font-size: 12px; font-weight: 600; color: #6b7280; display: block; margin-bottom: 5px; }
.cb-edit-panel-body label + label { margin-top: 10px; }
.cb-edit-panel-body .form-control, .cb-edit-panel-body .form-select {
    border: 1.5px solid #e8eaf0; border-radius: 8px; padding: 7px 11px;
    font-size: 13px; color: #374151; background: #fff; outline: none;
    width: 100%; font-family: inherit; transition: border-color .15s;
}
.cb-edit-panel-body .form-control:focus, .cb-edit-panel-body .form-select:focus { border-color: #3b82f6; }
.cb-edit-panel-body .cb-editable {
    border: 1.5px solid #e8eaf0; border-radius: 8px; padding: 8px 12px;
    font-size: 13px; color: #374151; outline: none; min-height: 60px;
    transition: border-color .15s; line-height: 1.5;
}
.cb-edit-panel-body .cb-editable:focus { border-color: #3b82f6; }
.cb-edit-panel-body .row-pair { display: flex; gap: 8px; }
.cb-edit-panel-body .row-pair > * { flex: 1; min-width: 0; }
.cb-edit-panel-close {
    margin-left: auto; width: 28px; height: 28px; border-radius: 6px;
    border: 1px solid #e8eaf0; background: transparent; color: #6b7280;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 14px;
}
.cb-edit-panel-close:hover { background: #f3f4f6; color: #111; }
.cb-node.selected { border-color: #3b82f6 !important; box-shadow: 0 0 0 3px rgba(59,130,246,.15), 0 4px 16px rgba(0,0,0,.1) !important; }
.cb-node { cursor: pointer; }
.cb-node-preview { padding: 10px 18px; font-size: 13px; color: #6b7280; line-height: 1.5; }
.cb-node-preview .branch-pills { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px; }
.cb-node-preview .branch-pill { background: #eff6ff; color: #2563eb; padding: 2px 8px; border-radius: 99px; font-size: 11px; font-weight: 500; }
.cb-node-preview .preview-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #9ca3af; margin-top: 4px; }
.cb-node.message  { background: radial-gradient(circle at left center, rgba(59,130,246,.06), transparent 60%), #fff; }
.cb-node.input    { background: radial-gradient(circle at left center, rgba(139,92,246,.06), transparent 60%), #fff; }
.cb-node.condition { background: radial-gradient(circle at left center, rgba(245,158,11,.06), transparent 60%), #fff; }
.cb-node.action   { background: radial-gradient(circle at left center, rgba(16,185,129,.06), transparent 60%), #fff; }
.cb-node.delay    { background: radial-gradient(circle at left center, rgba(236,72,153,.06), transparent 60%), #fff; }
.cb-node.end      { background: radial-gradient(circle at left center, rgba(107,114,128,.06), transparent 60%), #fff; }
.cb-node.cards    { background: radial-gradient(circle at left center, rgba(22,163,74,.06), transparent 60%), #fff; }

/* ── Canvas ───────────────────────────────────────────────────────── */
.cb-canvas {
    flex: 1; overflow: auto;
    background: #f4f6fb;
    padding: 36px 24px 60px;
    cursor: grab;
}
.cb-canvas.is-panning { cursor: grabbing; user-select: none; }
.cb-flow {
    width: fit-content;
    min-width: 520px;
    margin: 0 auto;
    padding: 0 40px;
    transform-origin: top center;
    transition: transform .1s ease;
    display: flex;
    flex-direction: column;
    position: relative;
    align-items: center;
    gap: 24px;
}

/* ── Node ─────────────────────────────────────────────────────────── */
.cb-node {
    background: #fff;
    border: 1.5px solid #e8eaf0;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    overflow: visible;
    position: relative;
    transition: box-shadow .15s;
    width: 420px;
}
.cb-node:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); }
.cb-node-bar { display: none; }
.cb-node.start    .cb-node-bar { background: #6366f1; }
.cb-node.message  .cb-node-bar { background: #3b82f6; }
.cb-node.input    .cb-node-bar { background: #8b5cf6; }
.cb-node.condition .cb-node-bar { background: #f59e0b; }
.cb-node.action   .cb-node-bar { background: #10b981; }
.cb-node.delay    .cb-node-bar { background: #ec4899; }
.cb-node.end      .cb-node-bar { background: #6b7280; }

.cb-node-head {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 14px 12px 18px;
    border-bottom: 1px solid #f3f4f6;
}
.cb-node-icon {
    width: 30px; height: 30px; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 13px; flex-shrink: 0;
}
.cb-node.start    .cb-node-icon { background: #e0e7ff; color: #6366f1; }
.cb-node.message  .cb-node-icon { background: #dbeafe; color: #2563eb; }
.cb-node.input    .cb-node-icon { background: #ede9fe; color: #7c3aed; }
.cb-node.condition .cb-node-icon { background: #fef9c3; color: #b45309; }
.cb-node.action   .cb-node-icon { background: #dcfce7; color: #16a34a; }
.cb-node.delay    .cb-node-icon { background: #fce7f3; color: #db2777; }
.cb-node.end      .cb-node-icon { background: #f3f4f6; color: #6b7280; }

.cb-node-label { flex: 1; min-width: 0; }
.cb-node-type {
    font-size: 10.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: #9ca3af;
}
.cb-node-name { font-size: 13px; font-weight: 600; color: #1a1d23; margin-top: 1px; }

.cb-node-remove {
    width: 26px; height: 26px; border-radius: 6px;
    border: 1px solid #e8eaf0; background: transparent; color: #9ca3af;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 12px; flex-shrink: 0;
    transition: all .15s;
}
.cb-node-remove:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

.cb-node-body { padding: 14px 18px; }
.cb-node-body label {
    font-size: 12px; font-weight: 600; color: #6b7280;
    display: block; margin-bottom: 5px;
}
.cb-node-body label + label { margin-top: 10px; }

/* ── Form controls ────────────────────────────────────────────────── */
.cb-node-body .form-control,
.cb-node-body .form-select {
    border: 1.5px solid #e8eaf0; border-radius: 8px;
    padding: 7px 11px; font-size: 13px; color: #374151;
    background: #fff; outline: none; width: 100%;
    box-sizing: border-box; font-family: inherit;
    transition: border-color .15s;
}
.cb-node-body .form-control:focus,
.cb-node-body .form-select:focus { border-color: #3b82f6; }
.cb-node-body textarea.form-control { resize: vertical; min-height: 64px; }
.cb-node-body .row-pair { display: flex; gap: 8px; }
.cb-node-body .row-pair > * { flex: 1; min-width: 0; }
.cb-node-body .cb-checkbox {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: #374151; cursor: pointer;
    margin-top: 8px;
}
.cb-node-body .cb-checkbox input { margin: 0; }

/* ── SVG Overlay connections ──────────────────────────────────────── */
.cb-svg-overlay {
    position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
    pointer-events: none; overflow: visible;
    z-index: 0;
}
.cb-path { fill: none; stroke: #d1d5db; stroke-width: 2; }
.cb-path-animated { /* sólido — sem animação */ }
.cb-path-dot { fill: #d1d5db; }

/* Legacy connector — hidden, replaced by SVG */
.cb-connector { display: none; }

/* ── Add step button ──────────────────────────────────────────────── */
.cb-add-step {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    width: 100%; padding: 10px;
    border: 2px dashed #d1d5db; border-radius: 12px;
    background: transparent; color: #6b7280;
    font-size: 13px; font-weight: 500;
    cursor: pointer; transition: all .15s;
    margin-top: 4px;
}
.cb-add-step:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }

/* ── Tree branching ──────────────────────────────────────────────── */
.cb-branches-wrapper {
    margin-top: 4px;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

/* Vertical line from parent node down to horizontal bar — hidden, replaced by SVG */
.cb-tree-stem {
    width: 2px; height: 28px;
    background: transparent;
}

/* Horizontal bar connecting all branches — hidden, replaced by SVG */
.cb-tree-hbar {
    height: 2px; background: transparent;
    position: relative;
}

/* Container flex das colunas */
.cb-branches {
    display: flex;
    gap: 24px;
    justify-content: center;
    flex-wrap: nowrap;
    position: relative;
}

/* Cada coluna de branch */
.cb-branch-col {
    flex: 0 0 auto;
    width: 320px;
    background: #f8fafc;
    border: 1.5px solid #e8eaf0;
    border-radius: 10px;
    overflow: visible;
    position: relative;
    display: flex;
    flex-direction: column;
}
/* Vertical line from hbar down to each column — hidden, replaced by SVG */
.cb-branch-col::before {
    content: '';
    display: block;
    width: 2px; height: 20px;
    background: transparent;
    margin: -20px auto 0;
    position: relative;
    z-index: 1;
}
/* Branch padrão com borda tracejada */
.cb-branch-col.default {
    border-style: dashed;
    border-color: #c7c7c7;
}

/* Header da branch */
.cb-branch-header {
    padding: 8px 12px;
    background: #f0f2f7;
    border-bottom: 1px solid #e8eaf0;
    display: flex; align-items: center;
    justify-content: space-between;
    gap: 6px;
    border-radius: 10px 10px 0 0;
}
.cb-branch-col.default .cb-branch-header {
    background: #f9fafb;
}
.cb-branch-label-input {
    font-size: 12.5px; font-weight: 700; color: #374151;
    border: none; background: transparent; outline: none;
    flex: 1; min-width: 0; padding: 0;
    font-family: inherit;
}
.cb-branch-label-input:focus { color: #2563eb; }
.cb-branch-remove {
    width: 20px; height: 20px; border-radius: 5px;
    border: none; background: transparent; color: #c7c7c7;
    cursor: pointer; font-size: 11px;
    display: inline-flex; align-items: center; justify-content: center;
    transition: all .15s; flex-shrink: 0;
}
.cb-branch-remove:hover { background: #fee2e2; color: #ef4444; }

/* Config editável (keywords, operador) */
.cb-branch-config {
    padding: 8px 12px;
    border-bottom: 1px solid #f0f2f7;
    background: #fafbfd;
}
.cb-branch-config label {
    font-size: 11px; font-weight: 600; color: #9ca3af;
    display: block; margin-bottom: 3px;
}
.cb-branch-config .form-control,
.cb-branch-config .form-select {
    border: 1.5px solid #e8eaf0; border-radius: 6px;
    padding: 5px 8px; font-size: 12px; color: #374151;
    background: #fff; outline: none; width: 100%;
    font-family: inherit; transition: border-color .15s;
}
.cb-branch-config .form-control:focus,
.cb-branch-config .form-select:focus { border-color: #3b82f6; }
.cb-branch-config .row-pair { display: flex; gap: 6px; }
.cb-branch-config .row-pair > * { flex: 1; min-width: 0; }

/* Body da branch (sub-nós) */
.cb-branch-body {
    padding: 12px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Nós compactos dentro de branches */
.cb-branch-body .cb-node {
    width: 100%;
    font-size: 12px;
}
.cb-branch-body .cb-node-head { padding: 8px 10px 8px 14px; }
.cb-branch-body .cb-node-body { padding: 10px 14px; }
.cb-branch-body .cb-node-icon { width: 24px; height: 24px; font-size: 11px; }
.cb-branch-body .cb-node-type { font-size: 9.5px; }
.cb-branch-body .cb-node-name { font-size: 12px; }
.cb-branch-body .cb-connector { display: none; }
.cb-branch-body .cb-add-step { font-size: 12px; padding: 8px; }
.cb-branch-body .cb-node-body .form-control,
.cb-branch-body .cb-node-body .form-select { font-size: 12px; padding: 5px 9px; }
.cb-branch-body .cb-node-body textarea.form-control { min-height: 48px; }
.cb-branch-body .cb-node-remove { width: 22px; height: 22px; font-size: 10px; }
.cb-branch-body .cb-node-move { width: 18px; height: 18px; font-size: 9px; }

/* ── Variable hint ───────────────────────────────────────────────── */
.cb-var-hint {
    margin-top: 6px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
}
.cb-var-hint-label {
    font-size: 11px; font-weight: 600; color: #9ca3af;
    display: inline-flex; align-items: center; gap: 3px;
}
.cb-var-tag {
    display: inline-block;
    padding: 1px 7px; border-radius: 5px;
    font-size: 11px; font-weight: 600; font-family: monospace;
    background: #eff6ff; color: #2563eb;
    border: 1px solid #bfdbfe; cursor: pointer;
    transition: all .12s;
}
.cb-var-tag:hover { background: #dbeafe; border-color: #93c5fd; }

/* ── Contenteditable + Variable chips ─────────────────────────────── */
.cb-editable {
    border: 1.5px solid #e8eaf0; border-radius: 8px;
    padding: 7px 11px; font-size: 13px; color: #374151;
    background: #fff; outline: none; width: 100%;
    box-sizing: border-box; font-family: inherit;
    transition: border-color .15s;
    min-height: 64px; white-space: pre-wrap;
    word-break: break-word; overflow-y: auto;
    line-height: 1.6;
}
.cb-editable:focus { border-color: #3b82f6; }
.cb-editable:empty::before {
    content: attr(data-placeholder);
    color: #9ca3af; pointer-events: none;
}
.cb-var-chip {
    display: inline-block;
    padding: 1px 8px; border-radius: 4px;
    background: #2563eb; color: #fff;
    font-size: 12px; font-weight: 600; font-family: monospace;
    user-select: all; cursor: default;
    vertical-align: baseline; line-height: 1.5;
    white-space: nowrap;
}
.cb-branch-body .cb-editable { min-height: 48px; font-size: 12px; }
.cb-branch-body .cb-var-chip { font-size: 11px; padding: 0px 6px; }

/* Botão adicionar branch */
.cb-add-branch-col {
    flex: 0 0 auto;
    width: 44px;
    min-height: 80px;
    border: 2px dashed #d1d5db;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    color: #9ca3af; font-size: 20px;
    transition: all .15s;
    margin-top: 15px; /* compensate for ::before */
}
.cb-add-branch-col:hover {
    border-color: #3b82f6; color: #3b82f6; background: #eff6ff;
}

/* ── Image upload area ────────────────────────────────────────────── */
.cb-image-area {
    margin-top: 8px; padding: 10px;
    border: 1.5px dashed #e8eaf0; border-radius: 8px;
    text-align: center; cursor: pointer;
    transition: all .15s; font-size: 12px; color: #9ca3af;
}
.cb-image-area:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
.cb-image-area img { max-width: 100%; border-radius: 6px; margin-top: 6px; }

/* ── Buttons ──────────────────────────────────────────────────────── */
.btn-primary-sm {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; background: #3b82f6; color: #fff;
    border: none; border-radius: 9px; font-size: 13.5px;
    font-weight: 600; cursor: pointer; transition: background .15s;
    font-family: inherit;
}
.btn-primary-sm:hover { background: #2563eb; color: #fff; }
.btn-cancel-sm {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; background: #fff; color: #374151;
    border: 1.5px solid #e8eaf0; border-radius: 9px; font-size: 13.5px;
    font-weight: 600; cursor: pointer; transition: all .15s;
    font-family: inherit; text-decoration: none;
}
.btn-cancel-sm:hover { background: #f3f4f6; color: #111827; }

/* ── Variables modal ──────────────────────────────────────────────── */
.cb-vars-list { list-style: none; padding: 0; margin: 0; }
.cb-vars-list li {
    display: flex; align-items: center; gap: 8px;
    padding: 6px 0; border-bottom: 1px solid #f3f4f6;
}
.cb-vars-list li:last-child { border-bottom: none; }
.cb-vars-list .var-name { flex: 1; font-size: 13px; font-weight: 600; color: #1a1d23; }
.cb-vars-list .var-remove { color: #d1d5db; cursor: pointer; font-size: 14px; }
.cb-vars-list .var-remove:hover { color: #ef4444; }

/* ── Move buttons ─────────────────────────────────────────────────── */
.cb-node-move {
    width: 22px; height: 22px; border-radius: 5px;
    border: 1px solid #e8eaf0; background: transparent; color: #9ca3af;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 11px; flex-shrink: 0; transition: all .15s;
}
.cb-node-move:hover { background: #eff6ff; color: #3b82f6; border-color: #bfdbfe; }
</style>
@endpush

@section('content')
<div class="cb-page">

    {{-- Header --}}
    <div class="cb-header">
        <a href="{{ route('chatbot.flows.index') }}" class="cb-back" title="{{ __('chatbot.form_back') }}">
            <i class="bi bi-arrow-left"></i>
        </a>
        <input type="text" class="cb-name-input" id="cbName"
            placeholder="{{ __('chatbot.builder_name_placeholder') }}"
            value="{{ $flow->name }}">
        <div class="cb-header-right">
            <span class="cb-status-badge {{ $flow->is_active ? 'active' : '' }}" id="cbStatusBadge" onclick="toggleActive()">
                {{ $flow->is_active ? __('chatbot.builder_active') : __('chatbot.builder_inactive') }}
            </span>
            <button class="btn-cancel-sm" onclick="openTestWidget()">
                <i class="bi bi-play-circle"></i> {{ __('chatbot.builder_test') }}
            </button>
            @if($flow->channel === 'website' && $flow->website_token)
            <button class="btn-cancel-sm" onclick="showEmbedModal()">
                <i class="bi bi-code-slash"></i> {{ __('chatbot.builder_embed') }}
            </button>
            @endif
            <a href="{{ route('chatbot.flows.edit', ['flow' => $flow->id, 'settings' => 1]) }}" class="btn-cancel-sm">
                <i class="bi bi-gear"></i> {{ __('chatbot.builder_config') }}
            </a>
            <button class="btn-cancel-sm" onclick="zoomOut()" title="Zoom out">
                <i class="bi bi-dash-lg"></i>
            </button>
            <button class="btn-cancel-sm" onclick="zoomIn()" title="Zoom in">
                <i class="bi bi-plus-lg"></i>
            </button>
            <button class="btn-cancel-sm" onclick="zoomReset(); centerCanvas();" title="Centralizar canvas">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button class="btn-primary-sm" onclick="saveFlow()">
                <i class="bi bi-check2"></i> {{ __('chatbot.builder_save') }}
            </button>
        </div>
    </div>

    {{-- Builder --}}
    <div class="cb-builder">

        {{-- Sidebar --}}
        <div class="cb-sidebar">
            <div class="cb-sidebar-section">
                <div class="cb-sidebar-section-title">{{ __('chatbot.sidebar_blocks') }}</div>
                <div class="cb-block-item message" onclick="addStepToRoot('message')">
                    <span class="cb-block-icon"><i class="bi bi-chat-dots"></i></span>{{ __('chatbot.sidebar_message') }}
                </div>
                <div class="cb-block-item input" onclick="addStepToRoot('input')">
                    <span class="cb-block-icon"><i class="bi bi-input-cursor-text"></i></span>{{ __('chatbot.sidebar_input') }}
                </div>
                <div class="cb-block-item condition" onclick="addStepToRoot('condition')">
                    <span class="cb-block-icon"><i class="bi bi-question-diamond"></i></span>{{ __('chatbot.sidebar_condition') }}
                </div>
                <div class="cb-block-item action" onclick="addStepToRoot('action')">
                    <span class="cb-block-icon"><i class="bi bi-lightning"></i></span>{{ __('chatbot.sidebar_action') }}
                </div>
                <div class="cb-block-item delay" onclick="addStepToRoot('delay')">
                    <span class="cb-block-icon"><i class="bi bi-hourglass-split"></i></span>{{ __('chatbot.sidebar_delay') }}
                </div>
                <div class="cb-block-item end" onclick="addStepToRoot('end')">
                    <span class="cb-block-icon"><i class="bi bi-stop-circle"></i></span>{{ __('chatbot.sidebar_end') }}
                </div>
                <div class="cb-block-item cards" onclick="addStepToRoot('cards')">
                    <span class="cb-block-icon"><i class="bi bi-card-heading"></i></span>{{ __('chatbot.sidebar_cards') }}
                </div>
            </div>

            <div class="cb-sidebar-divider"></div>

            <div class="cb-sidebar-section">
                <div class="cb-sidebar-section-title">{{ __('chatbot.sidebar_config') }}</div>

                {{-- Trigger type (Instagram only) --}}
                @if($flow->channel === 'instagram')
                <div style="padding:10px 12px;">
                    <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Gatilho</div>
                    <select id="sidebarTriggerType" onchange="onSidebarTriggerTypeChange(this.value)"
                            style="width:100%;padding:7px 10px;border:1px solid #e5e7eb;border-radius:7px;font-size:12px;color:#1a1d23;background:#fff;cursor:pointer;">
                        <option value="keyword" {{ ($flow->trigger_type ?? 'keyword') === 'keyword' ? 'selected' : '' }}>Palavras-chave em DM</option>
                        <option value="instagram_comment" {{ ($flow->trigger_type ?? '') === 'instagram_comment' ? 'selected' : '' }}>Comentou em publicação</option>
                    </select>

                    {{-- Comment-specific settings --}}
                    <div id="sidebarCommentConfig" style="{{ ($flow->trigger_type ?? 'keyword') === 'instagram_comment' ? '' : 'display:none;' }}margin-top:10px;">
                        <div style="font-size:11px;font-weight:600;color:#374151;margin-bottom:4px;">Publicação</div>
                        <select id="sidebarMediaScope" onchange="onSidebarMediaScopeChange(this.value)"
                                style="width:100%;padding:6px 10px;border:1px solid #e5e7eb;border-radius:7px;font-size:11px;background:#fff;margin-bottom:8px;">
                            <option value="all" {{ empty($flow->trigger_media_id) ? 'selected' : '' }}>Qualquer publicação</option>
                            <option value="specific" {{ !empty($flow->trigger_media_id) ? 'selected' : '' }}>Post/Reel específico</option>
                        </select>
                        <div id="sidebarPostPicker" style="{{ !empty($flow->trigger_media_id) ? '' : 'display:none;' }}">
                            <div id="sidebarPostGrid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:4px;max-height:160px;overflow-y:auto;margin-bottom:6px;"></div>
                            <button type="button" onclick="loadSidebarPosts()" style="width:100%;padding:5px;background:#eff6ff;color:#0085f3;border:1px solid #bfdbfe;border-radius:6px;font-size:10px;font-weight:600;cursor:pointer;">
                                <i class="bi bi-arrow-clockwise"></i> Carregar posts
                            </button>
                        </div>

                        <div style="margin-top:8px;">
                            <div style="font-size:11px;font-weight:600;color:#374151;margin-bottom:4px;">Resposta no comentário</div>
                            <textarea id="sidebarReplyComment" rows="2" maxlength="2200"
                                      placeholder="Ex: Vou te mandar no privado!"
                                      style="width:100%;padding:6px 10px;border:1px solid #e5e7eb;border-radius:7px;font-size:11px;resize:vertical;box-sizing:border-box;">{{ $flow->trigger_reply_comment ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
                @endif

                <div class="cb-block-item" onclick="showVarsModal()" style="color:#374151">
                    <span class="cb-block-icon" style="background:#f3f4f6;color:#6b7280"><i class="bi bi-braces"></i></span>{{ __('chatbot.sidebar_variables') }}
                </div>
                <div style="padding:10px 12px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12.5px;color:#374151;font-weight:600;">
                        <input type="checkbox" id="catchAllToggle" {{ $flow->is_catch_all ? 'checked' : '' }}
                            onchange="toggleCatchAll(this.checked)"
                            style="width:16px;height:16px;accent-color:#0085f3;">
                        {{ __('chatbot.sidebar_catch_all') }}
                    </label>
                    <div style="font-size:11px;color:#9ca3af;margin-top:4px;line-height:1.4;">
                        {{ __('chatbot.sidebar_catch_all_hint') }}
                    </div>
                </div>
            </div>

            <div class="cb-sidebar-divider"></div>

            <div class="cb-sidebar-section">
                <div class="cb-sidebar-section-title">{{ __('chatbot.sidebar_templates') }}</div>
                <div class="cb-block-item" onclick="showTemplatesModal()" style="color:#15803d">
                    <span class="cb-block-icon" style="background:#f0fdf4;color:#15803d"><i class="bi bi-lightning"></i></span>{{ __('chatbot.sidebar_use_template') }}
                </div>
            </div>
        </div>

        {{-- Canvas --}}
        <div class="cb-canvas" onclick="if(event.target===this)closeEditPanel()">
            <div class="cb-flow" id="cbFlow">
                <!-- Rendered by JS -->
            </div>
        </div>

        {{-- Edit Panel (drawer lateral) --}}
        <div class="cb-edit-panel" id="cbEditPanel">
            <div class="cb-edit-panel-header">
                <div class="cb-node-icon" id="panelIcon"></div>
                <div>
                    <div class="cb-node-type" id="panelType"></div>
                    <div style="font-size:14px;font-weight:600;color:#1a1d23;" id="panelName"></div>
                </div>
                <button class="cb-edit-panel-close" onclick="closeEditPanel()"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="cb-edit-panel-body" id="panelBody"></div>
        </div>
    </div>
</div>

{{-- Variables Modal --}}
<div class="modal fade" id="varsModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:14px;border:1.5px solid #e8eaf0;">
            <div class="modal-header" style="border-bottom:1px solid #f0f2f7;padding:14px 18px;">
                <h6 style="font-size:14px;font-weight:700;color:#1a1d23;margin:0;">{{ __('chatbot.vars_modal_title') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size:11px;"></button>
            </div>
            <div class="modal-body" style="padding:14px 18px;">
                <ul class="cb-vars-list" id="varsList"></ul>
                <div style="display:flex;gap:6px;margin-top:10px;">
                    <input type="text" id="newVarName" class="form-control" placeholder="{{ __('chatbot.vars_placeholder') }}" style="border:1.5px solid #e8eaf0;border-radius:8px;padding:7px 11px;font-size:13px;">
                    <button class="btn-primary-sm" onclick="addVariable()" style="padding:7px 14px;font-size:12px;">+</button>
                </div>
                <div style="margin-top:10px;font-size:11px;color:#9ca3af;">
                    {{ __('chatbot.vars_system_label') }} <code>$contact_name</code>, <code>$contact_email</code>, <code>$contact_phone</code>, <code>$lead_exists</code>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Embed --}}
@if($flow->website_token)
<div id="embedModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:14px;padding:28px 32px;width:520px;max-width:94vw;box-shadow:0 20px 60px rgba(0,0,0,.18);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-size:16px;font-weight:700;color:#1a1d23;margin:0;">{{ __('chatbot.builder_embed_title') }}</h3>
            <button onclick="document.getElementById('embedModal').style.display='none'" style="background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;padding:4px;">&times;</button>
        </div>
        <p style="font-size:13.5px;color:#6b7280;margin:0 0 14px;">{!! __('chatbot.builder_embed_paste') !!}</p>
        <textarea id="embedCode" readonly rows="3" style="width:100%;border:1.5px solid #e8eaf0;border-radius:9px;padding:12px;font-family:monospace;font-size:12.5px;color:#374151;background:#f8fafc;resize:none;">{{ '<script src="' . config('app.url') . '/api/widget/' . $flow->website_token . '.js"></' . 'script>' }}</textarea>
        <div style="display:flex;align-items:center;gap:10px;margin-top:14px;">
            <button onclick="copyEmbed()" class="btn-primary-sm" style="padding:9px 20px;font-size:13px;">
                <i class="bi bi-clipboard"></i> {{ __('chatbot.builder_embed_copy') }}
            </button>
            <span id="embedCopied" style="font-size:12px;color:#16a34a;font-weight:600;display:none;"><i class="bi bi-check-circle"></i> {{ __('chatbot.builder_embed_copied') }}</span>
        </div>
    </div>
</div>
@endif

{{-- Templates Modal --}}
<div id="templatesModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:14px;padding:28px 32px;width:780px;max-width:94vw;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.18);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-shrink:0;">
            <h3 style="font-size:16px;font-weight:700;color:#1a1d23;margin:0;"><i class="bi bi-lightning" style="color:#0085f3;"></i> {{ __('chatbot.tpl_modal_title') }}</h3>
            <button onclick="document.getElementById('templatesModal').style.display='none'" style="background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;padding:4px;">&times;</button>
        </div>
        <input type="text" id="tplSearch" placeholder="{{ __('chatbot.tpl_search_placeholder') }}" style="width:100%;border:1.5px solid #e8eaf0;border-radius:9px;padding:10px 14px;font-size:13.5px;color:#374151;outline:none;margin-bottom:16px;flex-shrink:0;font-family:inherit;" oninput="filterTemplates(this.value)">
        <div id="tplTabsWrap" style="position:relative;margin-bottom:14px;flex-shrink:0;">
            <button id="tplTabLeft" onclick="document.getElementById('tplCategoryTabs').scrollBy({left:-160,behavior:'smooth'})" style="display:none;position:absolute;left:0;top:0;bottom:0;z-index:2;width:32px;border:none;cursor:pointer;background:linear-gradient(90deg,#fff 60%,transparent);color:#374151;font-size:14px;padding:0;align-items:center;justify-content:center;"><i class="bi bi-chevron-left"></i></button>
            <div id="tplCategoryTabs" style="display:flex;gap:6px;overflow-x:auto;padding:2px 0 4px;scrollbar-width:none;-ms-overflow-style:none;"></div>
            <button id="tplTabRight" onclick="document.getElementById('tplCategoryTabs').scrollBy({left:160,behavior:'smooth'})" style="display:none;position:absolute;right:0;top:0;bottom:0;z-index:2;width:32px;border:none;cursor:pointer;background:linear-gradient(270deg,#fff 60%,transparent);color:#374151;font-size:14px;padding:0;align-items:center;justify-content:center;"><i class="bi bi-chevron-right"></i></button>
        </div>
        <div id="templatesGrid" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;overflow-y:auto;flex:1;padding-right:4px;"></div>
        <div id="tplEmpty" style="display:none;text-align:center;padding:40px 0;color:#9ca3af;font-size:13.5px;">{{ __('chatbot.tpl_empty') }}</div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    const FLOW_ID   = {{ $flow->id }};
    const SAVE_URL  = "{{ route('chatbot.flows.graph', $flow) }}";
    const UPLOAD_URL = "{{ route('chatbot.flows.upload-image') }}";
    const TOGGLE_URL = "{{ route('chatbot.flows.toggle', $flow) }}";
    const CSRF      = "{{ csrf_token() }}";
    const PIPELINES = {!! json_encode($pipelinesJs) !!};
    const TAGS      = {!! json_encode($tags) !!};
    const USERS     = {!! json_encode($users) !!};
    const CUSTOM_FIELDS = {!! json_encode($customFieldDefs) !!};
    const FLOW_CHANNEL  = "{{ $flow->channel }}";
    const CBLANG = @json(__('chatbot'));

    let flowSteps     = {!! json_encode($flow->steps ?? []) !!} || [];
    let flowVariables = {!! json_encode($flow->variables ?? []) !!} || [];

    // Trigger type for Instagram comment flows
    let _currentTriggerType = @json($flow->trigger_type ?? 'keyword');
    let _triggerMediaId     = @json($flow->trigger_media_id ?? '');
    let _triggerMediaThumb  = @json($flow->trigger_media_thumbnail ?? '');
    let _triggerMediaCaption = @json($flow->trigger_media_caption ?? '');
    let _triggerReplyComment = @json($flow->trigger_reply_comment ?? '');

    const NODE_TYPES = {
        message:   { icon: 'bi-chat-dots',          label: CBLANG.node_message,   color: 'message' },
        input:     { icon: 'bi-input-cursor-text',   label: CBLANG.node_input,     color: 'input' },
        condition: { icon: 'bi-question-diamond',    label: CBLANG.node_condition,  color: 'condition' },
        action:    { icon: 'bi-lightning',            label: CBLANG.node_action,     color: 'action' },
        delay:     { icon: 'bi-hourglass-split',      label: CBLANG.node_delay,      color: 'delay' },
        end:       { icon: 'bi-stop-circle',          label: CBLANG.node_end,        color: 'end' },
        cards:     { icon: 'bi-card-heading',         label: CBLANG.node_cards,      color: 'cards' },
    };

    let idCounter = 1;
    function genId() { return 's' + Date.now().toString(36) + (idCounter++); }

    // ── Resolve steps at a path ──────────────────────────────────────
    function resolveParentSteps(path) {
        if (path.length === 0) return flowSteps;
        let current = flowSteps;
        for (let i = 0; i < path.length; i++) {
            const key = path[i];
            if (current[key] === undefined || current[key] === null) {
                current[key] = [];
            }
            current = current[key];
        }
        return current;
    }

    // ── Default configs ──────────────────────────────────────────────
    function defaultConfig(type) {
        switch (type) {
            case 'message':   return { text: '', image_url: null };
            case 'input':     return { text: '', save_to: '', field_type: 'text', show_buttons: false };
            case 'condition': return { variable: '', conditions: [] };
            case 'action':    return { type: 'create_lead' };
            case 'delay':     return { seconds: 3 };
            case 'end':       return { text: '' };
            case 'cards':     return { items: [] };
            default:          return {};
        }
    }

    function createStep(type) {
        const step = { id: genId(), type: type, config: defaultConfig(type) };
        if (type === 'input' || type === 'condition') {
            step.branches = [];
            step.default_branch = { steps: [] };
        }
        return step;
    }

    // ── Add / Remove / Move ──────────────────────────────────────────
    window.addStepToRoot = function(type) {
        flowSteps.push(createStep(type));
        renderFlow();
    };

    function removeStep(path, index) {
        const arr = resolveParentSteps(path);
        arr.splice(index, 1);
        renderFlow();
    }

    function moveStep(path, fromIdx, direction) {
        const arr = resolveParentSteps(path);
        const toIdx = fromIdx + direction;
        if (toIdx < 0 || toIdx >= arr.length) return;
        [arr[fromIdx], arr[toIdx]] = [arr[toIdx], arr[fromIdx]];
        renderFlow();
    }

    // ── Branch management ────────────────────────────────────────────
    function addBranch(path, stepIndex) {
        const step = resolveParentSteps(path)[stepIndex];
        if (!step.branches) step.branches = [];
        const bId = 'b' + Date.now().toString(36) + (idCounter++);
        const branch = {
            id: bId,
            label: CBLANG.branch_option.replace(':number', step.branches.length + 1),
            keywords: [],
            steps: []
        };
        if (step.type === 'condition') {
            branch.operator = 'equals';
            branch.value = '';
        }
        step.branches.push(branch);
        if (!step.default_branch) step.default_branch = { steps: [] };
        renderFlow();
    }

    function removeBranch(path, stepIndex, branchIndex) {
        const step = resolveParentSteps(path)[stepIndex];
        step.branches.splice(branchIndex, 1);
        renderFlow();
    }

    // ── Escape HTML ──────────────────────────────────────────────────
    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    // ── Render Flow ──────────────────────────────────────────────────
    function renderFlow() {
        const container = document.getElementById('cbFlow');
        container.innerHTML = '';

        // Start node
        const _triggerType = (typeof _currentTriggerType !== 'undefined') ? _currentTriggerType : 'keyword';
        const _startDesc = _triggerType === 'instagram_comment'
            ? 'Quando comentam em publicação'
            : CBLANG.node_start_desc;
        const _startIcon = _triggerType === 'instagram_comment' ? 'bi-chat-left-heart' : 'bi-play-fill';

        container.innerHTML += `
            <div class="cb-node start" data-step-id="_start" style="align-self:center;">
                <div class="cb-node-bar"></div>
                <div class="cb-node-head">
                    <div class="cb-node-icon"><i class="bi ${_startIcon}"></i></div>
                    <div class="cb-node-label">
                        <div class="cb-node-type">${CBLANG.node_start}</div>
                        <div class="cb-node-name">${_startDesc}</div>
                    </div>
                </div>
            </div>`;

        if (flowSteps.length > 0) {
            container.innerHTML += '<div class="cb-connector"></div>';
        }

        // Render root steps
        renderStepListInto(flowSteps, container, []);

        // Add step button at the end
        const addBtnWrap = document.createElement('div');
        addBtnWrap.style.cssText = 'margin-top:8px;width:420px;align-self:center;';
        addBtnWrap.innerHTML = `
            <button class="cb-add-step" onclick="showAddMenu(this, [])">
                <i class="bi bi-plus-lg"></i> ${CBLANG.add_block}
            </button>`;
        container.appendChild(addBtnWrap);
    }

    function renderStepListInto(steps, container, path) {
        steps.forEach((step, i) => {
            const stepEl = document.createElement('div');
            stepEl.style.cssText = 'display:flex;flex-direction:column;align-items:center;width:100%;';
            stepEl.innerHTML = renderStep(step, path, i, steps.length);
            container.appendChild(stepEl);

            // Branches (tree layout) — for input nodes, only show when branches exist
            if ((step.type === 'condition' || (step.type === 'input' && step.branches && step.branches.length > 0)) && (step.branches || step.default_branch)) {
                const branchEl = document.createElement('div');
                branchEl.style.cssText = 'display:flex;flex-direction:column;align-items:center;width:100%;';
                branchEl.innerHTML = renderBranches(step, path, i);
                container.appendChild(branchEl);
            }

            if (i < steps.length - 1) {
                const conn = document.createElement('div');
                conn.className = 'cb-connector';
                container.appendChild(conn);
            }
        });
    }

    var _selectedPath = null;
    var _selectedIndex = null;

    function renderStep(step, path, index, totalSteps) {
        const info = NODE_TYPES[step.type] || { icon: 'bi-circle', label: step.type, color: '' };
        const pathStr = JSON.stringify(path).replace(/"/g, '&quot;');
        const isSelected = _selectedPath && JSON.stringify(_selectedPath) === JSON.stringify(path) && _selectedIndex === index;
        let html = '';

        html += `<div class="cb-node ${step.type}${isSelected ? ' selected' : ''}" data-step-id="${step.id}" onclick="openEditPanel(${pathStr}, ${index})">`;
        html += `<div class="cb-node-bar"></div>`;

        // Head (compact — no move buttons, just icon+label+delete)
        html += `<div class="cb-node-head">`;
        html += `<div class="cb-node-icon"><i class="bi ${info.icon}"></i></div>`;
        html += `<div class="cb-node-label">`;
        html += `<div class="cb-node-type">${esc(info.label)}</div>`;
        html += `<div class="cb-node-name">${esc(getStepSummary(step))}</div>`;
        html += `</div>`;
        html += `<button class="cb-node-remove" onclick="event.stopPropagation();cbRemoveStep(${pathStr}, ${index})" title="${CBLANG.panel_remove}"><i class="bi bi-x-lg"></i></button>`;
        html += `</div>`;

        // Preview (compact — full form is in the drawer)
        html += getStepPreview(step);

        html += `</div>`;
        return html;
    }

    function getStepSummary(step) {
        const c = step.config || {};
        switch (step.type) {
            case 'message': return truncate(c.text, 40) || CBLANG.summary_empty_message;
            case 'input':   return truncate(c.text, 40) || CBLANG.summary_empty_question;
            case 'condition': return c.variable ? CBLANG.summary_condition_if.replace(':variable', c.variable) : CBLANG.summary_empty_condition;
            case 'action':  return getActionLabel(c);
            case 'delay':   return CBLANG.summary_seconds.replace(':count', c.seconds || 3);
            case 'end':     return truncate(c.text, 40) || CBLANG.summary_finalize;
            case 'cards':   return CBLANG.summary_cards_count.replace(':count', (c.items || []).length);
            default:        return step.type;
        }
    }

    function truncate(str, len) {
        if (!str) return '';
        return str.length > len ? str.substring(0, len) + '...' : str;
    }

    function getActionLabel(c) {
        switch (c.type || '') {
            case 'create_lead':        return CBLANG.action_create_lead;
            case 'change_stage':       return CBLANG.action_change_stage;
            case 'add_tag':            return CBLANG.action_add_tag + ': ' + (c.value || '');
            case 'remove_tag':         return CBLANG.action_remove_tag + ': ' + (c.value || '');
            case 'save_variable':      return CBLANG.action_save_variable + ': ' + (c.variable || '');
            case 'close_conversation': return CBLANG.action_close_conversation;
            case 'assign_human':       return CBLANG.action_assign_human;
            case 'send_webhook':       return 'Webhook: ' + (c.url || '');
            case 'set_custom_field':   return CBLANG.action_field + ': ' + (c.field_label || c.field_name || '');
            case 'send_whatsapp':      return 'WhatsApp: ' + truncate(c.message || '', 30);
            case 'create_task':        return CBLANG.action_task_subject.split(' ')[0] + ': ' + truncate(c.subject || '', 30);
            case 'redirect':           return CBLANG.action_redirect + ': ' + truncate(c.url || '', 30);
            default:                   return c.type || CBLANG.node_action;
        }
    }

    // ── Step Preview (compact card) ──────────────────────────────────
    function getStepPreview(step) {
        const c = step.config || {};
        let html = '<div class="cb-node-preview">';
        switch (step.type) {
            case 'message':
                if (c.image_url) html += '<div class="preview-badge"><i class="bi bi-image"></i> ' + CBLANG.preview_image + '</div>';
                if (c.text) html += '<div>' + esc(truncate(c.text, 80)) + '</div>';
                break;
            case 'input':
                if (c.text) html += '<div>' + esc(truncate(c.text, 80)) + '</div>';
                if (c.save_to) html += '<div class="preview-badge"><i class="bi bi-floppy"></i> ' + esc(c.save_to) + '</div>';
                var branches = step.branches || c.branches || [];
                if (branches.length) {
                    html += '<div class="branch-pills">';
                    branches.forEach(function(b) { html += '<span class="branch-pill">' + esc(b.label || CBLANG.preview_option) + '</span>'; });
                    html += '</div>';
                }
                break;
            case 'condition':
                if (c.variable) html += '<div class="preview-badge"><i class="bi bi-code-slash"></i> ' + esc(c.variable) + '</div>';
                break;
            case 'action':
                html += '<div>' + esc(getActionLabel(c)) + '</div>';
                break;
            case 'delay':
                html += '<div><i class="bi bi-hourglass-split" style="color:#ec4899;margin-right:4px;"></i>' + CBLANG.summary_seconds.replace(':count', c.seconds || 3) + '</div>';
                break;
            case 'end':
                if (c.text) html += '<div>' + esc(truncate(c.text, 80)) + '</div>';
                else html += '<div style="color:#9ca3af;">' + CBLANG.summary_finalize_conversation + '</div>';
                break;
            case 'cards':
                html += '<div class="preview-badge"><i class="bi bi-card-heading"></i> ' + ((c.items || []).length) + ' card(s)</div>';
                break;
        }
        html += '</div>';
        return html;
    }

    // ── Edit Panel Functions ──────────────────────────────────────────
    function getStepByPath(path, index) {
        var list = flowSteps;
        for (var i = 0; i < path.length; i++) { list = list[path[i]]; if (!list) return null; }
        return list[index] || null;
    }

    window.openEditPanel = function(path, index) {
        _selectedPath = path;
        _selectedIndex = index;
        var step = getStepByPath(path, index);
        if (!step) return;
        var info = NODE_TYPES[step.type] || { icon: 'bi-circle', label: step.type };
        var panel = document.getElementById('cbEditPanel');
        var panelBody = document.getElementById('panelBody');

        document.getElementById('panelIcon').innerHTML = '<i class="bi ' + info.icon + '"></i>';
        document.getElementById('panelType').textContent = info.label.toUpperCase();
        document.getElementById('panelName').textContent = getStepSummary(step);

        var pathStr = JSON.stringify(path).replace(/"/g, '&quot;');

        // Move/delete buttons
        var moveHtml = '<div style="display:flex;gap:6px;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #f0f2f7;">';
        moveHtml += '<button class="btn-cancel-sm" onclick="cbMoveStep(' + pathStr + ',' + index + ',-1);openEditPanel(' + pathStr + ',' + Math.max(0, index - 1) + ')"><i class="bi bi-chevron-up"></i> ' + CBLANG.panel_move_up + '</button>';
        moveHtml += '<button class="btn-cancel-sm" onclick="cbMoveStep(' + pathStr + ',' + index + ',1);openEditPanel(' + pathStr + ',' + (index + 1) + ')"><i class="bi bi-chevron-down"></i> ' + CBLANG.panel_move_down + '</button>';
        moveHtml += '<button class="btn-cancel-sm" style="margin-left:auto;color:#ef4444;" onclick="cbRemoveStep(' + pathStr + ',' + index + ');closeEditPanel()"><i class="bi bi-trash3"></i></button>';
        moveHtml += '</div>';

        // Full form
        var formHtml = renderStepBody(step, path, index);

        // Buttons section for input/condition
        var btnHtml = '';
        if ((step.type === 'input' || step.type === 'condition') && step.branches) {
            btnHtml += '<div style="margin-top:16px;padding-top:16px;border-top:1px solid #f0f2f7;">';
            btnHtml += '<label style="font-size:12px;font-weight:700;color:#374151;margin-bottom:8px;">' + CBLANG.panel_buttons + '</label>';
            step.branches.forEach(function(b, bi) {
                btnHtml += '<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">';
                btnHtml += '<div style="flex:1;background:#0085f3;color:#fff;padding:8px 14px;border-radius:8px;display:flex;align-items:center;justify-content:center;">';
                btnHtml += '<input maxlength="24" value="' + esc(b.label || '') + '" onchange="cbUpdateBranch(' + pathStr + ',' + index + ',' + bi + ',\'label\',this.value)" style="background:transparent;border:none;color:#fff;text-align:center;width:100%;font-size:13px;font-weight:600;outline:none;font-family:inherit;" placeholder="' + CBLANG.panel_button_placeholder + '">';
                btnHtml += '</div>';
                btnHtml += '<button onclick="cbRemoveBranch(' + pathStr + ',' + index + ',' + bi + ');openEditPanel(' + pathStr + ',' + index + ')" style="border:none;background:none;color:#d1d5db;cursor:pointer;font-size:14px;padding:4px;" title="' + CBLANG.panel_remove + '"><i class="bi bi-trash3"></i></button>';
                btnHtml += '</div>';
            });
            btnHtml += '<button onclick="cbAddBranch(' + pathStr + ',' + index + ');openEditPanel(' + pathStr + ',' + index + ')" style="width:100%;padding:8px;border:1.5px dashed #d1d5db;border-radius:8px;background:transparent;color:#6b7280;font-size:12px;font-weight:600;cursor:pointer;margin-top:4px;" onmouseover="this.style.borderColor=\'#0085f3\';this.style.color=\'#0085f3\'" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\'"><i class="bi bi-plus-lg" style="margin-right:4px;"></i> ' + CBLANG.panel_add_button + '</button>';
            btnHtml += '</div>';
        }

        panelBody.innerHTML = moveHtml + formHtml + btnHtml;
        panel.classList.add('open');

        document.querySelectorAll('.cb-node.selected').forEach(function(n) { n.classList.remove('selected'); });
        var nodeEl = document.querySelector('.cb-node[data-step-id="' + step.id + '"]');
        if (nodeEl) nodeEl.classList.add('selected');

        requestAnimationFrame(function() { bindEditables(); });
    };

    window.closeEditPanel = function() {
        document.getElementById('cbEditPanel').classList.remove('open');
        document.querySelectorAll('.cb-node.selected').forEach(function(n) { n.classList.remove('selected'); });
        _selectedPath = null;
        _selectedIndex = null;
        _origRenderFlow();
        postRenderBranches();
        bindEditables();
        requestAnimationFrame(drawConnections);
    };

    // ── Step Body (inline form — now used by drawer) ──────────────────
    function renderStepBody(step, path, index) {
        const pathStr = JSON.stringify(path).replace(/"/g, '&quot;');
        const c = step.config || {};
        let html = '<div class="cb-node-body">';

        switch (step.type) {
            case 'message':
                html += '<label>' + CBLANG.msg_text_label + '</label>';
                html += '<div class="cb-editable" contenteditable="true" id="msg-' + step.id + '" data-path="' + pathStr + '" data-index="' + index + '" data-field="text" data-placeholder="' + CBLANG.msg_text_placeholder + '">' + textToHtml(c.text || '') + '</div>';
                html += renderVarHint('msg-' + step.id);
                html += renderImageArea(step, path, index);
                break;

            case 'input':
                html += '<label>' + CBLANG.input_question_label + '</label>';
                html += '<div class="cb-editable" contenteditable="true" id="inp-' + step.id + '" data-path="' + pathStr + '" data-index="' + index + '" data-field="text" data-placeholder="' + CBLANG.input_question_placeholder + '">' + textToHtml(c.text || '') + '</div>';
                html += renderVarHint('inp-' + step.id);
                var hideSave = c.field_type === 'buttons' ? 'display:none' : '';
                var stepUid  = step.id;
                html += '<div class="row-pair" style="margin-top:8px;">';
                html += '<div><label>' + CBLANG.input_field_type + '</label>';
                html += '<select class="form-select" onchange="var v=this.value; cbUpdateConfig(' + pathStr + ', ' + index + ', \'field_type\', v); if(v===\'buttons\'){ cbToggleButtons(' + pathStr + ', ' + index + ', true); } else { cbToggleButtons(' + pathStr + ', ' + index + ', false); } var h=v===\'buttons\'; document.getElementById(\'save-wrap-' + stepUid + '\').style.display=h?\'none\':\'\'; document.getElementById(\'chk-wrap-' + stepUid + '\').style.display=h?\'none\':\'\';">';
                var fieldTypes = [
                    { value: 'text',    label: CBLANG.field_type_text },
                    { value: 'name',    label: CBLANG.field_type_name },
                    { value: 'email',   label: CBLANG.field_type_email },
                    { value: 'phone',   label: CBLANG.field_type_phone },
                    { value: 'number',  label: CBLANG.field_type_number },
                    { value: 'buttons', label: CBLANG.field_type_buttons },
                ];
                fieldTypes.forEach(function(t) {
                    html += '<option value="' + t.value + '" ' + (c.field_type === t.value ? 'selected' : '') + '>' + t.label + '</option>';
                });
                html += '</select></div>';
                html += '<div id="save-wrap-' + stepUid + '" style="' + hideSave + '"><label>' + CBLANG.input_save_to + '</label>';
                html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'save_to\', this.value)">';
                html += '<option value="">' + CBLANG.input_save_none + '</option>';
                flowVariables.forEach(function(v) {
                    var name = v.name || v;
                    html += '<option value="' + esc(name) + '" ' + (c.save_to === name ? 'selected' : '') + '>' + esc(name) + '</option>';
                });
                html += '</select></div>';
                html += '</div>';
                html += '<div id="chk-wrap-' + stepUid + '" style="' + hideSave + '">';
                html += '<label class="cb-checkbox"><input type="checkbox" ' + (c.show_buttons ? 'checked' : '') + ' onchange="cbToggleButtons(' + pathStr + ', ' + index + ', this.checked)"> ' + CBLANG.input_show_buttons + '</label>';
                html += '</div>';
                break;

            case 'condition':
                html += '<label>' + CBLANG.condition_variable_label + '</label>';
                html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'variable\', this.value)">';
                html += '<option value="">' + CBLANG.condition_select + '</option>';
                ['$contact_name', '$contact_email', '$contact_phone', '$lead_exists'].forEach(function(v) {
                    html += '<option value="' + v + '" ' + (c.variable === v ? 'selected' : '') + '>' + v + '</option>';
                });
                flowVariables.forEach(function(v) {
                    var name = v.name || v;
                    html += '<option value="' + esc(name) + '" ' + (c.variable === name ? 'selected' : '') + '>' + esc(name) + '</option>';
                });
                html += '</select>';
                html += '<div style="margin-top:8px;padding:8px 10px;background:#fef9c3;border-radius:6px;font-size:11px;color:#92400e;line-height:1.4;">';
                html += '<i class="bi bi-info-circle" style="margin-right:4px;"></i> ' + CBLANG.condition_hint.replace(':variable', esc(c.variable || CBLANG.action_variable));
                html += '</div>';
                break;

            case 'action':
                html += renderActionBody(step, path, index);
                break;

            case 'delay':
                html += '<label>' + CBLANG.delay_seconds_label + '</label>';
                html += '<input type="number" class="form-control" min="1" max="300" value="' + (c.seconds || 3) + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'seconds\', parseInt(this.value))">';
                break;

            case 'end':
                html += '<label>' + CBLANG.end_message_label + '</label>';
                html += '<div class="cb-editable" contenteditable="true" id="end-' + step.id + '" data-path="' + pathStr + '" data-index="' + index + '" data-field="text" data-placeholder="' + CBLANG.end_message_placeholder + '">' + textToHtml(c.text || '') + '</div>';
                html += renderVarHint('end-' + step.id);
                break;

            case 'cards':
                (c.items || []).forEach(function(item, i) {
                    var ba = item.button_action || 'reply';
                    html += '<div style="border:1px solid #e8eaf0;border-radius:8px;padding:10px;margin-bottom:8px;background:#fafafa;">';
                    html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">';
                    html += '<span style="font-size:11px;font-weight:700;color:#6b7280;">CARD ' + (i + 1) + '</span>';
                    html += '<button onclick="cbRemoveCard(' + pathStr + ',' + index + ',' + i + ')" style="border:none;background:none;cursor:pointer;color:#ef4444;font-size:12px;padding:0 4px;">' + CBLANG.card_remove + '</button>';
                    html += '</div>';
                    html += '<input class="form-control" placeholder="' + CBLANG.card_title_placeholder + '" value="' + esc(item.title || '') + '" onchange="cbUpdateCardItem(' + pathStr + ',' + index + ',' + i + ',\'title\',this.value)" style="margin-bottom:4px;">';
                    html += '<textarea class="form-control" placeholder="' + CBLANG.card_description_placeholder + '" onchange="cbUpdateCardItem(' + pathStr + ',' + index + ',' + i + ',\'description\',this.value)" style="margin-bottom:4px;min-height:38px;">' + esc(item.description || '') + '</textarea>';
                    if (item.image_url) {
                        html += '<div style="position:relative;margin-bottom:4px;">';
                        html += '<img src="' + esc(item.image_url) + '" style="width:100%;max-height:120px;object-fit:cover;border-radius:6px;border:1px solid #e8eaf0;">';
                        html += '<button onclick="cbRemoveCardImage(' + pathStr + ',' + index + ',' + i + ')" style="position:absolute;top:4px;right:4px;width:22px;height:22px;border-radius:50%;border:none;background:rgba(0,0,0,.55);color:#fff;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;" title="' + CBLANG.panel_remove + '">&times;</button>';
                        html += '</div>';
                    } else {
                        html += '<button onclick="cbUploadCardImage(' + pathStr + ',' + index + ',' + i + ')" style="width:100%;padding:10px;margin-bottom:4px;border:1.5px dashed #d1d5db;border-radius:6px;background:#f9fafb;color:#6b7280;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:border-color .15s;" onmouseover="this.style.borderColor=\'#0085f3\'" onmouseout="this.style.borderColor=\'#d1d5db\'"><i class="bi bi-image" style="font-size:14px;"></i> ' + CBLANG.msg_upload_image + '</button>';
                    }
                    html += '<div style="display:flex;gap:6px;margin-top:2px;">';
                    html += '<input class="form-control" placeholder="' + CBLANG.card_button_placeholder + '" value="' + esc(item.button_label || '') + '" onchange="cbUpdateCardItem(' + pathStr + ',' + index + ',' + i + ',\'button_label\',this.value)">';
                    html += '<select class="form-select" style="max-width:140px;" onchange="cbUpdateCardItem(' + pathStr + ',' + index + ',' + i + ',\'button_action\',this.value);renderFlow();">';
                    html += '<option value="reply"' + (ba === 'reply' ? ' selected' : '') + '>' + CBLANG.card_button_action_reply + '</option>';
                    html += '<option value="url"' + (ba === 'url' ? ' selected' : '') + '>' + CBLANG.card_button_action_url + '</option>';
                    html += '</select>';
                    html += '<input class="form-control" placeholder="' + (ba === 'url' ? CBLANG.card_url_placeholder : CBLANG.card_value_placeholder) + '" value="' + esc(ba === 'url' ? (item.button_url || '') : (item.button_value || '')) + '" onchange="cbUpdateCardItem(' + pathStr + ',' + index + ',' + i + ',\'' + (ba === 'url' ? 'button_url' : 'button_value') + '\',this.value)">';
                    html += '</div>';
                    html += '</div>';
                });
                html += '<button style="width:100%;background:#eff6ff;color:#0085f3;border:1.5px solid #bfdbfe;border-radius:8px;font-size:12px;font-weight:600;padding:7px;cursor:pointer;margin-top:2px;" onclick="cbAddCard(' + pathStr + ',' + index + ')">';
                html += '<i class="bi bi-plus-lg"></i> ' + CBLANG.card_add + '</button>';
                break;
        }

        html += '</div>';
        return html;
    }

    // Blade-safe braces (double curly braces)
    var _LB = String.fromCharCode(123,123);
    var _RB = String.fromCharCode(125,125);

    // ── Contenteditable helpers ────────────────────────────────────
    // Convert plain text with variable placeholders to HTML with chip spans
    function textToHtml(text) {
        if (!text) return '';
        // Regex to match double-curly-brace vars — built dynamically to avoid Blade
        var re = new RegExp(_LB + '([^}]+)' + _RB, 'g');
        var result = '';
        var lastIdx = 0;
        var match;
        while ((match = re.exec(text)) !== null) {
            result += esc(text.substring(lastIdx, match.index));
            result += '<span class="cb-var-chip" contenteditable="false" data-var="' + esc(match[1]) + '">' + esc(match[1]) + '</span>';
            lastIdx = re.lastIndex;
        }
        result += esc(text.substring(lastIdx));
        // Convert newlines to <br>
        result = result.replace(/\n/g, '<br>');
        return result;
    }

    // Extract plain text with variable placeholders from contenteditable element
    function htmlToText(el) {
        var result = '';
        el.childNodes.forEach(function(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                result += node.textContent;
            } else if (node.nodeName === 'BR') {
                result += '\n';
            } else if (node.classList && node.classList.contains('cb-var-chip')) {
                result += _LB + (node.getAttribute('data-var') || node.textContent) + _RB;
            } else if (node.nodeName === 'DIV' || node.nodeName === 'P') {
                // Browsers sometimes wrap lines in divs
                if (result.length > 0 && result[result.length - 1] !== '\n') result += '\n';
                result += htmlToText(node);
            } else {
                result += node.textContent || '';
            }
        });
        return result;
    }

    function renderVarHint(editableId) {
        var allVars = ['$contact_name', '$contact_email', '$contact_phone'];
        flowVariables.forEach(function(v) {
            allVars.push(v.name || v);
        });
        if (allVars.length === 0) return '';
        var html = '<div class="cb-var-hint">';
        html += '<span class="cb-var-hint-label"><i class="bi bi-braces"></i> ' + CBLANG.vars_hint_label + '</span> ';
        allVars.forEach(function(name) {
            html += '<button type="button" class="cb-var-tag" onclick="cbInsertVar(\'' + esc(editableId) + '\', \'' + esc(name) + '\')" title="' + CBLANG.vars_insert_title + ' ' + _LB + esc(name) + _RB + '">' + _LB + esc(name) + _RB + '</button> ';
        });
        html += '</div>';
        return html;
    }

    // Insert a variable chip into a contenteditable at cursor position
    window.cbInsertVar = function(editableId, varName) {
        var el = document.getElementById(editableId);
        if (!el) return;
        el.focus();
        var chip = document.createElement('span');
        chip.className = 'cb-var-chip';
        chip.contentEditable = 'false';
        chip.setAttribute('data-var', varName);
        chip.textContent = varName;

        var sel = window.getSelection();
        if (sel.rangeCount > 0) {
            var range = sel.getRangeAt(0);
            // Ensure we're inside the editable
            if (el.contains(range.commonAncestorContainer)) {
                range.deleteContents();
                range.insertNode(chip);
                // Move cursor after chip
                range.setStartAfter(chip);
                range.setEndAfter(chip);
                sel.removeAllRanges();
                sel.addRange(range);
            } else {
                el.appendChild(chip);
            }
        } else {
            el.appendChild(chip);
        }
        // Trigger sync
        el.dispatchEvent(new Event('input', { bubbles: true }));
    };

    function renderImageArea(step, path, index) {
        const pathStr = JSON.stringify(path).replace(/"/g, '&quot;');
        const c = step.config || {};
        let html = '<div class="cb-image-area" onclick="cbUploadImage(' + pathStr + ', ' + index + ', this)">';
        if (c.image_url) {
            html += '<img src="' + esc(c.image_url) + '" alt="' + CBLANG.preview_image + '">';
            html += '<div style="margin-top:6px;font-size:11px;color:#6b7280;">' + CBLANG.msg_click_to_change_image + '</div>';
        } else {
            html += '<i class="bi bi-image" style="font-size:20px;display:block;margin-bottom:4px;"></i>';
            html += CBLANG.msg_click_to_add_image;
        }
        html += '</div>';
        return html;
    }

    function renderActionBody(step, path, index) {
        const pathStr = JSON.stringify(path).replace(/"/g, '&quot;');
        const c = step.config || {};
        let html = '';

        html += '<label>' + CBLANG.action_type_label + '</label>';
        html += '<select class="form-select" onchange="cbUpdateActionType(' + pathStr + ', ' + index + ', this.value)">';
        var actionTypes = [
            ['create_lead', CBLANG.action_create_lead],
            ['change_stage', CBLANG.action_change_stage],
            ['add_tag', CBLANG.action_add_tag],
            ['remove_tag', CBLANG.action_remove_tag],
            ['save_variable', CBLANG.action_save_variable],
            ['close_conversation', CBLANG.action_close_conversation],
            ['assign_human', CBLANG.action_assign_human],
            ['send_webhook', CBLANG.action_send_webhook],
            ['set_custom_field', CBLANG.action_set_custom_field],
            ['send_whatsapp', CBLANG.action_send_whatsapp],
            ['create_task', CBLANG.action_create_task],
            ['redirect', CBLANG.action_redirect],
        ];
        actionTypes.forEach(function(at) {
            if (at[0] === 'redirect' && FLOW_CHANNEL !== 'website') return;
            html += '<option value="' + at[0] + '" ' + (c.type === at[0] ? 'selected' : '') + '>' + at[1] + '</option>';
        });
        html += '</select>';

        switch (c.type) {
            case 'create_lead':
                var varOpts = function(field) {
                    var h = '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'' + field + '\', this.value)">';
                    h += '<option value="">' + CBLANG.action_select_variable + '</option>';
                    ['$contact_name', '$contact_email', '$contact_phone'].forEach(function(v) {
                        h += '<option value="' + v + '" ' + (c[field] === v ? 'selected' : '') + '>' + v + '</option>';
                    });
                    flowVariables.forEach(function(fv) {
                        var name = fv.name || fv;
                        h += '<option value="' + esc(name) + '" ' + (c[field] === name ? 'selected' : '') + '>' + esc(name) + '</option>';
                    });
                    h += '</select>';
                    return h;
                };
                html += '<div class="row-pair" style="margin-top:8px;">';
                html += '<div><label>' + CBLANG.action_name + '</label>' + varOpts('name_var') + '</div>';
                html += '<div><label>' + CBLANG.action_email + '</label>' + varOpts('email_var') + '</div>';
                html += '</div>';
                html += '<div class="row-pair" style="margin-top:8px;">';
                html += '<div><label>' + CBLANG.action_phone + '</label>' + varOpts('phone_var') + '</div>';
                html += '<div><label>' + CBLANG.action_stage + '</label><select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'stage_id\', parseInt(this.value))">';
                html += '<option value="">' + CBLANG.condition_select + '</option>';
                PIPELINES.forEach(function(p) {
                    p.stages.forEach(function(s) {
                        html += '<option value="' + s.id + '" ' + (c.stage_id == s.id ? 'selected' : '') + '>' + esc(p.name) + ' \u2192 ' + esc(s.name) + '</option>';
                    });
                });
                html += '</select></div></div>';
                break;
            case 'change_stage':
                html += '<label style="margin-top:8px;">' + CBLANG.action_target_stage + '</label>';
                html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'stage_id\', parseInt(this.value))">';
                html += '<option value="">' + CBLANG.condition_select + '</option>';
                PIPELINES.forEach(function(p) {
                    p.stages.forEach(function(s) {
                        html += '<option value="' + s.id + '" ' + (c.stage_id == s.id ? 'selected' : '') + '>' + esc(p.name) + ' → ' + esc(s.name) + '</option>';
                    });
                });
                html += '</select>';
                break;
            case 'add_tag':
            case 'remove_tag':
                html += '<label style="margin-top:8px;">' + CBLANG.action_tag + '</label>';
                html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'value\', this.value)">';
                html += '<option value="">' + CBLANG.condition_select + '</option>';
                TAGS.forEach(function(t) {
                    html += '<option value="' + esc(t) + '" ' + (c.value === t ? 'selected' : '') + '>' + esc(t) + '</option>';
                });
                html += '</select>';
                break;
            case 'save_variable':
                html += '<div class="row-pair" style="margin-top:8px;">';
                html += '<div><label>' + CBLANG.action_variable + '</label><input class="form-control" value="' + esc(c.variable || '') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'variable\', this.value)"></div>';
                html += '<div><label>' + CBLANG.action_value + '</label><input class="form-control" value="' + esc(c.value || '') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'value\', this.value)"></div>';
                html += '</div>';
                break;
            case 'send_webhook':
                html += '<div class="row-pair" style="margin-top:8px;">';
                html += '<div style="flex:0 0 120px;"><label>' + CBLANG.action_method + '</label><select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'http_method\', this.value)">';
                ['POST', 'GET', 'PUT', 'DELETE', 'PATCH'].forEach(function(m) {
                    html += '<option value="' + m + '" ' + ((c.http_method || 'POST') === m ? 'selected' : '') + '>' + m + '</option>';
                });
                html += '</select></div>';
                html += '<div><label>' + CBLANG.action_url + '</label><input class="form-control" value="' + esc(c.url || '') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'url\', this.value)" placeholder="https://..."></div>';
                html += '</div>';
                if ((c.http_method || 'POST') !== 'GET') {
                    html += '<label style="margin-top:8px;">' + CBLANG.action_json_body + '</label>';
                    html += '<div class="cb-editable" contenteditable="true" id="wh-' + step.id + '" data-path="' + pathStr + '" data-index="' + index + '" data-field="json_body" data-placeholder=\'{"nome": "valor"}\'>' + textToHtml(c.json_body || '') + '</div>';
                    html += renderVarHint('wh-' + step.id);
                }
                break;
            case 'set_custom_field':
                html += '<label style="margin-top:8px;">' + CBLANG.action_field + '</label>';
                html += '<select class="form-select" onchange="cbUpdateCustomField(' + pathStr + ', ' + index + ', this.value)">';
                html += '<option value="">' + CBLANG.condition_select + '</option>';
                CUSTOM_FIELDS.forEach(function(f) {
                    html += '<option value="' + esc(f.name) + '" ' + (c.field_name === f.name ? 'selected' : '') + '>' + esc(f.label) + '</option>';
                });
                html += '</select>';
                html += '<label style="margin-top:8px;">' + CBLANG.action_field_value + '</label>';
                html += '<input class="form-control" value="' + esc(c.field_value || '') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'field_value\', this.value)">';
                break;
            case 'send_whatsapp':
                html += '<label style="margin-top:8px;">' + CBLANG.action_destination + '</label>';
                html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'phone_mode\', this.value); renderFlow();">';
                html += '<option value="variable" ' + ((c.phone_mode || 'variable') === 'variable' ? 'selected' : '') + '>' + CBLANG.action_phone_mode_variable + '</option>';
                html += '<option value="custom" ' + (c.phone_mode === 'custom' ? 'selected' : '') + '>' + CBLANG.action_phone_mode_custom + '</option>';
                html += '</select>';
                if (c.phone_mode === 'custom') {
                    html += '<label style="margin-top:8px;">' + CBLANG.action_phone_number + '</label>';
                    html += '<input class="form-control" value="' + esc(c.custom_phone || '') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'custom_phone\', this.value)" placeholder="5511999999999">';
                } else {
                    html += '<label style="margin-top:8px;">' + CBLANG.action_phone_variable + '</label>';
                    html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'phone_var\', this.value)">';
                    html += '<option value="$contact_phone" ' + ((c.phone_var || '$contact_phone') === '$contact_phone' ? 'selected' : '') + '>$contact_phone</option>';
                    flowVariables.forEach(function(fv) {
                        var name = fv.name || fv;
                        html += '<option value="' + esc(name) + '" ' + (c.phone_var === name ? 'selected' : '') + '>' + esc(name) + '</option>';
                    });
                    html += '</select>';
                }
                html += '<label style="margin-top:8px;">' + CBLANG.action_wa_message + '</label>';
                html += '<div class="cb-editable" contenteditable="true" id="wa-' + step.id + '" data-path="' + pathStr + '" data-index="' + index + '" data-field="message" data-placeholder="' + CBLANG.msg_text_placeholder + '">' + textToHtml(c.message || '') + '</div>';
                html += renderVarHint('wa-' + step.id);
                html += '<p style="font-size:11px;color:#9ca3af;margin-top:6px;"><i class="bi bi-info-circle"></i> ' + CBLANG.action_wa_hint + '</p>';
                break;
            case 'create_task':
                html += '<label style="margin-top:8px;">' + CBLANG.action_task_subject + '</label>';
                html += '<input class="form-control" value="' + esc(c.subject || '') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'subject\', this.value)" placeholder="' + CBLANG.action_task_subject_placeholder + '">';
                html += '<label style="margin-top:8px;">' + CBLANG.action_task_description + '</label>';
                html += '<textarea class="form-control" rows="2" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'description\', this.value)" placeholder="' + CBLANG.action_task_desc_placeholder + '">' + esc(c.description || '') + '</textarea>';
                html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">';
                html += '<div><label style="margin-top:8px;">' + CBLANG.action_task_type + '</label>';
                html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'task_type\', this.value)">';
                var _ttypes = [['call',CBLANG.task_type_call],['email',CBLANG.task_type_email],['task',CBLANG.task_type_task],['visit',CBLANG.task_type_visit],['whatsapp',CBLANG.task_type_whatsapp],['meeting',CBLANG.task_type_meeting]];
                _ttypes.forEach(function(tt){ html += '<option value="'+tt[0]+'" '+((c.task_type||'task')===tt[0]?'selected':'')+'>'+tt[1]+'</option>'; });
                html += '</select></div>';
                html += '<div><label style="margin-top:8px;">' + CBLANG.action_task_priority + '</label>';
                html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'priority\', this.value)">';
                [['low',CBLANG.priority_low],['medium',CBLANG.priority_medium],['high',CBLANG.priority_high]].forEach(function(p){ html += '<option value="'+p[0]+'" '+((c.priority||'medium')===p[0]?'selected':'')+'>'+p[1]+'</option>'; });
                html += '</select></div></div>';
                html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">';
                html += '<div><label style="margin-top:8px;">' + CBLANG.action_task_due_days + '</label>';
                html += '<input type="number" class="form-control" min="0" max="365" value="' + (c.due_date_offset || 0) + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'due_date_offset\', parseInt(this.value))"></div>';
                html += '<div><label style="margin-top:8px;">' + CBLANG.action_task_due_time + '</label>';
                html += '<input type="time" class="form-control" value="' + esc(c.due_time || '09:00') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'due_time\', this.value)"></div>';
                html += '</div>';
                html += '<label style="margin-top:8px;">' + CBLANG.action_task_assign_to + '</label>';
                html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'assigned_to_mode\', this.value); renderFlow();">';
                html += '<option value="automatic" ' + ((c.assigned_to_mode || 'automatic') === 'automatic' ? 'selected' : '') + '>' + CBLANG.action_task_assign_auto + '</option>';
                html += '<option value="user" ' + (c.assigned_to_mode === 'user' ? 'selected' : '') + '>' + CBLANG.action_task_assign_user + '</option>';
                html += '</select>';
                if (c.assigned_to_mode === 'user') {
                    html += '<label style="margin-top:8px;">' + CBLANG.action_task_user + '</label>';
                    html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'assigned_to_user_id\', parseInt(this.value))">';
                    html += '<option value="">' + CBLANG.condition_select + '</option>';
                    (window.chatbotBuilderData.users || []).forEach(function(u) {
                        html += '<option value="' + u.id + '" ' + (c.assigned_to_user_id == u.id ? 'selected' : '') + '>' + esc(u.name) + '</option>';
                    });
                    html += '</select>';
                }
                html += '<p style="font-size:11px;color:#9ca3af;margin-top:6px;"><i class="bi bi-info-circle"></i> ' + CBLANG.action_task_hint + '</p>';
                break;
            case 'redirect':
                html += '<label style="margin-top:8px;">' + CBLANG.action_redirect_url + '</label>';
                html += '<input class="form-control" value="' + esc(c.url || '') + '" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'url\', this.value)" placeholder="https://seusite.com/obrigado">';
                html += '<label style="margin-top:8px;">' + CBLANG.action_redirect_open_in + '</label>';
                html += '<select class="form-select" onchange="cbUpdateConfig(' + pathStr + ', ' + index + ', \'target\', this.value)">';
                html += '<option value="_blank" ' + ((c.target || '_blank') === '_blank' ? 'selected' : '') + '>' + CBLANG.action_redirect_new_tab + '</option>';
                html += '<option value="_self" ' + (c.target === '_self' ? 'selected' : '') + '>' + CBLANG.action_redirect_same_tab + '</option>';
                html += '</select>';
                html += '<p style="font-size:11px;color:#9ca3af;margin-top:6px;"><i class="bi bi-info-circle"></i> ' + CBLANG.action_redirect_hint + '</p>';
                break;
        }

        return html;
    }

    // ── Render Branches (TREE layout) ────────────────────────────────
    function renderBranches(step, path, index) {
        const pathStr = JSON.stringify(path).replace(/"/g, '&quot;');
        const branches = step.branches || [];
        const totalCols = branches.length + 1; // +1 for default branch
        const colW = 320, gap = 24;
        const totalWidth = totalCols * colW + (totalCols - 1) * gap;
        const barWidth = (totalCols - 1) * (colW + gap);

        let html = '<div class="cb-branches-wrapper">';

        // Vertical stem from parent node
        html += '<div class="cb-tree-stem"></div>';

        // Horizontal bar
        if (totalCols > 1) {
            html += '<div class="cb-tree-hbar" style="width:' + barWidth + 'px;"></div>';
        }

        // Branch columns
        html += '<div class="cb-branches">';

        // Regular branches
        branches.forEach(function(b, bi) {
            const branchPath = path.concat([index, 'branches', bi, 'steps']);
            const branchPathStr = JSON.stringify(branchPath).replace(/"/g, '&quot;');

            html += '<div class="cb-branch-col">';

            // Header
            html += '<div class="cb-branch-header">';
            html += '<input class="cb-branch-label-input" maxlength="24" placeholder="' + CBLANG.branch_max_chars + '" value="' + esc(b.label || CBLANG.branch_option.replace(':number', bi + 1)) + '" onchange="cbUpdateBranch(' + pathStr + ', ' + index + ', ' + bi + ', \'label\', this.value)">';
            html += '<button class="cb-branch-remove" onclick="cbRemoveBranch(' + pathStr + ', ' + index + ', ' + bi + ')" title="' + CBLANG.branch_remove + '"><i class="bi bi-x-lg"></i></button>';
            html += '</div>';

            // Config
            html += '<div class="cb-branch-config">';
            if (step.type === 'input') {
                // Keywords auto-sync com label — campo oculto
            } else if (step.type === 'condition') {
                var varLabel = (step.config && step.config.variable) || 'variável';
                var opLabel = {equals:CBLANG.op_sentence_equals,not_equals:CBLANG.op_sentence_not_equals,contains:CBLANG.op_sentence_contains,starts_with:CBLANG.op_sentence_starts_with,ends_with:CBLANG.op_sentence_ends_with,gt:CBLANG.op_sentence_gt,lt:CBLANG.op_sentence_lt}[b.operator] || '...';
                var valLabel = b.value || '...';
                html += '<div style="font-size:11px;color:#6b7280;margin-bottom:6px;font-style:italic;">' + CBLANG.branch_condition_sentence.replace(':variable', esc(varLabel)).replace(':operator', esc(opLabel)).replace(':value', esc(valLabel)) + '</div>';
                html += '<div class="row-pair">';
                html += '<div><label>' + CBLANG.branch_operator + '</label><select class="form-select" onchange="cbUpdateBranch(' + pathStr + ', ' + index + ', ' + bi + ', \'operator\', this.value)">';
                var ops = { equals: CBLANG.op_equals, not_equals: CBLANG.op_not_equals, contains: CBLANG.op_contains, starts_with: CBLANG.op_starts_with, ends_with: CBLANG.op_ends_with, gt: CBLANG.op_gt, lt: CBLANG.op_lt };
                Object.keys(ops).forEach(function(op) {
                    html += '<option value="' + op + '" ' + (b.operator === op ? 'selected' : '') + '>' + ops[op] + '</option>';
                });
                html += '</select></div>';
                html += '<div><label>' + CBLANG.branch_value + '</label><input class="form-control" value="' + esc(b.value || '') + '" onchange="cbUpdateBranch(' + pathStr + ', ' + index + ', ' + bi + ', \'value\', this.value)"></div>';
                html += '</div>';
            }
            html += '</div>';

            // Body (sub-steps)
            html += '<div class="cb-branch-body" id="branch-' + step.id + '-' + bi + '">';
            // Sub-steps rendered via DOM after innerHTML is set
            html += '</div>';

            html += '</div>'; // .cb-branch-col
        });

        // Default branch
        const defPath = path.concat([index, 'default_branch', 'steps']);
        const defPathStr = JSON.stringify(defPath).replace(/"/g, '&quot;');

        html += '<div class="cb-branch-col default">';
        html += '<div class="cb-branch-header"><span class="cb-branch-label-input" style="color:#9ca3af;font-weight:600;">' + CBLANG.branch_default + '</span></div>';
        html += '<div class="cb-branch-config"><label style="color:#b0b0b0;font-size:10px;">' + CBLANG.branch_default_hint + '</label></div>';
        html += '<div class="cb-branch-body" id="branch-' + step.id + '-default"></div>';
        html += '</div>';

        // Add branch button
        html += '<div class="cb-add-branch-col" onclick="cbAddBranch(' + pathStr + ', ' + index + ')" title="' + CBLANG.branch_add + '">+</div>';

        html += '</div>'; // .cb-branches
        html += '</div>'; // .cb-branches-wrapper

        return html;
    }

    // After renderFlow sets innerHTML, we need to populate branch bodies via DOM
    // We use a post-render pass since innerHTML doesn't support nested renderStepListInto
    function postRenderBranches() {
        flowSteps.forEach(function(step, i) {
            populateBranchBodies(step, [], i);
        });
    }

    function populateBranchBodies(step, path, index) {
        if (step.type !== 'input' && step.type !== 'condition') return;
        if (!step.branches && !step.default_branch) return;
        if (step.type === 'input' && (!step.branches || !step.branches.length)) return;

        // Regular branches
        (step.branches || []).forEach(function(b, bi) {
            const containerId = 'branch-' + step.id + '-' + bi;
            const container = document.getElementById(containerId);
            if (!container) return;
            container.innerHTML = '';

            const branchPath = path.concat([index, 'branches', bi, 'steps']);
            const branchPathStr = JSON.stringify(branchPath).replace(/"/g, '&quot;');

            (b.steps || []).forEach(function(bs, bsi) {
                const stepEl = document.createElement('div');
                stepEl.style.cssText = 'display:flex;flex-direction:column;align-items:center;width:100%;';
                stepEl.innerHTML = renderStep(bs, branchPath, bsi, b.steps.length);
                container.appendChild(stepEl);

                // Recursively populate nested branches
                if ((bs.type === 'condition' || (bs.type === 'input' && bs.branches && bs.branches.length > 0)) && (bs.branches || bs.default_branch)) {
                    const nestedEl = document.createElement('div');
                    nestedEl.style.cssText = 'display:flex;flex-direction:column;align-items:center;width:100%;';
                    nestedEl.innerHTML = renderBranches(bs, branchPath, bsi);
                    container.appendChild(nestedEl);
                }

                if (bsi < b.steps.length - 1) {
                    const conn = document.createElement('div');
                    conn.className = 'cb-connector';
                    container.appendChild(conn);
                }
            });

            // Add step button
            const addBtn = document.createElement('button');
            addBtn.className = 'cb-add-step';
            addBtn.style.marginTop = '8px';
            addBtn.innerHTML = '<i class="bi bi-plus-lg"></i> ' + CBLANG.add_step;
            addBtn.onclick = function() { showAddMenu(addBtn, branchPath); };
            container.appendChild(addBtn);

            // Recursive: populate nested branches within this branch
            (b.steps || []).forEach(function(bs, bsi) {
                populateBranchBodies(bs, branchPath, bsi);
            });
        });

        // Default branch
        const defContainerId = 'branch-' + step.id + '-default';
        const defContainer = document.getElementById(defContainerId);
        if (!defContainer) return;
        defContainer.innerHTML = '';

        const defPath = path.concat([index, 'default_branch', 'steps']);
        const defPathStr = JSON.stringify(defPath).replace(/"/g, '&quot;');
        const defSteps = (step.default_branch && step.default_branch.steps) || [];

        defSteps.forEach(function(ds, dsi) {
            const stepEl = document.createElement('div');
            stepEl.style.cssText = 'display:flex;flex-direction:column;align-items:center;width:100%;';
            stepEl.innerHTML = renderStep(ds, defPath, dsi, defSteps.length);
            defContainer.appendChild(stepEl);

            if ((ds.type === 'condition' || (ds.type === 'input' && ds.branches && ds.branches.length > 0)) && (ds.branches || ds.default_branch)) {
                const nestedEl = document.createElement('div');
                nestedEl.style.cssText = 'display:flex;flex-direction:column;align-items:center;width:100%;';
                nestedEl.innerHTML = renderBranches(ds, defPath, dsi);
                defContainer.appendChild(nestedEl);
            }

            if (dsi < defSteps.length - 1) {
                const conn = document.createElement('div');
                conn.className = 'cb-connector';
                defContainer.appendChild(conn);
            }
        });

        const addBtn = document.createElement('button');
        addBtn.className = 'cb-add-step';
        addBtn.style.marginTop = '8px';
        addBtn.innerHTML = '<i class="bi bi-plus-lg"></i> ' + CBLANG.add_step;
        addBtn.onclick = function() { showAddMenu(addBtn, defPath); };
        defContainer.appendChild(addBtn);

        // Recursive for default branch
        defSteps.forEach(function(ds, dsi) {
            populateBranchBodies(ds, defPath, dsi);
        });
    }

    // ── SVG Connection Drawing ──────────────────────────────────────
    function drawConnections() {
        var flow = document.getElementById('cbFlow');
        if (!flow) return;

        // Remove old SVG
        var oldSvg = flow.querySelector('.cb-svg-overlay');
        if (oldSvg) oldSvg.remove();

        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'cb-svg-overlay');
        flow.insertBefore(svg, flow.firstChild);

        var flowRect = flow.getBoundingClientRect();

        function rel(rect) {
            return {
                cx: rect.left + rect.width / 2 - flowRect.left,
                top: rect.top - flowRect.top,
                bottom: rect.bottom - flowRect.top,
                left: rect.left - flowRect.left,
                right: rect.right - flowRect.left
            };
        }

        function bezierPath(x1, y1, x2, y2) {
            if (Math.abs(x1 - x2) < 2) return 'M' + x1 + ',' + y1 + ' L' + x2 + ',' + y2;
            var midY = y1 + (y2 - y1) * 0.5;
            var r = Math.min(8, Math.abs(y2 - y1) * 0.25, Math.abs(x2 - x1) * 0.5);
            var dir = x2 > x1 ? 1 : -1;
            return 'M' + x1 + ',' + y1 + ' L' + x1 + ',' + (midY - r)
                + ' Q' + x1 + ',' + midY + ' ' + (x1 + r * dir) + ',' + midY
                + ' L' + (x2 - r * dir) + ',' + midY
                + ' Q' + x2 + ',' + midY + ' ' + x2 + ',' + (midY + r)
                + ' L' + x2 + ',' + y2;
        }

        function addPath(x1, y1, x2, y2, animated) {
            var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', bezierPath(x1, y1, x2, y2));
            path.setAttribute('class', 'cb-path' + (animated ? ' cb-path-animated' : ''));
            svg.appendChild(path);
            var dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            dot.setAttribute('cx', x2);
            dot.setAttribute('cy', y2);
            dot.setAttribute('r', '3');
            dot.setAttribute('class', 'cb-path-dot');
            svg.appendChild(dot);
        }

        function findNode(stepId) {
            return flow.querySelector('.cb-node[data-step-id="' + stepId + '"]');
        }

        function connectNodes(fromEl, toEl, animated) {
            if (!fromEl || !toEl) return;
            var fromR = rel(fromEl.getBoundingClientRect());
            var toR = rel(toEl.getBoundingClientRect());
            addPath(fromR.cx, fromR.bottom, toR.cx, toR.top, animated !== false);
        }

        // Connect a list of steps sequentially, returns the last step's node element
        function connectStepList(steps, prevNodeEl) {
            var lastEl = prevNodeEl;
            steps.forEach(function(step) {
                var nodeEl = findNode(step.id);
                if (!nodeEl) return;
                if (lastEl) connectNodes(lastEl, nodeEl, true);
                lastEl = nodeEl;

                // If step has branches, connect to branch columns
                if ((step.type === 'condition' || (step.type === 'input' && step.branches && step.branches.length > 0)) && (step.branches || step.default_branch)) {
                    var branches = step.branches || [];
                    var allBranches = branches.slice();
                    // Add default branch
                    if (step.default_branch) allBranches.push(step.default_branch);

                    allBranches.forEach(function(branch, bi) {
                        var branchId = (bi < branches.length) ? ('branch-' + step.id + '-' + bi) : ('branch-' + step.id + '-default');
                        var branchBody = document.getElementById(branchId);
                        if (!branchBody) return;
                        var col = branchBody.closest('.cb-branch-col');
                        if (!col) return;

                        // Connect parent node to top of branch column
                        var colR = rel(col.getBoundingClientRect());
                        var nodeR = rel(nodeEl.getBoundingClientRect());
                        addPath(nodeR.cx, nodeR.bottom, colR.cx, colR.top, true);

                        // Connect sub-steps within the branch
                        var subSteps = branch.steps || [];
                        if (subSteps.length > 0) {
                            connectStepList(subSteps, null);
                        }
                    });

                    // After branches, the next step connects from below — use nodeEl as anchor
                    // (branches don't have a single exit point, so next step connects from parent)
                }
            });
            return lastEl;
        }

        // Start: connect _start → first step → second step → ...
        var startEl = findNode('_start');
        connectStepList(flowSteps, startEl);
    }

    var _drawDebounce;
    window.addEventListener('resize', function() {
        clearTimeout(_drawDebounce);
        _drawDebounce = setTimeout(drawConnections, 100);
    });

    // Override renderFlow to include post-render
    const _origRenderFlow = renderFlow;
    renderFlow = function() {
        _origRenderFlow();
        postRenderBranches();
        bindEditables();
        requestAnimationFrame(drawConnections);
        // Reabrir drawer se estava aberto (ex: após mudar tipo de ação)
        if (_selectedPath !== null && _selectedIndex !== null) {
            requestAnimationFrame(function() { openEditPanel(_selectedPath, _selectedIndex); });
        }
    };

    // Bind input listeners on contenteditable elements to sync data
    function bindEditables() {
        document.querySelectorAll('.cb-editable[data-path]').forEach(function(el) {
            if (el._cbBound) return;
            el._cbBound = true;
            el.addEventListener('input', function() {
                var path = JSON.parse(el.getAttribute('data-path'));
                var index = parseInt(el.getAttribute('data-index'), 10);
                var field = el.getAttribute('data-field');
                var text = htmlToText(el);
                var arr = resolveParentSteps(path);
                if (!arr[index].config) arr[index].config = {};
                arr[index].config[field] = text;
            });
            // Prevent paste from injecting rich HTML — paste plain text only
            el.addEventListener('paste', function(e) {
                e.preventDefault();
                var text = (e.clipboardData || window.clipboardData).getData('text/plain');
                document.execCommand('insertText', false, text);
            });
        });
    }

    // ── Global click handlers ────────────────────────────────────────
    window.cbRemoveStep = function(path, index) {
        removeStep(path, index);
    };

    window.cbMoveStep = function(path, index, direction) {
        moveStep(path, index, direction);
    };

    window.cbUpdateConfig = function(path, index, field, value) {
        const arr = resolveParentSteps(path);
        if (!arr[index].config) arr[index].config = {};
        arr[index].config[field] = value;
    };

    window.cbToggleButtons = function(path, index, enabled) {
        const step = resolveParentSteps(path)[index];
        if (!step.config) step.config = {};
        step.config.show_buttons = enabled;
        if (enabled) {
            if (!step.branches || !step.branches.length) {
                step.branches = [{ id: 'b' + Date.now().toString(36) + (idCounter++), label: CBLANG.branch_option.replace(':number', 1), keywords: [], steps: [] }];
            }
            if (!step.default_branch) step.default_branch = { steps: [] };
        } else {
            step.branches = [];
            step.default_branch = { steps: [] };
        }
        renderFlow();
    };

    window.cbAddCard = function(path, index) {
        const arr = resolveParentSteps(path);
        if (!arr[index].config.items) arr[index].config.items = [];
        arr[index].config.items.push({ title: '', description: '', image_url: '', button_label: '', button_action: 'reply', button_value: '', button_url: '' });
        renderFlow();
    };

    window.cbRemoveCard = function(path, index, i) {
        resolveParentSteps(path)[index].config.items.splice(i, 1);
        renderFlow();
    };

    window.cbUpdateCardItem = function(path, index, i, field, value) {
        resolveParentSteps(path)[index].config.items[i][field] = value;
    };

    window.cbUpdateActionType = function(path, index, newType) {
        const arr = resolveParentSteps(path);
        arr[index].config = { type: newType };
        renderFlow();
    };

    window.cbUpdateCustomField = function(path, index, fieldName) {
        const arr = resolveParentSteps(path);
        const field = CUSTOM_FIELDS.find(function(f) { return f.name === fieldName; });
        arr[index].config.field_name = fieldName;
        arr[index].config.field_label = field ? field.label : fieldName;
        renderFlow();
    };

    window.cbAddBranch = function(path, stepIndex) {
        addBranch(path, stepIndex);
    };

    window.cbRemoveBranch = function(path, stepIndex, branchIndex) {
        removeBranch(path, stepIndex, branchIndex);
    };

    window.cbUpdateBranch = function(path, stepIndex, branchIndex, field, value) {
        const step = resolveParentSteps(path)[stepIndex];
        step.branches[branchIndex][field] = value;
        if (field === 'label') step.branches[branchIndex].keywords = [value];
    };

    window.cbUpdateBranchKeywords = function(path, stepIndex, branchIndex, value) {
        const step = resolveParentSteps(path)[stepIndex];
        step.branches[branchIndex].keywords = value.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    };

    // ── Add menu (inline dropdown) ───────────────────────────────────
    window.showAddMenu = function(btn, path) {
        document.querySelectorAll('.cb-add-menu').forEach(function(m) { m.remove(); });

        const menu = document.createElement('div');
        menu.className = 'cb-add-menu';
        menu.style.cssText = 'position:absolute;z-index:100;background:#fff;border:1.5px solid #e8eaf0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);padding:6px 0;min-width:180px;';

        var types = [
            ['message', 'bi-chat-dots', CBLANG.node_message],
            ['input', 'bi-input-cursor-text', CBLANG.node_input],
            ['condition', 'bi-question-diamond', CBLANG.node_condition],
            ['action', 'bi-lightning', CBLANG.node_action],
            ['delay', 'bi-hourglass-split', CBLANG.node_delay],
            ['cards', 'bi-card-heading', CBLANG.node_cards],
            ['end', 'bi-stop-circle', CBLANG.node_end],
        ];

        types.forEach(function(t) {
            var item = document.createElement('div');
            item.style.cssText = 'display:flex;align-items:center;gap:10px;padding:8px 14px;cursor:pointer;font-size:13px;color:#374151;font-weight:500;transition:background .12s;';
            item.innerHTML = '<i class="bi ' + t[1] + '" style="font-size:14px;color:#6b7280;"></i>' + t[2];
            item.onmouseenter = function() { item.style.background = '#eff6ff'; };
            item.onmouseleave = function() { item.style.background = ''; };
            item.onclick = function(e) {
                e.stopPropagation();
                var arr = resolveParentSteps(path);
                arr.push(createStep(t[0]));
                renderFlow();
                menu.remove();
            };
            menu.appendChild(item);
        });

        btn.style.position = 'relative';
        btn.appendChild(menu);

        setTimeout(function() {
            document.addEventListener('click', function handler(e) {
                if (!menu.contains(e.target) && e.target !== btn) {
                    menu.remove();
                    document.removeEventListener('click', handler);
                }
            });
        }, 10);
    };

    // ── Image upload ─────────────────────────────────────────────────
    window.cbUploadImage = function(path, index, areaEl) {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = function() {
            var file = input.files[0];
            if (!file) return;
            var fd = new FormData();
            fd.append('image', file);
            fetch(UPLOAD_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF },
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.url) {
                    cbUpdateConfig(path, index, 'image_url', data.url);
                    renderFlow();
                }
            })
            .catch(function(err) {
                console.error('Upload error:', err);
                toastr.error(CBLANG.toast_upload_error);
            });
        };
        input.click();
    };

    // ── Card image upload ─────────────────────────────────────────────
    window.cbUploadCardImage = function(path, index, cardIndex) {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = function() {
            var file = input.files[0];
            if (!file) return;
            var fd = new FormData();
            fd.append('image', file);
            fetch(UPLOAD_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF },
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.url) {
                    cbUpdateCardItem(path, index, cardIndex, 'image_url', data.url);
                    renderFlow();
                }
            })
            .catch(function(err) {
                console.error('Card image upload error:', err);
                toastr.error(CBLANG.toast_upload_error);
            });
        };
        input.click();
    };

    window.cbRemoveCardImage = function(path, index, cardIndex) {
        cbUpdateCardItem(path, index, cardIndex, 'image_url', '');
        renderFlow();
    };

    // ── Toggle active ────────────────────────────────────────────────
    window.toggleActive = function() {
        fetch(TOGGLE_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var badge = document.getElementById('cbStatusBadge');
            if (data.is_active) {
                badge.classList.add('active');
                badge.textContent = CBLANG.builder_active;
            } else {
                badge.classList.remove('active');
                badge.textContent = CBLANG.builder_inactive;
            }
        });
    };

    // ── Save flow ────────────────────────────────────────────────────
    window.toggleCatchAll = function(checked) {
        fetch('{{ route('chatbot.flows.update', $flow) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'text/html',
            },
            body: new URLSearchParams({
                _method: 'PUT',
                name: document.getElementById('cbName').value.trim() || '{{ $flow->name }}',
                channel: '{{ $flow->channel }}',
                is_catch_all: checked ? '1' : '0',
            }),
        }).then(function() {
            toastr.success(checked ? CBLANG.toast_catch_all_on : CBLANG.toast_catch_all_off);
        }).catch(function() {
            toastr.error(CBLANG.toast_update_error);
        });
    };

    window.saveFlow = function(silent) {
        var name = document.getElementById('cbName').value.trim();
        if (!name) {
            if (!silent) toastr.warning(CBLANG.toast_name_required);
            return Promise.resolve();
        }

        return fetch(SAVE_URL, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                steps: JSON.stringify(flowSteps),
                variables: flowVariables,
                name: name,
                trigger_type: _currentTriggerType,
                trigger_media_id: _triggerMediaId || null,
                trigger_media_thumbnail: _triggerMediaThumb || null,
                trigger_media_caption: _triggerMediaCaption || null,
                trigger_reply_comment: _triggerReplyComment || null,
            }),
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                if (!silent) toastr.success(CBLANG.toast_flow_saved);
            } else {
                toastr.error(data.message || CBLANG.toast_save_error);
            }
        })
        .catch(function(err) {
            console.error('Save error:', err);
            toastr.error(CBLANG.toast_save_flow_error);
        });
    };

    // ── Sidebar trigger type (Instagram comment) ──────────────────
    window.onSidebarTriggerTypeChange = function(val) {
        _currentTriggerType = val;
        const panel = document.getElementById('sidebarCommentConfig');
        if (panel) panel.style.display = val === 'instagram_comment' ? '' : 'none';
        renderFlow();
    };

    window.onSidebarMediaScopeChange = function(val) {
        const picker = document.getElementById('sidebarPostPicker');
        if (picker) picker.style.display = val === 'specific' ? '' : 'none';
        if (val === 'all') { _triggerMediaId = ''; _triggerMediaThumb = ''; _triggerMediaCaption = ''; }
    };

    window.loadSidebarPosts = function(after) {
        const grid = document.getElementById('sidebarPostGrid');
        if (!after) grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:8px;color:#9ca3af;font-size:10px;">Carregando...</div>';
        const url = '{{ route("settings.ig-automations.posts") }}' + (after ? '?after=' + after : '');
        window.API.get(url).then(function(res) {
            if (!after) grid.innerHTML = '';
            (res.data || []).forEach(function(post) {
                const div = document.createElement('div');
                div.style.cssText = 'position:relative;aspect-ratio:1;border-radius:6px;overflow:hidden;border:2px solid ' + (post.id === _triggerMediaId ? '#0085f3' : '#e8eaf0') + ';cursor:pointer;';
                const img = post.thumbnail_url ? '<img src="' + post.thumbnail_url + '" style="width:100%;height:100%;object-fit:cover;" loading="lazy">' : '<div style="height:100%;background:#f3f4f6;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:12px;"><i class="bi bi-image"></i></div>';
                const badge = post.media_type === 'REEL' ? '<span style="position:absolute;top:2px;left:2px;background:rgba(124,58,237,.85);color:#fff;font-size:7px;font-weight:700;padding:1px 4px;border-radius:2px;">Reel</span>' : '';
                div.innerHTML = img + badge;
                div.onclick = function() {
                    grid.querySelectorAll('div').forEach(function(d) { d.style.borderColor = '#e8eaf0'; });
                    div.style.borderColor = '#0085f3';
                    _triggerMediaId = post.id;
                    _triggerMediaThumb = post.thumbnail_url || '';
                    _triggerMediaCaption = post.caption || '';
                };
                grid.appendChild(div);
            });
            if (res.next_cursor) {
                const more = document.createElement('div');
                more.style.cssText = 'grid-column:1/-1;text-align:center;';
                more.innerHTML = '<button type="button" onclick="loadSidebarPosts(\'' + res.next_cursor + '\');this.parentElement.remove();" style="padding:3px 10px;background:#eff6ff;color:#0085f3;border:1px solid #bfdbfe;border-radius:4px;font-size:9px;font-weight:600;cursor:pointer;">Mais</button>';
                grid.appendChild(more);
            }
        }).catch(function() {
            grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:8px;color:#ef4444;font-size:10px;">Erro ao carregar</div>';
        });
    };

    // Sync sidebar reply comment textarea to variable
    const _rcTextarea = document.getElementById('sidebarReplyComment');
    if (_rcTextarea) {
        _rcTextarea.addEventListener('input', function() { _triggerReplyComment = this.value; });
    }

    // ── Variables modal ──────────────────────────────────────────────
    window.showVarsModal = function() {
        renderVarsList();
        new bootstrap.Modal(document.getElementById('varsModal')).show();
    };

    function renderVarsList() {
        var list = document.getElementById('varsList');
        list.innerHTML = '';
        flowVariables.forEach(function(v, i) {
            var name = v.name || v;
            list.innerHTML += '<li><span class="var-name">' + esc(name) + '</span><span class="var-remove" onclick="removeVariable(' + i + ')"><i class="bi bi-x-lg"></i></span></li>';
        });
    }

    window.addVariable = function() {
        var input = document.getElementById('newVarName');
        var name = input.value.trim().replace(/[^a-zA-Z0-9_]/g, '');
        if (!name) return;
        if (flowVariables.some(function(v) { return (v.name || v) === name; })) {
            toastr.warning(CBLANG.toast_var_exists);
            return;
        }
        flowVariables.push({ name: name, default: '' });
        input.value = '';
        renderVarsList();
    };

    window.removeVariable = function(index) {
        flowVariables.splice(index, 1);
        renderVarsList();
    };

    // ── Bot Templates Library ───────────────────────────────────────────
    // Helper: build a standard lead-capture flow with customizable greeting, questions and farewell
    function buildLeadFlow(greeting, extraQuestions, farewell, extraVars) {
        var steps = [
            { id:'t1', type:'message', config:{ text: greeting } },
            { id:'t2', type:'input', config:{ input_type:'text', prompt:'Qual o seu nome?', save_to:'nome' }, branches:[], default_branch:{ steps:[] } },
        ];
        var vars = [{ name:'nome', default:'' }];
        var idx = 3;
        (extraQuestions || []).forEach(function(q) {
            var step = { id:'t'+(idx), type:'input', config:{ input_type: q.type || 'text', prompt: q.prompt, save_to: q.save_to }, branches:[], default_branch:{ steps:[] } };
            if (q.buttons) {
                step.config.input_type = 'buttons';
                step.branches = q.buttons.map(function(b, bi) {
                    return { id:'b'+idx+'_'+bi, label: b, keywords:[b.toLowerCase()], steps:[] };
                });
            }
            steps.push(step);
            vars.push({ name: q.save_to, default:'' });
            idx++;
        });
        steps.push({ id:'t'+(idx++), type:'input', config:{ input_type:'phone', prompt:'Seu telefone com DDD:', save_to:'telefone' }, branches:[], default_branch:{ steps:[] } });
        vars.push({ name:'telefone', default:'' });
        steps.push({ id:'t'+(idx++), type:'action', config:{ action_type:'create_lead', source:'website' } });
        steps.push({ id:'t'+(idx++), type:'message', config:{ text: farewell } });
        steps.push({ id:'t'+(idx++), type:'end', config:{} });
        return { steps: steps, variables: vars };
    }

    var BOT_TEMPLATES = [
        // ─── Geral ───
        (function() {
            var f = buildLeadFlow(
                'Seja bem-vindo! Vou te ajudar com algumas perguntas rapidas.',
                [{ prompt:'Qual o seu email?', save_to:'email', type:'email' }],
                'Obrigado! Em breve entraremos em contato.',
                []
            );
            return { id:'lead_capture', name:'Captura de Lead', category:'Geral', description:'Coleta nome, email e telefone. Cria lead.', icon:'bi-person-plus', color:'#2563eb', tags:'lead contato captura generico', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Quer receber um contato da nossa equipe?',
                [{ prompt:'Qual o seu email?', save_to:'email', type:'email' }, { prompt:'Como podemos te ajudar?', save_to:'interesse' }],
                'Perfeito! Nossa equipe vai entrar em contato em breve.',
                []
            );
            return { id:'callback', name:'Solicitar Callback', category:'Geral', description:'Visitante solicita que a equipe entre em contato.', icon:'bi-telephone-inbound', color:'#0d9488', tags:'callback retorno ligacao contato', steps:f.steps, variables:f.variables };
        })(),
        // ─── Imobiliária ───
        (function() {
            var f = buildLeadFlow(
                'Que bom que voce se interessou! Vou fazer perguntas rapidas.',
                [
                    { prompt:'Que tipo de imovel procura?', save_to:'tipo_imovel', buttons:['Apartamento','Casa','Comercial','Terreno'] },
                    { prompt:'Qual a faixa de preco?', save_to:'faixa_preco', buttons:['Ate R$ 300 mil','R$ 300-600 mil','Acima R$ 600 mil'] },
                    { prompt:'Em qual regiao ou bairro?', save_to:'regiao' }
                ],
                'Vou buscar as melhores opcoes para voce!', []
            );
            return { id:'real_estate', name:'Imobiliaria', category:'Imoveis', description:'Tipo de imovel, faixa de preco, localizacao.', icon:'bi-building', color:'#7c3aed', tags:'imovel imobiliaria casa apartamento aluguel venda corretor', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Procurando um imovel para alugar? Vou te ajudar!',
                [
                    { prompt:'Tipo de imovel?', save_to:'tipo', buttons:['Apartamento','Casa','Kitnet','Comercial'] },
                    { prompt:'Quantos quartos?', save_to:'quartos', buttons:['1','2','3','4+'] },
                    { prompt:'Orcamento mensal maximo?', save_to:'orcamento' }
                ],
                'Otimo! Vamos encontrar o lugar ideal pra voce.', []
            );
            return { id:'rental', name:'Aluguel de Imoveis', category:'Imoveis', description:'Qualificacao para locacao: tipo, quartos, orcamento.', icon:'bi-house-door', color:'#6d28d9', tags:'aluguel locacao imovel alugar kitnet', steps:f.steps, variables:f.variables };
        })(),
        // ─── Saude ───
        (function() {
            var f = buildLeadFlow(
                'Bem-vindo a nossa clinica! Vou te ajudar a agendar.',
                [
                    { prompt:'Qual a especialidade desejada?', save_to:'especialidade', buttons:['Clinico Geral','Ortopedia','Dermatologia','Outra'] },
                    { prompt:'Possui convenio?', save_to:'convenio', buttons:['Sim','Particular'] },
                    { prompt:'Melhor horario? (manha, tarde, noite)', save_to:'horario' }
                ],
                'Agendamento solicitado! Confirmaremos por telefone.', []
            );
            return { id:'clinic', name:'Clinica Medica', category:'Saude', description:'Agendamento: especialidade, convenio, horario.', icon:'bi-heart-pulse', color:'#dc2626', tags:'clinica medico saude consulta agendamento', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Bem-vindo ao nosso consultorio odontologico!',
                [
                    { prompt:'Qual o servico desejado?', save_to:'servico', buttons:['Limpeza','Clareamento','Ortodontia','Implante','Outro'] },
                    { prompt:'Possui convenio odontologico?', save_to:'convenio', buttons:['Sim','Nao'] },
                    { prompt:'Melhor horario para consulta?', save_to:'horario' }
                ],
                'Perfeito! Vamos agendar sua consulta.', []
            );
            return { id:'dentist', name:'Dentista', category:'Saude', description:'Servico odontologico, convenio, horario.', icon:'bi-emoji-smile', color:'#e11d48', tags:'dentista odontologia dente clareamento ortodontia implante', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Vou te ajudar a agendar sua sessao.',
                [
                    { prompt:'Qual o tipo de terapia?', save_to:'terapia', buttons:['Psicologia','Fisioterapia','Nutricao','Fonoaudiologia'] },
                    { prompt:'E a primeira consulta?', save_to:'primeira_vez', buttons:['Sim','Nao, retorno'] },
                    { prompt:'Prefere atendimento presencial ou online?', save_to:'modalidade', buttons:['Presencial','Online'] }
                ],
                'Sessao solicitada! Confirmaremos em breve.', []
            );
            return { id:'therapy', name:'Terapia / Psicologia', category:'Saude', description:'Agendamento de sessao terapeutica.', icon:'bi-chat-heart', color:'#be185d', tags:'psicologia terapia fisioterapia nutricao terapeuta', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Bem-vindo a nossa clinica veterinaria!',
                [
                    { prompt:'Qual o tipo de animal?', save_to:'animal', buttons:['Cao','Gato','Ave','Outro'] },
                    { prompt:'Qual o motivo da consulta?', save_to:'motivo', buttons:['Check-up','Vacina','Emergencia','Outro'] },
                    { prompt:'Nome do pet?', save_to:'pet_nome' }
                ],
                'Vamos cuidar bem do seu pet! Entraremos em contato.', []
            );
            return { id:'vet', name:'Veterinaria', category:'Saude', description:'Agendamento veterinario: animal, motivo.', icon:'bi-bug', color:'#9333ea', tags:'veterinaria vet pet cachorro gato animal clinica', steps:f.steps, variables:f.variables };
        })(),
        // ─── Estetica e Beleza ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Agende seu horario no nosso salao.',
                [
                    { prompt:'Qual o servico?', save_to:'servico', buttons:['Corte','Coloracao','Manicure','Sobrancelha','Outro'] },
                    { prompt:'Tem preferencia de profissional?', save_to:'profissional' },
                    { prompt:'Melhor dia e horario?', save_to:'horario' }
                ],
                'Agendamento solicitado! Confirmaremos seu horario.', []
            );
            return { id:'salon', name:'Salao de Beleza', category:'Estetica', description:'Agendamento: servico, profissional, horario.', icon:'bi-scissors', color:'#ec4899', tags:'salao beleza cabelo corte manicure sobrancelha cabeleireiro', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Bem-vindo a nossa clinica de estetica.',
                [
                    { prompt:'Qual procedimento te interessa?', save_to:'procedimento', buttons:['Botox','Preenchimento','Limpeza de pele','Depilacao a laser','Outro'] },
                    { prompt:'Ja fez esse procedimento antes?', save_to:'experiencia', buttons:['Sim','Primeira vez'] }
                ],
                'Otimo! Vamos agendar sua avaliacao.', []
            );
            return { id:'aesthetics', name:'Clinica de Estetica', category:'Estetica', description:'Procedimentos esteticos: botox, preenchimento, laser.', icon:'bi-stars', color:'#d946ef', tags:'estetica botox preenchimento pele depilacao laser clinica', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Agende sua sessao de massagem ou spa.',
                [
                    { prompt:'Qual servico?', save_to:'servico', buttons:['Massagem Relaxante','Drenagem','Day Spa','Reflexologia'] },
                    { prompt:'Prefere manha, tarde ou noite?', save_to:'horario', buttons:['Manha','Tarde','Noite'] }
                ],
                'Relaxe! Vamos confirmar seu agendamento.', []
            );
            return { id:'spa', name:'Spa / Massagem', category:'Estetica', description:'Agendamento de massagem e spa.', icon:'bi-droplet-half', color:'#8b5cf6', tags:'spa massagem relaxante drenagem bem-estar', steps:f.steps, variables:f.variables };
        })(),
        // ─── Fitness ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Quer conhecer nossa academia?',
                [
                    { prompt:'Qual seu objetivo?', save_to:'objetivo', buttons:['Emagrecer','Ganhar massa','Condicionamento','Outro'] },
                    { prompt:'Ja treina ou vai comecar agora?', save_to:'experiencia', buttons:['Ja treino','Iniciante'] },
                    { prompt:'Melhor horario para visita?', save_to:'horario' }
                ],
                'Perfeito! Vamos agendar sua visita e aula experimental.', []
            );
            return { id:'gym', name:'Academia / Fitness', category:'Fitness', description:'Captacao: objetivo, experiencia, visita.', icon:'bi-activity', color:'#ea580c', tags:'academia fitness musculacao treino crossfit pilates', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Que tal agendar uma aula experimental?',
                [
                    { prompt:'Qual modalidade?', save_to:'modalidade', buttons:['Yoga','Pilates','Danca','Funcional','Luta'] },
                    { prompt:'Nivel de experiencia?', save_to:'nivel', buttons:['Iniciante','Intermediario','Avancado'] }
                ],
                'Aula experimental agendada! Te esperamos.', []
            );
            return { id:'studio', name:'Studio / Aulas', category:'Fitness', description:'Aula experimental: modalidade, nivel.', icon:'bi-person-arms-up', color:'#f97316', tags:'yoga pilates danca funcional luta studio aula', steps:f.steps, variables:f.variables };
        })(),
        // ─── Educacao ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Que bom que voce quer estudar conosco!',
                [
                    { prompt:'Qual curso te interessa?', save_to:'curso' },
                    { prompt:'Qual sua escolaridade atual?', save_to:'escolaridade', buttons:['Fundamental','Medio','Superior','Pos-graduacao'] },
                    { prompt:'Seu email para enviarmos mais informacoes:', save_to:'email', type:'email' }
                ],
                'Informacoes enviadas! Logo entraremos em contato.', []
            );
            return { id:'school', name:'Escola / Curso', category:'Educacao', description:'Captacao de alunos: curso, escolaridade.', icon:'bi-mortarboard', color:'#0284c7', tags:'escola curso faculdade educacao matricula aluno', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Quer agendar uma aula de idiomas?',
                [
                    { prompt:'Qual idioma?', save_to:'idioma', buttons:['Ingles','Espanhol','Frances','Alemao','Outro'] },
                    { prompt:'Seu nivel atual?', save_to:'nivel', buttons:['Iniciante','Intermediario','Avancado'] },
                    { prompt:'Prefere aulas individuais ou em grupo?', save_to:'formato', buttons:['Individual','Grupo','Tanto faz'] }
                ],
                'Otimo! Vamos agendar sua aula experimental.', []
            );
            return { id:'language', name:'Escola de Idiomas', category:'Educacao', description:'Aula de idiomas: lingua, nivel, formato.', icon:'bi-translate', color:'#0369a1', tags:'idioma ingles espanhol escola lingua curso', steps:f.steps, variables:f.variables };
        })(),
        // ─── Alimentacao ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Bem-vindo ao nosso restaurante.',
                [
                    { prompt:'Para quantas pessoas?', save_to:'pessoas', buttons:['1-2','3-4','5-8','9+'] },
                    { prompt:'Qual dia e horario da reserva?', save_to:'horario' },
                    { prompt:'Alguma restricao alimentar ou observacao?', save_to:'observacao' }
                ],
                'Reserva solicitada! Confirmaremos em breve.', []
            );
            return { id:'restaurant', name:'Restaurante', category:'Alimentacao', description:'Reserva: pessoas, horario, restricoes.', icon:'bi-cup-hot', color:'#b45309', tags:'restaurante reserva mesa jantar almoco comida gastronomia', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Que bom que voce chegou! Faca seu pedido.',
                [
                    { prompt:'O que deseja pedir?', save_to:'pedido' },
                    { prompt:'Entrega ou retirada?', save_to:'tipo_entrega', buttons:['Entrega','Retirada'] },
                    { prompt:'Endereco de entrega (se aplicavel):', save_to:'endereco' }
                ],
                'Pedido anotado! Ja estamos preparando.', []
            );
            return { id:'delivery', name:'Delivery / Lanchonete', category:'Alimentacao', description:'Pedido, tipo de entrega, endereco.', icon:'bi-bag-check', color:'#a16207', tags:'delivery lanchonete hamburgueria pizza entrega pedido comida', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Vamos montar seu orcamento de encomendas.',
                [
                    { prompt:'Tipo de produto?', save_to:'produto', buttons:['Bolo','Doces','Salgados','Kit festa','Outro'] },
                    { prompt:'Para quantas pessoas?', save_to:'pessoas' },
                    { prompt:'Data do evento?', save_to:'data_evento' }
                ],
                'Orcamento solicitado! Enviaremos em breve.', []
            );
            return { id:'bakery', name:'Confeitaria / Padaria', category:'Alimentacao', description:'Encomendas: bolo, doces, salgados, data.', icon:'bi-cake2', color:'#ca8a04', tags:'confeitaria padaria bolo doce salgado encomenda festa', steps:f.steps, variables:f.variables };
        })(),
        // ─── E-commerce / Varejo ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Como posso te ajudar hoje?',
                [
                    { prompt:'Escolha uma opcao:', save_to:'assunto', buttons:['Acompanhar pedido','Troca/Devolucao','Duvida sobre produto','Outro'] }
                ],
                'Entendido! Um atendente vai te responder em breve.', []
            );
            return { id:'ecommerce', name:'E-commerce Geral', category:'Varejo', description:'Atendimento: pedido, troca, duvida.', icon:'bi-cart3', color:'#ea580c', tags:'ecommerce loja online pedido troca devolucao', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Bem-vindo a nossa loja.',
                [
                    { prompt:'O que voce procura?', save_to:'interesse', buttons:['Cortinas','Persianas','Blackout','Orcamento completo'] },
                    { prompt:'Qual o tamanho da janela? (largura x altura)', save_to:'medida' },
                    { prompt:'Em qual comodo?', save_to:'comodo', buttons:['Sala','Quarto','Cozinha','Escritorio','Outro'] }
                ],
                'Vamos preparar seu orcamento personalizado!', []
            );
            return { id:'curtains', name:'Cortinas e Persianas', category:'Varejo', description:'Orcamento: tipo, medida, comodo.', icon:'bi-window', color:'#6366f1', tags:'cortina persiana blackout janela decoracao loja', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Procurando moveis novos?',
                [
                    { prompt:'Que tipo de movel procura?', save_to:'tipo', buttons:['Sofa','Mesa','Cama','Armario','Outro'] },
                    { prompt:'Para qual ambiente?', save_to:'ambiente', buttons:['Sala','Quarto','Cozinha','Escritorio'] },
                    { prompt:'Tem um orcamento em mente?', save_to:'orcamento' }
                ],
                'Otimo! Vamos encontrar o movel perfeito.', []
            );
            return { id:'furniture', name:'Moveis / Decoracao', category:'Varejo', description:'Tipo de movel, ambiente, orcamento.', icon:'bi-lamp', color:'#7c3aed', tags:'moveis mobilia decoracao sofa mesa cama armario loja', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Bem-vindo a nossa otica.',
                [
                    { prompt:'O que voce precisa?', save_to:'servico', buttons:['Oculos de grau','Oculos de sol','Lentes de contato','Exame de vista'] },
                    { prompt:'Tem receita medica?', save_to:'receita', buttons:['Sim','Nao, preciso agendar exame'] }
                ],
                'Perfeito! Vamos te atender da melhor forma.', []
            );
            return { id:'optics', name:'Otica', category:'Varejo', description:'Oculos, lentes, exame de vista.', icon:'bi-eyeglasses', color:'#4f46e5', tags:'otica oculos lentes grau sol exame vista', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Bem-vindo a nossa pet shop.',
                [
                    { prompt:'O que procura?', save_to:'servico', buttons:['Banho e tosa','Racao/Acessorios','Consulta veterinaria','Outro'] },
                    { prompt:'Tipo de pet?', save_to:'pet', buttons:['Cao','Gato','Outro'] },
                    { prompt:'Nome do pet?', save_to:'pet_nome' }
                ],
                'Vamos cuidar do seu bichinho!', []
            );
            return { id:'petshop', name:'Pet Shop', category:'Varejo', description:'Banho e tosa, produtos, consulta.', icon:'bi-suit-heart', color:'#e11d48', tags:'pet shop banho tosa racao cachorro gato animal', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Procurando flores ou presentes?',
                [
                    { prompt:'Qual a ocasiao?', save_to:'ocasiao', buttons:['Aniversario','Namorados','Casamento','Condolencias','Outra'] },
                    { prompt:'Orcamento estimado?', save_to:'orcamento', buttons:['Ate R$100','R$100-200','R$200-500','Acima R$500'] },
                    { prompt:'Data de entrega desejada?', save_to:'data_entrega' }
                ],
                'Vamos preparar um arranjo especial!', []
            );
            return { id:'florist', name:'Floricultura', category:'Varejo', description:'Flores, ocasiao, orcamento, entrega.', icon:'bi-flower1', color:'#db2777', tags:'floricultura flores presente buque arranjo entrega', steps:f.steps, variables:f.variables };
        })(),
        // ─── Servicos ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Precisa de um orcamento?',
                [
                    { prompt:'Qual o servico?', save_to:'servico', buttons:['Eletrica','Hidraulica','Pintura','Reforma geral','Outro'] },
                    { prompt:'E residencial ou comercial?', save_to:'tipo', buttons:['Residencial','Comercial'] },
                    { prompt:'Descreva brevemente o servico:', save_to:'descricao' }
                ],
                'Orcamento solicitado! Entraremos em contato.', []
            );
            return { id:'handyman', name:'Servicos / Manutencao', category:'Servicos', description:'Eletrica, hidraulica, pintura, reforma.', icon:'bi-tools', color:'#d97706', tags:'eletricista encanador pintor pedreiro manutencao reforma servico', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Vamos cuidar da limpeza pra voce.',
                [
                    { prompt:'Tipo de limpeza?', save_to:'tipo', buttons:['Residencial','Comercial','Pos-obra','Vidros'] },
                    { prompt:'Tamanho do local (m2 aprox.)?', save_to:'tamanho' },
                    { prompt:'Frequencia desejada?', save_to:'frequencia', buttons:['Unica vez','Semanal','Quinzenal','Mensal'] }
                ],
                'Otimo! Enviaremos o orcamento em breve.', []
            );
            return { id:'cleaning', name:'Limpeza / Diarista', category:'Servicos', description:'Tipo de limpeza, tamanho, frequencia.', icon:'bi-droplet', color:'#0891b2', tags:'limpeza faxina diarista pos-obra lavar', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Precisa de assessoria juridica?',
                [
                    { prompt:'Qual a area?', save_to:'area', buttons:['Trabalhista','Civil','Criminal','Familia','Empresarial','Outro'] },
                    { prompt:'Descreva brevemente seu caso:', save_to:'descricao' },
                    { prompt:'Seu email:', save_to:'email', type:'email' }
                ],
                'Analisaremos seu caso e retornaremos em breve.', []
            );
            return { id:'lawyer', name:'Advocacia', category:'Servicos', description:'Area juridica, descricao do caso.', icon:'bi-bank', color:'#1e40af', tags:'advogado advocacia juridico direito lei escritorio', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Precisa de servicos contabeis?',
                [
                    { prompt:'Qual servico?', save_to:'servico', buttons:['Abertura de empresa','Imposto de renda','Contabilidade mensal','Outro'] },
                    { prompt:'Tipo de empresa (se aplicavel)?', save_to:'tipo_empresa', buttons:['MEI','ME','EPP','LTDA','SA','Pessoa fisica'] },
                    { prompt:'Seu email:', save_to:'email', type:'email' }
                ],
                'Entendido! Nossa equipe analisara e retornara.', []
            );
            return { id:'accountant', name:'Contabilidade', category:'Servicos', description:'Servicos contabeis, tipo de empresa.', icon:'bi-calculator', color:'#1e3a5f', tags:'contador contabilidade empresa imposto mei abertura', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Quer fazer um seguro ou cotacao?',
                [
                    { prompt:'Tipo de seguro?', save_to:'tipo', buttons:['Auto','Vida','Residencial','Empresarial','Viagem'] },
                    { prompt:'Ja possui seguro atualmente?', save_to:'possui_seguro', buttons:['Sim, quero trocar','Nao, primeira vez'] }
                ],
                'Vamos preparar sua cotacao personalizada!', []
            );
            return { id:'insurance', name:'Seguros / Corretora', category:'Servicos', description:'Tipo de seguro, cotacao.', icon:'bi-shield-check', color:'#15803d', tags:'seguro corretora auto vida residencial cotacao', steps:f.steps, variables:f.variables };
        })(),
        // ─── Automotivo ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Bem-vindo a nossa oficina.',
                [
                    { prompt:'Qual o servico?', save_to:'servico', buttons:['Revisao','Freios','Motor','Eletrica','Funilaria','Outro'] },
                    { prompt:'Marca e modelo do veiculo?', save_to:'veiculo' },
                    { prompt:'Melhor dia para levar?', save_to:'data' }
                ],
                'Agendamento solicitado! Confirmaremos o horario.', []
            );
            return { id:'mechanic', name:'Oficina Mecanica', category:'Automotivo', description:'Servico, veiculo, agendamento.', icon:'bi-wrench', color:'#78350f', tags:'oficina mecanica carro veiculo revisao freio motor', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Procurando um veiculo?',
                [
                    { prompt:'Novo ou usado?', save_to:'condicao', buttons:['Novo','Seminovo','Tanto faz'] },
                    { prompt:'Tipo de veiculo?', save_to:'tipo', buttons:['Carro','Moto','Caminhonete','SUV'] },
                    { prompt:'Faixa de preco?', save_to:'preco' }
                ],
                'Vamos encontrar o veiculo ideal pra voce!', []
            );
            return { id:'car_dealer', name:'Concessionaria / Veiculos', category:'Automotivo', description:'Novo/usado, tipo, faixa de preco.', icon:'bi-car-front', color:'#92400e', tags:'concessionaria carro moto veiculo comprar vender auto loja', steps:f.steps, variables:f.variables };
        })(),
        // ─── Tecnologia ───
        (function() {
            var f = buildLeadFlow(
                'Quer conhecer nossa plataforma? Crie sua conta gratis!',
                [
                    { prompt:'Seu email:', save_to:'email', type:'email' },
                    { prompt:'Nome da empresa:', save_to:'empresa' },
                    { prompt:'Segmento?', save_to:'segmento', buttons:['Tecnologia','Servicos','Varejo','Outro'] }
                ],
                'Conta trial solicitada! Verifique seu email.', []
            );
            return { id:'saas_trial', name:'SaaS / Teste Gratis', category:'Tecnologia', description:'Onboarding: email, empresa, segmento.', icon:'bi-rocket-takeoff', color:'#0891b2', tags:'saas software trial teste tecnologia startup', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Precisa de suporte tecnico?',
                [
                    { prompt:'Qual o problema?', save_to:'problema', buttons:['Computador lento','Sem internet','Erro no sistema','Outro'] },
                    { prompt:'E urgente?', save_to:'urgencia', buttons:['Sim, urgente','Nao, pode agendar'] },
                    { prompt:'Seu email:', save_to:'email', type:'email' }
                ],
                'Suporte registrado! Um tecnico vai te atender.', []
            );
            return { id:'tech_support', name:'Suporte Tecnico / TI', category:'Tecnologia', description:'Problema, urgencia, contato.', icon:'bi-pc-display', color:'#0e7490', tags:'suporte tecnico ti computador informatica assistencia', steps:f.steps, variables:f.variables };
        })(),
        // ─── Eventos ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Vamos planejar seu evento?',
                [
                    { prompt:'Tipo de evento?', save_to:'tipo', buttons:['Casamento','Aniversario','Corporativo','Formatura','Outro'] },
                    { prompt:'Numero estimado de convidados?', save_to:'convidados' },
                    { prompt:'Data prevista?', save_to:'data_evento' },
                    { prompt:'Orcamento estimado?', save_to:'orcamento' }
                ],
                'Seu evento vai ser incrivel! Entraremos em contato.', []
            );
            return { id:'events', name:'Eventos / Festas', category:'Eventos', description:'Tipo, convidados, data, orcamento.', icon:'bi-calendar-event', color:'#c026d3', tags:'evento festa casamento aniversario formatura buffet decoracao', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Quer fazer um orcamento de fotografia?',
                [
                    { prompt:'Tipo de ensaio/evento?', save_to:'tipo', buttons:['Casamento','Ensaio','Aniversario','Corporativo','Produto'] },
                    { prompt:'Data estimada?', save_to:'data' },
                    { prompt:'Local (cidade/bairro)?', save_to:'local' }
                ],
                'Orcamento de fotografia solicitado!', []
            );
            return { id:'photography', name:'Fotografia / Video', category:'Eventos', description:'Tipo de ensaio, data, local.', icon:'bi-camera', color:'#a21caf', tags:'fotografia foto video ensaio casamento evento fotografo', steps:f.steps, variables:f.variables };
        })(),
        // ─── Turismo ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Vamos planejar sua viagem dos sonhos?',
                [
                    { prompt:'Destino desejado?', save_to:'destino' },
                    { prompt:'Quantas pessoas?', save_to:'pessoas', buttons:['1','2','3-4','5+'] },
                    { prompt:'Periodo da viagem?', save_to:'periodo' },
                    { prompt:'Seu email:', save_to:'email', type:'email' }
                ],
                'Pacote em analise! Enviaremos opcoes por email.', []
            );
            return { id:'travel', name:'Agencia de Viagem', category:'Turismo', description:'Destino, pessoas, periodo.', icon:'bi-airplane', color:'#0284c7', tags:'viagem turismo agencia pacote hotel passagem destino', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Bem-vindo ao nosso hotel/pousada.',
                [
                    { prompt:'Tipo de acomodacao?', save_to:'tipo', buttons:['Standard','Luxo','Suite','Chalé'] },
                    { prompt:'Datas de check-in e check-out?', save_to:'datas' },
                    { prompt:'Quantos hospedes?', save_to:'hospedes', buttons:['1','2','3-4','5+'] }
                ],
                'Reserva solicitada! Confirmaremos disponibilidade.', []
            );
            return { id:'hotel', name:'Hotel / Pousada', category:'Turismo', description:'Acomodacao, datas, hospedes.', icon:'bi-building-check', color:'#0369a1', tags:'hotel pousada hospedagem reserva suite chale', steps:f.steps, variables:f.variables };
        })(),
        // ─── Financeiro ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Precisa de credito ou financiamento?',
                [
                    { prompt:'Qual o objetivo?', save_to:'objetivo', buttons:['Credito pessoal','Financiamento imovel','Financiamento veiculo','Consignado','Outro'] },
                    { prompt:'Valor estimado?', save_to:'valor' },
                    { prompt:'Seu email:', save_to:'email', type:'email' }
                ],
                'Simulacao solicitada! Enviaremos as opcoes.', []
            );
            return { id:'finance', name:'Credito / Financiamento', category:'Financeiro', description:'Tipo de credito, valor estimado.', icon:'bi-cash-stack', color:'#15803d', tags:'credito financiamento banco emprestimo consignado', steps:f.steps, variables:f.variables };
        })(),
        // ─── Construcao ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Precisa de materiais de construcao?',
                [
                    { prompt:'Tipo de obra?', save_to:'tipo_obra', buttons:['Construcao nova','Reforma','Acabamento','Manutencao'] },
                    { prompt:'Quais materiais precisa?', save_to:'materiais' },
                    { prompt:'CEP ou cidade para entrega?', save_to:'local' }
                ],
                'Orcamento solicitado! Entraremos em contato.', []
            );
            return { id:'construction', name:'Materiais de Construcao', category:'Construcao', description:'Tipo de obra, materiais, entrega.', icon:'bi-bricks', color:'#b45309', tags:'construcao material obra cimento tijolo reforma acabamento', steps:f.steps, variables:f.variables };
        })(),
        (function() {
            var f = buildLeadFlow(
                'Ola! Vamos projetar o espaco dos seus sonhos.',
                [
                    { prompt:'Tipo de projeto?', save_to:'tipo', buttons:['Residencial','Comercial','Interiores','Paisagismo'] },
                    { prompt:'Tamanho estimado (m2)?', save_to:'tamanho' },
                    { prompt:'Cidade/regiao?', save_to:'local' }
                ],
                'Projeto em analise! Agendaremos uma reuniao.', []
            );
            return { id:'architect', name:'Arquitetura / Design', category:'Construcao', description:'Tipo de projeto, tamanho, local.', icon:'bi-rulers', color:'#a16207', tags:'arquitetura design interiores projeto decoracao paisagismo', steps:f.steps, variables:f.variables };
        })(),
        // ─── Energia Solar ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Quer economizar na conta de luz com energia solar?',
                [
                    { prompt:'Valor medio da conta de luz?', save_to:'conta_luz' },
                    { prompt:'Tipo de imovel?', save_to:'tipo', buttons:['Residencial','Comercial','Rural','Industrial'] },
                    { prompt:'Cidade?', save_to:'cidade' }
                ],
                'Simulacao solicitada! Enviaremos a proposta.', []
            );
            return { id:'solar', name:'Energia Solar', category:'Servicos', description:'Conta de luz, tipo de imovel, cidade.', icon:'bi-sun', color:'#eab308', tags:'energia solar fotovoltaica painel placa economia luz', steps:f.steps, variables:f.variables };
        })(),
        // ─── Marketing ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Quer impulsionar sua presenca digital?',
                [
                    { prompt:'O que voce precisa?', save_to:'servico', buttons:['Gestao de redes sociais','Trafego pago','Criacao de site','Branding','Outro'] },
                    { prompt:'Site ou Instagram da empresa (se tiver):', save_to:'site' },
                    { prompt:'Seu email:', save_to:'email', type:'email' }
                ],
                'Otimo! Vamos criar uma estrategia pra voce.', []
            );
            return { id:'marketing_agency', name:'Agencia de Marketing', category:'Tecnologia', description:'Servico digital, site, contato.', icon:'bi-megaphone', color:'#7c3aed', tags:'marketing agencia trafego pago redes sociais site digital', steps:f.steps, variables:f.variables };
        })(),
        // ─── Moda ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Bem-vindo a nossa loja!',
                [
                    { prompt:'O que procura?', save_to:'interesse', buttons:['Roupas femininas','Roupas masculinas','Acessorios','Calcados'] },
                    { prompt:'Algum tamanho especifico?', save_to:'tamanho' },
                    { prompt:'Seu email para receber novidades:', save_to:'email', type:'email' }
                ],
                'Obrigado! Enviaremos novidades e ofertas.', []
            );
            return { id:'fashion', name:'Loja de Roupas / Moda', category:'Varejo', description:'Interesse, tamanho, novidades.', icon:'bi-handbag', color:'#be185d', tags:'roupa moda loja calcado acessorio feminino masculino', steps:f.steps, variables:f.variables };
        })(),
        // ─── Joias ───
        (function() {
            var f = buildLeadFlow(
                'Ola! Procurando joias ou semi-joias?',
                [
                    { prompt:'Tipo de peca?', save_to:'tipo', buttons:['Anel','Brinco','Colar','Pulseira','Alianca','Outro'] },
                    { prompt:'E para presente ou uso proprio?', save_to:'finalidade', buttons:['Presente','Uso proprio'] },
                    { prompt:'Faixa de preco?', save_to:'preco', buttons:['Ate R$200','R$200-500','R$500-1000','Acima R$1000'] }
                ],
                'Vamos encontrar a peca perfeita!', []
            );
            return { id:'jewelry', name:'Joalheria', category:'Varejo', description:'Tipo de peca, finalidade, preco.', icon:'bi-gem', color:'#9333ea', tags:'joia semi-joia anel alianca brinco colar pulseira presente', steps:f.steps, variables:f.variables };
        })(),
    ];

    // ── Categories & Search ──
    var _tplActiveCategory = CBLANG.tpl_category_all;

    function getTemplateCategories() {
        var cats = [CBLANG.tpl_category_all];
        BOT_TEMPLATES.forEach(function(t) {
            if (cats.indexOf(t.category) === -1) cats.push(t.category);
        });
        return cats;
    }

    function renderTemplateTabs() {
        var container = document.getElementById('tplCategoryTabs');
        if (!container) return;
        container.innerHTML = '';
        var cats = getTemplateCategories();
        cats.forEach(function(cat) {
            var btn = document.createElement('button');
            btn.textContent = cat;
            btn.style.cssText = 'padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;border:1.5px solid #e8eaf0;background:#fff;color:#6b7280;cursor:pointer;transition:all .15s;font-family:inherit;white-space:nowrap;flex-shrink:0;';
            if (cat === _tplActiveCategory) {
                btn.style.background = '#0085f3';
                btn.style.color = '#fff';
                btn.style.borderColor = '#0085f3';
            }
            btn.onclick = function() {
                _tplActiveCategory = cat;
                renderTemplateTabs();
                renderTemplateGrid(document.getElementById('tplSearch').value);
            };
            container.appendChild(btn);
        });
        setTimeout(updateTabArrows, 20);
    }

    function renderTemplateGrid(searchText) {
        var grid = document.getElementById('templatesGrid');
        var empty = document.getElementById('tplEmpty');
        if (!grid) return;
        grid.innerHTML = '';
        var q = (searchText || '').toLowerCase().trim();

        var filtered = BOT_TEMPLATES.filter(function(tpl) {
            if (_tplActiveCategory !== CBLANG.tpl_category_all && tpl.category !== _tplActiveCategory) return false;
            if (!q) return true;
            var haystack = (tpl.name + ' ' + tpl.description + ' ' + (tpl.tags || '') + ' ' + tpl.category).toLowerCase();
            return haystack.indexOf(q) !== -1;
        });

        if (empty) empty.style.display = filtered.length === 0 ? 'block' : 'none';
        grid.style.display = filtered.length === 0 ? 'none' : 'grid';

        filtered.forEach(function(tpl) {
            var card = document.createElement('div');
            card.style.cssText = 'border:1.5px solid #e8eaf0;border-radius:12px;padding:16px 14px;cursor:pointer;transition:all .15s;background:#fff;';
            card.onmouseenter = function() { card.style.borderColor = '#0085f3'; card.style.background = '#f8faff'; };
            card.onmouseleave = function() { card.style.borderColor = '#e8eaf0'; card.style.background = '#fff'; };
            card.innerHTML =
                '<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">' +
                    '<div style="width:34px;height:34px;border-radius:9px;background:#eff6ff;color:#0085f3;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;"><i class="bi ' + tpl.icon + '"></i></div>' +
                    '<div style="min-width:0;"><span style="font-size:13.5px;font-weight:700;color:#1a1d23;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + tpl.name + '</span>' +
                    '<span style="font-size:11px;color:#9ca3af;">' + tpl.category + '</span></div>' +
                '</div>' +
                '<p style="font-size:12px;color:#6b7280;margin:0 0 8px;line-height:1.4;">' + tpl.description + '</p>' +
                '<div style="font-size:11px;color:#b0b5bf;">' + tpl.steps.length + ' ' + CBLANG.tpl_nodes + ' · ' + (tpl.variables ? tpl.variables.length : 0) + ' ' + CBLANG.tpl_variables + '</div>';
            card.onclick = function() { loadTemplate(tpl); };
            grid.appendChild(card);
        });
    }

    window.filterTemplates = function(val) {
        renderTemplateGrid(val);
    };

    function updateTabArrows() {
        var tabs = document.getElementById('tplCategoryTabs');
        var btnL = document.getElementById('tplTabLeft');
        var btnR = document.getElementById('tplTabRight');
        if (!tabs || !btnL || !btnR) return;
        var canScrollLeft = tabs.scrollLeft > 4;
        var canScrollRight = tabs.scrollLeft + tabs.clientWidth < tabs.scrollWidth - 4;
        btnL.style.display = canScrollLeft ? 'inline-flex' : 'none';
        btnR.style.display = canScrollRight ? 'inline-flex' : 'none';
    }

    // Attach scroll listener once DOM is ready
    (function() {
        var tabs = document.getElementById('tplCategoryTabs');
        if (tabs) tabs.addEventListener('scroll', updateTabArrows);
    })();

    window.showTemplatesModal = function() {
        var modal = document.getElementById('templatesModal');
        if (!modal) return;
        var search = document.getElementById('tplSearch');
        if (search) search.value = '';
        _tplActiveCategory = CBLANG.tpl_category_all;
        renderTemplateTabs();
        renderTemplateGrid('');
        modal.style.display = 'flex';
        setTimeout(updateTabArrows, 50);
    };

    function loadTemplate(tpl) {
        var hasSteps = flowSteps.length > 0;
        if (hasSteps && !confirm(CBLANG.tpl_confirm_replace)) return;

        // Deep-clone steps with fresh IDs
        var newSteps = JSON.parse(JSON.stringify(tpl.steps));
        assignFreshIds(newSteps);
        flowSteps.length = 0;
        newSteps.forEach(function(s) { flowSteps.push(s); });

        // Load template variables
        if (tpl.variables) {
            flowVariables.length = 0;
            tpl.variables.forEach(function(v) { flowVariables.push({ name: v.name, default: v.default || '' }); });
        }

        document.getElementById('templatesModal').style.display = 'none';
        renderFlow();
        toastr.success(CBLANG.tpl_loaded.replace(':name', tpl.name));
    }

    function assignFreshIds(steps) {
        steps.forEach(function(s) {
            s.id = genId();
            if (s.branches) {
                s.branches.forEach(function(b) {
                    b.id = genId();
                    if (b.steps) assignFreshIds(b.steps);
                });
            }
            if (s.default_branch && s.default_branch.steps) {
                assignFreshIds(s.default_branch.steps);
            }
        });
    }

    // ── Center canvas on first node ──────────────────────────────────
    window.centerCanvas = function() {
        var canvas = document.querySelector('.cb-canvas');
        var firstNode = canvas.querySelector('.cb-node');
        if (canvas && firstNode) {
            firstNode.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'center' });
        }
    };

    // ── Init ─────────────────────────────────────────────────────────
    renderFlow();
    setTimeout(centerCanvas, 100);

    // ── Test Widget ──────────────────────────────────────────────────
    var _testWidgetActive = false;

    window.openTestWidget = async function() {
        // Salva o fluxo silenciosamente antes de testar
        await saveFlow(true);

        if (_testWidgetActive) {
            closeTestWidget();
            return;
        }

        // Limpar visitor ID para começar conversa nova
        var vkey = 'syncro_vid_' + '{{ $flow->website_token }}';
        localStorage.removeItem(vkey);

        // Injetar script do widget
        var s = document.createElement('script');
        s.id = 'syncro-test-widget';
        s.src = '{{ config("app.url") }}/api/widget/{{ $flow->website_token }}.js?' + Date.now() + '&force_bubble=1';
        document.body.appendChild(s);
        _testWidgetActive = true;
    };

    window.closeTestWidget = function() {
        ['syncro-launcher', 'syncro-panel', 'syncro-welcome', 'syncro-test-widget'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.remove();
        });
        // Remove style injetado pelo widget
        document.querySelectorAll('style').forEach(function(s) {
            if (s.textContent && s.textContent.indexOf('#syncro-launcher') !== -1) s.remove();
        });
        _testWidgetActive = false;
    };

    // ── Embed Modal ──────────────────────────────────────────────────
    window.showEmbedModal = function() {
        var modal = document.getElementById('embedModal');
        if (modal) modal.style.display = 'flex';
    };

    window.copyEmbed = function() {
        var textarea = document.getElementById('embedCode');
        if (textarea) {
            navigator.clipboard.writeText(textarea.value.trim()).then(function() {
                var msg = document.getElementById('embedCopied');
                if (msg) {
                    msg.style.display = 'inline-flex';
                    setTimeout(function() { msg.style.display = 'none'; }, 2500);
                }
            });
        }
    };

    // ── Drag-to-scroll (pan) on canvas ──────────────────────────────
    (function() {
        var canvas = document.querySelector('.cb-canvas');
        if (!canvas) return;
        var isDown = false, startX, startY, scrollLeft, scrollTop;
        canvas.addEventListener('mousedown', function(e) {
            if (e.target.closest('.cb-node, .cb-add-step, .cb-add-branch-col, .cb-branch-col, button, a, input, select, textarea, .cb-editable, .cb-var-hint')) return;
            isDown = true;
            startX = e.pageX; startY = e.pageY;
            scrollLeft = canvas.scrollLeft; scrollTop = canvas.scrollTop;
            canvas.classList.add('is-panning');
        });
        canvas.addEventListener('mouseleave', stop);
        canvas.addEventListener('mouseup', stop);
        canvas.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            canvas.scrollLeft = scrollLeft - (e.pageX - startX);
            canvas.scrollTop = scrollTop - (e.pageY - startY);
        });
        function stop() { isDown = false; canvas.classList.remove('is-panning'); }
    })();

    // ── Zoom via Ctrl+Wheel ─────────────────────────────────────────
    (function() {
        var canvas = document.querySelector('.cb-canvas');
        var flow   = document.getElementById('cbFlow');
        if (!canvas || !flow) return;
        var zoomLevel = 1;
        var MIN_ZOOM = 0.3, MAX_ZOOM = 1.5;

        canvas.addEventListener('wheel', function(e) {
            if (!e.ctrlKey && !e.metaKey) return;
            e.preventDefault();
            var delta = e.deltaY > 0 ? -0.05 : 0.05;
            zoomLevel = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, zoomLevel + delta));
            flow.style.transform = 'scale(' + zoomLevel + ')';
        }, { passive: false });

        window.zoomIn    = function() { zoomLevel = Math.min(MAX_ZOOM, zoomLevel + 0.1); flow.style.transform = 'scale(' + zoomLevel + ')'; };
        window.zoomOut   = function() { zoomLevel = Math.max(MIN_ZOOM, zoomLevel - 0.1); flow.style.transform = 'scale(' + zoomLevel + ')'; };
        window.zoomReset = function() { zoomLevel = 1; flow.style.transform = 'scale(1)'; };
    })();

})();
</script>
@endpush
