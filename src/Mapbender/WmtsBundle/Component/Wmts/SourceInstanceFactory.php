<?php


namespace Mapbender\WmtsBundle\Component\Wmts;


use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmtsBundle\Component\TileMatrixSetLink;
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

        foreach ($source->getLayers() as $layer) {
            $instLayer = $this->createInstanceLayer($layer);
            $instLayer->setSourceInstance($instance);
            $instance->addLayer($instLayer);
        }
        // avoid persistence errors (non-nullable column)
        $instance->setWeight(0);
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
        $matrixLinks = array_values($sourceLayer->getTilematrixSetlinks() ?: array());
        if ($matrixLinks) {
            /** @var TileMatrixSetLink $defaultLink */
            $defaultLink = $matrixLinks[0];
            $instanceLayer->setTileMatrixSet($defaultLink->getTileMatrixSet());
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

    public function getFormTemplate(SourceInstance $instance)
    {
        return '@MapbenderWmts/Repository/instance.html.twig';
    }

    public function getFormType(SourceInstance $instance)
    {
        return 'Mapbender\WmtsBundle\Form\Type\WmtsInstanceInstanceLayersType';
    }
}
