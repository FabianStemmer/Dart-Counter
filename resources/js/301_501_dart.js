const checkoutTable = window.checkoutTable || {};   
const gameData = window.gameData;

let currentThrows = [];
let currentMultiplier = 1;
let bust = false;
let winner = gameData.winnerInitial || false;
const currentPlayer = gameData.current;
const playerData = gameData.players;
const finalDuration = gameData.finalDuration;
const startTime = new Date(gameData.startTime);
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

    let currentScore = startScore; // Starte mit dem ursprünglichen Punktestand
    let darts = startDarts;
    let misses = startMisses;
    let v=0;

    currentThrows.forEach((t, i) => {
        const v = t.points * t.multiplier;
        currentScore -= v; // Ziehe die Punkte des aktuellen Wurfs vom Punktestand ab
        darts++;
        if (t.points === 0) misses++;
        document.getElementById('wurf' + i + 'display').textContent = v;
    });

    for (let i = currentThrows.length; i < 3; i++) {
        document.getElementById('wurf' + i + 'display').textContent = '–';
    }

    // Setze die korrekten Werte für Rundensumme und Restscore
    const scoredPoints = startScore - currentScore; // Punkte, die erzielt wurden
    document.getElementById('roundsum').textContent = scoredPoints;
    document.getElementById('score-display-' + currentPlayer).textContent = currentScore;

    // Aktualisiere Darts, Misses und Durchschnittswerte
    document.getElementById('darts-display-' + currentPlayer).textContent = darts;
    document.getElementById('misses-display-' + currentPlayer).textContent = misses;

    const avg1 = darts > 0 ? (scoredPoints / darts) : 0; // Durchschnitt pro Dart
    const avg3 = darts > 0 ? (scoredPoints / darts) * 3 : 0; // Durchschnitt pro 3 Darts
    document.getElementById('avg3-display-' + currentPlayer).textContent = avg3.toFixed(2);
    document.getElementById('avg1-display-' + currentPlayer).textContent = avg1.toFixed(2);

    // Live-Update für Checkout-Hilfe
    const checkoutTable = window.checkoutTable;
    const tip = checkoutTable && checkoutTable[currentScore] ? checkoutTable[currentScore].join(' – ') : '–';
    document.getElementById('checkoutHilfe').textContent = tip;

    // Schreibe die aktuellen Würfe in die Hidden Felder für das Backend
    for (let i = 0; i < 3; i++) {
        document.getElementById('points' + i).value = currentThrows[i] ? currentThrows[i].points : 0;
        document.getElementById('multiplier' + i).value = currentThrows[i] ? currentThrows[i].multiplier : 1;
    }
}

function checkShowModal() {
    const modal = document.getElementById('nextModal');
    if (bust || winner || currentThrows.length === 3) {
        modal.classList.add('active');
        if (bust) {
            document.getElementById('modalMessage').textContent = 'Bust! Punkte werden zurückgesetzt.';
        } else if (winner) {
            document.getElementById('modalMessage').textContent = 'Spiel beendet! Nächste Runde starten?';
        } else {
            document.getElementById('modalMessage').textContent = 'Nächster Spieler?';
        }
    } else {
        modal.classList.remove('active');
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
    document.getElementById('nextModal').classList.remove('active');
});

// Initialanzeige
window.onload = function() {
    updateDisplay();
    checkShowModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const winnerInitial = window.gameData.winnerInitial || false;
    let winner = winnerInitial;
    let bust = window.gameData.bust || false;
    const bustMessage = window.gameData.bustMessage || '';
    const finalDuration = window.gameData.finalDuration || null;
    const players = window.gameData.players || [];
    const startTime = new Date(window.gameData.startTime);

    let currentPlayer = window.gameData.current;
    let playerIsIn = window.gameData.playerIsIn || false;
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
        document.getElementById('nextModal').classList.add('active');
    }
    function hideNextModal() {
        document.getElementById('nextModal').classList.remove('active');
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