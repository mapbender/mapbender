<?php

namespace Mapbender\CoreBundle\Component\Presenter;

use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generator for frontend-facing configuration for SourceInstance entities.
 * Plugged into Application\ConfigService as the default generator.
 * Base class for atm the only shipping concrete implementation: @see WmsSourceService
 *
 */
abstract class SourceService
{
    /** @var ContainerInterface */
    protected $container;
    /** @var UrlProcessor */
    protected $urlProcessor;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->urlProcessor= $container->get('mapbender.source.url_processor.service');
    }

    /**
     * @param SourceInstance $sourceInstance
     * @return mixed[]
     */
    public function getConfiguration(SourceInstance $sourceInstance)
    {
        $innerConfig = $this->getInnerConfiguration($sourceInstance);
        $innerConfig = $this->postProcessInnerConfiguration($sourceInstance, $innerConfig);
        $wrappedConfig = array(
            'type'          => strtolower($sourceInstance->getType()),
            'title'         => $sourceInstance->getTitle(),
            'configuration' => $innerConfig,
            'id'            => strval($sourceInstance->getId()),
            'origId'        => strval($sourceInstance->getId()),
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
     * Validate the contents of the top-level "configuration" sub-key / aka "innerConfig"
     * @todo: do away with inner and outer configs, it's confusing and not beneficial
     *
     * @param mixed[] $configuration
     * @return boolean true if a configuration is valid otherwise false
     */
    public function validateInnerConfiguration($configuration)
    {
        $rootLayerContainer = ArrayUtil::getDefault($configuration, 'children', array(null));
        // TODO another tests for instance configuration
        /* check if root exists and has children */
        if (count($rootLayerContainer) !== 1 || !isset($rootLayerContainer[0])) {
            return false;
        } else {
            return $this->validateSubLayerConfiguration($rootLayerContainer[0]);
        }
    }

    /**
     * Validate generated layer configuration, recursively.
     *
     * @param mixed[] $configuration
     * @return bool
     */
    public function validateSubLayerConfiguration($configuration)
    {
        $childConfigs = ArrayUtil::getDefault($configuration, 'children', array());
        foreach ($childConfigs as $childConfig) {
            if (!$this->validateSubLayerConfiguration($childConfig)) {
                return false;
            }
        }
        return true;
    }

    /**
     * After generating a configuration array, this method can perform validation and adjustments.
     * Returns null on error, otherwise the (potentially modified) configuration.
     *
     * @param SourceInstance $sourceInstance
     * @param mixed[] $configuration
     * @return mixed[]|null
     */
    public function postProcessInnerConfiguration(SourceInstance $sourceInstance, $configuration)
    {
        if (!$this->validateInnerConfiguration($configuration)) {
            // @todo: Figure out why null. This is never checked. Won't this just cause errors elsewhere?
            return null;
        }
        $configuration['status'] = 'ok';    // for initial layertree visual; 'error' can only be produced client-side
        return $configuration;
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
     * Perform post-creation setup of a new SourceInstance entity.
     * The base implementation does nothing. Different types of source instances should perform necessary setup in an
     * override.
     *
     * @param SourceInstance $sourceInstance
     * @todo: This belongs in the repository layer. TBD if we can access the container / other services there.
     */
    public function initializeInstance(SourceInstance $sourceInstance)
    {
    }

    /**
     * Must return list of assets of given type required for source instances to work on the client.
     * @see TypeDirectoryService::getAssets()
     *
     * @param Application $application
     * @param string $type must be 'js' or 'trans'
     * @return string[]
     */
    abstract public function getAssets(Application $application, $type);
}
