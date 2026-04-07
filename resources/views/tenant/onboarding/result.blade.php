<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

        /* ── Error banner (when AI failed) ── */
        .error-banner {
            background: #fef2f2;
            border: 1.5px solid #fecaca;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 18px;
            text-align: left;
            opacity: 0; transform: translateY(8px);
            transition: opacity .4s ease, transform .4s ease;
        }
        .error-banner.show { opacity: 1; transform: translateY(0); }
        .error-banner-title {
            font-size: 14px; font-weight: 700; color: #991b1b;
            display: flex; align-items: center; gap: 6px;
            margin-bottom: 4px;
        }
        .error-banner-desc {
            font-size: 12.5px; color: #7f1d1d; line-height: 1.5;
        }
        .error-banner-detail {
            font-size: 11px; color: #991b1b; opacity: 0.7;
            margin-top: 6px; font-family: monospace;
            word-break: break-word;
            background: #fff; padding: 6px 8px; border-radius: 6px;
            max-height: 60px; overflow-y: auto;
        }
        .error-retry-btn {
            margin-top: 10px;
            background: #dc2626; color: #fff;
            border: none; border-radius: 8px;
            padding: 8px 16px; font-size: 12.5px; font-weight: 600;
            cursor: pointer; font-family: 'Inter', sans-serif;
            display: inline-flex; align-items: center; gap: 5px;
            transition: background .15s;
        }
        .error-retry-btn:hover { background: #b91c1c; }
        .error-retry-btn:disabled { opacity: .6; cursor: not-allowed; }

        /* ── Generated counts list ── */
        .generated-list {
            background: #f0fdf4;
            border: 1.5px solid #bbf7d0;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 18px;
            text-align: left;
            opacity: 0; transform: translateY(8px);
            transition: opacity .4s ease, transform .4s ease;
        }
        .generated-list.show { opacity: 1; transform: translateY(0); }
        .generated-list-title {
            font-size: 13px; font-weight: 700; color: #064e3b;
            display: flex; align-items: center; gap: 6px;
            margin-bottom: 8px;
        }
        .generated-item {
            font-size: 12.5px; color: #065f46;
            padding: 3px 0;
            display: flex; align-items: center; gap: 6px;
        }
        .generated-item.empty { color: #92400e; }
        .generated-item i { font-size: 14px; flex-shrink: 0; }
        .generated-fallback-note {
            margin-top: 8px; padding-top: 8px;
            border-top: 1px dashed #bbf7d0;
            font-size: 11.5px; color: #065f46; font-style: italic;
        }

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

    {{-- ── Error banner: aparece quando o status === 'error' ── --}}
    @if(($status ?? null) === 'error')
    <div class="error-banner show" id="errorBanner">
        <div class="error-banner-title">
            <i class="bi bi-exclamation-triangle-fill"></i>
            {{ __('onboarding.ai_failed_title') }}
        </div>
        <div class="error-banner-desc">{{ __('onboarding.ai_failed_desc') }}</div>
        @if(! empty($error))
        <div class="error-banner-detail">{{ __('onboarding.ai_error_label') }} {{ $error }}</div>
        @endif
        <button type="button" class="error-retry-btn" id="retryBtn" onclick="retryGeneration()">
            <i class="bi bi-arrow-clockwise"></i>
            <span id="retryBtnText">{{ __('onboarding.retry_btn') }}</span>
        </button>
    </div>
    @endif

    {{-- ── Generated counts: o que a IA realmente criou ── --}}
    @if(! empty($result))
    <div class="generated-list show" id="generatedList">
        <div class="generated-list-title">
            <i class="bi bi-stars"></i>
            {{ __('onboarding.result_section_title') }}
        </div>

        {{-- Pipeline --}}
        @if(! empty($result['pipeline']) && ($result['pipeline']['stages_count'] ?? 0) > 0)
            @if(! empty($result['pipeline']['template_slug']))
            <div class="generated-item">
                <i class="bi bi-diagram-3-fill"></i>
                {!! __('onboarding.result_template_used', ['name' => e($result['pipeline']['name'])]) !!}
                ({{ $result['pipeline']['stages_count'] }} {{ __('pipelines.stages') ?? 'etapas' }})
            </div>
            @else
            <div class="generated-item">
                <i class="bi bi-check-circle-fill"></i>
                {!! __('onboarding.result_pipeline_created', ['name' => e($result['pipeline']['name']), 'count' => $result['pipeline']['stages_count']]) !!}
            </div>
            @endif
        @endif

        {{-- Sequences --}}
        @php $seqCount = is_array($result['sequences'] ?? null) ? count($result['sequences']) : 0; @endphp
        @if($seqCount > 0)
        <div class="generated-item">
            <i class="bi bi-check-circle-fill"></i>
            {{ trans_choice('onboarding.result_sequences_created', $seqCount, ['count' => $seqCount]) }}
        </div>
        @endif

        {{-- Automations --}}
        @php $autoCount = is_array($result['automations'] ?? null) ? count($result['automations']) : 0; @endphp
        @if($autoCount > 0)
        <div class="generated-item">
            <i class="bi bi-check-circle-fill"></i>
            {{ trans_choice('onboarding.result_automations_created', $autoCount, ['count' => $autoCount]) }}
        </div>
        @endif

        {{-- Scoring rules --}}
        @php $scoringCount = is_array($result['scoring'] ?? null) ? count($result['scoring']) : 0; @endphp
        @if($scoringCount > 0)
        <div class="generated-item">
            <i class="bi bi-check-circle-fill"></i>
            {{ trans_choice('onboarding.result_scoring_created', $scoringCount, ['count' => $scoringCount]) }}
        </div>
        @endif

        {{-- AI agent --}}
        @if(! empty($result['ai_agent']['name']))
        <div class="generated-item">
            <i class="bi bi-check-circle-fill"></i>
            {!! __('onboarding.result_ai_agent_created', ['name' => e($result['ai_agent']['name'])]) !!}
        </div>
        @endif

        {{-- Quick messages --}}
        @php $qmCount = is_array($result['quick_messages'] ?? null) ? count($result['quick_messages']) : 0; @endphp
        @if($qmCount > 0)
        <div class="generated-item">
            <i class="bi bi-check-circle-fill"></i>
            {{ trans_choice('onboarding.result_quick_msgs_created', $qmCount, ['count' => $qmCount]) }}
        </div>
        @endif

        {{-- Tags --}}
        @php $tagsCount = is_array($result['tags'] ?? null) ? count($result['tags']) : 0; @endphp
        @if($tagsCount > 0)
        <div class="generated-item">
            <i class="bi bi-check-circle-fill"></i>
            {{ __('onboarding.result_tags_created', ['count' => $tagsCount]) }}
        </div>
        @endif

        {{-- Loss reasons --}}
        @php $reasonsCount = is_array($result['loss_reasons'] ?? null) ? count($result['loss_reasons']) : 0; @endphp
        @if($reasonsCount > 0)
        <div class="generated-item">
            <i class="bi bi-check-circle-fill"></i>
            {{ __('onboarding.result_reasons_created', ['count' => $reasonsCount]) }}
        </div>
        @endif

        {{-- Fallback note --}}
        @if($fallback ?? false)
        <div class="generated-fallback-note">
            <i class="bi bi-info-circle"></i> {{ __('onboarding.result_fallback_used') }}
        </div>
        @endif
    </div>
    @endif

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

// Retry button handler — re-dispatch o job de geração via /onboarding/retry
async function retryGeneration() {
    const btn  = document.getElementById('retryBtn');
    const text = document.getElementById('retryBtnText');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!btn || !csrf) return;

    btn.disabled = true;
    text.textContent = @json(__('onboarding.retry_btn_loading'));

    try {
        const res = await fetch('{{ route("onboarding.retry") }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
        });
        const data = await res.json();
        if (data.success && data.redirect) {
            window.location.href = data.redirect;
        } else {
            alert((data && data.message) || 'Erro ao tentar novamente.');
            btn.disabled = false;
            text.textContent = @json(__('onboarding.retry_btn'));
        }
    } catch (e) {
        alert('Erro de conexão. Tente novamente.');
        btn.disabled = false;
        text.textContent = @json(__('onboarding.retry_btn'));
    }
}
</script>
</body>
</html>
