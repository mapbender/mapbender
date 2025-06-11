<?php

namespace Mapbender\CoreBundle\Component\Source;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\PrintBundle\Component\LayerRenderer;

/**
 * Directory for map data source types
 * Each source type is
 * expected to supply its own service that performs source-type-specific tasks such as
 * * generating frontend configuration
 * * locating the correct form type for administration
 *
 * The directory itself is registered in container at mapbender.source.typedirectory.service
 *
 * Custom sources should extend from Mapbender.DataSource and tag the class as `mapbender.datasource`
 **/
class TypeDirectoryService implements SourceInstanceFactory, SourceInstanceInformationInterface
{

    /**
     * @var DataSource[]
     */
    protected array $sources = [];

    /**
     * @param DataSource[] $sources
     * @return void
     */
    public function __construct(array $sources)
    {
        foreach ($sources as $source) {
            $this->sources[$source->getName()] = $source;
        }
    }

    /**
     * Return a mapping of type codes => displayable type labels
     * @return string[]
     */
    public function getTypeLabels(): array
    {
        $labelMap = array();
        foreach ($this->sources as $source) {
            $loader = $source->getLoader();
            $labelMap[$loader->getTypeCode()] = $loader->getTypeLabel();
        }
        return $labelMap;
    }

    public function getSource(string $type): DataSource
    {
        $key = strtolower($type);
        if (!array_key_exists($key, $this->sources)) {
            throw new \RuntimeException('No data source available for key ' . $key);
        }
        return $this->sources[$key];
    }

    public function getConfigGenerator(SourceInstance $sourceInstance): SourceInstanceConfigGenerator
    {
        return $this->getSource($sourceInstance->getType())->getConfigService();
    }

    public function getInstanceFactory(Source $source): SourceInstanceFactory
    {
        return $this->getInstanceFactoryByType($source->getType());
    }

    public function getInstanceFactoryByType(string $type): SourceInstanceFactory
    {
        return $this->getSource($type)->getInstanceFactory();
    }

    public function getLayerRenderer(string $type): ?LayerRenderer
    {
        return $this->getSource($type)->getLayerRenderer();
    }

    public function getSourceLoaderByType($type): SourceLoader
    {
        return $this->getSource($type)->getLoader();
    }

    /**
     * @param Source $source
     * @return SourceInstance
     */
    public function createInstance(Source $source)
    {
        return $this->getInstanceFactory($source)->createInstance($source);
    }

    public function fromConfig(array $data, $id)
    {
        if (empty($data['type'])) {
            throw new \RuntimeException("Missing mandatory value 'type' in given data");
        }
        return $this->getInstanceFactoryByType($data['type'])->fromConfig($data, $id);
    }

    public function matchInstanceToPersistedSource(SourceInstance $instance, array $extraSources)
    {
        $implementation = $this->getInstanceFactoryByType($instance->getSource()->getType());
        return $implementation->matchInstanceToPersistedSource($instance, $extraSources);
    }

    /**
     * Returns list of assets of given type required for source instances to work on the client.
     *
     * @return string[]
     */
    public function getScriptAssets(Application $application): array
    {
        $refs = array();
        foreach ($this->sources as $source) {
            $typeRefs = $source->getConfigService()->getScriptAssets($application);
            if ($typeRefs) {
                $refs = array_merge($refs, $typeRefs);
            }
        }
        return $refs;
    }

    public function getFormType(SourceInstance $instance)
    {
        return $this->getInstanceFactory($instance->getSource())->getFormType($instance);
    }

    public function getFormTemplate(SourceInstance $instance)
    {
        return $this->getInstanceFactory($instance->getSource())->getFormTemplate($instance);
    }

    public function isInstanceEnabled(SourceInstance $sourceInstance)
    {
        return $this->getConfigGenerator($sourceInstance)->isInstanceEnabled($sourceInstance);
    }

    public function canDeactivateLayer(SourceInstanceItem $layer)
    {
        return $this->getConfigGenerator($layer->getSourceInstance())->canDeactivateLayer($layer);
    }
}
