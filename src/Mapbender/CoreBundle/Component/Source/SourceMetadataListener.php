<?php

namespace Mapbender\CoreBundle\Component\Source;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Mapbender\CoreBundle\Entity\Source;

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
