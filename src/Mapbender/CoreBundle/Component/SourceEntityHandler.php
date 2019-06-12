<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * @author Paul Schmidt
 * @property Source $entity
 */
abstract class SourceEntityHandler extends EntityHandler
{
    /**
     * @return SourceInstance
     * @deprecated
     */
    final public function createInstance()
    {
        /** @var TypeDirectoryService $directory */
        $directory = $this->container->get('mapbender.source.typedirectory.service');
        return $directory->createInstance($this->entity);
    }

    /**
     * Update a source from a new source
     * @param Source $source a Source object
     */
    abstract public function update(Source $source);
}
