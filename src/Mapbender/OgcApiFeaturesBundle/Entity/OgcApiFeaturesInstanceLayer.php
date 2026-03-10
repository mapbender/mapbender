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

    public function setActive(bool $active): static
    {
        $this->active = (bool)$active;
        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
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

    public function setAllowInfo(?bool $allowInfo): static
    {
        $this->allowInfo = (bool) $allowInfo;
        return $this;
    }

    public function getAllowInfo(): ?bool
    {
        return $this->allowInfo;
    }

    public function setInfo(?bool $info): static
    {
        $this->info = (bool) $info;
        return $this;
    }

    public function getInfo(): ?bool
    {
        return $this->info;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority !== null ? intval($priority) : $priority;
        return $this;
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

    public function setStyleId(?int $styleId): static
    {
        $this->styleId = $styleId;
        return $this;
    }
}
