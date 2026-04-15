{{--
    Partial compartilhado — inicializa intl-tel-input + imask em todos os
    <input type="tel"> da página. Carrega libs via CDN (lazy). Usado pelas 3
    views hospedadas (public classic/conversational/multistep).

    Variáveis aceitas:
        $defaultCountry  (ISO-2, ex: 'BR') — abre selecionado
        $allowedCountries (array ISO-2, ou []) — filtra bandeiras; vazio = todas
--}}

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/23.0.12/css/intlTelInput.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/23.0.12/js/intlTelInput.min.js" defer></script>
<script src="https://unpkg.com/imask@7.6.1/dist/imask.min.js" defer></script>

<style>
    .iti { width: 100%; display: block; }
    .iti__tel-input { width: 100%; padding-left: 100px !important; }
    .iti__selected-flag { background: transparent !important; }
    .iti__country-list { font-size: 13px; z-index: 10000; }
    .phone-err-msg { font-size: 12px; color: #dc2626; margin-top: 4px; display: none; }
</style>

<script>
window.__syncroPhoneConfig = {
    defaultCountry:   @json(strtolower($defaultCountry ?? 'br')),
    allowedCountries: @json(array_map('strtolower', (array) ($allowedCountries ?? []))),
};

(function() {
    var ready = function(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    };

    ready(function() {
        // Espera libs carregarem (scripts com defer executam após DOMContentLoaded)
        var interval = setInterval(function() {
            if (window.intlTelInput && window.IMask) {
                clearInterval(interval);
                initAll();
            }
        }, 50);
    });

    function initAll() {
        document.querySelectorAll('input[type="tel"]').forEach(applyPhone);
    }

    function applyPhone(inp) {
        if (inp._iti) return;  // já inicializado
        var cfg = window.__syncroPhoneConfig || {};
        var opts = {
            initialCountry:   cfg.defaultCountry || 'br',
            separateDialCode: true,
            nationalMode:     true,
            autoPlaceholder:  'aggressive',
            utilsScript:      'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/23.0.12/js/utils.js',
        };
        if (cfg.allowedCountries && cfg.allowedCountries.length > 0) {
            opts.onlyCountries = cfg.allowedCountries;
        }
        var iti = window.intlTelInput(inp, opts);
        inp._iti = iti;

        var applyMask = function() {
            if (!window.IMask) return;
            try {
                var country = iti.getSelectedCountryData().iso2;
                if (!country || typeof window.intlTelInputUtils === 'undefined') return;
                var example = window.intlTelInputUtils.getExampleNumber(
                    country, true, window.intlTelInputUtils.numberFormat.NATIONAL
                );
                var maskStr = (example || '').replace(/\d/g, '0');
                if (inp._imask) { inp._imask.destroy(); inp._imask = null; }
                if (maskStr) inp._imask = window.IMask(inp, { mask: maskStr });
            } catch(e) {}
        };

        setTimeout(applyMask, 200);  // utils.js carrega async
        inp.addEventListener('countrychange', applyMask);

        // Validação no blur + ao submeter o form pai
        var errEl = document.createElement('div');
        errEl.className = 'phone-err-msg';
        errEl.textContent = 'Número inválido pro país selecionado.';
        inp.parentNode.insertBefore(errEl, inp.nextSibling);

        inp.addEventListener('blur', function() {
            if (!inp.value.trim()) { errEl.style.display = 'none'; return; }
            errEl.style.display = iti.isValidNumber() ? 'none' : 'block';
        });

        // Intercepta submit pra bloquear se inválido + trocar valor pelo E.164
        var form = inp.closest('form');
        if (form && !form._syncroPhoneHooked) {
            form._syncroPhoneHooked = true;
            form.addEventListener('submit', function(e) {
                var ok = true;
                form.querySelectorAll('input[type="tel"]').forEach(function(el) {
                    if (!el._iti || !el.value.trim()) return;
                    if (!el._iti.isValidNumber()) {
                        ok = false;
                        var err = el.nextElementSibling;
                        if (err && err.classList.contains('phone-err-msg')) err.style.display = 'block';
                        el.focus();
                    } else {
                        // Troca input.value pelo E.164 antes do submit
                        el.value = el._iti.getNumber();
                    }
                });
                if (!ok) { e.preventDefault(); e.stopPropagation(); }
            }, true);
        }
    }
})();
</script>
