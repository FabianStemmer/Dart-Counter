@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 1400px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1em;">
        <h1 style="margin:0;">Sophiensaele Dart Counter</h1>
        <h2 style="margin:0;">
            Wurf eingeben für: <span style="color:blue">{{ $game['players'][$game['current']]['name'] }}</span>
        </h2>
    </div>
    <form method="POST" action="{{ route('dart.reset') }}">
        @csrf
        <button>Spiel zurücksetzen</button>
    </form>

    <div class="dart-flex-wrapper">
        <!-- Linke Spalte: Punktestände & Infos -->
        <div class="dart-leftcol">
	    @if ($game['winner'])
    		<h2>🎉 {{$game['winner']}} hat gewonnen! 🎉</h2>
    		<form method="POST" action="{{ route('dart.newround') }}">
        	    @csrf
        	    <button class="btn btn-success">Neue Runde mit den gleichen Spielern</button>
    		</form>
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
                    @for($i=0; $i<=20; $i++)
                        <button class="dart-btn" type="button" data-value="{{ $i }}">{{ $i }}</button>
                    @endfor
                    <button class="dart-btn" type="button" data-value="25">Bull</button>
                </div>
                <div>
                    <button type="button" class="dart-btn multiplier-btn" data-mul="2">Double</button>
                    <button type="button" class="dart-btn multiplier-btn" data-mul="3">Triple</button>
                    <button type="button" class="dart-btn" id="reset-btn">Reset</button>
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
	document.getElementById('wurf'+i+'display').textContent = (currentThrow > i || throwData[i].points > 0) ? (throwData[i].points + (throwData[i].multiplier>1 ? 'x'+throwData[i].multiplier : '')) : '-';
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
//                setTimeout(()=>{ document.getElementById('dart-form').submit(); }, 150);
	    document.getElementById('next-btn').style.display = 'inline-block';
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
    if (currentThrow > 0) {
        currentThrow--;
        throwData[currentThrow] = { points: 0, multiplier: 1 };
        document.getElementById('next-btn').style.display = 'none';
        updateDisplay();
    }
};


// zeigt meinen Korrekturbutton nach dem dritten Spielzug
document.getElementById('next-btn').onclick = function() {
    document.getElementById('dart-form').submit();
};


updateDisplay();

// Einfache Timer-Logik: Spielstart aus PHP, dann im JS durchlaufen lassen!
let startTime = new Date("{{ \Carbon\Carbon::parse($game['start_time'])->format('Y-m-d\TH:i:s\Z') }}");
let timerDiv = document.getElementById('timer');
let winner = @json($game['winner']);
let timerInterval;
function updateTimer() {
    let ms;
    if(!winner) {
        ms = new Date() - startTime;
    } else {
        // Wenn das Spiel vorbei ist, Timer einfrieren
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
