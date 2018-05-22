<?php

namespace Mapbender\CoreBundle\Component\Presenter;

use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\Source\Tunnel\Endpoint;
use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
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
    /** @var Signer */
    protected $signer;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->signer = $container->get('signer');
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
            'isBaseSource' => $sourceInstance->isBaseSource(),
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
        // TODO another tests for instance configuration
        /* check if root exists and has children */
        if (count($configuration['children']) !== 1 || !isset($configuration['children'][0]['children'])) {
            return false;
        } else {
            foreach ($configuration['children'][0]['children'] as $childConfig) {
                if ($this->validateLayerConfiguration($childConfig)) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Validate generated layer configuration, recursively.
     *
     * @param mixed[] $configuration
     * @return bool
     */
    public function validateLayerConfiguration($configuration)
    {
        if (isset($configuration['children'])) { // > 2 simple layers -> OK.
            foreach ($configuration['children'] as $childConfig) {
                if ($this->validateLayerConfiguration($childConfig)) {
                    return true;
                }
            }
            return false;
        } else {
            return true;
        }
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
        $status = $sourceInstance->getSource()->getStatus();
        $configuration['status'] = $status && $status === Source::STATUS_UNREACHABLE ? 'error' : 'ok';
        return $configuration;
    }

    /**
     * Extend URLs in already generated configuration with an owsproxy-compatible signature
     * @todo: this should and can be part of the initial generation
     *
     * @param $layerConfig
     */
    protected function signLayerUrls($layerConfig)
    {
        if (isset($layerConfig['options']['legend'])) {
            if (isset($layerConfig['options']['legend']['graphic'])) {
                $layerConfig['options']['legend']['graphic'] = $this->signer->signUrl($layerConfig['options']['legend']['graphic']);
            } elseif (isset($layer['options']['legend']['url'])) {
                $layerConfig['options']['legend']['url'] = $this->signer->signUrl($layerConfig['options']['legend']['url']);
            }
        }
        if (isset($layerConfig['children'])) {
            foreach ($layerConfig['children'] as &$child) {
                $this->signLayerUrls($child);
            }
        }
    }

    /**
     * @param SourceInstance $sourceInstance
     * @return Endpoint
     */
    public function makeTunnelEndpoint(SourceInstance $sourceInstance)
    {
        /** @var InstanceTunnelService $tunnelService */
        $tunnelService = $this->container->get('mapbender.source.instancetunnel.service');
        return $tunnelService->makeEndpoint($sourceInstance);
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
}
