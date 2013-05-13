<?php

namespace Mapbender\WmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\Utils;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\CoreBundle\Entity\Keyword;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmsBundle\Component\RequestInformation;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A WmsSource entity presents an OGC WMS.
 * @ORM\Entity
 * @ORM\Table(name="mb_wms_wmssource")
 * ORM\DiscriminatorMap({"mb_wms_wmssource" = "WmsSource"})
 */
class WmsSource extends Source
{

    /**
     * @var string An origin WMS URL
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Url()
     */
    protected $originUrl = "";

    /**
     * @var string A WMS name
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name = "";

    /**
     * @var string A WMS version
     * @ORM\Column(type="string", nullable=true)
     */
    protected $version = "";

    /**
     * @var string A WMS online resource
     * @ORM\Column(type="string",nullable=true)
     */
    protected $onlineResource;

    /**
     * @var Contact A contact.
     * @ORM\OneToOne(targetEntity="Mapbender\CoreBundle\Entity\Contact", cascade={"persist","remove"})
     */
    protected $contact;

    /**
     * @var string A fees.
     * @ORM\Column(type="text", nullable=true)
     */
    protected $fees = "";

    /**
     * @var string An access constraints.
     * @ORM\Column(type="text",nullable=true)
     */
    protected $accessConstraints = "";

    /**
     * @var integer A limit of the layers
     * @ORM\Column(type="integer",nullable=true)
     */
    protected $layerLimit;

    /**
     * @var integer A maximum width of the GetMap image
     * @ORM\Column(type="integer",nullable=true)
     */
    protected $maxWidth;

    /**
     * @var integer A maximum height of the GetMap image
     * @ORM\Column(type="integer",nullable=true)
     */
    protected $maxHeight;

    /**
     * @var array A list of supported exception formats
     * @ORM\Column(type="array",nullable=true)
     */
    protected $exceptionFormats = array();

    /**
     * @var boolean A SLD support
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $supportSld = false;

    /**
     * @var boolean A user layer
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $userLayer = false;

    /**
     * @var boolean A user layer
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $userStyle = false;

    /**
     * @var boolean A remote WFS
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $remoteWfs = false;

    /**
     * @var boolean A inline feature
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $inlineFeature = false;

    /**
     * @var boolean A remote WCS
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $remoteWcs = false;

    /**
     * @var RequestInformation A request information for the GetCapabilities operation
     * @ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $getCapabilities = null;

    /**
     * @var RequestInformation A request information for the GetMap operation
     * @ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $getMap = null;

    /**
     * @var RequestInformation A request information for the GetFeatureInfo operation
     * @ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $getFeatureInfo = null;

    /**
     * @var RequestInformation A request information for the DescribeLayer operation
     * @ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $describeLayer = null;

    /**
     * @var RequestInformation A request information for the GetLegendGraphic operation
     * @ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $getLegendGraphic = null;

    /**
     * @var RequestInformation A request information for the GetStyles operation
     * @ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $getStyles = null;

    /**
     * @var RequestInformation A request information for the PutStyles operation
     * @ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $putStyles = null;

    /**
     * @var RequestInformation A request information for the PutStyles operation
     * @ORM\Column(type="string", nullable=true);
     */
    protected $username = null;

    /**
     * @var string A password
     * @ORM\Column(type="string", nullable=true);
     */
    protected $password = null;

    /**
     * @var ArrayCollections A list of WMS layers
     * @ORM\OneToMany(targetEntity="WmsLayerSource",mappedBy="source", cascade={"persist","remove"})
     * @ORM\OrderBy({"id" = "asc"})
     */
    protected $layers;

    // FIXME: keywords cascade remove RM\OneToMany(targetEntity="Mapbender\CoreBundle\Entity\Keyword",mappedBy="id", cascade={"persist","remove"})

    /**
     * @var ArrayCollections A list of WMS keywords
     * @ORM\OneToMany(targetEntity="Mapbender\CoreBundle\Entity\Keyword",mappedBy="id", cascade={"persist"})
     */
    protected $keywords;

    /**
     * @var ArrayCollections A list of WMS instances
     * @ORM\OneToMany(targetEntity="WmsInstance",mappedBy="source", cascade={"persist","remove"})
     * 
     */
    protected $wmsinstance;

    public function __construct()
    {
        $this->keywords = new ArrayCollection();
        $this->layers = new ArrayCollection();
        $this->exceptionFormats = array();
    }

    public function getType()
    {
        return "WMS";
    }

    public function getManagertype()
    {
        return "wms";
    }

    /**
     * Set originUrl
     *
     * @param string $originUrl
     * @return WmsSource
     */
    public function setOriginUrl($originUrl)
    {
        $this->originUrl = $originUrl;
        return $this;
    }

    /**
     * Get originUrl
     *
     * @return string 
     */
    public function getOriginUrl()
    {
        return $this->originUrl;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return WmsSource
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
     * Set version
     *
     * @param string $version
     * @return WmsSource
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Get version
     *
     * @return string 
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set onlineResource
     *
     * @param string $onlineResource
     * @return WmsSource
     */
    public function setOnlineResource($onlineResource)
    {
        $this->onlineResource = $onlineResource;
        return $this;
    }

    /**
     * Get onlineResource
     *
     * @return string 
     */
    public function getOnlineResource()
    {
        return $this->onlineResource;
    }

    /**
     * Set contact
     *
     * @param string $contact
     * @return WmsSource
     */
    public function setContact($contact)
    {
        $this->contact = $contact;
        return $this;
    }

    /**
     * Get contact
     *
     * @return string 
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set fees
     *
     * @param text $fees
     * @return WmsSource
     */
    public function setFees($fees)
    {
        $this->fees = $fees;
        return $this;
    }

    /**
     * Get fees
     *
     * @return text 
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * Set accessConstraints
     *
     * @param text $accessConstraints
     * @return WmsSource
     */
    public function setAccessConstraints($accessConstraints)
    {
        $this->accessConstraints = $accessConstraints;
        return $this;
    }

    /**
     * Get accessConstraints
     *
     * @return text 
     */
    public function getAccessConstraints()
    {
        return $this->accessConstraints;
    }

    /**
     * Set layerLimit
     *
     * @param integer $layerLimit
     * @return WmsSource
     */
    public function setLayerLimit($layerLimit)
    {
        $this->layerLimit = $layerLimit;
        return $this;
    }

    /**
     * Get layerLimit
     *
     * @return integer 
     */
    public function getLayerLimit()
    {
        return $this->layerLimit;
    }

    /**
     * Set maxWidth
     *
     * @param integer $maxWidth
     * @return WmsSource
     */
    public function setMaxWidth($maxWidth)
    {
        $this->maxWidth = $maxWidth;
        return $this;
    }

    /**
     * Get maxWidth
     *
     * @return integer 
     */
    public function getMaxWidth()
    {
        return $this->maxWidth;
    }

    /**
     * Set maxHeight
     *
     * @param integer $maxHeight
     * @return WmsSource
     */
    public function setMaxHeight($maxHeight)
    {
        $this->maxHeight = $maxHeight;
        return $this;
    }

    /**
     * Get maxHeight
     *
     * @return integer 
     */
    public function getMaxHeight()
    {
        return $this->maxHeight;
    }

    /**
     * Set exceptionFormats
     *
     * @param array $exceptionFormats
     * @return WmsSource
     */
    public function setExceptionFormats($exceptionFormats)
    {
        $this->exceptionFormats = $exceptionFormats;
        return $this;
    }

    /**
     * Add exceptionFormat
     *
     * @param array $exceptionFormat
     * @return WmsSource
     */
    public function addExceptionFormat($exceptionFormat)
    {
        $this->exceptionFormats[] = $exceptionFormat;
        return $this;
    }

    /**
     * Get exceptionFormats
     *
     * @return array 
     */
    public function getExceptionFormats()
    {
        return $this->exceptionFormats;
    }

    /**
     * Set supportSld
     *
     * @param boolean $supportSld
     * @return WmsSource
     */
    public function setSupportSld($supportSld)
    {
        $this->supportSld = $supportSld;
        return $this;
    }

    /**
     * Get supportSld
     *
     * @return boolean 
     */
    public function getSupportSld()
    {
        return $this->supportSld;
    }

    /**
     * Set userLayer
     *
     * @param boolean $userLayer
     * @return WmsSource
     */
    public function setUserLayer($userLayer)
    {
        $this->userLayer = $userLayer;
        return $this;
    }

    /**
     * Get userLayer
     *
     * @return boolean 
     */
    public function getUserLayer()
    {
        return $this->userLayer;
    }

    /**
     * Set userStyle
     *
     * @param boolean $userStyle
     * @return WmsSource
     */
    public function setUserStyle($userStyle)
    {
        $this->userStyle = $userStyle;
        return $this;
    }

    /**
     * Get userStyle
     *
     * @return boolean 
     */
    public function getUserStyle()
    {
        return $this->userStyle;
    }

    /**
     * Set remoteWfs
     *
     * @param boolean $remoteWfs
     * @return WmsSource
     */
    public function setRemoteWfs($remoteWfs)
    {
        $this->remoteWfs = $remoteWfs;
        return $this;
    }

    /**
     * Get remoteWfs
     *
     * @return boolean 
     */
    public function getRemoteWfs()
    {
        return $this->remoteWfs;
    }

    /**
     * Set inlineFeature
     *
     * @param boolean $inlineFeature
     * @return WmsSource
     */
    public function setInlineFeature($inlineFeature)
    {
        $this->inlineFeature = $inlineFeature;
        return $this;
    }

    /**
     * Get inlineFeature
     *
     * @return boolean 
     */
    public function getInlineFeature()
    {
        return $this->inlineFeature;
    }

    /**
     * Set remoteWcs
     *
     * @param boolean $remoteWcs
     * @return WmsSource
     */
    public function setRemoteWcs($remoteWcs)
    {
        $this->remoteWcs = $remoteWcs;
        return $this;
    }

    /**
     * Get remoteWcs
     *
     * @return boolean 
     */
    public function getRemoteWcs()
    {
        return $this->remoteWcs;
    }

    /**
     * Set getCapabilities
     *
     * @param Object $getCapabilities
     * @return WmsSource
     */
    public function setGetCapabilities(RequestInformation $getCapabilities)
    {
        $this->getCapabilities = $getCapabilities;
        return $this;
    }

    /**
     * Get getCapabilities
     *
     * @return Object 
     */
    public function getGetCapabilities()
    {
        return $this->getCapabilities;
    }

    /**
     * Set getMap
     *
     * @param RequestInformation $getMap
     * @return WmsSource
     */
    public function setGetMap(RequestInformation $getMap)
    {
        $this->getMap = $getMap;
        return $this;
    }

    /**
     * Get getMap
     *
     * @return Object 
     */
    public function getGetMap()
    {
        return $this->getMap;
    }

    /**
     * Set getFeatureInfo
     *
     * @param RequestInformation $getFeatureInfo
     * @return WmsSource
     */
    public function setGetFeatureInfo(RequestInformation $getFeatureInfo)
    {
        $this->getFeatureInfo = $getFeatureInfo;
        return $this;
    }

    /**
     * Get getFeatureInfo
     *
     * @return Object 
     */
    public function getGetFeatureInfo()
    {
        return $this->getFeatureInfo;
    }

    /**
     * Set describeLayer
     *
     * @param RequestInformation $describeLayer
     * @return WmsSource
     */
    public function setDescribeLayer(RequestInformation $describeLayer)
    {
        $this->describeLayer = $describeLayer;
        return $this;
    }

    /**
     * Get describeLayer
     *
     * @return Object 
     */
    public function getDescribeLayer()
    {
        return $this->describeLayer;
    }

    /**
     * Set getLegendGraphic
     *
     * @param RequestInformation $getLegendGraphic
     * @return WmsSource
     */
    public function setGetLegendGraphic(RequestInformation $getLegendGraphic)
    {
        $this->getLegendGraphic = $getLegendGraphic;
        return $this;
    }

    /**
     * Get getLegendGraphic
     *
     * @return Object 
     */
    public function getGetLegendGraphic()
    {
        return $this->getLegendGraphic;
    }

    /**
     * Set getStyles
     *
     * @param RequestInformation $getStyles
     * @return WmsSource
     */
    public function setGetStyles(RequestInformation $getStyles)
    {
        $this->getStyles = $getStyles;
        return $this;
    }

    /**
     * Get getStyles
     *
     * @return Object 
     */
    public function getGetStyles()
    {
        return $this->getStyles;
    }

    /**
     * Set putStyles
     *
     * @param RequestInformation $putStyles
     * @return WmsSource
     */
    public function setPutStyles(RequestInformation $putStyles)
    {
        $this->putStyles = $putStyles;
        return $this;
    }

    /**
     * Get putStyles
     *
     * @return Object 
     */
    public function getPutStyles()
    {
        return $this->putStyles;
    }

    /**
     * Set username
     *
     * @param text $username
     * @return WmsSource
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Get username
     *
     * @return text 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param text $password
     * @return WmsSource
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Get password
     *
     * @return text 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set layers
     *
     * @param array $layers
     * @return WmsSource
     */
    public function setLayers($layers)
    {
        $this->layers = $layers;
        return $this;
    }

    /**
     * Get layers
     *
     * @return array 
     */
    public function getLayers()
    {
        return $this->layers;
    }

    /**
     * Add layer
     *
     * @param WmsLayerSource $layer
     * @return WmsSource
     */
    public function addLayer(WmsLayerSource $layer)
    {
        $this->layers->add($layer);
        return $this;
    }

    /**
     * Get root layer
     *
     * @return WmsLayerSource 
     */
    public function getRootlayer()
    {
        foreach($this->layers as $layer)
        {
            if($layer->getParent() === null)
            {
                return $layer;
            }
        }
        return null;
//        if($this->layers !== null && $this->layers->count() > 0){
//            return $this->layers->get(0);
//        } else {
//            return null;
//        }
    }

    /**
     * Set keywords
     *
     * @param array $keywords
     * @return Source
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * Get keywords
     *
     * @return string 
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Add keyword
     *
     * @param Keyword $keyword
     * @return Source
     */
    public function addKeyword(Keyword $keyword)
    {
        $this->keywords->add($keyword);
        return $this;
    }

    /**
     * Remove layers
     *
     * @param WmsLayerSource $layers
     */
    public function removeLayer(WmsLayerSource $layers)
    {
        $this->layers->removeElement($layers);
    }

    /**
     * Remove keywords
     *
     * @param Keyword $keywords
     */
    public function removeKeyword(Keyword $keywords)
    {
        $this->keywords->removeElement($keywords);
    }

    public function __toString()
    {
        return (string) $this->getId();
    }

    /**
     * @inheritdoc
     */
    public function createInstance()
    {
        $instance = new WmsInstance();
        $instance->setSource($this);
        $instance->setTitle($this->getTitle());
        $formats = $this->getGetMap()->getFormats();
        $instance->setFormat(count($formats) > 0 ? $formats[0] : null);
        $infoformats = $this->getGetFeatureInfo() !== null ?
                $this->getGetFeatureInfo()->getFormats() : array();
        $instance->setInfoformat(count($infoformats) > 0 ? $infoformats[0] : null);
        $excformats = $this->getExceptionFormats();
        $instance->setExceptionformat(count($excformats) > 0 ? $excformats[0] : null);
//        $instance->setOpacity(100);
        $num = 0;
        $wmslayer_root = $this->getRootlayer();
        $instLayer_root = new WmsInstanceLayer();
        $instLayer_root->setWmsinstance($instance);
        $instLayer_root->setWmslayersource($wmslayer_root);
        $instLayer_root->setTitle($wmslayer_root->getTitle());
        // @TODO min max from scaleHint
        $instLayer_root->setMinScale(
                $wmslayer_root->getScaleRecursive() !== null ?
                        $wmslayer_root->getScaleRecursive()->getMin() : null);
        $instLayer_root->setMaxScale(
                $wmslayer_root->getScaleRecursive() !== null ?
                        $wmslayer_root->getScaleRecursive()->getMax() : null);
        $queryable = $wmslayer_root->getQueryable();
        $instLayer_root->setInfo(Utils::getBool($queryable));
        $instLayer_root->setAllowinfo(Utils::getBool($queryable));

        $instLayer_root->setToggle(true);
        $instLayer_root->setAllowtoggle(true);
        
        $instLayer_root->setPriority($num);
        $instance->addLayer($instLayer_root);
        $this->addSublayer($instLayer_root, $wmslayer_root, $num, $instance);
        return $instance;
    }

    /**
     * Adds sublayers
     * 
     * @param WmsInstanceLayer $instlayer
     * @param WmsLayerSource $wmslayer
     * @param integer $num
     * @param WmsIstance $instance
     */
    private function addSublayer($instlayer, $wmslayer, $num, $instance)
    {
        foreach($wmslayer->getSublayer() as $wmssublayer)
        {
            $num++;
            $instsublayer = new WmsInstanceLayer();
            $instsublayer->setWmsinstance($instance);
            $instsublayer->setWmslayersource($wmssublayer);
            $instsublayer->setTitle($wmssublayer->getTitle());
            // @TODO min max from scaleHint
            $instsublayer->setMinScale(
                    $wmssublayer->getScaleRecursive() !== null ?
                            $wmssublayer->getScaleRecursive()->getMin() : null);
            $instsublayer->setMaxScale(
                    $wmssublayer->getScaleRecursive() !== null ?
                            $wmssublayer->getScaleRecursive()->getMax() : null);
            $queryable = $wmssublayer->getQueryable();
            $instsublayer->setInfo(Utils::getBool($queryable));
            $instsublayer->setAllowinfo(Utils::getBool($queryable));

            $instsublayer->setPriority($num);
            $instsublayer->setParent($instlayer);
            $instance->addLayer($instsublayer);
            if($wmssublayer->getSublayer()->count() > 0){
                $instsublayer->setToggle(true);
                $instsublayer->setAllowtoggle(true);
            }
            $this->addSublayer($instsublayer, $wmssublayer, $num, $instance);
        }
    }
    
    /**
     * @inheritdoc
     */
    public function remove(EntityManager $em)
    {
        $this->removeSourceRecursive($em, $this->getRootlayer());
        $em->remove($this);
    }
    
    /**
     * Recursively remove a nested Layerstructure
     * @param WmsLayerSource
     * @param EntityManager
     */
    private function removeSourceRecursive(EntityManager $em, WmsLayerSource $wmslayer)
    {
        foreach($wmslayer->getSublayer() as $sublayer)
        {
            $this->removeSourceRecursive($em, $sublayer);
        }
        $em->remove($wmslayer);
        $em->flush();
    }

}