<?php

namespace Mapbender\ManagerBundle\Component;

class UploadScreenshot
{
        /****
     * new foo
     */
    public function upload($filePath,$screenshotFile,$application)
    {
        $fileName = sprintf('screenshot-%d.%s', $application->getId(), $application->getScreenshotFile()->guessExtension());
        $fileExtension = strtolower($screenshotFile->guessExtension());
        $fullFilePath  = $filePath."/".$fileName;
        $fileSize      = 200;

        $screenshotFile->move($filePath,$fileName);
        $application->setScreenshot($fileName);
        switch ($fileExtension) {
            case 'png':
                $image = $this->resizeImage(ImageCreateFromPng($fullFilePath), $fileSize, $fileSize, 0);
                imagepng($image, $fullFilePath);
                break;
            case 'gif':
                $image = $this->resizeImage(ImageCreateFromGif($fullFilePath), $fileSize, $fileSize, 0);
                imagegif($image, $fullFilePath);
                break;
            case 'jpeg':
            case 'jpg':
                $image = $this->resizeImage(ImageCreateFromJpeg($fullFilePath), $fileSize, $fileSize, 0);
                imagejpeg($image, $fullFilePath);
                break;
        }
        return $fileName;
    }

 private function resizeImage($source_image, $destination_width, $destination_height)
    {       
//        $width = imagesx($source_image);
//        $height = imagesy($source_image);
        
       $source_width = imagesx($source_image);
       $source_height = imagesy($source_image);
       
       if ($source_width === $destination_width && $source_height === $destination_height){
            $destination_image = imagecreatetruecolor($destination_width,$destination_height);
            imagealphablending($destination_image, false);
            imagesavealpha($destination_image,true);
            $transparent = imagecolorallocatealpha($destination_image, 255, 255, 255, 127);
            imagefilledrectangle($destination_image, 0, 0, $destination_width, $destination_height, $transparent);
            imagecopyresized($destination_image, $source_image, 0, 0, 0, 0, $destination_width, $destination_height, $source_width, $source_height);     
       }else{
           
           $tmp_width = (int)($source_width / 100);
           $tmp_height = (int)($source_height / 100);
           
           
           $destinatin_scale = min($tmp_width,$tmp_height);
           
           $destination_width = $destinatin_scale * 100;
           $destination_height = $destinatin_scale * 100;
                      
            
           $dstX = (int)(($source_width-$destination_width)*0.5);
           $dstY = (int)(($source_height-$destination_height)*0.5);
           
           $destination_image = imagecreatetruecolor($destination_width,$destination_height);
           imagealphablending($destination_image, false);
           imagesavealpha($destination_image,true);
           $transparent = imagecolorallocatealpha($destination_image, 255, 255, 255, 127);
           imagefilledrectangle($destination_image, 0, 0, $destination_width, $destination_height, $transparent);
           imagecopyresampled($destination_image,$source_image,0,0,$dstX,$dstY, $source_width, $source_height,$source_width, $source_height);           
       }
         
          
       return $destination_image;
    }
}

