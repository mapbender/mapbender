<?php

namespace Mapbender\OgcApiFeaturesBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\Source\SourceInstanceConfigGenerator;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\Style;
use Mapbender\ManagerBundle\Form\DataTransformer\YAMLDataTransformer;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesInstance;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesInstanceLayer;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesSource;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class OgcApiFeaturesConfigGenerator extends SourceInstanceConfigGenerator
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Environment $twig,
        protected RouterInterface $router,
    ) {
    }

    public function getAssets(Application $application, string $type): array
    {
        return match ($type) {
            'js' => [
                '@MapbenderCoreBundle/Resources/public/mapbender.geosource.js',
                '@MapbenderOgcApiFeaturesBundle/Resources/public/OgcApiFeatureStyleHelper.js',
                '@MapbenderOgcApiFeaturesBundle/Resources/public/OgcApiFeatureTooltipManager.js',
                '@MapbenderOgcApiFeaturesBundle/Resources/public/geosource.ogc_api_features.source.js',
                '@MapbenderOgcApiFeaturesBundle/Resources/public/geosource.ogc_api_features.sourcelayer.js',
            ],
            'css' => [
                '@MapbenderOgcApiFeaturesBundle/Resources/public/css/ogc-api-tooltip.css',
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
        ];
        $config['state'] = [
            'info' => $this->featureInfoEnabled($sourceInstance),
        ];

        foreach (array_reverse($sourceInstance->getLayers()->toArray()) as $layer) {
            /** @var $layer OgcApiFeaturesInstanceLayer */
            if ($layer->getActive()) {
                $childConfig = [
                    'options' => [
                        // add additional underscore to prevent confusion with rootlayer-ID:
                        // identical rootlayer- and child-ID result in messed up layer tree structure
                        'id' => $layer->getId() . '_',
                        'priority' => $layer->getPriority(),
                        'title' => $layer->getTitle(),
                        'collectionId' => $layer->getSourceItem()->getCollectionId(),
                        'minScale' => ($layer->getMinScale() ?? $sourceInstance->getMinScale()),
                        'maxScale' => ($layer->getMaxScale() ?? $sourceInstance->getMaxScale()),
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
                        'hoverStyle' => $layer->getHoverStyle(),
                    ],
                ];
                $availableStyles = $this->buildAvailableStyles($layer);
                if (!empty($availableStyles)) {
                    $childConfig['options']['style'] = $availableStyles[0]['name'];
                    $childConfig['options']['availableStyles'] = $availableStyles;
                }
                $tooltipTemplate = $this->getTooltipOrFiTemplate(
                    $layer,
                    $layer->getTooltipTemplate(),
                    $layer->getTooltipPropertyMap(),
                    '@MapbenderOgcApiFeatures/tooltip-propertymap.html.twig',
                );

                if ($tooltipTemplate) {
                    $childConfig['options']['tooltip'] = [
                        'template' => $tooltipTemplate,
                    ];
                }

                $fiTemplate = $this->getTooltipOrFiTemplate(
                    $layer,
                    $layer->getFeatureInfoTemplate(),
                    $layer->getFeatureInfoPropertyMap(),
                    '@MapbenderOgcApiFeatures/featureinfo-propertymap.html.twig',
                );

                if ($fiTemplate) {
                    $childConfig['options']['featureInfo'] = [
                        'template' => $fiTemplate,
                    ];
                }

                $propertyTitles = $layer->getSourceItem()->getPropertyTitles();
                if ($propertyTitles) {
                    $childConfig['options']['propertyTitles'] = $propertyTitles;
                }
                $config['children'][] = $childConfig;
            }
        }

        return $config;
    }

    protected function featureInfoEnabled($sourceInstance): bool
    {
        foreach ($sourceInstance->getLayers() as $layer) {
            if ($layer->getInfo() === true) {
                return true;
            }
        }
        return false;
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
        if (empty($styleIds)) {
            return $styles;
        }
        $styleEntities = $this->em->getRepository(Style::class)->findBy(['id' => $styleIds]);
        $entityMap = [];
        foreach ($styleEntities as $entity) {
            $entityMap[$entity->getId()] = $entity;
        }
        foreach ($styleIds as $id) {
            $styleEntity = $entityMap[$id] ?? null;
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


        return $this->router->generate('mapbender_core_application_metadata', [
            'slug' => $layerset->getApplication()->getSlug(),
            'instanceId' => $instance->getId(),
            'layerId' => $layerId,
        ]);
    }

    private function getTooltipOrFiTemplate(
        OgcApiFeaturesInstanceLayer $layer,
        ?string                     $template,
        ?array                      $propertyMap,
        string                      $propertyMapTwigTemplate,
    ): ?string
    {
        // if the template is valid yaml, treat it as a property map
        if ($template && str_starts_with($template, '-')) {
            $transformer = new YAMLDataTransformer();
            $json = $transformer->reverseTransform($template);
            if ($json) {
                $propertyMap = $json;
                $template = null;
            }
        }

        if ($template) return $template;
        if (!$propertyMap) return null;

        $properties = [];
        $titleMap = $layer->getSourceItem()->getPropertyTitles();

        foreach ($propertyMap as $name => $label) {
            // the YAML transformer creates a one-element associative array in a numeric array, unwrap
            if (is_iterable($label)) {
                $name = array_key_first($label);
                $label = $label[$name];
            }

            // in a numeric array, use property title map to resolve name, in an associative array, use the label from the property map
            if (is_int($name)) {
                $name = $label;
                $label = $titleMap[$name] ?? $name;
            }

            $properties[$name] = $label;
        }

        return $this->twig->render($propertyMapTwigTemplate, ['properties' => $properties, 'layer' => $layer]);
    }
}
