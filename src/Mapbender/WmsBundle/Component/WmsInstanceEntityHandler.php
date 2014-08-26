<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Component\Utils;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 */
class WmsInstanceEntityHandler extends SourceInstanceEntityHandler
{

    /**
     * @inheritdoc
     */
    public function create()
    {
        $this->entity->setTitle($this->entity->getSource()->getTitle());
        $formats = $this->entity->getSource()->getGetMap()->getFormats();
        $this->entity->setFormat(count($formats) > 0 ? $formats[0] : null);
        $infoformats = $this->entity->getSource()->getGetFeatureInfo() !== null ?
        $this->entity->getSource()->getGetFeatureInfo()->getFormats() : array();
        $this->entity->setInfoformat(count($infoformats) > 0 ? $infoformats[0] : null);
        $excformats = $this->entity->getSource()->getExceptionFormats();
        $this->entity->setExceptionformat(count($excformats) > 0 ? $excformats[0] : null);
//        $this->entity->setOpacity(100);
        
        $this->entity->setWeight(-1);
        
        $this->container->get('doctrine')->getManager()->persist($this->entity);
        $this->container->get('doctrine')->getManager()->flush();
        
        $wmslayer_root = $this->entity->getSource()->getRootlayer();
        
        $instLayer = new WmsInstanceLayer();
        
        $entityHandler = self::createHandler($this->container, $instLayer);
        $entityHandler->create($this->entity, $wmslayer_root);
        
//        $instLayer_root->setSourceInstance($this->entity);
//        $instLayer_root->setSourceItem($wmslayer_root);
//        $instLayer_root->setTitle($wmslayer_root->getTitle());
//        // @TODO min max from scaleHint
//        $instLayer_root->setMinScale(
//            $wmslayer_root->getScaleRecursive() !== null ?
//                $wmslayer_root->getScaleRecursive()->getMin() : null);
//        $instLayer_root->setMaxScale(
//            $wmslayer_root->getScaleRecursive() !== null ?
//                $wmslayer_root->getScaleRecursive()->getMax() : null);
//        $queryable = $wmslayer_root->getQueryable();
//        $instLayer_root->setInfo(Utils::getBool($queryable));
//        $instLayer_root->setAllowinfo(Utils::getBool($queryable));
//
//        $instLayer_root->setToggle(false);
//        $instLayer_root->setAllowtoggle(true);
//
//        $instLayer_root->setPriority($num);
//        $this->entity->addLayer($instLayer_root);
//        $this->addSublayer($this->entity, $wmslayer_root, $instLayer_root, $num);
//
//        $this->entity->setWeight(-1);
//
//        $this->entity->getLayerset()->addInstance($this->entity);
//        $em = $this->container->get('doctrine')->getManager();
//        $em->persist($this->entity);
//        $em->persist($this->entity->getLayerset()->getApplication());
//        $em->persist($this->entity->getLayerset());
//        $em->flush();
//
        $num = 0;
        foreach ($this->entity->getLayerset()->getInstances() as $instance) {
            $this->entity->setWeight($num);
            $this->entity->generateConfiguration();
            $this->container->get('doctrine')->getManager()->persist($instance);
            $this->container->get('doctrine')->getManager()->flush();
            $num++;
        }
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        
        $layerHandler = self::createHandler($this->container, $this->entity->getRootlayer());
        $layerHandler->remove();
        $this->container->get('doctrine')->getManager()->remove($this->entity);
    }

    public function getSourceDimensions()
    {
        $dimensions = array();
        foreach ($this->entity->getSource()->getLayers() as $layer) {
            foreach ($layer->getDimension() as $dimension) {
                $dimensions[] = $dimension;
            }
        }
        return $dimensions;
    }

}
