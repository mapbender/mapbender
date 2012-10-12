<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\WmsBundle\Component\Exception\ParsingException;
use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\CoreBundle\Entity\Contact;

/**
* Class that Parses WMS 1.1.0 GetCapabilies Document 
* @package Mapbender
* @author Karim Malhas <karim@malhas.de>
*/
class Wms110CapabilitiesParser extends WmsCapabilitiesParser {
    
    public function parse(){
        $wms  = new WmsSource();

        $wms->setName($this->getNodeValue("/WMT_MS_Capabilities/Service/Name"));
        $wms->setTitle($this->getNodeValue("/WMT_MS_Capabilities/Service/Title"));
        $wms->setDescription($this->getNodeValue("/WMT_MS_Capabilities/Service/Abstract"));
        $wms->setOnlineResource($this->getNodeValue("/WMT_MS_Capabilities/Service/OnlineResource"));
        $wms->setFees($this->getNodeValue("/WMT_MS_Capabilities/Service/Fees"));
        $wms->setAccessConstraints($this->getNodeValue("/WMT_MS_Capabilities/Service/AccessConstraints"));
        
        $onlineResource = $this->xpath
            ->evaluate("/WMT_MS_Capabilities/Service/OnlineResource")
            ->item(0);
        if($onlineResource){
            $href = $onlineResource->getAttributeNS("http://www.w3.org/1999/xlink","href");
            $wms->setOnlineResource($href);
        }

        $contact = new Contact();
        $wms->setContact($contact);

        $contact->setPerson($this->getNodeValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactPersonPrimary/ContactPerson"));
        $contact->setOrganization($this->getNodeValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactPersonPrimary/ContactOrganization"));
        $contact->setPosition($this->getNodeValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactPosition"));
        $contact->setAddressType($this->getNodeValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactAddress/AddressType"));
        $contact->setAddress($this->getNodeValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactAddress/Address"));
        $contact->setAddressCity($this->getNodeValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactAddress/City"));
        $contact->setAddressStateOrProvince($this->getNodeValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactAddress/StateOrProvince"));
        $contact->setAddressPostCode($this->getNodeValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactAddress/PostCode"));
        $contact->setAddressCountry($this->getNodeValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactAddress/Country"));
        $contact->setVoiceTelephone($this->getNodeValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactVoiceTelephone"));
        $contact->setFacsimileTelephone($this->getNodeValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactFacsimileTelephone"));
        $contact->setElectronicMailAddress($this->getNodeValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactElectronicMailAddress"));

        return $wms;
    }
}
