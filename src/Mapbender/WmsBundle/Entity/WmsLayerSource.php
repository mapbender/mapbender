<?php

namespace Mapbender\WmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Entity\Keyword;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\WmsBundle\Component\Attribution;
use Mapbender\WmsBundle\Component\Authority;
use Mapbender\WmsBundle\Component\Dimension;
use Mapbender\WmsBundle\Component\Identifier;
use Mapbender\WmsBundle\Component\MetadataUrl;
use Mapbender\WmsBundle\Component\MinMax;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmsBundle\Component\Style;

/**
 * @ORM\Entity
 * @ORM\Table(name="mb_wms_wmslayersource")
 *
 * @property WmsSource $source
 * @method WmsSource getSource
 */
class WmsLayerSource extends SourceItem implements ContainingKeyword, MutableUrlTarget
{
    /**
     * @ORM\ManyToOne(targetEntity="WmsSource",inversedBy="layers")
     * @ORM\JoinColumn(name="wmssource", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $source;
    /**
     * @ORM\ManyToOne(targetEntity="WmsLayerSource",inversedBy="sublayer")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $parent = null;
    /**
     * @ORM\OneToMany(targetEntity="WmsLayerSource",mappedBy="parent", cascade={"persist", "remove"})
     * @ORM\OrderBy({"priority" = "asc","id" = "asc"})
     */
    protected $sublayer;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name = null;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $abstract = "";
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $queryable;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $cascaded = 0;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $opaque = false;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $noSubset = false;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $fixedWidth;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $fixedHeight;
    /**
     * @ORM\Column(type="object", nullable=true)
     */
    protected $latlonBounds;
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $boundingBoxes;
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $srs;
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $styles;
    /**
     * @ORM\Column(type="object",nullable=true)
     */
    protected $scale;

    /**
     * @ORM\Column(type="object", nullable=true)
     */
    protected $attribution;

    /**
     * @ORM\Column(type="array",nullable=true)
     */
    protected $identifier;

    /**
     * @ORM\Column(type="array",nullable=true)
     */
    protected $authority;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $metadataUrl;
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $dimension;
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $dataUrl;
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $featureListUrl;
    /**
     * @var ArrayCollection A list of WMS Layer keywords
     * @ORM\OneToMany(targetEntity="WmsLayerSourceKeyword",mappedBy="reference", cascade={"persist", "remove"})
     * @ORM\OrderBy({"value" = "asc"})
     */
    protected $keywords;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $priority;

    /**
     * @var ArrayCollection A list of layer instances
     * @ORM\OneToMany(targetEntity="Mapbender\WmsBundle\Entity\WmsInstanceLayer",mappedBy="sourceItem", cascade={"remove"})
     */
    protected $instanceLayers;

    /**
     * WmsLayerSource constructor.
     */
    public function __construct()
    {
        $this->sublayer = new ArrayCollection();
        $this->keywords = new ArrayCollection();
        $this->boundingBoxes = array();
        $this->metadataUrl = array();
        $this->dimension = array();
        $this->dataUrl = array();
        $this->featureListUrl = array();
        $this->styles = array();
        $this->srs = array();
        $this->authority = array();
    }

    /**
     * Set parent
     *
     * @param WmsLayerSource $parent
     * @return $this
     */
    public function setParent(WmsLayerSource $parent = null)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Get parent
     *
     * @return WmsLayerSource|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     *
     * @return ArrayCollection|WmsLayerSource[]
     */
    public function getSublayer()
    {
        return $this->sublayer;
    }

    /**
     * @param ArrayCollection $sublayer
     * @return $this
     */
    public function setSublayer($sublayer)
    {
        $this->sublayer = $sublayer;
        return $this;
    }

    /**
     * Add sublayer
     *
     * @param WmsLayerSource $sublayer
     * @return $this
     */
    public function addSublayer(WmsLayerSource $sublayer)
    {
        $this->sublayer->add($sublayer);
        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name = null)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle($title = null)
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
     * Set abstract
     *
     * @param string $abstract
     * @return $this
     */
    public function setAbstract($abstract = null)
    {
        $this->abstract = $abstract;
        return $this;
    }

    /**
     * Get abstract
     *
     * @return string
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * Set queryable
     *
     * @param boolean $queryable
     * @return $this
     */
    public function setQueryable($queryable)
    {
        $this->queryable = !!$queryable;
        return $this;
    }

    /**
     * Get queryable
     *
     * @return boolean
     */
    public function getQueryable()
    {
        return $this->queryable;
    }

    /**
     * Set cascaded
     *
     * @param integer $cascaded
     * @return $this
     */
    public function setCascaded($cascaded)
    {
        $this->cascaded = $cascaded;
        return $this;
    }

    /**
     * Get cascaded
     *
     * @return integer
     */
    public function getCascaded()
    {
        return $this->cascaded;
    }

    /**
     * Set opaque
     *
     * @param boolean $opaque
     * @return $this
     */
    public function setOpaque($opaque)
    {
        $this->opaque = $opaque;
        return $this;
    }

    /**
     * Get opaque
     *
     * @return boolean
     */
    public function getOpaque()
    {
        return $this->opaque;
    }

    /**
     * Set noSubset
     *
     * @param boolean $noSubset
     * @return $this
     */
    public function setNoSubset($noSubset)
    {
        $this->noSubset = $noSubset;
        return $this;
    }

    /**
     * Get noSubset
     *
     * @return boolean
     */
    public function getNoSubset()
    {
        return $this->noSubset;
    }

    /**
     * Set fixedWidth
     *
     * @param integer $fixedWidth
     * @return $this
     */
    public function setFixedWidth($fixedWidth = null)
    {
        $this->fixedWidth = $fixedWidth;
        return $this;
    }

    /**
     * Get fixedWidth
     *
     * @return integer
     */
    public function getFixedWidth()
    {
        return $this->fixedWidth;
    }

    /**
     * Set fixedHeight
     *
     * @param integer $fixedHeight
     * @return $this
     */
    public function setFixedHeight($fixedHeight = null)
    {
        $this->fixedHeight = $fixedHeight;
        return $this;
    }

    /**
     * @return integer
     */
    public function getFixedHeight()
    {
        return $this->fixedHeight;
    }

    /**
     * @param BoundingBox $latlonBounds
     * @return $this
     */
    public function setLatlonBounds(BoundingBox $latlonBounds = null)
    {
        $this->latlonBounds = $latlonBounds;
        return $this;
    }

    /**
     * @param bool $inherit
     * @return BoundingBox
     */
    public function getLatlonBounds($inherit = false)
    {
        if ($inherit && $this->latlonBounds === null && $this->getParent() !== null) {
            return $this->getParent()->getLatlonBounds($inherit);
        } else {
            return $this->latlonBounds;
        }
    }

    /**
     * Add boundingBox
     *
     * @param BoundingBox $boundingBoxes
     * @return $this
     */
    public function addBoundingBox(BoundingBox $boundingBoxes)
    {
        $this->boundingBoxes[] = $boundingBoxes;
        return $this;
    }

    /**
     * Set boundingBoxes
     *
     * @param BoundingBox[] $boundingBoxes
     * @return $this
     */
    public function setBoundingBoxes($boundingBoxes)
    {
        $this->boundingBoxes = $boundingBoxes ? $boundingBoxes : array();
        return $this;
    }

    /**
     * Get boundingBoxes
     *
     * @return BoundingBox[]
     */
    public function getBoundingBoxes()
    {
        return $this->boundingBoxes;
    }

    /**
     * Set srs
     *
     * @param array $srs
     * @return $this
     */
    public function setSrs($srs)
    {
        $this->srs = $srs ? $srs : array();
        return $this;
    }

    /**
     * Add srs
     *
     * @param string $srs
     * @return $this
     */
    public function addSrs($srs)
    {
        $this->srs[] = $srs;
        return $this;
    }

    /**
     * Get srs incl. from parent WmsLayerSource (OGC WMS
     * Implemantation Specification)
     *
     * @return array
     */
    public function getSrs($inherit = false)
    {
        if ($inherit && $this->getParent() !== null) { // add crses from parent
            return array_unique(array_merge($this->getParent()->getSrs(), $this->srs));
        } else {
            return $this->srs;
        }
    }

    /**
     * Add style
     *
     * @param Style $style
     * @return $this
     */
    public function addStyle(Style $style = null)
    {
        $this->styles[] = $style;
        return $this;
    }

    /**
     * Set styles
     *
     * @param array $styles
     * @return $this
     */
    public function setStyles($styles)
    {
        $this->styles = $styles ? $styles : array();
        return $this;
    }

    /**
     * Get styles incl. from parent WmsLayerSource (OGC WMS
     * Implemantation Specification)
     * @param bool $inherit to also return style objects from parent layer(s)
     *
     * @return Style[]
     */
    public function getStyles($inherit = false)
    {
        if ($inherit && $this->getParent() !== null) {
            return array_merge($this->getParent()->getStyles(true), $this->styles);
        } else {
            return $this->styles;
        }
    }

    /**
     * Set scale
     *
     * @param MinMax $scale
     * @return $this
     */
    public function setScale(MinMax $scale = null)
    {
        $this->scale = $scale;
        return $this;
    }

    /**
     * Get scale
     *
     * @return MinMax
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * Get minimum scale hint
     *
     * @param bool $recursive Try to get value from parent
     * @return float|null
     */
    public function getMinScale($recursive = false)
    {
        $value = null;
        $nextSource = $this;
        do {
            $scaleObj = $nextSource->getScale();
            $value = $scaleObj ? $scaleObj->getMin() : null;
            $nextSource = $nextSource->getParent();
        } while ($value === null && $recursive && $nextSource);

        return $value === null ? null : floatval($value);
    }

    /**
     * Get maximum scale hint
     *
     * @param bool $recursive Try to get value from parent
     * @return float|null
     */
    public function getMaxScale($recursive = false)
    {
        $value = null;
        $nextSource = $this;
        do {
            $scaleObj = $nextSource->getScale();
            $value = $scaleObj ? $scaleObj->getMax() : null;
            $nextSource = $nextSource->getParent();
        } while ($value === null && $recursive && $nextSource);

        return $value === null ? null : floatval($value);
    }

    /**
     * Set attribution
     *
     * @param Attribution $attribution
     * @return $this
     */
    public function setAttribution(Attribution $attribution = null)
    {
        $this->attribution = $attribution;
        return $this;
    }

    /**
     * Get attribution
     *
     * @return Attribution|null
     */
    public function getAttribution()
    {
        return $this->attribution;
    }

    /**
     * Set identifier
     *
     * @param Identifier $identifier
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Get identifier
     *
     * @return Identifier
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Add authority
     *
     * @param Authority $authority
     * @return $this
     */
    public function addAuthority(Authority $authority)
    {
        $this->authority[] = $authority;
        return $this;
    }

    /**
     * Set authority
     *
     * @param array $authority
     * @return $this
     */
    public function setAuthority($authority)
    {
        $this->authority = $authority ? $authority : array();
        return $this;
    }

    /**
     * Get authority
     *
     * @param bool $inherit to append Authrity objects inherited (recursively) from parent, if any
     * @return Authority[]
     */
    public function getAuthority($inherit = false)
    {
        if ($inherit && $this->getParent() !== null && $this->getParent()->getAuthority() !== null) {
            return array_merge($this->getParent()->getAuthority(), $this->authority);
        } else {
            return $this->authority;
        }
    }

    /**
     * Add metadataUrl
     *
     * @param MetadataUrl $metadataUrl
     * @return $this
     */
    public function addMetadataUrl(MetadataUrl $metadataUrl)
    {
        $this->metadataUrl[] = $metadataUrl;
        return $this;
    }

    /**
     * Set metadataUrl
     *
     * @param MetadataUrl[] $metadataUrl
     * @return $this
     */
    public function setMetadataUrl($metadataUrl)
    {
        $this->metadataUrl = $metadataUrl ? $metadataUrl : array();
        return $this;
    }

    /**
     * Get metadataUrl
     *
     * @return MetadataUrl[]
     */
    public function getMetadataUrl()
    {
        return $this->metadataUrl;
    }

    /**
     * Add dimension
     *
     * @param Dimension | null $dimension
     * @return $this
     */
    public function addDimension($dimension)
    {
        if ($dimension !== null) {
            $this->dimension[] = $dimension;
        }
        return $this;
    }

    /**
     * Set dimension
     *
     * @param array $dimension
     * @return $this
     */
    public function setDimension($dimension)
    {
        $this->dimension = $dimension ? $dimension : array();
        return $this;
    }

    /**
     * Get dimension
     *
     * @return Dimension[]
     */
    public function getDimension()
    {
        return $this->dimension;
    }

    /**
     * Add dataUrl
     *
     * @param OnlineResource $dataUrl
     * @return $this
     */
    public function addDataUrl(OnlineResource $dataUrl = null)
    {
        $this->dataUrl[] = $dataUrl;
        return $this;
    }

    /**
     * Set dataUrl
     *
     * @param OnlineResource[] $dataUrl
     * @return $this
     */
    public function setDataUrl($dataUrl)
    {
        $this->dataUrl = $dataUrl;
        return $this;
    }

    /**
     * Get dataUrl
     *
     * @return OnlineResource[]
     */
    public function getDataUrl()
    {
        return $this->dataUrl;
    }

    /**
     * Add featureListUrl
     *
     * @param OnlineResource $featureListUrl
     * @return $this
     */
    public function addFeatureListUrl(OnlineResource $featureListUrl)
    {
        $this->featureListUrl[] = $featureListUrl;
        return $this;
    }

    /**
     * Set featureListUrl
     *
     * @param array $featureListUrl
     * @return $this
     */
    public function setFeatureListUrl($featureListUrl)
    {
        $this->featureListUrl = $featureListUrl ? $featureListUrl : array();
        return $this;
    }

    /**
     * Get featureListUrl
     *
     * @return array
     */
    public function getFeatureListUrl()
    {
        return $this->featureListUrl;
    }

    /**
     * Set keywords
     *
     * @param ArrayCollection $keywords
     * @return $this
     */
    public function setKeywords(ArrayCollection $keywords)
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * Get keywords
     *
     * @return ArrayCollection|Keyword[]
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Add keywords
     *
     * @param Keyword $keyword
     * @return $this
     */
    public function addKeyword(Keyword $keyword)
    {
        $this->keywords->add($keyword);
        return $this;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority !== null ? intval($priority) : $priority;
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
     * Returns a merged array of the latlon bounds (if set) and other bounding boxes.
     * This is used by the *EntityHandler machinery frontend config generation.
     *
     * @return BoundingBox[]
     */
    public function getMergedBoundingBoxes()
    {
        $bboxes = array();
        $latLonBounds = $this->getLatlonBounds();
        if ($latLonBounds) {
            $bboxes[] = $latLonBounds;
        }
        return array_merge($bboxes, $this->getBoundingBoxes());
    }

    public function mutateUrls(OneWayTransformer $transformer)
    {
        $stylesNew = array();
        $authoritiesNew = array();
        foreach ($this->getStyles(false) as $style) {
            $style->mutateUrls($transformer);
            $stylesNew[] = clone $style;
        }
        foreach ($this->getAuthority(false) as $authority) {
            $authority->mutateUrls($transformer);
            $authoritiesNew[] = clone $authority;
        }
        $this->setStyles($stylesNew);
        $this->setAuthority($authoritiesNew);
    }
}
