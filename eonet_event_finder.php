<?php
// eonet_event_finder.php

// Diese API benötigt keinen Key für einfache Abfragen
$apiUrl = "https://eonet.gsfc.nasa.gov/api/v3/events?status=open&limit=10";
$response = @file_get_contents($apiUrl);
$data = json_decode($response, true);

echo "<h1>Die 10 letzten aktiven Naturereignisse (EONET)</h1>";
if (!empty($data['events'])) {
    echo "<ul>";
    foreach ($data['events'] as $event) {
        $title = $event['title'];
        $category = $event['categories'][0]['title'];
        $coords = $event['geometry'][0]['coordinates'];
        // Koordinate ist oft [Längengrad, Breitengrad]
        echo "<li><strong>{$title}</strong> ({$category})<br>Koordinaten: Längengrad {$coords[0]}, Breitengrad {$coords[1]}</li>";
    }
    echo "</ul>";
}
?>