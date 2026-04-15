<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include("partials._google-analytics")
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('onboarding.result_title') }} — Syncro</title>
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
            opacity: 0;
            transition: opacity .4s ease;
        }
        body.visible { opacity: 1; }

        /* ── Layout split-screen (mesmo padrão do onboarding/index e loading) ── */
        .onb-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Lado esquerdo — conteúdo */
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
            margin-bottom: 24px;
        }

        .onb-brand img {
            height: 34px;
            object-fit: contain;
        }

        .result-content {
            width: 100%;
            max-width: 480px;
        }

        /* ── Badge de sucesso (substitui o Lottie) ── */
        .result-success-badge {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: #DCFCE7;
            color: #059669;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            margin-bottom: 16px;
            opacity: 0; transform: scale(0.6);
            transition: opacity .35s ease, transform .35s cubic-bezier(.34,1.56,.64,1);
        }
        .result-success-badge.show { opacity: 1; transform: scale(1); }

        .result-step-label {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #10B981;
            margin-bottom: 8px;
            opacity: 0; transition: opacity .4s ease;
        }
        .result-step-label.show { opacity: 1; }

        .result-title {
            font-size: 28px; font-weight: 700; color: #111827;
            line-height: 1.25; margin-bottom: 6px;
            opacity: 0; transform: translateY(16px);
            transition: opacity .5s ease, transform .5s ease;
        }
        .result-title.show { opacity: 1; transform: translateY(0); }

        .result-subtitle {
            font-size: 14px; color: #6B7280; margin-bottom: 28px; line-height: 1.55;
            opacity: 0; transition: opacity .5s ease .3s;
        }
        .result-subtitle.show { opacity: 1; }

        /* ── Error banner (AI failed) ── */
        .error-banner {
            background: #FEF2F2;
            border: 1.5px solid #FECACA;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 18px;
            text-align: left;
            opacity: 0; transform: translateY(8px);
            transition: opacity .4s ease, transform .4s ease;
        }
        .error-banner.show { opacity: 1; transform: translateY(0); }
        .error-banner-title {
            font-size: 14px; font-weight: 700; color: #991B1B;
            display: flex; align-items: center; gap: 6px;
            margin-bottom: 4px;
        }
        .error-banner-desc {
            font-size: 12.5px; color: #7F1D1D; line-height: 1.5;
        }
        .error-banner-detail {
            font-size: 11px; color: #991B1B; opacity: 0.7;
            margin-top: 6px; font-family: monospace;
            word-break: break-word;
            background: #fff; padding: 6px 8px; border-radius: 6px;
            max-height: 60px; overflow-y: auto;
        }
        .error-retry-btn {
            margin-top: 10px;
            background: #DC2626; color: #fff;
            border: none; border-radius: 8px;
            padding: 8px 16px; font-size: 12.5px; font-weight: 600;
            cursor: pointer; font-family: 'Inter', sans-serif;
            display: inline-flex; align-items: center; gap: 5px;
            transition: background .15s;
        }
        .error-retry-btn:hover { background: #B91C1C; }
        .error-retry-btn:disabled { opacity: .6; cursor: not-allowed; }

        /* ── Generated list (o que a IA criou) ── */
        .generated-list {
            background: #F0FDF4;
            border: 1.5px solid #BBF7D0;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            text-align: left;
            opacity: 0; transform: translateY(8px);
            transition: opacity .4s ease, transform .4s ease;
        }
        .generated-list.show { opacity: 1; transform: translateY(0); }
        .generated-list-title {
            font-size: 13px; font-weight: 700; color: #064E3B;
            display: flex; align-items: center; gap: 6px;
            margin-bottom: 10px;
        }
        .generated-item {
            font-size: 12.5px; color: #065F46;
            padding: 3px 0;
            display: flex; align-items: center; gap: 6px;
        }
        .generated-item.empty { color: #92400E; }
        .generated-item i { font-size: 14px; flex-shrink: 0; }
        .generated-fallback-note {
            margin-top: 8px; padding-top: 8px;
            border-top: 1px dashed #BBF7D0;
            font-size: 11.5px; color: #065F46; font-style: italic;
        }

        /* ── Checklist ── */
        .checklist-section {
            margin-top: 8px; text-align: left;
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
            font-size: 12.5px; font-weight: 700; color: #3B82F6;
            background: #EFF6FF; padding: 2px 10px; border-radius: 100px;
        }
        .checklist-bar {
            background: #E8EAF0; border-radius: 100px; height: 5px;
            overflow: hidden; margin-bottom: 14px;
        }
        .checklist-bar-fill {
            height: 100%; background: #3B82F6; border-radius: 100px;
            transition: width .8s ease;
        }

        .check-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 12px; border-radius: 10px;
            margin-bottom: 2px;
            opacity: 0; transform: translateY(8px);
            transition: opacity .3s ease, transform .3s ease, background .15s;
        }
        .check-item.show { opacity: 1; transform: translateY(0); }
        .check-item:hover { background: #F9FAFB; }

        .check-icon {
            width: 22px; height: 22px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; flex-shrink: 0;
            transition: all .3s ease;
        }
        .check-icon.done {
            background: #DCFCE7; color: #059669;
            transform: scale(0);
        }
        .check-icon.done.pop { transform: scale(1); }
        .check-icon.pending {
            background: #F3F4F6; color: #D1D5DB;
            border: 1.5px solid #E5E7EB;
        }

        .check-text {
            flex: 1; font-size: 13px; color: #374151; font-weight: 500;
        }
        .check-item.is-done .check-text { color: #6B7280; }

        .check-action {
            font-size: 12px; font-weight: 600; color: #3B82F6;
            text-decoration: none; display: flex; align-items: center; gap: 3px;
            white-space: nowrap; transition: color .15s;
        }
        .check-action:hover { color: #2563EB; }

        /* ── CTA ── */
        .cta-section {
            margin-top: 24px; text-align: left;
            opacity: 0; transition: opacity .4s ease;
        }
        .cta-section.show { opacity: 1; }

        .btn-go-crm {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; max-width: 300px; height: 48px;
            background: #3B82F6; color: #fff;
            border: none; border-radius: 10px;
            font-size: 15px; font-weight: 600;
            cursor: pointer; text-decoration: none;
            font-family: 'Inter', sans-serif;
            transition: background .15s, transform .15s;
        }
        .btn-go-crm:hover { background: #2563EB; transform: translateY(-1px); }

        .cta-hint {
            font-size: 12px; color: #9CA3AF; margin-top: 10px;
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
        @media (max-width: 500px) {
            .result-title { font-size: 24px; }
        }
    </style>
</head>
<body>

<div class="onb-wrapper">

    {{-- Lado esquerdo — conteúdo de resultado --}}
    <div class="onb-left">

        <div class="onb-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Syncro" onerror="this.style.display='none'">
        </div>

        <div class="result-content">

            {{-- Badge de sucesso --}}
            <div class="result-success-badge" id="resultBadge">
                <i class="bi bi-check-lg"></i>
            </div>

            <div class="result-step-label" id="resultStepLabel">{{ __('onboarding.result_badge') }}</div>
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

            {{-- Checklist --}}
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

            {{-- CTA --}}
            <div class="cta-section" id="ctaSection">
                <a href="{{ route('dashboard') }}" class="btn-go-crm">
                    {{ __('onboarding.result_go_crm') }} <i class="bi bi-arrow-right"></i>
                </a>
                <p class="cta-hint">{{ __('onboarding.result_hint') }}</p>
            </div>

        </div>
    </div>

    {{-- Lado direito — imagem --}}
    <div class="onb-right">
        <img src="{{ asset('images/split-screen-onboarding.jpg') }}" alt="Onboarding">
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.body.classList.add('visible');

    setTimeout(() => document.getElementById('resultBadge').classList.add('show'), 100);
    setTimeout(() => document.getElementById('resultStepLabel').classList.add('show'), 250);
    setTimeout(() => document.getElementById('resultTitle').classList.add('show'), 350);
    setTimeout(() => document.getElementById('resultSubtitle').classList.add('show'), 500);

    setTimeout(() => {
        document.getElementById('checklistSection').classList.add('show');
        document.getElementById('checklistBar').style.width = '37.5%'; // 3/8
    }, 800);

    const items = document.querySelectorAll('.check-item');
    items.forEach((item, i) => {
        setTimeout(() => item.classList.add('show'), 900 + (i * 80));
    });

    [
        { id: 'check1', delay: 1000 },
        { id: 'check2', delay: 1200 },
        { id: 'check3', delay: 1400 },
    ].forEach(({ id, delay }) => {
        const el = document.getElementById(id);
        if (el) setTimeout(() => el.classList.add('pop'), delay);
    });

    setTimeout(() => document.getElementById('ctaSection').classList.add('show'), 1800);
});

// Retry button — re-dispatch via /onboarding/retry
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
