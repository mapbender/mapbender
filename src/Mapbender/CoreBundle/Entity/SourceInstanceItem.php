<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Entity;

//use Doctrine\ORM\EntityManager;
//use Mapbender\CoreBundle\Entity\SourceInstance;
//use Mapbender\CoreBundle\Component\SourceItem;

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

    /**
     * Set SourceInstance
     * @param $sourceInstance the source
     * @return SourceInstanceIn
     */
    public abstract function setSourceInstance(SourceInstance $sourceInstance);

    /**
     * Get SourceInstance
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
