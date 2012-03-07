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
        return; // TODO
        $em = $this->doctrine->getEntityManager();
        $query = $em->createQuery(
            'SELECT i FROM MapbenderWmsBundle:WmsInstance i WHERE i.layersetid = :layersetid AND i.layerid= :layerid'
            )->setParameter('layersetid', $this->layerSetId)->setParameter('layerid', $this->layerId);
        $wmsinstanceList = $query->getResult();
        foreach($wmsinstanceList as $wmsinstance){
            $wms = $wmsinstance->getWms_service();
            $layer = $this->doctrine->getRepository('MapbenderWmsBundle:WMSLayer')->find($wmsinstance->getLayeridentifier());
            $this->configuration["proxy"] = $wmsinstance->getProxy();
            $this->configuration["baselayer"] = true; // TODO ??
            $this->configuration["visible"] = $wmsinstance->getVisible();
            $this->configuration["title"] = "Hintergrundkarte"; // ??? title: WMS or from YAML

            $this->configuration["url"] = $wms->getRequestGetMapGET();//($wms->getRequestGetMapGET()!==null)? $wms->getRequestGetTileGETREST() : $wms->getRequestGetTileGETKVP();
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

