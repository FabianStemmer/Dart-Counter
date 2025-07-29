<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8" />
  <title>Sophiensaele Dart Zähler</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    /* ========== Basic Reset ========== */
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
      margin-bottom: 20px;
    }

    #div_Titel {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-top: 20px;
      height: 60px;
      font-weight: bold;
      font-size: 60px;
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
      max-height: 400px; /* hier z.B. maximale Höhe einstellen */
      overflow-y: auto; /* bei Überlauf vertikal scrollen */
      margin-bottom: 10px;
      padding: 10px;
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

    /* Spielerreihen Styling */
    .player-row {
        background-color: #fff;
        border-radius: 12px;
        margin-bottom: 16px;
        padding: 10px 18px;
        display: flex;
        gap: 12px;
        align-items: center; /* zentriert vertikal */
        box-shadow: 0 1px 4px rgba(0,0,0,0.03);
        transition: all 0.3s ease;
        font-size: 16px; /* Einheitliche Schriftgröße */
        line-height: 1.4; /* Einheitliche Zeilenhöhe */
        min-height: 40px; /* Gleichmäßige Zeilenhöhe */
    }

    .player-row:last-child {
        margin-bottom: 0;
    }

    /* Felder mit dezenter farblicher Abhebung */
    .player-row > div {
        background-color: #e5eaf0;
        border-radius: 6px;
        padding: 6px 12px;
        min-width: 50px;
        text-align: right; /* Werte rechtsbündig */
        font-weight: normal;
        display: flex;
        align-items: center; /* Text vertikal zentriert in den Feldern */
        line-height: 1.4;
    }

    /* Name ohne Hintergrund und linksbündig, etwas größer */
    .player-row > div:first-child {
        background: transparent;
        font-weight: bold;
        text-align: left;
        flex: 3; /* Name breiter */
        min-width: auto;
    }

    /* Andere Spalten etwas breiter */
    .player-row > div:not(:first-child) {
        flex: 2;
    }

    /* Aktiver Spieler größer, hervorgehoben */
    .player-row.active-player {
        background-color: #eaf5ff;
        box-shadow: 0 4px 15px rgba(60,140,220,0.15);
        padding: 20px 18px;
        font-size: 1.1em;
        z-index: 10;
    }

    /* Überschriftenzeile etwas anders */
    .player-row.header {
        font-weight: bold;
        font-size: 16px;
        line-height: 1.4;
        min-height: 40px;
        background: transparent !important;
        color: #234;
        display: flex;
        gap: 12px;
        align-items: center;
        padding: 0 18px;
    }

    /* Überschriften-Spalten */
    .player-row.header > div {
        background: transparent !important;
        color: #234;
        padding: 0 12px;
        min-width: auto;
        text-align: left; /* Links ausgerichtet */
        white-space: nowrap; /* verhindert Zeilenumbruch */
        display: flex;
        align-items: center; /* vertikale Zentrierung */
        line-height: 1.4;
    }

    /* Überschriften: Name breiter, andere Spalten gleichmäßig breiter */
    .player-row.header > div:nth-child(1) {
        flex: 3;
    }
    .player-row.header > div:nth-child(n+2) {
        flex: 2;
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
      gap: 10px; /* optional: etwas Abstand zwischen den beiden */
      box-sizing: border-box;
      overflow: hidden; /* Scrollbalken verhindern */
    }

    #uhrzeit,
    #spieldauer {
      flex-shrink: 1; /* erlaubt Schrumpfen falls nötig */
      flex-grow: 0;
      flex-basis: 48%; /* fast je die Hälfte, damit sie nebeneinander passen */
      white-space: nowrap; /* keine Umbrüche */
      overflow: hidden; /* Inhalt wird abgeschnitten falls zu lang */
      text-overflow: ellipsis; /* Überflüssiger Text wird mit "..." gekürzt */
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

      /* Für neues Layout responsiv */
      #div_Parent_Hauptfenster {
        flex-direction: column;
      }
    }

    .switch {
      position: relative;
      display: inline-block;
      width: 50px;
      height: 24px;
    }

    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
      position: absolute;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      background-color: #ccc;
      border-radius: 24px;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      transition: 0.4s;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 18px;
      width: 18px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      border-radius: 50%;
      transition: 0.4s;
    }

    input:checked + .slider {
      background-color: #2196F3;
    }

    input:focus + .slider {
      box-shadow: 0 0 1px #2196F3;
    }

    input:checked + .slider:before {
      transform: translateX(26px);
    }

    .toggle-container {
      display: flex;
      align-items: center; /* Vertikale Zentrierung */
      gap: 0.5em; /* Abstand zwischen Regler und Text */
    }

    table {
      width: 100%;
      border-collapse: separate; /* Wichtig: verhindert Überschreibung der Border-Radien */
      border-spacing: 0;
      border-radius: 18px;
      overflow: hidden; /* für Safari/Firefox bei Border-Radius */
      border: 3px solid #234;
      box-shadow: 0 2px 10px rgba(50,50,50,0.08);
    }

    th, td {
      border: 2px solid #234;
      padding: 10px;
    }

    th:first-child, td:first-child {
      border-left-width: 2px;
    }

    th:last-child, td:last-child {
      border-right-width: 2px;
    }

    .moveup {
      transition: transform 0.4s;
      transform: translateY(-1.5em);
    }
  </style>
</head>

<body>

  {{-- Hauptinhalt --}}
  @yield('content')

  {{-- Skripte --}}
  @yield('scripts')

</body>
</html>