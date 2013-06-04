<?php

namespace Mapbender\WmcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\State;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmsBundle\Component\LegendUrl;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A Wmc entity presents an OGC WMC.
 * @ORM\Entity
 * @ORM\Table(name="mb_wmc_wmc")
 * ORM\DiscriminatorMap({"mb_wmc" = "Wmc"})
 */
class Wmc
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @var string $version The wmc version
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    protected $version = "1.1.0";
    
    /**
     * @var string $wmcid a wmc id
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $wmcid;

    /**
     * @ORM\OneToOne(targetEntity="Mapbender\CoreBundle\Entity\State", cascade={"persist","remove"})
     * @ORM\JoinColumn(name="state", referencedColumnName="id")
     * */
    protected $state;

    /**
     * @var array $keywords The keywords of the wmc
     * @ORM\Column(type="array",nullable=true)
     * */
    protected $keywords = array();

    /**
     * @var string $abstract The wmc description
     * @ORM\Column(type="text", nullable=true)
     */
    protected $abstract;

    /**
     * @var string A description url
     * @ORM\Column(type="object", nullable=true)
     */
    public $logourl;

    /**
     * @var string A description url
     * @ORM\Column(type="object", nullable=true)
     */
    public $descriptionurl;
    
    /**
     * @var string $screenshotPath The wmc description
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $screenshotPath;

    /**
     * @Assert\File(maxSize="6000000")
     */
    private $screenshot;
    
    /**
     * @var Contact A contact.
     * @ORM\OneToOne(targetEntity="Mapbender\CoreBundle\Entity\Contact", cascade={"persist","remove"})
     */
    protected $contact;


    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Set abstract
     *
     * @param string $abstract
     * @return Source
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
     * Set logourl
     *
     * @param LegendUrl $logourl
     * @return Wmc
     */
    public function setLogourl(LegendUrl $logourl)
    {
        $this->logourl = $logourl;
        return $this;
    }

    /**
     * Get logourl
     *
     * @return LegendUrl 
     */
    public function getLogourl()
    {
        return $this->logourl;
    }

    /**
     * Set descriptionurl
     *
     * @param OnlineResource $descriptionurl
     * @return Wmc
     */
    public function setDescriptionurl(OnlineResource $descriptionurl)
    {
        $this->descriptionurl = $descriptionurl;
        return $this;
    }

    /**
     * Get descriptionurl
     *
     * @return OnlineResource 
     */
    public function getDescriptionurl()
    {
        return $this->descriptionurl;
    }
    
    /**
     * Set screenshotPath
     *
     * @param string $screenshotPath
     * @return Source
     */
    public function setScreenshotPath($screenshotPath)
    {
        $this->screenshotPath = $screenshotPath;
        return $this;
    }

    /**
     * Get screenshotPath
     *
     * @return string 
     */
    public function getScreenshotPath()
    {
        return $this->screenshotPath;
    }

    

    /**
     * @param string $screenshot
     */
    public function setScreenshot($screenshot) {
        $this->screenshot = $screenshot;
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
     * @param string $version
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
    public function getWmcid() {
        return $this->wmcid;
    }
    
    /**
     * @param string $wmcid
     */
    public function setWmcid($wmcid) {
        $this->wmcid = $wmcid;
        return $this;
    }

    /**
     * Get screenshot
     *
     * @return string
     */
    public function getScreenshot() {
        return $this->screenshot;
    }
    
    
    public static function create($state = null, $logoUrl = null,
            $descriptionUrl = null)
    {
        $state = $state === null ? new State() : $state;
        $wmc = new Wmc();
        $wmc->setState($state);
        $logoUrl = $logoUrl === null ? LegendUrl::create() : logoUrl;
        if($logoUrl !== null)
        {
            $wmc->setLogourl($logoUrl);
        }
        $descriptionUrl = $descriptionUrl === null ? OnlineResource::create() : $descriptionUrl;
        if($descriptionUrl !== null)
        {
            $wmc->setDescriptionurl($descriptionUrl);
        }
        return $wmc;
    }

}

