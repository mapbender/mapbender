<?php

namespace Mapbender\VectorTilesBundle\Component;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\Source\SourceInstanceFactory;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\ManagerBundle\Component\Exchange\EntityPool;
use Mapbender\ManagerBundle\Component\Exchange\ImportState;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Mapbender\VectorTilesBundle\Entity\VectorTileInstance;
use Mapbender\VectorTilesBundle\Entity\VectorTileSource;
use Mapbender\VectorTilesBundle\Type\VectorTileInstanceType;

class VectorTilesInstanceFactory extends SourceInstanceFactory
{

    public function __construct(
        protected EntityManagerInterface $entityManager,
    )
    {
    }

    public function createInstance(Source $source, ?array $options = null): SourceInstance
    {
        /** @var VectorTileSource $source $instance */
        $instance = new VectorTileInstance();
        $instance->setSource($source);
        $instance->setTitle($source->getTitle());
        $instance->setWeight(0);
        return $instance;
    }

    public function getFormType(SourceInstance $instance): string
    {
        return VectorTileInstanceType::class;
    }

    public function getFormTemplate(SourceInstance $instance): string
    {
        return '@MapbenderVectorTiles/edit-instance.html.twig';
    }

    public function fromConfig(array $data, string $id): SourceInstance
    {
        $source = $this->getSourceFromConfig($data, $id);

        $instance = new VectorTileInstance();
        $instance->setSource($source);
        $instance->setTitle($source->getTitle());
        $instance->setId($id);
        $instance->setSelected($data['selected'] ?? $data['visible'] ?? true);
        $instance->setAllowSelected($data['allowSelected'] ?? $data['allowSelect'] ?? true);
        $instance->setBasesource($data['basesource'] ?? $data['isBaseSource'] ?? false);
        $instance->setOpacity($data['opacity'] ?? 100);
        $instance->setMinScale($data['minScale'] ?? null);
        $instance->setMaxScale($data['maxScale'] ?? null);
        $instance->setFeatureInfo($data['featureInfo'] ?? true);
        $instance->setFeatureInfoAllowToggle($data['featureInfoAllowToggle'] ?? true);
        $instance->setPropertyMap($data['propertyMap'] ?? null);
        $instance->setHideIfNoTitle($data['hideIfNoTitle'] ?? true);
        $instance->setFeatureInfoTitle($data['featureInfoTitle'] ?? null);
        if (isset($data['bbox']) && is_array($data['bbox'])) {
            $source->setBbox($data['bbox']);
        }
        return $instance;
    }

    protected function getSourceFromConfig(array $data, string $id): VectorTileSource
    {
        $source = new VectorTileSource();
        $source->setJsonUrl($data['jsonUrl'] ?? throw new \InvalidArgumentException("Missing 'jsonUrl' in vector tile source config"));
        $source->setTitle($data['title'] ?? $id);
        $source->setId($id);
        return $source;
    }

    public function matchInstanceToPersistedSource(ImportState $importState, array $data, EntityPool $entityPool): bool
    {
        $repository = $this->entityManager->getRepository(VectorTileSource::class);
        $candidates = $repository->findBy(['jsonUrl' => $data['jsonUrl']]);
        if (count($candidates) === 0) return false;

        $classMeta = $this->entityManager->getClassMetadata(VectorTileSource::class);
        $entityPool->add($candidates[0], ImportHandler::extractArrayFields($data, $classMeta->getIdentifier()));
        return true;
    }
}
