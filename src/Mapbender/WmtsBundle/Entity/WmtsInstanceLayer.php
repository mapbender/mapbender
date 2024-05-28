<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;

/**
 * @author Paul Schmidt
 *
 *
 * @property WmtsLayerSource sourceItem
 * @method WmtsInstance getSourceInstance
 * @method WmtsLayerSource getSourceItem
 */
#[ORM\Entity]
#[ORM\Table(name: 'mb_wmts_wmtsinstancelayer')]
class WmtsInstanceLayer extends SourceInstanceItem
{
    #[ORM\ManyToOne(targetEntity: WmtsInstance::class, cascade: ['persist', 'refresh'], inversedBy: 'layers')]
    #[ORM\JoinColumn(name: 'wmtsinstance', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $sourceInstance;

    #[ORM\ManyToOne(targetEntity: WmtsLayerSource::class, cascade: ['persist', 'refresh'])]
    #[ORM\JoinColumn(name: 'wmtslayersource', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $sourceItem;

    #[ORM\Column(type: 'string', nullable: true)]
    protected $infoformat;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected $active = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected $allowselected = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected $selected = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected $info;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected $allowinfo;

    #[ORM\Column(type: 'string', nullable: true)]
    protected $style = "";

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'sublayer')]
    #[ORM\JoinColumn(name: 'parent', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?WmtsInstanceLayer $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['priority' => 'asc', 'id' => 'asc'])]
    protected $sublayer;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $toggle = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $allowtoggle = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected $priority;

    public function __construct() {
        $this->sublayer = new ArrayCollection();
        if ($this->id) {
            $sublayers = $this->getSublayer()->getValues();
            $newSublayers = array();
            $this->setId(null);
            foreach ($sublayers as $layer) {
                /** @var static $layer */
                $layerClone = clone $layer;
                $layerClone->setParent($this);
                $newSublayers[] = $layerClone;
            }
            $this->sublayer = new ArrayCollection($newSublayers);
        }
    }

    public function populateFromSource(WmtsInstance $instance, WmtsLayerSource $layerSource)
    {
        $this->setSourceInstance($instance);
        $this->setSourceItem($layerSource);
        $this->setPriority($layerSource->getPriority());

        $queryable = !!$layerSource->getInfoformats();
        $this->setInfo($queryable, true);
        $this->setAllowinfo($queryable);
        $instance->addLayer($this);
        if ($layerSource->getSublayer()->count() > 0) {
            $this->setToggle(false);
            $this->setAllowtoggle(true);
        } else {
            $this->setToggle(null);
            $this->setAllowtoggle(null);
        }
        foreach ($layerSource->getSublayer() as $wmslayersourceSub) {
            $subLayerInstance = new self();
            $subLayerInstance->populateFromSource($instance, $wmslayersourceSub);
            $subLayerInstance->setParent($this);
            $this->addSublayer($subLayerInstance);
        }
    }


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
        $this->active = (bool)$active;
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
        $this->allowselected = (bool)$allowselected;
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
        $this->selected = (bool)$selected;
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
        $this->info = (bool)$info;
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
        $this->allowinfo = (bool)$allowinfo;
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

    public function getParent(): ?WmtsInstanceLayer
    {
        return $this->parent;
    }

    public function setParent(?WmtsInstanceLayer $parent): void
    {
        $this->parent = $parent;
    }

    public function setSublayer(self $sublayer): self
    {
        $this->sublayer = $sublayer;
        return $this;
    }

    public function addSublayer(self $sublayer): self
    {
        $sublayer->setParent($this);
        $this->sublayer->add($sublayer);
        return $this;
    }

    /**
     * @return ArrayCollection|PersistentCollection|WmtsInstanceLayer[]
     */
    public function getSublayer(): ArrayCollection | PersistentCollection | array
    {
        return $this->sublayer;
    }

    public function getToggle(): ?bool
    {
        return $this->toggle;
    }

    public function setToggle(?bool $toggle): void
    {
        $this->toggle = $toggle;
    }

    public function getAllowtoggle(): ?bool
    {
        return $this->allowtoggle;
    }

    public function setAllowtoggle(?bool $allowtoggle): void
    {
        $this->allowtoggle = $allowtoggle;
    }

    public function setPriority(mixed $priority): self
    {
        if ($priority !== null) {
            $this->priority = intval($priority);
        } else {
            $this->priority = $priority;
        }
        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }


}
