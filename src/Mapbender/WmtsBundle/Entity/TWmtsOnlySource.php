<?php


namespace Mapbender\WmtsBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\WmtsBundle\Component\RequestInformation;


/**
 * Contains fields and methods used only in WMTS source, but not in TMS source
 */
trait TWmtsOnlySource
{
    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
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
     * @param string $fees
     * @return $this
     */
    public function setFees($fees)
    {
        $this->fees = $fees;
        return $this;
    }

    /**
     * @return string
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * @param string $accessConstraints
     */
    public function setAccessConstraints($accessConstraints)
    {
        $this->accessConstraints = $accessConstraints;
    }

    /**
     * @return string
     */
    public function getAccessConstraints()
    {
        return $this->accessConstraints;
    }


    /**
     * @param string $serviceProviderSite
     */
    public function setServiceProviderSite($serviceProviderSite)
    {
        $this->serviceProviderSite = $serviceProviderSite;
    }

    /**
     * @return string
     */
    public function getServiceProviderSite()
    {
        return $this->serviceProviderSite;
    }

    /**
     * @param string $serviceProviderName
     */
    public function setServiceProviderName($serviceProviderName)
    {
        $this->serviceProviderName = $serviceProviderName;
    }

    /**
     * @return string
     */
    public function getServiceProviderName()
    {
        return $this->serviceProviderName;
    }

    /**
     * @param RequestInformation $getTile
     * @return $this
     */
    public function setGetTile(RequestInformation $getTile)
    {
        $this->getTile = $getTile;
        return $this;
    }

    /**
     * @return RequestInformation
     */
    public function getGetTile()
    {
        return $this->getTile;
    }

    /**
     * @param RequestInformation $getFeatureInfo
     */
    public function setGetFeatureInfo(RequestInformation $getFeatureInfo)
    {
        $this->getFeatureInfo = $getFeatureInfo;
    }

    /**
     * @return RequestInformation
     */
    public function getGetFeatureInfo()
    {
        return $this->getFeatureInfo;
    }

    /**
     * @return ArrayCollection
     */
    public function getThemes()
    {
        return $this->themes;
    }

    /**
     * @param ArrayCollection $themes
     */
    public function setThemes(ArrayCollection $themes)
    {
        $this->themes = $themes;
    }

    /**
     * @param Theme $theme
     */
    public function addTheme(Theme $theme)
    {
        $this->themes->add($theme);
    }
}
