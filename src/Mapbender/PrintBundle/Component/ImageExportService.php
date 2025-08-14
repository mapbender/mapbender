<?php

namespace Mapbender\PrintBundle\Component;

use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Export\FeatureTransform;
use Mapbender\PrintBundle\Component\Export\Resolution;
use Psr\Log\LoggerInterface;

/**
 * Image export service.
 *
 * @author Stefan Winkelmann
 */
class ImageExportService
{

    /**
     * @param LayerRenderer[] $layerRenderers
     */
    public function __construct(
        protected array           $layerRenderers,
        protected LoggerInterface $logger,
        protected TypeDirectoryService $typeDirectoryService,
    )
    {
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * Extracts a convenient Box from $jobData; deliberately ignores rotation
     *
     * @param $jobData
     * @return Box
     */
    protected function getJobExtent($jobData)
    {
        $ext = $jobData['extent'];
        $cnt = $jobData['center'];
        return Box::fromCenterAndSize($cnt['x'], $cnt['y'], $ext['width'], $ext['height']);
    }

    /**
     * @param array $jobData
     * @return resource
     */
    protected function buildExportImage($jobData)
    {
        // NOTE: gd pixel coords are top down
        $targetBox = new Box(0, $jobData['height'], $jobData['width'], 0);
        $extentBox = $this->getJobExtent($jobData);
        if (isset($jobData['rotation']) && intval($jobData['rotation'])) {
            $rotation = floatval($jobData['rotation']);
            $expandedCanvas = $targetBox->getExpandedForRotation($rotation);
            $expandedCanvas->roundToIntegerBoundaries();

            $rotatedJob = array_replace($jobData, array(
                'rotation' => 0,
                'width' => abs($expandedCanvas->getWidth()),
                'height' => abs($expandedCanvas->getHeight()),
                'extent' => $extentBox->getAbsWidthAndHeight(),
                'center' => $extentBox->getCenterXy(),
            ));
            // self-delegate
            $rotatedImage = $this->buildExportImage($rotatedJob);
            return $this->rotateAndCrop($rotatedImage, $targetBox, $rotation, true);
        } else {
            $canvas = $this->canvasFactory($jobData);
            $this->addLayers($canvas, $jobData['layers'], $extentBox, $jobData);
            return $canvas->resource;
        }
    }

    /**
     * Echoes binary image data directly to stdout
     *
     * @param resource $image
     * @param string $format
     */
    public function echoImage($image, $format)
    {
        switch ($format) {
            case 'png':
                imagepng($image);
                break;
            case 'jpeg':
            case 'jpg':
            default:
                imagejpeg($image, null, 85);
                break;
        }
    }

    /**
     * @param resource $image GDish
     * @param string $format
     * @return string
     */
    public function dumpImage($image, $format)
    {
        ob_start();
        try {
            $this->echoImage($image, $format);
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }

    /**
     * @param array $jobData
     * @return resource GDish
     */
    public function runJob(array $jobData)
    {
        return $this->buildExportImage($jobData);
    }

    /**
     * @param array $jobData
     * @return ExportCanvas
     */
    protected function canvasFactory($jobData)
    {
        $dpi = ArrayUtil::getDefault($jobData, 'quality', null);
        $featureTransform = $this->initializeFeatureTransform($jobData);
        return new ExportCanvas($jobData['width'], $jobData['height'], $featureTransform, $dpi);
    }

    /**
     * Should return the "natural" pixel width for a rendered line.
     *
     * @param array $jobData
     * @return float
     */
    protected function getLineScale($jobData)
    {
        return 1.0;
    }

    /**
     * @param $jobData
     * @return FeatureTransform
     * @todo: do this without using an instance attribute
     */
    protected function initializeFeatureTransform($jobData)
    {
        $projectedBox = Box::fromCenterAndSize(
            $jobData['center']['x'], $jobData['center']['y'],
            $jobData['extent']['width'], $jobData['extent']['height']);
        $pixelBox = new Box(0, $jobData['height'], $jobData['width'], 0);
        $lineScale = $this->getLineScale($jobData);
        return FeatureTransform::boxToBox($projectedBox, $pixelBox, $lineScale);
    }

    /**
     * Produce and merge a single image layer onto $targetImage.
     * Override this to handle more layer types.
     *
     * @param ExportCanvas $canvas
     * @param array $layerDef
     * @param Box $extent projected
     * @param array $jobData
     */
    protected function addImageLayer(ExportCanvas $canvas, array $layerDef, Box $extent, array $jobData)
    {
        if (empty($layerDef['type'])) {
            $this->getLogger()->warning("Missing 'type' in layer definition", $layerDef);
            return;
        }
        $renderer = $this->getLayerRenderer($layerDef);
        $renderer->addLayer($canvas, $layerDef, $extent, $jobData);
    }

    /**
     * for some types like GeoJSON, the layerRenderer is set directly via the services.xml, for "regular" sources
     * like WMS or WMTS, the layerRenderer is read from the DataSource
     * @param array $layerDef
     */
    protected function getLayerRenderer(array $layerDef): LayerRenderer
    {
        $layerType = $layerDef['type'];
        if (!empty($this->layerRenderers[$layerType])) {
            return $this->layerRenderers[$layerType];
        }

        return $this->typeDirectoryService->getLayerRenderer($layerType);
    }

    /**
     * Folds compatible layers of same type into a single layer, which may be more efficient to process
     * overall.
     * Compatibility checks and folding logic are done by the layer renderers.
     *
     * @param mixed[][] $layers
     * @param Resolution $resolution
     * @return mixed[][]
     */
    protected function squashLayers($layers, $resolution)
    {
        $layersOut = array();
        $previous = null;
        foreach ($layers as $layerDef) {
            if (empty($layerDef['type'])) {
                $this->getLogger()->warning("Missing 'type' in layer definition", $layerDef);
                continue;
            }

            $renderer = $this->getLayerRenderer($layerDef);
            if ($previous !== null) {
                // squash only adjacent layers of the same type and opacity
                $previousOpacity = ArrayUtil::getDefault($previous, 'opacity', 1.0);
                $nextOpacity = ArrayUtil::getDefault($layerDef, 'opacity', 1.0);
                if ($previous['type'] === $layerDef['type'] && $previousOpacity == $nextOpacity) {
                    $squashed = $renderer->squashLayerDefinitions($previous, $layerDef, $resolution);
                } else {
                    $squashed = false;
                }
            } else {
                $squashed = false;
            }
            if ($squashed) {
                $previous = $squashed;
            } else {
                if ($previous) {
                    $layersOut[] = $previous;
                }
                $previous = $layerDef;
            }
        }
        if ($previous) {
            $layersOut[] = $previous;
        }
        return $layersOut;
    }

    /**
     * Collect and merge layer data into a PNG file.
     *
     * @param ExportCanvas $canvas
     * @param mixed[][] $layers
     * @param Box $extent projected
     * @param mixed[][] $jobData
     */
    protected function addLayers(ExportCanvas $canvas, array $layers, Box $extent, array $jobData)
    {
        $resolution = $canvas->getResolution($extent);
        $effectiveLayers = $this->squashLayers($layers, $resolution);

        foreach ($effectiveLayers as $k => $layerDef) {
            $this->addImageLayer($canvas, $layerDef, $extent, $jobData);
        }
    }

    protected function getColor($color, $alpha, $image)
    {
        list($r, $g, $b) = CSSColorParser::parse($color);
        $a = (1 - $alpha) * 127.0;
        return imagecolorallocatealpha($image, $r, $g, $b, $a);
    }

    /**
     * @param resource $image GDish
     * @param int $x0 source offset
     * @param int $y0 source offset
     * @param int $width target witdth
     * @param int $height target height
     * @param bool $destructive set to true to discard original image resource (saves memory)
     * @return bool|resource a NEW image resource
     */
    protected function cropImage($image, $x0, $y0, $width, $height, $destructive = false)
    {
        // NOTE GD deficiency: imagecrop cannot be used because it COPIES onto a new black image and cannot disable blending
        // This effectively converts transparent pixels to black.
        $newImage = imagecreatetruecolor($width, $height);
        imagesavealpha($newImage, true);
        imagealphablending($newImage, false);
        imagecopy($newImage, $image, 0, 0, $x0, $y0, $width, $height);
        if ($destructive) {
            imagedestroy($image);
        }
        return $newImage;
    }

    /**
     * @param resource $sourceImage GD image
     * @param Box $targetBox
     * @param number $rotation
     * @param bool $destructive set to true to discard original image resource (saves memory)
     * @return resource GD image
     */
    protected function rotateAndCrop($sourceImage, $targetBox, $rotation, $destructive = false)
    {
        $imageWidth = $targetBox->getWidth();
        $imageHeight = abs($targetBox->getHeight());

        $transColor = imagecolorallocatealpha($sourceImage, 255, 255, 255, 127);
        $rotatedImage = imagerotate($sourceImage, $rotation, $transColor);
        if ($destructive) {
            imagedestroy($sourceImage);
        }
        imagealphablending($rotatedImage, false);
        imagesavealpha($rotatedImage, true);

        $offsetX = (imagesx($rotatedImage) - $targetBox->getWidth()) * 0.5;
        $offsetY = (imagesy($rotatedImage) - abs($targetBox->getHeight())) * 0.5;

        return $this->cropImage($rotatedImage, $offsetX, $offsetY, $imageWidth, $imageHeight, true);
    }
}
