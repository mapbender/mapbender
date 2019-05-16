<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * Description of SourceEntityHandler
 *
 * @author Paul Schmidt
 */
abstract class SourceEntityHandler extends EntityHandler
{
    /**
     * Creates a SourceInstance
     * @return SourceInstance
     */
    abstract public function createInstance();
    
    /**
     * Update a source from a new source
     * @param Source $source a Source object
     */
    abstract public function update(Source $source);

}
