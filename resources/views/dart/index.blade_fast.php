@extends('layouts.app')

@section('content')
<div id="wrapper_div">
    <div id="div_Titel">
        <img src="{{ asset('images/sos_logo.jpg') }}" alt="Sophiensaele Logo" style="height: 70px; vertical-align: middle; margin-right: 10px;">
        &nbsp;Dart Counter
    </div>

    <div id="div_Parent_Hauptfenster">
        <div id="div_Daten">
            @if ($game['winner'])
            <div class="winner-headline" style="margin-bottom: 1rem; font-size: 1.25rem;">
                🎉 {{ $game['winner'] }} hat gewonnen! 🎉
            </div>
            <form method="POST" action="{{ route('dart.newround') }}" style="margin-bottom: 1.5rem;">
                @csrf
                <button type="submit">Neue Runde mit den gleichen Spielern</button>
            </form>
            @endif

            <h2 style="margin-top: 0; margin-bottom: 1rem;">
                Leg {{ $game['legNumber'] ?? 1 }}, Runde {{ $game['roundNumber'] ?? 1 }}
            </h2>

            {{-- Aktuellen Spieler immer ganz oben anzeigen --}}
            @php
                $current = $game['current'] ?? 0;
                $playerList = $game['players'];
                $orderedPlayers = array_merge(
                    [$playerList[$current]],
                    array_slice($playerList, 0, $current),
                    array_slice($playerList, $current + 1)
                );
            @endphp

            <div id="playerListContainer" style="border-radius: 18px; background: #f7f7fa; padding: 18px; max-height: 400px; overflow-y: auto;">
                <div class="player-row header">
                    <div style="flex: 2; text-align:left; padding: 6px 12px;">Name</div>
                    <div style="flex: 1; text-align:right; padding: 6px 12px;">Win</div>
                    <div style="flex: 1; text-align:right; padding: 6px 12px;">Punkte</div>
                    <div style="flex: 1; text-align:right; padding: 6px 12px;">Darts</div>
                    <div style="flex: 1; text-align:right; padding: 6px 12px;">❌ Misses</div>
                    <div style="flex: 1; text-align:right; padding: 6px 12px;">Ø (3 Dart)</div>
                    <div style="flex: 1; text-align:right; padding: 6px 12px;">Ø (1 Dart)</div>
                </div>
                <div id="playerList">
                    @foreach($orderedPlayers as $i => $player)
                        @php
                            $originalIndex = array_search($player['name'], array_column($playerList, 'name'));
                        @endphp
                        <div class="player-row @if($originalIndex == $current && !$game['winner']) active-player @endif" data-player-index="{{ $originalIndex }}">
                            <div style="flex: 2; font-weight: bold; text-align: left; padding: 6px 12px;">{{ $player['name'] }}</div>
                            <div class="player-legs" style="flex: 1; text-align: right; padding: 6px 12px;">{{ $player['legs'] ?? 0 }}</div>
                            <div class="player-score" style="flex: 1; text-align: right; padding: 6px 12px;" id="score-display-{{$originalIndex}}">{{ $player['score'] }}</div>
                            <div class="player-darts" style="flex: 1; text-align: right; padding: 6px 12px;" id="darts-display-{{$originalIndex}}">{{ $player['total_darts'] ?? 0 }}</div>
                            <div class="player-misses" style="flex: 1; text-align: right; padding: 6px 12px;" id="misses-display-{{$originalIndex}}">{{ $player['misses'] ?? 0 }}</div>
                            <div class="player-average-3dart" style="flex: 1; text-align: right; padding: 6px 12px;" id="avg3-display-{{$originalIndex}}">{{ number_format($player['average'] ?? 0, 2) }}</div>
                            <div class="player-average-1dart" style="flex: 1; text-align: right; padding: 6px 12px;" id="avg1-display-{{$originalIndex}}">{{ number_format($player['average_1dart'] ?? 0, 2) }}</div>
                        </div>
                    @endforeach
                </div>
                <div id="result-hint-container" style="margin-top: 1rem;">
                    @if($game['bust'] ?? false)
                    <div class="bust-message">{{ $game['bust_message'] ?? 'Bust!' }}</div>
                    @elseif($game['winner'] ?? false)
                    <div class="win-message">🎉 {{ $game['winner'] }} hat gewonnen! 🎉</div>
                    @endif
                </div>
            </div>

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
                <span id="checkoutHilfe">{{ $game['checkout_tip'] ?? '–' }}</span>
            </div>

            {{-- DoubleIn/DoubleOut Anzeige --}}
            <div class="toggle-container" style="margin-top: 1em; margin-bottom:1em; display: flex; align-items: center; gap: 1.5em;">
                <label class="switch" style="margin: 0;">
                    <input type="checkbox" id="doubleInToggle" name="doubleInToggle" {{ !empty($game['doubleInRequired']) ? 'checked' : '' }}>
                    <span class="slider round"></span>
                </label>
                <span>Double In aktivieren</span>

                <label class="switch" style="margin: 0;">
                    <input type="checkbox" id="doubleOutToggle" name="doubleOutToggle" {{ !empty($game['doubleOutRequired']) ? 'checked' : '' }}>
                    <span class="slider round"></span>
                </label>
                <span>Double Out aktivieren</span>
            </div>

            {{-- Uhrzeit + Dauer --}}
            <div class="info-row zeitdauer">
                <div id="uhrzeit">Uhrzeit: --:--:--</div>
                <div id="spieldauer">Dauer: 00:00</div>
            </div>

        </div>

        {{-- Spaltentrenner --}}
        <div id="div_Hauptfenster_Trennung"></div>

        {{-- Rechte Spalte: Dartboard und Eingabe --}}
        <div id="div_Eingabe">
            <form id="dart-form" method="POST" action="{{ route('dart.throw') }}" @if($game['winner']) style="display:none;" @endif>
                @csrf
                @for ($i = 0; $i < 3; $i++)
                <input type="hidden" name="throws[{{ $i }}][points]" id="points{{ $i }}" value="0">
                <input type="hidden" name="throws[{{ $i }}][multiplier]" id="multiplier{{ $i }}" value="1">
                @endfor

                <div class="dart-board">
                    @for($i = 1; $i <= 20; $i++)
                    <button type="button" class="dart-btn" data-value="{{ $i }}">{{ $i }}</button>
                    @endfor
                    <button type="button" class="dart-btn" data-value="25">🎯</button>
                    <button type="button" class="dart-btn miss-btn" data-value="0">Miss</button>
                </div>
                <div style="margin-bottom: 1em;">
                    <button type="button" class="dart-btn multiplier-btn" data-mul="2">Double</button>
                    <button type="button" class="dart-btn multiplier-btn" data-mul="3">Triple</button>
                    <button type="button" class="dart-btn" id="reset-btn">Letzten Wurf zurück</button>
                </div>
            </form>
            <form method="POST" action="{{ route('dart.reset') }}" style="margin-top: 1em;">
                @csrf
                <button type="submit">Spiel beenden</button>
            </form>
        </div>
    </div>

    {{-- Modal für "Weiter" --}}
    <div id="nextModal" style="display:none;">
        <div class="modal-content">
            <div id="modalMessage">Nächster Spieler?</div>
            <button id="modalContinueBtn" class="modal-btn">Weiter</button>
            <br><br>
            <button id="modalCancelBtn" class="modal-btn">Letzten Wurf korrigieren</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
window.checkoutTable = @json(include(app_path('CheckoutTable.php')));
let currentThrows = [];
let currentMultiplier = 1;
let bust = false;
let winner = false;
const currentPlayer = {{ $game['current'] ?? 0 }};
const playerData = @json($game['players']);
const finalDuration = @json($game['final_duration']);
const startTime = new Date("{{ \Carbon\Carbon::parse($game['start_time'])->toIso8601String() }}");

function pad(n) { return n < 10 ? '0' + n : n; }

function updateUhrzeit() {
    const now = new Date();
    document.getElementById('uhrzeit').textContent =
        'Uhrzeit: ' +
        pad(now.getHours()) + ':' +
        pad(now.getMinutes()) + ':' +
        pad(now.getSeconds());
}
setInterval(updateUhrzeit, 1000);
updateUhrzeit();

function updateSpieldauer() {
    let seconds = finalDuration;
    if (seconds === null) {
        seconds = Math.floor((Date.now() - startTime.getTime()) / 1000);
    }
    let min = Math.floor(seconds / 60);
    let sec = seconds % 60;
    document.getElementById('spieldauer').textContent =
        'Dauer: ' + pad(min) + ':' + pad(sec);
}
setInterval(updateSpieldauer, 1000);
updateSpieldauer();

// Startwerte aus dem aktuellen Spieler:
function getStartValues() {
    return {
        startScore: parseInt(document.getElementById('score-display-' + currentPlayer).textContent),
        startDarts: parseInt(document.getElementById('darts-display-' + currentPlayer).textContent),
        startMisses: parseInt(document.getElementById('misses-display-' + currentPlayer).textContent),
        startAvg3: parseFloat(document.getElementById('avg3-display-' + currentPlayer).textContent.replace(',', '.')),
        startAvg1: parseFloat(document.getElementById('avg1-display-' + currentPlayer).textContent.replace(',', '.'))
    };
}

function updateDisplay() {
    const { startScore, startDarts, startMisses } = getStartValues();

    let sum = 0;
    let darts = startDarts;
    let misses = startMisses;
    currentThrows.forEach((t, i) => {
        const v = t.points * t.multiplier;
        sum += v;
        darts++;
        if (t.points === 0) misses++;
        document.getElementById('wurf'+i+'display').textContent = v;
    });
    for(let i=currentThrows.length; i<3; i++) {
        document.getElementById('wurf'+i+'display').textContent = '–';
    }
    document.getElementById('roundsum').textContent = sum;

    // Zeige den Restscore an
    document.getElementById('score-display-' + currentPlayer).textContent = (startScore - sum);

    // Darts, Misses, Durchschnitt
    document.getElementById('darts-display-' + currentPlayer).textContent = darts;
    document.getElementById('misses-display-' + currentPlayer).textContent = misses;

    let currentPoints = startScore - (startScore - sum);
    let avg1 = darts > 0 ? (currentPoints / darts) : 0;
    let avg3 = darts > 0 ? (currentPoints / darts) * 3 : 0;
    document.getElementById('avg3-display-' + currentPlayer).textContent = avg3.toFixed(2);
    document.getElementById('avg1-display-' + currentPlayer).textContent = avg1.toFixed(2);

    // Checkout-Hilfe live
    const checkoutTable = window.checkoutTable;
    const newScore = startScore - sum;
    const tip = checkoutTable && checkoutTable[newScore] ? checkoutTable[newScore].join(' – ') : '–';
    document.getElementById('checkoutHilfe').textContent = tip;

    // Schreibe die aktuellen Würfe in die Hidden Felder für das Backend
    for(let i=0; i<3; i++) {
        document.getElementById('points'+i).value = currentThrows[i] ? currentThrows[i].points : 0;
        document.getElementById('multiplier'+i).value = currentThrows[i] ? currentThrows[i].multiplier : 1;
    }
}

function checkShowModal() {
    if (bust || winner || currentThrows.length === 3) {
        document.getElementById('nextModal').style.display = 'block';
        if (bust) {
            document.getElementById('modalMessage').textContent = 'Bust! Punkte werden zurückgesetzt.';
        } else if (winner) {
            document.getElementById('modalMessage').textContent = 'Spiel beendet! Nächste Runde starten?';
        } else {
            document.getElementById('modalMessage').textContent = 'Nächster Spieler?';
        }
    } else {
        document.getElementById('nextModal').style.display = 'none';
    }
}

function disableInputs(disable) {
    document.querySelectorAll('.dart-btn').forEach(btn => btn.disabled = disable);
    document.querySelectorAll('.multiplier-btn').forEach(btn => btn.disabled = disable);
    document.getElementById('reset-btn').disabled = disable;
}

// Sofortige Bust/Gewinn-Prüfung nach jedem Wurf!
document.querySelectorAll('.dart-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        if (winner || bust) return;
        if(this.classList.contains('multiplier-btn')) {
            currentMultiplier = parseInt(this.dataset.mul);
            return;
        }
        if(currentThrows.length >= 3) return;

        const value = parseInt(this.dataset.value);
        const mul = currentMultiplier;
        currentMultiplier = 1;

        currentThrows.push({points: value, multiplier: mul});

        // Hole Startwerte immer frisch!
        const { startScore } = getStartValues();

        let sum = currentThrows.reduce((acc, t) => acc + t.points * t.multiplier, 0);
        let newScore = startScore - sum;

        // DoubleIn/DoubleOut Werte aus der Anzeige holen
        const doubleIn = document.getElementById('doubleInToggle') && document.getElementById('doubleInToggle').checked;
        const doubleOut = document.getElementById('doubleOutToggle') && document.getElementById('doubleOutToggle').checked;

        // Double In prüfen (beim ersten Wurf)
        if (doubleIn && currentThrows.length === 1 && mul !== 2 && value !== 0) {
            bust = true;
            winner = false;
            updateDisplay();
            checkShowModal();
            disableInputs(true);
            return;
        }

        // Bust prüfen
        if (newScore < 0 || newScore === 1) {
            bust = true;
            winner = false;
            updateDisplay();
            checkShowModal();
            disableInputs(true);
            return;
        }

        // Double Out prüfen
        if (newScore === 0 && doubleOut && mul !== 2) {
            bust = true;
            winner = false;
            updateDisplay();
            checkShowModal();
            disableInputs(true);
            return;
        }

        // Gewinn prüfen
        if (newScore === 0) {
            winner = true;
            bust = false;
            updateDisplay();
            checkShowModal();
            disableInputs(true);
            return;
        }

        // Normalfall: nach 3 Würfen auch Modal zeigen
        if(currentThrows.length === 3) {
            updateDisplay();
            checkShowModal();
            disableInputs(true);
        } else {
            updateDisplay();
            checkShowModal();
        }
    });
});
document.getElementById('reset-btn').addEventListener('click', function(e) {
    e.preventDefault();
    if (currentThrows.length > 0 && !winner && !bust) {
        currentThrows.pop();
        updateDisplay();
        checkShowModal();
    }
});

// MODAL-Logik für "Weiter"
document.getElementById('modalContinueBtn').addEventListener('click', function(e) {
    e.preventDefault();
    if(currentThrows.length === 0 && !bust && !winner) return;
    document.getElementById('dart-form').submit();
});
document.getElementById('modalCancelBtn').addEventListener('click', function(e) {
    e.preventDefault();
    if(currentThrows.length > 0) {
        currentThrows.pop();
        bust = false;
        winner = false;
        updateDisplay();
        checkShowModal();
        disableInputs(false);
    }
    document.getElementById('nextModal').style.display = 'none';
});

// Initialanzeige
window.onload = function() {
    updateDisplay();
    checkShowModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const winnerInitial = @json($game['winner'] ? true : false);
    let winner = winnerInitial;
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

    let dartsThisRound = 0;
    let missesThisRound = 0;

    let submitting = false; // Schutz vor Mehrfach-Submit beim Modal Continue

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
            showNextModal();
            disableInputs(true);
        } else if(newScore === 0 && winner) {
            showWinMessage(`🎉 ${players[currentPlayer].name} hat gewonnen! 🎉`);
            hideNextModal();
            disableInputs(true);
        } else if (currentThrow === 3) {
            showNextModal();
            disableInputs(true);
        } else {
            hideNextModal();
            disableInputs(false);
        }

        const totalDarts = totalDartsArr[currentPlayer];
        const totalMisses = missesArr[currentPlayer];
        const totalPoints = totalPointsArr[currentPlayer] + sum;

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

    // Funktion zum Rückgängig machen beim „letzten Wurf zurück“
    function undoLastThrow() {
        if(winner) return;
        bust = false; // Bust sofort zurücksetzen!
        if (currentThrow > 0) {
            currentThrow--;

            dartsThisRound--;
            totalDartsArr[currentPlayer]--;

            if (throwData[currentThrow].points === 0) {
                missesThisRound--;
                missesArr[currentPlayer]--;
            }
            throwData[currentThrow] = { points: 0, multiplier: 1 };
        }
        disableInputs(false);
        updateDisplay();
    }

    // Modal Steuerung
    const nextModal = document.getElementById('nextModal');
    const modalContinueBtn = document.getElementById('modalContinueBtn');
    const modalCancelBtn = document.getElementById('modalCancelBtn');

    function showNextModal() {
        nextModal.style.display = 'flex';
    }
    function hideNextModal() {
        nextModal.style.display = 'none';
    }

    modalContinueBtn.addEventListener('click', () => {
        if (submitting) return; // verhindert mehrfaches Abschicken
        submitting = true;

        hideNextModal();
        bust = false;
        winner = false;
        currentThrow = 0;
        throwData = [
            {points: 0, multiplier: 1},
            {points: 0, multiplier: 1},
            {points: 0, multiplier: 1}
        ];
        dartsThisRound = 0;
        missesThisRound = 0;
        multiplier = 1;
        playerIsIn = !doubleInToggle.checked;

        clearHints();
        disableInputs(false);

        document.querySelectorAll('.multiplier-btn').forEach(b => b.classList.remove('selected'));
        document.querySelectorAll('.dart-btn').forEach(b => b.classList.remove('selected', 'active', 'highlight'));
        document.getElementById('final_duration').value =
        document.getElementById('spieldauer').textContent.replace('Dauer: ', '');

        document.getElementById('dart-form').submit();
    });

    modalCancelBtn.addEventListener('click', () => {
        hideNextModal();
        bust = false;          // Bust-Status zurücksetzen
        disableInputs(false);  // Buttons wieder aktivieren
        undoLastThrow();
    });

    document.querySelectorAll('.dart-btn[data-value]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (winner || bust) return;

            if (doubleInToggle.checked && !playerIsIn && currentThrow === 0) {
                if (multiplier !== 2) {
                    showBustMessage("Double In erforderlich! Erster Wurf muss Double sein.");
                    bust = true;

                    updateDisplay();
                    showNextModal();
                    disableInputs(true);
                    return;
                } else {
                    playerIsIn = true;
                }
            }

            const val = parseInt(btn.dataset.value) * multiplier;
            const sumThrows = throwData.slice(0, currentThrow).reduce((acc, t) => acc + t.points * t.multiplier, 0) + val;
            const newScore = initialScore - sumThrows;

            if (newScore < 0 || newScore === 1) {
                throwData[currentThrow] = {
                    points: parseInt(btn.dataset.value),
                    multiplier: multiplier
                };
                dartsThisRound++;
                totalDartsArr[currentPlayer]++;
                if (parseInt(btn.dataset.value) === 0) {
                    missesThisRound++;
                    missesArr[currentPlayer]++;
                }

                updateDisplay();
                showBustMessage("Bust! Punkte überschritten.");
                bust = true;
                showNextModal();
                disableInputs(true);
                return;
            }

            if (newScore === 0 && doubleOutToggle.checked && multiplier !== 2) {
                throwData[currentThrow] = {
                    points: parseInt(btn.dataset.value),
                    multiplier: multiplier
                };
                dartsThisRound++;
                totalDartsArr[currentPlayer]++;
                if (parseInt(btn.dataset.value) === 0) {
                    missesThisRound++;
                    missesArr[currentPlayer]++;
                }

                updateDisplay();
                showBustMessage("Double Out erforderlich! Zum Checkout nur Double erlaubt.");
                bust = true;
                showNextModal();
                disableInputs(true);
                return;
            }

            // Win-Check ohne Bust
            if (newScore === 0 && !bust) {
                winner = true;
                showWinMessage(`🎉 ${players[currentPlayer].name} hat gewonnen! 🎉`);
                disableInputs(true);
                updateDisplay();
                hideNextModal();
                return;
            }

            if (currentThrow < 3) {
                btn.classList.add('spin');
                setTimeout(() => btn.classList.remove('spin'), 600);

                throwData[currentThrow] = {
                    points: parseInt(btn.dataset.value),
                    multiplier: multiplier
                };

                dartsThisRound++;
                totalDartsArr[currentPlayer]++;

                if (parseInt(btn.dataset.value) === 0) {
                    missesThisRound++;
                    missesArr[currentPlayer]++;
                }

                // **Wichtige Änderung: aktualisiere initialScore und scores beim akzeptierten Wurf**
                initialScore = newScore;
                scores[currentPlayer] = newScore;

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
        undoLastThrow();
        hideNextModal();
    };

    // Zeit-Update
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