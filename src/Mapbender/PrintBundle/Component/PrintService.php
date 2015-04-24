<?php
namespace Mapbender\PrintBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use FPDF_FPDF;
use FPDF_FPDI;
use Mapbender\PrintBundle\Component\PDF_ImageAlpha;

/**
 * The print service.
 *
 * @author Stefan Winkelmann
 */
class PrintService
{

    public function __construct($container)
    {
        $this->container = $container;
        $this->tempdir = sys_get_temp_dir();
    }

    /**
     * The main print function.
     *
     */
    public function doPrint($content)
    {
        $this->data = $content;
        $template = $this->data['template'];
//        print "<pre>";
//        print_r($this->data);
//        print "</pre>";
//        die();
        $this->getTemplateConf($template);
        $this->createUrlArray();
        $this->addReplacePattern();
        $this->setMapParameter();

        if ($this->data['rotation'] == 0) {
            $this->setExtent();
            $this->setImageSize();
            $this->getImages();
        } else {
            $this->rotate();
        }
        return $this->buildPdf();
    }

    /**
     * Get the configuration from the template odg.
     *
     */
    private function getTemplateConf($template)
    {
        $odgParser = new OdgParser($this->container);
        $this->conf = $odgParser->getConf($template);
    }

    /**
     * Get the configuration from the template odg.
     *
     */
    private function createUrlArray()
    {
        foreach ($this->data['layers'] as $i => $layer) {
            if($layer['type'] != 'wms') {
                continue;
            }
            $url = strstr($this->data['layers'][$i]['url'], '&BBOX', true);
            $this->layer_urls[$i] = $url;
            //opacity
            $this->layerOpacity[$i] = $this->data['layers'][$i]['opacity']*100;
        }
    }

    private function addReplacePattern()
    {
        if(!isset($this->data['replace_pattern'])){
            return;
        }

        $quality = $this->data['quality'];
        $default = '';
        foreach ($this->layer_urls as $k => $url) {
            foreach ($this->data['replace_pattern'] as $rKey => $pattern) {
                if(isset($pattern['default'])){
                    if(isset($pattern['default'][$quality])){
                        $default = $pattern['default'][$quality];
                    }
                    continue;
                }
                if(strpos($url,$pattern['pattern']) === false){
                    continue;
                }
                if(strpos($url,$pattern['pattern']) !== false){
                    if(isset($pattern['replacement'][$quality])){
                        $url = str_replace($pattern['pattern'], $pattern['replacement'][$quality], $url);
                        $signer = $this->container->get('signer');
                        $this->layer_urls[$k] = $signer->signUrl($url);
                        continue 2;
                    }
                }

            }
           $url .= $default;
           $this->layer_urls[$k] = $url;
        }
    }

    /**
     * Todo
     *
     */
    private function setMapParameter()
    {
        $conf = $this->conf;
        $quality = $this->data['quality'];
        $this->orientation = $conf['orientation'];
        $this->x_ul = $conf['map']['x'] * 10;
        $this->y_ul = $conf['map']['y'] * 10;
        $this->width = $conf['map']['width'] * 10;
        $this->height = $conf['map']['height'] * 10;
        $this->image_width = round($conf['map']['width'] / 2.54 * $quality);
        $this->image_height = round($conf['map']['height'] / 2.54 * $quality);
    }

    /**
     * Todo
     *
     */
    private function setExtent()
    {
        $map_width = $this->data['extent']['width'];
        $map_height = $this->data['extent']['height'];
        $centerx = $this->data['center']['x'];
        $centery = $this->data['center']['y'];

        $ll_x = $centerx - $map_width * 0.5;
        $ll_y = $centery - $map_height * 0.5;
        $ur_x = $centerx + $map_width * 0.5;
        $ur_y = $centery + $map_height * 0.5;

        $bbox = '&BBOX=' . $ll_x . ',' . $ll_y . ',' . $ur_x . ',' . $ur_y;

        foreach ($this->layer_urls as $k => $url) {
            $url .= $bbox;
            $this->layer_urls[$k] = $url;
        }
    }

    /**
     * Todo
     *
     */
    private function setImageSize()
    {
        foreach ($this->layer_urls as $k => $url) {
            $width = '&WIDTH=' . $this->image_width;
            $height = '&HEIGHT=' . $this->image_height;
            $url .= $width . $height;
            if(!isset($this->data['replace_pattern'])){
                if ($this->data['quality'] != '72') {
                    $url .= '&map_resolution=' . $this->data['quality'];
                }
            }
            $this->layer_urls[$k] = $url;
        }
    }

    /**
     * Todo
     *
     */
    private function getImages()
    {
        $temp_names = array();
        foreach ($this->layer_urls as $k => $url) {
            $this->container->get("logger")->debug("Print Request Nr.: " . $k . ' ' . $url);
            $attributes = array();
            $attributes['_controller'] = 'OwsProxy3CoreBundle:OwsProxy:entryPoint';
            $subRequest = new Request(array(
                'url' => $url
                ), array(), $attributes, array(), array(), array(), '');
            $response = $this->container->get('http_kernel')->handle($subRequest,
                HttpKernelInterface::SUB_REQUEST);

            $tempdir = $this->tempdir;
            $imagename = tempnam($tempdir, 'mb_print');
            $temp_names[] = $imagename;

            file_put_contents($imagename, $response->getContent());
            $om = null;
            switch (trim($response->headers->get('content-type'))) {
                case 'image/png' :
                    $om = imagecreatefrompng($imagename);
                    break;
                case 'image/jpeg' :
                    $om = imagecreatefromjpeg($imagename);
                    break;
                case 'image/gif' :
                    $om = imagecreatefromgif($imagename);
                    break;
                default:
                    $this->container->get("logger")->debug("Unknown mimetype " . trim($response->headers->get('content-type')));
                    continue;
            }

            if ($om !== null) {
                // Make sure input image is truecolor with alpha, regardless of input mode!
                $im = imagecreatetruecolor($this->image_width, $this->image_height);
                imagealphablending($im, false);
                imagesavealpha($im, true);
                imagecopyresampled($im, $om, 0, 0, 0, 0, $this->image_width, $this->image_height, $this->image_width, $this->image_height);


                // Taking the painful way to alpha blending. Stupid PHP-GD
                $opacity = floatVal($this->data['layers'][$k]['opacity']);
                if(1.0 !== $opacity) {
                    $width = imagesx($im);
                    $height = imagesy($im);
                    for ($x = 0; $x < $width; $x++) {
                        for ($y = 0; $y < $height; $y++) {
                            $colorIn = imagecolorsforindex($im, imagecolorat($im, $x, $y));
                            $alphaOut = 127 - (127 - $colorIn['alpha']) * $opacity;

                            $colorOut = imagecolorallocatealpha(
                                $im,
                                $colorIn['red'],
                                $colorIn['green'],
                                $colorIn['blue'],
                                $alphaOut);
                            imagesetpixel($im, $x, $y, $colorOut);
                            imagecolordeallocate($im, $colorOut);
                        }
                    }
                }

                imagepng($im, $imagename);
            }
        }
        // create final merged image
        $finalimagename = tempnam($tempdir, 'mb_print_merged');
        $this->finalimagename = $finalimagename;
        $finalImage = imagecreatetruecolor($this->image_width,
            $this->image_height);
        $bg = ImageColorAllocate($finalImage, 255, 255, 255);
        imagefilledrectangle($finalImage, 0, 0, $this->image_width,
            $this->image_height, $bg);
        foreach ($temp_names as $temp_name) {
            // Note: suppressing the errors IS bad, bad PHP wants us to do it that way
            $src = @imagecreatefrompng($temp_name);
            // Check that imagecreatefrompng did yield something
            if ($src) {
                $dest = $finalImage;
                imagecopy($dest, $src, 0, 0, 0, 0, $this->image_width, $this->image_height);
                imagepng($dest, $finalimagename);
                unlink($temp_name);
            }
        }
    }

    /**
     * Todo
     *
     */
    private function rotate()
    {
        $tempdir = $this->tempdir;
        $rotation = $this->data['rotation'];
        $map_width = $this->data['extent']['width'];
        $map_height = $this->data['extent']['height'];
        $centerx = $this->data['center']['x'];
        $centery = $this->data['center']['y'];

        //set needed extent
        $neededExtentWidth = abs(sin(deg2rad($rotation)) * $map_height) +
            abs(cos(deg2rad($rotation)) * $map_width);
        $neededExtentHeight = abs(sin(deg2rad($rotation)) * $map_width) +
            abs(cos(deg2rad($rotation)) * $map_height);

        $ll_x = $centerx - $neededExtentWidth * 0.5;
        $ll_y = $centery - $neededExtentHeight * 0.5;
        $ur_x = $centerx + $neededExtentWidth * 0.5;
        $ur_y = $centery + $neededExtentHeight * 0.5;

        $bbox = '&BBOX=' . $ll_x . ',' . $ll_y . ',' . $ur_x . ',' . $ur_y;

        //set needed image size
        $neededImageWidth = abs(sin(deg2rad($rotation)) * $this->image_height) +
            abs(cos(deg2rad($rotation)) * $this->image_width);
        $neededImageHeight = abs(sin(deg2rad($rotation)) * $this->image_width) +
            abs(cos(deg2rad($rotation)) * $this->image_height);

        $w = '&WIDTH=' . $neededImageWidth;
        $h = '&HEIGHT=' . $neededImageHeight;

        $temp_names = array();

        foreach ($this->layer_urls as $k => $url) {
            $url .= $bbox . $w . $h;

            if(!isset($this->data['replace_pattern'])){
                if ($this->data['quality'] != '72') {
                    $url .= '&map_resolution=' . $this->data['quality'];
                }
            }

            $this->container->get("logger")->debug("Print Request Nr.: " . $k . ' ' . $url);

            //get image
            $attributes = array();
            $attributes['_controller'] = 'OwsProxy3CoreBundle:OwsProxy:entryPoint';
            $subRequest = new Request(array(
                'url' => $url
                ), array(), $attributes, array(), array(), array(), '');
            $response = $this->container->get('http_kernel')->handle($subRequest,
                HttpKernelInterface::SUB_REQUEST);

            $imagename = tempnam($tempdir, 'mb_print');
            $temp_names[] = $imagename;

            file_put_contents($imagename, $response->getContent());
            $om = null;
            switch (trim($response->headers->get('content-type'))) {
                case 'image/png' :
                    $om = imagecreatefrompng($imagename);
                    break;
                case 'image/jpeg' :
                    $om = imagecreatefromjpeg($imagename);
                    break;
                case 'image/gif' :
                    $om = imagecreatefromgif($imagename);
                    break;
                default:
                    continue;
                    $this->container->get("logger")->debug("Unknown mimetype " . trim($response->headers->get('content-type')));
            }

            if ($om !== null) {
                // Make sure input image is truecolor with alpha, regardless of input mode!
                $im = imagecreatetruecolor($neededImageWidth, $neededImageHeight);
                imagealphablending($im, false);
                imagesavealpha($im, true);
                imagecopyresampled($im, $om, 0, 0, 0, 0, $neededImageWidth, $neededImageHeight, $neededImageWidth, $neededImageHeight);

                // Taking the painful way to alpha blending. Stupid PHP-GD
                $opacity = floatVal($this->data['layers'][$k]['opacity']);
                if(1.0 !== $opacity) {
                    $width = imagesx($im);
                    $height = imagesy($im);
                    for ($x = 0; $x < $width; $x++) {
                        for ($y = 0; $y < $height; $y++) {
                            $colorIn = imagecolorsforindex($im, imagecolorat($im, $x, $y));
                            $alphaOut = 127 - (127 - $colorIn['alpha']) * $opacity;

                            $colorOut = imagecolorallocatealpha(
                                $im,
                                $colorIn['red'],
                                $colorIn['green'],
                                $colorIn['blue'],
                                $alphaOut);
                            imagesetpixel($im, $x, $y, $colorOut);
                            imagecolordeallocate($im, $colorOut);
                        }
                    }
                }

                imagepng($im, $imagename);
            }


        }

        // create temp merged image
        $tempimagename = tempnam($tempdir, 'mb_print_tempmerged');
        $this->finalimagename = $tempimagename;
        $finalImage = imagecreatetruecolor($neededImageWidth,
            $neededImageHeight);
        $bg = ImageColorAllocate($finalImage, 255, 255, 255);
        imagefilledrectangle($finalImage, 0, 0, $neededImageWidth,
            $neededImageHeight, $bg);
        imagepng($finalImage, $tempimagename);
        foreach ($temp_names as $temp_name) {
            // Note: suppressing the errors IS bad, bad PHP wants us to do it that way
            $src = @imagecreatefrompng($temp_name);
            // Check that imagecreatefrompng did yield something
            if ($src) {
                $dest = imagecreatefrompng($tempimagename);
                imagecopy($dest, $src, 0, 0, 0, 0, $neededImageWidth,
                    $neededImageHeight);
                imagepng($dest, $tempimagename);
                unlink($temp_name);
            }
        }

        //draw features
        //$this->drawFeatures();

        //rotate image
        $tempimg = imagecreatefrompng($tempimagename);
        $transColor = imagecolorallocatealpha($tempimg, 255, 255, 255, 127);
        $rotatedImage = imagerotate($tempimg, $rotation, $transColor);
        imagealphablending($rotatedImage, false);
        imagesavealpha($rotatedImage, true);

        $rotatimagename = tempnam($tempdir, 'mb_printrotated');
        imagepng($rotatedImage, $rotatimagename);

        //clip image from rotated
        $rotated_width = round(abs(sin(deg2rad($rotation)) * $neededImageHeight) +
            abs(cos(deg2rad($rotation)) * $neededImageWidth));
        $rotated_height = round(abs(sin(deg2rad($rotation)) * $neededImageWidth) +
            abs(cos(deg2rad($rotation)) * $neededImageHeight));
        $newx = ($rotated_width - $this->image_width ) / 2;
        $newy = ($rotated_height - $this->image_height ) / 2;

        $clippedImageName = tempnam($tempdir, 'mb_printclip');
        $clippedImage = imagecreatetruecolor($this->image_width,
            $this->image_height);
        imagealphablending($clippedImage, false);
        imagesavealpha($clippedImage, true);
        imagecopy($clippedImage, $rotatedImage, 0, 0, $newx, $newy,
            $this->image_width, $this->image_height);
        imagepng($clippedImage, $clippedImageName);
        $this->finalimagename = $clippedImageName;
        unlink($tempimagename);
        unlink($rotatimagename);
    }

    /**
     * Builds the pdf from a given template.
     *
     */
    private function buildPdf()
    {
        require_once('PDF_ImageAlpha.php');
        $tempdir = $this->tempdir;
        $resource_dir = $this->container->getParameter('kernel.root_dir') . '/Resources/MapbenderPrintBundle';
        $format = $this->data['format'];

        if ($format == 'a2') {
            $format = array(420, 594);
        }

        if ($format == 'a1') {
            $format = array(594, 841);
        }

        if ($format == 'a0') {
            $format = array(841, 1189);
        }

        $this->pdf = new PDF_ImageAlpha($this->orientation, 'mm', $format);
        //$this->pdf = new FPDF_FPDI($this->orientation,'mm',$format);
        $pdf = $this->pdf;
        $template = $this->data['template'];
        $pdffile = $resource_dir . '/templates/' . $template . '.pdf';
        $pagecount = $pdf->setSourceFile($pdffile);
        $tplidx = $pdf->importPage(1);

        $pdf->SetAutoPageBreak(false);
        $pdf->addPage();
        $pdf->useTemplate($tplidx);

        foreach ($this->conf['fields'] as $k => $v) {
            $pdf->SetFont('Arial', '', $this->conf['fields'][$k]['fontsize']);
            $pdf->SetXY($this->conf['fields'][$k]['x'] * 10,
                $this->conf['fields'][$k]['y'] * 10);
            switch ($k) {
                case 'date' :
                    $date = new \DateTime;
                    $pdf->Cell($this->conf['fields']['date']['width'] * 10,
                        $this->conf['fields']['date']['height'] * 10,
                        $date->format('d.m.Y'));
                    break;
                case 'scale' :
                    if (isset($this->data['scale_select'])) {
                        $pdf->Cell($this->conf['fields']['scale']['width'] * 10,
                            $this->conf['fields']['scale']['height'] * 10,
                            '1 : ' . $this->data['scale_select']);
                    } else {
                        $pdf->Cell($this->conf['fields']['scale']['width'] * 10,
                            $this->conf['fields']['scale']['height'] * 10,
                            '1 : ' . $this->data['scale_text']);
                    }
                    break;
                default:
                    if (isset($this->data['extra'][$k])) {
                        $pdf->MultiCell($this->conf['fields'][$k]['width'] * 10,
                            $this->conf['fields'][$k]['height'] * 10,
                            $this->data['extra'][$k]);
                    }
                    break;
            }
        }

        // draw features
        if ($this->data['rotation'] == 0) {
            $this->drawFeatures();
        }

        if ($this->data['rotation'] == 0) {

            $pdf->Image($this->finalimagename, $this->x_ul,
            $this->y_ul, $this->width, $this->height, 'png', '', false,
            0, 5, -1 * 0);

            $pdf->Rect($this->x_ul, $this->y_ul, $this->width, $this->height);
            if (isset($this->conf['northarrow'])) {
                $pdf->Image($resource_dir . '/images/northarrow.png',
                    $this->conf['northarrow']['x'] * 10,
                    $this->conf['northarrow']['y'] * 10,
                    $this->conf['northarrow']['width'] * 10,
                    $this->conf['northarrow']['height'] * 10);
            }
        } else {
            $pdf->Image($this->finalimagename, $this->x_ul, $this->y_ul,
                $this->width, $this->height, 'png', '', false, 0, 5, -1 * 0);

            $pdf->Rect($this->x_ul, $this->y_ul, $this->width, $this->height);
            if (isset($this->conf['northarrow'])) {
                $this->rotateNorthArrow();
            }
        }

        // add overview map
        if (isset($this->data['overview']) && isset($this->conf['overview']) ) {
            $this->getOverviewMap();
        }

        // add scalebar
        if (isset($this->conf['scalebar']) ) {
            $this->drawScaleBar();
        }

        if (isset($this->data['legends'])){
            $this->createLegend();
        }

        unlink($this->finalimagename);

        return $pdf->Output(null, 'S');
    }

    /**
     * Rotates the north arrow.
     *
     */
    private function rotateNorthArrow()
    {
        $tempdir = $this->tempdir;
        $resource_dir = $this->container->getParameter('kernel.root_dir') . '/Resources/MapbenderPrintBundle';
        $rotation = $this->data['rotation'];
        $northarrow = $resource_dir . '/images/northarrow.png';
        $im = imagecreatefrompng($northarrow);
        $transColor = imagecolorallocatealpha($im, 255, 255, 255, 0);
        $rotated = imagerotate($im, $rotation, $transColor);
        $imagename = tempnam($tempdir, 'mb_northarrow');
        imagepng($rotated, $imagename);

        if ($rotation == 90 || $rotation == 270) {
            //
        } else {
            $src_img = imagecreatefrompng($imagename);
            $srcsize = getimagesize($imagename);
            $destsize = getimagesize($resource_dir . '/images/northarrow.png');
            $x = ($srcsize[0] - $destsize[0]) / 2;
            $y = ($srcsize[1] - $destsize[1]) / 2;
            $dst_img = imagecreatetruecolor($destsize[0], $destsize[1]);
            imagecopy($dst_img, $src_img, 0, 0, $x, $y, $srcsize[0], $srcsize[1]);
            imagepng($dst_img, $imagename);
        }

        $this->pdf->Image($imagename,
                            $this->conf['northarrow']['x'] * 10,
                            $this->conf['northarrow']['y'] * 10,
                            $this->conf['northarrow']['width'] * 10,
                            $this->conf['northarrow']['height'] * 10,
                            'png');
        unlink($imagename);
    }

    private function getOverviewMap()
    {
        $temp_names = array();
        foreach ($this->data['overview'] as $i => $layer) {
            $url = strstr($this->data['overview'][$i]['url'], 'BBOX', true);

            $ov_width = $this->conf['overview']['width'] * $this->data['overview'][0]['scale'] / 100;
            $ov_height = $this->conf['overview']['height'] * $this->data['overview'][0]['scale'] / 100;

            $centerx = $this->data['center']['x'];
            $centery = $this->data['center']['y'];

            $ll_x = $centerx - $ov_width * 0.5;
            $ll_y = $centery - $ov_height * 0.5;
            $ur_x = $centerx + $ov_width * 0.5;
            $ur_y = $centery + $ov_height * 0.5;


            $bbox = 'BBOX=' . $ll_x . ',' . $ll_y . ',' . $ur_x . ',' . $ur_y;
            $url .= $bbox;

            // image size
            $conf = $this->conf;
            $quality = $this->data['quality'];
            $ov_image_width = round($conf['overview']['width'] / 2.54 * $quality);
            $ov_image_height = round($conf['overview']['height'] / 2.54 * $quality);

            $width = '&WIDTH=' . $ov_image_width;
            $height = '&HEIGHT=' . $ov_image_height;
            $url .= $width . $height;

            $this->overview_urls[$i] = $url;

            // get image
            $this->container->get("logger")->debug("Print Overview Request Nr.: " . $i . ' ' . $url);
            $attributes = array();
            $attributes['_controller'] = 'OwsProxy3CoreBundle:OwsProxy:entryPoint';
            $subRequest = new Request(array(
                'url' => $url
                ), array(), $attributes, array(), array(), array(), '');
            $response = $this->container->get('http_kernel')->handle($subRequest,
                HttpKernelInterface::SUB_REQUEST);

            $tempdir = $this->tempdir;
            $imagename = tempnam($tempdir, 'mb_print');
            $temp_names[] = $imagename;

            file_put_contents($imagename, $response->getContent());
            $im = null;
            switch (trim($response->headers->get('content-type'))) {
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
            }

            if ($im !== null) {
                imagesavealpha($im, true);
                imagepng($im, $imagename);
            }

        }

        // create final merged image
        $finalimagename = tempnam($tempdir, 'mb_print_merged');
        $finalImage = imagecreatetruecolor($ov_image_width,
            $ov_image_height);
        $bg = ImageColorAllocate($finalImage, 255, 255, 255);
        imagefilledrectangle($finalImage, 0, 0, $ov_image_width,
            $ov_image_height, $bg);
        imagepng($finalImage, $finalimagename);
        foreach ($temp_names as $temp_name) {
            // Note: suppressing the errors IS bad, bad PHP wants us to do it that way
            $src = @imagecreatefrompng($temp_name);
            // Check that imagecreatefrompng did yield something
            if ($src) {
                $dest = imagecreatefrompng($finalimagename);
                $src = imagecreatefrompng($temp_name);
                imagecopy($dest, $src, 0, 0, 0, 0, $ov_image_width,
                    $ov_image_height);
                imagepng($dest, $finalimagename);
            }
            unlink($temp_name);
        }

        $image = imagecreatefrompng($finalimagename);

        // ohne rotation
        if ($this->data['rotation'] == 0) {

            $map_width = $this->data['extent']['width'];
            $map_height = $this->data['extent']['height'];
            $centerx = $this->data['center']['x'];
            $centery = $this->data['center']['y'];

            $ll_x = $centerx - $map_width * 0.5;
            $ll_y = $centery - $map_height * 0.5;
            $ur_x = $centerx + $map_width * 0.5;
            $ur_y = $centery + $map_height * 0.5;

            $lowerleft = $this->realWorld2ovMapPos($ov_width, $ov_height, $ll_x, $ll_y);
            $upperright = $this->realWorld2ovMapPos($ov_width, $ov_height, $ur_x, $ur_y);


            $lowerleft[0] = round($lowerleft[0]);
            $lowerleft[1] = round($lowerleft[1]);
            $upperright[0] = round($upperright[0]);
            $upperright[1] = round($upperright[1]);

            $red = ImageColorAllocate($image,255,0,0);
            imageline ( $image, $lowerleft[0], $upperright[1], $upperright[0], $upperright[1], $red);
            imageline ( $image, $upperright[0], $upperright[1], $upperright[0], $lowerleft[1], $red);
            imageline ( $image, $upperright[0], $lowerleft[1], $lowerleft[0], $lowerleft[1], $red);
            imageline ( $image, $lowerleft[0], $lowerleft[1], $lowerleft[0], $upperright[1], $red);

        }else{// mit rotation

            $ll_x = $this->data['extent_feature'][3]['x'];
            $ll_y = $this->data['extent_feature'][3]['y'];
            $ul_x = $this->data['extent_feature'][0]['x'];
            $ul_y = $this->data['extent_feature'][0]['y'];

            $lr_x = $this->data['extent_feature'][2]['x'];
            $lr_y = $this->data['extent_feature'][2]['y'];
            $ur_x = $this->data['extent_feature'][1]['x'];
            $ur_y = $this->data['extent_feature'][1]['y'];


            $p1 = $this->realWorld2ovMapPos($ov_width, $ov_height, $ll_x, $ll_y);
            $p2 = $this->realWorld2ovMapPos($ov_width, $ov_height, $ul_x, $ul_y);
            $p3 = $this->realWorld2ovMapPos($ov_width, $ov_height, $ur_x, $ur_y);
            $p4 = $this->realWorld2ovMapPos($ov_width, $ov_height, $lr_x, $lr_y);


            $red = ImageColorAllocate($image,255,0,0);
            imageline ( $image, $p1[0], $p1[1], $p2[0], $p2[1], $red);
            imageline ( $image, $p2[0], $p2[1], $p3[0], $p3[1], $red);
            imageline ( $image, $p3[0], $p3[1], $p4[0], $p4[1], $red);
            imageline ( $image, $p4[0], $p4[1], $p1[0], $p1[1], $red);
        }

        imagepng($image, $finalimagename);

        $this->pdf->Image($finalimagename,
                    $this->conf['overview']['x'] * 10,
                    $this->conf['overview']['y'] * 10,
                    $this->conf['overview']['width'] * 10,
                    $this->conf['overview']['height'] * 10,
                    'png');

        $this->pdf->Rect($this->conf['overview']['x'] * 10,
                         $this->conf['overview']['y'] * 10,
                         $this->conf['overview']['width'] * 10,
                         $this->conf['overview']['height'] * 10);

        unlink($finalimagename);
    }

    private function drawScaleBar(){
        // temp scale bar

        $pdf = $this->pdf;
        // Linienbreite einstellen, 0.5 mm
        $pdf->SetLineWidth(0.1);
        // Rahmenfarbe
        $pdf->SetDrawColor(0, 0, 0);
        // Füllung
        $pdf->SetFillColor(0,0,0);
        // Schriftart definieren
        $pdf->SetFont('arial', '', 10 );

        $length = 0.01 * $this->data['scale_select'] * 5;
        $suffix = 'm';

        $pdf->Text( $this->conf['scalebar']['x'] * 10 -1 , $this->conf['scalebar']['y'] * 10 - 1 , '0' );
        $pdf->Text( $this->conf['scalebar']['x'] * 10 + 46, $this->conf['scalebar']['y'] * 10 - 1 , $length . '' . $suffix);

        $pdf->Rect($this->conf['scalebar']['x'] * 10 , $this->conf['scalebar']['y'] * 10, 10, 2, 'FD');
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($this->conf['scalebar']['x'] * 10 + 10 , $this->conf['scalebar']['y'] * 10, 10, 2, 'FD');
        $pdf->SetFillColor(0,0,0);
        $pdf->Rect($this->conf['scalebar']['x'] * 10 + 20  , $this->conf['scalebar']['y'] * 10, 10, 2, 'FD');
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($this->conf['scalebar']['x'] * 10 + 30 , $this->conf['scalebar']['y'] * 10, 10, 2, 'FD');
        $pdf->SetFillColor(0,0,0);
        $pdf->Rect($this->conf['scalebar']['x'] * 10 + 40  , $this->conf['scalebar']['y'] * 10, 10, 2, 'FD');
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
                if($this->data['rotation'] === 0){
                    $p = $this->realWorld2mapPos($c[0], $c[1]);
                }else{
                    $p = $this->realWorld2rotatedMapPos($c[0], $c[1]);
                }
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

            if($this->data['rotation'] === 0){
                $from = $this->realWorld2mapPos(
                    $geometry['coordinates'][$i - 1][0],
                    $geometry['coordinates'][$i - 1][1]);
                $to = $this->realWorld2mapPos(
                    $geometry['coordinates'][$i][0],
                    $geometry['coordinates'][$i][1]);
            }else{
                $from = $this->realWorld2rotatedMapPos(
                    $geometry['coordinates'][$i - 1][0],
                    $geometry['coordinates'][$i - 1][1]);
                $to = $this->realWorld2rotatedMapPos(
                    $geometry['coordinates'][$i][0],
                    $geometry['coordinates'][$i][1]);
            }

            imageline($image, $from[0], $from[1], $to[0], $to[1], $color);
        }

        // reset image thickness !!
        imagesetthickness($image, 0);
    }

    private function drawPoint($geometry, $image)
    {
        $c = $geometry['coordinates'];

        if($this->data['rotation'] === 0){
            $p = $this->realWorld2mapPos($c[0], $c[1]);
        }else{
            $p = $this->realWorld2rotatedMapPos($c[0], $c[1]);
        }

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
        imageellipse($image, $p[0], $p[1], 2*$radius, 2*$radius, $color);
    }

    private function drawFeatures()
    {
        $image = imagecreatefrompng($this->finalimagename);
        imagesavealpha($image, true);
        imagealphablending($image, true);

        foreach($this->data['layers'] as $idx => $layer) {
            if('GeoJSON+Style' !== $layer['type']) {
                continue;
            }

            foreach($layer['geometries'] as $geometry) {
                $renderMethodName = 'draw' . $geometry['type'];

                if(!method_exists($this, $renderMethodName)) {
                    throw new \RuntimeException('Can not draw geometries of type "' . $geometry['type'] . '".');
                }

                $this->$renderMethodName($geometry, $image);
            }
        }
        imagepng($image, $this->finalimagename);
    }

    private function realWorld2mapPos($rw_x,$rw_y)
    {
        $quality = $this->data['quality'];
        $map_width = $this->data['extent']['width'];
        $map_height = $this->data['extent']['height'];
        $centerx = $this->data['center']['x'];
        $centery = $this->data['center']['y'];

        $minX = $centerx - $map_width * 0.5;
        $minY = $centery - $map_height * 0.5;
        $maxX = $centerx + $map_width * 0.5;
        $maxY = $centery + $map_height * 0.5;

        $extentx = $maxX - $minX ;
	$extenty = $maxY - $minY ;

        $pixPos_x = (($rw_x - $minX)/$extentx) * round($this->conf['map']['width']  / 2.54 * $quality) ;
	$pixPos_y = (($maxY - $rw_y)/$extenty) * round($this->conf['map']['height']  / 2.54 * $quality);

        $pixPos = array($pixPos_x, $pixPos_y);

	return $pixPos;
    }

    private function realWorld2ovMapPos($ov_width, $ov_height, $rw_x,$rw_y)
    {
        $quality = $this->data['quality'];
        $centerx = $this->data['center']['x'];
        $centery = $this->data['center']['y'];

        $minX = $centerx - $ov_width * 0.5;
        $minY = $centery - $ov_height * 0.5;
        $maxX = $centerx + $ov_width * 0.5;
        $maxY = $centery + $ov_height * 0.5;

        $extentx = $maxX - $minX ;
	$extenty = $maxY - $minY ;

        $pixPos_x = (($rw_x - $minX)/$extentx) * round($this->conf['overview']['width'] / 2.54 * $quality) ;
	$pixPos_y = (($maxY - $rw_y)/$extenty) * round($this->conf['overview']['height'] / 2.54 * $quality);

        $pixPos = array($pixPos_x, $pixPos_y);

	return $pixPos;
    }

    private function realWorld2rotatedMapPos($rw_x,$rw_y)
    {
        $rotation = $this->data['rotation'];
        $map_width = $this->data['extent']['width'];
        $map_height = $this->data['extent']['height'];
        $centerx = $this->data['center']['x'];
        $centery = $this->data['center']['y'];

        //set needed extent
        $neededExtentWidth = round(abs(sin(deg2rad($rotation)) * $map_height) +
            abs(cos(deg2rad($rotation)) * $map_width));
        $neededExtentHeight = round(abs(sin(deg2rad($rotation)) * $map_width) +
            abs(cos(deg2rad($rotation)) * $map_height));

        $minX = $centerx - $neededExtentWidth * 0.5;
        $minY = $centery - $neededExtentHeight * 0.5;
        $maxX = $centerx + $neededExtentWidth * 0.5;
        $maxY = $centery + $neededExtentHeight * 0.5;

        //set needed image size
        $neededImageWidth = round(abs(sin(deg2rad($rotation)) * $this->image_height) +
            abs(cos(deg2rad($rotation)) * $this->image_width));
        $neededImageHeight = round(abs(sin(deg2rad($rotation)) * $this->image_width) +
            abs(cos(deg2rad($rotation)) * $this->image_height));

        $extentx = $maxX - $minX ;
	$extenty = $maxY - $minY ;

        $pixPos_x = (($rw_x - $minX)/$extentx) * round($neededImageWidth) ;
	$pixPos_y = (($maxY - $rw_y)/$extenty) * round($neededImageHeight);

        $pixPos = array($pixPos_x, $pixPos_y);

	return $pixPos;
    }

    private function createLegend()
    {
        if(empty($this->data['legends'])){
            return false;
        }

        $this->pdf->addPage('P');
        $this->pdf->SetFont('Arial', 'B', 14);
        $x = 5;
        $y = 10;

        foreach ($this->data['legends'] as $idx => $legendArray) {
            foreach ($legendArray as $title => $legendUrl) {

                if (preg_match('/request=GetLegendGraphic/i', $legendUrl) === 0) {
                    continue;
                }

                $image = $this->getLegendImage($legendUrl);

                $size = getimagesize($image);
                $tempY = round($size[1] * 25.4 / 72) + 10;

                if($idx > 0){
                    if($y + $tempY > ($this->pdf->h)){
                        $x += 105;
                        $y = 10;
                        if($x > ($this->pdf->w)-30){
                            $this->pdf->addPage('P');
                            $x = 5;
                            $y = 10;
                        }
                    }
                }

                $this->pdf->setXY($x,$y);
                $this->pdf->Cell(0,0,  utf8_decode($title));

                $this->pdf->Image($image, $x, $y + 5, 0, 0, 'png', '', false, 0);

                $y += round($size[1] * 25.4 / 72) + 10;

                if($y > ($this->pdf->h)-30){
                    $x += 105;
                    $y = 10;
                }
                if($x > ($this->pdf->w)-30){
                    $this->pdf->addPage('P');
                    $x = 5;
                    $y = 10;
                }

                unlink($image);
            }
        }
    }

    private function getLegendImage($unsignedUrl)
    {
        $signer = $this->container->get('signer');
        $url = $signer->signUrl($unsignedUrl);

        $attributes = array();
        $attributes['_controller'] = 'OwsProxy3CoreBundle:OwsProxy:entryPoint';
        $subRequest = new Request(array(
            'url' => $url
            ), array(), $attributes, array(), array(), array(), '');
        $response = $this->container->get('http_kernel')->handle($subRequest,
            HttpKernelInterface::SUB_REQUEST);

        $imagename = tempnam($this->tempdir, 'mb_printlegend');

        file_put_contents($imagename, $response->getContent());

        return $imagename;
    }

}