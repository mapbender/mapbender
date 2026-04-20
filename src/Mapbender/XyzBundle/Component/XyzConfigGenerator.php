<?php

namespace Mapbender\XyzBundle\Component;

use Mapbender\CoreBundle\Component\Source\SourceInstanceConfigGenerator;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\XyzBundle\Entity\XyzInstance;
use Mapbender\XyzBundle\Entity\XyzSource;

class XyzConfigGenerator extends SourceInstanceConfigGenerator
{

    public function getAssets(Application $application, string $type): array
    {
        return match ($type) {
            "js" => [
                '@MapbenderCoreBundle/Resources/public/mapbender.geosource.js',
                '@MapbenderXyzBundle/Resources/public/geosource.xyz.source.js',
                '@MapbenderXyzBundle/Resources/public/geosource.xyz.sourcelayer.js',
            ],
            default => [],
        };
    }

    public function getConfiguration(Application $application, SourceInstance $sourceInstance, ?string $idPrefix = null): array
    {
        /** @var XyzInstance $sourceInstance */
        /** @var XyzSource $source */
        $source = $sourceInstance->getSource();

        $config = parent::getConfiguration($application, $sourceInstance, $idPrefix);
        $config['options'] = [
            'url' => $source->getUrlTemplate(),
            'minZoom' => $sourceInstance->getMinZoom(),
            'maxZoom' => $sourceInstance->getMaxZoom(),
            'attribution' => $source->getAttribution(),
            'minScale' => $sourceInstance->getMinScale(),
            'maxScale' => $sourceInstance->getMaxScale(),
            'title' => $sourceInstance->getTitle() ?: $source->getTitle(),
            'opacity' => ($sourceInstance->getOpacity() ?? 100) / 100.0,
            'treeOptions' => [
                "selected" => $sourceInstance->getSelected(),
                "allow" => [
                    "selected" => $sourceInstance->getAllowSelected(),
                ],
            ],
            'id' => $sourceInstance->getId(),
        ];
        return $config;
    }
}
