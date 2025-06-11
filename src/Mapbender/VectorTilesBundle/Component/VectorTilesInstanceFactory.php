<?php

namespace Mapbender\VectorTilesBundle\Component;


use Mapbender\CoreBundle\Component\Source\SourceInstanceFactory;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;

class VectorTilesInstanceFactory implements SourceInstanceFactory
{

    public function createInstance(Source $source): SourceInstance
    {
        throw new \Exception("Not yet implemented");

    }

    public function fromConfig(array $data, string $id): SourceInstance
    {
        throw new \Exception("Not yet implemented");

    }

    public function matchInstanceToPersistedSource(SourceInstance $instance, array $extraSources): ?Source
    {
        throw new \Exception("Not yet implemented");

    }

    public function getFormType(SourceInstance $instance): string
    {
        throw new \Exception("Not yet implemented");

    }

    public function getFormTemplate(SourceInstance $instance): string
    {
        throw new \Exception("Not yet implemented");

    }
}
