<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_wmts_wmtsinstance")
 */
class WmtsInstance extends SourceInstance
{

    /**
     * @ORM\ManyToOne(targetEntity="WmtsSource", inversedBy="instances", cascade={"refresh"})
     * @ORM\JoinColumn(name="wmtssource", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $source;

    /**
     * @ORM\OneToMany(targetEntity="WmtsInstanceLayer", mappedBy="sourceInstance", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="layers", referencedColumnName="id")
     */
    protected $layers;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $opacity = 100;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $proxy = false;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $dimensions;


    public function __construct()
    {
        $this->layers = new ArrayCollection();
        $this->dimensions = array();
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
     * Returns dimensions
     *
     * @return array of DimensionIst
     */
    public function getDimensions()
    {
        return $this->dimensions ? : array();
    }

    /**
     * Sets dimensions
     *
     * @param array $dimensions array of DimensionIst
     * @return $this
     */
    public function setDimensions(array $dimensions)
    {
        $this->dimensions = $dimensions;
        return $this;
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

    /**
     * Set opacity
     *
     * @param integer $opacity
     * @return $this
     */
    public function setOpacity($opacity)
    {
        $this->opacity = $opacity;
        return $this;
    }

    /**
     * Get opacity
     *
     * @return integer
     */
    public function getOpacity()
    {
        return $this->opacity;
    }

    /**
     * Set proxy
     *
     * @param boolean $proxy
     * @return $this
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * Get proxy
     *
     * @return boolean
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Set wmtssource
     *
     * @param WmtsSource $wmtssource
     * @return $this
     */
    public function setSource($wmtssource = null)
    {
        $this->source = $wmtssource;
        return $this;
    }

    /**
     * Get wmtssource
     *
     * @return WmtsSource
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Add layers
     *
     * @param WmtsInstanceLayer $layer
     * @return $this
     */
    public function addLayer(WmtsInstanceLayer $layer)
    {
        $this->layers->add($layer);
        return $this;
    }

    /**
     * @param WmtsInstanceLayer $layer
     */
    public function removeLayer(WmtsInstanceLayer $layer)
    {
        $this->layers->removeElement($layer);
    }

    /**
     * @return null
     */
    public function getMetadata()
    {
        return null;
    }
}
