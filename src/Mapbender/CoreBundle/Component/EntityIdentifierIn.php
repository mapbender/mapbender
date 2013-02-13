<?php

namespace Mapbender\CoreBundle\Component;

/**
 * EntityIdentifierIn defines the functions for Source, SourceInstance classes.
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
interface EntityIdentifierIn
{

    /**
     * Get source type
     *
     * @return string 
     */
    public function getType();

    /**
     * Get manager type 
     *
     * @return string 
     */
    public function getManagertype();

    /**
     * Get full class name
     * 
     * @return string 
     */
    public function getClassname();
}
