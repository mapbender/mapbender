<?php

namespace Mapbender\VectorTilesBundle\Component;

use Mapbender\CoreBundle\Component\Source\SourceInstanceConfigGenerator;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\VectorTilesBundle\Entity\VectorTileInstance;
use Mapbender\VectorTilesBundle\Entity\VectorTileSource;

class VectorTilesConfigGenerator extends SourceInstanceConfigGenerator
{

    public function getAssets(Application $application, string $type): array
    {
        return match ($type) {
            "js" => [
                '@MapbenderCoreBundle/Resources/public/mapbender.geosource.js',
                '@MapbenderCoreBundle/Resources/public/element/featureinfo-highlighting.js',
                '@MapbenderVectorTilesBundle/Resources/public/geosource.vectortiles.source.js',
                '@MapbenderVectorTilesBundle/Resources/public/geosource.vectortiles.sourcelayer.js',
            ],
            "css" => [
                '@MapbenderVectorTilesBundle/Resources/public/vectortiles-featureinfo.scss',
            ],
            default => [],
        };
    }

    public function getConfiguration(Application $application, SourceInstance $sourceInstance): array
    {
        /** @var VectorTileInstance $sourceInstance */
        /** @var VectorTileSource $source */
        $source = $sourceInstance->getSource();

        $config = parent::getConfiguration($application, $sourceInstance);
        json_decode($sourceInstance->getFeatureInfoPropertyMap(), true);
        $hasFIPropertyMap = $sourceInstance->getFeatureInfoPropertyMap() && json_last_error() === JSON_ERROR_NONE;

        json_decode($sourceInstance->getLegendPropertyMap(), true);
        $hasLegendPropertyMap = $sourceInstance->getLegendPropertyMap() && json_last_error() === JSON_ERROR_NONE;

        $config['options'] = [
            'jsonUrl' => $source->getJsonUrl(),
            'minScale' => $sourceInstance->getMinScale(),
            'maxScale' => $sourceInstance->getMaxScale(),
            'title' => $sourceInstance->getTitle() ?: $source->getTitle(),
            'opacity' => ($sourceInstance->getOpacity() ?? 100) / 100.0,
            'treeOptions' => [
                "selected" => $sourceInstance->getSelected(),
                "info" => $sourceInstance->getFeatureInfo(),
                "allow" => [
                    "selected" => $sourceInstance->getAllowSelected(),
                    "info" => $sourceInstance->getFeatureInfoAllowToggle(),
                ],
            ],
            'id' => $sourceInstance->getId(),
            'bbox' => $source->getBoundsArray(),
            'featureInfo' => [
                'title' => $sourceInstance->getFeatureInfoTitle(),
                'propertyMap' => $hasFIPropertyMap ? json_decode($sourceInstance->getFeatureInfoPropertyMap(), true) : null,
                'hideIfNoTitle' => $sourceInstance->getHideIfNoTitle() ?? true,
            ],
            'legend' => [
                'enabled' => $sourceInstance->getLegendEnabled() ?? false,
                'propertyMap' => $hasLegendPropertyMap ? json_decode($sourceInstance->getLegendPropertyMap(), true) : null,
            ],
        ];
        return $config;
    }
}
