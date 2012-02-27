<?php

namespace Mapbender\WmtsBundle;

use Mapbender\CoreBundle\Component\LayerInterface;
use Mapbender\WmtsBundle\Entity\WmtsInstance;


/**
 * Base WMTS class
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class WmtsLayerLoader implements LayerInterface {
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
        $em = $this->doctrine->getEntityManager();
        $query = $em->createQuery(
            'SELECT i FROM MapbenderWmtsBundle:WmtsInstance i WHERE i.layersetid = :layersetid AND i.layerid= :layerid'
            )->setParameter('layersetid', $this->layerSetId)->setParameter('layerid', $this->layerId);
        $wmtsinstanceList = $query->getResult();
        foreach($wmtsinstanceList as $wmtsinstance){ 
            $wmts = $wmtsinstance->getWmts_service();
            $layer = $this->doctrine->getRepository('MapbenderWmtsBundle:WmtsLayerDetail')->find($wmtsinstance->getLayeridentifier());
            $this->configuration["proxy"] = $wmtsinstance->getProxy();
            $this->configuration["baselayer"] = true; // TODO ??
            $this->configuration["visible"] = $wmtsinstance->getVisible();
            $this->configuration["title"] = "Hintergrundkarte"; // ??? title: WMTS or from YAML
//                    
            $this->configuration["url"] = ($wmts->getRequestGetTileGETREST()!==null)? $wmts->getRequestGetTileGETREST() : $wmts->getRequestGetTileGETKVP();
            $this->configuration["layer"] = $layer->getTitle();
            $this->configuration["style"] = $wmtsinstance->getStyle();
            $this->configuration["matrixSet"] = $wmtsinstance->getMatrixset();
            $this->configuration["origin"] = $wmtsinstance->getTopleftcorner();
            $this->configuration["format"] = $wmtsinstance->getFormat();
            $this->configuration["tileSize"] = $wmtsinstance->getTilesize();
            $this->configuration["matrixIds"] = $wmtsinstance->getMatrixids();
            $this->configuration["tileFullExtent"] = $wmtsinstance->getCrsbound();
        }
    }

    public function render() {
        return array(
            'id' => $this->layerId,
            'type' => 'wmts',
            'configuration' => $this->configuration,
        );
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapbender.layer.wmts.js'
            )
        );
    }
}