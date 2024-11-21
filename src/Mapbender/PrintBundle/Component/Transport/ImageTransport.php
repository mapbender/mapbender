<?php


namespace Mapbender\PrintBundle\Component\Transport;


use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\PrintBundle\Util\GdUtil;
use Psr\Log\LoggerInterface;

/**
 * Bridge between http transport and GD image resource.
 */
class ImageTransport
{
    protected HttpTransportInterface $baseTransport;
    protected LoggerInterface $logger;

    public function __construct(HttpTransportInterface $baseTransport, LoggerInterface $logger)
    {
        $this->baseTransport = $baseTransport;
        $this->logger = $logger;
    }

    /**
     * Download given $url ~directly into a GD image resource, optionally also multiplying its
     * alpha by the given $opacity.
     *
     * @param string $url
     * @param float $opacity in [0;1]
     * @return ?\GdImage GDish
     */
    public function downloadImage($url, $opacity=1.0)
    {
        try {
            $response = $this->baseTransport->getUrl($url);
            $content = $response->getContent();
            $image = imagecreatefromstring($content);
            if ($image === false) {
                // If $image is false, the download probably failed, most likely because
                // of network issues or restrictions (such as a HTTP proxy) (see #1549)
                $this->logger->error("Could not download image from url ".$url.". RETURNS: ".$content);
                return null;
            }
            imagesavealpha($image, true);
            if ($opacity <= (1.0 - 1 / 127)) {
                $multipliedImage = GdUtil::multiplyAlpha($image, $opacity);
                if ($multipliedImage !== $image) {
                    imagedestroy($image);
                }
                return $multipliedImage;
            } else {
                return $image;
            }
        } catch (\ErrorException $e) {
            return null;
        }
    }
}
