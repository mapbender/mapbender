<?php
namespace Mapbender\WmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\CoreBundle\Entity\Keyword;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Component\RequestInformation;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A WmsSource entity presents an OGC WMS.
 * @ORM\Entity
 * @ORM\Table(name="mb_wms_wmssource")
 * ORM\DiscriminatorMap({"mb_wms_wmssource" = "WmsSource"})
 */
class WmsSource extends Source implements ContainingKeyword
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
     * @ORM\OneToOne(targetEntity="Mapbender\CoreBundle\Entity\Contact", cascade={"remove"})
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
    protected $getCapabilities = null;

    /**
     * @var RequestInformation A request information for the GetMap operation
     * @ORM\Column(type="object", nullable=true)
     */
    protected $getMap = null;

    /**
     * @var RequestInformation A request information for the GetFeatureInfo operation
     * @ORM\Column(type="object", nullable=true)
     */
    protected $getFeatureInfo = null;

    /**
     * @var RequestInformation A request information for the DescribeLayer operation
     * @ORM\Column(type="object", nullable=true)
     */
    protected $describeLayer = null;

    /**
     * @var RequestInformation A request information for the GetLegendGraphic operation
     * @ORM\Column(type="object", nullable=true)
     */
    protected $getLegendGraphic = null;

    /**
     * @var RequestInformation A request information for the GetStyles operation
     * @ORM\Column(type="object", nullable=true)
     */
    protected $getStyles = null;

    /**
     * @var RequestInformation A request information for the PutStyles operation
     * @ORM\Column(type="object", nullable=true)
     */
    protected $putStyles = null;

    /**
     * @var string a user name
     * @ORM\Column(type="string", nullable=true);
     */
    protected $username = null;

    /**
     * @var string a user password
     * @ORM\Column(type="string", nullable=true);
     */
    protected $password = null;

    /**
     * @var WmsLayerSource[]|ArrayCollection A list of WMS layers
     * @ORM\OneToMany(targetEntity="Mapbender\WmsBundle\Entity\WmsLayerSource",mappedBy="source", cascade={"remove"})
     * @ORM\OrderBy({"priority" = "asc","id" = "asc"})
     */
    protected $layers;

    /**
     * @var ArrayCollection A list of WMS keywords
     * @ORM\OneToMany(targetEntity="WmsSourceKeyword",mappedBy="reference", cascade={"remove"})
     * @ORM\OrderBy({"value" = "asc"})
     */
    protected $keywords;

    /**
     * @var ArrayCollection A list of WMS instances
     * @ORM\OneToMany(targetEntity="Mapbender\WmsBundle\Entity\WmsInstance",mappedBy="source", cascade={"remove"})
     */
    protected $instances;

    /**
     * WmsSource constructor.
     */
    public function __construct()
    {
        parent::__construct(Source::TYPE_WMS);
        $this->keywords = new ArrayCollection();
        $this->layers = new ArrayCollection();
        $this->exceptionFormats = array();
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return parent::getType() ? parent::getType() : Source::TYPE_WMS;
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
        $this->setIdentifier($originUrl);
        return $this;
    }

    /**
     * Get originUrl
     *
     * 
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
     * 
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
     * 
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
     * 
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
     * 
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set fees
     *
     * @param string $fees
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
     * @return string
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * Set accessConstraints
     *
     * @param string $accessConstraints
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
     * @return string
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
    public function setRemoteWfs($remoteWfs = null)
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
    public function setInlineFeature($inlineFeature = null)
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
     * @param RequestInformation $getCapabilities
     * @return WmsSource
     */
    public function setGetCapabilities(RequestInformation $getCapabilities = NULL)
    {
        $this->getCapabilities = $getCapabilities;
        return $this;
    }

    /**
     * Get getCapabilities
     *
     * @return RequestInformation
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
    public function setGetMap(RequestInformation $getMap = NULL)
    {
        $this->getMap = $getMap;
        return $this;
    }

    /**
     * Get getMap
     *
     * @return RequestInformation
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
    public function setGetFeatureInfo(RequestInformation $getFeatureInfo = NULL)
    {
        $this->getFeatureInfo = $getFeatureInfo;
        return $this;
    }

    /**
     * Get getFeatureInfo
     *
     * @return RequestInformation
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
    public function setDescribeLayer(RequestInformation $describeLayer = NULL)
    {
        $this->describeLayer = $describeLayer;
        return $this;
    }

    /**
     * Get describeLayer
     *
     * @return RequestInformation
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
    public function setGetLegendGraphic(RequestInformation $getLegendGraphic = NULL)
    {
        $this->getLegendGraphic = $getLegendGraphic;
        return $this;
    }

    /**
     * Get getLegendGraphic
     *
     * @return RequestInformation
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
    public function setGetStyles(RequestInformation $getStyles = NULL)
    {
        $this->getStyles = $getStyles;
        return $this;
    }

    /**
     * Get getStyles
     *
     * @return RequestInformation
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
    public function setPutStyles(RequestInformation $putStyles = NULL)
    {
        $this->putStyles = $putStyles;
        return $this;
    }

    /**
     * Get putStyles
     *
     * @return RequestInformation
     */
    public function getPutStyles()
    {
        return $this->putStyles;
    }

    /**
     * Set username
     *
     * @param string $username
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
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
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
     * @return string
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
     * @return ArrayCollection|WmsLayerSource[]
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
        foreach ($this->layers as $layer) {
            if ($layer->getParent() === null) {
                return $layer;
            }
        }
        return null;
    }

    /**
     * Set keywords
     *
     * @param ArrayCollection $keywords
     * @return Source
     */
    public function setKeywords(ArrayCollection $keywords)
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * Get keywords
     *
     * @return ArrayCollection
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Add keyword
     *
     * @param Keyword|WmsSourceKeyword $keyword
     * @return Source
     */
    public function addKeyword(Keyword $keyword)
    {
        $this->keywords->add($keyword);
        return $this;
    }

    /**
     * Add WMS instance
     *
     * @param WmsInstance $instance
     * @return $this
     */
    public function addInstance(WmsInstance $instance)
    {
        $this->instances->add($instance);
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
     * @inheritdoc
     */
    public function getIdentifier()
    {
        return $this->identifier ? $this->identifier : $this->originUrl;
    }

    /**
     * @inheritdoc
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return DimensionInst[]
     */
    public function dimensionInstancesFactory()
    {
        $dimensions = array();
        foreach ($this->getLayers() as $layer) {
            /** @var WmsLayerSource $layer */
            foreach ($layer->getDimension() as $dimension) {
                $dim = DimensionInst::fromDimension($dimension);
                if (!in_array($dim, $dimensions)) {
                    $dimensions[] = $dim;
                }
            }
        }
        return $dimensions;
    }
}
