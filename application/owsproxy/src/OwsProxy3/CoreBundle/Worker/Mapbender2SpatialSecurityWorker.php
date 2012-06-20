<?php

namespace OwsProxy3\CoreBundle\Worker;

/*
 * Nur mit Anpassung Geoportal Saarland für Gruppen und Benutzer nutzbar.
 */

use Symfony\Component\DependencyInjection\ContainerInterface;
use OwsProxy3\CoreBundle\Event\AfterProxyEvent;
use OwsProxy3\CoreBundle\Event\BeforeProxyEvent;

class Mapbender2SpatialSecurityWorker implements AbstractWorker {
    protected $container;
    
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }
    
    /**
     * Actor for GetFeatureInfo requests
     * @param BeforeProxyEvent $event 
     */
    public function onBeforeProxyEvent(BeforeProxyEvent $event) {
        if($event->getUrl()->getParam('Request') !== 'GetFeatureInfo') {
            return;
        }

        // Check if in allowd geom
        if(1) {
            
        } else {
            throw new \RuntimeException('Queried point outside allowed geometry.');
        }
    }
    
    /**
     * Actor for GetMap requests
     * 
     * @param AfterProxyEvent $event 
     */
    public function onAfterProxyEvent(AfterProxyEvent $event) {
        if($event->getUrl()->getParam('Request') !== 'GetMap') {
            return;
        }
        
        $geometry = $this->getGeometry();
        $response = $event->getBrowserResponse();
        $content = $response->getContent();
        
        // Magic
        
        $response->setContent($content);
    }
    
    private function getGeometry() {
        $mbUserId = 9412;
        $mbUserGeom = null;
        $mbGroupIds = array();
        $mbGroupGeoms = array();
        
        $conn = $this->container->get('doctrine.dbal.mb2_connection');
        
        // Get user geom
        $stmt = $conn->executeQuery(
            'SELECT * FROM mapbender.mb_user WHERE mb_user_id = ? LIMIT 1',
            array($mbUserId)
        );
        
        if($stmt && ($row = $stmt->fetch())) {
            $mbUserGeom = $row['mb_user_spatial'];
            
            // Get user groups
            $stmt = $conn->executeQuery(
                'SELECT * FROM mapbender.mb_user_mb_group WHERE fkey_mb_user_id = ?',
                array($mbUserId)
            );
            
            while($row = $stmt->fetch()) {
                $mbGroupIds[] = $row['fkey_mb_group_id'];
                
                // Get group geoms
                $stmtGroup = $conn->executeQuery(
                    'SELECT * FROM mapbender.mb_group WHERE mb_group_id = ? LIMIT 1',
                    array($row['fkey_mb_group_id'])
                );
                
                $rowGroup = $stmtGroup->fetch();
                
                if(isset($rowGroup['mb_group_spatial']) && trim($rowGroup['mb_group_spatial']) != "") {
                    $mbGroupGeoms[] = $rowGroup['mb_group_spatial'];
                }
            }
        }
        
        $geomIds = array();
        
        if(!empty($mbUserGeom)) {
            foreach(explode(",",$mbUserGeom) as $geom) {
                $geomIds[] = trim($geom);
            }
        }
        
        if(!empty($mbGroupGeoms)) {
            foreach($mbGroupGeoms as $geoms) {
                foreach(explode(",",$geoms) as $geom) {
                    $geomIds[] = trim($geom);
                }
            }
        }
        
        $geomIds = array_unique($geomIds);

        $conn = $this->container->get('doctrine.dbal.mb2gis_connection');

        
        $stmt = $conn->executeQuery(
            'SELECT st_assvg(st_union(the_geom)) as geom,
                st_assvg(envelope(st_union(the_geom))) as envelope
            FROM gis.verwaltungseinheit 
            WHERE gem_schl IN (?)',
            array($geomIds),
            array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
        );
        
        $row = $stmt->fetch();

        $viewBox = $this->getViewBox($row["envelope"]);
        
        header("Content-Type: image/svg+xml");
        
        
        echo $this->getSVG($row["geom"],$viewBox->string);
        die();
    }
    
    private function getSVG($path, $viewbox) {
        return '<?xml version="1.0"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg width="500px" height="500px" viewBox="' . $viewbox . '" version="1.1" xmlns="http://www.w3.org/2000/svg">
	<path d="' . $path . '" fill="black" />
</svg>';
    }
    
    private function getViewBox($svgString) {
        if(preg_match("/^M [0-9 \-.]+ Z$/i",trim($svgString))) {
            $result = new \stdClass();

            $min_x = null; $min_y = null;
            $max_x = null; $max_y = null;
            $min_max = explode(" ", substr(trim($svgString), 2, -2));

            for($i=0;$i<count($min_max); $i++) {
                if($i % 2 == 0) {
                    $min_x = is_null($min_x) ? $min_max[$i] : min($min_x, floatval($min_max[$i]));
                    $max_x = is_null($max_x) ? $min_max[$i] : max($max_x, floatval($min_max[$i]));
                } else {
                    $min_y = is_null($min_y) ? $min_max[$i] : min($min_y, floatval($min_max[$i]));
                    $max_y = is_null($max_y) ? $min_max[$i] : max($max_y, floatval($min_max[$i]));
                }
            }

            $result->minX = $min_x;
            $result->minY = $min_y;
            $result->width = $max_x - $min_x;
            $result->height = $max_y - $min_y;
            $result->string = "$result->minX $result->minY $result->width $result->height";

            return $result;
        }

        return false;
    }
}
