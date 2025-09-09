# NASA API Dashboard

Herzlich willkommen zum NASA API Dashboard! Dieses Projekt ist eine Sammlung von PHP-Skripten, die eine Web-Oberfläche zur Interaktion mit verschiedenen faszinierenden APIs der NASA bereitstellen.

Mit diesem Dashboard kannst du Daten und Bilder von Mars-Rovern, das tägliche Astronomiebild (APOD), Wetterdaten vom Mars, nahe Exoplaneten und vieles mehr abrufen, speichern und visualisieren. Das Projekt ist modular aufgebaut und nutzt eine lokale MySQL-Datenbank, um die gesammelten Daten dauerhaft zu speichern. Du kannst unmengen an Daten von der Nasa beziehen!

---

##  Screenshots

Hier sind einige Ansichten des Dashboards, um dir einen Eindruck zu verschaffen:

### Das Haupt-Dashboard
Die zentrale Anlaufstelle mit einer Übersicht über die neuesten Daten und Links zu allen Galerien und Werkzeugen.

<img width="863" height="861" alt="dashboard1" src="https://github.com/user-attachments/assets/ff3e8cd9-26dd-4f40-8756-37353526027e" />


### Mars Rover Galerie
Die interaktive Galerie aller heruntergeladenen Bilder der Mars-Rover, gruppiert nach Kameras.

<img width="1508" height="901" alt="Marsrover" src="https://github.com/user-attachments/assets/c7c384c0-f7f6-4e80-9aa8-0927b149c890" />



### Exoplaneten-Galerie
Eine detaillierte Ansicht der Exoplaneten in unserer kosmischen Nachbarschaft mit Visualisierungen zu Größe und Temperatur.

<img width="1298" height="628" alt="exoplanetengallery" src="https://github.com/user-attachments/assets/71cf7cd9-2942-497d-8aca-590f9be84709" />

### Lade die Gesamte daten der MARS EXPEDITION herunter!
Hier siehst du den fortschritt deines Downlaods
<img width="1142" height="507" alt="Livt-fortschritt-mars-rover-download" src="https://github.com/user-attachments/assets/1fdcfbae-1485-4c71-94be-a5521d982fd6" />

### Der Admin bereich der Seite um Daten zu laden und aktualisieren.

<img width="974" height="863" alt="dashboardADMIN" src="https://github.com/user-attachments/assets/10fad6f2-f4fe-4a80-b0f7-a1383e30454e" />


---

## Features

* **Zentrale Konfiguration:** Eine `connect.php`-Datei für alle Datenbank-Zugangsdaten und den API-Schlüssel.
* **Modulare Downloader:** Eigene Skripte für jede Datenquelle (APOD, Mars Rover, Asteroiden, Exoplaneten).
* **Intelligentes Caching:** Die Downloader prüfen auf Duplikate und laden nur neue Daten herunter.
* **Interaktive Galerien:** Schön gestaltete und responsive Galerien für Mars-Bilder, APODs und Exoplaneten.
* **Daten-Visualisierungen:**
    * Größenvergleich von Exoplaneten mit der Erde und ihrem Heimatstern.
    * Grafische Darstellung der Entfernung von Asteroiden in Mond-Distanzen.
    * Farbliche Darstellung von Sternen basierend auf Spektraltyp oder Temperatur.
* **Live-Werkzeuge:** Tools wie der EPIC Zeit-Explorer oder die NASA Bildersuche, die live mit den APIs interagieren.
* **Hintergrund-Prozesse:** Ein "Live-Monitor" zur Beobachtung von langen Download-Prozessen, die im Terminal laufen.

---

## Installation & Einrichtung

### ### 1. Voraussetzungen
* Eine lokale Webserver-Umgebung wie XAMPP.
* PHP und MySQL.
* Git (optional, für das Klonen).

### ### 2. Projekt herunterladen
Lade das Projekt als ZIP-Datei von GitHub herunter und entpacke es in dein `htdocs`-Verzeichnis oder klone das Repository:
```bash
git clone https://github.com/GreyRatHacker/NasaAPIDashboard.git
