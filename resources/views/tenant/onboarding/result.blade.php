<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('onboarding.result_title') }} — Syncro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: #f8fafc;
            padding: 24px;
            opacity: 0;
            transition: opacity .4s ease;
        }
        body.visible { opacity: 1; }

        .result-wrap {
            width: 100%; max-width: 520px; text-align: center;
        }

        /* ── Zona 1: Celebração ── */
        .result-logo { margin-bottom: 16px; }
        .result-logo img { height: 30px; }

        .lottie-container {
            width: 120px; height: 120px; margin: 0 auto 8px;
        }

        .result-title {
            font-size: 28px; font-weight: 600; color: #1a1d23;
            opacity: 0; transform: translateY(16px);
            transition: opacity .5s ease, transform .5s ease;
        }
        .result-title.show { opacity: 1; transform: translateY(0); }

        .result-subtitle {
            font-size: 15px; color: #6b7280; margin-top: 6px;
            opacity: 0; transition: opacity .5s ease .3s;
        }
        .result-subtitle.show { opacity: 1; }

        /* ── Zona 2: Checklist ── */
        .checklist-section {
            margin-top: 36px; text-align: left;
            opacity: 0; transform: translateY(12px);
            transition: opacity .4s ease, transform .4s ease;
        }
        .checklist-section.show { opacity: 1; transform: translateY(0); }

        .checklist-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 14px;
        }
        .checklist-title {
            font-size: 14px; font-weight: 700; color: #1a1d23;
        }
        .checklist-progress {
            font-size: 12.5px; font-weight: 700; color: #0085f3;
            background: #eff6ff; padding: 2px 10px; border-radius: 100px;
        }
        .checklist-bar {
            background: #e8eaf0; border-radius: 100px; height: 5px;
            overflow: hidden; margin-bottom: 18px;
        }
        .checklist-bar-fill {
            height: 100%; background: #0085f3; border-radius: 100px;
            transition: width .8s ease;
        }

        /* Items */
        .check-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 14px; border-radius: 10px;
            margin-bottom: 4px;
            opacity: 0; transform: translateY(8px);
            transition: opacity .3s ease, transform .3s ease, background .15s;
        }
        .check-item.show { opacity: 1; transform: translateY(0); }
        .check-item:hover { background: #f9fafb; }

        .check-icon {
            width: 24px; height: 24px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; flex-shrink: 0;
            transition: all .3s ease;
        }
        .check-icon.done {
            background: #dcfce7; color: #059669;
            transform: scale(0);
        }
        .check-icon.done.pop { transform: scale(1); }
        .check-icon.pending {
            background: #f3f4f6; color: #d1d5db;
            border: 1.5px solid #e5e7eb;
        }

        .check-text {
            flex: 1; font-size: 13.5px; color: #374151; font-weight: 500;
        }
        .check-item.is-done .check-text { color: #6b7280; }

        .check-action {
            font-size: 12.5px; font-weight: 600; color: #0085f3;
            text-decoration: none; display: flex; align-items: center; gap: 3px;
            white-space: nowrap; transition: color .15s;
        }
        .check-action:hover { color: #0070d1; }

        /* ── Zona 3: CTA ── */
        .cta-section {
            margin-top: 32px; text-align: center;
            opacity: 0; transition: opacity .4s ease;
        }
        .cta-section.show { opacity: 1; }

        .btn-go-crm {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            width: 280px; height: 46px;
            background: #0085f3; color: #fff;
            border: none; border-radius: 9px;
            font-size: 15px; font-weight: 600;
            cursor: pointer; text-decoration: none;
            font-family: 'Inter', sans-serif;
            transition: background .15s;
        }
        .btn-go-crm:hover { background: #0070d1; color: #fff; }

        .cta-hint {
            font-size: 12px; color: #9ca3af; margin-top: 10px;
        }

        @media (max-width: 500px) {
            .result-title { font-size: 24px; }
            .btn-go-crm { width: 100%; }
        }
    </style>
</head>
<body>
<div class="result-wrap">

    {{-- Logo --}}
    <div class="result-logo">
        <img src="{{ asset('images/logo.png') }}" alt="Syncro" onerror="this.style.display='none'">
    </div>

    {{-- Zona 1 — Celebração --}}
    <div class="lottie-container" id="lottieContainer"></div>
    <h1 class="result-title" id="resultTitle">{{ __('onboarding.result_title_text') }}</h1>
    <p class="result-subtitle" id="resultSubtitle">{{ __('onboarding.result_subtitle') }}</p>

    {{-- Zona 2 — Checklist --}}
    <div class="checklist-section" id="checklistSection">
        <div class="checklist-header">
            <span class="checklist-title">{{ __('onboarding.checklist_title') }}</span>
            <span class="checklist-progress">3/8</span>
        </div>
        <div class="checklist-bar">
            <div class="checklist-bar-fill" id="checklistBar" style="width:0%;"></div>
        </div>

        {{-- Done items --}}
        <div class="check-item is-done" data-delay="0">
            <div class="check-icon done" id="check1"><i class="bi bi-check-lg"></i></div>
            <span class="check-text">{{ __('onboarding.check_account') }}</span>
        </div>
        <div class="check-item is-done" data-delay="200">
            <div class="check-icon done" id="check2"><i class="bi bi-check-lg"></i></div>
            <span class="check-text">{{ __('onboarding.check_crm_configured') }}</span>
        </div>
        <div class="check-item is-done" data-delay="400">
            <div class="check-icon done" id="check3"><i class="bi bi-check-lg"></i></div>
            <span class="check-text">{{ __('onboarding.check_automations') }}</span>
        </div>

        {{-- Pending items --}}
        <div class="check-item" data-delay="560">
            <div class="check-icon pending"><i class="bi bi-circle"></i></div>
            <span class="check-text">{{ __('onboarding.check_whatsapp') }}</span>
            <a href="{{ route('settings.integrations.index') }}" class="check-action">{{ __('onboarding.action_connect') }} <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="check-item" data-delay="640">
            <div class="check-icon pending"><i class="bi bi-circle"></i></div>
            <span class="check-text">{{ __('onboarding.check_first_lead') }}</span>
            <a href="{{ route('leads.index') }}" class="check-action">{{ __('onboarding.action_add') }} <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="check-item" data-delay="720">
            <div class="check-icon pending"><i class="bi bi-circle"></i></div>
            <span class="check-text">{{ __('onboarding.check_first_message') }}</span>
            <a href="{{ route('chats.index') }}" class="check-action">{{ __('onboarding.action_open_chat') }} <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="check-item" data-delay="800">
            <div class="check-icon pending"><i class="bi bi-circle"></i></div>
            <span class="check-text">{{ __('onboarding.check_invite') }}</span>
            <a href="{{ route('settings.users') }}" class="check-action">{{ __('onboarding.action_invite') }} <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="check-item" data-delay="880">
            <div class="check-icon pending"><i class="bi bi-circle"></i></div>
            <span class="check-text">{{ __('onboarding.check_import') }}</span>
            <a href="{{ route('leads.index') }}" class="check-action">{{ __('onboarding.action_import') }} <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>

    {{-- Zona 3 — CTA --}}
    <div class="cta-section" id="ctaSection">
        <a href="{{ route('dashboard') }}" class="btn-go-crm">
            {{ __('onboarding.result_go_crm') }} <i class="bi bi-arrow-right"></i>
        </a>
        <p class="cta-hint">{{ __('onboarding.result_hint') }}</p>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 0ms — fade in body
    document.body.classList.add('visible');

    // 150ms — title
    setTimeout(() => document.getElementById('resultTitle').classList.add('show'), 150);

    // 300ms — subtitle
    setTimeout(() => document.getElementById('resultSubtitle').classList.add('show'), 300);

    // 500ms — Lottie
    setTimeout(() => {
        lottie.loadAnimation({
            container: document.getElementById('lottieContainer'),
            renderer: 'svg',
            loop: false,
            autoplay: true,
            path: '{{ asset("images/lotties/check-onboarding.json") }}'
        });
    }, 500);

    // 800ms — checklist section
    setTimeout(() => {
        document.getElementById('checklistSection').classList.add('show');
        document.getElementById('checklistBar').style.width = '37.5%'; // 3/8
    }, 800);

    // 900ms+ — cascade items
    const items = document.querySelectorAll('.check-item');
    items.forEach((item, i) => {
        const delay = 900 + (i * 80);
        setTimeout(() => item.classList.add('show'), delay);
    });

    // Animate green checks (done items) — pop effect
    const doneChecks = [
        { el: document.getElementById('check1'), delay: 1000 },
        { el: document.getElementById('check2'), delay: 1200 },
        { el: document.getElementById('check3'), delay: 1400 },
    ];
    doneChecks.forEach(({ el, delay }) => {
        setTimeout(() => el.classList.add('pop'), delay);
    });

    // CTA — after checklist finishes animating
    setTimeout(() => document.getElementById('ctaSection').classList.add('show'), 1800);
});
</script>
</body>
</html>
