<?php
// mars_weather.php

$apiKey = 'ttp2LK2UapnomkZauaqKEM3YIQy03ATi1ep58wiU'; // Bitte deinen echten Schlüssel eintragen!
$apiUrl = "https://api.nasa.gov/insight_weather/?api_key={$apiKey}&feedtype=json&ver=1.0";

/**
 * Eine robuste Funktion, um Daten von einer URL mit cURL abzurufen.
 */
function fetchUrlWithCurl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) { return false; }
    return $response;
}

$response = fetchUrlWithCurl($apiUrl);
$weather_data = json_decode($response, true);

// Die Daten sind nach "Sol" (Mars-Tagen) geordnet. Wir nehmen den letzten verfügbaren Tag.
$latest_sol_key = null;
if (isset($weather_data['sol_keys']) && !empty($weather_data['sol_keys'])) {
    $latest_sol_key = end($weather_data['sol_keys']);
}

$latest_weather = null;
if ($latest_sol_key && isset($weather_data[$latest_sol_key])) {
    $latest_weather = $weather_data[$latest_sol_key];
}

// Daten für die Anzeige vorbereiten
$sol = $latest_sol_key ?? 'N/A';
$earth_date = isset($latest_weather['Last_UTC']) ? date("d. F Y", strtotime($latest_weather['Last_UTC'])) : 'N/A';
$max_temp = isset($latest_weather['AT']['mx']) ? round($latest_weather['AT']['mx']) : 'N/A';
$min_temp = isset($latest_weather['AT']['mn']) ? round($latest_weather['AT']['mn']) : 'N/A';
$avg_wind = isset($latest_weather['HWS']['av']) ? round($latest_weather['HWS']['av'], 1) : 'N/A';
$pressure = isset($latest_weather['PRE']['av']) ? round($latest_weather['PRE']['av']) : 'N/A';
$season = isset($latest_weather['Season']) ? ucfirst($latest_weather['Season']) : 'N/A';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mars Wetterbericht</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap');
        :root { --dark-bg: #1a2027; --card-bg: #2d3748; --light-text: #e2e8f0; --accent-color: #e53e3e; }
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--dark-bg);
            color: var(--light-text);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background-image: url('https://mars.nasa.gov/system/resources/detail_files/26554_insight-in-the-sun-PIA25270-1600.jpg');
            background-size: cover;
            background-position: center;
        }
        .weather-card {
            background-color: rgba(45, 55, 72, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            width: 350px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .header { text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 1.8em; }
        .header .sol { font-size: 1.2em; color: #a0aec0; }
        .header .date { font-size: 0.9em; color: #718096; }
        .temperature { text-align: center; margin: 20px 0; }
        .temperature .main-temp { font-size: 4em; font-weight: 700; }
        .temperature .min-max { font-size: 1em; color: #a0aec0; }
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .detail-item { background-color: rgba(74, 85, 104, 0.5); padding: 15px; border-radius: 10px; text-align: center; }
        .detail-item .label { font-size: 0.8em; color: #a0aec0; margin-bottom: 5px; }
        .detail-item .value { font-size: 1.2em; font-weight: 700; }
        .footer { text-align: center; margin-top: 20px; font-size: 0.8em; color: #718096; }
    </style>
</head>
<body>
    <div class="weather-card">
        <?php if ($latest_weather): ?>
            <div class="header">
                <h1>Mars Wetter</h1>
                <div class="sol">Sol <?php echo htmlspecialchars($sol); ?></div>
                <div class="date"><?php echo htmlspecialchars($earth_date); ?></div>
            </div>

            <div class="temperature">
                <span class="main-temp"><?php echo htmlspecialchars($max_temp); ?>° C</span>
                <div class="min-max">min: <?php echo htmlspecialchars($min_temp); ?>° C</div>
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <div class="label">💨 Wind (km/h)</div>
                    <div class="value"><?php echo htmlspecialchars($avg_wind); ?></div>
                </div>
                <div class="detail-item">
                    <div class="label">🎈 Luftdruck (Pa)</div>
                    <div class="value"><?php echo htmlspecialchars($pressure); ?></div>
                </div>
            </div>
            <div class="footer">
                Daten vom InSight Lander (Elysium Planitia)<br>Jahreszeit: <?php echo htmlspecialchars($season); ?>
            </div>
        <?php else: ?>
            <div class="header">
                <h1>Mars Wetter</h1>
            </div>
            <p>Leider konnten keine aktuellen Wetterdaten von der NASA API abgerufen werden. Der Dienst ist möglicherweise vorübergehend nicht erreichbar.</p>
        <?php endif; ?>
    </div>
</body>
</html>