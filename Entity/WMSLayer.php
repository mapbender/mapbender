<?php
namespace Mapbender\WmsBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\WmsBundle\Entity\GroupLayer;

/**
 * @ORM\Entity
*/
class WMSLayer extends GroupLayer {

    /**
     * @ORM\Column(type="boolean", nullable="false")
     */
    protected $queryable = false;
    
    /**
     * @ORM\Column(type="integer", nullable="false")
     */
    protected $cascaded = 0;
    
    /**
     * @ORM\Column(type="boolean", nullable="false")
     */
    protected $opaque = false;
    
    /**
     * @ORM\Column(type="boolean", nullable="false")
     */
    protected $noSubset = false;
    
    /**
     * @ORM\Column(type="integer", nullable="false")
     */
    protected $fixedWidth = 0;
    
    /**
     * @ORM\Column(type="integer", nullable="false")
     */
    protected $fixedHeight = 0;
    

    /**
     * @ORM\Column(type="array",nullable="true")
     */
    protected $srs = array();
    
    /**
     * @ORM\Column(type="array",nullable="true")
     */
    protected $latLonBounds = "180 90 -180 -90";
    
    /*  
        FIXME BoundingBox is missing
    */ 

    /**
     * @ORM\Column(type="float",nullable="true")
     */
    protected $scaleHintMin = 0;
    
    /**
     * @ORM\Column(type="float",nullable="true")
     */
    protected $scaleHintMax = 0;

    /*
        FIXME Dimension and Extent are missing
    */
    /**
     * @ORM\Column(type="string",nullable="true")
     */
    protected $metadataURL = '';
    
    /**
     * @ORM\Column(type="string",nullable="true")
     */
    protected $attributionTitle = '';
    
    /**
     * @ORM\Column(type="string",nullable="true")
     */
    protected $attributionOnlineResource = '';
    
    /**
     * @ORM\Column(type="string",nullable="true")
     */
    protected $attributionLogoURL = '';
    
    /**
     * @ORM\Column(type="integer",nullable="true")
     */
    protected $attributionLogoWidth = 0;
    
    /**
     * @ORM\Column(type="integer",nullable="true")
     */
    protected $attributionLogoHeight = 0;
    
    /**
     * @ORM\Column(type="string",nullable="true")
     */
    protected $identifier = '';
    
    /**
     * @ORM\Column(type="string",nullable="true")
     */
    protected $identifierAuthorityURL = '';
    
    /**
     * @ORM\Column(type="string",nullable="true")
     * Refers to the DataURL element
     */
    protected $requestDataGET = '';
    
    /**
     * @ORM\Column(type="array",nullable="true")
     */
    protected $requestDataFormats = '';
    
    /**
     * @ORM\Column(type="string",nullable="true")
     * Refers to the DataURL element
     */
    protected $requestFeatureListGET = '';
    
    /**
     * @ORM\Column(type="array",nullable="true")
     */
    protected $requestFeatureListFormats = '';


    /**
     * returns the WMSService a WMSLayer belongs to. This is neccessary because WMSLayer::getParent() might return a GroupLayer only
     */
    public function getWMS(){
        $layer = $this;
        // go up until layer becomes falsy
        $parent = $layer->getParent();
        while($parent != null){
            $layer = $parent;
            $parent = $layer->getParent();
        }
        return $layer;
    }
    public function setSrs($srs){
        $this->srs = $srs;
    }
    public function getSrs(){
        return $this->srs;
    }
    public function getDefaultSrs(){
        $srs = explode(',',$this->srs);
        return $srs[0] ?:"";
    }
    public function setLatLonBounds($bounds){
        $this->latLonBounds = $bounds; 
    }
    public function getLatLonBounds(){
        return $this->latLonBounds;
    }
    
    public function __construct()
    {
        $this->queryable    = false;
        $this->cascaded     = 0;
        $this->opaque       = false;
        $this->noSubset     = false;
        $this->layer        = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set queryable
     *
     * @param boolean $queryable
     */
    public function setQueryable($queryable)
    {
        $this->queryable = $queryable;
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
     */
    public function setCascaded($cascaded)
    {
        if(!is_integer($cascaded)){
            $this->cascaded = 0;
        }else{
            $this->cascaded = $cascaded;
        }
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
     */
    public function setOpaque($opaque)
    {
        $this->opaque = $opaque;
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
     */
    public function setNoSubset($noSubset)
    {
        $this->noSubset = $noSubset;
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
     */
    public function setFixedWidth($fixedWidth)
    {
        if(!is_integer($fixedWidth)){
            $this->fixedWidth = null;
        }else{
            $this->fixedWidth = $fixedWidth;
        }
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
     */
    public function setFixedHeight($fixedHeight)
    {
        if(!is_integer($fixedHeight)){
            $this->fixedHeight = null;
        }else{
            $this->fixedHeight = $fixedHeight;
        } 
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
     * Set ScaleHintMin
     *
     * @param float $scaleHintMin
     */
    public function setScaleHintMin($scaleHintMin)
    {
        $this->scaleHintMin = $scaleHintMin;
    }

    /**
     * Get ScaleHintMin
     *
     * @return float 
     */
    public function getScaleHintMin()
    {
        return $this->scaleHintMin;
    }

    /**
     * Set ScaleHintMax
     *
     * @param float $scaleHintMax
     */
    public function setScaleHintMax($scaleHintMax)
    {
        $this->scaleHintMax = $scaleHintMax;
    }

    /**
     * Get ScaleHintMax
     *
     * @return float 
     */
    public function getScaleHintMax()
    {
        return $this->scaleHintMax;
    }

    /**
     * Set metadataURL
     *
     * @param string $metadataURL
     */
    public function setMetadataURL($metadataURL)
    {
        $this->metadataURL = $metadataURL;
    }

    /**
     * Get metadataURL
     *
     * @return string 
     */
    public function getMetadataURL()
    {
        return $this->metadataURL;
    }

    /**
     * Set attributionTitle
     *
     * @param string $attributionTitle
     */
    public function setAttributionTitle($attributionTitle)
    {
        $this->attributionTitle = $attributionTitle;
    }

    /**
     * Get attributionTitle
     *
     * @return string 
     */
    public function getAttributionTitle()
    {
        return $this->attributionTitle;
    }

    /**
     * Set attributionOnlineResource
     *
     * @param string $attributionOnlineResource
     */
    public function setAttributionOnlineResource($attributionOnlineResource)
    {
        $this->attributionOnlineResource = $attributionOnlineResource;
    }

    /**
     * Get attributionOnlineResource
     *
     * @return string 
     */
    public function getAttributionOnlineResource()
    {
        return $this->attributionOnlineResource;
    }

    /**
     * Set attributionLogoURL
     *
     * @param string $attributionLogoURL
     */
    public function setAttributionLogoURL($attributionLogoURL)
    {
        $this->attributionLogoURL = $attributionLogoURL;
    }

    /**
     * Get attributionLogoURL
     *
     * @return string 
     */
    public function getAttributionLogoURL()
    {
        return $this->attributionLogoURL;
    }

    /**
     * Set attributionLogoWidth
     *
     * @param integer $attributionLogoWidth
     */
    public function setAttributionLogoWidth($attributionLogoWidth)
    {
        if(!is_integer($attributionLogoWidth)){
            $this->attributionLogoWidth = 0;
        }else{
            $this->attributionLogoWidth = $attributionLogoWidth;
        }
    }

    /**
     * Get attributionLogoWidth
     *
     * @return integer 
     */
    public function getAttributionLogoWidth()
    {
        return $this->attributionLogoWidth;
    }

    /**
     * Set attributionLogoHeight
     *
     * @param integer $attributionLogoHeight
     */
    public function setAttributionLogoHeight($attributionLogoHeight)
    {
        if(!is_integer($attributionLogoHeight)){
            $this->attributionLogoHeight = 0;
        }else{
            $this->attributionLogoHeight = $attributionLogoHeight;
        }
    }

    /**
     * Get attributionLogoHeight
     *
     * @return integer 
     */
    public function getAttributionLogoHeight()
    {
        return $this->attributionLogoHeight;
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
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
     * Set identifierAuthorityURL
     *
     * @param string $identifierAuthorityURL
     */
    public function setIdentifierAuthorityURL($identifierAuthorityURL)
    {
        $this->identifierAuthorityURL = $identifierAuthorityURL;
    }

    /**
     * Get identifierAuthorityURL
     *
     * @return string 
     */
    public function getIdentifierAuthorityURL()
    {
        return $this->identifierAuthorityURL;
    }

    /**
     * Set requestDataGET
     *
     * @param string $requestDataGET
     */
    public function setRequestDataGET($requestDataGET)
    {
        $this->requestDataGET = $requestDataGET;
    }

    /**
     * Get requestDataGET
     *
     * @return string 
     */
    public function getRequestDataGET()
    {
        return $this->requestDataGET;
    }

    /**
     * Set requestDataFormats
     *
     * @param array $requestDataFormats
     */
    public function setRequestDataFormats($requestDataFormats)
    {
        $this->requestDataFormats = $requestDataFormats;
    }

    /**
     * Get requestDataFormats
     *
     * @return array 
     */
    public function getRequestDataFormats()
    {
        return $this->requestDataFormats;
    }

    /**
     * Set requestFeatureListGET
     *
     * @param string $requestFeatureListGET
     */
    public function setRequestFeatureListGET($requestFeatureListGET)
    {
        $this->requestFeatureListGET = $requestFeatureListGET;
    }

    /**
     * Get requestFeatureListGET
     *
     * @return string 
     */
    public function getRequestFeatureListGET()
    {
        return $this->requestFeatureListGET;
    }

    /**
     * Set requestFeatureListFormats
     *
     * @param array $requestFeatureListFormats
     */
    public function setRequestFeatureListFormats($requestFeatureListFormats)
    {
        $this->requestFeatureListFormats = $requestFeatureListFormats;
    }

    /**
     * Get requestFeatureListFormats
     *
     * @return array 
     */
    public function getRequestFeatureListFormats()
    {
        return $this->requestFeatureListFormats;
    }

}
