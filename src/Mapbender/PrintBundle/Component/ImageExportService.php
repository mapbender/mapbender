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

    public function __construct($container)
    {
        $this->container = $container;
        $this->tempdir = sys_get_temp_dir();
    }

    public function export($content)
    {
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

        $this->getImages();
    }

    private function getImages()
    {
        $temp_names = array();
        foreach ($this->requests as $k => $url) {
            
            $url = strstr($url, '&WIDTH', true);
            $width = '&WIDTH=' . $this->data['width'];
            $height = '&HEIGHT=' . $this->data['height'];
            $url .= $width . $height;
            
            $this->container->get("logger")->debug("Image Export Request Nr.: " . $k . ' ' . $url);
            $attributes = array();
            $attributes['_controller'] = 'OwsProxy3CoreBundle:OwsProxy:entryPoint';
            $subRequest = new Request(array(
                'url' => $url
                ), array(), $attributes, array(), array(), array(), '');
            $response = $this->container->get('http_kernel')->handle($subRequest,
                HttpKernelInterface::SUB_REQUEST);

            $imagename = tempnam($this->tempdir, 'mb_imgexp');
            $temp_names[] = $imagename;

            file_put_contents($imagename, $response->getContent());
            $rawImage = null;
            switch (trim($response->headers->get('content-type'))) {
                case 'image/png; mode=8bit' : 
                case 'image/png' :
                    $rawImage = imagecreatefrompng($imagename);
                    break;
                case 'image/jpeg' :
                    $rawImage = imagecreatefromjpeg($imagename);
                    break;
                case 'image/gif' :
                    $rawImage = imagecreatefromgif($imagename);
                    break;
                default:
                    continue;
                    $this->container->get("logger")->debug("Unknown mimetype " . trim($response->headers->get('content-type')));
                //throw new \RuntimeException("Unknown mimetype " . trim($response->headers->get('content-type')));
            }

            if ($rawImage !== null) {
                $width = imagesx($rawImage);
                $height = imagesy($rawImage);

                // Make sure input image is truecolor with alpha, regardless of input mode!
                $image = imagecreatetruecolor($width, $height);
                imagealphablending($image, false);
                imagesavealpha($image, true);
                imagecopyresampled($image, $rawImage, 0, 0, 0, 0, $width, $height, $width, $height);

                // Taking the painful way to alpha blending. Stupid PHP-GD
                $opacity = floatVal($this->data['requests'][$k]['opacity']);
                if(1.0 !== $opacity) {
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
                imagepng($image, $imagename);
            }
        }

        // create final merged image
        $finalImageName = tempnam($this->tempdir, 'mb_imgexp_merged');
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

        $finalImage = imagecreatefrompng($finalImageName);
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
        unlink($finalImageName);
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
}
