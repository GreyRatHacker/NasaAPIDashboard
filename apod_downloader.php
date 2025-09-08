<?php
// apod_downloader.php

// ##################################################################
// # KONFIGURATION
// ##################################################################
require_once 'connect.php';

// Hauptverzeichnis für die APOD-Bilder (muss schreibbar sein!)
$baseImageDir = 'images/apod/';

// ##################################################################
// # SKRIPT-LOGIK
// ##################################################################


echo "✅ Erfolgreich mit der Datenbank verbunden.\n";

// 1. NASA APOD API abfragen (standardmäßig für heute)
$apiUrl = "https://api.nasa.gov/planetary/apod?api_key={$apiKey}";
echo "➡️ Frage APOD-Daten von der NASA API ab...\n";

$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

if (!$data || isset($data['error'])) {
    die("❌ FEHLER: Konnte keine gültigen Daten von der APOD API abrufen. Grund: " . ($data['error']['message'] ?? 'Unbekannt'));
}

echo "✅ Daten erfolgreich erhalten für Datum: {$data['date']}\n";

// 2. Daten extrahieren
$date = $data['date'];
$title = $data['title'];
$explanation = $data['explanation'];
$media_type = $data['media_type'];
$copyright = $data['copyright'] ?? null; // Copyright ist optional
$nasa_url = $data['hdurl'] ?? $data['url']; // Bevorzuge HD-URL, wenn vorhanden
$local_path = null; // Standardmäßig leer lassen

// 3. Wenn es ein Bild ist, herunterladen
if ($media_type === 'image') {
    echo "ℹ️ Medientyp ist 'image'. Beginne Download...\n";
    
    // Ordner erstellen, falls nicht vorhanden
    if (!is_dir($baseImageDir)) {
        mkdir($baseImageDir, 0777, true);
    }
    
    // Dateinamen aus dem Datum generieren (z.B. 2025-09-08.jpg)
    $filename = $date . '.jpg';
    $local_path = $baseImageDir . $filename;

    $imageData = file_get_contents($nasa_url);
    if ($imageData === false) {
        echo " ❌ FEHLER: Konnte das Bild nicht herunterladen von: {$nasa_url}\n";
        $local_path = null; // Download fehlgeschlagen, Pfad zurücksetzen
    } else {
        file_put_contents($local_path, $imageData);
        echo " ✔️ Bild erfolgreich gespeichert unter: {$local_path}\n";
    }
} else {
    echo "ℹ️ Medientyp ist '{$media_type}'. Es wird kein Bild heruntergeladen.\n";
}

// 4. Alle Informationen in die Datenbank speichern (oder aktualisieren)
$stmt = $mysqli->prepare(
    "INSERT INTO apod (date, title, explanation, media_type, nasa_url, local_path, copyright)
     VALUES (?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE 
     title = VALUES(title), explanation = VALUES(explanation), media_type = VALUES(media_type), 
     nasa_url = VALUES(nasa_url), local_path = VALUES(local_path), copyright = VALUES(copyright)"
);

$stmt->bind_param("sssssss", $date, $title, $explanation, $media_type, $nasa_url, $local_path, $copyright);

if ($stmt->execute()) {
    echo "🎉 Datensatz für {$date} erfolgreich in der Datenbank gespeichert/aktualisiert.\n";
} else {
    echo " ❌ FEHLER beim Schreiben in die Datenbank: " . $stmt->error . "\n";
}

$stmt->close();
$mysqli->close();
?>