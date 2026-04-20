<?php

namespace Mapbender\XyzBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\Source\SourceInstanceFactory;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\ManagerBundle\Component\Exchange\EntityPool;
use Mapbender\ManagerBundle\Component\Exchange\ImportState;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Mapbender\XyzBundle\Entity\XyzInstance;
use Mapbender\XyzBundle\Entity\XyzSource;
use Mapbender\XyzBundle\Type\XyzInstanceType;

class XyzInstanceFactory extends SourceInstanceFactory
{

    public function __construct(
        protected EntityManagerInterface $entityManager,
    )
    {
    }

    public function createInstance(Source $source, ?array $options = null): SourceInstance
    {
        /** @var XyzSource $source */
        $instance = new XyzInstance();
        $instance->setSource($source);
        $instance->setTitle($source->getTitle());
        $instance->setWeight(0);
        return $instance;
    }

    public function getFormType(SourceInstance $instance): string
    {
        return XyzInstanceType::class;
    }

    public function getFormTemplate(SourceInstance $instance): string
    {
        return '@MapbenderXyz/edit-instance.html.twig';
    }

    public function fromConfig(array $data, string $id): SourceInstance
    {
        $source = $this->getSourceFromConfig($data, $id);

        $instance = new XyzInstance();
        $instance->setSource($source);
        $instance->setTitle($data['title'] ?? $source->getTitle());
        $instance->setId($id);
        $instance->setSelected($data['selected'] ?? $data['visible'] ?? true);
        $instance->setAllowSelected($data['allowSelected'] ?? $data['allowSelect'] ?? true);
        $instance->setBasesource($data['basesource'] ?? $data['isBaseSource'] ?? false);
        $instance->setOpacity($data['opacity'] ?? 100);
        $instance->setMinScale($data['minScale'] ?? null);
        $instance->setMaxScale($data['maxScale'] ?? null);
        $instance->setMinZoom($data['minZoom'] ?? 0);
        $instance->setMaxZoom($data['maxZoom'] ?? 22);
        return $instance;
    }

    protected function getSourceFromConfig(array $data, string $id): XyzSource
    {
        $source = new XyzSource();
        $source->setUrlTemplate($data['url'] ?? throw new \InvalidArgumentException("Missing 'url' in XYZ source config"));
        $source->setTitle($data['title'] ?? $id);
        $source->setId($id);
        $source->setAttribution($data['attribution'] ?? null);
        return $source;
    }

    public function matchInstanceToPersistedSource(ImportState $importState, array $data, EntityPool $entityPool): bool
    {
        $repository = $this->entityManager->getRepository(XyzSource::class);
        $candidates = $repository->findBy(['urlTemplate' => $data['url'] ?? '']);
        if (count($candidates) === 0) return false;

        $classMeta = $this->entityManager->getClassMetadata(XyzSource::class);
        $entityPool->add($candidates[0], ImportHandler::extractArrayFields($data, $classMeta->getIdentifier()));
        return true;
    }
}
