@extends('layouts.app')

@section('content')
<div id="wrapper_div">

    {{-- Header --}}
    <div id="div_Titel">
        <img src="{{ asset('images/sos_logo.jpg') }}" alt="Sophiensaele Logo" style="height: 70px; vertical-align: middle; margin-right: 10px;">
        &nbsp;Dart Counter
    </div>

    {{-- Hauptbereich: Zwei-Spalten-Layout --}}
    <div id="div_Parent_Hauptfenster">

        {{-- Linke Spalte: Punktebereich + Infos --}}
        <div id="div_Daten">

            {{-- Gewinnmeldung (nur sichtbar wenn Gewinner gesetzt) --}}
            @if ($game['winner'])
            <div class="winner-headline" style="margin-bottom: 1rem; font-size: 1.25rem;">
                🎉 {{ $game['winner'] }} hat gewonnen! 🎉
            </div>
            <form method="POST" action="{{ route('dart.newround') }}" style="margin-bottom: 1.5rem;">
                @csrf
                <button type="submit">Neue Runde mit den gleichen Spielern</button>
            </form>
            @endif

            {{-- Anzeige Leg und Runde --}}
            <h2 style="margin-top: 0; margin-bottom: 1rem;">
                Leg {{ $game['legNumber'] ?? 1 }}, Runde {{ $game['roundNumber'] ?? 1 }}
            </h2>

            {{-- Punkteübersicht --}}
            <div id="playerListContainer" style="border-radius: 18px; background: #f7f7fa; padding: 18px; max-height: 400px; overflow-y: auto;">

                {{-- Überschriften-Zeile --}}
                <div class="player-row header">
                    <div style="flex: 2; text-align:left; padding: 6px 12px;">Name</div>
                    <div style="flex: 1; text-align:right; padding: 6px 12px;">Win</div>
                    <div style="flex: 1; text-align:right; padding: 6px 12px;">Punkte</div>
                    <div style="flex: 1; text-align:right; padding: 6px 12px;">Darts</div>
                    <div style="flex: 1; text-align:right; padding: 6px 12px;">❌ Misses</div>
                    <div style="flex: 1; text-align:right; padding: 6px 12px;">Ø (3 Dart)</div>
                    <div style="flex: 1; text-align:right; padding: 6px 12px;">Ø (1 Dart)</div>
                </div>

                {{-- Spieler-Liste --}}
                <div id="playerList">
                    @foreach($game['players'] as $i => $player)
                    <div class="player-row @if($i == $game['current'] && !$game['winner']) active-player @endif" data-player-index="{{ $i }}">
                        <div style="flex: 2; font-weight: bold; text-align: left; padding: 6px 12px;">{{ $player['name'] }}</div>
                        <div class="player-legs" style="flex: 1; text-align: right; padding: 6px 12px;">{{ $player['legs'] ?? 0 }}</div>
                        <div class="player-score" style="flex: 1; text-align: right; padding: 6px 12px;">{{ $player['score'] }}</div>
                        <div class="player-darts" style="flex: 1; text-align: right; padding: 6px 12px;">{{ $player['total_darts'] ?? 0 }}</div>
                        <div class="player-misses" style="flex: 1; text-align: right; padding: 6px 12px;">{{ $player['misses'] ?? 0 }}</div>
                        <div class="player-average-3dart" style="flex: 1; text-align: right; padding: 6px 12px;">{{ number_format($player['average'] ?? 0, 2) }}</div>
                        <div class="player-average-1dart" style="flex: 1; text-align: right; padding: 6px 12px;">{{ number_format($player['average_1dart'] ?? 0, 2) }}</div>
                    </div>
                    @endforeach
                </div>

                {{-- Hinweis für Bust oder Gewinn --}}
                <div id="result-hint-container" style="margin-top: 1rem;">
                    @if($game['bust'] ?? false)
                    <div class="bust-message">{{ $game['bust_message'] ?? 'Bust!' }}</div>
                    @elseif($game['winner'] ?? false)
                    <div class="win-message">🎉 {{ $game['winner'] }} hat gewonnen! 🎉</div>
                    @endif
                </div>
            </div>

            {{-- Wurfanzeige --}}
            <div class="info-row" style="margin-top: 1rem;">
                Aktuelle Würfe:
                <span>
                    <span id="wurf0display">–</span> /
                    <span id="wurf1display">–</span> /
                    <span id="wurf2display">–</span>
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    <strong>Summe:</strong> <span id="roundsum">0</span>
                </span>
            </div>

            {{-- Checkout-Hilfe --}}
            <div class="info-row" style="margin-top: 0.5rem;">
                Checkout-Hilfe:
                <span id="checkoutHilfe">–</span>
            </div>

            <div class="toggle-container" style="margin-top: 1em; margin-bottom:1em; display: flex; align-items: center; gap: 1.5em;">
                <label class="switch" style="margin: 0;">
                    <input type="checkbox" id="doubleInToggle" name="doubleInToggle" @if(!empty($game['doubleInRequired'])) checked @endif>
                    <span class="slider round"></span>
                </label>
                <span>Double In aktivieren</span>

                <label class="switch" style="margin: 0;">
                    <input type="checkbox" id="doubleOutToggle" name="doubleOutToggle" @if(!empty($game['doubleOutRequired'])) checked @endif>
                    <span class="slider round"></span>
                </label>
                <span>Double Out aktivieren</span>
            </div>

            {{-- Uhrzeit + Dauer --}}
            <div class="info-row zeitdauer">
                <div id="uhrzeit">Uhrzeit: 00:00:00</div>
                <div id="spieldauer">Dauer: 00:00</div>
            </div>
        </div>

        {{-- Spaltentrenner --}}
        <div id="div_Hauptfenster_Trennung"></div>

        {{-- Rechte Spalte: Dartboard und Eingabe --}}
        <div id="div_Eingabe">
            <form id="dart-form" method="POST" action="{{ route('dart.throw') }}" @if($game['winner']) style="display:none;" @endif>
                @csrf
                <input type="hidden" name="final_duration" id="final_duration" value="">

                @for ($i = 0; $i < 3; $i++)
                <input type="hidden" name="throws[{{ $i }}][points]" id="points{{ $i }}" value="0">
                <input type="hidden" name="throws[{{ $i }}][multiplier]" id="multiplier{{ $i }}" value="1">
                @endfor

                {{-- Dartboard --}}
                <div class="dart-board">
                    @for($i = 1; $i <= 20; $i++)
                    <button type="button" class="dart-btn" data-value="{{ $i }}">{{ $i }}</button>
                    @endfor
                    <button type="button" class="dart-btn" data-value="26">ST</button>
                    <button type="button" class="dart-btn" data-value="25">🎯</button>
                    <button type="button" class="dart-btn miss-btn" data-value="0">Miss</button>
                </div>

                {{-- Multiplier-Buttons + Zurück --}}
                <div style="margin-bottom: 1em;">
                    <button type="button" class="dart-btn multiplier-btn" data-mul="2">Double</button>
                    <button type="button" class="dart-btn multiplier-btn" data-mul="3">Triple</button>
                    <button type="button" class="dart-btn" id="reset-btn">Letzten Wurf zurück</button>
                </div>

                {{-- Weiter --}}
                <div style="display:flex; justify-content:end;">
                    <button type="button" id="next-btn" style="display:none;">Weiter</button>
                </div>
            </form>

            {{-- Rücksetzen --}}
            <form method="POST" action="{{ route('dart.reset') }}" style="margin-top: 1em;">
                @csrf
                <button type="submit">Spiel beenden</button>
            </form>
        </div>
    </div>

    {{-- Footer --}}
    <div id="div_footer">
        <div id="footer_left">Version 0.8 (Beta)</div>
        <div id="footer_center">© 2025 Stemmer Software Systems Engineering</div>
        <div id="footer_right">Build 0250.20250806</div>
    </div>

</div>
@endsection

@section('scripts')
<script>
window.checkoutTable = @json(include(app_path('CheckoutTable.php')));
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const winner = @json($game['winner'] ? true : false);
    let bust = @json($game['bust'] ? true : false);
    const bustMessage = @json($game['bust_message'] ?? '');
    const finalDuration = @json($game['final_duration'] ?? null);
    const players = @json($game['players']);
    const startTime = new Date("{{ \Carbon\Carbon::parse($game['start_time'])->toIso8601String() }}");

    let currentPlayer = {{ $game['current'] }};
    let playerIsIn = @json($game['players'][$game['current']]['is_in']);
    let scores = players.map(p => p.score);
    let totalDartsArr = players.map(p => p.total_darts || 0);
    let totalPointsArr = players.map(p => p.total_points || 0);
    let missesArr = players.map(p => p.misses || 0);
    let legsArr = players.map(p => p.legs || 0);

    let initialScore = scores[currentPlayer];
    let currentThrow = 0;
    let throwData = [
        {points: 0, multiplier: 1},
        {points: 0, multiplier: 1},
        {points: 0, multiplier: 1}
    ];
    let multiplier = 1;

    const doubleInToggle = document.getElementById('doubleInToggle');
    const doubleOutToggle = document.getElementById('doubleOutToggle');

    function getPlayerRow(playerIndex) {
        return document.querySelector(`#playerList > div.player-row[data-player-index='${playerIndex}']`);
    }

    function updateDisplay() {
        let sum = 0;
        for(let i = 0; i < 3; i++) {
            let val = throwData[i].points * throwData[i].multiplier;
            document.getElementById('wurf' + i + 'display').textContent =
                (currentThrow > i || throwData[i].points > 0) ?
                (throwData[i].points + (throwData[i].multiplier > 1 ? 'x' + throwData[i].multiplier : '')) : '–';
            sum += val;
            document.getElementById('points' + i).value = throwData[i].points;
            document.getElementById('multiplier' + i).value = throwData[i].multiplier;
        }
        document.getElementById('roundsum').textContent = sum;

        const newScore = initialScore - sum;

        const playerRow = getPlayerRow(currentPlayer);
        if(playerRow) {
            let scoreDiv = playerRow.querySelector('.player-score');
            if(scoreDiv) scoreDiv.textContent = newScore;
        }

        const tip = window.checkoutTable?.[newScore];
        document.getElementById('checkoutHilfe').textContent = tip ? tip.join(' – ') : '–';

        clearHints();
        if(bust) {
            showBustMessage(bustMessage || "🚫 Bust! Wurf rückgängig oder weiter.");
            document.getElementById('next-btn').style.display = 'inline-block';
            disableInputs(true);
        } else if(newScore === 0 && winner) {
            showWinMessage(`🎉 ${players[currentPlayer].name} hat gewonnen! 🎉`);
            disableInputs(true);
            document.getElementById('next-btn').style.display = 'none';
        } else {
            document.getElementById('next-btn').style.display = (currentThrow === 3) ? 'inline-block' : 'none';
            disableInputs(false);
        }

        const dartsThisRound = currentThrow;
        const missesThisRound = throwData.slice(0, currentThrow).filter(t => t.points === 0).length;
        const sumPointsThisRound = sum;

        const totalDarts = totalDartsArr[currentPlayer] + dartsThisRound;
        const totalPoints = totalPointsArr[currentPlayer] + sumPointsThisRound;
        const totalMisses = missesArr[currentPlayer] + missesThisRound;

        if(playerRow) {
            playerRow.querySelector('.player-darts').textContent = totalDarts;
            playerRow.querySelector('.player-misses').textContent = totalMisses;
            playerRow.querySelector('.player-average-3dart').textContent = (totalDarts > 0 ? (totalPoints / totalDarts * 3) : 0).toFixed(2);
            playerRow.querySelector('.player-average-1dart').textContent = (totalDarts > 0 ? (totalPoints / totalDarts) : 0).toFixed(2);
            playerRow.querySelector('.player-legs').textContent = legsArr[currentPlayer];
        }
    }

    function disableInputs(disable) {
        document.querySelectorAll('.dart-btn[data-value]').forEach(btn => btn.disabled = disable);
        document.querySelectorAll('.multiplier-btn').forEach(btn => btn.disabled = disable);
        document.getElementById('reset-btn').disabled = disable;
    }

    function showWinMessage(msg) {
        const container = document.getElementById('result-hint-container');
        clearHints();
        const div = document.createElement('div');
        div.className = 'win-message';
        div.textContent = msg;
        container.appendChild(div);
    }

    function showBustMessage(msg) {
        const container = document.getElementById('result-hint-container');
        clearHints();
        const div = document.createElement('div');
        div.className = 'bust-message';
        div.textContent = msg;
        container.appendChild(div);
    }

    function clearHints() {
        const container = document.getElementById('result-hint-container');
        container.innerHTML = '';
    }

    function highlightCurrentPlayer() {
        document.querySelectorAll('#playerList > div.player-row').forEach(e => e.classList.remove('active-player'));
        const currentRow = getPlayerRow(currentPlayer);
        if(currentRow) currentRow.classList.add('active-player');
    }

    function resetDartButtons() {
        document.querySelectorAll('.dart-btn').forEach(btn => {
            btn.classList.remove('spin');
            btn.classList.remove('selected');
        });
    }

    document.querySelectorAll('.dart-btn[data-value]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (winner || bust) return;

            // Double In nur prüfen, wenn aktiviert, Spieler noch nicht drin ist UND erster Wurf
            if (doubleInToggle.checked && !playerIsIn && currentThrow === 0) {
                if (multiplier !== 2) {
                    showBustMessage("Double In erforderlich! Erster Wurf muss Double sein.");
                    document.getElementById('next-btn').style.display = 'inline-block';
                    disableInputs(true);
                    bust = true;
                    return;
                } else {
                    // Spieler ist nun drin nach Double In erfolgreich:
                    playerIsIn = true;
                }
            }

            // Berechnung neuer Score nach aktuellem Wurf
            const val = parseInt(btn.dataset.value) * multiplier;
            const sumThrows = throwData.slice(0, currentThrow).reduce((acc, t) => acc + t.points * t.multiplier, 0) + val;
            const newScore = initialScore - sumThrows;

            // Double Out sofort prüfen bei Score == 0
            if (newScore === 0 && doubleOutToggle.checked) {
                if (multiplier !== 2) {
                    showBustMessage("Double Out erforderlich! Zum Checkout nur Double erlaubt.");
                    document.getElementById('next-btn').style.display = 'inline-block';
                    disableInputs(true);
                    bust = true;
                    return;
                }
            }

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
            if(winner || bust) return;
            multiplier = parseInt(btn.dataset.mul);
            document.querySelectorAll('.multiplier-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
        });
    });

    document.getElementById('reset-btn').onclick = () => {
        if(winner || bust) return;
        if (currentThrow > 0) {
            currentThrow--;
            throwData[currentThrow] = { points: 0, multiplier: 1 };
            document.getElementById('next-btn').style.display = 'none';
            updateDisplay();
        }
    };

    document.getElementById('next-btn').onclick = () => {
        if(winner) return;

        resetDartButtons();
        currentThrow = 0;
        throwData = [
            {points: 0, multiplier: 1},
            {points: 0, multiplier: 1},
            {points: 0, multiplier: 1}
        ];

        clearHints();

        document.getElementById('final_duration').value =
            document.getElementById('spieldauer').textContent.replace('Dauer: ', '');
        document.getElementById('dart-form').submit();
    };

    const form = document.getElementById('dart-form');

    if (doubleInToggle && form) {
        let hiddenDoubleIn = document.createElement('input');
        hiddenDoubleIn.type = 'hidden';
        hiddenDoubleIn.name = 'doubleInRequired';
        hiddenDoubleIn.value = doubleInToggle.checked ? '1' : '0';
        form.appendChild(hiddenDoubleIn);
        doubleInToggle.addEventListener('change', () => {
            hiddenDoubleIn.value = doubleInToggle.checked ? '1' : '0';
        });
    }

    if (doubleOutToggle && form) {
        let hiddenDoubleOut = document.createElement('input');
        hiddenDoubleOut.type = 'hidden';
        hiddenDoubleOut.name = 'doubleOutRequired';
        hiddenDoubleOut.value = doubleOutToggle.checked ? '1' : '0';
        form.appendChild(hiddenDoubleOut);
        doubleOutToggle.addEventListener('change', () => {
            hiddenDoubleOut.value = doubleOutToggle.checked ? '1' : '0';
        });
    }

    setInterval(() => {
        const now = new Date();
        const uhr = now.toLocaleTimeString('de-DE');
        document.getElementById('uhrzeit').textContent = "Uhrzeit: " + uhr;

        if (winner && finalDuration) {
            document.getElementById('spieldauer').textContent = 'Dauer: ' + finalDuration;
            return;
        }

        const ms = now - startTime;
        const min = Math.floor(ms / 60000);
        const sec = Math.floor((ms % 60000) / 1000);
        document.getElementById('spieldauer').textContent = `Dauer: ${String(min).padStart(2,'0')}:${String(sec).padStart(2,'0')}`;
    }, 1000);

    updateDisplay();
    highlightCurrentPlayer();
});
</script>
@endsection