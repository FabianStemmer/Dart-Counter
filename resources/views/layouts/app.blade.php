<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Dart Zähler</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
    <style>
        .dart-board { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 1em;}
        .dart-btn {
            min-width: 96px; min-height: 96px; font-size: 2.2em; border-radius: 10px;
            border: 2px solid #222; background: #f5f5f5; cursor: pointer;
            transition: background 0.2s;
        }
        .dart-btn.selected { background: #8ecae6; }
        .multiplier-btn { background: #ffd166; }
        .player-row.active { font-weight: bold; background: #caf0f8; }
        .timer { font-size: 1.5em; margin-top: 1em; }
        @media (max-width: 600px) {
            .dart-btn { min-width: 56px; min-height: 56px; font-size: 1.3em;}
        }

    .dart-flex-wrapper {
        display: flex;
        gap: 3em;
        align-items: flex-start;
        flex-wrap: nowrap;
    }
    .dart-leftcol {
        flex: 1 1 350px;
        min-width: 300px;
        max-width: 380px;
    }
    .dart-rightcol {
        flex: 2 1 540px;
        min-width: 360px;
        max-width: 900px;
    }
    @media (max-width: 1200px) {
        .dart-rightcol { max-width: 100%; }
    }
    @media (max-width: 900px) {
        .dart-flex-wrapper { flex-direction: column; gap: 1.2em; }
        .dart-rightcol, .dart-leftcol { max-width: 100%; min-width: 0; }
    }
    .dart-board {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 12px;
        margin-bottom: 1em;
    }
    .dart-btn {
        min-width: 70px; min-height: 70px; font-size: 1.7em; border-radius: 10px;
        border: 2px solid #222; background: #f5f5f5; cursor: pointer;
        transition: background 0.2s;
        box-sizing: border-box;
        width: 100%;
        height: 100%;
    }
    .dart-btn.selected { background: #8ecae6; }
    .multiplier-btn { background: #ffd166; }
    .player-row.active { font-weight: bold; background: #caf0f8; }
    .timer { font-size: 1.3em; margin-top: 1em; }

    </style>
</head>
<body>
    @yield('content')
    <footer style="text-align:center; font-size:0.8em; color:#888; margin-top:60px;">
        &copy;2025 Stemmer Software Systems Engineering
    </footer>
</body>
</html>
