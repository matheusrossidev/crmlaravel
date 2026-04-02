@if(!request()->routeIs('whatsapp.*', 'chatbot.flows.edit', 'partner.*'))
@php
    $helpLocale = app()->getLocale();
    $helpUserName = auth()->user()->name ?? 'User';
    $isPortuguese = str_starts_with($helpLocale, 'pt');

    $welcomeTitle = $isPortuguese ? "Oi, {$helpUserName}!" : "Hi, {$helpUserName}!";
    $welcomeText = $isPortuguese
        ? 'Eu sou a Sophia, sua assistente de IA. Posso te ajudar com:'
        : "I'm Sophia, your AI assistant. I can help you with:";
    $welcomeItems = $isPortuguese
        ? ['Navegar pela plataforma', 'Entender funcionalidades', 'Guias passo a passo', 'Dicas e boas praticas']
        : ['Navigate the platform', 'Understand features', 'Step-by-step guides', 'Tips and best practices'];

    $suggestions = $isPortuguese
        ? ['Como criar um lead?', 'Como funciona o pipeline?', 'Como conectar o WhatsApp?', 'Como criar um chatbot?', 'Como configurar um agente de IA?', 'Como rastrear campanhas?']
        : ['How to create a lead?', 'How does the pipeline work?', 'How to connect WhatsApp?', 'How to create a chatbot?', 'How to set up an AI agent?', 'How to track campaigns?'];

    $placeholderText = $isPortuguese ? 'Pergunte a Sophia...' : 'Ask Sophia...';
    $typingText = $isPortuguese ? 'Digitando...' : 'Typing...';
    $aiLabel = 'AI';
    $assistantSubtitle = $isPortuguese ? 'Assistente IA' : 'AI Assistant';
    $errorMsg = $isPortuguese ? 'Desculpe, ocorreu um erro. Tente novamente.' : 'Sorry, an error occurred. Please try again.';

    $thinkingSteps = $isPortuguese
        ? ['Analisando sua pergunta', 'Identificando detalhes importantes', 'Buscando informações relevantes', 'Revisando dados encontrados', 'Gerando resposta']
        : ['Analyzing your request', 'Identifying key details', 'Finding relevant information', 'Reviewing gathered information', 'Generating the response'];
@endphp

<style>
    /* ── Help Bubble ── */
    .shw-bubble {
        position: fixed;
        bottom: 80px;
        right: 24px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        z-index: 200;
        cursor: pointer;
        border: none;
        background: none;
        padding: 0;
        transition: transform .2s ease;
    }
    .shw-bubble:hover { transform: scale(1.05); }
    .shw-bubble-img {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        box-shadow: 0 4px 14px rgba(0,133,243,.35);
    }
    .shw-bubble-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: #0085f3;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 2px;
        line-height: 1;
        box-shadow: 0 2px 6px rgba(0,0,0,.15);
    }
    .shw-bubble-badge span { font-size: 10px; }

    /* Pulse ring */
    .shw-bubble::before {
        content: '';
        position: absolute;
        top: -4px;
        left: -4px;
        width: 68px;
        height: 68px;
        border-radius: 50%;
        border: 2px solid #0085f3;
        animation: shwPulse 2.5s ease-out infinite;
        pointer-events: none;
    }
    @keyframes shwPulse {
        0%   { transform: scale(1); opacity: .6; }
        100% { transform: scale(1.35); opacity: 0; }
    }

    /* ── Overlay ── */
    .shw-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.3);
        z-index: 200;
        opacity: 0;
        visibility: hidden;
        transition: opacity .3s ease, visibility .3s ease;
    }
    .shw-overlay.open {
        opacity: 1;
        visibility: visible;
    }

    /* ── Drawer ── */
    .shw-drawer {
        position: fixed;
        right: 0;
        top: 0;
        height: 100vh;
        width: 30vw;
        min-width: 380px;
        max-width: 480px;
        background: #fff;
        z-index: 201;
        display: flex;
        flex-direction: column;
        box-shadow: -8px 0 32px rgba(0,0,0,.12);
        transform: translateX(100%);
        transition: transform .3s cubic-bezier(.4,0,.2,1);
    }
    .shw-drawer.open {
        transform: translateX(0);
    }

    /* ── Header ── */
    .shw-header {
        padding: 16px 20px;
        background: #0085f3;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }
    .shw-header-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255,255,255,.3);
        flex-shrink: 0;
    }
    .shw-header-info { flex: 1; min-width: 0; }
    .shw-header-title {
        font-size: 15px;
        font-weight: 700;
        line-height: 1.2;
    }
    .shw-header-subtitle {
        font-size: 12px;
        opacity: .85;
        line-height: 1.2;
    }
    .shw-header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .shw-header-btn {
        background: none;
        border: none;
        color: #fff;
        cursor: pointer;
        opacity: .8;
        padding: 4px;
        line-height: 1;
        font-size: 18px;
        transition: opacity .15s;
    }
    .shw-header-btn:hover { opacity: 1; }

    /* ── Messages ── */
    .shw-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    /* Welcome card */
    .shw-welcome {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 16px;
    }
    .shw-welcome-head {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 6px;
    }
    .shw-welcome-head span {
        font-size: 16px;
        font-weight: 700;
        color: #1a1d23;
    }
    .shw-welcome p {
        font-size: 13px;
        color: #374151;
        margin: 0 0 8px;
        line-height: 1.5;
    }
    .shw-welcome ul {
        margin: 0;
        padding-left: 18px;
        list-style: disc;
    }
    .shw-welcome ul li {
        font-size: 13px;
        color: #374151;
        line-height: 1.6;
    }

    /* Quick suggestions */
    .shw-suggestions {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 4px 0 8px;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .shw-suggestions::-webkit-scrollbar { display: none; }
    .shw-suggestion-pill {
        display: inline-flex;
        padding: 8px 14px;
        background: #eff6ff;
        color: #0085f3;
        border: 1px solid #bfdbfe;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        white-space: nowrap;
        flex-shrink: 0;
        transition: background .15s;
    }
    .shw-suggestion-pill:hover { background: #dbeafe; }
    .shw-suggestions-fixed {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 8px 16px;
        border-top: 1px solid #f0f2f7;
        flex-shrink: 0;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .shw-suggestions-fixed::-webkit-scrollbar { display: none; }
    .shw-suggestions-fixed:empty { display: none; }

    /* Bot message row */
    .shw-msg-row-bot {
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }
    .shw-msg-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .shw-msg-bot {
        background: #f1f5f9;
        color: #1a1d23;
        border-radius: 12px 12px 12px 4px;
        padding: 10px 14px;
        max-width: 85%;
        font-size: 13px;
        line-height: 1.6;
    }
    .shw-msg-bot a {
        color: #0085f3;
        text-decoration: underline;
    }
    .shw-msg-bot code {
        background: #e2e8f0;
        padding: 1px 5px;
        border-radius: 4px;
        font-size: 12px;
    }
    .shw-msg-bot ul, .shw-msg-bot ol {
        margin: 4px 0;
        padding-left: 18px;
    }
    .shw-msg-bot li { margin-bottom: 2px; }
    .shw-msg-bot strong { font-weight: 600; }

    .shw-msg-user {
        background: #0085f3;
        color: #fff;
        border-radius: 12px 12px 4px 12px;
        padding: 10px 14px;
        max-width: 85%;
        align-self: flex-end;
        font-size: 13px;
        line-height: 1.6;
    }

    /* Typing indicator */
    .shw-typing {
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }
    .shw-typing-dots {
        background: #f1f5f9;
        border-radius: 12px 12px 12px 4px;
        padding: 12px 18px;
        display: flex;
        gap: 4px;
        align-items: center;
    }
    .shw-typing-dot {
        width: 6px;
        height: 6px;
        background: #9ca3af;
        border-radius: 50%;
        animation: shwBounce 1.4s ease-in-out infinite;
    }
    .shw-typing-dot:nth-child(2) { animation-delay: .2s; }
    .shw-typing-dot:nth-child(3) { animation-delay: .4s; }
    @keyframes shwBounce {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-6px); }
    }

    /* ── Thinking Steps ── */
    .shw-thinking {
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }
    .shw-thinking-body {
        background: #f8fafc;
        border: 1px solid #e8eaf0;
        border-radius: 12px;
        padding: 14px 16px;
        min-width: 220px;
    }
    .shw-thinking-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
    }
    .shw-thinking-header img {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        object-fit: cover;
    }
    .shw-thinking-header span {
        font-size: 13px;
        font-weight: 700;
        color: #1a1d23;
    }
    .shw-step {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 0;
        font-size: 12.5px;
        color: #6b7280;
        opacity: 0;
        transform: translateY(6px);
        transition: opacity .3s ease, transform .3s ease;
    }
    .shw-step.visible {
        opacity: 1;
        transform: translateY(0);
    }
    .shw-step.done { color: #374151; }
    .shw-step-icon {
        width: 16px;
        height: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .shw-step-check {
        color: #10b981;
        font-size: 14px;
    }
    .shw-step-spinner {
        width: 14px;
        height: 14px;
        border: 2px solid #e2e8f0;
        border-top-color: #0085f3;
        border-radius: 50%;
        animation: shwSpin .7s linear infinite;
    }
    .shw-step-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #d1d5db;
    }
    @keyframes shwSpin {
        to { transform: rotate(360deg); }
    }

    /* ── Input Bar ── */
    .shw-input-bar {
        display: flex;
        gap: 8px;
        padding: 12px 16px;
        border-top: 1px solid #e8eaf0;
        flex-shrink: 0;
        background: #fff;
    }
    .shw-input-bar input {
        flex: 1;
        padding: 10px 16px;
        border: 1.5px solid #e2e8f0;
        border-radius: 24px;
        font-size: 13px;
        outline: none;
        color: #1a1d23;
        background: #fff;
        transition: border-color .15s;
    }
    .shw-input-bar input:focus { border-color: #0085f3; }
    .shw-input-bar input::placeholder { color: #9ca3af; }
    .shw-input-bar input:disabled {
        background: #f9fafb;
        color: #9ca3af;
    }
    .shw-send-btn {
        width: 40px;
        height: 40px;
        background: #0085f3;
        color: #fff;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: background .15s;
    }
    .shw-send-btn:hover { background: #0070d1; }
    .shw-send-btn:disabled {
        background: #93c5fd;
        cursor: not-allowed;
    }

    /* ── Mobile ── */
    @media (max-width: 767px) {
        .shw-drawer {
            width: 100vw;
            min-width: unset;
            max-width: unset;
        }
    }
</style>

<!-- Bubble -->
<button class="shw-bubble" id="shwBubble" title="Sophia AI">
    <img src="{{ asset('images/avatars/sophia-ai-agent.jpeg') }}" alt="Sophia" class="shw-bubble-img">
    <div class="shw-bubble-badge">
        <span>&#10024;</span> {{ $aiLabel }}
    </div>
</button>

<!-- Overlay -->
<div class="shw-overlay" id="shwOverlay"></div>

<!-- Drawer -->
<div class="shw-drawer" id="shwDrawer">
    <!-- Header -->
    <div class="shw-header">
        <div style="position:relative;">
            <img src="{{ asset('images/avatars/sophia-ai-agent.jpeg') }}" alt="Sophia" class="shw-header-avatar">
            <div style="position:absolute;bottom:0;right:0;width:10px;height:10px;border-radius:50%;background:#10b981;border:2px solid #0085f3;"></div>
        </div>
        <div class="shw-header-info">
            <div class="shw-header-title">Sophia <span style="font-size:10px;background:rgba(255,255,255,.2);padding:1px 6px;border-radius:8px;font-weight:500;margin-left:4px;">AI</span></div>
            <div class="shw-header-subtitle">{{ $assistantSubtitle }}</div>
        </div>
        <div class="shw-header-actions">
            <button class="shw-header-btn" id="shwClearBtn" title="Clear conversation">
                <i class="bi bi-trash3"></i>
            </button>
            <button class="shw-header-btn" id="shwCloseBtn" title="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <!-- Messages -->
    <div class="shw-messages" id="shwMessages"></div>

    <!-- Quick Suggestions (always visible above input) -->
    <div class="shw-suggestions-fixed" id="shwSuggestionsFixed"></div>

    <!-- Input -->
    <div class="shw-input-bar">
        <input type="text"
               id="shwInput"
               placeholder="{{ $placeholderText }}"
               autocomplete="off">
        <button class="shw-send-btn" id="shwSendBtn">
            <i class="bi bi-send-fill" style="font-size:14px;"></i>
        </button>
    </div>
</div>

<script>
(function() {
    'use strict';

    const STORAGE_KEY = 'syncro_help_messages';
    const CSRF_TOKEN  = '{{ csrf_token() }}';
    const CHAT_URL    = '{{ route("help.chat") }}';
    const SOPHIA_IMG  = '{{ asset("images/avatars/sophia-ai-agent.jpeg") }}';
    const ROBOT_IMG   = '{{ asset("images/avatars/robot.gif") }}';
    const TYPING_TEXT = @json($typingText);
    const ERROR_MSG   = @json($errorMsg);

    const welcomeData = {
        title: @json($welcomeTitle),
        text: @json($welcomeText),
        items: @json($welcomeItems)
    };
    const suggestions = @json($suggestions);

    /* ── State ── */
    let messages = [];
    let isOpen = false;
    let isProcessing = false;
    let welcomeShown = false;

    /* ── Elements ── */
    const bubble   = document.getElementById('shwBubble');
    const overlay  = document.getElementById('shwOverlay');
    const drawer   = document.getElementById('shwDrawer');
    const msgArea  = document.getElementById('shwMessages');
    const input    = document.getElementById('shwInput');
    const sendBtn  = document.getElementById('shwSendBtn');
    const closeBtn = document.getElementById('shwCloseBtn');
    const clearBtn = document.getElementById('shwClearBtn');

    /* ── Init ── */
    function init() {
        loadMessages();
        bubble.addEventListener('click', toggle);
        overlay.addEventListener('click', toggle);
        closeBtn.addEventListener('click', toggle);
        clearBtn.addEventListener('click', clearConversation);
        sendBtn.addEventListener('click', sendMessage);
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    /* ── Toggle ── */
    function toggle() {
        isOpen = !isOpen;
        drawer.classList.toggle('open', isOpen);
        overlay.classList.toggle('open', isOpen);

        if (isOpen) {
            if (!welcomeShown && messages.length === 0) {
                renderWelcome();
                renderSuggestions();
                welcomeShown = true;
            }
            setTimeout(function() { input.focus(); }, 100);
            scrollToBottom();
        }
    }

    /* ── Render from storage ── */
    function loadMessages() {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (stored) {
                messages = JSON.parse(stored);
                if (messages.length > 0) {
                    renderWelcome();
                    renderSuggestions();
                    welcomeShown = true;
                    messages.forEach(function(m) {
                        if (m.role === 'user') appendUserBubble(m.content);
                        else appendBotBubble(m.content);
                    });
                }
            }
        } catch(e) {
            messages = [];
        }
    }

    function saveMessages() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(messages));
        } catch(e) { /* quota exceeded */ }
    }

    /* ── Welcome card ── */
    function renderWelcome() {
        const div = document.createElement('div');
        div.className = 'shw-welcome';
        let itemsHtml = '';
        welcomeData.items.forEach(function(item) {
            itemsHtml += '<li>' + escHtml(item) + '</li>';
        });
        div.innerHTML =
            '<div class="shw-welcome-head">' +
                '<img src="' + ROBOT_IMG + '" style="width:28px;height:28px;border-radius:6px;object-fit:contain;"> <span>' + escHtml(welcomeData.title) + '</span>' +
            '</div>' +
            '<p>' + escHtml(welcomeData.text) + '</p>' +
            '<ul>' + itemsHtml + '</ul>';
        msgArea.appendChild(div);
    }

    /* ── Suggestions (fixed above input) ── */
    function renderSuggestions() {
        const row = document.getElementById('shwSuggestionsFixed');
        if (!row) return;
        row.innerHTML = '';
        suggestions.forEach(function(text) {
            const pill = document.createElement('button');
            pill.className = 'shw-suggestion-pill';
            pill.textContent = text;
            pill.addEventListener('click', function() {
                input.value = text;
                sendMessage();
            });
            row.appendChild(pill);
        });
    }

    /* ── Thinking Steps ── */
    const THINKING_STEPS = {!! json_encode($thinkingSteps) !!};

    function createThinkingBlock() {
        const row = document.createElement('div');
        row.className = 'shw-thinking';
        row.id = 'shwThinkingBlock';

        let stepsHtml = '';
        THINKING_STEPS.forEach(function(label, i) {
            stepsHtml += '<div class="shw-step" data-step="' + i + '">' +
                '<div class="shw-step-icon"><div class="shw-step-dot"></div></div>' +
                '<span>' + escHtml(label) + '</span>' +
            '</div>';
        });

        row.innerHTML =
            '<img src="' + SOPHIA_IMG + '" alt="Sophia" class="shw-msg-avatar">' +
            '<div class="shw-thinking-body">' +
                '<div class="shw-thinking-header">' +
                    '<img src="' + SOPHIA_IMG + '" alt="">' +
                    '<span>Sophia</span>' +
                '</div>' +
                stepsHtml +
            '</div>';

        msgArea.appendChild(row);
        scrollToBottom();
        return row;
    }

    function animateSteps(thinkingBlock, onAllVisible) {
        const steps = thinkingBlock.querySelectorAll('.shw-step');
        let current = 0;

        function showNext() {
            if (current >= steps.length) {
                if (onAllVisible) onAllVisible();
                return;
            }

            const step = steps[current];
            step.classList.add('visible');

            // Previous step: mark as done with check
            if (current > 0) {
                const prev = steps[current - 1];
                prev.classList.add('done');
                prev.querySelector('.shw-step-icon').innerHTML = '<span class="shw-step-check">✓</span>';
            }

            // Current step: show spinner (except waiting steps)
            if (current < steps.length - 1) {
                step.querySelector('.shw-step-icon').innerHTML = '<div class="shw-step-spinner"></div>';
                setTimeout(function() {
                    step.classList.add('done');
                    step.querySelector('.shw-step-icon').innerHTML = '<span class="shw-step-check">✓</span>';
                    current++;
                    showNext();
                }, 1200 + Math.random() * 800);
            } else {
                // Last step: keep spinner until response
                step.querySelector('.shw-step-icon').innerHTML = '<div class="shw-step-spinner"></div>';
                if (onAllVisible) onAllVisible();
            }

            scrollToBottom();
        }

        // Start first step after small delay
        setTimeout(showNext, 600);
    }

    function completeThinking(thinkingBlock) {
        const steps = thinkingBlock.querySelectorAll('.shw-step');
        steps.forEach(function(step) {
            step.classList.add('visible', 'done');
            step.querySelector('.shw-step-icon').innerHTML = '<span class="shw-step-check">✓</span>';
        });
    }

    /* ── Send ── */
    function sendMessage() {
        if (isProcessing) return;
        const text = (input.value || '').trim();
        if (!text) return;

        input.value = '';
        appendUserBubble(text);
        messages.push({ role: 'user', content: text });
        saveMessages();

        setProcessing(true);

        // Show thinking steps instead of simple typing dots
        const thinkingBlock = createThinkingBlock();
        let responseReady = false;
        let responseData = null;

        // Start step animation
        animateSteps(thinkingBlock, function() {
            // All steps visible — if response already arrived, render it
            if (responseReady) {
                renderResponse(thinkingBlock, responseData);
            }
        });

        // Fire API call in parallel
        fetch(CHAT_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({
                message: text,
                history: messages.slice(0, -1),
                page: window.location.pathname
            })
        })
        .then(function(res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(function(data) {
            responseData = data.reply || data.message || ERROR_MSG;
            responseReady = true;
            // If steps already finished animating, render now
            const lastStep = thinkingBlock.querySelector('.shw-step:last-child');
            if (lastStep && lastStep.classList.contains('visible')) {
                renderResponse(thinkingBlock, responseData);
            }
        })
        .catch(function() {
            responseData = ERROR_MSG;
            responseReady = true;
            const lastStep = thinkingBlock.querySelector('.shw-step:last-child');
            if (lastStep && lastStep.classList.contains('visible')) {
                renderResponse(thinkingBlock, responseData);
            }
        });
    }

    function renderResponse(thinkingBlock, reply) {
        // Mark all steps as done
        completeThinking(thinkingBlock);

        // Small pause after last check, then replace with response
        setTimeout(function() {
            thinkingBlock.remove();
            typewriterBotBubble(reply, function() {
                messages.push({ role: 'assistant', content: reply });
                saveMessages();
            });
            setProcessing(false);
        }, 500);
    }

    /* ── Processing state ── */
    function setProcessing(state) {
        isProcessing = state;
        input.disabled = state;
        sendBtn.disabled = state;
        input.placeholder = state ? TYPING_TEXT : '{{ $placeholderText }}';
        if (!state) input.focus();
    }

    /* ── Typing indicator ── */
    function showTyping() {
        const row = document.createElement('div');
        row.className = 'shw-typing';
        row.id = 'shwTypingIndicator';
        row.innerHTML =
            '<img src="' + SOPHIA_IMG + '" alt="Sophia" class="shw-msg-avatar">' +
            '<div class="shw-typing-dots">' +
                '<div class="shw-typing-dot"></div>' +
                '<div class="shw-typing-dot"></div>' +
                '<div class="shw-typing-dot"></div>' +
            '</div>';
        msgArea.appendChild(row);
        scrollToBottom();
    }

    function hideTyping() {
        const el = document.getElementById('shwTypingIndicator');
        if (el) el.remove();
    }

    /* ── Append bubbles ── */
    function appendUserBubble(text) {
        const div = document.createElement('div');
        div.className = 'shw-msg-user';
        div.textContent = text;
        msgArea.appendChild(div);
        scrollToBottom();
    }

    function appendBotBubble(text) {
        const row = document.createElement('div');
        row.className = 'shw-msg-row-bot';
        row.innerHTML =
            '<img src="' + SOPHIA_IMG + '" alt="Sophia" class="shw-msg-avatar">' +
            '<div class="shw-msg-bot">' + parseMd(text) + '</div>';
        msgArea.appendChild(row);
        scrollToBottom();
    }

    function typewriterBotBubble(text, onDone) {
        const row = document.createElement('div');
        row.className = 'shw-msg-row-bot';
        const bubble = document.createElement('div');
        bubble.className = 'shw-msg-bot';
        row.innerHTML = '<img src="' + SOPHIA_IMG + '" alt="Sophia" class="shw-msg-avatar">';
        row.appendChild(bubble);
        msgArea.appendChild(row);

        const finalHtml = parseMd(text);
        const chars = text.split('');
        let idx = 0;
        const speed = Math.max(8, Math.min(25, 1500 / chars.length));

        function typeNext() {
            if (idx < chars.length) {
                idx += Math.ceil(Math.random() * 3);
                if (idx > chars.length) idx = chars.length;
                bubble.innerHTML = parseMd(text.substring(0, idx));
                scrollToBottom();
                setTimeout(typeNext, speed);
            } else {
                bubble.innerHTML = finalHtml;
                scrollToBottom();
                if (onDone) onDone();
            }
        }
        typeNext();
    }

    /* ── Simple markdown parser ── */
    function parseMd(text) {
        let html = escHtml(text);
        // Bold **text**
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        // Inline code `text`
        html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
        // Links [text](url)
        html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
        // Bullet lists (lines starting with - or *)
        html = html.replace(/^[\-\*]\s+(.+)$/gm, '<li>$1</li>');
        html = html.replace(/((?:<li>.*<\/li>\s*)+)/g, '<ul>$1</ul>');
        // Numbered lists
        html = html.replace(/^\d+\.\s+(.+)$/gm, '<li>$1</li>');
        // Line breaks
        html = html.replace(/\n/g, '<br>');
        // Clean up br inside ul
        html = html.replace(/<br><ul>/g, '<ul>');
        html = html.replace(/<\/ul><br>/g, '</ul>');
        html = html.replace(/<br><li>/g, '<li>');
        return html;
    }

    /* ── Escape HTML ── */
    function escHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }

    /* ── Scroll ── */
    function scrollToBottom() {
        requestAnimationFrame(function() {
            msgArea.scrollTop = msgArea.scrollHeight;
        });
    }

    /* ── Clear ── */
    function clearConversation() {
        messages = [];
        welcomeShown = false;
        localStorage.removeItem(STORAGE_KEY);
        msgArea.innerHTML = '';
        renderWelcome();
        renderSuggestions();
        welcomeShown = true;
    }

    /* ── Boot ── */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endif
