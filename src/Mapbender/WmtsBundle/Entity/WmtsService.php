<?php
namespace Mapbender\WmtsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * WMTSService class
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 *
 * 
 * @ORM\Entity
*/
class WmtsService extends WmtsGroupLayer {

    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $version = "";
    
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $alias = "";
    
    /**
    * @ORM\Column(type="text", nullable="true")
    */
    protected $fees = "";
    
    /**
    * @ORM\Column(type="text",nullable="true")
    */
    protected $accessConstraints = "";
    
    /**
    * @ORM\Column(type="text",nullable="true")
    */
    protected $serviceType = "";
    
    /**
     * @ORM\Column(type="string",nullable="true")
     */
    protected $serviceProviderSite = "";
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $serviceProviderName = "";
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactIndividualName = "";
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactPositionName = "";
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactPhoneVoice = "";
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactPhoneFacsimile = "";

    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactAddressDeliveryPoint = "";
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactAddressCity = "";
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactAddressPostalCode = "";
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactAddressCountry = "";
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactElectronicMailAddress = "";
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactAddressAdministrativeArea = "";

    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetCapabilitiesGETREST = "";
    
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetCapabilitiesGETKVP = "";
//    /**
//    * @ORM\Column(type="string", nullable="true")
//    */
//    protected $requestGetCapabilitiesPOST = "";
//        /**
//    * @ORM\Column(type="string", nullable="true")
//    */
//    protected $requestGetCapabilitiesPOSTSOAP = "";
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetTileGETREST = "";
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetTileGETKVP = "";
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetFeatureInfoGETREST = "";
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetFeatureInfoGETKVP = "";

    /**
    * @ORM\Column(type="array", nullable="true");
    */
    protected $theme = null; 
    
    /**
    * @ORM\Column(type="array", nullable="true");
    */
    protected $tilematrixset = null; 

    /**
    * @ORM\Column(type="text", nullable="true");
    */
    protected $username = null;

    /**
    * @ORM\Column(type="text", nullable="true");
    */
    protected $password = null; 

    /**
     * Create an instance of WMTSService
     */
    public function __construct() {
        # calling super  - how to avoid ?
        return parent::__construct();
        $this->tilematrixset = array();
        $this->theme = array();
    }
    /**
     * Set version
     * 
     * @param type $version 
     */
    public function setVersion($version){
        $this->version = $version;
    }
    /**
     * Get version
     *
     * @return string
     */
    public function getVersion(){
        return $this->version;
    }
    /**
     * Set alias
     * 
     * @param string $alias 
     */
    public function setAlias($alias){
        $this->alias = $alias;
    }
    /**
     * Get alias
     * 
     * @return string
     */
    public function getAlias(){
        return $this->alias;
    }
    /**
     * Set fees
     * 
     * @param string $fees 
     */
    public function setFees($fees){
        $this->fees = $fees;
    }
    /**
     * Get fees
     * 
     * @return string
     */
    public function getFees(){
        return $this->fees;
    }
    /**
     * Get accessConstraints
     * 
     * @param string $accessConstraints 
     */
    public function setAccessConstraints($accessConstraints){
        $this->accessConstraints = $accessConstraints;
    }
    /**
     * Get accessConstraints
     * 
     * @return string
     */
    public function getAccessConstraints(){
        return $this->accessConstraints;
    }
    /**
     * Set serviceType
     * 
     * @param string $serviceType 
     */
    public function setServiceType($serviceType){
        $this->serviceType = $serviceType;
    }
    /**
     * Get serviceType
     * 
     * @return string
     */
    public function getServiceType(){
        return $this->serviceType;
    }
    /**
     * Get root layer
     * 
     * @return WMTSLayer
     */
    public function getRootLayer(){
        return $this->getLayer()->get(0);
    }

    /**
     * returns all Layers of the WMTS as comma-seperated string so that they can be used in a WMTS Request's LAYER parameter
     */
    public function getAllLayerNames($grouplayers = null){
        $grouplayers  = $grouplayers == null? $this->getLayer(): $grouplayers;
        $names = "";
        foreach ($grouplayers as $layer){
            $name = $layer->getName();
            if ( $name != ""){
                $names .= $name;
            }
            $names .= ",".$this->getAllLayerNames($layer->getLayer());
        }
        return trim($names,",");
    }
    
    /**
     * returns all Layers of the WMTS as comma-seperated string so that they can be used in a WMTS Request's LAYER parameter
     */
    public function getAllLayer($grouplayers=null, &$layers = array()){
        $grouplayers  = $grouplayers == null? $this->getLayer(): $grouplayers;
        foreach ($grouplayers as $layer){
            $layers[] = $layer;
            $this->getAllLayer($layer->getLayer(), $layers);
        }
        return $layers;
    }

    /**
     * Set serviceProviderSite
     *
     * @param string $serviceProviderSite
     */
    public function setServiceProviderSite($serviceProviderSite)
    {
        $this->serviceProviderSite = $serviceProviderSite;
    }

    /**
     * Get serviceProviderSite
     *
     * @return string 
     */
    public function getServiceProviderSite()
    {
        return $this->serviceProviderSite;
    }

    /**
     * Set ContactIndividualName
     *
     * @param string $contactIndividualName
     */
    public function setContactIndividualName($contactIndividualName)
    {
        $this->contactIndividualName = $contactIndividualName;
    }

    /**
     * Get ContactIndividualName
     *
     * @return string 
     */
    public function getContactIndividualName()
    {
        return $this->contactIndividualName;
    }

    /**
     * Set ServiceProviderName
     *
     * @param string $serviceProviderName
     */
    public function setServiceProviderName($serviceProviderName)
    {
        $this->serviceProviderName = $serviceProviderName;
    }

    /**
     * Get ServiceProviderName
     *
     * @return string 
     */
    public function getServiceProviderName()
    {
        return $this->serviceProviderName;
    }
    
    /**
     * Set ContactPositionName
     *
     * @param string $contactPositionName
     */
    public function setContactPositionName($contactPositionName)
    {
        $this->contactPositionName = $contactPositionName;
    }

    /**
     * Get ContactPositionName
     *
     * @return string 
     */
    public function getContactPositionName()
    {
        return $this->contactPositionName;
    }

    /**
     * Set contactPhoneVoice
     *
     * @param string $contactPhoneVoice
     */
    public function setContactPhoneVoice($contactPhoneVoice)
    {
        $this->contactPhoneVoice = $contactPhoneVoice;
    }

    /**
     * Get contactPhoneVoice
     *
     * @return string 
     */
    public function getContactPhoneVoice()
    {
        return $this->contactPhoneVoice;
    }

    /**
     * Set contactPhoneFacsimile
     *
     * @param string $contactPhoneFacsimile
     */
    public function setContactPhoneFacsimile($contactPhoneFacsimile)
    {
        $this->contactPhoneFacsimile = $contactPhoneFacsimile;
    }

    /**
     * Get contactPhoneFacsimile
     *
     * @return string 
     */
    public function getContactPhoneFacsimile()
    {
        return $this->contactPhoneFacsimile;
    }

    /**
     * Set ContactElectronicMailAddress
     *
     * @param string $contactElectronicMailAddress
     */
    public function setContactElectronicMailAddress($contactElectronicMailAddress)
    {
        $this->contactElectronicMailAddress = $contactElectronicMailAddress;
    }

    /**
     * Get ContactElectronicMailAddress
     *
     * @return string 
     */
    public function getContactElectronicMailAddress()
    {
        return $this->contactElectronicMailAddress;
    }

    /**
     * Set contactAddressDeliveryPoint
     *
     * @param string $contactAddressDeliveryPoint
     */
    public function setContactAddressDeliveryPoint($contactAddressDeliveryPoint)
    {
        $this->contactAddressDeliveryPoint = $contactAddressDeliveryPoint;
    }

    /**
     * Get contactAddressDeliveryPoint
     *
     * @return string 
     */
    public function getContactAddressDeliveryPoint()
    {
        return $this->contactAddressDeliveryPoint;
    }

    /**
     * Set ContactAddressCity
     *
     * @param string $contactAddressCity
     */
    public function setContactAddressCity($contactAddressCity)
    {
        $this->contactAddressCity = $contactAddressCity;
    }

    /**
     * Get ContactAddressCity
     *
     * @return string 
     */
    public function getContactAddressCity()
    {
        return $this->contactAddressCity;
    }

    /**
     * Set contactAddressAdministrativeArea
     *
     * @param string $contactAddressAdministrativeArea
     */
    public function setContactAddressAdministrativeArea($contactAddressAdministrativeArea)
    {
        $this->contactAddressAdministrativeArea = $contactAddressAdministrativeArea;
    }

    /**
     * Get contactAddressAdministrativeArea
     *
     * @return string 
     */
    public function getContactAddressAdministrativeArea()
    {
        return $this->contactAddressAdministrativeArea;
    }

    /**
     * Set ContactAddressPostalCode
     *
     * @param string $contactAddressPostalCode
     */
    public function setContactAddressPostalCode($contactAddressPostalCode)
    {
        $this->contactAddressPostalCode = $contactAddressPostalCode;
    }

    /**
     * Get ContactAddressPostalCode
     *
     * @return string 
     */
    public function getContactAddressPostalCode()
    {
        return $this->contactAddressPostalCode;
    }

    /**
     * Set ContactAddressCountry
     *
     * @param string $contactAddressCountry
     */
    public function setContactAddressCountry($contactAddressCountry)
    {
        $this->contactAddressCountry = $contactAddressCountry;
    }

    /**
     * Get ContactAddressCountry
     *
     * @return string
     */
    public function getContactAddressCountry()
    {
        return $this->contactAddressCountry;
    }
    /**
     * Set requestGetCapabilitiesGETREST
     *
     * @param type $requestGetCapabilitiesGETREST 
     */
    public function setRequestGetCapabilitiesGETREST($requestGetCapabilitiesGETREST){
        $this->requestGetCapabilitiesGETREST = $requestGetCapabilitiesGETREST;
    }
    /**
     * Get requestGetCapabilitiesGETREST
     *
     * @return string 
     */
    public function getRequestGetCapabilitiesGETREST(){
        return $this->requestGetCapabilitiesGETREST;
    }
    /**
     * Set requestGetCapabilitiesGETKVP
     * 
     * @param string $requestGetCapabilitiesGETKVP 
     */
    public function setRequestGetCapabilitiesGETKVP($requestGetCapabilitiesGETKVP){
        $this->requestGetCapabilitiesGETKVP = $requestGetCapabilitiesGETKVP;
    }
    /**
     * Get requestGetCapabilitiesGETKVP
     * 
     * @return string
     */
    public function getRequestGetCapabilitiesGETKVP(){
        return $this->requestGetCapabilitiesGETKVP;
    }

    /**
     * Set requestGetTileGETREST
     * 
     * @param string $requestGetTileGETREST 
     */
    public function setRequestGetTileGETREST($requestGetTileGETREST){
        $this->requestGetTileGETREST = $requestGetTileGETREST;
    }
    /**
     * Get requestGetTileGETREST
     * 
     * @return string 
     */
    public function getRequestGetTileGETREST(){
        return $this->requestGetTileGETREST;
    }
    /**
     * Set requestGetTileGETKVP
     * 
     * @param string $requestGetTileGETKVP 
     */
    public function setRequestGetTileGETKVP($requestGetTileGETKVP){
        $this->requestGetTileGETKVP = $requestGetTileGETKVP;
    }
    /**
     * Get requestGetTileGETKVP
     * 
     * @return string
     */
    public function getRequestGetTileGETKVP(){
        return $this->requestGetTileGETKVP;
    }
    /**
     * Get theme
     * 
     * @return array 
     */
    public function getTheme (){
        return $this->theme ;
    }
    
    /**
     * Get theme as ArrayCollection of Theme
     * 
     * @return ArrayCollection 
     */
    public function getThemeAsObjects (){
        $array = new ArrayCollection();
        foreach ($this->theme as $theme){
            $array->add(new Theme($theme));
        }
        return $array;
    }
    
    /**
     * Set theme
     * 
     * @param array of Theme or Theme->getAsArray $themes 
     */
    public function setTheme ($themes){
        if($themes === null){
            $this->theme = $themes;
        } else if(count($themes)> 0){
            if(is_array($themes[0])){
                $this->theme = $themes;
            } else if($themes[0] instanceof Theme) {
                foreach ($themes as $theme){
                    $this->theme[] = $theme->getAsArray();
                }
            }
        } else {
            $this->theme = $themes;
        }
    }
    /**
     * Add $theme to theme
     * 
     * @param Theme or array $theme 
     */
    public function addTheme ($theme){
        if(is_array($theme)){
            $this->theme[] = $theme ;
        } else if($theme instanceof Theme){
            $this->theme[] = $theme->getAsArray();
        }
    }
    
    /**
     * Get tilematrixset
     * 
     * @return array 
     */
    public function getTileMatrixSet (){
        return $this->tilematrixset;
    }
    
    /**
     * Get tilematrixset
     * 
     * @return array 
     */
    public function getTileMatrixSetAsObjects (){
        $array = new ArrayCollection();
        foreach ($this->tilematrixset as $tilematrixset){
            $array->add(new TileMatrixSet($tilematrixset));
        }
        return $array;
    }
    /**
     * Set tilematrixset
     * 
     * @param array $tilematrixset 
     */
    public function setTtilematrixset ($tilematrixset){
        $this->tilematrixset  = $tilematrixset ;
        if($tilematrixset === null){
            $this->tilematrixset = $tilematrixset;
        } else if(count($tilematrixset)> 0){
            if(is_array($tilematrixset[0])){
                $this->tilematrixset = $tilematrixset;
            } else if($tilematrixset[0] instanceof TileMatrixSet) {
                foreach ($tilematrixset as $tilematrixset_){
                    $this->tilematrixset = array();
                    $this->tilematrixset[] = $tilematrixset_->getAsArray();
                }
            }
        } else {
            $this->tilematrixset = $tilematrixset;
        }
    }
    /**
     * Add tilematrixset
     * @param TilematrixSet or array $tilematrixset 
     */
    public function addTtilematrixset ($tilematrixset){
        if(is_array($tilematrixset)) {
            $this->tilematrixset[]  = $tilematrixset ;
        } else if($tilematrixset instanceof TileMatrixSet){
            $this->tilematrixset[]  = $tilematrixset->getAsArray() ;
        }
    }
    /**
     * Get username
     * 
     * @return string
     */
    public function getUsername (){
        return $this->username ;
    }
    /**
     * Set username
     * 
     * @param string $username 
     */
    public function setUsername ($username ){
        $this->username  = $username ;
    }
    /**
     * Get password
     * 
     * @return string
     */
    public function getPassword (){
        return $this->password ;
    }
    /**
     * Set password
     * 
     * @param string $password 
     */
    public function setPassword ($password ){
        $this->password  = $password ;
    }


}



//    
//    /**
//    * @ORM\Column(type="boolean", nullable="true")
//    */
//    protected $symbolSupportSLD = false;
//    
//    /**
//    * @ORM\Column(type="boolean", nullable="true")
//    */
//    protected $symbolUserLayer = false;
//    
//    /**
//    * @ORM\Column(type="boolean", nullable="true")
//    */
//    protected $symbolUserStyle = false;
//    
//    /**
//    * @ORM\Column(type="boolean", nullable="true")
//    */
//    protected $symbolRemoteWFS = false;



//    public function setRequestGetCapabilitiesPOST($requestGetCapabilitiesPOST){
//        $this->requestGetCapabilitiesPOST = $requestGetCapabilitiesPOST;
//    }
//    
//    public function getRequestGetCapabilitiesPOST(){
//        return $this->requestGetCapabilitiesPOST;
//    }
//    /**
//     * Set supportedExceptionFormats
//     *
//     * @param array $supportedExceptionFormats
//     */
//    public function setSupportedExceptionFormats($supportedExceptionFormats)
//    {
//        $this->supportedExceptionFormats = $supportedExceptionFormats;
//    }
//
//    /**
//     * Get supportedExceptionFormats
//     *
//     * @return array 
//     */
//    public function getSupportedExceptionFormats()
//    {
//        return $this->supportedExceptionFormats;
//    }

//    /**
//     * Set requestSupportedGetCapabilitiesFormats
//     *
//     * @param array $requestSupportedGetCapabilitiesFormats
//     */
//    public function setRequestSupportedGetCapabilitiesFormats($requestSupportedGetCapabilitiesFormats)
//    {
//        $this->requestSupportedGetCapabilitiesFormats = $requestSupportedGetCapabilitiesFormats;
//    }
//
//    /**
//     * Get requestSupportedGetCapabilitiesFormats
//     *
//     * @return array 
//     */
//    public function getRequestSupportedGetCapabilitiesFormats()
//    {
//        return $this->requestSupportedGetCapabilitiesFormats;
//    }
//
//    /**
//     * Set requestSupportedGetMapFormats
//     *
//     * @param array $requestSupportedGetMapFormats
//     */
//    public function setRequestSupportedGetMapFormats($requestSupportedGetMapFormats)
//    {
//        $this->requestSupportedGetMapFormats = $requestSupportedGetMapFormats;
//    }
//
//    /**
//     * Get requestSupportedGetMapFormats
//     *
//     * @return array 
//     */
//    public function getRequestSupportedGetMapFormats()
//    {
//        return $this->requestSupportedGetMapFormats;
//    }
//
//    /**
//     * Set requestSupportedGetFeatureInfoFormats
//     *
//     * @param array $requestSupportedGetFeatureInfoFormats
//     */
//    public function setRequestSupportedGetFeatureInfoFormats($requestSupportedGetFeatureInfoFormats)
//    {
//        $this->requestSupportedGetFeatureInfoFormats = $requestSupportedGetFeatureInfoFormats;
//    }
//
//    /**
//     * Get requestSupportedGetFeatureInfoFormats
//     *
//     * @return array 
//     */
//    public function getRequestSupportedGetFeatureInfoFormats()
//    {
//        return $this->requestSupportedGetFeatureInfoFormats;
//    }

//
//    /**
//     * Set requestSupportedGetLegendGraphicFormats
//     *
//     * @param array $requestSupportedGetLegendGraphicFormats
//     */
//    public function setRequestSupportedGetLegendGraphicFormats($requestSupportedGetLegendGraphicFormats)
//    {
//        $this->requestSupportedGetLegendGraphicFormats = $requestSupportedGetLegendGraphicFormats;
//    }
//
//    /**
//     * Get requestSupportedGetLegendGraphicFormats
//     *
//     * @return array 
//     */
//    public function getRequestSupportedGetLegendGraphicFormats()
//    {
//        return $this->requestSupportedGetLegendGraphicFormats;
//    }
//
//    /**
//     * Set requestSupportedGetStylesFormats
//     *
//     * @param array $requestSupportedGetStylesFormats
//     */
//    public function setRequestSupportedGetStylesFormats($requestSupportedGetStylesFormats)
//    {
//        $this->requestSupportedGetStylesFormats = $requestSupportedGetStylesFormats;
//    }
//
//    /**
//     * Get requestSupportedGetStylesFormats
//     *
//     * @return array 
//     */
//    public function getRequestSupportedGetStylesFormats()
//    {
//        return $this->requestSupportedGetStylesFormats;
//    }
//
//    /**
//     * Set requestSupportedPutStylesFormats
//     *
//     * @param array $requestSupportedPutStylesFormats
//     */
//    public function setRequestSupportedPutStylesFormats($requestSupportedPutStylesFormats)
//    {
//        $this->requestSupportedPutStylesFormats = $requestSupportedPutStylesFormats;
//    }
//
//    /**
//     * Get requestSupportedPutStylesFormats
//     *
//     * @return array 
//     */
//    public function getRequestSupportedPutStylesFormats()
//    {
//        return $this->requestSupportedPutStylesFormats;
//    }

//    public function setExceptionFormats(array $exceptionFormats){
//        $this->exceptionFormats = $exceptionFormats;
//    }
//
//    public function getExceptionFormats(){
//        $this->exceptionFormats = $this->exceptionFormats == ""? array(): $this->exceptionFormats;
//        return $this->exceptionFormats;
//    }
//
//    
//    /**
//     * returns the default (first) exceptionFormats that a wmts supports 
//    */
//    public function getDefaultExceptionFormats(){
//        return isset($this->exceptionFormats[0])? $this->exceptionFormats[0]: '';
//    }
//    
//    /**
//    * returns an array of all locally known ExceptionFormats
//    */
//    public static function getAllExceptionFormats(){
//        return array(
//            "application/vnd__ogc__se_xml",
//            "application/vnd__ogc__se_inimage",
//            "application/vnd__ogc__se_blank"
//        );
//    }
//    
//    public function setRequestGetCapabilitiesFormats(array $formats){
//        $this->requestGetCapabilitiesFormats = $formats;
//    }
//    
//    public function getRequestGetCapabilitiesFormats(){
//        return $this->requestGetCapabilitiesFormats;
//    }
//    
//    /**
//    * returns an array of all locally known RequestCapabilitiesFormats
//    */
//    public static function getAllRequestGetCapabilitiesFormats(){ // ???????
//        return array(
//            "application/vnd__ogc__wms_xml",
//        );
//    }
//
//    /**
//     * returns the default (first) format that a wmts supports for getCapabilities requests
//    */
//    public function getDefaultRequestGetCapabilitiesFormat(){
//        return isset($this->requestGetCapabilitiesFormats[0])? $this->requestGetCapabilitiesFormats[0]: '';
//    }
//
//
//
//
//    public function setRequestGetMapGET($requestGetMapGET){
//        $this->requestGetMapGET = $requestGetMapGET;
//    }
//    
//    public function getRequestGetMapGET(){
//        return $this->requestGetMapGET;
//    }
//    
//
//    /*
//    *
//    * returns a the getMapUrl with added username and password like "http://user:password@host/
//    */
//    public function getAuthRequestGetMapGET(){
//        if(!$this->username){
//            return $this->requestGetMapGET;
//        }
//
//        $authString = $this->username .":".$this->password . "@";
//        //die($authString."- " .$this->requestGetMapGET);
//        $authRequestUrl =  preg_replace("/^https*:\/\//", $authString, $this->requestGetMapGET );
//        return $authRequestUrl;
//    }
//    
//    public function setRequestGetMapPOST($requestGetMapPOST){
//        $this->requestGetMapPOST = $requestGetMapPOST;
//    }
//    
//    public function getRequestGetMapPOST(){
//        return $this->requestGetMapPOST;
//    }
//    
//    public function setRequestGetMapFormats(array $formats){
//        $this->requestGetMapFormats = $formats;
//    }
//    
//    public function getRequestGetMapFormats(){
//        return $this->requestGetMapFormats;
//    }
//    
//    /**
//    * returns an array of all locally known RequestMapFormats
//    */
//    public static function getAllRequestGetMapFormats(){
//        return array(
//            "image/png",
//            "image/gif",
//            "image/png; mode=24bit",
//            "image/jpeg",
//            "image/wbmp",
//            "image/tiff"
//        );
//    }
//    
//    /**
//     * returns the default (first) format that a wmts supports for getMap requests
//    */
//    public function getDefaultRequestGetMapFormat(){
//        return isset($this->requestGetMapFormats[0])? $this->requestGetMapFormats[0]: '';
//    }

//
//
//    public function setRequestGetFeatureInfoGET($requestGetFeatureInfoGET){
//        $this->requestGetFeatureInfoGET = $requestGetFeatureInfoGET;
//    }
//    
//    public function getRequestGetFeatureInfoGET(){
//        return $this->requestGetFeatureInfoGET;
//    }
//    
//    public function setRequestGetFeatureInfoPOST($requestGetFeatureInfoPOST){
//        $this->requestGetFeatureInfoPOST = $requestGetFeatureInfoPOST;
//    }
//    
//    public function getRequestGetFeatureInfoPOST(){
//        return $this->requestGetFeatureInfoPOST;
//    }
//    
//    public function setRequestGetFeatureInfoFormats(array $formats){
//        $this->requestGetFeatureInfoFormats = $formats;
//    }
//    
//    public function getRequestGetFeatureInfoFormats(){
//        return $this->requestGetFeatureInfoFormats;
//    }
//    
//    /**
//    * returns an array of all locally known RequestGetFeatureInfoFormats
//    */
//    public static function getAllRequestGetFeatureinfoFormats(){
//        return array(
//            "text/html",
//            "text/plain",
//            "application/vnd__ogc__gml"
//        );
//    }
//    
//    /**
//     * returns the default (first) format that a wmts supports for getFeatureInfo requests
//    */
//    public function getDefaultRequestGetFeatureInfoFormat(){
//        return isset($this->requestGetFeatureInfoFormats[0])? $this->requestGetFeatureInfoFormats[0]: '';
//    }
//
//
//
//
//    public function setRequestGetLegendGraphicGET($requestGetLegendGraphicGET){
//        $this->requestGetLegendGraphicGET = $requestGetLegendGraphicGET;
//    }
//    
//    public function getRequestGetLegendGraphicGET(){
//        return $this->requestGetLegendGraphicGET;
//    }
//    
//    public function setRequestGetLegendGraphicPOST($requestGetLegendGraphicPOST){
//        $this->requestGetLegendGraphicPOST = $requestGetLegendGraphicPOST;
//    }
//    
//    public function getRequestGetLegendGraphicPOST(){
//        return $this->requestGetLegendGraphicPOST;
//    }
//    
//    public function setRequestGetLegendGraphicFormats(array $formats){
//        $this->requestGetLegendGraphicFormats = $formats;
//    }
//    
//    public function getRequestGetLegendGraphicFormats(){
//    
//        return $this->requestGetLegendGraphicFormats;
//    }
//    
//    /**
//    * returns an array of all locally known RequestGetLegendGraphicsFormats
//    */
//    public static function getAllRequestGetLegendGraphicFormats(){
//        return array(
//            "image/png",
//            "image/gif",
//            "image/png; mode=24bit",
//            "image/jpeg",
//            "image/wbmp",
//            "image/tiff"
//        );
//    }
//    
//    /**
//     * returns the default (first) format that a wmts supports for getLegendGraphic requests
//    */
//    public function getDefaultRequestGetLegendGraphicFormat(){
//        return isset($this->requestGetLegendGraphicFormats[0])? $this->requestGetLegendGraphicFormats[0]: '';
//    }
//
//
//
//    public function setRequestGetStylesGET($requestGetStylesGET){
//        $this->requestGetStylesGET = $requestGetStylesGET;
//    }
//    
//    public function getRequestGetStylesGET(){
//        return $this->requestGetStylesGET;
//    }
//    
//    public function setRequestGetStylesPOST($requestGetStylesPOST){
//        $this->requestGetStylesPOST = $requestGetStylesPOST;
//    }
//    
//    public function getRequestGetStylesPOST(){
//        return $this->requestGetStylesPOST;
//    }
//    
//    public function setRequestGetStylesFormats(array $formats){
//        $this->requestGetStylesFormats = $formats;
//    }
//    
//    public function getRequestGetStylesFormats(){
//        return $this->requestGetStylesFormats;
//    }
//    
//    /**
//    * returns an array of all locally known RequestGetStylesFormats
//    */
//    public static function getAllRequestGetStylesFormats(){
//        return array(
//        );
//    }
//    
//    /**
//     * returns the default (first) format that a wmts supports for getStyles requests
//    */
//    public function getDefaultRequestGetStylesFormat(){
//        return isset($this->requestGetStylesFormats[0])? $this->requestGetStylesFormats[0]: '';
//    }
//
//
//
//    public function setRequestPutStylesGET($requestPutStylesGET){
//        $this->requestPutStylesGET = $requestPutStylesGET;
//    }
//    
//    public function getRequestPutStylesGET(){
//        return $this->requestPutStylesGET;
//    }
//    
//    public function setRequestPutStylesPOST($requestPutStylesPOST){
//        $this->requestPutStylesPOST = $requestPutStylesPOST;
//    }
//    
//    public function getRequestPutStylesPOST(){
//        return $this->requestPutStylesPOST;
//    }
    
//    public function setRequestPutStylesFormats(array $formats){
//        $this->requestPutStylesFormats = $formats;
//    }
//    
//    public function getRequestPutStylesFormats(){
//        return $this->requestPutStylesFormats;
//    }
//    
//    /**
//    * returns an array of all locally known RequestPutStylesFormats
//    */
//    public static function getAllRequestPutStylesFormats(){
//        return array(
//        );
//    }
//    
//    /**
//     * returns the default (first) format that a wmts supports for putStyles requests
//    */
//    public function getDefaultRequestPutStylesFormat(){
//        return isset($this->requestPutStylesFormats[0])? $this->requestPutStylesFormats[0]: '';
//    }

//    /**
//     * Set symbolSupportSLD
//     *
//     * @param boolean $symbolSupportSLD
//     */
//    public function setSymbolSupportSLD($symbolSupportSLD)
//    {
//        $this->symbolSupportSLD = $symbolSupportSLD;
//    }

//    /**
//     * Get symbolSupportSLD
//     *
//     * @return boolean 
//     */
//    public function getSymbolSupportSLD()
//    {
//        return $this->symbolSupportSLD;
//    }
//
//    /**
//     * Set symbolUserLayer
//     *
//     * @param boolean $symbolUserLayer
//     */
//    public function setSymbolUserLayer($symbolUserLayer)
//    {
//        $this->symbolUserLayer = $symbolUserLayer;
//    }
//
//    /**
//     * Get symbolUserLayer
//     *
//     * @return boolean 
//     */
//    public function getSymbolUserLayer()
//    {
//        return $this->symbolUserLayer;
//    }

//    /**
//     * Set symbolUserStyle
//     *
//     * @param boolean $symbolUserStyle
//     */
//    public function setSymbolUserStyle($symbolUserStyle)
//    {
//        $this->symbolUserStyle = $symbolUserStyle;
//    }
//
//    /**
//     * Get symbolUserStyle
//     *
//     * @return boolean 
//     */
//    public function getSymbolUserStyle()
//    {
//        return $this->symbolUserStyle;
//    }
//
//    /**
//     * Set symbolRemoteWFS
//     *
//     * @param boolean $symbolRemoteWFS
//     */
//    public function setSymbolRemoteWFS($symbolRemoteWFS)
//    {
//        $this->symbolRemoteWFS = $symbolRemoteWFS;
//    }
//
//    /**
//     * Get symbolRemoteWFS
//     *
//     * @return boolean 
//     */
//    public function getSymbolRemoteWFS()
//    {
//        return $this->symbolRemoteWFS;
//    }