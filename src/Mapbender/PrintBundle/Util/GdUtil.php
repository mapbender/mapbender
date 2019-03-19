<?php


namespace Mapbender\PrintBundle\Util;


class GdUtil
{
    /**
     * Multiply the alpha channgel of the whole $image with the given $opacity.
     * May return a different image than given if the input $image is not
     * in truecolor format. The caller is responsible for cleanup of new and old
     * resources.
     *
     * @param resource $image GDish
     * @param float $opacity in [0;1]
     * @return resource GDish
     */
    public static function multiplyAlpha($image, $opacity)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        if (!imageistruecolor($image)) {
            // promote to RGBA image first
            $workImage = imagecreatetruecolor($width, $height);
            imagesavealpha($workImage, true);
            imagealphablending($workImage, false);
            imagecopyresampled($workImage, $image, 0, 0, 0, 0, $width, $height, $width, $height);
        } else {
            $workImage = $image;
        }
        imagealphablending($workImage, false);

        // Taking the painful way to alpha blending
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $colorIn = imagecolorat($workImage, $x, $y);
                $alphaIn = $colorIn >> 24 & 0x7F;
                if ($alphaIn === 127) {
                    // pixel is already fully transparent, no point
                    // modifying it
                    continue;
                }
                $alphaOut = intval(127 - (127 - $alphaIn) * $opacity);

                $colorOut = imagecolorallocatealpha(
                    $workImage,
                    $colorIn >> 16 & 0xFF,
                    $colorIn >> 8 & 0xFF,
                    $colorIn & 0xFF,
                    $alphaOut);
                imagesetpixel($workImage, $x, $y, $colorOut);
                imagecolordeallocate($workImage, $colorOut);
            }
        }
        return $workImage;
    }

    /**
     * @param string $fontName
     * @param float $fontSize
     * @param string $text
     * @return float[] width and height (numerically indexed)
     */
    public static function getTtfTextSize($fontName, $fontSize, $text)
    {
        $labelBbox = imagettfbbox($fontSize, 0, $fontName, $text);
        $width = $labelBbox[2] - $labelBbox[0];
        $height = abs($labelBbox[5] - $labelBbox[3]);
        return array(
            $width,
            $height,
        );
    }
}
