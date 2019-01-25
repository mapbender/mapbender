<?php


namespace Mapbender\PrintBundle\Component;

use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Transport\ImageTransport;
use Psr\Log\LoggerInterface;

/**
 * Renders Wms layers in export and print.
 */
class LayerRendererWms extends LayerRenderer
{
    /** @var LoggerInterface */
    protected $logger;
    /** @var ImageTransport */
    protected $imageTransport;

    /**
     * @param ImageTransport $imageTransport
     * @param LoggerInterface $logger
     */
    public function __construct(ImageTransport $imageTransport, LoggerInterface $logger)
    {
        $this->imageTransport = $imageTransport;
        $this->logger = $logger;
    }

    public function addLayer(ExportCanvas $canvas, $layerDef, Box $extent)
    {
        if (empty($layerDef['url'])) {
            $this->logger->warning("Missing url in WMS layer", $layerDef);
            return;
        }
        $url = $this->preprocessUrl($layerDef, $canvas, $extent);

        $layerImage = $this->imageTransport->downloadImage($url, $layerDef['opacity']);
        if ($layerImage) {
            imagecopyresampled($canvas->resource, $layerImage,
                0, 0, 0, 0,
                $canvas->getWidth(), $canvas->getHeight(),
                imagesx($layerImage), imagesy($layerImage));
            imagedestroy($layerImage);
            unset($layerImage);
        } else {
            $this->logger->warning("Failed request to {$url}");
        }
    }

    public function squashLayerDefinitions($layerDef, $nextLayerDef)
    {
        // @todo: merge requests with same path and BBOX by appending
        //        LAYERS params left-to-right
        return false;
    }

    /**
     * @param $layerDef
     * @param GdCanvas $canvas
     * @param Box $extent
     * @return string
     */
    protected function preprocessUrl($layerDef, $canvas, Box $extent)
    {
        $params = array(
            'WIDTH' => $canvas->getWidth(),
            'HEIGHT' => $canvas->getHeight(),
        );
        if (!empty($layerDef['changeAxis'])){
            $params['BBOX'] = $extent->bottom . ',' . $extent->left . ',' . $extent->top . ',' . $extent->right;
        } else {
            $params['BBOX'] = $extent->left . ',' . $extent->bottom . ',' . $extent->right . ',' . $extent->top;
        }
        return UrlUtil::validateUrl($layerDef['url'], $params);
    }
}
