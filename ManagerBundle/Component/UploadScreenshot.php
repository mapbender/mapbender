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
        $isGeometryIdentical = $sourceWidth === $width && $sourceHeight === $height;

        if ($isGeometryIdentical) {
            $destinationImage = imagecreatetruecolor($width, $height);
            imagealphablending($destinationImage, false);
            imagesavealpha($destinationImage, true);
            $transparent = imagecolorallocatealpha($destinationImage, 255, 255, 255, 127);
            imagefilledrectangle($destinationImage, 0, 0, $width, $height, $transparent);
            imagecopyresized($destinationImage, $sourceImage, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
        } else {
            $tmpWidth         = (int)($sourceWidth / 100);
            $tmpHeight        = (int)($sourceHeight / 100);
            $destinationScale = min($tmpWidth, $tmpHeight);
            $width            = $destinationScale * 100;
            $height           = $destinationScale * 100;
            $dstX             = (int)(($sourceWidth - $width) * 0.5);
            $dstY             = (int)(($sourceHeight - $height) * 0.5);
            $destinationImage = imagecreatetruecolor($width, $height);

            imagealphablending($destinationImage, false);
            imagesavealpha($destinationImage, true);
            $transparent = imagecolorallocatealpha($destinationImage, 255, 255, 255, 127);
            imagefilledrectangle($destinationImage, 0, 0, $width, $height, $transparent);
            imagecopyresampled($destinationImage, $sourceImage, 0, 0, $dstX, $dstY, $sourceWidth, $sourceHeight, $sourceWidth, $sourceHeight);
        }

        return $destinationImage;
    }
}

