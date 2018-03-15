<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;

/**
 * WmtsInstanceLayer class
 *
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_wmts_wmtsinstancelayer")
 */
class WmtsInstanceLayer extends SourceInstanceItem
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="WmtsInstance", inversedBy="layers", cascade={"refresh"})
     * @ORM\JoinColumn(name="wmtsinstance", referencedColumnName="id")
     */
    protected $sourceInstance;

    /**
     * @ORM\ManyToOne(targetEntity="WmtsLayerSource", inversedBy="id", cascade={"refresh"})
     * @ORM\JoinColumn(name="wmtslayersource", referencedColumnName="id")
     */
    protected $sourceItem;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $title;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $format;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $infoformat;
//
//    /**
//     * @ORM\Column(type="string", nullable=true)
//     */
//    protected $exceptionformat = null;

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
//
//    /**
//     * @ORM\Column(type="boolean", nullable=true)
//     */
//    protected $allowreorder = true;
//
//    /**
//     * @ORM\Column(type="float", nullable=true)
//     */
//    protected $minScale;
//
//    /**
//     * @ORM\Column(type="float", nullable=true)
//     */
//    protected $maxScale;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $style = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $tileMatrixSet = "";

    public function __construct()
    {
//        $this->style    = "";
    }

    /**
     * Set id
     * @param integer $id
     * @return WmtsInstanceLayer
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
     * @return WmtsInstanceLayer
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
     * Set format
     *
     * @param string $format
     * @return WmtsInstance
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
        return $this->format;
    }

    /**
     * Set infoformat
     *
     * @param string $infoformat
     * @return WmtsInstance
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

    /**
     * Set active
     *
     * @param boolean $active
     * @return WmtsInstanceLayer
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
     * @return WmtsInstanceLayer
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
     * @return WmtsInstanceLayer
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
     * @return WmtsInstanceLayer
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
     * @return WmtsInstanceLayer
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
//
//    /**
//     * Get allowreorder
//     *
//     * @return boolean $allowreorder
//     */
//    public function getAllowreorder()
//    {
//        return $this->allowreorder;
//    }
//
//    /**
//     * Set allowreorder
//     *
//     * @param boolean $allowreorder
//     */
//    public function setAllowreorder($allowreorder)
//    {
//        $this->allowreorder = $allowreorder;
//        return $this;
//    }
//
//    /**
//     * Set minScale
//     *
//     * @param float $minScale
//     * @return WmtsInstanceLayer
//     */
//    public function setMinScale($minScale)
//    {
//        $this->minScale = $minScale;
//        return $this;
//    }
//
//    /**
//     * Get minScale
//     *
//     * @return float
//     */
//    public function getMinScale()
//    {
//        return $this->minScale;
//    }
//
//    /**
//     * Set maxScale
//     *
//     * @param float $maxScale
//     * @return WmtsInstanceLayer
//     */
//    public function setMaxScale($maxScale)
//    {
//        $this->maxScale = $maxScale;
//        return $this;
//    }
//
//    /**
//     * Get maxScale
//     *
//     * @return float
//     */
//    public function getMaxScale()
//    {
//        return $this->maxScale;
//    }

    /**
     * Set style
     *
     * @param string $style
     * @return WmtsInstanceLayer
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
     * Sets a tileMatrixSetLink
     * @param string $tileMatrixSet
     * @return \Mapbender\WmtsBundle\Entity\WmtsInstanceLayer
     */
    public function setTileMatrixSet($tileMatrixSet)
    {
        $this->tileMatrixSet = $tileMatrixSet;
        return $this;
    }

    /**
     * Gets a tileMatrixSetLink
     * @return string
     */
    public function getTileMatrixSet()
    {
        return $this->tileMatrixSet;
    }

        /**
     * @inheritdoc
     */
    public function setSourceInstance(SourceInstance $sourceInstance = NULL)
    {
        $this->sourceInstance = $sourceInstance;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSourceInstance()
    {
        return $this->sourceInstance;
    }

    /**
     * @inheritdoc
     */
    public function getSourceItem()
    {
        return $this->sourceItem;
    }

    /**
     * @inheritdoc
     */
    public function setSourceItem(SourceItem $sourceItem)
    {
        $this->sourceItem = $sourceItem;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return (string) $this->getId();
    }
}
