<?php

namespace Mapbender\OgcApiFeaturesBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\SourceInstance;
use \Mapbender\CoreBundle\Entity\Source;

#[ORM\Entity]
#[ORM\Table(name: 'mb_ogc_api_features_instance')]
class OgcApiFeaturesInstance extends SourceInstance
{
    #[ORM\ManyToOne(targetEntity: OgcApiFeaturesSource::class, cascade: ['refresh'], inversedBy: 'instances')]
    #[ORM\JoinColumn(name: 'source', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Source $source;

    #[ORM\OneToMany(mappedBy: 'sourceInstance', targetEntity: OgcApiFeaturesInstanceLayer::class, cascade: ['persist', 'remove', 'refresh'])]
    #[ORM\JoinColumn(name: 'layers', referencedColumnName: 'id')]
    #[ORM\OrderBy(['priority' => 'asc', 'id' => 'asc'])]
    protected $layers;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $opacity = 100;

    #[ORM\Column(type: 'float', nullable: true)]
    protected ?float $minScale;

    #[ORM\Column(type: 'float', nullable: true)]
    protected ?float $maxScale;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $featureLimit = 100;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $allowSelected = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $selected = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $allowToggle;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $toggle;

    #[ORM\Column(name: 'feature_info_property_map', type: 'text', nullable: true)]
    protected ?string $featureInfoPropertyMap = "";

    public function __construct()
    {
        $this->layers = new ArrayCollection();
    }

    public function setSource($source): void
    {
        $this->source = $source;
    }

    public function getSource(): Source
    {
        return $this->source;
    }

    public function setLayers($layers): static
    {
        $this->layers = $layers;
        return $this;
    }

    public function getLayers()
    {
        return $this->layers;
    }

    public function addLayer(OgcApiFeaturesInstanceLayer $layer): static
    {
        $this->layers->add($layer);
        return $this;
    }

    public function setOpacity(int $opacity): self
    {
        $this->opacity = $opacity;
        return $this;
    }

    public function getOpacity(): int
    {
        return $this->opacity ?? 100;
    }

    public function setMinScale(?float $value): static
    {
        $this->minScale = ($value === null || $value == INF) ? null : floatval($value);
        return $this;
    }

    public function getMinScale(): ?float
    {
        return $this->minScale;
    }

    public function setMaxScale(?float $value): static
    {
        $this->maxScale = ($value === null || $value == INF) ? null : floatval($value);
        return $this;
    }

    public function getMaxScale(): ?float
    {
        return $this->maxScale;
    }

    public function setFeatureLimit(int $featureLimit): self
    {
        $this->featureLimit = $featureLimit;
        return $this;
    }

    public function getFeatureLimit(): int
    {
        return $this->featureLimit ?? 100;
    }

    public function setAllowSelected(?bool $allowSelected): static
    {
        $this->allowSelected = (bool)$allowSelected;
        return $this;
    }

    public function getAllowSelected(): ?bool
    {
        return $this->allowSelected;
    }

    public function setSelected(?bool $selected): static
    {
        $this->selected = (bool)$selected;
        return $this;
    }

    public function getSelected(): ?bool
    {
        return $this->selected;
    }

    public function setAllowToggle(?bool $allowToggle): static
    {
        $this->allowToggle = (bool) $allowToggle;
        return $this;
    }

    public function getAllowToggle(): ?bool
    {
        return $this->allowToggle;
    }

    public function setToggle(?bool $toggle): static
    {
        $this->toggle = (bool) $toggle;
        return $this;
    }

    public function getToggle(): ?bool
    {
        return $this->toggle;
    }

    public function getFeatureInfoPropertyMap(): ?string
    {
        return $this->featureInfoPropertyMap;
    }

    public function setFeatureInfoPropertyMap(?string $featureInfoPropertyMap): void
    {
        $this->featureInfoPropertyMap = $featureInfoPropertyMap;
    }

    public function getDisplayTitle(): string
    {
        return $this->getTitle() ?: $this->getSource()->getTitle();
    }
}
