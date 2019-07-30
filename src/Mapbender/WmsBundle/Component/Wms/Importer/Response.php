<?php


namespace Mapbender\WmsBundle\Component\Wms\Importer;


use Mapbender\Component\Loader\SourceLoaderResponse;
use Mapbender\WmsBundle\Entity\WmsSource;

/**
 * @deprecated
 * @method WmsSource getSource
 */
class Response extends SourceLoaderResponse
{
    /**
     * @return WmsSource
     */
    public function getWmsSourceEntity()
    {
        return $this->getSource();
    }

}
