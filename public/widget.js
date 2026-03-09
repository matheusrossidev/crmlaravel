(function () {
    'use strict';

    // ── Config ──────────────────────────────────────────────────────────────────
    // Placeholders — replaced by server when served via /api/widget/{token}.js
    var __INJECTED_TOKEN__ = null;
    var __INJECTED_BASE__  = null;
    var __INJECTED_COLOR__ = null;

    var script   = document.currentScript || (function () {
        var scripts = document.getElementsByTagName('script');
        return scripts[scripts.length - 1];
    })();

    var token        = __INJECTED_TOKEN__ || script.getAttribute('data-token');
    var apiBase      = __INJECTED_BASE__  || script.src.replace(/\/widget\.js(\?[^#]*)?(#.*)?$/, '');
    var colorPrimary = __INJECTED_COLOR__ || script.getAttribute('data-color') || '#0085f3';
    var forceBubble  = script.src.indexOf('force_bubble=1') !== -1;

    if (!token) { console.warn('[Widget] data-token is required'); return; }

    // ── Inject Poppins font ────────────────────────────────────────────────────
    var fontLink = document.createElement('link');
    fontLink.rel = 'stylesheet';
    fontLink.href = 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap';
    document.head.appendChild(fontLink);

    var FONT = "'Poppins', sans-serif";

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

    // ── UTM params (read once at load time) ──────────────────────────────────────
    function getUtmParams() {
        var p = (typeof URLSearchParams !== 'undefined')
            ? new URLSearchParams(window.location.search)
            : { get: function() { return null; } };
        return {
            utm_id:       p.get('utm_id')       || undefined,
            utm_source:   p.get('utm_source')   || undefined,
            utm_medium:   p.get('utm_medium')   || undefined,
            utm_campaign: p.get('utm_campaign') || undefined,
            utm_content:  p.get('utm_content')  || undefined,
            utm_term:     p.get('utm_term')     || undefined,
            page_url:     window.location.href  || undefined,
            referrer_url: document.referrer     || undefined,
        };
    }

    var utmParams = getUtmParams();

    // ── Bot identity (populated from init response) ──────────────────────────────
    var botName   = 'Chat';
    var botAvatar = null;
    var widgetMode = 'bubble'; // 'bubble' or 'inline'

    // ── Inject CSS ───────────────────────────────────────────────────────────────
    var EASE_POP = 'cubic-bezier(0,0.35,0.28,0.9)';
    var style = document.createElement('style');
    style.textContent = [
        /* ── Keyframes ─────────────────────────────────────── */
        '@keyframes syncro-super-pop-in{0%{opacity:0;transform:scale(0);}50%{opacity:1;transform:scale(1.15);}100%{opacity:1;transform:scale(1);}}',
        '@keyframes syncro-slide-in{0%{opacity:0;transform:translateY(80px);}100%{opacity:1;transform:translateY(0);}}',
        '@keyframes syncro-slide-out{0%{opacity:1;transform:translateY(0);}100%{opacity:0;transform:translateY(80px);}}',
        '@keyframes syncro-pop-in{0%{opacity:0;transform:scale(0.8);}100%{opacity:1;transform:scale(1);}}',
        '@keyframes syncro-msg-in{0%{opacity:0;transform:translateY(40px);}100%{opacity:1;transform:translateY(0);}}',
        '@keyframes syncro-blink{0%,100%{opacity:.35;}50%{opacity:1;}}',
        '@keyframes syncro-shake{0%,100%{transform:translateX(0);}20%,60%{transform:translateX(-4px);}40%,80%{transform:translateX(4px);}}',
        '@keyframes syncro-btn-in{0%{opacity:0;transform:scale(0.85) translateY(8px);}100%{opacity:1;transform:scale(1) translateY(0);}}',

        /* ── Launcher (bubble button) ──────────────────────── */
        '#syncro-launcher{position:fixed;bottom:24px;right:20px;width:60px;height:60px;border-radius:50%;background:' + colorPrimary + ';color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 20px rgba(0,0,0,.2);z-index:99998;opacity:0;animation:syncro-super-pop-in .5s ' + EASE_POP + ' 1s forwards;}',
        '#syncro-launcher:hover{box-shadow:0 6px 28px rgba(0,0,0,.32);cursor:pointer;}',
        '#syncro-launcher svg{width:28px;height:28px;fill:currentColor;}',
        '#syncro-launcher img{width:60px;height:60px;border-radius:50%;object-fit:cover;}',
        '#syncro-launcher::after{content:"";position:absolute;bottom:2px;right:2px;width:14px;height:14px;background:#22c55e;border-radius:50%;border:2.5px solid #fff;}',

        /* ── Welcome bubble (invite) ───────────────────────── */
        '#syncro-welcome{position:fixed;bottom:32px;right:94px;max-width:260px;background:#fff;border-radius:12px 12px 4px 12px;box-shadow:0 4px 24px rgba(0,0,0,.12);border:1px solid #e8eaf0;padding:12px 14px 12px 16px;font-size:14px;line-height:1.5;color:#1a1d23;z-index:99997;cursor:pointer;font-family:' + FONT + ';opacity:0;transform:scale(0.8);transition:none;}',
        '#syncro-welcome.visible{opacity:1;transform:scale(1);animation:syncro-pop-in .4s ' + EASE_POP + ';}',
        '#syncro-welcome-close{position:absolute;top:4px;right:6px;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:14px;line-height:1;padding:2px;}',
        '#syncro-welcome-close:hover{color:#6b7280;}',

        /* ── Panel (chat window) — bubble mode ─────────────── */
        '#syncro-panel{position:fixed;bottom:24px;right:24px;width:400px;max-height:560px;border-radius:14px;background:#fff;box-shadow:0 8px 48px rgba(0,0,0,.18);display:none;flex-direction:column;overflow:hidden;z-index:99999;font-family:' + FONT + ';}',
        '#syncro-panel.open{display:flex;animation:syncro-slide-in .3s ease forwards;}',
        '#syncro-panel.syncro-closing{animation:syncro-slide-out .25s ease forwards;}',

        /* ── Panel — inline mode ───────────────────────────── */
        '#syncro-panel.syncro-inline{position:static;bottom:auto;right:auto;width:100%;max-width:100%;height:100vh;max-height:100vh;border-radius:0;box-shadow:none;display:flex;font-family:' + FONT + ';animation:none;}',

        /* ── Header ────────────────────────────────────────── */
        '.syncro-header{background:' + colorPrimary + ';color:#fff;padding:16px 18px;display:flex;align-items:center;gap:12px;flex-shrink:0;min-height:68px;}',
        '.syncro-header-avatar{width:42px;height:42px;border-radius:50% !important;object-fit:cover;border:2.5px solid rgba(255,255,255,0.5);flex-shrink:0;}',
        '.syncro-header-avatar-placeholder{width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;}',
        '.syncro-header-info{flex:1;min-width:0;}',
        '.syncro-header-title{font-size:15px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}',
        '.syncro-header-status{font-size:11.5px;opacity:.9;display:flex;align-items:center;gap:5px;margin-top:1px;}',
        '.syncro-header-status::before{content:"";display:inline-block;width:8px;height:8px;background:#22c55e;border-radius:50%;flex-shrink:0;}',
        '.syncro-header-close{background:rgba(255,255,255,0.12);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .15s;}',
        '.syncro-header-close:hover{background:rgba(255,255,255,0.28);}',

        /* Hide header in inline mode */
        '#syncro-panel.syncro-inline .syncro-header{display:none;}',

        /* ── Messages area ─────────────────────────────────── */
        '.syncro-messages{flex:1;overflow-y:auto;padding:18px 16px 10px;display:flex;flex-direction:column;gap:14px;min-height:200px;scroll-behavior:smooth;-webkit-overflow-scrolling:touch;scrollbar-width:none;-ms-overflow-style:none;}',
        '.syncro-messages::-webkit-scrollbar{display:none;}',

        /* ── Message rows ──────────────────────────────────── */
        '.syncro-msg-row{display:flex;align-items:flex-end;gap:8px;animation:syncro-msg-in .4s ' + EASE_POP + ';}',
        '.syncro-msg-row.out-row{flex-direction:row-reverse;}',
        '.syncro-msg-row.instant{animation:none;}',
        '.syncro-msg-avatar{width:36px;height:36px;border-radius:50% !important;object-fit:cover;flex-shrink:0;}',
        '.syncro-msg-avatar-placeholder{width:36px;height:36px;border-radius:50%;background:' + colorPrimary + '18;display:flex;align-items:center;justify-content:center;flex-shrink:0;}',

        /* ── Bubbles ───────────────────────────────────────── */
        '.syncro-bubble{max-width:78%;padding:14px 20px;border-radius:20px;font-size:14px;line-height:1.55;word-break:break-word;}',
        '.syncro-bubble.in{background:#f0f2f7;color:#1a1d23;border-bottom-left-radius:6px;}',
        '.syncro-bubble.out{background:' + colorPrimary + ';color:#fff;border-bottom-right-radius:6px;}',
        '.syncro-bubble img{max-width:100%;border-radius:8px;display:block;}',
        '.syncro-bubble strong{font-weight:700;}',

        /* ── Typing indicator ──────────────────────────────── */
        '.syncro-typing-row{display:flex;align-items:flex-end;gap:8px;padding:4px 0 6px;animation:syncro-msg-in .3s ' + EASE_POP + ';}',
        '.syncro-typing-bubble{background:#f0f2f7;border-radius:20px 20px 20px 6px;padding:14px 18px;display:flex;gap:5px;align-items:center;}',
        '.syncro-typing-dot{width:10px;height:10px;border-radius:50%;background:#22c55e;opacity:.35;animation:syncro-blink 1s ease infinite;}',
        '.syncro-typing-dot:nth-child(2){animation-delay:.166s;}',
        '.syncro-typing-dot:nth-child(3){animation-delay:.333s;}',

        /* ── Quick reply buttons ───────────────────────────── */
        '.syncro-buttons{display:flex;flex-wrap:wrap;gap:8px;padding:6px 0 8px;}',
        '.syncro-btn{padding:10px 20px;background:#fff;border:1.5px solid ' + colorPrimary + ';border-radius:22px;font-size:13.5px;font-weight:600;color:' + colorPrimary + ';cursor:pointer;transition:all .2s;font-family:inherit;opacity:0;animation:syncro-btn-in .35s ' + EASE_POP + ' forwards;}',
        '.syncro-btn:nth-child(1){animation-delay:.05s;}',
        '.syncro-btn:nth-child(2){animation-delay:.12s;}',
        '.syncro-btn:nth-child(3){animation-delay:.19s;}',
        '.syncro-btn:nth-child(4){animation-delay:.26s;}',
        '.syncro-btn:hover{background:' + colorPrimary + ';color:#fff;box-shadow:0 2px 12px ' + colorPrimary + '40;}',

        /* ── Input row ─────────────────────────────────────── */
        '.syncro-input-row{display:flex;gap:8px;padding:10px 14px;border-top:none;flex-shrink:0;align-items:center;}',
        '.syncro-input{flex:1;padding:11px 16px;border:1.5px solid #e2e6ed !important;border-radius:28px !important;font-size:14px;outline:none;font-family:inherit;transition:border-color .15s,box-shadow .15s;background:#fff;}',
        '.syncro-input:focus{border-color:' + colorPrimary + ' !important;box-shadow:0 0 0 3px ' + colorPrimary + '18;}',
        '.syncro-input.syncro-input-error{border-color:#ef4444 !important;animation:syncro-shake .4s ease-in-out;}',

        /* ── Send button ───────────────────────────────────── */
        '.syncro-send{width:38px;height:38px;min-width:38px;background:' + colorPrimary + ';color:#fff;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:opacity .15s,box-shadow .15s;padding:0;}',
        '.syncro-send:hover{opacity:.9;box-shadow:0 2px 10px ' + colorPrimary + '40;}',
        '.syncro-send:disabled{opacity:.4;cursor:default;box-shadow:none;}',
        '.syncro-send svg{width:17px;height:17px;fill:currentColor;}',

        /* ── Brand bar ────────────────────────────────────── */
        '.syncro-brand{text-align:center;padding:6px 16px;border-top:1px solid #f0f2f7;flex-shrink:0;}',
        '.syncro-brand a{display:inline-flex;align-items:center;gap:4px;text-decoration:none;font-size:11px;color:#9ca3af;transition:color .15s;}',
        '.syncro-brand a:hover{color:#6b7280;}',
        '.syncro-brand img{height:13px;}',

        /* ── Cards ────────────────────────────────────────── */
        '.syncro-cards-row{display:flex;gap:10px;overflow-x:auto;padding:4px 0 8px;scrollbar-width:none;max-width:320px;}',
        '.syncro-cards-row::-webkit-scrollbar{display:none;}',
        '.syncro-card{background:#fff;border:1.5px solid #e8eaf0;border-radius:12px;min-width:200px;max-width:200px;overflow:hidden;flex-shrink:0;}',
        '.syncro-card-img{width:100%;height:130px;object-fit:cover;display:block;}',
        '.syncro-card-body{padding:12px;}',
        '.syncro-card-title{font-size:13.5px;font-weight:700;color:#1a1d23;line-height:1.3;margin-bottom:4px;}',
        '.syncro-card-desc{font-size:12px;color:#6b7280;line-height:1.4;margin-bottom:8px;}',
        '.syncro-card-btn{display:block;width:100%;padding:8px;background:#0085f3;color:#fff;border:none;border-radius:8px;font-size:12.5px;font-weight:600;cursor:pointer;text-align:center;text-decoration:none;font-family:inherit;}',
        '.syncro-card-btn:hover{opacity:.9;}',

        /* ── Mobile responsive ─────────────────────────────── */
        '@media(max-width:480px){#syncro-panel{width:calc(100vw - 16px);right:8px;bottom:8px;max-height:calc(100vh - 16px);border-radius:12px;}}',
    ].join('');
    document.head.appendChild(style);

    // ── Build panel HTML (shared between bubble and inline) ──────────────────────
    function buildPanelHTML() {
        return [
            '<div class="syncro-header" id="syncro-header">',
            '  <div class="syncro-header-avatar-placeholder" id="syncro-hdr-avatar-wrap">',
            '    <svg viewBox="0 0 24 24" style="width:22px;height:22px;fill:rgba(255,255,255,0.85)"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>',
            '  </div>',
            '  <div class="syncro-header-info">',
            '    <div class="syncro-header-title" id="syncro-hdr-name">Chat</div>',
            '    <div class="syncro-header-status">Online agora</div>',
            '  </div>',
            '  <button class="syncro-header-close" id="syncro-close" aria-label="Fechar" style="display:none;">',
            '    <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M19 6.4L17.6 5 12 10.6 6.4 5 5 6.4 10.6 12 5 17.6 6.4 19 12 13.4 17.6 19 19 17.6 13.4 12z"/></svg>',
            '  </button>',
            '</div>',
            '<div class="syncro-messages" id="syncro-msgs"></div>',
            '<div class="syncro-input-row">',
            '  <input class="syncro-input" id="syncro-input" placeholder="Digite uma mensagem..." autocomplete="off">',
            '  <button class="syncro-send" id="syncro-send" aria-label="Enviar">',
            '    <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>',
            '  </button>',
            '</div>',
            '<div class="syncro-brand" id="syncro-brand" style="display:none;">',
            '  <a href="https://syncro.chat" target="_blank" rel="noopener">',
            '    Feito com <img src="' + apiBase + '/images/logo.png" alt="Syncro">',
            '  </a>',
            '</div>',
        ].join('');
    }

    // ── State ────────────────────────────────────────────────────────────────────
    var convId  = null;
    var isOpen  = false;
    var sending = false;
    var welcomeEl = null;
    var panel, launcher;
    var msgsEl, inputEl, sendBtn, hdrName, hdrAvatarWrap;
    var inputType = 'text';
    var openChat, closeChat;

    // ── Helpers ──────────────────────────────────────────────────────────────────
    function sleep(ms) { return new Promise(function(r){ setTimeout(r, ms); }); }

    function phoneMask(e) {
        var v = e.target.value.replace(/\D/g, '').substring(0, 11);
        if (v.length <= 2)       { /* keep */ }
        else if (v.length <= 7)  { v = '(' + v.slice(0,2) + ') ' + v.slice(2); }
        else if (v.length <= 10) { v = '(' + v.slice(0,2) + ') ' + v.slice(2,6) + '-' + v.slice(6); }
        else                     { v = '(' + v.slice(0,2) + ') ' + v.slice(2,7) + '-' + v.slice(7); }
        e.target.value = v;
    }

    function applyInputType(type) {
        inputType = type || 'text';
        if (!inputEl) return;
        // Clear error state
        inputEl.classList.remove('syncro-input-error');
        if (type === 'phone') {
            inputEl.type = 'tel';
            inputEl.placeholder = '(11) 99999-9999';
            inputEl.setAttribute('inputmode', 'numeric');
            if (!inputEl._phoneMaskOn) {
                inputEl._phoneMaskOn = true;
                inputEl.addEventListener('input', phoneMask);
            }
        } else if (type === 'email') {
            inputEl.type = 'email';
            inputEl.placeholder = 'seu@email.com';
            inputEl.removeAttribute('inputmode');
            if (inputEl._phoneMaskOn) {
                inputEl._phoneMaskOn = false;
                inputEl.removeEventListener('input', phoneMask);
            }
        } else {
            inputEl.type = 'text';
            inputEl.placeholder = 'Digite uma mensagem...';
            inputEl.removeAttribute('inputmode');
            if (inputEl._phoneMaskOn) {
                inputEl._phoneMaskOn = false;
                inputEl.removeEventListener('input', phoneMask);
            }
        }
    }

    function validateInput(text) {
        if (inputType === 'phone') {
            var digits = text.replace(/\D/g, '');
            if (digits.length < 10) {
                inputEl.classList.add('syncro-input-error');
                setTimeout(function() { inputEl.classList.remove('syncro-input-error'); }, 600);
                return false;
            }
        }
        if (inputType === 'email') {
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(text)) {
                inputEl.classList.add('syncro-input-error');
                setTimeout(function() { inputEl.classList.remove('syncro-input-error'); }, 600);
                return false;
            }
        }
        return true;
    }

    function typingDelay(reply) {
        if (typeof reply === 'object' && reply !== null && reply.type === 'cards') { return 1500; }
        var text = (typeof reply === 'object' && reply !== null) ? (reply.text || '') : String(reply);
        return Math.min(1000 + text.length * 20, 3000);
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function formatText(s) {
        // Escape HTML first, then convert **bold** to <strong>
        var escaped = escHtml(s);
        return escaped.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    }

    function appendBubble(reply, direction, instant) {
        // Handle cards reply
        if (typeof reply === 'object' && reply !== null && reply.type === 'cards') {
            var row = document.createElement('div');
            row.className = 'syncro-msg-row' + (instant ? ' instant' : '');
            if (botAvatar) {
                var av = document.createElement('img');
                av.src = botAvatar;
                av.className = 'syncro-msg-avatar';
                av.alt = botName;
                row.appendChild(av);
            } else {
                var avPh = document.createElement('div');
                avPh.className = 'syncro-msg-avatar-placeholder';
                avPh.innerHTML = '<svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:' + colorPrimary + '"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>';
                row.appendChild(avPh);
            }
            var wrapper = document.createElement('div');
            wrapper.className = 'syncro-cards-row';
            (reply.cards || []).forEach(function(card) {
                var el = document.createElement('div');
                el.className = 'syncro-card';
                if (card.image_url) {
                    var img = document.createElement('img');
                    img.src = card.image_url;
                    img.className = 'syncro-card-img';
                    img.alt = '';
                    img.onerror = function() { img.style.display = 'none'; };
                    el.appendChild(img);
                }
                var body = document.createElement('div');
                body.className = 'syncro-card-body';
                if (card.title) {
                    var t = document.createElement('div');
                    t.className = 'syncro-card-title';
                    t.textContent = card.title;
                    body.appendChild(t);
                }
                if (card.description) {
                    var d = document.createElement('div');
                    d.className = 'syncro-card-desc';
                    d.textContent = card.description;
                    body.appendChild(d);
                }
                if (card.button_label) {
                    if (card.button_action === 'url' && card.button_url) {
                        var btn = document.createElement('a');
                        btn.className = 'syncro-card-btn';
                        btn.textContent = card.button_label;
                        btn.href = card.button_url;
                        btn.target = '_blank';
                        btn.rel = 'noopener';
                        body.appendChild(btn);
                    } else {
                        var btn = document.createElement('button');
                        btn.className = 'syncro-card-btn';
                        btn.textContent = card.button_label;
                        (function(bv, bl) {
                            btn.addEventListener('click', function() {
                                inputEl.value = bv || bl;
                                sendMessage();
                            });
                        })(card.button_value, card.button_label);
                        body.appendChild(btn);
                    }
                }
                el.appendChild(body);
                wrapper.appendChild(el);
            });
            row.appendChild(wrapper);
            msgsEl.appendChild(row);
            msgsEl.scrollTop = msgsEl.scrollHeight;
            return row;
        }

        var text     = (typeof reply === 'object' && reply !== null) ? (reply.text || '') : String(reply);
        var imageUrl = (typeof reply === 'object' && reply !== null) ? (reply.image_url || '') : '';
        if (imageUrl) imageUrl = resolveUrl(imageUrl) || '';

        var row = document.createElement('div');
        row.className = 'syncro-msg-row ' + (direction === 'inbound' ? 'out-row' : '') + (instant ? ' instant' : '');

        if (direction !== 'inbound' && botAvatar) {
            var av = document.createElement('img');
            av.src = botAvatar;
            av.className = 'syncro-msg-avatar';
            av.alt = botName;
            row.appendChild(av);
        } else if (direction !== 'inbound') {
            var avPh = document.createElement('div');
            avPh.className = 'syncro-msg-avatar-placeholder';
            avPh.innerHTML = '<svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:' + colorPrimary + '"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>';
            row.appendChild(avPh);
        }

        var b = document.createElement('div');
        b.className = 'syncro-bubble ' + (direction === 'inbound' ? 'out' : 'in');

        if (imageUrl) {
            var img = document.createElement('img');
            img.src = imageUrl;
            img.alt = '';
            if (text) img.style.marginBottom = '6px';
            img.onerror = function() { img.style.display = 'none'; };
            b.appendChild(img);
        }

        if (text) {
            var span = document.createElement('span');
            span.innerHTML = formatText(text);
            b.appendChild(span);
        }

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
            avPh.innerHTML = '<svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:' + colorPrimary + '"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>';
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

        if (!buttons || !buttons.length) {
            // Reabilitar input se não há botões
            if (inputEl) {
                inputEl.disabled = false;
                sendBtn.disabled = false;
            }
            return;
        }

        // Desabilitar input quando há botões
        inputEl.disabled = true;
        inputEl.placeholder = 'Selecione uma opção...';
        sendBtn.disabled = true;

        var row = document.createElement('div');
        row.className = 'syncro-buttons';
        row.id = 'syncro-quick-btns';

        buttons.forEach(function(btn) {
            var b = document.createElement('button');
            b.className = 'syncro-btn';
            b.textContent = btn.label;
            b.addEventListener('click', function() {
                row.remove();
                // Reabilitar input
                inputEl.disabled = false;
                sendBtn.disabled = false;
                applyInputType('text');
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
                await sleep(600);
            }
        }
        renderButtons(buttons);
    }

    // Resolve avatar/image paths: relative paths (starting with /) are prefixed with apiBase
    // so they resolve correctly when the widget is embedded on an external site.
    function resolveUrl(path) {
        if (!path) return null;
        if (/^https?:\/\//i.test(path)) return path;
        if (path.charAt(0) === '/') return apiBase + path;
        return path;
    }

    function updateBotIdentity(name, avatar) {
        botName   = name  || 'Chat';
        botAvatar = resolveUrl(avatar);

        if (hdrName) hdrName.textContent = botName;

        if (botAvatar && hdrAvatarWrap) {
            hdrAvatarWrap.innerHTML = '';
            hdrAvatarWrap.className = '';
            var img = document.createElement('img');
            img.src = botAvatar;
            img.className = 'syncro-header-avatar';
            img.alt = botName;
            hdrAvatarWrap.appendChild(img);
        }

        if (launcher && botAvatar) {
            launcher.innerHTML = '<img src="' + botAvatar + '" alt="' + botName + '" style="width:60px;height:60px;border-radius:50%;object-fit:cover;">';
        }
    }

    // ── API fetch helper (POST with UTMs) ────────────────────────────────────────
    function fetchInit() {
        var body = Object.assign({ visitor_id: visitorId }, utmParams);
        return fetch(apiBase + '/api/widget/' + token + '/init', {
            method:  'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body:    JSON.stringify(body),
        });
    }

    // ── Welcome bubble (bubble mode only) ────────────────────────────────────────
    function showWelcomeBubble(message) {
        if (isOpen || welcomeEl) return;

        welcomeEl = document.createElement('div');
        welcomeEl.id = 'syncro-welcome';
        welcomeEl.innerHTML =
            '<button class="syncro-welcome-close" id="syncro-welcome-close" aria-label="Fechar" style="position:absolute;top:4px;right:6px;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:14px;line-height:1;padding:0;">&times;</button>' +
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

    // ── Init conversation data ───────────────────────────────────────────────────
    var _initiated      = false;
    var _prefetchedData = null;

    async function loadConversationData(data) {
        convId = data.conversation_id;
        updateBotIdentity(data.bot_name, data.bot_avatar);

        (data.messages || []).forEach(function (m) {
            appendBubble(m.content, m.direction, true);
        });

        if ((data.replies || []).length) {
            await renderReplies(data.replies, data.buttons || [], false);
        }
        applyInputType(data.input_type || 'text');
    }

    async function initConversation() {
        try {
            var data = _prefetchedData;
            if (!data) {
                var res = await fetchInit();
                data = await res.json();
                if (!res.ok) { console.warn('[Widget] init error', data); return; }
            }
            await loadConversationData(data);
        } catch (e) {
            console.warn('[Widget] init failed', e);
        }
    }

    // ── Send message ─────────────────────────────────────────────────────────────
    async function sendMessage() {
        var text = inputEl.value.trim();
        if (!text || sending) return;

        // Validate phone/email before sending
        if (!validateInput(text)) return;

        var btns = document.getElementById('syncro-quick-btns');
        if (btns) btns.remove();

        // Normalize phone to digits only before sending
        var payload = inputType === 'phone' ? text.replace(/\D/g, '') : text;

        inputEl.value = '';
        applyInputType('text'); // reset mask while waiting
        sending = true;
        sendBtn.disabled = true;
        appendBubble(text, 'inbound', true);

        try {
            var res  = await fetch(apiBase + '/api/widget/' + token + '/message', {
                method:  'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body:    JSON.stringify({ visitor_id: visitorId, message: payload }),
            });
            var data = await res.json();

            await renderReplies(data.replies || [], data.buttons || [], false);
            applyInputType(data.input_type);

            // Handle redirect action
            if (data.redirect_url) {
                var target = data.redirect_target || '_blank';
                setTimeout(function() {
                    window.open(data.redirect_url, target);
                }, 600);
            }
        } catch (e) {
            console.warn('[Widget] send failed', e);
        }

        sending = false;
        sendBtn.disabled = false;
        inputEl.focus();
    }

    // ── Bubble mode setup ────────────────────────────────────────────────────────
    function setupBubbleMode() {
        panel = document.createElement('div');
        panel.id = 'syncro-panel';
        panel.innerHTML = buildPanelHTML();

        launcher = document.createElement('button');
        launcher.id  = 'syncro-launcher';
        launcher.setAttribute('aria-label', 'Abrir chat');
        launcher.innerHTML = '<svg viewBox="0 0 24 24"><path d="M20 2H4C2.9 2 2 2.9 2 4v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>';

        document.body.appendChild(panel);
        document.body.appendChild(launcher);

        msgsEl        = document.getElementById('syncro-msgs');
        inputEl       = document.getElementById('syncro-input');
        sendBtn       = document.getElementById('syncro-send');
        hdrName       = document.getElementById('syncro-hdr-name');
        hdrAvatarWrap = document.getElementById('syncro-hdr-avatar-wrap');

        // Show close button and brand bar in bubble mode
        var closeBtn = document.getElementById('syncro-close');
        if (closeBtn) closeBtn.style.display = '';
        var brandBar = document.getElementById('syncro-brand');
        if (brandBar) brandBar.style.display = '';

        openChat = function() {
            isOpen = true;
            panel.classList.remove('syncro-closing');
            panel.classList.add('open');
            launcher.style.display = 'none';
            dismissWelcome();
            if (!_initiated) {
                _initiated = true;
                initConversation();
            }
            setTimeout(function () { inputEl.focus(); }, 350);
        };

        closeChat = function() {
            isOpen = false;
            panel.classList.add('syncro-closing');
            setTimeout(function() {
                panel.classList.remove('open', 'syncro-closing');
                launcher.style.display = '';
            }, 250);
        };

        launcher.addEventListener('click', function () {
            if (isOpen) { closeChat(); } else { openChat(); }
        });

        document.getElementById('syncro-close').addEventListener('click', closeChat);
        sendBtn.addEventListener('click', sendMessage);
        inputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        });

        // Show welcome bubble after 3s using pre-fetched data (no second request)
        setTimeout(function () {
            if (_initiated || !_prefetchedData) return;
            if (_prefetchedData.welcome_message) {
                showWelcomeBubble(_prefetchedData.welcome_message);
            }
        }, 3000);
    }

    // ── Inline mode setup ────────────────────────────────────────────────────────
    function setupInlineMode() {
        var container = document.getElementById('syncro-chat');
        if (!container) {
            console.warn('[Widget] inline mode: element #syncro-chat not found');
            return;
        }

        // Style the container — full viewport
        container.style.cssText = 'display:flex;flex-direction:column;width:100%;height:100vh;font-family:' + FONT + ';';

        panel = document.createElement('div');
        panel.id = 'syncro-panel';
        panel.className = 'syncro-inline';
        panel.innerHTML = buildPanelHTML();
        panel.style.cssText = 'flex:1;display:flex;flex-direction:column;';

        container.appendChild(panel);

        msgsEl        = document.getElementById('syncro-msgs');
        inputEl       = document.getElementById('syncro-input');
        sendBtn       = document.getElementById('syncro-send');
        hdrName       = document.getElementById('syncro-hdr-name');
        hdrAvatarWrap = document.getElementById('syncro-hdr-avatar-wrap');

        sendBtn.addEventListener('click', sendMessage);
        inputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        });

        // Init immediately
        _initiated = true;
        initConversation();
    }

    // ── Bootstrap: detect widget_type from API then render ───────────────────────
    // We need to know widget_type before rendering (to pick bubble vs inline mode).
    // For bubble: we do lazy init. For inline: we init immediately.
    // We do a lightweight pre-fetch first, then set up the correct mode.

    fetchInit()
        .then(function(res) { return res.json(); })
        .then(function(data) {
            _prefetchedData = data;
            var hasChatContainer = !!document.getElementById('syncro-chat');
            widgetMode = (hasChatContainer || (!forceBubble && data.widget_type === 'inline')) ? 'inline' : 'bubble';

            if (widgetMode === 'inline') {
                setupInlineMode();
            } else {
                setupBubbleMode();
                updateBotIdentity(data.bot_name, data.bot_avatar);
                // welcome bubble is shown by the timer inside setupBubbleMode
            }
        })
        .catch(function(e) {
            console.warn('[Widget] bootstrap fetch failed', e);
            // Fallback to bubble mode without pre-fetched data
            setupBubbleMode();
        });

})();
