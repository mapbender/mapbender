<?php

namespace Mapbender\WmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\WmsBundle\Component\RequestInformation;

/**
 * @ORM\Entity
 * @ORM\Table(name="mb_wms_wmssource")
 * ORM\DiscriminatorMap({"mb_wms_wmssource" = "WmsSource"})
 */
class WmsSource extends Source {
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $originUrl = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $version = "";

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $onlineResource;

    /**
     * @ORM\OneToOne(targetEntity="Mapbender\CoreBundle\Entity\Contact", cascade={"persist","remove"})
     */
    protected $contact;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $fees = "";

    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $accessConstraints = "";
    
    /**
     * @ORM\Column(type="integer",nullable=true)
     */
    protected $layerLimit;
    
    /**
     * @ORM\Column(type="integer",nullable=true)
     */
    protected $maxWidth;

    /**
     * @ORM\Column(type="integer",nullable=true)
     */
    protected $maxHeight;

    /**
     * @ORM\Column(type="array",nullable=true)
     */
    protected $exceptionFormats = array();

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $supportSld = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $userLayer = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $userStyle = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $remoteWfs = false;
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $inlineFeature = false;
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $remoteWcs = false;

    /**
     * @ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $getCapabilities = null;

    /**
     * @ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $getMap = null;

    /**
     * @ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $getFeatureInfo = null;

    /**
     * @ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $describeLayer = null;

    /**
     * @ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $getLegendGraphic = null;

    /**
     * @ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $getStyles = null;

    /**
     * @ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $putStyles = null;

    /**
     * @ORM\Column(type="text", nullable=true);
     */
    protected $username = null;

    /**
     * @ORM\Column(type="text", nullable=true);
     */
    protected $password = null;

    /**
     * @ORM\OneToMany(targetEntity="WmsLayerSource",mappedBy="layers", cascade={"persist","remove"})
     */
    protected $layers;

    public function __construct() {
        $this->layers = new ArrayCollection();
        $this->exceptionFormats = array();
    }
    
    /**
     * Set originUrl
     *
     * @param string $originUrl
     * @return WmsSource
     */
    public function setOriginUrl($originUrl) {
        $this->originUrl = $originUrl;
        return $this;
    }

    /**
     * Get originUrl
     *
     * @return string 
     */
    public function getOriginUrl() {
        return $this->originUrl;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return WmsSource
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
     * Set version
     *
     * @param string $version
     * @return WmsSource
     */
    public function setVersion($version) {
        $this->version = $version;
        return $this;
    }

    /**
     * Get version
     *
     * @return string 
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * Set onlineResource
     *
     * @param string $onlineResource
     * @return WmsSource
     */
    public function setOnlineResource($onlineResource) {
        $this->onlineResource = $onlineResource;
        return $this;
    }

    /**
     * Get onlineResource
     *
     * @return string 
     */
    public function getOnlineResource() {
        return $this->onlineResource;
    }

    /**
     * Set contact
     *
     * @param string $contact
     * @return WmsSource
     */
    public function setContact($contact) {
        $this->contact = $contact;
        return $this;
    }

    /**
     * Get contact
     *
     * @return string 
     */
    public function getContact() {
        return $this->contact;
    }

    /**
     * Set fees
     *
     * @param text $fees
     * @return WmsSource
     */
    public function setFees($fees) {
        $this->fees = $fees;
        return $this;
    }

    /**
     * Get fees
     *
     * @return text 
     */
    public function getFees() {
        return $this->fees;
    }

    /**
     * Set accessConstraints
     *
     * @param text $accessConstraints
     * @return WmsSource
     */
    public function setAccessConstraints($accessConstraints) {
        $this->accessConstraints = $accessConstraints;
        return $this;
    }

    /**
     * Get accessConstraints
     *
     * @return text 
     */
    public function getAccessConstraints() {
        return $this->accessConstraints;
    }
    
    /**
     * Set layerLimit
     *
     * @param integer $layerLimit
     * @return WmsSource
     */
    public function setLayerLimit($layerLimit) {
        $this->layerLimit = $layerLimit;
        return $this;
    }

    /**
     * Get layerLimit
     *
     * @return integer 
     */
    public function getLayerLimit() {
        return $this->layerLimit;
    }
    
    
    
    /**
     * Set maxWidth
     *
     * @param integer $maxWidth
     * @return WmsSource
     */
    public function setMaxWidth($maxWidth) {
        $this->maxWidth = $maxWidth;
        return $this;
    }

    /**
     * Get maxWidth
     *
     * @return integer 
     */
    public function getMaxWidth() {
        return $this->maxWidth;
    }
    
    /**
     * Set maxHeight
     *
     * @param integer $maxHeight
     * @return WmsSource
     */
    public function setMaxHeight($maxHeight) {
        $this->maxHeight = $maxHeight;
        return $this;
    }

    /**
     * Get maxHeight
     *
     * @return integer 
     */
    public function getMaxHeight() {
        return $this->maxHeight;
    }

    /**
     * Set exceptionFormats
     *
     * @param array $exceptionFormats
     * @return WmsSource
     */
    public function setExceptionFormats($exceptionFormats) {
        $this->exceptionFormats = $exceptionFormats;
        return $this;
    }
    
    /**
     * Add exceptionFormat
     *
     * @param array $exceptionFormat
     * @return WmsSource
     */
    public function addExceptionFormat($exceptionFormat) {
        $this->exceptionFormats[] = $exceptionFormat;
        return $this;
    }

    /**
     * Get exceptionFormats
     *
     * @return array 
     */
    public function getExceptionFormats() {
        return $this->exceptionFormats;
    }

    /**
     * Set supportSld
     *
     * @param boolean $supportSld
     * @return WmsSource
     */
    public function setSupportSld($supportSld) {
        $this->supportSld = $supportSld;
        return $this;
    }

    /**
     * Get supportSld
     *
     * @return boolean 
     */
    public function getSupportSld() {
        return $this->supportSld;
    }

    /**
     * Set userLayer
     *
     * @param boolean $userLayer
     * @return WmsSource
     */
    public function setUserLayer($userLayer) {
        $this->userLayer = $userLayer;
        return $this;
    }

    /**
     * Get userLayer
     *
     * @return boolean 
     */
    public function getUserLayer() {
        return $this->userLayer;
    }

    /**
     * Set userStyle
     *
     * @param boolean $userStyle
     * @return WmsSource
     */
    public function setUserStyle($userStyle) {
        $this->userStyle = $userStyle;
        return $this;
    }

    /**
     * Get userStyle
     *
     * @return boolean 
     */
    public function getUserStyle() {
        return $this->userStyle;
    }

    /**
     * Set remoteWfs
     *
     * @param boolean $remoteWfs
     * @return WmsSource
     */
    public function setRemoteWfs($remoteWfs) {
        $this->remoteWfs = $remoteWfs;
        return $this;
    }

    /**
     * Get remoteWfs
     *
     * @return boolean 
     */
    public function getRemoteWfs() {
        return $this->remoteWfs;
    }

    /**
     * Set inlineFeature
     *
     * @param boolean $inlineFeature
     * @return WmsSource
     */
    public function setInlineFeature($inlineFeature) {
        $this->inlineFeature = $inlineFeature;
        return $this;
    }

    /**
     * Get inlineFeature
     *
     * @return boolean 
     */
    public function getInlineFeature() {
        return $this->inlineFeature;
    }

    /**
     * Set remoteWcs
     *
     * @param boolean $remoteWcs
     * @return WmsSource
     */
    public function setRemoteWcs($remoteWcs) {
        $this->remoteWcs = $remoteWcs;
        return $this;
    }

    /**
     * Get remoteWcs
     *
     * @return boolean 
     */
    public function getRemoteWcs() {
        return $this->remoteWcs;
    }
    
    /**
     * Set getCapabilities
     *
     * @param Object $getCapabilities
     * @return WmsSource
     */
    public function setGetCapabilities(RequestInformation $getCapabilities) {
        $this->getCapabilities = $getCapabilities;
        return $this;
    }

    /**
     * Get getCapabilities
     *
     * @return Object 
     */
    public function getGetCapabilities() {
        return $this->getCapabilities;
    }

    /**
     * Set getMap
     *
     * @param RequestInformation $getMap
     * @return WmsSource
     */
    public function setGetMap(RequestInformation $getMap) {
        $this->getMap = $getMap;
        return $this;
    }

    /**
     * Get getMap
     *
     * @return Object 
     */
    public function getGetMap() {
        return $this->getMap;
    }

    /**
     * Set getFeatureInfo
     *
     * @param RequestInformation $getFeatureInfo
     * @return WmsSource
     */
    public function setGetFeatureInfo(RequestInformation $getFeatureInfo) {
        $this->getFeatureInfo = $getFeatureInfo;
        return $this;
    }

    /**
     * Get getFeatureInfo
     *
     * @return Object 
     */
    public function getGetFeatureInfo() {
        return $this->getFeatureInfo;
    }

    /**
     * Set describeLayer
     *
     * @param RequestInformation $describeLayer
     * @return WmsSource
     */
    public function setDescribeLayer(RequestInformation $describeLayer) {
        $this->describeLayer = $describeLayer;
        return $this;
    }

    /**
     * Get describeLayer
     *
     * @return Object 
     */
    public function getDescribeLayer() {
        return $this->describeLayer;
    }

    /**
     * Set getLegendGraphic
     *
     * @param RequestInformation $getLegendGraphic
     * @return WmsSource
     */
    public function setGetLegendGraphic(RequestInformation $getLegendGraphic) {
        $this->getLegendGraphic = $getLegendGraphic;
        return $this;
    }

    /**
     * Get getLegendGraphic
     *
     * @return Object 
     */
    public function getGetLegendGraphic() {
        return $this->getLegendGraphic;
    }

    /**
     * Set getStyles
     *
     * @param RequestInformation $getStyles
     * @return WmsSource
     */
    public function setGetStyles(RequestInformation $getStyles) {
        $this->getStyles = $getStyles;
        return $this;
    }

    /**
     * Get getStyles
     *
     * @return Object 
     */
    public function getGetStyles() {
        return $this->getStyles;
    }

    /**
     * Set putStyles
     *
     * @param RequestInformation $putStyles
     * @return WmsSource
     */
    public function setPutStyles(RequestInformation $putStyles) {
        $this->putStyles = $putStyles;
        return $this;
    }

    /**
     * Get putStyles
     *
     * @return Object 
     */
    public function getPutStyles() {
        return $this->putStyles;
    }

    /**
     * Set username
     *
     * @param text $username
     * @return WmsSource
     */
    public function setUsername($username) {
        $this->username = $username;
        return $this;
    }

    /**
     * Get username
     *
     * @return text 
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param text $password
     * @return WmsSource
     */
    public function setPassword($password) {
        $this->password = $password;
        return $this;
    }

    /**
     * Get password
     *
     * @return text 
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * Set layers
     *
     * @param array $layers
     * @return WmsSource
     */
    public function setLayers($layers) {
        $this->layers = $layers;
        return $this;
    }

    /**
     * Get layers
     *
     * @return array 
     */
    public function getLayers() {
        return $this->layers;
    }

    /**
     * Add layer
     *
     * @param WmsLayerSource $layer
     * @return WmsSource
     */
    public function addLayer(WmsLayerSource $layer) {
        $this->layers->add($layer);
        return $this;
    }
    
    /**
     * Get root layer
     *
     * @return WmsLayerSource 
     */
    public function getRootlayer() {
        if($this->layers !== null && $this->layers->count() > 0){
            return $this->layers->get(0);
        } else {
            return null;
        }
    }
    
    public function getType(){
        return "WMS";
    }
    
    public function __toString(){
        return (string) $this->getId();
    }

}