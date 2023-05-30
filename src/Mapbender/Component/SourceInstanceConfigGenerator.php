<?php


namespace Mapbender\Component;


use Mapbender\CoreBundle\Component\Source\SourceInstanceInformationInterface;
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
     *
     * @param SourceInstance $sourceInstance
     * @return mixed[]
     */
    public function getConfiguration(SourceInstance $sourceInstance);

    /**
     * Returns references to JavaScript assets required for source
     * instances to work client-side.
     *
     * @param Application $application
     * @return string[]
     */
    public function getScriptAssets(Application $application);

    /**
     * Non-public legend url for tunneled instance
     *
     * @param SourceInstanceItem $instanceLayer
     * @return string|null
     */
    public function getInternalLegendUrl(SourceInstanceItem $instanceLayer);

    /**
     * @param SourceInstance $sourceInstance
     * @return bool
     */
    public function useTunnel(SourceInstance $sourceInstance);
}
