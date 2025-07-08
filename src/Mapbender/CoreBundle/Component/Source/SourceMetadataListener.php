<?php

namespace Mapbender\CoreBundle\Component\Source;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Mapbender\CoreBundle\Entity\Source;

/**
 * A MappedSuperclass in doctrine needs to know all possible discriminator values to work performantly.
 * However, since data sources can be added dynamically, we cannot hardcode the discriminator map in the Source class.
 * This metadata listener dynamically adds all known source types to the discriminator map of the Source class.
 * @see Source
 * @see https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/inheritance-mapping.html#entity-inheritance
 */
class SourceMetadataListener
{

    public function __construct(private TypeDirectoryService $typeDirectoryService)
    {
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        $class = $args->getClassMetadata();
        // Dynamically add new subclasses to the discriminator map of the Node class.
        if ($class->name === Source::class) {
            foreach ($this->typeDirectoryService->getSources() as $source) {
                $entityClass = $source->getSourceEntityClass();
                if (!$entityClass) continue;
                $class->discriminatorMap[$source->getEntityTypeDiscriminator()] = $entityClass;
                if (!in_array($entityClass, $class->subClasses)) {
                    $class->subClasses[] = $entityClass;
                }
            }
        }
    }

}
