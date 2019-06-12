<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * @author Paul Schmidt
 *
 * @property SourceInstance $entity
 */
abstract class SourceInstanceEntityHandler extends EntityHandler
{
    /**
     * @param array $configuration
     * @return SourceInstance
     * @internal param SourceInstance $instance
     */
    abstract public function setParameters(array $configuration = array());

    /**
     * Update instance parameters
     */
    abstract public function update();
}
