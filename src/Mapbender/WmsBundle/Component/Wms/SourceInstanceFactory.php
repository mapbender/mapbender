<?php


namespace Mapbender\WmsBundle\Component\Wms;


use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsSource;

class SourceInstanceFactory implements \Mapbender\Component\SourceInstanceFactory
{
    /** @var string|null */
    protected $defaultLayerOrder;

    /**
     * @param $defaultLayerOrder
     */
    public function __construct($defaultLayerOrder)
    {
        $this->defaultLayerOrder = $defaultLayerOrder;
    }

    /**
     * @param Source $source
     * @return WmsInstance
     */
    public function createInstance(Source $source)
    {
        /** @var WmsSource $source $instance */
        $instance = new WmsInstance();
        $instance->setSource($source);
        $instance->populateFromSource($source);

        if ($this->defaultLayerOrder) {
            $instance->setLayerOrder($this->defaultLayerOrder);
        }
        return $instance;
    }
}
