#alt

@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 1200px;">
    <h1>Dart Zähler</h1>
    <form method="POST" action="{{ route('dart.reset') }}">
        @csrf
        <button>Spiel zurücksetzen</button>
    </form>

    <div class="dart-flex-wrapper">
        <!-- Linke Spalte: Punktestände & Infos -->
        <div class="dart-leftcol">
            @if ($game['winner'])
                <h2>🎉 {{$game['winner']}} hat gewonnen! 🎉</h2>
            @endif
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
                    <td>{{$player['name']}}</td>
                    <td>{{$player['score']}}</td>
                    <td>{{ $player['total_darts'] ?? 0 }}</td>
                    <td>{{ $player['average'] ?? 0 }}</td>
                </tr>
                @endforeach
            </table>
            @if (session('error'))
                <div style="color:red;">{{ session('error') }}</div>
            @endif
            @if ($game['bust'])
                <div style="color:red;">{{ $game['bust_message'] }}</div>
            @endif
            <div class="timer" id="timer"></div>
        </div>

        <!-- Rechte Spalte: Eingabe -->
        <div class="dart-rightcol">
            <h2>
                Wurf eingeben für: <span style="color:blue">{{ $game['players'][$game['current']]['name'] }}</span>
            </h2>
            @if(!$game['winner'])
            <form id="dart-form" method="POST" action="{{ route('dart.throw') }}">
                @csrf
                <input type="hidden" name="throws[0][points]" id="points0" value="0">
                <input type="hidden" name="throws[0][multiplier]" id="multiplier0" value="1">
                <input type="hidden" name="throws[1][points]" id="points1" value="0">
                <input type="hidden" name="throws[1][multiplier]" id="multiplier1" value="1">
                <input type="hidden" name="throws[2][points]" id="points2" value="0">
                <input type="hidden" name="throws[2][multiplier]" id="multiplier2" value="1">
                <div class="dart-board">
                    @for($i=1; $i<=20; $i++)
                        <button class="dart-btn" type="button" data-value="{{ $i }}">{{ $i }}</button>
                    @endfor
                    <button class="dart-btn" type="button" data-value="25">Bull</button>
                </div>
                <div>
                    <button type="button" class="dart-btn multiplier-btn" data-mul="2">Double</button>
                    <button type="button" class="dart-btn multiplier-btn" data-mul="3">Triple</button>
                    <button type="button" class="dart-btn" id="reset-btn">Reset</button>
                </div>
                <div style="margin-top:1em">
                    <span>Wurf 1: <span id="wurf0display">-</span></span> /
                    <span>Wurf 2: <span id="wurf1display">-</span></span> /
                    <span>Wurf 3: <span id="wurf2display">-</span></span>
                    <br>
                    <strong>Rundensumme: <span id="roundsum">0</span></strong>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>
<script>
let currentThrow = 0;
let throwData = [{points:0, multiplier:1},{points:0, multiplier:1},{points:0, multiplier:1}];
let multiplier = 1;

function updateDisplay() {
    let sum = 0;
    for(let i=0;i<3;i++) {
        let val = throwData[i].points * throwData[i].multiplier;
        document.getElementById('wurf'+i+'display').textContent = val > 0 ? (throwData[i].points + (throwData[i].multiplier>1 ? 'x'+throwData[i].multiplier : '')) : '-';
        sum += val;
        document.getElementById('points'+i).value = throwData[i].points;
        document.getElementById('multiplier'+i).value = throwData[i].multiplier;
    }
    document.getElementById('roundsum').textContent = sum;
}

// Board click: Register throw
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
                setTimeout(()=>{ document.getElementById('dart-form').submit(); }, 150);
            }
        }
    });
});

// Multiplier
document.querySelectorAll('.multiplier-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        multiplier = parseInt(this.dataset.mul);
        document.querySelectorAll('.multiplier-btn').forEach(b=>b.classList.remove('selected'));
        this.classList.add('selected');
    });
});

// Reset round
document.getElementById('reset-btn').onclick = function() {
    currentThrow = 0;
    multiplier = 1;
    throwData = [{points:0,multiplier:1},{points:0,multiplier:1},{points:0,multiplier:1}];
    document.querySelectorAll('.multiplier-btn').forEach(b=>b.classList.remove('selected'));
    updateDisplay();
};

updateDisplay();

// Millisekundengenauer Timer: Hochzählen ab 0, stoppt bei Gewinn
let timerMs = {{ ($game['timer_seconds_display'] ?? 0) * 1000 }};
let timerDiv = document.getElementById('timer');
let winner = @json($game['winner']);
let timerInterval;
function updateTimer() {
    let ms = timerMs;
    let min = Math.floor(ms/60000);
    let sec = Math.floor((ms%60000)/1000);
    let msec = Math.floor((ms%1000)/10);
    timerDiv.textContent = 'Spieldauer: ' + 
        String(min).padStart(2,'0') + ':' + 
        String(sec).padStart(2,'0') + '.' +
        String(msec).padStart(2,'0');
    if(!winner) timerMs += 10;
    else clearInterval(timerInterval);
}
timerInterval = setInterval(updateTimer, 10);
updateTimer();
</script>
@endsection
