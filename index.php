<?php
// index.php v11.1 (Final - Mit Admin-Bereich)
require_once 'connect.php';

// --- HELFER-FUNKTIONEN ---
function getTempClassification($temp_k)
{
    if ($temp_k === null) return ['Unbekannt', 'temp-unknown'];
    if ($temp_k > 373) return ['Heiß', 'temp-hot'];
    if ($temp_k >= 273 && $temp_k <= 373) return ['Warm', 'temp-warm'];
    return ['Kalt', 'temp-cold'];
}
function getStarColor($spectralType)
{
    if (empty($spectralType)) return '#FFFFFF';
    $type = strtoupper($spectralType[0]);
    switch ($type) {
        case 'O':
            return '#9bb0ff';
        case 'B':
            return '#aabfff';
        case 'A':
            return '#cad7ff';
        case 'F':
            return '#f8f7ff';
        case 'G':
            return '#fff4ea';
        case 'K':
            return '#ffd2a1';
        case 'M':
            return '#ffb56c';
        default:
            return '#FFFFFF';
    }
}
function getStarColorByTemp($temp_k)
{
    if ($temp_k === null) return '#FFFFFF';
    if ($temp_k >= 10000) return '#aabfff';
    if ($temp_k >= 7500) return '#cad7ff';
    if ($temp_k >= 6000) return '#f8f7ff';
    if ($temp_k >= 5200) return '#fff4ea';
    if ($temp_k >= 3700) return '#ffd2a1';
    return '#ffb56c';
}
function fetchUrlWithCurl($url)
{
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_SSL_VERIFYPEER => 1]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function getNasaVideoUrl($searchTerm)
{
    $apiUrl = "https://images-api.nasa.gov/search?q=" . urlencode($searchTerm) . "&media_type=video";
    $response = fetchUrlWithCurl($apiUrl);
    $data = json_decode($response, true);
    if (!empty($data['collection']['items'])) {
        $collectionUrl = $data['collection']['items'][0]['href'];
        $collectionData = json_decode(fetchUrlWithCurl($collectionUrl), true);
        foreach ($collectionData as $fileUrl) {
            if (stripos($fileUrl, '.mp4') !== false && stripos($fileUrl, 'mobile.mp4') === false) {
                return $fileUrl;
            }
        }
    }
    return null;
}

// --- DATEN FÜR DAS DASHBOARD ABRUFEN ---
$apod_post = $mysqli->query("SELECT * FROM apod ORDER BY date DESC LIMIT 1")->fetch_assoc();
$mars_post_result = $mysqli->query("SELECT * FROM mars_photos WHERE rover_name = 'perseverance' AND camera_name = 'navcam_left' ORDER BY sol DESC, id DESC LIMIT 1 OFFSET 1");
$mars_post = $mars_post_result ? $mars_post_result->fetch_assoc() : null;
if (!$mars_post) {
    $mars_post = $mysqli->query("SELECT * FROM mars_photos ORDER BY sol DESC, id DESC LIMIT 1")->fetch_assoc();
}
$exo_posts_result = $mysqli->query("SELECT * FROM exoplanets_nearby ORDER BY sy_dist ASC LIMIT 3");
$today = date('Y-m-d');
$asteroid_posts_result = $mysqli->query("SELECT * FROM asteroids WHERE DATE(close_approach_date) >= '{$today}' ORDER BY close_approach_date ASC LIMIT 4");
$mars_weather_response = fetchUrlWithCurl("https://api.nasa.gov/insight_weather/?api_key={$apiKey}&feedtype=json&ver=1.0");
$weather_data = json_decode($mars_weather_response, true);
$latest_sol_key = null;
if (isset($weather_data['sol_keys']) && !empty($weather_data['sol_keys'])) {
    $latest_sol_key = end($weather_data['sol_keys']);
}
$weather_post = $latest_sol_key ? $weather_data[$latest_sol_key] : null;
$video_url = $apod_post ? getNasaVideoUrl($apod_post['title']) : null;
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NASA Dashboard</title>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <h1 data-aos="fade-down">🚀 NASA Dashboard</h1>
        <div class="main-grid">

            <?php if ($apod_post): ?>
                <div class="card" data-aos="fade-up">
                    <h3><?php echo htmlspecialchars($apod_post['title']); ?></h3>
                    <img src="<?php echo htmlspecialchars($apod_post['local_path']); ?>" alt="APOD">
                    <p><?php echo mb_strimwidth(htmlspecialchars($apod_post['explanation']), 0, 200, "..."); ?></p>
                    <a href="apod_gallery.php" class="cta-button">Mehr im Archiv &raquo;</a>
                </div>
            <?php endif; ?>

            <?php if ($video_url): ?>
                <div class="card" data-aos="fade-up" data-aos-delay="100">
                    <h3>Video zum Thema</h3>
                    <div class="video-container">
                        <video controls muted loop playsinline poster="<?php echo htmlspecialchars($apod_post['local_path']); ?>">
                            <source src="<?php echo htmlspecialchars($video_url); ?>" type="video/mp4">
                            Dein Browser unterstützt das Video-Tag nicht.
                        </video>
                    </div>
                    <p>Ein thematisch passendes Video zum Bild des Tages.</p>
                </div>
            <?php endif; ?>

            <?php if ($mars_post): ?>
                <div class="card" data-aos="fade-up" data-aos-delay="200">
                    <h3>Mars Rover Vorschau</h3>
                    <img src="<?php echo htmlspecialchars($mars_post['local_path']); ?>" alt="Mars Rover Bild">
                    <p>Bild von <?php echo htmlspecialchars($mars_post['rover_name']); ?> (Sol <?php echo htmlspecialchars($mars_post['sol']); ?>) als Vorschau.</p>
                    <a href="gallery.php" class="cta-button">Zur interaktiven Rover-Galerie &raquo;</a>
                </div>
            <?php endif; ?>

            <div class="card" data-aos="fade-up" data-aos-delay="100">
                <h3>Letzter Mars-Wetterbericht</h3>
                <?php if ($weather_post): ?>
                    <p style="text-align:center; color: #a0aec0; margin-top:0;">Sol <?php echo $latest_sol_key; ?> (<?php echo date("d.m.Y", strtotime($weather_post['Last_UTC'])); ?>)</p>
                    <div class="weather-grid">
                        <div class="grid-item">
                            <div class="label">Max. Temp.</div>
                            <div class="value <?php echo getTempClassification($weather_post['AT']['mx'])[1]; ?>"><?php echo round($weather_post['AT']['mx']); ?>°C</div>
                        </div>
                        <div class="grid-item">
                            <div class="label">Min. Temp.</div>
                            <div class="value temp-cold"><?php echo round($weather_post['AT']['mn']); ?>°C</div>
                        </div>
                        <div class="grid-item">
                            <div class="label">Wind</div>
                            <div class="value"><?php echo round($weather_post['HWS']['av'], 1); ?> km/h</div>
                        </div>
                        <div class="grid-item">
                            <div class="label">Luftdruck</div>
                            <div class="value"><?php echo round($weather_post['PRE']['av']); ?> Pa</div>
                        </div>
                    </div>
                <?php else: ?><p>Daten nicht verfügbar.</p><?php endif; ?>
                <a href="mars_weather.php" class="cta-button">Mehr Details &raquo;</a>
            </div>

            <div class="card" data-aos="fade-up">
                <h3>Ein Blick auf unsere Nachbarn</h3>
                <div class="exo-showcase">
                    <?php while ($exo_post = $exo_posts_result->fetch_assoc()): ?>
                        <?php
                        $starColor = getStarColor($exo_post['star_spectral_type']);
                        if ($starColor === '#FFFFFF' && !empty($exo_post['star_temp_k'])) {
                            $starColor = getStarColorByTemp($exo_post['star_temp_k']);
                        }
                        ?>
                        <div class="exo-card">
                            <h4><?php echo htmlspecialchars($exo_post['pl_name']); ?></h4>
                            <div class="visuals">
                                <div class="celestial-body">
                                    <div class="star" style="--star-size: <?php echo max(0.2, $exo_post['star_radius_solar'] ?? 0.5); ?>; --star-color: <?php echo $starColor; ?>;"></div>
                                    <div class="label">Stern</div>
                                </div>
                                <div class="celestial-body">
                                    <div class="planet" style="--size: <?php echo max(0.5, $exo_post['pl_rade'] ?? 1); ?>;"></div>
                                    <div class="label">Planet</div>
                                </div>
                                <div class="celestial-body">
                                    <div class="earth"></div>
                                    <div class="label">Erde</div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <a href="exoplanet_gallery.php" class="cta-button">Vollständige Galerie &raquo;</a>
            </div>

            <div class="card" data-aos="fade-up" data-aos-delay="100">
                <h3>Bevorstehende Annäherungen</h3>
                <ul class="asteroid-list">
                    <?php if ($asteroid_posts_result->num_rows > 0): while ($neo = $asteroid_posts_result->fetch_assoc()): ?>
                            <?php
                            $distance_ld = round($neo['miss_distance_km'] / 384400, 1);
                            $bar_percentage = min(100, ($distance_ld / 20) * 100);
                            ?>
                            <li>
                                <div class="asteroid-info">
                                    <span><strong><?php echo htmlspecialchars($neo['name']); ?></strong></span>
                                    <span class="distance-label"><?php echo number_format($distance_ld, 1, ',', ''); ?>-fache Mond-Distanz</span>
                                </div>
                                <div class="distance-bar-container">
                                    <div class="distance-bar" style="width: <?php echo $bar_percentage; ?>%;"></div>
                                </div>
                            </li>
                        <?php endwhile;
                    else: ?>
                        <p>Keine bevorstehenden Annäherungen in der Datenbank.</p>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <h2 data-aos="fade-up">⚙️ Administration: Daten-Downloader</h2>
        <div class="main-grid">
            <div class="card" data-aos="fade-up">
                <h3>Tägliche Updates</h3>
                <p>Führe diese Skripte einmal täglich aus, um die neuesten Daten zu holen.</p>
                <a href="apod_downloader.php" class="cta-button" target="_blank">APOD für heute laden</a>
                <a href="neo_downloader.php" class="cta-button" target="_blank">Asteroiden für heute laden</a>
            </div>
            <div class="card" data-aos="fade-up" data-aos-delay="100">
                <h3>Große Downloads</h3>
                <p>Diese Skripte laden große Datenmengen. Nur bei Bedarf und am besten im Terminal ausführen!</p>
                <a href="apod_bulk_downloader.php" class="cta-button" target="_blank">APOD-Archiv füllen</a>
                <a href="mars_rover_bulk_downloader.php" class="cta-button" target="_blank">Mars-Rover-Archiv füllen</a>
            </div>
            <div class="card" data-aos="fade-up" data-aos-delay="200">
                <h3>Spezial-Datenbanken</h3>
                <p>Füllt deine kuratierten Datenbanken mit speziellen Datensets.</p>
                <a href="exoplanets_nearby_downloader.php" class="cta-button" target="_blank">Nahe Exoplaneten laden</a>
                <a href="progress_viewer.php" class="cta-button" target="_blank">Live-Download ansehen</a>
            </div>
        </div>

    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            easing: 'ease-in-out'
        });
    </script>

</body>

</html>