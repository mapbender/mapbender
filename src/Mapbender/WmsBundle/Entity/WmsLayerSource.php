<?php

namespace Mapbender\WmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\WmsBundle\Component\Attribution;
use Mapbender\WmsBundle\Component\Identifier;
use Mapbender\WmsBundle\Component\MetadataUrl;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmsBundle\Component\Style;

/**
 * @ORM\Entity
 * @ORM\Table(name="mb_wms_wmslayersource")
 */
class WmsLayerSource {

    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="WmsSource",inversedBy="layers", cascade={"refresh", "persist"})
     * @ORM\JoinColumn(name="wmssource", referencedColumnName="id")
     */
    protected $wmssource;

    /**
     * @ORM\ManyToOne(targetEntity="WmsLayerSource",inversedBy="layer", cascade={"refresh", "persist"})
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    protected $parent = null;

    /**
     * @ORM\OneToMany(targetEntity="WmsLayerSource",mappedBy="parent", cascade={"persist","remove"})
     */
    protected $layer;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $title = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $abstract = "";

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $queryable = false;

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
     * @ORM\Column(type="float",nullable=true)
     */
    protected $minScale;

    /**
     * @ORM\Column(type="float",nullable=true)
     */
    protected $maxScale;

    /**
     * @ORM\Column(type="object", nullable=true)
     */
    protected $attribution;

    /**
     * @ORM\Column(type="object",nullable=true)
     */
    protected $identifier = '';

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $metadataUrl;
    
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $dataUrl;
    
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $featureListUrl;
    
    public function __construct() {
        $this->boundingBoxes = new ArrayCollection();
        $this->metadataUrl = new ArrayCollection();
        $this->dataUrl = new ArrayCollection();
        $this->featureListUrl = new ArrayCollection();
        $this->styles = new ArrayCollection();
        $this->srs = array();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set parent
     *
     * @param Object $parent
     * @return WmsLayerSource
     */
    public function setParent(WmsLayerSource $parent) {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Get parent
     *
     * @return Object 
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return WmsLayerSource
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return WmsLayerSource
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set abstract
     *
     * @param string $abstract
     * @return WmsLayerSource
     */
    public function setAbstract($abstract) {
        $this->abstract = $abstract;
        return $this;
    }

    /**
     * Get abstract
     *
     * @return string 
     */
    public function getAbstract() {
        return $this->abstract;
    }

    /**
     * Set queryable
     *
     * @param boolean $queryable
     * @return WmsLayerSource
     */
    public function setQueryable($queryable) {
        $this->queryable = $queryable;
        return $this;
    }

    /**
     * Get queryable
     *
     * @return boolean 
     */
    public function getQueryable() {
        return $this->queryable;
    }

    /**
     * Set cascaded
     *
     * @param integer $cascaded
     * @return WmsLayerSource
     */
    public function setCascaded($cascaded) {
        $this->cascaded = $cascaded;
        return $this;
    }

    /**
     * Get cascaded
     *
     * @return integer 
     */
    public function getCascaded() {
        return $this->cascaded;
    }

    /**
     * Set opaque
     *
     * @param boolean $opaque
     * @return WmsLayerSource
     */
    public function setOpaque($opaque) {
        $this->opaque = $opaque;
        return $this;
    }

    /**
     * Get opaque
     *
     * @return boolean 
     */
    public function getOpaque() {
        return $this->opaque;
    }

    /**
     * Set noSubset
     *
     * @param boolean $noSubset
     * @return WmsLayerSource
     */
    public function setNoSubset($noSubset) {
        $this->noSubset = $noSubset;
        return $this;
    }

    /**
     * Get noSubset
     *
     * @return boolean 
     */
    public function getNoSubset() {
        return $this->noSubset;
    }

    /**
     * Set fixedWidth
     *
     * @param integer $fixedWidth
     * @return WmsLayerSource
     */
    public function setFixedWidth($fixedWidth) {
        $this->fixedWidth = $fixedWidth;
        return $this;
    }

    /**
     * Get fixedWidth
     *
     * @return integer 
     */
    public function getFixedWidth() {
        return $this->fixedWidth;
    }

    /**
     * Set fixedHeight
     *
     * @param integer $fixedHeight
     * @return WmsLayerSource
     */
    public function setFixedHeight($fixedHeight) {
        $this->fixedHeight = $fixedHeight;
        return $this;
    }

    /**
     * Get fixedHeight
     *
     * @return integer 
     */
    public function getFixedHeight() {
        return $this->fixedHeight;
    }

    /**
     * Set latlonBounds
     *
     * @param BoundingBox $latlonBounds
     * @return WmsLayerSource
     */
    public function setLatlonBounds(BoundingBox $latlonBounds) {
        $this->latlonBounds = $latlonBounds;
        return $this;
    }

    /**
     * Get latlonBounds
     *
     * @return Object 
     */
    public function getLatlonBounds() {
        return $this->latlonBounds;
    }
    
    /**
     * Add boundingBox
     *
     * @param BoundingBox $boundingBoxes
     * @return WmsLayerSource
     */
    public function addBoundingBox(BoundingBox $boundingBoxes) {
        $this->boundingBoxes->add($boundingBoxes);
        return $this;
    }

    /**
     * Set boundingBoxes
     *
     * @param array $boundingBoxes
     * @return WmsLayerSource
     */
    public function setBoundingBoxes($boundingBoxes) {
        $this->boundingBoxes = $boundingBoxes;
        return $this;
    }

    /**
     * Get boundingBoxes
     *
     * @return array 
     */
    public function getBoundingBoxes() {
        return $this->boundingBoxes;
    }
    
    /**
     * Set srs
     *
     * @param array $srs
     * @return WmsLayerSource
     */
    public function setSrs($srs) {
        $this->srs = $srs;
        return $this;
    }
    
    /**
     * Add srs
     *
     * @param string $srs
     * @return WmsLayerSource
     */
    public function addSrs($srs) {
        $this->srs[] = $srs;
        return $this;
    }

    /**
     * Get srs incl. from parent WmsLayerSource (OGC WMS 
     * Implemantation Specification)
     *
     * @return array 
     */
    public function getSrs() {
//        return $this->srs;
        if($this->getParent() !== null){ // add crses from parent
            return array_merge(
                    $this->getParent()->getSrs(),
                    $this->getStyles());
        } else {
            $this->getStyles();
        }
    }
    
    /**
     * Add style
     *
     * @param Style $style
     * @return WmsLayerSource
     */
    public function addStyle(Style $style) {
        $this->styles->add($style);
        return $this;
    }

    /**
     * Set styles
     *
     * @param array $styles
     * @return WmsLayerSource
     */
    public function setStyles($styles) {
        $this->styles = $styles;
        return $this;
    }

    /**
     * Get styles incl. from parent WmsLayerSource (OGC WMS 
     * Implemantation Specification)
     *
     * @return array 
     */
    public function getStyles() {
        if($this->getParent() !== null){ // add styles from parent
            return new ArrayCollection(array_merge(
                    $this->getParent()->getStyles()->toArray(),
                    $this->getStyles()->toArray()));
        } else {
            $this->getStyles();
        }
    }

    /**
     * Set minScale
     *
     * @param float $minScale
     * @return WmsLayerSource
     */
    public function setMinScale($minScale) {
        $this->minScale = $minScale;
        return $this;
    }

    /**
     * Get minScale
     *
     * @return float 
     */
    public function getMinScale() {
        return $this->minScale;
    }

    /**
     * Set maxScale
     *
     * @param float $maxScale
     * @return WmsLayerSource
     */
    public function setMaxScale($maxScale) {
        $this->maxScale = $maxScale;
        return $this;
    }

    /**
     * Get maxScale
     *
     * @return float 
     */
    public function getMaxScale() {
        return $this->maxScale;
    }

    /**
     * Set attribution
     *
     * @param Attribution $attribution
     * @return WmsLayerSource
     */
    public function setAttribution(Attribution $attribution) {
        $this->attribution = $attribution;
        return $this;
    }

    /**
     * Get attribution
     *
     * @return Object 
     */
    public function getAttribution() {
        return $this->attribution;
    }

    /**
     * Set identifier
     *
     * @param Identifier $identifier
     * @return WmsLayerSource
     */
    public function setIdentifier(Identifier $identifier) {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Get identifier
     *
     * @return Identifier 
     */
    public function getIdentifier() {
        return $this->identifier;
    }
    
    /**
     * Add metadataUrl
     *
     * @param array $metadataUrl
     * @return WmsLayerSource
     */
    public function addMetadataUrl(MetadataUrl $metadataUrl) {
        $this->metadataUrl->add($metadataUrl);
        return $this;
    }

    /**
     * Set metadataUrl
     *
     * @param array $metadataUrl
     * @return WmsLayerSource
     */
    public function setMetadataUrl($metadataUrl) {
        $this->metadataUrl = $metadataUrl;
        return $this;
    }

    /**
     * Get metadataUrl
     *
     * @return array 
     */
    public function getMetadataUrl() {
        return $this->metadataUrl;
    }
    
    /**
     * Add dataUrl
     *
     * @param array $dataUrl
     * @return WmsLayerSource
     */
    public function addDataUrl(OnlineResource $dataUrl) {
        $this->dataUrl->add($dataUrl);
        return $this;
    }

    /**
     * Set dataUrl
     *
     * @param array $dataUrl
     * @return WmsLayerSource
     */
    public function setDataUrl($dataUrl) {
        $this->dataUrl = $dataUrl;
        return $this;
    }

    /**
     * Get dataUrl
     *
     * @return array 
     */
    public function getDataUrl() {
        return $this->dataUrl;
    }

    /**
     * Add featureListUrl
     *
     * @param array $featureListUrl
     * @return WmsLayerSource
     */
    public function addFeatureListUrl(OnlineResource $featureListUrl) {
        $this->featureListUrl->add($featureListUrl);
        return $this;
    }

    /**
     * Set featureListUrl
     *
     * @param array $featureListUrl
     * @return WmsLayerSource
     */
    public function setFeatureListUrl($featureListUrl) {
        $this->featureListUrl = $featureListUrl;
        return $this;
    }

    /**
     * Get featureListUrl
     *
     * @return array 
     */
    public function getFeatureListUrl() {
        return $this->featureListUrl;
    }
    
    public function __toString(){
        return (string) $this->getId();
    }
}