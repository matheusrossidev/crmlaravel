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
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #f8fafc;
            padding: 48px 24px;
        }

        .result-container { max-width: 680px; margin: 0 auto; }

        .result-header { text-align: center; margin-bottom: 36px; }
        .result-logo { margin-bottom: 24px; }
        .result-logo img { height: 32px; }
        .result-title { font-size: 28px; font-weight: 700; color: #1a1d23; margin-bottom: 6px; }
        .result-subtitle { font-size: 15px; color: #6b7280; }

        /* Cards grid */
        .result-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
            margin-bottom: 32px;
        }

        .result-card {
            background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px;
            padding: 18px 20px;
        }
        .result-card-header {
            display: flex; align-items: center; gap: 10px; margin-bottom: 10px;
        }
        .result-card-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
        }
        .result-card-icon.pipeline { background: #eff6ff; color: #0085f3; }
        .result-card-icon.sequences { background: #f0fdf4; color: #16a34a; }
        .result-card-icon.automations { background: #fef3c7; color: #d97706; }
        .result-card-icon.scoring { background: #fce7f3; color: #db2777; }
        .result-card-icon.agent { background: #f5f3ff; color: #7c3aed; }
        .result-card-icon.messages { background: #ecfdf5; color: #059669; }
        .result-card-icon.tags { background: #eff6ff; color: #3b82f6; }
        .result-card-icon.reasons { background: #fef2f2; color: #ef4444; }

        .result-card-title { font-size: 13px; font-weight: 700; color: #1a1d23; }
        .result-card-count { font-size: 12px; color: #6b7280; }

        .result-pills {
            display: flex; flex-wrap: wrap; gap: 5px;
        }
        .result-pill {
            padding: 3px 10px; background: #f3f4f6; border-radius: 100px;
            font-size: 11.5px; color: #374151; font-weight: 500;
        }

        .result-status {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; background: #ecfdf5; color: #059669;
            border-radius: 100px; font-size: 11.5px; font-weight: 600;
        }

        /* CTA */
        .result-cta { text-align: center; }
        .btn-go-crm {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 14px 36px; background: #0085f3; color: #fff;
            border: none; border-radius: 9px; font-size: 15px; font-weight: 600;
            cursor: pointer; text-decoration: none; transition: background .15s;
            font-family: 'Inter', sans-serif;
        }
        .btn-go-crm:hover { background: #0070d1; color: #fff; }

        @media (max-width: 600px) {
            .result-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="result-container">

    <div class="result-header">
        <div class="result-logo">
            <img src="{{ asset('images/logo-dark.png') }}" alt="Syncro" onerror="this.style.display='none'">
        </div>
        <h1 class="result-title">{{ __('onboarding.result_title') }}</h1>
        <p class="result-subtitle">{{ __('onboarding.result_subtitle') }}</p>
    </div>

    <div class="result-grid">
        {{-- Pipeline --}}
        @if(!empty($result['pipeline']['stages']))
        <div class="result-card">
            <div class="result-card-header">
                <div class="result-card-icon pipeline"><i class="bi bi-funnel"></i></div>
                <div>
                    <div class="result-card-title">{{ __('onboarding.result_pipeline') }}</div>
                    <div class="result-card-count">{{ $result['pipeline']['stages_count'] ?? 0 }} {{ __('leads.stages') }}</div>
                </div>
            </div>
            <div class="result-pills">
                @foreach(($result['pipeline']['stages'] ?? []) as $stage)
                <span class="result-pill">{{ $stage }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Sequences --}}
        @if(!empty($result['sequences']))
        <div class="result-card">
            <div class="result-card-header">
                <div class="result-card-icon sequences"><i class="bi bi-arrow-repeat"></i></div>
                <div>
                    <div class="result-card-title">{{ __('onboarding.result_sequences') }}</div>
                    <div class="result-card-count">{{ count($result['sequences']) }} {{ __('sequences.nav_title') }}</div>
                </div>
            </div>
            <div class="result-pills">
                @foreach($result['sequences'] as $seq)
                <span class="result-pill">{{ $seq['name'] }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Automations --}}
        @if(!empty($result['automations']))
        <div class="result-card">
            <div class="result-card-header">
                <div class="result-card-icon automations"><i class="bi bi-lightning"></i></div>
                <div>
                    <div class="result-card-title">{{ __('onboarding.result_automations') }}</div>
                    <div class="result-card-count">{{ count($result['automations']) }}</div>
                </div>
            </div>
            <div class="result-pills">
                @foreach($result['automations'] as $auto)
                <span class="result-pill">{{ $auto['name'] }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Scoring --}}
        @if(!empty($result['scoring']))
        <div class="result-card">
            <div class="result-card-header">
                <div class="result-card-icon scoring"><i class="bi bi-speedometer2"></i></div>
                <div>
                    <div class="result-card-title">{{ __('onboarding.result_scoring') }}</div>
                    <div class="result-card-count">{{ count($result['scoring']) }}</div>
                </div>
            </div>
            <div class="result-pills">
                @foreach(array_slice($result['scoring'], 0, 4) as $rule)
                <span class="result-pill">{{ $rule['name'] }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- AI Agent --}}
        @if(!empty($result['ai_agent']['name']))
        <div class="result-card">
            <div class="result-card-header">
                <div class="result-card-icon agent"><i class="bi bi-robot"></i></div>
                <div>
                    <div class="result-card-title">{{ __('onboarding.result_ai_agent') }}</div>
                    <div class="result-card-count">{{ $result['ai_agent']['name'] }}</div>
                </div>
            </div>
            <span class="result-status"><i class="bi bi-check-circle-fill"></i> {{ __('onboarding.result_ready') }}</span>
        </div>
        @endif

        {{-- Quick Messages --}}
        @if(!empty($result['quick_messages']))
        <div class="result-card">
            <div class="result-card-header">
                <div class="result-card-icon messages"><i class="bi bi-chat-dots"></i></div>
                <div>
                    <div class="result-card-title">{{ __('onboarding.result_quick_messages') }}</div>
                    <div class="result-card-count">{{ count($result['quick_messages']) }}</div>
                </div>
            </div>
            <div class="result-pills">
                @foreach(array_slice($result['quick_messages'], 0, 4) as $msg)
                <span class="result-pill">{{ $msg['title'] }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Tags --}}
        @if(!empty($result['tags']))
        <div class="result-card">
            <div class="result-card-header">
                <div class="result-card-icon tags"><i class="bi bi-tags"></i></div>
                <div>
                    <div class="result-card-title">{{ __('onboarding.result_tags') }}</div>
                    <div class="result-card-count">{{ count($result['tags']) }}</div>
                </div>
            </div>
            <div class="result-pills">
                @foreach(array_slice($result['tags'], 0, 6) as $tag)
                <span class="result-pill">{{ $tag }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Loss Reasons --}}
        @if(!empty($result['loss_reasons']))
        <div class="result-card">
            <div class="result-card-header">
                <div class="result-card-icon reasons"><i class="bi bi-x-circle"></i></div>
                <div>
                    <div class="result-card-title">{{ __('onboarding.result_loss_reasons') }}</div>
                    <div class="result-card-count">{{ count($result['loss_reasons']) }}</div>
                </div>
            </div>
            <div class="result-pills">
                @foreach(array_slice($result['loss_reasons'], 0, 4) as $reason)
                <span class="result-pill">{{ $reason }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="result-cta">
        <a href="{{ route('dashboard') }}" class="btn-go-crm">
            {{ __('onboarding.result_go_crm') }} <i class="bi bi-arrow-right"></i>
        </a>
    </div>

</div>
</body>
</html>
