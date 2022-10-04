<?php


namespace Mapbender\WmtsBundle\Component\Presenter;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\WmtsBundle\Component\TileMatrix;

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
}
