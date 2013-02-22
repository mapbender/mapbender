<?php

namespace Mapbender\WmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Component\InstanceIn;

/**
 * WmsInstance class
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_wms_wmsinstance")
 * ORM\DiscriminatorMap({"mb_wms_wmssourceinstance" = "WmsSourceInstance"})
 */
class WmsInstance
        extends SourceInstance
        implements InstanceIn
{

    /**
     * @var array $configuration The instance configuration
     */
    protected $configuration;

    /**
     * @ORM\ManyToOne(targetEntity="WmsSource", inversedBy="wmsinstance", cascade={"refresh"})
     * @ORM\JoinColumn(name="wmssource", referencedColumnName="id")
     */
    protected $source;

    /**
     * @ORM\OneToMany(targetEntity="WmsInstanceLayer", mappedBy="wmsinstance", cascade={"refresh", "persist", "remove"})
     * @ORM\JoinColumn(name="layers", referencedColumnName="id")
     * @ORM\OrderBy({"priority" = "asc"})
     */
    protected $layers; //{ name: 1,   title: Webatlas,   visible: true }
//    /**
//     * @ORM\Column(type="string", nullable=false)
//     */
//    protected $title;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $srs;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $format;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $infoformat;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $exceptionformat = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $transparency = true;

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
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $tiled = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $info = true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $selected = true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $toggle = true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $allowinfo = true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $allowselected = true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $allowtoggle = true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $allowreorder = true;

    public function __construct()
    {
        $this->layers = new ArrayCollection();
        $this->opacity;
    }

    /**
     * Set id
     * @param integer $id
     * @return WmsInstance
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
     * Set configuration
     *
     * @param array $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * Get an Instance Configuration.
     * @return array
     */
    public function getConfiguration()
    {
        if($this->configuration !== null)
        { // from yaml
            return $this->configuration;
        }
        // from db
        $layers = array();
        $infoLayers = array();
        $rootlayer = null;
        foreach($this->layers as $layer)
        {
            if($layer->getWmslayersource()->getParent() === null)
            {
                $rootlayer = $layer;
                break;
            }
        }

        foreach($this->layers as $layer)
        {
            if($layer->getActive() === true
                    && $layer->getWmslayersource()->getParent() !== null
            )
            { //only active and not wms root layer
                $layers[] = $layer->getConfiguration();
                if($layer->getInfo() !== null && $layer->getInfo())
                {
                    $infoLayers[] = $layer->getTitle();
                }
            }
        }
        $configuration = array(
            "id" => $rootlayer->getId(),
            "title" => $rootlayer->getTitle() !== null
            && $rootlayer->getTitle() !== "" ?
                    $rootlayer->getTitle() : $this->title,
            "url" => $this->source->getGetMap()->getHttpGet(),
            "proxy" => $this->getProxy(),
            "visible" => $this->getVisible(),
            "format" => $this->getFormat(),
            "info_format" => $this->getInfoformat(),
            "queryFormat" => $this->infoformat,
            "transparent" => $this->transparency, //@TODO: This must be "transparent", not "transparency"
            "opacity" => $this->opacity / 100,
            "tiled" => $this->tiled,
            "layers" => array_reverse($layers),
            "queryLayers" => array_reverse($infoLayers),
            "layertree" => $this->getLayertreeConfiguration()
        );
        return $configuration;
    }

    public function getLayertreeConfiguration()
    {
        $layertree = array();
        foreach($this->layers as $layer)
        {
            if($layer->getActive() === true)
            {
                $layertree[$layer->getId()] = $layer->getLayertreeConfiguration();
            }
        }
        return $layertree;
    }

    /**
     * Set layers
     *
     * @param array $layers
     * @return WmsInstance
     */
    public function setLayers($layers)
    {
        $this->layers = $layers;

        return $this;
    }

    /**
     * Get layers
     *
     * @return array
     */
    public function getLayers()
    {
        return $this->layers;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return WmsInstance
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

    /**
     * Set srs
     *
     * @param array $srs
     * @return WmsInstance
     */
    public function setSrs($srs)
    {
        $this->srs = $srs;

        return $this;
    }

    /**
     * Get srs
     *
     * @return array
     */
    public function getSrs()
    {
        return $this->srs;
    }

    /**
     * Set format
     *
     * @param string $format
     * @return WmsInstance
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format !== null ? $this->format : 'image/png';
    }

    /**
     * Set infoformat
     *
     * @param string $infoformat
     * @return WmsInstance
     */
    public function setInfoformat($infoformat)
    {
        $this->infoformat = $infoformat;

        return $this;
    }

    /**
     * Get infoformat
     *
     * @return string
     */
    public function getInfoformat()
    {
        return $this->infoformat;
    }

    /**
     * Set exceptionformat
     *
     * @param string $exceptionformat
     * @return WmsInstance
     */
    public function setExceptionformat($exceptionformat)
    {
        $this->exceptionformat = $exceptionformat;

        return $this;
    }

    /**
     * Get exceptionformat
     *
     * @return string
     */
    public function getExceptionformat()
    {
        return $this->exceptionformat;
    }

    /**
     * Set transparency
     *
     * @param boolean $transparency
     * @return WmsInstance
     */
    public function setTransparency($transparency)
    {
        $this->transparency = $transparency;

        return $this;
    }

    /**
     * Get transparency
     *
     * @return boolean
     */
    public function getTransparency()
    {
        return $this->transparency;
    }

    /**
     * Set visible
     *
     * @param boolean $visible
     * @return WmsInstance
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
     * @return WmsInstance
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
     * @return WmsInstance
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
     * Set tiled
     *
     * @param boolean $tiled
     * @return WmsInstance
     */
    public function setTiled($tiled)
    {
        $this->tiled = $tiled;

        return $this;
    }

    /**
     * Get tiled
     *
     * @return boolean
     */
    public function getTiled()
    {
        return $this->tiled;
    }

    /**
     * Set wmssource
     *
     * @param WmsSource $wmssource
     * @return WmsInstance
     */
    public function setSource(WmsSource $wmssource = null)
    {
        $this->source = $wmssource;

        return $this;
    }

    /**
     * Get wmssource
     *
     * @return WmsSource
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Add layers
     *
     * @param WmsInstanceLayer $layers
     * @return WmsInstance
     */
    public function addLayer(WmsInstanceLayer $layer)
    {
        $this->layers->add($layer);

        return $this;
    }

    /**
     * Remove layers
     *
     * @param WmsInstanceLayer $layers
     */
    public function removeLayer(WmsInstanceLayer $layers)
    {
        $this->layers->removeElement($layers);
    }

    /**
     * Get info
     *
     * @return boolean $info
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Set info
     *
     * @param boolean $info
     */
    public function setInfo($info)
    {
        $this->info = $info;
        return $this;
    }

    /**
     * Get selected
     *
     * @return boolean $selected
     */
    public function getSelected()
    {
        return $this->selected;
    }

    /**
     * Set selected
     *
     * @param boolean $selected
     */
    public function setSelected($selected)
    {
        $this->selected = $selected;
        return $this;
    }

    /**
     * Get toggle
     *
     * @return boolean $toggle
     */
    public function getToggle()
    {
        return $this->toggle;
    }

    /**
     * Set toggle
     *
     * @param string $toggle
     */
    public function setToggle($toggle)
    {
        $this->toggle = $toggle;
        return $this;
    }

    /**
     * Get allowinfo
     *
     * @return boolean $allowinfo
     */
    public function getAllowinfo()
    {
        return $this->allowinfo;
    }

    /**
     * Set allowinfo
     *
     * @param boolean $allowinfo
     */
    public function setAllowinfo($allowinfo)
    {
        $this->allowinfo = $allowinfo;
        return $this;
    }

    /**
     * Get allowselected
     *
     * @return boolean $allowselected
     */
    public function getAllowselected()
    {
        return $this->allowselected;
    }

    /**
     * Set allowselected
     *
     * @param boolean $allowselected
     */
    public function setAllowselected($allowselected)
    {
        $this->allowselected = $allowselected;
        return $this;
    }

    /**
     * Get allowtoggle
     *
     * @return boolean $allowtoggle
     */
    public function getAllowtoggle()
    {
        return $this->allowtoggle;
    }

    /**
     * Set allowtoggle
     *
     * @param boolean $allowtoggle
     */
    public function setAllowtoggle($allowtoggle)
    {
        $this->allowtoggle = $allowtoggle;
        return $this;
    }

    /**
     * Get allowreorder
     *
     * @return boolean $allowreorder
     */
    public function getAllowreorder()
    {
        return $this->allowreorder;
    }

    /**
     * Set allowreorder
     *
     * @param boolean $allowreorder
     */
    public function setAllowreorder($allowreorder)
    {
        $this->allowreorder = $allowreorder;
        return $this;
    }

    public function __toString()
    {
        return (string) $this->getId();
    }

    public function getType()
    {
        return "wms";
    }

    public function getManagerType()
    {
        return "wms";
    }

    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderWmsBundle/Resources/public/mapbender.layer.wms.js'),
            'css' => array());
    }

}