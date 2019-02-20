<?php


namespace Mapbender\PrintBundle\Component\Transport;


use Mapbender\Component\Transport\HttpTransportInterface;

/**
 * Bridge between http transport and GD image resource.
 */
class ImageTransport
{
    /** @var HttpTransportInterface */
    protected $baseTransport;

    /**
     * @param HttpTransportInterface $baseTransport
     */
    public function __construct(HttpTransportInterface $baseTransport)
    {
        $this->baseTransport = $baseTransport;
    }

    /**
     * Download given $url ~directly into a GD image resource, optionally also multiplying its
     * alpha by the given $opacity.
     *
     * @param string $url
     * @param float $opacity in [0;1]
     * @return resource|null GDish
     */
    public function downloadImage($url, $opacity=1.0)
    {
        try {
            $response = $this->baseTransport->getUrl($url);
            $image = @imagecreatefromstring($response->getContent());
            if ($image) {
                imagesavealpha($image, true);
                if ($opacity < (1.0 - 1 / 127)) {
                    return $this->multiplyAlpha($image, $opacity);
                } else {
                    return $image;
                }
            } else {
                return null;
            }
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Multiply the alpha channgel of the whole $image with the given $opacity.
     * May return a different image than given if the input $image is not
     * in truecolor format.
     *
     * @param resource $image GDish
     * @param float $opacity
     * @return resource GDish
     */
    protected function multiplyAlpha($image, $opacity)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        if (!imageistruecolor($image)) {
            // promote to RGBA image first
            $imageCopy = imagecreatetruecolor($width, $height);
            imagesavealpha($imageCopy, true);
            imagealphablending($imageCopy, false);
            imagecopyresampled($imageCopy, $image, 0, 0, 0, 0, $width, $height, $width, $height);
            imagedestroy($image);
            $image = $imageCopy;
            unset($imageCopy);
        }
        imagealphablending($image, false);

        // Taking the painful way to alpha blending
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $colorIn = imagecolorat($image, $x, $y);
                $alphaIn = $colorIn >> 24 & 0x7F;
                if ($alphaIn === 127) {
                    // pixel is already fully transparent, no point
                    // modifying it
                    continue;
                }
                $alphaOut = intval(127 - (127 - $alphaIn) * $opacity);

                $colorOut = imagecolorallocatealpha(
                    $image,
                    $colorIn >> 16 & 0xFF,
                    $colorIn >> 8 & 0xFF,
                    $colorIn & 0xFF,
                    $alphaOut);
                imagesetpixel($image, $x, $y, $colorOut);
                imagecolordeallocate($image, $colorOut);
            }
        }
        return $image;
    }
}
