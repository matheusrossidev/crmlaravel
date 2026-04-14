@extends('tenant.layouts.app')
@php
$title = __('chat.title');
$pageIcon = 'chat-dots';
@endphp

@push('styles')
<style>
    /* ── Layout geral ── */
    .wa-page {
        display: flex;
        height: calc(100vh - 64px);
        overflow: hidden;
        background: #f4f6fb;
    }

    /* ── Empty state ── */
    .wa-empty-state {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
        color: #6b7280;
        text-align: center;
        padding: 40px;
    }

    .wa-empty-state .wa-icon-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #eff6ff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        color: #3b82f6;
    }

    .wa-empty-state h3 {
        font-size: 20px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0;
    }

    .wa-empty-state p {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
        max-width: 360px;
    }

    .btn-go-integrations {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 22px;
        background: #3b82f6;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: background .15s;
        cursor: pointer;
    }

    .btn-go-integrations:hover {
        background: #2563eb;
        color: #fff;
    }

    /* ── Sidebar de conversas ── */
    .wa-sidebar {
        width: 320px;
        flex-shrink: 0;
        background: #fff;
        border-right: 1px solid #e8eaf0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .wa-sidebar-header {
        padding: 16px 16px 12px;
        border-bottom: 1px solid #f0f2f7;
        flex-shrink: 0;
    }

    .wa-sidebar-title {
        font-size: 15px;
        font-weight: 700;
        color: #1a1d23;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .wa-sidebar-title .wa-badge {
        font-size: 11px;
        font-weight: 700;
        background: #EF4444;
        color: #fff;
        border-radius: 99px;
        padding: 2px 7px;
    }

    .wa-search {
        position: relative;
    }

    .wa-search i {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 14px;
    }

    .wa-search input {
        width: 100%;
        padding: 8px 10px 8px 32px;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        font-size: 13px;
        color: #1a1d23;
        background: #f8fafc;
        outline: none;
        transition: border-color .15s;
    }

    .wa-search input:focus {
        border-color: #3b82f6;
        background: #fff;
    }

    .wa-channel-tabs {
        display: flex;
        border-bottom: 1.5px solid #f0f0f0;
        padding: 0 8px;
        flex-shrink: 0;
    }

    .wa-channel-tab {
        flex: 1;
        padding: 9px 4px;
        background: none;
        border: none;
        border-bottom: 2.5px solid transparent;
        margin-bottom: -1.5px;
        font-size: 11.5px;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        transition: color .15s, border-color .15s;
        font-family: inherit;
    }

    .wa-channel-tab:hover { color: #374151; }

    .wa-channel-tab.active {
        color: #3b82f6;
        border-bottom-color: #3b82f6;
    }

    .wa-channel-tab[data-channel="whatsapp"].active { color: #16a34a; border-bottom-color: #16a34a; }
    .wa-channel-tab[data-channel="instagram"].active { color: #d946ef; border-bottom-color: #d946ef; }

    .wa-filters {
        display: flex;
        gap: 4px;
        padding: 10px 16px 0;
        flex-shrink: 0;
    }

    .wa-filter-btn {
        padding: 5px;
        border-radius: 99px;
        font-size: 10px;
        font-weight: 600;
        border: 1.5px solid #e8eaf0;
        background: #fff;
        color: #6b7280;
        cursor: pointer;
        transition: all .15s;
    }

    .wa-filter-btn.active {
        background: #3b82f6;
        border-color: #3b82f6;
        color: #fff;
    }

    .wa-conv-list {
        flex: 1;
        overflow-y: auto;
        padding: 8px 0;
    }

    .wa-conv-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 11px 16px;
        cursor: pointer;
        transition: background .12s;
        border-bottom: 1px solid #f9fafb;
    }

    .wa-conv-item:hover {
        background: #f8fafc;
    }

    .wa-conv-item.active {
        background: #eff6ff;
        border-left: 3px solid #3b82f6;
    }

    /* Avatar com indicador de canal */
    .wa-conv-avatar-wrap {
        position: relative;
        flex-shrink: 0;
    }

    .wa-conv-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
        font-weight: 700;
        overflow: hidden;
    }

    .wa-conv-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Ícone de canal no canto do avatar */
    .wa-channel-icon {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        border: 1.5px solid #fff;
        color: #fff;
    }

    .wa-channel-icon.whatsapp {
        background: #25D366;
    }

    .wa-channel-icon.instagram {
        background: linear-gradient(135deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
    }

    .wa-conv-info {
        flex: 1;
        min-width: 0;
    }

    .wa-conv-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 3px;
    }

    .wa-conv-name {
        font-size: 13.5px;
        font-weight: 600;
        color: #1a1d23;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 160px;
    }

    .wa-conv-time {
        font-size: 11px;
        color: #9ca3af;
        flex-shrink: 0;
    }

    .wa-conv-preview {
        font-size: 12.5px;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
    }

    .wa-conv-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 3px;
    }

    .wa-unread-dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #3b82f6;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .wa-conv-assignee {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #6b7280;
        font-size: 8px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-left: auto;
        overflow: hidden;
    }
    .wa-conv-assignee img {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        object-fit: cover;
    }

    .wa-conv-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 3px;
        margin-top: 4px;
    }

    .wa-tag {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 1px 7px;
        background: #eff6ff;
        color: #2563eb;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        white-space: nowrap;
    }

    .wa-tag .wa-tag-remove {
        cursor: pointer;
        font-size: 11px;
        line-height: 1;
        color: #93c5fd;
    }

    .wa-tag .wa-tag-remove:hover {
        color: #1d4ed8;
    }

    /* ── Área principal de chat ── */
    .wa-chat-area {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #f4f6fb;
    }

    .wa-no-conv {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        color: #9ca3af;
    }

    .wa-no-conv i {
        font-size: 56px;
        opacity: .3;
        color: #3b82f6;
    }

    .wa-no-conv p {
        font-size: 14px;
    }

    /* ── Chat Header ── */
    .wa-chat-header {
        background: #fff;
        border-bottom: 1px solid #e8eaf0;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }

    .wa-chat-contact-name {
        font-size: 14.5px;
        font-weight: 700;
        color: #1a1d23;
    }

    .wa-chat-contact-phone {
        font-size: 12px;
        color: #9ca3af;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* Canal no header */
    .wa-header-channel {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 99px;
        color: #fff;
    }

    .wa-header-channel.whatsapp {
        background: #25D366;
    }

    .wa-header-channel.instagram {
        background: linear-gradient(135deg, #f09433, #dc2743);
    }

    .wa-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #3b82f6;
        flex-shrink: 0;
    }

    .wa-chat-actions {
        margin-left: auto;
        display: flex;
        gap: 8px;
    }

    .wa-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1.5px solid #e8eaf0;
        background: #fff;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        cursor: pointer;
        transition: all .15s;
    }

    .wa-action-btn:hover {
        background: #f4f6fb;
        color: #1a1d23;
    }

    .wa-action-btn.danger:hover {
        background: #fef2f2;
        color: #EF4444;
        border-color: #fecaca;
    }

    /* ── Messages area ── */
    .wa-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .wa-date-sep {
        text-align: center;
        font-size: 11.5px;
        color: #9ca3af;
        font-weight: 600;
        margin: 12px 0 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .wa-date-sep::before,
    .wa-date-sep::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e8eaf0;
    }

    /* Balões de mensagem */
    .wa-msg {
        display: flex;
        flex-direction: column;
        max-width: 65%;
        margin-bottom: 2px;
        position: relative;
    }

    .wa-msg.inbound {
        align-self: flex-start;
        align-items: flex-start;
    }

    .wa-msg.outbound {
        align-self: flex-end;
        align-items: flex-end;
    }

    .wa-msg.note, .wa-msg.event {
        align-self: center;
        width: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
    }
    .wa-msg.note::before, .wa-msg.event::before,
    .wa-msg.note::after, .wa-msg.event::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e5e7eb;
    }

    .wa-bubble {
        padding: 8px 12px;
        border-radius: 12px;
        font-size: 13.5px;
        line-height: 1.5;
        color: #1a1d23;
        word-break: break-word;
        position: relative;
    }

    .wa-msg.inbound .wa-bubble {
        background: #fff;
        border-radius: 2px 12px 12px 12px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .06);
    }

    .wa-msg.outbound .wa-bubble {
        background: #3b82f6;
        border-radius: 12px 2px 12px 12px;
        color: #fff;
    }

    .wa-msg.outbound .wa-msg-meta { color: rgb(0 0 0 / 40%); }
    .wa-msg.outbound .wa-ack.read i { color: rgba(255,255,255,.9); }

    .wa-msg.note .wa-bubble { display: none; }

    .wa-bubble.deleted {
        font-style: italic;
        color: #9ca3af;
        font-size: 12.5px;
    }

    /* ── Author badge (sent_by) ──
       Posicionado absolute pra "saltar" pra fora do canto superior da bolha,
       meio dentro meio fora — visual mais slick que badge separado em cima. */
    .wa-msg.outbound { padding-top: 10px; }
    .msg-author-badge {
        position: absolute;
        top: -2px;
        right: 6px;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 9px 3px 4px;
        border-radius: 11px;
        line-height: 1;
        max-width: fit-content;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1.5px solid #fff;
    }
    .msg-author-avatar {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }
    .msg-author-name { white-space: nowrap; }

    .msg-author-ai_agent { background: #f3e8ff; color: #7c3aed; }
    .msg-author-followup { background: #fed7aa; color: #9a3412; }
    .msg-author-chatbot { background: #e0e7ff; color: #4338ca; padding-left: 9px; }
    .msg-author-automation { background: #d1fae5; color: #065f46; padding-left: 9px; }
    .msg-author-scheduled { background: #dbeafe; color: #1e40af; padding-left: 9px; }
    .msg-author-human { background: #f3f4f6; color: #374151; padding-left: 9px; }
    .msg-author-human_phone { background: #fef3c7; color: #92400e; padding-left: 9px; }
    .msg-author-event { background: #fef3c7; color: #92400e; padding-left: 9px; }

    /* Glow pulsante no primeiro render — chama atencao pra mensagem nova */
    @keyframes msg-author-pulse {
        0%, 100% { box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        50% { box-shadow: 0 0 0 6px rgba(124, 58, 237, 0.18), 0 1px 3px rgba(0,0,0,0.08); }
    }
    .msg-author-badge.fresh.msg-author-ai_agent,
    .msg-author-badge.fresh.msg-author-followup {
        animation: msg-author-pulse 1.6s ease-in-out 1;
    }

    /* Sender name label (group messages) */
    .wa-sender-label {
        font-size: 11px;
        font-weight: 700;
        color: #6366f1;
        margin-bottom: 2px;
        padding-left: 2px;
    }

    /* Metadados da mensagem */
    .wa-msg-meta {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 10.5px;
        color: #9ca3af;
        margin-top: 2px;
        padding: 0 4px;
    }

    .wa-ack i {
        font-size: 12px;
    }

    .wa-ack.read i {
        color: #3b82f6;
    }

    /* Timeline entries (eventos + notas) */
    .wa-event-box {
        display: flex; align-items: center; gap: 8px;
        flex-shrink: 0; white-space: nowrap;
    }
    .wa-event-icon {
        font-size: 16px; flex-shrink: 0; line-height: 1;
    }
    .wa-event-box.ai .wa-event-icon { color: #10b981; }
    .wa-event-box.user .wa-event-icon { color: #3b82f6; }
    .wa-event-box.note-type .wa-event-icon { color: #d97706; }
    .wa-event-content { flex-shrink: 0; }
    .wa-event-title { font-size: 12px; font-weight: 700; color: #1a1d23; }
    .wa-event-desc { font-size: 11px; color: #6b7280; line-height: 1.3; white-space: normal; }
    .wa-event-time { font-size: 10px; color: #9ca3af; }

    /* Reações */
    .wa-reactions {
        margin-top: 2px;
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }

    .wa-reaction-pill {
        background: rgba(255, 255, 255, .9);
        border: 1px solid #e8eaf0;
        border-radius: 99px;
        padding: 1px 6px;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 3px;
        cursor: pointer;
    }

    .wa-reaction-pill span {
        font-size: 10px;
        color: #6b7280;
    }

    /* Imagem */
    .wa-img-thumb {
        max-width: 220px;
        max-height: 200px;
        border-radius: 8px;
        cursor: pointer;
        object-fit: cover;
        display: block;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .wa-img-thumb:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 14px rgba(0, 0, 0, .15);
    }

    /* ── Documento (card) ─────────────────────────────────────────── */
    /* Default = estilo outbound (bubble azul). Inbound sobrescreve abaixo. */
    .wa-doc-card {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: rgba(255, 255, 255, .15);
        border-radius: 10px;
        min-width: 240px;
        max-width: 320px;
        color: #fff;
    }
    .wa-msg.inbound .wa-doc-card {
        background: rgba(59, 130, 246, .08);
        color: #1a1d23;
    }
    .wa-doc-icon {
        width: 42px;
        height: 42px;
        border-radius: 9px;
        background: rgba(255, 255, 255, .25);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #fff;
        flex-shrink: 0;
    }
    .wa-msg.inbound .wa-doc-icon {
        background: #eff6ff;
        color: #3b82f6;
    }
    .wa-doc-info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .wa-doc-name {
        font-size: 13px;
        font-weight: 600;
        color: inherit;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.3;
    }
    .wa-doc-meta {
        font-size: 11px;
        opacity: .8;
        line-height: 1.3;
        color: inherit;
    }
    .wa-doc-download {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: rgba(255, 255, 255, .25);
        border: none;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 16px;
        flex-shrink: 0;
        text-decoration: none;
        transition: background .12s, transform .12s;
    }
    .wa-doc-download:hover {
        background: rgba(255, 255, 255, .4);
        transform: scale(1.05);
    }
    .wa-msg.inbound .wa-doc-download {
        background: #3b82f6;
        color: #fff;
    }
    .wa-msg.inbound .wa-doc-download:hover { background: #2563eb; }
    .wa-doc-caption {
        margin-top: 8px;
        font-size: 13px;
        line-height: 1.5;
        word-wrap: break-word;
        color: inherit;
    }
    .wa-doc-caption a { color: inherit; text-decoration: underline; }

    /* ── Image Lightbox Modal ─────────────────────────────────────── */
    .wa-img-lightbox {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .85);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        animation: waImgFadeIn .15s ease-out;
    }
    .wa-img-lightbox.open { display: flex; }
    .wa-img-lightbox-content {
        position: relative;
        max-width: 100%;
        max-height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .wa-img-lightbox-img {
        max-width: 100%;
        max-height: calc(100vh - 100px);
        border-radius: 8px;
        box-shadow: 0 12px 40px rgba(0, 0, 0, .5);
        object-fit: contain;
    }
    .wa-img-lightbox-close,
    .wa-img-lightbox-download {
        position: absolute;
        top: -42px;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .15);
        border: none;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 18px;
        transition: background .12s;
        text-decoration: none;
    }
    .wa-img-lightbox-close { right: 0; }
    .wa-img-lightbox-download { right: 46px; font-size: 16px; }
    .wa-img-lightbox-close:hover,
    .wa-img-lightbox-download:hover {
        background: rgba(255, 255, 255, .3);
        color: #fff;
    }
    @keyframes waImgFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @media (max-width: 640px) {
        .wa-img-lightbox { padding: 60px 12px 12px; }
        .wa-img-lightbox-close { top: 12px; right: 12px; }
        .wa-img-lightbox-download { top: 12px; right: 58px; }
    }

    /* Vídeo */
    .wa-bubble video {
        max-width: 280px;
        border-radius: 8px;
        display: block;
    }

    /* ── Custom Audio Player ── */
    .wa-audio-player {
        display: flex;
        flex-direction: column;
        gap: 4px;
        background: #fff;
        border-radius: 10px;
        padding: 10px 12px;
        min-width: 220px;
        max-width: 280px;
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
    }

    .ap-top-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .ap-play-btn {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #3b82f6;
        border: none;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        font-size: 14px;
        transition: background .15s;
    }
    .ap-play-btn:hover { background: #2563eb; }
    .ap-play-btn i { pointer-events: none; }

    .ap-waveform {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 2px;
        height: 32px;
        cursor: pointer;
    }

    .ap-bar {
        flex: 1;
        border-radius: 2px;
        background: #d1d5db;
        transition: background .1s;
        min-height: 4px;
    }

    .ap-bar.played { background: #3b82f6; }

    @keyframes ap-pulse {
        0%, 100% { opacity: 1; }
        50%       { opacity: 0.5; }
    }
    .ap-bar.playing { animation: ap-pulse .8s ease-in-out infinite; }

    .ap-bottom-row {
        display: flex;
        align-items: center;
        gap: 6px;
        padding-left: 44px;
    }

    .ap-timer {
        font-size: 11px;
        font-weight: 600;
        color: #6b7280;
        min-width: 28px;
    }

    .ap-label {
        font-size: 10.5px;
        color: #9ca3af;
    }

    /* ── Footer de composição ── */
    .wa-compose-area {
        background: #fff;
        border-top: 1px solid #e8eaf0;
        padding: 12px 16px;
        padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px));
        flex-shrink: 0;
    }

    .wa-compose-tabs {
        display: flex;
        gap: 0;
        margin-bottom: 10px;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        overflow: hidden;
        width: fit-content;
    }

    .wa-tab-btn {
        padding: 6px 16px;
        font-size: 12.5px;
        font-weight: 600;
        color: #6b7280;
        background: #fff;
        border: none;
        cursor: pointer;
        transition: all .15s;
    }

    .wa-tab-btn.active {
        background: #3b82f6;
        color: #fff;
    }

    .wa-tab-btn:not(.active):hover {
        background: #f4f6fb;
    }

    .wa-compose-row {
        display: flex;
        gap: 8px;
    }

    .wa-textarea-wrap {
        flex: 1;
    }

    .wa-textarea {
        width: 100%;
        min-height: 40px;
        max-height: 120px;
        resize: none;
        border: 1.5px solid #e8eaf0;
        border-radius: 10px;
        padding: 9px 12px;
        font-size: 13.5px;
        font-family: 'Inter', sans-serif;
        color: #1a1d23;
        background: #fafafa;
        outline: none;
        line-height: 1.5;
        overflow-y: auto;
        transition: border-color .15s;
    }

    .wa-textarea:focus {
        border-color: #3b82f6;
        background: #fff;
    }

    .wa-textarea.note-mode {
        border-color: #F59E0B;
        background: #fffbeb;
    }

    .wa-btn-icon {
        width: 38px;
        height: 38px;
        border: 1.5px solid #e8eaf0;
        border-radius: 9px;
        background: #fff;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
        transition: all .15s;
        flex-shrink: 0;
    }

    .wa-btn-icon:hover {
        background: #f4f6fb;
        color: #1a1d23;
    }

    .wa-attach-item {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 500;
        color: #374151;
        background: none;
        border: none;
        cursor: pointer;
        transition: background .1s;
        text-align: left;
    }
    .wa-attach-item:hover { background: #f4f6fb; }
    .wa-attach-item i { font-size: 16px; width: 20px; text-align: center; }

    .wa-btn-send {
        width: 38px;
        height: 38px;
        border: none;
        border-radius: 9px;
        background: #3b82f6;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
        transition: background .15s;
        flex-shrink: 0;
    }

    .wa-btn-send:hover {
        background: #2563eb;
    }

    .wa-btn-send:disabled {
        background: #bfdbfe;
        cursor: not-allowed;
    }

    /* Gravação de áudio */
    .wa-recording-indicator {
        display: none;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #EF4444;
        font-weight: 600;
        flex: 1;
    }

    .wa-recording-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #EF4444;
        animation: pulse 1s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: .3;
        }
    }

    /* ── Painel de detalhes ── */
    .wa-details {
        width: 260px;
        flex-shrink: 0;
        background: #fff;
        border-left: 1px solid #e8eaf0;
        overflow-y: auto;
        display: none;
        flex-direction: column;
        gap: 0;
    }

    .wa-details.open {
        display: flex;
    }

    .wa-details-section {
        padding: 16px;
        border-bottom: 1px solid #f0f2f7;
    }

    .wa-details-label {
        font-size: 11px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 8px;
    }

    .wa-details-value {
        font-size: 13px;
        color: #374151;
    }

    /* Emoji picker simples */
    .wa-emoji-picker {
        display: none;
        position: absolute;
        bottom: calc(100% + 6px);
        right: 0;
        background: #fff;
        border: 1px solid #e8eaf0;
        border-radius: 10px;
        padding: 8px;
        gap: 4px;
        flex-wrap: wrap;
        width: 160px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, .1);
        z-index: 200;
    }

    .wa-emoji-picker.open {
        display: flex;
    }

    .wa-emoji-opt {
        font-size: 20px;
        cursor: pointer;
        padding: 2px;
        border-radius: 4px;
    }

    .wa-emoji-opt:hover {
        background: #f4f6fb;
    }

    /* WebSocket status indicator */
    .ws-status {
        position: fixed;
        bottom: 16px;
        left: 50%;
        transform: translateX(-50%);
        padding: 6px 16px;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 600;
        color: #fff;
        z-index: 9999;
        display: none;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
    }

    .ws-status.connecting {
        background: #f59e0b;
        display: flex;
    }

    .ws-status.error {
        background: #ef4444;
        display: flex;
    }

    /* ── Vincular/Desvincular Lead ────────────────────────────────────── */
    .unlink-lead-btn {
        background: none; border: none; cursor: pointer;
        padding: 2px 0; font-size: 11px; color: #ef4444;
        display: flex; align-items: center; gap: 3px;
        font-family: inherit;
    }
    .unlink-lead-btn:hover { color: #dc2626; }

    .btn-link-existing {
        width: 100%; padding: 6px 10px; background: #f9fafb;
        border: 1.5px dashed #d1d5db; border-radius: 9px;
        font-size: 12px; color: #374151; cursor: pointer;
        display: flex; align-items: center; gap: 6px;
        justify-content: center; font-family: inherit;
        transition: border-color .15s, color .15s;
    }
    .btn-link-existing:hover { border-color: #3b82f6; color: #3b82f6; }

    .lead-search-item {
        display: flex; align-items: center; justify-content: space-between;
        padding: 6px 10px; border-radius: 7px; cursor: pointer;
        font-size: 12.5px; transition: background .15s;
    }
    .lead-search-item:hover { background: #f3f4f6; }
    .lead-search-name { font-weight: 600; color: #111827; }
    .lead-search-phone { font-size: 11px; color: #6b7280; }

    /* ── Mobile ── */
    .wa-chat-back-btn {
        display: none;
        width: 32px; height: 32px;
        border: none; background: none;
        color: #374151; font-size: 18px;
        cursor: pointer; flex-shrink: 0;
        align-items: center; justify-content: center;
    }
    @media (max-width: 768px) {
        .wa-page { position: relative; }
        .wa-sidebar { width: 100%; position: absolute; inset: 0; z-index: 10; }
        .wa-chat-area { position: absolute; inset: 0; z-index: 20; display: none; flex-direction: column; }
        .wa-chat-area.mobile-active { display: flex; }
        .wa-details { display: none !important; }
        .wa-chat-back-btn { display: flex; }
        .wa-chat-header { padding: 10px 14px; gap: 8px; }
        .wa-msg { max-width: 85%; }
    }
</style>
@endpush

@section('content')
<div class="wa-page">

    {{-- Banner de alerta quando token Cloud API expirando/expirado/inválido.
         Query esporádica (só instances cloud_api do tenant atual com status ruim).
         Se não tem nenhuma, ignora silenciosamente. --}}
    @php
        $expiringCloudInstance = \App\Models\WhatsappInstance::where('tenant_id', activeTenantId())
            ->where('provider', 'cloud_api')
            ->whereIn('token_status', ['expiring', 'expired', 'invalid'])
            ->first();
    @endphp
    @if($expiringCloudInstance)
    <div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:12px 16px;margin:14px 18px 0;display:flex;align-items:center;gap:12px;color:#991b1b;font-size:13px;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:18px;color:#dc2626;flex-shrink:0;"></i>
        <div style="flex:1;">
            <strong>WhatsApp Cloud ({{ $expiringCloudInstance->label ?: $expiringCloudInstance->phone_number }})</strong>
            @if($expiringCloudInstance->token_status === 'expired')
                expirou. Envios e recebimentos bloqueados até reconectar.
            @elseif($expiringCloudInstance->token_status === 'invalid')
                está com token inválido. Reconecte pra restabelecer.
            @else
                expira em breve. Reconecte pra evitar interrupção.
            @endif
        </div>
        <a href="{{ route('settings.integrations.index') }}" style="padding:7px 14px;background:#dc2626;color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;flex-shrink:0;">
            Reconectar
        </a>
    </div>
    @endif

    @if(! $connected)
    {{-- ── Empty State: não conectado ───────────────────────────────────────────── --}}
    <div class="wa-empty-state">
        <div class="wa-icon-circle">
            <i class="bi bi-chat-dots-fill"></i>
        </div>
        <h3>{{ __('chat.wa_not_connected') }}</h3>
        <p>{{ __('chat.wa_not_connected_desc') }}</p>
        <a href="{{ route('settings.integrations.index') }}" class="btn-go-integrations">
            <i class="bi bi-plugin"></i> {{ __('chat.go_to_integrations') }}
        </a>
    </div>

    @else
    {{-- ── Inbox ─────────────────────────────────────────────────────────────────── --}}

    {{-- Sidebar de conversas --}}
    <div class="wa-sidebar">
        <div class="wa-sidebar-header">
            <div class="wa-search">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" placeholder="{{ __('chat.search_conversation') }}">
            </div>
        </div>

        <div class="wa-channel-tabs">
            <button class="wa-channel-tab active" data-channel="all">
                <i class="bi bi-grid-3x3-gap-fill"></i> {{ __('chat.tab_all') }}
            </button>
            <button class="wa-channel-tab" data-channel="whatsapp">
                <i class="bi bi-whatsapp"></i> {{ __('chat.tab_whatsapp') }}
            </button>
            <button class="wa-channel-tab" data-channel="instagram">
                <i class="bi bi-instagram"></i> {{ __('chat.tab_instagram') }}
            </button>
        </div>

        <div class="wa-filters">
            <button class="wa-filter-btn active" data-filter="all">{{ __('chat.filter_all') }}</button>
            <button class="wa-filter-btn" data-filter="mine">{{ __('chat.filter_mine') }}</button>
            <button class="wa-filter-btn" data-filter="open">{{ __('chat.filter_open') }}</button>
            <button class="wa-filter-btn" data-filter="closed">{{ __('chat.filter_closed') }}</button>
        </div>

        <div class="wa-conv-list" id="convList">
            @forelse($allConversations as $conv)
            @php
                $ch      = $conv->_channel;
                $convName = match($ch) {
                    'instagram' => $conv->contact_name ?? $conv->contact_username ?? __('chat.ig_contact'),
                    default     => $conv->contact_name ?? ($conv->is_group ? __('chat.group') : $conv->phone),
                };
                $convPhone = match($ch) {
                    'instagram' => '@' . ltrim($conv->contact_username ?? '', '@'),
                    default     => $conv->phone ?? '',
                };
                $avatarLetter = strtoupper(substr($convName, 0, 1));
            @endphp
            <div class="wa-conv-item"
                data-conv-id="{{ $conv->id }}"
                data-phone="{{ $convPhone }}"
                data-status="{{ $conv->status }}"
                data-channel="{{ $ch }}"
                data-tags="{{ json_encode($conv->tags ?? []) }}"
                data-assigned-user-id="{{ $conv->assigned_user_id ?? '' }}"
                data-department-id="{{ $conv->department_id ?? '' }}"
                data-instance-label="{{ ($ch === 'whatsapp' && $conv->instance) ? ($conv->instance->label ?: $conv->instance->phone_number) : '' }}"
                data-picture="{{ $conv->contact_picture_url }}"
                onclick="openConversation({{ $conv->id }}, this)">
                <div class="wa-conv-avatar-wrap">
                    <div class="wa-conv-avatar">
                        @if($conv->contact_picture_url)
                        <img src="{{ $conv->contact_picture_url }}" alt="">
                        @else
                        {{ $avatarLetter }}
                        @endif
                    </div>
                    @if($ch === 'instagram')
                    <span class="wa-channel-icon instagram" title="Instagram">
                        <i class="bi bi-instagram"></i>
                    </span>
                    @else
                    <span class="wa-channel-icon whatsapp" title="WhatsApp">
                        <i class="bi bi-whatsapp"></i>
                    </span>
                    @endif
                </div>
                <div class="wa-conv-info">
                    <div class="wa-conv-top">
                        <span class="wa-conv-name">{{ $convName }}</span>
                        <span class="wa-conv-time">{{ $conv->last_message_at?->diffForHumans(short: true) }}</span>
                    </div>
                    <div class="wa-conv-bottom">
                        <span class="wa-conv-preview">
                            @if($conv->latestMessage)
                            @if($conv->latestMessage->type === 'image') {{ __('chat.preview_image') }}
                            @elseif($conv->latestMessage->type === 'share') {{ __('chat.preview_post') }}
                            @elseif($conv->latestMessage->type === 'audio') {{ __('chat.preview_audio') }}
                            @elseif($conv->latestMessage->type === 'document') 📎 {{ $conv->latestMessage->media_filename ?? __('chat.preview_file') }}
                            @elseif($conv->latestMessage->type === 'note') {{ __('chat.preview_note') }}
                            @else {{ Str::limit($conv->latestMessage->body ?? '', 40) }}
                            @endif
                            @endif
                        </span>
                        @if($conv->unread_count > 0)
                        <span class="wa-unread-dot">{{ $conv->unread_count }}</span>
                        @endif
                        @php $convAssignee = $conv->assigned_user_id ? $users->firstWhere('id', $conv->assigned_user_id) : null; @endphp
                        @if($convAssignee)
                        <span class="wa-conv-assignee" title="{{ $convAssignee->name }}">
                            @if($convAssignee->avatar)
                            <img src="{{ asset($convAssignee->avatar) }}" alt="">
                            @else
                            {{ collect(explode(' ', $convAssignee->name))->map(fn($w) => mb_strtoupper(mb_substr($w,0,1)))->take(2)->join('') }}
                            @endif
                        </span>
                        @endif
                    </div>
                    @if(!empty($conv->tags) || ($instanceCount > 1 && $ch === 'whatsapp' && $conv->instance) || $conv->department)
                    <div class="wa-conv-tags">
                        @if($instanceCount > 1 && $ch === 'whatsapp' && $conv->instance)
                        <span class="wa-tag" style="background:#dcfce71a;color:#15803d;border:1px solid #15803d40;font-size:10px;">{{ $conv->instance->label ?: $conv->instance->phone_number }}</span>
                        @endif
                        @if($conv->department)
                        <span class="wa-tag" style="background:{{ $conv->department->color ?? '#6b7280' }}1a;color:{{ $conv->department->color ?? '#6b7280' }};border:1px solid {{ $conv->department->color ?? '#6b7280' }}40;font-size:10px;">{{ $conv->department->name }}</span>
                        @endif
                        @foreach(($conv->tags ?? []) as $tag)
                        @php $tagDef = $whatsappTags->firstWhere('name', $tag); $tagColor = $tagDef?->color ?? null; @endphp
                        @if($tagColor)
                        <span class="wa-tag" style="background:{{ $tagColor }}1a;color:{{ $tagColor }};border:1px solid {{ $tagColor }}40;">{{ $tag }}</span>
                        @else
                        <span class="wa-tag">{{ $tag }}</span>
                        @endif
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div style="padding:32px 16px;text-align:center;color:#9ca3af;font-size:13px;">
                <i class="bi bi-chat-dots" style="font-size:32px;display:block;margin-bottom:10px;opacity:.4;"></i>
                {!! nl2br(e(__('chat.no_conversations'))) !!}
            </div>
            @endforelse
        </div>
    </div>

    {{-- Área de chat --}}
    <div class="wa-chat-area" id="chatArea">
        <div class="wa-no-conv" id="noConvPlaceholder">
            <i class="bi bi-chat-quote"></i>
            <p>Selecione uma conversa para começar</p>
        </div>

        {{-- Header do chat (oculto até abrir conversa) --}}
        <div class="wa-chat-header" id="chatHeader" style="display:none;">
            <button class="wa-chat-back-btn" onclick="mobileBackToList()" title="{{ __('chat.back') }}">
                <i class="bi bi-arrow-left"></i>
            </button>
            <div class="wa-conv-avatar-wrap">
                <div class="wa-conv-avatar" id="chatAvatar" style="width:38px;height:38px;font-size:14px;"></div>
                <span class="wa-channel-icon whatsapp" id="chatChannelIcon" title="WhatsApp">
                    <i class="bi bi-whatsapp"></i>
                </span>
            </div>
            <div>
                <div class="wa-chat-contact-name" id="chatContactName"></div>
                <div class="wa-chat-contact-phone" id="chatContactPhone"></div>
            </div>
            <div class="wa-chat-actions">
                <button class="wa-action-btn" id="btnToggleDetails" title="{{ __('chat.details') }}" onclick="toggleDetails()">
                    <i class="bi bi-info-circle"></i>
                </button>
                <div style="position:relative;">
                    <button class="wa-action-btn" id="btnCloseConv" title="{{ __('chat.close_conversation') }}" onclick="toggleConvStatus()">
                        <i class="bi bi-check-circle"></i>
                    </button>
                </div>
                <button class="wa-action-btn" title="{{ __('chat.delete_conversation') }}" onclick="deleteConversation()"
                    style="color:#ef4444;" id="btnDeleteConv">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>

        {{-- Mensagens --}}
        <div class="wa-messages" id="messagesContainer" style="display:none;"></div>

        {{-- Footer de composição --}}
        @if(!empty($isPartnerView))
        <div class="wa-compose-area" id="composeArea" style="display:none;">
            <div style="padding:14px 18px;text-align:center;color:#6b7280;font-size:13px;background:#f8fafc;border-top:1px solid #f0f2f7;">
                <i class="bi bi-eye" style="margin-right:5px;"></i> {{ __('chat.view_only_agency') }}
            </div>
        </div>
        @elseif(auth()->user()->isViewer())
        <div class="wa-compose-area" id="composeArea" style="display:none;">
            <div style="padding:12px 16px;text-align:center;color:#9ca3af;font-size:13px;background:#f9fafb;border-top:1px solid #e8eaf0;">
                <i class="bi bi-eye" style="margin-right:5px;"></i> {{ __('chat.view_only_viewer') }}
            </div>
        </div>
        @else
        <div class="wa-compose-area" id="composeArea" style="display:none;">
            <div class="wa-compose-tabs">
                <button class="wa-tab-btn active" id="tabReply" onclick="setComposeMode('reply')">{{ __('chat.reply') }}</button>
                <button class="wa-tab-btn" id="tabNote" onclick="setComposeMode('note')">{{ __('chat.private_note') }}</button>
                <button class="wa-tab-btn" id="tabSchedule" onclick="setComposeMode('schedule')"><i class="bi bi-clock" style="margin-right:3px;"></i> {{ __('chat.schedule') ?? 'Agendar' }}</button>
            </div>

            <div class="wa-compose-row" id="recordingRow" style="display:none;">
                <div class="wa-recording-indicator" style="display:flex;" id="recordingIndicator">
                    <div class="wa-recording-dot"></div>
                    {{ __('chat.recording_audio') }} <span id="recordingTimer">0:00</span>
                </div>
                <button class="wa-btn-icon" onclick="cancelRecording()" title="{{ __('chat.cancel') }}">
                    <i class="bi bi-x"></i>
                </button>
                <button class="wa-btn-send" onclick="stopAndSendRecording()" title="{{ __('chat.send_audio') }}">
                    <i class="bi bi-send"></i>
                </button>
            </div>

            {{-- Image preview bar --}}
            <div id="imagePreviewBar" style="display:none;padding:10px 14px;background:#f8fafc;border-bottom:1px solid #e8eaf0;display:none;align-items:center;gap:10px;">
                <img id="imagePreviewThumb" style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #e8eaf0;" alt="">
                <div style="flex:1;min-width:0;">
                    <div id="imagePreviewName" style="font-size:12px;font-weight:600;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>
                    <input id="imageCaption" type="text" placeholder="Adicionar legenda..." style="width:100%;margin-top:4px;padding:6px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:12px;font-family:inherit;outline:none;" onkeydown="if(event.key==='Enter'){event.preventDefault();confirmSendImage();}">
                </div>
                <button onclick="confirmSendImage()" style="background:#0085f3;color:#fff;border:none;border-radius:100px;padding:7px 16px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:4px;">
                    <i class="bi bi-send"></i> Enviar
                </button>
                <button onclick="cancelImagePreview()" style="background:none;border:none;color:#9ca3af;cursor:pointer;font-size:16px;padding:4px;" title="Cancelar">&times;</button>
            </div>

            <div class="wa-compose-row" id="normalRow">
                <input type="file" id="fileInput" accept="image/*" style="display:none;" onchange="previewImage(this)">
                <input type="file" id="docInput" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.csv" style="display:none;" onchange="sendDocument(this)">
                {{-- Botão "+" com menu de anexos --}}
                <div style="position:relative;" id="attachMenuWrap">
                    <button class="wa-btn-icon" onclick="toggleAttachMenu()" title="{{ __('chat.attach') }}" id="btnAttach">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                    <div id="attachMenu" style="display:none;position:absolute;bottom:calc(100% + 8px);left:0;
                         width:200px;background:#fff;border:1px solid #e8eaf0;border-radius:12px;
                         box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:1000;overflow:hidden;">
                        <button onclick="document.getElementById('fileInput').click();closeAttachMenu()" class="wa-attach-item">
                            <i class="bi bi-image" style="color:#3b82f6;"></i> {{ __('chat.image') }}
                        </button>
                        <button onclick="document.getElementById('docInput').click();closeAttachMenu()" class="wa-attach-item">
                            <i class="bi bi-paperclip" style="color:#6b7280;"></i> {{ __('chat.document') }}
                        </button>
                        <button onclick="startRecording();closeAttachMenu()" class="wa-attach-item" id="btnMic">
                            <i class="bi bi-mic" style="color:#ef4444;"></i> {{ __('chat.record_audio') }}
                        </button>
                        <button onclick="openQmModal();closeAttachMenu()" class="wa-attach-item" id="btnQuickMsgs">
                            <i class="bi bi-lightning-charge-fill" style="color:#f59e0b;"></i> {{ __('chat.quick_reply') }}
                        </button>
                    </div>
                </div>
                <div class="wa-textarea-wrap" style="position:relative;">
                    {{-- Popup de mensagens rápidas --}}
                    <div id="quickMsgPopup" style="display:none;position:absolute;bottom:calc(100% + 8px);left:0;
                         width:340px;background:#fff;border:1px solid #e8eaf0;border-radius:12px;
                         box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:1000;max-height:280px;overflow-y:auto;">
                        <div style="padding:8px 10px;border-bottom:1px solid #f0f2f7;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;">
                            {{ __('chat.quick_messages') }}
                        </div>
                        <div id="quickMsgList"></div>
                    </div>
                    <textarea class="wa-textarea"
                        id="messageInput"
                        placeholder="{{ __('chat.placeholder') }}"
                        rows="1"
                        oninput="autoResize(this);handleQmTrigger(this)"></textarea>
                </div>
                <button class="wa-btn-send" id="btnSend" onclick="sendMessage()" title="{{ __('chat.send') }}">
                    <i class="bi bi-send"></i>
                </button>
            </div>
            <div style="padding:0 14px 4px;text-align:right;">
                <span style="font-size:10px;color:#c4c9d4;">{{ __('chat.shift_enter_hint') }}</span>
            </div>
        </div>
        @endif
    </div>

    {{-- Painel de detalhes --}}
    <div class="wa-details" id="detailsPanel">
        <div class="wa-details-section">
            <div class="wa-details-label" style="display:flex;align-items:center;justify-content:space-between;">
                {{ __('chat.contact') }}
                <button onclick="toggleContactEdit()" id="btnEditContact"
                    style="background:none;border:none;cursor:pointer;color:#6b7280;padding:2px 4px;font-size:13px;"
                    title="{{ __('chat.edit_contact') }}">
                    <i class="bi bi-pencil"></i>
                </button>
            </div>
            {{-- modo visualização --}}
            <div id="contactViewMode">
                <div class="wa-details-value" id="detailsName" style="font-weight:600;margin-bottom:4px;"></div>
                <div class="wa-details-value" id="detailsPhone" style="color:#9ca3af;font-size:12px;"></div>
            </div>
            {{-- modo edição --}}
            <div id="contactEditMode" style="display:none;">
                <input id="editContactName" class="wa-textarea"
                    style="min-height:unset;height:34px;padding:5px 8px;font-size:13px;margin-bottom:6px;"
                    placeholder="{{ __('chat.contact_name') }}">
                <input id="editContactPhone" class="wa-textarea"
                    style="min-height:unset;height:34px;padding:5px 8px;font-size:13px;margin-bottom:6px;"
                    placeholder="{{ __('chat.phone_digits') }}">
                <div style="display:flex;gap:6px;">
                    <button onclick="saveContact()"
                        style="flex:1;padding:5px 10px;background:#3b82f6;color:#fff;border:none;border-radius:6px;font-size:12px;cursor:pointer;">
                        {{ __('chat.save') }}
                    </button>
                    <button onclick="toggleContactEdit()"
                        style="flex:1;padding:5px 10px;background:#f1f5f9;color:#374151;border:none;border-radius:6px;font-size:12px;cursor:pointer;">
                        {{ __('chat.cancel') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Tags --}}
        <div class="wa-details-section">
            <div class="wa-details-label" style="display:flex;align-items:center;justify-content:space-between;">
                {{ __('chat.tags') }}
                <a href="{{ route('settings.tags') }}" target="_blank"
                    style="font-size:11px;color:#9ca3af;text-decoration:none;" title="{{ __('chat.manage_tags') }}">
                    <i class="bi bi-gear" style="font-size:12px;"></i>
                </a>
            </div>

            {{-- Tags aplicadas --}}
            <div id="tagsList" style="display:flex;flex-wrap:wrap;gap:4px;min-height:22px;margin-bottom:8px;"></div>

            {{-- Chips predefinidos --}}
            @if(isset($whatsappTags) && $whatsappTags->isNotEmpty())
            <div style="margin-bottom:8px;">
                <div style="font-size:10px;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px;">{{ __('chat.select_tag') }}</div>
                <div style="display:flex;flex-wrap:wrap;gap:4px;">
                    @foreach($whatsappTags as $wTag)
                    <button type="button"
                        class="predefined-tag-chip"
                        data-tag-name="{{ $wTag->name }}"
                        onclick="togglePredefinedTag('{{ addslashes($wTag->name) }}')"
                        style="padding:2px 9px;border-radius:20px;font-size:10px;font-weight:600;cursor:pointer;border:1px solid {{ $wTag->color }}40;background:{{ $wTag->color }}1a;color:{{ $wTag->color }};transition:opacity .15s;white-space:nowrap;">
                        {{ $wTag->name }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Input tag livre --}}
            <div style="display:flex;gap:6px;">
                <input id="tagInput" class="wa-textarea"
                    style="min-height:unset;height:30px;padding:4px 8px;font-size:12px;"
                    placeholder="{{ __('chat.type_tag') }}"
                    onkeydown="if(event.key==='Enter'){event.preventDefault();addTag();}">
                <button onclick="addTag()"
                    style="padding:4px 10px;background:#3b82f6;color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer;white-space:nowrap;">
                    +
                </button>
            </div>
        </div>

        {{-- Seção Lead / CRM --}}
        <div class="wa-details-section" id="leadSection" style="display:none;">
            <div class="wa-details-label" style="display:flex;align-items:center;justify-content:space-between;">
                {{ __('chat.lead') }}
                <div style="display:flex;align-items:center;gap:8px;">
                    <a id="leadProfileLink" href="#" target="_blank"
                        style="font-size:11px;color:#3b82f6;font-weight:600;text-decoration:none;">
                        {{ __('chat.view_profile') }}
                    </a>
                    <button onclick="unlinkCurrentLead()" class="unlink-lead-btn" title="{{ __('chat.unlink') }}">
                        <i class="bi bi-x-circle"></i> {{ __('chat.unlink') }}
                    </button>
                </div>
            </div>
            <div id="leadNameDisplay" style="font-size:13px;font-weight:600;color:#1a1d23;margin-bottom:6px;"></div>
            <div class="wa-details-label" style="margin-top:8px;">{{ __('chat.pipeline') }}</div>
            <select class="wa-textarea" style="min-height:unset;height:34px;padding:5px 8px;font-size:12px;margin-bottom:6px;"
                id="pipelineSelect" onchange="onPipelineChange()">
                <option value="">{{ __('chat.select_pipeline') }}</option>
                @foreach($pipelines as $pipeline)
                <option value="{{ $pipeline->id }}" data-stages="{{ $pipeline->stages->toJson() }}">
                    {{ $pipeline->name }}
                </option>
                @endforeach
            </select>
            <div class="wa-details-label">{{ __('chat.stage') }}</div>
            <select class="wa-textarea" style="min-height:unset;height:34px;padding:5px 8px;font-size:12px;"
                id="stageSelect" onchange="saveLeadCrm()">
                <option value="">{{ __('chat.select_stage') }}</option>
            </select>
        </div>

        <div class="wa-details-section" id="noLeadSection" style="display:none;">
            <div class="wa-details-label">{{ __('chat.lead') }}</div>
            <div id="noLeadDefault">
                <div style="font-size:12px;color:#9ca3af;margin-bottom:8px;">{{ __('chat.no_lead_linked') }}</div>
                <button onclick="showCreateLeadForm()"
                    style="width:100%;padding:6px 10px;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                    <i class="bi bi-plus-lg"></i> {{ __('chat.create_lead_pipeline') }}
                </button>
                <div style="text-align:center;font-size:11px;color:#9ca3af;margin:6px 0;">{{ __('chat.or') }}</div>
                <button onclick="showLinkExistingLead()" class="btn-link-existing">
                    <i class="bi bi-search"></i> {{ __('chat.link_existing_lead') }}
                </button>
                <div id="linkExistingPanel" style="display:none;margin-top:8px;">
                    <div style="display:flex;align-items:center;gap:4px;margin-bottom:4px;">
                        <input type="text" id="leadSearchInput" placeholder="{{ __('chat.search_lead') }}"
                               class="wa-textarea" oninput="onLeadSearch(this.value)"
                               style="min-height:unset;height:32px;padding:5px 8px;font-size:12px;flex:1;" />
                        <button onclick="hideLinkExistingLead()" title="{{ __('chat.cancel') }}"
                            style="width:28px;height:28px;flex-shrink:0;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:7px;cursor:pointer;font-size:13px;color:#6b7280;">
                            ×
                        </button>
                    </div>
                    <div id="leadSearchResults" style="max-height:160px;overflow-y:auto;border-radius:8px;"></div>
                </div>
            </div>
            <div id="createLeadForm" style="display:none;">
                <select id="createLeadPipeline" onchange="onCreateLeadPipelineChange()"
                    class="wa-textarea" style="min-height:unset;height:34px;padding:5px 8px;font-size:12px;margin-bottom:6px;">
                    @foreach($pipelines as $p)
                    <option value="{{ $p->id }}" data-stages="{{ $p->stages->toJson() }}">{{ $p->name }}</option>
                    @endforeach
                </select>
                <select id="createLeadStage"
                    class="wa-textarea" style="min-height:unset;height:34px;padding:5px 8px;font-size:12px;margin-bottom:8px;">
                    <option value="">{{ __('chat.select_stage') }}</option>
                </select>
                <div style="display:flex;gap:6px;">
                    <button onclick="cancelCreateLead()"
                        style="flex:1;padding:6px;background:#f3f4f6;color:#374151;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                        {{ __('chat.cancel') }}
                    </button>
                    <button onclick="doCreateLead()"
                        style="flex:1;padding:6px;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                        {{ __('common.create') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="wa-details-section">
            <div class="wa-details-label">{{ __('chat.assigned_to') }}</div>
            <select class="wa-textarea" style="min-height:unset;height:36px;padding:6px 10px;" id="assignSelect" onchange="assignUser()" {{ auth()->user()->isViewer() ? 'disabled' : '' }}>
                <option value="">{{ __('chat.no_assignment') }}</option>
                @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Setor / Departamento --}}
        @if(isset($departments) && $departments->isNotEmpty())
        <div class="wa-details-section">
            <div class="wa-details-label"><i class="bi bi-building" style="margin-right:4px;color:#0085f3;"></i> {{ __('chat.department') }}</div>
            <select class="wa-textarea" style="min-height:unset;height:36px;padding:6px 10px;" id="departmentSelect" onchange="assignDepartment()" {{ auth()->user()->isViewer() ? 'disabled' : '' }}>
                <option value="">{{ __('chat.no_department') }}</option>
                @foreach($departments as $dept)
                <option value="{{ $dept->id }}" data-color="{{ $dept->color }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Agente de IA --}}
        @if(isset($aiAgents) && $aiAgents->isNotEmpty())
        <div class="wa-details-section">
            <div class="wa-details-label" style="display:flex;align-items:center;justify-content:space-between;">
                <span><i class="bi bi-robot" style="margin-right:4px;color:#6366f1;"></i> {{ __('chat.ai_agent') }}</span>
                <span id="aiAgentStatus" style="font-size:11px;font-weight:600;"></span>
            </div>
            <select class="wa-textarea" style="min-height:unset;height:36px;padding:6px 10px;" id="aiAgentSelect" onchange="assignAiAgent()" {{ auth()->user()->isViewer() ? 'disabled' : '' }}>
                <option value="">{{ __('chat.no_ai_agent') }}</option>
                @foreach($aiAgents as $ag)
                <option value="{{ $ag->id }}">{{ $ag->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="wa-details-section">
            <div class="wa-details-label" style="display:flex;align-items:center;justify-content:space-between;">
                <span><i class="bi bi-diagram-3" style="margin-right:4px;color:#8b5cf6;"></i> {{ __('chat.chatbot') }}</span>
                <a href="{{ route('chatbot.flows.index') }}" style="font-size:11px;color:#8b5cf6;text-decoration:none;" title="{{ __('chat.manage_flows') }}">
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
            <select class="wa-textarea" style="min-height:unset;height:36px;padding:6px 10px;" id="chatbotFlowSelect" onchange="assignChatbotFlow()" {{ auth()->user()->isViewer() ? 'disabled' : '' }}>
                <option value="">{{ __('chat.no_chatbot') }}</option>
                @foreach($chatbotFlows as $cf)
                <option value="{{ $cf->id }}" data-channel="{{ $cf->channel }}">{{ $cf->name }}</option>
                @endforeach
            </select>
            <div id="chatbotVarsInfo" style="display:none;font-size:11px;color:#8b5cf6;margin-top:4px;"></div>
        </div>
        @endif

        {{-- Seção de status removida — informação disponível no header da conversa --}}
    </div>

    {{-- WebSocket status toast --}}
    <div class="ws-status" id="wsStatus">
        <i class="bi bi-wifi-off"></i>
        <span id="wsStatusText">Reconectando...</span>
    </div>

    @endif
</div>

{{-- Modal: confirmar exclusão de conversa --}}
<div class="del-modal-overlay" id="delConvModal">
    <div class="del-modal">
        <div class="del-modal-icon"><i class="bi bi-trash3-fill"></i></div>
        <div class="del-modal-title">{{ __('chat.delete_title') }}</div>
        <div class="del-modal-text">{!! nl2br(e(__('chat.delete_desc'))) !!}</div>
        <div class="del-modal-footer">
            <button class="btn-del-cancel" onclick="document.getElementById('delConvModal').classList.remove('open')">{{ __('chat.cancel') }}</button>
            <button class="btn-del-confirm" onclick="_doDeleteConversation()">{{ __('chat.delete') }}</button>
        </div>
    </div>
</div>

{{-- Modal de Mensagens Rápidas --}}
<div id="qmModalOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;"
     onclick="if(event.target===this)closeQmModal()">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
                background:#fff;border-radius:16px;width:560px;max-width:95vw;
                max-height:90vh;display:flex;flex-direction:column;overflow:hidden;
                box-shadow:0 20px 60px rgba(0,0,0,.2);">
        {{-- Header --}}
        <div style="padding:18px 22px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div style="font-size:15px;font-weight:700;color:#1a1d23;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-lightning-charge-fill" style="color:#f59e0b;"></i>
                {{ __('chat.qm_title') }}
            </div>
            <button onclick="closeQmModal()" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;line-height:1;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        {{-- Body --}}
        <div style="flex:1;overflow-y:auto;padding:16px 22px;" id="qmModalBody">
            {{-- Lista de mensagens rápidas --}}
            <div id="qmList" style="margin-bottom:14px;"></div>

            {{-- Formulário add/edit --}}
            <div id="qmForm" style="display:none;border:1.5px solid #e8eaf0;border-radius:12px;padding:16px;background:#fafafa;">
                <input type="hidden" id="qmEditId" value="">
                <div style="margin-bottom:10px;">
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">
                        {{ __('chat.qm_field_title') }} <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="text" id="qmTitle" maxlength="100" placeholder="{{ __('chat.qm_placeholder_title') }}"
                           style="width:100%;padding:8px 12px;border:1.5px solid #e8eaf0;border-radius:9px;font-size:13.5px;
                                  font-family:inherit;outline:none;box-sizing:border-box;background:#fff;">
                </div>
                <div style="margin-bottom:10px;">
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">
                        {{ __('chat.qm_field_message') }} <span style="color:#ef4444;">*</span>
                    </label>
                    {{-- Toolbar de formatação WhatsApp --}}
                    <div style="display:flex;gap:4px;margin-bottom:6px;">
                        <button type="button" onclick="qmFormat('bold')" title="{{ __('chat.qm_bold') }}"
                                style="padding:4px 9px;border:1.5px solid #e8eaf0;border-radius:6px;background:#fff;
                                       font-size:12px;font-weight:700;cursor:pointer;">B</button>
                        <button type="button" onclick="qmFormat('italic')" title="{{ __('chat.qm_italic') }}"
                                style="padding:4px 9px;border:1.5px solid #e8eaf0;border-radius:6px;background:#fff;
                                       font-size:12px;font-style:italic;cursor:pointer;">I</button>
                        <button type="button" onclick="qmFormat('strike')" title="{{ __('chat.qm_strikethrough') }}"
                                style="padding:4px 9px;border:1.5px solid #e8eaf0;border-radius:6px;background:#fff;
                                       font-size:12px;text-decoration:line-through;cursor:pointer;">S</button>
                        <button type="button" onclick="qmFormat('mono')" title="{{ __('chat.qm_mono') }}"
                                style="padding:4px 9px;border:1.5px solid #e8eaf0;border-radius:6px;background:#fff;
                                       font-size:12px;font-family:monospace;cursor:pointer;">&lt;/&gt;</button>
                    </div>
                    <textarea id="qmBody" rows="5" maxlength="2000" placeholder="{{ __('chat.qm_placeholder_msg') }}"
                              style="width:100%;padding:9px 12px;border:1.5px solid #e8eaf0;border-radius:9px;
                                     font-size:13.5px;font-family:inherit;resize:vertical;
                                     outline:none;box-sizing:border-box;background:#fff;min-height:100px;"></textarea>
                    <div style="font-size:11px;color:#9ca3af;text-align:right;margin-top:2px;">
                        {{ __('chat.qm_help') }}
                    </div>
                </div>
                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button type="button" onclick="cancelQmForm()"
                            style="padding:8px 16px;border:1.5px solid #e8eaf0;border-radius:8px;background:#fff;
                                   font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;">{{ __('chat.cancel') }}</button>
                    <button type="button" onclick="saveQm()"
                            style="padding:8px 18px;border:none;border-radius:8px;background:#3B82F6;
                                   color:#fff;font-size:13px;font-weight:600;cursor:pointer;">{{ __('chat.save') }}</button>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding:14px 22px;border-top:1px solid #f0f2f7;flex-shrink:0;">
            <button type="button" onclick="showQmForm()"
                    style="width:100%;padding:9px;background:#f0f7ff;color:#3B82F6;
                           border:1.5px dashed #3B82F6;border-radius:9px;
                           font-size:13.5px;font-weight:600;cursor:pointer;">
                <i class="bi bi-plus-lg"></i> {{ __('chat.qm_new') }}
            </button>
        </div>
    </div>
</div>

{{-- ── Image Lightbox Modal ─────────────────────────────────────── --}}
<div class="wa-img-lightbox" id="waImgLightbox" onclick="closeImageLightbox(event)">
    <div class="wa-img-lightbox-content" onclick="event.stopPropagation()">
        <a id="waImgLightboxDownload" class="wa-img-lightbox-download" href="#" download target="_blank" title="{{ __('chat.download') }}">
            <i class="bi bi-download"></i>
        </a>
        <button type="button" class="wa-img-lightbox-close" onclick="closeImageLightbox()" title="{{ __('chat.close') }}">
            <i class="bi bi-x-lg"></i>
        </button>
        <img id="waImgLightboxImg" class="wa-img-lightbox-img" src="" alt="">
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ── Traduções (i18n) ─────────────────────────────────────────────────────────
    const LANG = @json(__('chat'));

    // ── Estado global ─────────────────────────────────────────────────────────────
    let activeConvId = null;
    let activeConvChannel = 'whatsapp'; // 'whatsapp' | 'instagram'
    let activeConvStatus = 'open';
    let activeConvLeadId = null;
    let activeLeadId = null; // lead vinculado à conversa ativa

    // ── Formatação de telefone brasileiro ─────────────────────────────────────
    function formatBrPhone(phone) {
        let d = (phone || '').replace(/\D/g, '');
        if (d.startsWith('55') && d.length >= 12) d = d.slice(2);
        if (d.length === 11) return `(${d.slice(0,2)}) ${d.slice(2,7)}-${d.slice(7)}`;
        if (d.length === 10) return `(${d.slice(0,2)}) ${d.slice(2,6)}-${d.slice(6)}`;
        return phone || '';
    }

    function phoneLink(phone) {
        if (!phone) return '';
        const digits = phone.replace(/\D/g, '');
        const waNum = digits.startsWith('55') ? digits : '55' + digits;
        const fmt = formatBrPhone(phone);
        return `<a href="https://wa.me/${waNum}" target="_blank" rel="noopener" style="color:inherit;text-decoration:none;">${fmt}</a>`;
    }

    function instagramLink(username) {
        const clean = (username || '').replace(/^@/, '').trim();
        if (!clean) return '';
        return `<a href="https://www.instagram.com/${clean}" target="_blank" rel="noopener" style="color:inherit;text-decoration:none;">@${clean}</a>`;
    }

    // Define innerHTML formatado + salva raw em data-raw (para o campo de edição)
    function setPhoneDisplay(el, phone) {
        if (activeConvChannel === 'instagram') {
            el.innerHTML = instagramLink(phone);
        } else if (activeConvChannel === 'website') {
            el.innerHTML = phone ? `<span>${phone}</span>` : '';
        } else {
            el.innerHTML = phone ? phoneLink(phone) : '';
        }
        el.dataset.raw = phone || '';
    }

    function setAvatar(el, name, pictureUrl) {
        const initial = escHtml((name || '?').charAt(0).toUpperCase());
        if (pictureUrl) {
            el.innerHTML = `<img src="${escHtml(pictureUrl)}" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover;" onerror="this.parentElement.textContent='${initial}'">`;
        } else {
            el.textContent = initial;
        }
    }

    const CHATS_BASE = '{{ rtrim(url("/chats"), "/") }}';
    function convBaseUrl(id) {
        if (activeConvChannel === 'instagram') return `${CHATS_BASE}/instagram-conversations/${id}`;
        if (activeConvChannel === 'website')   return `${CHATS_BASE}/website-conversations/${id}`;
        return `${CHATS_BASE}/conversations/${id}`;
    }
    // Endpoint generico do inbox (PUT /chats/inbox/{channel}/{conv}/contact).
    // Funciona pros 3 canais — resolve o bug latente do Instagram nao salvar tags
    // e habilita pela primeira vez tags em conversas de Website.
    function inboxContactUrl(id) {
        const ch = activeConvChannel || 'whatsapp';
        return `${CHATS_BASE}/inbox/${ch}/${id}/contact`;
    }
    let composeMode = 'reply';
    let mediaRecorder = null;
    let audioChunks = [];
    let recordingSeconds = 0;
    let recordingTimerInt = null;
    let reactionTargetId = null;
    let lastRenderedDate = null; // persiste entre chamadas a renderMessages/appendMessages
    const renderedMsgIds = new Set(); // evita duplicatas quando polling e histórico coexistem

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
    const TENANT_ID       = {{ auth()->user()->tenant_id ?? 'null' }};
    const CURRENT_USER_ID = {{ auth()->id() ?? 'null' }};
    const PIPELINES = @json($pipelines ?? []);

    // Tags predefinidas e mapa de cores { 'VIP': '#F59E0B', ... }
    const _whatsappTagsDefs = @json($whatsappTags ?? []);
    const tagColorMap = {};
    _whatsappTagsDefs.forEach(t => {
        tagColorMap[t.name] = t.color;
    });

    function hexToRgba(hex, alpha) {
        const r = parseInt(hex.slice(1, 3), 16),
            g = parseInt(hex.slice(3, 5), 16),
            b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r},${g},${b},${alpha})`;
    }

    // ── Inicialização ─────────────────────────────────────────────────────────────
    @if($connected)
    document.addEventListener('DOMContentLoaded', () => {
        setupSearch();
        setupFilters();
        setupChannelTabs();
        updateTotalUnread();
        setupEcho();

        document.getElementById('messageInput')?.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    });
    @endif

    // ── WebSocket via Laravel Echo + Reverb ───────────────────────────────────────
    let pollInterval = null;
    let lastPollAt = new Date().toISOString();
    let echoConnected = false;

    function startFallbackPolling() {
        if (pollInterval) return; // already running
        pollInterval = setInterval(runPoll, 5000);
    }

    function stopFallbackPolling() {
        if (!pollInterval) return;
        clearInterval(pollInterval);
        pollInterval = null;
    }

    function runPoll() {
        const params = new URLSearchParams({
            since: lastPollAt
        });
        if (activeConvId) {
            params.append('conversation_id', activeConvId);
            params.append('conv_channel', activeConvChannel || 'whatsapp');
        }

        fetch(`${CHATS_BASE}/poll?${params}`, {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data) return;
                lastPollAt = data.now;

                if (data.new_messages?.length) {
                    appendMessages(data.new_messages);
                    if (activeConvId) {
                        fetch(`${convBaseUrl(activeConvId)}/read`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': CSRF,
                                'Accept': 'application/json'
                            }
                        });
                    }
                }

                data.conversations_updated?.forEach(c => updateConvInSidebar(c));
            })
            .catch(() => {}); // silent fail — will retry on next interval
    }

    function setupEcho() {
        // Always start polling immediately — if Echo connects successfully it will be stopped below.
        // This guarantees updates even if Echo setup throws or takes too long.
        startFallbackPolling();

        if (!window.Echo || !TENANT_ID) {
            showWsStatus('error', LANG.ws_unavailable);
            return;
        }

        try {
            const channel = window.Echo.private(`tenant.${TENANT_ID}`);

            channel.listen('.whatsapp.message', data => {
                if (data.conversation_id == activeConvId) {
                    appendMessages([data]);
                    fetch(`${convBaseUrl(activeConvId)}/read`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json'
                        }
                    });
                }
                // Sound + browser notification for inbound messages
                if (data.direction === 'inbound' && window.NotifManager) {
                    window.NotifManager.notify(
                        data.user_name || LANG.new_message,
                        data.body ? data.body.substring(0, 100) : LANG.new_wa_message,
                        '/chats?conv=' + data.conversation_id,
                        'whatsapp_message',
                        'message-received'
                    );
                }
                // Keep lastPollAt in sync so fallback doesn't re-deliver
                lastPollAt = data.sent_at ?? new Date().toISOString();
            });

            channel.listen('.whatsapp.conversation', data => {
                updateConvInSidebar(data);
                if (data.assigned_user_id && data.assigned_user_id == CURRENT_USER_ID) {
                    const name = data.contact_name || data.phone || LANG.contact;
                    toastr.info(
                        LANG.assigned_toast.replace(':name', escHtml(name)),
                        LANG.new_assignment,
                        { timeOut: 8000, closeButton: true, progressBar: true }
                    );
                    if (window.NotifManager) {
                        window.NotifManager.notify(
                            LANG.conv_assigned,
                            LANG.assigned_toast.replace(':name', name).replace(/<[^>]+>/g, ''),
                            '/chats?conv=' + data.id,
                            'whatsapp_assigned',
                            'notification-chime'
                        );
                    }
                }
            });

            channel.listen('.instagram.message', data => {
                if (data.conversation_id == activeConvId && activeConvChannel === 'instagram') {
                    appendMessages([data]);
                    fetch(`${convBaseUrl(activeConvId)}/read`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json'
                        }
                    });
                }
                lastPollAt = data.sent_at ?? new Date().toISOString();
            });

            channel.listen('.instagram.conversation', data => {
                updateConvInSidebar(data);
                if (data.assigned_user_id && data.assigned_user_id == CURRENT_USER_ID) {
                    const name = data.contact_name || LANG.ig_contact;
                    toastr.info(
                        LANG.assigned_toast.replace(':name', escHtml(name)),
                        LANG.new_assignment,
                        { timeOut: 8000, closeButton: true, progressBar: true }
                    );
                }
            });

            // Use optional chaining — connector.pusher might not exist for all Echo drivers
            const conn = window.Echo.connector?.pusher?.connection;
            if (!conn) return; // No pusher connector — keep polling

            conn.bind('connected', () => {
                echoConnected = true;
                stopFallbackPolling();
                hideWsStatus();
            });
            conn.bind('unavailable', () => {
                echoConnected = false;
                startFallbackPolling();
                showWsStatus('error', LANG.ws_no_connection);
            });
            conn.bind('disconnected', () => {
                echoConnected = false;
                startFallbackPolling();
            });
        } catch (e) {
            // Echo setup failed — polling already running, no action needed
            showWsStatus('error', LANG.ws_unavailable);
        }
    }

    function showWsStatus(type, text) {
        const el = document.getElementById('wsStatus');
        if (!el) return;
        el.className = `ws-status ${type}`;
        document.getElementById('wsStatusText').textContent = text;
    }

    function hideWsStatus() {
        const el = document.getElementById('wsStatus');
        if (el) el.className = 'ws-status';
    }

    // ── Filtros e pesquisa ────────────────────────────────────────────────────────
    let activeStatusFilter = 'all';
    let activeChannelTab   = 'all';

    function setupSearch() {
        document.getElementById('searchInput').addEventListener('input', applyFilters);
    }

    function setupFilters() {
        document.querySelectorAll('.wa-filter-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.wa-filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                activeStatusFilter = this.dataset.filter;
                applyFilters();
            });
        });
    }

    function setupChannelTabs() {
        document.querySelectorAll('.wa-channel-tab').forEach(tab => {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.wa-channel-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                activeChannelTab = this.dataset.channel;
                applyFilters();
            });
        });
    }

    function applyFilters() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('.wa-conv-item').forEach(item => {
            let visible = true;

            // Canal
            if (activeChannelTab !== 'all') {
                visible = item.dataset.channel === activeChannelTab;
            }

            // Status / atribuição
            if (visible) {
                if (activeStatusFilter === 'mine') {
                    visible = String(item.dataset.assignedUserId) === String(CURRENT_USER_ID);
                } else if (activeStatusFilter === 'open' || activeStatusFilter === 'closed') {
                    visible = item.dataset.status === activeStatusFilter;
                }
            }

            // Busca por texto
            if (visible && q) {
                const name  = item.querySelector('.wa-conv-name')?.textContent.toLowerCase() ?? '';
                const phone = item.dataset.phone?.toLowerCase() ?? '';
                visible = name.includes(q) || phone.includes(q);
            }

            item.style.display = visible ? '' : 'none';
        });
    }

    // ── Abrir conversa ────────────────────────────────────────────────────────────
    function mobileBackToList() {
        document.getElementById('chatArea').classList.remove('mobile-active');
    }

    async function openConversation(convId, el) {
        activeConvId = convId;
        activeConvChannel = el.dataset.channel || 'whatsapp';

        document.getElementById('detailsPanel').classList.add('open');
        document.querySelectorAll('.wa-conv-item').forEach(i => i.classList.remove('active'));
        el.classList.add('active');
        if (window.innerWidth <= 768) document.getElementById('chatArea').classList.add('mobile-active');

        const dot = el.querySelector('.wa-unread-dot');
        if (dot) dot.remove();

        const name = el.querySelector('.wa-conv-name').textContent;
        const phone = el.dataset.phone;
        const channel = activeConvChannel;
        activeConvStatus = el.dataset.status;

        document.getElementById('chatHeader').style.display = 'flex';
        document.getElementById('messagesContainer').style.display = 'flex';
        // composeArea visibility handled below based on channel
        document.getElementById('noConvPlaceholder').style.display = 'none';

        document.getElementById('chatContactName').textContent = name;
        setPhoneDisplay(document.getElementById('chatContactPhone'), phone);
        setAvatar(document.getElementById('chatAvatar'), name, el.dataset.picture || '');
        document.getElementById('detailsName').textContent = name;
        setPhoneDisplay(document.getElementById('detailsPhone'), phone);
        // Reset tags e contact edit ao trocar conversa
        const tagsRaw = el.dataset.tags ? JSON.parse(el.dataset.tags) : [];
        renderTags(tagsRaw);
        document.getElementById('contactViewMode').style.display = '';
        document.getElementById('contactEditMode').style.display = 'none';
        const detailsStatusEl = document.getElementById('detailsStatus');
        if (detailsStatusEl) detailsStatusEl.textContent = activeConvStatus === 'open' ? LANG.status_open : LANG.status_closed;
        document.getElementById('btnCloseConv').title = activeConvStatus === 'open' ? LANG.close_conversation : LANG.reopen;
        document.getElementById('btnCloseConv').querySelector('i').className = activeConvStatus === 'open' ?
            'bi bi-check-circle' : 'bi bi-arrow-counterclockwise';

        // Atualizar ícone de canal no header
        const channelIcon = document.getElementById('chatChannelIcon');
        if (channelIcon) {
            channelIcon.className = `wa-channel-icon ${channel}`;
            if (channel === 'instagram') {
                channelIcon.innerHTML = '<i class="bi bi-instagram"></i>';
                channelIcon.title = 'Instagram';
            } else {
                channelIcon.innerHTML = '<i class="bi bi-whatsapp"></i>';
                channelIcon.title = 'WhatsApp';
            }
        }

        // Mostrar compose area
        const composeArea = document.getElementById('composeArea');
        if (composeArea) composeArea.style.display = 'block';

        await fetch(`${convBaseUrl(convId)}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            }
        });
        updateTotalUnread();

        const res = await fetch(convBaseUrl(convId), {
            headers: {
                'Accept': 'application/json'
            }
        });
        const data = await res.json();
        renderMessages(data.messages, true);

        // Atualiza nome/telefone com dados frescos do servidor (pode ter migrado de LID)
        if (data.contact_name || data.phone) {
            const freshName = data.contact_name || (data.is_group ? LANG.group : data.phone);
            const freshPhone = data.phone;
            document.getElementById('chatContactName').textContent = freshName;
            setPhoneDisplay(document.getElementById('chatContactPhone'), freshPhone);
            setAvatar(document.getElementById('chatAvatar'), freshName, data.contact_picture_url || '');
            document.getElementById('detailsName').textContent = freshName;
            setPhoneDisplay(document.getElementById('detailsPhone'), freshPhone);
            // Atualiza card na sidebar
            const cardEl = document.querySelector(`[data-conv-id="${convId}"]`);
            if (cardEl) {
                cardEl.dataset.phone = freshPhone;
                if (data.contact_picture_url) cardEl.dataset.picture = data.contact_picture_url;
                const nameEl = cardEl.querySelector('.wa-conv-name');
                if (nameEl) nameEl.textContent = freshName;
            }
        }

        // Tags frescas do servidor
        renderTags(data.tags || []);
        const cardEl = document.querySelector(`[data-conv-id="${convId}"]`);
        if (cardEl) cardEl.dataset.tags = JSON.stringify(data.tags || []);

        // Atualiza select de atribuição
        const assignSel = document.getElementById('assignSelect');
        if (assignSel && data.assigned_user_id) {
            assignSel.value = data.assigned_user_id;
        } else if (assignSel) {
            assignSel.value = '';
        }

        // Atualiza select de departamento
        const deptSel = document.getElementById('departmentSelect');
        if (deptSel) {
            deptSel.value = data.department_id ?? '';
        }

        // Atualiza select de agente de IA
        const aiSel = document.getElementById('aiAgentSelect');
        if (aiSel) {
            aiSel.value = data.ai_agent_id ?? '';
            updateAiAgentStatusBadge(data.ai_agent_id);
        }

        // Atualiza select de chatbot flow — filtra por canal da conversa
        const chatbotSel = document.getElementById('chatbotFlowSelect');
        const chatbotInfo = document.getElementById('chatbotVarsInfo');
        if (chatbotSel) {
            // Mostrar apenas bots do canal desta conversa (whatsapp/instagram)
            chatbotSel.querySelectorAll('option[data-channel]').forEach(opt => {
                opt.style.display = opt.dataset.channel === activeConvChannel ? '' : 'none';
            });
            chatbotSel.value = data.chatbot_flow_id ?? '';
            if (chatbotInfo) {
                chatbotInfo.style.display = data.chatbot_flow_id ? 'block' : 'none';
                if (data.chatbot_flow_id) chatbotInfo.textContent = LANG.chatbot_active;
            }
        }

        // Renderiza painel de lead
        activeConvLeadId = data.lead?.id || null;
        renderLeadPanel(data.lead);

        // Avança o anchor do poll para agora, evitando que o próximo poll re-entregue
        // mensagens do histórico já renderizadas pelo openConversation.
        lastPollAt = new Date().toISOString();
    }

    // ── Author badge ──────────────────────────────────────────────────────────────
    // Constroi um badge HTML pra mostrar quem enviou a mensagem outbound.
    // IA/follow-up: avatar circular + nome do agente.
    // Resto (chatbot, automation, scheduled, human, human_phone, event): label texto.
    function buildAuthorBadge(msg) {
        const labels = {
            ai_agent: 'IA',
            followup: 'Follow-up',
            chatbot: 'Chatbot',
            automation: 'Automacao',
            scheduled: 'Agendada',
            human: 'Voce',
            human_phone: 'Celular',
            event: 'Sistema',
        };

        const isAi = msg.sent_by === 'ai_agent' || msg.sent_by === 'followup';
        const agent = msg.sent_by_agent;

        const badge = document.createElement('div');
        badge.className = `msg-author-badge msg-author-${msg.sent_by} fresh`;

        if (isAi && agent && agent.name) {
            const avatarUrl = agent.avatar || '/images/agents-avatar/default.png';
            badge.innerHTML = `
                <img src="${escHtml(avatarUrl)}" class="msg-author-avatar" alt="${escHtml(agent.name)}" onerror="this.style.display='none'">
                <span class="msg-author-name">${escHtml(agent.name)}</span>
            `;
        } else if (msg.sent_by === 'human' && msg.user_name) {
            badge.innerHTML = `<span class="msg-author-name">${escHtml(msg.user_name)}</span>`;
        } else {
            const label = labels[msg.sent_by] || msg.sent_by;
            badge.innerHTML = `<span class="msg-author-name">${escHtml(label)}</span>`;
        }

        // Remove a classe 'fresh' depois de 2s pra animacao nao re-rodar em
        // re-renders subsequentes (ex: mensagem mudou de ack ack)
        setTimeout(() => badge.classList.remove('fresh'), 2000);

        return badge;
    }

    // ── Renderizar mensagens ──────────────────────────────────────────────────────
    function renderMessages(messages, clear = false) {
        const container = document.getElementById('messagesContainer');
        if (clear) {
            container.innerHTML = '';
            lastRenderedDate = null; // reset ao abrir nova conversa
            renderedMsgIds.clear(); // reset deduplicação ao trocar de conversa
        }

        messages.forEach(msg => {
            // Evitar duplicatas: skip se este ID já foi renderizado (ex: poll re-entrega histórico)
            if (msg.id && renderedMsgIds.has(msg.id)) return;
            if (msg.id) renderedMsgIds.add(msg.id);

            const msgDate = msg.sent_at ? new Date(msg.sent_at).toLocaleDateString('pt-BR') : null;

            if (msgDate && msgDate !== lastRenderedDate) {
                lastRenderedDate = msgDate;
                const sep = document.createElement('div');
                sep.className = 'wa-date-sep';
                const today = new Date().toLocaleDateString('pt-BR');
                const yesterday = new Date(Date.now() - 86400000).toLocaleDateString('pt-BR');
                sep.textContent = msgDate === today ? LANG.today : msgDate === yesterday ? LANG.yesterday : msgDate;
                container.appendChild(sep);
            }

            container.appendChild(buildMessageEl(msg));
        });

        container.scrollTop = container.scrollHeight;
    }

    function buildMessageEl(msg) {
        const isNote = msg.type === 'note';
        const isReaction = msg.type === 'reaction';
        const isEvent = msg.type === 'event';

        if (isReaction) {
            return document.createComment('reaction');
        }

        // Evento/Ação (IA ou usuário)
        if (isEvent) {
            const wrap = document.createElement('div');
            wrap.className = 'wa-msg event';
            wrap.dataset.id = msg.id;
            const isAi = (msg.media_mime || '').startsWith('ai_');
            const box = document.createElement('div');
            box.className = 'wa-event-box' + (isAi ? ' ai' : ' user');

            const icon = document.createElement('div');
            icon.className = 'wa-event-icon';
            icon.innerHTML = isAi ? '<i class="bi bi-robot"></i>' : '<i class="bi bi-person-gear"></i>';
            box.appendChild(icon);

            const content = document.createElement('div');
            content.className = 'wa-event-content';

            const title = document.createElement('div');
            title.className = 'wa-event-title';
            title.textContent = msg.media_filename || LANG.action_done;
            content.appendChild(title);

            if (msg.body) {
                const desc = document.createElement('div');
                desc.className = 'wa-event-desc';
                desc.textContent = msg.body;
                content.appendChild(desc);
            }

            if (msg.sent_at) {
                const time = document.createElement('div');
                time.className = 'wa-event-time';
                const d = new Date(msg.sent_at);
                const dateStr = d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
                const timeStr = d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                time.textContent = `${dateStr} ${LANG.at} ${timeStr}`;
                content.appendChild(time);
            }

            box.appendChild(content);
            wrap.appendChild(box);
            return wrap;
        }

        const dir = msg.direction;
        const wrap = document.createElement('div');
        wrap.className = `wa-msg ${isNote ? 'note' : dir}`;
        wrap.dataset.id = msg.id;
        wrap.dataset.wahaId = msg.waha_message_id || '';

        if (isNote) {
            const box = document.createElement('div');
            box.className = 'wa-event-box note-type';
            const icon = document.createElement('div');
            icon.className = 'wa-event-icon';
            icon.innerHTML = '<i class="bi bi-sticky"></i>';
            box.appendChild(icon);
            const content = document.createElement('div');
            content.className = 'wa-event-content';
            const title = document.createElement('div');
            title.className = 'wa-event-title';
            title.textContent = LANG.note_created;
            content.appendChild(title);
            if (msg.body) {
                const desc = document.createElement('div');
                desc.className = 'wa-event-desc';
                desc.textContent = msg.body;
                content.appendChild(desc);
            }
            if (msg.sent_at) {
                const time = document.createElement('div');
                time.className = 'wa-event-time';
                const d = new Date(msg.sent_at);
                const dateStr = d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
                const timeStr = d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                time.textContent = `${dateStr} ${LANG.at} ${timeStr}`;
                content.appendChild(time);
            }
            box.appendChild(content);
            wrap.appendChild(box);
            return wrap;
        }

        // Sender name (only for group messages with sender_name set)
        if (msg.direction === 'inbound' && msg.sender_name) {
            const senderLabel = document.createElement('div');
            senderLabel.className = 'wa-sender-label';
            senderLabel.textContent = msg.sender_name;
            wrap.appendChild(senderLabel);
        }

        // Author badge (sent_by) — so pra outbound, mostra quem mandou:
        // humano, IA, chatbot, automation, scheduled, follow-up.
        // Mensagens antigas (sent_by null) ficam sem badge igual antes.
        if (msg.direction === 'outbound' && msg.sent_by) {
            const badge = buildAuthorBadge(msg);
            if (badge) wrap.appendChild(badge);
        }

        const bubble = document.createElement('div');
        bubble.className = `wa-bubble${msg.is_deleted ? ' deleted' : ''}`;

        if (msg.is_deleted) {
            bubble.innerHTML = '<i class="bi bi-slash-circle" style="margin-right:4px;"></i>' + escHtml(LANG.msg_deleted);
        } else if (msg.type === 'image' && msg.media_url) {
            const imgUrl = escHtml(msg.media_url);
            bubble.innerHTML = `<img src="${imgUrl}" class="wa-img-thumb" onclick="openImageLightbox('${imgUrl}')" alt="Imagem">`;
            if (msg.body) bubble.innerHTML += `<div style="margin-top:6px;font-size:13px;">${formatWhatsappText(msg.body)}</div>`;
        } else if (msg.type === 'audio' && msg.media_url) {
            const apBars = Array.from({length: 28}, () => {
                const h = 4 + Math.floor(Math.random() * 22);
                return `<div class="ap-bar" style="height:${h}px"></div>`;
            }).join('');
            bubble.innerHTML = `<div class="wa-audio-player" data-src="${escHtml(msg.media_url)}">
                <div class="ap-top-row">
                    <button class="ap-play-btn" onclick="apToggle(this)"><i class="bi bi-play-fill"></i></button>
                    <div class="ap-waveform" onclick="apSeek(this,event)">${apBars}</div>
                </div>
                <div class="ap-bottom-row">
                    <span class="ap-timer">0:00</span>
                    <span class="ap-label">${LANG.audio}</span>
                </div>
                <audio preload="metadata" src="${escHtml(msg.media_url)}" style="display:none;"></audio>
            </div>`;
        } else if (msg.type === 'video' && msg.media_url) {
            bubble.innerHTML = `<video src="${escHtml(msg.media_url)}" controls preload="metadata" style="max-width:100%;border-radius:8px;display:block;"></video>`;
            if (msg.body) bubble.innerHTML += `<div style="margin-top:6px;font-size:13px;">${formatWhatsappText(msg.body)}</div>`;
        } else if (msg.type === 'document' && msg.media_url) {
            const fname = escHtml(msg.media_filename || LANG.preview_file.replace('📎 ', ''));
            const docUrl = escHtml(msg.media_url);
            const docIcon = getDocIcon(msg.media_filename || '');
            const ext = (msg.media_filename || '').split('.').pop().toUpperCase().slice(0, 5) || 'FILE';
            bubble.innerHTML = `
                <div class="wa-doc-card">
                    <div class="wa-doc-icon"><i class="bi ${docIcon}"></i></div>
                    <div class="wa-doc-info">
                        <div class="wa-doc-name" title="${fname}">${fname}</div>
                        <div class="wa-doc-meta">${ext}</div>
                    </div>
                    <a href="${docUrl}" download="${fname}" target="_blank" rel="noopener" class="wa-doc-download" title="${escHtml(LANG.download || 'Baixar')}">
                        <i class="bi bi-download"></i>
                    </a>
                </div>`;
            if (msg.body) bubble.innerHTML += `<div class="wa-doc-caption">${formatWhatsappText(msg.body)}</div>`;
        } else if (msg.type === 'share') {
            let inner = '';
            if (msg.media_url) {
                const shareImgUrl = escHtml(msg.media_url);
                inner += `<img src="${shareImgUrl}" class="wa-img-thumb" onclick="openImageLightbox('${shareImgUrl}')" alt="Publicação" onerror="this.style.display='none'">`;
            }
            const postLink = escHtml(msg.body || '');
            if (postLink) {
                inner += `<div style="margin-top:6px;font-size:12px;"><i class="bi bi-instagram" style="color:#e1306c;margin-right:4px;"></i><a href="${postLink}" target="_blank" rel="noopener" style="color:inherit;">${escHtml(LANG.view_post)}</a></div>`;
            }
            if (!inner) inner = `<span style="color:#6b7280;font-style:italic;">${escHtml(LANG.shared_post)}</span>`;
            bubble.innerHTML = inner;
        } else {
            bubble.innerHTML = formatWhatsappText(msg.body);
        }

        wrap.appendChild(bubble);


        if (!isNote) {
            const meta = document.createElement('div');
            meta.className = 'wa-msg-meta';
            const time = msg.sent_at ? new Date(msg.sent_at).toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
            }) : '';
            meta.innerHTML = `<span>${time}</span>`;
            if (dir === 'outbound') {
                const ackIcon = {
                    pending: '🕐',
                    sent: '✓',
                    delivered: '✓✓',
                    read: '✓✓'
                };
                const ackColor = msg.ack === 'read' ? 'color:#3b82f6;' : '';
                meta.innerHTML += `<span style="${ackColor}">${ackIcon[msg.ack] || '✓'}</span>`;
            }
            wrap.appendChild(meta);
        }

        if (dir === 'inbound' && msg.waha_message_id) {
            bubble.addEventListener('dblclick', () => showEmojiPicker(msg.waha_message_id, wrap));
        }

        return wrap;
    }

    function appendMessages(messages) {
        if (!messages?.length) return;
        renderMessages(messages, false);
    }

    // ── Emoji picker simples ──────────────────────────────────────────────────────
    const QUICK_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '🙏'];

    function showEmojiPicker(wahaId, wrap) {
        document.querySelectorAll('.wa-emoji-picker').forEach(e => e.remove());

        reactionTargetId = wahaId;
        const picker = document.createElement('div');
        picker.className = 'wa-emoji-picker open';

        QUICK_EMOJIS.forEach(emoji => {
            const btn = document.createElement('span');
            btn.className = 'wa-emoji-opt';
            btn.textContent = emoji;
            btn.onclick = () => sendReaction(wahaId, emoji);
            picker.appendChild(btn);
        });

        wrap.style.position = 'relative';
        wrap.appendChild(picker);

        setTimeout(() => document.addEventListener('click', closeEmojiPicker, {
            once: true
        }), 50);
    }

    function closeEmojiPicker() {
        document.querySelectorAll('.wa-emoji-picker').forEach(e => e.remove());
    }

    // ── Envio de mensagens ────────────────────────────────────────────────────────
    async function sendMessage() {
        if (!activeConvId) return;
        const input = document.getElementById('messageInput');
        const body = input.value.trim();
        if (!body) return;

        // Schedule mode — send to scheduled messages endpoint
        if (composeMode === 'schedule') {
            const dt = document.getElementById('scheduleDateTime');
            if (!dt || !dt.value) { toastr.warning('Selecione a data e hora do agendamento.'); return; }

            const leadId = document.getElementById('detailsLeadId')?.value || activeConvLeadId;
            if (!leadId) { toastr.warning('Vincule um lead a esta conversa para agendar.'); return; }

            input.value = '';
            autoResize(input);

            try {
                const res = await fetch(`/contatos/${leadId}/mensagens-agendadas`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'text', body: body, send_at: dt.value }),
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success('Mensagem agendada para ' + new Date(dt.value).toLocaleString('pt-BR'));
                    setComposeMode('reply');
                } else {
                    toastr.error(data.message || data.error || 'Erro ao agendar.');
                }
            } catch { toastr.error('Erro de conexão.'); }
            return;
        }

        input.value = '';
        autoResize(input);

        const type = composeMode === 'note' ? 'note' : 'text';

        const formData = new FormData();
        formData.append('type', type);
        formData.append('body', body);

        const res = await fetch(`${convBaseUrl(activeConvId)}/messages`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
            body: formData,
        });

        const data = await res.json();
        if (data.success) {
            appendMessages([data.message]);
        } else {
            toastr.error(data.error || LANG.error_send_msg);
        }
    }

    // ── Image preview + caption before sending ──────────────────────────
    let _pendingImageFile = null;

    function previewImage(input) {
        if (!input.files[0]) return;
        _pendingImageFile = input.files[0];
        const bar = document.getElementById('imagePreviewBar');
        document.getElementById('imagePreviewThumb').src = URL.createObjectURL(_pendingImageFile);
        document.getElementById('imagePreviewName').textContent = _pendingImageFile.name;
        document.getElementById('imageCaption').value = '';
        bar.style.display = 'flex';
        document.getElementById('imageCaption').focus();
        input.value = '';
    }

    function cancelImagePreview() {
        _pendingImageFile = null;
        document.getElementById('imagePreviewBar').style.display = 'none';
    }

    async function confirmSendImage() {
        if (!activeConvId || !_pendingImageFile) return;

        const formData = new FormData();
        formData.append('type', 'image');
        formData.append('file', _pendingImageFile);
        const caption = document.getElementById('imageCaption').value.trim();
        if (caption) formData.append('caption', caption);

        cancelImagePreview();

        const res = await fetch(`${convBaseUrl(activeConvId)}/messages`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
            body: formData,
        });

        const data = await res.json();
        if (data.success) {
            appendMessages([data.message]);
        } else {
            toastr.error(data.error || LANG.error_send_image);
        }
    }

    async function sendDocument(input) {
        if (!activeConvId || !input.files[0]) return;

        const formData = new FormData();
        formData.append('type', 'document');
        formData.append('file', input.files[0]);

        const res = await fetch(`${convBaseUrl(activeConvId)}/messages`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
            body: formData,
        });

        const data = await res.json();
        if (data.success) {
            appendMessages([data.message]);
        } else {
            toastr.error(data.error || LANG.error_send_file);
        }

        input.value = '';
    }

    async function sendReaction(wahaId, emoji) {
        closeEmojiPicker();
        if (!activeConvId) return;

        const formData = new FormData();
        formData.append('waha_message_id', wahaId);
        formData.append('emoji', emoji);

        await fetch(`${convBaseUrl(activeConvId)}/react`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
            body: formData,
        });
    }

    // ── Gravação de áudio ─────────────────────────────────────────────────────────
    async function startRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true
            });
            mediaRecorder = new MediaRecorder(stream, {
                mimeType: 'audio/webm;codecs=opus'
            });
            audioChunks = [];

            mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
            mediaRecorder.start();

            document.getElementById('normalRow').style.display = 'none';
            document.getElementById('recordingRow').style.display = 'flex';

            recordingSeconds = 0;
            recordingTimerInt = setInterval(() => {
                recordingSeconds++;
                const m = Math.floor(recordingSeconds / 60);
                const s = recordingSeconds % 60;
                document.getElementById('recordingTimer').textContent = `${m}:${s.toString().padStart(2,'0')}`;
            }, 1000);
        } catch (e) {
            toastr.error(LANG.error_mic);
        }
    }

    /* ── Custom Audio Player ── */
    function apToggle(btn) {
        const player = btn.closest('.wa-audio-player');
        const audio  = player.querySelector('audio');
        const icon   = btn.querySelector('i');

        if (audio.paused) {
            // Pausa todos os outros players
            document.querySelectorAll('.wa-audio-player audio').forEach(a => {
                if (a !== audio) {
                    a.pause();
                    const ob = a.closest('.wa-audio-player').querySelector('.ap-play-btn i');
                    ob.className = 'bi bi-play-fill';
                    a.closest('.wa-audio-player').querySelectorAll('.ap-bar').forEach(b => b.classList.remove('playing'));
                }
            });
            audio.play();
            icon.className = 'bi bi-pause-fill';
            player.querySelectorAll('.ap-bar').forEach(b => b.classList.add('playing'));
            audio.ontimeupdate = () => apUpdateProgress(player, audio);
            audio.onended = () => {
                icon.className = 'bi bi-play-fill';
                player.querySelectorAll('.ap-bar').forEach(b => b.classList.remove('playing', 'played'));
                player.querySelector('.ap-timer').textContent = '0:00';
            };
        } else {
            audio.pause();
            icon.className = 'bi bi-play-fill';
            player.querySelectorAll('.ap-bar').forEach(b => b.classList.remove('playing'));
        }
    }

    function apSeek(waveformEl, e) {
        const player = waveformEl.closest('.wa-audio-player');
        const audio  = player.querySelector('audio');
        if (!audio.duration) return;
        const rect = waveformEl.getBoundingClientRect();
        const pct  = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
        audio.currentTime = pct * audio.duration;
        apUpdateProgress(player, audio);
    }

    function apUpdateProgress(player, audio) {
        if (!audio.duration) return;
        const pct    = audio.currentTime / audio.duration;
        const bars   = player.querySelectorAll('.ap-bar');
        const cutoff = Math.floor(pct * bars.length);
        bars.forEach((b, i) => b.classList.toggle('played', i < cutoff));
        const m = Math.floor(audio.currentTime / 60);
        const s = Math.floor(audio.currentTime % 60);
        player.querySelector('.ap-timer').textContent = `${m}:${s.toString().padStart(2, '0')}`;
    }

    function cancelRecording() {
        if (mediaRecorder) {
            mediaRecorder.stop();
            mediaRecorder.stream.getTracks().forEach(t => t.stop());
        }
        clearInterval(recordingTimerInt);
        document.getElementById('recordingRow').style.display = 'none';
        document.getElementById('normalRow').style.display = 'flex';
    }

    async function stopAndSendRecording() {
        if (!mediaRecorder) return;

        mediaRecorder.onstop = async () => {
            const blob = new Blob(audioChunks, {
                type: 'audio/webm'
            });
            const file = new File([blob], 'audio.webm', {
                type: 'audio/webm'
            });

            const formData = new FormData();
            formData.append('type', 'audio');
            formData.append('file', file);

            cancelRecording();

            const res = await fetch(`${convBaseUrl(activeConvId)}/messages`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                },
                body: formData,
            });

            const data = await res.json();
            if (data.success) {
                appendMessages([data.message]);
            } else {
                toastr.error(data.error || LANG.error_send_audio);
            }
        };

        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(t => t.stop());
        clearInterval(recordingTimerInt);
    }

    // ── Compose mode (reply / note) ───────────────────────────────────────────────
    function setComposeMode(mode) {
        composeMode = mode;
        const textarea = document.getElementById('messageInput');
        document.getElementById('tabReply').classList.toggle('active', mode === 'reply');
        document.getElementById('tabNote').classList.toggle('active', mode === 'note');
        document.getElementById('tabSchedule').classList.toggle('active', mode === 'schedule');
        textarea.classList.toggle('note-mode', mode === 'note');

        // Schedule row
        let schedRow = document.getElementById('scheduleRow');
        if (mode === 'schedule') {
            textarea.placeholder = LANG.placeholder_schedule ?? 'Mensagem para agendar...';
            if (!schedRow) {
                schedRow = document.createElement('div');
                schedRow.id = 'scheduleRow';
                schedRow.style.cssText = 'display:flex;align-items:center;gap:8px;padding:6px 14px;background:#eff6ff;border-bottom:1px solid #bfdbfe;';
                schedRow.innerHTML = '<i class="bi bi-clock" style="color:#0085f3;font-size:13px;"></i>' +
                    '<span style="font-size:11px;color:#374151;font-weight:600;">Agendar para:</span>' +
                    '<input type="datetime-local" id="scheduleDateTime" style="padding:4px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;color:#1a1d23;" />';
                const composeArea = document.getElementById('composeArea');
                const normalRow = document.getElementById('normalRow');
                composeArea.insertBefore(schedRow, normalRow);
            }
            schedRow.style.display = 'flex';
            // Set default to 1 hour from now
            const now = new Date(Date.now() + 3600000);
            const dt = document.getElementById('scheduleDateTime');
            if (dt && !dt.value) dt.value = now.toISOString().slice(0, 16);
        } else {
            if (schedRow) schedRow.style.display = 'none';
            textarea.placeholder = mode === 'note' ? LANG.placeholder_note : LANG.placeholder;
        }

        document.getElementById('normalRow').querySelectorAll('.wa-btn-icon').forEach(b => {
            b.style.display = mode === 'note' ? 'none' : '';
        });
    }

    // ── Status da conversa ────────────────────────────────────────────────────────
    async function toggleConvStatus() {
        if (!activeConvId) return;
        const newStatus = activeConvStatus === 'open' ? 'closed' : 'open';

        const res = await fetch(`${convBaseUrl(activeConvId)}/status`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                status: newStatus
            }),
        });

        const data = await res.json();
        if (data.success) {
            activeConvStatus = newStatus;
            const convEl = document.querySelector(`[data-conv-id="${activeConvId}"]`);
            if (convEl) convEl.dataset.status = newStatus;
            const statusEl = document.getElementById('detailsStatus');
            if (statusEl) statusEl.textContent = newStatus === 'open' ? LANG.status_open : LANG.status_closed;
            document.getElementById('btnCloseConv').title = newStatus === 'open' ? LANG.close_conversation : LANG.reopen;
            document.getElementById('btnCloseConv').querySelector('i').className = newStatus === 'open' ?
                'bi bi-check-circle' : 'bi bi-arrow-counterclockwise';
            toastr.success(newStatus === 'closed' ? LANG.conv_closed : LANG.conv_reopened);
        }
    }

    // ── Deletar conversa ──────────────────────────────────────────────────────────
    function deleteConversation() {
        if (!activeConvId) return;
        document.getElementById('delConvModal').classList.add('open');
    }

    async function _doDeleteConversation() {
        document.getElementById('delConvModal').classList.remove('open');
        if (!activeConvId) return;

        const res = await fetch(convBaseUrl(activeConvId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
        });
        const data = await res.json().catch(() => ({}));

        if (data.success) {
            // Remove da lista lateral
            const convEl = document.querySelector(`[data-conv-id="${activeConvId}"]`);
            if (convEl) convEl.remove();

            // Limpa área de chat
            activeConvId = null;
            document.getElementById('chatHeader').style.display = 'none';
            document.getElementById('messagesContainer').style.display = 'none';
            document.getElementById('composeArea').style.display = 'none';
            document.getElementById('messagesContainer').innerHTML = '';
            updateTotalUnread();
        } else {
            toastr.error(LANG.error_delete_conv);
        }
    }

    // ── Lead / CRM ────────────────────────────────────────────────────────────────
    function renderLeadPanel(lead) {
        activeConvLeadId = lead?.id || null;
        const leadSection = document.getElementById('leadSection');
        const noLeadSection = document.getElementById('noLeadSection');

        if (!lead) {
            activeLeadId = null;
            leadSection.style.display = 'none';
            noLeadSection.style.display = '';
            return;
        }

        activeLeadId = lead.id;
        leadSection.style.display = '';
        noLeadSection.style.display = 'none';

        document.getElementById('leadNameDisplay').textContent = lead.name || '';
        const link = document.getElementById('leadProfileLink');
        link.href = `/contatos/${lead.id}`;

        // Popula pipeline select
        const pipelineSel = document.getElementById('pipelineSelect');
        pipelineSel.value = lead.pipeline_id || '';

        // Popula stages do pipeline selecionado
        populateStages(lead.pipeline_id, lead.stage_id);
    }

    // ── Criar lead a partir do chat ───────────────────────────────────────────────
    function showCreateLeadForm() {
        document.getElementById('noLeadDefault').style.display = 'none';
        document.getElementById('createLeadForm').style.display = '';
        onCreateLeadPipelineChange();
    }

    function cancelCreateLead() {
        document.getElementById('createLeadForm').style.display = 'none';
        document.getElementById('noLeadDefault').style.display = '';
    }

    // ── Vincular lead existente ────────────────────────────────────────────
    function showLinkExistingLead() {
        document.getElementById('linkExistingPanel').style.display = 'block';
        document.getElementById('leadSearchInput').focus();
    }

    function hideLinkExistingLead() {
        document.getElementById('linkExistingPanel').style.display = 'none';
        document.getElementById('leadSearchInput').value = '';
        document.getElementById('leadSearchResults').innerHTML = '';
    }

    let _leadSearchTimer = null;
    function onLeadSearch(val) {
        clearTimeout(_leadSearchTimer);
        document.getElementById('leadSearchResults').innerHTML = '';
        if (val.trim().length < 2) return;
        _leadSearchTimer = setTimeout(() => doLeadSearch(val.trim()), 300);
    }

    async function doLeadSearch(q) {
        const res = await fetch(`${CHATS_BASE}/leads/search?q=${encodeURIComponent(q)}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        });
        const data = await res.json();
        const el = document.getElementById('leadSearchResults');
        if (!data.leads || !data.leads.length) {
            el.innerHTML = `<p style="font-size:12px;color:#9ca3af;padding:6px 0;">${escHtml(LANG.no_lead_found)}</p>`;
            return;
        }
        el.innerHTML = data.leads.map(l => `
            <div class="lead-search-item" onclick='linkExistingLead(${l.id}, ${JSON.stringify(l)})'>
                <span class="lead-search-name">${escapeHtml(l.name)}</span>
                ${l.phone ? `<span class="lead-search-phone">${escapeHtml(l.phone)}</span>` : ''}
            </div>
        `).join('');
    }

    async function linkExistingLead(leadId, lead) {
        const url = activeConvChannel === 'instagram'
            ? `${CHATS_BASE}/instagram-conversations/${activeConvId}/link-lead`
            : activeConvChannel === 'website'
            ? `${CHATS_BASE}/website-conversations/${activeConvId}/link-lead`
            : `${convBaseUrl(activeConvId)}/link-lead`;

        const res = await fetch(url, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ lead_id: leadId }),
        });
        const data = await res.json();
        if (data.success) {
            toastr.success(LANG.lead_linked.replace(':name', escapeHtml(lead.name)));
            hideLinkExistingLead();
            renderLeadPanel(lead);
        } else {
            toastr.error(LANG.error_link_lead);
        }
    }

    // ── Desvincular lead da conversa ──────────────────────────────────────
    async function unlinkCurrentLead() {
        if (!confirm(LANG.unlink_confirm)) return;
        const url = activeConvChannel === 'instagram'
            ? `${CHATS_BASE}/instagram-conversations/${activeConvId}/unlink-lead`
            : activeConvChannel === 'website'
            ? `${CHATS_BASE}/website-conversations/${activeConvId}/unlink-lead`
            : `${convBaseUrl(activeConvId)}/unlink-lead`;

        const res = await fetch(url, {
            method: 'PUT',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        });
        const data = await res.json();
        if (data.success) {
            toastr.success(LANG.lead_unlinked);
            renderLeadPanel(null);
        } else {
            toastr.error(LANG.error_unlink_lead);
        }
    }

    function onCreateLeadPipelineChange() {
        const sel = document.getElementById('createLeadPipeline');
        const stages = JSON.parse(sel.selectedOptions[0]?.dataset.stages || '[]');
        const stageSel = document.getElementById('createLeadStage');
        stageSel.innerHTML = `<option value="">${escHtml(LANG.select_stage)}</option>`;
        stages.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name;
            stageSel.appendChild(opt);
        });
        if (stages.length) stageSel.value = stages[0].id;
    }

    async function doCreateLead() {
        const pipelineId = document.getElementById('createLeadPipeline').value;
        const stageId    = document.getElementById('createLeadStage').value;
        if (!pipelineId || !stageId || !activeConvId) return;

        const contactName  = document.getElementById('detailsName')?.textContent?.trim() || 'Contato';
        const contactPhone = document.getElementById('detailsPhone')?.textContent?.trim() || null;

        const createRes = await fetch(LEAD_STORE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                name: contactName,
                phone: contactPhone,
                pipeline_id: parseInt(pipelineId),
                stage_id: parseInt(stageId),
                source: activeConvChannel,
            }),
        });
        const lead = await createRes.json();
        if (!lead.id) return;

        const linkUrl = activeConvChannel === 'instagram'
            ? `${CHATS_BASE}/instagram-conversations/${activeConvId}/link-lead`
            : activeConvChannel === 'website'
            ? `${CHATS_BASE}/website-conversations/${activeConvId}/link-lead`
            : `${convBaseUrl(activeConvId)}/link-lead`;
        await fetch(linkUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ lead_id: lead.id }),
        });

        renderLeadPanel(lead);
        cancelCreateLead();
    }

    function populateStages(pipelineId, selectedStageId = null) {
        const stageSel = document.getElementById('stageSelect');
        const pipeline = PIPELINES.find(p => p.id == pipelineId);

        stageSel.innerHTML = `<option value="">${escHtml(LANG.select_stage)}</option>`;

        if (pipeline?.stages) {
            pipeline.stages.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name;
                if (s.id == selectedStageId) opt.selected = true;
                stageSel.appendChild(opt);
            });
        }
    }

    function onPipelineChange() {
        const pipelineId = document.getElementById('pipelineSelect').value;
        populateStages(pipelineId);
        saveLeadCrm();
    }

    async function saveLeadCrm() {
        if (!activeConvId || !activeLeadId) return;
        const pipelineId = document.getElementById('pipelineSelect').value;
        const stageId = document.getElementById('stageSelect').value;

        if (!pipelineId || !stageId) return;

        await fetch(`${convBaseUrl(activeConvId)}/lead`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                pipeline_id: parseInt(pipelineId),
                stage_id: parseInt(stageId)
            }),
        });
    }

    // ── Atribuição ────────────────────────────────────────────────────────────────
    async function assignDepartment() {
        if (!activeConvId) return;
        const sel = document.getElementById('departmentSelect');
        if (!sel) return;
        const deptId = sel.value;

        try {
            const res = await fetch(`${convBaseUrl(activeConvId)}/department`, {
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ department_id: deptId || null }),
            });
            const data = await res.json();
            if (data.success !== false) {
                toastr.success(LANG.dept_updated);
                // Atualizar badge na sidebar
                const el = document.querySelector(`.wa-conv-item[data-conv-id="${activeConvId}"]`);
                if (el) el.dataset.departmentId = deptId || '';
            } else {
                toastr.error(data.message || LANG.error_dept);
            }
        } catch (e) {
            toastr.error(LANG.error_connection);
        }
    }

    async function assignUser() {
        if (!activeConvId) return;
        const userId = document.getElementById('assignSelect').value;

        await fetch(`${convBaseUrl(activeConvId)}/assign`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: userId
            }),
        });
    }

    async function assignAiAgent() {
        if (!activeConvId) return;
        const sel = document.getElementById('aiAgentSelect');
        const agentId = sel ? sel.value : '';

        try {
            const res = await fetch(`${convBaseUrl(activeConvId)}/ai-agent`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ai_agent_id: agentId || null
                }),
            });
            const data = await res.json();
            if (data.success) {
                updateAiAgentStatusBadge(agentId || null);
                toastr.success(agentId ? LANG.ai_enabled : LANG.ai_removed);
            }
        } catch {
            toastr.error(LANG.error_ai);
        }
    }

    function updateAiAgentStatusBadge(agentId) {
        const badge = document.getElementById('aiAgentStatus');
        if (!badge) return;
        if (agentId) {
            badge.textContent = LANG.ai_active;
            badge.style.color = '#16a34a';
        } else {
            badge.textContent = LANG.ai_inactive;
            badge.style.color = '#9ca3af';
        }
    }

    async function assignChatbotFlow() {
        if (!activeConvId) return;
        const sel = document.getElementById('chatbotFlowSelect');
        const flowId = sel ? sel.value : '';

        try {
            const res = await fetch(`${convBaseUrl(activeConvId)}/chatbot-flow`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    chatbot_flow_id: flowId || null
                }),
            });
            const data = await res.json();
            if (data.success) {
                toastr.success(flowId ? LANG.chatbot_enabled : LANG.chatbot_disabled);
                const info = document.getElementById('chatbotVarsInfo');
                if (info) {
                    if (flowId) {
                        info.style.display = 'block';
                        info.textContent = LANG.chatbot_reset;
                    } else {
                        info.style.display = 'none';
                    }
                }
            }
        } catch {
            toastr.error(LANG.error_chatbot);
        }
    }

    // ── Painel de detalhes ────────────────────────────────────────────────────────
    function toggleDetails() {
        document.getElementById('detailsPanel').classList.toggle('open');
    }

    // ── Edição de contato ─────────────────────────────────────────────────────────
    let _convTags = [];

    function toggleContactEdit() {
        const view = document.getElementById('contactViewMode');
        const edit = document.getElementById('contactEditMode');
        const editing = edit.style.display !== 'none';
        if (editing) {
            // cancelar — restaura view
            view.style.display = '';
            edit.style.display = 'none';
        } else {
            document.getElementById('editContactName').value = document.getElementById('detailsName').textContent;
            document.getElementById('editContactPhone').value = document.getElementById('detailsPhone').dataset.raw || '';
            view.style.display = 'none';
            edit.style.display = '';
            document.getElementById('editContactName').focus();
        }
    }

    async function saveContact() {
        if (!activeConvId) return;
        const name = document.getElementById('editContactName').value.trim();
        const phone = document.getElementById('editContactPhone').value.trim().replace(/\D/g, '');
        if (!name && !phone) return;

        const res = await fetch(inboxContactUrl(activeConvId), {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name,
                phone
            }),
        });
        if (!res.ok) return;
        const data = await res.json();
        const c = data.conversation;

        const cDisplayName = c.contact_name || (c.is_group ? LANG.group : c.phone);
        // Actualiza header e detalhes
        document.getElementById('detailsName').textContent = cDisplayName;
        setPhoneDisplay(document.getElementById('detailsPhone'), c.phone);
        document.getElementById('chatContactName').textContent = cDisplayName;
        setPhoneDisplay(document.getElementById('chatContactPhone'), c.phone);

        // Actualiza card na sidebar
        const el = document.querySelector(`[data-conv-id="${activeConvId}"]`);
        if (el) {
            el.dataset.phone = c.phone;
            const nameEl = el.querySelector('.wa-conv-name');
            if (nameEl) nameEl.textContent = cDisplayName;
        }
        setAvatar(document.getElementById('chatAvatar'), cDisplayName, el?.dataset.picture || '');

        toggleContactEdit();
    }

    // ── Tags ──────────────────────────────────────────────────────────────────────
    function renderTags(tags) {
        _convTags = tags || [];
        const container = document.getElementById('tagsList');
        container.innerHTML = '';
        _convTags.forEach((tag, i) => {
            const color = tagColorMap[tag] || null;
            const span = document.createElement('span');
            span.className = 'wa-tag';
            if (color) {
                span.style.background = hexToRgba(color, .12);
                span.style.color = color;
                span.style.border = `1px solid ${hexToRgba(color, .3)}`;
            }
            span.innerHTML = `${escHtml(tag)} <span class="wa-tag-remove" onclick="removeTag(${i})" title="Remover" style="color:inherit;opacity:.6;">×</span>`;
            container.appendChild(span);
        });
        updatePredefinedChipsState();
    }

    async function addTag() {
        const input = document.getElementById('tagInput');
        const tag = input.value.trim();
        if (!tag || !activeConvId) return;
        input.value = '';
        if (_convTags.includes(tag)) return;
        _convTags = [..._convTags, tag];
        await saveTags();
    }

    async function removeTag(index) {
        if (!activeConvId) return;
        _convTags = _convTags.filter((_, i) => i !== index);
        await saveTags();
    }

    async function togglePredefinedTag(tagName) {
        if (!activeConvId) return;
        if (_convTags.includes(tagName)) {
            _convTags = _convTags.filter(t => t !== tagName);
        } else {
            _convTags = [..._convTags, tagName];
        }
        await saveTags();
    }

    function updatePredefinedChipsState() {
        document.querySelectorAll('.predefined-tag-chip').forEach(btn => {
            const active = _convTags.includes(btn.dataset.tagName);
            btn.style.opacity = active ? '1' : '0.45';
            btn.style.fontWeight = active ? '700' : '600';
        });
    }

    async function saveTags() {
        await fetch(inboxContactUrl(activeConvId), {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                tags: _convTags
            }),
        });
        renderTags(_convTags);
        // Actualiza card na sidebar
        const el = document.querySelector(`[data-conv-id="${activeConvId}"]`);
        if (el) {
            let tagsEl = el.querySelector('.wa-conv-tags');
            if (_convTags.length > 0) {
                if (!tagsEl) {
                    tagsEl = document.createElement('div');
                    tagsEl.className = 'wa-conv-tags';
                    el.querySelector('.wa-conv-info').appendChild(tagsEl);
                }
                tagsEl.innerHTML = _convTags.map(t => {
                    const c = tagColorMap[t] || null;
                    const s = c ? `style="background:${hexToRgba(c,.12)};color:${c};border:1px solid ${hexToRgba(c,.3)};"` : '';
                    return `<span class="wa-tag" ${s}>${escHtml(t)}</span>`;
                }).join('');
            } else if (tagsEl) {
                tagsEl.remove();
            }
        }
    }

    // ── Atualizar conversa na sidebar ─────────────────────────────────────────────
    function updateConvInSidebar(conv) {
        let el = document.querySelector(`[data-conv-id="${conv.id}"]`);

        if (!el) {
            el = document.createElement('div');
            el.className = 'wa-conv-item';
            el.dataset.convId          = conv.id;
            el.dataset.phone           = conv.phone || '';
            el.dataset.status          = conv.status || 'open';
            el.dataset.channel         = conv.channel || 'whatsapp';
            el.dataset.assignedUserId  = conv.assigned_user_id || '';
            el.dataset.departmentId    = conv.department_id || '';
            el.dataset.instanceLabel   = conv.instance_label || '';
            el.dataset.tags            = JSON.stringify(conv.tags || []);
            el.onclick = function() {
                openConversation(conv.id, this);
            };
            document.getElementById('convList').prepend(el);
        }

        // Update picture attribute whenever we have fresh data
        if (conv.contact_picture) el.dataset.picture = conv.contact_picture;

        const preview = conv.last_message_type === 'image'    ? LANG.preview_image    :
            conv.last_message_type === 'video'    ? '🎥 Video'      :
            conv.last_message_type === 'share'    ? LANG.preview_post  :
            conv.last_message_type === 'audio'    ? LANG.preview_audio :
            conv.last_message_type === 'document' ? LANG.preview_file  :
            conv.last_message_type === 'event'    ? LANG.action_done   :
            conv.last_message_type === 'note'     ? LANG.preview_note  :
            (conv.last_message_body || '').substring(0, 40);

        const channel = el.dataset.channel || 'whatsapp';
        const convDisplayName = conv.contact_name ||
            (channel === 'website' ? ('Visitante #' + (conv.phone || '').substring(0, 8)) :
            (conv.is_group ? LANG.group : conv.phone));
        const initial = (convDisplayName || '?').charAt(0).toUpperCase();
        const pictureUrl = conv.contact_picture || el.dataset.picture || '';
        const avatarInner = pictureUrl
            ? `<img src="${pictureUrl}" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover;" onerror="this.parentElement.textContent='${initial}'">`
            : initial;
        const timeAgo = conv.last_message_at ? timeRelative(conv.last_message_at) : '';
        const chanIcon = channel === 'instagram' ? '<i class="bi bi-instagram"></i>' :
                         channel === 'website'   ? '<i class="bi bi-globe"></i>' :
                                                   '<i class="bi bi-whatsapp"></i>';
        const chanTitle = channel === 'instagram' ? 'Instagram' : channel === 'website' ? 'Website' : 'WhatsApp';
        const unread = conv.unread_count > 0 && conv.id !== activeConvId ?
            `<span class="wa-unread-dot">${conv.unread_count}</span>` : '';
        const tags = (conv.tags || []).map(t => {
            const c = tagColorMap[t] || null;
            const s = c ? `style="background:${hexToRgba(c,.12)};color:${c};border:1px solid ${hexToRgba(c,.3)};"` : '';
            return `<span class="wa-tag" ${s}>${escHtml(t)}</span>`;
        }).join('');
        const tagsRow = tags ? `<div class="wa-conv-tags">${tags}</div>` : '';

        el.innerHTML = `
        <div class="wa-conv-avatar-wrap">
            <div class="wa-conv-avatar">${avatarInner}</div>
            <span class="wa-channel-icon ${channel}" title="${chanTitle}">${chanIcon}</span>
        </div>
        <div class="wa-conv-info">
            <div class="wa-conv-top">
                <span class="wa-conv-name">${escHtml(convDisplayName)}</span>
                <span class="wa-conv-time">${timeAgo}</span>
            </div>
            <div class="wa-conv-bottom">
                <span class="wa-conv-preview">${escHtml(preview)}</span>
                ${unread}
            </div>
            ${tagsRow}
        </div>`;

        el.dataset.tags = JSON.stringify(conv.tags || []);

        el.dataset.status = conv.status;
        document.getElementById('convList').prepend(el);
        updateTotalUnread();
    }

    function updateTotalUnread() {
        let total = 0;
        document.querySelectorAll('.wa-unread-dot').forEach(d => {
            total += parseInt(d.textContent || '0', 10);
        });
        const badge = document.getElementById('totalUnreadBadge');
        if (badge) {
            badge.textContent = total;
            badge.style.display = total > 0 ? '' : 'none';
        }
    }

    // ── Utilitários ───────────────────────────────────────────────────────────────
    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 120) + 'px';
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // ── Image Lightbox ────────────────────────────────────────────
    function openImageLightbox(url) {
        if (!url) return;
        const lb = document.getElementById('waImgLightbox');
        const img = document.getElementById('waImgLightboxImg');
        const dl  = document.getElementById('waImgLightboxDownload');
        if (!lb || !img) return;
        img.src = url;
        if (dl) dl.href = url;
        lb.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeImageLightbox(e) {
        // Click no overlay (não no conteúdo) ou no botão fecha
        if (e && e.target && e.target.id !== 'waImgLightbox' && !e.target.closest('.wa-img-lightbox-close')) {
            return;
        }
        const lb = document.getElementById('waImgLightbox');
        if (!lb) return;
        lb.classList.remove('open');
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const lb = document.getElementById('waImgLightbox');
            if (lb && lb.classList.contains('open')) closeImageLightbox({ target: lb });
        }
    });

    // ── Document icon picker by extension ──────────────────────────
    function getDocIcon(filename) {
        const ext = (filename || '').toLowerCase().split('.').pop();
        const map = {
            pdf:  'bi-file-earmark-pdf',
            doc:  'bi-file-earmark-word',
            docx: 'bi-file-earmark-word',
            xls:  'bi-file-earmark-excel',
            xlsx: 'bi-file-earmark-excel',
            csv:  'bi-file-earmark-spreadsheet',
            ppt:  'bi-file-earmark-ppt',
            pptx: 'bi-file-earmark-ppt',
            zip:  'bi-file-earmark-zip',
            rar:  'bi-file-earmark-zip',
            '7z': 'bi-file-earmark-zip',
            txt:  'bi-file-earmark-text',
            mp3:  'bi-file-earmark-music',
            wav:  'bi-file-earmark-music',
            mp4:  'bi-file-earmark-play',
            mov:  'bi-file-earmark-play',
            avi:  'bi-file-earmark-play',
            jpg:  'bi-file-earmark-image',
            jpeg: 'bi-file-earmark-image',
            png:  'bi-file-earmark-image',
            gif:  'bi-file-earmark-image',
            webp: 'bi-file-earmark-image',
        };
        return map[ext] || 'bi-file-earmark';
    }

    function formatWhatsappText(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>')
            .replace(/_([^_\n]+)_/g, '<em>$1</em>')
            .replace(/~([^~\n]+)~/g, '<s>$1</s>')
            .replace(/`([^`\n]+)`/g, '<code style="background:rgba(0,0,0,.06);padding:1px 4px;border-radius:3px;font-family:monospace;font-size:12px;">$1</code>')
            .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline;">$1</a>')
            .replace(/\n/g, '<br>');
    }

    function timeRelative(iso) {
        const diff = (Date.now() - new Date(iso)) / 1000;
        if (diff < 60) return LANG.now;
        if (diff < 3600) return Math.floor(diff / 60) + 'm';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        return new Date(iso).toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit'
        });
    }

    // ── Auto-abre conversa via ?open=ID (vindo do Kanban) ─────────────────────
    (function () {
        const openId = new URLSearchParams(location.search).get('open');
        if (!openId) return;
        const el = document.querySelector(`[data-conv-id="${openId}"]`);
        if (el) {
            openConversation(parseInt(openId), el);
        }
    })();

    // ── IA Analista — Sugestões ────────────────────────────────────────────────
    const ANALYST_BASE  = '{{ rtrim(url("/chats"), "/") }}';
    const ANALYST_CSRF  = '{{ csrf_token() }}';
    const TYPE_ICONS    = { stage_change: 'bi-arrow-right-circle', add_tag: 'bi-tag', add_note: 'bi-journal-text', fill_field: 'bi-input-cursor-text', update_lead: 'bi-person-gear' };

    function loadAnalystSuggestions(convId) {
        const section = document.getElementById('analystSection');
        if (!section) return;

        fetch(`${ANALYST_BASE}/${convId}/analyst-suggestions`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => renderAnalystSuggestions(data.suggestions || []))
        .catch(() => {});
    }

    function renderAnalystSuggestions(suggestions) {
        const section      = document.getElementById('analystSection');
        const list         = document.getElementById('analystList');
        const approveAllBtn = document.getElementById('approveAllBtn');
        if (!section || !list) return;

        section.style.display = '';

        if (!suggestions.length) {
            list.innerHTML = `<div style="font-size:11px;color:#9ca3af;padding:4px 0;">${escHtml(LANG.no_suggestions)}</div>`;
            approveAllBtn.style.display = 'none';
            return;
        }

        list.innerHTML = suggestions.map(s => {
            const icon  = TYPE_ICONS[s.type] || 'bi-lightbulb';
            const label = buildSuggestionLabel(s);
            const reason = s.reason ? `<div style="font-size:10px;color:#6b7280;margin:2px 0 4px;">"${escHtml(s.reason)}"</div>` : '';
            return `<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:8px;margin-bottom:6px;">
                      <div style="font-size:11px;font-weight:600;color:#1e40af;"><i class="bi ${icon}"></i> ${escHtml(label)}</div>
                      ${reason}
                      <div style="display:flex;gap:6px;margin-top:4px;">
                        <button onclick="approveSuggestion(${s.id}, this)" type="button"
                                style="flex:1;padding:3px 0;background:#0085f3;color:#fff;border:none;border-radius:6px;font-size:10px;font-weight:600;cursor:pointer;">
                          <i class="bi bi-check-lg"></i> Aprovar
                        </button>
                        <button onclick="rejectSuggestion(${s.id}, this)" type="button"
                                style="flex:1;padding:3px 0;background:#f3f4f6;color:#374151;border:none;border-radius:6px;font-size:10px;font-weight:600;cursor:pointer;">
                          <i class="bi bi-x-lg"></i> Rejeitar
                        </button>
                      </div>
                    </div>`;
        }).join('');

        approveAllBtn.style.display = suggestions.length > 1 ? '' : 'none';
    }

    function buildSuggestionLabel(s) {
        const p = s.payload || {};
        switch (s.type) {
            case 'stage_change': return `Mover para "${p.stage_name || 'etapa'}"`;
            case 'add_tag':      return `Tag: "${p.tag}"`;
            case 'add_note':     return `Nota: ${(p.note || '').substring(0, 60)}${(p.note||'').length > 60 ? '…' : ''}`;
            case 'fill_field':   return `Campo "${p.label || p.name}": ${p.value}`;
            case 'update_lead':  return `Atualizar ${p.field}: ${p.value}`;
            default:             return s.type_label || s.type;
        }
    }

    function approveSuggestion(id, btn) {
        btn.disabled = true;
        fetch(`/analyst-suggestions/${id}/approve`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': ANALYST_CSRF, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(() => {
            if (window.toastr) toastr.success(LANG.suggestion_applied, '', { timeOut: 2000 });
            loadAnalystSuggestions(activeConvId);
            if (window.loadIntentSignals) loadIntentSignals();
        })
        .catch(() => { btn.disabled = false; });
    }

    function rejectSuggestion(id, btn) {
        btn.disabled = true;
        fetch(`/analyst-suggestions/${id}/reject`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': ANALYST_CSRF, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(() => {
            loadAnalystSuggestions(activeConvId);
            if (window.loadIntentSignals) loadIntentSignals();
        })
        .catch(() => { btn.disabled = false; });
    }

    function approveAllSuggestions() {
        if (!activeConvId) return;
        const btn = document.getElementById('approveAllBtn');
        if (btn) btn.disabled = true;
        fetch(`${ANALYST_BASE}/${activeConvId}/analyst-suggestions/approve-all`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': ANALYST_CSRF, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (window.toastr) toastr.success(LANG.suggestions_applied.replace(':count', data.approved), '', { timeOut: 3000 });
            loadAnalystSuggestions(activeConvId);
            if (window.loadIntentSignals) loadIntentSignals();
        })
        .catch(() => { if (btn) btn.disabled = false; });
    }

    function triggerAnalysis() {
        if (!activeConvId) return;
        const btn = document.getElementById('analyzeBtn');
        if (btn) { btn.textContent = LANG.analyzing; btn.disabled = true; }
        fetch(`${ANALYST_BASE}/${activeConvId}/analyze`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': ANALYST_CSRF, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            renderAnalystSuggestions(data.suggestions || []);
            if (window.loadIntentSignals) loadIntentSignals();
        })
        .catch(() => {})
        .finally(() => {
            if (btn) { btn.textContent = LANG.analyze; btn.disabled = false; }
        });
    }

    // ── Mensagens Rápidas ─────────────────────────────────────────────────────
    let _quickMsgs = {!! json_encode($quickMessages ?? []) !!};
    const QM_BASE  = '{{ route("chats.quick-messages.index") }}';
    let _qmSelectedIdx = -1;

    // ── Popup "/" ─────────────────────────────────────────────────────────────
    function handleQmTrigger(textarea) {
        const val = textarea.value;
        if (val.startsWith('/')) {
            const query = val.slice(1).toLowerCase();
            const filtered = _quickMsgs.filter(m =>
                m.title.toLowerCase().includes(query) ||
                m.body.toLowerCase().includes(query)
            );
            renderQmPopup(filtered);
            document.getElementById('quickMsgPopup').style.display = 'block';
        } else {
            closeQmPopup();
        }
    }

    function renderQmPopup(items) {
        const list = document.getElementById('quickMsgList');
        _qmSelectedIdx = -1;
        if (!items.length) {
            list.innerHTML = `<div style="padding:12px 14px;font-size:13px;color:#9ca3af;">${escHtml(LANG.no_messages_found)}</div>`;
            return;
        }
        list.innerHTML = items.map((m, i) => `
            <div class="qm-popup-item" data-idx="${i}" onclick="insertQm(${m.id})"
                 style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f7f8fa;transition:background .1s;">
                <div style="font-size:13px;font-weight:700;color:#1a1d23;">${escapeHtml(m.title)}</div>
                <div style="font-size:12px;color:#6b7280;margin-top:2px;
                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:300px;">
                    ${escapeHtml(m.body.substring(0, 80))}${m.body.length > 80 ? '…' : ''}
                </div>
            </div>`
        ).join('');
        // hover styles
        list.querySelectorAll('.qm-popup-item').forEach(el => {
            el.addEventListener('mouseenter', () => el.style.background = '#f0f7ff');
            el.addEventListener('mouseleave', () => {
                if (parseInt(el.dataset.idx) !== _qmSelectedIdx) el.style.background = '';
            });
        });
    }

    function closeQmPopup() {
        document.getElementById('quickMsgPopup').style.display = 'none';
        _qmSelectedIdx = -1;
    }

    function insertQm(id) {
        const msg = _quickMsgs.find(m => m.id === id);
        if (!msg) return;
        const textarea = document.getElementById('messageInput');
        textarea.value = msg.body;
        autoResize(textarea);
        textarea.focus();
        closeQmPopup();
    }

    // Keyboard nav dentro do popup
    document.getElementById('messageInput')?.addEventListener('keydown', e => {
        const popup = document.getElementById('quickMsgPopup');
        if (popup.style.display === 'none') return;
        const items = popup.querySelectorAll('.qm-popup-item');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            _qmSelectedIdx = Math.min(_qmSelectedIdx + 1, items.length - 1);
            items.forEach((el, i) => el.style.background = i === _qmSelectedIdx ? '#f0f7ff' : '');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            _qmSelectedIdx = Math.max(_qmSelectedIdx - 1, 0);
            items.forEach((el, i) => el.style.background = i === _qmSelectedIdx ? '#f0f7ff' : '');
        } else if (e.key === 'Enter' && _qmSelectedIdx >= 0) {
            e.preventDefault();
            items[_qmSelectedIdx]?.click();
        } else if (e.key === 'Escape') {
            closeQmPopup();
        }
    });

    // Fechar popup ao clicar fora
    document.addEventListener('click', e => {
        const popup = document.getElementById('quickMsgPopup');
        const textarea = document.getElementById('messageInput');
        const btn = document.getElementById('btnQuickMsgs');
        if (!popup?.contains(e.target) && e.target !== textarea && e.target !== btn) {
            closeQmPopup();
        }
    });

    // ── Modal de gerenciamento ────────────────────────────────────────────────
    function toggleAttachMenu() {
        const menu = document.getElementById('attachMenu');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }
    function closeAttachMenu() {
        document.getElementById('attachMenu').style.display = 'none';
    }
    document.addEventListener('click', function(e) {
        const wrap = document.getElementById('attachMenuWrap');
        if (wrap && !wrap.contains(e.target)) closeAttachMenu();
    });

    function openQmModal() {
        renderQmModalList();
        document.getElementById('qmModalOverlay').style.display = 'block';
    }

    function closeQmModal() {
        document.getElementById('qmModalOverlay').style.display = 'none';
        cancelQmForm();
    }

    function renderQmModalList() {
        const list = document.getElementById('qmList');
        if (!_quickMsgs.length) {
            list.innerHTML = `<p style="text-align:center;color:#9ca3af;font-size:13px;padding:12px 0;">${escHtml(LANG.qm_none)}</p>`;
            return;
        }
        list.innerHTML = _quickMsgs.map(m => `
            <div style="display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid #f0f2f7;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13.5px;font-weight:700;color:#1a1d23;">${escapeHtml(m.title)}</div>
                    <div style="font-size:12.5px;color:#6b7280;margin-top:2px;white-space:pre-wrap;word-break:break-word;max-height:60px;overflow:hidden;">
                        ${escapeHtml(m.body.substring(0, 120))}${m.body.length > 120 ? '…' : ''}
                    </div>
                </div>
                <div style="display:flex;gap:5px;flex-shrink:0;margin-top:2px;">
                    <button onclick="editQm(${m.id})" title="Editar"
                            style="width:28px;height:28px;border:1px solid #e8eaf0;border-radius:7px;
                                   background:#fff;color:#6b7280;cursor:pointer;font-size:13px;
                                   display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button onclick="deleteQm(${m.id})" title="Excluir"
                            style="width:28px;height:28px;border:1px solid #fecaca;border-radius:7px;
                                   background:#fff;color:#ef4444;cursor:pointer;font-size:13px;
                                   display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </div>`
        ).join('');
    }

    function showQmForm(msg = null) {
        const form = document.getElementById('qmForm');
        document.getElementById('qmEditId').value = msg ? msg.id : '';
        document.getElementById('qmTitle').value  = msg ? msg.title : '';
        document.getElementById('qmBody').value   = msg ? msg.body  : '';
        form.style.display = 'block';
        setTimeout(() => document.getElementById('qmTitle').focus(), 50);
    }

    function cancelQmForm() {
        document.getElementById('qmForm').style.display = 'none';
        document.getElementById('qmEditId').value = '';
        document.getElementById('qmTitle').value  = '';
        document.getElementById('qmBody').value   = '';
    }

    function editQm(id) {
        const msg = _quickMsgs.find(m => m.id === id);
        if (msg) showQmForm(msg);
    }

    async function saveQm() {
        const id    = document.getElementById('qmEditId').value;
        const title = document.getElementById('qmTitle').value.trim();
        const body  = document.getElementById('qmBody').value.trim();
        if (!title) { document.getElementById('qmTitle').focus(); return; }
        if (!body)  { document.getElementById('qmBody').focus();  return; }

        const url    = id ? `${QM_BASE}/${id}` : QM_BASE;
        const method = id ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ title, body }),
            });
            const data = await res.json();
            if (!data.success) throw new Error();

            if (id) {
                const idx = _quickMsgs.findIndex(m => m.id === parseInt(id));
                if (idx >= 0) _quickMsgs[idx] = data.message;
            } else {
                _quickMsgs.push(data.message);
            }
            cancelQmForm();
            renderQmModalList();
        } catch {
            toastr.error(LANG.error_save_qm);
        }
    }

    async function deleteQm(id) {
        if (!confirm(LANG.confirm_delete_qm)) return;
        try {
            const res = await fetch(`${QM_BASE}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (!data.success) throw new Error();
            _quickMsgs = _quickMsgs.filter(m => m.id !== id);
            renderQmModalList();
        } catch {
            toastr.error(LANG.error_delete_qm);
        }
    }

    // ── Formatação WhatsApp no textarea do modal ──────────────────────────────
    function qmFormat(type) {
        const ta = document.getElementById('qmBody');
        const start = ta.selectionStart;
        const end   = ta.selectionEnd;
        const sel   = ta.value.substring(start, end);
        const markers = { bold: '*', italic: '_', strike: '~', mono: '`' };
        const m = markers[type];
        if (!m) return;
        const wrapped = sel ? `${m}${sel}${m}` : `${m}${m}`;
        ta.value = ta.value.substring(0, start) + wrapped + ta.value.substring(end);
        const newPos = sel ? start + wrapped.length : start + 1;
        ta.setSelectionRange(newPos, newPos);
        ta.focus();
    }
</script>
<style>
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .spin {
        animation: spin .8s linear infinite;
        display: inline-block;
    }
    /* ── Delete Confirmation Modal ── */
    .del-modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.45); z-index: 9999;
        align-items: center; justify-content: center;
    }
    .del-modal-overlay.open { display: flex; }
    .del-modal {
        background: #fff; border-radius: 14px; padding: 28px;
        width: 400px; max-width: 94vw;
        box-shadow: 0 20px 60px rgba(0,0,0,.18);
        text-align: center;
    }
    .del-modal-icon { font-size: 36px; color: #EF4444; margin-bottom: 12px; }
    .del-modal-title { font-size: 16px; font-weight: 700; color: #1a1d23; margin-bottom: 8px; }
    .del-modal-text { font-size: 13.5px; color: #6b7280; margin-bottom: 24px; line-height: 1.5; }
    .del-modal-footer { display: flex; justify-content: center; gap: 10px; }
    .btn-del-cancel {
        padding: 9px 22px; border-radius: 9px; font-size: 13.5px; font-weight: 600;
        border: 1.5px solid #e8eaf0; background: #f4f6fb; color: #4b5563; cursor: pointer;
    }
    .btn-del-cancel:hover { background: #e8eaf0; }
    .btn-del-confirm {
        padding: 9px 22px; border-radius: 9px; font-size: 13.5px; font-weight: 600;
        border: none; background: #EF4444; color: #fff; cursor: pointer;
    }
    .btn-del-confirm:hover { background: #dc2626; }
</style>
@endpush