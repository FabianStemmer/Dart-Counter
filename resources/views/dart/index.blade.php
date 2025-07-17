@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 1400px;">

    <!-- Header -->
    <div class="header-bar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1em;">
        <div>
            <h1>Sophiensaele Dart Counter</h1>
        </div>
        <div>
            <h2>Wurf eingeben für: <span style="color:blue">{{ $game['players'][$game['current']]['name'] }}</span></h2>
        </div>
    </div>

    <form method="POST" action="{{ route('dart.reset') }}">
        @csrf
        <button type="submit">Spiel zurücksetzen</button>
    </form>

    <div class="dart-flex-wrapper" style="display:flex; gap:2rem; margin-top:1em;">
        <!-- Linke Spalte -->
        <div class="dart-leftcol" style="flex:1;">

            <div id="winner-container" style="margin-bottom:1em;">
                @if ($game['winner'])
                    <h2 class="winner-headline">🎉 {{ $game['winner'] }} hat gewonnen! 🎉</h2>
                    <form method="POST" action="{{ route('dart.newround') }}">
                        @csrf
                        <button type="submit" class="btn btn-success">Neue Runde mit den gleichen Spielern</button>
                    </form>
                @endif
            </div>

            <h2>Punktestände</h2>
            <table style="width:100%; border-collapse: collapse;">
                <thead>
                <tr>
                    <th style="border-bottom:1px solid #ccc; text-align:left;">Name</th>
                    <th style="border-bottom:1px solid #ccc; text-align:right;">Punkte</th>
                    <th style="border-bottom:1px solid #ccc; text-align:right;">Darts</th>
                    <th style="border-bottom:1px solid #ccc; text-align:right;">Ø</th>
                </tr>
                </thead>
                <tbody>
                @foreach($game['players'] as $i => $player)
                    <tr style="@if($i == $game['current'] && !$game['winner']) font-weight:bold; background:#eef; @endif" class="player-row @if($i == $game['current'] && !$game['winner']) active @endif">
                        <td>{{ $player['name'] }}</td>
                        <td style="text-align:right;" id="score-{{ $i }}">{{ $player['score'] }}</td>
                        <td style="text-align:right;">{{ $player['total_darts'] ?? 0 }}</td>
                        <td style="text-align:right;">{{ $player['average'] ?? 0 }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @if (session('error'))
                <div style="color:red; margin-top:1em;">{{ session('error') }}</div>
            @endif
            @if ($game['bust'])
                <div style="color:red; font-weight:bold; font-size:1.5em; margin-top:1em;">{{ $game['bust_message'] }}</div>
            @endif

            <div id="timer" class="timer" style="margin-top: 1em; font-weight: bold; font-size: 1.2em;">Spieldauer: 00:00.00</div>

        </div>

        <!-- Rechte Spalte -->
        <div class="dart-rightcol" style="flex:1;">
            <form id="dart-form" method="POST" action="{{ route('dart.throw') }}" @if($game['winner']) style="display:none;" @endif>
                @csrf
                <input type="hidden" name="final_duration" id="final_duration" value="">
                @for ($i = 0; $i < 3; $i++)
                    <input type="hidden" name="throws[{{ $i }}][points]" id="points{{ $i }}" value="0">
                    <input type="hidden" name="throws[{{ $i }}][multiplier]" id="multiplier{{ $i }}" value="1">
                @endfor

                <div class="dart-board" style="display: grid; grid-template-columns: repeat(6, 70px); gap:12px; margin-bottom:1em;">
                    @for($i = 1; $i <= 20; $i++)
                        <button type="button" class="dart-btn" data-value="{{ $i }}">{{ $i }}</button>
                    @endfor
                    <button type="button" class="dart-btn" data-value="25">🎯</button>
                    <div></div>
                    <button type="button" class="dart-btn miss-btn" data-value="0">Miss</button>
                </div>

                <div style="margin-bottom: 1em;">
                    <button type="button" class="dart-btn multiplier-btn" data-mul="2" style="margin-right: 8px;">Double</button>
                    <button type="button" class="dart-btn multiplier-btn" data-mul="3" style="margin-right: 8px;">Triple</button>
                    <button type="button" class="dart-btn" id="reset-btn">Letzten Wurf zurück</button>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <div>
                        Wurf 1: <span id="wurf0display">-</span> /
                        Wurf 2: <span id="wurf1display">-</span> /
                        Wurf 3: <span id="wurf2display">-</span>
                        <br>
                        <strong>Rundensumme: <span id="roundsum">0</span></strong>
                    </div>

                    <button type="button" id="next-btn" style="display:none;">Weiter</button>
                </div>

            </form>
        </div>
    </div>
</div>

<style>
    .dart-btn {
        height: 70px;
        font-size: 1.7em;
        border-radius: 10px;
        border: 2px solid #222;
        background-color: #f5f5f5;
        cursor: pointer;
        user-select: none;
        transition: background 0.2s;
        color: black;
    }

    .dart-btn:hover {
        background-color: #ddd;
    }

    .dart-btn.spin {
        animation: dart-spin 0.6s ease-in-out;
    }

    @keyframes dart-spin {
        0% { transform: rotate(0deg) scale(1); }
        50% { transform: rotate(360deg) scale(1.1); }
        100% { transform: rotate(720deg) scale(1); }
    }

    .miss-btn {
        grid-column: span 2;
        font-weight: bold;
        background-color: #f88;
    }

    .multiplier-btn.selected {
        background-color: #4caf50;
        color: white;
    }

    .player-row.active {
        font-weight: bold;
        background: #caf0f8;
    }
</style>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const winner = @json($game['winner'] ? true : false);
    const finalDuration = winner ? @json($game['final_duration'] ?? null) : null;

    let initialScore = {{ $game['players'][$game['current']]['score'] }};
    let currentPlayer = {{ $game['current'] }};
    let currentThrow = 0;
    let throwData = [
        {points: 0, multiplier: 1},
        {points: 0, multiplier: 1},
        {points: 0, multiplier: 1}
    ];
    let multiplier = 1;

    function updateDisplay() {
        let sum = 0;
        for(let i = 0; i < 3; i++) {
            let val = throwData[i].points * throwData[i].multiplier;
            document.getElementById('wurf' + i + 'display').textContent =
                (currentThrow > i || throwData[i].points > 0)
                    ? (throwData[i].points + (throwData[i].multiplier > 1 ? 'x' + throwData[i].multiplier : ''))
                    : '-';
            sum += val;
            document.getElementById('points' + i).value = throwData[i].points;
            document.getElementById('multiplier' + i).value = throwData[i].multiplier;
        }

        document.getElementById('roundsum').textContent = sum;

        let newScore = initialScore - sum;
        document.getElementById('score-' + currentPlayer).textContent = newScore;

        const winnerHeadline = document.querySelector('.winner-headline');
        const winContainer = document.getElementById('winner-container');
        if (newScore === 0 && !winnerHeadline) {
            const headline = document.createElement('h2');
            headline.className = 'winner-headline';
            headline.textContent = '🎉 Gewonnen! 🎉';
            winContainer.insertBefore(headline, winContainer.firstChild);
        }
        if (newScore !== 0 && winnerHeadline) {
            winnerHeadline.remove();
        }

        // Weiter Button nur zeigen, wenn kein Gewinner und 3 Würfe eingetragen
        if (!winner && currentThrow === 3) {
            document.getElementById('next-btn').style.display = 'inline-block';
        } else {
            document.getElementById('next-btn').style.display = 'none';
        }
    }

    // Klick Listener für Punkt-Buttons
    document.querySelectorAll('.dart-btn[data-value]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentThrow < 3) {
                btn.classList.add('spin');
                setTimeout(() => btn.classList.remove('spin'), 600);

                throwData[currentThrow].points = parseInt(btn.dataset.value);
                throwData[currentThrow].multiplier = multiplier;
                multiplier = 1;

                document.querySelectorAll('.multiplier-btn').forEach(b => b.classList.remove('selected'));
                currentThrow++;
                updateDisplay();
            }
        });
    });

    // Klick Listener für Multiplier Buttons
    document.querySelectorAll('.multiplier-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            multiplier = parseInt(btn.dataset.mul);
            document.querySelectorAll('.multiplier-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
        });
    });

    // Reset Button klick
    document.getElementById('reset-btn').onclick = () => {
        if (currentThrow > 0) {
            currentThrow--;
            throwData[currentThrow] = { points: 0, multiplier: 1 };
            document.getElementById('next-btn').style.display = 'none';
            updateDisplay();
        }
    };

    // Weiter Button klick (Wert aus Timer holen und absenden)
    document.getElementById('next-btn').onclick = () => {
        let timerText = document.getElementById('timer').textContent.replace('Spieldauer: ', '');
        document.getElementById('final_duration').value = timerText;
        document.getElementById('dart-form').submit();
    };

    updateDisplay();

    const startTime = new Date("{{ \Carbon\Carbon::parse($game['start_time'])->format('Y-m-d\\TH:i:s\\Z') }}");
    const timerDiv = document.getElementById('timer');
    let timerInterval;

    function updateTimer() {
        if (winner && finalDuration) {
            timerDiv.textContent = 'Spieldauer: ' + finalDuration;
            clearInterval(timerInterval);
            return;
        }
        const ms = new Date() - startTime;
        const min = Math.floor(ms / 60000);
        const sec = Math.floor((ms % 60000) / 1000);
        const msec = Math.floor((ms % 1000) / 10);
        timerDiv.textContent = `Spieldauer: ${String(min).padStart(2,'0')}:${String(sec).padStart(2,'0')}.${String(msec).padStart(2,'0')}`;
    }

    timerInterval = setInterval(updateTimer, 10);
    updateTimer();
});
</script>
@endsection

