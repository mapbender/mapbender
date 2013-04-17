<?php

namespace Mapbender\PrintBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use FPDF_FPDF;
use FPDF_FPDI;
use Buzz\Browser;

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
        $this->getTemplateConf($template);         
        $this->createUrlArray();     
        $this->setMapParameter();

        $this->rotate();

//        if ($this->data['rotation'] == 0)
//        {
//            $this->setExtent();
//            $this->setImageSize();    
//            $this->getImages();
//        }else{
//            $this->rotate();
//        }
        
        $this->buildPdf();
    }
    
    /**
     * Get the configuration from the template odg.
     * 
     */
    private function getTemplateConf($template) 
    {
        $odgParser = new OdgParser();
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
        for ($i=0; $i<count($this->data['layers']); $i++)
        {
            $url = strstr($this->data['layers'][$i+1]['url'], 'BBOX', true);
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
            $url .= $width.$height;//'&map_resolution=288';
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
            $buzz = new Browser;
            $buzz->timeout = 10000;
            $response = $buzz->get($url);
            
//        print "<pre>";
//        print_r($response);
//        print "</pre>";
//        die();

            if ($response->getHeader('Content-Type') != 'image/png'){
                print_r ($response->getContent());
                die();
            }
            $image =  $response->getContent();
            $imagename = $this->tempdir.'/tempimage'.$k.'.png';
            $handle = fopen($imagename, "w");
            fwrite($handle, $image);
            fclose($handle);

            //interlace
            //$this->checkInterlace($imagename);
        }
    }     
    
    /**
     * Disable interlace.
     * 
     */
    private function checkInterlace($imagename)
    {
        $im = imagecreatefrompng($imagename);
        if (imageinterlace($im) == 1)
        {
            imageinterlace($im, 0);
            imagepng($im, $imagename);
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
            ), array(), $attributes);
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
                    throw new \RuntimeException("Unknown mimetype " . trim($response->headers->get('content-type')));
            }
            
            //rotate image
            $transColor = imagecolorallocatealpha($im, 255, 255, 255, 127);
            $rotatedImage = imagerotate($im , $rotation, $transColor);
            imagesavealpha($rotatedImage, true);
            imagepng($rotatedImage , $imagename);
            
            //create finalimage 
            $rotated_width = round(abs(sin(deg2rad($rotation))*$neededImageHeight)+abs(cos(deg2rad($rotation))*$neededImageWidth));
            $rotated_height = round(abs(sin(deg2rad($rotation))*$neededImageWidth)+abs(cos(deg2rad($rotation))*$neededImageHeight));
            $newx = ($rotated_width - $this->image_width ) / 2  ;
            $newy = ($rotated_height - $this->image_height ) / 2  ;
                
            $finalimagename = $tempdir.'/rotated_image'.$k.'.png';  
            $final_image = imagecreatetruecolor($this->image_width, $this->image_height);

            $transColor1 = imagecolorallocatealpha($final_image, 0, 0, 0, 127);
            $transColor2 = imagecolorallocatealpha($rotatedImage, 0, 0, 0, 127);
            
            imagecolortransparent($final_image ,  $transColor1);
            imagecolortransparent($rotatedImage , $transColor2);        

            imagecopymerge($final_image , $rotatedImage , 0 , 0 , $newx , $newy , 
                           $this->image_width , $this->image_height, 100 );

            imagepng($final_image , $finalimagename);
        }
    }	
    
    /**
     * Builds the pdf from a given template.
     * 
     */
    private function buildPdf() 
    {
        $tempdir = $this->tempdir;
        $format = substr($this->data['template'],0,2);
        $this->pdf = new FPDF_FPDI($this->orientation,'mm',$format);
        $pdf = $this->pdf;
        $template = $this->data['template'];
        $pdffile = __DIR__. '/../Templates/'.$template.'.pdf'; 
        $pagecount = $pdf->setSourceFile($pdffile);
        $tplidx = $pdf->importPage(1);
        
        $pdf->addPage();
        $pdf->useTemplate($tplidx);
        
        $pdf->SetFont('Arial','',$this->conf['title']['fontsize']);
        $pdf->SetXY($this->conf['title']['x']*10, $this->conf['title']['y']*10); 
        if ($this->data['extra']['titel'] != null)
        {
            $pdf->Cell($this->conf['title']['width']*10,$this->conf['title']['height']*10,$this->data['extra']['titel']);
            
        }     
        
        $pdf->SetFont('Arial','',$this->conf['scale']['fontsize']);
        $pdf->SetXY($this->conf['scale']['x']*10, $this->conf['scale']['y']*10); 
        $pdf->Cell($this->conf['scale']['width']*10,$this->conf['scale']['height']*10,'1 : '.$this->data['scale_select']);
        
        $date = new \DateTime;
        $pdf->SetFont('Arial','',$this->conf['date']['fontsize']);
        $pdf->SetXY($this->conf['date']['x']*10, $this->conf['date']['y']*10); 
        $pdf->Cell($this->conf['date']['width']*10,$this->conf['date']['height']*10,$date->format('d.m.Y'));
        
        
        if ($this->data['rotation'] == 0)
        {       
            $tempdir = sys_get_temp_dir();
            foreach ($this->layer_urls as $k => $url) 
            {
                $pdf->Image($tempdir.'/rotated_image'.$k.'.png', $this->x_ul, $this->y_ul, $this->width, $this->height, 'png');
                unlink($tempdir.'/tempimage'.$k);
                unlink($tempdir.'/rotated_image'.$k.'.png');
            }
            $pdf->Rect($this->x_ul, $this->y_ul, $this->width, $this->height);     
            $pdf->Image(__DIR__. '/../Images/northarrow.png', 
                        $this->conf['northarrow']['x']*10, 
                        $this->conf['northarrow']['y']*10, 
                        $this->conf['northarrow']['width']*10, 
                        $this->conf['northarrow']['height']*10);
            
        }else{
            $this->rotateNorthArrow();
            foreach ($this->layer_urls as $k => $url)
            {
                $pdf->Image($tempdir.'/rotated_image'.$k.'.png', 
                            $this->x_ul, 
                            $this->y_ul, 
                            $this->width, 
                            $this->height, 
                            'png');
                unlink($tempdir.'/tempimage'.$k);
                unlink($tempdir.'/rotated_image'.$k.'.png');
            }
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
        $rotation = $this->data['rotation'];
        $northarrow = __DIR__. '/../Images/northarrow.png';
        $im = imagecreatefrompng($northarrow);
        $transColor = imagecolorallocatealpha($im, 255, 255, 255, 127);
        $rotated = imagerotate($im , $rotation ,$transColor);
        imagepng($rotated , $tempdir.'/rotatednorth.png');
        
        if ($rotation == 90 || $rotation == 270)
        {
            
        }else{
            $src_img = imagecreatefrompng($tempdir.'/rotatednorth.png');
            $srcsize = getimagesize($tempdir.'/rotatednorth.png');
            $destsize = getimagesize(__DIR__. '/../Images/northarrow.png');
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