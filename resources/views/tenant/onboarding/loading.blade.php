<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
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
            display: flex; align-items: center; justify-content: center;
            background: #f8fafc;
            padding: 24px;
        }

        .loading-card {
            width: 100%; max-width: 640px;
            background: #fff;
            border: 1.5px solid #e8eaf0;
            border-radius: 14px;
            padding: 40px 44px 36px;
        }

        .loading-logo { margin-bottom: 28px; }
        .loading-logo img { height: 30px; }

        .loading-title {
            font-size: 22px; font-weight: 700; color: #1a1d23; margin-bottom: 6px;
        }
        .loading-subtitle {
            font-size: 14px; color: #6b7280; margin-bottom: 28px;
        }

        /* Progress bar */
        .progress-bar-wrap {
            background: #e8eaf0; border-radius: 100px; height: 6px;
            overflow: hidden; margin-bottom: 28px;
        }
        .progress-bar-fill {
            height: 100%; background: #0085f3; border-radius: 100px;
            transition: width .6s ease; width: 0%;
        }

        /* Steps list — items start hidden, appear with animation */
        .step-list {
            text-align: left; margin-bottom: 28px;
            min-height: 48px;
        }

        .step-item {
            display: flex; align-items: center; gap: 14px;
            padding: 13px 16px; border-radius: 10px; margin-bottom: 4px;
            transition: all .35s ease;
            animation: slideIn .35s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .step-item.processing {
            background: #eff6ff; color: #0070d1; font-weight: 600;
            border: 1.5px solid #bfdbfe;
        }
        .step-item.done {
            background: transparent; color: #059669;
            border: 1.5px solid transparent;
        }

        .step-icon {
            width: 30px; height: 30px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; flex-shrink: 0;
        }
        .step-item.processing .step-icon { background: #0085f3; color: #fff; }
        .step-item.done .step-icon { background: #dcfce7; color: #059669; }

        .step-label { font-size: 14px; }

        @keyframes spin { to { transform: rotate(360deg); } }
        .spin { animation: spin 1s linear infinite; display: inline-block; }

        /* Quote block */
        .stat-quote {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 16px 20px;
            background: #f8fafc;
            border-left: 3px solid #0085f3;
            border-radius: 0 10px 10px 0;
            transition: opacity .4s ease;
        }
        .stat-quote-icon {
            font-size: 18px; color: #0085f3; flex-shrink: 0; margin-top: 1px;
        }
        .stat-quote-text {
            font-size: 13.5px; color: #374151; line-height: 1.55; font-style: italic;
        }

        @media (max-width: 500px) {
            .loading-card { padding: 28px 20px 24px; }
        }
    </style>
</head>
<body>
<div class="loading-card">
    <div class="loading-logo">
        <img src="{{ asset('images/logo-dark.png') }}" alt="Syncro" onerror="this.style.display='none'">
    </div>

    <h1 class="loading-title">{{ __('onboarding.loading_title') }}</h1>
    <p class="loading-subtitle">{{ __('onboarding.loading_subtitle') }}</p>

    <div class="progress-bar-wrap">
        <div class="progress-bar-fill" id="progressBar"></div>
    </div>

    <div class="step-list" id="stepList">
        {{-- Steps are added dynamically by JS --}}
    </div>

    <div class="stat-quote" id="statQuote">
        <div class="stat-quote-icon"><i class="bi bi-lightbulb"></i></div>
        <div class="stat-quote-text" id="statQuoteText">{{ __('onboarding.stat_1') }}</div>
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
        // If done, redirect
        if (generateDone) {
            document.getElementById('progressBar').style.width = '100%';
            setTimeout(() => window.location.href = RESULT_URL, 800);
        }
        return;
    }

    const pct = Math.round(((visualStep + 1) / STEP_LABELS.length) * 100);
    document.getElementById('progressBar').style.width = pct + '%';

    // Mark previous step as done
    if (visualStep > 0) {
        const prev = listEl.children[visualStep - 1];
        if (prev) {
            prev.className = 'step-item done';
            prev.querySelector('.step-icon').innerHTML = '<i class="bi bi-check-lg"></i>';
        }
    }

    // Add new step (appears with animation)
    const item = document.createElement('div');
    item.className = 'step-item processing';
    item.innerHTML = `
        <div class="step-icon"><i class="bi bi-arrow-clockwise spin"></i></div>
        <span class="step-label">${STEP_LABELS[visualStep]}</span>
    `;
    listEl.appendChild(item);

    // Scroll into view if list gets long
    item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    // Rotate stat quote
    if (visualStep > 0 && visualStep % 2 === 0 && currentStatIdx < STATS.length - 1) {
        currentStatIdx++;
        const quoteText = document.getElementById('statQuoteText');
        quoteText.style.opacity = '0';
        setTimeout(() => {
            quoteText.textContent = STATS[currentStatIdx];
            quoteText.style.opacity = '1';
        }, 300);
    }

    // If this was the last step and generate is done
    if (generateDone && visualStep === STEP_LABELS.length - 1) {
        setTimeout(() => {
            item.className = 'step-item done';
            item.querySelector('.step-icon').innerHTML = '<i class="bi bi-check-lg"></i>';
            document.getElementById('progressBar').style.width = '100%';
            setTimeout(() => window.location.href = RESULT_URL, 800);
        }, 1500);
    }
}

// Start visual animation — first item immediately, then every 2.5s
advanceVisual();
visualTimer = setInterval(advanceVisual, 2500);

// Fire the POST to generate CRM
const answers = JSON.parse(sessionStorage.getItem('onboarding_answers') || '{}');

if (answers.company_name) {
    const formData = new FormData();
    formData.append('_token', CSRF_TOKEN);
    formData.append('company_name', answers.company_name);
    formData.append('niche', answers.niche || 'outro');
    formData.append('sales_process', answers.sales_process || answers.niche || 'outro');
    formData.append('difficulty', answers.difficulty || 'followup');
    formData.append('team_size', answers.team_size || 'solo');
    (answers.channels || []).forEach(ch => formData.append('channels[]', ch));

    fetch(GENERATE_URL, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
    })
    .then(r => r.json())
    .then(data => {
        generateDone = true;
        sessionStorage.removeItem('onboarding_answers');

        // If visual already past all steps, finish immediately
        if (visualStep >= STEP_LABELS.length - 1) {
            clearInterval(visualTimer);
            // Mark last as done
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
