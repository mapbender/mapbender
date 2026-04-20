<?php

namespace Mapbender\XyzBundle\Component;

use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Export\Resolution;
use Mapbender\PrintBundle\Component\LayerRenderer;
use Mapbender\PrintBundle\Component\Transport\ImageTransport;
use Psr\Log\LoggerInterface;

class XyzLayerRenderer extends LayerRenderer
{
    private const TILE_SIZE = 256;

    public function __construct(
        protected ImageTransport  $imageTransport,
        protected LoggerInterface $logger,
    )
    {
    }

    public function squashLayerDefinitions(array $layerDef, array $nextLayerDef, Resolution $resolution): false|array
    {
        return false;
    }

    public function addLayer(ExportCanvas $canvas, array $layerDef, Box $extent, array $jobData): void
    {
        $urlTemplate = $layerDef['url'];
        $opacity = floatval($layerDef['opacity'] ?? 1.0);
        $zoom = $this->calculateZoom($extent, $canvas);

        $tileSize = self::TILE_SIZE;
        $n = pow(2, $zoom);

        // Convert extent (EPSG:3857) to pixels
        $originShift = 20037508.342789244;

        $minTileX = intval(floor(($extent->left + $originShift) / (2 * $originShift) * $n));
        $maxTileX = intval(floor(($extent->right + $originShift) / (2 * $originShift) * $n));
        $minTileY = intval(floor((1 - log(tan(deg2rad($this->mercatorToLat($extent->top))) + 1 / cos(deg2rad($this->mercatorToLat($extent->top)))) / M_PI) / 2 * $n));
        $maxTileY = intval(floor((1 - log(tan(deg2rad($this->mercatorToLat($extent->bottom))) + 1 / cos(deg2rad($this->mercatorToLat($extent->bottom)))) / M_PI) / 2 * $n));

        // Clamp tile indices
        $minTileX = max(0, $minTileX);
        $maxTileX = min($n - 1, $maxTileX);
        $minTileY = max(0, $minTileY);
        $maxTileY = min($n - 1, $maxTileY);

        // Size of the tile area in map units per tile
        $worldSize = 2 * $originShift;
        $tileWorldSize = $worldSize / $n;

        // Create layer image
        $totalTilesX = $maxTileX - $minTileX + 1;
        $totalTilesY = $maxTileY - $minTileY + 1;
        $layerImageWidth = $totalTilesX * $tileSize;
        $layerImageHeight = $totalTilesY * $tileSize;

        $tileCanvas = imagecreatetruecolor($layerImageWidth, $layerImageHeight);
        imagesavealpha($tileCanvas, true);
        imagealphablending($tileCanvas, false);
        imagefill($tileCanvas, 0, 0, IMG_COLOR_TRANSPARENT);
        imagealphablending($tileCanvas, true);

        // Fetch and place tiles
        for ($tx = $minTileX; $tx <= $maxTileX; $tx++) {
            for ($ty = $minTileY; $ty <= $maxTileY; $ty++) {
                $url = $this->buildTileUrl($urlTemplate, $zoom, $tx, $ty);
                $tileImage = $this->imageTransport->downloadImage($url, $opacity);
                if ($tileImage) {
                    $destX = ($tx - $minTileX) * $tileSize;
                    $destY = ($ty - $minTileY) * $tileSize;
                    imagecopyresampled(
                        $tileCanvas, $tileImage,
                        $destX, $destY,
                        0, 0,
                        $tileSize, $tileSize,
                        imagesx($tileImage), imagesy($tileImage)
                    );
                    imagedestroy($tileImage);
                }
            }
        }

        // Now resample the tile canvas to match the export canvas, mapping coordinates
        $canvasWidth = imagesx($canvas->resource);
        $canvasHeight = imagesy($canvas->resource);

        // Tile grid extents in map units
        $tileGridLeft = $minTileX * $tileWorldSize - $originShift;
        $tileGridRight = ($maxTileX + 1) * $tileWorldSize - $originShift;
        // Y axis for web mercator tiles: tile 0 is at top (north)
        $tileGridTop = $originShift - $minTileY * $tileWorldSize;
        $tileGridBottom = $originShift - ($maxTileY + 1) * $tileWorldSize;

        // Source rectangle in tileCanvas pixel space
        $srcX = ($extent->left - $tileGridLeft) / ($tileGridRight - $tileGridLeft) * $layerImageWidth;
        $srcY = ($tileGridTop - $extent->top) / ($tileGridTop - $tileGridBottom) * $layerImageHeight;
        $srcW = ($extent->right - $extent->left) / ($tileGridRight - $tileGridLeft) * $layerImageWidth;
        $srcH = ($extent->top - $extent->bottom) / ($tileGridTop - $tileGridBottom) * $layerImageHeight;

        imagecopyresampled(
            $canvas->resource, $tileCanvas,
            0, 0,
            intval(round($srcX)), intval(round($srcY)),
            $canvasWidth, $canvasHeight,
            intval(round($srcW)), intval(round($srcH))
        );

        imagedestroy($tileCanvas);
    }

    private function buildTileUrl(string $template, int $z, int $x, int $y): string
    {
        return str_replace(
            ['{z}', '{x}', '{y}', '{-y}'],
            [$z, $x, $y, (pow(2, $z) - 1 - $y)],
            $template
        );
    }

    private function calculateZoom(Box $extent, ExportCanvas $canvas): int
    {
        $canvasWidth = imagesx($canvas->resource);
        $extentWidth = abs($extent->getWidth());
        $originShift = 20037508.342789244;
        $worldSize = 2 * $originShift;

        // resolution = map units per pixel
        $resolution = $extentWidth / $canvasWidth;
        // At zoom z, each pixel covers worldSize / (256 * 2^z) map units
        $zoom = intval(round(log($worldSize / (self::TILE_SIZE * $resolution)) / log(2)));
        return max(0, min(22, $zoom));
    }

    private function mercatorToLat(float $mercatorY): float
    {
        return rad2deg(atan(exp($mercatorY / 20037508.342789244 * M_PI)) * 2 - M_PI / 2);
    }
}
