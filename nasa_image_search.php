<?php
// nasa_image_search.php v2.1 (Mit Paginierung)

$searchTerm = '';
$results = [];
$totalHits = 0;
$totalPages = 0;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

// Prüfe, ob das Formular mit einem Suchbegriff abgeschickt wurde
if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $searchTerm = trim($_GET['query']);
    // NEU: Füge den 'page'-Parameter zur API-URL hinzu
    $apiUrl = "https://images-api.nasa.gov/search?q=" . urlencode($searchTerm) . "&media_type=image&page={$currentPage}";
    
    $response = @file_get_contents($apiUrl);
    $data = json_decode($response, true);
    
    if (!empty($data['collection']['items'])) {
        $results = $data['collection']['items'];
        // NEU: Lese die Gesamtanzahl der Treffer aus den Metadaten
        $totalHits = $data['collection']['metadata']['total_hits'] ?? 0;
        $totalPages = ceil($totalHits / 100); // Die API liefert 100 Ergebnisse pro Seite
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>NASA Bildersuche</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; margin: 2em; }
        .container { max-width: 1200px; margin: auto; }
        .search-form { text-align: center; margin-bottom: 30px; }
        .search-form input { width: 300px; padding: 10px; font-size: 1em; }
        .search-form button { padding: 10px 15px; font-size: 1em; cursor: pointer; }
        .results-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .result-item { border: 1px solid #ddd; padding: 10px; text-align: center; }
        .result-item img { width: 100%; height: 150px; object-fit: cover; }
        .result-item p { font-size: 0.9em; height: 3.6em; overflow: hidden; } /* Begrenzt Titel auf 3 Zeilen */
        /* NEU: Styles für Paginierung */
        .pagination { text-align: center; margin: 30px 0; }
        .pagination a { background: #0b3d91; color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 4px; margin: 0 10px; }
        .pagination a:hover { background: #fc3d21; }
        .pagination .page-info { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>NASA Medien-Bibliothek durchsuchen</h1>
        
        <form action="nasa_image_search.php" method="GET" class="search-form">
            <input type="text" name="query" placeholder="z.B. James Webb, Apollo 11, Mars..." value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit">Suchen</button>
        </form>

        <?php if (!empty($searchTerm)): ?>
            <h2>Suchergebnisse für '<?php echo htmlspecialchars($searchTerm); ?>' (Seite <?php echo $currentPage; ?> von <?php echo $totalPages; ?>)</h2>
            <div class="results-grid">
                <?php foreach ($results as $item): ?>
                    <div class="result-item">
                        <a href="<?php echo $item['links'][0]['href']; ?>" target="_blank">
                           <img src="<?php echo $item['links'][0]['href']; ?>" alt="<?php echo htmlspecialchars($item['data'][0]['title']); ?>" loading="lazy">
                        </a>
                        <p><?php echo htmlspecialchars($item['data'][0]['title']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <nav class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?query=<?php echo urlencode($searchTerm); ?>&page=<?php echo ($currentPage - 1); ?>">&laquo; Vorherige</a>
                <?php endif; ?>

                <span class="page-info">Seite <?php echo $currentPage; ?> von <?php echo $totalPages; ?></span>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?query=<?php echo urlencode($searchTerm); ?>&page=<?php echo ($currentPage + 1); ?>">Nächste &raquo;</a>
                <?php endif; ?>
            </nav>
            
        <?php endif; ?>
    </div>
</body>
</html>