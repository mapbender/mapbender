<?php

namespace Mapbender\WmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Component\Utils;
use Mapbender\CoreBundle\Entity\Keyword;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\WmsBundle\Component\Attribution;
use Mapbender\WmsBundle\Component\Authority;
use Mapbender\WmsBundle\Component\Dimension;
use Mapbender\WmsBundle\Component\Identifier;
use Mapbender\WmsBundle\Component\IdentifierAuthority;
use Mapbender\WmsBundle\Component\MetadataUrl;
use Mapbender\WmsBundle\Component\MinMax;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmsBundle\Component\Style;

/**
 * @ORM\Entity
 * @ORM\Table(name="mb_wms_wmslayersource")
 */
class WmsLayerSource extends SourceItem implements ContainingKeyword
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @ORM\ManyToOne(targetEntity="WmsSource",inversedBy="layers")
     * @ORM\JoinColumn(name="wmssource", referencedColumnName="id")
     */
    protected $source; # change this variable name together with "get" "set" functions (s. SourceItem too)
    /**
     * @ORM\ManyToOne(targetEntity="WmsLayerSource",inversedBy="sublayer")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    protected $parent = null;
    /**
     * @ORM\OneToMany(targetEntity="WmsLayerSource",mappedBy="parent")
     * @ORM\OrderBy({"priority" = "asc","id" = "asc"})
     */
    protected $sublayer;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name = null;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $title = "";
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
     * @ORM\Column(type="object",nullable=true)
     */
    protected $scaleHint;

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
     * @ORM\OneToMany(targetEntity="WmsLayerSourceKeyword",mappedBy="reference", cascade={"remove"})
     * @ORM\OrderBy({"value" = "asc"})
     */
    protected $keywords;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $priority;

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
        $this->identifier = array();
        $this->authority = array();
    }

    /**
     * Sets an id
     * @param integer $id
     * @return WmsLayerSource
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
     * @inheritdoc
     */
    public function setSource(Source $wmssource)
    {
        $this->source = $wmssource;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set parent
     *
     * @param WmsLayerSource $parent
     * @return WmsLayerSource
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
     *
     * @return WmsLayerSource
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
     * @return WmsLayerSource
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
     * @return WmsLayerSource
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
     * @return WmsLayerSource
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
     * @return WmsLayerSource
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
     * @return WmsLayerSource
     */
    public function setQueryable($queryable)
    {
        $this->queryable = Utils::getBool($queryable);
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
     * @return WmsLayerSource
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
     * @return WmsLayerSource
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
     * @return WmsLayerSource
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
     * @return WmsLayerSource
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
     * @return WmsLayerSource
     */
    public function setFixedHeight($fixedHeight = null)
    {
        $this->fixedHeight = $fixedHeight;
        return $this;
    }

    /**
     * Get fixedHeight
     *
     * @return integer
     */
    public function getFixedHeight()
    {
        return $this->fixedHeight;
    }

    /**
     * Set latlonBounds
     *
     * @param BoundingBox $latlonBounds
     * @return WmsLayerSource
     */
    public function setLatlonBounds(BoundingBox $latlonBounds = null)
    {
        $this->latlonBounds = $latlonBounds;
        return $this;
    }

    /**
     * Get latlonBounds
     *
     * @return BoundingBox
     */
    public function getLatlonBounds($inherit = false)
    {
//        //@TODO check layer inheritance if layer->latlonBounds === null
        if ($inherit && $this->latlonBounds === null && $this->getParent() !== null) {
            return $this->getParent()->getLatlonBounds();
        } else {
            return $this->latlonBounds;
        }
//        return $this->latlonBounds;
    }

    /**
     * Add boundingBox
     *
     * @param BoundingBox $boundingBoxes
     * @return WmsLayerSource
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
     * @return WmsLayerSource
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
    public function getBoundingBoxes($inherit = false)
    {
//        //@TODO check layer inheritance if count(layer->boundingBoxes) === 0
//        if(count($this->boundingBoxes) === 0 && $this->getParent() !== null){
//            return $this->getParent()->getBoundingBoxes();
//        } else {
//            return $this->boundingBoxes;
//        }
        return $this->boundingBoxes;
    }

    /**
     * Set srs
     *
     * @param array $srs
     * @return WmsLayerSource
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
     * @return WmsLayerSource
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
     * @return WmsLayerSource
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
     * @return WmsLayerSource
     */
    public function setStyles($styles)
    {
        $this->styles = $styles ? $styles : array();
        return $this;
    }

    /**
     * Get styles incl. from parent WmsLayerSource (OGC WMS
     * Implemantation Specification)
     *
     * @return Style[]
     */
    public function getStyles($inherit = true)
    {
        if ($inherit && $this->getParent() !== null) { // add styles from parent
            return array_merge($this->getParent()->getStyles(), $this->styles);
        } else {
            return $this->styles;
        }
    }

    /**
     * Set scale
     *
     * @param MinMax $scale
     * @return WmsLayerSource
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
     * Get scale hint
     *
     * @return MinMax
     */
    public function getScaleRecursive()
    {
        $minScale = $this->getMinScale(true);
        $maxScale = $this->getMaxScale(true);
        $mergedScale = new MinMax($minScale, $maxScale);

        return $mergedScale;
    }

    /**
     * Set scaleHint
     *
     * @param MinMax $scaleHint
     * @return WmsLayerSource
     */
    public function setScaleHint(MinMax $scaleHint = null)
    {
        $this->scaleHint = $scaleHint;
        return $this;
    }

    /**
     * Get scaleHint
     *
     * @return MinMax
     */
    public function getScaleHint()
    {
        return $this->scaleHint;
    }

    /**
     * Set attribution
     *
     * @param Attribution $attribution
     * @return WmsLayerSource
     */
    public function setAttribution(Attribution $attribution = null)
    {
        $this->attribution = $attribution;
        return $this;
    }

    /**
     * Get attribution
     *
     * @return Object
     */
    public function getAttribution()
    {
        return $this->attribution;
    }

    /**
     * Add identifier
     *
     * @param Identifier $identifier
     * @return WmsLayerSource
     */
    public function addIdentifier(Identifier $identifier = null)
    {
        $this->identifier[] = $identifier;
        return $this;
    }

    /**
     * Set identifier
     *
     * @param array $identifier
     * @return WmsLayerSource
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier ? $identifier : array();
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
     * Get identifier
     *
     * @return array
     */
    public function getIdentifierAuthority()
    {
        $result = array();
        $authorities = $this->getAuthority(true);
        if (count($this->identifier) != 0 && count($authorities) != 0) {
            foreach ($this->identifier as $identifier) {
                foreach ($authorities as $authority) {
                    if ($authority->getName() == $identifier->getAuthority()) {
                        $ident_auth = new IdentifierAuthority();
                        $ident_auth->setAuthority($authority);
                        $ident_auth->setIdentifier($identifier);
                        $result[] = $ident_auth;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Add authority
     *
     * @param Authority $authority
     * @return WmsLayerSource
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
     * @return WmsLayerSource
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
     * @return ArrayCollection|Authority[]
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
     * @param array $metadataUrl
     * @return WmsLayerSource
     */
    public function addMetadataUrl(MetadataUrl $metadataUrl)
    {
        $this->metadataUrl[] = $metadataUrl;
        return $this;
    }

    /**
     * Set metadataUrl
     *
     * @param array $metadataUrl
     * @return WmsLayerSource
     */
    public function setMetadataUrl($metadataUrl)
    {
        $this->metadataUrl = $metadataUrl ? $metadataUrl : array();
        return $this;
    }

    /**
     * Get metadataUrl
     *
     * @return array
     */
    public function getMetadataUrl()
    {
        return $this->metadataUrl;
    }

    /**
     * Add dimension
     *
     * @param Dimension | null $dimension
     * @return WmsLayerSource
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
     * @return WmsLayerSource
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
     * @param array $dataUrl
     * @return WmsLayerSource
     */
    public function addDataUrl(OnlineResource $dataUrl = null)
    {
        $this->dataUrl[] = $dataUrl;
        return $this;
    }

    /**
     * Set dataUrl
     *
     * @param array $dataUrl
     * @return WmsLayerSource
     */
    public function setDataUrl($dataUrl)
    {
        $this->dataUrl = $dataUrl;
        return $this;
    }

    /**
     * Get dataUrl
     *
     * @return array
     */
    public function getDataUrl()
    {
        return $this->dataUrl;
    }

    /**
     * Add featureListUrl
     *
     * @param array $featureListUrl
     * @return WmsLayerSource
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
     * @return WmsLayerSource
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
     * @return WmsLayerSource
     */
    public function setKeywords(ArrayCollection $keywords)
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * Get keywords
     *
     * @return ArrayCollection collection of keywords
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Add keywords
     *
     * @param WmsLayerSourceKeyword $keyword
     * @return WmsLayerSource
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
     * @return WmsInstanceLayer
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
     * @inheritdoc
     */
    public function getClassname()
    {
        return get_class();
    }

    public function __toString()
    {
        return (string)$this->id;
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
}
