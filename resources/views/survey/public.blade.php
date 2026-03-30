<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisa de Satisfação</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            margin: 0; min-height: 100vh;
            background: #f4f6fb;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
        }

        .survey-card {
            background: #fff;
            border-radius: 24px;
            border: 1px solid #e8eaf0;
            padding: 48px 36px 36px;
            width: 100%;
            max-width: 460px;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0,0,0,.06);
        }

        .survey-logo { margin-bottom: 28px; }
        .survey-logo img { max-height: 36px; }
        .survey-logo-text { font-size: 16px; font-weight: 700; color: #1a1d23; }

        .survey-greeting {
            font-size: 13px; color: #97A3B7; margin-bottom: 6px;
        }

        .survey-question {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 22px; font-weight: 700; color: #1a1d23;
            margin: 0 0 32px; line-height: 1.35;
        }

        /* ── Emoji faces ─────────────────────────────────────── */
        .faces-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
            padding: 0 8px;
        }

        .face-btn {
            width: 52px; height: 52px;
            border-radius: 50%;
            border: 3px solid transparent;
            background: #f3f4f6;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            cursor: pointer;
            transition: all .3s cubic-bezier(.4,0,.2,1);
            position: relative;
            filter: grayscale(60%) opacity(0.6);
        }

        .face-btn:hover {
            filter: grayscale(0%) opacity(1);
            transform: scale(1.1);
        }

        .face-btn.selected {
            filter: grayscale(0%) opacity(1);
            transform: scale(1.3);
            border-color: var(--face-color);
            background: var(--face-bg);
            box-shadow: 0 0 0 6px var(--face-glow);
        }

        .face-btn:not(.selected).has-selection {
            filter: grayscale(80%) opacity(0.35);
            transform: scale(0.9);
        }

        .face-label {
            font-size: 12px; font-weight: 600;
            color: var(--face-color);
            margin-top: 8px;
            opacity: 0;
            transition: opacity .3s;
            text-align: center;
            height: 18px;
        }
        .face-label.show { opacity: 1; }

        /* ── Score precise (0-10) ────────────────────────────── */
        .score-section {
            max-height: 0; overflow: hidden; opacity: 0;
            transition: max-height .4s cubic-bezier(.4,0,.2,1), opacity .3s, margin .3s;
            margin-top: 0;
        }
        .score-section.show {
            max-height: 200px; opacity: 1; margin-top: 20px;
        }

        .score-hint {
            font-size: 12px; color: #97A3B7; margin-bottom: 10px;
        }

        .score-slider-wrap {
            padding: 0 4px;
        }

        .score-pills {
            display: flex; gap: 6px; justify-content: center;
        }

        .score-pill {
            width: 34px; height: 34px;
            border-radius: 10px;
            border: 2px solid #e8eaf0;
            background: #fff;
            font-size: 13px; font-weight: 700; color: #677489;
            cursor: pointer;
            transition: all .2s;
            display: flex; align-items: center; justify-content: center;
        }
        .score-pill:hover { border-color: #0085f3; color: #0085f3; background: #f0f7ff; }
        .score-pill.selected {
            color: #fff; border-color: var(--pill-color); background: var(--pill-color);
            transform: scale(1.15);
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }

        .score-labels {
            display: flex; justify-content: space-between;
            font-size: 10px; color: #c4c9d2; margin-top: 6px; padding: 0 2px;
        }

        /* ── Comment ─────────────────────────────────────────── */
        .comment-section {
            max-height: 0; overflow: hidden; opacity: 0;
            transition: max-height .4s cubic-bezier(.4,0,.2,1), opacity .3s, margin .3s;
            margin-top: 0;
        }
        .comment-section.show { max-height: 300px; opacity: 1; margin-top: 24px; }

        .comment-label {
            font-size: 13px; font-weight: 600; color: #374151;
            margin-bottom: 8px; text-align: left;
        }
        .comment-input {
            width: 100%; padding: 14px;
            border: 1.5px solid #e8eaf0; border-radius: 12px;
            font-size: 14px; font-family: inherit;
            resize: none; min-height: 80px; outline: none;
            transition: border-color .2s;
        }
        .comment-input:focus { border-color: #0085f3; }

        /* ── Submit ───────────────────────────────────────────── */
        .submit-section {
            max-height: 0; overflow: hidden; opacity: 0;
            transition: max-height .3s, opacity .3s, margin .3s;
            margin-top: 0;
        }
        .submit-section.show { max-height: 80px; opacity: 1; margin-top: 20px; }

        .submit-btn {
            width: 100%; padding: 15px;
            background: #0085f3; color: #fff; border: none;
            border-radius: 14px; font-size: 15px; font-weight: 600;
            cursor: pointer; transition: all .2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .submit-btn:hover { background: #0070d1; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,133,243,.3); }
        .submit-btn:active { transform: translateY(0); }
        .submit-btn:disabled { opacity: .6; cursor: not-allowed; transform: none; box-shadow: none; }

        .powered { margin-top: 28px; font-size: 11px; color: #d1d5db; }
        .powered a { color: #c4c9d2; text-decoration: none; }

        @media (max-width: 480px) {
            .survey-card { padding: 36px 20px 28px; border-radius: 20px; }
            .survey-question { font-size: 19px; }
            .face-btn { width: 44px; height: 44px; font-size: 24px; }
            .faces-row { gap: 8px; }
            .score-pill { width: 30px; height: 30px; font-size: 12px; }
        }

        /* Confetti burst */
        @keyframes confetti-pop {
            0% { transform: scale(0) rotate(0deg); opacity: 1; }
            50% { transform: scale(1.2) rotate(180deg); opacity: .8; }
            100% { transform: scale(0) rotate(360deg); opacity: 0; }
        }
    </style>
</head>
<body>

<div class="survey-card">
    @if($tenant?->logo)
        <div class="survey-logo"><img src="{{ $tenant->logo }}" alt="{{ $tenant->name }}"></div>
    @else
        <div class="survey-logo"><span class="survey-logo-text">{{ $tenant?->name ?? 'Syncro' }}</span></div>
    @endif

    @if($leadName)
        <div class="survey-greeting">Olá, {{ $leadName }}!</div>
    @endif

    <h1 class="survey-question">{{ $survey->question }}</h1>

    <form method="POST" action="{{ route('survey.answer', $response->uuid) }}" id="surveyForm">
        @csrf
        <input type="hidden" name="score" id="scoreInput" value="">

        {{-- Step 1: Emoji faces --}}
        <div class="faces-row" id="facesRow">
            <button type="button" class="face-btn" data-range="0-2" style="--face-color:#EF4444;--face-bg:#fef2f2;--face-glow:rgba(239,68,68,.15);" onclick="selectFace(this,'0-2')">😡</button>
            <button type="button" class="face-btn" data-range="3-4" style="--face-color:#F97316;--face-bg:#fff7ed;--face-glow:rgba(249,115,22,.15);" onclick="selectFace(this,'3-4')">😟</button>
            <button type="button" class="face-btn" data-range="5-6" style="--face-color:#F59E0B;--face-bg:#fffbeb;--face-glow:rgba(245,158,11,.15);" onclick="selectFace(this,'5-6')">😐</button>
            <button type="button" class="face-btn" data-range="7-8" style="--face-color:#10B981;--face-bg:#f0fdf4;--face-glow:rgba(16,185,129,.15);" onclick="selectFace(this,'7-8')">😊</button>
            <button type="button" class="face-btn" data-range="9-10" style="--face-color:#0085f3;--face-bg:#eff6ff;--face-glow:rgba(0,133,243,.15);" onclick="selectFace(this,'9-10')">🤩</button>
        </div>
        <div class="face-label" id="faceLabel" style="--face-color:#97A3B7;"></div>

        {{-- Step 2: Precise score --}}
        <div class="score-section" id="scoreSection">
            <div class="score-hint">Agora, de 0 a 10:</div>
            <div class="score-pills" id="scorePills"></div>
            <div class="score-labels">
                <span>Pior nota</span>
                <span>Melhor nota</span>
            </div>
        </div>

        {{-- Step 3: Comment --}}
        <div class="comment-section" id="commentSection">
            @if($survey->follow_up_question)
                <div class="comment-label">{{ $survey->follow_up_question }}</div>
            @else
                <div class="comment-label">Quer compartilhar mais? (opcional)</div>
            @endif
            <textarea name="comment" class="comment-input" placeholder="Seu comentário..."></textarea>
        </div>

        {{-- Step 4: Submit --}}
        <div class="submit-section" id="submitSection">
            <button type="submit" class="submit-btn" id="submitBtn">
                Enviar resposta
            </button>
        </div>
    </form>

    <div class="powered">Powered by <a href="https://syncro.chat" target="_blank">Syncro</a></div>
</div>

<script>
const FACE_LABELS = {
    '0-2': 'Muito insatisfeito',
    '3-4': 'Insatisfeito',
    '5-6': 'Neutro',
    '7-8': 'Satisfeito',
    '9-10': 'Muito satisfeito',
};

const FACE_RANGES = {
    '0-2': [0,1,2],
    '3-4': [3,4],
    '5-6': [5,6],
    '7-8': [7,8],
    '9-10': [9,10],
};

const PILL_COLORS = {
    0:'#EF4444',1:'#EF4444',2:'#EF4444',
    3:'#F97316',4:'#F97316',
    5:'#F59E0B',6:'#F59E0B',
    7:'#10B981',8:'#10B981',
    9:'#0085f3',10:'#0085f3',
};

let selectedRange = null;
let selectedScore = null;

function selectFace(btn, range) {
    selectedRange = range;

    // Update face states
    document.querySelectorAll('.face-btn').forEach(b => {
        b.classList.remove('selected');
        b.classList.add('has-selection');
    });
    btn.classList.add('selected');
    btn.classList.remove('has-selection');

    // Show label with animation
    const label = document.getElementById('faceLabel');
    label.style.setProperty('--face-color', btn.style.getPropertyValue('--face-color'));
    label.textContent = FACE_LABELS[range];
    label.classList.add('show');

    // Build score pills for this range
    const pills = document.getElementById('scorePills');
    const nums = FACE_RANGES[range];
    pills.innerHTML = nums.map(n =>
        `<button type="button" class="score-pill" data-score="${n}" style="--pill-color:${PILL_COLORS[n]};" onclick="selectPrecise(${n})">${n}</button>`
    ).join('');

    // Show score section
    document.getElementById('scoreSection').classList.add('show');

    // If range has only 2 options, don't auto-select
    // Reset previous precise selection
    selectedScore = null;
    document.getElementById('scoreInput').value = '';
    document.getElementById('commentSection').classList.remove('show');
    document.getElementById('submitSection').classList.remove('show');
}

function selectPrecise(score) {
    selectedScore = score;
    document.getElementById('scoreInput').value = score;

    // Highlight selected pill
    document.querySelectorAll('.score-pill').forEach(p => p.classList.remove('selected'));
    document.querySelector(`.score-pill[data-score="${score}"]`).classList.add('selected');

    // Show comment + submit with animation
    setTimeout(() => {
        document.getElementById('commentSection').classList.add('show');
        document.getElementById('submitSection').classList.add('show');
    }, 150);
}

// Submit handler
document.getElementById('surveyForm').addEventListener('submit', function(e) {
    if (selectedScore === null) { e.preventDefault(); return; }
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10" stroke-linecap="round"/></svg> Enviando...';
});

// Spin animation
const style = document.createElement('style');
style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(style);
</script>

</body>
</html>
