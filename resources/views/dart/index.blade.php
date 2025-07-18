@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 1400px;">

    <!-- Header -->
    <div class="header-bar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1em;">
        <div><h1>Sophiensaele Dart Counter</h1></div>
        <div><h2>Wurf eingeben für: <span style="color:blue">{{ $game['players'][$game['current']]['name'] }}</span></h2></div>
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

            @if($game['checkout_tip'] ?? false)
                <div style="margin:1em 0; font-size:1.2em; color:#232;">
                    <strong>Checkout-Hilfe:</strong>
                    @foreach($game['checkout_tip'] as $step)
                        {{ $step }}{{ !$loop->last ? ' – ' : '' }}
                    @endforeach
                </div>
            @endif

            <h2>Punktestände</h2>
            <table style="width:100%; border-collapse: collapse;">
                <thead>
                  <tr>
                      <th>Name</th><th style="text-align:right;">Punkte</th><th style="text-align:right;">Darts</th><th style="text-align:right;">Ø</th>
                  </tr>
                </thead>
                <tbody>
                    @foreach($game['players'] as $i => $player)
                        <tr class="player-row @if($i == $game['current'] && !$game['winner']) active @endif">
                            <td>{{ $player['name'] }}</td>
                            <td style="text-align:right;" id="score-{{ $i }}">{{ $player['score'] }}</td>
                            <td style="text-align:right;">{{ $player['total_darts'] ?? 0 }}</td>
                            <td style="text-align:right;">{{ $player['average'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($game['bust'])
                <div class="bust-message">{{ $game['bust_message'] }}</div>
            @endif

            <div id="timer" class="timer">Spieldauer: 00:00.00</div>
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

                <div class="dart-board">
                    @for($i = 1; $i <= 20; $i++)
                        <button type="button" class="dart-btn" data-value="{{ $i }}">{{ $i }}</button>
                    @endfor
                    <button type="button" class="dart-btn" data-value="25">🎯</button>
                    <div></div>
                    <button type="button" class="dart-btn miss-btn" data-value="0">Miss</button>
                </div>

                <div style="margin-bottom: 1em;">
                    <button type="button" class="dart-btn multiplier-btn" data-mul="2">Double</button>
                    <button type="button" class="dart-btn multiplier-btn" data-mul="3">Triple</button>
                    <button type="button" class="dart-btn" id="reset-btn">Letzten Wurf zurück</button>
                </div>

                <div style="display:flex; justify-content:space-between;">
                    <div>
                        Wurf 1: <span id="wurf0display">-</span> /
                        Wurf 2: <span id="wurf1display">-</span> /
                        Wurf 3: <span id="wurf2display">-</span><br>
                        <strong>Rundensumme: <span id="roundsum">0</span></strong>
                    </div>
                    <button type="button" id="next-btn" style="display:none;">Weiter</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<script>
window.checkoutTable = @json(include(app_path('CheckoutTable.php')));
</script>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const winner = @json($game['winner'] ? true : false);
    const finalDuration = @json($game['final_duration'] ?? null);

    let initialScore = {{ $game['players'][$game['current']]['score'] }};
    let currentPlayer = {{ $game['current'] }};
    let currentThrow = 0;
    let throwData = [{points:0,multiplier:1},{points:0,multiplier:1},{points:0,multiplier:1}];
    let multiplier = 1;

    function updateDisplay() {
        let sum = 0;
        for(let i = 0; i < 3; i++) {
            let val = throwData[i].points * throwData[i].multiplier;
            document.getElementById('wurf'+i+'display').textContent =
                (currentThrow > i || throwData[i].points > 0) ?
                (throwData[i].points + (throwData[i].multiplier > 1 ? 'x'+throwData[i].multiplier : '')) :
                '-';
            sum += val;
            document.getElementById('points'+i).value = throwData[i].points;
            document.getElementById('multiplier'+i).value = throwData[i].multiplier;
        }

        document.getElementById('roundsum').textContent = sum;
        const newScore = initialScore - sum;
        document.getElementById('score-' + currentPlayer).textContent = newScore;

        let checkoutDiv = document.getElementById('checkout-help');
        if (!checkoutDiv) {
            checkoutDiv = document.createElement('div');
            checkoutDiv.id = 'checkout-help';
            checkoutDiv.style.margin = '1em 0';
            checkoutDiv.style.fontSize = '1.2em';
            checkoutDiv.style.color = '#232';
            const ref = document.querySelector('.dart-leftcol h2');
            if (ref) ref.insertAdjacentElement('afterend', checkoutDiv);
            else document.body.appendChild(checkoutDiv);
        }
        const tip = window.checkoutTable?.[newScore];
        checkoutDiv.innerHTML = tip ? `<strong>Checkout-Hilfe:</strong> ${tip.join(' – ')}` : '';

        let message = '';
        if (newScore < 2 && newScore !== 0) message = "🚫 Bust! Bitte prüfen und <b>Weiter</b> klicken.";
        if (newScore === 0) message = "🎉 Gewonnen! Bitte prüfen und <b>Weiter</b> klicken.";

        let resultDiv = document.getElementById('result-hint');
        if (!resultDiv) {
            resultDiv = document.createElement('div');
            resultDiv.id = 'result-hint';
            resultDiv.style.margin = '10px 0';
            resultDiv.style.fontWeight = 'bold';
            resultDiv.style.fontSize = '1.2em';
            resultDiv.style.color = '#900';
            document.getElementById('winner-container').appendChild(resultDiv);
        }
        resultDiv.innerHTML = message;

        document.getElementById('next-btn').style.display =
            (!winner && ((newScore === 0 || (newScore < 2 && newScore !== 0)) || currentThrow === 3))
            ? 'inline-block' : 'none';
    }

    document.querySelectorAll('.dart-btn[data-value]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentThrow < 3) {
                btn.classList.add('spin');
                setTimeout(() => btn.classList.remove('spin'), 600);

                throwData[currentThrow] = {
                    points: parseInt(btn.dataset.value),
                    multiplier: multiplier
                };
                multiplier = 1;
                document.querySelectorAll('.multiplier-btn').forEach(b => b.classList.remove('selected'));
                currentThrow++;
                updateDisplay();
            }
        });
    });

    document.querySelectorAll('.multiplier-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            multiplier = parseInt(btn.dataset.mul);
            document.querySelectorAll('.multiplier-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
        });
    });

    document.getElementById('reset-btn').onclick = () => {
        if (currentThrow > 0) {
            currentThrow--;
            throwData[currentThrow] = { points: 0, multiplier: 1 };
            document.getElementById('next-btn').style.display = 'none';
            updateDisplay();
        }
    };

    document.getElementById('next-btn').onclick = () => {
        document.getElementById('final_duration').value = document.getElementById('timer').textContent.replace('Spieldauer: ', '');
        document.getElementById('dart-form').submit();
    };

    const startTime = new Date("{{ \Carbon\Carbon::parse($game['start_time'])->toIso8601String() }}");
    const timer = document.getElementById('timer');
    setInterval(() => {
        if (winner && finalDuration) {
            timer.textContent = 'Spieldauer: ' + finalDuration;
            return;
        }
        const ms = new Date() - startTime;
        const min = Math.floor(ms / 60000);
        const sec = Math.floor((ms % 60000) / 1000);
        const msec = Math.floor((ms % 1000) / 10);
        timer.textContent = `Spieldauer: ${String(min).padStart(2,'0')}:${String(sec).padStart(2,'0')}.${String(msec).padStart(2,'0')}`;
    }, 10);

    updateDisplay();
});
</script>
@endsection
