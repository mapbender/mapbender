<?php

namespace Mapbender\WmsBundle;

use Mapbender\CoreBundle\Component\Layer;

/**
 * Base WMS class
 *
 * @author Christian Wygoda
 */
class WmsLayer extends Layer {
    public function getType() {
        return 'wms';
    }

    public function getAssets() {
        return array(
            'js' => array('@MapbenderWmsBundle/Resources/public/mapbender.layer.wms.js'),
            'css' => array());
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
            $this->configuration["srs"] = $wmsinstance->getSrs();
            $this->configuration["url"] = $wms->getRequestGetMapGET();
            $this->configuration["layers"] = $wmsinstance->getLayers();
            $this->configuration["format"] = $wmsinstance->getFormat();
            $this->configuration["transparent"] = $wmsinstance->getTransparent();
            $this->configuration["tiled"] = $wmsinstance->getTiled();
        }
    }
}

