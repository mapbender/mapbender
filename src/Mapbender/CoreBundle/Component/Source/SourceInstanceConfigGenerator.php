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
    /**
     * Determines whether the source instance is enabled in the frontend. Defaults to the `enabled` property
     * of the source instance, but can be overridden to provide additional logic.
     */
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
    abstract public function getScriptAssets(Application $application): array;

    /**
     * Non-public legend url for tunneled instance
     */
    public function getInternalLegendUrl(SourceInstanceItem $instanceLayer): ?string
    {
        return null;
    }

    /**
     * returns if this SourceInstance should be loaded using a proxy tunnel.
     */
    public function useTunnel(SourceInstance $sourceInstance): bool
    {
        return false;
    }
}
