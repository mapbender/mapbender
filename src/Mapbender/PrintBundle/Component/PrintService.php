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
        $this->data = json_decode($content, true);
        $template = $this->data['template'];
//        print "<pre>";
//        print_r($this->data);
//        print "</pre>";
//        die();
        
        $this->getTemplateConf($template);
        $this->createUrlArray();
        $this->setMapParameter();

        if ($this->data['rotation'] == 0) {
            $this->setExtent();
            $this->setImageSize();
            $this->getImages();
        } else {
            $this->rotate();
        }
        $this->buildPdf();
    }

    /**
     * Get the configuration from the template odg.
     *
     */
    private function getTemplateConf($template)
    {
        $odgParser = new OdgParser($this->container);
        $this->conf = $odgParser->getConf($template);
//        print "<pre>";
//        print_r($this->conf);
//        print "</pre>";
//        die();
    }

    /**
     * Get the configuration from the template odg.
     *
     */
    private function createUrlArray()
    {
        foreach ($this->data['layers'] as $i => $layer) {
            $url = strstr($this->data['layers'][$i]['url'], 'BBOX', true);
            $this->layer_urls[$i] = $url;
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

        $bbox = 'BBOX=' . $ll_x . ',' . $ll_y . ',' . $ur_x . ',' . $ur_y;

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
            if ($this->data['quality'] != '72') {
                $url .= '&map_resolution=' . $this->data['quality'];
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
            $imagename = $tempdir . '/tempimage' . $k;

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
                //throw new \RuntimeException("Unknown mimetype " . trim($response->headers->get('content-type')));
            }

            if ($im !== null) {
                imagesavealpha($im, true);
                imagepng($im, $imagename);
            }
        }
        // create final merged image
        $finalimagename = $tempdir . '/mergedimage.png';
        $finalImage = imagecreatetruecolor($this->image_width,
            $this->image_height);
        $bg = ImageColorAllocate($finalImage, 255, 255, 255);
        imagefilledrectangle($finalImage, 0, 0, $this->image_width,
            $this->image_height, $bg);
        imagepng($finalImage, $finalimagename);
        foreach ($this->layer_urls as $k => $url) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if (is_file($tempdir . '/tempimage' . $k) && finfo_file($finfo,
                    $tempdir . '/tempimage' . $k) == 'image/png') {
                $dest = imagecreatefrompng($finalimagename);
                $src = imagecreatefrompng($tempdir . '/tempimage' . $k);
                imagecopy($dest, $src, 0, 0, 0, 0, $this->image_width,
                    $this->image_height);
                imagepng($dest, $finalimagename);
            }
            unlink($tempdir . '/tempimage' . $k);
            finfo_close($finfo);
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

        foreach ($this->layer_urls as $k => $url) {
            $map_width = $this->data['extent']['width'];
            $map_height = $this->data['extent']['height'];
            $centerx = $this->data['center']['x'];
            $centery = $this->data['center']['y'];

            //set needed extent
            $neededExtentWidth = round(abs(sin(deg2rad($rotation)) * $map_height) +
                abs(cos(deg2rad($rotation)) * $map_width));
            $neededExtentHeight = round(abs(sin(deg2rad($rotation)) * $map_width) +
                abs(cos(deg2rad($rotation)) * $map_height));

            $ll_x = $centerx - $neededExtentWidth * 0.5;
            $ll_y = $centery - $neededExtentHeight * 0.5;
            $ur_x = $centerx + $neededExtentWidth * 0.5;
            $ur_y = $centery + $neededExtentHeight * 0.5;

            $bbox = 'BBOX=' . $ll_x . ',' . $ll_y . ',' . $ur_x . ',' . $ur_y;
            $url .= $bbox;
            $this->layer_urls[$k] = $url;

            //set needed image size
            $neededImageWidth = round(abs(sin(deg2rad($rotation)) * $this->image_height) +
                abs(cos(deg2rad($rotation)) * $this->image_width));
            $neededImageHeight = round(abs(sin(deg2rad($rotation)) * $this->image_width) +
                abs(cos(deg2rad($rotation)) * $this->image_height));

            $w = '&WIDTH=' . $neededImageWidth;
            $h = '&HEIGHT=' . $neededImageHeight;
            $url .= $w . $h;
            $this->layer_urls[$k] = $url;

            //get image
            $attributes = array();
            $attributes['_controller'] = 'OwsProxy3CoreBundle:OwsProxy:entryPoint';
            $subRequest = new Request(array(
                'url' => $url
                ), array(), $attributes, array(), array(), array(), '');
            $response = $this->container->get('http_kernel')->handle($subRequest,
                HttpKernelInterface::SUB_REQUEST);

            $tempdir = $this->tempdir;
            $imagename = $tempdir . '/tempimage' . $k;

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
                //throw new \RuntimeException("Unknown mimetype " . trim($response->headers->get('content-type')));
            }
            
            if ($k == 0 && isset($this->data['features'])){
                $this->drawRotatedFeatures($im);
            }
            if ($im !== null) {
            
                //rotate image
                $transColor = imagecolorallocatealpha($im, 255, 255, 255, 127);
                $rotatedImage = imagerotate($im, $rotation, $transColor);
                imagealphablending($rotatedImage, false);
                imagesavealpha($rotatedImage, true);
                imagepng($rotatedImage, $imagename);

                //clip image from rotated
                $rotated_width = round(abs(sin(deg2rad($rotation)) * $neededImageHeight) +
                    abs(cos(deg2rad($rotation)) * $neededImageWidth));
                $rotated_height = round(abs(sin(deg2rad($rotation)) * $neededImageWidth) +
                    abs(cos(deg2rad($rotation)) * $neededImageHeight));
                $newx = ($rotated_width - $this->image_width ) / 2;
                $newy = ($rotated_height - $this->image_height ) / 2;

                $clippedImageName = $tempdir . '/clipped_image' . $k . '.png';
                $clippedImage = imagecreatetruecolor($this->image_width,
                    $this->image_height);

                imagealphablending($clippedImage, false);
                imagesavealpha($clippedImage, true);

                imagecopy($clippedImage, $rotatedImage, 0, 0, $newx, $newy,
                    $this->image_width, $this->image_height);
                imagepng($clippedImage, $clippedImageName);

                unlink($tempdir . '/tempimage' . $k);
            }
        }
        // create final merged image
        $finalimagename = $tempdir . '/mergedimage.png';
        $finalImage = imagecreatetruecolor($this->image_width,
            $this->image_height);
        $bg = ImageColorAllocate($finalImage, 255, 255, 255);
        imagefilledrectangle($finalImage, 0, 0, $this->image_width,
            $this->image_height, $bg);
        imagepng($finalImage, $finalimagename);
        foreach ($this->layer_urls as $k => $url) {
            $dest = imagecreatefrompng($finalimagename);
            $src = imagecreatefrompng($tempdir . '/clipped_image' . $k . '.png');
            imagecopy($dest, $src, 0, 0, 0, 0, $this->image_width,
                $this->image_height);
            imagepng($dest, $finalimagename);
            unlink($tempdir . '/clipped_image' . $k . '.png');
        }
    }

    /**
     * Builds the pdf from a given template.
     *
     */
    private function buildPdf()
    {
        require('PDF_ImageAlpha.php');
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
                        $pdf->Cell($this->conf['fields'][$k]['width'] * 10,
                            $this->conf['fields'][$k]['height'] * 10,
                            utf8_decode($this->data['extra'][$k]));
                    }
                    break;
            }
        }
        
        // draw features
        if ($this->data['rotation'] == 0 && isset($this->data['features'])) {
            $this->drawFeatures();
        }
        
        if ($this->data['rotation'] == 0) {
            $tempdir = sys_get_temp_dir();
            foreach ($this->layer_urls as $k => $url)
                    $pdf->Image($tempdir . '/mergedimage.png', $this->x_ul,
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
            $pdf->Image($tempdir . '/mergedimage.png', $this->x_ul, $this->y_ul,
                $this->width, $this->height, 'png', '', false, 0, 5, -1 * 0);

            $pdf->Rect($this->x_ul, $this->y_ul, $this->width, $this->height);
            if (isset($this->conf['northarrow'])) {
                $this->rotateNorthArrow();
            }
        }
               
        if (isset($this->data['overview']) && isset($this->conf['overview']) ) {
            $this->getOverviewMap();
        }
        
        unlink($tempdir . '/mergedimage.png');
        
        if (null != $this->data['file_prefix']) {
            $pdf->Output($this->data['file_prefix'] . '.pdf', 'D'); //file output
        } else {
            $pdf->Output();
        }
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
        $transColor = imagecolorallocatealpha($im, 255, 255, 255, 127);
        $rotated = imagerotate($im, $rotation, $transColor);
        imagepng($rotated, $tempdir . '/rotatednorth.png');

        if ($rotation == 90 || $rotation == 270) {
            //
        } else {
            $src_img = imagecreatefrompng($tempdir . '/rotatednorth.png');
            $srcsize = getimagesize($tempdir . '/rotatednorth.png');
            $destsize = getimagesize($resource_dir . '/images/northarrow.png');
            $x = ($srcsize[0] - $destsize[0]) / 2;
            $y = ($srcsize[1] - $destsize[1]) / 2;
            $dst_img = imagecreatetruecolor($destsize[0], $destsize[1]);
            imagecopy($dst_img, $src_img, 0, 0, $x, $y, $srcsize[0], $srcsize[1]);
            imagepng($dst_img, $tempdir . '/rotatednorth.png');
        }

        $this->pdf->Image($tempdir . '/rotatednorth.png',
            $this->conf['northarrow']['x'] * 10,
            $this->conf['northarrow']['y'] * 10,
            $this->conf['northarrow']['width'] * 10,
            $this->conf['northarrow']['height'] * 10);
        unlink($tempdir . '/rotatednorth.png');
    }
    
    private function getOverviewMap()
    {
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
            $imagename = $tempdir . '/tempovimage' . $i;

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
        $finalimagename = $tempdir . '/mergedovimage.png';
        $finalImage = imagecreatetruecolor($ov_image_width,
            $ov_image_height);
        $bg = ImageColorAllocate($finalImage, 255, 255, 255);
        imagefilledrectangle($finalImage, 0, 0, $ov_image_width,
            $ov_image_height, $bg);
        imagepng($finalImage, $finalimagename);
        foreach ($this->overview_urls as $k => $url) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if (is_file($tempdir . '/tempovimage' . $k) && finfo_file($finfo,
                    $tempdir . '/tempovimage' . $k) == 'image/png') {
                $dest = imagecreatefrompng($finalimagename);
                $src = imagecreatefrompng($tempdir . '/tempovimage' . $k);
                imagecopy($dest, $src, 0, 0, 0, 0, $ov_image_width,
                    $ov_image_height);
                imagepng($dest, $finalimagename);
            }
            unlink($tempdir . '/tempovimage' . $k);
            finfo_close($finfo);
        }       
        
        $image = imagecreatefrompng($tempdir . '/mergedovimage.png');             
        
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
               
        imagepng($image, $tempdir . '/mergedovimage.png');
        
        $this->pdf->Image($tempdir . '/mergedovimage.png',
                    $this->conf['overview']['x'] * 10,
                    $this->conf['overview']['y'] * 10,
                    $this->conf['overview']['width'] * 10,
                    $this->conf['overview']['height'] * 10);
        
        $this->pdf->Rect($this->conf['overview']['x'] * 10,
                         $this->conf['overview']['y'] * 10,
                         $this->conf['overview']['width'] * 10,
                         $this->conf['overview']['height'] * 10);    

        unlink($tempdir . '/mergedovimage.png');
    }
    
    private function drawFeatures()
    {      
        $tempdir = $this->tempdir;
        $image = imagecreatefrompng($tempdir . '/mergedimage.png');        
        
        $feature = $this->data['features'][0];
        
        $points;        
        foreach ($feature as $k => $v){
            $points[$k] = $this->realWorld2mapPos($feature[$k]['x'],$feature[$k]['y']);
        }       
        
        $red = ImageColorAllocate($image,255,0,0); 
        
        $keys = array_keys($points);
        $last_key = end($keys);
        foreach ($points as $k => $v){
            if ($k == $last_key) {
                break;
            }else{
                imageline ( $image, $points[$k][0], $points[$k][1], $points[$k+1][0], $points[$k+1][1], $red);
            }
        }

        imagepng($image, $tempdir . '/mergedimage.png');
    }
    
    private function drawRotatedFeatures($image)
    {              
        $feature = $this->data['features'][0];
        
        $points;        
        foreach ($feature as $k => $v){
            $points[$k] = $this->realWorld2rotatedMapPos($feature[$k]['x'],$feature[$k]['y']);
        }     

        $red = ImageColorAllocate($image,255,0,0); 
        
        $keys = array_keys($points);
        $last_key = end($keys);
        foreach ($points as $k => $v){
            if ($k == $last_key) {
                break;
            }else{
                imageline ( $image, $points[$k][0], $points[$k][1], $points[$k+1][0], $points[$k+1][1], $red);
            }
        }
        
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
    
}
