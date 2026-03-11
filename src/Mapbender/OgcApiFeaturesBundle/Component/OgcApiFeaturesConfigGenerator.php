<?php

namespace Mapbender\OgcApiFeaturesBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\Source\SourceInstanceConfigGenerator;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\Style;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesInstance;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesInstanceLayer;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesSource;

class OgcApiFeaturesConfigGenerator extends SourceInstanceConfigGenerator
{
    public function __construct(
        protected EntityManagerInterface $em,
    ) {
    }

    public function getAssets(Application $application, string $type): array
    {
        return match ($type) {
            'js' => [
                '@MapbenderCoreBundle/Resources/public/mapbender.geosource.js',
                '@MapbenderOgcApiFeaturesBundle/Resources/public/vendor/olms.js',
                '@MapbenderOgcApiFeaturesBundle/Resources/public/geosource.ogc_api_features.source.js',
                '@MapbenderOgcApiFeaturesBundle/Resources/public/geosource.ogc_api_features.sourcelayer.js',
            ],
            default => [],
        };
    }

    public function getConfiguration(Application $application, SourceInstance $sourceInstance, ?string $idPrefix = null): array
    {
        /** @var OgcApiFeaturesInstance $sourceInstance */
        /** @var OgcApiFeaturesSource $source */
        $source = $sourceInstance->getSource();
        $config = parent::getConfiguration($application, $sourceInstance, $idPrefix);
        json_decode($sourceInstance->getFeatureInfoPropertyMap(), true);
        $featureInfoPropertyMapExists = $sourceInstance->getFeatureInfoPropertyMap() && json_last_error() === JSON_ERROR_NONE;
        $config['options'] = [
            'id' => $sourceInstance->getId(),
            'jsonUrl' => $source->getJsonUrl(),
            'title' => $sourceInstance->getTitle() ?: $source->getTitle(),
            'opacity' => ($sourceInstance->getOpacity() ?? 100) / 100.0,
            'minScale' => $sourceInstance->getMinScale(),
            'maxScale' => $sourceInstance->getMaxScale(),
            'metadataUrl' => $this->getMetaDataUrl($sourceInstance),
            'treeOptions' => [
                'selected' => $sourceInstance->getSelected(),
                'toggle' => $sourceInstance->getToggle(),
                'allow' => [
                    'selected' => $sourceInstance->getAllowSelected(),
                    'toggle' => $sourceInstance->getAllowToggle(),
                ],
            ],
            'featureInfo' => [
                'propertyMap' => $featureInfoPropertyMapExists ? json_decode($sourceInstance->getFeatureInfoPropertyMap(), true) : null,
            ],
        ];
        $config['state'] = [
            'info' => $this->featureInfoEnabled($sourceInstance),
        ];

        foreach (array_reverse($sourceInstance->getLayers()->toArray()) as $layer) {
            if ($layer->getActive()) {
                $childConfig = [
                    'options' => [
                        'id' => $layer->getId() . '_',
                        'priority' => $layer->getPriority(),
                        'title' => $layer->getTitle(),
                        'collectionId' => $layer->getSourceItem()->getCollectionId(),
                        'minScale' => (!empty($layer->getMinScale()) ? $layer->getMinScale() : $sourceInstance->getMinScale()),
                        'maxScale' => (!empty($layer->getMaxScale()) ? $layer->getMaxScale() : $sourceInstance->getMaxScale()),
                        'featureLimit' => (!empty($layer->getFeatureLimit()) ? $layer->getFeatureLimit() : $sourceInstance->getFeatureLimit()),
                        'metadataUrl' => $this->getMetaDataUrl($sourceInstance, $layer),
                        'bbox' => $layer->getSourceItem()->getBbox(),
                        'treeOptions' => [
                            'selected' => $layer->getSelected(),
                            'info' => $layer->getInfo(),
                            'allow' => [
                                'selected' => $layer->getAllowSelected(),
                                'info' => $layer->getAllowInfo(),
                            ],
                        ],
                    ],
                ];
                $availableStyles = $this->buildAvailableStyles($layer);
                if (!empty($availableStyles)) {
                    $childConfig['options']['style'] = $availableStyles[0]['name'];
                    $childConfig['options']['availableStyles'] = $availableStyles;
                }
                $config['children'][] = $childConfig;
            }
        }

        return $config;
    }

    protected function featureInfoEnabled($sourceInstance): bool
    {
        $featureInfoEnabled = false;
        foreach ($sourceInstance->getLayers() as $layer) {
            if ($layer->getInfo() === true) {
                $featureInfoEnabled = $layer->getInfo();
                break;
            }
        }
        return $featureInfoEnabled;
    }

    protected function buildAvailableStyles(OgcApiFeaturesInstanceLayer $layer): array
    {
        $styles = [];
        $styleIds = [];
        if ($layer->getStyleId()) {
            $styleIds[] = $layer->getStyleId();
        }
        foreach ($layer->getSecondaryStyleIds() as $secId) {
            $styleIds[] = (int) $secId;
        }
        foreach ($styleIds as $id) {
            $styleEntity = $this->em->find(Style::class, $id);
            if ($styleEntity && $styleEntity->getStyle()) {
                $decoded = json_decode($styleEntity->getStyle(), true);
                if (is_array($decoded)) {
                    $styles[] = [
                        'name' => (string) $styleEntity->getId(),
                        'title' => $styleEntity->getName(),
                        'style' => $decoded,
                    ];
                }
            }
        }
        return $styles;
    }

    protected function getMetaDataUrl($instance, $layer = null): ?string
    {
        $layerset = $instance->getLayerset();
        if ($layerset && $layerset->getApplication() && !$layerset->getApplication()->isDbBased()) {
            return null;
        }
        $layerId = $layer !== null ? $layer->getId() : 0;
        return '/application/metadata/' . $instance->getId() . '/' . $layerId . '/';
    }
}
