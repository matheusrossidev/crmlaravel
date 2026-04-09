{{--
    Modal de Biblioteca de Templates — reusável pelas 3 páginas (scoring,
    automations, sequences). Parâmetros esperados (passados via @include):

    @include('tenant.settings._template_library_modal', [
        'modalId'        => 'tplScoringLibrary',     // único por instância
        'title'          => __('scoring.tpl_modal_title'),
        'subtitle'       => __('scoring.tpl_modal_subtitle'),
        'templates'      => $templates,              // de XxxTemplates::all()
        'categories'     => $templateCategories,     // de XxxTemplates::categories()
        'installRoute'   => 'settings.scoring.templates.install',  // route name
        'onInstallJs'    => 'onScoringTemplateInstalled',          // window.<func>(rule|automation|sequence)
        'installedKey'   => 'rule',                  // chave do JSON de retorno (rule|automation|sequence)
    ])

    A view DEVE definir window.<onInstallJs> globalmente pra receber o objeto
    instalado e atualizar a tabela sem reload.
--}}

@once
<style>
    /* Botão "Modelos" no header das páginas (scoring/automations/sequences) */
    .btn-templates-trigger {
        height: 38px;
        padding: 0 16px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #0085f3;
        background: #eff6ff;
        border: 1.5px solid #bfdbfe;
        cursor: pointer;
        font-family: inherit;
        white-space: nowrap;
        transition: all .15s;
    }
    .btn-templates-trigger:hover {
        background: #dbeafe;
        border-color: #93c5fd;
    }
    .btn-templates-trigger i {
        font-size: 14px;
    }

    .tplib-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(15,23,42,.55); z-index: 9000;
    }
    .tplib-overlay.open { display: block; }
    .tplib-shell {
        position: fixed; top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: min(1100px, 95vw); height: min(720px, 92vh);
        background: #fff; border-radius: 16px; z-index: 9001;
        display: none; flex-direction: column;
        box-shadow: 0 24px 64px rgba(15,23,42,.2);
        overflow: hidden;
    }
    .tplib-shell.open { display: flex; }

    .tplib-header {
        padding: 18px 24px; border-bottom: 1px solid #f0f2f7;
        display: flex; align-items: center; justify-content: space-between;
        flex-shrink: 0;
    }
    .tplib-header-info { flex: 1; min-width: 0; }
    .tplib-title { font-size: 16px; font-weight: 700; color: #1a1d23; margin: 0 0 2px; }
    .tplib-subtitle { font-size: 12.5px; color: #6b7280; margin: 0; }
    .tplib-close {
        width: 32px; height: 32px; border-radius: 8px; border: none;
        background: rgba(0,0,0,.04); cursor: pointer; font-size: 16px;
        display: flex; align-items: center; justify-content: center;
        color: #6b7280; flex-shrink: 0;
    }
    .tplib-close:hover { background: rgba(0,0,0,.08); }

    .tplib-search-bar {
        padding: 14px 24px; border-bottom: 1px solid #f0f2f7;
        flex-shrink: 0;
    }
    .tplib-search {
        width: 100%; padding: 10px 16px 10px 40px;
        border: 1.5px solid #e8eaf0; border-radius: 10px;
        font-size: 13px; outline: none;
        background: #fafbfd url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239ca3af' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'/></svg>") no-repeat 12px center;
        background-size: 16px;
    }
    .tplib-search:focus { border-color: #0085f3; background-color: #fff; }

    .tplib-body {
        flex: 1; display: grid;
        grid-template-columns: 200px 1fr;
        overflow: hidden;
    }
    .tplib-cats {
        border-right: 1px solid #f0f2f7;
        padding: 14px 12px;
        overflow-y: auto;
        background: #fafbfd;
    }
    .tplib-cat {
        display: block; width: 100%;
        padding: 9px 12px; margin-bottom: 2px;
        background: none; border: none; border-radius: 8px;
        font-size: 12.5px; color: #6b7280; text-align: left;
        cursor: pointer; transition: all .15s;
        font-family: inherit;
    }
    .tplib-cat:hover { background: #f3f4f6; color: #374151; }
    .tplib-cat.active { background: #eff6ff; color: #0085f3; font-weight: 600; }

    .tplib-grid {
        padding: 18px 22px;
        overflow-y: auto;
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 14px;
        align-content: start;
        align-items: stretch;
    }
    @media (max-width: 720px) {
        .tplib-body { grid-template-columns: 1fr !important; }
        .tplib-cats { max-height: 140px; border-right: none; border-bottom: 1px solid #f0f2f7; }
        .tplib-grid { grid-template-columns: 1fr !important; }
    }

    .tplib-shell .tplib-card {
        background: #fff !important;
        border: 1.5px solid #e8eaf0 !important;
        border-radius: 12px !important;
        padding: 16px 18px !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 10px !important;
        transition: all .15s;
        min-width: 0;
        min-height: 180px !important;
        height: auto !important;
        overflow: hidden;
    }
    .tplib-shell .tplib-card:hover {
        border-color: #0085f3 !important;
        box-shadow: 0 6px 18px rgba(0,133,243,.08);
    }
    .tplib-shell .tplib-card-head {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        flex-shrink: 0 !important;
    }
    .tplib-shell .tplib-card-icon {
        width: 36px !important;
        min-width: 36px !important;
        max-width: 36px !important;
        height: 36px !important;
        min-height: 36px !important;
        max-height: 36px !important;
        border-radius: 9px !important;
        background: #eff6ff !important;
        color: #0085f3 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 16px !important;
        flex-shrink: 0 !important;
        flex-grow: 0 !important;
    }
    .tplib-shell .tplib-card-name {
        font-size: 14px !important;
        font-weight: 700 !important;
        color: #1a1d23 !important;
        flex: 1 1 auto !important;
        min-width: 0 !important;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        line-height: 1.3 !important;
    }
    .tplib-shell .tplib-card-desc {
        font-size: 12px !important;
        color: #6b7280 !important;
        line-height: 1.5 !important;
        display: block !important;
        overflow: hidden;
        word-wrap: break-word;
        flex: 1 1 auto !important;
        min-height: 50px !important;
    }
    .tplib-shell .tplib-card-btn {
        margin-top: auto !important;
        padding: 9px 14px !important;
        background: #0085f3 !important;
        color: #fff !important;
        border: none !important;
        border-radius: 8px !important;
        font-size: 12.5px !important;
        font-weight: 600 !important;
        cursor: pointer;
        transition: background .15s;
        width: 100% !important;
        flex-shrink: 0 !important;
    }
    .tplib-shell .tplib-card-btn:hover { background: #0070d1 !important; }
    .tplib-shell .tplib-card-btn:disabled { background: #9ca3af !important; cursor: not-allowed; }

    .tplib-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 24px;
        color: #9ca3af; font-size: 13px;
    }
</style>
@endonce

<div class="tplib-overlay" id="{{ $modalId }}-overlay" onclick="closeTplLibrary('{{ $modalId }}')"></div>
<div class="tplib-shell" id="{{ $modalId }}-shell">
    <div class="tplib-header">
        <div class="tplib-header-info">
            <h3 class="tplib-title">{{ $title }}</h3>
            <p class="tplib-subtitle">{{ $subtitle }}</p>
        </div>
        <button type="button" class="tplib-close" onclick="closeTplLibrary('{{ $modalId }}')">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="tplib-search-bar">
        <input type="text"
               class="tplib-search"
               id="{{ $modalId }}-search"
               data-tplib-search
               placeholder="{{ __('templates.search_placeholder') }}">
    </div>

    <div class="tplib-body">
        <aside class="tplib-cats" id="{{ $modalId }}-cats" data-active-cat="all">
            <button type="button" class="tplib-cat active" data-tplib-cat="all">
                {{ __('templates.cat_all') }}
            </button>
            @foreach($categories as $catSlug => $catLabel)
                <button type="button" class="tplib-cat" data-tplib-cat="{{ $catSlug }}">
                    {{ $catLabel }}
                </button>
            @endforeach
        </aside>

        <main class="tplib-grid" id="{{ $modalId }}-grid">
            @forelse($templates as $tpl)
                <div class="tplib-card"
                     data-slug="{{ $tpl['slug'] }}"
                     data-category="{{ $tpl['category'] }}"
                     data-name="{{ strtolower($tpl['name']) }}">
                    <div class="tplib-card-head">
                        <div class="tplib-card-icon">
                            <i class="bi {{ $tpl['icon'] ?? 'bi-collection' }}"></i>
                        </div>
                        <div class="tplib-card-name" title="{{ $tpl['name'] }}">{{ $tpl['name'] }}</div>
                    </div>
                    <div class="tplib-card-desc">{{ $tpl['description'] ?? '' }}</div>
                    <button type="button" class="tplib-card-btn"
                            data-tpl-slug="{{ $tpl['slug'] }}"
                            onclick="installTplFromLib('{{ $modalId }}', '{{ $tpl['slug'] }}', '{{ $installRoute }}', '{{ $onInstallJs }}', '{{ $installedKey }}', this)">
                        <i class="bi bi-download"></i> {{ __('templates.install_btn') }}
                    </button>
                </div>
            @empty
                <div class="tplib-empty">{{ __('templates.no_results') }}</div>
            @endforelse

            <div class="tplib-empty" id="{{ $modalId }}-no-results" style="display:none;">
                {{ __('templates.no_results') }}
            </div>
        </main>
    </div>
</div>

@once
<script>
/**
 * Template Library — versao refatorada (08/04/2026)
 *
 * Bug original: clique nos filtros de nicho nao filtrava nada. Causa raiz
 * provavel: o JS antigo extraia a categoria ativa via regex no atributo
 * `onclick` (`getAttribute('onclick')?.match(/'([^']+)'/g)?.[1]`) — fragil
 * e quebrava silenciosamente. Alem disso, handlers inline `onclick="..."`
 * misturavam state e logica.
 *
 * Refatoracao: event delegation no `tplib-cats` + state guardado em
 * `data-active-cat`. Sem regex. Sem onclick inline. Sem fragilidade.
 */

(function () {
    // Cada modal tem seu state em window pra os outros scripts (install handlers)
    // poderem chamar. Mantemos as funcoes globais como wrappers pra compat.
    window.__tplLib = window.__tplLib || {};

    function getModalState(modalId) {
        if (! window.__tplLib[modalId]) {
            window.__tplLib[modalId] = { activeCat: 'all', search: '' };
        }
        return window.__tplLib[modalId];
    }

    function applyFilters(modalId) {
        const state = getModalState(modalId);
        const term  = (state.search || '').toLowerCase().trim();
        let visible = 0;

        document.querySelectorAll(`#${modalId}-grid .tplib-card`).forEach(card => {
            const cardCat  = card.dataset.category || '';
            const cardName = card.dataset.name     || '';
            const matchCat = state.activeCat === 'all' || cardCat === state.activeCat;
            const matchSrc = term === '' || cardName.indexOf(term) !== -1;
            const show     = matchCat && matchSrc;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        const noResults = document.getElementById(modalId + '-no-results');
        if (noResults) noResults.style.display = visible === 0 ? '' : 'none';
    }

    function setActiveCat(modalId, cat) {
        const state = getModalState(modalId);
        state.activeCat = cat;

        const cats = document.getElementById(modalId + '-cats');
        if (cats) {
            cats.dataset.activeCat = cat;
            cats.querySelectorAll('.tplib-cat').forEach(b => {
                b.classList.toggle('active', b.dataset.tplibCat === cat);
            });
        }

        applyFilters(modalId);
    }

    function setSearch(modalId, value) {
        getModalState(modalId).search = value;
        applyFilters(modalId);
    }

    // Bind delegation por modal — chamado uma vez quando o modal abre
    function bindModalEvents(modalId) {
        const shell = document.getElementById(modalId + '-shell');
        if (! shell || shell.dataset.tplibBound === '1') {
            return;
        }
        shell.dataset.tplibBound = '1';

        // Click nas categorias (event delegation)
        const cats = document.getElementById(modalId + '-cats');
        if (cats) {
            cats.addEventListener('click', function (e) {
                const btn = e.target.closest('.tplib-cat');
                if (! btn) return;
                e.preventDefault();
                const cat = btn.dataset.tplibCat || 'all';
                setActiveCat(modalId, cat);
            });
        }

        // Input no search
        const search = document.getElementById(modalId + '-search');
        if (search) {
            search.addEventListener('input', function (e) {
                setSearch(modalId, e.target.value);
            });
        }
    }

    // ── API publica (mantida pra compat com onclick inline em outros lugares) ──

    window.openTplLibrary = function (modalId) {
        const overlay = document.getElementById(modalId + '-overlay');
        const shell   = document.getElementById(modalId + '-shell');
        if (! overlay || ! shell) {
            console.warn('[tplLib] Modal nao encontrado:', modalId);
            return;
        }
        bindModalEvents(modalId);
        overlay.classList.add('open');
        shell.classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById(modalId + '-search')?.focus(), 100);
    };

    window.closeTplLibrary = function (modalId) {
        document.getElementById(modalId + '-overlay')?.classList.remove('open');
        document.getElementById(modalId + '-shell')?.classList.remove('open');
        document.body.style.overflow = '';
    };

    // Wrappers de compat — alguma view antiga ou inline pode chamar
    window.filterTplCategory = function (modalId, category) {
        setActiveCat(modalId, category);
    };
    window.filterTplCards = function (modalId, search) {
        setSearch(modalId, search);
    };
    window.applyTplFilters = function (modalId, category, search) {
        const state = getModalState(modalId);
        if (category !== undefined) state.activeCat = category;
        if (search !== undefined)   state.search    = search;
        applyFilters(modalId);
    };
})();
async function installTplFromLib(modalId, slug, routeName, onInstallJs, installedKey, btn) {
    if (btn.disabled) return;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> ...';

    try {
        // Construir URL substituindo {slug} no route placeholder
        const url = window.tplLibraryRoutes[modalId].replace('__SLUG__', encodeURIComponent(slug));
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        });
        const data = await res.json();
        if (!data.success) {
            window.toastr?.error(data.message || 'Erro ao instalar template');
            return;
        }

        window.toastr?.success('Template instalado com sucesso!');

        // Chama callback global pra view atualizar a UI
        if (typeof window[onInstallJs] === 'function') {
            window[onInstallJs](data[installedKey]);
        }

        closeTplLibrary(modalId);
    } catch (e) {
        console.error(e);
        window.toastr?.error('Erro de conexão');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

// Map modalId → URL pattern (a view passa via window.tplLibraryRoutes)
window.tplLibraryRoutes = window.tplLibraryRoutes || {};
</script>
@endonce
