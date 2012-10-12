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

        $wms->setName($this->getValue("/WMT_MS_Capabilities/Service/Name/text()"));
        $wms->setTitle($this->getValue("/WMT_MS_Capabilities/Service/Title/text()"));
        $wms->setDescription($this->getValue("/WMT_MS_Capabilities/Service/Abstract/text()"));
        $wms->setOnlineResource($this->getValue("/WMT_MS_Capabilities/Service/OnlineResource/text()"));
        $wms->setFees($this->getValue("/WMT_MS_Capabilities/Service/Fees/text()"));
        $wms->setAccessConstraints($this->getValue("/WMT_MS_Capabilities/Service/AccessConstraints/text()"));
        
        $onlineResource = $this->xpath
            ->evaluate("/WMT_MS_Capabilities/Service/OnlineResource")
            ->item(0);
        if($onlineResource){
            $href = $onlineResource->getAttributeNS("http://www.w3.org/1999/xlink","href");
            $wms->setOnlineResource($href);
        }

        $contact = new Contact();
        $wms->setContact($contact);

        $contact->setPerson($this->getValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactPersonPrimary/ContactPerson/text()"));
        $contact->setOrganization($this->getValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactPersonPrimary/ContactOrganization/text()"));
        $contact->setPosition($this->getValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactPosition/text()"));
        $contact->setAddressType($this->getValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactAddress/AddressType/text()"));
        $contact->setAddress($this->getValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactAddress/Address/text()"));
        $contact->setAddressCity($this->getValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactAddress/City/text()"));
        $contact->setAddressStateOrProvince($this->getValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactAddress/StateOrProvince/text()"));
        $contact->setAddressPostCode($this->getValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactAddress/PostCode/text()"));
        $contact->setAddressCountry($this->getValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactAddress/Country/text()"));
        $contact->setVoiceTelephone($this->getValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactVoiceTelephone/text()"));
        $contact->setFacsimileTelephone($this->getValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactFacsimileTelephone/text()"));
        $contact->setElectronicMailAddress($this->getValue("/WMT_MS_Capabilities/Service/ContactInformation/ContactElectronicMailAddress/text()"));

        return $wms;
    }
}
