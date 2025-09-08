<?php
// exoplanets_nearby_downloader.php v2.2 (holt jetzt auch die Sterntemperatur)

require_once 'connect.php';

$distance_ly = 50;
$distance_pc = $distance_ly / 3.26156;

// Die Abfrage wurde um "st_teff" (Stellar Effective Temperature) erweitert
$query = "select+pl_name,hostname,sy_pnum,discoverymethod,disc_year,pl_rade,sy_dist,pl_masse,pl_dens,pl_eqt,pl_orbper,pl_orbsmax,pl_orbeccen,st_spectype,st_age,pl_ntranspec,st_rad,st_teff+from+ps+where+pl_name+in+(select+distinct+pl_name+from+ps+where+sy_dist+<+{$distance_pc})";

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_error) { die("FEHLER: Datenbankverbindung fehlgeschlagen."); }
echo "✅ Erfolgreich mit der Datenbank verbunden.\n";

$baseUrl = "https://exoplanetarchive.ipac.caltech.edu/TAP/sync?query=";
$apiUrl = $baseUrl . $query . "&format=json";

echo "➡️ Frage erweiterte Daten (inkl. Sterntemperatur) für nahe Planeten an...\n";
$response = @file_get_contents($apiUrl);
$json_start = strpos($response, '[');
if ($json_start === false) { die("Keine gültigen JSON-Daten gefunden."); }
$json_string = substr($response, $json_start);
$planets = json_decode($json_string, true);

if (empty($planets)) { die("ℹ️ Keine Planeten für dieses Subset gefunden.\n"); }
echo "✅ " . count($planets) . " einzigartige Planeten gefunden. Beginne Speicherung/Aktualisierung...\n";

$processedCount = 0;
foreach ($planets as $planet) {
    if (empty($planet['pl_name'])) { continue; }

    $stmt = $mysqli->prepare(
        "INSERT INTO exoplanets_nearby 
        (pl_name, hostname, sy_pnum, discoverymethod, disc_year, pl_rade, sy_dist, mass_earth, density_gcc, temp_kelvin, orbital_period_days, distance_from_star_au, orbit_eccentricity, star_spectral_type, star_age_gyr, atmosphere_signals, star_radius_solar, star_temp_k)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
         hostname=VALUES(hostname), mass_earth=VALUES(mass_earth), density_gcc=VALUES(density_gcc), temp_kelvin=VALUES(temp_kelvin), orbital_period_days=VALUES(orbital_period_days), distance_from_star_au=VALUES(distance_from_star_au), orbit_eccentricity=VALUES(orbit_eccentricity), star_spectral_type=VALUES(star_spectral_type), star_age_gyr=VALUES(star_age_gyr), atmosphere_signals=VALUES(atmosphere_signals), star_radius_solar=VALUES(star_radius_solar), star_temp_k=VALUES(star_temp_k)"
    );
    
    // Der Typen-String wurde um ein 'i' (integer) für die Temperatur erweitert
    $stmt->bind_param("ssisidddidddsdsdid", 
        $planet['pl_name'], $planet['hostname'], $planet['sy_pnum'], $planet['discoverymethod'], 
        $planet['disc_year'], $planet['pl_rade'], $planet['sy_dist'], $planet['pl_masse'], 
        $planet['pl_dens'], $planet['pl_eqt'], $planet['pl_orbper'], $planet['pl_orbsmax'], 
        $planet['pl_orbeccen'], $planet['st_spectype'], $planet['st_age'], $planet['pl_ntranspec'],
        $planet['st_rad'], $planet['st_teff']
    );

    if ($stmt->execute()) { $processedCount++; }
    $stmt->close();
}

echo "🎉 Verarbeitung abgeschlossen! {$processedCount} Planeten aktualisiert.\n";
$mysqli->close();
?>