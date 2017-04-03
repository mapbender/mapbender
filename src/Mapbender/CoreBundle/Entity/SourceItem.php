<?php
namespace Mapbender\CoreBundle\Entity;

/**
 *
 * @author Paul Schmidt
 */
abstract class SourceItem
{
    /**
     *
     * @var Source source
     */
    protected $source;

    /**
     * Set Source
     *
     * @param Source $source the source
     * @return Source
     */
    abstract public function setSource(Source $source);

    /**
     * Get Source
     *
     * @return Source
     */
    abstract public function getSource();
}
