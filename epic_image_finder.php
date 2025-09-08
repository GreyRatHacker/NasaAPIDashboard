<?php
// epic_image_finder.php v4.1 (Final - Mit cURL)

$apiKey = 'ttp2LK2UapnomkZauaqKEM3YIQy03ATi1ep58wiU'; // Stelle sicher, dass hier dein echter Schlüssel steht
$images = [];
$selected_date = $_GET['selected_date'] ?? '';

/**
 * FINALE VERSION: Eine robuste Funktion, um Daten von einer URL mit cURL abzurufen.
 * @param string $url Die URL, die aufgerufen werden soll.
 * @return string|false Die Antwort der Seite oder false bei einem Fehler.
 */
function fetchUrlWithCurl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    
    // ##### SICHERHEITSPRÜFUNG IST WIEDER AKTIV #####
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "<p style='color: red;'>cURL Fehler: {$error}</p>";
        return false;
    }
    return $response;
}


if (!empty($selected_date)) {
    $date_obj = new DateTime($selected_date);
    $date_for_api = $date_obj->format('Y-m-d');
    
    $apiUrl = "https://api.nasa.gov/EPIC/api/natural/date/{$date_for_api}?api_key={$apiKey}";
    
    // Wir benutzen jetzt unsere neue, robuste cURL-Funktion
    $response = fetchUrlWithCurl($apiUrl);
    
    if ($response) {
        $images = json_decode($response, true);
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>EPIC Zeit-Explorer</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; margin: 2em; }
        .container { max-width: 1200px; margin: auto; }
        .date-selector { text-align: center; margin-bottom: 30px; background: #f4f4f4; padding: 20px; border-radius: 8px; }
        .date-selector input[type="date"] { padding: 10px; font-size: 1.1em; }
        .date-selector button { padding: 10px 15px; font-size: 1.1em; cursor: pointer; }
        .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
        .image-grid img { width: 100%; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>EPIC Zeit-Explorer</h1>
        <form action="epic_image_finder.php" method="GET" class="date-selector">
            <label for="selected_date">Wähle ein Datum:</label>
            <input type="date" id="selected_date" name="selected_date" value="<?php echo htmlspecialchars($selected_date); ?>">
            <button type="submit">Bilder laden</button>
        </form>

        <?php if (!empty($selected_date)): ?>
            <hr>
            <h2>Bilder vom <?php echo htmlspecialchars($selected_date); ?></h2>
            <?php if (empty($images)): ?>
                <p>Für dieses Datum wurden leider keine Bilder gefunden. (Mögliche Gründe: Datenlücke bei der NASA oder API-Fehler).</p>
            <?php else: ?>
                <p><?php echo count($images); ?> Bilder gefunden.</p>
                <div class="image-grid">
                    <?php foreach ($images as $image):
                        $image_name = $image['image'];
                        $image_date = new DateTime($image['date']);
                        $year = $image_date->format('Y');
                        $month = $image_date->format('m');
                        $day = $image_date->format('d');

                        $imageUrl = "https://epic.gsfc.nasa.gov/archive/natural/{$year}/{$month}/{$day}/png/{$image_name}.png";
                    ?>
                        <a href="<?php echo $imageUrl; ?>" target="_blank">
                            <img src="<?php echo $imageUrl; ?>" title="<?php echo $image['caption']; ?>" loading="lazy">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>