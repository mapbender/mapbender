<?php

namespace Mapbender\OgcApiFeaturesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;

#[ORM\Entity()]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'mb_ogc_api_features_instancelayer')]
class OgcApiFeaturesInstanceLayer extends SourceInstanceItem
{
    #[ORM\ManyToOne(targetEntity: OgcApiFeaturesInstance::class, cascade: ['refresh', 'persist'], inversedBy: 'layers')]
    #[ORM\JoinColumn(name: 'ogcapifeaturesinstance', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $sourceInstance;

    #[ORM\ManyToOne(targetEntity: OgcApiFeaturesLayerSource::class, cascade: ['refresh'], inversedBy: 'instanceLayers')]
    #[ORM\JoinColumn(name: 'ogcapifeatureslayersource', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $sourceItem;

    #[ORM\Column(type: 'float', nullable: true)]
    protected ?float $minScale;

    #[ORM\Column(type: 'float', nullable: true)]
    protected ?float $maxScale;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $featureLimit = 100;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $active = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $allowSelected = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $selected = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $allowInfo;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $info;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $priority;

    #[ORM\Column(name: 'style_id', type: 'integer', nullable: true)]
    protected ?int $styleId = null;

    #[ORM\Column(name: 'native_style_id', type: 'integer', nullable: true)]
    protected ?int $nativeStyleId = null;

    #[ORM\Column(name: 'secondary_style_ids', type: 'simple_array', nullable: true)]
    protected ?array $secondaryStyleIds = null;

    #[ORM\Column(name: 'tooltip_property_map', type: 'json', nullable: true)]
    protected ?array $tooltipPropertyMap = null;

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

    public function setActive(bool $active): void
    {
        $this->active = (bool)$active;
    }

    public function getActive(): bool
    {
        return $this->active;
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

    public function setAllowInfo(?bool $allowInfo): void
    {
        $this->allowInfo = (bool) $allowInfo;
    }

    public function getAllowInfo(): ?bool
    {
        return $this->allowInfo;
    }

    public function setInfo(?bool $info): void
    {
        $this->info = (bool) $info;
    }

    public function getInfo(): ?bool
    {
        return $this->info;
    }

    public function setPriority($priority): void
    {
        $this->priority = $priority !== null ? intval($priority) : $priority;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function __toString()
    {
        return (string)$this->getId();
    }

    public function getStyleId(): ?int
    {
        return $this->styleId;
    }

    public function setStyleId(?int $styleId): void
    {
        $this->styleId = $styleId;
    }

    public function getNativeStyleId(): ?int
    {
        return $this->nativeStyleId;
    }

    public function setNativeStyleId(?int $nativeStyleId): void
    {
        $this->nativeStyleId = $nativeStyleId;
    }

    public function getSecondaryStyleIds(): array
    {
        return $this->secondaryStyleIds ?? [];
    }

    public function setSecondaryStyleIds(?array $secondaryStyleIds): void
    {
        $this->secondaryStyleIds = $secondaryStyleIds ?: null;
    }

    public function getTooltipPropertyMap(): ?array
    {
        return $this->tooltipPropertyMap;
    }

    public function setTooltipPropertyMap(?array $tooltipPropertyMap): void
    {
        $this->tooltipPropertyMap = $tooltipPropertyMap;
    }
}
