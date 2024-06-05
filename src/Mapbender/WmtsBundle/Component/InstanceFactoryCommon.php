<?php


namespace Mapbender\WmtsBundle\Component;


use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;
use Mapbender\WmtsBundle\Entity\WmtsSource;

abstract class InstanceFactoryCommon
    implements \Mapbender\Component\SourceInstanceFactory
{
    public function createInstance(Source $source)
    {
        /** @var WmtsSource $source */
        $instance = new WmtsInstance();
        $instance->setSource($source);
        $instance->setTitle($source->getTitle());

        $rootLayer = null;
        foreach ($source->getLayers() as $layer) {
            $instLayer = $this->createInstanceLayer($layer, $rootLayer);
            if ($layer->getParent() === null) $rootLayer = $instLayer;
            $instance->addLayer($instLayer);
        }
        // avoid persistence errors (non-nullable column)
        $instance->setWeight(0);
        return $instance;
    }

    public static function createInstanceLayer(WmtsLayerSource $sourceLayer, ?WmtsInstanceLayer $parent = null)
    {
        $instanceLayer = new WmtsInstanceLayer();
        $instanceLayer->setSourceItem($sourceLayer);
        $instanceLayer->setTitle($sourceLayer->getTitle());
        $instanceLayer->setPriority($sourceLayer->getPriority());
        $instanceLayer->setAllowtoggle($sourceLayer->getParent() === null);
        $instanceLayer->setToggle($sourceLayer->getParent() === null);
        $instanceLayer->setParent($parent);
        return $instanceLayer;
    }

    public function fromConfig(array $data, $id)
    {
        throw new \RuntimeException("Yaml-defined Wmts sources not implemented");
    }

    public function matchInstanceToPersistedSource(SourceInstance $instance, array $extraSources)
    {
        throw new \RuntimeException("Yaml-defined Wmts sources not implemented");
    }
}
