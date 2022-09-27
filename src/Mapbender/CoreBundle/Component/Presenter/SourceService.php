<?php

namespace Mapbender\CoreBundle\Component\Presenter;

use Mapbender\CoreBundle\Component\Source\SourceInstanceInformationInterface;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;

/**
 * Generator for frontend-facing configuration for SourceInstance entities.
 * Plugged into Application\ConfigService as the default generator.
 * Base class for atm the only shipping concrete implementation: @see WmsSourceService
 *
 */
abstract class SourceService implements SourceInstanceInformationInterface
{
    /** @var UrlProcessor */
    protected $urlProcessor;

    public function __construct(UrlProcessor $urlProcessor)
    {
        $this->urlProcessor = $urlProcessor;
    }

    /**
     * @return string
     */
    abstract public function getTypeLabel();

    /**
     * @return string
     */
    abstract public function getTypeCode();


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

    /**
     * Extend all URLs in the layer to run over owsproxy
     * @todo: this should and can be part of the initial generation
     *
     * @param mixed[] $layerConfig
     * @return mixed[]
     */
    protected function proxifyLayerUrls($layerConfig)
    {
        if (isset($layerConfig['children'])) {
            foreach ($layerConfig['children'] as $ix => $childConfig) {
                $layerConfig['children'][$ix] = $this->proxifyLayerUrls($childConfig);
            }
        }
        if (isset($layerConfig['options']['legend'])) {
            // might have keys 'graphic' and 'url', both kind of serve the same purpose
            $mangler = $this->urlProcessor;
            $fn = function($url) use ($mangler) {
                return $mangler->proxifyUrl($url);
            };
            $layerConfig['options']['legend'] = array_map($fn, $layerConfig['options']['legend']);
        }
        return $layerConfig;
    }

    /**
     * Must return list of assets of given type required for source instances to work on the client.
     * @see TypeDirectoryService::getAssets()
     *
     * @param Application $application
     * @param string $type must be 'js'
     * @return string[]
     */
    abstract public function getAssets(Application $application, $type);

    abstract public function getInternalLegendUrl(SourceInstanceItem $instanceLayer);

    /**
     * @param SourceInstance $sourceInstance
     * @return bool
     */
    abstract public function useTunnel(SourceInstance $sourceInstance);
}
