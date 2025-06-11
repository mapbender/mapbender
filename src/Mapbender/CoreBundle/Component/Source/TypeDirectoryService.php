<?php

namespace Mapbender\CoreBundle\Component\Source;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
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
class TypeDirectoryService
{

    /**
     * @var DataSource[]
     */
    protected array $sources = [];

    /**
     * @param DataSource[] $sources
     */
    public function __construct(array $sources)
    {
        foreach ($sources as $source) {
            $this->sources[strtolower($source->getName())] = $source;
        }
    }

    /**
     * Return a mapping of type codes => displayable type labels
     * @return string[]
     */
    public function getTypeLabels(bool $filterAllowAddFromManager = true): array
    {
        $labelMap = array();
        foreach ($this->sources as $source) {
            if ($filterAllowAddFromManager && !$source->allowAddSourceFromManager()) continue;
            $labelMap[strtolower($source->getName())] = $source->getLabel();
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
        return $this->getSource($sourceInstance->getType())->getConfigGenerator();
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
     * Returns list of assets of given type required for source instances to work on the client.
     *
     * @return string[]
     */
    public function getScriptAssets(Application $application): array
    {
        $refs = array();
        foreach ($this->sources as $source) {
            $typeRefs = $source->getConfigGenerator()->getScriptAssets($application);
            if ($typeRefs) {
                $refs = array_merge($refs, $typeRefs);
            }
        }
        return $refs;
    }

}
