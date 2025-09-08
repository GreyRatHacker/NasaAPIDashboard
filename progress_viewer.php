<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Live-Fortschritt: Mars-Download</title>
    <style>
        body { font-family: sans-serif; background-color: #101418; color: #e0e0e0; margin: 0; padding: 2em; }
        h1 { color: #fc3d21; }
        #log-output {
            background-color: #000;
            border: 1px solid #333a45;
            border-radius: 8px;
            padding: 20px;
            height: 70vh;
            overflow-y: scroll;
            font-family: 'Courier New', Courier, monospace;
            white-space: pre-wrap;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <h1>🔴 Live-Fortschritt des Mars Rover Downloads</h1>
    <p>Diese Seite aktualisiert sich alle 3 Sekunden automatisch. Starte das `mars_rover_bulk_downloader.php`-Skript im Terminal, um den Prozess zu beginnen.</p>
    <pre id="log-output">Warte auf den Start des Downloader-Skripts...</pre>
    <p><a href="index.php">&laquo; Zurück zum Haupt-Dashboard</a></p>

    <script>
        const logElement = document.getElementById('log-output');

        async function updateLog() {
            try {
                const response = await fetch('mars_progress.log?t=' + new Date().getTime());
                const text = await response.text();
                logElement.textContent = text;
                logElement.scrollTop = logElement.scrollHeight;
            } catch (error) {
                logElement.textContent = "Fehler beim Laden der Log-Datei...";
            }
        }
        updateLog();
        setInterval(updateLog, 3000);
    </script>
</body>
</html>