<?php

namespace Mapbender\WmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Component\Utils;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * WmsInstanceLayer class
 *
 * @author Paul Schmidt
 *
 * @ORM\Entity(repositoryClass="WmsInstanceLayerRepository")
 * @ORM\Table(name="mb_wms_wmsinstancelayer")
 * @ORM\HasLifeCycleCallbacks()
 * @property WmsLayerSource $sourceItem
 */
class WmsInstanceLayer extends SourceInstanceItem
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
     * @ORM\OneToMany(targetEntity="WmsInstanceLayer",mappedBy="parent", cascade={"remove"})
     * @ORM\OrderBy({"priority" = "asc", "id" = "asc"})
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

    /**
     * WmsInstanceLayer constructor.
     */
    public function __construct()
    {
        $this->sublayer = new ArrayCollection();
        $this->style = "";
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
     * @return string|null
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
     * @return $this
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
     * Set allow reorder
     *
     * @param boolean $value
     * @return $this
     */
    public function setAllowreorder($value)
    {
        $this->allowreorder = $value;
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
     * 1) if admin replaced min scale for THE parent layer instance, use that value.
     * 2) if THE parent instance has no admin-set value, use value from source layer
     * 3) if neither is set, recurse up the tree, maintaining preference instance first, then source
     * @return float|null
     */
    public function getInheritedMinScale()
    {
        $parent = $this->getParent();
        $parentValue = $parent ? $parent->getMinScale(false) : null;
        if ($parentValue !== null) {
            $value = $parentValue;
        } else {
            $value = $this->getSourceItem()->getMinScale(false);
            if ($value === null && $parent) {
                $value = $parent->getInheritedMinScale();
            }
        }
        return $value;
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
     * 1) if admin replaced max scale for THE parent layer instance, use that value.
     * 2) if THE parent instance has no admin-set value, use value from source layer
     * 3) if neither is set, recurse up the tree, maintaining preference instance first, then source
     * @return float|null
     */
    public function getInheritedMaxScale()
    {
        $parent = $this->getParent();
        $parentValue = $parent ? $parent->getMaxScale(false) : null;
        if ($parentValue !== null) {
            $value = $parentValue;
        } else {
            $value = $this->getSourceItem()->getMaxScale(false);
            if ($value === null && $parent) {
                $value = $parent->getInheritedMaxScale();
            }
        }
        return $value;
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
    public function setSourceInstance(SourceInstance $sourceInstance = NULL)
    {
        $this->sourceInstance = $sourceInstance;
        return $this;
    }

    /**
     * @return WmsInstance
     */
    public function getSourceInstance()
    {
        return $this->sourceInstance;
    }


    /**
     * @return WmsLayerSource
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

    /**
     * @internal
     * @param WmsInstance $instance source
     * @param WmsLayerSource $layerSource also the source, purpose unknown
     * @param int $priority
     */
    public function populateFromSource(WmsInstance $instance, WmsLayerSource $layerSource, $priority = 0)
    {
        $this->setSourceInstance($instance);
        $this->setSourceItem($layerSource);

        $this->setMinScale($layerSource->getMinScale());
        $this->setMaxScale($layerSource->getMaxScale());

        $queryable = $layerSource->getQueryable();
        $this->setInfo(Utils::getBool($queryable));
        $this->setAllowinfo(Utils::getBool($queryable));
        $this->setPriority($priority);
        $instance->addLayer($this);
        if ($layerSource->getSublayer()->count() > 0) {
            $this->setToggle(false);
            $this->setAllowtoggle(true);
        } else {
            $this->setToggle(null);
            $this->setAllowtoggle(null);
        }
        foreach ($layerSource->getSublayer() as $wmslayersourceSub) {
            $subLayerInstance = new static();
            $subLayerInstance->populateFromSource($instance, $wmslayersourceSub, $priority);
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
