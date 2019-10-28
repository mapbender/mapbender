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
     * @ORM\JoinColumn(name="wmtssource", referencedColumnName="id")
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


    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $roottitle;

    public function __construct()
    {
        $this->layers = new ArrayCollection();
        $this->dimensions = array();
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
     * Set layers
     *
     * @param array $layers
     * @return $this
     */
    public function setLayers($layers)
    {
        $this->layers = $layers;
        return $this;
    }

    /**
     * Get layers
     *
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
     * @return WmtsInstance
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
     * @return WmtsInstance
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
     * Remove layers
     *
     * @param WmtsInstanceLayer $layers
     */
    public function removeLayer(WmtsInstanceLayer $layers)
    {
        $this->layers->removeElement($layers);
    }

    public function getRoottitle()
    {
        return $this->roottitle;
    }

    public function setRoottitle($roottitle)
    {
        $this->roottitle = $roottitle;
        return $this;
    }

    /**
     * @return null
     */
    public function getMetadata()
    {
        return null;
    }
}
