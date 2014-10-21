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
        $fileSize      = 500;

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
        $width = imagesx($source_image);
        $height = imagesy($source_image);
        
         
        if ($height > $width)  {
            $ratio = $destination_height / $height;
            $newheight = $destination_height;
            $newwidth = $width * $ratio;
        } else {
            $ratio = $destination_width / $width;
            $newwidth = $destination_width;
            $newheight = $height * $ratio;
        }

        $destination_image = imagecreatetruecolor($newwidth,$newheight);
        imagealphablending($destination_image, false);
        imagesavealpha($destination_image,true);
        $transparent = imagecolorallocatealpha($destination_image, 255, 255, 255, 127);
        imagefilledrectangle($destination_image, 0, 0, $width, $height, $transparent);
        imagecopyresized($destination_image, $source_image, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);       
        return $destination_image;
    }
}

