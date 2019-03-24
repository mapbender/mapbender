<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmtsBundle\Component\WmtsMetadata;

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
     * @var array $configuration The instance configuration
     * @ORM\Column(type="array", nullable=true)
     */
    protected $configuration;

    /**
     * @ORM\ManyToOne(targetEntity="WmtsSource", inversedBy="instance", cascade={"refresh"})
     * @ORM\JoinColumn(name="wmtssource", referencedColumnName="id")
     */
    protected $source;

    /**
     * @ORM\OneToMany(targetEntity="WmtsInstanceLayer", mappedBy="sourceInstance", cascade={"remove"})
     * @ORM\JoinColumn(name="layers", referencedColumnName="id")
     */
    protected $layers; //{ name: 1,   title: Webatlas,   visible: true }
//
//    /**
//     * @ORM\Column(type="string", nullable=true)
//     */
//    protected $srs;
//
//    /**
//     * @ORM\Column(type="string", nullable=true)
//     */
//    protected $format;
//
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
//
//    /**
//     * @ORM\Column(type="integer", options={"default" = 0})
//     */
//    protected $buffer = 0;
//
//    /**
//     * @ORM\Column(type="decimal", scale=2, options={"default" = 1.25})
//     */
//    protected $ratio = 1.25;

    public function __construct()
    {
        $this->layers = new ArrayCollection();
        $this->dimensions = array();
    }

    /**
     * Set id
     * @param integer $id
     * @return WmtsInstance
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
     * @return \Mapbender\WmtsBundle\Entity\WmtsInstance
     */
    public function setDimensions(array $dimensions)
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    /**
     * Set configuration
     *
     * @param array $configuration
     * @return $this
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * Get an Instance Configuration.
     *
     * @return array $configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Set layers
     *
     * @param array $layers
     * @return WmtsInstance
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
//
//    /**
//     * Get root layer
//     *
//     * @return WmtsInstanceLayer
//     */
//    public function getRootlayer()
//    {
//        foreach ($this->layers as $layer) {
//            if ($layer->getParent() === null) {
//                return $layer;
//            }
//        }
//        return null;
//    }

    /**
     * Set title
     *
     * @param string $title
     * @return WmtsInstance
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
//
//    /**
//     * Set srs
//     *
//     * @param array $srs
//     * @return WmtsInstance
//     */
//    public function setSrs($srs)
//    {
//        $this->srs = $srs;
//        return $this;
//    }
//
//    /**
//     * Get srs
//     *
//     * @return array
//     */
//    public function getSrs()
//    {
//        return $this->srs;
//    }
//
//    /**
//     * Set format
//     *
//     * @param string $format
//     * @return WmtsInstance
//     */
//    public function setFormat($format)
//    {
//        $this->format = $format;
//        return $this;
//    }
//
//    /**
//     * Get format
//     *
//     * @return string
//     */
//    public function getFormat()
//    {
//        return $this->format !== null ? $this->format : 'image/png';
//    }
//
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

//
//    /**
//     * Set ratio
//     *
//     * @param boolean $ratio
//     * @return WmtsInstance
//     */
//    public function setRatio($ratio)
//    {
//        $this->ratio = $ratio;
//
//        return $this;
//    }
//
//    /**
//     * Get ratio
//     *
//     * @return boolean
//     */
//    public function getRatio()
//    {
//        return $this->ratio;
//    }
//
//    /**
//     * Set buffer
//     *
//     * @param boolean $buffer
//     * @return WmtsInstance
//     */
//    public function setBuffer($buffer)
//    {
//        $this->buffer = $buffer;
//
//        return $this;
//    }
//
//    /**
//     * Get buffer
//     *
//     * @return boolean
//     */
//    public function getBuffer()
//    {
//        return $this->buffer;
//    }

    /**
     * Set wmtssource
     *
     * @param WmtsSource $wmtssource
     * @return WmtsInstance
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
     * @param WmtsInstanceLayer $layers
     * @return WmtsInstance
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
