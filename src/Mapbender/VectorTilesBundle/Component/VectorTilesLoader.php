<?php

namespace Mapbender\VectorTilesBundle\Component;


use Mapbender\CoreBundle\Component\Source\SourceLoader;
use Mapbender\CoreBundle\Element\Type\ScaleBarAdminType;
use Mapbender\CoreBundle\Entity\Source;

class VectorTilesLoader extends SourceLoader
{

    public function loadSource(mixed $formData): Source
    {
        throw new \Exception('Not implemented');
    }

    public function getFormType(): string
    {
        return ScaleBarAdminType::class;
    }
}
