@extends('tenant.layouts.app')
@php
    $title = __('integrations.title');
    $pageIcon = 'plugin';
@endphp

@push('styles')
<style>
    /* ========================================================================
       Catálogo de integrações (refactor Agendor-style)
       ======================================================================== */

    .catalog-layout {
        display: grid;
        grid-template-columns: 240px 1fr;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 900px) {
        .catalog-layout { grid-template-columns: 1fr; }
        .catalog-sidebar { position: static !important; }
    }

    .catalog-sidebar {
        background: #fff;
        border: 1.5px solid #e8eaf0;
        border-radius: 14px;
        padding: 12px;
        position: sticky;
        top: 80px;
    }
    .cat-item {
        display: flex; align-items: center; gap: 10px;
        width: 100%; padding: 10px 14px;
        background: none; border: none;
        border-radius: 10px;
        font-size: 13.5px; font-family: inherit;
        color: #6b7280; cursor: pointer; text-align: left;
        transition: all .15s; margin-bottom: 2px;
    }
    .cat-item i { font-size: 16px; }
    .cat-item:hover { background: #f3f4f6; color: #374151; }
    .cat-item.active { background: #eff6ff; color: #0085f3; font-weight: 600; }

    .catalog-search {
        width: 100%;
        padding: 12px 18px 12px 42px;
        border: 1.5px solid #e8eaf0;
        border-radius: 12px;
        font-size: 13.5px;
        margin-bottom: 18px;
        background: #fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239ca3af' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'/></svg>") no-repeat 14px center;
        background-size: 18px;
        outline: none;
        transition: border-color .15s;
    }
    .catalog-search:focus { border-color: #0085f3; }

    .catalog-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }
    @media (max-width: 1100px) { .catalog-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 700px)  { .catalog-grid { grid-template-columns: 1fr; } }

    .catalog-card {
        background: #fff;
        border: 1.5px solid #e8eaf0;
        border-radius: 14px;
        padding: 18px 18px 16px;
        cursor: pointer;
        transition: all .15s;
        display: flex; flex-direction: column; gap: 10px;
    }
    .catalog-card:hover {
        border-color: #0085f3;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,133,243,.08);
    }
    .catalog-card .card-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px;
        background: #f8fafc;
        border: 1px solid #f0f2f7;
    }
    .catalog-card .card-icon img {
        width: 30px; height: 30px;
        object-fit: contain;
    }
    .catalog-card .card-name {
        font-size: 14.5px; font-weight: 700; color: #1a1d23;
    }
    .catalog-card .card-type-badge {
        display: inline-flex;
        padding: 3px 9px;
        border-radius: 99px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        align-self: flex-start;
    }
    .card-type-badge.type-native  { background: #ecfdf5; color: #059669; }
    .card-type-badge.type-partner { background: #f3f4f6; color: #6b7280; }
    .card-type-badge.type-beta    { background: #fef3c7; color: #b45309; }
    .catalog-card .card-desc {
        font-size: 12.5px; color: #6b7280; line-height: 1.5;
    }

    .catalog-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 24px;
        color: #9ca3af;
        font-size: 14px;
    }

    /* ========== Modal de detalhes ========== */
    .integration-modal { display: none; }
    .integration-modal.open { display: block; }

    .integration-modal .im-overlay {
        position: fixed; inset: 0;
        background: rgba(15,23,42,.55);
        z-index: 9000;
        animation: imFade .15s ease-out;
    }
    @keyframes imFade {
        from { opacity: 0; }
        to   { opacity: 1; }
    }
    .integration-modal .im-shell {
        position: fixed;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: min(960px, 94vw);
        height: min(680px, 90vh);
        background: #fff;
        border-radius: 16px;
        z-index: 9001;
        display: flex;
        overflow: hidden;
        box-shadow: 0 24px 64px rgba(15,23,42,.2);
        animation: imSlideIn .2s ease-out;
    }
    @keyframes imSlideIn {
        from { opacity: 0; transform: translate(-50%, -45%); }
        to   { opacity: 1; transform: translate(-50%, -50%); }
    }
    .im-sidebar {
        flex: 0 0 280px;
        padding: 32px 26px;
        border-right: 1px solid #f0f2f7;
        overflow-y: auto;
        background: #fafbfd;
    }
    .im-icon {
        width: 64px; height: 64px;
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 30px;
        margin-bottom: 18px;
        background: #f8fafc;
        border: 1px solid #f0f2f7;
    }
    .im-icon img {
        width: 40px; height: 40px;
        object-fit: contain;
    }
    .im-name {
        font-size: 18px; font-weight: 700; color: #1a1d23;
        margin: 0 0 10px;
    }
    .im-desc-long {
        font-size: 13px; color: #6b7280; line-height: 1.55;
        margin-bottom: 22px;
    }
    .im-section-label {
        font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .06em;
        color: #97a3b7;
        margin: 16px 0 8px;
    }
    .im-plans, .im-cats {
        display: flex; flex-wrap: wrap; gap: 6px;
    }
    .im-plans .plan-badge {
        background: #1a1d23; color: #fff;
        padding: 5px 12px; border-radius: 6px;
        font-size: 11px; font-weight: 600;
    }
    .im-cats .cat-tag {
        background: #f3f4f6; color: #374151;
        padding: 5px 12px; border-radius: 6px;
        font-size: 11px; font-weight: 600;
    }

    .im-main {
        flex: 1; padding: 28px 32px;
        overflow-y: auto;
    }
    .im-close {
        position: absolute; top: 14px; right: 14px;
        width: 32px; height: 32px;
        border-radius: 8px; border: none;
        background: rgba(0,0,0,.04);
        cursor: pointer; font-size: 18px;
        z-index: 9002;
        display: flex; align-items: center; justify-content: center;
    }
    .im-close:hover { background: rgba(0,0,0,.08); }

    /* Estilos compartilhados pelos panels */
    .panel-header {
        display: flex; align-items: center; gap: 14px;
        margin-bottom: 16px;
    }
    .panel-header > div:first-child { flex: 1; min-width: 0; }
    .panel-title  { font-size: 16px; font-weight: 700; color: #1a1d23; margin: 0 0 2px; }
    .panel-subtitle { font-size: 12.5px; color: #6b7280; margin: 0; }

    @media (max-width: 768px) {
        .integration-modal .im-shell { flex-direction: column; height: 95vh; max-height: none; }
        .im-sidebar { flex: 0 0 auto; max-height: 220px; border-right: none; border-bottom: 1px solid #f0f2f7; }
    }

    /* Containers escondidos com source HTML dos panels (não devem aparecer) */
    [data-panel-for] { display: none; }

    /* Boost z-index dos modais existentes pra não conflitar com o im-shell */
    #waQrModal.wa-modal-overlay { z-index: 10000; }
    #waImportModal.wa-modal-overlay { z-index: 10000; }
    #fbLeadOverlay.int-modal-overlay { z-index: 10001; }
    #fbLeadDrawer.int-modal { z-index: 10002; }
    #waBtnOverlay.int-modal-overlay { z-index: 10001; }
    #waBtnDrawer.int-modal { z-index: 10002; }

    /* ========================================================================
       Estilos legados (mantidos pros panels — não mexer)
       ======================================================================== */

    .integrations-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    @media (max-width: 1100px) {
        .integrations-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 700px) {
        .integrations-grid { grid-template-columns: 1fr; }
    }

    .integration-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
    }

    .integration-header {
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 14px;
        border-bottom: 1px solid #f0f2f7;
    }

    .integration-logo {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 800;
        color: #fff;
        flex-shrink: 0;
    }

    .integration-logo.facebook   { background: #1877F2; }
    .integration-logo.google     { background: linear-gradient(135deg, #4285F4 0%, #EA4335 50%, #FBBC04 75%, #34A853 100%); }
    .integration-logo.whatsapp   { background: #25D366; }
    .integration-logo.instagram  { background: linear-gradient(135deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }

    .conn-soon { background: #f3f4f6; color: #9ca3af; }

    .integration-features {
        list-style: none;
        padding: 0;
        margin: 0 0 16px;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .integration-features li {
        font-size: 12.5px;
        color: #4b5563;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .integration-features li::before {
        content: '✓';
        font-size: 11px;
        font-weight: 700;
        color: #9ca3af;
        flex-shrink: 0;
        width: 14px;
        text-align: center;
    }

    .btn-coming-soon {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 18px;
        background: #f3f4f6;
        color: #9ca3af;
        border: 1.5px solid #e8eaf0;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: not-allowed;
    }

    /* Modal QR */
    .wa-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 10000;
        background: rgba(15,23,42,.55);
        align-items: center;
        justify-content: center;
    }
    .wa-modal-overlay.open { display: flex; }

    .wa-modal {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        width: 100%;
        max-width: 440px;
        margin: 16px;
        text-align: center;
        box-shadow: 0 24px 60px rgba(0,0,0,.18);
    }

    /* QR modal — layout horizontal 2 colunas */
    #waQrModal .wa-modal {
        max-width: 720px;
        display: flex;
        gap: 32px;
        text-align: left;
        padding: 36px;
    }

    .wa-modal-left {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .wa-modal-left h4 {
        font-size: 18px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0 0 6px;
    }

    .wa-modal-left .wa-subtitle {
        font-size: 13px;
        color: #6b7280;
        margin: 0 0 20px;
    }

    .wa-modal-left label {
        font-size: 13px;
        font-weight: 600;
        color: #1a1d23;
        display: block;
        margin-bottom: 6px;
    }

    .wa-modal-left input[type="text"] {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        font-size: 13px;
        color: #374151;
        outline: none;
        transition: border-color .15s;
        box-sizing: border-box;
    }

    .wa-modal-left input[type="text"]:focus { border-color: #25D366; }
    .wa-modal-left input[type="text"]:read-only { background: #f9fafb; color: #9ca3af; cursor: default; }

    .wa-modal-right {
        width: 260px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .wa-modal h4 {
        font-size: 18px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0 0 6px;
    }

    .wa-modal p {
        font-size: 13px;
        color: #6b7280;
        margin: 0 0 20px;
    }

    .wa-steps {
        text-align: left;
        font-size: 13px;
        color: #374151;
        line-height: 1.7;
        margin-bottom: 22px;
        padding: 14px 16px;
        background: #f8fafc;
        border-radius: 10px;
        list-style: none;
        counter-reset: step;
    }

    .wa-steps li {
        counter-increment: step;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 6px;
    }

    .wa-steps li:last-child { margin-bottom: 0; }

    .wa-steps li::before {
        content: counter(step);
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #25D366;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .wa-qr-area {
        width: 220px;
        height: 220px;
        margin: 0 0 12px;
        border: 1.5px solid #e5e7eb;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .wa-qr-area img { width: 100%; height: 100%; object-fit: contain; }

    .wa-qr-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        color: #c9cdd5;
    }

    .wa-qr-placeholder i { font-size: 48px; }
    .wa-qr-placeholder span { font-size: 12px; color: #9ca3af; }

    #waQrStatus {
        font-size: 13px;
        color: #6b7280;
        text-align: center;
    }

    #waQrStatus.connected { color: #10B981; font-weight: 600; }
    #waQrStatus.error     { color: #EF4444; }

    .btn-wa-cancel {
        padding: 9px 20px;
        background: #fff;
        color: #374151;
        border: 1.5px solid #e8eaf0;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
    }

    .btn-wa-cancel:hover { background: #f4f6fb; }

    .btn-wa-generate {
        padding: 10px 24px;
        background: #25D366;
        color: #fff;
        border: none;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-wa-generate:hover { background: #1fb855; }
    .btn-wa-generate:disabled { opacity: .6; cursor: not-allowed; }

    .wa-modal-actions {
        display: flex;
        gap: 8px;
        margin-top: auto;
        padding-top: 16px;
    }

    @media (max-width: 640px) {
        #waQrModal .wa-modal {
            flex-direction: column;
            max-width: 420px;
            gap: 20px;
        }
        .wa-modal-right { width: 100%; }
        .wa-qr-area { margin: 0 auto 12px; }
    }

    .integration-title {
        flex: 1;
    }

    .integration-title h3 {
        font-size: 15px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0 0 3px;
    }

    .integration-title p {
        font-size: 12px;
        color: #9ca3af;
        margin: 0;
    }

    .conn-badge {
        font-size: 11.5px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 99px;
        white-space: nowrap;
    }

    .conn-active   { background: #d1fae5; color: #065f46; }
    .conn-expired  { background: #fef3c7; color: #92400e; }
    .conn-revoked,
    .conn-none     { background: #f3f4f6; color: #6b7280; }

    .integration-body {
        padding: 18px 24px;
    }

    .conn-detail {
        font-size: 13px;
        color: #374151;
        margin-bottom: 16px;
    }

    .conn-detail strong { color: #1a1d23; }
    .conn-detail span   { color: #9ca3af; font-size: 12px; }

    .integration-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-connect {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 18px;
        background: #0085f3;
        color: #fff;
        border: none;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background .15s;
    }

    .btn-connect:hover { background: #0070d1; color: #fff; }

    .btn-sync {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: #fff;
        color: #374151;
        border: 1.5px solid #e8eaf0;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
    }

    .btn-sync:hover { background: #f0f4ff; border-color: #dbeafe; color: #0085f3; }

    .btn-disconnect {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: #fff;
        color: #EF4444;
        border: 1.5px solid #fecaca;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
    }

    .btn-disconnect:hover { background: #fef2f2; }

    /* ── WhatsApp Instances (dentro do card) ───────────────────── */
    .wa-instance-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: #f8fafc;
        border-radius: 8px;
        margin-bottom: 8px;
    }
    .wa-instance-item:last-child { margin-bottom: 0; }

    .wa-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .wa-dot.connected {
        background: #10b981;
        animation: pulse-green 2s ease-in-out infinite;
    }
    .wa-dot.qr       { background: #f59e0b; animation: pulse-yellow 2s ease-in-out infinite; }
    .wa-dot.offline   { background: #d1d5db; }

    @keyframes pulse-green {
        0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, .5); }
        50%      { box-shadow: 0 0 0 5px rgba(16, 185, 129, 0); }
    }
    @keyframes pulse-yellow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, .5); }
        50%      { box-shadow: 0 0 0 5px rgba(245, 158, 11, 0); }
    }

    .wa-instance-detail {
        flex: 1;
        min-width: 0;
    }

    .wa-label-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .wa-label-input {
        border: 1px solid #e5e7eb;
        background: #fff;
        font-size: 12.5px;
        font-weight: 600;
        color: #1a1d23;
        padding: 3px 8px;
        border-radius: 6px;
        width: 100%;
        max-width: 160px;
        outline: none;
        transition: border-color .15s;
    }
    .wa-label-input:focus { border-color: #0085f3; }
    .wa-label-input::placeholder { color: #b0b7c3; font-weight: 400; font-style: italic; }

    .wa-edit-icon {
        color: #9ca3af;
        font-size: 11px;
        flex-shrink: 0;
    }

    .wa-instance-phone {
        font-size: 11.5px;
        color: #6b7280;
        display: block;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .wa-instance-actions {
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="page-container">

    @include('tenant.settings._tabs')

    {{-- ============================================================
         Catálogo de integrações (refactor Agendor-style)
         ============================================================ --}}
    <div class="catalog-layout">

        {{-- Sidebar de categorias --}}
        <aside class="catalog-sidebar">
            @php
                $categoryIcons = [
                    'all'          => 'bi-grid-3x3-gap',
                    'messaging'    => 'bi-chat-dots',
                    'lead_capture' => 'bi-collection',
                    'calendar'     => 'bi-calendar3',
                ];
            @endphp
            @foreach(\App\Support\IntegrationCatalog::categories() as $catSlug => $catLangKey)
                <button type="button"
                        class="cat-item {{ $catSlug === 'all' ? 'active' : '' }}"
                        data-category="{{ $catSlug }}">
                    <i class="bi {{ $categoryIcons[$catSlug] ?? 'bi-circle' }}"></i>
                    {{ __($catLangKey) }}
                </button>
            @endforeach
        </aside>

        {{-- Conteúdo principal --}}
        <main class="catalog-main">
            <input type="text" id="catalogSearch" class="catalog-search"
                   placeholder="{{ __('integrations.search_placeholder') }}"
                   autocomplete="off">

            <div class="catalog-grid" id="catalogGrid">
                @forelse($catalog as $integration)
                    <div class="catalog-card"
                         data-slug="{{ $integration['slug'] }}"
                         data-category="{{ $integration['category'] }}"
                         data-name="{{ strtolower(__($integration['name'])) }}"
                         onclick="openIntegrationModal('{{ $integration['slug'] }}')">
                        <div class="card-icon">
                            @if(!empty($integration['image']))
                                <img src="{{ asset($integration['image']) }}" alt="{{ __($integration['name']) }}">
                            @else
                                <i class="bi {{ $integration['icon'] }}" style="color:{{ $integration['icon_bg'] }};"></i>
                            @endif
                        </div>
                        <div class="card-name">{{ __($integration['name']) }}</div>
                        <span class="card-type-badge type-{{ $integration['type'] }}">
                            {{ __('integrations.type_' . $integration['type']) }}
                        </span>
                        <div class="card-desc">{{ __($integration['description_short']) }}</div>
                    </div>
                @empty
                    <div class="catalog-empty">{{ __('integrations.no_results') }}</div>
                @endforelse

                <div class="catalog-empty" id="catalogNoResults" style="display:none;">
                    {{ __('integrations.no_results') }}
                </div>
            </div>
        </main>
    </div>

    {{-- ============================================================
         Modal de detalhes da integração (single instance)
         O conteúdo direito é populado via JS copiando de [data-panel-for]
         ============================================================ --}}
    <div id="integrationModal" class="integration-modal">
        <div class="im-overlay" onclick="closeIntegrationModal()"></div>
        <div class="im-shell">
            <button type="button" class="im-close" onclick="closeIntegrationModal()" aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>

            {{-- Lateral esquerda fixa --}}
            <aside class="im-sidebar">
                <div class="im-icon" id="imIcon"></div>
                <h2 class="im-name" id="imName"></h2>
                <p class="im-desc-long" id="imDescLong"></p>

                <div class="im-section-label">{{ __('integrations.available_plans') }}</div>
                <div class="im-plans" id="imPlans"></div>

                <div class="im-section-label">{{ __('integrations.categories_label') }}</div>
                <div class="im-cats" id="imCats"></div>
            </aside>

            {{-- Lateral direita scrollável (conteúdo dinâmico) --}}
            <main class="im-main" id="imMain">
                {{-- Populado via JS ao abrir o modal --}}
            </main>
        </div>
    </div>

    {{-- Painéis source (escondidos) — JS copia o innerHTML pro modal ao abrir --}}
    @foreach($catalog as $integration)
        <div data-panel-for="{{ $integration['slug'] }}">
            @include($integration['panel_partial'])
        </div>
    @endforeach

</div>{{-- fecha page-container --}}

{{-- ─── Modal QR WhatsApp ──────────────────────────────────────────── --}}
<div id="waQrModal" class="wa-modal-overlay">
    <div class="wa-modal">
        {{-- Coluna esquerda: info + input --}}
        <div class="wa-modal-left">
            <h4><i class="bi bi-whatsapp" style="color:#25D366;margin-right:6px;"></i>{{ __('integrations.qr_title') }}</h4>
            <p class="wa-subtitle">{{ __('integrations.qr_subtitle') }}</p>

            <label for="waLabelInput">{{ __('integrations.wa_label_field') }}</label>
            <input type="text" id="waLabelInput" placeholder="{{ __('integrations.wa_label_placeholder') }}" maxlength="60">
            <p style="font-size:11.5px;color:#9ca3af;margin:6px 0 18px;">{{ __('integrations.wa_label_hint') }}</p>

            <ol class="wa-steps">
                <li>{!! __('integrations.qr_step_1') !!}</li>
                <li>{!! __('integrations.qr_step_2') !!}</li>
                <li>{!! __('integrations.qr_step_3') !!}</li>
                <li>{!! __('integrations.qr_step_4') !!}</li>
            </ol>

            <div class="wa-modal-actions" id="waModalActions">
                <button class="btn-wa-cancel" onclick="closeWaModal()">{{ __('integrations.qr_cancel') }}</button>
                <button class="btn-wa-generate" id="btnWaGenerate" onclick="generateWaQr()">
                    <i class="bi bi-qr-code"></i> {{ __('integrations.wa_generate_qr') }}
                </button>
            </div>
        </div>

        {{-- Coluna direita: QR code --}}
        <div class="wa-modal-right">
            <div class="wa-qr-area" id="waQrArea">
                <div class="wa-qr-placeholder">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>{{ __('integrations.wa_qr_placeholder') }}</span>
                </div>
            </div>
            <p id="waQrStatus"></p>
        </div>
    </div>
</div>

{{-- ─── Modal Importar Histórico ──────────────────────────────────── --}}
<div id="waImportModal" class="wa-modal-overlay">
    <div class="wa-modal" style="max-width:420px;">
        {{-- Estado 1: Configuração --}}
        <div id="importConfigState">
            <h4 style="margin:0 0 4px;font-size:16px;font-weight:700;color:#1a1d23;">
                <i class="bi bi-cloud-download" style="color:#0085f3;margin-right:6px;"></i>{{ __('integrations.import_title') }}
            </h4>
            <p style="font-size:13px;color:#6b7280;margin:0 0 18px;">{{ __('integrations.import_subtitle') }}</p>

            <div style="text-align:left;margin-bottom:20px;">
                <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('integrations.import_period') }}</label>
                <select id="importDaysSelect" style="width:100%;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;color:#374151;background:#fff;outline:none;">
                    <option value="7">{{ __('integrations.import_7d') }}</option>
                    <option value="15">{{ __('integrations.import_15d') }}</option>
                    <option value="30" selected>{{ __('integrations.import_30d') }}</option>
                </select>
                <p style="font-size:11.5px;color:#9ca3af;margin:8px 0 0;">{{ __('integrations.import_help') }}</p>
            </div>

            <div style="display:flex;gap:8px;justify-content:center;">
                <button class="btn-wa-cancel" onclick="closeImportModal()">{{ __('integrations.import_cancel') }}</button>
                <button class="btn-connect" id="btnStartImport" onclick="startImport()">
                    <i class="bi bi-cloud-download"></i> {{ __('integrations.import_btn') }}
                </button>
            </div>
        </div>

        {{-- Estado 2: Progresso --}}
        <div id="importProgressState" style="display:none;">
            <h4 id="importProgressTitle" style="margin:0 0 4px;font-size:16px;font-weight:700;color:#1a1d23;">
                <i class="bi bi-arrow-clockwise spin" style="color:#0085f3;margin-right:6px;"></i>{{ __('integrations.import_progress') }}
            </h4>
            <p id="importProgressSubtitle" style="font-size:13px;color:#6b7280;margin:0 0 18px;">{{ __('integrations.import_progress_sub') }}</p>

            {{-- Barra de progresso --}}
            <div style="background:#f3f4f6;border-radius:8px;height:10px;overflow:hidden;margin-bottom:18px;">
                <div id="importProgressBar" style="height:100%;background:#0085f3;border-radius:8px;transition:width .5s ease;width:0%;"></div>
            </div>

            {{-- Contadores --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
                <div style="background:#f0f7ff;border-radius:10px;padding:12px 14px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#0085f3;" id="importCountChats">0</div>
                    <div style="font-size:11px;color:#6b7280;font-weight:600;">{{ __('integrations.import_conversations') }}</div>
                </div>
                <div style="background:#ecfdf5;border-radius:10px;padding:12px 14px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#059669;" id="importCountMessages">0</div>
                    <div style="font-size:11px;color:#6b7280;font-weight:600;">{{ __('integrations.import_messages') }}</div>
                </div>
                <div style="background:#fef3c7;border-radius:10px;padding:12px 14px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#d97706;" id="importCountSkipped">0</div>
                    <div style="font-size:11px;color:#6b7280;font-weight:600;">{{ __('integrations.import_duplicates') }}</div>
                </div>
                <div style="background:#f3f4f6;border-radius:10px;padding:12px 14px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#374151;" id="importCountTime">0:00</div>
                    <div style="font-size:11px;color:#6b7280;font-weight:600;">{{ __('integrations.import_time') }}</div>
                </div>
            </div>

            {{-- Chat atual --}}
            <div id="importCurrentChat" style="font-size:12px;color:#9ca3af;text-align:center;margin-bottom:16px;min-height:18px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>

            {{-- Botão fechar --}}
            <div style="text-align:center;">
                <button class="btn-wa-cancel" id="importCloseBtn" onclick="closeImportModal()">{{ __('integrations.import_close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ─── Facebook Lead Ads Drawer ──────────────────────────────────────── --}}
@if($enabledIntegrations['facebook_leadads'] ?? false)
<div id="fbLeadOverlay" class="int-modal-overlay int-modal-overlay-fb" onclick="closeFbLeadDrawer(event)"></div>
<div id="fbLeadDrawer" class="int-modal int-modal-fb">
    <div style="padding:20px 24px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;">
        <div>
            <div style="font-size:16px;font-weight:700;color:#1a1d23;">{{ __('integrations.fb_drawer_title') }}</div>
            <div style="font-size:12px;color:#9ca3af;">{{ __('integrations.fb_drawer_subtitle') }}</div>
        </div>
        <button onclick="closeFbLeadDrawer()" style="background:none;border:none;font-size:20px;color:#6b7280;cursor:pointer;">&times;</button>
    </div>

    {{-- Stepper --}}
    <div style="display:flex;padding:16px 24px;gap:8px;" id="fbStepper">
        <div class="fb-step active" data-step="1"><span class="fb-step-num">1</span> {{ __('integrations.fb_step_form') }}</div>
        <div class="fb-step" data-step="2"><span class="fb-step-num">2</span> {{ __('integrations.fb_step_pipeline') }}</div>
        <div class="fb-step" data-step="3"><span class="fb-step-num">3</span> {{ __('integrations.fb_step_fields') }}</div>
    </div>

    {{-- Step 1: Select Page + Form --}}
    <div class="fb-panel" id="fbStep1" style="padding:0 24px 24px;">
        <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('integrations.fb_page_label') }}</label>
        <select id="fbPageSelect" style="width:100%;padding:10px 12px;border:1.5px solid #e8eaf0;border-radius:8px;font-size:13px;margin-bottom:16px;" onchange="onPageSelected()">
            <option value="">{{ __('integrations.fb_page_loading') }}</option>
        </select>

        {{-- Search mode (Business Login fallback) --}}
        <div id="fbPageSearchArea" style="display:none;margin-bottom:16px;">
            <div style="display:flex;gap:8px;">
                <input type="text" id="fbPageSearchInput" placeholder="Cole a URL ou ID da sua Facebook Page"
                    style="flex:1;padding:10px 12px;border:1.5px solid #e8eaf0;border-radius:8px;font-size:13px;"
                    onkeydown="if(event.key==='Enter'){event.preventDefault();searchFbPage();}">
                <button onclick="searchFbPage()" class="fb-btn-primary" style="padding:10px 16px;white-space:nowrap;">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </div>
            <div style="font-size:11.5px;color:#9ca3af;margin-top:6px;">
                Ex: https://facebook.com/SuaPagina ou o ID numérico da página
            </div>
            <div id="fbPageSearchResult" style="margin-top:10px;"></div>
        </div>

        <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('integrations.fb_form_label') }}</label>
        <select id="fbFormSelect" style="width:100%;padding:10px 12px;border:1.5px solid #e8eaf0;border-radius:8px;font-size:13px;margin-bottom:16px;" disabled>
            <option value="">{{ __('integrations.fb_form_select_page') }}</option>
        </select>

        <div style="font-size:12px;color:#9ca3af;margin-bottom:16px;">
            <i class="bi bi-info-circle"></i> {{ __('integrations.fb_form_hint') }}
        </div>

        <div style="text-align:right;">
            <button onclick="fbGoStep(2)" class="fb-btn-primary" id="fbNextStep1" disabled>{{ __('integrations.fb_btn_next') }} <i class="bi bi-arrow-right"></i></button>
        </div>
    </div>

    {{-- Step 2: Pipeline + Stage --}}
    <div class="fb-panel" id="fbStep2" style="display:none;padding:0 24px 24px;">
        <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('integrations.fb_pipeline_label') }}</label>
        <select id="fbPipelineSelect" style="width:100%;padding:10px 12px;border:1.5px solid #e8eaf0;border-radius:8px;font-size:13px;margin-bottom:16px;" onchange="onPipelineSelected()">
            @foreach($pipelines as $p)
            <option value="{{ $p->id }}" data-stages="{{ $p->stages->toJson() }}">{{ $p->name }}</option>
            @endforeach
        </select>

        <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('integrations.fb_stage_label') }}</label>
        <select id="fbStageSelect" style="width:100%;padding:10px 12px;border:1.5px solid #e8eaf0;border-radius:8px;font-size:13px;margin-bottom:16px;"></select>

        <label style="font-size:13px;font-weight:600;color:#1a1d23;display:block;margin-bottom:6px;">{{ __('integrations.fb_tags_label') }}</label>
        <input type="text" id="fbDefaultTags" placeholder="{{ __('integrations.fb_tags_placeholder') }}" style="width:100%;padding:10px 12px;border:1.5px solid #e8eaf0;border-radius:8px;font-size:13px;margin-bottom:16px;">

        <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;margin-bottom:16px;">
            <input type="checkbox" id="fbAllowDuplicates" checked style="margin-top:2px;cursor:pointer;flex-shrink:0;">
            <label for="fbAllowDuplicates" style="margin:0;cursor:pointer;flex:1;">
                <div style="font-size:13px;font-weight:600;color:#1a1d23;">{{ __('integrations.fb_allow_dup_label') }}</div>
                <div style="font-size:11.5px;color:#0369a1;margin-top:2px;line-height:1.4;">{{ __('integrations.fb_allow_dup_desc') }}</div>
            </label>
        </div>

        <div style="display:flex;justify-content:space-between;margin-top:8px;">
            <button onclick="fbGoStep(1)" class="fb-btn-secondary"><i class="bi bi-arrow-left"></i> {{ __('integrations.fb_btn_back') }}</button>
            <button onclick="fbGoStep(3)" class="fb-btn-primary">{{ __('integrations.fb_btn_next') }} <i class="bi bi-arrow-right"></i></button>
        </div>
    </div>

    {{-- Step 3: Field Mapping --}}
    <div class="fb-panel" id="fbStep3" style="display:none;padding:0 24px 24px;">
        <div style="font-size:13px;font-weight:600;color:#1a1d23;margin-bottom:12px;">{{ __('integrations.fb_mapping_title') }}</div>
        <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:8px;align-items:center;font-size:12px;font-weight:600;color:#6b7280;margin-bottom:8px;">
            <div>{{ __('integrations.fb_mapping_form_col') }}</div>
            <div></div>
            <div>{{ __('integrations.fb_mapping_crm_col') }}</div>
        </div>
        <div id="fbFieldMappingContainer"></div>

        <div style="display:flex;justify-content:space-between;margin-top:20px;">
            <button onclick="fbGoStep(2)" class="fb-btn-secondary"><i class="bi bi-arrow-left"></i> {{ __('integrations.fb_btn_back') }}</button>
            <button onclick="saveFbLeadConnection()" class="fb-btn-primary"><i class="bi bi-check-lg"></i> {{ __('integrations.fb_btn_save') }}</button>
        </div>
    </div>
</div>

{{-- ── Botao WhatsApp pra sites — drawer/modal ───────────────────────────── --}}
<div id="waBtnOverlay" class="int-modal-overlay" onclick="closeWaBtnDrawer(event)"></div>
<div id="waBtnDrawer" class="int-modal int-modal-540">
    <div style="padding:20px 24px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;">
        <h4 id="waBtnDrawerTitle" style="margin:0;font-size:16px;font-weight:700;color:#1a1d23;">{{ __('integrations.wabtn_drawer_title') }}</h4>
        <button onclick="closeWaBtnDrawer()" style="background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;padding:4px;"><i class="bi bi-x-lg"></i></button>
    </div>
    <div style="flex:1;overflow-y:auto;padding:20px 24px;">
        <input type="hidden" id="waBtnEditId" value="">
        <div style="margin-bottom:14px;">
            <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">{{ __('integrations.wabtn_phone') }}</label>
            <input type="text" id="waBtnPhone" class="form-control" placeholder="{{ __('integrations.wabtn_phone_ph') }}" style="font-size:13px;">
        </div>
        <div style="margin-bottom:14px;">
            <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">{{ __('integrations.wabtn_label') }}</label>
            <input type="text" id="waBtnLabel" class="form-control" placeholder="{{ __('integrations.wabtn_label_ph') }}" style="font-size:13px;">
        </div>
        <div style="margin-bottom:14px;">
            <label style="font-size:12.5px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">{{ __('integrations.wabtn_message') }}</label>
            <textarea id="waBtnMessage" class="form-control" rows="3" placeholder="{{ __('integrations.wabtn_message_ph') }}" style="font-size:13px;resize:vertical;"></textarea>
        </div>
        <div style="margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <input type="checkbox" id="waBtnFloating" checked style="width:16px;height:16px;">
            <label for="waBtnFloating" style="font-size:12.5px;color:#374151;cursor:pointer;">{{ __('integrations.wabtn_floating') }}</label>
        </div>
        <div style="margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <input type="checkbox" id="waBtnActive" checked style="width:16px;height:16px;">
            <label for="waBtnActive" style="font-size:12.5px;color:#374151;cursor:pointer;">{{ __('integrations.wabtn_active') }}</label>
        </div>
        <div id="waBtnEmbedSection" style="display:none;padding-top:16px;border-top:1px solid #f0f2f7;">
            <label style="font-size:13px;font-weight:700;color:#1a1d23;display:block;margin-bottom:6px;"><i class="bi bi-code-slash"></i> {{ __('integrations.wabtn_embed') }}</label>
            <p style="font-size:11.5px;color:#6b7280;margin-bottom:8px;">Cole antes do <code style="background:#f1f5f9;padding:1px 5px;border-radius:3px;font-size:10.5px;">&lt;/body&gt;</code> do seu site.</p>
            <div style="position:relative;">
                <textarea id="waBtnEmbed" readonly onclick="this.select()" style="width:100%;height:50px;font-family:monospace;font-size:11.5px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 70px 10px 10px;resize:none;color:#334155;"></textarea>
                <button onclick="navigator.clipboard.writeText(document.getElementById('waBtnEmbed').value);toastr.success(ILANG.toast_copied)" style="position:absolute;top:8px;right:8px;background:#0085f3;color:#fff;border:none;border-radius:6px;padding:4px 10px;font-size:11px;font-weight:600;cursor:pointer;"><i class="bi bi-clipboard"></i> {{ __('integrations.wabtn_copy') }}</button>
            </div>
        </div>
        <div id="waBtnTrackSection" style="display:none;margin-top:16px;padding-top:16px;border-top:1px solid #f0f2f7;">
            <label style="font-size:13px;font-weight:700;color:#1a1d23;display:block;margin-bottom:6px;"><i class="bi bi-link-45deg"></i> {{ __('integrations.wabtn_tracking') }}</label>
            <p style="font-size:11.5px;color:#6b7280;margin-bottom:8px;">{{ __('integrations.wabtn_tracking_hint') }}</p>
            <div style="position:relative;">
                <input type="text" id="waBtnTrackLink" readonly onclick="this.select()" style="width:100%;font-family:monospace;font-size:11.5px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 70px 10px 10px;color:#334155;">
                <button onclick="navigator.clipboard.writeText(document.getElementById('waBtnTrackLink').value);toastr.success(ILANG.toast_link_copied)" style="position:absolute;top:6px;right:8px;background:#0085f3;color:#fff;border:none;border-radius:6px;padding:4px 10px;font-size:11px;font-weight:600;cursor:pointer;"><i class="bi bi-clipboard"></i> {{ __('integrations.wabtn_copy') }}</button>
            </div>
        </div>
    </div>
    <div style="padding:16px 24px;border-top:1px solid #f0f2f7;display:flex;gap:8px;justify-content:flex-end;">
        <button onclick="closeWaBtnDrawer()" style="padding:8px 20px;border:1px solid #e2e8f0;background:#fff;border-radius:100px;font-size:13px;cursor:pointer;color:#374151;">{{ __('integrations.wabtn_cancel') }}</button>
        <button onclick="saveWaButton()" style="padding:8px 20px;background:#25D366;color:#fff;border:none;border-radius:100px;font-size:13px;font-weight:600;cursor:pointer;"><i class="bi bi-check-lg"></i> {{ __('integrations.wabtn_save') }}</button>
    </div>
</div>

<style>
.fb-step { flex:1;text-align:center;padding:8px;border-radius:8px;font-size:12px;font-weight:600;color:#9ca3af;background:#f9fafb;transition:.2s; }
.fb-step.active { background:#eff6ff;color:#1877F2; }
.fb-step.done { background:#ecfdf5;color:#059669; }
.fb-step-num { display:inline-flex;width:20px;height:20px;align-items:center;justify-content:center;border-radius:50%;background:#e5e7eb;color:#6b7280;font-size:11px;margin-right:4px; }
.fb-step.active .fb-step-num { background:#1877F2;color:#fff; }
.fb-step.done .fb-step-num { background:#059669;color:#fff; }
.fb-btn-primary { padding:9px 20px;background:#1877F2;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer; }
.fb-btn-primary:hover { background:#1565D8; }
.fb-btn-primary:disabled { opacity:.5;cursor:not-allowed; }
.fb-btn-secondary { padding:9px 20px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;cursor:pointer; }
.fb-mapping-row { display:grid;grid-template-columns:1fr auto 1fr;gap:8px;align-items:center;margin-bottom:8px; }
.fb-mapping-row .fb-field-label { padding:8px 12px;background:#f9fafb;border:1px solid #e8eaf0;border-radius:8px;font-size:12.5px;color:#1a1d23; }
.fb-mapping-row .fb-arrow { color:#9ca3af;font-size:14px; }
.fb-mapping-row select { padding:8px 10px;border:1.5px solid #e8eaf0;border-radius:8px;font-size:12.5px;width:100%; }
</style>
@endif

@endsection

@push('scripts')

{{-- ============================================================
     Catálogo de integrações — filtros + modal de detalhes
     ============================================================ --}}
<script>
const CATALOG_DATA = @json($catalogJs);

/* ---------- Filtros (categoria + busca) ---------- */
let activeCategory = 'all';
let searchTerm = '';

function filterCatalogCards() {
    let visible = 0;
    document.querySelectorAll('.catalog-card').forEach(function(card) {
        const cat = card.dataset.category;
        const name = card.dataset.name;
        const matchCat = activeCategory === 'all' || cat === activeCategory;
        const matchSearch = searchTerm === '' || name.indexOf(searchTerm) !== -1;
        const show = matchCat && matchSearch;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const noResults = document.getElementById('catalogNoResults');
    if (noResults) noResults.style.display = visible === 0 ? '' : 'none';
}

document.querySelectorAll('.cat-item').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.cat-item').forEach(function(x) { x.classList.remove('active'); });
        btn.classList.add('active');
        activeCategory = btn.dataset.category;
        filterCatalogCards();
    });
});

const searchInput = document.getElementById('catalogSearch');
if (searchInput) {
    searchInput.addEventListener('input', function(e) {
        searchTerm = e.target.value.toLowerCase().trim();
        filterCatalogCards();
    });
}

/* ---------- Modal de detalhes ---------- */
function openIntegrationModal(slug) {
    const data = CATALOG_DATA[slug];
    if (!data) return;

    // Sidebar do modal — usa SVG (se tem 'image') ou Bootstrap Icon
    const iconEl = document.getElementById('imIcon');
    if (data.image) {
        iconEl.innerHTML = '<img src="' + data.image + '" alt="' + data.name + '">';
        iconEl.style.background = '#f8fafc';
        iconEl.style.color = '';
    } else {
        iconEl.innerHTML = '<i class="bi ' + data.icon + '" style="color:' + data.icon_bg + ';"></i>';
        iconEl.style.background = '#f8fafc';
    }

    document.getElementById('imName').textContent = data.name;
    document.getElementById('imDescLong').textContent = data.description_long;

    // Plans badges
    const plansHtml = (data.plans || []).map(function(p) {
        return '<span class="plan-badge">' + p.charAt(0).toUpperCase() + p.slice(1) + '</span>';
    }).join('');
    document.getElementById('imPlans').innerHTML = plansHtml;

    // Categories tags
    document.getElementById('imCats').innerHTML =
        '<span class="cat-tag">' + (data.category_label || data.category) + '</span>';

    // Conteúdo do painel direito: copia HTML do source escondido
    const source = document.querySelector('[data-panel-for="' + slug + '"]');
    document.getElementById('imMain').innerHTML = source ? source.innerHTML : '';

    // Mostra modal
    document.getElementById('integrationModal').classList.add('open');
    document.body.style.overflow = 'hidden';

    // Init function opcional por integração (rebind handlers se necessário)
    if (typeof window['init_' + slug] === 'function') {
        try { window['init_' + slug](); } catch (e) { console.error(e); }
    }
}

function closeIntegrationModal() {
    document.getElementById('integrationModal').classList.remove('open');
    document.body.style.overflow = '';
    // Limpa o conteúdo pra não deixar handlers órfãos
    document.getElementById('imMain').innerHTML = '';
}

// ESC fecha
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('integrationModal').classList.contains('open')) {
        closeIntegrationModal();
    }
});
</script>

{{-- Facebook JS SDK pro Embedded Signup do WhatsApp Coexistence --}}
@if($enabledIntegrations['whatsapp_cloud_api'] ?? false)
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>
<script>
    window.fbAsyncInit = function() {
        if (typeof FB === 'undefined') return;
        FB.init({
            appId   : @json(config('services.whatsapp_cloud.app_id')),
            cookie  : true,
            xfbml   : true,
            version : @json(config('services.whatsapp_cloud.api_version', 'v22.0')),
        });
    };

    // Listener pro Embedded Signup postar resultado de volta (WA_EMBEDDED_SIGNUP)
    window.addEventListener('message', function(event) {
        if (typeof event.origin !== 'string' || !event.origin.endsWith('facebook.com')) return;
        try {
            const payload = (typeof event.data === 'string') ? JSON.parse(event.data) : event.data;
            if (payload && payload.type === 'WA_EMBEDDED_SIGNUP') {
                if (payload.event === 'FINISH' && payload.data) {
                    window._wacloudSignupData = payload.data;
                } else if (payload.event === 'CANCEL') {
                    window._wacloudSignupData = null;
                }
            }
        } catch (e) { /* ignore non-JSON messages */ }
    });
</script>
@endif
<script>
const ILANG = @json(__('integrations'));
const DISCONNECT_URL      = @json(route('settings.integrations.disconnect', ['platform' => '__P__']));
const WA_CONNECT_URL      = @json(route('settings.integrations.whatsapp.connect'));
const WA_BASE_URL         = @json(rtrim(route('settings.integrations.whatsapp.connect'), '/connect'));
const IG_DISCONNECT_URL   = @json(route('settings.integrations.instagram.disconnect'));

let waQrPollInterval = null;
let waQrNullCount    = 0;
let waCurrentInstanceId = null;
let waConnected      = false;

// ── WhatsApp ──────────────────────────────────────────────────────────────────

function startWhatsappConnect(btn) {
    // Abre o modal primeiro — POST só acontece ao clicar "Gerar QR"
    waConnected = false;
    waCurrentInstanceId = null;
    document.getElementById('waLabelInput').value = '';
    document.getElementById('waLabelInput').readOnly = false;
    resetWaModalUi();
    document.getElementById('waQrModal').classList.add('open');
}

function resetWaModalUi() {
    // Reset QR area to placeholder
    document.getElementById('waQrArea').innerHTML =
        '<div class="wa-qr-placeholder"><i class="bi bi-qr-code-scan"></i><span>' + (ILANG.wa_qr_placeholder || 'QR Code') + '</span></div>';
    document.getElementById('waQrStatus').textContent = '';
    document.getElementById('waQrStatus').className = '';

    // Reset actions: cancelar + gerar QR
    const actions = document.getElementById('waModalActions');
    actions.innerHTML =
        '<button class="btn-wa-cancel" onclick="closeWaModal()">' + ILANG.qr_cancel + '</button>' +
        '<button class="btn-wa-generate" id="btnWaGenerate" onclick="generateWaQr()">' +
        '<i class="bi bi-qr-code"></i> ' + (ILANG.wa_generate_qr || 'Gerar QR Code') + '</button>';

    // Remove retry button if exists
    const retry = document.getElementById('btnWaRetry');
    if (retry) retry.remove();
}

async function generateWaQr() {
    const btn = document.getElementById('btnWaGenerate');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> ' + (ILANG.import_connecting || 'Conectando...');

    const label = document.getElementById('waLabelInput').value.trim();

    try {
        const res = await fetch(WA_CONNECT_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ label }),
        });
        const data = await res.json();

        if (data.success) {
            waCurrentInstanceId = data.instance_id;
            document.getElementById('waLabelInput').readOnly = true;

            // Trocar botões: só cancelar enquanto polling
            const actions = document.getElementById('waModalActions');
            actions.innerHTML = '<button class="btn-wa-cancel" onclick="closeWaModal()">' + ILANG.qr_cancel + '</button>';

            // Iniciar polling QR
            document.getElementById('waQrArea').innerHTML = '<i class="bi bi-arrow-clockwise spin" style="font-size:36px;color:#9ca3af;"></i>';
            document.getElementById('waQrStatus').textContent = ILANG.qr_waiting;
            waQrNullCount = 0;
            clearInterval(waQrPollInterval);
            pollWaQr();
            waQrPollInterval = setInterval(pollWaQr, 3000);
        } else {
            toastr.error(data.message || ILANG.toast_connect_error);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-qr-code"></i> ' + (ILANG.wa_generate_qr || 'Gerar QR Code');
        }
    } catch (e) {
        toastr.error(ILANG.toast_conn_error);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-qr-code"></i> ' + (ILANG.wa_generate_qr || 'Gerar QR Code');
    }
}

async function openWaModal(instanceId) {
    // Usado pelo reconnect — já tem instância, vai direto pro QR
    waConnected = false;
    waCurrentInstanceId = instanceId;
    document.getElementById('waLabelInput').value = '';
    document.getElementById('waLabelInput').readOnly = true;
    document.getElementById('waQrModal').classList.add('open');

    const actions = document.getElementById('waModalActions');
    actions.innerHTML = '<button class="btn-wa-cancel" onclick="closeWaModal()">' + ILANG.qr_cancel + '</button>';

    document.getElementById('waQrArea').innerHTML = '<i class="bi bi-arrow-clockwise spin" style="font-size:36px;color:#9ca3af;"></i>';
    document.getElementById('waQrStatus').textContent = ILANG.qr_waiting;
    document.getElementById('waQrStatus').className = '';

    // Restart WAHA session to ensure fresh QR generation
    try {
        await fetch(`${WA_BASE_URL}/${instanceId}/restart`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
    } catch (e) { /* continue to poll anyway */ }

    waQrNullCount = 0;
    clearInterval(waQrPollInterval);
    setTimeout(() => { pollWaQr(); waQrPollInterval = setInterval(pollWaQr, 3000); }, 2000);
}

function closeWaModal() {
    clearInterval(waQrPollInterval);
    document.getElementById('waQrModal').classList.remove('open');
}

async function pollWaQr() {
    if (!waCurrentInstanceId) return;
    try {
        const res  = await fetch(`${WA_BASE_URL}/${waCurrentInstanceId}/qr`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.status === 'connected') {
            clearInterval(waQrPollInterval);
            waConnected = true;
            document.getElementById('waQrArea').innerHTML = '<i class="bi bi-check-circle-fill" style="font-size:64px;color:#25D366;"></i>';
            const st = document.getElementById('waQrStatus');
            st.textContent = ILANG.qr_connected;
            st.className = 'connected';

            // Trocar ações: só botão fechar
            const actions = document.getElementById('waModalActions');
            actions.innerHTML = '<button class="btn-wa-generate" onclick="closeWaModal(); location.reload();">' +
                '<i class="bi bi-check-lg"></i> ' + (ILANG.qr_close || 'Fechar') + '</button>';

            // Auto-reload em 2.5s caso não feche manualmente
            setTimeout(() => location.reload(), 2500);
        } else if (data.qr_base64) {
            waQrNullCount = 0;
            document.getElementById('waQrArea').innerHTML = `<img src="data:image/png;base64,${data.qr_base64}" alt="QR Code">`;
            document.getElementById('waQrStatus').textContent = ILANG.qr_scan_now;
        } else if (data.status === 'disconnected' || ++waQrNullCount >= 5) {
            clearInterval(waQrPollInterval);
            document.getElementById('waQrArea').innerHTML =
                '<i class="bi bi-x-circle-fill" style="font-size:48px;color:#ef4444;margin-bottom:12px;display:block;"></i>';
            const st = document.getElementById('waQrStatus');
            st.textContent = ILANG.qr_expired;
            st.className = 'error';
            if (!document.getElementById('btnWaRetry')) {
                st.insertAdjacentHTML('afterend',
                    '<button id="btnWaRetry" style="margin-top:12px;padding:8px 20px;background:#25D366;color:#fff;border:none;border-radius:100px;cursor:pointer;font-weight:600;">'
                    + '<i class="bi bi-arrow-clockwise"></i> ' + ILANG.qr_retry + '</button>');
                document.getElementById('btnWaRetry').addEventListener('click', async () => {
                    const retryBtn = document.getElementById('btnWaRetry');
                    retryBtn.disabled = true;
                    retryBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Reiniciando...';
                    document.getElementById('waQrArea').innerHTML = '<i class="bi bi-arrow-clockwise spin" style="font-size:36px;color:#9ca3af;"></i>';
                    document.getElementById('waQrStatus').textContent = ILANG.qr_waiting;
                    document.getElementById('waQrStatus').className = '';

                    // Restart WAHA session (stop + start) to get a fresh QR
                    try {
                        await fetch(`${WA_BASE_URL}/${waCurrentInstanceId}/restart`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });
                    } catch (e) { /* continue to poll anyway */ }

                    retryBtn.remove();
                    waQrNullCount = 0;
                    clearInterval(waQrPollInterval);
                    // Small delay to let WAHA generate new QR
                    setTimeout(() => { pollWaQr(); waQrPollInterval = setInterval(pollWaQr, 3000); }, 2000);
                });
            }
        }
    } catch (e) {
        // Silenciar erros de polling
    }
}

async function reconnectWhatsapp(btn, instanceId) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i>';
    openWaModal(instanceId);
}

async function disconnectWhatsapp(btn, instanceId) {
    confirmAction({
        title: ILANG.confirm_wa_disc_title,
        message: ILANG.confirm_wa_disc_msg,
        confirmText: ILANG.confirm_wa_disc_btn,
        onConfirm: async () => {
            btn.disabled = true;
            try {
                const res  = await fetch(`${WA_BASE_URL}/${instanceId}/disconnect`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success(ILANG.toast_wa_disconnected);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr.error(ILANG.toast_disconnect_error);
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error(ILANG.toast_conn_error);
                btn.disabled = false;
            }
        },
    });
}

async function deleteWhatsappInstance(btn, instanceId) {
    confirmAction({
        title: ILANG.confirm_wa_remove_title,
        message: ILANG.confirm_wa_remove_msg,
        confirmText: ILANG.confirm_wa_remove_btn,
        onConfirm: async () => {
            btn.disabled = true;
            try {
                const res  = await fetch(`${WA_BASE_URL}/${instanceId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success(ILANG.toast_number_removed);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr.error(ILANG.toast_remove_error);
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error(ILANG.toast_conn_error);
                btn.disabled = false;
            }
        },
    });
}

async function saveWaLabel(input) {
    const instanceId = input.dataset.instanceId;
    const label = input.value.trim();
    if (!label) return;

    try {
        await fetch(`${WA_BASE_URL}/${instanceId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ label }),
        });
    } catch (e) {
        // silenciar
    }
}

// Fechar modal clicando no overlay
document.getElementById('waQrModal').addEventListener('click', function(e) {
    if (e.target === this) closeWaModal();
});

// ── Import histórico ────────────────────────────────────────────────────────

let waImportInstanceId = null;
let importPollTimer    = null;
let importStartedTime  = null;
let importTimeTimer    = null;

function openImportModal(instanceId) {
    waImportInstanceId = instanceId;
    document.getElementById('importDaysSelect').value = '30';
    document.getElementById('importConfigState').style.display = '';
    document.getElementById('importProgressState').style.display = 'none';
    document.getElementById('waImportModal').classList.add('open');

    // Checar se já tem import rodando
    checkExistingImport(instanceId);
}

function closeImportModal() {
    document.getElementById('waImportModal').classList.remove('open');
    if (importPollTimer) { clearInterval(importPollTimer); importPollTimer = null; }
    if (importTimeTimer) { clearInterval(importTimeTimer); importTimeTimer = null; }
    waImportInstanceId = null;
}

document.getElementById('waImportModal').addEventListener('click', function(e) {
    if (e.target === this) closeImportModal();
});

async function checkExistingImport(instanceId) {
    try {
        const res  = await fetch(`${WA_BASE_URL}/${instanceId}/import/progress`, {
            headers: { 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.status === 'running') {
            showProgressState();
            updateProgressUI(data);
            startProgressPolling(instanceId);
        }
    } catch (e) {}
}

async function startImport() {
    if (!waImportInstanceId) return;

    const days = document.getElementById('importDaysSelect').value;
    const btn  = document.getElementById('btnStartImport');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> ' + ILANG.import_starting;

    try {
        const res  = await fetch(`${WA_BASE_URL}/${waImportInstanceId}/import`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ days: parseInt(days) }),
        });
        const data = await res.json();

        if (data.success) {
            showProgressState();
            startProgressPolling(waImportInstanceId);
        } else {
            toastr.error(data.message || ILANG.toast_import_error);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cloud-download"></i> ' + ILANG.import_btn;
        }
    } catch (e) {
        toastr.error(ILANG.toast_conn_error);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-download"></i> ' + ILANG.import_btn;
    }
}

function showProgressState() {
    document.getElementById('importConfigState').style.display = 'none';
    document.getElementById('importProgressState').style.display = '';
    document.getElementById('importProgressBar').style.width = '0%';
    document.getElementById('importCountChats').textContent = '0';
    document.getElementById('importCountMessages').textContent = '0';
    document.getElementById('importCountSkipped').textContent = '0';
    document.getElementById('importCountTime').textContent = '0:00';
    document.getElementById('importCurrentChat').textContent = '';
    document.getElementById('importProgressTitle').innerHTML = '<i class="bi bi-arrow-clockwise spin" style="color:#0085f3;margin-right:6px;"></i>' + ILANG.import_progress;
    document.getElementById('importProgressSubtitle').textContent = ILANG.import_progress_sub;

    importStartedTime = Date.now();
    if (importTimeTimer) clearInterval(importTimeTimer);
    importTimeTimer = setInterval(() => {
        const elapsed = Math.floor((Date.now() - importStartedTime) / 1000);
        const min = Math.floor(elapsed / 60);
        const sec = String(elapsed % 60).padStart(2, '0');
        document.getElementById('importCountTime').textContent = `${min}:${sec}`;
    }, 1000);
}

function startProgressPolling(instanceId) {
    if (importPollTimer) clearInterval(importPollTimer);

    importPollTimer = setInterval(async () => {
        try {
            const res  = await fetch(`${WA_BASE_URL}/${instanceId}/import/progress`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            updateProgressUI(data);

            if (data.status === 'completed' || data.status === 'failed' || data.status === 'idle') {
                clearInterval(importPollTimer);
                importPollTimer = null;
                if (importTimeTimer) { clearInterval(importTimeTimer); importTimeTimer = null; }
            }
        } catch (e) {}
    }, 2000);
}

function updateProgressUI(data) {
    if (!data || data.status === 'idle') return;

    const processed = data.processed || 0;
    const total     = data.total || 0;
    const messages  = data.messages || 0;
    const skipped   = data.skipped || 0;
    const current   = data.current || '';
    const pct       = total > 0 ? Math.min(Math.round((processed / total) * 100), 100) : 0;

    document.getElementById('importProgressBar').style.width = (total > 0 ? pct : 30) + '%';
    document.getElementById('importCountChats').textContent = total > 0 ? `${processed}/${total}` : processed;
    document.getElementById('importCountMessages').textContent = messages.toLocaleString('pt-BR');
    document.getElementById('importCountSkipped').textContent = skipped.toLocaleString('pt-BR');

    if (data.started_at && importStartedTime) {
        // Sincronizar com o tempo real do servidor
        const serverStart = new Date(data.started_at).getTime();
        if (Math.abs(serverStart - importStartedTime) > 5000) {
            importStartedTime = serverStart;
        }
    }

    if (current) {
        document.getElementById('importCurrentChat').textContent = ILANG.import_processing.replace(':current', current);
        document.getElementById('importProgressSubtitle').textContent = ILANG.import_processing_chats;
    }

    if (data.status === 'completed') {
        document.getElementById('importProgressTitle').innerHTML = '<i class="bi bi-check-circle-fill" style="color:#059669;margin-right:6px;"></i>' + ILANG.import_completed;
        document.getElementById('importProgressSubtitle').textContent = ILANG.import_completed_msg.replace(':count', processed);
        document.getElementById('importProgressBar').style.width = '100%';
        document.getElementById('importProgressBar').style.background = '#059669';
        document.getElementById('importCurrentChat').textContent = '';
        document.getElementById('importCloseBtn').textContent = ILANG.import_close;
    } else if (data.status === 'failed') {
        document.getElementById('importProgressTitle').innerHTML = '<i class="bi bi-exclamation-triangle-fill" style="color:#dc2626;margin-right:6px;"></i>' + ILANG.import_error_title;
        document.getElementById('importProgressSubtitle').textContent = data.error || ILANG.import_error_default;
        document.getElementById('importProgressBar').style.background = '#dc2626';
        document.getElementById('importCurrentChat').textContent = '';
    }
}

function disconnectPlatform(platform, btn) {
    confirmAction({
        title: ILANG.confirm_disc_title,
        message: ILANG.confirm_disc_msg,
        confirmText: ILANG.confirm_disc_btn,
        onConfirm: async () => {
            const url = DISCONNECT_URL.replace('__P__', platform);
            btn.disabled = true;
            try {
                const res  = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success(ILANG.toast_integration_disc);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr.error(ILANG.toast_disconnect_error);
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error(ILANG.toast_conn_error);
                btn.disabled = false;
            }
        },
    });
}

async function disconnectInstagram(btn) {
    confirmAction({
        title: ILANG.confirm_ig_disc_title,
        message: ILANG.confirm_ig_disc_msg,
        confirmText: ILANG.confirm_ig_disc_btn,
        onConfirm: async () => {
            btn.disabled = true;
            try {
                const res  = await fetch(IG_DISCONNECT_URL, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success(ILANG.toast_ig_disconnected);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr.error(ILANG.toast_disconnect_error);
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error(ILANG.toast_conn_error);
                btn.disabled = false;
            }
        },
    });
}

// ── WhatsApp Button CRUD (múltiplos — até 3) ─────────────────────────
var _waButtons = {!! json_encode($waButtons->keyBy('id')->toArray()) !!};
var _baseUrl = "{{ rtrim(config('app.url'), '/') }}";

function openWaBtnDrawer(btnId) {
    var btn = btnId ? _waButtons[btnId] : null;
    document.getElementById('waBtnEditId').value = btn ? btn.id : '';
    document.getElementById('waBtnPhone').value = btn ? btn.phone_number : '';
    document.getElementById('waBtnLabel').value = btn ? btn.button_label : '';
    document.getElementById('waBtnMessage').value = btn ? btn.default_message : '';
    document.getElementById('waBtnFloating').checked = btn ? !!btn.show_floating : true;
    document.getElementById('waBtnActive').checked = btn ? !!btn.is_active : true;
    document.getElementById('waBtnDrawerTitle').textContent = btn ? 'Editar botão' : 'Novo botão WhatsApp';

    // Embed/tracking sections
    var embedSec = document.getElementById('waBtnEmbedSection');
    var trackSec = document.getElementById('waBtnTrackSection');
    if (btn && btn.website_token) {
        var embedCode = '<script src="' + _baseUrl + '/api/widget/' + btn.website_token + '/wa-button.js"><\/script>';
        document.getElementById('waBtnEmbed').value = embedCode;
        document.getElementById('waBtnTrackLink').value = _baseUrl + '/wa/' + btn.website_token;
        embedSec.style.display = 'block';
        trackSec.style.display = 'block';
    } else {
        embedSec.style.display = 'none';
        trackSec.style.display = 'none';
    }

    document.getElementById('waBtnOverlay').classList.add('open');
    document.getElementById('waBtnDrawer').classList.add('open');
}

function closeWaBtnDrawer(e) {
    if (e && e.target && e.target.id !== 'waBtnOverlay') return;
    document.getElementById('waBtnOverlay').classList.remove('open');
    document.getElementById('waBtnDrawer').classList.remove('open');
}

function saveWaButton() {
    var phone = document.getElementById('waBtnPhone').value.trim();
    if (!phone) { toastr.error(ILANG.toast_phone_required); return; }

    var editId = document.getElementById('waBtnEditId').value;
    var data = {
        phone_number: phone,
        default_message: document.getElementById('waBtnMessage').value || ILANG.wabtn_message_ph,
        button_label: document.getElementById('waBtnLabel').value || ILANG.wabtn_label_ph,
        show_floating: document.getElementById('waBtnFloating').checked,
        is_active: document.getElementById('waBtnActive').checked,
    };

    if (editId) {
        API.put("{{ route('settings.integrations.wa-button.store') }}/" + editId, data).done(function() {
            toastr.success(ILANG.toast_btn_updated);
            setTimeout(function(){ location.reload(); }, 800);
        });
    } else {
        API.post("{{ route('settings.integrations.wa-button.store') }}", data).done(function(r) {
            toastr.success(ILANG.toast_btn_created);
            setTimeout(function(){ location.reload(); }, 800);
        }).fail(function(xhr) {
            var msg = xhr.responseJSON?.message || 'Erro ao criar botão.';
            toastr.error(msg);
        });
    }
}

function deleteWaButton(btnId, phone) {
    if (!btnId) return;
    window.confirmAction({
        title: 'Remover botão WhatsApp?',
        message: 'O botão do número ' + phone + ' será removido permanentemente.',
        confirmText: 'Remover',
        onConfirm: function() {
            API.delete("{{ route('settings.integrations.wa-button.store') }}/" + btnId).done(function() {
                toastr.success(ILANG.toast_btn_removed);
                setTimeout(function(){ location.reload(); }, 800);
            });
        }
    });
}
</script>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.spin { animation: spin .8s linear infinite; display: inline-block; }

/* ── Integration drawers → modais centralizados ─────────────────── */
.int-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .55);
    z-index: 1040;
    animation: intFadeIn .15s ease-out;
}
.int-modal-overlay-fb { z-index: 1040; }
.int-modal-overlay.open { display: block; }

.int-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(.97);
    width: 540px;
    max-width: calc(100vw - 40px);
    max-height: 88vh;
    height: auto;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, .3);
    z-index: 1050;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    visibility: hidden;
    opacity: 0;
    transition: opacity .2s ease, transform .2s ease, visibility 0s linear .2s;
}
.int-modal-540 { width: 540px; }
.int-modal-fb  { width: 600px; }

.int-modal.open {
    visibility: visible;
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
    transition: opacity .2s ease, transform .2s ease, visibility 0s linear;
}
.int-modal > * { flex-shrink: 0; }
.int-modal > .fb-panel,
#waBtnDrawer > div:not(:first-child):not(:last-child) {
    flex: 1 1 auto;
    overflow-y: auto;
    min-height: 0;
}

@keyframes intFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@media (max-width: 640px) {
    .int-modal,
    .int-modal-540,
    .int-modal-fb {
        width: calc(100vw - 24px) !important;
        max-height: 92vh !important;
    }
}
</style>

{{-- ─── Facebook Lead Ads JS ──────────────────────────────────────── --}}
<script>
let fbCurrentStep = 1;
let fbPagesData = [];
let fbFormsData = [];
let fbSelectedPage = null;
let fbSelectedForm = null;
let fbPageAccessToken = null;

@php
    $crmFieldOptions = [
        '' => __('integrations.fb_map_ignore'),
        'name' => __('integrations.fb_map_name'),
        'email' => __('integrations.fb_map_email'),
        'phone' => __('integrations.fb_map_phone'),
        'company' => __('integrations.fb_map_company'),
        'value' => __('integrations.fb_map_value'),
        'tags' => __('integrations.fb_map_tags'),
    ];
    foreach ($customFields as $cf) {
        $crmFieldOptions['custom:' . $cf->id] = $cf->label . ' (campo extra)';
    }
@endphp
const CRM_FIELD_OPTIONS = {!! json_encode($crmFieldOptions) !!};
@php
    $fbConnectionsJs = $fbLeadConnections->map(fn($c) => [
        'id'               => $c->id,
        'form_id'          => $c->form_id,
        'form_name'        => $c->form_name,
        'page_id'          => $c->page_id,
        'page_name'        => $c->page_name,
        'pipeline_id'      => $c->pipeline_id,
        'stage_id'         => $c->stage_id,
        'field_mapping'    => $c->field_mapping ?? [],
        'default_tags'     => $c->default_tags ?? [],
        'form_fields_json' => $c->form_fields_json ?? [],
        'allow_duplicates' => (bool) $c->allow_duplicates,
    ])->keyBy('id');
@endphp
const FB_CONNECTIONS = {!! json_encode($fbConnectionsJs) !!};
let fbEditingId = null;

function openFbLeadDrawer() {
    fbEditingId = null;
    document.getElementById('fbLeadOverlay').classList.add('open');
    document.getElementById('fbLeadDrawer').classList.add('open');
    // Show step 1 (form/page picker)
    document.querySelector('.fb-step[data-step="1"]').style.display = '';
    // Reset allow_duplicates to default (checked = true)
    const allowDupEl = document.getElementById('fbAllowDuplicates');
    if (allowDupEl) allowDupEl.checked = true;
    fbGoStep(1);
    loadFbPages();
}

function editFbLeadConnection(id) {
    const conn = FB_CONNECTIONS[id];
    if (!conn) { toastr.error('Conexão não encontrada'); return; }

    fbEditingId = id;
    // Reuse existing globals so save/build use the right data
    fbSelectedPage = { id: conn.page_id, name: conn.page_name };
    fbSelectedForm = {
        id: conn.form_id,
        name: conn.form_name,
        questions: Array.isArray(conn.form_fields_json) ? conn.form_fields_json : [],
    };

    // Open modal
    document.getElementById('fbLeadOverlay').classList.add('open');
    document.getElementById('fbLeadDrawer').classList.add('open');

    // Hide step 1 — form/page locked in edit mode
    document.querySelector('.fb-step[data-step="1"]').style.display = 'none';

    // Pre-fill step 2
    const pipSel = document.getElementById('fbPipelineSelect');
    pipSel.value = conn.pipeline_id;
    onPipelineSelected();
    document.getElementById('fbStageSelect').value = conn.stage_id;
    document.getElementById('fbDefaultTags').value = (conn.default_tags || []).join(', ');
    const allowDupEl = document.getElementById('fbAllowDuplicates');
    if (allowDupEl) allowDupEl.checked = conn.allow_duplicates !== false;

    // Jump straight to step 2
    fbGoStep(2);

    // Override step 2 "back" to close (no step 1 in edit mode)
    // and pre-build mapping when going to step 3 — handled by buildFieldMapping reading FB_EDIT_MAPPING
    window.__fbEditMapping = conn.field_mapping || {};
}

function closeFbLeadDrawer(e) {
    if (e && e.target && e.target.id !== 'fbLeadOverlay') return;
    document.getElementById('fbLeadOverlay').classList.remove('open');
    document.getElementById('fbLeadDrawer').classList.remove('open');
}

function fbGoStep(step) {
    // In edit mode, step 1 is locked — back from step 2 closes the drawer
    if (step === 1 && fbEditingId) {
        closeFbLeadDrawer();
        return;
    }
    fbCurrentStep = step;
    document.querySelectorAll('.fb-panel').forEach(p => p.style.display = 'none');
    document.getElementById('fbStep' + step).style.display = 'block';

    document.querySelectorAll('.fb-step').forEach(s => {
        const sn = parseInt(s.dataset.step);
        s.classList.remove('active', 'done');
        if (sn === step) s.classList.add('active');
        else if (sn < step) s.classList.add('done');
    });

    if (step === 2) onPipelineSelected();
    if (step === 3) buildFieldMapping();
}

function loadFbPages() {
    const sel = document.getElementById('fbPageSelect');
    const searchArea = document.getElementById('fbPageSearchArea');
    sel.innerHTML = '<option value="">' + ILANG.fb_page_loading + '</option>';
    searchArea.style.display = 'none';

    window.API.get('{{ route("settings.integrations.facebook-leadads.pages") }}')
    .then(data => {
        if (!data.success) {
            sel.innerHTML = '<option value="">Error</option>';
            return;
        }
        if (data.pages && data.pages.length > 0) {
            // Classic login — pages listed normally
            fbPagesData = data.pages;
            sel.innerHTML = '<option value="">' + ILANG.fb_page_select + '</option>';
            data.pages.forEach(p => {
                sel.innerHTML += '<option value="' + p.id + '">' + window.escapeHtml(p.name) + '</option>';
            });
        } else {
            // Business Login — need manual search
            sel.style.display = 'none';
            searchArea.style.display = 'block';
        }
    })
    .catch(() => { sel.innerHTML = '<option value="">Error</option>'; });
}

function searchFbPage() {
    const input = document.getElementById('fbPageSearchInput').value.trim();
    const resultDiv = document.getElementById('fbPageSearchResult');
    if (!input) return;

    resultDiv.innerHTML = '<div style="color:#6b7280;font-size:13px;"><i class="bi bi-hourglass-split"></i> Buscando...</div>';

    window.API.get('{{ route("settings.integrations.facebook-leadads.search-page") }}?query=' + encodeURIComponent(input))
    .then(data => {
        if (!data.success || !data.page) {
            resultDiv.innerHTML = '<div style="color:#dc2626;font-size:13px;"><i class="bi bi-x-circle"></i> ' + (data.message || 'Página não encontrada') + '</div>';
            return;
        }
        fbSelectedPage = { id: data.page.id, name: data.page.name };
        fbPageAccessToken = null; // Will be fetched with forms
        resultDiv.innerHTML = '<div style="padding:10px 14px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;font-size:13px;">' +
            '<i class="bi bi-check-circle" style="color:#059669;"></i> <strong>' + window.escapeHtml(data.page.name) + '</strong> (ID: ' + data.page.id + ')' +
            '</div>';
        document.getElementById('fbNextStep1').disabled = false;
        // Pre-load forms
        loadFbFormsForPage(data.page.id);
    })
    .catch(() => {
        resultDiv.innerHTML = '<div style="color:#dc2626;font-size:13px;"><i class="bi bi-x-circle"></i> Erro na busca</div>';
    });
}

function loadFbFormsForPage(pageId) {
    const formSel = document.getElementById('fbFormSelect');
    formSel.innerHTML = '<option value="">' + ILANG.fb_form_loading + '</option>';
    formSel.disabled = true;

    window.API.get('{{ route("settings.integrations.facebook-leadads.forms") }}?page_id=' + pageId)
    .then(data => {
        if (!data.success || !data.forms || !data.forms.length) {
            formSel.innerHTML = '<option value="">' + ILANG.fb_form_empty + '</option>';
            return;
        }
        fbFormsData = data.forms;
        if (data.page_access_token) fbPageAccessToken = data.page_access_token;
        if (data.page_name && fbSelectedPage) fbSelectedPage.name = data.page_name;

        formSel.innerHTML = '<option value="">' + ILANG.fb_form_select + '</option>';
        data.forms.forEach(f => {
            const status = f.status === 'ACTIVE' ? '' : ' (' + f.status + ')';
            formSel.innerHTML += '<option value="' + f.id + '">' + window.escapeHtml(f.name) + status + '</option>';
        });
        formSel.disabled = false;
        formSel.onchange = function() {
            fbSelectedForm = fbFormsData.find(x => x.id === this.value) || null;
            document.getElementById('fbNextStep1').disabled = !this.value;
        };
    })
    .catch(() => { formSel.innerHTML = '<option value="">Error</option>'; });
}

function onPageSelected() {
    const pageId = document.getElementById('fbPageSelect').value;
    const formSel = document.getElementById('fbFormSelect');
    document.getElementById('fbNextStep1').disabled = true;

    if (!pageId) {
        formSel.innerHTML = '<option value="">Selecione a página primeiro</option>';
        formSel.disabled = true;
        return;
    }

    formSel.innerHTML = '<option value="">' + ILANG.fb_form_loading + '</option>';
    formSel.disabled = true;

    window.API.get('{{ route("settings.integrations.facebook-leadads.forms") }}?page_id=' + pageId)
    .then(data => {
        if (!data.success || !data.forms.length) {
            formSel.innerHTML = '<option value="">' + ILANG.fb_form_empty + '</option>';
            return;
        }
        fbFormsData = data.forms;
        fbSelectedPage = { id: pageId, name: data.page_name };
        fbPageAccessToken = data.page_access_token;

        formSel.innerHTML = '<option value="">' + ILANG.fb_form_select + '</option>';
        data.forms.forEach(f => {
            const status = f.status === 'ACTIVE' ? '' : ' (' + f.status + ')';
            formSel.innerHTML += '<option value="' + f.id + '">' + window.escapeHtml(f.name) + status + '</option>';
        });
        formSel.disabled = false;

        formSel.onchange = function() {
            fbSelectedForm = fbFormsData.find(x => x.id === this.value) || null;
            document.getElementById('fbNextStep1').disabled = !this.value;
        };
    })
    .catch(() => { formSel.innerHTML = '<option value="">Error</option>'; });
}

function onPipelineSelected() {
    const pipSel = document.getElementById('fbPipelineSelect');
    const opt = pipSel.options[pipSel.selectedIndex];
    const stageSel = document.getElementById('fbStageSelect');

    try {
        const stages = JSON.parse(opt.dataset.stages || '[]');
        stageSel.innerHTML = '';
        stages.sort((a,b) => a.position - b.position).forEach(s => {
            stageSel.innerHTML += '<option value="' + s.id + '">' + window.escapeHtml(s.name) + '</option>';
        });
    } catch(e) {
        stageSel.innerHTML = '<option value="">Erro</option>';
    }
}

function buildFieldMapping() {
    const container = document.getElementById('fbFieldMappingContainer');
    container.innerHTML = '';

    if (!fbSelectedForm || !fbSelectedForm.questions) {
        container.innerHTML = '<div style="color:#9ca3af;font-size:13px;">' + ILANG.fb_mapping_empty + '</div>';
        return;
    }

    const editMap = (fbEditingId && window.__fbEditMapping) ? window.__fbEditMapping : null;

    fbSelectedForm.questions.forEach(q => {
        const key = q.key || q.id || '';
        const label = q.label || key;

        let optionsHtml = '';
        for (const [val, text] of Object.entries(CRM_FIELD_OPTIONS)) {
            // Edit mode: honor existing mapping; otherwise auto-select common mappings
            let selected = '';
            if (editMap) {
                if (editMap[key] === val) selected = ' selected';
            } else {
                if (key === 'full_name' && val === 'name') selected = ' selected';
                else if (key === 'email' && val === 'email') selected = ' selected';
                else if (key === 'phone_number' && val === 'phone') selected = ' selected';
                else if (key === 'company_name' && val === 'company') selected = ' selected';
            }
            optionsHtml += '<option value="' + val + '"' + selected + '>' + window.escapeHtml(text) + '</option>';
        }

        container.innerHTML += '<div class="fb-mapping-row">' +
            '<div class="fb-field-label">' + window.escapeHtml(label) + '</div>' +
            '<div class="fb-arrow"><i class="bi bi-arrow-right"></i></div>' +
            '<select data-meta-key="' + window.escapeHtml(key) + '">' + optionsHtml + '</select>' +
            '</div>';
    });
}

function saveFbLeadConnection() {
    // Collect mapping
    const mapping = {};
    document.querySelectorAll('#fbFieldMappingContainer select').forEach(sel => {
        if (sel.value) {
            mapping[sel.dataset.metaKey] = sel.value;
        }
    });

    const tagsRaw = document.getElementById('fbDefaultTags').value;
    const defaultTags = tagsRaw ? tagsRaw.split(',').map(t => t.trim()).filter(Boolean) : [];
    const allowDuplicates = document.getElementById('fbAllowDuplicates')?.checked ?? true;

    if (fbEditingId) {
        // EDIT mode — only update pipeline/stage/mapping/tags/allow_duplicates
        const editPayload = {
            pipeline_id: document.getElementById('fbPipelineSelect').value,
            stage_id: document.getElementById('fbStageSelect').value,
            field_mapping: mapping,
            default_tags: defaultTags.length ? defaultTags : null,
            allow_duplicates: allowDuplicates,
        };
        const url = '{{ route("settings.integrations.facebook-leadads.connections.update", ["connection" => "__ID__"]) }}'.replace('__ID__', fbEditingId);
        window.API.put(url, editPayload)
        .then(data => {
            if (data.success) {
                toastr.success(ILANG.fb_save_success);
                closeFbLeadDrawer();
                setTimeout(() => location.reload(), 800);
            } else {
                toastr.error(data.message || 'Erro ao salvar');
            }
        })
        .catch(() => { toastr.error(ILANG.fb_save_error); });
        return;
    }

    const payload = {
        page_id: fbSelectedPage.id,
        page_name: fbSelectedPage.name,
        page_access_token: fbPageAccessToken,
        form_id: fbSelectedForm.id,
        form_name: fbSelectedForm.name || fbSelectedForm.id,
        form_fields_json: fbSelectedForm.questions || [],
        pipeline_id: document.getElementById('fbPipelineSelect').value,
        stage_id: document.getElementById('fbStageSelect').value,
        field_mapping: mapping,
        default_tags: defaultTags.length ? defaultTags : null,
        allow_duplicates: allowDuplicates,
    };

    window.API.post('{{ route("settings.integrations.facebook-leadads.connections.store") }}', payload)
    .then(data => {
        if (data.success) {
            toastr.success(ILANG.fb_save_success);
            closeFbLeadDrawer();
            setTimeout(() => location.reload(), 800);
        } else {
            toastr.error(data.message || 'Erro ao salvar');
        }
    })
    .catch(err => { toastr.error(ILANG.fb_save_error); });
}

function deleteFbLeadConnection(id, btn) {
    confirmAction({
        title: ILANG.fb_lead_delete,
        message: ILANG.fb_confirm_delete,
        confirmText: ILANG.fb_lead_delete,
        onConfirm: async () => {
            if (btn) btn.disabled = true;
            try {
                const url = '{{ route("settings.integrations.facebook-leadads.connections.destroy", ["connection" => "__ID__"]) }}'.replace('__ID__', id);
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success(ILANG.fb_deleted_success);
                    setTimeout(() => location.reload(), 800);
                } else {
                    toastr.error(data.message || 'Erro');
                    if (btn) btn.disabled = false;
                }
            } catch (e) {
                toastr.error('Erro');
                if (btn) btn.disabled = false;
            }
        },
    });
}

function disconnectFbLeadAds(btn) {
    confirmAction({
        title: ILANG.fb_lead_title,
        message: ILANG.fb_confirm_disconnect,
        confirmText: ILANG.fb_lead_disconnect,
        onConfirm: async () => {
            btn.disabled = true;
            try {
                const res = await fetch('{{ route("settings.integrations.facebook-leadads.disconnect") }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success(ILANG.fb_disconnected_success);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    toastr.error(data.message || 'Erro');
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error('Erro');
                btn.disabled = false;
            }
        }
    });
}

// ── WhatsApp Cloud API: connect via Embedded Signup (FB JS SDK) ─────────
// Usa FB.login() com featureType=whatsapp_business_app_onboarding pro fluxo
// de Coexistência (cliente escaneia QR no celular). Fallback pro OAuth velho
// (window.open) caso config_id ainda não esteja configurado no servidor.
function connectWhatsappCloud() {
    const configId  = @json(config('services.whatsapp_cloud.config_id'));
    const fbVersion = @json(config('services.whatsapp_cloud.api_version', 'v22.0'));

    // Sem config_id → ainda em fase de aprovação na Meta. Usa o fluxo OAuth velho
    // (escolhe WABA existente) como fallback pra testes.
    if (!configId) {
        return _connectWhatsappCloudFallback();
    }

    if (typeof FB === 'undefined') {
        toastr.error('SDK do Facebook ainda carregando. Tente novamente em 2s.');
        return;
    }

    // Limpa dados de signup anterior
    window._wacloudSignupData = null;

    FB.login(function(response) {
        if (!response || !response.authResponse || !response.authResponse.code) {
            toastr.info('Conexão cancelada.');
            return;
        }

        const code       = response.authResponse.code;
        const signupData = window._wacloudSignupData || {};

        if (!signupData.phone_number_id || !signupData.waba_id) {
            toastr.error('Dados incompletos do Embedded Signup. Tente novamente.');
            return;
        }

        toastr.info('Registrando seu número...');

        $.ajax({
            url    : @json(route('settings.integrations.whatsapp-cloud.exchange')),
            method : 'POST',
            data   : {
                _token          : @json(csrf_token()),
                code            : code,
                phone_number_id : signupData.phone_number_id,
                waba_id         : signupData.waba_id,
                business_id     : signupData.business_id || null,
            },
        }).done(function(data) {
            if (data && data.success) {
                toastr.success('WhatsApp conectado com sucesso!');
                setTimeout(() => location.reload(), 800);
            } else {
                toastr.error((data && data.message) || 'Erro ao conectar.');
            }
        }).fail(function(xhr) {
            const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Erro ao conectar com o servidor.';
            toastr.error(msg);
        });
    }, {
        config_id     : configId,
        scope         : 'whatsapp_business_management,whatsapp_business_messaging',
        response_type : 'code',
        override_default_response_type: true,
        extras        : {
            setup              : {},
            featureType        : 'whatsapp_business_app_onboarding',
            sessionInfoVersion : '3',
        },
    });
}

// Fallback: OAuth tradicional via popup window.open (sem config_id).
// Permite testar a infra do backend antes do App Review aprovar
// whatsapp_business_messaging. Usuário precisa ter WABA pré-existente.
function _connectWhatsappCloudFallback() {
    const url  = '{{ route("settings.integrations.whatsapp-cloud.redirect") }}';
    const w    = 600;
    const h    = 750;
    const left = Math.max(0, (window.screen.width  - w) / 2);
    const top  = Math.max(0, (window.screen.height - h) / 2);

    const popup = window.open(
        url,
        'wacloud_connect',
        `width=${w},height=${h},left=${left},top=${top},resizable=yes,scrollbars=yes,status=no,toolbar=no,menubar=no,location=no`
    );

    if (!popup || popup.closed || typeof popup.closed === 'undefined') {
        toastr.error('Permita popups deste site para conectar com o Meta');
        return;
    }

    const onMessage = (event) => {
        if (event.data && event.data.type === 'wacloud_done') {
            window.removeEventListener('message', onMessage);
            if (event.data.success) toastr.success('WhatsApp Cloud conectado!');
        }
    };
    window.addEventListener('message', onMessage);

    const poll = setInterval(() => {
        if (popup.closed) {
            clearInterval(poll);
            window.removeEventListener('message', onMessage);
            setTimeout(() => location.reload(), 600);
        }
    }, 500);
}

// ── WhatsApp Cloud API: disconnect instance ─────────────────────────────
function disconnectWaCloud(instanceId, btn) {
    confirmAction({
        title: 'Desconectar WhatsApp Cloud?',
        message: 'O número será removido do Syncro. Você pode reconectar depois.',
        confirmText: 'Desconectar',
        onConfirm: async () => {
            btn.disabled = true;
            try {
                const url = '{{ route("settings.integrations.whatsapp-cloud.disconnect", ["instance" => "__ID__"]) }}'.replace('__ID__', instanceId);
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success('Desconectado');
                    setTimeout(() => location.reload(), 800);
                } else {
                    toastr.error(data.message || 'Erro');
                    btn.disabled = false;
                }
            } catch (e) {
                toastr.error('Erro');
                btn.disabled = false;
            }
        }
    });
}
</script>
@endpush
