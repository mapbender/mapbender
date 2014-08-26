<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceInstanceItemEntityHandler;
use Mapbender\CoreBundle\Component\Utils;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Component\SourceItem;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 */
class WmsInstanceLayerEntityHandler extends SourceInstanceItemEntityHandler
{

    /**
     * @inheritdoc
     */
    public function create(SourceInstance $instance, SourceItem $wmslayersource, $num = 0)
    {
        $this->entity->setSourceInstance($instance);
        $this->entity->setSourceItem($wmslayersource);
        $this->entity->setTitle($wmslayersource->getTitle());
        // @TODO min max from scaleHint
        $this->entity->setMinScale(
            $wmslayersource->getScaleRecursive() !== null ?
                $wmslayersource->getScaleRecursive()->getMin() : null);
        $this->entity->setMaxScale(
            $wmslayersource->getScaleRecursive() !== null ?
                $wmslayersource->getScaleRecursive()->getMax() : null);
        $queryable = $wmslayersource->getQueryable();
        $this->entity->setInfo(Utils::getBool($queryable));
        $this->entity->setAllowinfo(Utils::getBool($queryable));

        $this->entity->setToggle(false);
        $this->entity->setAllowtoggle(true);

        $this->entity->setPriority($num);
        $num++;
        $instance->addLayer($this->entity);
        if ($wmslayersource->getSublayer()->count() > 0) {
            $this->entity->setToggle(false);
            $this->entity->setAllowtoggle(true);
        }
        $this->container->get('doctrine')->getManager()->persist($this->entity);
        $this->container->get('doctrine')->getManager()->flush();
        foreach ($wmslayersource->getSublayer() as $wmslayersourceSub) {
            $entityHandler = self::createHandler($this->container, new WmsInstanceLayer());
            $entityHandler->create($instance, $wmslayersourceSub, $num);
            $entityHandler->getEntity()->setParent($this->entity);
            $this->entity->addSublayer($entityHandler->getEntity());
            $this->container->get('doctrine')->getManager()->persist($this->entity);
            $this->container->get('doctrine')->getManager()->persist($entityHandler->getEntity());
            $this->container->get('doctrine')->getManager()->flush();
        }
//        return 
//        $this->addSublayer($this->entity, $wmslayersource, $this->entity, $num);
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
//        $num = 0;
//        foreach ($this->entity->getLayerset()->getInstances() as $instance) {
//            $this->entity->setWeight($num);
//            $this->entity->generateConfiguration();
//            $em->persist($instance);
//            $em->flush();
//            $num++;
//        }
//        $num++;
//        $instsublayer = new WmsInstanceLayer();
//        $instsublayer->setSourceInstance($instance);
//        $instsublayer->setSourceItem($wmssublayer);
//        $instsublayer->setTitle($wmssublayer->getTitle());
//        // @TODO min max from scaleHint
//        $instsublayer->setMinScale(
//            $wmssublayer->getScaleRecursive() !== null ?
//                $wmssublayer->getScaleRecursive()->getMin() : null);
//        $instsublayer->setMaxScale(
//            $wmssublayer->getScaleRecursive() !== null ?
//                $wmssublayer->getScaleRecursive()->getMax() : null);
//        $queryable = $wmssublayer->getQueryable();
//        $instsublayer->setInfo(Utils::getBool($queryable));
//        $instsublayer->setAllowinfo(Utils::getBool($queryable));
//
//        $instsublayer->setPriority($num);
//        $instsublayer->setParent($instlayer);
//        $instance->addLayer($instsublayer);
//        if ($wmssublayer->getSublayer()->count() > 0) {
//            $instsublayer->setToggle(false);
//            $instsublayer->setAllowtoggle(true);
//        }
//        $this->addSublayer($instance, $wmssublayer, $instsublayer, $num);
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        $this->removeRecursively($this->entity);
    }

    /**
     * Recursively remove a nested Layerstructure
     * @param WmsInstanceLayer
     * @param EntityManager
     */
    private function removeRecursively(WmsInstanceLayer $wmslayer)
    {
        foreach ($wmslayer->getSublayer() as $sublayer) {
            $this->removeRecursively($sublayer);
        }
        $this->container->get('doctrine')->getManager()->remove($wmslayer);
        $this->container->get('doctrine')->getManager()->flush();
    }

    public function getSourceDimensions()
    {
        $dimensions = array();
        foreach ($this->wmsinstance->getSource()->getLayers() as $layer) {
            foreach ($layer->getDimension() as $dimension) {
                $dimensions[] = $dimension;
            }
        }
        return $dimensions;
    }

}
