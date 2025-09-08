<?php
// exoplanet_gallery.php v3.3 (Final)

require_once 'connect.php';

// Lade alle Planeten aus der "nearby" Tabelle, die nächsten zuerst.
$result = $mysqli->query("SELECT * FROM exoplanets_nearby ORDER BY sy_dist ASC");
$planets = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $planets[] = $row;
    }
}

// ##################################################################
// # HELFER-FUNKTIONEN FÜR DIE VISUALISIERUNG
// ##################################################################

function getTempClassification($temp_k)
{
    if ($temp_k === null) return ['Unbekannt', 'temp-unknown'];
    if ($temp_k > 373) return ['Heiß', 'temp-hot'];
    if ($temp_k >= 273 && $temp_k <= 373) return ['Warm', 'temp-warm'];
    return ['Kalt', 'temp-cold'];
}

function getDensityClassification($density)
{
    if ($density === null) return 'Unbekannt';
    if ($density > 3.0) return 'Gesteinsplanet (vermutet)';
    if ($density > 0.5) return 'Gasplanet (vermutet)';
    return 'Gasplanet (sehr geringe Dichte)';
}

function getStarColor($spectralType)
{
    if (empty($spectralType)) return '#FFFFFF'; // Weiß als Standard
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
    if ($temp_k >= 10000) return '#688dffff';
    if ($temp_k >= 7500) return '#afc3ffff';
    if ($temp_k >= 6000) return '#f8f7ff';
    if ($temp_k >= 5200) return '#f8caa0ff';
    if ($temp_k >= 3700) return '#ff9355ff';
    return '#ff5035ff';
}

?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exoplaneten in unserer Nachbarschaft</title>
    <style>
        :root {
            --dark-bg: #101418;
            --card-bg: #1a2027;
            --light-text: #e0e0e0;
            --accent-color: #fc3d21;
            --border-color: #333a45;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            margin: 0;
            background-color: var(--dark-bg);
            color: var(--light-text);
        }

        .container {
            max-width: 1400px;
            margin: 2em auto;
            padding: 1em;
        }

        h1 {
            color: #fff;
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 20px;
        }

        h1 span {
            color: var(--accent-color);
        }

        a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: bold;
        }

        .gallery-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
        }

        .planet-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .planet-card h2 {
            margin: 0 0 5px 0;
            color: var(--light-text);
            font-size: 1.4em;
        }

        .planet-card .hostname {
            color: #8899a6;
            margin-top: 0;
        }

        .visuals {
            display: flex;
            align-items: center;
            justify-content: space-around;
            height: 120px;
            margin: 20px 0;
            padding: 10px;
            background-color: #15191e;
            border-radius: 8px;
            overflow: hidden;
        }

        .celestial-body {
            text-align: center;
        }

        .celestial-body .label {
            font-size: 0.7em;
            color: #8899a6;
        }

        .star,
        .planet,
        .earth {
            border-radius: 50%;
            margin: 0 auto 5px auto;
        }

        .star {
            width: calc(50px * var(--star-size, 1));
            height: calc(50px * var(--star-size, 1));
            max-width: 80px;
            max-height: 80px;
            background-color: var(--star-color, #fff4ea);
        }

        .planet {
            width: calc(15px * var(--size, 1));
            height: calc(15px * var(--size, 1));
            max-width: 60px;
            max-height: 60px;
            background-color: #8899a6;
        }

        .earth {
            width: 15px;
            height: 15px;
            background-color: #6b93d6;
            flex-shrink: 0;
        }

        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }

        .data-point {
            background-color: #252c35;
            padding: 10px;
            border-radius: 6px;
        }

        .data-point .label {
            font-size: 0.8em;
            color: #8899a6;
        }

        .data-point .value {
            font-size: 1.1em;
            font-weight: bold;
            word-wrap: break-word;
        }

        .temp-hot {
            color: #ffadad;
        }

        .temp-warm {
            color: #a0e8ff;
        }

        .temp-cold {
            color: #a4b0ff;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Exoplaneten <span>innerhalb von 50 Lichtjahren</span></h1>
        <p style="text-align: center;"><a href="index.php">&laquo; Zurück zum Dashboard</a></p>
        <div class="gallery-container">
            <?php foreach ($planets as $planet): ?>
                <?php
                $starColor = getStarColor($planet['star_spectral_type']);
                if ($starColor === '#FFFFFF' && !empty($planet['star_temp_k'])) {
                    $starColor = getStarColorByTemp($planet['star_temp_k']);
                }
                ?>
                <div class="planet-card">
                    <div>
                        <h2><?php echo htmlspecialchars($planet['pl_name']); ?></h2>
                        <p class="hostname">Stern: <?php echo htmlspecialchars($planet['hostname']); ?></p>
                    </div>
                    <div class="visuals">
                        <div class="celestial-body">
                            <div class="star" style="--star-size: <?php echo max(0.2, $planet['star_radius_solar'] ?? 0.5); ?>; --star-color: <?php echo $starColor; ?>;"></div>
                            <div class="label">Heimatstern (<?php echo $planet['star_radius_solar'] ?? 'N/A'; ?>x Sonne)</div>
                        </div>
                        <div class="celestial-body">
                            <div class="planet" style="--size: <?php echo max(0.5, $planet['pl_rade'] ?? 1); ?>;"></div>
                            <div class="label">Exoplanet</div>
                        </div>
                        <div class="celestial-body">
                            <div class="earth"></div>
                            <div class="label">Erde</div>
                        </div>
                    </div>
                    <div class="data-grid">
                        <div class="data-point">
                            <div class="label">🌡️ Temperatur</div>
                            <?php $temp = getTempClassification($planet['temp_kelvin']); ?>
                            <div class="value <?php echo $temp[1]; ?>"><?php echo $planet['temp_kelvin'] ? ($planet['temp_kelvin'] - 273) . ' °C' : 'N/A'; ?></div>
                        </div>
                        <div class="data-point">
                            <div class="label">🧱 Typ (via Dichte)</div>
                            <div class="value"><?php echo getDensityClassification($planet['density_gcc']); ?></div>
                        </div>
                        <div class="data-point">
                            <div class="label">📏 Größe (Radius)</div>
                            <div class="value"><?php echo $planet['pl_rade'] ?? 'N/A'; ?> x Erde</div>
                        </div>
                        <div class="data-point">
                            <div class="label">⚖️ Masse</div>
                            <div class="value"><?php echo $planet['mass_earth'] ?? 'N/A'; ?> x Erde</div>
                        </div>
                        <div class="data-point">
                            <div class="label">🗓️ Umlaufzeit (1 Jahr)</div>
                            <div class="value"><?php echo $planet['orbital_period_days'] ? round($planet['orbital_period_days']) . ' Tage' : 'N/A'; ?></div>
                        </div>
                        <div class="data-point">
                            <div class="label">💨 Atmosphäre</div>
                            <div class="value"><?php echo ($planet['atmosphere_signals'] ?? 0) > 0 ? 'Spektren gemessen!' : 'Unbekannt'; ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>
<?php $mysqli->close(); ?>