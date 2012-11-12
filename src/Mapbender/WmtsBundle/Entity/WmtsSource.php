<?php
namespace Mapbender\WmtsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Component\KeywordIn;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\CoreBundle\Entity\Keyword;
//use Mapbender\WmsBundle\Component\RequestInformation;
//use Mapbender\WmsBundle\Entity\WmsLayerSource;

/**
 * Description of WmtsSource
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_wmts_wmssource")
 * ORM\DiscriminatorMap({"mb_wmts_wmssource" = "WmtsSource"})
 */
class WmtsSource extends Source implements KeywordIn {
    
    /**
    * @ORM\Column(type="string", nullable=true)
    */
    protected $version = "";
    
    /**
    * @ORM\Column(type="string", nullable=true)
    */
    protected $fees = "";
    /**
    * @ORM\Column(type="string", nullable=true)
    */
    protected $originUrl;
    
    /**
    * @ORM\Column(type="string",nullable=true)
    */
    protected $accessConstraints = "";
    
    /**
    * @ORM\Column(type="text",nullable=true)
    */
    protected $serviceType = "";
    
    /**
     * @ORM\OneToOne(targetEntity="Mapbender\CoreBundle\Entity\Contact", cascade={"persist","remove"})
     */
    protected $contact;
    
//    /**
//     * @ORM\Column(type="string",nullable=true)
//     */
//    protected $serviceProviderSite = "";
//    /**
//    * @ORM\Column(type="string",nullable=true)
//    */
//    protected $serviceProviderName = "";
//    /**
//    * @ORM\Column(type="string",nullable=true)
//    */
//    protected $contactIndividualName = "";
//    
//    /**
//    * @ORM\Column(type="string",nullable=true)
//    */
//    protected $contactPositionName = "";
//    
//    /**
//    * @ORM\Column(type="string",nullable=true)
//    */
//    protected $contactPhoneVoice = "";
//    
//    /**
//    * @ORM\Column(type="string",nullable=true)
//    */
//    protected $contactPhoneFacsimile = "";
//
//    /**
//    * @ORM\Column(type="string",nullable=true)
//    */
//    protected $contactAddressDeliveryPoint = "";
//    
//    /**
//    * @ORM\Column(type="string",nullable=true)
//    */
//    protected $contactAddressCity = "";
//    
//    /**
//    * @ORM\Column(type="string",nullable=true)
//    */
//    protected $contactAddressPostalCode = "";
//    
//    /**
//    * @ORM\Column(type="string",nullable=true)
//    */
//    protected $contactAddressCountry = "";
//    
//    /**
//    * @ORM\Column(type="string",nullable=true)
//    */
//    protected $contactElectronicMailAddress = "";
//    
//    /**
//    * @ORM\Column(type="string",nullable=true)
//    */
//    protected $contactAddressAdministrativeArea = "";
//
//    /**
//    * @ORM\Column(type="string", nullable=true)
//    */
//    protected $requestGetCapabilitiesGETREST = "";
//    
//    /**
//    * @ORM\Column(type="string", nullable=true)
//    */
//    protected $requestGetCapabilitiesGETKVP = "";
//    /**
//    * @ORM\Column(type="string", nullable=true)
//    */
//    protected $requestGetCapabilitiesPOST = "";
//        /**
//    * @ORM\Column(type="string", nullable=true)
//    */
//    protected $requestGetCapabilitiesPOSTSOAP = "";
//    /**
//    * @ORM\Column(type="string", nullable=true)
//    */
//    protected $requestGetTileGETREST = "";
//    /**
//    * @ORM\Column(type="string", nullable=true)
//    */
//    protected $requestGetTileGETKVP = "";
//    /**
//    * @ORM\Column(type="string", nullable=true)
//    */
//    protected $requestGetFeatureInfoGETREST = "";
//    /**
//    * @ORM\Column(type="string", nullable=true)
//    */
//    protected $requestGetFeatureInfoGETKVP = "";
//
//    /**
//    * @ORM\Column(type="array", nullable=true);
//    */
//    protected $theme = null; 
//    
//    /**
//    * @ORM\Column(type="array", nullable=true);
//    */
//    protected $tilematrixset = null; 

    /**
    * @ORM\Column(type="text", nullable=true);
    */
    protected $username = null;

    /**
    * @ORM\Column(type="text", nullable=true);
    */
    protected $password = null; 
    
    public function __construct(){
//        $this->keywords = new ArrayCollection();
//        $this->layers = new ArrayCollection();
//        $this->exceptionFormats = array();
    }
    
    
    
    public function getType(){
        return "WMS";
    }
    
    public function getClassname(){
        return "Mapbender\WmsBundle\Entity\WmsSource";
    }
    
    public function getBundlename(){
        return "MapbenderWmsBundle";
    }


    /**
     * Set version
     *
     * @param string $version
     * @return WmtsSource
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
     * Set fees
     *
     * @param string $fees
     * @return WmtsSource
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
     * Set originUrl
     *
     * @param string $originUrl
     * @return WmtsSource
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
     * Set accessConstraints
     *
     * @param string $accessConstraints
     * @return WmtsSource
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
     * Set serviceType
     *
     * @param string $serviceType
     * @return WmtsSource
     */
    public function setServiceType($serviceType)
    {
        $this->serviceType = $serviceType;
    
        return $this;
    }

    /**
     * Get serviceType
     *
     * @return string 
     */
    public function getServiceType()
    {
        return $this->serviceType;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return WmtsSource
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
     * @return WmtsSource
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

}