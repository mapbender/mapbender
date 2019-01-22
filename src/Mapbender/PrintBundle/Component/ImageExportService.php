<?php
namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Export\FeatureTransform;
use Mapbender\PrintBundle\Component\Transport\ImageTransport;
use Mapbender\PrintBundle\Element\ImageExport;
use Psr\Log\LoggerInterface;

/**
 * Image export service.
 *
 * @author Stefan Winkelmann
 */
class ImageExportService
{
    /** @var string */
    protected $resourceDir;
    /** @var LoggerInterface */
    protected $logger;
    /** @var ImageTransport */
    protected $imageTransport;
    /** @var LayerRenderer[] */
    protected $layerRenderers;

    /**
     * @param LayerRenderer[] $layerRenderers
     * @param ImageTransport $imageTransport
     * @param string $resourceDir absolute path
     * @param LoggerInterface $logger
     */
    public function __construct($layerRenderers, ImageTransport $imageTransport,
                                $resourceDir,
                                LoggerInterface $logger)
    {
        $this->layerRenderers = $layerRenderers;
        $this->imageTransport = $imageTransport;
        $this->resourceDir = $resourceDir;
        $this->logger = $logger;
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
            $rotation = intval($jobData['rotation']);
            $expandedCanvas = $targetBox->getExpandedForRotation($rotation);
            $expandedCanvas->roundToIntegerBoundaries();
            $expandedExtent = $extentBox->getExpandedForRotation($rotation);

            $rotatedJob = array_replace($jobData, array(
                'rotation' => 0,
                'width' => abs($expandedCanvas->getWidth()),
                'height' => abs($expandedCanvas->getHeight()),
                'extent' => $expandedExtent->getAbsWidthAndHeight(),
                'center' => $expandedExtent->getCenterXy(),
            ));
            // self-delegate
            $rotatedImage = $this->buildExportImage($rotatedJob);
            return $this->rotateAndCrop($rotatedImage, $targetBox, $rotation, true);
        } else {
            $canvas = $this->canvasFactory($jobData);
            $this->addLayers($canvas, $jobData['layers'], $extentBox);
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
     * Builds a png image and emits it directly to the browser
     *
     * @param string $content the job description in valid JSON
     * @return void
     * @deprecated
     *
     * @todo: converting from JSON encoding is controller responsibility
     * @todo: emitting to browser is controller responsibility
     */
    public function export($content)
    {
        $jobData = json_decode($content, true);
        $image = $this->runJob($jobData);
        $this->emitImageToBrowser($image, $jobData['format']);
    }

    /**
     * @param array $jobData
     * @return ExportCanvas
     */
    protected function canvasFactory($jobData)
    {
        $featureTransform = $this->initializeFeatureTransform($jobData);
        return new ExportCanvas($jobData['width'], $jobData['height'], $featureTransform);
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
     */
    protected function addImageLayer($canvas, $layerDef, Box $extent)
    {
        if (empty($layerDef['type'])) {
            $this->getLogger()->warning("Missing 'type' in layer definition", $layerDef);
            return;
        }
        $layerType = $layerDef['type'];
        if (!empty($this->layerRenderers[$layerType])) {
            $renderer = $this->layerRenderers[$layerType];
            $renderer->addLayer($canvas, $layerDef, $extent);
        } else {
            $this->getLogger()->warning("Unhandled layer type {$layerDef['type']}");
        }
    }

    /**
     * Collect and merge WMS tiles and vector layers into a PNG file.
     *
     * @param ExportCanvas $canvas
     * @param mixed[] $layers
     * @param Box $extent projected
     */
    protected function addLayers($canvas, $layers, Box $extent)
    {
        foreach ($layers as $k => $layerDef) {
            $this->addImageLayer($canvas, $layerDef, $extent);
        }
    }

    /**
     * @param string $url
     * @param float $opacity
     * @return resource|null GDish
     */
    protected function downloadImage($url, $opacity=1.0)
    {
        return $this->imageTransport->downloadImage($url, $opacity);
    }

    /**
     * @param resource $image GDish
     * @param string $format
     * @deprecated service layer should never do http
     */
    protected function emitImageToBrowser($image, $format)
    {
        $fileName = "export_" . date("YmdHis") . ($format === 'png' ? ".png" : 'jpg');
        header("Content-Type: " . ImageExport::getMimetype($format));
        header("Content-Disposition: attachment; filename={$fileName}");
        echo $this->dumpImage($image, $format);
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
     * @return bool|resource a NEW image resource
     */
    protected function cropImage($image, $x0, $y0, $width, $height)
    {
        if (PHP_VERSION_ID >= 55000) {
            // imagecrop requires PHP >= 5.5, see http://php.net/manual/de/function.imagecrop.php
            return imagecrop($image, array(
                'x' => $x0,
                'y' => $y0,
                'width' => $width,
                'height' => $height,
            ));
        } else {
            $newImage = imagecreatetruecolor($width, $height);
            imagesavealpha($newImage, true);
            imagecopy($newImage, $image, 0, 0, $x0, $y0, $width, $height);
            return $newImage;
        }
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

        $cropped = $this->cropImage($rotatedImage, $offsetX, $offsetY, $imageWidth, $imageHeight);
        imagedestroy($rotatedImage);
        return $cropped;
    }
}
