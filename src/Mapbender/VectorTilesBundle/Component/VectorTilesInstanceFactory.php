<?php

namespace Mapbender\VectorTilesBundle\Component;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\Source\SourceInstanceFactory;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
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
        // TODO: Opacity
        return $instance;
    }

    public function matchInstanceToPersistedSource(SourceInstance $instance, array $extraSources): ?Source
    {
        /** @var VectorTileInstance $instance */
        $repository = $this->entityManager->getRepository(VectorTileSource::class);
        /** @var VectorTileSource $yamlSource */
        $yamlSource = $instance->getSource();

        $candidates = $repository->findBy(['jsonUrl' => $yamlSource->getJsonUrl()]);
        return count($candidates) > 0 ? $candidates[0] : null;
    }

    protected function getSourceFromConfig(array $data, string $id): VectorTileSource
    {
        $source = new VectorTileSource();
        $source->setJsonUrl($data['jsonUrl'] ?? throw new \InvalidArgumentException("Missing 'jsonUrl' in vector tile source config"));
        $source->setTitle($data['title'] ?? $id);
        $source->setId($id);
        return $source;
    }
}
