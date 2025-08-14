@extends('layouts.app')

@section('content')
<div id="wrapper_div">
    <div id="div_Titel">
        <img src="{{ asset('images/sos_logo.jpg') }}" alt="Sophiensaele Logo" style="height: 100px; vertical-align: middle; margin-right: 10px;">
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

            <!-- Spieler-Tabelle -->
            <div id="playerListContainer">
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
                    <div class="player-row @if($i == 0 && !$game['winner']) active-player @endif" data-player-index="{{ $i }}">
                        <div style="flex: 2; font-weight: bold; text-align: left; padding: 6px 12px;">{{ $player['name'] }}</div>
                        <div class="player-legs" style="flex: 1; text-align: right; padding: 6px 12px;">{{ $player['legs'] ?? 0 }}</div>
                        <div class="player-score" style="flex: 1; text-align: right; padding: 6px 12px;" id="score-display-{{$i}}">{{ $player['score'] }}</div>
                        <div class="player-darts" style="flex: 1; text-align: right; padding: 6px 12px;" id="darts-display-{{$i}}">{{ $player['total_darts'] ?? 0 }}</div>
                        <div class="player-misses" style="flex: 1; text-align: right; padding: 6px 12px;" id="misses-display-{{$i}}">{{ $player['misses'] ?? 0 }}</div>
                        <div class="player-average-3dart" style="flex: 1; text-align: right; padding: 6px 12px;" id="avg3-display-{{$i}}">{{ number_format($player['average'] ?? 0, 2) }}</div>
                        <div class="player-average-1dart" style="flex: 1; text-align: right; padding: 6px 12px;" id="avg1-display-{{$i}}">{{ number_format($player['average_1dart'] ?? 0, 2) }}</div>
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

            <div class="info-row" style="margin-top: 0.5rem;">
                Checkout-Hilfe:
                <span id="checkoutHilfe">{{ $game['checkout_tip'] ?? '–' }}</span>
            </div>

            <!-- Double In/Out Switches -->
            <div class="toggle-container" style="margin-top: 1em; margin-bottom:1em;">
                <label class="switch">
                    <input type="checkbox" {{ !empty($game['doubleInRequired']) ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
                <span>Double In aktivieren</span>

                <label class="switch">
                    <input type="checkbox" {{ !empty($game['doubleOutRequired']) ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
                <span>Double Out aktivieren</span>
            </div>

            <div class="info-row">
                Aktuelle Würfe:
                <span>
                    <span id="wurf0display">–</span> /
                    <span id="wurf1display">–</span> /
                    <span id="wurf2display">–</span>
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    <strong>Summe:</strong> <span id="roundsum">0</span>
                </span>
            </div>

            <div class="info-row zeitdauer" style="display: flex; justify-content: space-between;">
                <div id="uhrzeit">Uhrzeit:</div>
                <div id="spieldauer">Dauer:</div>
            </div>
        </div>

        <div id="div_Hauptfenster_Trennung"></div>

        <!-- Rechte Spalte -->
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
                    <div></div>
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

    <!-- Modal für "Weiter" -->
    <div id="nextModal">
        <div class="modal-content">
            <div id="modalMessage">Nächster Spieler?</div>
            <button id="modalContinueBtn" class="modal-btn">Weiter</button>
            <br><br>
            <button id="modalCancelBtn" class="modal-btn">Letzten Wurf korrigieren</button>
        </div>
    </div>

    
    {{-- Footer --}}
    @include('partials.footer')

</div>
@endsection

@section('scripts')

<script src="{{ asset('js/301_501_dart.js') }}"></script>

@endsection
