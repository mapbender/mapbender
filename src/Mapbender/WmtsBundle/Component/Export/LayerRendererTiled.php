<?php


namespace Mapbender\WmtsBundle\Component\Export;


use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\LayerRenderer;
use Mapbender\PrintBundle\Component\Transport\ImageTransport;
use Psr\Log\LoggerInterface;

abstract class LayerRendererTiled extends LayerRenderer
{
    /** @var ImageTransport */
    protected $imageTransport;
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ImageTransport $imageTransport
     * @param LoggerInterface $logger
     */
    public function __construct(ImageTransport $imageTransport, LoggerInterface $logger)
    {
        $this->imageTransport = $imageTransport;
        $this->logger = $logger;
    }

    public function squashLayerDefinitions($layerDef, $nextLayerDef, $resolution)
    {
        return false;
    }

    public function addLayer(ExportCanvas $canvas, $layerDef, Box $extent)
    {
        $layerImage = $this->buildLayerImage($canvas, $layerDef, $extent);
        imagecopyresampled($canvas->resource, $layerImage,
            0, 0,
            0, 0,
            imagesx($canvas->resource), imagesy($canvas->resource),
            imagesx($layerImage), imagesy($layerImage));
    }

    protected function buildLayerImage(ExportCanvas $canvas, $layerDef, Box $extent)
    {
        $targetResolution = $canvas->getResolution($extent)->getHorizontal();
        $tileMatrix = $this->getTileMatrix($layerDef, $targetResolution);
        $layerImageWidth = intval(round(abs($extent->getWidth()) / $tileMatrix->getResolution()));
        $layerImageHeight = intval(round(abs($extent->getHeight()) / $tileMatrix->getResolution()));
        $image = imagecreatetruecolor($layerImageWidth, $layerImageHeight);
        imagesavealpha($image, true);
        imagealphablending($image, false);
        imagefill($image, 0, 0, IMG_COLOR_TRANSPARENT);
        $imageTiles = $tileMatrix->getTileRequests($extent);
        $this->addTiles($image, $tileMatrix, $imageTiles, floatval($layerDef['opacity']));
        return $image;
    }

    /**
     * @param resource $image GDish
     * @param TileMatrix $tileMatrix
     * @param ImageTile[] $imageTiles
     * @param float $opacity
     */
    protected function addTiles($image, $tileMatrix, $imageTiles, $opacity)
    {
        foreach ($imageTiles as $imageTile) {
            $tileUrl = $tileMatrix->getTileUrl($imageTile->getTileX(), $imageTile->getTileY());
            $tileImage = $this->imageTransport->downloadImage($tileUrl, $opacity);
            if ($tileImage) {
                imagecopyresampled($image, $tileImage,
                    $imageTile->getOffsetX(), $imageTile->getOffsetY(),
                    0, 0,
                    // NOTE: Returned tile image sizes may actually be different than sadvertised in capabilities.
                    //       This is a common "High quality print" / "retina" hack
                    //       Stitching target coordinates are always based on advertised dimensions though.
                    $tileMatrix->getTileWidth(), $tileMatrix->getTileHeight(),
                    imagesx($tileImage), imagesy($tileImage));
            }
        }
    }

    /**
     * @param array $layerDef
     * @param float $resolution
     * @return TileMatrix
     */
    abstract protected function getTileMatrix($layerDef, $resolution);
}
