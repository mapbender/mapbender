<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceItemEntityHandler;
use Mapbender\WmsBundle\Entity\WmsLayerSource;

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

}
