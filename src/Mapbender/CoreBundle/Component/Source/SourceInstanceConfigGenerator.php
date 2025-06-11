<?php


namespace Mapbender\CoreBundle\Component\Source;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;

/**
 * Generates configuration for source instances.
 */
interface SourceInstanceConfigGenerator extends SourceInstanceInformationInterface
{
    /**
     * Produces serializable frontend configuration.
     */
    public function getConfiguration(SourceInstance $sourceInstance): array;

    /**
     * Returns references to JavaScript assets required for source
     * instances to work client-side.
     *
     * @return string[]
     */
    public function getScriptAssets(Application $application): array;

    /**
     * Non-public legend url for tunneled instance
     */
    public function getInternalLegendUrl(SourceInstanceItem $instanceLayer): ?string;

    public function useTunnel(SourceInstance $sourceInstance): bool;
}
