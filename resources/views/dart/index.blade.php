@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 1400px;">

    <!-- Neuer Header-Block -->
    <div class="header-bar">
        <h1>Sophiensaele Dart Counter</h1>
        <h2>
            Wurf eingeben für: <span style="color:blue">{{ $game['players'][$game['current']]['name'] }}</span>
        </h2>
    </div>

    <form method="POST" action="{{ route('dart.reset') }}">
        @csrf
        <button>Spiel zurücksetzen</button>
    </form>

    <div class="dart-flex-wrapper">
        <!-- Linke Spalte -->
        <div class="dart-leftcol">
            <div id="winner-container">
                @if ($game['winner'])
                    <h2 class="winner-headline">🎉 {{ $game['winner'] }} hat gewonnen! 🎉</h2>
                    <form method="POST" action="{{ route('dart.newround') }}">
                        @csrf
                        <button class="btn btn-success">Neue Runde mit den gleichen Spielern</button>
                    </form>
                @endif
            </div>

            <h2>Punktestände</h2>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Punkte</th>
                    <th>Darts</th>
                    <th>Ø</th>
                </tr>
                @foreach($game['players'] as $i => $player)
                <tr class="player-row @if($i == $game['current'] && !$game['winner']) active @endif">
                    <td>{{ $player['name'] }}</td>
                    <td id="score-{{ $i }}">{{ $player['score'] }}</td>
                    <td>{{ $player['total_darts'] ?? 0 }}</td>
                    <td>{{ $player['average'] ?? 0 }}</td>
                </tr>
                @endforeach
            </table>

            @if (session('error'))
                <div style="color:red;">{{ session('error') }}</div>
            @endif
            @if ($game['bust'])
                <div style="color:red; font-weight: bold; font-size: 1.5em;">{{ $game['bust_message'] }}</div>
            @endif
            <div class="timer" id="timer"></div>
        </div>

        <!-- Rechte Spalte -->
        <div class="dart-rightcol">
            <form id="dart-form" method="POST" action="{{ route('dart.throw') }}" @if($game['winner']) style="display:none;" @endif>
                @csrf
                @for ($i = 0; $i < 3; $i++)
                    <input type="hidden" name="throws[{{ $i }}][points]" id="points{{ $i }}" value="0">
                    <input type="hidden" name="throws[{{ $i }}][multiplier]" id="multiplier{{ $i }}" value="1">
                @endfor

                <div class="dart-board">
                    @for($i=0; $i<=20; $i++)
                        <button class="dart-btn" type="button" data-value="{{ $i }}">{{ $i }}</button>
                    @endfor
                    <button class="dart-btn" type="button" data-value="25">Bull</button>
                </div>

                <div>
                    <button type="button" class="dart-btn multiplier-btn" data-mul="2">Double</button>
                    <button type="button" class="dart-btn multiplier-btn" data-mul="3">Triple</button>
                    <button type="button" class="dart-btn" id="reset-btn">Letzten Wurf zurück</button>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; margin-top:1em;">
                    <div>
                        <span>Wurf 1: <span id="wurf0display">-</span></span> /
                        <span>Wurf 2: <span id="wurf1display">-</span></span> /
                        <span>Wurf 3: <span id="wurf2display">-</span></span>
                        <br>
                        <strong>Rundensumme: <span id="roundsum">0</span></strong>
                    </div>
                    <button type="button" id="next-btn" style="display:none;">Weiter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- HEADER CSS EINBINDEN -->
<style>
.header-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1em;
    margin-bottom: 1em;
}
.header-bar h1,
.header-bar h2 {
    margin: 0;
    font-size: 1.8em;
    flex: 1 1 auto;
}
@media (max-width: 600px) {
    .header-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    .header-bar h1,
    .header-bar h2 {
        width: 100%;
    }
}
</style>

<!-- JAVASCRIPT -->
<script>
let initialScore = {{ $game['players'][$game['current']]['score'] }};
let currentPlayer = {{ $game['current'] }};
let currentThrow = 0;
let throwData = [
    {points:0, multiplier:1},
    {points:0, multiplier:1},
    {points:0, multiplier:1}
];
let multiplier = 1;

function updateDisplay() {
    let sum = 0;
    for(let i=0;i<3;i++) {
        let val = throwData[i].points * throwData[i].multiplier;
        document.getElementById('wurf'+i+'display').textContent =
            (currentThrow > i || throwData[i].points > 0)
            ? (throwData[i].points + (throwData[i].multiplier>1 ? 'x'+throwData[i].multiplier : ''))
            : '-';
        sum += val;
        document.getElementById('points'+i).value = throwData[i].points;
        document.getElementById('multiplier'+i).value = throwData[i].multiplier;
    }

    document.getElementById('roundsum').textContent = sum;

    // Punkte sofort abziehen und live anzeigen
    let newScore = initialScore - sum;
    document.getElementById('score-' + currentPlayer).textContent = newScore;

    // Anzeige "🎉 Gewonnen!" live erzeugen
    let winnerHeadline = document.querySelector('.winner-headline');
    let winContainer = document.getElementById('winner-container');
    if (newScore === 0 && !winnerHeadline) {
        let headline = document.createElement('h2');
        headline.className = 'winner-headline';
        headline.innerHTML = '🎉 Gewonnen! 🎉';
        winContainer.insertBefore(headline, winContainer.firstChild);
    }
    // Rücknahme -> "🎉 Gewonnen!" entfernen, falls Score wieder über 0 ist
    if (newScore !== 0 && winnerHeadline) {
        winnerHeadline.remove();
    }
}

// Klick auf Punkte-Buttons
document.querySelectorAll('.dart-btn[data-value]').forEach(btn => {
    btn.addEventListener('click', function() {
        if(currentThrow < 3) {
            throwData[currentThrow].points = parseInt(this.dataset.value);
            throwData[currentThrow].multiplier = multiplier;
            multiplier = 1;
            document.querySelectorAll('.multiplier-btn').forEach(b=>b.classList.remove('selected'));
            currentThrow++;
            updateDisplay();
            if(currentThrow === 3) {
                document.getElementById('next-btn').style.display = 'inline-block';
            }
        }
    });
});

// Multiplier-Auswahl
document.querySelectorAll('.multiplier-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        multiplier = parseInt(this.dataset.mul);
        document.querySelectorAll('.multiplier-btn').forEach(b=>b.classList.remove('selected'));
        this.classList.add('selected');
    });
});

// Letzten Wurf zurück
document.getElementById('reset-btn').onclick = function() {
    if (currentThrow > 0) {
        currentThrow--;
        throwData[currentThrow] = { points: 0, multiplier: 1 };
        document.getElementById('next-btn').style.display = 'none';
        updateDisplay();
    }
};

// Weiter-Button
document.getElementById('next-btn').onclick = function() {
    document.getElementById('dart-form').submit();
};

// Initial anzeigen
updateDisplay();

// Timer
let startTime = new Date("{{ \Carbon\Carbon::parse($game['start_time'])->format('Y-m-d\TH:i:s\Z') }}");
let timerDiv = document.getElementById('timer');
let winner = @json($game['winner']);
let timerInterval;
function updateTimer() {
    let ms;
    if(!winner) {
        ms = new Date() - startTime;
    } else {
        ms = new Date("{{ $game['throw_time'] }}") - startTime;
        clearInterval(timerInterval);
    }
    let min = Math.floor(ms/60000);
    let sec = Math.floor((ms%60000)/1000);
    let msec = Math.floor((ms%1000)/10);
    timerDiv.textContent = 'Spieldauer: ' +
        String(min).padStart(2,'0') + ':' +
        String(sec).padStart(2,'0') + '.' +
        String(msec).padStart(2,'0');
}
timerInterval = setInterval(updateTimer, 10);
updateTimer();
</script>
@endsection

