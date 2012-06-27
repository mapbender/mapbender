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
    * @ORM\Column(type="string", nullable=true)
    */
    protected $version = "";
    
    /**
    * @ORM\Column(type="string", nullable=true)
    */
    protected $alias = "";
    
    /**
    * @ORM\Column(type="text", nullable=true)
    */
    protected $fees = "";
    
    /**
    * @ORM\Column(type="text",nullable=true)
    */
    protected $accessConstraints = "";
    
    /**
    * @ORM\Column(type="text",nullable=true)
    */
    protected $serviceType = "";
    
    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $serviceProviderSite = "";
    /**
    * @ORM\Column(type="string",nullable=true)
    */
    protected $serviceProviderName = "";
    /**
    * @ORM\Column(type="string",nullable=true)
    */
    protected $contactIndividualName = "";
    
    /**
    * @ORM\Column(type="string",nullable=true)
    */
    protected $contactPositionName = "";
    
    /**
    * @ORM\Column(type="string",nullable=true)
    */
    protected $contactPhoneVoice = "";
    
    /**
    * @ORM\Column(type="string",nullable=true)
    */
    protected $contactPhoneFacsimile = "";

    /**
    * @ORM\Column(type="string",nullable=true)
    */
    protected $contactAddressDeliveryPoint = "";
    
    /**
    * @ORM\Column(type="string",nullable=true)
    */
    protected $contactAddressCity = "";
    
    /**
    * @ORM\Column(type="string",nullable=true)
    */
    protected $contactAddressPostalCode = "";
    
    /**
    * @ORM\Column(type="string",nullable=true)
    */
    protected $contactAddressCountry = "";
    
    /**
    * @ORM\Column(type="string",nullable=true)
    */
    protected $contactElectronicMailAddress = "";
    
    /**
    * @ORM\Column(type="string",nullable=true)
    */
    protected $contactAddressAdministrativeArea = "";

    /**
    * @ORM\Column(type="string", nullable=true)
    */
    protected $requestGetCapabilitiesGETREST = "";
    
    /**
    * @ORM\Column(type="string", nullable=true)
    */
    protected $requestGetCapabilitiesGETKVP = "";
//    /**
//    * @ORM\Column(type="string", nullable=true)
//    */
//    protected $requestGetCapabilitiesPOST = "";
//        /**
//    * @ORM\Column(type="string", nullable=true)
//    */
//    protected $requestGetCapabilitiesPOSTSOAP = "";
    /**
    * @ORM\Column(type="string", nullable=true)
    */
    protected $requestGetTileGETREST = "";
    /**
    * @ORM\Column(type="string", nullable=true)
    */
    protected $requestGetTileGETKVP = "";
    /**
    * @ORM\Column(type="string", nullable=true)
    */
    protected $requestGetFeatureInfoGETREST = "";
    /**
    * @ORM\Column(type="string", nullable=true)
    */
    protected $requestGetFeatureInfoGETKVP = "";

    /**
    * @ORM\Column(type="array", nullable=true);
    */
    protected $theme = null; 
    
    /**
    * @ORM\Column(type="array", nullable=true);
    */
    protected $tilematrixset = null; 

    /**
    * @ORM\Column(type="text", nullable=true);
    */
    protected $username = null;

    /**
    * @ORM\Column(type="text", nullable=true);
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