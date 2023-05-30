<?php


namespace Mapbender\WmtsBundle\Component\Presenter;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\WmtsBundle\Component\TileMatrix;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;

class ConfigGeneratorTms extends ConfigGeneratorCommon
{

    public function getScriptAssets(Application $application)
    {
        return array(
            '@MapbenderCoreBundle/Resources/public/mapbender.geosource.js',
            '@MapbenderWmtsBundle/Resources/public/geosource-base.js',
            '@MapbenderWmtsBundle/Resources/public/mapbender.geosource.tms.js',
        );
    }

    protected function getLayerTreeOptions(SourceInstanceItem $instanceLayer, $isFakeRoot)
    {
        return array(
            'info' => false,
            'selected' => $instanceLayer->getSelected(),
            'toggle' => false,
            'allow' => array(
                'info' => false,
                'selected' => $instanceLayer->getAllowSelected(),
                'toggle' => false,
                'reorder' => null,
            ),
        );
    }

    protected function getLayerConfigs($sourceInstance)
    {
        // Deduplicate by title, merging matrix sets.
        // TMS XML structure cannot model multiple matrix sets / multiple CRSes
        // on the same layer. Instead, layers are repeated with different matrix sets.
        $titleMap = array();
        foreach ($sourceInstance->getLayers() as $layer) {
            if ($layer->getActive()) {
                $title = $layer->getSourceItem()->getTitle();
                if (\array_key_exists($title, $titleMap)) {
                    $this->mergeLayer($titleMap[$title], $layer);
                } else {
                    $titleMap[$title] = $layer;
                }
            }
        }
        $layerConfigs = array();
        foreach ($titleMap as $layer) {
            $layerConfigs[] = $this->formatInstanceLayer($layer, false);
        }
        return $layerConfigs;
    }

    public function getInternalLegendUrl(SourceInstanceItem $instanceLayer)
    {
        return null;
    }

    protected function getLayerLegendConfig(SourceInstanceItem $instanceLayer)
    {
        return array();
    }

    protected function formatTileMatrix(TileMatrix $tilematrix)
    {
        return parent::formatTileMatrix($tilematrix) + array(
            'href' => $tilematrix->getHref(),
        );
    }

    /**
     * @param WmtsInstanceLayer $instanceLayer
     * @return array
     */
    protected function formatInstanceLayerOptions(SourceInstanceItem $instanceLayer)
    {
        $options = parent::formatInstanceLayerOptions($instanceLayer);
        foreach ($instanceLayer->getSourceItem()->getTileResources() as $ru) {
            $options += \array_filter(array(
                'extension' => $ru->getExtension(),
            ));
        }
        return $options;
    }

    protected function mergeLayer(WmtsInstanceLayer $target, WmtsInstanceLayer $next)
    {
        foreach ($next->getSourceItem()->getTilematrixSetlinks() as $tmsl) {
            $target->getSourceItem()->addTilematrixSetlinks($tmsl);
        }
    }
}
