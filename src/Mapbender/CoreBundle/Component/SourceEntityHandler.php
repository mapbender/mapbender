<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Source;

/**
 * Description of SourceEntityHandler
 *
 * @author Paul Schmidt
 */
abstract class SourceEntityHandler extends EntityHandler
{
    
    /**
     * Creates a SourceInstance
     */
    abstract public function create();
        
    /**
     * Creates a SourceInstance
     * @param Layerset|null $layerset layerset
     */
    abstract public function createInstance(Layerset $layerset = null);
    
    /**
     * Update a source from a new source
     * @param Source $source a Source object
     */
    abstract public function update(Source $source);

    /**
     * Returns a source from a database
     */
    abstract public function getInstances();
}
