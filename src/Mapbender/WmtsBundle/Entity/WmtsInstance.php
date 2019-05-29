<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * WmtsInstance class
 *
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_wmts_wmtsinstance")
 * ORM\DiscriminatorMap({"mb_wmts_wmtssourceinstance" = "WmtsSourceInstance"})
 */
class WmtsInstance extends SourceInstance
{

    /**
     * @ORM\ManyToOne(targetEntity="WmtsSource", inversedBy="instance", cascade={"refresh"})
     * @ORM\JoinColumn(name="wmtssource", referencedColumnName="id")
     */
    protected $source;

    /**
     * @ORM\OneToMany(targetEntity="WmtsInstanceLayer", mappedBy="sourceInstance", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="layers", referencedColumnName="id")
     */
    protected $layers; //{ name: 1,   title: Webatlas,   visible: true }
//    /**
//     * @ORM\Column(type="string", nullable=true)
//     */
//    protected $infoformat;
//
//    /**
//     * @ORM\Column(type="string", nullable=true)
//     */
//    protected $exceptionformat = null;
//
//    /**
//     * @ORM\Column(type="boolean", nullable=true)
//     */
//    protected $transparency = true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $visible = true;

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

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    protected $active = true;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    protected $allowselected = true;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    protected $selected = true;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    protected $info;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    protected $allowinfo;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    protected $toggle;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    protected $allowtoggle;

    public function __construct()
    {
        $this->layers = new ArrayCollection();
        $this->dimensions = array();
    }

    /**
     * Set id
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * Set title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

//    /**
//     * Set infoformat
//     *
//     * @param string $infoformat
//     * @return WmtsInstance
//     */
//    public function setInfoformat($infoformat)
//    {
//        $this->infoformat = $infoformat;
//        return $this;
//    }
//
//    /**
//     * Get infoformat
//     *
//     * @return string
//     */
//    public function getInfoformat()
//    {
//        return $this->infoformat;
//    }
//
//    /**
//     * Set exceptionformat
//     *
//     * @param string $exceptionformat
//     * @return WmtsInstance
//     */
//    public function setExceptionformat($exceptionformat)
//    {
//        $this->exceptionformat = $exceptionformat;
//        return $this;
//    }
//
//    /**
//     * Get exceptionformat
//     *
//     * @return string
//     */
//    public function getExceptionformat()
//    {
//        return $this->exceptionformat;
//    }
//
//    /**
//     * Set transparency
//     *
//     * @param boolean $transparency
//     * @return WmtsInstance
//     */
//    public function setTransparency($transparency)
//    {
//        $this->transparency = $transparency;
//        return $this;
//    }
//
//    /**
//     * Get transparency
//     *
//     * @return boolean
//     */
//    public function getTransparency()
//    {
//        return $this->transparency;
//    }

    /**
     * Set visible
     *
     * @param boolean $visible
     * @return WmtsInstance
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * Get visible
     *
     * @return boolean
     */
    public function getVisible()
    {
        return $this->visible;
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


    public function getActive()
    {
        return $this->active;
    }

    public function getAllowselected()
    {
        return $this->allowselected;
    }

    public function getSelected()
    {
        return $this->selected;
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function getAllowinfo()
    {
        return $this->allowinfo;
    }

    public function getToggle()
    {
        return $this->toggle;
    }

    public function getAllowtoggle()
    {
        return $this->allowtoggle;
    }

    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    public function setAllowselected($allowselected)
    {
        $this->allowselected = $allowselected;
        return $this;
    }

    public function setSelected($selected)
    {
        $this->selected = $selected;
        return $this;
    }

    public function setInfo($info)
    {
        $this->info = $info;
        return $this;
    }

    public function setAllowinfo($allowinfo)
    {
        $this->allowinfo = $allowinfo;
        return $this;
    }

    public function setToggle($toggle)
    {
        $this->toggle = $toggle;
        return $this;
    }

    public function setAllowtoggle($allowtoggle)
    {
        $this->allowtoggle = $allowtoggle;
        return $this;
    }

    public function __toString()
    {
        return (string) $this->getId();
    }

    /**
     * @return null
     */
    public function getMetadata()
    {
        return null;
    }
}
