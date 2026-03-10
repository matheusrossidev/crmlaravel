{{-- Cookie Consent Banner + Preferences Modal (LGPD) --}}
<div id="cookieConsent" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:99999;
    background:#fff;border-top:1px solid #e2e8f0;padding:16px 24px;
    box-shadow:0 -4px 12px rgba(0,0,0,.08);font-family:'Inter',sans-serif;">
    <div style="max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div style="flex:1;min-width:280px;">
            <p style="margin:0 0 2px;font-size:14px;font-weight:600;color:#1a1d23;">Sua Privacidade</p>
            <p style="margin:0;font-size:13px;color:#374151;line-height:1.55;">
                Utilizamos cookies para manter sua sessão segura e melhorar sua experiência.
                Você pode gerenciar suas preferências a qualquer momento.
                <a href="{{ route('privacy') }}" target="_blank" style="color:#0085f3;text-decoration:none;font-weight:500;">Política de Privacidade</a>
            </p>
        </div>
        <div style="display:flex;gap:10px;flex-shrink:0;">
            <button onclick="openCookiePrefs()" style="padding:9px 18px;background:#fff;color:#374151;
                border:1.5px solid #d1d5db;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;">
                Preferências
            </button>
            <button onclick="acceptAllCookies()" style="padding:9px 22px;background:#0085f3;color:#fff;border:none;
                border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                Aceitar todos
            </button>
        </div>
    </div>
</div>

{{-- Preferences Modal --}}
<div id="cookiePrefModal" style="display:none;position:fixed;inset:0;z-index:100000;
    background:rgba(0,0,0,.45);font-family:'Inter',sans-serif;
    justify-content:center;align-items:center;">
    <div style="background:#fff;border-radius:14px;width:94%;max-width:520px;max-height:90vh;overflow-y:auto;
        box-shadow:0 20px 60px rgba(0,0,0,.15);">

        {{-- Header --}}
        <div style="padding:20px 24px 16px;border-bottom:1px solid #f0f2f7;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <h3 style="margin:0;font-size:16px;font-weight:700;color:#1a1d23;">Preferências de Cookies</h3>
                <button onclick="closeCookiePrefs()" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:18px;">
                    &times;
                </button>
            </div>
            <p style="margin:6px 0 0;font-size:13px;color:#6b7280;line-height:1.5;">
                Gerencie quais cookies deseja permitir. Cookies essenciais não podem ser desativados pois são necessários para o funcionamento da plataforma.
            </p>
        </div>

        {{-- Cookie Categories --}}
        <div style="padding:8px 24px 16px;">

            {{-- Essential --}}
            <div style="padding:16px 0;border-bottom:1px solid #f0f2f7;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                    <span style="font-size:14px;font-weight:600;color:#1a1d23;">Essenciais</span>
                    <span style="font-size:11px;font-weight:600;color:#059669;background:#ecfdf5;padding:2px 10px;border-radius:20px;">Sempre ativo</span>
                </div>
                <p style="margin:0;font-size:12.5px;color:#6b7280;line-height:1.5;">
                    Necessários para o funcionamento básico da plataforma. Incluem cookies de sessão (autenticação) e proteção CSRF. Sem eles, o site não funciona.
                </p>
            </div>

            {{-- Functional --}}
            <div style="padding:16px 0;border-bottom:1px solid #f0f2f7;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                    <span style="font-size:14px;font-weight:600;color:#1a1d23;">Funcionais</span>
                    <div style="position:relative;width:42px;height:24px;cursor:pointer;" onclick="toggleCookie('cookieFunctional')">
                        <input type="checkbox" id="cookieFunctional" checked style="display:none;">
                        <span id="toggleFunctional" class="ck-toggle active"></span>
                    </div>
                </div>
                <p style="margin:0;font-size:12.5px;color:#6b7280;line-height:1.5;">
                    Permitem lembrar suas preferências como idioma, layout do painel (sidebar colapsada) e configurações de exibição. Melhoram sua experiência sem coletar dados pessoais.
                </p>
            </div>

            {{-- Analytics --}}
            <div style="padding:16px 0;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                    <span style="font-size:14px;font-weight:600;color:#1a1d23;">Analíticos</span>
                    <div style="position:relative;width:42px;height:24px;cursor:pointer;" onclick="toggleCookie('cookieAnalytics')">
                        <input type="checkbox" id="cookieAnalytics" style="display:none;">
                        <span id="toggleAnalytics" class="ck-toggle"></span>
                    </div>
                </div>
                <p style="margin:0;font-size:12.5px;color:#6b7280;line-height:1.5;">
                    Nos ajudam a entender como você usa a plataforma para melhorar nossos serviços. Nenhum dado é compartilhado com terceiros para publicidade.
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding:16px 24px;border-top:1px solid #f0f2f7;display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="rejectOptionalCookies()" style="padding:9px 18px;background:#fff;color:#374151;
                border:1.5px solid #d1d5db;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;">
                Apenas essenciais
            </button>
            <button onclick="savePrefs()" style="padding:9px 22px;background:#0085f3;color:#fff;border:none;
                border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                Salvar preferências
            </button>
        </div>
    </div>
</div>

<style>
.ck-toggle {
    position:absolute;inset:0;background:#d1d5db;border-radius:24px;transition:.2s;cursor:pointer;
}
.ck-toggle::before {
    content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;
    background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.15);
}
.ck-toggle.active { background:#22c55e; }
.ck-toggle.active::before { transform:translateX(18px); }
</style>

<script>
(function() {
    var consent = localStorage.getItem('cookie_consent');
    if (!consent) {
        document.getElementById('cookieConsent').style.display = 'block';
    } else {
        try {
            var prefs = JSON.parse(consent);
            if (prefs.functional !== undefined) return;
        } catch(e) {}
        // Legacy simple consent — show banner for granular re-consent
        // Don't show again if already accepted (even legacy)
    }
})();

function acceptAllCookies() {
    var prefs = { essential: true, functional: true, analytics: true, accepted_at: new Date().toISOString() };
    localStorage.setItem('cookie_consent', JSON.stringify(prefs));
    document.getElementById('cookieConsent').style.display = 'none';
    closeCookiePrefs();
}

function rejectOptionalCookies() {
    var prefs = { essential: true, functional: false, analytics: false, accepted_at: new Date().toISOString() };
    localStorage.setItem('cookie_consent', JSON.stringify(prefs));
    document.getElementById('cookieConsent').style.display = 'none';
    closeCookiePrefs();
}

function savePrefs() {
    var prefs = {
        essential: true,
        functional: document.getElementById('cookieFunctional').checked,
        analytics: document.getElementById('cookieAnalytics').checked,
        accepted_at: new Date().toISOString()
    };
    localStorage.setItem('cookie_consent', JSON.stringify(prefs));
    document.getElementById('cookieConsent').style.display = 'none';
    closeCookiePrefs();
}

function toggleCookie(id) {
    var cb = document.getElementById(id);
    cb.checked = !cb.checked;
    var toggleId = 'toggle' + id.replace('cookie', '');
    var el = document.getElementById(toggleId);
    if (cb.checked) { el.classList.add('active'); } else { el.classList.remove('active'); }
}

function syncToggleVisual(id) {
    var cb = document.getElementById(id);
    var toggleId = 'toggle' + id.replace('cookie', '');
    var el = document.getElementById(toggleId);
    if (cb.checked) { el.classList.add('active'); } else { el.classList.remove('active'); }
}

function openCookiePrefs() {
    var modal = document.getElementById('cookiePrefModal');
    modal.style.display = 'flex';
    // Load saved prefs
    try {
        var saved = JSON.parse(localStorage.getItem('cookie_consent'));
        if (saved) {
            document.getElementById('cookieFunctional').checked = saved.functional !== false;
            document.getElementById('cookieAnalytics').checked = !!saved.analytics;
        }
    } catch(e) {}
    syncToggleVisual('cookieFunctional');
    syncToggleVisual('cookieAnalytics');
}

function closeCookiePrefs() {
    document.getElementById('cookiePrefModal').style.display = 'none';
}

document.getElementById('cookiePrefModal').addEventListener('click', function(e) {
    if (e.target === this) closeCookiePrefs();
});
</script>
