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
    protected $application;

    public function __construct($layerSetId, $layerId, array $configuration, $application) {
        $this->layerSetId = $layerSetId;
        $this->layerId = $layerId;
        $this->configuration = $configuration;
        $this->application = $application;
    }
    
    public function getConfiguration(){
        return $this->configuration;
    }
    
    public function getLayerSetId(){
        return $this->layerSetId;
    }
    
	public function getLayerId(){
        return $this->layerId;
    }
    
    public function loadLayer(){
        $em = $this->application->get("doctrine")->getEntityManager();
        $query = $em->createQuery(
            'SELECT i FROM MapbenderWmsBundle:WmsInstance i WHERE i.layersetid = :layersetid AND i.layerid= :layerid'
            )->setParameter('layersetid', $this->layerSetId)->setParameter('layerid', $this->layerId);
        $wmsinstanceList = $query->getResult();
        foreach($wmsinstanceList as $wmsinstance){
            $wms = $wmsinstance->getService();
            $this->configuration["proxy"] = $wmsinstance->getProxy();
            $this->configuration["baselayer"] = $wmsinstance->getBaselayer();
            $this->configuration["visible"] = $wmsinstance->getVisible();
            $this->configuration["title"] = $wmsinstance->getLayerid();

            $this->configuration["url"] = $wms->getRequestGetMapGET();
            $this->configuration["layers"] = $wmsinstance->getLayers();
            $this->configuration["format"] = $wmsinstance->getFormat();
            $this->configuration["transparent"] = $wmsinstance->getTransparent();
            $this->configuration["tiled"] = $wmsinstance->getTiled();
        }
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

