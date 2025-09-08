<?php
// exoplanet_column_finder.php - Findet alle verfügbaren Datenspalten

$query = "select+column_name,description+from+TAP_SCHEMA.columns+where+table_name+=+'ps'";
$apiUrl = "https://exoplanetarchive.ipac.caltech.edu/TAP/sync?query=" . $query . "&format=json";

$response = @file_get_contents($apiUrl);
if (!$response) { die("FEHLER: API konnte nicht erreicht werden."); }

$json_start = strpos($response, '[');
if ($json_start === false) { die("Keine gültigen JSON-Daten gefunden."); }
$json_string = substr($response, $json_start);
$columns = json_decode($json_string, true);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Exoplaneten-Datenbank Spalten-Finder</title>
    <style> /* ... (Styles wie im exoplanet_finder.php) ... */ </style>
</head>
<body>
    <div class="container">
        <h1>Alle verfügbaren Datenfelder für Exoplaneten</h1>
        <p>Dies ist eine Liste aller Spalten, die du in deinen Abfragen verwenden kannst.</p>
        <table>
            <thead><tr><th>Spaltenname (für Query)</th><th>Beschreibung</th></tr></thead>
            <tbody>
                <?php foreach ($columns as $column): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($column['column_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($column['description']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>