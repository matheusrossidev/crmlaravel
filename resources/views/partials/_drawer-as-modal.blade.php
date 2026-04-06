{{--
    Drawer-as-Modal compatibility layer.

    Inclua este partial em qualquer view que use as classes legadas de drawer
    (.drawer, .drawer-overlay, .list-drawer, .page-drawer) para convertê-las
    automaticamente em modais centralizados — sem precisar mudar o HTML
    nem o JS existente.

    Como usar:
        @include('partials._drawer-as-modal')

    O CSS é injetado via @push('styles') E posicionado APÓS as styles
    inline da página, garantindo que sobrescreva o slide-from-right original.

    Funciona porque:
    - Overlay continua sendo um sibling da drawer (markup intacto)
    - Drawer vira `position: fixed` centralizado via translate(-50%, -50%)
    - A classe `.open` aciona visibility/opacity (não mais `right: 0`)
    - JS existente (`openDrawer()`, `closeDrawer()` toggling `.open`) continua funcionando
--}}
@once
@push('styles')
<style>
/* ── Drawer → Modal compatibility layer ───────────────────────────── */
/* Sobrescreve as classes legadas de drawer (slide lateral) pra centralizar
   como modal. Usa especificidade alta para vencer estilos inline da página. */

.drawer-overlay,
.list-drawer-overlay,
.page-drawer-overlay {
    display: none !important;
    position: fixed !important;
    inset: 0 !important;
    background: rgba(0, 0, 0, .55) !important;
    z-index: 300 !important;
    animation: dam-fadeIn .15s ease-out;
}
.drawer-overlay.open,
.list-drawer-overlay.open,
.page-drawer-overlay.open {
    display: block !important;
}

.drawer,
.list-drawer,
.page-drawer {
    /* Reset slide-from-right */
    position: fixed !important;
    top: 50% !important;
    left: 50% !important;
    right: auto !important;
    bottom: auto !important;

    /* Center via transform */
    transform: translate(-50%, -50%) scale(.97) !important;

    /* Modal sizing */
    width: 720px !important;
    max-width: calc(100vw - 40px) !important;
    max-height: 88vh !important;
    height: auto !important;

    /* Visual */
    background: #fff !important;
    border-radius: 14px !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, .25) !important;
    z-index: 301 !important;

    /* Layout interno: header fixo + body scroll + footer fixo */
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;

    /* Hidden state */
    visibility: hidden;
    opacity: 0;
    transition: opacity .2s ease, transform .2s ease, visibility 0s linear .2s !important;
}

.drawer.open,
.list-drawer.open,
.page-drawer.open {
    visibility: visible;
    opacity: 1;
    transform: translate(-50%, -50%) scale(1) !important;
    transition: opacity .2s ease, transform .2s ease, visibility 0s linear !important;
}

/* Garantir que o body do drawer faz scroll dentro do modal */
.drawer .drawer-body,
.drawer > div[class*="body"],
.list-drawer .list-drawer-body,
.page-drawer .dw-body,
.page-drawer > div[class*="body"] {
    flex: 1 1 auto !important;
    overflow-y: auto !important;
    min-height: 0 !important;
}

/* Header e footer não devem encolher */
.drawer .drawer-header,
.drawer > div[class*="header"],
.list-drawer .list-drawer-header,
.page-drawer .dw-header,
.page-drawer > div[class*="header"] {
    flex-shrink: 0 !important;
}
.drawer .drawer-footer,
.drawer > div[class*="footer"],
.list-drawer .list-drawer-footer,
.page-drawer .dw-footer,
.page-drawer > div[class*="footer"] {
    flex-shrink: 0 !important;
}

@keyframes dam-fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* ── Mobile ── */
@media (max-width: 640px) {
    .drawer,
    .list-drawer,
    .page-drawer {
        width: calc(100vw - 24px) !important;
        max-height: 92vh !important;
    }
}

/* ── Larger drawers (lead drawer compartilhado) ───────────────────── */
/* O drawer de lead é mais complexo (abas, tags, produtos, histórico).
   Damos mais largura quando a página atual carrega o lead drawer. */
body:has(#drawerProfileLink) .drawer {
    width: 880px !important;
    max-width: calc(100vw - 40px) !important;
}
</style>
@endpush
@endonce
