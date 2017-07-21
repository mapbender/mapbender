<?php
namespace Mapbender\PrintBundle\Component;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Psr\Log\LoggerInterface;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use OwsProxy3\CoreBundle\Component\WmsProxy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
    /** @var array */
    protected $data;
    /** @var string[] */
    protected $requests = array();

    public function __construct($container)
    {
        $this->container = $container;
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

    /**
     * Builds a png image and emits it directly to the browser
     *
     * @param string $content the job description in valid JSON
     * @return void
     *
     * @todo: converting from JSON encoding is controller responsibility
     * @todo: emitting to browser is controller responsibility
     */
    public function export($content)
    {
        // Clean up internally modified / collected state
        $this->requests = array();
        $this->data = json_decode($content, true);

        foreach ($this->data['requests'] as $i => $layer) {
            if ($layer['type'] != 'wms') {
                continue;
            }
            $this->requests[$i] = $layer['url'];
        }

        if(isset($this->data['vectorLayers'])){
            foreach ($this->data['vectorLayers'] as $idx => $layer){
                $this->data['vectorLayers'][$idx] = json_decode($this->data['vectorLayers'][$idx], true);
            }
        }

        $imagePath = $this->getImages();
        $this->emitImageToBrowser($imagePath);
        unlink($imagePath);
    }

    /**
     * Collect and merge WMS tiles and vector layers into a PNG file.
     *
     * @return string path to merged PNG file
     */
    private function getImages()
    {
        $temp_names = array();
        foreach ($this->requests as $k => $url) {
            
            $url = strstr($url, '&WIDTH', true);
            $width = '&WIDTH=' . $this->data['width'];
            $height = '&HEIGHT=' . $this->data['height'];
            $url .= $width . $height;
            
            $this->getLogger()->debug("Image Export Request Nr.: " . $k . ' ' . $url);

            $mapRequestResponse = $this->mapRequest($url);

            $imagename = $this->makeTempFile('mb_imgexp');
            $temp_names[] = $imagename;
            $rawImage = $this->serviceResponseToGdImage($imagename, $mapRequestResponse);


            if ($rawImage !== null) {
                $this->forceToRgba($imagename, $rawImage, $this->data['requests'][$k]['opacity']);
                $width = imagesx($rawImage);
                $height = imagesy($rawImage);
            }
        }
        // create final merged image
        $finalImageName = $this->makeTempFile('mb_imgexp_merged');
        $mergedImage = imagecreatetruecolor($width, $height);
        $bg = ImageColorAllocate($mergedImage, 255, 255, 255);
        imagefilledrectangle($mergedImage, 0, 0, $width, $height, $bg);
        imagepng($mergedImage, $finalImageName);
        foreach ($temp_names as $temp_name) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if (is_file($temp_name) && finfo_file($finfo, $temp_name) == 'image/png') {
                $dest = imagecreatefrompng($finalImageName);
                $src = imagecreatefrompng($temp_name);
                imagecopy($dest, $src, 0, 0, 0, 0, $width, $height);
                imagepng($dest, $finalImageName);
            }
            unlink($temp_name);
            finfo_close($finfo);
        }

        if (isset($this->data['vectorLayers'])) {
            $this->drawFeatures($finalImageName);
        }
        return $finalImageName;
    }

    /**
     * Convert a GD image to true-color RGBA and write it back to the file
     * system.
     *
     * @param string $imageName will be overwritten
     * @param resource $imageResource source image
     * @param float $opacity in [0;1]
     */
    protected function forceToRgba($imageName, $imageResource, $opacity)
    {
        $width = imagesx($imageResource);
        $height = imagesy($imageResource);

        // Make sure input image is truecolor with alpha, regardless of input mode!
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagecopyresampled($image, $imageResource, 0, 0, 0, 0, $width, $height, $width, $height);

        // Taking the painful way to alpha blending. Stupid PHP-GD
        if (1.0 !== $opacity) {
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
        imagepng($image, $imageName);
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
            try {
                $serviceType = strtolower($proxyQuery->getServiceType());
            } catch (\Exception $e) {
                // fired when null "content" is loaded as an XML document...
                $serviceType = null;
            }
            $proxyConfig = $this->container->getParameter('owsproxy.proxy');
            switch ($serviceType) {
                case "wms":
                    /** @var EventDispatcherInterface $eventDispatcher */
                    $eventDispatcher = $this->container->get('event_dispatcher');
                    $proxy = new WmsProxy($eventDispatcher, $proxyConfig, $proxyQuery, $this->getLogger());
                    break;
                default:
                    $proxy = new CommonProxy($proxyConfig, $proxyQuery, $this->getLogger());
                    break;
            }
            /** @var \Buzz\Message\Response $buzzResponse */
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
     * Converts a http response to a GD image, respecting the mimetype.
     *
     * @param string $storagePath for temp file storage
     * @param Response $response
     * @return resource|null GD image or null on failure
     */
    protected function serviceResponseToGdImage($storagePath, $response)
    {
        file_put_contents($storagePath, $response->getContent());
        $contentType = trim($response->headers->get('content-type'));
        switch ($contentType) {
            case (preg_match("/image\/png/", $contentType) ? $contentType : !$contentType) :
                return imagecreatefrompng($storagePath);
                break;
            case (preg_match("/image\/jpeg/", $contentType) ? $contentType : !$contentType) :
                return imagecreatefromjpeg($storagePath);
                break;
            case (preg_match("/image\/gif/", $contentType) ? $contentType : !$contentType) :
                return imagecreatefromgif($storagePath);
                break;
            default:
                return null;
                $this->getLogger()->debug("Unknown mimetype " . trim($response->headers->get('content-type')));
            //throw new \RuntimeException("Unknown mimetype " . trim($response->headers->get('content-type')));
        }
    }

    /**
     * @param string $imagePath
     */
    protected function emitImageToBrowser($imagePath)
    {
        $finalImage = imagecreatefrompng($imagePath);
        if ($this->data['format'] == 'png') {
            header("Content-type: image/png");
            header("Content-Disposition: attachment; filename=export_" . date("YmdHis") . ".png");
            //header('Content-Length: ' . filesize($file));
            imagepng($finalImage);
        } else {
            header("Content-type: image/jpeg");
            header("Content-Disposition: attachment; filename=export_" . date("YmdHis") . ".jpg");
            //header('Content-Length: ' . filesize($file));
            imagejpeg($finalImage, null, 85);
        }
    }

    private function drawFeatures($finalImageName)
    {
        $image = imagecreatefrompng($finalImageName);
        imagesavealpha($image, true);
        imagealphablending($image, true);

        foreach($this->data['vectorLayers'] as $idx => $layer) {
            foreach($layer['geometries'] as $geometry) {
                $renderMethodName = 'draw' . $geometry['type'];

                if(!method_exists($this, $renderMethodName)) {
                    continue;
                    //throw new \RuntimeException('Can not draw geometries of type "' . $geometry['type'] . '".');
                }

                $this->$renderMethodName($geometry, $image);
            }
        }
        imagepng($image, $finalImageName);
    }

    private function getColor($color, $alpha, $image)
    {
        list($r, $g, $b) = CSSColorParser::parse($color);

        if(0 == $alpha) {
            return ImageColorAllocate($image, $r, $g, $b);
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
                $p = $this->realWorld2mapPos($c[0], $c[1]);
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
                $p = $this->realWorld2mapPos($c[0], $c[1]);
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

            $from = $this->realWorld2mapPos(
                $geometry['coordinates'][$i - 1][0],
                $geometry['coordinates'][$i - 1][1]);
            $to = $this->realWorld2mapPos(
                $geometry['coordinates'][$i][0],
                $geometry['coordinates'][$i][1]);

            imageline($image, $from[0], $from[1], $to[0], $to[1], $color);
        }
    }

    private function drawPoint($geometry, $image)
    {
        $c = $geometry['coordinates'];

        $p = $this->realWorld2mapPos($c[0], $c[1]);

        if(isset($geometry['style']['label'])){
            // draw label with white halo
            $color = $this->getColor('#ff0000', 1, $image);
            $bgcolor = $this->getColor('#ffffff', 1, $image);
            $fontPath = $this->container->getParameter('kernel.root_dir') . '/Resources/MapbenderPrintBundle/fonts/';
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

    private function realWorld2mapPos($rw_x,$rw_y)
    {
        $quality = 72;
        $map_width = $this->data['extentwidth'];
        $map_height = $this->data['extentheight'];
        $centerx = $this->data['centerx'];
        $centery = $this->data['centery'];

        $height = $this->data['height'];
        $width = $this->data['width'];

        $minX = $centerx - $map_width * 0.5;
        $minY = $centery - $map_height * 0.5;
        $maxX = $centerx + $map_width * 0.5;
        $maxY = $centery + $map_height * 0.5;

        $extentx = $maxX - $minX ;
	$extenty = $maxY - $minY ;

        $pixPos_x = (($rw_x - $minX)/$extentx) * $width;
	$pixPos_y = (($maxY - $rw_y)/$extenty) * $height;

        $pixPos = array($pixPos_x, $pixPos_y);

	return $pixPos;
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
