loader<?php
// downloader.php

// ##################################################################
// # KONFIGURATION
// ##################################################################

require_once 'connect.php';

// --- ZIEL-PARAMETER ---
// Welchen Rover und welchen Mars-Tag (Sol) wollen wir abfragen?
// Ändere diese Werte, um Fotos von anderen Tagen oder Rovern zu laden.
$targetRover = 'perseverance'; // 'perseverance', 'curiosity', 'opportunity', 'spirit'
$targetSol = 1247;              // Beispiel: Sol 1250

// Hauptverzeichnis für die Bilder (muss schreibbar sein!)
$baseImageDir = 'images/';

// ##################################################################
// # SKRIPT-LOGIK (ab hier nichts mehr ändern)
// ##################################################################


echo "✅ Erfolgreich mit der Datenbank verbunden.\n";

// 2. NASA API abfragen
$apiUrl = "https://api.nasa.gov/mars-photos/api/v1/rovers/{$targetRover}/photos?sol={$targetSol}&api_key={$apiKey}";
echo "➡️ Frage API für Rover '{$targetRover}' an Sol {$targetSol} ab...\n";

$response = file_get_contents($apiUrl);
if ($response === FALSE) {
    die("FEHLER: Konnte die NASA API nicht abfragen.");
}
$data = json_decode($response, true);

if (empty($data['photos'])) {
    die("ℹ️ Für diesen Tag wurden keine Fotos gefunden.\n");
}

$photos = $data['photos'];
$totalPhotos = count($photos);
echo "✅ {$totalPhotos} Fotos von der API erhalten. Beginne Verarbeitung...\n\n";

// 3. Jedes Foto verarbeiten (prüfen, herunterladen, speichern)
$newPhotosCount = 0;
$skippedPhotosCount = 0;

foreach ($photos as $photo) {
    $photoId = $photo['id'];

    // PRÜFEN: Ist dieses Foto bereits in unserer Datenbank?
    $stmt = $mysqli->prepare("SELECT id FROM mars_photos WHERE id = ?");
    $stmt->bind_param("i", $photoId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Ja, existiert bereits. Überspringen.
        // echo " überspringe Foto {$photoId} (bereits vorhanden).\n";
        $skippedPhotosCount++;
        continue;
    }

    // NEUES FOTO: Verarbeiten
    echo "Processing new photo ID: {$photoId} ...\n";

    // Daten aus der API extrahieren
    $roverName = $photo['rover']['name'];
    $cameraName = $photo['camera']['name'];
    $cameraFullName = $photo['camera']['full_name'];
    $imgSrcNasa = $photo['img_src'];
    $sol = $photo['sol'];
    $earthDate = $photo['earth_date'];

    // Lokalen Speicherpfad definieren
    $localDir = $baseImageDir . strtolower($roverName) . '/' . strtolower($cameraName) . '/';
    $localPath = $localDir . $photoId . '.jpg';

    // Ordner erstellen, falls er nicht existiert
    if (!is_dir($localDir)) {
        if (!mkdir($localDir, 0777, true)) {
            echo " ❌ FEHLER: Konnte den Ordner nicht erstellen: {$localDir}\n";
            continue; // Nächstes Foto
        }
    }

    // Bild herunterladen
    $imageData = file_get_contents($imgSrcNasa);
    if ($imageData === false) {
        echo " ❌ FEHLER: Konnte das Bild nicht herunterladen: {$imgSrcNasa}\n";
        continue; // Nächstes Foto
    }

    // Bild lokal speichern
    if (file_put_contents($localPath, $imageData) === false) {
        echo " ❌ FEHLER: Konnte das Bild nicht lokal speichern unter: {$localPath}\n";
        continue; // Nächstes Foto
    }
    echo " ✔️ Bild heruntergeladen und gespeichert: {$localPath}\n";
    
    // Metadaten in die Datenbank einfügen
    $insertStmt = $mysqli->prepare(
        "INSERT INTO mars_photos (id, rover_name, camera_name, camera_full_name, img_src_nasa, local_path, sol, earth_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $insertStmt->bind_param("isssssis", $photoId, $roverName, $cameraName, $cameraFullName, $imgSrcNasa, $localPath, $sol, $earthDate);
    
    if ($insertStmt->execute()) {
        echo " ✔️ Metadaten in die Datenbank geschrieben.\n\n";
        $newPhotosCount++;
    } else {
        echo " ❌ FEHLER: Konnte Metadaten nicht in die DB schreiben: " . $insertStmt->error . "\n\n";
    }
    $insertStmt->close();
}
$stmt->close();

// 4. Zusammenfassung ausgeben
echo "--------------------------------------------------\n";
echo "🎉 Verarbeitung abgeschlossen!\n";
echo "Neu heruntergeladen: {$newPhotosCount}\n";
echo "Übersprungen (Duplikate): {$skippedPhotosCount}\n";
echo "--------------------------------------------------\n";

// 5. Verbindung schließen
$mysqli->close();

?>