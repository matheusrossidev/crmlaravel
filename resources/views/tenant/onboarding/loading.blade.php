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
        }

        .loading-container {
            width: 100%; max-width: 520px; padding: 32px; text-align: center;
        }

        .loading-logo { margin-bottom: 32px; }
        .loading-logo img { height: 32px; }

        .loading-title {
            font-size: 22px; font-weight: 700; color: #1a1d23; margin-bottom: 6px;
        }
        .loading-subtitle {
            font-size: 14px; color: #6b7280; margin-bottom: 36px;
        }

        /* Progress bar */
        .progress-bar-wrap {
            background: #e8eaf0; border-radius: 100px; height: 8px;
            overflow: hidden; margin-bottom: 32px;
        }
        .progress-bar-fill {
            height: 100%; background: #0085f3; border-radius: 100px;
            transition: width .5s ease; width: 0%;
        }

        /* Steps list */
        .step-list { text-align: left; margin-bottom: 32px; }

        .step-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-radius: 10px; margin-bottom: 6px;
            transition: all .3s;
        }
        .step-item.pending { color: #9ca3af; }
        .step-item.processing {
            background: #eff6ff; color: #0085f3; font-weight: 600;
        }
        .step-item.done {
            background: #f0fdf4; color: #059669;
        }

        .step-icon {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; flex-shrink: 0;
        }
        .step-item.pending .step-icon { background: #f3f4f6; color: #9ca3af; }
        .step-item.processing .step-icon { background: #0085f3; color: #fff; }
        .step-item.done .step-icon { background: #059669; color: #fff; }

        .step-label { font-size: 13.5px; }

        @keyframes spin { to { transform: rotate(360deg); } }
        .spin { animation: spin 1s linear infinite; }

        /* Stat quote */
        .stat-quote {
            font-size: 13px; color: #9ca3af; font-style: italic;
            padding: 16px; background: #fff; border: 1px solid #e8eaf0;
            border-radius: 12px; line-height: 1.5;
            transition: opacity .3s;
        }
    </style>
</head>
<body>
<div class="loading-container">
    <div class="loading-logo">
        <img src="{{ asset('images/logo-dark.png') }}" alt="Syncro" onerror="this.style.display='none'">
    </div>

    <h1 class="loading-title">{{ __('onboarding.loading_title') }}</h1>
    <p class="loading-subtitle">{{ __('onboarding.loading_subtitle') }}</p>

    <div class="progress-bar-wrap">
        <div class="progress-bar-fill" id="progressBar"></div>
    </div>

    <div class="step-list" id="stepList">
        <div class="step-item pending" data-step="analyzing">
            <div class="step-icon"><i class="bi bi-circle"></i></div>
            <span class="step-label">{{ __('onboarding.loading_step_1') }}</span>
        </div>
        <div class="step-item pending" data-step="pipeline">
            <div class="step-icon"><i class="bi bi-circle"></i></div>
            <span class="step-label">{{ __('onboarding.loading_step_2') }}</span>
        </div>
        <div class="step-item pending" data-step="sequences">
            <div class="step-icon"><i class="bi bi-circle"></i></div>
            <span class="step-label">{{ __('onboarding.loading_step_3') }}</span>
        </div>
        <div class="step-item pending" data-step="automations">
            <div class="step-icon"><i class="bi bi-circle"></i></div>
            <span class="step-label">{{ __('onboarding.loading_step_4') }}</span>
        </div>
        <div class="step-item pending" data-step="scoring">
            <div class="step-icon"><i class="bi bi-circle"></i></div>
            <span class="step-label">{{ __('onboarding.loading_step_5') }}</span>
        </div>
        <div class="step-item pending" data-step="ai_agent">
            <div class="step-icon"><i class="bi bi-circle"></i></div>
            <span class="step-label">{{ __('onboarding.loading_step_6') }}</span>
        </div>
        <div class="step-item pending" data-step="quick_messages">
            <div class="step-icon"><i class="bi bi-circle"></i></div>
            <span class="step-label">{{ __('onboarding.loading_step_7') }}</span>
        </div>
        <div class="step-item pending" data-step="config">
            <div class="step-icon"><i class="bi bi-circle"></i></div>
            <span class="step-label">{{ __('onboarding.loading_step_8') }}</span>
        </div>
    </div>

    <div class="stat-quote" id="statQuote">{{ __('onboarding.stat_1') }}</div>
</div>

<script>
const GENERATE_URL = '{{ route("onboarding.generate") }}';
const RESULT_URL   = '{{ route("onboarding.result") }}';
const CSRF_TOKEN   = '{{ csrf_token() }}';
const STEPS_ORDER  = ['analyzing', 'pipeline', 'sequences', 'automations', 'scoring', 'ai_agent', 'quick_messages', 'config'];
const STATS = [
    @json(__('onboarding.stat_1')),
    @json(__('onboarding.stat_2')),
    @json(__('onboarding.stat_3')),
    @json(__('onboarding.stat_4')),
];

let currentStatIdx = 0;
let visualStep = 0;
let visualTimer = null;
let generateDone = false;

function advanceVisual() {
    if (visualStep >= STEPS_ORDER.length) {
        clearInterval(visualTimer);
        return;
    }

    const pct = Math.round(((visualStep + 1) / STEPS_ORDER.length) * 100);
    document.getElementById('progressBar').style.width = pct + '%';

    STEPS_ORDER.forEach((stepName, i) => {
        const el = document.querySelector(`[data-step="${stepName}"]`);
        if (!el) return;

        if (i < visualStep) {
            el.className = 'step-item done';
            el.querySelector('.step-icon').innerHTML = '<i class="bi bi-check-lg"></i>';
        } else if (i === visualStep) {
            el.className = 'step-item processing';
            el.querySelector('.step-icon').innerHTML = '<i class="bi bi-arrow-clockwise spin"></i>';
        } else {
            el.className = 'step-item pending';
            el.querySelector('.step-icon').innerHTML = '<i class="bi bi-circle"></i>';
        }
    });

    // Rotate stat
    if (visualStep > 0 && visualStep % 2 === 0 && currentStatIdx < STATS.length - 1) {
        currentStatIdx++;
        document.getElementById('statQuote').textContent = STATS[currentStatIdx];
    }

    visualStep++;

    // If generate is done and we've shown all steps, redirect
    if (generateDone && visualStep >= STEPS_ORDER.length) {
        clearInterval(visualTimer);
        document.getElementById('progressBar').style.width = '100%';
        // Mark all as done
        STEPS_ORDER.forEach(s => {
            const el = document.querySelector(`[data-step="${s}"]`);
            if (el) {
                el.className = 'step-item done';
                el.querySelector('.step-icon').innerHTML = '<i class="bi bi-check-lg"></i>';
            }
        });
        setTimeout(() => window.location.href = RESULT_URL, 800);
    }
}

// Start visual animation (advance every 2.5s)
visualTimer = setInterval(advanceVisual, 2500);
advanceVisual();

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

        // If visual is already past all steps, redirect immediately
        if (visualStep >= STEPS_ORDER.length) {
            document.getElementById('progressBar').style.width = '100%';
            STEPS_ORDER.forEach(s => {
                const el = document.querySelector(`[data-step="${s}"]`);
                if (el) {
                    el.className = 'step-item done';
                    el.querySelector('.step-icon').innerHTML = '<i class="bi bi-check-lg"></i>';
                }
            });
            setTimeout(() => window.location.href = RESULT_URL, 800);
        }
        // Otherwise, visualTimer will handle the redirect when it catches up
    })
    .catch(err => {
        console.error('Generate failed:', err);
        generateDone = true;
        // Still redirect — onboarding marked as done on server even on error
    });
} else {
    // No answers in session — redirect to result or dashboard
    window.location.href = RESULT_URL;
}
</script>
</body>
</html>
