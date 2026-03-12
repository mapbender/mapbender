<?php

namespace Mapbender\OgcApiFeaturesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceItem;

#[ORM\Entity]
#[ORM\Table(name: 'mb_ogc_api_features_layersource')]
class OgcApiFeaturesLayerSource extends SourceItem
{
    #[ORM\ManyToOne(targetEntity: OgcApiFeaturesSource::class, inversedBy: 'layers')]
    #[ORM\JoinColumn(name: 'ogc_api_features_source', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $source;

    #[ORM\OneToMany(mappedBy: 'sourceItem', targetEntity: OgcApiFeaturesInstanceLayer::class, cascade: ['remove'])]
    protected Collection $instanceLayers;

    #[ORM\Column(name: 'bbox', type: 'json', nullable: true, options: ['jsonb' => true])]
    private ?array $bbox = null;

    #[ORM\Column(name: 'collection_id', type: 'string', nullable: false)]
    private string $collectionId;

    #[ORM\Column(name: 'properties', type: 'json', nullable: true)]
    private ?array $properties = null;

    public function setTitle(string $title = null): static
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCollectionId(): string
    {
        return $this->collectionId;
    }

    public function setCollectionId(string $collectionId): void
    {
        $this->collectionId = $collectionId;
    }

    public function getBbox(): ?array
    {
        return $this->bbox;
    }

    public function setBbox(?array $bbox): void
    {
        $this->bbox = $bbox;
    }

    public function getProperties(): ?array
    {
        return $this->properties;
    }

    public function setProperties(?array $properties): void
    {
        $this->properties = $properties;
    }

    public function setSource(OgcApiFeaturesSource|Source $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getSource(): ?OgcApiFeaturesSource
    {
        return $this->source;
    }
}
