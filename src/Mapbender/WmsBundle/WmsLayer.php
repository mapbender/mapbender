<?php

namespace Mapbender\WmsBundle;

use Mapbender\CoreBundle\Component\LayerInterface;

/**
 * Base WMS class
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */
class WmsLayer implements LayerInterface {
    protected $layerSetId;
    protected $layerId;
    protected $configuration;
    protected $doctrine;

    public function __construct($layerSetId, $layerId, array $configuration, $doctrine = null) {
        $this->layerSetId = $layerSetId;
        $this->layerId = $layerId;
        $this->configuration = $configuration;
        if($doctrine!==null){
            $this->doctrine = $doctrine;
            $this->loadLayer();
        }
    }
    
    public function loadLayer(){
        $a=0;
    }

    public function render() {
        return array(
            'id' => $this->layerId,
            'type' => 'wms',
            'configuration' => $this->configuration,
        );
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapbender.layer.wms.js'
            )
        );
    }
}

