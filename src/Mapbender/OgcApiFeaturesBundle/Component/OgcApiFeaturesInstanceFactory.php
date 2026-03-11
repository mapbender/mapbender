<?php

namespace Mapbender\OgcApiFeaturesBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\Source\SourceInstanceFactory;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\Style;
use Mapbender\ManagerBundle\Component\Exchange\EntityPool;
use Mapbender\ManagerBundle\Component\Exchange\ImportState;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Mapbender\ManagerBundle\Component\Exception\ImportException;
use Mapbender\ManagerBundle\Component\Exchange\EntityHelper;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesInstance;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesLayerSource;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesSource;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesInstanceLayer;
use Mapbender\OgcApiFeaturesBundle\Form\Type\OgcApiFeaturesInstanceType;

class OgcApiFeaturesInstanceFactory extends SourceInstanceFactory
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    )
    {
    }

    public function createInstance(Source $source, ?array $options = null): SourceInstance
    {
        /** @var OgcApiFeaturesSource $source $instance */
        $instance = new OgcApiFeaturesInstance();
        $instance->setSource($source);

        // Pre-load styles for this source for auto-assignment
        $styleMap = $this->getStyleMapForSource($source->getId());

        foreach ($source->getLayers() as $layer) {
            $instanceLayer = new OgcApiFeaturesInstanceLayer();
            $instanceLayer->setTitle($layer->getTitle());
            $instanceLayer->setSourceInstance($instance);
            $instanceLayer->setSourceItem($layer);
            $instanceLayer->setSelected(true);
            $instanceLayer->setAllowSelected(true);
            // Auto-assign matching style
            $collectionId = $layer->getCollectionId();
            if (isset($styleMap[$collectionId])) {
                $instanceLayer->setStyleId($styleMap[$collectionId]);
                $instanceLayer->setNativeStyleId($styleMap[$collectionId]);
            }
            $instance->addLayer($instanceLayer);
        };

        $instance->setTitle($source->getTitle());
        $instance->setSelected(true);
        $instance->setAllowSelected(true);
        $instance->setToggle(true);
        $instance->setAllowToggle(true);
        $instance->setWeight(0);
        return $instance;
    }

    /**
     * Returns a map of collectionId => styleId for styles belonging to the given source.
     */
    private function getStyleMapForSource(?int $sourceId): array
    {
        if (!$sourceId) {
            return [];
        }
        $styles = $this->entityManager->getRepository(Style::class)->findBy([
            'sourceId' => $sourceId,
        ]);
        $map = [];
        foreach ($styles as $style) {
            $cid = $style->getCollectionId();
            if ($cid && !isset($map[$cid])) {
                $map[$cid] = $style->getId();
            }
        }
        return $map;
    }

    public function fromConfig(array $data, string $id): SourceInstance
    {
        $source = $this->getSourceFromConfig($data, $id);
        $instance = new OgcApiFeaturesInstance();
        $instance->setSource($source);
        $instance->setTitle($data['title']);
        $instance->setOpacity($data['opacity']);
        $instance->setAllowSelected($data['allowSelected']);
        $instance->setSelected($data['selected']);
        $instance->setAllowToggle($data['allowToggle']);
        $instance->setToggle($data['toggle']);
        $instance->setMinScale($data['minScale']);
        $instance->setMaxScale($data['maxScale']);
        $instance->setFeatureLimit($data['featureLimit']);
        $featureInfoPropertyMap = json_encode($data['featureInfoPropertyMap']);
        $instance->setFeatureInfoPropertyMap($featureInfoPropertyMap);

        foreach ($data['layers'] as $layer) {
            $layerSource = new OgcApiFeaturesLayerSource();
            $layerSource->setSource($source);
            $layerSource->setTitle($layer['title']);
            $layerSource->setCollectionId($layer['collectionId']);
            $instanceLayer = new OgcApiFeaturesInstanceLayer();
            $instanceLayer->setId($layer['collectionId']);
            $instanceLayer->setTitle($layer['title']);
            $instanceLayer->setSourceInstance($instance);
            $instanceLayer->setSourceItem($layerSource);
            $instanceLayer->setActive($layer['active']);
            $instanceLayer->setMinScale($layer['minScale']);
            $instanceLayer->setMaxScale($layer['maxScale']);
            $instanceLayer->setSelected($layer['selected']);
            $instanceLayer->setAllowSelected($layer['allowSelected']);
            $instanceLayer->setInfo($layer['info']);
            $instanceLayer->setAllowInfo($layer['allowInfo']);
            $instanceLayer->setFeatureLimit($data['featureLimit']);
            $instanceLayer->setPriority(null);
            $instance->addLayer($instanceLayer);
        };

        return $instance;
    }

    protected function getSourceFromConfig(array $data, string $id): OgcApiFeaturesSource
    {
        $source = new OgcApiFeaturesSource();
        $source->setJsonUrl($data['jsonUrl'] ?? throw new \InvalidArgumentException("Missing 'jsonUrl' in vector tile source config"));
        $source->setTitle($data['title'] ?? $id);
        $source->setDescription($data['description'] ?? null);
        $source->setId($id);
        $source->setAttribution($data['attribution'] ?? null);
        $source->setVersion($data['version'] ?? null);
        return $source;
    }

    public function matchInstanceToPersistedSource(ImportState $importState, array $data, EntityPool $entityPool): bool
    {
        $repository = $this->entityManager->getRepository(OgcApiFeaturesSource::class);
        $candidates = $repository->findBy(['jsonUrl' => $data['jsonUrl']]);
        if (count($candidates) === 0) {
            return false;
        }
        $source = $candidates[0];
        // Match layers to persisted layers on the source
        if (!empty($data['layers'])) {
            foreach ($data['layers'] as $layerData) {
                $layerClass = ImportHandler::extractClassName($layerData);
                if (!$layerClass) {
                    throw new ImportException("Missing source item class definition");
                }
                if (!is_a($layerClass, OgcApiFeaturesLayerSource::class, true)) {
                    throw new ImportException("Unsupported layer type {$layerClass}");
                }
                $layerMeta = EntityHelper::getInstance($this->entityManager, $layerClass)->getClassMeta();
                $layerIdentData = ImportHandler::extractArrayFields($layerData, $layerMeta->getIdentifier());
                $layerData = $importState->getEntityData($layerClass, $layerIdentData) ?: $layerData;
                // Match by collectionId on the persisted source's layers
                $match = null;
                foreach ($source->getLayers() as $existingLayer) {
                    if ($existingLayer->getCollectionId() === ($layerData['collectionId'] ?? null)) {
                        $match = $existingLayer;
                        break;
                    }
                }
                if ($match) {
                    $entityPool->add($match, $layerIdentData);
                } else {
                    return false;
                }
            }
        }
        $classMeta = $this->entityManager->getClassMetadata(OgcApiFeaturesSource::class);
        $entityPool->add($source, ImportHandler::extractArrayFields($data, $classMeta->getIdentifier()));
        return true;
    }

    public function getFormTemplate(SourceInstance $instance): string
    {
        return '@MapbenderOgcApiFeatures/edit-instance.html.twig';
    }

    public function getFormType(SourceInstance $instance): string
    {
        return OgcApiFeaturesInstanceType::class;
    }
}
