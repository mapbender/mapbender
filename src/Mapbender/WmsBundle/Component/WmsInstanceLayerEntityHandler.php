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

    /**
     * Generates a configuration for layers
     *
     * @param array $configuration
     * @return array
     */
    public function generateConfiguration()
    {
        $configuration = array();
        if ($this->entity->getActive() === true) {
            $children = array();
            if ($this->entity->getSublayer()->count() > 0) {
                foreach ($this->entity->getSublayer() as $sublayer) {
                    $instLayHandler = self::createHandler($this->container, $sublayer);
                    $configurationTemp = $instLayHandler->generateConfiguration();
                    if (count($configurationTemp) > 0) {
                        $children[] = $configurationTemp;
                    }
                }
            }
            $layerConf = $this->entity->getConfiguration();
            $configuration = array(
                "options" => $layerConf,
                "state" => array(
                    "visibility" => null,
                    "info" => null,
                    "outOfScale" => null,
                    "outOfBounds" => null),);
            if (count($children) > 0) {
                $configuration["children"] = $children;
            }
        }
        return $configuration;
    }

}
