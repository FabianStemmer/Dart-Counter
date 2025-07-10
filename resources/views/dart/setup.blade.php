@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Dartspiel Setup</h1>
    <form method="POST" action="{{ route('dart.start') }}">
        @csrf
        <div>
            <label>Wie viele Spieler?</label>

	    <select id="num_players" name="num_players" required onchange="updatePlayerInputs(this.value)">
    		@for($i = 1; $i <= 4; $i++)
        	    <option value="{{ $i }}">{{ $i }}</option>
    		@endfor
	    </select>

        </div>
        <div id="player-names">

	@for($i = 1; $i <= 4; $i++)
    		<div class="player-input" id="player-input-{{ $i }}" style="{{ $i == 1 ? '' : 'display:none;' }}">
        		<label>Spieler {{ $i }} Name:</label>
        		<input type="text" name="players[]" {{ $i == 1 ? 'required' : '' }}>
    		</div>
	@endfor

        </div>
        <div>
            <label>Spielart:</label>
            <select name="game_type">
                <option value="301">301</option>
                <option value="501">501</option>
            </select>
        </div>
        <button type="submit">Spiel starten</button>
    </form>
</div>


<script>

function updatePlayerInputs(num) {
    for (let i = 1; i <= 4; i++) {
        let el = document.getElementById('player-input-' + i);
        el.style.display = (i <= num) ? '' : 'none';
        let input = el.querySelector('input');
        input.required = (i <= num);
        if(i > num) input.value = '';
    }
}

</script>
@endsection
