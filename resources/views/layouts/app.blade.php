<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>@yield('title', 'Sophiensaele Dart Counter')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    /* ========== Basic Reset ========== */transliterator_list_ids
    *, *::before, *::after {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: system-ui, Calibri, sans-serif;
      font-size: 16px;
      line-height: 1.6;
      color: #222;
      background-color: #fff;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    h1, h2, h3 {
      font-weight: bold;
      margin-top: 1em;
      margin-bottom: 0.5em;
    }

    a {
      color: dodgerblue;
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }

    /* ========== Formulare ========== */
    input[type="text"],
    input[type="number"],
    input[type="email"],
    input[type="password"],
    textarea,
    select {
      width: 100%;
      max-width: 100%;
      padding: 8px 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 16px;
    }

    button,
    input[type="submit"],
    input[type="button"] {
      background-color: #f0f0f0;
      border: 1px solid #ccc;
      padding: 8px 14px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }

    button:hover,
    input[type="submit"]:hover,
    input[type="button"]:hover {
      background-color: #e0e0e0;
    }

    /* ========== Tabellen ========== */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 1em;
    }

    th, td {
      border: 1px solid #ccc;
      padding: 8px;
      text-align: left;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    /* Container mittig und mit begrenzter Breite */
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1em;
      flex-shrink: 0;
    }

    /* Flex-Wrapper für Statistik + Eingabe: alt, kann in Zukunft raus */
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

    .dart-btn:hover {
      background-color: #ddd;
    }

    .dart-btn.spin {
      animation: dart-spin 0.6s ease-in-out;
    }

    @keyframes dart-spin {
      0% { transform: rotate(0deg) scale(1); }
      50% { transform: rotate(360deg) scale(1.1); }
      100% { transform: rotate(720deg) scale(1); }
    }

    .miss-btn {
      grid-column: span 2;
      font-weight: bold;
      background-color: #f88;
    }

    /* Ausgewählter Button & Multiplier */
    .dart-btn.selected {
      background: #8ecae6;
    }

    .multiplier-btn {
      background: #ffd166;
    }

    /* Aktiver Spieler hervorheben */
    .player-row.active,
    .player-row.active-player {
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

    /* Neue Layout-Stile (aus deinem aktuellen Design) */
    html, body {
      height: 100vh;
      margin: 0;
      padding: 0;
      font-family: Calibri, sans-serif;
    }

    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      background: #fff;
    }

    #wrapper_div {
      flex: 1;
      display: flex;
      flex-direction: column;
      width: 80%;
      margin: auto;
    }

    #div_Titel,
    #div_Spieler {
      padding: 10px 2%;
      text-align: center;
      flex-shrink: 0;
      margin-bottom: 10px;
    }

    #div_Titel {
      height: 100px;
      line-height: 50px;
      font-weight: bold;
      font-size: 50px;
    }

    #div_Spieler {
      height: 50px;
      line-height: 30px;
      font-size: 35px;
    }

    #div_Parent_Hauptfenster {
      flex: 1;
      display: flex;
      gap: 10px;
      padding: 10px;
      overflow: hidden;
      min-height: 0;
    }

    #div_Daten,
    #div_Eingabe {
      text-align: center;
      padding: 20px;
      flex: 1;
      overflow-y: auto;
      min-height: 0;
      display: flex;
      flex-direction: column;
    }

    #div_Punktebereich {
      flex: 1;
      min-height: 400px;
      max-height: 400px;
      overflow-y: auto;
      margin-bottom: 10px;
      padding: 10px;
    }

    /* Tabelle im neuen Design */
    #playerListContainer {
      background: #e3f4ff;
      border-radius: 18px;
      box-shadow: 0 4px 32px rgba(33, 150, 243, 0.10);
      padding: 24px 12px;
      max-width: 1000px;
      margin: 0 auto 18px auto;
    }

    .player-row,
    .player-row.header {
      background: none !important;
      font-size: 1.11em;
      margin-bottom: 8px;
    }
    .player-row.header {
      font-weight: bold;
      background: #f9f9fc;
      border-radius: 10px 10px 0 0;
      padding: 6px 0;
    }
    .player-row {
      display: flex;
      align-items: center;
      margin-bottom: 8px;
      background: #fff;
      border-radius: 10px;
      min-height: 44px;
      transition: background 0.2s, box-shadow 0.2s;
    }
    .player-row.active-player {
      background: #d0edff;
      font-size: 1.18em;
      min-height: 56px;
      box-shadow: 0 2px 12px rgba(33,150,243,0.09);
    }

    /* Tabellenkopf bleibt wie bisher */
    .player-row.header > div {
      background: none !important;
      font-weight: bold;
      border-radius: 0;
      color: #333;
      padding: 8px 12px !important;
    }

    /* Aktiver Spieler: Zeile hervorheben und Text größer */
    .player-row.active-player {
      background: #e3f4ff !important;
      font-size: 1.23em;
      border: 2px solid #2196F3;
      box-shadow: 0 2px 12px rgba(33,150,243,0.06);
    }

    /* Zellen-Design für Werte beim aktiven Spieler */
    .player-row.active-player > div {
      background: #f4f6fa !important; /* hellgrau */
      color: #222;
      border-radius: 10px;
      margin: 0 5px;
      padding: 10px 14px !important;
      font-weight: 500;
      box-shadow: none;
      border: none;
    }

    /* Wertefelder für ALLE Spieler: abgerundet, hellgrau */
    .player-row > div:not(:first-child) {
      background: #f4f6fa;
      color: #222;
      border-radius: 8px;
      margin: 0 5px;
      padding: 8px 12px !important;
      font-weight: 500;
      box-shadow: none;
      border: none;
    }

        /* Name-Feld ohne Box, nur Text */
    .player-row > div:first-child {
      background: transparent !important;
      font-weight: bold;
      padding: 8px 12px !important;
      border-radius: 8px 0 0 8px;
    }

    /* Unterschiedliche Farben für die einzelnen Werte-Spalten */
    .player-row.active-player > div.player-score    { background: #b6e0fe; color: #09344e; }
    .player-row.active-player > div.player-darts    { background: #e0ecf7; color: #09344e; }
    .player-row.active-player > div.player-misses   { background: #ffd6d6; color: #700; }
    .player-row.active-player > div.player-average-3dart,
    .player-row.active-player > div.player-average-1dart { background: #ffeabf; color: #664d03; }
    .player-row.active-player > div.player-legs     { background: #daf3e3; color: #10743f; }
    .player-row.active-player > div:first-child {
      background: #e3f4ff !important; /* gleicht dem Zeilenhintergrund */
      font-weight: bold;
    }


    .info-row {
      height: 40px;
      margin-bottom: 5px;
      padding: 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 28px;
    }

    .info-row:last-of-type {
      margin-bottom: 0;
    }

    #div_Hauptfenster_Trennung {
      width: 5%;
      color: black;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      user-select: none;
      flex-shrink: 0;
    }

    #div_footer {
      height: 20px;
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      padding: 0 20px;
      display: flex;
      align-items: center;
      font-size: 14px;
      flex-shrink: 0;
      border-top: 1px solid #bbb;
    }

    #footer_left,
    #footer_center,
    #footer_right {
      flex: 1;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    #footer_left {
      text-align: left;
    }

    #footer_center {
      text-align: center;
    }

    #footer_right {
      text-align: right;
    }

    .zeitdauer {
      display: flex;
      justify-content: space-between;
      width: 100%;
      gap: 10px;
      box-sizing: border-box;
      overflow: hidden;
    }

    #uhrzeit,
    #spieldauer {
      flex-shrink: 1;
      flex-grow: 0;
      flex-basis: 48%;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Switches (Double In/Out) */
    .toggle-container {
      display: flex;
      align-items: center;
      gap: 2em;
      margin: 12px 0;
    }
    .switch {
      position: relative;
      display: inline-block;
      width: 44px;
      height: 24px;
      margin-right: 8px;
    }
    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    .slider {
      position: absolute;
      cursor: pointer;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 24px;
    }
    .slider:before {
      position: absolute;
      content: "";
      height: 18px; width: 18px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }
    input:checked + .slider {
      background-color: #2196F3;
    }
    input:checked + .slider:before {
      transform: translateX(20px);
    }

    /* Modal */

    #nextModal {
      display: none;
      position: fixed;
      top: 50%;
      left: 70%;
      transform: translate(-50%, -50%);
      z-index: 1000;
      border: 5px solid rgba(173, 199, 201, 0.95); 
      border-radius: 16px;      /* abgerundete Ecken */
      padding: 0px;           /* Innenabstand */
    }
    #nextModal.active {
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background:  rgba(229, 244, 255, 0.9);
      border-radius: 12px;
      padding: 32px 18px;
      box-shadow: 0 8px 22px rgba(0,0,0,0.09);
      text-align: center;
      min-width: 400px;
      margin: auto;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .modal-content > * {
      margin-bottom: 12px;
    }

    #modalMessage {
      font-size: 1.45em;
      font-weight: bold;
      margin-bottom: 28px;
    }

    .modal-content > *:last-child {
      margin-bottom: 0;
    }

    .modal-btn {
      font-size: 1.17em;
      padding: 13px 0;
      border-radius: 9px;
      border: 2px solid #222;
      background: #fff;
      cursor: pointer;
      width: 90%;
      min-width: 160px;
      margin-bottom: 15px;
      transition: background 0.14s, border-color 0.14s;
    }
    .modal-btn:last-child {
      margin-bottom: 0;
    }
    .modal-btn:active {
      background: #e3f4ff;
      border-color: #2196F3;
    }

    /* Win & Bust */
    .win-message {
      color: #2196F3;
      font-weight: bold;
      padding: 6px 0;
    }
    .bust-message {
      color: #c00;
      font-weight: bold;
      padding: 6px 0;
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
      #div_Parent_Hauptfenster {
        flex-direction: column;
      }
      #wrapper_div {
        width: 98%;
      }
    }
  </style>
  @yield('head')
</head>

<body>
  @yield('content')
  @yield('scripts')
</body>
</html>