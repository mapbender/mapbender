<?php

namespace Mapbender\WmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\InstanceLayerIn;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\CoreBundle\Component\Utils;

/**
 * WmsInstanceLayer class
 *
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_wms_wmsinstancelayer")
 */
class WmsInstanceLayer implements InstanceLayerIn
{

    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="WmsInstance", inversedBy="layers", cascade={"refresh"})
     * @ORM\JoinColumn(name="wmsinstance", referencedColumnName="id")
     */
    protected $wmsinstance;

    /**
     * @ORM\ManyToOne(targetEntity="WmsLayerSource", inversedBy="id", cascade={"refresh"})
     * @ORM\JoinColumn(name="wmslayersource", referencedColumnName="id")
     */
    protected $wmslayersource;

    /**
     * @ORM\ManyToOne(targetEntity="WmsInstanceLayer",inversedBy="sublayer")
     * @ORM\JoinColumn(name="parent", referencedColumnName="id", nullable=true)
     */
    protected $parent = null;

    /**
     * @ORM\OneToMany(targetEntity="WmsInstanceLayer",mappedBy="parent", cascade={"remove"})
     * @ORM\OrderBy({"priority" = "asc"})
     */
    protected $sublayer;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $title;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $active = true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $allowselected = true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $selected = true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $info;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $allowinfo;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $toggle;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $allowtoggle;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $allowreorder = true;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected $minScale;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected $maxScale;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $style = "";

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $priority;

    public function __construct()
    {
        $this->sublayer = new ArrayCollection();
        $this->style = "";
    }
    
    /**
     * Set id
     * @param integer $id
     * @return WmsInstanceLayer
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
     * Set title
     *
     * @param string $title
     * @return WmsInstanceLayer
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
     * Set sublayer as array of string
     *
     * @param array $sublayer
     * @return WmsInstanceLayer
     */
    public function setSublayer($sublayer)
    {
        $this->sublayer = $sublayer;

        return $this;
    }

    /**
     * Set sublayer as array of string
     *
     * @param WmsInstanceLayer $sublayer
     * @return WmsInstanceLayer
     */
    public function addSublayer(WmsInstanceLayer $sublayer)
    {
        $this->sublayer->add($sublayer);

        return $this;
    }

    /**
     * Get sublayer
     *
     * @return array 
     */
    public function getSublayer()
    {
        return $this->sublayer;
    }

    /**
     * Set parent
     *
     * @param WmsInstanceLayer $parent
     * @return WmsInstanceLayer
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return WmsInstanceLayer 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return WmsInstanceLayer
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set allowselected
     *
     * @param boolean $allowselected
     * @return WmsInstanceLayer
     */
    public function setAllowselected($allowselected)
    {
        $this->allowselected = $allowselected;

        return $this;
    }

    /**
     * Get allowselected
     *
     * @return boolean 
     */
    public function getAllowselected()
    {
        return $this->allowselected;
    }

    /**
     * Set selected
     *
     * @param boolean $selected
     * @return WmsInstanceLayer
     */
    public function setSelected($selected)
    {
        $this->selected = $selected;

        return $this;
    }

    /**
     * Get selected
     *
     * @return boolean 
     */
    public function getSelected()
    {
        return $this->selected;
    }

    /**
     * Set info
     *
     * @param boolean $info
     * @return WmsInstanceLayer
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get info
     *
     * @return boolean 
     */
    public function getInfo()
    {
        return $this->info;
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
     * Set allowinfo
     *
     * @param boolean $allowinfo
     * @return WmsInstanceLayer
     */
    public function setAllowinfo($allowinfo)
    {
        $this->allowinfo = $allowinfo;

        return $this;
    }

    /**
     * Get allowinfo
     *
     * @return boolean 
     */
    public function getAllowinfo()
    {
        return $this->allowinfo;
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

    /**
     * Set minScale
     *
     * @param float $minScale
     * @return WmsInstanceLayer
     */
    public function setMinScale($minScale)
    {
        $this->minScale = $minScale;

        return $this;
    }

    /**
     * Get minScale
     *
     * @return float 
     */
    public function getMinScale()
    {
        return $this->minScale;
    }

    /**
     * Set maxScale
     *
     * @param float $maxScale
     * @return WmsInstanceLayer
     */
    public function setMaxScale($maxScale)
    {
        $this->maxScale = $maxScale;

        return $this;
    }

    /**
     * Get maxScale
     *
     * @return float 
     */
    public function getMaxScale()
    {
        return $this->maxScale;
    }

    /**
     * Set style
     *
     * @param string $style
     * @return WmsInstanceLayer
     */
    public function setStyle($style)
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Get style
     *
     * @return string 
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     * @return WmsInstanceLayer
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get priority
     *
     * @return integer 
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set wmsinstance
     *
     * @param WmsInstance $wmsinstance
     * @return WmsInstanceLayer
     */
    public function setWmsinstance(WmsInstance $wmsinstance = null)
    {
        $this->wmsinstance = $wmsinstance;

        return $this;
    }

    /**
     * Get wmsinstance
     *
     * @return WmsInstance 
     */
    public function getWmsinstance()
    {
        return $this->wmsinstance;
    }

    /**
     * Set wmslayersource
     *
     * @param WmsLayerSource $wmslayersource
     * @return WmsInstanceLayer
     */
    public function setWmslayersource(WmsLayerSource $wmslayersource = null)
    {
        $this->wmslayersource = $wmslayersource;

        return $this;
    }

    /**
     * Get wmslayersource
     *
     * @return WmsLayerSource 
     */
    public function getWmslayersource()
    {
        return $this->wmslayersource;
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return (string) $this->getId();
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = array(
            "id" => $this->id,
            "name" => $this->wmslayersource->getName() !== null ? $this->wmslayersource->getName() : "",
            "title" => $this->title,
            "queryable" => $this->getInfo(),
            "style" => $this->style,
            "minScale" => $this->minScale !== null ? floatval($this->minScale) : null,
            "maxScale" => $this->maxScale !== null ? floatval($this->maxScale) : null
        );

        if(count($this->wmslayersource->getStyles()) > 0)
        {
            $styles = $this->wmslayersource->getStyles();
            $legendurl = $styles[0]->getLegendUrl(); // first style object
            if($legendurl !== null){
            $configuration["legend"] = array(
                "url" => $legendurl->getOnlineResource()->getHref(),
                "width" => intval($legendurl->getWidth()),
                "height" => intval($legendurl->getHeight()));
            }
        } else if($this->wmsinstance->getSource()->getGetLegendGraphic() !== null)
        {
            $legend = $this->wmsinstance->getSource()->getGetLegendGraphic();
            $url = $legend->getHttpGet();
            $formats = $legend->getFormats();
            $params = "service=WMS&request=GetLegendGraphic"
                    . "&version="
                    . $this->wmsinstance->getSource()->getVersion()
                    . "&layer=" . $this->wmslayersource->getName()
                    . (count($formats) > 0 ? "&format=" . $formats[0] : "")
                    . "&sld_version=1.1.0";
            $legendgraphic = Utils::getHttpUrl($url, $params);
            $configuration["legend"] = array(
                "graphic" => $legendgraphic);
        }
        $configuration["treeOptions"] = array(
            "info" => $this->info,
            "selected" => $this->selected,
            "toggle" => $this->toggle,
            "allow" => array(
                "info" => $this->allowinfo,
                "selected" => $this->allowselected,
                "toggle" => $this->allowtoggle,
                "reorder" => $this->allowreorder,
            )
        );
        return $configuration;
    }

}