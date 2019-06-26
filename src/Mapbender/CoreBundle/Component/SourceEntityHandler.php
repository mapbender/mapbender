<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmsBundle\Component\Wms\Importer;

/**
 * @author Paul Schmidt
 * @property Source $entity
 */
class SourceEntityHandler extends EntityHandler
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
    final public function update(Source $source)
    {
        /** @var TypeDirectoryService $directory */
        $directory = $this->container->get('mapbender.source.typedirectory.service');
        /** @var Importer $loader */
        $loader = $directory->getSourceLoaderByType($this->entity->getType());
        $loader->updateSource($this->entity, $source);
    }
}
