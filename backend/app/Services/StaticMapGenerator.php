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

        // Draw marker at the center point
        $this->drawMarker($canvas, $markerCanvasX, $markerCanvasY);

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

        // Add subtle OSM attribution text at bottom-right
        $attrColor = imagecolorallocatealpha($finalImage, 0, 0, 0, 60);
        $attrBg = imagecolorallocatealpha($finalImage, 255, 255, 255, 40);
        $attrText = '© OpenStreetMap';
        $attrWidth = strlen($attrText) * imagefontwidth(2);
        imagefilledrectangle(
            $finalImage,
            $this->width - $attrWidth - 8,
            $this->height - 16,
            $this->width,
            $this->height,
            $attrBg
        );
        imagestring($finalImage, 2, $this->width - $attrWidth - 4, $this->height - 14, $attrText, $attrColor);

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
     * Draw a map marker pin at the given canvas position.
     */
    private function drawMarker(\GdImage $canvas, int $x, int $y): void
    {
        // Marker dimensions
        $pinHeight = 36;
        $pinWidth = 24;
        $circleRadius = 8;

        // Draw drop shadow
        $shadow = imagecolorallocatealpha($canvas, 0, 0, 0, 100);
        imagefilledellipse($canvas, $x + 2, $y + 2, 10, 6, $shadow);

        // Draw marker pin body (teardrop shape using filled polygon + circle)
        $pinColor = imagecolorallocate($canvas, 220, 53, 69); // Red pin
        $pinDark = imagecolorallocate($canvas, 180, 40, 55);

        // Pin point (triangle at bottom)
        $points = [
            $x - (int) ($pinWidth / 2) + 2,
            $y - (int) ($pinHeight / 2) + 4,  // left
            $x,
            $y,                                                            // bottom point
            $x + (int) ($pinWidth / 2) - 2,
            $y - (int) ($pinHeight / 2) + 4,  // right
        ];
        imagefilledpolygon($canvas, $points, $pinColor);

        // Pin head (circle at top)
        $headY = $y - (int) ($pinHeight / 2) + 2;
        imagefilledellipse($canvas, $x, $headY, $pinWidth, $pinWidth, $pinColor);

        // Inner circle (white dot)
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledellipse($canvas, $x, $headY, $circleRadius * 2, $circleRadius * 2, $white);

        // Inner dot
        imagefilledellipse($canvas, $x, $headY, 6, 6, $pinColor);

        // Border
        imageellipse($canvas, $x, $headY, $pinWidth, $pinWidth, $pinDark);
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
