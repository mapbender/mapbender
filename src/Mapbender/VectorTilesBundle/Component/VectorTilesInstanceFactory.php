<?php

namespace Mapbender\VectorTilesBundle\Component;


use Mapbender\CoreBundle\Component\Source\SourceInstanceFactory;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\VectorTilesBundle\Entity\VectorTileInstance;
use Mapbender\VectorTilesBundle\Entity\VectorTileSource;

class VectorTilesInstanceFactory extends SourceInstanceFactory
{

    public function createInstance(Source $source, ?array $options = null): SourceInstance
    {
        /** @var VectorTileSource $source $instance */
        $instance = new VectorTileInstance();
        $instance->setSource($source);
        $instance->setTitle($source->getTitle());
        $instance->setWeight(0);
        return $instance;

    }

    public function fromConfig(array $data, string $id): SourceInstance
    {
        throw new \Exception("Not yet implemented");

    }

    public function matchInstanceToPersistedSource(SourceInstance $instance, array $extraSources): ?Source
    {
        throw new \Exception("Not yet implemented");

    }
}
