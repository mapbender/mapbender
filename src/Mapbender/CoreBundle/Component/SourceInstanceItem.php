<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Component;

use Doctrine\ORM\EntityManager;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 *
 * @author Paul Schmidt
 */
abstract class SourceInstanceItem
{
    
    /**
     *
     * @var SourceInstance a source instance
     */
    protected $sourceInstance;
    
    /**
     *
     * @var SourceInstance a source instance
     */
    protected $sourceItem;

//    /**
//     * Creates the mapbender configuration.
//     * 
//     * 
//     * @return array configuration parameters
//     */
//    public abstract function getConfiguration();

    /**
     * Copies a source instance
     * @param EntityManager $em
     * @return InstanceLayerIn a copy of InstanceLayerIn
     */
    public abstract function copy(EntityManager $em);

    /**
     * Set SourceInstance
     * @param $sourceInstance the source
     * @return SourceInstanceIn 
     */
    public abstract function setSourceInstance(SourceInstance $sourceInstance);

    /**
     * Get SourceInstance
     *
     * @return SourceInstance 
     */
    public abstract function getSourceInstance();

    /**
     * Get SourceItem
     *
     * @return SourceItem
     */
    public abstract function getSourceItem();

    /**
     * Set SourceInstance
     * @param SourceItem $sourceItem the source item
     * @return SourceInstanceItemIn 
     */
    public abstract function setSourceItem(SourceItem $sourceItem);
}
