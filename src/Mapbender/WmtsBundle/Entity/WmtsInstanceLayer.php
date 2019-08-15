<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;

/**
 * WmtsInstanceLayer class
 *
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_wmts_wmtsinstancelayer")
 *
 * @property WmtsLayerSource sourceItem
 * @method WmtsInstance getSourceInstance
 * @method WmtsLayerSource getSourceItem
 */
class WmtsInstanceLayer extends SourceInstanceItem
{
    /**
     * @ORM\ManyToOne(targetEntity="WmtsInstance", inversedBy="layers", cascade={"refresh"})
     * @ORM\JoinColumn(name="wmtsinstance", referencedColumnName="id")
     */
    protected $sourceInstance;

    /**
     * @ORM\ManyToOne(targetEntity="WmtsLayerSource", cascade={"refresh"})
     * @ORM\JoinColumn(name="wmtslayersource", referencedColumnName="id")
     */
    protected $sourceItem;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $infoformat;

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
     * @ORM\Column(type="string", nullable=true)
     */
    protected $style = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $tileMatrixSet = "";

    /**
     * Set infoformat
     *
     * @param string $infoformat
     * @return $this
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
     * Set active
     *
     * @param boolean $active
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setAllowtoggle($allowtoggle)
    {
        $this->allowtoggle = $allowtoggle;
        return $this;
    }

    /**
     * Set style
     *
     * @param string $style
     * @return $this
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
     * @return $this
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

    public function __toString()
    {
        return (string)$this->getId();
    }
}
