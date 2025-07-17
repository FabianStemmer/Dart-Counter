<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Sophiensaele Dart Zähler</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- SimpleCSS Baseline Design -->
    <link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">

    <style>
    /* Container mittig und mit begrenzter Breite */
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1em;
    }

    /* Flex-Wrapper für Statistik + Eingabe */
    .dart-flex-wrapper {
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        flex-wrap: nowrap;
        gap: 2em;
    }

    /* Linke Spalte: Statistik */
    .dart-leftcol {
        flex: 1 1 350px;
        min-width: 250px;
        max-width: 400px;
        margin-left: 0;
        padding-left: 0.5em;
        background: #f9f9f9;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }

    /* Rechte Spalte: Eingabe */
    .dart-rightcol {
        flex: 2 1 600px;
        min-width: 300px;
        max-width: 800px;
    }

    /* Dartboard als Grid */
    .dart-board {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 12px;
        margin-bottom: 1em;
    }

    /* Dart-Buttons */
    .dart-btn {
        width: 100%;
        height: 100%;
        min-width: 70px;
        min-height: 70px;
        font-size: 1.7em;
        border-radius: 10px;
        border: 2px solid #222;
        background: #f5f5f5;
        cursor: pointer;
        transition: background 0.2s;
        box-sizing: border-box;
        color: black;
    }

    /* Ausgewählter Button & Multiplier */
    .dart-btn.selected {
        background: #8ecae6;
    }
    .multiplier-btn {
        background: #ffd166;
    }

    /* Aktiver Spieler hervorheben */
    .player-row.active {
        font-weight: bold;
        background: #caf0f8;
    }

    /* Timer */
    .timer {
        font-size: 1.3em;
        margin-top: 1em;
    }

    /* Bust Message optisch hervorgehoben */
    .bust-message {
        color: red;
        font-weight: bold;
        font-size: 1.5em;
        margin-top: 1em;
    }

    /* Mobile Optimierung */
    @media (max-width: 900px) {
        .dart-flex-wrapper {
            flex-direction: column;
            gap: 1.5em;
        }

        .dart-leftcol,
        .dart-rightcol {
            max-width: 100%;
            min-width: auto;
        }
    }
    </style>
</head>
<body>

    {{-- Hauptinhalt --}}
    @yield('content')

    {{-- Footer --}}
    <footer style="text-align: center; font-size: 0.8em; color: #888; margin-top: 60px;">
        &copy; 2025 Stemmer Software Systems Engineering
    </footer>

    {{-- Skripte einbinden --}}
    @yield('scripts')

</body>
</html>

