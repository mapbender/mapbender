<?php

namespace Mapbender\XyzBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SupportsOpacity;

#[ORM\Entity]
#[ORM\Table(name: 'mb_xyz_instance')]
class XyzInstance extends SourceInstance implements SupportsOpacity
{

    #[ORM\ManyToOne(targetEntity: XyzSource::class, cascade: ['refresh'], inversedBy: 'instances')]
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

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $opacity = 100;

    #[ORM\Column(name: 'min_zoom', type: 'integer', nullable: true)]
    private ?int $minZoom = 0;

    #[ORM\Column(name: 'max_zoom', type: 'integer', nullable: true)]
    private ?int $maxZoom = 22;

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

    public function getMinZoom(): int
    {
        return $this->minZoom ?? 0;
    }

    public function setMinZoom(int $minZoom): void
    {
        $this->minZoom = $minZoom;
    }

    public function getMaxZoom(): int
    {
        return $this->maxZoom ?? 22;
    }

    public function setMaxZoom(int $maxZoom): void
    {
        $this->maxZoom = $maxZoom;
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
