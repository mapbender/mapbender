<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;
use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\CoreBundle\Entity\Keyword;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmtsBundle\Component\RequestInformation;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A WmtsSource entity presents an OGC WMTS.
 * @ORM\Entity
 * @ORM\Table(name="mb_wmts_wmtssource")
 */
class WmtsSource extends Source implements ContainingKeyword, MutableUrlTarget
{
    /**
     * @var string An origin WMTS URL
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Url()
     */
    protected $originUrl = "";

    /**
     * @var string A WMTS version
     * @ORM\Column(type="string", nullable=true)
     */
    protected $version = "";

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $fees = "";

    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $accessConstraints = "";

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $serviceProviderSite = "";

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $serviceProviderName = "";

    /**
     * @var Contact
     * @ORM\OneToOne(targetEntity="Mapbender\CoreBundle\Entity\Contact", cascade={"persist", "remove"})
     */
    protected $contact;

    /**
     * @var ArrayCollection A list of WMS keywords
     * @ORM\OneToMany(targetEntity="WmtsSourceKeyword",mappedBy="reference", cascade={"remove"})
     * @ORM\OrderBy({"value" = "asc"})
     */
    protected $keywords;

    /**
     * @var RequestInformation|null
     * @ORM\Column(type="object", nullable=true)
     */
    public $getCapabilities = null;

    /**
     * @var RequestInformation|null
     * @ORM\Column(type="object", nullable=true)
     */
    public $getTile = null;

    /**
     * @var RequestInformation|null
     * @ORM\Column(type="object", nullable=true)
     */
    public $getFeatureInfo = null;

    /**
     * @var ArrayCollection A list of WMTS Theme
     * @ORM\OneToMany(targetEntity="Theme",mappedBy="source", cascade={"persist", "remove"})
     * @ORM\OrderBy({"id" = "asc"})
     */
    protected $themes;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="TileMatrixSet",mappedBy="source", cascade={"persist", "remove"})
     * @ORM\OrderBy({"id" = "asc"})
     */
    protected $tilematrixsets;

    /**
     * @ORM\Column(type="text",nullable=true);
     */
    protected $username = null;

    /**
     * @ORM\Column(type="text",nullable=true);
     */
    protected $password = null;

    /**
     * @var ArrayCollection A list of WMTS layers
     * @ORM\OneToMany(targetEntity="WmtsLayerSource",mappedBy="source", cascade={"persist", "remove"})
     * @ORM\OrderBy({"id" = "asc"})
     */
    protected $layers;

    /**
     * @var ArrayCollection A list of WMTS instances
     * @ORM\OneToMany(targetEntity="WmtsInstance",mappedBy="source", cascade={"remove"})
     */
    protected $instances;

    public function __construct()
    {
        parent::__construct();
        $this->contact = new Contact();
        $this->instances = new ArrayCollection();
        $this->keywords = new ArrayCollection();
        $this->layers = new ArrayCollection();
        $this->tilematrixsets = new ArrayCollection();
        $this->themes = new ArrayCollection();
    }

    /**
     * @return ArrayCollection|WmtsInstance[]
     */
    public function getInstances()
    {
        return $this->instances;
    }

    public function getTypeLabel()
    {
        if (!$this->type) {
            // new entity, type undecided
            return 'OGC WMTS / TMS';
        } else {
            if ($this->type === Source::TYPE_WMTS) {
                return 'OGC WMTS';
            } else {
                return 'TMS';
            }
        }
    }

    /**
     * Set originUrl
     * @param string $originUrl
     * @return $this
     */
    public function setOriginUrl($originUrl)
    {
        $this->originUrl = $originUrl;
        return $this;
    }

    /**
     * Get originUrl
     * @return string
     */
    public function getOriginUrl()
    {
        return $this->originUrl;
    }

    /**
     * Set version
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
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set alias
     * @param string $alias
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
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
     * @return string
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * Get accessConstraints
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
     * @return string
     */
    public function getAccessConstraints()
    {
        return $this->accessConstraints;
    }

    /**
     * Set layers
     * @param ArrayCollection $layers
     * @return $this
     */
    public function setLayers(ArrayCollection $layers)
    {
        $this->layers = $layers;
        return $this;
    }

    /**
     * Get layers
     * @return WmtsLayerSource[]|ArrayCollection
     */
    public function getLayers()
    {
        return $this->layers;
    }

    /**
     * @param WmtsLayerSource $layer
     * @return $this
     */
    public function addLayer(WmtsLayerSource $layer)
    {
        $this->layers->add($layer);
        return $this;
    }


    /**
     * Set serviceProviderSite
     * @param string $serviceProviderSite
     * @return $this
     */
    public function setServiceProviderSite($serviceProviderSite)
    {
        $this->serviceProviderSite = $serviceProviderSite;
        return $this;
    }

    /**
     * Get serviceProviderSite
     * @return string
     */
    public function getServiceProviderSite()
    {
        return $this->serviceProviderSite;
    }

    /**
     * Set serviceProviderName
     * @param string $serviceProviderName
     * @return $this
     */
    public function setServiceProviderName($serviceProviderName)
    {
        $this->serviceProviderName = $serviceProviderName;
        return $this;
    }

    /**
     * Get serviceProviderName
     * @return string
     */
    public function getServiceProviderName()
    {
        return $this->serviceProviderName;
    }

        /**
     * Set Contact
     * @param Contact $contact
     * @return $this
     */
    public function setContact(Contact $contact)
    {
        $this->contact = $contact;
        return $this;
    }

    /**
     * Get Contact
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set keywords
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
     * @return ArrayCollection
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Add keyword
     * @param Keyword $keyword
     * @return Source
     */
    public function addKeyword(Keyword $keyword)
    {
        $this->keywords->add($keyword);
        return $this;
    }

    /**
     * Set getTile
     * @param RequestInformation $getTile
     * @return $this
     */
    public function setGetTile(RequestInformation $getTile)
    {
        $this->getTile = $getTile;
        return $this;
    }

    /**
     * Get getTile
     * @return RequestInformation
     */
    public function getGetTile()
    {
        return $this->getTile;
    }

    /**
     * Set getFeatureInfo
     * @param RequestInformation $getFeatureInfo
     * @return $this
     */
    public function setGetFeatureInfo(RequestInformation $getFeatureInfo)
    {
        $this->getFeatureInfo = $getFeatureInfo;
        return $this;
    }

    /**
     * Get getFeatureInfo
     * @return RequestInformation
     */
    public function getGetFeatureInfo()
    {
        return $this->getFeatureInfo;
    }

    /**
     * Get themes.
     * @return ArrayCollection
     */
    public function getThemes()
    {
        return $this->themes;
    }

    /**
     * Set themes
     * @param ArrayCollection $themes
     * @return $this
     */
    public function setThemes(ArrayCollection $themes)
    {
        $this->themes = $themes;
        return $this;
    }

    /**
     * Add theme
     * @param Theme $theme
     * @return $this
     */
    public function addTheme(Theme $theme)
    {
        $this->themes->add($theme);
        return $this;
    }

    /**
     * Set tilematrixsets
     * @param ArrayCollection $tilematrixsets
     * @return $this
     */
    public function setTilematrixsets(ArrayCollection $tilematrixsets)
    {
        $this->tilematrixsets = $tilematrixsets;
        return $this;
    }

    /**
     * Get tilematrixset
     * @return TileMatrixSet[]|ArrayCollection
     */
    public function getTilematrixsets()
    {
        return $this->tilematrixsets;
    }

    /**
     * Add a tilematrixset.
     * @param TileMatrixSet $tilematrixset
     * @return $this
     */
    public function addTilematrixset(TileMatrixSet $tilematrixset)
    {
        $this->tilematrixsets->add($tilematrixset);
        return $this;
    }

    /**
     * Get username.
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set username.
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Get password.
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set password.
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    public function mutateUrls(OneWayTransformer $transformer)
    {
        $this->setOriginUrl($transformer->process($this->getOriginUrl()));
        if ($requestInfo = $this->getGetTile()) {
            $requestInfo->mutateUrls($transformer);
            $this->setGetTile(clone $requestInfo);
        }
        if ($requestInfo = $this->getGetFeatureInfo()) {
            $requestInfo->mutateUrls($transformer);
            $this->setGetFeatureInfo(clone $requestInfo);
        }
        foreach ($this->getLayers() as $layer) {
            $layer->mutateUrls($transformer);
        }
    }

    public function getViewTemplate()
    {
        return '@MapbenderWmts/Repository/view.html.twig';
    }
}
