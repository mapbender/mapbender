<?php


namespace Mapbender\WmtsBundle\Component\Wmts;


use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;
use Mapbender\WmtsBundle\Entity\WmtsSource;

class SourceInstanceFactory implements \Mapbender\Component\SourceInstanceFactory
{
    public function createInstance(Source $source)
    {
        /** @var WmtsSource $source */
        $instance = new WmtsInstance();
        $instance->setSource($source);
        $instance->setTitle($source->getTitle());
        $instance->setRoottitle($source->getTitle());

        foreach ($source->getLayers() as $layer) {
            $instLayer = $this->createInstanceLayer($layer);
            $instLayer->setSourceInstance($instance);
            $instance->addLayer($instLayer);
        }
        return $instance;
    }

    protected function createInstanceLayer(WmtsLayerSource $sourceLayer)
    {
        $instanceLayer = new WmtsInstanceLayer();
        $instanceLayer->setSourceItem($sourceLayer);
        $instanceLayer->setTitle($sourceLayer->getTitle());
        $infoFormats = array_values(array_filter($sourceLayer->getInfoformats() ?: array()));
        if ($infoFormats) {
            $instanceLayer->setInfoformat($infoFormats[0]);
            $instanceLayer->setInfo(true);
            $instanceLayer->setAllowinfo(true);
        }
        $styles = $sourceLayer->getStyles();
        if ($styles && count($styles)) {
            $instanceLayer->setStyle($styles[0]->identifier);
        }
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
