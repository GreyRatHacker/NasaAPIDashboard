<?php
// apod_bulk_downloader.php - Lädt die letzten 30 APOD-Einträge herunter.
set_time_limit(0);
// ##################################################################
// # KONFIGURATION
// ##################################################################
require_once 'connect.php';

$baseImageDir = 'images/apod/';
$daysToFetch = 100; // Wie viele Tage in die Vergangenheit sollen geprüft werden?


// ##################################################################
// # SKRIPT-LOGIK
// ##################################################################

echo "✅ Erfolgreich mit der Datenbank verbunden.\n";
echo "Starte den Download für die letzten {$daysToFetch} Tage...\n";
echo "===========================================================\n";

for ($i = 0; $i < $daysToFetch; $i++) {
    $dateToFetch = date('Y-m-d', strtotime("-{$i} days"));
    echo "Prüfe Datum: {$dateToFetch}...\n";

    $stmt_check = $mysqli->prepare("SELECT date FROM apod WHERE date = ?");
    $stmt_check->bind_param("s", $dateToFetch);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $stmt_check->close();

    if ($result->num_rows > 0) {
        echo " ⏭️  Übersprungen. Datensatz existiert bereits in der DB.\n\n";
        continue;
    }

    echo " ➡️  Neuer Datensatz. Frage API an...\n";
    $apiUrl = "https://api.nasa.gov/planetary/apod?api_key={$apiKey}&date={$dateToFetch}";
    
    $response = @file_get_contents($apiUrl);
    $data = json_decode($response, true);

    if (!$data || isset($data['error'])) {
        echo " ❌ FEHLER bei der API-Abfrage für {$dateToFetch}. Grund: " . ($data['error']['message'] ?? 'Unbekannt') . "\n\n";
        continue;
    }
    
    $date = $data['date'];
    $title = $data['title'];
    $explanation = $data['explanation'];
    $media_type = $data['media_type'];
    $copyright = $data['copyright'] ?? null;

    // ##### KORREKTUR HIER #####
    // Setze die URL auf NULL, falls weder 'hdurl' noch 'url' existieren.
    $nasa_url = $data['hdurl'] ?? $data['url'] ?? null;
    // ##### ENDE DER KORREKTUR #####
    
    $local_path = null;

    // Nur versuchen herunterzuladen, wenn es ein Bild ist UND eine URL existiert
    if ($media_type === 'image' && !empty($nasa_url)) {
        if (!is_dir($baseImageDir)) {
            mkdir($baseImageDir, 0777, true);
        }
        $filename = $date . '.jpg';
        $local_path = $baseImageDir . $filename;
        
        $imageData = @file_get_contents($nasa_url);
        if ($imageData === false) {
            echo " ❌ FEHLER beim Bild-Download für {$dateToFetch}.\n";
            $local_path = null;
        } else {
            file_put_contents($local_path, $imageData);
            echo " ✔️  Bild gespeichert: {$local_path}\n";
        }
    } else {
        echo " ℹ️  Kein Bild (Typ: {$media_type}) oder keine URL vorhanden.\n";
    }

    $stmt_insert = $mysqli->prepare(
        "INSERT INTO apod (date, title, explanation, media_type, nasa_url, local_path, copyright) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt_insert->bind_param("sssssss", $date, $title, $explanation, $media_type, $nasa_url, $local_path, $copyright);

    if ($stmt_insert->execute()) {
        echo " ✔️  Datensatz in DB gespeichert.\n\n";
    } else {
        echo " ❌ FEHLER beim DB-Schreiben für {$dateToFetch}: " . $stmt_insert->error . "\n\n";
    }
    $stmt_insert->close();

    sleep(1);
}

echo "===========================================================\n";
echo "🎉 Bulk-Download abgeschlossen!\n";
$mysqli->close();
?>