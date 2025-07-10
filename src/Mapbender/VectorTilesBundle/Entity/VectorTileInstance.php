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

    #[ORM\Column(name: 'min_zoom', type: 'integer', nullable: true)]
    private ?int $minZoom = null;

    #[ORM\Column(name: 'max_zoom', type: 'integer', nullable: true)]
    private ?int $maxZoom = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $selected = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $allowSelected = true;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $opacity = 100;


    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getMinZoom(): ?int
    {
        return $this->minZoom;
    }

    public function setMinZoom(?int $minZoom): void
    {
        $this->minZoom = $minZoom;
    }

    public function getMaxZoom(): ?int
    {
        return $this->maxZoom;
    }

    public function setMaxZoom(?int $maxZoom): void
    {
        $this->maxZoom = $maxZoom;
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

    public function getLayers()
    {
        return [];
    }

    public function getDisplayTitle(): string
    {
        return $this->getTitle() ?: $this->getSource()->getTitle();
    }
}
