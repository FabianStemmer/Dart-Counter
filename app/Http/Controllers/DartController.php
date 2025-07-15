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
            'start_time' => now(), // Nur Spielstart speichern!
            'throw_time' => now(),
            'round_darts' => [],
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
        // Die Zeit wird nur im Frontend berechnet!
        Session::put('dart_game', $game);
        return view('dart.index', ['game' => $game]);
    }
    
    public function throwDart(Request $request)
    {
        $game = Session::get('dart_game');
        if (!$game || $game['winner']) return redirect()->route('dart.index');

        $current = $game['current'];
        $player = &$game['players'][$current];

        $roundsum = 0;
        $throws = $request->input('throws', []);
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
//            $player['average'] = $player['total_darts'] > 0 ? round($player['total_points'] / $player['total_darts'],1) : 0;

	    $startScore = $game['start_score'];
	    $rest = $player['score'];
	    $scoredPoints = $startScore - $rest;
	    $throwsCount = $player['total_darts'];

	    $player['average'] = $throwsCount > 0
	        ? round(($scoredPoints / $throwsCount) * 3, 1)
	    : 0;

        }

        $game['throw_time'] = now();
        $game['bust'] = $bust;
        $game['bust_message'] = $bust_message;
        $game['winner'] = $winner;

        // Spielerwechsel, falls kein Gewinner
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

    public function newRound(Request $request) {
        $game = session('dart_game');
    
        if (!$game || !isset($game['players'])) {
            return redirect()->route('dart.setup')->with('error', 'Spieler konnten nicht geladen werden.');
        }

        $players = $game['players'];

        $newGame = [
            'players' => array_map(function($p) use ($game) {
                return [
                    'name' => $p['name'],
                    'score' => $game['start_score'] ?? 501,
                    'darts' => [],
                    'total_darts' => 0,
                    'total_points' => 0,
                    'average' => 0,
                ];
            }, $players),
            'current' => 0,
            'start_score' => $game['start_score'] ?? 501,
            'bust' => false,
            'bust_message' => '',
            'winner' => null,
            'start_time' => now(),
            'throw_time' => now(),
            'round_darts' => [],
        ];

        Session::put('dart_game', $newGame);
    
        return redirect()->route('dart.index');
    }

}
