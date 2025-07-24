<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class DartController extends Controller
{
    public function setup()
    {
        return view('dart.setup');
    }

    public function startGame(Request $request)
    {
        $numPlayers = (int) $request->input('num_players', 2);
        $players = array_slice($request->input('players', []), 0, $numPlayers);
        $gameType = (int) $request->input('game_type', 301);

        $game = [
            'players' => [],
            'current' => 0,
            'start_score' => $gameType,
            'bust' => false,
            'bust_message' => '',
            'winner' => null,
            'start_time' => now(),
            'throw_time' => now(),
            'round_darts' => [],
            'round_start_scores' => [],      // Startscore der aktuellen Runde pro Spieler
            'round_start_points' => [],      // total_points vor Runde
            'round_start_darts' => [],       // total_darts vor Runde
        ];

        foreach ($players as $player) {
            $game['players'][] = [
                'name' => $player,
                'score' => $gameType,
                'darts' => [],
                'total_darts' => 0,
                'total_points' => 0,
                'average' => 0,
                'average_1dart' => 0,
                'legs' => 0,
                'misses' => 0,
            ];
        }

        Session::put('dart_game', $game);
        return redirect()->route('dart.index');
    }

    public function index()
    {
        $game = Session::get('dart_game', null);
        if (!$game) return redirect()->route('dart.setup');

        // Checkout-Hilfe laden
        $checkoutTable = include(app_path('CheckoutTable.php'));
        $currentScore = $game['players'][$game['current']]['score'] ?? null;
        $game['checkout_tip'] = $currentScore !== null ? ($checkoutTable[$currentScore] ?? null) : null;

        Session::put('dart_game', $game);
        return view('dart.index', ['game' => $game]);
    }

    public function throwDart(Request $request)
    {
        $game = Session::get('dart_game');
        if (!$game || $game['winner']) return redirect()->route('dart.index');

        $current = $game['current'];
        $player = &$game['players'][$current];

        $throws = $request->input('throws', []);
        $bust = false;
        $bust_message = '';
        $winner = null;
        $gameEnded = false;

        // Startwerte der Runde merken, falls noch nicht gesetzt
        if (!isset($game['round_start_scores'][$current])) {
            $game['round_start_scores'][$current] = $player['score'];
            $game['round_start_points'][$current] = $player['total_points'];
            $game['round_start_darts'][$current] = $player['total_darts'];
        }

        foreach ($throws as $throw) {
            if ($gameEnded) break;

            $points = (int)($throw['points'] ?? 0);
            $multiplier = (int)($throw['multiplier'] ?? 1);
            $val = $points * $multiplier;

            if ($points === 0) {
                $player['misses'] = ($player['misses'] ?? 0) + 1;
            }

            $newScore = $player['score'] - $val;

            if ($newScore < 0 || $newScore == 1) {
                $bust = true;
                $bust_message = 'Bust! Punkte werden zurückgesetzt.';
                break;
            } elseif ($newScore == 0) {
                $player['legs'] = ($player['legs'] ?? 0) + 1;
                $player['score'] = 0;
                $player['darts'][] = $val;
                $player['total_points'] += $val;
                $player['total_darts']++;
                $winner = $player['name'];
                $gameEnded = true;
                break;
            } else {
                $player['score'] = $newScore;
                $player['darts'][] = $val;
                $player['total_points'] += $val;
                $player['total_darts']++;
            }
        }

        if ($bust) {
            // Bei Bust: Punkte auf Start der Runde zurücksetzen
            $player['score'] = $game['round_start_scores'][$current];

            // Punkte und Darts ebenfalls auf Anfang der Runde zurücksetzen
            $player['total_points'] = $game['round_start_points'][$current];
            $player['total_darts'] = $game['round_start_darts'][$current];

            // Darts-Array bleibt unverändert, geworfene Pfeile bleiben sichtbar
        } else {
            // Runde regulär abgeschlossen, Startwerte löschen
            unset($game['round_start_scores'][$current]);
            unset($game['round_start_points'][$current]);
            unset($game['round_start_darts'][$current]);
        }

        // Durchschnitt (Average) aktualisieren
        $startScore = $game['start_score'];
        $scoredPoints = $startScore - $player['score'];
        $throwsCount = $player['total_darts'];

        $player['average'] = $throwsCount > 0 ? round(($scoredPoints / $throwsCount) * 3, 1) : 0;
        $player['average_1dart'] = $throwsCount > 0 ? round($scoredPoints / $throwsCount, 1) : 0;

        $game['throw_time'] = now();
        $game['bust'] = $bust;
        $game['bust_message'] = $bust_message;
        $game['winner'] = $winner;

        // Checkout-Hilfe aktualisieren
        $checkoutTable = include(app_path('CheckoutTable.php'));
        $game['checkout_tip'] = $checkoutTable[$player['score']] ?? null;

        if ($request->has('final_duration') && $winner) {
            $game['final_duration'] = $request->input('final_duration');
        }

        // Spieler wechseln, wenn kein Gewinner
        if (!$winner) {
            $game['current'] = ($game['current'] + 1) % count($game['players']);
        }

        Session::put('dart_game', $game);
        return redirect()->route('dart.index');
    }

    public function resetGame()
    {
        Session::forget('dart_game');
        return redirect()->route('dart.setup');
    }

    public function newRound(Request $request)
    {
        $game = session('dart_game');

        if (!$game || !isset($game['players'])) {
            return redirect()->route('dart.setup')->with('error', 'Spieler konnten nicht geladen werden.');
        }

        $players = $game['players'];

        // Nächster Starter rotiert (modulo Spielerzahl)
        $nextStart = ($game['current'] + 1) % count($players);

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
                ];
            }, $players),
            'current' => $nextStart,
            'start_score' => $game['start_score'] ?? 501,
            'bust' => false,
            'bust_message' => '',
            'winner' => null,
            'start_time' => now(),
            'throw_time' => now(),
            'round_darts' => [],
            'round_start_scores' => [],
            'round_start_points' => [],
            'round_start_darts' => [],
        ];

        Session::put('dart_game', $newGame);
        return redirect()->route('dart.index');
    }
}