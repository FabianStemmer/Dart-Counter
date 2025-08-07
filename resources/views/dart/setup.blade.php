@extends('layouts.app')

@section('content')

<div style="height: 100px;"></div>

<div id="div_setup" class="container">
    <h1>Dartspiel Setup</h1>

    @php
        $previousPlayers = collect(Session::get('dart_game')['players'] ?? [])
                            ->pluck('name')
                            ->unique()
                            ->filter()
                            ->values()
                            ->all();

        // Fallback-Namen, falls keine Session-Werte da sind
        if (empty($previousPlayers)) {
            $previousPlayers = ['Fabian', 'Ernesto', 'Conne', 'Nick'];
        }
    @endphp

    <form method="POST" action="{{ route('dart.start') }}">
        @csrf

        {{-- Spieleranzahl --}}
        <div style="margin-bottom: 1em;">
            <label for="num_players">Wie viele Spieler?</label>
            <select id="num_players" name="num_players" required onchange="updatePlayerInputs(this.value)">
                @for($i = 1; $i <= 4; $i++)
                    <option value="{{ $i }}">{{ $i }}</option>
                @endfor
            </select>
        </div>

        {{-- Dynamische Spielerfelder mit Datalist --}}
        <div id="player-names">
            @for($i = 1; $i <= 4; $i++)
                <div class="player-input" id="player-input-{{ $i }}" style="{{ $i == 1 ? '' : 'display:none;' }}">
                    <label>Spieler {{ $i }} Name:</label>
                    <input type="text" name="players[]" list="name-list" placeholder="Spieler {{ $i }}" {{ $i == 1 ? 'required' : '' }}>
                </div>
            @endfor
        </div>

        {{-- Gemeinsame Datalist für alle Eingabefelder --}}
        <datalist id="name-list">
            @foreach($previousPlayers as $name)
                <option value="{{ $name }}">
            @endforeach
        </datalist>

        {{-- Spielart --}}
        <div style="margin-top: 1em;">
            <label>Spielart:</label>
            <select name="game_type">
                <option value="11">11 (Debug)</option>
                <option value="301" selected>301</option>
                <option value="501">501</option>
            </select>
        </div>

        {{-- Start-Button --}}
        <button type="submit" style="margin-top: 1em;">Spiel starten</button>
    </form>

</div>


{{-- JS für dynamisches Ein- / Ausblenden --}}
<script>
function updatePlayerInputs(num) {
    for (let i = 1; i <= 4; i++) {
        const container = document.getElementById('player-input-' + i);
        const input = container.querySelector('input');
        if (i <= num) {
            container.style.display = '';
            input.required = true;
        } else {
            container.style.display = 'none';
            input.required = false;
            input.value = '';
        }
    }
}
</script>
@endsection

