<?php


namespace Mapbender\PrintBundle\Component;


use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Util\GdUtil;

class LayerRendererMarkers extends LayerRenderer
{
    /** @var string */
    protected $imageRoot;

    /**
     * @param string $imageRoot should be the web root
     */
    public function __construct($imageRoot)
    {
        $this->imageRoot = $imageRoot;
    }

    public function squashLayerDefinitions($layerDef, $nextLayerDef, $resolution)
    {
        // squash everything
        $layerDef['markers'] = array_merge($layerDef['markers'], $nextLayerDef['markers']);
        return $layerDef;
    }

    public function addLayer(ExportCanvas $canvas, $layerDef, Box $extent)
    {
        foreach ($layerDef['markers'] as $markerDef) {
            $this->addMarker($canvas, $markerDef, $layerDef['opacity']);
        }
    }

    protected function addMarker(ExportCanvas $canvas, $markerDef, $opacity)
    {
        $image = $this->getMarkerImage($markerDef, $opacity);
        if ($image) {
            $transform = $canvas->featureTransform;
            $transformedCoords = $transform->transformXy($markerDef['coordinates']);
            $offsetX = $transformedCoords['x'] + $markerDef['offset']['x'] * $transform->lineScale;
            $offsetY = $transformedCoords['y'] + $markerDef['offset']['y'] * $transform->lineScale;
            imagecopyresampled($canvas->resource, $image, $offsetX, $offsetY, 0, 0,
                $markerDef['width'] * $transform->lineScale, $markerDef['height'] * $transform->lineScale,
                imagesx($image), imagesy($image));
            imagedestroy($image);
        }
    }

    /**
     * @param array $markerDef
     * @param float $opacity
     * @return resource|null
     */
    protected function getMarkerImage($markerDef, $opacity)
    {
        $markerPath = rtrim($this->imageRoot, '/') . '/' . ltrim($markerDef['path'], '/');
        $data = file_get_contents($markerPath);
        if ($data) {
            $image = imagecreatefromstring($data);
            if ($image) {
                if ($opacity <= (1.0 - 1 / 127)) {
                    $multipliedImage = GdUtil::multiplyAlpha($image, $opacity);
                    if ($multipliedImage !== $image) {
                        imagedestroy($image);
                    }
                    $image = $multipliedImage;
                }
                return $image;
            }
        }
        return null;
    }
}
