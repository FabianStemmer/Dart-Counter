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
        $gameType = $request->input('game_type', 301);

        $game = [
            'players' => [],
            'current' => 0,
            'start_score' => (int)$gameType,
            'bust' => false,
            'bust_message' => '',
            'winner' => null,
            'start_time' => now(),
            'throw_time' => now(),
            'round_darts' => [],
            'timer_started_at' => now(),
            'timer_seconds' => 0,
        ];
        foreach ($players as $player) {
            $game['players'][] = [
                'name' => $player,
                'score' => (int)$gameType,
                'darts' => [],
                'total_darts' => 0,
                'total_points' => 0,
                'average' => 0,
            ];
        }
        Session::put('dart_game', $game);
        return redirect()->route('dart.index');
    }

    public function index()
    {
        $game = Session::get('dart_game', null);
        if(!$game) return redirect()->route('dart.setup');
        if (!$game['winner'] && isset($game['timer_started_at'])) {
            $game['timer_seconds_display'] = $game['timer_seconds'] + now()->diffInSeconds(\Carbon\Carbon::parse($game['timer_started_at']));
        } else {
            $game['timer_seconds_display'] = $game['timer_seconds'];
        }
        Session::put('dart_game', $game);
        return view('dart.index', ['game' => $game]);
    }
    
    public function throwDart(Request $request)
    {
        $throws = $request->input('throws', []);
        $game = Session::get('dart_game');
        if (!$game || $game['winner']) return redirect()->route('dart.index');

        // TIMER: Zeit seit letztem Start addieren
        if (isset($game['timer_started_at'])) {
            $game['timer_seconds'] += now()->diffInSeconds(\Carbon\Carbon::parse($game['timer_started_at']));
            $game['timer_started_at'] = now();
        }

        $current = $game['current'];
        $player = &$game['players'][$current];

        $roundsum = 0;
        foreach ($throws as $throw) {
            $points = (int)($throw['points'] ?? 0);
            $multiplier = (int)($throw['multiplier'] ?? 1);
            $roundsum += $points * $multiplier;
        }
        $oldScore = $player['score'];
        $newScore = $player['score'] - $roundsum;

        $bust = false;
        $bust_message = '';
        $winner = null;

        if ($newScore < 0 || $newScore == 1) {
            $bust = true;
            $bust_message = 'Bust! Punkte werden zurückgesetzt.';
            // Kein Abzug, zurücksetzen auf Anfang der Runde
        } elseif ($newScore == 0) {
            $player['score'] = 0;
            $winner = $player['name'];
        } else {
            $player['score'] = $newScore;
            foreach ($throws as $throw) {
                $points = (int)($throw['points'] ?? 0);
                $multiplier = (int)($throw['multiplier'] ?? 1);
                $val = $points * $multiplier;
                if($val>0){
                    $player['darts'][] = $val;
                    $player['total_darts']++;
                    $player['total_points'] += $val;
                }
            }
            $player['average'] = $player['total_darts'] > 0 ? round($player['total_points'] / $player['total_darts'],1) : 0;
        }

        $game['throw_time'] = now();
        $game['bust'] = $bust;
        $game['bust_message'] = $bust_message;
        $game['winner'] = $winner;

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
}
