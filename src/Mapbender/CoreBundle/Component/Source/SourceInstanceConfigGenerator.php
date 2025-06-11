<?php

namespace Mapbender\CoreBundle\Component\Source;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;

/**
 * Generator for frontend-facing configuration for SourceInstance entities.
 */
abstract class SourceInstanceConfigGenerator implements SourceInstanceInformationInterface
{
    public function isInstanceEnabled(SourceInstance $sourceInstance): bool
    {
        return $sourceInstance->getEnabled();
    }

    /**
     * Produces serializable frontend configuration.
     */
    public function getConfiguration(SourceInstance $sourceInstance): array
    {
        return [
            'id' => strval($sourceInstance->getId()),
            'type' => strtolower($sourceInstance->getType()),
            'title' => $sourceInstance->getTitle(),
            'isBaseSource' => $sourceInstance->isBasesource(),
        ];
    }

    /**
     * Returns references to JavaScript assets required for source
     * instances to work client-side.
     *
     * @return string[]
     */
    public abstract function getScriptAssets(Application $application): array;

    /**
     * Non-public legend url for tunneled instance
     */
    public function getInternalLegendUrl(SourceInstanceItem $instanceLayer): ?string
    {
        return null;
    }

    public function useTunnel(SourceInstance $sourceInstance): bool
    {
        return false;
    }

}
