<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map Screenshot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <style>
        html, body, #map {
            width: 860px;
            height: 400px;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .leaflet-control-attribution {
            display: none !important;
        }
    </style>
</head>
<body>
    <div id="map"></div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
        // Initialize map
        const map = L.map('map', {
            zoomControl: false,       // usuwa plus i minus
            dragging: false,          // opcjonalnie zablokuje przeciąganie
            scrollWheelZoom: false,   // opcjonalnie wyłączy zoom scroll
            doubleClickZoom: false    // opcjonalnie wyłączy podwójne kliknięcie
        }).setView([{{ $latitude }}, {{ $longitude }}], 13);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Add marker
        L.marker([{{ $latitude }}, {{ $longitude }}]).addTo(map);
        
        // Signal when map is ready
        // Signal when map and tiles are fully loaded
        map.whenReady(() => {
            let tilesLoaded = 0;
            const totalTiles = [];

            map.eachLayer(layer => {
                if (layer instanceof L.TileLayer) {
                    layer.on('tileload', () => {
                        tilesLoaded++;
                    });
                    layer.on('tileerror', () => {
                        tilesLoaded++;
                    });
                    totalTiles.push(layer);
                }
            });

            const checkLoaded = () => {
                if (tilesLoaded >= totalTiles.length * 1) { // prosta kontrola
                    window.mapReady = true;
                } else {
                    setTimeout(checkLoaded, 100);
                }
            };

            checkLoaded();
        });

    </script>
</body>
</html>
