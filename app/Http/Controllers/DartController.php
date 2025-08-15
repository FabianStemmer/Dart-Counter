<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class DartController extends Controller
{
    /**
     * Zeigt das Setup-Formular für das Dartspiel.
     */
    public function setup()
    {
        return view('dart.setup');
    }

    /**
     * Initialisiert ein neues Spiel und setzt alle Variablen sauber.
     */
    public function startGame(Request $request)
    {
        $numPlayers = (int) $request->input('num_players', 2);
        $players = array_slice($request->input('players', []), 0, $numPlayers);
        $gameType = (int) $request->input('game_type', 301);

        $game = [
            'players' => [],
            'start_score' => $gameType,
            'winner' => null,
            'final_duration' => null,
            'legNumber' => 1,
            'roundNumber' => 1,
            'doubleOutRequired' => (bool)$request->input('doubleOutRequired', false),
            'doubleInRequired' => (bool)$request->input('doubleInRequired', false),
            'bust' => false,
            'bust_message' => '',
            'checkout_tip' => '',
            'start_time' => now(),
            'current' => 0,
        ];

        foreach ($players as $index => $player) {
            $game['players'][] = [
                'name' => ($index + 1) . '. ' . $player,
                'score' => $gameType,
                'darts' => [],
                'total_darts' => 0,
                'total_points' => 0,
                'average' => 0,
                'average_1dart' => 0,
                'legs' => 0,
                'misses' => 0,
                'is_in' => $game['doubleInRequired'] ? false : true,
            ];
        }

        // Checkout-Tipp initialisieren
        $this->updateCheckoutTip($game, $game['players'][0]['score'] ?? null);

        Session::put('dart_game', $game);
        return redirect()->route('dart.301_501_dart');
    }

    /**
     * Zeigt die aktuelle Spielübersicht an und stellt Defaults für alle Variablen sicher.
     */
    public function index()
    {
        $game = Session::get('dart_game', null);
        if (!$game) return redirect()->route('dart.setup');

        // Defaults für alle Keys setzen:
        $game = $this->ensureDefaults($game);

        // Checkout-Tipp aktualisieren:
        $this->updateCheckoutTip($game, $game['players'][ $game['current'] ?? 0 ]['score'] ?? null);

        return view('dart.301_501_dart', ['game' => $game]);
    }

    /**
     * Verarbeitet einen Durchgang (bis zu 3 Würfe).
     */
    public function throwDart(Request $request)
    {
        $game = Session::get('dart_game');
        if (!$game || ($game['winner'] ?? false)) return redirect()->route('dart.301_501_dart');
        $game = $this->ensureDefaults($game);

        $throws = $request->input('throws', []);
        $current = $game['current'] ?? 0;
        $player = &$game['players'][$current];

        // Double-In prüfen
        if ($game['doubleInRequired'] && !$player['is_in']) {
            $entered = false;
            foreach ($throws as $throw) {
                if ((int)($throw['points'] ?? 0) > 0 && (int)($throw['multiplier'] ?? 1) === 2) {
                    $entered = true;
                    break;
                }
            }
            if (!$entered) {
                $game['bust'] = true;
                $game['bust_message'] = 'Double In erforderlich! Kein gültiger Einstieg. Runde zählt nicht.';
                $game['winner'] = null;
                $this->updateCheckoutTip($game, $player['score']);
                Session::put('dart_game', $game);
                return redirect()->route('dart.301_501_dart');
            }
            $player['is_in'] = true;
        }

        // Werte für Bust zurücksetzen
        $game['bust'] = false;
        $game['bust_message'] = '';
        $game['winner'] = null;

        $startScore   = $player['score'];
        $startDarts   = $player['total_darts'];
        $startPoints  = $player['total_points'];
        $startMisses  = $player['misses'];

        $bust = false;
        $bust_message = '';
        $winner = null;

        foreach ($throws as $throw) {
            $points = (int)($throw['points'] ?? 0);
            $multiplier = (int)($throw['multiplier'] ?? 1);
            $value = $points * $multiplier;

            // Darts zählen
            $player['total_darts']++;

            // Miss zählen
            if ($points === 0) {
                $player['misses']++;
            }

            // Score abziehen, aber vor jedem Wurf prüfen auf Bust
            if ($player['score'] - $value < 0) {
                $bust = true;
                $bust_message = 'Bust! Punkte werden zurückgesetzt.';
                break;
            }
            if ($player['score'] - $value == 1) {
                $bust = true;
                $bust_message = 'Bust! 1 Punkt Rest ist nicht erlaubt.';
                break;
            }
            if ($player['score'] - $value == 0) {
                // Double Out prüfen falls aktiviert
                if ($game['doubleOutRequired'] && $multiplier != 2) {
                    $bust = true;
                    $bust_message = 'Double Out erforderlich! Bust.';
                    break;
                }
                $player['score'] = 0;
                $player['total_points'] += $value;
                $player['darts'][] = $value;
                $winner = $player['name'];
                $player['legs'] = ($player['legs'] ?? 0) + 1;

                // Endzeit stoppen (final_duration setzen)
                $startTime = isset($game['start_time']) ? Carbon::parse($game['start_time']) : now();
                $game['final_duration'] = $startTime->diffInSeconds(now());

                break;
            }
            // Noch nicht gewonnen, Punkte abziehen
            $player['score'] -= $value;
            $player['total_points'] += $value;
            $player['darts'][] = $value;
        }

        // Bei Bust alles zurück
        if ($bust) {
            $player['score'] = $startScore;
            $player['total_darts'] = $startDarts;
            $player['total_points'] = $startPoints;
            $player['misses'] = $startMisses;
        }

        // Durchschnitt berechnen
        $scoredPoints = $game['start_score'] - $player['score'];
        $throwsCount = $player['total_darts'];
        $player['average'] = $throwsCount > 0 ? round(($scoredPoints / $throwsCount) * 3, 1) : 0;
        $player['average_1dart'] = $throwsCount > 0 ? round($scoredPoints / $throwsCount, 1) : 0;

        // Checkout-Tipp aktualisieren
        $this->updateCheckoutTip($game, $player['score']);

        // Gewinner setzen, Bust setzen, Message setzen
        $game['winner'] = $winner;
        $game['bust'] = $bust;
        $game['bust_message'] = $bust_message;

        // Spieler rotieren, wenn noch nicht gewonnen
        if (!$winner) {
            $game['current'] = ($current + 1) % count($game['players']);
        }

        // Am Ende: Alle Keys nochmal sicherstellen (auch für neue Spieler nach Rotation)
        $game = $this->ensureDefaults($game);

        Session::put('dart_game', $game);
        return redirect()->route('dart.301_501_dart');
    }

    /**
     * Setzt das Spiel komplett zurück.
     */
    public function resetGame()
    {
        Session::forget('dart_game');
        return redirect()->route('dart.setup');
    }

    /**
     * Startet eine neue Runde ("Leg") und setzt alle Variablen korrekt zurück.
     */
    public function newRound(Request $request)
    {
        $game = session('dart_game');
        if (!$game || !isset($game['players'])) {
            return redirect()->route('dart.setup')->with('error', 'Spieler konnten nicht geladen werden.');
        }

        $players = $game['players'];
        $legNumber = $game['legNumber'] ?? 1;
        if (!empty($game['winner'])) {
            $legNumber++;
        }

        // Startspieler nach der Rotation: aktueller vorn, nächster beginnt
        $current = $game['current'] ?? 0;
        $next = ($current + 1) % count($players);

        // Spieler rotieren für neue Runde, der nächste ist der neue Startspieler
        $orderedPlayers = [];
        for ($i = 0; $i < count($players); $i++) {
            $orderedPlayers[] = $players[ ($next + $i) % count($players) ];
        }

        $newGame = [
            'players' => array_map(function ($p) use ($game) {
                return [
                    'name' => $p['name'],
                    'score' => $game['start_score'] ?? 501,
                    'darts' => [],
                    'total_darts' => 0,
                    'total_points' => 0,
                    'average' => 0,
                    'average_1dart' => 0,
                    'legs' => $p['legs'] ?? 0,
                    'misses' => 0,
                    'is_in' => $game['doubleInRequired'] ? false : true,
                ];
            }, $orderedPlayers),
            'start_score' => $game['start_score'] ?? 501,
            'winner' => null,
            'final_duration' => null,
            'legNumber' => $legNumber,
            'roundNumber' => 1,
            'doubleOutRequired' => $game['doubleOutRequired'] ?? false,
            'doubleInRequired' => $game['doubleInRequired'] ?? false,
            'bust' => false,
            'bust_message' => '',
            'checkout_tip' => '',
            'start_time' => now(),
            'current' => 0,
        ];

        // Checkout-Tipp für neuen Startwert
        $this->updateCheckoutTip($newGame, $newGame['players'][0]['score']);

        // Alle Keys sicherstellen
        $newGame = $this->ensureDefaults($newGame);

        Session::put('dart_game', $newGame);
        return redirect()->route('dart.301_501_dart');
    }

    /**
     * Stellt sicher, dass im $game-Array alle wichtigen Keys für die View immer gesetzt sind.
     */
    private function ensureDefaults(array $game)
    {
        $defaults = [
            'winner' => null,
            'final_duration' => null,
            'bust' => false,
            'bust_message' => '',
            'checkout_tip' => '',
            'legNumber' => 1,
            'roundNumber' => 1,
            'doubleOutRequired' => false,
            'doubleInRequired' => false,
            'start_time' => now(),
            'current' => 0,
        ];
        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $game)) {
                $game[$key] = $value;
            }
        }
        // Für jeden Spieler
        foreach ($game['players'] as $i => $player) {
            $pDefaults = [
                'score' => $game['start_score'] ?? 501,
                'darts' => [],
                'total_darts' => 0,
                'total_points' => 0,
                'average' => 0,
                'average_1dart' => 0,
                'legs' => 0,
                'misses' => 0,
                'is_in' => $game['doubleInRequired'] ? false : true,
            ];
            foreach ($pDefaults as $k => $v) {
                if (!array_key_exists($k, $player)) {
                    $game['players'][$i][$k] = $v;
                }
            }
        }
        return $game;
    }

    /**
     * Aktualisiert den Checkout-Tipp im $game-Array (immer als String).
     */
    private function updateCheckoutTip(array &$game, $score)
    {
        $checkoutTable = include(app_path('CheckoutTable.php'));
        $tip = $score !== null ? ($checkoutTable[$score] ?? null) : null;
        if (is_array($tip)) {
            $tip = implode(' | ', $tip);
        }
        $game['checkout_tip'] = $tip;
    }
}