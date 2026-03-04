(function () {
    'use strict';

    // ── Config ──────────────────────────────────────────────────────────────────
    var script   = document.currentScript || (function () {
        var scripts = document.getElementsByTagName('script');
        return scripts[scripts.length - 1];
    })();

    var token    = script.getAttribute('data-token');
    var apiBase  = script.src.replace('/widget.js', '');
    var colorPrimary = script.getAttribute('data-color') || '#0085f3';

    if (!token) { console.warn('[Widget] data-token is required'); return; }

    // ── Visitor ID (persisted via localStorage) ─────────────────────────────────
    var VID_KEY  = 'syncro_vid_' + token;
    var visitorId = localStorage.getItem(VID_KEY);
    if (!visitorId) {
        visitorId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
        localStorage.setItem(VID_KEY, visitorId);
    }

    // ── Inject CSS ───────────────────────────────────────────────────────────────
    var style = document.createElement('style');
    style.textContent = [
        '#syncro-launcher{position:fixed;bottom:24px;right:24px;width:56px;height:56px;border-radius:50%;background:' + colorPrimary + ';color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,.2);z-index:99998;transition:transform .15s;}',
        '#syncro-launcher:hover{transform:scale(1.08);}',
        '#syncro-launcher svg{width:26px;height:26px;fill:currentColor;}',
        '#syncro-panel{position:fixed;bottom:96px;right:24px;width:340px;max-height:520px;border-radius:16px;background:#fff;box-shadow:0 8px 40px rgba(0,0,0,.18);display:none;flex-direction:column;overflow:hidden;z-index:99997;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}',
        '#syncro-panel.open{display:flex;}',
        '.syncro-header{background:' + colorPrimary + ';color:#fff;padding:16px 18px;display:flex;align-items:center;gap:10px;flex-shrink:0;}',
        '.syncro-header-title{font-size:15px;font-weight:700;flex:1;}',
        '.syncro-messages{flex:1;overflow-y:auto;padding:14px 14px 8px;display:flex;flex-direction:column;gap:8px;min-height:200px;}',
        '.syncro-bubble{max-width:80%;padding:9px 13px;border-radius:12px;font-size:13.5px;line-height:1.45;word-break:break-word;}',
        '.syncro-bubble.in{background:#f0f2f7;color:#1a1d23;align-self:flex-start;border-bottom-left-radius:4px;}',
        '.syncro-bubble.out{background:' + colorPrimary + ';color:#fff;align-self:flex-end;border-bottom-right-radius:4px;}',
        '.syncro-input-row{display:flex;gap:8px;padding:10px 12px;border-top:1px solid #f0f2f7;flex-shrink:0;}',
        '.syncro-input{flex:1;padding:9px 12px;border:1.5px solid #e8eaf0;border-radius:9px;font-size:13.5px;outline:none;font-family:inherit;}',
        '.syncro-input:focus{border-color:' + colorPrimary + ';}',
        '.syncro-send{padding:9px 14px;background:' + colorPrimary + ';color:#fff;border:none;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;}',
        '.syncro-send:hover{opacity:.88;}',
        '.syncro-typing{padding:4px 14px 10px;font-size:11.5px;color:#9ca3af;display:none;}',
    ].join('');
    document.head.appendChild(style);

    // ── Build HTML ───────────────────────────────────────────────────────────────
    var launcher = document.createElement('button');
    launcher.id  = 'syncro-launcher';
    launcher.setAttribute('aria-label', 'Abrir chat');
    launcher.innerHTML = '<svg viewBox="0 0 24 24"><path d="M20 2H4C2.9 2 2 2.9 2 4v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 10H6V9h12v3zm0-4H6V5h12v3z"/></svg>';

    var panel = document.createElement('div');
    panel.id  = 'syncro-panel';
    panel.innerHTML = [
        '<div class="syncro-header">',
        '  <div class="syncro-header-title">Chat</div>',
        '</div>',
        '<div class="syncro-messages" id="syncro-msgs"></div>',
        '<div class="syncro-typing" id="syncro-typing">Digitando...</div>',
        '<div class="syncro-input-row">',
        '  <input class="syncro-input" id="syncro-input" placeholder="Digite uma mensagem..." autocomplete="off">',
        '  <button class="syncro-send" id="syncro-send">Enviar</button>',
        '</div>',
    ].join('');

    document.body.appendChild(panel);
    document.body.appendChild(launcher);

    // ── State ────────────────────────────────────────────────────────────────────
    var convId   = null;
    var isOpen   = false;
    var sending  = false;

    var msgsEl   = document.getElementById('syncro-msgs');
    var inputEl  = document.getElementById('syncro-input');
    var sendBtn  = document.getElementById('syncro-send');
    var typingEl = document.getElementById('syncro-typing');

    // ── Helpers ──────────────────────────────────────────────────────────────────
    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function appendBubble(text, direction) {
        var b = document.createElement('div');
        b.className = 'syncro-bubble ' + (direction === 'inbound' ? 'out' : 'in');
        b.textContent = text;
        msgsEl.appendChild(b);
        msgsEl.scrollTop = msgsEl.scrollHeight;
    }

    function showTyping(show) {
        typingEl.style.display = show ? 'block' : 'none';
    }

    // ── Init conversation ────────────────────────────────────────────────────────
    async function initConversation() {
        try {
            var res  = await fetch(apiBase + '/api/widget/' + token + '/init?visitor_id=' + visitorId, {
                headers: { 'Accept': 'application/json' }
            });
            var data = await res.json();
            if (!res.ok) { console.warn('[Widget] init error', data); return; }

            convId = data.conversation_id;

            // Render history
            (data.messages || []).forEach(function (m) {
                appendBubble(m.content, m.direction);
            });

            // Initial bot replies (new conversation start)
            (data.replies || []).forEach(function (r) {
                appendBubble(r, 'outbound');
            });
        } catch (e) {
            console.warn('[Widget] init failed', e);
        }
    }

    // ── Send message ─────────────────────────────────────────────────────────────
    async function sendMessage() {
        var text = inputEl.value.trim();
        if (!text || sending) return;

        inputEl.value = '';
        sending = true;
        sendBtn.disabled = true;
        appendBubble(text, 'inbound');

        showTyping(true);
        try {
            var res  = await fetch(apiBase + '/api/widget/' + token + '/message', {
                method:  'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body:    JSON.stringify({ visitor_id: visitorId, message: text }),
            });
            var data = await res.json();

            showTyping(false);
            (data.replies || []).forEach(function (r) {
                appendBubble(r, 'outbound');
            });
        } catch (e) {
            showTyping(false);
            console.warn('[Widget] send failed', e);
        }

        sending = false;
        sendBtn.disabled = false;
        inputEl.focus();
    }

    // ── Events ───────────────────────────────────────────────────────────────────
    launcher.addEventListener('click', function () {
        isOpen = !isOpen;
        panel.classList.toggle('open', isOpen);
        if (isOpen) {
            if (!convId) { initConversation(); }
            setTimeout(function () { inputEl.focus(); }, 100);
        }
    });

    sendBtn.addEventListener('click', sendMessage);

    inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
})();
