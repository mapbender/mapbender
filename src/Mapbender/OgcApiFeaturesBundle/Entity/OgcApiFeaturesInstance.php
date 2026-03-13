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

    public function setLayers($layers): void
    {
        $this->layers = $layers;
    }

    public function getLayers()
    {
        return $this->layers;
    }

    public function addLayer(OgcApiFeaturesInstanceLayer $layer): void
    {
        $this->layers->add($layer);
    }

    public function setOpacity(int $opacity): void
    {
        $this->opacity = $opacity;
    }

    public function getOpacity(): int
    {
        return $this->opacity ?? 100;
    }

    public function setMinScale(?float $value): void
    {
        $this->minScale = ($value === null || $value == INF) ? null : floatval($value);
    }

    public function getMinScale(): ?float
    {
        return $this->minScale;
    }

    public function setMaxScale(?float $value): void
    {
        $this->maxScale = ($value === null || $value == INF) ? null : floatval($value);
    }

    public function getMaxScale(): ?float
    {
        return $this->maxScale;
    }

    public function setFeatureLimit(int $featureLimit): void
    {
        $this->featureLimit = $featureLimit;
    }

    public function getFeatureLimit(): int
    {
        return $this->featureLimit ?? 100;
    }

    public function setAllowSelected(?bool $allowSelected): void
    {
        $this->allowSelected = (bool)$allowSelected;
    }

    public function getAllowSelected(): ?bool
    {
        return $this->allowSelected;
    }

    public function setSelected(?bool $selected): void
    {
        $this->selected = (bool)$selected;
    }

    public function getSelected(): ?bool
    {
        return $this->selected;
    }

    public function setAllowToggle(?bool $allowToggle): void
    {
        $this->allowToggle = (bool) $allowToggle;
    }

    public function getAllowToggle(): ?bool
    {
        return $this->allowToggle;
    }

    public function setToggle(?bool $toggle): void
    {
        $this->toggle = (bool) $toggle;
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
