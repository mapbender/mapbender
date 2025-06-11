<?php

namespace Mapbender\CoreBundle\Component\Source;

use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * Generator for frontend-facing configuration for SourceInstance entities.
 */
abstract class SourceService implements SourceInstanceConfigGenerator
{
    public function isInstanceEnabled(SourceInstance $sourceInstance): bool
    {
        return $sourceInstance->getEnabled();
    }

    public function getConfiguration(SourceInstance $sourceInstance): array
    {
        return [
            'id' => strval($sourceInstance->getId()),
            'type' => strtolower($sourceInstance->getType()),
            'title' => $sourceInstance->getTitle(),
            'isBaseSource' => $sourceInstance->isBasesource(),
        ];
    }

}
