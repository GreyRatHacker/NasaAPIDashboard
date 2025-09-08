<?php
// neo_downloader.php v1.1 - KORRIGIERT

// ##################################################################
// # KONFIGURATION
// ##################################################################
require_once 'connect.php';

$today = date('Y-m-d');
$apiUrl = "https://api.nasa.gov/neo/rest/v1/feed?start_date={$today}&end_date={$today}&api_key={$apiKey}";
echo "➡️ Frage API für erdnahe Objekte von heute ({$today}) ab...\n";

$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

if (empty($data['near_earth_objects'][$today])) {
    die("ℹ️ Für heute wurden keine erdnahen Objekte gefunden.\n");
}

$neos = $data['near_earth_objects'][$today];
$totalNeos = count($neos);
echo "✅ {$totalNeos} Objekte von der API erhalten. Beginne Verarbeitung...\n\n";

$processedCount = 0;
foreach ($neos as $neo) {
    $id = $neo['neo_reference_id'];
    $name = $neo['name'];
    $is_hazardous = (int)$neo['is_potentially_hazardous_asteroid'];
    $min_diameter = $neo['estimated_diameter']['meters']['estimated_diameter_min'];
    $max_diameter = $neo['estimated_diameter']['meters']['estimated_diameter_max'];
    $nasa_jpl_url = $neo['nasa_jpl_url'];
    $approach = $neo['close_approach_data'][0];
    $miss_distance = $approach['miss_distance']['kilometers'];

    // ##### FINALE KORREKTUR HIER #####
    // 1. Hole das Datum als Text von der API (z.B. "2025-Sep-08 13:54")
    $approach_date_from_api = $approach['close_approach_date_full'];
    // 2. Wandle diesen Text in einen PHP-Zeitstempel um
    $timestamp = strtotime($approach_date_from_api);
    // 3. Formatiere den Zeitstempel in das von MySQL bevorzugte Format "YYYY-MM-DD HH:MM:SS"
    $approach_date_for_mysql = date('Y-m-d H:i:s', $timestamp);
    // ##### ENDE DER KORREKTUR #####

    $stmt = $mysqli->prepare(
        "INSERT INTO asteroids (neo_reference_id, name, is_hazardous, diameter_min_m, diameter_max_m, close_approach_date, miss_distance_km, nasa_jpl_url) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
         name = VALUES(name), is_hazardous = VALUES(is_hazardous), diameter_min_m = VALUES(diameter_min_m), diameter_max_m = VALUES(diameter_max_m), 
         close_approach_date = VALUES(close_approach_date), miss_distance_km = VALUES(miss_distance_km), nasa_jpl_url = VALUES(nasa_jpl_url)"
    );

    // Wir übergeben jetzt die sauber formatierte Datumsvariable an die Datenbank
    $stmt->bind_param("ssiddsds", $id, $name, $is_hazardous, $min_diameter, $max_diameter, $approach_date_for_mysql, $miss_distance, $nasa_jpl_url);

    if ($stmt->execute()) {
        echo " ✔️ Objekt '{$name}' (ID: {$id}) in Datenbank gespeichert/aktualisiert.\n";
        $processedCount++;
    } else {
        echo " ❌ FEHLER beim Speichern von Objekt {$id}: " . $stmt->error . "\n";
    }
    $stmt->close();
}

echo "--------------------------------------------------\n";
echo "🎉 Verarbeitung abgeschlossen! {$processedCount} von {$totalNeos} Objekten verarbeitet.\n";
echo "--------------------------------------------------\n";

$mysqli->close();
?>