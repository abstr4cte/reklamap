<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class StaticMapGenerator
{
    private int $zoom = 13;
    private int $width = 860;
    private int $height = 400;
    private int $tileSize = 256;
    private string $tileUrl = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

    /**
     * Generate a static map image for the given coordinates.
     *
     * @param float $latitude
     * @param float $longitude
     * @param string $outputPath Full path to save the PNG image
     * @return bool
     */
    public function generate(float $latitude, float $longitude, string $outputPath): bool
    {
        // Calculate center tile coordinates
        $centerX = $this->lonToTileX($longitude, $this->zoom);
        $centerY = $this->latToTileY($latitude, $this->zoom);

        // Calculate how many tiles we need in each direction
        $tilesX = (int) ceil($this->width / $this->tileSize) + 1;
        $tilesY = (int) ceil($this->height / $this->tileSize) + 1;

        // Create the canvas (big enough for all tiles)
        $canvasWidth = $tilesX * $this->tileSize;
        $canvasHeight = $tilesY * $this->tileSize;
        $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);

        if ($canvas === false) {
            Log::error('StaticMapGenerator: Failed to create GD canvas');
            return false;
        }

        // Fill with a light gray background as fallback
        $bgColor = imagecolorallocate($canvas, 242, 239, 233);
        imagefill($canvas, 0, 0, $bgColor);

        // Calculate the offset of the center pixel within the tile grid
        $centerTileX = (int) floor($centerX);
        $centerTileY = (int) floor($centerY);
        $offsetX = (int) floor(($centerX - $centerTileX) * $this->tileSize);
        $offsetY = (int) floor(($centerY - $centerTileY) * $this->tileSize);

        // Calculate starting tile
        $startTileX = $centerTileX - (int) floor($tilesX / 2);
        $startTileY = $centerTileY - (int) floor($tilesY / 2);

        // Download and place tiles
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: ReklaMap/1.0 (https://reklamap.pl)\r\n",
                'timeout' => 10,
            ],
        ]);

        for ($x = 0; $x < $tilesX; $x++) {
            for ($y = 0; $y < $tilesY; $y++) {
                $tileX = $startTileX + $x;
                $tileY = $startTileY + $y;

                // Wrap tile X coordinate
                $maxTile = (int) pow(2, $this->zoom);
                $tileX = (($tileX % $maxTile) + $maxTile) % $maxTile;

                // Skip invalid Y tiles
                if ($tileY < 0 || $tileY >= $maxTile) {
                    continue;
                }

                $url = str_replace(
                    ['{z}', '{x}', '{y}'],
                    [$this->zoom, $tileX, $tileY],
                    $this->tileUrl
                );

                try {
                    $tileData = @file_get_contents($url, false, $context);
                    if ($tileData !== false) {
                        $tileImage = @imagecreatefromstring($tileData);
                        if ($tileImage !== false) {
                            imagecopy(
                                $canvas,
                                $tileImage,
                                $x * $this->tileSize,
                                $y * $this->tileSize,
                                0,
                                0,
                                $this->tileSize,
                                $this->tileSize
                            );
                            imagedestroy($tileImage);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("StaticMapGenerator: Failed to fetch tile {$url}: " . $e->getMessage());
                }
            }
        }

        // Calculate the pixel position of the center point on the canvas
        $markerCanvasX = (int) floor($tilesX / 2) * $this->tileSize + $offsetX;
        $markerCanvasY = (int) floor($tilesY / 2) * $this->tileSize + $offsetY;

        // Draw marker at the center point using the real Leaflet marker icon
        $this->drawLeafletMarker($canvas, $markerCanvasX, $markerCanvasY, $context);

        // Crop the canvas to the desired output size, centered on the marker
        $cropX = max(0, $markerCanvasX - (int) floor($this->width / 2));
        $cropY = max(0, $markerCanvasY - (int) floor($this->height / 2));

        // Make sure we don't crop beyond the canvas
        if ($cropX + $this->width > $canvasWidth) {
            $cropX = $canvasWidth - $this->width;
        }
        if ($cropY + $this->height > $canvasHeight) {
            $cropY = $canvasHeight - $this->height;
        }

        $finalImage = imagecreatetruecolor($this->width, $this->height);
        imagecopy($finalImage, $canvas, 0, 0, $cropX, $cropY, $this->width, $this->height);

        // Ensure output directory exists
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Save the final image
        $result = imagepng($finalImage, $outputPath, 6);

        imagedestroy($canvas);
        imagedestroy($finalImage);

        return $result;
    }

    /**
     * Draw the real Leaflet marker icon (with shadow) at the given canvas position.
     * Downloads marker-icon.png and marker-shadow.png from Leaflet CDN.
     * The marker anchor point is at the bottom-center of the icon (same as Leaflet).
     */
    private function drawLeafletMarker(\GdImage $canvas, int $x, int $y, $httpContext): void
    {
        $leafletCdn = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images';
        $cacheDir = storage_path('app/cache/map-icons');

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Download and cache the shadow (41x41, anchor: 12,41)
        $shadowPath = $cacheDir . '/marker-shadow.png';
        if (!file_exists($shadowPath)) {
            $shadowData = @file_get_contents($leafletCdn . '/marker-shadow.png', false, $httpContext);
            if ($shadowData !== false) {
                file_put_contents($shadowPath, $shadowData);
            }
        }

        // Download and cache the marker icon (25x41, anchor: 12,41)
        $iconPath = $cacheDir . '/marker-icon.png';
        if (!file_exists($iconPath)) {
            $iconData = @file_get_contents($leafletCdn . '/marker-icon.png', false, $httpContext);
            if ($iconData !== false) {
                file_put_contents($iconPath, $iconData);
            }
        }

        // Draw shadow first (slightly offset to the right, like Leaflet)
        if (file_exists($shadowPath)) {
            $shadow = @imagecreatefrompng($shadowPath);
            if ($shadow !== false) {
                $shadowW = imagesx($shadow);
                $shadowH = imagesy($shadow);
                // Leaflet shadow anchor: [12, 41] - bottom of shadow aligns with marker bottom
                $shadowX = $x - 12;
                $shadowY = $y - $shadowH;

                imagealphablending($canvas, true);
                imagecopy($canvas, $shadow, $shadowX, $shadowY, 0, 0, $shadowW, $shadowH);
                imagedestroy($shadow);
            }
        }

        // Draw marker icon on top
        if (file_exists($iconPath)) {
            $icon = @imagecreatefrompng($iconPath);
            if ($icon !== false) {
                $iconW = imagesx($icon);
                $iconH = imagesy($icon);
                // Leaflet marker anchor: [12, 41] - center-bottom of icon
                $iconX = $x - 12;
                $iconY = $y - $iconH;

                imagealphablending($canvas, true);
                imagecopy($canvas, $icon, $iconX, $iconY, 0, 0, $iconW, $iconH);
                imagedestroy($icon);
            }
        }
    }

    /**
     * Convert longitude to tile X coordinate.
     */
    private function lonToTileX(float $lon, int $zoom): float
    {
        return (($lon + 180) / 360) * pow(2, $zoom);
    }

    /**
     * Convert latitude to tile Y coordinate.
     */
    private function latToTileY(float $lat, int $zoom): float
    {
        $latRad = deg2rad($lat);
        return (1 - log(tan($latRad) + 1 / cos($latRad)) / M_PI) / 2 * pow(2, $zoom);
    }
}
