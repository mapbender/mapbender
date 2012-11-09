<?php
//namespace Mapbender\WmtsBundle\Entity;
//
//use Doctrine\ORM\Mapping as ORM;
//use Doctrine\Common\Collections\ArrayCollection;
//use Mapbender\CoreBundle\Component\KeywordIn;
//use Mapbender\CoreBundle\Entity\Source;
//use Mapbender\CoreBundle\Entity\Contact;
//use Mapbender\CoreBundle\Entity\Keyword;
////use Mapbender\WmsBundle\Component\RequestInformation;
////use Mapbender\WmsBundle\Entity\WmsLayerSource;
//
///**
// * Description of WmtsSource
// *
// * @ORM\Entity
// * @ORM\Table(name="mb_wmts_wmssource")
// * ORM\DiscriminatorMap({"mb_wmts_wmssource" = "WmtsSource"})
// */
//class WmtsSource extends Source implements KeywordIn {
//    
//    /**
//    * @ORM\Column(type="string", nullable=true)
//    */
//    protected $version = "";
//    
////    /**
////    * @ORM\Column(type="string", nullable=true)
////    */
////    protected $alias = "";
//    
//    /**
//    * @ORM\Column(type="string", nullable=true)
//    */
//    protected $fees = "";
//    
//    /**
//    * @ORM\Column(type="string",nullable=true)
//    */
//    protected $accessConstraints = "";
//    
//    /**
//    * @ORM\Column(type="text",nullable=true)
//    */
//    protected $serviceType = "";
//    
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
////    /**
////    * @ORM\Column(type="string", nullable=true)
////    */
////    protected $requestGetCapabilitiesPOST = "";
////        /**
////    * @ORM\Column(type="string", nullable=true)
////    */
////    protected $requestGetCapabilitiesPOSTSOAP = "";
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
//
//    /**
//    * @ORM\Column(type="text", nullable=true);
//    */
//    protected $username = null;
//
//    /**
//    * @ORM\Column(type="text", nullable=true);
//    */
//    protected $password = null; 
//    
//    public function __construct() {
//        
//    }
//    
//    public function getType(){
//        return "WMTS";
//    }
//    
//    public function getClassname(){
//        return "Mapbender\WmtsBundle\Entity\WmtsSource";
//    }
//}

