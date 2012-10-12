<?php
/*
* @package mapbender_wms_testing
* @author Karim Malhas <karim@malhas.de>
*/

use Mapbender\WmsBundle\Component\WmsCapabilitiesParser;
use Mapbender\WmsBundle\Component\Exception\ParsingException;
use Mapbender\WmsBundle\Entity\WmsSource;

class Wms110CapabilitiesParserTest extends PHPUnit_Framework_TestCase {


    public function testParseServiceSection(){

        $xml  = '<?xml version="1.0" encoding="utf-8" ?>';
        $xml .= '<WMT_MS_Capabilities version="1.1.0">';
        $xml .= '<Service>';
        $xml .= '<Name>thename</Name>';
        $xml .= '<Title>thetitle</Title>';
        $xml .= '<Abstract>theabstract</Abstract>';
        $xml .= '<OnlineResource xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="theonlineResource" />';
        $xml .= '<Fees>thefees</Fees>';
        $xml .= '<AccessConstraints>theaccessconstraints</AccessConstraints>';
        $xml .= '</Service>';
        $xml .= '</WMT_MS_Capabilities>';

        $parser = WmsCapabilitiesParser::create($xml);

        $wms = $parser->parse();
        $this->assertEquals("thename",$wms->getName());
        $this->assertEquals("thetitle",$wms->getTitle());
        $this->assertEquals("theabstract",$wms->getDescription());
        $this->assertEquals("theonlineResource",$wms->getOnlineResource());
        $this->assertEquals("thefees",$wms->getfees());
        $this->assertEquals("theaccessconstraints",$wms->getAccessConstraints());
    
    }

    public function testParseContactSection(){
        $xml  = '<?xml version="1.0" encoding="utf-8" ?>';
        $xml .= '<WMT_MS_Capabilities version="1.1.0">';
        $xml .= '<Service><ContactInformation>';

        $xml .= '<ContactPersonPrimary>';
        $xml .= '   <ContactPerson>thecontactperson</ContactPerson>';
        $xml .= '   <ContactOrganization>thecontactorganization</ContactOrganization>';
        $xml .= '</ContactPersonPrimary>';
        
        $xml .= '<ContactPosition>thecontactposition</ContactPosition>';
        
        $xml .= '<ContactAddress>';
        $xml .= '   <AddressType>theaddresstype</AddressType>';
        $xml .= '   <Address>theaddress</Address>';
        $xml .= '   <City>thecity</City>';
        $xml .= '   <StateOrProvince>thestate</StateOrProvince>';
        $xml .= '   <PostCode>thepostcode</PostCode>';
        $xml .= '   <Country>thecountry</Country>';
        $xml .= '</ContactAddress>';

        $xml .= '<ContactVoiceTelephone>thevoice</ContactVoiceTelephone>';
        $xml .= '<ContactFacsimileTelephone>thefax</ContactFacsimileTelephone>';
        $xml .= '<ContactElectronicMailAddress>theemail</ContactElectronicMailAddress>';
        $xml .= '</ContactInformation></Service>';
        $xml .= '</WMT_MS_Capabilities>';
        
        $parser = WmsCapabilitiesParser::create($xml);

        $wms = $parser->parse();
        $contact = $wms->getContact();
        $this->assertEquals("thecontactperson",$contact->getPerson());
        $this->assertEquals("thecontactorganization",$contact->getOrganization());
        $this->assertEquals("thecontactposition",$contact->getPosition());
        $this->assertEquals("theaddresstype",$contact->getAddressType());
        $this->assertEquals("theaddress",$contact->getAddress());
        $this->assertEquals("thecity",$contact->getAddressCity());
        $this->assertEquals("thestate",$contact->getAddressStateOrProvince());
        $this->assertEquals("thepostcode",$contact->getAddressPostCode());
        $this->assertEquals("thecountry",$contact->getAddressCountry());
        $this->assertEquals("thevoice",$contact->getVoiceTelephone());
        $this->assertEquals("thefax",$contact->getFacsimileTelephone());
        $this->assertEquals("theemail",$contact->getElectronicMailAddress());

    }

}

