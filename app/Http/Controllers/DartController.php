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
            'startPlayerIndex' => 0,
            'start_score' => $gameType,
            'bust' => false,
            'bust_message' => '',
            'winner' => null,
            'start_time' => now(),
            'throw_time' => now(),
            'round_darts' => [],
            'round_start_scores' => [],
            'round_start_points' => [],
            'round_start_darts' => [],
            'legNumber' => 1,
            'roundNumber' => 1,
            'doubleOutRequired' => false,
            'doubleInRequired' => false,
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
                'is_in' => $game['doubleInRequired'] ? false : true,
            ];
        }

        Session::put('dart_game', $game);
        return redirect()->route('dart.index');
    }

    public function index()
    {
        $game = Session::get('dart_game', null);
        if (!$game) return redirect()->route('dart.setup');

        $currentIndex = $game['current'];
        $players = $game['players'];

        if ($currentIndex > 0 && count($players) > 1) {
            $players = array_merge(
                array_slice($players, $currentIndex),
                array_slice($players, 0, $currentIndex)
            );
        }

        $game['players'] = $players;
        $game['current'] = 0;

        $checkoutTable = include(app_path('CheckoutTable.php'));
        $currentScore = $game['players'][0]['score'] ?? null;
        $game['checkout_tip'] = $currentScore !== null ? ($checkoutTable[$currentScore] ?? null) : null;

        return view('dart.index', ['game' => $game]);
    }

    public function throwDart(Request $request)
    {
        $game = Session::get('dart_game');
        if (!$game || $game['winner']) return redirect()->route('dart.index');

        if ($request->has('doubleOutRequired')) {
            $game['doubleOutRequired'] = filter_var($request->input('doubleOutRequired'), FILTER_VALIDATE_BOOLEAN);
        }
        if ($request->has('doubleInRequired')) {
            $game['doubleInRequired'] = filter_var($request->input('doubleInRequired'), FILTER_VALIDATE_BOOLEAN);
        }

        $current = $game['current'];
        $player = &$game['players'][$current];
        $throws = $request->input('throws', []);
        $bust = false;
        $bust_message = '';
        $winner = null;
        $gameEnded = false;

        // Frühprüfung Double In (erster Wurf muss Double sein falls erforderlich)
        if (!empty($game['doubleInRequired']) && empty($player['is_in'])) {
            if (!isset($throws[0]) || (int)$throws[0]['points'] === 0 || (int)$throws[0]['multiplier'] !== 2) {
                $game['bust'] = true;
                $game['bust_message'] = 'Double In erforderlich! Wurf nicht gültig, nochmal versuchen.';
                Session::put('dart_game', $game);
                return redirect()->route('dart.index');
            } else {
                $player['is_in'] = true;
            }
        }

        // Werte für Bust-Reset merken
        if (!isset($game['round_start_scores'][$current])) {
            $game['round_start_scores'][$current] = $player['score'];
            $game['round_start_points'][$current] = $player['total_points'];
            $game['round_start_darts'][$current] = $player['total_darts'];
        }

        foreach ($throws as $i => $throw) {
            if ($gameEnded) break;

            $points = (int)($throw['points'] ?? 0);
            $multiplier = (int)($throw['multiplier'] ?? 1);
            $val = $points * $multiplier;

            if ($points === 0) {
                $player['misses'] = ($player['misses'] ?? 0) + 1;
            }

            if (!empty($game['doubleInRequired']) && empty($player['is_in'])) {
                continue;
            }

            $newScore = $player['score'] - $val;

            // Bust sofort bei negativem Score oder Rest 1
            if ($newScore < 0 || $newScore == 1) {
                $bust = true;
                $bust_message = 'Bust! Punkte werden zurückgesetzt.';
                break;
            }

            // Double Out Checkout prüfen
            if ($newScore == 0) {
                if (!empty($game['doubleOutRequired']) && $multiplier != 2) {
                    $bust = true;
                    $bust_message = 'Double Out erforderlich! Bust.';
                    break;
                }

                // Gültiger Checkout, Spieler gewinnt
                $player['legs'] = ($player['legs'] ?? 0) + 1;
                $player['score'] = 0;
                $player['darts'][] = $val;
                $player['total_points'] += $val;
                $player['total_darts']++;
                $winner = $player['name'];
                $gameEnded = true;
                break;
            }

            $player['score'] = $newScore;
            $player['darts'][] = $val;
            $player['total_points'] += $val;
            $player['total_darts']++;
        }

        if ($bust) {
            $player['score'] = $game['round_start_scores'][$current];
            $player['total_points'] = $game['round_start_points'][$current];
            $player['total_darts'] = $game['round_start_darts'][$current];
            $winner = null;
        } else {
            unset($game['round_start_scores'][$current]);
            unset($game['round_start_points'][$current]);
            unset($game['round_start_darts'][$current]);
        }

        $startScore = $game['start_score'];
        $scoredPoints = $startScore - $player['score'];
        $throwsCount = $player['total_darts'];

        $player['average'] = $throwsCount > 0 ? round(($scoredPoints / $throwsCount) * 3, 1) : 0;
        $player['average_1dart'] = $throwsCount > 0 ? round($scoredPoints / $throwsCount, 1) : 0;

        $game['throw_time'] = now();
        $game['bust'] = $bust;
        $game['bust_message'] = $bust_message;
        $game['winner'] = $winner;

        $checkoutTable = include(app_path('CheckoutTable.php'));
        $game['checkout_tip'] = $checkoutTable[$player['score']] ?? null;

        if ($request->has('final_duration') && $winner) {
            $game['final_duration'] = $request->input('final_duration');
        }

        if (!$winner) {
            $game['current'] = ($game['current'] + 1) % count($game['players']);
            if ($game['current'] == $game['startPlayerIndex']) {
                $game['roundNumber'] = ($game['roundNumber'] ?? 1) + 1;
            }
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

        $legNumber = $game['legNumber'] ?? 1;
        if (!empty($game['winner'])) {
            $legNumber++;
        }

        $roundNumber = 1;

        $startPlayerIndex = ($game['startPlayerIndex'] + 1) % count($players);

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
            }, $players),
            'current' => $startPlayerIndex,
            'startPlayerIndex' => $startPlayerIndex,
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
            'legNumber' => $legNumber,
            'roundNumber' => $roundNumber,
            'doubleOutRequired' => $game['doubleOutRequired'] ?? false,
            'doubleInRequired' => $game['doubleInRequired'] ?? false,
            'is_in' => $game['doubleInRequired'] ? false : true,
        ];

        Session::put('dart_game', $newGame);
        return redirect()->route('dart.index');
    }
}
