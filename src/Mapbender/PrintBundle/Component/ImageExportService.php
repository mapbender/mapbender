<?php
namespace Mapbender\PrintBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    /**
     * Todo
     *
     */
    public function export($content)
    {
        $this->data = json_decode($content, true);

        foreach ($this->data['vectorLayers'] as $idx => $layer){
            $this->data['vectorLayers'][$idx] = json_decode($this->data['vectorLayers'][$idx], true);
        }
        
//        print "<pre>";
//        print_r($this->data);
//        print "</pre>";
//        die();
        $this->format = $this->data['format'];
        $this->requests = $this->data['requests'];
        $this->getImages();
    }

    /**
     * Todo
     *
     */
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

            $tempdir = $this->tempdir;
            $imagename = tempnam($tempdir, 'mb_imgexp');
            $temp_names[] = $imagename;

            file_put_contents($imagename, $response->getContent());
            $im = null;
            switch (trim($response->headers->get('content-type'))) {
                case 'image/png; mode=8bit' : 
                case 'image/png' :
                    $im = imagecreatefrompng($imagename);
                    break;
                case 'image/jpeg' :
                    $im = imagecreatefromjpeg($imagename);
                    break;
                case 'image/gif' :
                    $im = imagecreatefromgif($imagename);
                    break;
                default:
                    continue;
                    $this->container->get("logger")->debug("Unknown mimetype " . trim($response->headers->get('content-type')));
                //throw new \RuntimeException("Unknown mimetype " . trim($response->headers->get('content-type')));
            }

            if ($im !== null) {
                imagesavealpha($im, true);
                imagepng($im, $imagename);

                $this->image_width = imagesx($im);
                $this->image_height = imagesy($im);
            }
        }

        // create final merged image
        $finalimagename = tempnam($tempdir, 'mb_imgexp_merged');
        $finalImage = imagecreatetruecolor($this->image_width,
            $this->image_height);
        $bg = ImageColorAllocate($finalImage, 255, 255, 255);
        imagefilledrectangle($finalImage, 0, 0, $this->image_width,
            $this->image_height, $bg);
        imagepng($finalImage, $finalimagename);
        foreach ($temp_names as $temp_name) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if (is_file($temp_name) && finfo_file($finfo, $temp_name) == 'image/png') {
                $dest = imagecreatefrompng($finalimagename);
                $src = imagecreatefrompng($temp_name);
                imagecopy($dest, $src, 0, 0, 0, 0, $this->image_width,
                    $this->image_height);
                imagepng($dest, $finalimagename);
            }
            unlink($temp_name);
            finfo_close($finfo);
        }

        $date = date("Ymd");
        $time = date("His");

        $this->finalimagename = $finalimagename;

        if (sizeof($this->data['vectorLayers']) > 0) {
            $this->drawFeatures();
        }

        $file = $this->finalimagename;
        $image = imagecreatefrompng($file);
        if ($this->format == 'png') {
            header("Content-type: image/png");
            header("Content-Disposition: attachment; filename=export_" . $date . $time . ".png");
            header('Content-Length: ' . filesize($file));
            imagepng($image);
        } else {
            header("Content-type: image/jpeg");
            header("Content-Disposition: attachment; filename=export_" . $date . $time . ".jpg");
            header('Content-Length: ' . filesize($file));
            imagejpeg($image, null, 85);
        }
        unlink($this->finalimagename);
    }

    private function drawFeatures()
    {
        $image = imagecreatefrompng($this->finalimagename);
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
        imagepng($image, $this->finalimagename);
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

            // Filled area
            $color = $this->getColor(
                $geometry['style']['fillColor'],
                $geometry['style']['fillOpacity'],
                $image);
            imagefilledpolygon($image, $points, count($ring), $color);

            // Border
            $color = $this->getColor(
                $geometry['style']['strokeColor'],
                $geometry['style']['strokeOpacity'],
                $image);
            imagesetthickness($image, $geometry['style']['strokeWidth']);
            imagepolygon($image, $points, count($ring), $color);
        }
        // reset image thickness !!
        imagesetthickness($image, 0);
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

        // reset image thickness !!
        imagesetthickness($image, 0);
    }

    private function drawPoint($geometry, $image)
    {
        $c = $geometry['coordinates'];

        $p = $this->realWorld2mapPos($c[0], $c[1]);

        if(isset($geometry['style']['label'])){
            $color = $this->getColor(
                '#ff0000',
                1,
                $image);
            $fontPath = $this->container->get('kernel')->getRootDir().'/Resources/MapbenderPrintBundle/fonts/';
            imagettftext($image, 14, 0, $p[0], $p[1], $color, $fontPath.'Trebuchet_MS.ttf', $geometry['style']['label']);
            return;
        }

        $radius = $geometry['style']['pointRadius'];
        // Filled circle
        $color = $this->getColor(
            $geometry['style']['fillColor'],
            $geometry['style']['fillOpacity'],
            $image);

        imagefilledellipse($image, $p[0], $p[1], 2*$radius, 2*$radius, $color);

        // Circle border
        $color = $this->getColor(
            $geometry['style']['strokeColor'],
            $geometry['style']['strokeOpacity'],
            $image);
        imagesetthickness($image, $geometry['style']['strokeWidth']);
        imageellipse($image, $p[0], $p[1], 2*$radius, 2*$radius, $color);

        // reset image thickness !!
        imagesetthickness($image, 0);
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
