<?php

namespace Mapbender\WmsBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

/**
 * MapbenderWmsBundle
 */
class MapbenderWmsBundle extends MapbenderBundle
{

    /**
     * @inheritdoc
     */
    public function getElements()
    {
        $elements = array(
            'Mapbender\WmsBundle\Element\WmsLoader',
        );
        if ($this->container->getParameter('mapbender.preview.element.dimensionshandler')) {
            $elements[] = 'Mapbender\WmsBundle\Element\DimensionsHandler';
        }
        return $elements;
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryManagers()
    {
        return array(
            'wms' => array(
                'id' => 'wms',
                'label' => 'OGC WMS',
                'manager' => 'mapbender_wms_repository',
                'startAction' => "MapbenderWmsBundle:Repository:start",
                'updateformAction' => "MapbenderWmsBundle:Repository:updateform",
                'bundle' => "MapbenderWmsBundle"
            )
        );
    }
}
