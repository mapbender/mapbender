<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SupportsOpacity;
use Mapbender\CoreBundle\Entity\SupportsProxy;

/**
 * @author Paul Schmidt
 */
#[ORM\Entity]
#[ORM\Table(name: 'mb_wmts_wmtsinstance')]
class WmtsInstance extends SourceInstance implements SupportsOpacity
{

    #[ORM\ManyToOne(targetEntity: WmtsSource::class, cascade: ['refresh'], inversedBy: 'instances')]
    #[ORM\JoinColumn(name: 'wmtssource', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $source;

    /**
     * @var WmtsInstanceLayer[]|ArrayCollection
     */
    #[ORM\OneToMany(mappedBy: 'sourceInstance', targetEntity: WmtsInstanceLayer::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'layers', referencedColumnName: 'id')]
    #[ORM\OrderBy(['id' => 'asc'])]
    protected $layers;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected $opacity = 100;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected $proxy = false;


    public function __construct()
    {
        $this->layers = new ArrayCollection();
    }

    public function __clone()
    {
        if ($this->id) {
            $originalLayers = $this->getLayers()->getValues();
            $this->setId(null);
            $clonedLayers = array();
            foreach ($originalLayers as $layer) {
                /** @var WmtsInstanceLayer $layer */
                $layerClone = clone $layer;
                $layerClone->setSourceInstance($this);
                $clonedLayers[] = $layerClone;
            }
            $this->setLayers(new ArrayCollection($clonedLayers));
        }
    }

    /**
     * @param WmtsInstanceLayer[]|ArrayCollection $layers
     * @return $this
     */
    public function setLayers($layers)
    {
        $this->layers = $layers;
        return $this;
    }

    /**
     * @return WmtsInstanceLayer[]|ArrayCollection
     */
    public function getLayers()
    {
        return $this->layers;
    }

    public function setOpacity(int $opacity): self
    {
        if (is_numeric($opacity)) {
            $this->opacity = intval($opacity);
        }
        return $this;
    }

    public function getOpacity(): int
    {
        return $this->opacity;
    }

    public function setProxy(bool $proxy): self
    {
        $this->proxy = (bool) $proxy;
        return $this;
    }

    public function getProxy(): bool
    {
        return $this->proxy;
    }

    /**
     * @param WmtsSource $source
     * @return $this
     */
    public function setSource($source = null)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @return WmtsSource
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param WmtsInstanceLayer $layer
     */
    public function addLayer(WmtsInstanceLayer $layer)
    {
        $this->layers->add($layer);
        $layer->setSourceInstance($this);
    }

    /**
     * @param WmtsInstanceLayer $layer
     */
    public function removeLayer(WmtsInstanceLayer $layer)
    {
        $this->layers->removeElement($layer);
    }

    public function getDisplayTitle(): string
    {
        return $this->getTitle() ?: $this->getSource()->getTitle();
    }

    public function getRootlayer(): ?WmtsInstanceLayer
    {
        foreach ($this->layers as $layer) {
            if ($layer->getParent() === null) {
                return $layer;
            }
        }
        return null;
    }
}
