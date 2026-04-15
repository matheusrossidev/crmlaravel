<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include("partials._google-analytics")
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('onboarding.loading_title') }} — Syncro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            background: #fff;
        }

        /* ── Layout split-screen (mesmo padrão do onboarding/index) ── */
        .onb-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Lado esquerdo — loading */
        .onb-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 64px;
            min-width: 0;
            overflow-y: auto;
            position: relative;
        }

        .onb-brand {
            width: 100%;
            max-width: 480px;
            margin-bottom: 36px;
        }

        .onb-brand img {
            height: 34px;
            object-fit: contain;
        }

        .loading-content {
            width: 100%;
            max-width: 480px;
        }

        .loading-step-label {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #3B82F6;
            margin-bottom: 8px;
        }

        .loading-title {
            font-size: 26px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 6px;
            line-height: 1.25;
        }

        .loading-subtitle {
            font-size: 14px;
            color: #6B7280;
            margin-bottom: 28px;
        }

        /* Progress bar */
        .progress-bar-wrap {
            background: #e8eaf0;
            border-radius: 100px;
            height: 6px;
            overflow: hidden;
            margin-bottom: 28px;
        }
        .progress-bar-fill {
            height: 100%;
            background: #3B82F6;
            border-radius: 100px;
            transition: width .6s ease;
            width: 0%;
        }

        /* Steps list */
        .step-list {
            text-align: left;
            margin-bottom: 28px;
            min-height: 48px;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 4px;
            transition: all .35s ease;
            animation: slideIn .35s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .step-item.processing {
            background: #EFF6FF;
            color: #1d4ed8;
            font-weight: 600;
            border: 1.5px solid #BFDBFE;
        }
        .step-item.done {
            background: transparent;
            color: #059669;
            border: 1.5px solid transparent;
        }

        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .step-item.processing .step-icon { background: #3B82F6; color: #fff; }
        .step-item.done .step-icon { background: #dcfce7; color: #059669; }

        .step-label { font-size: 14px; }

        @keyframes spin { to { transform: rotate(360deg); } }
        .spin { animation: spin 1s linear infinite; display: inline-block; }

        /* Quote block */
        .stat-quote {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 16px 20px;
            background: #f8fafc;
            border-left: 3px solid #3B82F6;
            border-radius: 0 10px 10px 0;
            transition: opacity .4s ease;
        }
        .stat-quote-icon {
            font-size: 18px; color: #3B82F6; flex-shrink: 0; margin-top: 1px;
        }
        .stat-quote-text {
            font-size: 13.5px; color: #374151; line-height: 1.55; font-style: italic;
        }

        /* ── Lado direito — imagem ── */
        .onb-right {
            flex: 1;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow: hidden;
        }
        .onb-right img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @media (max-width: 900px) {
            .onb-wrapper { flex-direction: column; }
            .onb-right { display: none; }
            .onb-left { padding: 32px 24px; }
        }
    </style>
</head>
<body>

<div class="onb-wrapper">

    {{-- Lado esquerdo — conteúdo de loading --}}
    <div class="onb-left">

        <div class="onb-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Syncro" onerror="this.style.display='none'">
        </div>

        <div class="loading-content">

            <div class="loading-step-label">{{ __('onboarding.loading_badge') }}</div>
            <h1 class="loading-title">{{ __('onboarding.loading_title') }}</h1>
            <p class="loading-subtitle">{{ __('onboarding.loading_subtitle') }}</p>

            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" id="progressBar"></div>
            </div>

            <div class="step-list" id="stepList">
                {{-- Steps adicionados dinamicamente pelo JS --}}
            </div>

            <div class="stat-quote" id="statQuote">
                <div class="stat-quote-icon"><i class="bi bi-lightbulb"></i></div>
                <div class="stat-quote-text" id="statQuoteText">{{ __('onboarding.stat_1') }}</div>
            </div>

        </div>
    </div>

    {{-- Lado direito — imagem --}}
    <div class="onb-right">
        <img src="{{ asset('images/split-screen-onboarding.jpg') }}" alt="Onboarding">
    </div>

</div>

<script>
const GENERATE_URL = '{{ route("onboarding.generate") }}';
const RESULT_URL   = '{{ route("onboarding.result") }}';
const CSRF_TOKEN   = '{{ csrf_token() }}';

const STEP_LABELS = [
    @json(__('onboarding.loading_step_1')),
    @json(__('onboarding.loading_step_2')),
    @json(__('onboarding.loading_step_3')),
    @json(__('onboarding.loading_step_4')),
    @json(__('onboarding.loading_step_5')),
    @json(__('onboarding.loading_step_6')),
    @json(__('onboarding.loading_step_7')),
    @json(__('onboarding.loading_step_8')),
];

const STATS = [
    @json(__('onboarding.stat_1')),
    @json(__('onboarding.stat_2')),
    @json(__('onboarding.stat_3')),
    @json(__('onboarding.stat_4')),
];

let currentStatIdx = 0;
let visualStep = -1;
let visualTimer = null;
let generateDone = false;
const listEl = document.getElementById('stepList');

function advanceVisual() {
    visualStep++;

    if (visualStep >= STEP_LABELS.length) {
        clearInterval(visualTimer);
        if (generateDone) {
            document.getElementById('progressBar').style.width = '100%';
            setTimeout(() => window.location.href = RESULT_URL, 800);
        }
        return;
    }

    const pct = Math.round(((visualStep + 1) / STEP_LABELS.length) * 100);
    document.getElementById('progressBar').style.width = pct + '%';

    if (visualStep > 0) {
        const prev = listEl.children[visualStep - 1];
        if (prev) {
            prev.className = 'step-item done';
            prev.querySelector('.step-icon').innerHTML = '<i class="bi bi-check-lg"></i>';
        }
    }

    const item = document.createElement('div');
    item.className = 'step-item processing';
    item.innerHTML = `
        <div class="step-icon"><i class="bi bi-arrow-clockwise spin"></i></div>
        <span class="step-label">${STEP_LABELS[visualStep]}</span>
    `;
    listEl.appendChild(item);
    item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    if (visualStep > 0 && visualStep % 2 === 0 && currentStatIdx < STATS.length - 1) {
        currentStatIdx++;
        const quoteText = document.getElementById('statQuoteText');
        quoteText.style.opacity = '0';
        setTimeout(() => {
            quoteText.textContent = STATS[currentStatIdx];
            quoteText.style.opacity = '1';
        }, 300);
    }

    if (generateDone && visualStep === STEP_LABELS.length - 1) {
        setTimeout(() => {
            item.className = 'step-item done';
            item.querySelector('.step-icon').innerHTML = '<i class="bi bi-check-lg"></i>';
            document.getElementById('progressBar').style.width = '100%';
            setTimeout(() => window.location.href = RESULT_URL, 800);
        }, 1500);
    }
}

advanceVisual();
visualTimer = setInterval(advanceVisual, 2500);

// ── Dispara POST pra gerar CRM ──
const answers = JSON.parse(sessionStorage.getItem('onboarding_answers') || '{}');

if (answers.company_name) {
    const formData = new FormData();
    formData.append('_token', CSRF_TOKEN);
    formData.append('company_name', answers.company_name);
    formData.append('niche', answers.niche || 'outro');
    if (answers.sales_process) {
        formData.append('sales_process', answers.sales_process);
    }
    formData.append('difficulty', answers.difficulty || 'followup');
    formData.append('team_size', answers.team_size || 'solo');
    (answers.channels || []).forEach(ch => formData.append('channels[]', ch));
    if (answers.pipeline_template_slug) {
        formData.append('pipeline_template_slug', answers.pipeline_template_slug);
    }

    fetch(GENERATE_URL, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
    })
    .then(r => r.json())
    .then(data => {
        generateDone = true;
        sessionStorage.removeItem('onboarding_answers');

        if (visualStep >= STEP_LABELS.length - 1) {
            clearInterval(visualTimer);
            const last = listEl.lastElementChild;
            if (last) {
                last.className = 'step-item done';
                last.querySelector('.step-icon').innerHTML = '<i class="bi bi-check-lg"></i>';
            }
            document.getElementById('progressBar').style.width = '100%';
            setTimeout(() => window.location.href = RESULT_URL, 800);
        }
    })
    .catch(err => {
        console.error('Generate failed:', err);
        generateDone = true;
    });
} else {
    window.location.href = RESULT_URL;
}
</script>
</body>
</html>
