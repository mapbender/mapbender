<?php
namespace Mapbender\ManagerBundle\Component;

use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class UploadScreenshot
 *
 */
class UploadScreenshot
{
    const MAX_WIDTH  = 200;
    const MAX_HEIGHT = 200;

    /**
     * @param $filePath
     * @param $screenShotFile
     * @param $application
     * @return string
     */
    public function upload($filePath, File $screenShotFile, Application $application)
    {
        $fileName      = sprintf('screenshot-%d.%s', $application->getId(), $application->getScreenshotFile()->guessExtension());
        $fileExtension = strtolower($screenShotFile->guessExtension());
        $fullFilePath  = $filePath . "/" . $fileName;

        $screenShotFile->move($filePath, $fileName);
        $application->setScreenshot($fileName);

        switch ($fileExtension) {
            case 'png':
                $image = static::resizeImage(imagecreatefrompng($fullFilePath), static::MAX_WIDTH, static::MAX_HEIGHT);
                imagepng($image, $fullFilePath);
                break;
            case 'gif':
                $image = static::resizeImage(imagecreatefromgif($fullFilePath), static::MAX_WIDTH, static::MAX_HEIGHT);
                imagegif($image, $fullFilePath);
                break;
            case 'jpeg':
            case 'jpg':
                $image = static::resizeImage(imagecreatefromjpeg($fullFilePath), static::MAX_WIDTH, static::MAX_HEIGHT);
                imagejpeg($image, $fullFilePath);
                break;
        }
        return $fileName;
    }

    /**
     * @param $sourceImage
     * @param $width
     * @param $height
     * @return resource
     */
    static function resizeImage($sourceImage, $width, $height)
    {
        $sourceWidth         = imagesx($sourceImage);
        $sourceHeight        = imagesy($sourceImage);

        $destinationImage = imagecreatetruecolor($width, $height);
        imagealphablending($destinationImage, false);
        imagesavealpha($destinationImage, true);
        $transparent = imagecolorallocatealpha($destinationImage, 255, 255, 255, 127);
        imagefilledrectangle($destinationImage, 0, 0, $width, $height, $transparent);

        $sourceAspect = $sourceWidth / $sourceHeight;
        $targetAspect = $width / $height;
        if ($sourceAspect >= $targetAspect) {
            // wide aspect ratio
            $dstX = 0;
            $dstY = 0.5 * $height * (1 - $targetAspect / $sourceAspect);
        } else {
            // tall aspect ratio
            $dstX = 0.5 * $width * (1 - $sourceAspect / $targetAspect);
            $dstY = 0;
        }
        imagecopyresampled($destinationImage, $sourceImage,
            $dstX, $dstY,
            0, 0,
            $width - 2 * $dstX, $height - 2 * $dstY,
            $sourceWidth, $sourceHeight);

        return $destinationImage;
    }
}

