<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

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
    public function updateFromSourceItem(SourceItem $sourceItem)
    {
        foreach ($this->entity->getSublayer() as $layerOrigSublayer) {
            foreach ($sourceItem->getSublayer() as $layerFreshSublayer) {
                if ($layerOrigSublayer->getName() === $layerFreshSublayer->getName()) {
                    $handler = self::createHandler($this->container, $layerOrigSublayer);
                    $handler->updateFromSourceItem($layerFreshSublayer);
                    break;
                }
            }
        }
        $this->entity->setName($sourceItem->getName());
        $this->entity->setTitle($sourceItem->getTitle());
        $this->entity->setLatlonBounds($sourceItem->getLatlonBounds());
        $this->entity->setSrs($sourceItem->getSrs());
        $this->entity->setAbstract($sourceItem->getAbstract());
        $this->container->get('doctrine')->getManager()->persist($this->entity);
        $this->container->get('doctrine')->getManager()->flush();
    }

}
