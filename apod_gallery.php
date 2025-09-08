<?php
// apod_gallery.php (korrigiert)

// Lädt alle Konfigurationen und stellt die DB-Verbindung her
require_once 'connect.php';

// --- Paginierungs-Logik ---
$itemsPerPage = 9;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

$totalResult = $mysqli->query("SELECT COUNT(*) as total FROM apod");
$totalItems = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;

// --- Datenabfrage für die aktuelle Seite ---
$sql = "SELECT * FROM apod ORDER BY date DESC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
// HIER WAR DER FEHLER, JETZT KORRIGIERT:
$stmt->bind_param("ii", $itemsPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archiv: Bild des Tages</title>
    <style>
        :root { --dark-bg: #101418; --card-bg: #1a2027; --light-text: #e0e0e0; --accent-color: #fc3d21; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: var(--dark-bg); color: var(--light-text); margin: 0; padding: 1em; }
        .container { max-width: 1200px; margin: auto; }
        h1 { text-align: center; color: #fff; }
        a { color: var(--accent-color); text-decoration: none; font-weight: bold; }
        .apod-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-top: 20px; }
        .apod-card { background: var(--card-bg); border: 1px solid #333a45; border-radius: 8px; padding: 15px; display: flex; flex-direction: column; }
        .apod-card h3 { margin-top: 0; font-size: 1.1em; color: var(--light-text); }
        .apod-card img { width: 100%; height: auto; border-radius: 4px; margin-bottom: 10px; }
        .apod-explanation { font-size: 0.9em; flex-grow: 1; color: #a0aec0; }
        .pagination { text-align: center; margin: 30px 0; }
        .pagination a, .pagination .page-info { color: var(--light-text); margin: 0 10px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Archiv: Bilder des Tages</h1>
        <p style="text-align: center;"><a href="index.php">&laquo; Zurück zum Feed</a></p>
        <div class="apod-grid">
            <?php while ($apod = $result->fetch_assoc()): ?>
                <div class="apod-card">
                    <h3><?php echo htmlspecialchars($apod['title']); ?> (<?php echo date("d.m.Y", strtotime($apod['date'])); ?>)</h3>
                    <?php if ($apod['media_type'] === 'image' && !empty($apod['local_path'])): ?>
                        <a href="<?php echo htmlspecialchars($apod['local_path']); ?>" target="_blank"><img src="<?php echo htmlspecialchars($apod['local_path']); ?>" loading="lazy"></a>
                    <?php else: ?>
                        <p>Video/Anderer Medientyp: <a href="<?php echo htmlspecialchars($apod['nasa_url']); ?>" target="_blank">Hier ansehen</a></p>
                    <?php endif; ?>
                    <p class="apod-explanation"><?php echo htmlspecialchars($apod['explanation']); ?></p>
                </div>
            <?php endwhile; ?>
        </div>
        <nav class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="?page=<?php echo ($currentPage - 1); ?>">&laquo; Vorherige</a>
            <?php endif; ?>
            <span class="page-info">Seite <?php echo $currentPage; ?> von <?php echo $totalPages; ?></span>
            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?php echo ($currentPage + 1); ?>">Nächste &raquo;</a>
            <?php endif; ?>
        </nav>
    </div>
</body>
</html>
<?php $mysqli->close(); ?>