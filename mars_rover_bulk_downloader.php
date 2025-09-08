<?php
// mars_rover_bulk_downloader.php v2.1 (Vollständig)
set_time_limit(0);
// Lädt die zentrale Konfiguration und stellt die DB-Verbindung her
require_once 'connect.php';

// ##################################################################
// # KONFIGURATION
// ##################################################################

// Für welchen Rover sollen alle Bilder heruntergeladen werden?
// Optionen: 'perseverance', 'curiosity', 'opportunity', 'spirit'
$targetRover = 'perseverance';

// Hauptverzeichnis für die Bilder (muss schreibbar sein!)
$baseImageDir = 'images/';
// Der Name unserer Log-Datei für die Live-Ansicht
$logFile = 'mars_progress.log';

// ##################################################################
// # HELFER-FUNKTION
// ##################################################################
/**
 * Schreibt eine Nachricht ins Terminal UND in die Log-Datei.
 * @param string $message Die Nachricht.
 * @param string $logFile Der Pfad zur Log-Datei.
 * @param bool $clearFile Ob die Datei vorher geleert werden soll (nur beim Start).
 */
function logProgress($message, $logFile, $clearFile = false) {
    echo $message . "\n"; // Ausgabe im Terminal
    if ($clearFile) {
        // Leert die Datei und schreibt die erste Nachricht
        file_put_contents($logFile, $message . "\n");
    } else {
        // Hängt neue Nachrichten an die Datei an
        file_put_contents($logFile, $message . "\n", FILE_APPEND);
    }
}

// ##################################################################
// # SKRIPT-LOGIK
// ##################################################################

$header = "🚀 Mars Rover Bulk Downloader gestartet für: " . strtoupper($targetRover);
logProgress("===========================================================", $logFile, true);
logProgress($header, $logFile);
logProgress("===========================================================", $logFile);

// 1. Mission Manifest abfragen, um den letzten Sol zu finden
logProgress("➡️ Frage Mission-Manifest an, um den letzten Sol zu finden...", $logFile);
$manifestUrl = "https://api.nasa.gov/mars-photos/api/v1/manifests/{$targetRover}?api_key={$apiKey}";
$manifestResponse = @file_get_contents($manifestUrl);

if (!$manifestResponse) {
    die(logProgress("❌ FEHLER: Konnte das Mission-Manifest nicht abrufen. Bitte überprüfe den Rover-Namen und deinen API-Schlüssel.", $logFile));
}
$manifestData = json_decode($manifestResponse, true);
$latestSol = $manifestData['photo_manifest']['max_sol'] ?? 0;

if ($latestSol == 0) {
    die(logProgress("❌ FEHLER: Konnte den letzten Sol aus dem Manifest nicht auslesen.", $logFile));
}
logProgress("✅ Letzter verfügbarer Sol ist: {$latestSol}. Starte den Countdown...\n", $logFile);


// 2. Die große Rückwärts-Schleife von letzten Sol bis Sol 1
for ($sol = $latestSol; $sol >= 1; $sol--) {
    logProgress("--- Bearbeite Sol {$sol} ---", $logFile);

    $apiUrl = "https://api.nasa.gov/mars-photos/api/v1/rovers/{$targetRover}/photos?sol={$sol}&api_key={$apiKey}";
    $response = @file_get_contents($apiUrl);
    if (!$response) {
        logProgress(" ⚠️  Warnung: API für Sol {$sol} nicht erreichbar. Überspringe...", $logFile);
        sleep(1);
        continue;
    }
    $data = json_decode($response, true);

    if (empty($data['photos'])) {
        logProgress(" ℹ️  Keine Fotos für Sol {$sol} gefunden. Überspringe...", $logFile);
        continue;
    }

    $photos = $data['photos'];
    logProgress(" ✅ " . count($photos) . " Fotos von der API erhalten. Beginne Überprüfung...", $logFile);
    
    $newPhotosCount = 0;
    $skippedPhotosCount = 0;

    // Jedes einzelne Foto dieses Sols verarbeiten
    foreach ($photos as $photo) {
        $photoId = $photo['id'];

        $stmt = $mysqli->prepare("SELECT id FROM mars_photos WHERE id = ?");
        $stmt->bind_param("i", $photoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $skippedPhotosCount++;
            continue;
        }

        $roverName = $photo['rover']['name'];
        $cameraName = $photo['camera']['name'];
        $cameraFullName = $photo['camera']['full_name'];
        $imgSrcNasa = $photo['img_src'];
        $earthDate = $photo['earth_date'];

        $localDir = $baseImageDir . strtolower($roverName) . '/' . strtolower($cameraName) . '/';
        $localPath = $localDir . $photoId . '.jpg';

        if (!is_dir($localDir)) {
            if (!mkdir($localDir, 0777, true)) {
                logProgress(" ❌ FEHLER: Konnte Ordner nicht erstellen: {$localDir}", $logFile);
                continue;
            }
        }

        $imageData = @file_get_contents($imgSrcNasa);
        if ($imageData === false) {
            logProgress(" ❌ FEHLER: Bild-Download fehlgeschlagen: {$imgSrcNasa}", $logFile);
            continue;
        }

        if (file_put_contents($localPath, $imageData) === false) {
            logProgress(" ❌ FEHLER: Bild-Speicherung fehlgeschlagen: {$localPath}", $logFile);
            continue;
        }
        
        $insertStmt = $mysqli->prepare("INSERT INTO mars_photos (id, rover_name, camera_name, camera_full_name, img_src_nasa, local_path, sol, earth_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("isssssis", $photoId, $roverName, $cameraName, $cameraFullName, $imgSrcNasa, $localPath, $sol, $earthDate);
        
        if ($insertStmt->execute()) {
            $newPhotosCount++;
        } else {
            logProgress(" ❌ FEHLER beim DB-Schreiben für Foto-ID {$photoId}: " . $insertStmt->error, $logFile);
        }
        $insertStmt->close();
    }
    
    logProgress(" ℹ️  Zusammenfassung für Sol {$sol}: {$newPhotosCount} neu, {$skippedPhotosCount} übersprungen.\n", $logFile);
    sleep(1);
}

logProgress("===========================================================", $logFile);
logProgress("🎉 Alle Sols verarbeitet! Bulk-Download abgeschlossen!", $logFile);
logProgress("===========================================================", $logFile);
$mysqli->close();
?>