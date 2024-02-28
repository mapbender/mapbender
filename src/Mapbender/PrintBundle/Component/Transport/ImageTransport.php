<?php


namespace Mapbender\PrintBundle\Component\Transport;


use Psr\Log\LoggerInterface;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\PrintBundle\Util\GdUtil;

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
     * @param LoggerInterface $logger
     * @return resource|null GDish
     */
    public function downloadImage($url, $opacity=1.0, $logger)
    {
        try {
            $response = $this->baseTransport->getUrl($url);
            $image = imagecreatefromstring($response->getContent());
            if ($image == null) {
                /*
                 * If $image is null, the download probably failed, most likely
                 * because of network issues or restrictions (such as a HTTP proxy).
                 * 
                 * See #1549
                 */
                $logger->error('Could not download image from url {url}');
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
