<?php

namespace Mapbender\VectorTilesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SupportsOpacity;

#[ORM\Entity]
#[ORM\Table(name: 'mb_vectortiles_instance')]
class VectorTileInstance extends SourceInstance implements SupportsOpacity
{

    #[ORM\ManyToOne(targetEntity: VectorTileSource::class, cascade: ['refresh'], inversedBy: 'instances')]
    #[ORM\JoinColumn(name: 'source', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $source;

    #[ORM\Column(name: 'min_scale', type: 'integer', nullable: true)]
    private ?int $minScale = null;

    #[ORM\Column(name: 'max_scale', type: 'integer', nullable: true)]
    private ?int $maxScale = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $selected = true;

    #[ORM\Column(name: 'allow_selected', type: 'boolean', nullable: true)]
    protected ?bool $allowSelected = true;

    #[ORM\Column(name: 'feature_info', type: 'boolean', nullable: true)]
    protected ?bool $featureInfo = true;

    #[ORM\Column(name: 'feature_info_allow_toggle', type: 'boolean', nullable: true)]
    protected ?bool $featureInfoAllowToggle = true;

    #[ORM\Column(name: 'feature_info_title', type: 'string', nullable: true)]
    protected ?string $featureInfoTitle = null;

    #[ORM\Column(name: 'hide_if_no_title', type: 'boolean', nullable: true)]
    protected ?bool $hideIfNoTitle = true;

    #[ORM\Column(name: 'property_map', type: 'text', nullable: true)]
    protected ?string $propertyMap = "";

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $opacity = 100;

    #[ORM\Column(name: 'print_scale_correction', type: 'float', nullable: true)]
    protected ?float $printScaleCorrection = 1.0;


    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getMinScale(): ?int
    {
        return $this->minScale;
    }

    public function setMinScale(?int $minScale): void
    {
        $this->minScale = $minScale;
    }

    public function getMaxScale(): ?int
    {
        return $this->maxScale;
    }

    public function setMaxScale(?int $maxScale): void
    {
        $this->maxScale = $maxScale;
    }

    public function getSelected(): ?bool
    {
        return $this->selected;
    }

    public function setSelected(?bool $selected): void
    {
        $this->selected = $selected;
    }

    public function getAllowSelected(): ?bool
    {
        return $this->allowSelected;
    }

    public function setAllowSelected(?bool $allowSelected): void
    {
        $this->allowSelected = $allowSelected;
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

    public function getFeatureInfo(): ?bool
    {
        return $this->featureInfo;
    }

    public function setFeatureInfo(?bool $featureInfo): void
    {
        $this->featureInfo = $featureInfo;
    }

    public function getFeatureInfoAllowToggle(): ?bool
    {
        return $this->featureInfoAllowToggle;
    }

    public function setFeatureInfoAllowToggle(?bool $featureInfoAllowToggle): void
    {
        $this->featureInfoAllowToggle = $featureInfoAllowToggle;
    }

    public function getFeatureInfoTitle(): ?string
    {
        return $this->featureInfoTitle;
    }

    public function setFeatureInfoTitle(?string $featureInfoTitle): void
    {
        $this->featureInfoTitle = $featureInfoTitle;
    }

    public function getHideIfNoTitle(): ?bool
    {
        return $this->hideIfNoTitle;
    }

    public function setHideIfNoTitle(?bool $hideIfNoTitle): void
    {
        $this->hideIfNoTitle = $hideIfNoTitle;
    }

    public function getPropertyMap(): ?string
    {
        return $this->propertyMap;
    }

    public function setPropertyMap(?string $propertyMap): void
    {
        $this->propertyMap = $propertyMap;
    }

    public function getPrintScaleCorrection(): ?float
    {
        return $this->printScaleCorrection;
    }

    public function setPrintScaleCorrection(?float $printScaleCorrection): void
    {
        $this->printScaleCorrection = $printScaleCorrection;
    }


    public function getLayers()
    {
        return [];
    }

    public function getDisplayTitle(): string
    {
        return $this->getTitle() ?: $this->getSource()->getTitle();
    }
}
