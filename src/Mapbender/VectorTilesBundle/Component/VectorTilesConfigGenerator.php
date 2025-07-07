<?php

namespace Mapbender\VectorTilesBundle\Component;

use Mapbender\CoreBundle\Component\Source\SourceInstanceConfigGenerator;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;

class VectorTilesConfigGenerator extends SourceInstanceConfigGenerator
{

    public function getScriptAssets(Application $application): array
    {
        return [];
    }
}
