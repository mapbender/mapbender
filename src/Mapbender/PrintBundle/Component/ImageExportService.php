<?php
namespace Mapbender\PrintBundle\Component;

use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Export\FeatureTransform;
use Mapbender\PrintBundle\Element\ImageExport;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;

/**
 * Image export service.
 *
 * @author Stefan Winkelmann
 */
class ImageExportService
{
    /** @var string */
    protected $tempDir;
    /** @var string */
    protected $resourceDir;
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param string $resourceDir absolute path
     * @param string|null $tempDir absolute path or emptyish to autodetect via sys_get_temp_dir()
     * @param LoggerInterface $logger
     */
    public function __construct($resourceDir, $tempDir, LoggerInterface $logger)
    {
        $this->resourceDir = $resourceDir;
        $this->tempDir = $tempDir ?: sys_get_temp_dir();
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
     * @param GdCanvas $canvas
     * @param array $layerDef
     * @param Box $extent projected
     */
    protected function addImageLayer($canvas, $layerDef, Box $extent)
    {
        if (empty($layerDef['type'])) {
            $this->getLogger()->warning("Missing 'type' in layer definition", $layerDef);
            return;
        }

        switch ($layerDef['type']) {
            case 'wms':
                $this->addWmsLayer($canvas, $layerDef, $extent);
                break;
            case 'GeoJSON+Style':
                $this->drawFeatures($canvas, array($layerDef));
                break;
            default:
                $this->getLogger()->warning("Unhandled layer type {$layerDef['type']}");
                break;
        }
    }

    /**
     * Collect and merge WMS tiles and vector layers into a PNG file.
     *
     * @param GdCanvas $canvas
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
     * @param $layerDef
     * @param GdCanvas $canvas
     * @param Box $extent
     * @return string
     */
    protected function preprocessWmsUrl($layerDef, $canvas, Box $extent)
    {
        $params = array(
            'WIDTH' => $canvas->width,
            'HEIGHT' => $canvas->height,
        );
        if (!empty($layerDef['changeAxis'])){
            $params['BBOX'] = $extent->bottom . ',' . $extent->left . ',' . $extent->top . ',' . $extent->right;
        } else {
            $params['BBOX'] = $extent->left . ',' . $extent->bottom . ',' . $extent->right . ',' . $extent->top;
        }
        return UrlUtil::validateUrl($layerDef['url'], $params);
    }

    /**
     * @param GdCanvas $canvas
     * @param array $layerDef
     * @param Box $extent
     */
    protected function addWmsLayer($canvas, $layerDef, $extent)
    {
        if (empty($layerDef['url'])) {
            $this->getLogger()->warning("Missing url in WMS layer", $layerDef);
            return;
        }
        $url = $this->preprocessWmsUrl($layerDef, $canvas, $extent);

        $layerImage = $this->downloadImage($url, $layerDef['opacity']);
        if ($layerImage) {
            imagecopyresampled($canvas->resource, $layerImage,
                0, 0, 0, 0,
                $canvas->width, $canvas->height,
                imagesx($layerImage), imagesy($layerImage));
            imagedestroy($layerImage);
            unset($layerImage);
        } else {
            $this->getLogger()->warning("Failed request to {$url}");
        }
    }

    /**
     * Multiply the alpha channgel of the whole $image with the given $opacity.
     * May return a different image than given if the input $image is not
     * in truecolor format.
     *
     * @param resource $image GDish
     * @param float $opacity
     * @return resource GDish
     */
    protected function multiplyAlpha($image, $opacity)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        if (!imageistruecolor($image)) {
            // promote to RGBA image first
            $imageCopy = imagecreatetruecolor($width, $height);
            imagesavealpha($imageCopy, true);
            imagealphablending($imageCopy, false);
            imagecopyresampled($imageCopy, $image, 0, 0, 0, 0, $width, $height, $width, $height);
            imagedestroy($image);
            $image = $imageCopy;
            unset($imageCopy);
        }
        imagealphablending($image, false);

        // Taking the painful way to alpha blending
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $colorIn = imagecolorat($image, $x, $y);
                $alphaIn = $colorIn >> 24 & 0xFF;
                if ($alphaIn === 127) {
                    // pixel is already fully transparent, no point
                    // modifying it
                    continue;
                }
                $alphaOut = intval(127 - (127 - $alphaIn) * $opacity);

                $colorOut = imagecolorallocatealpha(
                    $image,
                    $colorIn >> 16 & 0xFF,
                    $colorIn >> 8 & 0xFF,
                    $colorIn & 0xFF,
                    $alphaOut);
                imagesetpixel($image, $x, $y, $colorOut);
                imagecolordeallocate($image, $colorOut);
            }
        }
        return $image;
    }

    /**
     * @param string $url
     * @param float $opacity
     * @return resource|null GDish
     */
    protected function downloadImage($url, $opacity=1.0)
    {
        try {
            $response = $this->mapRequest($url);
            $image = @imagecreatefromstring($response->getContent());
            if ($image) {
                imagesavealpha($image, true);
                if ($opacity < (1.0 - 1 / 127)) {
                    return $this->multiplyAlpha($image, $opacity);
                } else {
                    return $image;
                }
            } else {
                return null;
            }
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Query a (presumably) WMS service $url and return the Response object.
     *
     * @param string $url
     * @return Response
     */
    protected function mapRequest($url)
    {
        $proxyQuery = ProxyQuery::createFromUrl($url);
        $proxyConfig = $this->container->getParameter('owsproxy.proxy');
        $proxy = new CommonProxy($proxyConfig, $proxyQuery, $this->getLogger());
        $buzzResponse = $proxy->handle();
        return $this->convertBuzzResponse($buzzResponse);
    }

    /**
     * Convert a Buzz Response to a Symfony HttpFoundation Response
     *
     * @todo: This belongs in owsproxy; it's the only part of Mapbender that uses Buzz
     *
     * @param \Buzz\Message\Response $buzzResponse
     * @return Response
     */
    public static function convertBuzzResponse($buzzResponse)
    {
        // adapt header formatting: Buzz uses a flat list of lines, HttpFoundation expects a name: value mapping
        $headers = array();
        foreach ($buzzResponse->getHeaders() as $headerLine) {
            $parts = explode(':', $headerLine, 2);
            if (count($parts) == 2) {
                $headers[$parts[0]] = $parts[1];
            }
        }
        $response = new Response($buzzResponse->getContent(), $buzzResponse->getStatusCode(), $headers);
        $response->setProtocolVersion($buzzResponse->getProtocolVersion());
        $statusText = $buzzResponse->getReasonPhrase();
        if ($statusText) {
            $response->setStatusCode($buzzResponse->getStatusCode(), $statusText);
        }
        return $response;
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

    /**
     * @param GdCanvas $canvas
     * @param array[][] $vectorLayers
     */
    protected function drawFeatures($canvas, $vectorLayers)
    {
        imagesavealpha($canvas->resource, true);
        imagealphablending($canvas->resource, true);

        foreach ($vectorLayers as $idx => $layer) {
            foreach($layer['geometries'] as $geometry) {
                $renderMethodName = 'draw' . $geometry['type'];

                if(!method_exists($this, $renderMethodName)) {
                    continue;
                }

                $this->$renderMethodName($canvas, $geometry);
            }
        }
    }

    protected function getColor($color, $alpha, $image)
    {
        list($r, $g, $b) = CSSColorParser::parse($color);
        $a = (1 - $alpha) * 127.0;
        return imagecolorallocatealpha($image, $r, $g, $b, $a);
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawPolygon($canvas, $geometry)
    {
        // promote to single-item MultiPolygon and delegate
        $multiPolygon = array_replace($geometry, array(
            'type' => 'MultiPolygon',
            'coordinates' => array($geometry['coordinates']),
        ));
        $this->drawMultiPolygon($canvas, $multiPolygon);
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawMultiPolygon($canvas, $geometry)
    {
        $image = $canvas->resource;
        $style = $this->getFeatureStyle($geometry);
        foreach ($geometry['coordinates'] as $polygon) {
            foreach ($polygon as $ring) {
                if (count($ring) < 3) {
                    continue;
                }

                $points = array();
                foreach ($ring as $c) {
                    $points[] = $canvas->featureTransform->transformPair($c);
                }
                if ($style['fillOpacity'] > 0){
                    $color = $this->getColor($style['fillColor'], $style['fillOpacity'], $image);
                    $canvas->drawPolygonBody($points, $color);
                }
                if ($this->applyStrokeStyle($canvas, $style)) {
                    $canvas->drawPolygonOutline($points, IMG_COLOR_STYLED);
                }
            }
        }
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawLineString($canvas, $geometry)
    {
        // promote to single-item MultiLineString and delegate
        $mlString = array_replace($geometry, array(
            'type' => 'MultiLineString',
            'coordinates' => array($geometry['coordinates']),
        ));
        $this->drawMultiLineString($canvas, $mlString);
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawMultiLineString($canvas, $geometry)
    {
        $style = $this->getFeatureStyle($geometry);
        if ($this->applyStrokeStyle($canvas, $style)) {
            foreach ($geometry['coordinates'] as $lineString) {
                $pixelCoords = array();
                foreach ($lineString as $coord) {
                    $pixelCoords[] = $canvas->featureTransform->transformPair($coord);
                }
                $canvas->drawLineString($pixelCoords, IMG_COLOR_STYLED);
            }
        }
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawPoint($canvas, $geometry)
    {
        $style = $this->getFeatureStyle($geometry);
        $image = $canvas->resource;
        $resizeFactor = $canvas->featureTransform->lineScale;

        $p = $canvas->featureTransform->transformPair($geometry['coordinates']);
        $p[0] = round($p[0]);
        $p[1] = round($p[1]);

        if (isset($style['label'])) {
            // draw label with halo
            $color = $this->getColor($style['fontColor'], 1, $image);
            $bgcolor = $this->getColor($style['labelOutlineColor'], 1, $image);
            $fontPath = $this->resourceDir.'/fonts/';
            $font = $fontPath . 'OpenSans-Bold.ttf';

            $fontSize = floatval(10 * $resizeFactor);
            $haloOffsets = array(
                array(0, +$resizeFactor),
                array(0, -$resizeFactor),
                array(-$resizeFactor, 0),
                array(+$resizeFactor, 0),
            );
            // offset text to the right of the point
            $textXy = array(
                $p[0] + $resizeFactor * 1.5 * $style['pointRadius'],
                // center vertically on original y
                $p[1] + 0.5 * $fontSize,
            );
            $text = $style['label'];
            foreach ($haloOffsets as $xy) {
                imagettftext($image, $fontSize, 0,
                    $textXy[0] + $xy[0], $textXy[1] + $xy[1],
                    $bgcolor, $font, $text);
            }
            imagettftext($image, $fontSize, 0,
                $textXy[0], $textXy[1],
                $color, $font, $text);
        }

        $diameter = max(1, round(2 * $style['pointRadius'] * $resizeFactor));
        // Filled circle
        if ($style['fillOpacity'] > 0) {
            $color = $this->getColor(
                $style['fillColor'],
                $style['fillOpacity'],
                $image);
            imagesetthickness($image, 0);
            imagefilledellipse($image, $p[0], $p[1], $diameter, $diameter, $color);
        }
        // Circle border
        if ($this->applyStrokeStyle($canvas, $style)) {
            // Imageellipse DOES NOT support IMG_COLOR_STYLED-based line styling
            // It does not even support line thickness.
            // To support properly styled and patterned point outlining, we have
            // to generate ~circle geometry ourselves and use a styling-aware drawing
            // function.
            $coords = $this->generatePointOutlineCoords($p[0], $p[1], $diameter / 2);
            $canvas->drawPolygonOutline($coords, IMG_COLOR_STYLED);
        }
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


    /**
     * Creates a ~randomly named temp file with given $prefix and returns its name
     *
     * @param $prefix
     * @return string
     */
    protected function makeTempFile($prefix)
    {
        $filePath = tempnam($this->tempDir, $prefix);
        // tempnam may return false in undocumented error cases
        if (!$filePath) {
            throw new \RuntimeException("Failed to create temp file with prefix '$prefix' in '{$this->tempDir}'");
        }
        return $filePath;
    }

    /**
     * @param string $type (a GeoJson type name)
     * @return array
     */
    protected function getDefaultFeatureStyle($type)
    {
        return array(
            'strokeWidth' => 1,
            'fontColor' => '#ff0000',
            'labelOutlineColor' => '#ffffff',
            'strokeDashstyle' => 'solid',
        );
    }

    /**
     * @param mixed[] $geometry
     * @return array
     */
    protected function getFeatureStyle($geometry)
    {
        $defaults = $this->getDefaultFeatureStyle($geometry['type']);
        return array_replace($defaults, $geometry['style']);
    }

    /**
     * Return an array appropriate for gd imagesetstyle that will impact lines
     * drawn with a 'color' value of IMG_COLOR_STYLED.
     * @see http://php.net/manual/en/function.imagesetstyle.php
     *
     * @param int $color from imagecollorallocate
     * @param int $thickness
     * @param string $patternName
     * @param float $patternScale
     * @return array
     */
    protected function getStrokeStyle($color, $thickness, $patternName='solid', $patternScale = 1.0)
    {
        // NOTE: GD actually counts one style entry per produced pixel, NOT per pixel-space length unit.
        // => Length of the style array must scale with the line thickness
        $dotLength = max(1, intval(round($thickness * $patternScale)));
        $dashLength = max(1, intval(round($patternScale * 45)));
        $longDashLength = max(1, intval(round($patternScale * 85)));
        $spaceLength = max(1, intval(round($patternScale * 45)));

        $dot = array_fill(0, $thickness * $dotLength, $color);
        $dash = array_fill(0, $thickness * $dashLength, $color);
        $longdash = array_fill(0, $thickness * $longDashLength, $color);
        $space = array_fill(0, $thickness * $spaceLength, IMG_COLOR_TRANSPARENT);

        switch ($patternName) {
            case 'solid' :
                return array($color);
            case 'dot' :
                return array_merge($dot, $space);
            case 'dash' :
                return array_merge($dash, $space);
            case 'dashdot' :
                return array_merge($dash, $space, $dot, $space);
            case 'longdash' :
                return array_merge($longdash, $space);
            case 'longdashdot' :
                return array_merge($longdash, $space, $dot, $space);
            default:
                throw new \InvalidArgumentException("Unsupported pattern name " . print_r($patternName, true));
        }
    }

    /**
     * Generate and apply extended (OpenLayers 2) stroke style.
     * Returns false to indicate that stroke style is degenerate (zero width or zero opacity).
     * Callers should check the return value and skip line rendering completely if false.
     *
     * @param ExportCanvas $canvas
     * @param mixed[] $style
     * @return bool
     */
    protected function applyStrokeStyle($canvas, $style)
    {
        // NOTE: gd imagesetstyle patterns are not based on distance from starting point
        //       on the line, but rather on integral pixel quantities, making it
        //       a) scale proportionally to stroke width
        //       b) fundamentally incompatible with non-integral line widths
        // To generate any functional stroke style, the width must be quantized to an
        // integer, and that integral with must be provided to the style generation function.
        $lineScale = $canvas->featureTransform->lineScale;
        $intThickness = intval(round($style['strokeWidth'] * $lineScale));
        if ($style['strokeOpacity'] && $intThickness >= 1) {
            $color = $this->getColor($style['strokeColor'], $style['strokeOpacity'], $canvas->resource);
            imagesetthickness($canvas->resource, $intThickness);
            $strokeStyle = $this->getStrokeStyle($color, $intThickness, $style['strokeDashstyle'], $lineScale);
            imagesetstyle($canvas->resource, $strokeStyle);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param float $centerX in pixel space
     * @param float $centerY in pixel space
     * @param float $radius in pixel space
     * @return float[][]
     */
    protected function generatePointOutlineCoords($centerX, $centerY, $radius)
    {
        $step = min(M_PI / 8, M_PI / 4 / $radius);
        $points = array();
        for ($a = 0; $a < 2 * M_PI; $a += $step) {
            $x = round($centerX + sin($a) * $radius);
            $y = round($centerY + cos($a) * $radius);
            $points[] = array($x, $y);
        }
        return $points;
    }
}
