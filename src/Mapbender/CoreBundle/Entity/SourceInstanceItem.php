<?php
namespace Mapbender\CoreBundle\Entity;

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
     * @var SourceItem
     */
    protected $sourceItem;

    /**
     * Set SourceInstance
     * @param SourceInstance $sourceInstance Source instance
     * @return SourceInstanceItem
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
     * @return SourceInstanceItem
     */
    public abstract function setSourceItem(SourceItem $sourceItem);
}
