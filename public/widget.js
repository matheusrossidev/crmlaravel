(function () {
    'use strict';

    // ── Config ──────────────────────────────────────────────────────────────────
    var script   = document.currentScript || (function () {
        var scripts = document.getElementsByTagName('script');
        return scripts[scripts.length - 1];
    })();

    var token        = script.getAttribute('data-token');
    var apiBase      = script.src.replace('/widget.js', '');
    var colorPrimary = script.getAttribute('data-color') || '#0085f3';

    if (!token) { console.warn('[Widget] data-token is required'); return; }

    // ── Visitor ID (persisted via localStorage) ─────────────────────────────────
    var VID_KEY   = 'syncro_vid_' + token;
    var visitorId = localStorage.getItem(VID_KEY);
    if (!visitorId) {
        visitorId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
        localStorage.setItem(VID_KEY, visitorId);
    }

    // ── Bot identity (populated from init response) ──────────────────────────────
    var botName   = 'Chat';
    var botAvatar = null;

    // ── Inject CSS ───────────────────────────────────────────────────────────────
    var style = document.createElement('style');
    style.textContent = [
        /* Launcher */
        '#syncro-launcher{position:fixed;bottom:24px;right:24px;width:60px;height:60px;border-radius:50%;background:' + colorPrimary + ';color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 20px rgba(0,0,0,.25);z-index:99998;transition:transform .15s;}',
        '#syncro-launcher:hover{transform:scale(1.08);}',
        '#syncro-launcher svg{width:28px;height:28px;fill:currentColor;}',
        '#syncro-launcher img{width:60px;height:60px;border-radius:50%;object-fit:cover;}',

        /* Welcome bubble */
        '#syncro-welcome{position:fixed;bottom:98px;right:24px;max-width:220px;background:#fff;border-radius:14px 14px 4px 14px;box-shadow:0 4px 24px rgba(0,0,0,.15);padding:11px 14px;font-size:13.5px;line-height:1.45;color:#1a1d23;z-index:99997;cursor:pointer;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;opacity:0;transform:translateY(8px);transition:opacity .3s,transform .3s;}',
        '#syncro-welcome.visible{opacity:1;transform:translateY(0);}',
        '#syncro-welcome-close{position:absolute;top:6px;right:8px;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:14px;line-height:1;padding:0;}',

        /* Panel */
        '#syncro-panel{position:fixed;bottom:96px;right:24px;width:340px;max-height:540px;border-radius:16px;background:#fff;box-shadow:0 8px 40px rgba(0,0,0,.18);display:none;flex-direction:column;overflow:hidden;z-index:99999;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}',
        '#syncro-panel.open{display:flex;}',

        /* Header */
        '.syncro-header{background:' + colorPrimary + ';color:#fff;padding:14px 16px;display:flex;align-items:center;gap:10px;flex-shrink:0;}',
        '.syncro-header-avatar{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.5);flex-shrink:0;}',
        '.syncro-header-avatar-placeholder{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,0.25);display:flex;align-items:center;justify-content:center;flex-shrink:0;}',
        '.syncro-header-info{flex:1;min-width:0;}',
        '.syncro-header-title{font-size:14.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}',
        '.syncro-header-status{font-size:11px;opacity:.85;display:flex;align-items:center;gap:4px;}',
        '.syncro-header-status::before{content:"";display:inline-block;width:7px;height:7px;background:#4ade80;border-radius:50%;}',
        '.syncro-header-close{background:rgba(255,255,255,0.15);border:none;color:#fff;width:28px;height:28px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .15s;}',
        '.syncro-header-close:hover{background:rgba(255,255,255,0.25);}',

        /* Messages */
        '.syncro-messages{flex:1;overflow-y:auto;padding:12px 12px 6px;display:flex;flex-direction:column;gap:6px;min-height:200px;}',

        /* Message rows */
        '.syncro-msg-row{display:flex;align-items:flex-end;gap:6px;}',
        '.syncro-msg-row.out-row{flex-direction:row-reverse;}',
        '.syncro-msg-avatar{width:26px;height:26px;border-radius:50%;object-fit:cover;flex-shrink:0;}',
        '.syncro-msg-avatar-placeholder{width:26px;height:26px;border-radius:50%;background:' + colorPrimary + '22;display:flex;align-items:center;justify-content:center;flex-shrink:0;}',

        /* Bubbles */
        '.syncro-bubble{max-width:75%;padding:9px 13px;border-radius:12px;font-size:13.5px;line-height:1.5;word-break:break-word;}',
        '.syncro-bubble.in{background:#f0f2f7;color:#1a1d23;border-bottom-left-radius:4px;}',
        '.syncro-bubble.out{background:' + colorPrimary + ';color:#fff;border-bottom-right-radius:4px;}',

        /* Typing */
        '.syncro-typing-row{display:flex;align-items:flex-end;gap:6px;padding:4px 0 6px;}',
        '.syncro-typing-bubble{background:#f0f2f7;border-radius:12px 12px 12px 4px;padding:10px 14px;display:flex;gap:4px;align-items:center;}',
        '.syncro-typing-dot{width:7px;height:7px;border-radius:50%;background:#9ca3af;animation:syncro-bounce .8s infinite ease-in-out;}',
        '.syncro-typing-dot:nth-child(2){animation-delay:.15s;}',
        '.syncro-typing-dot:nth-child(3){animation-delay:.3s;}',
        '@keyframes syncro-bounce{0%,80%,100%{transform:translateY(0);}40%{transform:translateY(-5px);}}',

        /* Quick reply buttons */
        '.syncro-buttons{display:flex;flex-wrap:wrap;gap:7px;padding:4px 0 6px;}',
        '.syncro-btn{padding:8px 16px;background:#fff;border:1.5px solid ' + colorPrimary + ';border-radius:20px;font-size:13px;font-weight:600;color:' + colorPrimary + ';cursor:pointer;transition:background .15s,color .15s;font-family:inherit;}',
        '.syncro-btn:hover{background:' + colorPrimary + ';color:#fff;}',

        /* Input row */
        '.syncro-input-row{display:flex;gap:8px;padding:10px 12px;border-top:1px solid #f0f2f7;flex-shrink:0;}',
        '.syncro-input{flex:1;padding:9px 12px;border:1.5px solid #e8eaf0;border-radius:9px;font-size:13.5px;outline:none;font-family:inherit;transition:border-color .15s;}',
        '.syncro-input:focus{border-color:' + colorPrimary + ';}',
        '.syncro-send{padding:9px 16px;background:' + colorPrimary + ';color:#fff;border:none;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;transition:opacity .15s;}',
        '.syncro-send:hover{opacity:.88;}',
        '.syncro-send:disabled{opacity:.5;cursor:default;}',
    ].join('');
    document.head.appendChild(style);

    // ── Build HTML ───────────────────────────────────────────────────────────────
    var launcher = document.createElement('button');
    launcher.id  = 'syncro-launcher';
    launcher.setAttribute('aria-label', 'Abrir chat');
    launcher.innerHTML = '<svg viewBox="0 0 24 24"><path d="M20 2H4C2.9 2 2 2.9 2 4v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>';

    var panel = document.createElement('div');
    panel.id  = 'syncro-panel';
    panel.innerHTML = [
        '<div class="syncro-header" id="syncro-header">',
        '  <div class="syncro-header-avatar-placeholder" id="syncro-hdr-avatar-wrap">',
        '    <svg viewBox="0 0 24 24" style="width:22px;height:22px;fill:rgba(255,255,255,0.85)"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>',
        '  </div>',
        '  <div class="syncro-header-info">',
        '    <div class="syncro-header-title" id="syncro-hdr-name">Chat</div>',
        '    <div class="syncro-header-status">Online agora</div>',
        '  </div>',
        '  <button class="syncro-header-close" id="syncro-close" aria-label="Fechar">',
        '    <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M19 6.4L17.6 5 12 10.6 6.4 5 5 6.4 10.6 12 5 17.6 6.4 19 12 13.4 17.6 19 19 17.6 13.4 12z"/></svg>',
        '  </button>',
        '</div>',
        '<div class="syncro-messages" id="syncro-msgs"></div>',
        '<div class="syncro-input-row">',
        '  <input class="syncro-input" id="syncro-input" placeholder="Digite uma mensagem..." autocomplete="off">',
        '  <button class="syncro-send" id="syncro-send">Enviar</button>',
        '</div>',
    ].join('');

    document.body.appendChild(panel);
    document.body.appendChild(launcher);

    // ── State ────────────────────────────────────────────────────────────────────
    var convId  = null;
    var isOpen  = false;
    var sending = false;
    var welcomeEl = null;

    var msgsEl  = document.getElementById('syncro-msgs');
    var inputEl = document.getElementById('syncro-input');
    var sendBtn = document.getElementById('syncro-send');
    var hdrName = document.getElementById('syncro-hdr-name');
    var hdrAvatarWrap = document.getElementById('syncro-hdr-avatar-wrap');

    // ── Helpers ──────────────────────────────────────────────────────────────────
    function sleep(ms) { return new Promise(function(r){ setTimeout(r, ms); }); }

    function typingDelay(text) {
        // 600ms base + 15ms per char, capped at 1800ms
        return Math.min(600 + text.length * 15, 1800);
    }

    function appendBubble(text, direction, instant) {
        var row = document.createElement('div');
        row.className = 'syncro-msg-row ' + (direction === 'inbound' ? 'out-row' : '');

        if (direction !== 'inbound' && botAvatar) {
            var av = document.createElement('img');
            av.src = botAvatar;
            av.className = 'syncro-msg-avatar';
            av.alt = botName;
            row.appendChild(av);
        } else if (direction !== 'inbound') {
            var avPh = document.createElement('div');
            avPh.className = 'syncro-msg-avatar-placeholder';
            avPh.innerHTML = '<svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:' + colorPrimary + '"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>';
            row.appendChild(avPh);
        }

        var b = document.createElement('div');
        b.className = 'syncro-bubble ' + (direction === 'inbound' ? 'out' : 'in');
        b.textContent = text;
        row.appendChild(b);

        msgsEl.appendChild(row);
        msgsEl.scrollTop = msgsEl.scrollHeight;
        return row;
    }

    function showTypingIndicator() {
        var row = document.createElement('div');
        row.className = 'syncro-typing-row';
        row.id = 'syncro-typing-row';

        if (botAvatar) {
            var av = document.createElement('img');
            av.src = botAvatar;
            av.className = 'syncro-msg-avatar';
            av.alt = botName;
            row.appendChild(av);
        } else {
            var avPh = document.createElement('div');
            avPh.className = 'syncro-msg-avatar-placeholder';
            avPh.innerHTML = '<svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:' + colorPrimary + '"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>';
            row.appendChild(avPh);
        }

        var bubble = document.createElement('div');
        bubble.className = 'syncro-typing-bubble';
        bubble.innerHTML = '<div class="syncro-typing-dot"></div><div class="syncro-typing-dot"></div><div class="syncro-typing-dot"></div>';
        row.appendChild(bubble);

        msgsEl.appendChild(row);
        msgsEl.scrollTop = msgsEl.scrollHeight;
        return row;
    }

    function hideTypingIndicator() {
        var el = document.getElementById('syncro-typing-row');
        if (el) el.remove();
    }

    function renderButtons(buttons) {
        var existing = document.getElementById('syncro-quick-btns');
        if (existing) existing.remove();

        if (!buttons || !buttons.length) return;

        var row = document.createElement('div');
        row.className = 'syncro-buttons';
        row.id = 'syncro-quick-btns';

        buttons.forEach(function(btn) {
            var b = document.createElement('button');
            b.className = 'syncro-btn';
            b.textContent = btn.label;
            b.addEventListener('click', function() {
                row.remove();
                inputEl.value = btn.value;
                sendMessage();
            });
            row.appendChild(b);
        });

        msgsEl.appendChild(row);
        msgsEl.scrollTop = msgsEl.scrollHeight;
    }

    async function renderReplies(replies, buttons, instant) {
        for (var i = 0; i < replies.length; i++) {
            var typingRow;
            if (!instant) {
                typingRow = showTypingIndicator();
                await sleep(typingDelay(replies[i]));
                typingRow.remove();
            }
            appendBubble(replies[i], 'outbound', instant);
            if (!instant && i < replies.length - 1) {
                await sleep(250);
            }
        }
        renderButtons(buttons);
    }

    function updateBotIdentity(name, avatar) {
        botName   = name  || 'Chat';
        botAvatar = avatar || null;

        hdrName.textContent = botName;

        if (botAvatar) {
            hdrAvatarWrap.innerHTML = '';
            hdrAvatarWrap.className = '';
            var img = document.createElement('img');
            img.src = botAvatar;
            img.className = 'syncro-header-avatar';
            img.alt = botName;
            hdrAvatarWrap.appendChild(img);
        }

        // Update launcher icon to avatar if present
        if (botAvatar) {
            launcher.innerHTML = '<img src="' + botAvatar + '" alt="' + botName + '" style="width:60px;height:60px;border-radius:50%;object-fit:cover;">';
        }
    }

    // ── Welcome bubble ───────────────────────────────────────────────────────────
    function showWelcomeBubble(message) {
        if (isOpen || welcomeEl) return;

        welcomeEl = document.createElement('div');
        welcomeEl.id = 'syncro-welcome';
        welcomeEl.innerHTML =
            '<button class="syncro-welcome-close" id="syncro-welcome-close" aria-label="Fechar" style="position:absolute;top:6px;right:8px;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:16px;line-height:1;padding:0;">&times;</button>' +
            '<span>' + escHtml(message) + '</span>';

        welcomeEl.addEventListener('click', function(e) {
            if (e.target.id === 'syncro-welcome-close') {
                dismissWelcome();
                return;
            }
            dismissWelcome();
            openChat();
        });

        document.body.appendChild(welcomeEl);

        // Trigger fade-in
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                welcomeEl.classList.add('visible');
            });
        });
    }

    function dismissWelcome() {
        if (welcomeEl) {
            welcomeEl.classList.remove('visible');
            var el = welcomeEl;
            welcomeEl = null;
            setTimeout(function() { if (el.parentNode) el.remove(); }, 350);
        }
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
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

            // Apply bot identity
            updateBotIdentity(data.bot_name, data.bot_avatar);

            // Render history (instant, no delay)
            (data.messages || []).forEach(function (m) {
                appendBubble(m.content, m.direction, true);
            });

            // Initial bot replies with animation
            if ((data.replies || []).length) {
                await renderReplies(data.replies, data.buttons || [], false);
            }

            // Schedule welcome bubble if no history and message configured
            if (!data.messages || data.messages.length === 0) {
                if (data.welcome_message) {
                    // Store for later — show after 3s from page load
                    _pendingWelcome = data.welcome_message;
                }
            }
        } catch (e) {
            console.warn('[Widget] init failed', e);
        }
    }

    // ── Send message ─────────────────────────────────────────────────────────────
    async function sendMessage() {
        var text = inputEl.value.trim();
        if (!text || sending) return;

        // Remove any quick reply buttons
        var btns = document.getElementById('syncro-quick-btns');
        if (btns) btns.remove();

        inputEl.value = '';
        sending = true;
        sendBtn.disabled = true;
        appendBubble(text, 'inbound', true);

        try {
            var res  = await fetch(apiBase + '/api/widget/' + token + '/message', {
                method:  'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body:    JSON.stringify({ visitor_id: visitorId, message: text }),
            });
            var data = await res.json();

            await renderReplies(data.replies || [], data.buttons || [], false);
        } catch (e) {
            console.warn('[Widget] send failed', e);
        }

        sending = false;
        sendBtn.disabled = false;
        inputEl.focus();
    }

    // ── Open / Close ─────────────────────────────────────────────────────────────
    var _initiated = false;
    var _pendingWelcome = null;

    function openChat() {
        isOpen = true;
        panel.classList.add('open');
        dismissWelcome();

        if (!_initiated) {
            _initiated = true;
            initConversation();
        }
        setTimeout(function () { inputEl.focus(); }, 100);
    }

    function closeChat() {
        isOpen = false;
        panel.classList.remove('open');
    }

    // ── Events ───────────────────────────────────────────────────────────────────
    launcher.addEventListener('click', function () {
        if (isOpen) { closeChat(); } else { openChat(); }
    });

    document.getElementById('syncro-close').addEventListener('click', closeChat);

    sendBtn.addEventListener('click', sendMessage);

    inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // ── Welcome bubble delayed fetch ─────────────────────────────────────────────
    // We fetch init data early (after 2.5s) so we have bot_name + welcome_message
    // without waiting for the user to open the chat.
    setTimeout(function () {
        if (_initiated) return; // already opened
        fetch(apiBase + '/api/widget/' + token + '/init?visitor_id=' + visitorId, {
            headers: { 'Accept': 'application/json' }
        }).then(function(res) { return res.json(); }).then(function(data) {
            if (_initiated) return;
            updateBotIdentity(data.bot_name, data.bot_avatar);
            if (data.welcome_message) {
                showWelcomeBubble(data.welcome_message);
            }
            // Pre-store data so initConversation can use it when panel opens
            _prefetchedData = data;
        }).catch(function(){});
    }, 2500);

    var _prefetchedData = null;

    // Override initConversation to use pre-fetched data when available
    var _originalInit = initConversation;
    initConversation = async function() {
        try {
            var data = _prefetchedData;
            if (!data) {
                var res = await fetch(apiBase + '/api/widget/' + token + '/init?visitor_id=' + visitorId, {
                    headers: { 'Accept': 'application/json' }
                });
                data = await res.json();
                if (!res.ok) { console.warn('[Widget] init error', data); return; }
            }

            convId = data.conversation_id;
            updateBotIdentity(data.bot_name, data.bot_avatar);

            (data.messages || []).forEach(function (m) {
                appendBubble(m.content, m.direction, true);
            });

            if ((data.replies || []).length) {
                await renderReplies(data.replies, data.buttons || [], false);
            }
        } catch (e) {
            console.warn('[Widget] init failed', e);
        }
    };
})();
