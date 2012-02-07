<?php
namespace Mapbender\WmsBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\WmsBundle\Entity\GroupLayer;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
*/
class WMSService extends GroupLayer {

    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $version = "";
    
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $alias = "";

    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $onlineResource;

    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactPerson;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactPosition;

    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactOrganization;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactVoiceTelephone;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactFacsimileTelephone;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactElectronicMailAddress;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactAddress;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactAddressType;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactAddressCity;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactAddressStateOrProvince;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactAddressPostCode;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $contactAddressCountry;

    /**
    * @ORM\Column(type="text", nullable="true")
    */
    protected $fees = "";
    
    /**
    * @ORM\Column(type="text",nullable="true")
    */
    protected $accessConstraints = "";
    
    /**
    * @ORM\Column(type="array",nullable="true")
    */
    protected $exceptionFormats = array();
    
    /**
    * @ORM\Column(type="boolean", nullable="true")
    */
    protected $symbolSupportSLD = false;
    
    /**
    * @ORM\Column(type="boolean", nullable="true")
    */
    protected $symbolUserLayer = false;
    
    /**
    * @ORM\Column(type="boolean", nullable="true")
    */
    protected $symbolUserStyle = false;
    
    /**
    * @ORM\Column(type="boolean", nullable="true")
    */
    protected $symbolRemoteWFS = false;

    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetCapabilitiesGET = "";
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetCapabilitiesPOST = "";

    /**
    * @ORM\Column(type="array", nullable="true")
    */
    protected $requestGetCapabilitiesFormats = array();
    
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetMapGET = "";
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetMapPOST = "";

    /**
    * @ORM\Column(type="array", nullable="true")
    */
    protected $requestGetMapFormats = array();

    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetFeatureInfoGET = "";
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetFeatureInfoPOST = "";

    /**
    * @ORM\Column(type="array", nullable="true")
    */
    protected $requestGetFeatureInfoFormats = array();

    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestDescribeLayerGET = "";
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestDescribeLayerPOST = "";

    /**
    * @ORM\Column(type="array", nullable="true")
    */
    protected $requestDescribeLayerFormats = array();

    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetLegendGraphicGET = "";
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetLegendGraphicPOST = "";

    /**
    * @ORM\Column(type="array", nullable="true")
    */
    protected $requestGetLegendGraphicFormats = array();

    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetStylesGET = "";
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestGetStylesPOST = "";

    /**
    * @ORM\Column(type="array", nullable="true")
    */
    protected $requestGetStylesFormats = array();

    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestPutStylesGET = "";
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $requestPutStylesPOST = "";

    /**
    * @ORM\Column(type="array", nullable="true")
    */
    protected $requestPutStylesFormats = array();

    /**
    * @ORM\Column(type="text", nullable="true");
    */
    protected $username = null;

    /**
    * @ORM\Column(type="text", nullable="true");
    */
    protected $password = null; 


    public function __construct() {
        # calling super  - how to avoid ?
        return parent::__construct();
    }

    public function setVersion($version){
        $this->version = $version;
    }
    
    public function getVersion(){
        return $this->version;
    }
    
    public function setAlias($alias){
        $this->alias = $alias;
    }
    
    public function getAlias(){
        return $this->alias;
    }
    
    public function setFees($fees){
        $this->fees = $fees;
    }
    
    public function getFees(){
        return $this->fees;
    }
    
    public function setAccessConstraints($accessConstraints){
        $this->accessConstraints = $accessConstraints;
    }
    
    public function getAccessConstraints(){
        return $this->accessConstraints;
    }



    public function setExceptionFormats(array $exceptionFormats){
        $this->exceptionFormats = $exceptionFormats;
    }

    public function getExceptionFormats(){
        $this->exceptionFormats = $this->exceptionFormats == ""? array(): $this->exceptionFormats;
        return $this->exceptionFormats;
    }

    
    /**
     * returns the default (first) exceptionFormats that a wms supports 
    */
    public function getDefaultExceptionFormats(){
        return isset($this->exceptionFormats[0])? $this->exceptionFormats[0]: '';
    }
    
    /**
    * returns an array of all locally known ExceptionFormats
    */
    public static function getAllExceptionFormats(){
        return array(
            "application/vnd__ogc__se_xml",
            "application/vnd__ogc__se_inimage",
            "application/vnd__ogc__se_blank"
        );
    }
    
   /**
    *
    */ 
    public function getRootLayer(){
        return $this->getLayer()->get(0);
    }


    /**
     * returns all Layers of the WMS as comma-seperated string so that they can be used in a WMS Request's LAYER parameter
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
     * returns all Layers of the WMS as comma-seperated string so that they can be used in a WMS Request's LAYER parameter
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
     * Set onlineResource
     *
     * @param string $onlineResource
     */
    public function setOnlineResource($onlineResource)
    {
        $this->onlineResource = $onlineResource;
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
     * Set ContactPerson
     *
     * @param string $contactPerson
     */
    public function setContactPerson($contactPerson)
    {
        $this->contactPerson = $contactPerson;
    }

    /**
     * Get ContactPerson
     *
     * @return string 
     */
    public function getContactPerson()
    {
        return $this->contactPerson;
    }

    /**
     * Set ContactOrganization
     *
     * @param string $contactOrganization
     */
    public function setContactOrganization($contactOrganization)
    {
        $this->contactOrganization = $contactOrganization;
    }

    /**
     * Get ContactOrganization
     *
     * @return string 
     */
    public function getContactOrganization()
    {
        return $this->contactOrganization;
    }
    
    /**
     * Set ContactPosition
     *
     * @param string $contactPosition
     */
    public function setContactPosition($contactPosition)
    {
        $this->contactPosition = $contactPosition;
    }

    /**
     * Get ContactPosition
     *
     * @return string 
     */
    public function getContactPosition()
    {
        return $this->contactPosition;
    }

    /**
     * Set ContactVoiceTelephone
     *
     * @param string $contactVoiceTelephone
     */
    public function setContactVoiceTelephone($contactVoiceTelephone)
    {
        $this->contactVoiceTelephone = $contactVoiceTelephone;
    }

    /**
     * Get ContactVoiceTelephone
     *
     * @return string 
     */
    public function getContactVoiceTelephone()
    {
        return $this->contactVoiceTelephone;
    }

    /**
     * Set ContactFacsimileTelephone
     *
     * @param string $contactFacsimileTelephone
     */
    public function setContactFacsimileTelephone($contactFacsimileTelephone)
    {
        $this->contactFacsimileTelephone = $contactFacsimileTelephone;
    }

    /**
     * Get ContactFacsimileTelephone
     *
     * @return string 
     */
    public function getContactFacsimileTelephone()
    {
        return $this->contactFacsimileTelephone;
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
     * Set ContactAddress
     *
     * @param string $contactAddress
     */
    public function setContactAddress($contactAddress)
    {
        $this->contactAddress = $contactAddress;
    }

    /**
     * Get ContactAddress
     *
     * @return string 
     */
    public function getContactAddress()
    {
        return $this->contactAddress;
    }

    /**
     * Set ContactAddressType
     *
     * @param string $contactAddressType
     */
    public function setContactAddressType($contactAddressType)
    {
        $this->contactAddressType = $contactAddressType;
    }

    /**
     * Get ContactAddressType
     *
     * @return string 
     */
    public function getContactAddressType()
    {
        return $this->contactAddressType;
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
     * Set ContactAddressStateOrProvince
     *
     * @param string $contactAddressStateOrProvince
     */
    public function setContactAddressStateOrProvince($contactAddressStateOrProvince)
    {
        $this->contactAddressStateOrProvince = $contactAddressStateOrProvince;
    }

    /**
     * Get ContactAddressStateOrProvince
     *
     * @return string 
     */
    public function getContactAddressStateOrProvince()
    {
        return $this->contactAddressStateOrProvince;
    }

    /**
     * Set ContactAddressPostCode
     *
     * @param string $contactAddressPostCode
     */
    public function setContactAddressPostCode($contactAddressPostCode)
    {
        $this->contactAddressPostCode = $contactAddressPostCode;
    }

    /**
     * Get ContactAddressPostCode
     *
     * @return string 
     */
    public function getContactAddressPostCode()
    {
        return $this->contactAddressPostCode;
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
    
    public function setRequestGetCapabilitiesGET($requestGetCapabilitiesGET){
        $this->requestGetCapabilitiesGET = $requestGetCapabilitiesGET;
    }
    
    public function getRequestGetCapabilitiesGET(){
        return $this->requestGetCapabilitiesGET;
    }

    public function setRequestGetCapabilitiesPOST($requestGetCapabilitiesPOST){
        $this->requestGetCapabilitiesPOST = $requestGetCapabilitiesPOST;
    }
    
    public function getRequestGetCapabilitiesPOST(){
        return $this->requestGetCapabilitiesPOST;
    }
    
    public function setRequestGetCapabilitiesFormats(array $formats){
        $this->requestGetCapabilitiesFormats = $formats;
    }
    
    public function getRequestGetCapabilitiesFormats(){
        return $this->requestGetCapabilitiesFormats;
    }
    
    /**
    * returns an array of all locally known RequestCapabilitiesFormats
    */
    public static function getAllRequestGetCapabilitiesFormats(){
        return array(
            "application/vnd__ogc__wms_xml",
        );
    }

    /**
     * returns the default (first) format that a wms supports for getCapabilities requests
    */
    public function getDefaultRequestGetCapabilitiesFormat(){
        return isset($this->requestGetCapabilitiesFormats[0])? $this->requestGetCapabilitiesFormats[0]: '';
    }




    public function setRequestGetMapGET($requestGetMapGET){
        $this->requestGetMapGET = $requestGetMapGET;
    }
    
    public function getRequestGetMapGET(){
        return $this->requestGetMapGET;
    }
    

    /*
    *
    * returns a the getMapUrl with added username and password like "http://user:password@host/
    */
    public function getAuthRequestGetMapGET(){
        if(!$this->username){
            return $this->requestGetMapGET;
        }

        $authString = $this->username .":".$this->password . "@";
        //die($authString."- " .$this->requestGetMapGET);
        $authRequestUrl =  preg_replace("/^https*:\/\//", $authString, $this->requestGetMapGET );
        return $authRequestUrl;
    }
    
    public function setRequestGetMapPOST($requestGetMapPOST){
        $this->requestGetMapPOST = $requestGetMapPOST;
    }
    
    public function getRequestGetMapPOST(){
        return $this->requestGetMapPOST;
    }
    
    public function setRequestGetMapFormats(array $formats){
        $this->requestGetMapFormats = $formats;
    }
    
    public function getRequestGetMapFormats(){
        return $this->requestGetMapFormats;
    }
    
    /**
    * returns an array of all locally known RequestMapFormats
    */
    public static function getAllRequestGetMapFormats(){
        return array(
            "image/png",
            "image/gif",
            "image/png; mode=24bit",
            "image/jpeg",
            "image/wbmp",
            "image/tiff"
        );
    }
    
    /**
     * returns the default (first) format that a wms supports for getMap requests
    */
    public function getDefaultRequestGetMapFormat(){
        return isset($this->requestGetMapFormats[0])? $this->requestGetMapFormats[0]: '';
    }



    public function setRequestGetFeatureInfoGET($requestGetFeatureInfoGET){
        $this->requestGetFeatureInfoGET = $requestGetFeatureInfoGET;
    }
    
    public function getRequestGetFeatureInfoGET(){
        return $this->requestGetFeatureInfoGET;
    }
    
    public function setRequestGetFeatureInfoPOST($requestGetFeatureInfoPOST){
        $this->requestGetFeatureInfoPOST = $requestGetFeatureInfoPOST;
    }
    
    public function getRequestGetFeatureInfoPOST(){
        return $this->requestGetFeatureInfoPOST;
    }
    
    public function setRequestGetFeatureInfoFormats(array $formats){
        $this->requestGetFeatureInfoFormats = $formats;
    }
    
    public function getRequestGetFeatureInfoFormats(){
        return $this->requestGetFeatureInfoFormats;
    }
    
    /**
    * returns an array of all locally known RequestGetFeatureInfoFormats
    */
    public static function getAllRequestGetFeatureinfoFormats(){
        return array(
            "text/html",
            "text/plain",
            "application/vnd__ogc__gml"
        );
    }
    
    /**
     * returns the default (first) format that a wms supports for getFeatureInfo requests
    */
    public function getDefaultRequestGetFeatureInfoFormat(){
        return isset($this->requestGetFeatureInfoFormats[0])? $this->requestGetFeatureInfoFormats[0]: '';
    }



    public function setRequestDescribeLayerGET($requestDescribeLayerGET){
        $this->requestDescribeLayerGET = $requestDescribeLayerGET;
    }
    
    public function getRequestDescribeLayerGET(){
        return $this->requestDescribeLayerGET;
    }
    
    public function setRequestDescribeLayerPOST($requestDescribeLayerPOST){
        $this->requestDescribeLayerPOST = $requestDescribeLayerPOST;
    }
    
    public function getRequestDescribeLayerPOST(){
        return $this->requestDescribeLayerPOST;
    }
    
    public function setRequestDescribeLayerFormats(array $formats){
        $this->requestDescribeLayerFormats = $formats;
    }
    
    public function getRequestDescribeLayerFormats(){
        return $this->requestDescribeLayerFormats;
    }
    
    /**
    * returns an array of all locally known RequestGetFeatureInfoFormats
    */
    public static function getAllRequestDescribeLayerFormats(){
        return array(
            "text/plain",
            "text/xml",
            "application/vnd__ogc__gml",
        );
    }
    
    /**
     * returns the default (first) format that a wms supports for getDescribeLayer requests
    */
    public function getDefaultRequestDescribeLayerFormat(){
        return isset($this->requestDescribeLayerFormats[0])? $this->requestDescribeLayerFormats[0]: '';
    }



    public function setRequestGetLegendGraphicGET($requestGetLegendGraphicGET){
        $this->requestGetLegendGraphicGET = $requestGetLegendGraphicGET;
    }
    
    public function getRequestGetLegendGraphicGET(){
        return $this->requestGetLegendGraphicGET;
    }
    
    public function setRequestGetLegendGraphicPOST($requestGetLegendGraphicPOST){
        $this->requestGetLegendGraphicPOST = $requestGetLegendGraphicPOST;
    }
    
    public function getRequestGetLegendGraphicPOST(){
        return $this->requestGetLegendGraphicPOST;
    }
    
    public function setRequestGetLegendGraphicFormats(array $formats){
        $this->requestGetLegendGraphicFormats = $formats;
    }
    
    public function getRequestGetLegendGraphicFormats(){
    
        return $this->requestGetLegendGraphicFormats;
    }
    
    /**
    * returns an array of all locally known RequestGetLegendGraphicsFormats
    */
    public static function getAllRequestGetLegendGraphicFormats(){
        return array(
            "image/png",
            "image/gif",
            "image/png; mode=24bit",
            "image/jpeg",
            "image/wbmp",
            "image/tiff"
        );
    }
    
    /**
     * returns the default (first) format that a wms supports for getLegendGraphic requests
    */
    public function getDefaultRequestGetLegendGraphicFormat(){
        return isset($this->requestGetLegendGraphicFormats[0])? $this->requestGetLegendGraphicFormats[0]: '';
    }



    public function setRequestGetStylesGET($requestGetStylesGET){
        $this->requestGetStylesGET = $requestGetStylesGET;
    }
    
    public function getRequestGetStylesGET(){
        return $this->requestGetStylesGET;
    }
    
    public function setRequestGetStylesPOST($requestGetStylesPOST){
        $this->requestGetStylesPOST = $requestGetStylesPOST;
    }
    
    public function getRequestGetStylesPOST(){
        return $this->requestGetStylesPOST;
    }
    
    public function setRequestGetStylesFormats(array $formats){
        $this->requestGetStylesFormats = $formats;
    }
    
    public function getRequestGetStylesFormats(){
        return $this->requestGetStylesFormats;
    }
    
    /**
    * returns an array of all locally known RequestGetStylesFormats
    */
    public static function getAllRequestGetStylesFormats(){
        return array(
        );
    }
    
    /**
     * returns the default (first) format that a wms supports for getStyles requests
    */
    public function getDefaultRequestGetStylesFormat(){
        return isset($this->requestGetStylesFormats[0])? $this->requestGetStylesFormats[0]: '';
    }



    public function setRequestPutStylesGET($requestPutStylesGET){
        $this->requestPutStylesGET = $requestPutStylesGET;
    }
    
    public function getRequestPutStylesGET(){
        return $this->requestPutStylesGET;
    }
    
    public function setRequestPutStylesPOST($requestPutStylesPOST){
        $this->requestPutStylesPOST = $requestPutStylesPOST;
    }
    
    public function getRequestPutStylesPOST(){
        return $this->requestPutStylesPOST;
    }
    
    public function setRequestPutStylesFormats(array $formats){
        $this->requestPutStylesFormats = $formats;
    }
    
    public function getRequestPutStylesFormats(){
        return $this->requestPutStylesFormats;
    }
    
    /**
    * returns an array of all locally known RequestPutStylesFormats
    */
    public static function getAllRequestPutStylesFormats(){
        return array(
        );
    }
    
    /**
     * returns the default (first) format that a wms supports for putStyles requests
    */
    public function getDefaultRequestPutStylesFormat(){
        return isset($this->requestPutStylesFormats[0])? $this->requestPutStylesFormats[0]: '';
    }

    /**
     * Set symbolSupportSLD
     *
     * @param boolean $symbolSupportSLD
     */
    public function setSymbolSupportSLD($symbolSupportSLD)
    {
        $this->symbolSupportSLD = $symbolSupportSLD;
    }

    /**
     * Get symbolSupportSLD
     *
     * @return boolean 
     */
    public function getSymbolSupportSLD()
    {
        return $this->symbolSupportSLD;
    }

    /**
     * Set symbolUserLayer
     *
     * @param boolean $symbolUserLayer
     */
    public function setSymbolUserLayer($symbolUserLayer)
    {
        $this->symbolUserLayer = $symbolUserLayer;
    }

    /**
     * Get symbolUserLayer
     *
     * @return boolean 
     */
    public function getSymbolUserLayer()
    {
        return $this->symbolUserLayer;
    }

    /**
     * Set symbolUserStyle
     *
     * @param boolean $symbolUserStyle
     */
    public function setSymbolUserStyle($symbolUserStyle)
    {
        $this->symbolUserStyle = $symbolUserStyle;
    }

    /**
     * Get symbolUserStyle
     *
     * @return boolean 
     */
    public function getSymbolUserStyle()
    {
        return $this->symbolUserStyle;
    }

    /**
     * Set symbolRemoteWFS
     *
     * @param boolean $symbolRemoteWFS
     */
    public function setSymbolRemoteWFS($symbolRemoteWFS)
    {
        $this->symbolRemoteWFS = $symbolRemoteWFS;
    }

    /**
     * Get symbolRemoteWFS
     *
     * @return boolean 
     */
    public function getSymbolRemoteWFS()
    {
        return $this->symbolRemoteWFS;
    }
    
    public function getUsername (){
        return $this->username ;
    }
    
    public function setUsername ($username ){
        $this->username  = $username ;
    }

    public function getPassword (){
        return $this->password ;
    }
    
    public function setPassword ($password ){
        $this->password  = $password ;
    }

}
