<?php

namespace Mapbender\WmtsBundle;

use Mapbender\CoreBundle\Component\LayerInterface;
use Mapbender\WmtsBundle\Entity\WmtsInstance;


/**
 * Base WMTS class
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */
class WmtsLayer implements LayerInterface {
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
//            if($serviceref->getType()=="WMTS"){
//                $wmts = $this->doctrine->getRepository("MapbenderWmtsBundle:WMTSService")->findOneById($serviceref->getWmtsid());
//                $alllayer = $wmts->getAllLayer();
//                foreach($alllayer as $layer) {
//                    $layerref = $this->doctrine->getRepository('BkgGeoportalBundle:BkgLayerRef')->findOneByWmtslayerid($layer->getId());
//                    
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
//                    
////                        #title: Hintergrundkarte
////                        url: http://arcgis.geodatenzentrum.de/arcgis/rest/services/Webatlas/MapServer/WMTS/tile
//                               http://arcgis.geodatenzentrum.de/arcgis/rest/services/Webatlas/MapServer/WMTS/tile/1.0.0/
//                               
//                               http://arcgis.geodatenzentrum.de/arcgis/rest/services/Webatlas_grau/MapServer/WMTS/tile
//                               http://arcgis.geodatenzentrum.de/arcgis/rest/services/Webatlas_grau/MapServer/WMTS/tile/1.0.0/
//                               
//                               http://arcgis.geodatenzentrum.de/arcgis/rest/services/DOP_cache/MapServer/WMTS/tile
//                               http://arcgis.geodatenzentrum.de/arcgis/rest/services/DOP_cache/MapServer/WMTS/tile/1.0.0/
//                               
////                        layer: Webatlas
////                        style: default
////                        #matrixSet: nativeTileMatrixSet
////                        matrixIds:
////                            - { identifier: "0", scaleDenominator: 10000000.0 }
////                            - { identifier: "1", scaleDenominator: 7500000.0 }
////                            - { identifier: "2", scaleDenominator: 5000000.0 }
////                            - { identifier: "3", scaleDenominator: 3000000.0 }
////                            - { identifier: "4", scaleDenominator: 2000000.0 }
////                            - { identifier: "5", scaleDenominator: 1500000.0 }
////                            - { identifier: "6", scaleDenominator: 1000000.0 }
////                            - { identifier: "7", scaleDenominator: 750000.0 }
////                            - { identifier: "8", scaleDenominator: 500000.0 }
////                            - { identifier: "9", scaleDenominator: 300000.0 }
////                            - { identifier: "10", scaleDenominator: 150000.0 }
////                            - { identifier: "11", scaleDenominator: 100000.0 }
////                            - { identifier: "12", scaleDenominator: 75000.0 }
////                            - { identifier: "13", scaleDenominator: 30000.0 }
////                            - { identifier: "14", scaleDenominator: 20000.0 }
////                            - { identifier: "15", scaleDenominator: 15000.0 }
////                            - { identifier: "16", scaleDenominator: 10000.0 }
////                            - { identifier: "17", scaleDenominator: 7500.0 }
////                            - { identifier: "18", scaleDenominator: 5000.0 }
////                            - { identifier: "19", scaleDenominator: 3000.0 }
////                        origin: [-5120900.0, 9998100.0]
////                        format: image/jpg
////                        tileSize: [512, 512]
////                        tileFullExtent: [280388.0, 5235855.0, 921290.0, 6101349.0]
////                        #visible: true
////                        #baselayer: true
////                        #proxy: true
                    $a= 0;
//                    break; // only one layer supported
//                }
//            }
        }
//        $em = $this->doctrine->getEntityManager();
//        $em = $em;
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

