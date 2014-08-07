<?php

namespace Mapbender\CoreBundle\Component;

use Doctrine\ORM\EntityManager;

/**
 * InstanceLayer
 * 
 * @author Paul Schmidt
 */
interface InstanceLayerInXXX
{

    /**
     * Creates the mapbender configuration.
     * 
     * 
     * @return array configuration parameters
     */
    public function getConfiguration();

    /**
     * Copies a source instance
     * @param EntityManager $em
     * @return InstanceLayerIn a copy of InstanceLayerIn
     */
    public function copy(EntityManager $em);
}
