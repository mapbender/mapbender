<?php
namespace Mapbender\WmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;
use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\CoreBundle\Entity\HttpParsedSource;
use Mapbender\CoreBundle\Entity\Keyword;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmsBundle\Component\Dimension;
use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Component\RequestInformation;
use Mapbender\WmsBundle\WmsDataSource;


/**
 * A WmsSource entity presents an OGC WMS.
 */
#[ORM\Entity]
#[ORM\Table(name: 'mb_wms_wmssource')]
class WmsSource extends HttpParsedSource
    implements ContainingKeyword, MutableUrlTarget
{
    /**
     * @var string A WMS name
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected $name = "";

    /**
     * @var string A WMS version
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected $version = "";

    /**
     * @var string A WMS online resource
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected $onlineResource;

    /**
     * @var Contact A contact.
     */
    #[ORM\OneToOne(targetEntity: Contact::class, cascade: ['persist', 'remove'])]
    protected $contact;

    /**
     * @var string A fees.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected $fees = "";

    /**
     * @var string An access constraints.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected $accessConstraints = "";

    /**
     * @var integer A limit of the layers
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected $layerLimit;

    /**
     * @var integer A maximum width of the GetMap image
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected $maxWidth;

    /**
     * @var integer A maximum height of the GetMap image
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected $maxHeight;

    /**
     * @var array A list of supported exception formats
     */
    #[ORM\Column(type: 'array', nullable: true)]
    protected $exceptionFormats = array();

    /**
     * @var boolean A SLD support
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    protected $supportSld = false;

    /**
     * @var boolean A user layer
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    protected $userLayer = false;

    /**
     * @var boolean A user layer
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    protected $userStyle = false;

    /**
     * @var boolean A remote WFS
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    protected $remoteWfs = false;

    /**
     * @var boolean A inline feature
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    protected $inlineFeature = false;

    /**
     * @var boolean A remote WCS
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    protected $remoteWcs = false;

    /**
     * @var RequestInformation A request information for the GetCapabilities operation
     */
    #[ORM\Column(type: 'object', nullable: true)]
    protected $getCapabilities = null;

    /**
     * @var RequestInformation A request information for the GetMap operation
     */
    #[ORM\Column(type: 'object', nullable: true)]
    protected $getMap = null;

    /**
     * @var RequestInformation A request information for the GetFeatureInfo operation
     */
    #[ORM\Column(type: 'object', nullable: true)]
    protected $getFeatureInfo = null;

    /**
     * @var RequestInformation A request information for the DescribeLayer operation
     */
    #[ORM\Column(type: 'object', nullable: true)]
    protected $describeLayer = null;

    /**
     * @var RequestInformation A request information for the GetLegendGraphic operation
     */
    #[ORM\Column(type: 'object', nullable: true)]
    protected $getLegendGraphic = null;

    /**
     * @var RequestInformation A request information for the GetStyles operation
     */
    #[ORM\Column(type: 'object', nullable: true)]
    protected $getStyles = null;

    /**
     * @var RequestInformation A request information for the PutStyles operation
     */
    #[ORM\Column(type: 'object', nullable: true)]
    protected $putStyles = null;

    /**
     * @var WmsLayerSource[]|ArrayCollection A list of WMS layers
     */
    #[ORM\OneToMany(mappedBy: 'source', targetEntity: WmsLayerSource::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['priority' => 'asc', 'id' => 'asc'])]
    protected $layers;

    /**
     * @var ArrayCollection A list of WMS keywords
     */
    #[ORM\OneToMany(mappedBy: 'reference', targetEntity: WmsSourceKeyword::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['value' => 'asc'])]
    protected $keywords;

    /**
     * @var ArrayCollection A list of WMS instances
     */
    #[ORM\OneToMany(mappedBy: 'source', targetEntity: WmsInstance::class, cascade: ['remove'])]
    protected $instances;

    /**
     * WmsSource constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setType(WmsDataSource::TYPE);
        $this->instances = new ArrayCollection();
        $this->keywords = new ArrayCollection();
        $this->layers = new ArrayCollection();
        $this->exceptionFormats = array();
        $this->contact = new Contact();
    }

    /**
     * Set name
     *
     * @param string $name
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @param Contact $contact
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setSupportSld($supportSld)
    {
        $this->supportSld = (bool) $supportSld;
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
     * @return $this
     */
    public function setUserLayer($userLayer)
    {
        $this->userLayer = (bool) $userLayer;
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
     * @return $this
     */
    public function setUserStyle($userStyle)
    {
        $this->userStyle = (bool) $userStyle;
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
     * @return $this
     */
    public function setRemoteWfs($remoteWfs = null)
    {
        $this->remoteWfs = (bool) $remoteWfs;
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
     * @return $this
     */
    public function setInlineFeature($inlineFeature = null)
    {
        $this->inlineFeature = (bool) $inlineFeature;
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
     * @return $this
     */
    public function setRemoteWcs($remoteWcs)
    {
        $this->remoteWcs = (bool) $remoteWcs;
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
     * @param RequestInformation|null $getCapabilities
     * @return $this
     */
    public function setGetCapabilities(?RequestInformation $getCapabilities = NULL)
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
     * @param RequestInformation|null $getMap
     * @return $this
     */
    public function setGetMap(?RequestInformation $getMap = NULL)
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
     * @param RequestInformation|null $getFeatureInfo
     * @return $this
     */
    public function setGetFeatureInfo(?RequestInformation $getFeatureInfo = NULL)
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
     * @param RequestInformation|null $describeLayer
     * @return $this
     */
    public function setDescribeLayer(?RequestInformation $describeLayer = NULL)
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
     * @param RequestInformation|null $getLegendGraphic
     * @return $this
     */
    public function setGetLegendGraphic(?RequestInformation $getLegendGraphic = NULL)
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
     * @param RequestInformation|null $getStyles
     * @return $this
     */
    public function setGetStyles(?RequestInformation $getStyles = NULL)
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
     * @param RequestInformation|null $putStyles
     * @return $this
     */
    public function setPutStyles(?RequestInformation $putStyles = NULL)
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
     * Set layers
     *
     * @param array $layers
     * @return $this
     */
    public function setLayers($layers)
    {
        $this->layers = $layers;
        return $this;
    }

    /**
     * Get layers
     *
     * @return Collection|WmsLayerSource[]
     */
    public function getLayers(): Collection|array
    {
        return $this->layers;
    }

    /**
     * Add layer
     *
     * @param WmsLayerSource $layer
     * @return $this
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
     * @param Collection $keywords
     * @return Source
     */
    public function setKeywords(Collection $keywords)
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * @return Collection
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
     * @return Collection|WmsInstance[]
     */
    public function getInstances(): Collection|array
    {
        return $this->instances;
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
     * @return Dimension[]
     */
    public function getDimensions()
    {
        $dimensions = array();
        $uniqueNames = array();
        foreach ($this->getLayers() as $layer) {
            foreach ($layer->getDimension() as $dimension) {
                if (!in_array($dimension->getName(), $uniqueNames)) {
                    $uniqueNames[] = $dimension->getName();
                    $dimensions[] = $dimension;
                }
            }
        }
        return $dimensions;
    }

    /**
     * @return DimensionInst[]
     */
    public function dimensionInstancesFactory()
    {
        $dimensions = array();
        foreach ($this->getDimensions() as $dimension) {
            $dimensions[] = DimensionInst::fromDimension($dimension);
        }
        return $dimensions;
    }

    public function mutateUrls(OneWayTransformer $transformer)
    {
        $this->setOriginUrl($transformer->process($this->getOriginUrl()));

        if ($onlineResource = $this->getOnlineResource()) {
            $this->setOnlineResource($transformer->process($onlineResource));
        }

        if ($requestInfo = $this->getGetMap()) {
            $requestInfo->mutateUrls($transformer);
            $this->setGetMap(clone $requestInfo);
        }
        if ($requestInfo = $this->getGetFeatureInfo()) {
            $requestInfo->mutateUrls($transformer);
            $this->setGetFeatureInfo(clone $requestInfo);
        }
        if ($requestInfo = $this->getGetCapabilities()) {
            $requestInfo->mutateUrls($transformer);
            $this->setGetCapabilities(clone $requestInfo);
        }

        if ($requestInfo = $this->getDescribeLayer()) {
            $requestInfo->mutateUrls($transformer);
            $this->setDescribeLayer(clone $requestInfo);
        }
        if ($requestInfo = $this->getGetLegendGraphic()) {
            $requestInfo->mutateUrls($transformer);
            $this->setGetLegendGraphic(clone $requestInfo);
        }
        if ($requestInfo = $this->getGetStyles()) {
            $requestInfo->mutateUrls($transformer);
            $this->setGetStyles(clone $requestInfo);
        }
        if ($requestInfo = $this->getPutStyles()) {
            $requestInfo->mutateUrls($transformer);
            $this->setPutStyles(clone $requestInfo);
        }

        $layers = $this->getLayers();
        foreach ($layers as $layer) {
            $layer->mutateUrls($transformer);
        }

        return $this;
    }
}
