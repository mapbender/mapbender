<?php
namespace Mapbender\WmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\WmsBundle\Entity\GroupLayer;

/**
 * @ORM\Entity
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
     * @ORM\Column(type="object", nullable=true)
     */
    protected $parent = null;

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
     * @ORM\Column(type="string",nullable=true)
     */
    protected $identifier = '';
    
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $metadataUrl;
    

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
     * Set parent
     *
     * @param Object $parent
     * @return WmsLayerSource
     */
    public function setParent(\Object $parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Get parent
     *
     * @return Object 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return WmsLayerSource
     */
    public function setName($name)
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
     * Set abstract
     *
     * @param string $abstract
     * @return WmsLayerSource
     */
    public function setAbstract($abstract)
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
        $this->queryable = $queryable;
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
    public function setFixedWidth($fixedWidth)
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
    public function setFixedHeight($fixedHeight)
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
     * @param Object $latlonBounds
     * @return WmsLayerSource
     */
    public function setLatlonBounds(\Object $latlonBounds)
    {
        $this->latlonBounds = $latlonBounds;
        return $this;
    }

    /**
     * Get latlonBounds
     *
     * @return Object 
     */
    public function getLatlonBounds()
    {
        return $this->latlonBounds;
    }

    /**
     * Set boundingBoxes
     *
     * @param array $boundingBoxes
     * @return WmsLayerSource
     */
    public function setBoundingBoxes($boundingBoxes)
    {
        $this->boundingBoxes = $boundingBoxes;
        return $this;
    }

    /**
     * Get boundingBoxes
     *
     * @return array 
     */
    public function getBoundingBoxes()
    {
        return $this->boundingBoxes;
    }

    /**
     * Set minScale
     *
     * @param float $minScale
     * @return WmsLayerSource
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
     * @return WmsLayerSource
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
     * Set attribution
     *
     * @param Object $attribution
     * @return WmsLayerSource
     */
    public function setAttribution(\Object $attribution)
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
     * Set identifier
     *
     * @param string $identifier
     * @return WmsLayerSource
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Get identifier
     *
     * @return string 
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set metadataUrl
     *
     * @param array $metadataUrl
     * @return WmsLayerSource
     */
    public function setMetadataUrl($metadataUrl)
    {
        $this->metadataUrl = $metadataUrl;
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
}