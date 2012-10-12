<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\WmsBundle\Component\Exception\ParsingException;

use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\WmsBundle\Entity\RequestInformation;

/**
* Class that Parses WMS 1.3.0 GetCapabilies Document 
* @package Mapbender
* @author Karim Malhas <karim@malhas.de>
*/
class Wms130CapabilitiesParser extends WmsCapabilitiesParser {
    
    private $servNsPrefix = "ms";
    
    public function __construct(\DOMDocument $doc){
        parent::__construct($doc);

        foreach( $this->xpath->query('namespace::*', $this->doc->documentElement) as $node ) {
            $nsPrefix = $node->prefix;
            $nsUri = $node->nodeValue;
            if($nsPrefix == "" && $nsUri == "http://www.opengis.net/wms"){
                $nsPrefix = "wms";
            }
            if($nsPrefix != "wms" && $nsPrefix != "xsi" &&  $nsPrefix != "xlink" &&  $nsPrefix != "sld" &&  $nsPrefix != "xml"){
                $this->servNsPrefix = $nsPrefix;
            }
            $this->xpath->registerNamespace($nsPrefix , $nsUri);
        }
    }
    
    public function parse(){
        $wms  = new WmsSource();
        $root = $this->doc->documentElement;

        $this->parseService($wms, $this->getValue("./wms:Service", $root));
        $this->parseCapabilityRequest($wms, $this->getValue("./wms:Capability/wms:Request", $root));
        return $wms;
    }
    
    private function parseService(WmsSource $wms, \DOMElement $serviceElm){

        $wms->setName($this->getValue("./wms:Name/text()", $serviceElm));
        $wms->setTitle($this->getValue("./wms:Title/text()", $serviceElm));
        $wms->setDescription($this->getValue("./wms:Abstract/text()", $serviceElm));
        $wms->setOnlineResource($this->getValue("./wms:OnlineResource/@xlink:href", $serviceElm));
        
        $wms->setFees($this->getValue("./wms:Fees/text()", $serviceElm));
        $wms->setAccessConstraints($this->getValue("./wms:AccessConstraints/text()", $serviceElm));
        $wms->setMaxWidth(intval($this->getValue("./wms:MaxWidth/text()", $serviceElm)));
        $wms->setMaxHeight(intval($this->getValue("./wms:MaxHeight/text()", $serviceElm)));
        $contact = new Contact();
        
        $contact->setPerson($this->getValue("./wms:ContactInformation/wms:ContactPersonPrimary/wms:ContactPerson/text()", $serviceElm));
        $contact->setOrganization($this->getValue("./wms:ContactInformation/wms:ContactPersonPrimary/wms:ContactOrganization/text()", $serviceElm));
        $contact->setPosition($this->getValue("./wms:ContactInformation/wms:ContactPosition/text()", $serviceElm));
        $contact->setAddressType($this->getValue("./wms:ContactInformation/wms:ContactAddress/wms:AddressType/text()", $serviceElm));
        $contact->setAddress($this->getValue("./wms:ContactInformation/wms:ContactAddress/wms:Address/text()", $serviceElm));
        $contact->setAddressCity($this->getValue("./wms:ContactInformation/wms:ContactAddress/wms:City/text()", $serviceElm));
        $contact->setAddressStateOrProvince($this->getValue("./wms:ContactInformation/wms:ContactAddress/wms:StateOrProvince/text()", $serviceElm));
        $contact->setAddressPostCode($this->getValue("./wms:ContactInformation/wms:ContactAddress/wms:PostCode/text()", $serviceElm));
        $contact->setAddressCountry($this->getValue("./wms:ContactInformation/wms:ContactAddress/wms:Country/text()", $serviceElm));
        $contact->setVoiceTelephone($this->getValue("./wms:ContactInformation/wms:ContactVoiceTelephone/text()", $serviceElm));
        $contact->setFacsimileTelephone($this->getValue("./wms:ContactInformation/wms:ContactFacsimileTelephone/text()", $serviceElm));
        $contact->setElectronicMailAddress($this->getValue("./wms:ContactInformation/wms:ContactElectronicMailAddress/text()", $serviceElm));

        $wms->setContact($contact);
    }
    
    private function parseCapabilityRequest(WmsSource $wms, \DOMElement $requestElm){
        $tempEl = $this->getValue("./wms:GetCapabilities", $requestElm);
        if($tempEl !== null) {
            $getCapabilities = $this->parseOperationRequestInformation($tempEl);
            $wms->setGetCapabilities($getCapabilities);
        }
    
        $getMap =  $this->parseOperationRequestInformation($tempEl);
        if($tempEl !== null) {
            $tempEl = $this->getValue("./wms:GetMap", $requestElm);
            $wms->setGetMap($getMap);
        }
    
        $tempEl = $this->getValue("./wms:GetFeatureInfo", $requestElm);
        if($tempEl !== null) {
            $getFeatureInfo =  $this->parseOperationRequestInformation($tempEl);
            $wms->setGetFeatureInfo($getFeatureInfo);
        }
        
        $tempEl = $this->getValue("./sld:GetLegendGraphic", $requestElm);
        if($tempEl !== null) {
            $getLegendGraphic = $this->parseOperationRequestInformation($tempEl);
            $wms->setGetLegendGraphic($getLegendGraphic);
        }
        
        $tempEl = $this->getValue("./sld:DescribeLayer", $requestElm);
        if($tempEl !== null) {
            $describeLayer =  $this->parseOperationRequestInformation($tempEl);
            $wms->setDescribeLayer($describeLayer);
        }
        
        $tempEl = $this->getValue("./".$this->servNsPrefix.":GetStyles", $requestElm);
        if($tempEl !== null) {
            $getStyles = $this->parseOperationRequestInformation($tempEl);
            $wms->setGetStyles($getStyles);
        }
        
        $tempEl = $this->getValue("./".$this->servNsPrefix.":PutStyles", $requestElm);
        if($tempEl !== null) {
            $putStyles =  $this->parseOperationRequestInformation($tempEl);
            $wms->setPutStyles($putStyles);
        }
    }
    
    private function parseOperationRequestInformation(\DOMElement $contextElm){
        $requestImformation = new RequestInformation();
        $tempList = $this->xpath->query("./wms:Format", $contextElm);
        foreach ($tempList as $item) {
            $requestImformation->addFormat($this->getValue("./text()", $item));
        }
        $requestImformation->setHttpGet($this->getValue(
                "./wms:DCPType/wms:HTTP/wms:Get/wms:OnlineResource/@xlink:href",
                $contextElm));
        $requestImformation->setHttpPost($this->getValue(
                "./wms:DCPType/wms:HTTP/wms:Post/wms:OnlineResource/@xlink:href",
                $contextElm));
        
        return $requestImformation;
    }
}

