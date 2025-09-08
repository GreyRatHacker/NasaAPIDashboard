<?php
// connect.php - Zentrale Konfigurations- und Datenbankverbindungsdatei

// --- Datenbank-Zugangsdaten ---
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'nasa_data';

// --- NASA API Key ---
$apiKey = 'DEIN_API_SCHLUESSEL'; // Trage hier deinen echten API-Schlüssel ein

// --- Datenbankverbindung herstellen ---
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

// Verbindung prüfen
if ($mysqli->connect_error) {
    // Gibt eine klare Fehlermeldung aus und beendet das Skript
    die("FEHLER: Verbindung zur Datenbank fehlgeschlagen: " . $mysqli->connect_error);
}

// Setze den Zeichensatz für eine saubere Datenübertragung
$mysqli->set_charset('utf8mb4');
?>