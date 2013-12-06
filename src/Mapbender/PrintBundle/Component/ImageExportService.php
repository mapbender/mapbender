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
        foreach ($this->requests as $k => $url)
        {
            $this->container->get("logger")->debug("Image Export Request Nr.: " . $k .' '. $url);
            $attributes = array();
            $attributes['_controller'] = 'OwsProxy3CoreBundle:OwsProxy:entryPoint';
            $subRequest = new Request(array(
                'url' => $url
            ), array(), $attributes, array(), array(), array(), '');
            $response = $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
            
            $tempdir = $this->tempdir;
            $imagename = $tempdir.'/tempimage'.$k;
            
            file_put_contents($imagename, $response->getContent());
            $im = null;
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

            if($im !== null) {
                imagesavealpha($im, true);
                imagepng($im , $imagename);
                
                $this->image_width = imagesx($im);
                $this->image_height = imagesy($im);
            }

        }
        
         
        // create final merged image
        $finalimagename = $tempdir.'/mergedimage.png';
        $finalImage = imagecreatetruecolor($this->image_width, $this->image_height);
        $bg = ImageColorAllocate($finalImage, 255, 255, 255);
        imagefilledrectangle($finalImage,0,0,$this->image_width, $this->image_height,$bg);
        imagepng($finalImage , $finalimagename);
        foreach ($this->requests as $k => $url)
        {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if(is_file($tempdir.'/tempimage'.$k) && finfo_file($finfo, $tempdir.'/tempimage'.$k) == 'image/png') {
                $dest = imagecreatefrompng($finalimagename);
                $src = imagecreatefrompng($tempdir.'/tempimage'.$k);
                imagecopy($dest, $src, 0, 0, 0, 0, $this->image_width , $this->image_height);
                imagepng($dest , $finalimagename);
            }
            unlink($tempdir.'/tempimage'.$k);
            finfo_close($finfo);
        }
        
        $date = date("Ymd");
        $time = date("His");
        
        if ($this->format == 'png'){
            $file = $tempdir.'/mergedimage.png';
            $image = imagecreatefrompng($file);
            unlink($tempdir.'/mergedimage.png');
            imagepng($image , $tempdir.'/export_temp.png');
            $pngfile = $tempdir.'/export_temp.png';
            header("Content-type: image/png");
            header("Content-Disposition: attachment; filename=export_".$date.$time.".png");
            header('Content-Length: ' . filesize($pngfile));
            readfile($pngfile);           
        }else{
            $file = $tempdir.'/mergedimage.png';
            $image = imagecreatefrompng($file);
            unlink($tempdir.'/mergedimage.png');
            imagejpeg($image, $tempdir.'/export_temp.jpg' , 100);        
            $jpgfile = $tempdir.'/export_temp.jpg';
            header("Content-type: image/jpeg");
            header("Content-Disposition: attachment; filename=export_".$date.$time.".jpg");
            header('Content-Length: ' . filesize($jpgfile));
            readfile($jpgfile);
        }
        
   }

}
