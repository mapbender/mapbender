<?php

namespace Mapbender\CoreBundle\Component\Presenter;

use Mapbender\Component\SourceInstanceConfigGenerator;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * Generator for frontend-facing configuration for SourceInstance entities.
 * Plugged into Application\ConfigService as the default generator.
 * Base class for atm the only shipping concrete implementation: @see WmsSourceService
 *
 */
abstract class SourceService
    implements SourceInstanceConfigGenerator
{
    /** @var UrlProcessor */
    protected $urlProcessor;

    public function __construct(UrlProcessor $urlProcessor)
    {
        $this->urlProcessor = $urlProcessor;
    }

    public function isInstanceEnabled(SourceInstance $sourceInstance)
    {
        return $sourceInstance->getEnabled();
    }

    /**
     * @param SourceInstance $sourceInstance
     * @return mixed[]
     */
    public function getConfiguration(SourceInstance $sourceInstance)
    {
        $innerConfig = $this->getInnerConfiguration($sourceInstance);
        $wrappedConfig = array(
            'type'          => strtolower($sourceInstance->getType()),
            'title'         => $sourceInstance->getTitle(),
            'configuration' => $innerConfig,
            'id'            => strval($sourceInstance->getId()),
        );
        return $wrappedConfig;
    }

    /**
     * Generates the contents of the top-level "configuration" sub-key
     * @see getConfiguration
     * @todo: do away with inner and outer configs, it's confusing and not beneficial
     * @todo: this is now WmsInstance-specific, because only WmsInstance has a root layer
     *        Either SourceInstance must absorb the root layer concept, or this hierarchy must split
     *
     * @param SourceInstance $sourceInstance
     * @return mixed[]|null
     */
    public function getInnerConfiguration(SourceInstance $sourceInstance)
    {
        return array(
            'type' => strtolower($sourceInstance->getType()),
            'title' => $sourceInstance->getTitle(),
            'isBaseSource' => $sourceInstance->isBasesource(),
        );
    }
}
