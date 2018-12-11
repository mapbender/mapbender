<?php
namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Export\Affine2DTransform;
use Mapbender\PrintBundle\Component\Export\Box;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
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
    /** @var ContainerInterface */
    protected $container;
    /** @var string */
    protected $tempDir;
    /** @var string */
    protected $urlHostPath;
    /** @var string */
    protected $resourceDir;

    /** @var Affine2DTransform */
    protected $featureTransform;

    public function __construct($container)
    {
        $this->container = $container;
        $this->resourceDir = $this->container->getParameter('kernel.root_dir') . '/Resources/MapbenderPrintBundle';
        $this->tempDir = sys_get_temp_dir();
        # Extract URL base path so we can later decide to let Symfony handle internal requests or make proper
        # HTTP connections.
        # NOTE: This is only possible in web, not CLI
        if (php_sapi_name() != "cli") {
            $request = $this->container->get('request');
            $this->urlHostPath = $request->getHttpHost() . $request->getBaseURL();
        } else {
            $this->urlHostPath = null;
        }
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        /** @var LoggerInterface $logger */
        $logger = $this->container->get("logger");
        return $logger;
    }

    protected function buildExportImage($jobData)
    {
        $mapImage = $this->makeBlank($jobData['width'], $jobData['height']);
        $this->addLayers($mapImage, $jobData['layers'], $jobData['width'], $jobData['height']);
        return $mapImage;
    }

    /**
     * @param string $format
     * @return string
     */
    public function getMimetype($format)
    {
        switch ($format) {
            case 'png':
                return 'image/png';
            case 'jpeg':
            case 'jpg':
                return 'image/jpeg';
            default:
                throw new \InvalidArgumentException("Unsupported format $format");
        }
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
        // Clean up internally modified / collected state
        $this->featureTransform = $this->initializeFeatureTransform($jobData);
        $image = $this->buildExportImage($jobData);

        $this->emitImageToBrowser($image, $jobData['format']);
    }

    /**
     * @param $jobData
     * @return Affine2DTransform
     */
    protected function initializeFeatureTransform($jobData)
    {
        $projectedBox = Box::fromCenterAndSize(
            $jobData['center']['x'], $jobData['center']['y'],
            $jobData['extent']['width'], $jobData['extent']['height']);
        $pixelBox = new Box(0, $jobData['height'], $jobData['width'], 0);
        return Affine2DTransform::boxToBox($projectedBox, $pixelBox);
    }

    /**
     * @param int $width
     * @param int $height
     * @return resource GDish
     */
    protected function makeBlank($width, $height)
    {
        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);
        imagecolordeallocate($image, $bg);
        return $image;
    }

    /**
     * Collect and merge WMS tiles and vector layers into a PNG file.
     *
     * @param resource $targetImage GDish
     * @param mixed[] $layers
     * @param int $width
     * @param int $height
     * @return resource GDish
     */
    protected function addLayers($targetImage, $layers, $width, $height)
    {
        foreach ($layers as $k => $layerDef) {
            if (!empty($layerDef['url'])) {
                $this->addRasterLayer($targetImage, $layerDef, $width, $height);
            } elseif ($layerDef['type'] === 'GeoJSON+Style') {
                $this->drawFeatures($targetImage, array($layerDef));
            }
        }
        return $targetImage;
    }

    protected function preprocessRasterUrl($layerDef, $width, $height)
    {
        $url = strstr($layerDef['url'], '&WIDTH', true);
        $url .= "&WIDTH={$width}&HEIGHT={$height}";
        return $url;
    }

    protected function addRasterLayer($targetImage, $layerDef, $width, $height)
    {
        if (empty($layerDef['url'])) {
                return;
        }
        $url = $this->preprocessRasterUrl($layerDef, $width, $height);

        $layerImage = $this->downloadImage($url, $layerDef['opacity']);
        if ($layerImage) {
            imagecopyresampled($targetImage, $layerImage,
                0, 0, 0, 0,
                $width, $height,
                imagesx($layerImage), imagesy($layerImage));
            imagedestroy($layerImage);
            unset($layerImage);
        } else {
            $this->getLogger()->warn("Failed request to {$url}");
        }
    }

    /**
     * Convert a GD image to true-color RGBA and write it back to the file
     * system.
     *
     * @param resource $input source image
     * @param float $opacity in [0;1]
     * @return resource GDish
     */
    protected function forceToRgba($input, $opacity)
    {
        $width = imagesx($input);
        $height = imagesy($input);

        // Make sure input image is truecolor with alpha, regardless of input mode!
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagecopyresampled($image, $input, 0, 0, 0, 0, $width, $height, $width, $height);

        // Taking the painful way to alpha blending
        if ($opacity < 1) {
            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $colorIn = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                    $alphaOut = 127 - (127 - $colorIn['alpha']) * $opacity;

                    $colorOut = imagecolorallocatealpha(
                        $image,
                        $colorIn['red'],
                        $colorIn['green'],
                        $colorIn['blue'],
                        $alphaOut);
                    imagesetpixel($image, $x, $y, $colorOut);
                    imagecolordeallocate($image, $colorOut);
                }
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
        $response = $this->mapRequest($url);
        $image = @imagecreatefromstring($response->getContent());
        if ($image) {
            return $this->forceToRgba($image, $opacity);
        } else {
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
        // find urls from this host (tunnel connection for secured services)
        $parsed   = parse_url($url);
        $host = isset($parsed['host']) ? $parsed['host'] : $this->container->get('request')->getHttpHost();
        $hostpath = $host . $parsed['path'];
        $pos      = strpos($hostpath, $this->urlHostPath);
        if ($pos === 0 && ($routeStr = substr($hostpath, strlen($this->urlHostPath))) !== false) {
            $attributes = $this->container->get('router')->match($routeStr);
            $gets       = array();
            parse_str($parsed['query'], $gets);
            $subRequest = new Request($gets, array(), $attributes, array(), array(), array(), '');
            /** @var HttpKernelInterface $kernel */
            $kernel = $this->container->get('http_kernel');
            $response = $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        } else {
            $proxyQuery = ProxyQuery::createFromUrl($url);
            $proxyConfig = $this->container->getParameter('owsproxy.proxy');
            $proxy = new CommonProxy($proxyConfig, $proxyQuery, $this->getLogger());
            $buzzResponse = $proxy->handle();
            $response = $this->convertBuzzResponse($buzzResponse);
        }
        return $response;
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
     */
    protected function emitImageToBrowser($image, $format)
    {
        if ($format == 'png') {
            header("Content-type: image/png");
            header("Content-Disposition: attachment; filename=export_" . date("YmdHis") . ".png");
            //header('Content-Length: ' . filesize($file));
            imagepng($image);
        } else {
            header("Content-type: image/jpeg");
            header("Content-Disposition: attachment; filename=export_" . date("YmdHis") . ".jpg");
            //header('Content-Length: ' . filesize($file));
            imagejpeg($image, null, 85);
        }
    }

    protected function drawFeatures($image, $vectorLayers)
    {
        imagesavealpha($image, true);
        imagealphablending($image, true);

        foreach ($vectorLayers as $idx => $layer) {
            foreach($layer['geometries'] as $geometry) {
                $renderMethodName = 'draw' . $geometry['type'];

                if(!method_exists($this, $renderMethodName)) {
                    continue;
                }

                $this->$renderMethodName($geometry, $image);
            }
        }
    }

    protected function getColor($color, $alpha, $image)
    {
        list($r, $g, $b) = CSSColorParser::parse($color);

        if(0 == $alpha) {
            return imagecolorallocate($image, $r, $g, $b);
        } else {
            $a = (1 - $alpha) * 127.0;
            return imagecolorallocatealpha($image, $r, $g, $b, $a);
        }
    }

    private function drawPolygon($geometry, $image)
    {
        foreach($geometry['coordinates'] as $ring) {
            if(count($ring) < 3) {
                continue;
            }

            $points = array();
            foreach($ring as $c) {
                $p = $this->featureTransform->transformPair($c);
                $points[] = floatval($p[0]);
                $points[] = floatval($p[1]);
            }
            imagesetthickness($image, 0);
            // Filled area
            if($geometry['style']['fillOpacity'] > 0){
                $color = $this->getColor(
                    $geometry['style']['fillColor'],
                    $geometry['style']['fillOpacity'],
                    $image);
                imagefilledpolygon($image, $points, count($ring), $color);
            }
            // Border
            $color = $this->getColor(
                $geometry['style']['strokeColor'],
                $geometry['style']['strokeOpacity'],
                $image);
            imagesetthickness($image, $geometry['style']['strokeWidth']);
            imagepolygon($image, $points, count($ring), $color);
        }
    }

    private function drawMultiPolygon($geometry, $image)
    {
        foreach($geometry['coordinates'][0] as $ring) {
            if(count($ring) < 3) {
                continue;
            }

            $points = array();
            foreach($ring as $c) {
                $p = $this->featureTransform->transformPair($c);
                $points[] = floatval($p[0]);
                $points[] = floatval($p[1]);
            }
            imagesetthickness($image, 0);
            // Filled area
            if($geometry['style']['fillOpacity'] > 0){
                $color = $this->getColor(
                    $geometry['style']['fillColor'],
                    $geometry['style']['fillOpacity'],
                    $image);
                imagefilledpolygon($image, $points, count($ring), $color);
            }
            // Border
            $color = $this->getColor(
                $geometry['style']['strokeColor'],
                $geometry['style']['strokeOpacity'],
                $image);
            imagesetthickness($image, $geometry['style']['strokeWidth']);
            imagepolygon($image, $points, count($ring), $color);
        }
    }

    private function drawLineString($geometry, $image)
    {
        $color = $this->getColor(
            $geometry['style']['strokeColor'],
            $geometry['style']['strokeOpacity'],
            $image);
        imagesetthickness($image, $geometry['style']['strokeWidth']);

        for($i = 1; $i < count($geometry['coordinates']); $i++) {

            $from = $this->featureTransform->transformPair($geometry['coordinates'][$i - 1]);
            $to = $this->featureTransform->transformPair($geometry['coordinates'][$i]);

            imageline($image, $from[0], $from[1], $to[0], $to[1], $color);
        }
    }

    private function drawPoint($geometry, $image)
    {
        $p = $this->featureTransform->transformPair($geometry['coordinates']);

        if(isset($geometry['style']['label'])){
            // draw label with white halo
            $color = $this->getColor('#ff0000', 1, $image);
            $bgcolor = $this->getColor('#ffffff', 1, $image);
            $fontPath = "{$this->resourceDir}/fonts/";
            $font = $fontPath . 'OpenSans-Bold.ttf';
            imagettftext($image, 14, 0, $p[0], $p[1]+1, $bgcolor, $font, $geometry['style']['label']);
            imagettftext($image, 14, 0, $p[0], $p[1]-1, $bgcolor, $font, $geometry['style']['label']);
            imagettftext($image, 14, 0, $p[0]-1, $p[1], $bgcolor, $font, $geometry['style']['label']);
            imagettftext($image, 14, 0, $p[0]+1, $p[1], $bgcolor, $font, $geometry['style']['label']);
            imagettftext($image, 14, 0, $p[0], $p[1], $color, $font, $geometry['style']['label']);
            return;
        }

        $radius = $geometry['style']['pointRadius'];
        // Filled circle
        if($geometry['style']['fillOpacity'] > 0){
            $color = $this->getColor(
                $geometry['style']['fillColor'],
                $geometry['style']['fillOpacity'],
                $image);
            imagefilledellipse($image, $p[0], $p[1], 2*$radius, 2*$radius, $color);
        }
        // Circle border
        $color = $this->getColor(
            $geometry['style']['strokeColor'],
            $geometry['style']['strokeOpacity'],
            $image);
        imageellipse($image, $p[0], $p[1], 2*$radius, 2*$radius, $color);
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

}
