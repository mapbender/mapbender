<?php
namespace MB\WMSBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use MB\CoreBundle\Entity\Keyword;
use MB\WMSBundle\Entity\GroupLayer;
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
    * @ORM\Column(type="string",nullable="true")
    */
    protected $onlineResource;

    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $ContactPerson;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $ContactPosition;

    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $ContactOrganization;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $ContactVoiceTelephone;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $ContactFacsimileTelephone;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $ContactElectronicMailAddress;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $ContactAddress;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $ContactAddressType;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $ContactAddressCity;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $ContactAddressStateOrProvince;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $ContactAddressPostCode;
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $ContactAddressCountry;

    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $fees = "";
    
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $getMapGet = "";

    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $getMapFormats = "";
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $accessConstraints = "";
    

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
    
    public function setGetMapGet($getMapGet){
        $this->getMapGet = $getMapGet;
    }
    
    public function getGetMapGet(){
        return $this->getMapGet;
    }
    
    public function setGetMapFormats($formats){
        $this->getMapFormats = $formats;
    }
    
    public function getGetMapFormats(){
        return $this->getMapFormats;
    }

    /**
     * returns the default (first) format that a wms supports for getMap requests
    */
    public function getDefaultGetMapFormat(){
        $formats = explode(',',$this->getMapFormats);
        return $formats[0];
    }

   /**
    *
    */ 
    public function getRootLayer(){
        return $this->getLayer()->get(0);
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
        $this->ContactPerson = $contactPerson;
    }

    /**
     * Get ContactPerson
     *
     * @return string 
     */
    public function getContactPerson()
    {
        return $this->ContactPerson;
    }

    /**
     * Set ContactOrganization
     *
     * @param string $contactOrganization
     */
    public function setContactOrganization($contactOrganization)
    {
        $this->ContactOrganization = $contactOrganization;
    }

    /**
     * Get ContactOrganization
     *
     * @return string 
     */
    public function getContactOrganization()
    {
        return $this->ContactOrganization;
    }
    
    /**
     * Set ContactPosition
     *
     * @param string $contactPosition
     */
    public function setContactPosition($contactPosition)
    {
        $this->ContactPosition = $contactPosition;
    }

    /**
     * Get ContactPosition
     *
     * @return string 
     */
    public function getContactPosition()
    {
        return $this->ContactPosition;
    }

    /**
     * Set ContactVoiceTelephone
     *
     * @param string $contactVoiceTelephone
     */
    public function setContactVoiceTelephone($contactVoiceTelephone)
    {
        $this->ContactVoiceTelephone = $contactVoiceTelephone;
    }

    /**
     * Get ContactVoiceTelephone
     *
     * @return string 
     */
    public function getContactVoiceTelephone()
    {
        return $this->ContactVoiceTelephone;
    }

    /**
     * Set ContactFacsimileTelephone
     *
     * @param string $contactFacsimileTelephone
     */
    public function setContactFacsimileTelephone($contactFacsimileTelephone)
    {
        $this->ContactFacsimileTelephone = $contactFacsimileTelephone;
    }

    /**
     * Get ContactFacsimileTelephone
     *
     * @return string 
     */
    public function getContactFacsimileTelephone()
    {
        return $this->ContactFacsimileTelephone;
    }

    /**
     * Set ContactElectronicMailAddress
     *
     * @param string $contactElectronicMailAddress
     */
    public function setContactElectronicMailAddress($contactElectronicMailAddress)
    {
        $this->ContactElectronicMailAddress = $contactElectronicMailAddress;
    }

    /**
     * Get ContactElectronicMailAddress
     *
     * @return string 
     */
    public function getContactElectronicMailAddress()
    {
        return $this->ContactElectronicMailAddress;
    }

    /**
     * Set ContactAddress
     *
     * @param string $contactAddress
     */
    public function setContactAddress($contactAddress)
    {
        $this->ContactAddress = $contactAddress;
    }

    /**
     * Get ContactAddress
     *
     * @return string 
     */
    public function getContactAddress()
    {
        return $this->ContactAddress;
    }

    /**
     * Set ContactAddressType
     *
     * @param string $contactAddressType
     */
    public function setContactAddressType($contactAddressType)
    {
        $this->ContactAddressType = $contactAddressType;
    }

    /**
     * Get ContactAddressType
     *
     * @return string 
     */
    public function getContactAddressType()
    {
        return $this->ContactAddressType;
    }

    /**
     * Set ContactAddressCity
     *
     * @param string $contactAddressCity
     */
    public function setContactAddressCity($contactAddressCity)
    {
        $this->ContactAddressCity = $contactAddressCity;
    }

    /**
     * Get ContactAddressCity
     *
     * @return string 
     */
    public function getContactAddressCity()
    {
        return $this->ContactAddressCity;
    }

    /**
     * Set ContactAddressStateOrProvince
     *
     * @param string $contactAddressStateOrProvince
     */
    public function setContactAddressStateOrProvince($contactAddressStateOrProvince)
    {
        $this->ContactAddressStateOrProvince = $contactAddressStateOrProvince;
    }

    /**
     * Get ContactAddressStateOrProvince
     *
     * @return string 
     */
    public function getContactAddressStateOrProvince()
    {
        return $this->ContactAddressStateOrProvince;
    }

    /**
     * Set ContactAddressPostCode
     *
     * @param string $contactAddressPostCode
     */
    public function setContactAddressPostCode($contactAddressPostCode)
    {
        $this->ContactAddressPostCode = $contactAddressPostCode;
    }

    /**
     * Get ContactAddressPostCode
     *
     * @return string 
     */
    public function getContactAddressPostCode()
    {
        return $this->ContactAddressPostCode;
    }

    /**
     * Set ContactAddressCountry
     *
     * @param string $contactAddressCountry
     */
    public function setContactAddressCountry($contactAddressCountry)
    {
        $this->ContactAddressCountry = $contactAddressCountry;
    }

    /**
     * Get ContactAddressCountry
     *
     * @return string 
     */
    public function getContactAddressCountry()
    {
        return $this->ContactAddressCountry;
    }

}
