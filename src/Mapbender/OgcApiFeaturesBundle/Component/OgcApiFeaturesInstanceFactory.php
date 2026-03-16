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
            /** @var OgcApiFeaturesLayerSource $layer */
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
        $instance->setTitle($data['title'] ?? $source->getTitle());
        $instance->setOpacity($data['opacity'] ?? 100);
        $instance->setAllowSelected($data['allowSelected'] ?? true);
        $instance->setSelected($data['selected'] ?? true);
        $instance->setAllowToggle($data['allowToggle'] ?? true);
        $instance->setToggle($data['toggle'] ?? true);
        $instance->setMinScale($data['minScale'] ?? null);
        $instance->setMaxScale($data['maxScale'] ?? null);
        $instance->setFeatureLimit($data['featureLimit'] ?? null);
        $featureInfoPropertyMap = json_encode($data['featureInfoPropertyMap']);
        $instance->setFeatureInfoPropertyMap($featureInfoPropertyMap);

        foreach ($data['layers'] as $layer) {
            if (empty($layer['collectionId'])) {
                continue;
            }
            $layerSource = new OgcApiFeaturesLayerSource();
            $layerSource->setSource($source);
            $layerSource->setTitle($layer['title'] ?? '');
            $layerSource->setCollectionId($layer['collectionId']);
            $instanceLayer = new OgcApiFeaturesInstanceLayer();
            $instanceLayer->setId($layer['collectionId']);
            $instanceLayer->setTitle($layer['title'] ?? '');
            $instanceLayer->setSourceInstance($instance);
            $instanceLayer->setSourceItem($layerSource);
            $instanceLayer->setActive($layer['active'] ?? true);
            $instanceLayer->setMinScale($layer['minScale'] ?? null);
            $instanceLayer->setMaxScale($layer['maxScale'] ?? null);
            $instanceLayer->setSelected($layer['selected'] ?? true);
            $instanceLayer->setAllowSelected($layer['allowSelected'] ?? true);
            $instanceLayer->setInfo($layer['info'] ?? false);
            $instanceLayer->setAllowInfo($layer['allowInfo'] ?? true);
            $instanceLayer->setFeatureLimit($data['featureLimit'] ?? null);
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
        $pendingPoolEntries = [];
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
                    $pendingPoolEntries[] = [$match, $layerIdentData];
                } else {
                    return false;
                }
            }
        }
        // All layers matched — now commit them to the entity pool
        foreach ($pendingPoolEntries as [$entity, $identData]) {
            $entityPool->add($entity, $identData);
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
