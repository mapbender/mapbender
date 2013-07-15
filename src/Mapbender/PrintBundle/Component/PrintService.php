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

        if ($this->data['rotation'] == 0)
        {
            $this->setExtent();
            $this->setImageSize();
            $this->getImages();
        }else{
            $this->rotate();
        }
        //$this->test();
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
        foreach ($this->data['layers'] as $i => $layer)
        {
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
        $this->x_ul = $conf['map']['x']*10;
        $this->y_ul = $conf['map']['y']*10;
        $this->width = $conf['map']['width']*10;
        $this->height = $conf['map']['height']*10;
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

        $bbox = 'BBOX='.$ll_x.','.$ll_y.','.$ur_x.','.$ur_y;

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
        foreach ($this->layer_urls as $k => $url)
        {
            $width = '&WIDTH='.$this->image_width;
            $height = '&HEIGHT='.$this->image_height;
            $url .= $width.$height;
            if ($this->data['quality'] == '288')
            {
               $url .= '&map_resolution=288';
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
        foreach ($this->layer_urls as $k => $url)
        {
            $attributes = array();
            $attributes['_controller'] = 'OwsProxy3CoreBundle:OwsProxy:entryPoint';
            $subRequest = new Request(array(
                'url' => $url
            ), array(), $attributes, array(), array(), array(), '');
            $response = $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

            $tempdir = $this->tempdir;
            $imagename = $tempdir.'/tempimage'.$k;

            file_put_contents($imagename, $response->getContent());

            switch(trim($response->headers->get('content-type'))) {
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

            if(isset($im)) {
                imagesavealpha($im, true);
                imagepng($im , $imagename);
            }

        }
        // create final merged image
        $finalimagename = $tempdir.'/mergedimage.png';
        $finalImage = imagecreatetruecolor($this->image_width, $this->image_height);
        $bg = ImageColorAllocate($finalImage, 255, 255, 255);
        imagefilledrectangle($finalImage,0,0,$this->image_width, $this->image_height,$bg);
        imagepng($finalImage , $finalimagename);
        foreach ($this->layer_urls as $k => $url)
        {
            if(is_file($tempdir.'/tempimage'.$k) && mime_content_type($tempdir.'/tempimage'.$k) == 'image/png') {
                $dest = imagecreatefrompng($finalimagename);
                $src = imagecreatefrompng($tempdir.'/tempimage'.$k);
                imagecopy($dest, $src, 0, 0, 0, 0, $this->image_width , $this->image_height);
                imagepng($dest , $finalimagename);
            }
            unlink($tempdir.'/tempimage'.$k);
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

        foreach ($this->layer_urls as $k => $url)
        {
            $map_width = $this->data['extent']['width'];
            $map_height = $this->data['extent']['height'];
            $centerx = $this->data['center']['x'];
            $centery = $this->data['center']['y'];

            //set needed extent
            $neededExtentWidth = round(abs(sin(deg2rad($rotation))*$map_height)+abs(cos(deg2rad($rotation))*$map_width));
            $neededExtentHeight = round(abs(sin(deg2rad($rotation))*$map_width)+abs(cos(deg2rad($rotation))*$map_height));

            $ll_x = $centerx - $neededExtentWidth  * 0.5;
            $ll_y = $centery - $neededExtentHeight * 0.5;
            $ur_x = $centerx + $neededExtentWidth * 0.5;
            $ur_y = $centery + $neededExtentHeight * 0.5;

            $bbox = 'BBOX='.$ll_x.','.$ll_y.','.$ur_x.','.$ur_y;
            $url .= $bbox;
            $this->layer_urls[$k] = $url;

            //set needed image size
            $neededImageWidth = round(abs(sin(deg2rad($rotation))*$this->image_height)+abs(cos(deg2rad($rotation))*$this->image_width));
            $neededImageHeight = round(abs(sin(deg2rad($rotation))*$this->image_width)+abs(cos(deg2rad($rotation))*$this->image_height));

            $w = '&WIDTH='.$neededImageWidth;
            $h = '&HEIGHT='.$neededImageHeight;
            $url .= $w.$h;
            $this->layer_urls[$k] = $url;

            //get image
            $attributes = array();
            $attributes['_controller'] = 'OwsProxy3CoreBundle:OwsProxy:entryPoint';
            $subRequest = new Request(array(
                'url' => $url
            ), array(), $attributes, array(), array(), array(), '');
            $response = $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

            $tempdir = $this->tempdir;
            $imagename = $tempdir.'/tempimage'.$k;

            file_put_contents($imagename, $response->getContent());

            switch(trim($response->headers->get('content-type'))) {
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

            //rotate image
            $transColor = imagecolorallocatealpha($im, 255, 255, 255, 127);
            $rotatedImage = imagerotate($im , $rotation, $transColor);
            imagealphablending($rotatedImage, false);
            imagesavealpha($rotatedImage, true);
            imagepng($rotatedImage , $imagename);

            //clip image from rotated
            $rotated_width = round(abs(sin(deg2rad($rotation))*$neededImageHeight)+abs(cos(deg2rad($rotation))*$neededImageWidth));
            $rotated_height = round(abs(sin(deg2rad($rotation))*$neededImageWidth)+abs(cos(deg2rad($rotation))*$neededImageHeight));
            $newx = ($rotated_width - $this->image_width ) / 2  ;
            $newy = ($rotated_height - $this->image_height ) / 2  ;

            $clippedImageName = $tempdir.'/clipped_image'.$k.'.png';
            $clippedImage = imagecreatetruecolor($this->image_width, $this->image_height);

            imagealphablending($clippedImage, false);
            imagesavealpha($clippedImage, true);

            imagecopy($clippedImage , $rotatedImage , 0 , 0 , $newx , $newy , $this->image_width , $this->image_height );
            imagepng($clippedImage , $clippedImageName);

            unlink($tempdir.'/tempimage'.$k);
        }
        // create final merged image
        $finalimagename = $tempdir.'/mergedimage.png';
        $finalImage = imagecreatetruecolor($this->image_width, $this->image_height);
        $bg = ImageColorAllocate($finalImage, 255, 255, 255);
        imagefilledrectangle($finalImage,0,0,$this->image_width, $this->image_height,$bg);
        imagepng($finalImage , $finalimagename);
        foreach ($this->layer_urls as $k => $url)
        {
            $dest = imagecreatefrompng($finalimagename);
            $src = imagecreatefrompng($tempdir.'/clipped_image'.$k.'.png');
            imagecopy($dest, $src, 0, 0, 0, 0, $this->image_width , $this->image_height);
            imagepng($dest , $finalimagename);
            unlink($tempdir.'/clipped_image'.$k.'.png');
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
        $format = substr($this->data['template'],0,2);
        $this->pdf = new PDF_ImageAlpha($this->orientation,'mm',$format);
        //$this->pdf = new FPDF_FPDI($this->orientation,'mm',$format);
        $pdf = $this->pdf;
        $template = $this->data['template'];
        $pdffile = $resource_dir.'/templates/'.$template.'.pdf';
        $pagecount = $pdf->setSourceFile($pdffile);
        $tplidx = $pdf->importPage(1);

        $pdf->addPage();
        $pdf->useTemplate($tplidx);

        foreach ($this->conf['fields'] as $k => $v) {
            $pdf->SetFont('Arial','',$this->conf['fields'][$k]['fontsize']);
            $pdf->SetXY($this->conf['fields'][$k]['x']*10, $this->conf['fields'][$k]['y']*10);
            switch($k) {
                case 'date' :
                    $date = new \DateTime;
                    $pdf->Cell($this->conf['fields']['date']['width']*10,$this->conf['fields']['date']['height']*10,$date->format('d.m.Y'));
                    break;
                case 'scale' :
                    if (isset($this->data['scale_select']))
                    {
                        $pdf->Cell($this->conf['fields']['scale']['width']*10,$this->conf['fields']['scale']['height']*10,'1 : '.$this->data['scale_select']);
                    }else{
                        $pdf->Cell($this->conf['fields']['scale']['width']*10,$this->conf['fields']['scale']['height']*10,'1 : '.$this->data['scale_text']);
                    }
                    break;
                default:
                    if (isset($this->data['extra'][$k]))
                    {
                        $pdf->Cell($this->conf['fields'][$k]['width']*10,$this->conf['fields'][$k]['height']*10,utf8_decode($this->data['extra'][$k]));
                    }
                    break;
            }
        }

        if ($this->data['rotation'] == 0)
        {
            $tempdir = sys_get_temp_dir();
            foreach ($this->layer_urls as $k => $url)
//            {
//                $pdf->Image($tempdir.'/tempimage'.$k,
//                            $this->x_ul,
//                            $this->y_ul,
//                            $this->width,
//                            $this->height,
//                            'png','',false,0,5,-1*0);
//                //unlink($tempdir.'/tempimage'.$k);
//            }
            $pdf->Image($tempdir.'/mergedimage.png',
                            $this->x_ul,
                            $this->y_ul,
                            $this->width,
                            $this->height,
                            'png','',false,0,5,-1*0);

            $pdf->Rect($this->x_ul, $this->y_ul, $this->width, $this->height);
            $pdf->Image($resource_dir.'/images/northarrow.png',
                        $this->conf['northarrow']['x']*10,
                        $this->conf['northarrow']['y']*10,
                        $this->conf['northarrow']['width']*10,
                        $this->conf['northarrow']['height']*10);

        }else{
            $this->rotateNorthArrow();
//            foreach ($this->layer_urls as $k => $url)
//            {
//                $pdf->Image($tempdir.'/rotated_image'.$k.'.png',
//                            $this->x_ul,
//                            $this->y_ul,
//                            $this->width,
//                            $this->height,
//                            'png','',false,0,5,-1*0);
//            }
            $pdf->Image($tempdir.'/mergedimage.png',
                            $this->x_ul,
                            $this->y_ul,
                            $this->width,
                            $this->height,
                            'png','',false,0,5,-1*0);

            $pdf->Rect($this->x_ul, $this->y_ul, $this->width, $this->height);
        }

        //$pdf->Output('newpdf.pdf', 'D'); //file output
        $pdf->Output();
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
        $northarrow = $resource_dir.'/images/northarrow.png';
        $im = imagecreatefrompng($northarrow);
        $transColor = imagecolorallocatealpha($im, 255, 255, 255, 127);
        $rotated = imagerotate($im , $rotation ,$transColor);
        imagepng($rotated , $tempdir.'/rotatednorth.png');

        if ($rotation == 90 || $rotation == 270)
        {
            //
        }else{
            $src_img = imagecreatefrompng($tempdir.'/rotatednorth.png');
            $srcsize = getimagesize($tempdir.'/rotatednorth.png');
            $destsize = getimagesize($resource_dir.'/images/northarrow.png');
            $x = ($srcsize[0] - $destsize[0]) / 2;
            $y = ($srcsize[1] - $destsize[1]) / 2;
            $dst_img = imagecreatetruecolor($destsize[0], $destsize[1]);
            imagecopy($dst_img, $src_img, 0, 0, $x, $y,$srcsize[0], $srcsize[1]);
            imagepng($dst_img, $tempdir.'/rotatednorth.png');
        }

        $this->pdf->Image($tempdir.'/rotatednorth.png',
                    $this->conf['northarrow']['x']*10,
                    $this->conf['northarrow']['y']*10,
                    $this->conf['northarrow']['width']*10,
                    $this->conf['northarrow']['height']*10);
        unlink($tempdir.'/rotatednorth.png');
    }

}
