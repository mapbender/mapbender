<?php
namespace Mapbender\PrintBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
    /** @var string[] plain WMS URLs */
    protected $mapRequests = array();

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
     * Todo
     *
     */
    public function export($content)
    {
        $this->data = json_decode($content, true);

        if (isset($this->data['vectorLayers'])) {
            foreach ($this->data['vectorLayers'] as $idx => $layer) {
                $this->data['vectorLayers'][$idx] = json_decode($this->data['vectorLayers'][$idx], true);
            }
        }
//        print "<pre>";
//        print_r($this->data);
//        print "</pre>";
//        die();
        $this->format = $this->data['format'];
        $this->requests = $this->data['requests'];
        // resource dir
        $this->resourceDir = $this->container->getParameter('kernel.root_dir') . '/Resources/MapbenderPrintBundle';
        $imgWidth = $this->data['width'];
        $imgHeight = $this->data['height'];
        $temp_names = $this->getImages($imgWidth, $this->data['height']);

        // create final merged image
        $finalimagename = tempnam($this->tempDir, 'mb_imgexp_merged');
        $finalImage = imagecreatetruecolor($imgWidth, $imgHeight);
        $bg = ImageColorAllocate($finalImage, 255, 255, 255);
        imagefilledrectangle($finalImage, 0, 0, $imgWidth, $imgHeight, $bg);
        imagepng($finalImage, $finalimagename);
        foreach ($temp_names as $temp_name) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if (is_file($temp_name) && finfo_file($finfo, $temp_name) == 'image/png') {
                $dest = imagecreatefrompng($finalimagename);
                $src = imagecreatefrompng($temp_name);
                imagecopy($dest, $src, 0, 0, 0, 0, $imgWidth, $imgHeight);
                imagepng($dest, $finalimagename);
            }
            unlink($temp_name);
            finfo_close($finfo);
        }

        $date = date("Ymd");
        $time = date("His");

        $this->finalimagename = $finalimagename;

        if (isset($this->data['vectorLayers'])) {
            $this->drawFeatures();
        }

        $file = $this->finalimagename;
        $image = imagecreatefrompng($file);
        if ($this->format == 'png') {
            header("Content-type: image/png");
            header("Content-Disposition: attachment; filename=export_" . $date . $time . ".png");
            //header('Content-Length: ' . filesize($file));
            imagepng($image);
        } else {
            header("Content-type: image/jpeg");
            header("Content-Disposition: attachment; filename=export_" . $date . $time . ".jpg");
            //header('Content-Length: ' . filesize($file));
            imagejpeg($image, null, 85);
        }
        unlink($this->finalimagename);
    }

    private function getImages($width, $height)
    {
        $logger = $this->container->get("logger");
        $imageNames = array();
        $rawImage = null;
        foreach ($this->requests as $i => $request) {
            $imageName = tempnam($this->tempDir, 'mb_print');
            if (is_array($request) && $request['type'] === 'wms') { // wms
                $logger->debug("Print Request Nr.: " . $i . ' ' . $request['url']);
                $rawImage = $this->getImage($request['url'], $imageName);
            } elseif (is_array($request) && $request['type'] === 'tms') { // only tms
                $logger->debug("Print Request Nr.: " . $i . ' ' . $request['url']);
                $rawImage = $this->getTmsImage($request, $imageName, $width, $height);
            } elseif (is_array($request) && $request['type'] === 'wmts') { // only wmts
                $logger->debug("Print Request Nr.: " . $i . ' ' . $request['url']);
                $rawImage = $this->getWmtsImage($request, $imageName, $width, $height);
            } else { // tms, wmts
                continue;
            }
            if ($rawImage !== null) {
                $imageNames[] = $imageName;
                // Make sure input image is truecolor with alpha, regardless of input mode!
                $image = imagecreatetruecolor($width, $height);
                $this->drawImage($image, $rawImage, 0, 0, 0, 0, $width, $height, $width, $height);
                // Taking the painful way to alpha blending. Stupid PHP-GD
                $opacity = 1.0;#floatVal(???);
                if (1.0 !== $opacity) {
                    $width = imagesx($dst_image);
                    $height = imagesy($dst_image);
                    for ($x = 0; $x < $width; $x++) {
                        for ($y = 0; $y < $height; $y++) {
                            $colorIn = imagecolorsforindex($dst_image, imagecolorat($dst_image, $x, $y));
                            $alphaOut = 127 - (127 - $colorIn['alpha']) * $opacity;

                            $colorOut = imagecolorallocatealpha(
                                $dst_image,
                                $colorIn['red'],
                                $colorIn['green'],
                                $colorIn['blue'],
                                $alphaOut
                            );
                            imagesetpixel($dst_image, $x, $y, $colorOut);
                            imagecolordeallocate($dst_image, $colorOut);
                        }
                    }
                }
                imagepng($image, $imageName);
            }
        }
        return $imageNames;
    }

    private function getWmtsImage($request, $imageName, $width, $height)
    {
        $logger = $this->container->get("logger");
//        $msg = '';
        $metersPerUnit = 1; // only 1 m /
        $scaleDenominator = floatval($this->data['scale']);
        $tilematrixset = $request['matrixset'];
        if (isset($request['options']['style'])) {
            $style = $request['options']['style'];
        }
        $matrix = null;
        //var_dump(array($tilematrixset,$scaleDenominator));die;
        foreach ($tilematrixset['tilematrices'] as $tilematrix) {
            if ($scaleDenominator === round($tilematrix['scaleDenominator'], 5)) {
                $matrix = $tilematrix;
                break;
            }
        }
        if (!$matrix) {
            return null;
        }
        $tileWidth = isset($matrix['tileWidth']) ? $matrix['tileWidth'] : $tilematrixset['tileSize'][0];
        $tileHeight = isset($matrix['tileHeight']) ? $matrix['tileHeight'] : $tilematrixset['tileSize'][1];
        $topLeftX = isset($matrix['topLeftCorner']) ? $matrix['topLeftCorner'][0] : $tilematrixset['origin'][0];
        $topLeftY = isset($matrix['topLeftCorner']) ? $matrix['topLeftCorner'][1] : $tilematrixset['origin'][1];
        $bbox = $this->data['bbox'];
        $pixelSpan  = $scaleDenominator * 0.00028 / $metersPerUnit;
        $tileSpanX = $tileWidth * $pixelSpan;
        $tileSpanY = $tileHeight * $pixelSpan;

        $matrixSizeX = $matrix['matrixSize'][0];
        $matrixSizeY = $matrix['matrixSize'][1];
        $matrixBbox = array(
            $topLeftX,
            $topLeftY - ($tileSpanY * $matrixSizeY),
            $topLeftX + ($tileSpanX * $matrixSizeX),
            $topLeftY
        );
        $urlHelp = str_replace('{TileMatrixSet}', $tilematrixset['identifier'], $request['url']);
        $urlHelp = str_replace('{TileMatrix}', $matrix['identifier'], $urlHelp);
        if (isset($style)) {
            $urlHelp = str_replace('{Style}', $style, $urlHelp);
        }
        $image = imagecreatetruecolor($width, $height);
        for ($tytop = $bbox[3]; $tytop >= $bbox[1]- $tileSpanY; $tytop = $tytop - $tileSpanY) {
            $tybottom = $tytop - $tileSpanY;
            if ($tybottom <= $matrixBbox[3] && $tytop >= $matrixBbox[1]) { // ??? check
                for ($txleft = $bbox[0]; $txleft <= $bbox[2] + $tileSpanX; $txleft = $txleft + $tileSpanX) {
                    $txright = $txleft + $tileSpanX;
                    if ($txright >= $matrixBbox[0] && $txleft <= $matrixBbox[2]) {
                        $xnum = floor(($txleft - $matrixBbox[0]) / $tileSpanX);
                        $ynum = $matrixSizeY - floor(($tytop - $matrixBbox[1]) / $tileSpanY) - 1;
                        $url = str_replace('{TileCol}', $xnum, $urlHelp);
                        $url = str_replace('{TileRow}', $ynum, $url);
                        $logger->debug("WMTS: " . $xnum . "-" . $ynum . " " . $url);
//                        $msg .= $xnum . "\t" . $ynum . "\t" . $url .'\n\n';
                        $imageName = tempnam($this->tempDir, 'mb_print_wmts' . $xnum . "-" . $ynum);

                        $rawImage = $this->getImage($url, $imageName);
                        if ($rawImage !== null) {
                            $tx = $xnum * $tileSpanX + $matrixBbox[0];
                            $ty = $matrixBbox[3] - $ynum * $tileSpanY;
                            $txpos = intval(round(($tx - $bbox[0]) / $pixelSpan));
                            $typos = intval(round(($bbox[3] - $ty) / $pixelSpan));
//                            $msg .= $txpos . "\t" . $typos . "\t" . $xnum . "\t" . $ynum .'\t' . $url . '\n\n';
                            $image = $this->drawImage($image, $rawImage, $txpos, $typos, 0, 0, $tileWidth, $tileHeight, $tileWidth, $tileHeight);
                            unlink($imageName);
                        }
                    }
                }
            }
        }
//        $logger->debug($msg);
        return $image;
    }



    private function getTmsImage($request, $imageName, $width, $height)
    {
        $logger = $this->container->get("logger");
//        $msg = '';
        $tilematrixset = $request['options']['tilematrixset'];
        $tileWidth = $tilematrixset['tileSize'][0];
        $tileHeight = $tilematrixset['tileSize'][1];
        $tileMatrixStartX = $tilematrixset['origin'][0];
        $tileMatrixStartY = $tilematrixset['origin'][1];
        $matrix = $tilematrixset['tilesets'][$request['zoom']];
        $unitsPerPixel = $matrix['units-per-pixel'];
        $bbox = $this->data['bbox'];
        $matrixBbox = $request['options']['bbox'][$tilematrixset['supportedCrs']];
        $xstep = $unitsPerPixel * $tileWidth;
        $ystep = $unitsPerPixel * $tileHeight;
        $image = imagecreatetruecolor($width, $height);

        $minx = $bbox[0] > $matrixBbox[0] ? $bbox[0] : $matrixBbox[0];
        $miny = $bbox[1] > $matrixBbox[1] ? $bbox[1] : $matrixBbox[1];
        $maxx = $bbox[2] < $matrixBbox[2] ? $bbox[2] : $matrixBbox[2];
        $maxy = $bbox[3] < $matrixBbox[3] ? $bbox[3] : $matrixBbox[3];
        if ($minx < $maxx && $miny < $maxy) {
            $ynum = floor(($miny - $tileMatrixStartY) / $ystep);
            $ty0 = $ynum * $ystep + $tileMatrixStartY;
            for ($tileBottom = $ty0; $tileBottom <= $maxy; $tileBottom += $ystep, $ynum++) {
                $xnum = floor(($minx - $tileMatrixStartX) / $xstep);
                $tx0 = $xnum * $xstep + $tileMatrixStartX;
                for ($tileLeft = $tx0; $tileLeft <= $maxx; $tileLeft += $xstep, $xnum++) {
                    $url = $matrix['href'] . "/"  . $xnum . "/" . $ynum . "." . $request['options']['format_ext'];
                    $logger->debug("TMS: " . $xnum . "-" . $ynum . " " . $url);
                    $imageName = tempnam($this->tempDir, 'mb_print_tms' . $xnum . "-" . $ynum);
                    $rawImage = $this->getImage($url, $imageName);
                    if ($rawImage !== null) {
                        $txpos = intval(round(($tileLeft - $minx) / $unitsPerPixel));
                        $typos = intval(round(($miny - $tileBottom) / $unitsPerPixel)) + $height - $tileHeight;
//                        $msg .= $txpos . "\t" . $typos . "\t" . $xnum . "\t" . $ynum .'\t' . $url . '\n\n';
                        $image = $this->drawImage($image, $rawImage, $txpos, $typos, 0, 0, $tileWidth, $tileHeight, $tileWidth, $tileHeight);
                        unlink($imageName);
                    }
                }
            }
        }
//        $logger->debug($msg);
        return $image;
    }

    private function getImage($request, $imageName)
    {
        $logger = $this->container->get("logger");
        $path = array(
            '_controller' => 'OwsProxy3CoreBundle:OwsProxy:genericProxy',
            'url' => $request
        );
        $subRequest = $this->container->get('request')->duplicate(array(), null, $path);
        $response = $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

        file_put_contents($imageName, $response->getContent());
        $rawImage = null;
        switch (trim($response->headers->get('content-type'))) {
            case 'image/png':
                $rawImage = imagecreatefrompng($imageName);
                break;
            case 'image/jpeg':
                $rawImage = imagecreatefromjpeg($imageName);
                break;
            case 'image/gif':
                $rawImage = imagecreatefromgif($imageName);
                break;
            default:
                $logger->debug("Unknown mimetype " . trim($response->headers->get('content-type')));
                continue;
        }
        return $rawImage;
    }

    private function drawImage(&$dst_image, $src_image, $dst_x , $dst_y , $src_x , $src_y , $dst_w , $dst_h , $src_w , $src_h)
    {
        imagealphablending($dst_image, false);
        imagesavealpha($dst_image, true);
        imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
        return $dst_image;
    }

    private function drawFeatures()
    {
        $image = imagecreatefrompng($this->finalimagename);
        imagesavealpha($image, true);
        imagealphablending($image, true);

        foreach ($this->data['vectorLayers'] as $idx => $layer) {
            foreach ($layer['geometries'] as $geometry) {
                $renderMethodName = 'draw' . $geometry['type'];

                if (!method_exists($this, $renderMethodName)) {
                    continue;
                    //throw new \RuntimeException('Can not draw geometries of type "' . $geometry['type'] . '".');
                }

                $this->$renderMethodName($geometry, $image);
            }
        }
        imagepng($image, $this->finalimagename);
    }

    private function getColor($color, $alpha, $image)
    {
        list($r, $g, $b) = CSSColorParser::parse($color);

        if (0 == $alpha) {
            return ImageColorAllocate($image, $r, $g, $b);
        } else {
            $a = (1 - $alpha) * 127.0;
            return imagecolorallocatealpha($image, $r, $g, $b, $a);
        }
    }

    private function drawPolygon($geometry, $image)
    {
        foreach ($geometry['coordinates'] as $ring) {
            if (count($ring) < 3) {
                continue;
            }

            $points = array();
            foreach ($ring as $c) {
                $p = $this->realWorld2mapPos($c[0], $c[1]);
                $points[] = floatval($p[0]);
                $points[] = floatval($p[1]);
            }
            imagesetthickness($image, 0);
            // Filled area
            if ($geometry['style']['fillOpacity'] > 0) {
                $color = $this->getColor(
                    $geometry['style']['fillColor'],
                    $geometry['style']['fillOpacity'],
                    $image
                );
                imagefilledpolygon($image, $points, count($ring), $color);
            }
            // Border
            $color = $this->getColor(
                $geometry['style']['strokeColor'],
                $geometry['style']['strokeOpacity'],
                $image
            );
            imagesetthickness($image, $geometry['style']['strokeWidth']);
            imagepolygon($image, $points, count($ring), $color);
        }
    }

    private function drawMultiPolygon($geometry, $image)
    {
        foreach ($geometry['coordinates'][0] as $ring) {
            if (count($ring) < 3) {
                continue;
            }

            $points = array();
            foreach ($ring as $c) {
                $p = $this->realWorld2mapPos($c[0], $c[1]);
                $points[] = floatval($p[0]);
                $points[] = floatval($p[1]);
            }
            imagesetthickness($image, 0);
            // Filled area
            if ($geometry['style']['fillOpacity'] > 0) {
                $color = $this->getColor(
                    $geometry['style']['fillColor'],
                    $geometry['style']['fillOpacity'],
                    $image
                );
                imagefilledpolygon($image, $points, count($ring), $color);
            }
            // Border
            $color = $this->getColor(
                $geometry['style']['strokeColor'],
                $geometry['style']['strokeOpacity'],
                $image
            );
            imagesetthickness($image, $geometry['style']['strokeWidth']);
            imagepolygon($image, $points, count($ring), $color);
        }
    }

    private function drawLineString($geometry, $image)
    {
        $color = $this->getColor(
            $geometry['style']['strokeColor'],
            $geometry['style']['strokeOpacity'],
            $image
        );
        imagesetthickness($image, $geometry['style']['strokeWidth']);

        for ($i = 1; $i < count($geometry['coordinates']); $i++) {
            $from = $this->realWorld2mapPos(
                $geometry['coordinates'][$i - 1][0],
                $geometry['coordinates'][$i - 1][1]
            );
            $to = $this->realWorld2mapPos(
                $geometry['coordinates'][$i][0],
                $geometry['coordinates'][$i][1]
            );

            imageline($image, $from[0], $from[1], $to[0], $to[1], $color);
        }
    }

    private function drawPoint($geometry, $image)
    {
        $c = $geometry['coordinates'];

        $p = $this->realWorld2mapPos($c[0], $c[1]);

        if (isset($geometry['style']['label'])) {
            // draw label with white halo
            $color = $this->getColor('#ff0000', 1, $image);
            $bgcolor = $this->getColor('#ffffff', 1, $image);
            $fontPath = $this->resourceDir.'/fonts/';
            $font = $fontPath . 'Trebuchet_MS.ttf';
            imagettftext($image, 14, 0, $p[0], $p[1]+1, $bgcolor, $font, $geometry['style']['label']);
            imagettftext($image, 14, 0, $p[0], $p[1]-1, $bgcolor, $font, $geometry['style']['label']);
            imagettftext($image, 14, 0, $p[0]-1, $p[1], $bgcolor, $font, $geometry['style']['label']);
            imagettftext($image, 14, 0, $p[0]+1, $p[1], $bgcolor, $font, $geometry['style']['label']);
            imagettftext($image, 14, 0, $p[0], $p[1], $color, $font, $geometry['style']['label']);
            return;
        }

        $radius = $geometry['style']['pointRadius'];
        // Filled circle
        if ($geometry['style']['fillOpacity'] > 0) {
            $color = $this->getColor(
                $geometry['style']['fillColor'],
                $geometry['style']['fillOpacity'],
                $image
            );
            imagefilledellipse($image, $p[0], $p[1], 2*$radius, 2*$radius, $color);
        }
        // Circle border
        $color = $this->getColor(
            $geometry['style']['strokeColor'],
            $geometry['style']['strokeOpacity'],
            $image
        );
        imageellipse($image, $p[0], $p[1], 2*$radius, 2*$radius, $color);
    }

    private function realWorld2mapPos($rw_x, $rw_y)
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

        $extentx = $maxX - $minX;
        $extenty = $maxY - $minY;
        $pixPos_x = (($rw_x - $minX)/$extentx) * $width;
        $pixPos_y = (($maxY - $rw_y) / $extenty) * $height;

        $pixPos = array($pixPos_x, $pixPos_y);
        return $pixPos;
    }
}