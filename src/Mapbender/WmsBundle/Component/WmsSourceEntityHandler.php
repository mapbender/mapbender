<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\Utils;
use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 */
class WmsSourceEntityHandler extends SourceEntityHandler
{
    
    public function create()
    {
        ;
    }
    
    /**
     * @inheritdoc
     */
    public function createInstance(Layerset $layerset)
    {
        $instance = new WmsInstance();
        $instance->setSource($this->entity);
        $instance->setLayerset($layerset);
        $entityHandler = self::createHandler($this->container, $instance);
        $entityHandler->create();
        return $instance;
    }
    
//    /**
//     * Adds sublayers
//     * 
//     * @param WmsIstance $instance
//     * @param WmsLayerSource $wmslayer
//     * @param WmsInstanceLayer $instlayer
//     * @param integer $num
//     */
//    private function addSublayer(WmsInstance $instance, WmsLayerSource $wmslayer, WmsInstanceLayer $instlayer, &$num)
//    {
//        foreach ($wmslayer->getSublayer() as $wmssublayer) {
//            $num++;
//            $instsublayer = new WmsInstanceLayer();
//            $instsublayer->setSourceInstance($instance);
//            $instsublayer->setSourceItem($wmssublayer);
//            $instsublayer->setTitle($wmssublayer->getTitle());
//            // @TODO min max from scaleHint
//            $instsublayer->setMinScale(
//                $wmssublayer->getScaleRecursive() !== null ?
//                    $wmssublayer->getScaleRecursive()->getMin() : null);
//            $instsublayer->setMaxScale(
//                $wmssublayer->getScaleRecursive() !== null ?
//                    $wmssublayer->getScaleRecursive()->getMax() : null);
//            $queryable = $wmssublayer->getQueryable();
//            $instsublayer->setInfo(Utils::getBool($queryable));
//            $instsublayer->setAllowinfo(Utils::getBool($queryable));
//
//            $instsublayer->setPriority($num);
//            $instsublayer->setParent($instlayer);
//            $instance->addLayer($instsublayer);
//            if ($wmssublayer->getSublayer()->count() > 0) {
//                $instsublayer->setToggle(false);
//                $instsublayer->setAllowtoggle(true);
//            }
//            $this->addSublayer($instance, $wmssublayer, $instsublayer, $num);
//        }
//    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        $layerHandler = self::createHandler($this->container, $this->entity->getRootlayer());
        $layerHandler->remove();
        $this->container->get('doctrine')->getManager()->remove($this->entity);
        $this->container->get('doctrine')->getManager()->flush();
    }
    
    public function getSourceDimensions(Source $source)
    {
        $dimensions = array();
        foreach ($source->getLayers() as $layer) {
            foreach($layer->getDimension() as $dimension){
                $dimensions[] = $dimension;
            }
        }
        return $dimensions;
    }


}
