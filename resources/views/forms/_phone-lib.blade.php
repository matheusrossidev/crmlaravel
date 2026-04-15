{{--
    Partial compartilhado — inicializa intl-tel-input v25 em todos os
    <input type="tel"> da página. v25 tem formatAsYouType nativo (máscara
    auto) + bandeiras via emoji. Sem dependência de sprite ou imask.

    Variáveis:
        $defaultCountry   (ISO-2, ex: 'BR') — abre selecionado
        $allowedCountries (array ISO-2, ou []) — filtra bandeiras; vazio = todas
--}}

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.2.0/build/css/intlTelInput.min.css">
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.2.0/build/js/intlTelInputWithUtils.min.js" defer></script>

<style>
    .iti { width: 100%; display: block; }
    .iti__country-list { font-size: 13.5px; max-height: 280px; z-index: 10000; }
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
        var interval = setInterval(function() {
            if (window.intlTelInput) {
                clearInterval(interval);
                initAll();
            }
        }, 50);
    });

    function initAll() {
        document.querySelectorAll('input[type="tel"]').forEach(applyPhone);
    }

    function applyPhone(inp) {
        if (inp._iti) return;
        var cfg = window.__syncroPhoneConfig || {};
        var opts = {
            initialCountry:   cfg.defaultCountry || 'br',
            separateDialCode: true,
            nationalMode:     true,
            autoPlaceholder:  'aggressive',
            formatAsYouType:  true,
            strictMode:       true,
            countryOrder:     ['br', 'us', 'pt'],
        };
        if (cfg.allowedCountries && cfg.allowedCountries.length > 0) {
            opts.onlyCountries = cfg.allowedCountries;
        }
        var iti = window.intlTelInput(inp, opts);
        inp._iti = iti;

        // Validação + mensagem de erro
        var errEl = document.createElement('div');
        errEl.className = 'phone-err-msg';
        errEl.textContent = 'Número inválido pro país selecionado.';
        inp.parentNode.parentNode.appendChild(errEl);

        inp.addEventListener('blur', function() {
            if (!inp.value.trim()) { errEl.style.display = 'none'; return; }
            errEl.style.display = iti.isValidNumber() ? 'none' : 'block';
        });

        var form = inp.closest('form');
        if (form && !form._syncroPhoneHooked) {
            form._syncroPhoneHooked = true;
            form.addEventListener('submit', function(e) {
                var ok = true;
                form.querySelectorAll('input[type="tel"]').forEach(function(el) {
                    if (!el._iti || !el.value.trim()) return;
                    if (!el._iti.isValidNumber()) {
                        ok = false;
                        var err = el.parentNode.parentNode.querySelector('.phone-err-msg');
                        if (err) err.style.display = 'block';
                        el.focus();
                    } else {
                        el.value = el._iti.getNumber();  // E.164 pro backend
                    }
                });
                if (!ok) { e.preventDefault(); e.stopPropagation(); }
            }, true);
        }
    }
})();
</script>
