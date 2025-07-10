<?php

namespace Mapbender\VectorTilesBundle\Component;

use Mapbender\CoreBundle\Component\Source\SourceInstanceConfigGenerator;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\VectorTilesBundle\Entity\VectorTileInstance;
use Mapbender\VectorTilesBundle\Entity\VectorTileSource;

class VectorTilesConfigGenerator extends SourceInstanceConfigGenerator
{

    public function getScriptAssets(Application $application): array
    {
        return [
            '@MapbenderCoreBundle/Resources/public/mapbender.geosource.js',
            '@MapbenderVectorTilesBundle/Resources/public/geosource.vectortiles.source.js',
            '@MapbenderVectorTilesBundle/Resources/public/geosource.vectortiles.sourcelayer.js',
        ];
    }

    public function getConfiguration(SourceInstance $sourceInstance): array
    {
        /** @var VectorTileInstance $sourceInstance */
        /** @var VectorTileSource $source */
        $source = $sourceInstance->getSource();

        $config = parent::getConfiguration($sourceInstance);
        $config['options'] = [
            'jsonUrl' => $source->getJsonUrl(),
            'minScale' => $sourceInstance->getMinScale(),
            'maxScale' => $sourceInstance->getMaxScale(),
            'title' => $sourceInstance->getTitle() ?: $source->getTitle(),
            'opacity' => ($sourceInstance->getOpacity() ?? 100) / 100.0,
            'treeOptions' => [
                "selected" => $sourceInstance->getSelected(),
                "allow" => [
                    "selected" => $sourceInstance->getAllowSelected()
                ],
            ],
            'id' => $source->getId(),
        ];
        return $config;
    }
}
