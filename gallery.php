<?php
// gallery.php v3.0 - Kamera-basierte Übersicht mit Modal-Galerie

// ##################################################################
// # KONFIGURATION
// ##################################################################
require_once 'connect.php';


// 2. Aktuellen Sol bestimmen (wie in v2.0)
$currentSol = 0;
if (isset($_GET['sol']) && is_numeric($_GET['sol'])) {
    $currentSol = (int)$_GET['sol'];
} else {
    $result = $mysqli->query("SELECT MAX(sol) AS latest_sol FROM mars_photos");
    if ($result && $row = $result->fetch_assoc()) {
        $currentSol = $row['latest_sol'];
    }
}

// 3. Alle verfügbaren Sols für die Navigation holen (wie in v2.0)
$availableSols = [];
$result = $mysqli->query("SELECT DISTINCT sol FROM mars_photos ORDER BY sol DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $availableSols[] = $row['sol'];
    }
}

// 4. NEUE LOGIK: Alle Fotos für den aktuellen Sol holen und nach Kamera gruppieren
$photosByCamera = [];
if ($currentSol > 0) {
    $sql = "SELECT id, local_path, rover_name, camera_name, camera_full_name, sol, earth_date 
            FROM mars_photos 
            WHERE sol = ?
            ORDER BY id ASC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $currentSol);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Jedes Foto wird dem Array seiner Kamera hinzugefügt
            $photosByCamera[$row['camera_name']][] = $row;
        }
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mars Rover Kameras | Sol <?php echo $currentSol; ?></title>
    <style>
        /* Basis-Styles (leicht angepasst) */
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #121212; color: #e0e0e0; margin: 0; padding: 20px; }
        .container { max-width: 1600px; margin: auto; }
        h1, h2 { color: #d73f27; text-align: center; border-bottom: 2px solid #555; padding-bottom: 10px; margin-bottom: 20px; }
        .sol-nav { text-align: center; padding: 10px 0; margin-bottom: 30px; background-color: #1a1a1a; border-radius: 8px; }
        .sol-nav a { color: #ccc; text-decoration: none; padding: 8px 12px; margin: 0 4px; border-radius: 4px; transition: background-color 0.2s; }
        .sol-nav a:hover { background-color: #555; color: #fff; }
        .sol-nav a.active { background-color: #d73f27; color: #fff; font-weight: bold; }
        
        /* Kamera-Übersicht Grid */
        .camera-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .camera-card { background-color: #222; border: 1px solid #444; border-radius: 8px; overflow: hidden; cursor: pointer; transition: transform 0.2s; }
        .camera-card:hover { transform: scale(1.03); border-color: #d73f27; }
        .camera-card img { width: 100%; height: 250px; object-fit: cover; display: block; }
        .camera-card .caption { padding: 15px; font-size: 1em; text-align: center; }
        .caption p { margin: 5px 0; }
        .caption strong { font-size: 1.1em; }
        .caption .photo-count { color: #aaa; font-size: 0.9em; }

        /* NEU: Styles für das Modal-Pop-up */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.9); z-index: 1000; display: none; align-items: center; justify-content: center; }
        .modal-content { position: relative; width: 95%; height: 95%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .modal-main-image { max-width: 100%; max-height: 80%; object-fit: contain; }
        .modal-thumbnails { width: 80%; text-align: center; margin-top: 15px; overflow-x: auto; white-space: nowrap; padding-bottom: 10px; }
        .modal-thumbnails img { width: 100px; height: 100px; object-fit: cover; margin: 0 5px; cursor: pointer; border: 2px solid #555; border-radius: 4px; transition: border-color 0.2s; }
        .modal-thumbnails img:hover, .modal-thumbnails img.active { border-color: #d73f27; }
        .modal-close, .modal-prev, .modal-next { position: absolute; color: #fff; font-size: 40px; font-weight: bold; cursor: pointer; user-select: none; z-index: 1001; }
        .modal-close { top: 20px; right: 35px; }
        .modal-prev, .modal-next { top: 50%; transform: translateY(-50%); }
        .modal-prev { left: 20px; }
        .modal-next { right: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📸 Mars Rover Kameras</h1>
        <h2>Ansicht für Sol: <?php echo $currentSol; ?></h2>
        
        <nav class="sol-nav">
            <?php foreach ($availableSols as $sol): ?>
                <a href="?sol=<?php echo $sol; ?>" class="<?php echo ($sol == $currentSol) ? 'active' : ''; ?>">Sol <?php echo $sol; ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="camera-grid">
            <?php if (!empty($photosByCamera)): ?>
                <?php foreach ($photosByCamera as $cameraName => $photos): ?>
                    <?php 
                        // Wir nehmen das letzte Bild im Array als "Titelbild"
                        $coverPhoto = end($photos); 
                        $photoCount = count($photos);
                    ?>
                    <div class="camera-card" data-camera="<?php echo $cameraName; ?>">
                        <img src="<?php echo htmlspecialchars($coverPhoto['local_path']); ?>" alt="<?php echo htmlspecialchars($coverPhoto['camera_full_name']); ?>">
                        <div class="caption">
                            <p><strong><?php echo htmlspecialchars($coverPhoto['camera_full_name']); ?></strong></p>
                            <p class="photo-count">(<?php echo $photoCount; ?> Bilder verfügbar)</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Für Sol <?php echo $currentSol; ?> wurden keine Bilder in der Datenbank gefunden.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="modal" class="modal-overlay">
        <span class="modal-close">&times;</span>
        <span class="modal-prev">&#10094;</span>
        <span class="modal-next">&#10095;</span>
        <div class="modal-content">
            <img id="modal-main-image" class="modal-main-image" src="" alt="Vergrößertes Mars-Bild">
            <div id="modal-thumbnails" class="modal-thumbnails"></div>
        </div>
    </div>

    <script>
        // Schritt 1: PHP-Daten in JavaScript verfügbar machen
        const photosByCamera = <?php echo json_encode(array_values($photosByCamera)); ?>;
        const photosByCameraGrouped = photosByCamera.reduce((acc, photos) => {
            if (photos.length > 0) {
                acc[photos[0].camera_name] = photos;
            }
            return acc;
        }, {});


        // Schritt 2: DOM-Elemente des Modals holen
        const modal = document.getElementById('modal');
        const modalMainImage = document.getElementById('modal-main-image');
        const modalThumbnails = document.getElementById('modal-thumbnails');
        
        let currentImageSet = [];
        let currentImageIndex = 0;

        // Schritt 3: Funktion zum Öffnen des Modals
        function openModal(cameraName, initialPhoto) {
            currentImageSet = photosByCameraGrouped[cameraName];
            currentImageIndex = currentImageSet.findIndex(p => p.id === initialPhoto.id);
            
            updateModalContent();
            modal.style.display = 'flex';
        }

        // Schritt 4: Funktion zum Aktualisieren des Modal-Inhalts
        function updateModalContent() {
            const photo = currentImageSet[currentImageIndex];
            modalMainImage.src = photo.local_path;
            
            modalThumbnails.innerHTML = ''; // Alte Thumbnails löschen
            currentImageSet.forEach((thumbPhoto, index) => {
                const thumb = document.createElement('img');
                thumb.src = thumbPhoto.local_path;
                if(index === currentImageIndex) {
                    thumb.className = 'active';
                }
                thumb.onclick = () => {
                    currentImageIndex = index;
                    updateModalContent();
                };
                modalThumbnails.appendChild(thumb);
            });
            // Zum aktiven Thumbnail scrollen
            const activeThumb = modalThumbnails.querySelector('.active');
            if(activeThumb) {
                activeThumb.scrollIntoView({ behavior: 'smooth', inline: 'center' });
            }
        }

        // Schritt 5: Navigationsfunktionen
        function showNextImage() {
            currentImageIndex = (currentImageIndex + 1) % currentImageSet.length;
            updateModalContent();
        }

        function showPrevImage() {
            currentImageIndex = (currentImageIndex - 1 + currentImageSet.length) % currentImageSet.length;
            updateModalContent();
        }

        // Schritt 6: Event Listeners hinzufügen
        document.querySelectorAll('.camera-card').forEach(card => {
            card.addEventListener('click', () => {
                const cameraName = card.dataset.camera;
                const coverPhoto = photosByCameraGrouped[cameraName][photosByCameraGrouped[cameraName].length - 1];
                openModal(cameraName, coverPhoto);
            });
        });

        // Event Listeners für Modal-Steuerung
        document.querySelector('.modal-close').addEventListener('click', () => modal.style.display = 'none');
        document.querySelector('.modal-next').addEventListener('click', showNextImage);
        document.querySelector('.modal-prev').addEventListener('click', showPrevImage);
        
        // Bonus: Mit Tastatur navigieren
        document.addEventListener('keydown', (e) => {
            if (modal.style.display === 'flex') {
                if (e.key === 'ArrowRight') showNextImage();
                if (e.key === 'ArrowLeft') showPrevImage();
                if (e.key === 'Escape') modal.style.display = 'none';
            }
        });
    </script>
</body>
</html>
<?php
$mysqli->close();
?>