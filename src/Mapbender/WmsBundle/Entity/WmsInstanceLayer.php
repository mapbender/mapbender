<?php

namespace Mapbender\WmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\Component\SourceLoaderSettings;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;

/**
 * @author Paul Schmidt
 *
 * @ORM\Entity(repositoryClass="WmsInstanceLayerRepository")
 * @ORM\Table(name="mb_wms_wmsinstancelayer")
 * @ORM\HasLifecycleCallbacks
 *
 * @property WmsLayerSource $sourceItem
 * @method WmsInstance getSourceInstance
 * @method WmsLayerSource getSourceItem
 */
class WmsInstanceLayer extends SourceInstanceItem
{

    /**
     * @ORM\ManyToOne(targetEntity="WmsInstance", inversedBy="layers", cascade={"refresh", "persist"})
     * @ORM\JoinColumn(name="wmsinstance", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $sourceInstance;

    /**
     * @ORM\ManyToOne(targetEntity="WmsLayerSource", cascade={"refresh"}, inversedBy="instanceLayers")
     * @ORM\JoinColumn(name="wmslayersource", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $sourceItem;

    /**
     * @ORM\ManyToOne(targetEntity="WmsInstanceLayer",inversedBy="sublayer")
     * @ORM\JoinColumn(name="parent", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $parent = null;

    /**
     * @ORM\OneToMany(targetEntity="WmsInstanceLayer",mappedBy="parent", cascade={"persist", "remove"})
     * @ORM\OrderBy({"priority" = "asc", "id" = "asc"})
     */
    protected $sublayer;

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

    /**
     * if set to true, info is not just disabled, but not available. If this is set to true, setting info to
     * true is prevented. This is required for the case when the info was previously activated but not available
     * anymore after a WMS update. The info status should then be resetted.
     */
    private ?bool $infoUnavailable = false;

    /**
     * WmsInstanceLayer constructor.
     */
    public function __construct(SourceLoaderSettings $settings = null)
    {
        $this->sublayer = new ArrayCollection();
        $this->style = "";
        if ($settings !== null) {
            $this->active = $settings->activateNewLayers();
            $this->selected = $settings->selectNewLayers();;
        }
    }

    public function __clone()
    {
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

    /**
     * @ORM\PostLoad()
     */
    public function postLoad()
    {
        if ($this->minScale == INF) {
            $this->minScale = null;
        }
        if ($this->maxScale == INF) {
            $this->maxScale = null;
        }
        if (!$this->sublayer->count() && ($this->toggle !== null || $this->allowtoggle !== null)) {
            /** @todo: write a migration / automatic bootstrap process that fixes bad values permanently */
            @trigger_error("WARNING: resetting invalid toggle / allowtoggle state on " . get_class($this) . " #{$this->id}", E_USER_DEPRECATED);
            $this->setToggle(null);
            $this->setAllowtoggle(null);
        }
    }

    /**
     * Set sublayer as array of string
     *
     * @param ArrayCollection $sublayer
     * @return WmsInstanceLayer
     */
    public function setSublayer($sublayer)
    {
        $this->sublayer = $sublayer;
        return $this;
    }

    /**
     * @param WmsInstanceLayer $sublayer
     * @return WmsInstanceLayer
     */
    public function addSublayer(WmsInstanceLayer $sublayer)
    {
        $sublayer->setParent($this);
        $this->sublayer->add($sublayer);
        return $this;
    }

    /**
     * Get sublayer
     *
     * @return ArrayCollection|WmsInstanceLayer[]
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
        $this->active = (bool) $active;
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
        $this->allowselected = (bool) $allowselected;
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
        $this->selected = (bool) $selected;
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
    public function setInfo($info, bool $force = false)
    {
        if ($this->infoUnavailable === true && !$force) {
            $this->info = false;
        } else {
            $this->info = (bool) $info;
            if ($force && !$info) $this->infoUnavailable = true;
        }
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
        $this->toggle = (bool) $toggle;
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
        if ($this->infoUnavailable === true) {
            $this->allowinfo = false;
        } else {
            $this->allowinfo = (bool) $allowinfo;
        }

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
        $this->allowtoggle = (bool) $allowtoggle;
        return $this;
    }

    /**
     * Set minScale
     *
     * @param float|null $value
     * @return WmsInstanceLayer
     */
    public function setMinScale($value)
    {
        $this->minScale = ($value === null || $value == INF) ? null : floatval($value);
        return $this;
    }

    /**
     * Get minScale
     *
     * Recursive path used by frontend config generation and backend instance form
     * for placeholders only.
     *
     * @param boolean $recursive Try to get value from parent
     * @return float
     */
    public function getMinScale($recursive = false)
    {
        $value = $this->minScale;

        if ($recursive && $value === null) {
            $value = $this->getInheritedMinScale();
        }
        return $value;
    }

    /**
     * Get inherited effective min scale for layer instance
     * 1) if source layer has a non-null value, use that
     * 2) if admin replaced min scale for the parent layer instance, use that value.
     * 3) if neither is set, recurse up, maintaining preference source layer first, then parent instance layer
     * @return float|null
     */
    public function getInheritedMinScale()
    {
        $sourceItemScale = $this->getSourceItem()->getMinScale(false);
        if ($sourceItemScale !== null) {
            return $sourceItemScale;
        }
        $parent = $this->getParent();
        return $parent ? $parent->getMinScale(true) : null;
    }

    /**
     * Set maximum scale hint
     *
     * @param float|null $value
     * @return WmsInstanceLayer
     */
    public function setMaxScale($value)
    {
        $this->maxScale = ($value === null || $value == INF) ? null : floatval($value);
        return $this;
    }

    /**
     * Get maximums scale hint
     *
     * Recursive path used by frontend config generation and backend instance form
     * for placeholders only.
     *
     * @param boolean $recursive Try to get value from parent
     * @return float|null
     */
    public function getMaxScale($recursive = false)
    {
        $value = $this->maxScale;

        if ($recursive && $value === null) {
            $value = $this->getInheritedMaxScale();
        }
        return $value;
    }

    /**
     * Get inherited effective max scale for layer instance
     * 1) if source layer has a non-null value, use that
     * 2) if admin replaced min scale for the parent layer instance, use that value.
     * 3) if neither is set, recurse up, maintaining preference source layer first, then parent instance layer
     * @return float|null
     */
    public function getInheritedMaxScale()
    {
        $sourceItemScale = $this->getSourceItem()->getMaxScale(false);
        if ($sourceItemScale !== null) {
            return $sourceItemScale;
        }
        $parent = $this->getParent();
        return $parent ? $parent->getMaxScale(true) : null;
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
        if ($priority !== null) {
            $this->priority = intval($priority);
        } else {
            $this->priority = $priority;
        }
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
     * @inheritdoc
     */
    public function __toString()
    {
        return (string)$this->getId();
    }

    /**
     * @internal
     * @param WmsInstance $instance
     * @param WmsLayerSource $layerSource
     */
    public function populateFromSource(WmsInstance $instance, WmsLayerSource $layerSource, ?SourceLoaderSettings $settings = null)
    {
        $this->setSourceInstance($instance);
        $this->setSourceItem($layerSource);
        $this->setPriority($layerSource->getPriority());

        $queryable = !!$layerSource->getQueryable();
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
            $subLayerInstance = new static($settings);
            $subLayerInstance->populateFromSource($instance, $wmslayersourceSub);
            $subLayerInstance->setParent($this);
            $this->addSublayer($subLayerInstance);
        }
    }

    /**
     * @return boolean
     */
    public function isRoot()
    {
        return !$this->getParent();
    }
}
