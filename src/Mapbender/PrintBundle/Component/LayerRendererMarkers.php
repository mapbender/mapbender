<?php


namespace Mapbender\PrintBundle\Component;


use Mapbender\CoreBundle\Utils\ArrayUtil;
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
            $this->addIcon($canvas, $image, $transformedCoords,
                $markerDef['offset'], $markerDef['width'], $markerDef['height']);
            imagedestroy($image);
        }
    }

    /**
     * @param ExportCanvas $canvas
     * @param float[] $anchorXy in canvas pixel space
     * @param array $featureStyle
     */
    public function addFeatureGraphic(ExportCanvas $canvas, $anchorXy, $featureStyle)
    {
        $iconPath = rtrim($this->imageRoot, '/') . '/' . ltrim($featureStyle['externalGraphic'], '/');
        $image = $this->getImage($iconPath, ArrayUtil::getDefault($featureStyle, 'graphicOpacity', 1));
        if ($image) {
            $offsetXy = array(
                'x' => ArrayUtil::getDefault($featureStyle, 'graphicXOffset', 0),
                'y' => ArrayUtil::getDefault($featureStyle, 'graphicYOffset', 0),
            );
            $iconWidth = ArrayUtil::getDefault($featureStyle, 'graphicWidth', imagesx($image));
            $iconHeight = ArrayUtil::getDefault($featureStyle, 'graphicHeight', imagesy($image));
            $this->addIcon($canvas, $image, $anchorXy, $offsetXy, $iconWidth, $iconHeight);
            imagedestroy($image);
        }
    }

    /**
     * @param ExportCanvas $canvas
     * @param resource $image GDish
     * @param float[] $anchorXy in canvas pixel space
     * @param float[] $offsetXy in icon pixel space
     * @param int $width
     * @param int $height
     */
    protected function addIcon(ExportCanvas $canvas, $image, $anchorXy, $offsetXy, $width, $height)
    {
        $transform = $canvas->featureTransform;
        $x = $anchorXy['x'] + $offsetXy['x'] * $transform->lineScale;
        $y = $anchorXy['y'] + $offsetXy['y'] * $transform->lineScale;
        $w = $width * $transform->lineScale;
        $h = $height * $transform->lineScale;
        imagecopyresampled($canvas->resource, $image, $x, $y, 0, 0, $w, $h,
            imagesx($image), imagesy($image));
    }

    /**
     * @param array $markerDef
     * @param float $opacity
     * @return resource|null
     */
    protected function getMarkerImage($markerDef, $opacity)
    {
        $markerPath = rtrim($this->imageRoot, '/') . '/' . ltrim($markerDef['path'], '/');
        return $this->getImage($markerPath, $opacity);
    }

    /**
     * @param string $path absolute file system path
     * @param float $opacity
     * @return resource|null
     */
    protected function getImage($path, $opacity)
    {
        $data = file_get_contents($path);
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
