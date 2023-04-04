<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;

/**
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
     * @ORM\ManyToOne(targetEntity="WmtsInstance", inversedBy="layers", cascade={"persist", "refresh"})
     * @ORM\JoinColumn(name="wmtsinstance", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $sourceInstance;

    /**
     * @ORM\ManyToOne(targetEntity="WmtsLayerSource", cascade={"persist", "refresh"})
     * @ORM\JoinColumn(name="wmtslayersource", referencedColumnName="id", onDelete="CASCADE")
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
     * @ORM\Column(type="string", nullable=true)
     */
    protected $style = "";

    public function __clone()
    {
        if ($this->id) {
            $this->setId(null);
        }
    }

    /**
     * @param string $infoformat
     * @return $this
     */
    public function setInfoformat($infoformat)
    {
        $this->infoformat = $infoformat;
        return $this;
    }

    /**
     * @return string
     */
    public function getInfoformat()
    {
        return $this->infoformat;
    }

    /**
     * @param boolean $active
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = (bool) $active;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $allowselected
     * @return $this
     */
    public function setAllowselected($allowselected)
    {
        $this->allowselected = (bool) $allowselected;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getAllowselected()
    {
        return $this->allowselected;
    }

    /**
     * @param boolean $selected
     * @return $this
     */
    public function setSelected($selected)
    {
        $this->selected = (bool) $selected;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getSelected()
    {
        return $this->selected;
    }

    /**
     * @param boolean $info
     * @return $this
     */
    public function setInfo($info)
    {
        $this->info = (bool) $info;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param boolean $allowinfo
     * @return $this
     */
    public function setAllowinfo($allowinfo)
    {
        $this->allowinfo = (bool) $allowinfo;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getAllowinfo()
    {
        return $this->allowinfo;
    }

    /**
     * @param string $style
     * @return $this
     */
    public function setStyle($style)
    {
        $this->style = $style;
        return $this;
    }

    /**
     * @return string
     */
    public function getStyle()
    {
        return $this->style;
    }

    public function __toString()
    {
        return (string)$this->getId();
    }
}
