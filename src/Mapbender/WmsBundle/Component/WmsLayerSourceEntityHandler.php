<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\Exception\NotUpdateableException;
use Mapbender\CoreBundle\Component\SourceItemEntityHandler;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\CoreBundle\Component\SourceItem;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 */
class WmsLayerSourceEntityHandler extends SourceItemEntityHandler
{

    public function create()
    {
        
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
     * @param WmsLayerSource
     * @param EntityManager
     */
    private function removeRecursively(WmsLayerSource $wmslayer)
    {
        foreach ($wmslayer->getSublayer() as $sublayer) {
            $this->removeRecursively($sublayer);
        }
        $this->container->get('doctrine')->getManager()->remove($wmslayer);
        $this->container->get('doctrine')->getManager()->flush();
    }

    /**
     * @inheritdoc
     */
    public function update(SourceItem $sourceItem)
    {
        if ($sourceItem->getName() !== $this->entity->getName()) {
            throw new NotUpdateableException("WMS Layer: " . $this->entity->getName()
            . "(" . $this->entity->getName() . ") can't be updated.");
        }
        foreach ($this->entity->getSublayer() as $layerOrigSublayer) {
            $num = 0;
            foreach ($sourceItem->getSublayer() as $layerSublayer) {
                if ($layerOrigSublayer->getName() === $layerSublayer->getName()) {
                    $num++;
                    if ($num > 1) {
                        throw new NotUpdateableException("WMS Layer: " . $layerOrigSublayer->getName()
                            . "(" . $layerOrigSublayer->getName() . ") can't be updated.");
                    }
                    $handler = self::createHandler($this->container, $layerOrigSublayer);
                    $handler->update($layerSublayer);
                }
            }
        }
        $this->entity->setName($sourceItem->getName());
        $this->entity->setTitle($sourceItem->getTitle());
        $this->entity->setLatlonBounds($sourceItem->getLatlonBounds());
        $this->entity->setSrs($sourceItem->getSrs());
        $this->entity->setAbstract($sourceItem->getAbstract());
        $this->entity->setStyles($sourceItem->getStyles());
        $this->container->get('doctrine')->getManager()->persist($this->entity);
        $this->container->get('doctrine')->getManager()->flush();
    }

}
